<?php

namespace App\Services;

use App\Models\Subscription as UserSubscription;
use App\Models\Order;
use App\Models\User;
use App\Models\DomainRemovalTask;
use App\Mail\SubscriptionCancellationMail;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrderCancelledService
{
    public function cancelSubscription($chargebee_subscription_id, $user_id, $reason, $remove_accounts = false)
    {
        $subscription = UserSubscription::where('chargebee_subscription_id', $chargebee_subscription_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$subscription || $subscription->status !== 'active') {
            return [
                'success' => false,
                'message' => 'No active subscription found'
            ];
        }

        try {
            $result = \ChargeBee\ChargeBee\Models\Subscription::cancelForItems($chargebee_subscription_id, [
                "end_of_term" => false,
                "credit_option" => "none",
                "unbilled_charges_option" => "delete",
                "account_receivables_handling" => "no_action"
            ]);

            if ($result->subscription()->status === 'cancelled') {
                $user = User::find($user_id);

                $subscription->update([
                    'status' => 'cancelled',
                    'cancellation_at' => now(),
                    'reason' => $reason,
                    'end_date' => now(), // Replace with getEndExpiryDate if needed
                    'next_billing_date' => null,
                ]);

                if ($user) {
                    $user->update([
                        'subscription_status' => 'cancelled',
                        'subscription_id' => null,
                        'plan_id' => null
                    ]);
                }

                $order = Order::where('chargebee_subscription_id', $chargebee_subscription_id)->first();
                if ($order) {
                    $order->update([
                        'status_manage_by_admin' => 'cancelled',
                    ]);
                }

                ActivityLogService::log(
                    'customer-subscription-cancelled',
                    'Subscription cancelled successfully: ' . $subscription->id,
                    $subscription,
                    [
                        'user_id' => $user_id,
                        'subscription_id' => $subscription->id,
                        'status' => $subscription->status,
                    ]
                );
                // Add entry to domain removal queue table
                // Queue date is set to 72 hours after subscription end date
                $subscriptionEndDate = $subscription->end_date ? Carbon::parse($subscription->end_date) : Carbon::now();
                $queueStartDate = $subscriptionEndDate->addHours(72);
                
                DomainRemovalTask::create([
                    'started_queue_date' => $queueStartDate,
                    'user_id' => $user_id,
                    'order_id' => $order ? $order->id : null,
                    'chargebee_subscription_id' => $chargebee_subscription_id,
                    'reason' => $reason,
                    'assigned_to' => $order->assigned_to,
                    'status' => 'pending'
                ]);

                try {
                    $reasonString = $reason ?? '';
                    Mail::to($user->email)
                        ->queue(new SubscriptionCancellationMail($subscription, $user, $reasonString));
                    Mail::to(config('mail.admin_address', 'admin@example.com'))
                        ->queue(new SubscriptionCancellationMail($subscription, $user, $reasonString, true));
                } catch (\Exception $e) {
                    // Log or ignore email errors
                }

                return [
                    'success' => true,
                    'message' => 'Subscription cancelled successfully',
                    'order_id' => $order ? $order->id : null,
                    'cancellation_reason' => $reason,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to cancel subscription in payment gateway'
            ];
        } catch (\Exception $e) {
            \Log::error('Error cancelling subscription: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to cancel subscription: ' . $e->getMessage()
            ];
        }
    }
}
