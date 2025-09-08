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
use App\Models\OrderPaymentLog;

class ChargebeeCustomCheckoutController extends Controller
{
    protected $chargebee;

    public function __construct(ChargebeeCustomCheckoutService $chargebee)
    {
        $this->chargebee = $chargebee;
    }

    // countries function to convert country name to short name
    public function countries()
    {
        $countries = [
            'Afghanistan' => 'AF',
            'Albania' => 'AL',
            'Algeria' => 'DZ',
            'Andorra' => 'AD',
            'Angola' => 'AO',
            'Antigua and Barbuda' => 'AG',
            'Argentina' => 'AR',
            'Armenia' => 'AM',
            'Australia' => 'AU',
            'Austria' => 'AT',
            'Azerbaijan' => 'AZ',
            'Bahamas' => 'BS',
            'Bahrain' => 'BH',
            'Bangladesh' => 'BD',
            'Barbados' => 'BB',
            'Belarus' => 'BY',
            'Belgium' => 'BE',
            'Belize' => 'BZ',
            'Benin' => 'BJ',
            'Bhutan' => 'BT',
            'Bolivia' => 'BO',
            'Bosnia and Herzegovina' => 'BA',
            'Botswana' => 'BW',
            'Brazil' => 'BR',
            'Brunei' => 'BN',
            'Bulgaria' => 'BG',
            'Burkina Faso' => 'BF',
            'Burundi' => 'BI',
            'Cabo Verde' => 'CV',
            'Cambodia' => 'KH',
            'Cameroon' => 'CM',
            'Canada' => 'CA',
            'Central African Republic' => 'CF',
            'Chad' => 'TD',
            'Chile' => 'CL',
            'China' => 'CN',
            'Colombia' => 'CO',
            'Comoros' => 'KM',
            'Congo (Congo-Brazzaville)' => 'CG',
            'Costa Rica' => 'CR',
            'Croatia' => 'HR',
            'Cuba' => 'CU',
            'Cyprus' => 'CY',
            'Czech Republic' => 'CZ',
            'Democratic Republic of the Congo' => 'CD',
            'Denmark' => 'DK',
            'Djibouti' => 'DJ',
            'Dominica' => 'DM',
            'Dominican Republic' => 'DO',
            'Ecuador' => 'EC',
            'Egypt' => 'EG',
            'El Salvador' => 'SV',
            'Equatorial Guinea' => 'GQ',
            'Eritrea' => 'ER',
            'Estonia' => 'EE',
            'Eswatini' => 'SZ',
            'Ethiopia' => 'ET',
            'Fiji' => 'FJ',
            'Finland' => 'FI',
            'France' => 'FR',
            'Gabon' => 'GA',
            'Gambia' => 'GM',
            'Georgia' => 'GE',
            'Germany' => 'DE',
            'Ghana' => 'GH',
            'Greece' => 'GR',
            'Grenada' => 'GD',
            'Guatemala' => 'GT',
            'Guinea' => 'GN',
            'Guinea-Bissau' => 'GW',
            'Guyana' => 'GY',
            'Haiti' => 'HT',
            'Honduras' => 'HN',
            'Hungary' => 'HU',
            'Iceland' => 'IS',
            'India' => 'IN',
            'Indonesia' => 'ID',
            'Iran' => 'IR',
            'Iraq' => 'IQ',
            'Ireland' => 'IE',
            'Israel' => 'IL',
            'Italy' => 'IT',
            'Ivory Coast' => 'CI',
            'Jamaica' => 'JM',
            'Japan' => 'JP',
            'Jordan' => 'JO',
            'Kazakhstan' => 'KZ',
            'Kenya' => 'KE',
            'Kiribati' => 'KI',
            'Kuwait' => 'KW',
            'Kyrgyzstan' => 'KG',
            'Laos' => 'LA',
            'Latvia' => 'LV',
            'Lebanon' => 'LB',
            'Lesotho' => 'LS',
            'Liberia' => 'LR',
            'Libya' => 'LY',
            'Liechtenstein' => 'LI',
            'Lithuania' => 'LT',
            'Luxembourg' => 'LU',
            'Madagascar' => 'MG',
            'Malawi' => 'MW',
            'Malaysia' => 'MY',
            'Maldives' => 'MV',
            'Mali' => 'ML',
            'Malta' => 'MT',
            'Marshall Islands' => 'MH',
            'Mauritania' => 'MR',
            'Mauritius' => 'MU',
            'Mexico' => 'MX',
            'Micronesia' => 'FM',
            'Moldova' => 'MD',
            'Monaco' => 'MC',
            'Mongolia' => 'MN',
            'Montenegro' => 'ME',
            'Morocco' => 'MA',
            'Mozambique' => 'MZ',
            'Myanmar' => 'MM',
            'Namibia' => 'NA',
            'Nauru' => 'NR',
            'Nepal' => 'NP',
            'Netherlands' => 'NL',
            'New Zealand' => 'NZ',
            'Nicaragua' => 'NI',
            'Niger' => 'NE',
            'Nigeria' => 'NG',
            'North Korea' => 'KP',
            'North Macedonia' => 'MK',
            'Norway' => 'NO',
            'Oman' => 'OM',
            'Pakistan' => 'PK',
            'Palau' => 'PW',
            'Palestine' => 'PS',
            'Panama' => 'PA',
            'Papua New Guinea' => 'PG',
            'Paraguay' => 'PY',
            'Peru' => 'PE',
            'Philippines' => 'PH',
            'Poland' => 'PL',
            'Portugal' => 'PT',
            'Qatar' => 'QA',
            'Romania' => 'RO',
            'Russia' => 'RU',
            'Rwanda' => 'RW',
            'Saint Kitts and Nevis' => 'KN',
            'Saint Lucia' => 'LC',
            'Saint Vincent and the Grenadines' => 'VC',
            'Samoa' => 'WS',
            'San Marino' => 'SM',
            'Sao Tome and Principe' => 'ST',
            'Saudi Arabia' => 'SA',
            'Senegal' => 'SN',
            'Serbia' => 'RS',
            'Seychelles' => 'SC',
            'Sierra Leone' => 'SL',
            'Singapore' => 'SG',
            'Slovakia' => 'SK',
            'Slovenia' => 'SI',
            'Solomon Islands' => 'SB',
            'Somalia' => 'SO',
            'South Africa' => 'ZA',
            'South Korea' => 'KR',
            'South Sudan' => 'SS',
            'Spain' => 'ES',
            'Sri Lanka' => 'LK',
            'Sudan' => 'SD',
            'Suriname' => 'SR',
            'Sweden' => 'SE',
            'Switzerland' => 'CH',
            'Syria' => 'SY',
            'Taiwan' => 'TW',
            'Tajikistan' => 'TJ',
            'Tanzania' => 'TZ',
            'Thailand' => 'TH',
            'Timor-Leste' => 'TL',
            'Togo' => 'TG',
            'Tonga' => 'TO',
            'Trinidad and Tobago' => 'TT',
            'Tunisia' => 'TN',
            'Turkey' => 'TR',
            'Turkmenistan' => 'TM',
            'Tuvalu' => 'TV',
            'Uganda' => 'UG',
            'Ukraine' => 'UA',
            'United Arab Emirates' => 'AE',
            'United Kingdom' => 'GB',
            'United States' => 'US',
            'Uruguay' => 'UY',
            'Uzbekistan' => 'UZ',
            'Vanuatu' => 'VU',
            'Vatican City' => 'VA',
            'Venezuela' => 'VE',
            'Vietnam' => 'VN',
            'Yemen' => 'YE',
            'Zambia' => 'ZM',
            'Zimbabwe' => 'ZW'
        ];
        return $countries;
    }


