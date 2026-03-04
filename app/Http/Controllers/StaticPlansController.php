<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MasterPlan;
use App\Models\Plan;
use App\Models\PoolPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use ChargeBee\ChargeBee\Models\HostedPage;
use Illuminate\Support\Facades\Auth;

class StaticPlansController extends Controller
{
    public function index($encrypted = null)
    {
        // Check if user came from a static link (pool or master plan)
        if (!session('static_plan_data')) {
            return redirect()->route('login');
        }

        // Check static type and handle pool plans differently
        $staticType = session('static_type');
        
        if ($staticType === 'pool_static_plan') {
            return $this->handlePoolStaticPlan($encrypted);
        }

        // Handle Master Plan Static Links (existing logic)
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

        return view('static-plans', compact('masterPlan', 'selectedPlan', 'staticPlanData', 'encrypted'));
    }

    /**
     * Handle Pool Static Plan - Direct ChargeBee checkout
     */
    private function handlePoolStaticPlan($encrypted = null)
    {
        $poolStaticPlanData = session('static_plan_data');
        
        if (!$poolStaticPlanData) {
            return redirect()->route('login')->withErrors(['error' => 'Pool plan session expired']);
        }
        $chargebeePlanId = $poolStaticPlanData['chargebee_plan_id'];

        // Get the pool plan
        $poolPlan = PoolPlan::where('chargebee_plan_id', $chargebeePlanId)->firstOrFail();
        
        if (!$poolPlan || !$poolPlan->is_chargebee_synced || $poolPlan->chargebee_plan_id !== $chargebeePlanId) {
            return redirect()->route('login')->withErrors(['error' => 'Invalid pool plan link']);
        }

        // Get user from encrypted data or current auth
        $user = Auth::user();
        if (!$user && $encrypted) {
            try {
                $decrypted = Crypt::decryptString($encrypted);
                $parts = explode('/', $decrypted);
                $userEmail = $parts[0];
                $user = User::where('email', $userEmail)->first();
            } catch (\Exception $e) {
                return redirect()->route('login')->withErrors(['error' => 'Invalid user session']);
            }
        }

        if (!$user) {
            return redirect()->route('login')->withErrors(['error' => 'User authentication required']);
        }

        // Redirect directly to ChargeBee checkout for pool plan
        return $this->redirectToPoolPlanCheckout($poolPlan, $user);
    }

    /**
     * Create ChargeBee checkout for pool plan and redirect
     */
    private function redirectToPoolPlanCheckout($poolPlan, $user)
    {
        try {
            $charge_customer_id = $user->chargebee_customer_id ?? null;
            
            if ($charge_customer_id == null) {
                // Create hosted page for new customer
                $result = HostedPage::checkoutNewForItems([
                    "subscription_items" => [
                        [
                            "item_price_id" => $poolPlan->chargebee_plan_id,
                            "quantity" => 1, // Pool plans typically have quantity 1
                            "quantity_editable" => false, // Pool plans don't need quantity editing
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
                    "allow_plan_change" => false, // Pool plans are fixed
                    "redirect_url" => route('customer.pool-subscription.success'),
                    "cancel_url" => route('customer.pool-subscription.cancel')
                ]);
            } else {
                // Payment for existing customer
                $result = HostedPage::checkoutNewForItems([
                    "subscription_items" => [
                        [
                            "item_price_id" => $poolPlan->chargebee_plan_id,
                            "quantity" => 1
                        ]
                    ],
                    "customer" => [
                        "id" => $charge_customer_id,
                    ],
                    "billing_address" => [
                        "first_name" => $user->name,
                        "last_name" => "",
                        "line1" => "Pool Plan Address",
                        "city" => "City",
                        "state" => "State",
                        "zip" => "12345",
                        "country" => "US"
                    ],
                    "allow_plan_change" => false,
                    "redirect_url" => route('customer.pool-subscription.success'),
                    "cancel_url" => route('customer.pool-subscription.cancel')
                ]);
            }

            $hostedPage = $result->hostedPage();
            
            // Clear static plan session data
            session()->forget(['pool_static_plan_data', 'static_link_hit', 'static_type','static_plan_data']);

            // Redirect directly to ChargeBee checkout
            return redirect($hostedPage->url);

        } catch (\Exception $e) {
            \Log::error('Pool Plan ChargeBee Error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['error' => 'Failed to initiate pool plan subscription: ' . $e->getMessage()]);
        }
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
