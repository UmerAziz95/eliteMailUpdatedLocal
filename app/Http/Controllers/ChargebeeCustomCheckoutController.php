<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomCheckoutId;
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
                $verified_user=session()->get('verified_discounted_user');
                if(isset($verified_user)){
                    $email = $verified_user->email;
                    $firstName = $verified_user->name;
                }
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

                            $subscreationCreationResponse=$this->subscriptionSuccess($result,$customer); //for database record
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



  public function subscriptionSuccess($content, $customerData)
{
    try {
        // Extract and validate data
        $subscription = $content["subscription"]->getValues() ?? null;
        $customer     = $customerData ?? null;
        $invoice      = $content["invoice"]->getValues() ?? null;

        if (!$subscription || !$customer || !$invoice) {
            return $this->errorResponse('Missing subscription, customer, or invoice data.');
        }

        // Extract shipping/billing details
        $shippingAddress = $invoice["billing_address"] ?? [];
        $billingData     = $this->extractBillingData($shippingAddress);

        // Create or update user
        $user = $this->getOrCreateUser($customer, $billingData);

        // Determine plan from quantity
        $quantity  = $subscription["subscription_items"][0]->quantity ?? 1;
        $plan      = $this->findPlanByQuantity($quantity);
        $planId    = $plan?->id ?? null;
        $chargebeePlanId = $subscription["subscription_items"][0]->itemPriceId ?? null;

        // Save updated billing info
        $this->updateUserBilling($user, $billingData);

        // Create order
        $order = $this->createOrUpdateOrder($invoice, $user, $planId, $subscription, $customer);

        // Create reorder info
        $this->createReorderInfo($order, $user, $planId, $quantity);

        // Create invoice
        $existingInvoice = $this->createOrUpdateInvoice($invoice, $user, $planId, $order, $subscription, $customer);

        // Update GHL
        $this->updateGHL($user, $existingInvoice);

        // Create or update subscription
        $this->createOrUpdateUserSubscription($subscription, $invoice, $user, $planId, $order, $customer);

        // Update user subscription status
        $this->updateUserSubscriptionStatus($user, $subscription, $planId, $customer);

        // Clear old session data
        session()->forget('order_info');

        // Log activities
        $this->logActivities($user, $order, $planId, $subscription, $invoice, $existingInvoice);

        // Send notifications & emails
        $this->sendOrderEmails($order, $user);
        $this->notifyContractorsIfUnassigned($order);

        return $this->successResponse('Subscription has been created successfully');

    } catch (\Exception $e) {
        return $this->errorResponse('Subscription creation failed: ' . $e->getMessage());
    }
}

private function extractBillingData($address)
{
    return [
        'line1' => $address['line1'] ?? '',
        'line2' => '',
        'city' => $address['city'] ?? '',
        'state' => $address['state'] ?? '',
        'country' => $address['country'] ?? '',
        'zip' => $address['zip'] ?? '',
    ];
}

private function getOrCreateUser($customer, $billingData)
{
    
    if (Auth::check()) {
        $user = Auth::user();
        $user->chargebee_customer_id = $customer->id;
        $user->save();

        return Auth::user();
    }

    $user = User::where('email', $customer->email)->first();
    if ($user) {
        $user->chargebee_customer_id = $customer->id;
        $user->save();
    }
    if (!$user) {
        $randomPassword = Str::upper(Str::random(5)) . rand(100, 999);
        $user = User::create([
            'email' => $customer->email,
            'name' => $customer->firstName . ' ' . ($customer->lastName ?? 'Guest'),
            'password' => Hash::make($randomPassword),
            'role_id' => 3,
            'status' => 1,
            'phone' => $customer->phone ?? '',
            'chargebee_customer_id' => $customer->id,
            'billing_address' => $billingData['line1'],
            'billing_address2' => $billingData['line2'],
            'billing_city' => $billingData['city'],
            'billing_state' => $billingData['state'],
            'billing_country' => $billingData['country'],
            'billing_zip' => $billingData['zip'],
        ]);

        try {
            Mail::to($user->email)->queue(new SendPasswordMail($user, $randomPassword));
        } catch (\Exception $e) {
            Log::error("Failed to send user credentials: {$user->email} - " . $e->getMessage());
        }
    }

    Auth::login($user);
    return $user;
}

private function findPlanByQuantity($quantity)
{
    return Plan::where('is_active', 1)
        ->where('min_inbox', '<=', $quantity)
        ->where(function ($query) use ($quantity) {
            $query->where('max_inbox', '>=', $quantity)
                ->orWhere('max_inbox', 0);
        })
        ->orderBy('min_inbox', 'desc')
        ->first();
}

private function updateUserBilling($user, $billingData)
{
    $user->fill([
        'billing_address' => $billingData['line1'],
        'billing_address2' => $billingData['line2'],
        'billing_city' => $billingData['city'],
        'billing_state' => $billingData['state'],
        'billing_country' => $billingData['country'],
        'billing_zip' => $billingData['zip'],
        'billing_address_syn' => 1,
    ])->save();
}

