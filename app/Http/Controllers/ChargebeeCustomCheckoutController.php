<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CustomCheckoutId;
use App\Services\ChargebeeCustomCheckoutService;

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
        
        $publicPage = true; // Assuming this is a public page, adjust as necessary
        return view('admin.checkout.index', compact('page_id','publicPage'));
    }




        public function subscribe(Request $request)
        {
            $request->validate([
                'cbtoken' => 'required|string',
            ]);

            try {
                $planId = session()->get('discounted_plan_id');
                dd($planId); // Debugging line, remove in production
                // ✅ Static test values (you can replace with dynamic later)
                $email = 'test@gmail.com';
                $firstName = 'Test';
                $lastName = 'User';

                // 1. Create the customer
                $customer = $this->chargebee->createCustomer($email, $firstName, $lastName);
                $customerId = $customer->id;
            
                // 2. Attach the payment source using cbtoken
            
               $attachedResult=  $this->chargebee->attachPaymentSource($customerId, $request->cbtoken,$request->vaultToke);
               
                $result = $this->chargebee->createSubscription($planId, $customerId);
            //    $result= $this->chargebee->createSubscriptionWithPaymentToken($request->vaultToke);
                dd($result); // Debugging line, remove in production

                // 4. Return success response
                return response()->json([
                    'message' => 'Subscription successful',
                    'subscription' => $result->getValue()->subscription,
                    'invoice' => $result->getValue()->invoice
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'error' => $e->getMessage()
                ], 500);
            }
        }



   
} 

