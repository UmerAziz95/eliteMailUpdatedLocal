<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use ChargeBee\ChargeBee\Models\HostedPage;
use ChargeBee\ChargeBee\Models\Subscription;
use App\Models\DiscordSettingS;

class DiscountedPlanController extends Controller
{
    //
   public function index($id = null)
{
    if ($id !== null) {
        $setting = DiscordSettingS::where('url_string', $id)->first();

        if (!$setting) {
            abort(404, 'Invalid or expired discount link.');
        }
    }

    $getMostlyUsed = Plan::getMostlyUsed();
    $plans = Plan::with('features')
        ->where('is_active', true)
        ->where('is_discounted', true)
        ->get();

    $publicPage = true;

    return view('customer.public_outside.discounted_plans', compact('plans', 'getMostlyUsed', 'publicPage'));
}


      public function initiateSubscription(Request $request, $planId,$encrypted=null)
    {
        
        if(!$planId ){
            abort(404);
        }
        
        try {
           $plan = Plan::findOrFail($planId);
          
          
            // get charge_customer_id from user
            $charge_customer_id =null;
            if ($charge_customer_id == null) {
                // Create hosted page for subscription
                $result = HostedPage::checkoutNewForItems([
                    "subscription_items" => [
                        [
                            "item_price_id" => $plan->chargebee_plan_id,
                            "quantity" => 1,
                            "quantity_editable" => true,
                        ]
                    ],
                    "customer" => [
                        "email" => null,
                        "first_name" => null,
                        // "last_name" => "xcxc",
                        "phone" => null,
                    ],
                    "billing_address" => [
                        "first_name" => null,
                       
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
}
