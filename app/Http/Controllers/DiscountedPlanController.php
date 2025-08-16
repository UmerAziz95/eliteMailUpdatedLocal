<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Plan;
use ChargeBee\ChargeBee\Models\HostedPage;
use ChargeBee\ChargeBee\Models\Subscription;
use App\Models\DiscordSettings;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use App\Models\CustomCheckOutId;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use App\Models\Invoice;
use App\Models\Subscription as SubscriptionModel;




class DiscountedPlanController extends Controller
{

  
public function index($id = null)
{
    if (!$id) {
        abort(404, 'Invalid or expired discount link.');
    }

    $setting = DiscordSettings::where('url_string', $id)->first();

    if (!$setting) {
        abort(404, 'Invalid or expired discount link.');
    }

    session()->put('iam_discounted_user', $setting->url_string);

    return redirect()->to('/?type=' . $setting->url_string);
     // $getMostlyUsed = Plan::getMostlyUsed();
    // $plans = Plan::with('features')
    //     ->where('is_active', true)
    //     ->where('is_discounted', true)
    //     ->get();

    // $publicPage = true; 
    // $url_string = $id; // Assuming $id is the encrypted string for the discount link
    // return view('customer.public_outside.discounted_plans', compact('plans', 'getMostlyUsed', 'publicPage','url_string', 'id'));

    // The rest of your code for displaying plans
}

public function verifyDiscountedUser($encrypted, $id)
{
    $discordLink = DiscordSettings::where('url_string', $id)->first();
    if ($discordLink) {
        $decrypted = Crypt::decryptString($encrypted);
        [$email, $expectedCode, $timestamp] = explode('/', $decrypted);

        $user = User::where('email', $email)->firstOrFail();

        if ($user) {
            session()->put('verified_discounted_user', $user);
            session()->forget('iam_discounted_user');
 
            $user->email_verified_at = now();
            $user->email_verification_code = null;
            $user->status = 1;
            $user->save();
                $getMostlyUsed = Plan::getMostlyUsed();
                $plans = Plan::with('features')
                ->where('is_active', true)
                ->where('is_discounted', true)
                ->get();

                $publicPage = true; 
                $url_string = $id; // Assuming $id is the encrypted string for the discount link
                return view('customer.public_outside.discounted_plans', compact('plans', 'getMostlyUsed', 'publicPage','url_string', 'id'));

        }
        else{
            abort(404,"User not found");
        }
    } else {
        abort(404, 'Invalid or expired discount link.');
    }
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
        CustomCheckOutId::create([
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


public function redirectToDashboard($subscription_id)
{
    $subscription = SubscriptionModel::where('chargebee_subscription_id', $subscription_id)->first();

    if (!$subscription) {
        return redirect()->route('customer.dashboard')->with('error', 'Subscription not found.');
    }

    $plan = Plan::findOrFail($subscription->plan_id);

    $amountPaid = Invoice::where('chargebee_subscription_id', $subscription_id)
        ->pluck('amount')
        ->first() ?? 0;

    // Redirect to success page with subscription details
    return view('customer.plans.subscription-success', [
        'subscription_id' => $subscription->id,
        'order_id'        => $subscription->order_id,
        'plan'            => $plan,
        'amount'          => $amountPaid,
    ]);
}

}
