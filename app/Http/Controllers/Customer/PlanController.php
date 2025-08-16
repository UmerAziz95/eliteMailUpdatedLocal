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
use App\Mail\InvoiceGeneratedMail;
use App\Models\ReorderInfo;
// User
use App\Models\User;
use App\Services\ActivityLogService;
use App\Models\Notification;
use App\Mail\UserWelcomeMail;
use App\Mail\SendPasswordMail;  
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\PaymentFailure;
use Illuminate\Support\Facades\DB;
use App\Mail\FailedPaymentNotificationMail;
class PlanController extends Controller
{
    public function index()
    {
        $getMostlyUsed = Plan::getMostlyUsed();
        $plans = Plan::with('features')->where('is_active', true)->where(function($query) {
            $query->where('is_discounted', 0)->orWhereNull('is_discounted');
        })->get();
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
  
    
    public function initiateSubscription(Request $request, $planId,$encrypted=null)
    {
        if(!$planId ){
            abort(404);
        }
        
        try {
           $plan = Plan::findOrFail($planId);
           if($encrypted !==null){
            $decrypted = Crypt::decryptString($request->encrypted);
            [$email, $expectedCode, $timestamp] = explode('/', $decrypted);
            }
            // Check if user is already logged in or fetch by email
            $user = Auth::check() ? auth()->user() : User::where('email', $email)->first();
             // Login and create session 
             //  Auth::login($user);
            // dd(auth()->user()); 
         
            if (!$user) {
                abort(404, 'User not found, auth failed please login or contact to support');
            }

             if (!Auth::check()) {
                session()->put('unauthorized_session', $user);
            }

            // get charge_customer_id from user
            $charge_customer_id = $user->chargebee_customer_id ?? null;
            if ($request->has('order_id') && $charge_customer_id == null) {
                $order = Order::findOrFail($request->order_id);
                $charge_customer_id = $order->chargebee_customer_id ?? null;
            }
            if ($charge_customer_id == null) {
                // Create hosted page for subscription
                $result = HostedPage::checkoutNewForItems([
                    "subscription_items" => [
                        [
                            "item_price_id" => $plan->chargebee_plan_id,
                            "quantity" => $request->session()->has('order_info') ? $request->session()->get('order_info')['total_inboxes'] : 1,
                            "quantity_editable" => true,
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
                       
                    ],
                    "allow_plan_change" => true,
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
                        "last_name" => "",
                        "line1" => "Address Line 1", // Default value
                        "city" => "City", // Default value
                        "state" => "State", // Default value
                        "zip" => "12345", // Default value
                        "country" => "US" // Default value
                    ],
                    "allow_plan_change" => true,
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
    
   
    public function subscriptionSuccess(Request $request)
    {
        try {
            $hostedPageId = $request->input('id');
            // dd($hostedPageId);
            // get session order_info
            if (!$hostedPageId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing hosted page ID in request.'
                ]);
            }

            if(!Auth::check()){
                $unauthorized_user = session()->get('unauthorized_session');
                if($unauthorized_user) {
                    $user = User::where('email', $unauthorized_user->email)->first();
                    if($user) {
                        Auth::login($user);
                        session()->forget('unauthorized_session');
                    }
                }
            }

          
          
            

            $result = \ChargeBee\ChargeBee\Models\HostedPage::retrieve($hostedPageId);
            $hostedPage = $result->hostedPage();
            $content = $hostedPage->content();

            $subscription = $content->subscription() ?? null;
            $customer = $content->customer() ?? null;
            $invoice = $content->invoice() ?? null;
            $shippingAddress = $subscription->getValues()['shipping_address'] ?? null;
            //shipping address
            $firstName = $shippingAddress['first_name'] ?? '';
            $lastName = $shippingAddress['last_name'] ?? '';
            $line1 = $shippingAddress['line1'] ?? '';
            $line2 = $shippingAddress['line2'] ?? '';
            $city = $shippingAddress['city'] ?? '';
            $state = $shippingAddress['state'] ?? '';
            $country = $shippingAddress['country'] ?? '';
            $zip = $shippingAddress['zip'] ?? '';
            $validationStatus = $shippingAddress['validation_status'] ?? '';
            $plan_id = null;
            $charge_plan_id = null;


              if(!Auth::check()){
              
                $user = User::where('email', $customer->email)->first();
              if(!Auth::check()){
                $user = User::where('email', $customer->email)->first();
                if(!$user){
                    $user = new User();
                    $user->email = $customer->email;
                    $user->name = $customer->firstName.' '. $customer->lastName ?? 'Guest';
                    $randomPassword = Str::upper(Str::random(5)) . rand(100, 999);
                    $user->password = Hash::make($randomPassword);
                    $user->role_id = 3;
                    $user->status = 1;
                    $user->billing_address = $line1;
                    $user->billing_address2 = $line2;
                    $user->billing_city = $city;
                    $user->billing_state = $state;
                    $user->billing_country = $country;
                    $user->billing_zip = $zip;
                    $user->save();

                    Auth::login($user);

                    try {
                        Mail::to($user->email)->queue(new SendPasswordMail($user, $randomPassword));
                    } catch (\Exception $e) {
                        Log::error('Failed to send user credentials : '.$user->email.' '.$e->getMessage());
                    }
                }
            }

            }   
         

            if ($subscription && $subscription->subscriptionItems) {
                $charge_plan_id = $subscription->subscriptionItems[0]->itemPriceId ?? null;
                $quantity = $subscription->subscriptionItems[0]->quantity ?? 1;
                
                // Find plan based on quantity range instead of chargebee_plan_id
                // $plan = Plan::where('is_active', 1)
                //     ->where('min_inbox', '<=', $quantity)
                //     ->where('is_discounted', '<>', 1)
                //     ->where(function ($query) use ($quantity) {
                //         $query->where('max_inbox', '>=', $quantity)
                //               ->orWhere('max_inbox', 0); // 0 means unlimited
                //     })
                //     ->orderBy('min_inbox', 'desc') // Get the most specific plan first
                //     ->first();
                $plan = Plan::where('is_active', 1)
                        ->where('min_inbox', '<=', $quantity)
                        ->where(function ($query) use ($quantity) {
                            $query->where('max_inbox', '>=', $quantity)
                                  ->orWhere('max_inbox', 0); // 0 means unlimited
                        })
                        ->where(function($query) {
                            $query->where('is_discounted', 0)->orWhereNull('is_discounted');
                        })
                        ->orderBy('min_inbox', 'desc') // Get the most specific plan first
                        ->first();
                    
                if ($plan) {
                    $plan_id = $plan->id;
                }
            }
            // dd($subscription, $customer, $invoice, $plan_id, $charge_plan_id);
            $user = auth()->user();
            $user->billing_address=$line1;
            $user->billing_address2=$line2;
            $user->billing_city=$city;
            $user->billing_state=$state;
            $user->billing_country=$country;
            $user->billing_zip=$zip;
            $user->billing_address_syn=1;
            $user->save(); 
            // dd($user);
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
            // create session for set observer_total_inboxes
            $request->session()->put('observer_total_inboxes', $subscription->subscriptionItems[0]->quantity ?? 1);
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
                $prefixVariants = $order_info['prefix_variants'] ?? [];
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
                    'prefix_variants' => $order_info['prefix_variants'],
                    'prefix_variant_1' => $order_info['prefix_variant_1'],
                    'prefix_variant_2' => $order_info['prefix_variant_2'],
                    'persona_password' => $order_info['persona_password']??"123",
                    'profile_picture_link' => $order_info['profile_picture_link'] ?? null,
                    'email_persona_password' => $order_info['email_persona_password'] ?? null,
                    'email_persona_picture_link' => $order_info['email_persona_picture_link'] ?? null,
                    'master_inbox_email' => $order_info['master_inbox_email'] ?? null,
                    'additional_info' => $order_info['additional_info'] ?? null,
                ]);
            }else{
                // status_manage_by_admin
                $order->update([
                    'status_manage_by_admin' => 'draft',
                ]);
                // get total per unit qty from chargebee
                $total_inboxes = $subscription->subscriptionItems[0]->quantity ?? 1;
                // dd($total_inboxes);
                // save data on reorder_info table
                $order->reorderInfo()->create([
                    'user_id' => $user->id,
                    'plan_id' => $plan_id,
                    'forwarding_url' => null,
                    'other_platform' => null,
                    'tutorial_section' => null,
                    'hosting_platform' => null,
                    'backup_codes' => null,
                    'bison_url' => null,
                    'bison_workspace' => null,
                    'platform_login' => null,
                    'platform_password' => null,
                    'domains' => null,
                    'sending_platform' => null,
                    'sequencer_login' => null,
                    'sequencer_password' => null,
                    'total_inboxes' => $total_inboxes, // Use the quantity from subscription
                    'inboxes_per_domain' => 1, // Default value
                    'first_name' => $user->name, // Use user's name
                    'last_name' => '', // Default value
                    'prefix_variant_1' => '', // Default value
                    'prefix_variant_2' => '', // Default value
                    'persona_password' => "123", // Default value
                    'profile_picture_link' => null, // Default value
                    'email_persona_password' => null, // Default value
                    'email_persona_picture_link' => null, // Default value
                    'master_inbox_email' => null, // Default value
                    'additional_info' => null, // Default value
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
                $existingInvoice = Invoice::create([
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

            // Update GHL contact to customer when invoice is created/paid
            // This converts the contact from 'lead' to 'customer' status and updates tags
            try {
                $ghlService = new \App\Services\AccountCreationGHL();
                // get address details from chargebee customer
                // $user->billing_address = $invoice->billingAddress->line1 ?? null;
                // $user->billing_city = $invoice->billingAddress->city ?? null;
                // $user->billing_state = $invoice->billingAddress->state ?? null;
                // $user->billing_zip = $invoice->billingAddress->zip ?? null;
                // $user->billing_country = $invoice->billingAddress->country ?? null;
                if ($ghlService->isEnabled()) {
                    $ghlResult = $ghlService->updateContactToCustomer($user, 'customer');
                    
                    if ($ghlResult) {
                        Log::info('GHL contact successfully converted to customer', [
                            'user_id' => $user->id,
                            'invoice_id' => $existingInvoice->id,
                            'ghl_contact_id' => $user->ghl_contact_id
                        ]);
                    } else {
                        Log::warning('Failed to convert GHL contact to customer', [
                            'user_id' => $user->id,
                            'invoice_id' => $existingInvoice->id
                        ]);
                    }
                } else {
                    Log::info('GHL integration is disabled, skipping contact update');
                }
            } catch (\Exception $e) {
                Log::error('Exception while updating GHL contact to customer', [
                    'user_id' => $user->id,
                    'invoice_id' => $existingInvoice->id,
                    'error' => $e->getMessage()
                ]);
                // Don't throw the exception - let the process continue
            }

            // Create or update subscription
            $user_subscription_data = UserSubscription::updateOrCreate(
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
            
            // destroy session order_info
            if ($request->session()->has('order_info')) {
                $request->session()->forget('order_info');
            }
            // Create a new activity log using the custom log service
            ActivityLogService::log(
                'customer-order-created',
                'Order created successfully: ' . $order->id,
                $order, 
                [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'plan_id' => $plan_id,
                    'chargebee_subscription_id' => $subscription->id,
                    'chargebee_invoice_id' => $invoice->id,
                    'amount' => ($invoice->amountPaid ?? 0) / 100,
                    'status' => $invoice->status,
                    'paid_at' => Carbon::createFromTimestamp($invoice->paidAt)->toDateTimeString(),
                ]
            );
            // Create a new activity log using the custom log service
            ActivityLogService::log(
                'customer-subscription-created',
                'Subscription created successfully: ' . $user_subscription_data->id,
                $user_subscription_data, 
                [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'plan_id' => $plan_id,
                    'chargebee_subscription_id' => $subscription->id,
                    'chargebee_invoice_id' => $invoice->id,
                    'amount' => ($invoice->amountPaid ?? 0) / 100,
                    'status' => $invoice->status,
                    'paid_at' => Carbon::createFromTimestamp($invoice->paidAt)->toDateTimeString(),
                ]
            );
            // Create notification for the customer after sending mail
            Notification::create([
                'user_id' => $user->id,
                'type' => 'subscription_created',
                'title' => 'New Subscription Created',
                'message' => "Your subscription #{$subscription->id} has been created successfully",
                'data' => [
                    'subscription_id' => $subscription->id,
                    'amount' => ($invoice->amountPaid ?? 0) / 100
                ]
            ]);
            // Create a new activity log using the custom log service
            ActivityLogService::log(
                'customer-invoice-processed',
                'Invoice created successfully: ' . $existingInvoice->id,
                $existingInvoice, 
                [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'plan_id' => $plan_id,
                    'chargebee_subscription_id' => $subscription->id,
                    'chargebee_invoice_id' => $invoice->id,
                    'amount' => ($invoice->amountPaid ?? 0) / 100,
                    'status' => $invoice->status,
                    'paid_at' => Carbon::createFromTimestamp($invoice->paidAt)->toDateTimeString(),
                ]
            );
            // Send email notifications
            try {
                // Send email to user
                Mail::to($user->email)
                    ->queue(new OrderCreatedMail(
                        $order,
                        $user,
                        false
                    ));

                // Create notification for the customer after sending mail
                Notification::create([
                    'user_id' => $user->id,
                    'type' => 'order_created',
                    'title' => 'New Order Created',
                    'message' => "Your order #{$order->id} has been created successfully",
                    'data' => [
                        'order_id' => $order->id,
                        'amount' => $order->amount
                    ]
                ]);

                 //to super admin
      
               $superAdmins = User::where('role_id', 1)->get(); // Only super admins (role_id = 1)

                foreach ($superAdmins as $superAdmin) {
                    Mail::to($superAdmin->email)->queue(new OrderCreatedMail(
                        $order,
                        $user,
                        true
                    ));
                }

                

            } catch (\Exception $e) {
                \Log::error('Failed to send order creation emails: ' . $e->getMessage());
                // Continue execution since the order was already created
            }
            
            // Send email to all contractors if order is not assigned
            if (is_null($order->assigned_to)) {
                $contractors = User::where('role_id', '4')->get();
                if ($contractors->count() > 0) {
                    foreach ($contractors as $contractor) {
                        try {
                            Mail::to($contractor->email)
                                ->queue(new OrderCreatedMail(
                                    $order,
                                    $contractor,
                                    true
                                ));
                        } catch (\Exception $e) {
                            \Log::error('Failed to send order notification to contractor: ' . $e->getMessage());
                        }
                    }
                }
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

        // Use the OrderCancelledService
        $subscriptionService = new \App\Services\OrderCancelledService();
        $result = $subscriptionService->cancelSubscription(
            $request->chargebee_subscription_id,
            $user->id,
            $request->reason,
            $request->remove_accounts
        );
        
        return response()->json($result);
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
        // dd('ok');
        return redirect('/customer/dashboard')->with('error', 'Subscription process was cancelled.');
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
    // create new subscription with customer_id and plan_id
    public function createSubscription(Request $request)
    {
        try {
            $plan = Plan::first();
            $chargebeeCustomerId = 'AzqIC4Ut46lF5IXx';

            // Create subscription using ChargeBee Product Catalog 2.0
            $result = Subscription::createWithItems($chargebeeCustomerId, [
                "subscription_items" => [
                    [
                        "item_price_id" => $plan->chargebee_plan_id,
                        "quantity" => 1
                    ]
                ]
            ]);

            $subscription = $result->subscription();

            if ($subscription) {
                return response()->json([
                    'success' => true,
                    'subscription_id' => $subscription->id,
                    'status' => $subscription->status,
                    'message' => 'Subscription created successfully'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create subscription'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating subscription: ' . $e->getMessage()
            ], 500);
        }
    }
    public function updatePaymentMethod(Request $request)
    {
        try {
            $charge_customer_id = null;
            $user = auth()->user();
            
            if($request->has('order_id') && !empty($request->order_id)){
                // If order_id is provided, get chargebee_customer_id from that order
                $order = Order::findOrFail($request->order_id);
                $charge_customer_id = $order->chargebee_customer_id ?? null;
            }else{
                // If order_id is not provided or empty, try to get from user
                $charge_customer_id = $user->chargebee_customer_id ?? null;
                
                // If user doesn't have chargebee_customer_id, get from latest order
                if(is_null($charge_customer_id)){
                    $latestOrder = Order::where('user_id', $user->id)
                        ->whereNotNull('chargebee_customer_id')
                        ->latest()
                        ->first();
                    $charge_customer_id = $latestOrder->chargebee_customer_id ?? null;
                }
            }
            
            if(is_null($charge_customer_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid payment information found for this account.'
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
            $user = auth()->user();
            
            if($request->has('order_id') && !empty($request->order_id)){
                // If order_id is provided, get chargebee_customer_id from that order
                $order = Order::findOrFail($request->order_id);
                $charge_customer_id = $order->chargebee_customer_id ?? null;
            }else{
                // If order_id is not provided or empty, try to get from user
                $charge_customer_id = $user->chargebee_customer_id ?? null;
                
                // If user doesn't have chargebee_customer_id, get from latest order
                if(is_null($charge_customer_id)){
                    $latestOrder = Order::where('user_id', $user->id)
                        ->whereNotNull('chargebee_customer_id')
                        ->latest()
                        ->first();
                    $charge_customer_id = $latestOrder->chargebee_customer_id ?? null;
                }
            }
            
            if(is_null($charge_customer_id)){
                return response()->json([
                    'success' => false,
                    'message' => 'No valid payment information found for this account.'
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
    public function deletePaymentMethod(Request $request)
    {
        try {
            $request->validate([
                'payment_source_id' => 'required|string'
            ]);

            $charge_customer_id = null;
            $user = auth()->user();
            
            if($request->has('order_id') && !empty($request->order_id)){
                $order = Order::findOrFail($request->order_id);
                $charge_customer_id = $order->chargebee_customer_id ?? null;
            } else{
                $charge_customer_id = $user->chargebee_customer_id ?? null;
                
                if(is_null($charge_customer_id)){
                    $latestOrder = Order::where('user_id', $user->id)
                        ->whereNotNull('chargebee_customer_id')
                        ->latest()
                        ->first();
                    $charge_customer_id = $latestOrder->chargebee_customer_id ?? null;
                }
            }
            
            if(is_null($charge_customer_id)){
                return response()->json([
                    'success' => false,
                    'message' => 'No valid payment information found for this account.'
                ]);
            }

            // Delete the payment source
            $result = \ChargeBee\ChargeBee\Models\PaymentSource::delete($request->payment_source_id);

            if ($result && $result->paymentSource()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Payment method deleted successfully.'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment method.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
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
            Log::info('Invoice Webhook Received Chargebee', [
                'payload' => $request->all()
            ]);
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
                    //  case 'invoice_payment_succeeded':
                    // $invoiceData = $webhookData['content']['invoice'] ?? [];

                    // $subscriptionId = $invoiceData['subscription_id'] ?? null;
                    // $customerId = $invoiceData['customer_id'] ?? null;

                    // // Attempt to remove any failure records for this subscription
                    // try {
                    //     DB::table('payment_failures')
                    //         ->where('chargebee_subscription_id', $subscriptionId)
                    //         ->where('chargebee_customer_id', $customerId)
                    //         ->where('created_at', '>=', now('UTC')->subHours(72)) // Only remove if it's within 72 hours
                    //         ->delete();

                    //     Log::info("✅ Cleared payment failure for subscription: $subscriptionId");
                    // } catch (\Exception $e) {
                    //     Log::error("❌ Failed to clear payment failure: " . $e->getMessage());
                    // }

                    // break;

                case 'invoice_payment_failed':
                    if ($eventType === 'invoice_payment_failed') {
                    try {
                       DB::table('payment_failures')->updateOrInsert(
                        [
                            'chargebee_subscription_id' => $subscriptionId,
                            'chargebee_customer_id' => $customerId,
                        ],
                        [
                            'reason' => $failureReason,
                            'invoice_id' => $invoiceId,
                            'updated_at' => now('UTC'),
                            'created_at' => now('UTC'), // optional — use only if table doesn't auto-set this
                        ]
                    );

                        Log::info('Payment failure recorded successfully', [
                            'subscription_id' => $invoiceData['subscription_id'] ?? null,
                            'user_id'         => $user_id,
                            'plan_id'         => $plan_id,
                        ]);
                    } catch (\Exception $ex) {
                        Log::error('Failed to record payment failure: ' . $ex->getMessage());
                    }
                }
                case 'invoice_generated':
                    $invoiceData = $content['invoice'] ?? null;
                     $subscriptionId = $invoiceData['subscription_id'] ?? null;
                    $customerId = $invoiceData['customer_id'] ?? null;
                    
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
                    // get order_id, user_id, plan_id from invoce where subscription_id
                    $order_id = Invoice::where('chargebee_subscription_id', $invoiceData['subscription_id'])
                        ->value('order_id') ?? 1;
                    $user_id = Invoice::where('chargebee_subscription_id', $invoiceData['subscription_id'])
                        ->value('user_id') ?? 1;
                    $plan_id = Invoice::where('chargebee_subscription_id', $invoiceData['subscription_id'])
                        ->value('plan_id') ?? null;
                    if (!$plan_id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Plan ID not found for the subscription'
                        ], 400);
                    }
                    if (!$order_id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Order ID not found for the subscription'
                        ], 400);
                    }
                    if (!$user_id) {
                        return response()->json([
                            'success' => false,
                            'message' => 'User ID not found for the subscription'
                        ], 400);
                    }
                    // Find or create invoice record
                    $invoice = Invoice::updateOrCreate(
                        ['chargebee_invoice_id' => $invoiceData['id']],
                        [
                            'chargebee_customer_id' => $invoiceData['customer_id'] ?? null,
                            'chargebee_subscription_id' => $invoiceData['subscription_id'] ?? null,
                            'user_id' => $user_id,
                            'plan_id' => $plan_id,
                            'order_id' => $order_id,
                            'amount' => $amount,
                            'status' => $this->mapInvoiceStatus($invoiceData['status'] ?? 'pending', $eventType),
                            'paid_at' => isset($invoiceData['paid_at']) 
                                ? Carbon::createFromTimestamp($invoiceData['paid_at'])->toDateTimeString() 
                                : null,
                            'metadata' => $metadata,
                        ]
                    );
                    
                    // Update GHL contact to customer when invoice is created/paid via webhook
                    // This converts the contact from 'lead' to 'customer' status and updates tags
                    // if (in_array($eventType, ['invoice_created', 'invoice_paid', 'invoice_generated']) && $invoice->status === 'paid') {
                    //     try {
                    //         $user = User::find($invoice->user_id);
                    //         if ($user) {
                    //             $ghlService = new \App\Services\AccountCreationGHL();
                    //             if ($ghlService->isEnabled()) {
                    //                 $ghlResult = $ghlService->updateContactToCustomer($user, 'customer');
                                    
                    //                 if ($ghlResult) {
                    //                     Log::info('GHL contact successfully converted to customer via webhook', [
                    //                         'user_id' => $user->id,
                    //                         'invoice_id' => $invoice->id,
                    //                         'event_type' => $eventType,
                    //                         'ghl_contact_id' => $user->ghl_contact_id
                    //                     ]);
                    //                 } else {
                    //                     Log::warning('Failed to convert GHL contact to customer via webhook', [
                    //                         'user_id' => $user->id,
                    //                         'invoice_id' => $invoice->id,
                    //                         'event_type' => $eventType
                    //                     ]);
                    //                 }
                    //             } else {
                    //                 Log::info('GHL integration is disabled, skipping contact update via webhook');
                    //             }
                    //         }
                    //     } catch (\Exception $e) {
                    //         Log::error('Exception while updating GHL contact to customer via webhook', [
                    //             'invoice_id' => $invoice->id,
                    //             'event_type' => $eventType,
                    //             'error' => $e->getMessage()
                    //         ]);
                    //         // Don't throw the exception - let the webhook process continue
                    //     }
                    // }
                    
                    // Remove any payment failure records for this subscription
                try {
                    DB::table('payment_failures')
                        ->where('chargebee_subscription_id', $subscriptionId)
                        ->where('chargebee_customer_id', $customerId)
                        ->where('created_at', '>=', now('UTC')->subHours(72)) // last 72 hours
                        ->delete();

                    Log::info("✅ Cleared payment failure for subscription: $subscriptionId");
                } catch (\Exception $e) {
                    Log::error("❌ Failed to clear payment failure: " . $e->getMessage());
                }
                    // Send email notification if invoice is generated
                    if ($eventType === 'invoice_generated') {
                        try {
                            $user = User::find($invoice->user_id);
                            if ($user) {
                                // Send email to user
                                Mail::to($user->email)
                                    ->queue(new InvoiceGeneratedMail(
                                        $invoice,
                                        $user,
                                        false
                                    ));

                                // Send email to admin
                                Mail::to(config('mail.admin_address', 'admin@example.com'))
                                    ->queue(new InvoiceGeneratedMail(
                                        $invoice,
                                        $user,
                                        true
                                    ));
                                
                                // Slack notification is now handled by InvoiceObserver
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to send invoice generation emails: ' . $e->getMessage());
                        }
                    }

                    // Slack notification for payment failures is now handled by InvoiceObserver
                    // when the invoice status is updated to 'failed'

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
                    // Create a new activity log using the custom log service
                    ActivityLogService::log(
                        'customer-invoice-processed',
                        'Invoice processed successfully: ' . $invoice->id,
                        $invoice, 
                        [
                            'user_id' => $invoice->user_id,
                            'invoice_id' => $invoice->id,
                            'chargebee_invoice_id' => $invoiceData['id'],
                            'amount' => $amount,
                            'status' => $this->mapInvoiceStatus($invoiceData['status'] ?? 'pending', $eventType),
                            'paid_at' => isset($invoiceData['paid_at']) 
                                ? Carbon::createFromTimestamp($invoiceData['paid_at'])->toDateTimeString() 
                                : null,
                        ],
                        $invoice->user_id
                    );
                    
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
    // handlePaymentWebhook 
    public function handlePaymentWebhook(Request $request)
    {
        try {
            // Log the incoming webhook data for debugging
            Log::info('Payment webhook received', ['data' => $request->all()]);
            
            // Verify webhook data integrity
            $webhookData = $request->all();
            $eventType = $webhookData['event_type'] ?? null;
            $content = $webhookData['content'] ?? null;
            
            // Log complete webhook payload for debugging
            Log::debug('Complete webhook payload structure', [
                'event_type' => $eventType,
                'content_structure' => array_keys($content ?? []),
                'subscription_keys' => isset($content['subscription']) ? array_keys($content['subscription']) : 'No subscription data',
                'invoice_keys' => isset($content['invoice']) ? array_keys($content['invoice']) : 'No invoice data',
                'line_items' => isset($content['invoice']['line_items']) ? $content['invoice']['line_items'] : 'No line items'
            ]);
            
            if (!$eventType || !$content) {
                throw new \Exception('Invalid webhook data received');
            }
            // Process different payment event types
            switch ($eventType) {
                // case 'payment_succeeded':
                case 'payment_failed':
                case 'subscription_created':
                case 'subscription_activated':
                case 'customer_created':
                case 'payment_succeeded':
                    // Extract important data from webhook content
                    $customerData = $content['customer'] ?? null;
                    $paymentData = $content['payment'] ?? null;
                    $subscriptionData = $content['subscription'] ?? null;
                    $hostedPageData = $content['hosted_page'] ?? null;
                    $invoiceData = $content['invoice'] ?? null;
                    $meta_json = json_encode([
                        'customer' => $customerData,
                        'payment' => $paymentData,
                        'subscription' => $subscriptionData,
                        'hosted_page' => $hostedPageData,
                        'invoice' => $invoiceData
                    ]);
                    // return response()->json([
                    //     'success' => true,
                    //     'message' => 'Webhook processed successfully',
                    //     'eventType'=> $eventType,
                    //     'customerData'=> $customerData,
                    //     'paymentData'=> $paymentData,
                    //     'subscriptionData'=> $subscriptionData,
                    //     'invoiceData'=> $invoiceData,
                    //     'hostedPageData'=> $hostedPageData,

                    // ]);
                    // Process customer data - find or create user
                    if ($customerData) {
                        $email = $customerData['email'] ?? null;
                        $chargebeeCustomerId = $customerData['id'] ?? null;
                        
                        if ($email && $chargebeeCustomerId) {
                            // Try to find user by email
                            $user = User::where('email', $email)->first();
                            
                            if (!$user) {
                                // Generate a secure random password
                                $password = \Illuminate\Support\Str::random(12);
                                
                                // Create new user with details from Chargebee
                                $user = User::create([
                                    'name' => $customerData['first_name'] ?? 'Customer',
                                    'email' => $email,
                                    'phone' => $customerData['phone'] ?? null,
                                    'password' => bcrypt($password),
                                    'role_id' => 3, // Assuming 3 is the customer role
                                    'chargebee_customer_id' => $chargebeeCustomerId,
                                    'email_verified_at' => now(),
                                ]);
                                
                                Log::info('New user created from payment webhook', [
                                    'user_id' => $user->id,
                                    'email' => $email,
                                    'chargebee_customer_id' => $chargebeeCustomerId
                                ]);
                                
                                // Send welcome email with password to the new user
                                try {
                                    Mail::to($email)->queue(new UserWelcomeMail($user, $password));
                                    
                                    // Create notification for the user
                                    Notification::create([
                                        'user_id' => $user->id,
                                        'type' => 'account_created',
                                        'title' => 'Account Created',
                                        'message' => 'Your account has been created successfully.',
                                        'data' => [
                                            'user_id' => $user->id
                                        ]
                                    ]);
                                } catch (\Exception $e) {
                                    Log::error('Failed to send welcome email: ' . $e->getMessage());
                                }
                            } 
                            // Update existing user if they don't have chargebee_customer_id
                            elseif (!$user->chargebee_customer_id) {
                                $user->update(['chargebee_customer_id' => $chargebeeCustomerId]);
                            }
                            
                            // Process subscription data if available
                            if ($subscriptionData) {
                                $subscriptionId = $subscriptionData['id'] ?? null;
                                
                                // Try to get plan ID from different possible keys in Chargebee's data structure
                                // First check if plan ID is in subscription items array (new Chargebee structure)
                                $planId = null;
                                
                                // Check for subscription_items array which may contain the item_price_id
                                if (isset($subscriptionData['subscription_items']) && is_array($subscriptionData['subscription_items']) && !empty($subscriptionData['subscription_items'])) {
                                    foreach ($subscriptionData['subscription_items'] as $item) {
                                        if (isset($item['item_price_id'])) {
                                            $planId = $item['item_price_id'];
                                            break;
                                        }
                                    }
                                }
                                
                                // If not found in subscription_items, try direct keys
                                if (!$planId) {
                                    $planId = $subscriptionData['item_price_id'] ?? $subscriptionData['plan_id'] ?? null;
                                }
                                
                                // Log the plan ID for debugging
                                Log::info('Subscription plan details', [
                                    'subscription_id' => $subscriptionId,
                                    'chargebee_plan_id' => $planId,
                                    'raw_subscription_data' => $subscriptionData
                                ]);
                                
                                $status = $subscriptionData['status'] ?? 'active';
                                
                                // Find corresponding plan in our database
                                $plan = Plan::where('chargebee_plan_id', $planId)->first();
                                
                                // If plan not found, try to extract from metadata if available
                                if (!$plan && isset($subscriptionData['meta_data']) && is_array($subscriptionData['meta_data'])) {
                                    if (isset($subscriptionData['meta_data']['plan_id'])) {
                                        $alternatePlanId = $subscriptionData['meta_data']['plan_id'];
                                        $plan = Plan::where('chargebee_plan_id', $alternatePlanId)->first();
                                        
                                        if ($plan) {
                                            Log::info('Found plan using meta_data', [
                                                'meta_data_plan_id' => $alternatePlanId,
                                                'subscription_id' => $subscriptionId
                                            ]);
                                        }
                                    }
                                }
                                
                                // If still no plan found, try looking for any active plan as a last resort
                                if (!$plan) {
                                    Log::warning('Could not find plan with ID: ' . $planId . ', trying to find active plans');
                                    // Get all active plans in the system
                                    $activePlans = Plan::where('is_active', true)->get();
                                    
                                    if ($activePlans->count() > 0) {
                                        // Use the first active plan as fallback
                                        $plan = $activePlans->first();
                                        Log::info('Using fallback plan', [
                                            'fallback_plan_id' => $plan->id,
                                            'fallback_plan_name' => $plan->name
                                        ]);
                                    }
                                }
                                $localPlanId = $plan ? $plan->id : null;
                                
                                // Create order first to ensure we have an order_id for the invoice
                                if ($subscriptionId) {
                                    // Create or update order
                                    $order = Order::updateOrCreate(
                                        ['chargebee_subscription_id' => $subscriptionId],
                                        [
                                            'user_id' => $user->id,
                                            'chargebee_customer_id' => $chargebeeCustomerId,
                                            'plan_id' => $localPlanId ?? 1,
                                            'status_manage_by_admin' => 'new',
                                            'amount' => isset($invoiceData['amount_paid']) ? ($invoiceData['amount_paid'] / 100) : 0,
                                            'payment_status' => ($eventType === 'payment_succeeded') ? 'paid' : 'pending',
                                            'paid_at' => Carbon::createFromTimestamp($invoiceData['paid_at'])->toDateTimeString(),
                                            'meta' => $meta_json,
                                        ]);
                                    
                                    Log::info('Fallback order created via webhook without subscription ID', [
                                        'order_id' => $order->id,
                                        'user_id' => $user->id
                                    ]);
                                }
                                
                                if ($subscriptionId && $localPlanId) {
                                    // Create or update subscription
                                    $subscription = UserSubscription::updateOrCreate(
                                        ['chargebee_subscription_id' => $subscriptionId],
                                        [
                                            'user_id' => $user->id,
                                            'plan_id' => $localPlanId,
                                            'status' => $status,
                                            'start_date' => isset($subscriptionData['current_term_start']) 
                                                ? Carbon::createFromTimestamp($subscriptionData['current_term_start'])->toDateTimeString() 
                                                : now()->toDateTimeString(),
                                            'end_date' => isset($subscriptionData['current_term_end']) 
                                                ? Carbon::createFromTimestamp($subscriptionData['current_term_end'])->toDateTimeString() 
                                                : null,
                                            'next_billing_date' => isset($subscriptionData['next_billing_at']) 
                                                ? Carbon::createFromTimestamp($subscriptionData['next_billing_at'])->toDateTimeString() 
                                                : null,
                                            'order_id' => $order->id // Link subscription to order
                                        ]
                                    );
                                    
                                    // Update user's subscription info
                                    $user->update([
                                        'subscription_id' => $subscriptionId,
                                        'subscription_status' => $status,
                                        'plan_id' => $localPlanId
                                    ]);
                                    
                                    // Log activity for subscription
                                    ActivityLogService::log(
                                        'webhook-subscription-processed',
                                        'Subscription processed via webhook: ' . $subscriptionId,
                                        $subscription,
                                        [
                                            'user_id' => $user->id,
                                            'subscription_id' => $subscriptionId,
                                            'plan_id' => $localPlanId,
                                            'status' => $status,
                                            'event_type' => $eventType
                                        ],
                                        $user->id
                                    );
                                    
                                    // Create notification for the user
                                    Notification::create([
                                        'user_id' => $user->id,
                                        'type' => 'subscription_created',
                                        'title' => 'Subscription Created',
                                        'message' => "Your subscription #{$subscriptionId} has been created successfully",
                                        'data' => [
                                            'subscription_id' => $subscriptionId,
                                            'amount' => isset($invoiceData['amount_paid']) ? ($invoiceData['amount_paid'] / 100) : 0
                                        ]
                                    ]);
                                }
                            }
                            
                            // Process invoice data if available
                            if ($invoiceData) {
                                $invoiceId = $invoiceData['id'] ?? null;
                                $amount = isset($invoiceData['amount_paid']) ? ($invoiceData['amount_paid'] / 100) : 0;
                                
                                // Try to extract plan ID from invoice line items if not already found
                                if (!isset($localPlanId) || !$localPlanId) {
                                    if (isset($invoiceData['line_items']) && is_array($invoiceData['line_items']) && !empty($invoiceData['line_items'])) {
                                        foreach ($invoiceData['line_items'] as $lineItem) {
                                            // Check for entity_id which could be the item price ID
                                            if (isset($lineItem['entity_id'])) {
                                                $itemPriceId = $lineItem['entity_id'];
                                                
                                                // Look up the plan in our database
                                                $plan = Plan::where('chargebee_plan_id', $itemPriceId)->first();
                                                if ($plan) {
                                                    $localPlanId = $plan->id;
                                                    
                                                    Log::info('Plan ID extracted from invoice line items', [
                                                        'item_price_id' => $itemPriceId,
                                                        'local_plan_id' => $localPlanId,
                                                        'invoice_id' => $invoiceId
                                                    ]);
                                                    
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                if ($invoiceId) {
                                    try {
                                        // Ensure we have a valid order_id before creating the invoice
                                        if (!isset($order) || !$order) {
                                            // If no order exists at this point, create one as fallback
                                            $order = Order::create([
                                                'user_id' => $user->id,
                                                'chargebee_customer_id' => $chargebeeCustomerId,
                                                'chargebee_subscription_id' => $subscriptionData['id'] ?? null,
                                                'plan_id' => $localPlanId ?? 1,
                                                'status_manage_by_admin' => 'new',
                                                'amount' => $amount,
                                                'payment_status' => ($eventType === 'payment_succeeded') ? 'paid' : 'pending'
                                            ]);
                                            
                                            Log::info('Emergency fallback order created for invoice', [
                                                'order_id' => $order->id,
                                                'invoice_id' => $invoiceId
                                            ]);
                                        }
                                        
                                        // Verify the order exists and has an ID
                                        if (!$order->id) {
                                            Log::error('Order ID is missing despite order object existing', [
                                                'order' => $order,
                                                'invoice_id' => $invoiceId
                                            ]);
                                            throw new \Exception('Invalid order ID when processing invoice');
                                        }
                                        
                                        // Create or update invoice with explicit order_id
                                        $invoice = Invoice::updateOrCreate(
                                            ['chargebee_invoice_id' => $invoiceId],
                                            [
                                                'user_id' => $user->id,
                                                'chargebee_customer_id' => $chargebeeCustomerId,
                                                'chargebee_subscription_id' => $subscriptionData['id'] ?? null,
                                                'order_id' => $order->id, // Ensure this is not null
                                                'plan_id' => $localPlanId ?? $order->plan_id,
                                                'amount' => $amount,
                                                'status' => ($eventType === 'payment_succeeded') ? 'paid' : 'pending',
                                                'paid_at' => ($eventType === 'payment_succeeded') ? now()->toDateTimeString() : null,
                                                'metadata' => json_encode([
                                                    'invoice' => $invoiceData,
                                                    'subscription' => $subscriptionData
                                                ])
                                            ]
                                        );
                                        
                                        // Log successful invoice creation
                                        Log::info('Invoice created/updated via webhook', [
                                            'invoice_id' => $invoice->id, 
                                            'chargebee_invoice_id' => $invoiceId,
                                            'order_id' => $order->id
                                        ]);
                                    } catch (\Exception $e) {
                                        Log::error('Error creating invoice: ' . $e->getMessage(), [
                                            'chargebee_invoice_id' => $invoiceId,
                                            'order' => isset($order) ? $order->id : 'No order',
                                            'exception' => $e->getMessage()
                                        ]);
                                        throw $e; // Re-throw to be caught by the outer try/catch
                                    }
                                    
                                    // Log activity for invoice
                                    ActivityLogService::log(
                                        'webhook-invoice-processed',
                                        'Invoice processed via webhook: ' . $invoiceId,
                                        $invoice,
                                        [
                                            'user_id' => $user->id,
                                            'invoice_id' => $invoiceId,
                                            'amount' => $amount,
                                            'status' => $invoice->status,
                                            'event_type' => $eventType
                                        ],
                                        $user->id
                                    );
                                    
                                    // Send invoice notification email if payment succeeded
                                    if ($eventType === 'payment_succeeded') {
                                        try {
                                            Mail::to($user->email)
                                                ->queue(new InvoiceGeneratedMail(
                                                    $invoice,
                                                    $user,
                                                    false
                                                ));
                                                
                                            // Send to admin as well
                                            Mail::to(config('mail.admin_address', 'admin@example.com'))
                                                ->queue(new InvoiceGeneratedMail(
                                                    $invoice,
                                                    $user,
                                                    true
                                                ));
                                        } catch (\Exception $e) {
                                            Log::error('Failed to send invoice emails: ' . $e->getMessage());
                                        }
                                    }
                                }
                            }
                        } else {
                            Log::warning('Missing customer email or ID in webhook data');
                        }
                    } else {
                        Log::warning('No customer data found in webhook payload', ['event_type' => $eventType]);
                    }
                    
                    break;
                    
                default:
                    Log::info('Unhandled payment event type', ['event_type' => $eventType]);
                    break;
            }

            return response()->json(['success' => true, 'message' => 'Webhook processed successfully']);
            
        } catch (\Exception $e) {
            Log::error('Error processing payment webhook: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook: ' . $e->getMessage()
            ], 500);
        }
    }


   public function handleCancelSubscriptionByCron(Request $request)
{
    $cutoffTime = Carbon::now()->subHours(72);

    // Get payment failures older than 72 hours
   $paymentFailures = PaymentFailure::where("type", "invoice")
    ->where("status", "!=", "cancelled")
    ->where("created_at", "<=", $cutoffTime)
    ->get();

    $subscriptionService = new \App\Services\OrderCancelledService();
    foreach ($paymentFailures as $failure) {
        // Make sure user_id and subscription_id exist
        if ($failure->chargebee_subscription_id && $failure->user_id) {
           $result= $subscriptionService->cancelSubscription(
                $failure->chargebee_subscription_id,
                $failure->user_id,
                'Auto-cancel due to repeated failure after 72 hours',
                true // or false depending on your logic for removing accounts
            );
            if ($result['success']) {
                // Mark the payment failure as processed
                $failure->update(['status' => 'cancelled']);
            } else {
                Log::error('Failed to cancel subscription via cron', [
                    'chargebee_subscription_id' => $failure->chargebee_subscription_id,
                    'user_id' => $failure->user_id,
                    'error' => $result['message']
                ]);
            }

        }
    }

    return response()->json([
        'message' => 'Checked and processed expired failed payments.',
        'cancelled_count' => $paymentFailures->count(),
    ]);
}



public function sendMailsTo72HoursFailedPayments(Request $request)
{
    $now = Carbon::now();

    // Get payment failures where it's within 72 hours from created_at
    $paymentFailures = PaymentFailure::where("type", "invoice")
        ->where("status", "!=", "cancelled")
        ->where("created_at", ">=", $now->copy()->subHours(72))
        ->get();

    $sentCount = 0;

    foreach ($paymentFailures as $failure) {
        if (!$failure->user_id || !$failure->chargebee_subscription_id) {
            continue;
        }

        $user = User::find($failure->user_id);
        if (!$user) continue;

        $createdAt = Carbon::parse($failure->created_at);
        $hoursSinceFailure = $createdAt->diffInHours($now);

        if ($hoursSinceFailure > 72) {
            continue; // Outside the 72-hour window
        }

        // Only send one email per calendar day
        $alreadySentToday = \DB::table('payment_failure_email_logs')
            ->where('payment_failure_id', $failure->id)
            ->whereDate('sent_date', $now->toDateString())
            ->exists();

        if ($alreadySentToday) {
            continue;
        }

        try {
            Mail::to($user->email)->queue(new FailedPaymentNotificationMail($user, $failure));

            \DB::table('payment_failure_email_logs')->insert([
                'payment_failure_id' => $failure->id,
                'sent_date' => $now->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sentCount++;
        } catch (\Exception $e) {
            Log::error('Failed to send failed payment email', [
                'user_id' => $user->id,
                'failure_id' => $failure->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    return response()->json([
        'message' => 'Emails sent for failed payments within 72 hours of creation.',
        'sent_count' => $sentCount,
    ]);
}



}