private function createOrUpdateOrder($invoice, $user, $planId, $subscription, $customer)
{
    $order = Order::firstOrCreate(
        ['chargebee_invoice_id' => $invoice["id"]],
        [
            'user_id' => $user->id,
            'plan_id' => $planId,
            'chargebee_customer_id' => $customer->id,
            'chargebee_subscription_id' => $subscription["id"],
            'amount' => ($invoice["amount_paid"] ?? 0) / 100,
            'status' => $invoice["status"],
            'currency' => $invoice["currency_code"],
            'paid_at' => Carbon::createFromTimestamp($invoice["paid_at"]),
            'meta' => json_encode(compact('invoice', 'customer', 'subscription')),
        ]
    );

    $order->update(['status_manage_by_admin' => 'draft']);
    return $order;
}

private function createReorderInfo($order, $user, $planId, $quantity)
{
    $order->reorderInfo()->create([
        'user_id' => $user->id,
        'plan_id' => $planId,
        'total_inboxes' => $quantity,
        'inboxes_per_domain' => 1,
        'first_name' => $user->name,
        'persona_password' => '123',
    ]);
}

private function createOrUpdateInvoice($invoice, $user, $planId, $order, $subscription, $customer)
{
    return Invoice::updateOrCreate(
        ['chargebee_invoice_id' => $invoice["id"]],
        [
            'chargebee_customer_id' => $customer->id,
            'chargebee_subscription_id' => $subscription["id"],
            'user_id' => $user->id,
            'plan_id' => $planId,
            'order_id' => $order->id,
            'amount' => ($invoice["paid_at"] ?? 0) / 100,
            'status' => $invoice["status"],
            'paid_at' => Carbon::createFromTimestamp($invoice["paid_at"]),
            'metadata' => json_encode(compact('invoice', 'customer', 'subscription')),
        ]
    );
}

private function updateGHL($user, $invoice)
{
    try {
        $ghlService = new \App\Services\AccountCreationGHL();
        if ($ghlService->isEnabled()) {
            $result = $ghlService->updateContactToCustomer($user, 'customer');
            if ($result) {
                Log::info('GHL contact updated', compact('user', 'invoice', 'result'));
            } else {
                Log::warning('Failed GHL contact update', compact('user', 'invoice', 'result'));
            }
        }
    } catch (\Exception $e) {
        Log::error('GHL update error', ['error' => $e->getMessage()]);
    }
}

private function createOrUpdateUserSubscription($subscription, $invoice, $user, $planId, $order, $customer)
{
    UserSubscription::updateOrCreate(
        ['chargebee_subscription_id' => $subscription["id"]],
        [
            'user_id' => $user->id,
            'order_id' => $order->id,
            'chargebee_invoice_id' => $invoice["id"],
            'chargebee_customer_id' => $customer->id,
            'plan_id' => $planId,
            'status' => $subscription["status"],
            'start_date' => Carbon::now(),
            'last_billing_date' => Carbon::createFromTimestamp($invoice["paid_at"]),
            'next_billing_date' => Carbon::createFromTimestamp($invoice["paid_at"])->addMonth()->addDay(),
        ]
    );
}

private function updateUserSubscriptionStatus($user, $subscription, $planId, $customer)
{
    $user->update([
        'subscription_id' => $subscription["id"],
        'subscription_status' => $subscription["status"],
        'plan_id' => $planId,
        'chargebee_customer_id' => $customer->id,
    ]);
}

private function logActivities($user, $order, $planId, $subscription, $invoice, $existingInvoice)
{
    ActivityLogService::log('customer-order-created', "Order created: {$order->id}", $order);
    ActivityLogService::log('customer-subscription-created', "Subscription created", $subscription);
    ActivityLogService::log('customer-invoice-processed', "Invoice processed", $existingInvoice);
}

private function sendOrderEmails($order, $user)
{
    try {
        Mail::to($user->email)->queue(new OrderCreatedMail($order, $user, false));
        $superAdmins = User::where('role_id', 1)->get();
        foreach ($superAdmins as $admin) {
            Mail::to($admin->email)->queue(new OrderCreatedMail($order, $user, true));
        }
    } catch (\Exception $e) {
        Log::error('Failed to send order emails: ' . $e->getMessage());
    }
}

private function notifyContractorsIfUnassigned($order)
{
    if (!$order->assigned_to) {
        $contractors = User::where('role_id', 4)->get();
        foreach ($contractors as $contractor) {
            try {
                Mail::to($contractor->email)->queue(new OrderCreatedMail($order, $contractor, true));
            } catch (\Exception $e) {
                Log::error('Contractor email failed: ' . $e->getMessage());
            }
        }
    }
}

private function successResponse($message)
{
    return ['success' => true, 'message' => $message];
}

private function errorResponse($message)
{
    return ['success' => false, 'message' => $message];
}


   
} 

