<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use ChargeBee\ChargeBee\Models\Subscription as CBSubscription;

class CronController extends Controller
{
    /**
     * Auto-cancel pending orders older than 3 days
     */
    public function cancelSubscriptions()
    {
        $thresholdDate = Carbon::now()->subDays(3);

        $orders = Order::where('status_manage_by_admin', 'Pending')
            ->where('created_at', '<=', $thresholdDate)
            ->get();

        foreach ($orders as $order) {
            $this->subscriptionCancelProcess($order->chargebee_subscription_id);
        }

        return response()->json([
            'success' => true,
            'message' => count($orders) . ' pending subscriptions processed for cancellation.'
        ]);
    }

    /**
     * Cancel a subscription and update related data
     */
    public function subscriptionCancelProcess($chargebee_subscription_id)
    {
        $subscription = Subscription::where('chargebee_subscription_id', $chargebee_subscription_id)->first();

        if (!$subscription || $subscription->status !== 'active') {
            return false;
        }

        try {
            $result = CBSubscription::cancelForItems($chargebee_subscription_id, [
                "end_of_term" => false,
                "credit_option" => "none",
                "unbilled_charges_option" => "delete",
                "account_receivables_handling" => "no_action"
            ]);

            $subscriptionData = $result->subscription();

            if ($subscriptionData->status === 'cancelled') {
                // Update subscription
                $subscription->update([
                    'status' => 'cancelled',
                    'cancellation_at' => now(),
                    'reason' => "Auto-cancelled by system after 3 days in pending status",
                    'end_date' => $this->getEndExpiryDate($subscription->start_date),
                ]);

                // Update user
                $user = User::find($subscription->user_id);
                if ($user) {
                    $user->update([
                        'subscription_status' => 'cancelled',
                        'subscription_id' => null,
                        'plan_id' => null,
                    ]);
                }

                // Update order
                $order = Order::where('chargebee_subscription_id', $chargebee_subscription_id)->first();
                if ($order) {
                    $order->update([
                        'status_manage_by_admin' => 'Cancelled',
                    ]);
                }
            }

        } catch (\Throwable $e) {
            \Log::error('Auto cancellation failed for subscription: ' . $chargebee_subscription_id . ' | ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Get calculated end expiry date (assuming this is implemented elsewhere)
     */
    protected function getEndExpiryDate($startDate)
    {
        return Carbon::parse($startDate)->addMonth(); // Adjust this logic as per your actual subscription cycle
    }
}

