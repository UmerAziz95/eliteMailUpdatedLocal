<?php

namespace App\Services;

use App\Models\Subscription as UserSubscription;
use App\Models\Order;
use App\Models\OrderPanel;
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
        Log::info("Initiating cancellation for ChargeBee ID {$chargebee_subscription_id}, User ID {$user_id}, Force Cancel: " . ($force_cancel ? 'Yes' : 'No'));
        $subscription = UserSubscription::where('chargebee_subscription_id', $chargebee_subscription_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$subscription || $subscription->status !== 'active') {
            Log::warning("No active subscription found for cancellation: ChargeBee ID {$chargebee_subscription_id}, User ID {$user_id}");
            return [
                'success' => false,
                'message' => 'No active subscription found'
            ];
        }
        
        Log::info("Found active subscription for cancellation: Subscription ID {$subscription->id}, ChargeBee ID {$chargebee_subscription_id}, User ID {$user_id}");
        try {
            // First, check if subscription is already cancelled in Chargebee
            $chargebeeSubscription = \ChargeBee\ChargeBee\Models\Subscription::retrieve($chargebee_subscription_id);
            $isAlreadyCancelled = $chargebeeSubscription->subscription()->status === 'cancelled';
            
            // Only call cancel API if not already cancelled
            if (!$isAlreadyCancelled) {
                $result = \ChargeBee\ChargeBee\Models\Subscription::cancelForItems($chargebee_subscription_id, [
                    "end_of_term" => false,
                    "credit_option" => "none",
                    "unbilled_charges_option" => "delete",
                    "account_receivables_handling" => "no_action"
                ]);
                Log::info("Subscription cancelled in ChargeBee: {$chargebee_subscription_id}");
            } else {
                Log::info("Subscription already cancelled in ChargeBee: {$chargebee_subscription_id}");
                // Use the retrieved subscription data
                $result = $chargebeeSubscription;
            }

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
                    'is_cancelled_force' => $force_cancel,
                ]);
                Log::info("Updated local subscription record to cancelled: Subscription ID {$subscription->id}, User ID {$user_id}");
                if ($user) {
                    $user->update([
                        'subscription_status' => 'cancelled',
                        'subscription_id' => null,
                        'plan_id' => null
                    ]);
                    Log::info("Updated user record to cancelled: User ID {$user_id}");
                }

                $order = Order::where('chargebee_subscription_id', $chargebee_subscription_id)->first();
                if ($order) {
                    $order->update([
                        'status_manage_by_admin' => 'cancelled',
                    ]);
                    Log::info("Updated order record to cancelled: Order ID {$order->id}, User ID {$user_id}");
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
                
                // Check if order has splits before creating domain removal task
                $hasSplits = false;
                if ($order) {
                    $hasSplits = OrderPanel::where('order_id', $order->id)->exists();
                }
                
                // Only create domain removal task if splits are found
                if ($hasSplits) {
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
                }

                try {
                    $reasonString = $reason ?? '';
                    Mail::to($user->email)
                        ->queue(new SubscriptionCancellationMail($subscription, $user, $reasonString));
                    Mail::to(config('mail.admin_address', 'admin@example.com'))
                        ->queue(new SubscriptionCancellationMail($subscription, $user, $reasonString, true));
                } catch (\Exception $e) {
                    // Log or ignore email errors
                }
                Log::info("Subscription cancellation process completed for ChargeBee ID {$chargebee_subscription_id}, User ID {$user_id}");
                return [
                    'success' => true,
                    'message' => 'Subscription cancelled successfully',
                    'order_id' => $order ? $order->id : null,
                    'cancellation_reason' => $reason,
                ];
            }
            Log::error("Failed to cancel subscription in ChargeBee: {$chargebee_subscription_id}, Status: " . $result->subscription()->status);
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

    public function reactivateSubscription($chargebee_subscription_id, $user_id)
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
            // First, reactivate the subscription on ChargeBee
            $chargebeeResult = \ChargeBee\ChargeBee\Models\Subscription::reactivate($chargebee_subscription_id);
            
            if ($chargebeeResult->subscription()->status !== 'active') {
                throw new Exception('Failed to reactivate subscription on ChargeBee: status is ' . $chargebeeResult->subscription()->status);
            }

            // Delete any pending domain removal tasks for this subscription
            DomainRemovalTask::where('chargebee_subscription_id', $chargebee_subscription_id)->delete();

            // Get updated billing info from ChargeBee response
            $chargebeeSubscription = $chargebeeResult->subscription();
            $nextBillingAt = $chargebeeSubscription->nextBillingAt ?? null;

            // Reactivate the subscription record
            $subscription->update([
                'status' => 'active',
                'cancellation_at' => null,
                'reason' => null,
                'end_date' => null,
                'next_billing_date' => $nextBillingAt ? Carbon::createFromTimestamp($nextBillingAt) : null,
                'is_cancelled_force' => false,
            ]);

            // Restore user subscription pointers where possible
            $user = User::find($user_id);
            if ($user) {
                $user->update([
                    'subscription_status' => 'active',
                    'subscription_id' => $subscription->id,
                    'plan_id' => $subscription->plan_id ?? $user->plan_id,
                ]);
            }

            // Update order status if exists
            $order = Order::where('chargebee_subscription_id', $chargebee_subscription_id)->first();
            if ($order) {
                $statusToRestore = 'completed'; // Better default fallback
                
                // First, try to find the cancellation log to get the previous status
                $cancellationLog = \App\Models\Log::where('performed_on_type', 'App\Models\Order')
                    ->where('performed_on_id', $order->id)
                    ->whereIn('action_type', ['order_status_updated', 'contractor-order-status-update', 'admin-order-status-update'])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.new_status')) IN ('cancelled', 'cancelled_force')")
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($cancellationLog && isset($cancellationLog->data['old_status'])) {
                    // Get the status that was active before cancellation
                    $statusToRestore = $cancellationLog->data['old_status'];
                } else {
                    // Fallback: Find the last non-cancelled status from logs
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

                $order->update([
                    'status_manage_by_admin' => $statusToRestore,
                ]);
            }

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