     public function showCustomCheckout($page_id, $user_id = null)
    {
        $isValidPage=CustomCheckOutId::where('page_id', $page_id)->first();
        if (!$isValidPage) {
            abort(404, 'Invalid or expired checkout link.');
        }
        // dd($isValidPage);
        session()->put("checkout_page_id",$isValidPage->id);

        $planId = session()->get('discounted_plan_id');
        if (!$planId) {
            abort(404, 'No plan selected for checkout.');
        }
        $plan=Plan::where('id', $planId)->where("is_discounted",true)->first(); // Ensure the plan exists
        if (!$plan) {
            abort(404, 'Plan not found.');
        }  
        $planId = $plan->id;
        // temp_user_custom_checkout
        $user = session()->get('temp_user_custom_checkout');
        $countries = $this->countries();
        $publicPage = true; // Assuming this is a public page will hide the header and footer
        $url = $this->initiateSubscription($planId, $user_id);
        // redirect to url
        // dd($url);
        return redirect($url);
        // return view('admin.checkout.index', compact('page_id','publicPage','planId','plan','user','countries'));
    }
    public function initiateSubscription($planId, $user_id = null)
    {
        if(!$planId ){
            abort(404);
        }
        
        try {
           $plan = Plan::findOrFail($planId);
           
            // Check if user is already logged in
            $user = Auth::check() ? auth()->user() : null;
            if($user == null){
                $user = session()->get('temp_user_custom_checkout');
            }
            if (!$user) {
                $user_id = Crypt::decryptString($user_id);
                $user = User::where('id', $user_id)->first();
                if(!$user) {
                    abort(404, 'User not found, auth failed please login or contact to support');
                }
                // abort(404, 'User not found, auth failed please login or contact to support');
            }

            // get charge_customer_id from user
            $charge_customer_id = $user->chargebee_customer_id ?? null;
            
            if ($charge_customer_id == null) {
                // Create hosted page for subscription
                $result = HostedPage::checkoutNewForItems([
                    "subscription_items" => [
                        [
                            "item_price_id" => $plan->chargebee_plan_id,
                            "quantity" => session()->has('order_info') ? session()->get('order_info')['total_inboxes'] : 1,
                            "quantity_editable" => true,
                        ]
                    ],
                    "customer" => [
                        "email" => $user->email,
                        "first_name" => $user->name,
                        "phone" => $user->phone,
                    ],
                    "billing_address" => [
                        "first_name" => $user->name,
                    ],
                    "allow_plan_change" => true,
                    "redirect_url" => route('customer.subscription.success'),
                    "cancel_url" => route('customer.subscription.cancel'),
                    // Expire hosted page after one use and set time limit
                    "expires_at" => time() + (10 * 60), // Expire after 10 minutes
                    "embed" => false, // Ensure it's not embeddable to prevent multiple uses
                ]);
            } else {
                // payment done with old customer
                $result = HostedPage::checkoutNewForItems([
                    "subscription_items" => [
                        [
                            "item_price_id" => $plan->chargebee_plan_id,
                            "quantity" => session()->has('order_info') ? session()->get('order_info')['total_inboxes'] : 1
                        ]
                    ],
                    "customer" => [
                        "id" => $charge_customer_id,
                    ],
                    "billing_address" => [
                        "first_name" => $user->name,
                        "last_name" => "",
                        "line1" => "Address Line 1", // Default value
                        "city" => "City", // Default value
                        "state" => "State", // Default value
                        "zip" => "12345", // Default value
                        "country" => "US" // Default value
                    ],
                    "allow_plan_change" => true,
                    "redirect_url" => route('customer.subscription.success'),
                    "cancel_url" => route('customer.subscription.cancel'),
                    // Expire hosted page after one use and set time limit
                    "expires_at" => time() + (10 * 60), // Expire after 10 minutes
                    "embed" => false, // Ensure it's not embeddable to prevent multiple uses
                ]);
            }
            $hostedPage = $result->hostedPage();

            return $hostedPage->url;
        } catch (\Exception $e) {
            Log::error('Failed to initiate subscription: ' . $e->getMessage());
            abort(500, 'Failed to initiate subscription: ' . $e->getMessage());
        }
        
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
            ->where('is_discounted', 1)
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
            // Validate and extract form data
            $email = $request->email;
            $firstName = $request->first_name;
            $lastName = $request->last_name;
            $addressLine1 = $request->address_line1;
            $city = $request->city;
            $state = $request->state;
            $zip = $request->zip;
            $country = $request->country;
            $quantity = (int) $request->quantity;
            $planCheck = $this->findPlanByQuantity($quantity);
            $planId=$planCheck->chargebee_plan_id ?? $planId;
            
            // Additional quantity validation
            if ($quantity <= 0) {
                return response()->json([
                    'error' => 'Invalid quantity provided: ' . $quantity
                ], 400);
            }
            
            session()->put('observer_total_inboxes', $quantity);
            Log::info("Processing subscription with quantity: " . $quantity);

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
            
            $subscription = $result["subscription"]->getValues();
            
            if($subscription["id"]){
                $page_id=session()->get("checkout_page_id");
                $isValidPage=CustomCheckOutId::where('id', $page_id)->first();
                if($isValidPage){
                    $isValidPage->delete();
                }
                $this->createOrderPaymentLog($subscription["id"], $result, $customer);
                $subscreationCreationResponse = $this->subscriptionSuccess($result, $customer);
                // update is_exception
                $updateOrderPaymentLog = OrderPaymentLog::where('chargebee_subscription_id', $subscription["id"])
                    ->update(['is_exception' => false]);

                $responseMessage = $subscreationCreationResponse["success"] ? 
                    "Subscription successful" : 
                    "Subscription Created but failed to save data in system. Please contact support immediately!";
                
                Log::info("Subscription process completed", [
                    'subscription_id' => $subscription["id"],
                    'chargebee_ok' => true,
                    'saved_db_ok' => $subscreationCreationResponse["success"],
                    'message' => $subscreationCreationResponse["message"] ?? 'No message'
                ]);
                
                return response()->json([
                    'message' => $responseMessage,
                    "chargebee_ok" => $subscription["id"] ? true : false,
                    "saved_db_ok" => $subscreationCreationResponse["success"] ? true : false,
                    "subscription_id" => $subscription["id"],
                    'redirect_url' => url('/discounted/user/redirect/' . $subscription["id"]),
                ], 200); 
            } else {
                // update is_exception
                OrderPaymentLog::where('chargebee_subscription_id', $subscription["id"])
                    ->update(['is_exception' => true]);
                return response()->json([
                    'message' =>"Failed to create subscription",
                    'subscription' =>null,
                    'invoice' => null,
                ], 500);
            }
        } catch (\Exception $e) {
            // update is_exception
            OrderPaymentLog::where('chargebee_subscription_id', $subscription["id"] ?? null)
                ->update(['is_exception' => true]);
            Log::error("Subscription creation failed: " . $e->getMessage(), [
                'email' => $email ?? 'unknown',
                'quantity' => $quantity ?? 'unknown',
                'plan_id' => $planId ?? 'unknown',
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }



  public function subscriptionSuccess($content, $customerData)
{
    try {
        // Extract and validate data
        $subscription = (array) $content["subscription"]->getValues() ?? null;
        $customer     = $customerData ?? null;
        $invoice      = $content["invoice"]->getValues() ?? null;
        
        // if (!$subscription || !$customer || !$invoice) {
        //     return $this->errorResponse('Missing subscription, customer, or invoice data.');
        // }

        // Extract shipping/billing details
        $shippingAddress = $invoice["billing_address"] ?? [];
        $billingData     = $this->extractBillingData($shippingAddress);
        // Create or update user
        $user = $this->getOrCreateUser($customer, $billingData);
        // Determine plan from quantity with proper validation
        $subscriptionItems = $subscription["subscription_items"] ?? [];
        if (empty($subscriptionItems) || !isset($subscriptionItems[0])) {
            throw new \Exception('No subscription items found in the subscription data');
        }
        $quantity = (int) ($subscriptionItems[0]['quantity'] ?? 1);

        if ($quantity <= 0) {
            throw new \Exception('Invalid quantity found in subscription: ' . $quantity);
        }
        
        $plan = $this->findPlanByQuantity($quantity);

        $planId = $plan?->id ?? null;
        $chargebeePlanId = $subscriptionItems[0]['item_price_id'] ?? null;
        
        Log::info("Subscription quantity details: ", [
            'quantity' => $quantity,
            'planId' => $planId,
            'chargebeePlanId' => $chargebeePlanId
        ]);

        // Save updated billing info
        $this->updateUserBilling($user, $billingData);

        // Create order
        $order = $this->createOrUpdateOrder($invoice, $user, $planId, (array) $subscription, $customer);
        
        Log::info("Order created/updated successfully: {$order}");
        Log::info("------------------------------------------- Custom Checkout Order Details -------------------------------------------");
        Log::info("Custom Checkout Order details: ", [
            'invoice' => $invoice,
            'user' => $user,
            'planId' => $planId,
            'subscription' => $subscription,
            'customer' => $customer,
            'order' => $order
        ]);
        // Create reorder info with proper exception handling
        try {
            $this->createReorderInfo($order, $user, $planId, $quantity);
            Log::info("Reorder info created successfully", [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'plan_id' => $planId,
                'quantity' => $quantity
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create reorder info: " . $e->getMessage(), [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'plan_id' => $planId,
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);
            // Don't throw the exception to avoid breaking the subscription flow
            // but log it for debugging
        }

        Log::info("Custom Checkout Reorder details: ", [
            'order' => $order,
            'user' => $user,
            'planId' => $planId,
            'quantity' => $quantity
        ]);
        Log::info("------------------------------------------- Custom Checkout Reorder Details End -------------------------------------------");
        // Create invoice
        $existingInvoice = $this->createOrUpdateInvoice($invoice, $user, $planId, $order, $subscription, $customer);

        // Update GHL
        $this->updateGHL($user, $existingInvoice);

        // Create or update subscription
        $subscription_obj = $this->createOrUpdateUserSubscription($subscription, $invoice, $user, $planId, $order, $customer);

        // Update user subscription status
        $this->updateUserSubscriptionStatus($user, $subscription, $planId, $customer);

        // Clear old session data
        session()->forget('order_info');

        // Log activities
        $this->logActivities($user, $order, $planId, $subscription_obj, $existingInvoice);

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
        ->where('is_discounted', 1)
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

private function createOrUpdateOrder($invoice, $user, $planId, array $subscription, $customer)
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
    try {
        // Validate input parameters
        if (!$order || !$order->id) {
            throw new \Exception('Invalid order provided for reorder info creation');
        }
        
        if (!$user || !$user->id) {
            throw new \Exception('Invalid user provided for reorder info creation');
        }
        
        if (!$planId) {
            throw new \Exception('Invalid plan ID provided for reorder info creation');
        }
        
        $quantity = (int) $quantity;
        if ($quantity <= 0) {
            throw new \Exception('Invalid quantity provided for reorder info creation: ' . $quantity);
        }
        
        Log::info("Creating reorder info with data: ", [
            'order_id' => $order->id,
            'user_id' => $user->id,
            'plan_id' => $planId,
            'total_inboxes' => $quantity,
            'user_name' => $user->name
        ]);
        
        $reorderInfo = $order->reorderInfo()->create([
            'user_id' => $user->id,
            'plan_id' => $planId,
            'total_inboxes' => $quantity,
            'inboxes_per_domain' => 1,
            'first_name' => $user->name,
            'persona_password' => '123',
        ]);
        
        if (!$reorderInfo || !$reorderInfo->id) {
            throw new \Exception('Failed to create reorder info record');
        }
        
        // Verify the total_inboxes was saved correctly
        $savedReorderInfo = ReorderInfo::find($reorderInfo->id);
        if (!$savedReorderInfo || $savedReorderInfo->total_inboxes != $quantity) {
            Log::error("Reorder info total_inboxes mismatch", [
                'expected' => $quantity,
                'saved' => $savedReorderInfo ? $savedReorderInfo->total_inboxes : 'null',
                'reorder_info_id' => $reorderInfo->id
            ]);
            throw new \Exception('Failed to save total_inboxes correctly in reorder info');
        }
        
        Log::info("Reorder info created and verified successfully", [
            'reorder_info_id' => $reorderInfo->id,
            'total_inboxes' => $savedReorderInfo->total_inboxes,
            'order_id' => $order->id
        ]);
        
        
        return $reorderInfo;
        
    } catch (\Exception $e) {
        Log::error("Exception in createReorderInfo: " . $e->getMessage(), [
            'order_id' => $order ? $order->id : 'null',
            'user_id' => $user ? $user->id : 'null',
            'plan_id' => $planId,
            'quantity' => $quantity,
            'stack_trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}

private function createOrUpdateInvoice($invoice, $user, $planId, $order, $subscription, $customer)
{
   
   
    $invoice=Invoice::updateOrCreate(
        ['chargebee_invoice_id' => $invoice["id"]],
        [
            'chargebee_customer_id' => $customer->id,
            'chargebee_subscription_id' => $subscription["id"],
            'user_id' => $user->id,
            'plan_id' => $planId,
            'order_id' => $order->id,
            'amount' => ($invoice["amount_paid"] ?? 0) / 100,
            'status' => $invoice["status"],
            'paid_at' => Carbon::createFromTimestamp($invoice["paid_at"]),
            'metadata' => json_encode(compact('invoice', 'customer', 'subscription')),
        ]
    );
    
    return $invoice;
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
   $userSubss= UserSubscription::updateOrCreate(
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
    return $userSubss;

    
}

private function updateUserSubscriptionStatus($user, $subscription, $planId, $customer)
{
    $user=$user->update([
        'subscription_id' => $subscription["id"],
        'subscription_status' => $subscription["status"],
        'plan_id' => $planId,
        'chargebee_customer_id' => $customer->id,
    ]);
    
}

private function logActivities($user, $order, $planId, $subscription, $existingInvoice)
{
   
    ActivityLogService::log('customer-order-created', "Order created: {$order["id"]}", $order);
    ActivityLogService::log('customer-subscription-created', "Subscription created", $subscription);
    ActivityLogService::log('customer-invoice-processed', "Invoice processed", $existingInvoice);
    // dd("fklajsf");
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

private function createOrderPaymentLog($subscriptionId, $result, $customer)
{
    try {
        $subscription = $result["subscription"]->getValues();
        $invoice = $result["invoice"]->getValues();
        
        // Get the current user if authenticated, otherwise get from verified session
        $user = Auth::check() ? Auth::user() : session()->get('verified_discounted_user');
        $userId = $user ? $user->id : null;
        
        // Get the plan information
        $quantity = session()->get('observer_total_inboxes', 1);
        $plan = $this->findPlanByQuantity($quantity);
        $planId = $plan ? $plan->id : null;
        
        $paymentLogData = [
            'hosted_page_id' => null, // Set to null as requested for custom checkout
            'user_id' => $userId,
            'is_exception' => true,
            'chargebee_invoice_id' => $invoice["id"] ?? null,
            'chargebee_subscription_id' => $subscriptionId,
            'customer_id' => $customer->id ?? null,
            'plan_id' => $planId,
            'amount' => isset($invoice["amount_paid"]) ? ($invoice["amount_paid"] / 100) : null,
            'payment_status' => $invoice["status"] ?? 'active',
            'invoice_data' => $invoice,
            'customer_data' => $customer->getValues(),
            'subscription_data' => $subscription,
            'response' => $result,
        ];
        
        $paymentLog = OrderPaymentLog::create($paymentLogData);
        
        Log::info('OrderPaymentLog created successfully for custom checkout', [
            'subscription_id' => $subscriptionId,
            'payment_log_id' => $paymentLog->id,
            'user_id' => $userId,
            'plan_id' => $planId
        ]);
        
        return $paymentLog;
        
    } catch (\Exception $e) {
        Log::error('Failed to create OrderPaymentLog for custom checkout: ' . $e->getMessage(), [
            'subscription_id' => $subscriptionId,
            'error' => $e->getMessage(),
            'stack_trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}


   
} 