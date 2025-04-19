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

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::with('features')->where('is_active', true)->get();
        return view('customer.pricing.pricing', compact('plans'));
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

    public function initiateSubscription(Request $request, $planId)
    {
        $plan = Plan::findOrFail($planId);
   
        // Validate user can subscribe to this plan
        // if (!auth()->user()->canSubscribeToPlan($plan)) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'You are not eligible for this plan'
        //     ], 403);
        // }

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
            
            // dd($order_info);
            // dd($hostedPageId);
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
            if(!is_null($order_info)){
                // save data on reorder_info table
                $order->reorderInfo()->create([
                    'user_id' => $user->id,
                    'plan_id' => $plan_id,
                    'forwarding_url' => $order_info['forwarding_url'],
                    'hosting_platform' => $order_info['hosting_platform'],
                    'backup_codes' => $order_info['backup_codes'],
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
            // "user_id" => "2"
            // "plan_id" => "1"
            // "forwarding_url" => "http://127.0.0.1:8080/customer/orders/reorder/42"
            // "hosting_platform" => "Namecheap"
            // "backup_codes" => "dfd434"
            // "platform_login" => "zlatin@expandacquisition.com"
            // "platform_password" => "343"
            // "domains" => "434"
            // "sending_platform" => "Instantly"
            // "sequencer_login" => "venkat.viswanathan2000@yahoo.com"
            // "sequencer_password" => "Joy4Jesus"
            // "total_inboxes" => "324"
            // "inboxes_per_domain" => "2"
            // "first_name" => "Venkat"
            // "last_name" => "Viswanathan"
            // "prefix_variant_1" => "venkat"
            // "prefix_variant_2" => "venkat.viswanathan"
            // "persona_password" => "Joy4Jesus"
            // "profile_picture_link" => "https://drive.google.com/file/d/148yvyXqit0XxNS1foFALosulbeEHO-a-G/view?usp=sharing"
            // "email_persona_password" => "Joy4Jesus"
            // "email_persona_picture_link" => "https://drive.google.com/file/d/148yvyXqit0XxNS1foFALosulbeEHO-a-G/view?usp=sharing"
            // "master_inbox_email" => "Viswanathan@e.com"
            // "additional_info" => "dsd"
            // "coupon_code" => "Viswanathan"

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

    public function cancelCurrentSubscription(Request $request)
    {
        $user = auth()->user();
        
        if (!$user->subscription || $user->subscription_status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found'
            ], 400);
        }

        try {
            // Cancel the subscription in Chargebee 
            $result = \ChargeBee\ChargeBee\Models\Subscription::cancel($user->subscription_id, [
                "end_of_term" => false
            ]);

            // Update user and subscription using relationships
            $user->subscription->update([
                'status' => 'cancelled',
                'end_date' => now()
            ]);

            $user->update([
                'subscription_status' => 'cancelled',
                'subscription_id' => null,
                'plan_id' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Subscription cancelled successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error cancelling subscription: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel subscription: ' . $e->getMessage()
            ], 500);
        }
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
}