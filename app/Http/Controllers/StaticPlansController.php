<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MasterPlan;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class StaticPlansController extends Controller
{
    public function index($encrypted = null)
    {
        // Check if user came from a static link
        if (!session('static_plan_data')) {
            return redirect()->route('login');
        }

        // Handle encrypted user data if provided
        $userEmail = null;
        if ($encrypted) {
            try {
                $decrypted = Crypt::decryptString($encrypted);
                [$userEmail, $code, $timestamp] = explode('/', $decrypted);
            } catch (\Exception $e) {
                // Handle decryption error
                $userEmail = null;
            }
        }

        $staticPlanData = session('static_plan_data');
        $masterPlanId = $staticPlanData['master_plan_id'];
        $chargebeePlanId = $staticPlanData['chargebee_plan_id'];

        // Get the master plan and related plans
        $masterPlan = MasterPlan::with(['volumeItems' => function($query) {
            $query->orderBy('max_inbox', 'asc');
        }])->findOrFail($masterPlanId);
        
        // Find the specific plan that matches the chargebee_plan_id
        $selectedPlan = $masterPlan->volumeItems->first();
        if (!$selectedPlan) {
            return redirect()->route('login');
        }
        // $selectedPlan = null;

        return view('static-plans', compact('masterPlan', 'selectedPlan', 'staticPlanData', 'encrypted'));
    }

    public function selectPlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|integer|exists:plans,id'
        ]);

        $planId = $request->plan_id;
        
        // Get plan details to verify it exists and is available
        $plan = Plan::findOrFail($planId);

        // Clear the static plan session data since we're proceeding with subscription
        session()->forget(['static_plan_data']);

        // Redirect to the PlanController's initiateSubscription method
        // Using the customer plan subscription route
        return redirect()->route('customer.plans.subscribe', ['id' => $planId]);
    }

    public function clearSession(Request $request)
    {
        // Clear the static plan session data
        session()->forget(['static_plan_data']);
        session()->forget(['static_link_hit']);
        return response()->json(['success' => true]);
    }
}
