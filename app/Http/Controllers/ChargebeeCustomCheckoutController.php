<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomCheckoutId;
use App\Services\ChargebeeCustomCheckoutService;
use App\Models\Plan;

class ChargebeeCustomCheckoutController extends Controller
{
    protected $chargebee;

    public function __construct(ChargebeeCustomCheckoutService $chargebee)
    {
        $this->chargebee = $chargebee;
    }




     public function showCustomCheckout($page_id)
    {
        $isValidPage=CustomCheckOutId::where('page_id', $page_id)->exists();
        if (!$isValidPage) {
            abort(404, 'Invalid or expired checkout link.');
        }
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
                break; // stop after finding the matching range
            }
            //infinite consition
        } elseif ($min_inbox > 0 && $max_inbox == 0) {
            if ($qty >= $min_inbox) {
                $total_price = $price * $qty;
                $price_per_qty = $price;
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

                return response()->json([
                    'message' => 'Subscription successful',
                    'subscription' => $result["subscription"]->getValues(),
                    'invoice' => isset($result["invoice"]) ? $result["invoice"]->getValues() : null,
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage()
                ], 500);
            }
        }



   
} 

