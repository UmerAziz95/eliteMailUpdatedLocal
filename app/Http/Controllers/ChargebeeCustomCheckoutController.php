<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomCheckOutId;
use App\Services\ChargebeeCustomCheckoutService;
use App\Models\Plan;
//
use App\Models\Feature;
use App\Models\Invoice;
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

class ChargebeeCustomCheckoutController extends Controller
{
    protected $chargebee;

    public function __construct(ChargebeeCustomCheckoutService $chargebee)
    {
        $this->chargebee = $chargebee;
    }




     public function showCustomCheckout($page_id)
    {
        $isValidPage=CustomCheckOutId::where('page_id', $page_id)->first();
        if (!$isValidPage) {
            abort(404, 'Invalid or expired checkout link.');
        }
        session()->put("checkout_page_id",$isValidPage->id);

        $planId = session()->get('discounted_plan_id');
        if (!$planId) {
            abort(404, 'No plan selected for checkout.');
        }
        $plan=Plan::where('id', $planId)->where("is_discounted",true)->first(); // Ensure the plan exists
        if (!$plan) {
            abort(404, 'Plan not found.');
        }    
        $publicPage = true; // Assuming this is a public page will hide the header and footer
        return view('admin.checkout.index', compact('page_id','publicPage','planId','plan'));
    }


