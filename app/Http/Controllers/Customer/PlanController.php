<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Feature;
use App\Models\Invoice;
use Illuminate\Http\Request;
use ChargeBee\ChargeBee\Models\HostedPage;
use ChargeBee\ChargeBee\Models\Subscription;
use App\Models\Order;
use App\Models\Subscription as UserSubscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionCancellationMail;
use App\Mail\OrderCreatedMail;
use Log;

class PlanController extends Controller
{
    public function index()
    {
        $getMostlyUsed = Plan::getMostlyUsed();
        $plans = Plan::with('features')->where('is_active', true)->get();
        return view('customer.pricing.pricing', compact('plans', 'getMostlyUsed'));
    }

    public function show($id)
    {
        $plan = Plan::with('features')->findOrFail($id);
        return response()->json(['plan' => $plan]);
    }

    public function getPlanDetails($id)
    {
        $plan = Plan::with('features')->findOrFail($id);
        return view('customer.pricing.plan-details', compact('plan'));
    }
    // add chargebee card change details
    public function chargeBeeChangeCardDetails(Request $request) {}
    public function initiateSubscription(Request $request, $planId)
    {
        $plan = Plan::findOrFail($planId);
        try {
            $user = auth()->user();
            $charge_customer_id = null;
            if ($request->has('order_id')) {
                $order = Order::findOrFail($request->order_id);
                $charge_customer_id = $order->chargebee_customer_id ?? null;
            }
            // dd($charge_customer_id);
            if ($charge_customer_id == null) {

                // Create hosted page for subscription
                $result = HostedPage::checkoutNewForItems([
                    "subscription_items" => [
                        [
                            "item_price_id" => $plan->chargebee_plan_id,
                            "quantity" => $request->session()->has('order_info') ? $request->session()->get('order_info')['total_inboxes'] : 1
                        ]
                    ],
                    "customer" => [
                        "email" => $user->email,
                        "first_name" => $user->name,
                        // "last_name" => "xcxc",
                        "phone" => $user->phone,
                    ],
                    "billing_address" => [
                        "first_name" => $user->name,
                        // "last_name" => "xcxc",
                        // "line1" => "Address Line 1", // Default value
                        // "city" => "City", // Default value 
                        // "state" => "State", // Default value
                        // "zip" => "12345", // Default value
                        // "country" => "US" // Default value
                    ],
                    "redirect_url" => route('customer.subscription.success'),
                    "cancel_url" => route('customer.subscription.cancel')
                ]);
            } else {
                // payment done with old customer
                $result = HostedPage::checkoutNewForItems([
                    "subscription_items" => [
                        [
                            "item_price_id" => $plan->chargebee_plan_id,
                            "quantity" => $request->session()->has('order_info') ? $request->session()->get('order_info')['total_inboxes'] : 1
                        ]
                    ],
                    "customer" => [
                        "id" => $charge_customer_id,
                    ],
                    "billing_address" => [
                        "first_name" => $user->first_name,
                        "last_name" => "xcxc",
                        "line1" => "Address Line 1", // Default value
                        "city" => "City", // Default value
                        "state" => "State", // Default value
                        "zip" => "12345", // Default value
                        "country" => "US" // Default value
                    ],
                    "redirect_url" => route('customer.subscription.success'),
                    "cancel_url" => route('customer.subscription.cancel')
                ]);
            }


            $hostedPage = $result->hostedPage();

            return response()->json([
                'success' => true,
                'hosted_page_url' => $hostedPage->url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate subscription: ' . $e->getMessage()
            ], 500);
        }
    }
    public function initiateSubscriptionOld(Request $request, $planId)
    {
        $plan = Plan::findOrFail($planId);
        try {
            $user = auth()->user();

            // Create hosted page for subscription
            $result = HostedPage::checkoutNewForItems([
                "subscription_items" => [
                    [
                        "item_price_id" => $plan->chargebee_plan_id,
                        "quantity" => 1
                    ]
                ],
                "customer" => [
                    "email" => $user->email,
                    "first_name" => $user->first_name,
                    "last_name" => $user->last_name,
                    "phone" => $user->phone,
                ],
                "redirect_url" => route('customer.subscription.success'),
                "cancel_url" => route('customer.subscription.cancel')
            ]);


            $hostedPage = $result->hostedPage();

            return response()->json([
                'success' => true,
                'hosted_page_url' => $hostedPage->url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate subscription: ' . $e->getMessage()
            ], 500);
        }
    }

