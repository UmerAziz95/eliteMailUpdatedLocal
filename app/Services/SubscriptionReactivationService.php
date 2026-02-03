<?php

namespace App\Services;

use App\Models\Subscription as UserSubscription;
use App\Models\Order;
use App\Models\User;
use App\Models\DomainRemovalTask;
use App\Services\ActivityLogService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionReactivationService
{
    /**
     * Reactivate a cancelled subscription on ChargeBee and locally.
     *
     * @param string $chargebee_subscription_id
     * @param int $user_id
     * @return array
     */
    public function reactivate($chargebee_subscription_id, $user_id)
    {
        $subscription = UserSubscription::where('chargebee_subscription_id', $chargebee_subscription_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$subscription || $subscription->status !== 'cancelled') {
            return [
                'success' => false,
                'message' => 'No cancelled subscription found'
            ];
        }

        DB::beginTransaction();
        try {
            // 1. Reactivate on ChargeBee
            $chargebeeResult = \ChargeBee\ChargeBee\Models\Subscription::reactivate($chargebee_subscription_id);

            if ($chargebeeResult->subscription()->status !== 'active') {
                throw new Exception('Failed to reactivate subscription on ChargeBee: status is ' . $chargebeeResult->subscription()->status);
            }

            // 2. Delete pending domain removal tasks
            DomainRemovalTask::where('chargebee_subscription_id', $chargebee_subscription_id)->delete();

            // 3. Get updated billing dates
            $chargebeeSubscription = $chargebeeResult->subscription();
            $nextBillingAt = $chargebeeSubscription->nextBillingAt ?? null;

            // 4. Update local subscription record
            $subscription->update([
                'status' => 'active',
                'cancellation_at' => null,
                'reason' => null,
                'end_date' => null,
                'next_billing_date' => $nextBillingAt ? Carbon::createFromTimestamp($nextBillingAt) : null,
                'is_cancelled_force' => false,
            ]);

            // 5. Update user record
            $user = User::find($user_id);
            if ($user) {
                $user->update([
                    'subscription_status' => 'active',
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->plan_id ?? $user->plan_id,
                ]);
            }

            // 6. Restore Order Status
            $order = Order::where('chargebee_subscription_id', $chargebee_subscription_id)->first();
            if ($order) {
                $statusToRestore = 'completed'; // Default

                // Try to find previous status from logs
                $cancellationLog = \App\Models\Log::where('performed_on_type', 'App\Models\Order')
                    ->where('performed_on_id', $order->id)
                    ->whereIn('action_type', ['order_status_updated', 'contractor-order-status-update', 'admin-order-status-update'])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.new_status')) IN ('cancelled', 'cancelled_force')")
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($cancellationLog && isset($cancellationLog->data['old_status'])) {
                    $statusToRestore = $cancellationLog->data['old_status'];
                } else {
                    // Fallback log search
                    $lastOrderLog = \App\Models\Log::where('performed_on_type', 'App\Models\Order')
                        ->where('performed_on_id', $order->id)
                        ->whereIn('action_type', ['order_status_updated', 'contractor-order-status-update', 'admin-order-status-update'])
                        ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.new_status')) NOT IN ('cancelled', 'cancelled_force')")
                        ->orderBy('created_at', 'desc')
                        ->first();

                    if ($lastOrderLog && isset($lastOrderLog->data['new_status'])) {
                        $statusToRestore = $lastOrderLog->data['new_status'];
                    } elseif ($lastOrderLog && isset($lastOrderLog->data['previous_status'])) {
                        $statusToRestore = $lastOrderLog->data['previous_status'];
                    }
                }

                $order->updateQuietly([
                    'status_manage_by_admin' => $statusToRestore,
                ]);
            }

            // 7. Log Activity
            ActivityLogService::log(
                'customer-subscription-reactivated',
                'Subscription reactivated successfully: ' . $subscription->id,
                $subscription,
                [
                    'user_id' => $user_id,
                    'subscription_id' => $subscription->id,
                    'status' => $subscription->status,
                ]
            );

            DB::commit();

            return [
                'success' => true,
                'message' => 'Subscription reactivated successfully',
                'subscription_id' => $subscription->id,
                'current_term_start' => $chargebeeSubscription->currentTermStart ?? null,
                'current_term_end' => $chargebeeSubscription->currentTermEnd ?? null,
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error reactivating subscription: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to reactivate subscription: ' . $e->getMessage(),
            ];
        }
    }
}
