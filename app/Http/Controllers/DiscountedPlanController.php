<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use ChargeBee\ChargeBee\Models\HostedPage;
use ChargeBee\ChargeBee\Models\Subscription;
use App\Models\DiscordSettings;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Models\CustomCheckoutId;

class DiscountedPlanController extends Controller
{
    //
   public function index($id = null)
{
    if ($id !== null) {
        $setting = DiscordSettings::where('url_string', $id)->first();

        if (!$setting) {
            abort(404, 'Invalid or expired discount link.');
        }
    }
    if($id==null){
         abort(404, 'Invalid or expired discount link.');
    }

    
    $getMostlyUsed = Plan::getMostlyUsed();
    $plans = Plan::with('features')
        ->where('is_active', true)
        ->where('is_discounted', true)
        ->get();

    $publicPage = true; 
    $url_string = $id; // Assuming $id is the encrypted string for the discount link
    return view('customer.public_outside.discounted_plans', compact('plans', 'getMostlyUsed', 'publicPage','url_string', 'id'));
}


      public function initiateSubscription(Request $request, $planId, $encrypted=null)
    {
        
        if(!$planId){
            return response()->json([
                'success' => false,
                'message' => 'Plan does not found.'
            ], 500);
        }
      
        $setting = DiscordSettings::where('url_string', $encrypted)->first();
        if (!$setting) {
           return response()->json([
                'success' => false,
                'message' => 'Invalid or expired discount link.'
            ], 500);
        }
        $plan = Plan::where('id', $planId)->where('is_discounted', true)->first();
        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Plan not found or not discounted.'
            ], 404);
        }

      
        session()->put('discounted_plan_id', $planId);
        session()->put('discounted_master_plan_id', $plan->master_plan_id);
    try { 
        $uuid=Str::uuid()->toString();
        $hostedPageUrl = URL::to('/custom/checkout/'.$uuid);
        CustomCheckoutId::create([
            'page_id'=>$uuid
        ]);
           
            return response()->json([
                'success' => true,
                'hosted_page_url' =>  $hostedPageUrl 
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate subscription: ' . $e->getMessage()
            ], 500);
        }
        
    }
}