  public function calculateCheckout($qty)
{
    $qty = (int) $qty;
    if ($qty <= 0) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid quantity.'
        ], 400);
    }

    $master_plan_id = session()->get('discounted_master_plan_id');
    if (!$master_plan_id) {
        return response()->json([
            'success' => false,
            'message' => 'No plan selected for checkout or plan has been removed.'
        ], 500);
    }

    $plans = Plan::where('master_plan_id', $master_plan_id)
        ->where('is_discounted', true)
        ->where('is_active', true)
        ->get();

    if ($plans->isEmpty()) {
        return response()->json([
            'success' => false,
            'message' => 'Plan not found.'
        ], 500);
    }

    $total_price = 0;
    $price_per_qty = 0;

    foreach ($plans as $discountedPlan) {
        $price = (float) $discountedPlan->price; // 0.68
        $min_inbox = (int) $discountedPlan->min_inbox;
        $max_inbox = (int) $discountedPlan->max_inbox;


        //without infinite
        if ($min_inbox > 0 && $max_inbox > 0) { 
            if ($qty >= $min_inbox && $qty <= $max_inbox) {
                $total_price = $price * $qty;
                $price_per_qty = $price;
                session()->put('discounted_plan_id',$discountedPlan->id);
                break; // stop after finding the matching range
            }
            //infinite consition
        } elseif ($min_inbox > 0 && $max_inbox == 0) {
            if ($qty >= $min_inbox) {
                $total_price = $price * $qty;
                $price_per_qty = $price;
                session()->put('discounted_plan_id',$discountedPlan->id);
                break;
            }
        }
    }

    return response()->json([
        'success' => true,
        'total_price' => round($total_price, 2),
        'price_per_qty' => round($price_per_qty, 2),
        'plans'=>$plans
    ]);
}





        public function subscribe(Request $request)
        {
             $page_id=session()->get("checkout_page_id");
              $isValidPage=CustomCheckOutId::where('id', $page_id)->first();
           if (!$isValidPage) {
                return response()->json([
                    'message' => 'Page has expired!',
                    'status'=>419
                ], 419);
            }
           $request->validate([
                'cbtoken'      => 'required|string',
                'vaultToken'   => 'required|string',
                'email'        => 'required|email',
                'first_name'   => 'required|string|max:255',
                'last_name'    => 'required|string|max:255',
                'address_line1'=> 'required|string|max:500',
                'city'         => 'required|string|max:255',
                'state'        => 'required|string|max:255',
                'zip'          => 'required|string|max:20',
                'country'      => 'required|string|max:255',
                'quantity'    => 'required|integer|min:1',
            ]);

            
            try {
                $planId = session()->get('discounted_plan_id');
                // ✅ Static test values (you can replace with dynamic later)
                $email = $request->email;
                $firstName = $request->first_name;
                $lastName =$request->last_name;
                $addressLine1 = $request->address_line1;
                $city = $request->city;
                $state = $request->state;
                $zip = $request->zip;
                $country = $request->country;
                $quantity = $request->quantity;

                // 1. Create the customer
                $customer = $this->chargebee->createCustomer($email, $firstName, $lastName, $addressLine1, $city, $state, $zip, $country);
                $customerId = $customer->id;
            
                $attachedResult=  $this->chargebee->attachPaymentSource($customerId, $request->cbtoken, $request->vaultToken);

                $result = $this->chargebee->createSubscription($planId, $customerId, $quantity);
                 //$result= $this->chargebee->createSubscriptionWithPaymentToken($request->vaultToke);
                 //d($result["subscription"],$result["subscription"]); // Debugging line, remove in production
                 $subscription = $result["subscription"]->getValues();
                 if($subscription["id"]){
                            $page_id=session()->get("checkout_page_id");
                            $isValidPage=CustomCheckOutId::where('id', $page_id)->first();
                            if($isValidPage){
                                $isValidPage->delete();
                            }

                            $subscreationCreationResponse=$this->subscriptionSuccess($result,$customer);
                            $responseMessage=$subscreationCreationResponse["success"] ? "Subscription successful":"Subsction Created but failed to save data in system,Please contact support immediately!";
                            return response()->json([
                            'message' =>$responseMessage,
                            "chargebee_ok"=>$subscription["id"]? true:false,
                            "saved_db_ok"=>$subscreationCreationResponse["success"] ?true:false,
                            "subscription_id"=>$subscription["id"],
                            'redirect_url'=>url('/customer/dashboard')
                            ], 200); 
                  }
                else{
                    return response()->json([
                        'message' =>"Failed to create subscription",
                        'subscription' =>null,
                        'invoice' => null,
                    ], 500);
                    }
                } catch (\Exception $e) {
                    return response()->json([
                        'error' => $e->getMessage()
                    ], 500);
                }
        }



         public function subscriptionSuccess($content,$customerData)
    {
        try {
         
            $subscription = $content["subscription"]->getValues() ?? null;
            $customer =$customerData ?? null;
            $invoice = $content["invoice"]->getValues() ?? null;
            $shippingAddress =$invoice["billing_address"] ?? null;
            // dd($subscription, $customer, $invoice, $shippingAddress);
            // exit();
            //shipping address
            $firstName = $shippingAddress['first_name'] ?? '';
            $lastName = $shippingAddress['last_name'] ?? '';
            $line1 = $shippingAddress['line1'] ?? '';
            $line2 =  '';
            $city = $shippingAddress['city'] ?? '';
            $state = $shippingAddress['state'] ?? '';
            $country = $shippingAddress['country'] ?? '';
            $zip = $shippingAddress['zip'] ?? '';
            $validationStatus = $shippingAddress['validation_status'] ?? '';
            $plan_id = null;
            $charge_plan_id = null;


              if(!Auth::check()){
                $user = User::where('email', $customer->email)->first();
                if(!$user){
                    $user = new User();
                    $user->email = $customer->email;
                    $user->name = $customer->firstName.' '. $customer->lastName ?? 'Guest';
                     $randomPassword = Str::upper(Str::random(5)) . rand(100, 999);
                     $user->password=Hash::make($randomPassword);
                        $user->role_id = 3; // Assuming 3 is the role_id for customers
                        $user->status=1;
                        $user->billing_address = $line1;
                        $user->billing_address2 = $line2;
                        $user->billing_city = $city;
                        $user->billing_state = $state;
                        $user->billing_country = $country;
                        $user->billing_zip = $zip;
                        $user->save();
                        Auth::login($user);
                }
                else{
                    Auth::login($user);
                }  
            try {
            Mail::to($user->email)->queue(new SendPasswordMail($user,$randomPassword));
             } catch (\Exception $e) {
               Log::error('Failed to send user credentials : '.$user->email . $e->getMessage());
              }
            }   
    
            if ($subscription && $subscription["subscription_items"]) {
                $charge_plan_id = $subscription["subscription_items"][0]->itemPriceId ?? null;
                $quantity =$subscription["subscription_items"][0]->quantity ?? 1;
                
                // Find plan based on quantity range instead of chargebee_plan_id
                $plan = Plan::where('is_active', 1)
                    ->where('min_inbox', '<=', $quantity)
                    ->where(function ($query) use ($quantity) {
                        $query->where('max_inbox', '>=', $quantity)
                              ->orWhere('max_inbox', 0); // 0 means unlimited
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
          
            if (!$subscription || !$customer || !$invoice) {
                return [
                    'success' => false,
                    'message' => 'Missing subscription, customer, or invoice data.'
                ];
            }

            $meta_json = json_encode([
                'invoice' => $invoice,
                'customer' => $customer,
                'subscription' => $subscription,
            ]);
            

            // create session for set observer_total_inboxes
            
            session()->put('observer_total_inboxes', $subscription->subscription_items[0]["quantity"] ?? 1);
           

      
                  $order = Order::firstOrCreate(
                ['chargebee_invoice_id' => $invoice["id"]],
                [
                    'user_id' => $user->id,
                    'plan_id' => $plan_id,
                    'chargebee_customer_id' => $customer->id,
                    'chargebee_subscription_id' => $subscription["id"],
                    'amount' => ($invoice["amount_paid"] ?? 0) / 100,
                    'status' => $invoice["status"],
                    'currency' =>$invoice["currency_code"],
                    'paid_at' => Carbon::createFromTimestamp($invoice["paid_at"])->toDateTimeString(),
                    'meta' => $meta_json,
                ]
            );
            // Create or update order
          
        
                // status_manage_by_admin
                $order->update([
                    'status_manage_by_admin' => 'draft',
                ]);
                // get total per unit qty from chargebee
                $total_inboxes =  $subscription->subscription_items[0]["quantity"] ?? 1;
              
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
            
            
            // Create or update invoice
            $existingInvoice = Invoice::where('chargebee_invoice_id', $invoice["id"])->first();
           
            if ($existingInvoice) {
                $existingInvoice->update([
                    'chargebee_customer_id' => $customer->id,
                    'chargebee_subscription_id' => $subscription["id"],
                    'user_id' => $user->id,
                    'plan_id' => $plan_id,
                    'order_id' => $order->id,
                    'amount' => ($invoice["paid_at"] ?? 0) / 100,
                    'status' => $invoice["status"],
                    'paid_at' => Carbon::createFromTimestamp($invoice["paid_at"])->toDateTimeString(),
                    'metadata' => $meta_json,
                ]);
            } else {
                $existingInvoice = Invoice::create([
                    'chargebee_invoice_id' => $invoice["id"],
                    'chargebee_customer_id' => $customer->id,
                    'chargebee_subscription_id' => $subscription["id"],
                    'user_id' => $user->id,
                    'plan_id' => $plan_id,
                    'order_id' => $order->id,
                    'amount' => ($invoice["paid_at"] ?? 0) / 100,
                    'status' => $invoice["status"],
                    'paid_at' => Carbon::createFromTimestamp($invoice["paid_at"])->toDateTimeString(),
                    'metadata' => $meta_json,
                ]);
            }

            // dd($existingInvoice, $order, $subscription, $customer, $plan_id);
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
                ['chargebee_subscription_id' => $subscription["id"]],
                [
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'chargebee_invoice_id' => $invoice["id"],
                    'chargebee_customer_id' => $customer->id,
                    'plan_id' => $plan_id,
                    'status' => $subscription["status"],
                    'start_date' => Carbon::now(),
                    'meta' => $meta_json,
                    // subscription last_billing_date, next_billing_date
                    'last_billing_date' => Carbon::createFromTimestamp($invoice["paid_at"])->toDateTimeString(),
                    'next_billing_date' => Carbon::createFromTimestamp($invoice["paid_at"])->addMonth()->addDay()->toDateTimeString(),
                ]
            );

           
            // Update user's subscription status
            $user->update([
                'subscription_id' => $subscription["id"],
                'subscription_status' => $subscription["status"],
                'plan_id' => $plan_id,
                'chargebee_customer_id' => $customer->id,
            ]);


            
            // destroy session order_info
            if (session()->has('order_info')) {
                session()->forget('order_info');
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
                    'chargebee_subscription_id' => $subscription["id"],
                    'chargebee_invoice_id' => $invoice["id"],
                    'amount' => ($invoice["amount_paid"] ?? 0) / 100,
                    'status' => $invoice["status"],
                    'paid_at' => Carbon::createFromTimestamp($invoice["paid_at"])->toDateTimeString(),
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
                    'chargebee_subscription_id' => $subscription["id"],
                    'chargebee_invoice_id' => $invoice["id"],
                    'amount' => ($invoice["amount_paid"] ?? 0) / 100,
                    'status' => $invoice["status"],
                    'paid_at' => Carbon::createFromTimestamp($invoice["paid_at"])->toDateTimeString(),
                ]
            );

         
            // Create notification for the customer after sending mail
            Notification::create([
                'user_id' => $user->id,
                'type' => 'subscription_created',
                'title' => 'New Subscription Created',
                'message' => "Your subscription #{$subscription["id"]} has been created successfully",
                'data' => [
                    'subscription_id' => $subscription["id"],
                    'amount' => ($invoice["amount_paid"] ?? 0) / 100
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
                    'chargebee_subscription_id' => $subscription["id"],
                    'chargebee_invoice_id' => $invoice["id"],
                    'amount' => ($invoice["amount_paid"] ?? 0) / 100,
                    'status' => $invoice["status"],
                    'paid_at' => Carbon::createFromTimestamp($invoice["paid_at"])->toDateTimeString(),
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
           
            return [
                    'success' => true,
                    'message' => 'Subscription has created successfully'
                ];

                } catch (\Exception $e) {
                    return [
                    'success' => false,
                    'message' => 'Subscription creation has failed, Error'.' '.$e->getMessage()
                ];
                }
    }
   
} 

