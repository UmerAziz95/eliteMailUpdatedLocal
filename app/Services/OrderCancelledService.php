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
    public function cancelSubscription($chargebee_subscription_id, $user_id, $reason, $remove_accounts = false, $force_cancel = false)
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

                // Calculate proper end date based on billing cycle or force cancel
                $endDate = now(); // Default fallback
                
                if ($force_cancel) {
                    // For force cancel, set end date to now
                    $endDate = now();
                } else {
                    // If subscription has next_billing_date, calculate end date from last billing period
                    if ($subscription->next_billing_date) {
                        $nextBillingDate = Carbon::parse($subscription->next_billing_date);
                        // Assume monthly billing - subtract 1 month to get last billing date
                        $lastBillingDate = $nextBillingDate->copy()->subMonth();
                        $endDate = $nextBillingDate->copy()->subDay(); // End date is day before next billing
                    }else{
                        // get last billing date from subscription
                        $lastBillingDate = $subscription->last_billing_date ? Carbon::parse($subscription->last_billing_date) : null;
                        if ($lastBillingDate) {
                            $endDate = $lastBillingDate->copy()->addMonth()->subDay(); // End date is last billing date + 1 month - 1 day
                        }
                    }
                }

                $subscription->update([
                    'status' => 'cancelled',
                    'cancellation_at' => now(),
                    'reason' => $reason,
                    'end_date' => $endDate,
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
                // Queue date is set to 72 hours after subscription end date for normal cancel
                // For force cancel, queue starts immediately (now)
                if ($force_cancel) {
                    $queueStartDate = now();
                } else {
                    $queueStartDate = $endDate->copy()->addHours(72);
                }
                
                DomainRemovalTask::create([
                    'started_queue_date' => $queueStartDate,
                    'user_id' => $user_id,
                    'order_id' => $order ? $order->id : null,
                    'chargebee_subscription_id' => $chargebee_subscription_id,
                    'reason' => $reason,
                    'assigned_to' => null, // Assuming no specific user assigned yet
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