    public function subscriptionSuccess(Request $request)
    {
        try {
            $hostedPageId = $request->input('id');
            // get session order_info
            if (!$hostedPageId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing hosted page ID in request.'
                ]);
            }

            $result = \ChargeBee\ChargeBee\Models\HostedPage::retrieve($hostedPageId);
            $hostedPage = $result->hostedPage();
            $content = $hostedPage->content();

            $subscription = $content->subscription() ?? null;
            $customer = $content->customer() ?? null;
            $invoice = $content->invoice() ?? null;
            $plan_id = null;
            $charge_plan_id = null;

            if ($subscription && $subscription->subscriptionItems) {
                $charge_plan_id = $subscription->subscriptionItems[0]->itemPriceId ?? null;
            }

            $plan = Plan::where('chargebee_plan_id', $charge_plan_id)->first();
            if ($plan) {
                $plan_id = $plan->id;
            }

            $user = auth()->user();

            if (!$subscription || !$customer || !$invoice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing subscription, customer, or invoice data.'
                ]);
            }

            $meta_json = json_encode([
                'invoice' => $invoice->getValues(),
                'customer' => $customer->getValues(),
                'subscription' => $subscription->getValues(),
            ]);

            // Create or update order
            $order = Order::firstOrCreate(
                ['chargebee_invoice_id' => $invoice->id],
                [
                    'user_id' => $user->id,
                    'plan_id' => $plan_id,
                    'chargebee_customer_id' => $customer->id,
                    'chargebee_subscription_id' => $subscription->id,
                    'amount' => ($invoice->amountPaid ?? 0) / 100,
                    'status' => $invoice->status,
                    'currency' => $invoice->currencyCode,
                    'paid_at' => Carbon::createFromTimestamp($invoice->paidAt)->toDateTimeString(),
                    'meta' => $meta_json,
                ]
            );
            $order_info = $request->session()->get('order_info');
            // first check if order_info is not null
            if (!is_null($order_info)) {
                // save data on reorder_info table
                $order->reorderInfo()->create([
                    'user_id' => $user->id,
                    'plan_id' => $plan_id,
                    'forwarding_url' => $order_info['forwarding_url'],
                    // other_platform
                    'other_platform' => $order_info['other_platform'] ?? null,
                    // tutorial_section
                    'tutorial_section' => $order_info['tutorial_section'] ?? null,
                    'hosting_platform' => $order_info['hosting_platform'],
                    'other_platform' => $order_info['other_platform'] ?? null,
                    'backup_codes' => $order_info['backup_codes'] ?? null,
                    'bison_url' => $order_info['bison_url'] ?? null,
                    'bison_workspace' => $order_info['bison_workspace'] ?? null,
                    'platform_login' => $order_info['platform_login'],
                    'platform_password' => $order_info['platform_password'],
                    'domains' => $order_info['domains'],
                    'sending_platform' => $order_info['sending_platform'],
                    'sequencer_login' => $order_info['sequencer_login'],
                    'sequencer_password' => $order_info['sequencer_password'],
                    'total_inboxes' => $order_info['total_inboxes'],
                    'inboxes_per_domain' => $order_info['inboxes_per_domain'],
                    'first_name' => $order_info['first_name'],
                    'last_name' => $order_info['last_name'],
                    'prefix_variant_1' => $order_info['prefix_variant_1'],
                    'prefix_variant_2' => $order_info['prefix_variant_2'],
                    'persona_password' => $order_info['persona_password'],
                    'profile_picture_link' => $order_info['profile_picture_link'] ?? null,
                    'email_persona_password' => $order_info['email_persona_password'] ?? null,
                    'email_persona_picture_link' => $order_info['email_persona_picture_link'] ?? null,
                    'master_inbox_email' => $order_info['master_inbox_email'] ?? null,
                    'additional_info' => $order_info['additional_info'] ?? null,
                ]);
            }
        
            // Create or update invoice
            $existingInvoice = Invoice::where('chargebee_invoice_id', $invoice->id)->first();

            if ($existingInvoice) {
                $existingInvoice->update([
                    'chargebee_customer_id' => $customer->id,
                    'chargebee_subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'plan_id' => $plan_id,
                    'order_id' => $order->id,
                    'amount' => ($invoice->amountPaid ?? 0) / 100,
                    'status' => $invoice->status,
                    'paid_at' => Carbon::createFromTimestamp($invoice->paidAt)->toDateTimeString(),
                    'metadata' => $meta_json,
                ]);
            } else {
                Invoice::create([
                    'chargebee_invoice_id' => $invoice->id,
                    'chargebee_customer_id' => $customer->id,
                    'chargebee_subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'plan_id' => $plan_id,
                    'order_id' => $order->id,
                    'amount' => ($invoice->amountPaid ?? 0) / 100,
                    'status' => $invoice->status,
                    'paid_at' => Carbon::createFromTimestamp($invoice->paidAt)->toDateTimeString(),
                    'metadata' => $meta_json,
                ]);
            }

            // Create or update subscription
            UserSubscription::updateOrCreate(
                ['chargebee_subscription_id' => $subscription->id],
                [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'chargebee_invoice_id' => $invoice->id,
                    'chargebee_customer_id' => $customer->id,
                    'plan_id' => $plan_id,
                    'status' => $subscription->status,
                    'start_date' => Carbon::now(),
                    'meta' => $meta_json,
                    // subscription last_billing_date, next_billing_date
                    'last_billing_date' => Carbon::createFromTimestamp($invoice->paidAt)->toDateTimeString(),
                    'next_billing_date' => Carbon::createFromTimestamp($invoice->paidAt)->addMonth()->addDay()->toDateTimeString(),
                ]
            );

            // Update user's subscription status
            $user->update([
                'subscription_id' => $subscription->id,
                'subscription_status' => $subscription->status,
                'plan_id' => $plan_id,
                'chargebee_customer_id' => $customer->id,
            ]);
            // dd("success");

            // destroy session order_info
            if ($request->session()->has('order_info')) {
                $request->session()->forget('order_info');
            }

            // Send email notifications
            try {
                // Send email to user
                // Mail::to($user->email)
                //     ->queue(new OrderCreatedMail(
                //         $order,
                //         $user,
                //         false
                //     ));

                // Send email to admin
                // Mail::to(config('mail.admin_address', 'admin@example.com'))
                //     ->queue(new OrderCreatedMail(
                //         $order,
                //         $user,
                //         true
                //     ));
            } catch (\Exception $e) {
                // \Log::error('Failed to send order creation emails: ' . $e->getMessage());
                // Continue execution since the order was already created
            }

            // Redirect to success page with subscription details
            return view('customer.plans.subscription-success', [
                'subscription_id' => $subscription->id,
                'order_id' => $order->id,
                'plan' => $plan,
                'amount' => ($invoice->amountPaid ?? 0) / 100,
            ]);
        } catch (\Exception $e) {
            \Log::error('Subscription confirmation failed: ' . $e->getMessage());
            return view('customer.plans.subscription-failed')->with('error', 'Failed to confirm subscription: ' . $e->getMessage());
        }
    }

    // Cancel Subscription Method
    public function cancelSubscription(Request $request)
    {
        $subscriptionId = $request->get('subscription_id'); // Get subscription ID from request

        try {
            // Cancel subscription
            $result = Subscription::cancel($subscriptionId, [
                'cancel_at_end_of_period' => true, // Cancel immediately or at the end of the billing period
            ]);

            // Check if the subscription was successfully canceled
            $subscription = $result->subscription();
            if ($subscription->status === 'cancelled') {
                return response()->json([
                    'success' => true,
                    'message' => 'Subscription has been canceled successfully.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel the subscription.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error canceling subscription: ' . $e->getMessage(),
            ]);
        }
    }

    public function subscriptionCancelProcess(Request $request)
    {
        if ($request->remove_accounts == null || $request->remove_accounts == false) {
            $request->remove_accounts = 0;
        } else {
            $request->remove_accounts = true;
        }
        $request->validate([
            'chargebee_subscription_id' => 'required|string',
            'reason' => 'required|string',
            'remove_accounts' => 'required',
        ]);

        $user = auth()->user();
        $subscription = UserSubscription::where('chargebee_subscription_id', $request->chargebee_subscription_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription || $subscription->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 404);
        }

        try {
            $result = \ChargeBee\ChargeBee\Models\Subscription::cancelForItems($request->chargebee_subscription_id, [
                "end_of_term" => false,
                "credit_option" => "none",
                "unbilled_charges_option" => "delete",
                "account_receivables_handling" => "no_action"
            ]);

            $subscriptionData = $result->subscription();
            $invoiceData = $result->invoice();
            $customerData = $result->customer();

            if ($result->subscription()->status === 'cancelled') {
                // Update subscription status and end date
                $subscription->update([
                    'status' => 'cancelled',
                    'cancellation_at' => now(),
                    'reason' => $request->reason,
                    'end_date' => $this->getEndExpiryDate($subscription->start_date),
                    'next_billing_date' => null,
                ]);

                // Update user status
                $user->update([
                    'subscription_status' => 'cancelled',
                    'subscription_id' => null,
                    'plan_id' => null
                ]);

                // Update order status
                $order = Order::where('chargebee_subscription_id', $request->chargebee_subscription_id)->first();
                if ($order) {
                    $order->update([
                        'status_manage_by_admin' => 'expired',
                    ]);
                }

                try {
                    // Send email to user
                    // Mail::to($user->email)
                    //     ->queue(new SubscriptionCancellationMail(
                    //         $subscription, 
                    //         $user, 
                    //         $request->reason
                    //     ));

                    // Send email to admin
                    // Mail::to(config('mail.admin_address', 'admin@example.com'))
                    //     ->queue(new SubscriptionCancellationMail(
                    //         $subscription, 
                    //         $user, 
                    //         $request->reason,
                    //         true
                    //     ));
                } catch (\Exception $e) {
                    // \Log::error('Failed to send subscription cancellation emails: ' . $e->getMessage());
                    // Continue execution since the subscription was already cancelled
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription cancelled successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription in payment gateway'
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Error cancelling subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: ' . $e->getMessage()
            ], 500);
        }
    }
    // getEndExpiryDate from start Date
    public function getEndExpiryDate($startDate)
    {
        // $startDate = '2025-04-21 07:02:48'; // Example start date
        $currentDate = Carbon::now(); // Get current date
        $startDateCarbon = Carbon::parse($startDate);

        // Calculate the difference in months
        $monthsToAdd = $currentDate->diffInMonths($startDateCarbon); // Difference in months

        // Calculate the next expiry date
        $expiryDate = $startDateCarbon
            ->addMonths(++$monthsToAdd) // Add the dynamic number of months
            ->subDay()  // Subtract 1 day
            ->format('Y-m-d H:i:s');

        return $expiryDate; // Outputs the dynamically calculated expiry date
    }
    public function upgradePlan(Request $request, $planId)
    {
        $user = auth()->user();
        $newPlan = Plan::with('features')->findOrFail($planId);

        if (!$user->subscription || $user->subscription_status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 400);
        }

        try {
            // First cancel the current subscription
            $cancelResult = \ChargeBee\ChargeBee\Models\Subscription::cancel($user->subscription_id, [
                "end_of_term" => false
            ]);

            if ($cancelResult->subscription()->status === 'cancelled') {
                // Create new subscription with the new plan
                return $this->initiateSubscription($request, $planId);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel current subscription'
            ], 500);
        } catch (\Exception $e) {
            \Log::error('Error upgrading plan: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to upgrade plan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Subscription Cancel Method
    public function subscriptionCancel(Request $request)
    {
        return redirect('/plans')->with('error', 'Subscription process was cancelled.');
    }

    // Refund Payment Method
    public function refundPayment(Request $request)
    {
        $invoiceId = $request->get('invoice_id'); // Get invoice ID from request

        try {
            // Fetch the invoice to check if it's refundable
            $invoiceResult = Invoice::retrieve($invoiceId);
            $invoice = $invoiceResult->invoice();

            // Check if the invoice can be refunded (e.g., it's not already refunded)
            if ($invoice->status !== 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice is not in paid status, refund cannot be processed.',
                ]);
            }

            // Refund the payment (you can specify the amount to refund)
            $refundAmount = $invoice->amount_paid; // Full refund
            $result = Refund::create([
                'invoice_id' => $invoiceId,
                'amount' => $refundAmount, // Full refund or specify partial amount
                'gateway' => $invoice->gateway,
                'payment_source_id' => $invoice->payment_source_id,
                'refund_reason' => 'Requested by customer', // Customize the reason
            ]);

            // Check if refund was successful
            $refund = $result->refund();
            if ($refund->status === 'successful') {
                return response()->json([
                    'success' => true,
                    'message' => 'Refund has been processed successfully.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to process the refund.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing refund: ' . $e->getMessage(),
            ]);
        }
    }

    public function updatePaymentMethod(Request $request)
    {
        try {
            $charge_customer_id = null;
            if ($request->has('order_id')) {
                $order = Order::findOrFail($request->order_id);
                $charge_customer_id = $order->chargebee_customer_id ?? null;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing order ID in request.'
                ]);
            }
            if (is_null($charge_customer_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing chargebee customer ID in request.'
                ]);
            }

            // Create hosted page for payment method update
            $result = HostedPage::managePaymentSources([
                "customer" => [
                    "id" => $charge_customer_id
                ],
                "redirect_url" => route('customer.dashboard'),
                "cancel_url" => route('customer.dashboard')
            ]);

            $hostedPage = $result->hostedPage();

            return response()->json([
                'success' => true,
                'hosted_page_url' => $hostedPage->url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate card update: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCardDetails(Request $request)
    {
        try {
            $charge_customer_id = null;
            if($request->has('order_id')){
                $order = Order::findOrFail($request->order_id);
                $charge_customer_id = $order->chargebee_customer_id ?? null;
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Missing order ID in request.'
                ]);
            }
            
            if(is_null($charge_customer_id)){
                return response()->json([
                    'success' => false,
                    'message' => 'Missing chargebee customer ID in request.'
                ]);
            }

            // Get customer's payment sources from ChargeBee using the PaymentSource API
            $result = \ChargeBee\ChargeBee\Models\PaymentSource::all([
                'customer_id[is]' => $charge_customer_id,
                'status[is]' => 'valid'
            ]);
            // dd($result);
            $paymentSources = [];

            foreach ($result as $paymentSource) {
                $source = $paymentSource->paymentSource()->getValues();
                // dd($source);
                $paymentSources[] = [
                    'id' => $source['id'] ?? null,
                    'type' => $source['type'] ?? null,
                    'status' => $source['status'] ?? null,
                    'card' => [
                        'last4' => $source['card']['last4'] ?? null,
                        'expiry_month' => $source['card']['expiry_month'] ?? null,
                        'expiry_year' => $source['card']['expiry_year'] ?? null,
                        'masked_number' => $source['card']['masked_number'] ?? null,
                        "iin" => $source['card']['iin'] ?? null,
                    ],
                    'created_at' => $source['created_at'] ?? null,
                    'updated_at' => $source['updated_at'] ?? null
                ];
            }

            return response()->json([
                'success' => true,
                'payment_sources' => $paymentSources
            ]);
           
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve card details: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createChargeBeeItem()
    {
        try {
            // $request->validate([
            //     'name' => 'required|string',
            //     'description' => 'nullable|string',
            //     'price' => 'required|numeric',
            //     'period' => 'required|string|in:month,year',
            //     'period_unit' => 'required|integer',
            //     'currency_code' => 'required|string|size:3'
            // ]);

            // Create an item in ChargeBee
            // Using static test values instead of request data
            $result = \ChargeBee\ChargeBee\Models\Item::create([
                'id' => 'test_plan_basic',
                'name' => 'Test Plan Basic',
                'description' => 'This is a test plan for development purposes',
                'type' => 'plan',
                'enabled_in_portal' => true,
                // Removed included_in_mrr parameter as MRR setting is not enabled
                'item_family_id' => 'cbdemo_omnisupport-solutions',
                'status' => 'active'
            ]);
            // dd($result);
            if ($result && $result->item()) {
                // Create item price for the plan
                $priceResult = \ChargeBee\ChargeBee\Models\ItemPrice::create([
                    'id' => strtolower(str_replace(' ', '_', $request->name)) . '_price',
                    'name' => $request->name . ' Price',
                    'item_id' => $result->item()->id,
                    'pricing_model' => 'flat_fee',
                    'price' => $request->price * 100, // Convert to cents
                    'period' => $request->period,
                    'period_unit' => $request->period_unit,
                    'currency_code' => $request->currency_code,
                    'status' => 'active'
                ]);

                if ($priceResult && $priceResult->itemPrice()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Plan created successfully in ChargeBee',
                        'data' => [
                            'item_id' => $result->item()->id,
                            'price_id' => $priceResult->itemPrice()->id,
                            'name' => $result->item()->name,
                            'price' => $priceResult->itemPrice()->price / 100, // Convert back to main currency unit
                            'currency' => $priceResult->itemPrice()->currencyCode,
                            'period' => $priceResult->itemPrice()->period,
                            'period_unit' => $priceResult->itemPrice()->periodUnit
                        ]
                    ]);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create plan in ChargeBee'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating plan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function handleInvoiceWebhook(Request $request)
    {
        try {
            // Verify webhook authenticity
            $webhookData = $request->all();
            // Get the event type and content
            $eventType = $webhookData['event_type'] ?? null;
            $content = $webhookData['content'] ?? null;
            if (!$eventType || !$content) {
                throw new \Exception('Invalid webhook data received');
            }

            // Process based on event type
            switch ($eventType) {
                case 'invoice_created':
                case 'invoice_updated':
                case 'invoice_paid':
                case 'invoice_payment_failed':
                case 'invoice_generated':
                    $invoiceData = $content['invoice'] ?? null;
                    
                    if (!$invoiceData) {
                        throw new \Exception('No invoice data in webhook content');
                    }

                    // Extract customer and subscription data 
                    $customerData = $content['customer'] ?? [];
                    $subscriptionData = $content['subscription'] ?? [];

                    // Calculate amount in dollars (Chargebee sends amount in cents)
                    $amount = isset($invoiceData['amount_paid']) ? ($invoiceData['amount_paid'] / 100) : 0;
                    
                    // Get tax information
                    $tax = isset($invoiceData['tax']) ? ($invoiceData['tax'] / 100) : 0;

                    // Prepare metadata
                    $metadata = json_encode([
                        'invoice' => $invoiceData,
                    ]);

                    // Find or create invoice record
                    $invoice = Invoice::updateOrCreate(
                        ['chargebee_invoice_id' => $invoiceData['id']],
                        [
                            'chargebee_customer_id' => $invoiceData['customer_id'] ?? null,
                            'chargebee_subscription_id' => $invoiceData['subscription_id'] ?? null,
                            'user_id' => Invoice::where('chargebee_customer_id', $invoiceData['customer_id'])->value('user_id') ?? 2,
                            'plan_id' => Invoice::where('chargebee_customer_id', $invoiceData['customer_id'])->value('plan_id') ?? 4,
                            'order_id' => Invoice::where('chargebee_customer_id', $invoiceData['customer_id'])->value('order_id') ?? 5,
                            'amount' => $amount,
                            'status' => $this->mapInvoiceStatus($invoiceData['status'] ?? 'pending', $eventType),
                            'paid_at' => isset($invoiceData['paid_at']) 
                                ? Carbon::createFromTimestamp($invoiceData['paid_at'])->toDateTimeString() 
                                : null,
                            'metadata' => $metadata,
                        ]
                    );
                    // update subscription last_billing_date, next_billing_date
                    $subscription = UserSubscription::where('chargebee_subscription_id', $invoiceData['subscription_id'])->first();
                    if ($subscription) {
                        $subscription->update([
                            'last_billing_date' => isset($invoiceData['paid_at']) 
                                ? Carbon::createFromTimestamp($invoiceData['paid_at'])->toDateTimeString() 
                                : null,
                            'next_billing_date' => isset($invoiceData['paid_at']) 
                                ? Carbon::createFromTimestamp($invoiceData['paid_at'])->addMonth()->addDay()->toDateTimeString() 
                                : null
                        ]);
                    }
                    Log::info('Invoice processed successfully', [
                        'invoice_id' => $invoice->id,
                        'chargebee_invoice_id' => $invoiceData['id'],
                        'status' => $invoice->status,
                        'event_type' => $eventType
                    ]);
                    break;

                default:
                    Log::info('Unhandled invoice event type', ['event_type' => $eventType]);
                    break;
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error processing invoice webhook: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook: ' . $e->getMessage()
            ], 500);
        }
    }

    private function mapInvoiceStatus($chargebeeStatus, $eventType)
    {
        // Map Chargebee invoice status to our system status
        switch ($chargebeeStatus) {
            case 'paid':
                return 'paid';
            case 'payment_due':
                return 'pending';
            case 'voided':
                return 'voided';
            case 'not_paid':
                return $eventType === 'invoice_payment_failed' ? 'failed' : 'pending';
            default:
                return 'pending';
        }
    }
}
