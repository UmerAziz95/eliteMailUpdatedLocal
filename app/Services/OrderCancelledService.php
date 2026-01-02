<?php

namespace App\Services;

use App\Models\Subscription as UserSubscription;
use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\User;
use App\Models\DomainRemovalTask;
use App\Models\OrderEmail;
use App\Mail\SubscriptionCancellationMail;
use App\Services\ActivityLogService;
use App\Services\MailinAiService;
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
                        Log::info("Calculated end date from next billing date: {$endDate->toDateString()} for Subscription ID {$subscription->id}");
                    }else{
                        // get last billing date from subscription
                        $lastBillingDate = $subscription->last_billing_date ? Carbon::parse($subscription->last_billing_date) : null;
                        if ($lastBillingDate) {
                            Log::info("Calculated end date from last billing date: " . $lastBillingDate->toDateString() . " for Subscription ID {$subscription->id}");
                            $endDate = $lastBillingDate->copy()->addMonth(); // End date is last billing date + 1 month - 1 day
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
                Log::info("Cancellation end date set to: {$endDate->toDateString()} for Subscription ID {$subscription->id}");
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
                    
                    // Delete mailboxes from Mailin.ai immediately only for Force Cancel
                    // For EOBC, mailboxes should remain active until subscription end date
                    if ($force_cancel) {
                        $this->deleteOrderMailboxes($order);
                    } else {
                        Log::info("Skipping immediate mailbox deletion for EOBC cancellation - mailboxes will remain active until subscription end date", [
                            'action' => 'cancel_subscription',
                            'order_id' => $order->id,
                            'subscription_id' => $subscription->id,
                            'end_date' => $endDate->toDateString(),
                        ]);
                    }
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
                
                // Check if Mailin.ai automation is enabled and provider type is Private SMTP
                $automationEnabled = config('mailin_ai.automation_enabled', false);
                $providerType = $order ? $order->provider_type : null;
                $shouldSkipDomainRemovalTask = $automationEnabled && $providerType === 'Private SMTP';
                
                // Only create domain removal task if splits are found AND automation conditions are not met
                if ($hasSplits && !$shouldSkipDomainRemovalTask) {
                    // Add entry to domain removal queue table
                    // Queue date is set to 72 hours after subscription end date for normal cancel
                    // For force cancel, queue starts immediately (now)
                    if ($force_cancel) {
                        $queueStartDate = now();
                    } else {
                        $queueStartDate = $endDate->copy()->addHours(72);
                    }
                    Log::info("Creating domain removal task with queue start date: {$queueStartDate->toDateTimeString()} for Subscription ID {$subscription->id}");
                    DomainRemovalTask::create([
                        'started_queue_date' => $queueStartDate,
                        'user_id' => $user_id,
                        'order_id' => $order ? $order->id : null,
                        'chargebee_subscription_id' => $chargebee_subscription_id,
                        'reason' => $reason,
                        'assigned_to' => null, // Assuming no specific user assigned yet
                        'status' => 'pending'
                    ]);
                } elseif ($hasSplits && $shouldSkipDomainRemovalTask) {
                    Log::info("Skipping domain removal task creation - Mailin.ai automation enabled for Private SMTP order", [
                        'action' => 'cancel_subscription',
                        'order_id' => $order ? $order->id : null,
                        'subscription_id' => $subscription->id,
                        'automation_enabled' => $automationEnabled,
                        'provider_type' => $providerType,
                    ]);
                }

                try {
                    $reasonString = $reason ?? '';
                    try {
                        Mail::to($user->email)
                            ->queue(new SubscriptionCancellationMail($subscription, $user, $reasonString));
                    } catch (\Exception $e) {
                        \Log::channel('email-failures')->error('Failed to send subscription cancellation email to user', [
                            'recipient_email' => $user->email,
                            'exception' => $e->getMessage(),
                            'stack_trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'user_id' => $user->id,
                            'subscription_id' => $subscription->id,
                            'chargebee_subscription_id' => $chargebee_subscription_id,
                            'timestamp' => now()->toDateTimeString(),
                            'context' => 'OrderCancelledService::cancelSubscription'
                        ]);
                    }
                    
                    try {
                        Mail::to(config('mail.admin_address', 'admin@example.com'))
                            ->queue(new SubscriptionCancellationMail($subscription, $user, $reasonString, true));
                    } catch (\Exception $e) {
                        \Log::channel('email-failures')->error('Failed to send subscription cancellation email to admin', [
                            'recipient_email' => config('mail.admin_address', 'admin@example.com'),
                            'exception' => $e->getMessage(),
                            'stack_trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine(),
                            'user_id' => $user->id,
                            'subscription_id' => $subscription->id,
                            'chargebee_subscription_id' => $chargebee_subscription_id,
                            'timestamp' => now()->toDateTimeString(),
                            'context' => 'OrderCancelledService::cancelSubscription'
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::channel('email-failures')->error('Failed to process subscription cancellation emails', [
                        'exception' => $e->getMessage(),
                        'stack_trace' => $e->getTraceAsString(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'user_id' => $user->id,
                        'subscription_id' => $subscription->id,
                        'timestamp' => now()->toDateTimeString()
                    ]);
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

                $order->updateQuietly([
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

    /**
     * Delete all mailboxes for an order from Mailin.ai
     * Only executes if MAILIN_AI_AUTOMATION_ENABLED is true and provider_type is "Private SMTP"
     * 
     * @param Order $order The order to delete mailboxes for
     * @return void
     */
    public function deleteOrderMailboxes(Order $order)
    {
        try {
            // Check if Mailin.ai automation is enabled and provider type is Private SMTP
            $automationEnabled = config('mailin_ai.automation_enabled', false);
            $providerType = $order->provider_type;
            
            if (!$automationEnabled || $providerType !== 'Private SMTP') {
                Log::info("Skipping Mailin.ai mailbox deletion - conditions not met", [
                    'action' => 'delete_order_mailboxes',
                    'order_id' => $order->id,
                    'automation_enabled' => $automationEnabled,
                    'provider_type' => $providerType,
                ]);
                return;
            }

            // Get all OrderEmail records for this order that have Mailin.ai mailbox IDs
            $orderEmails = OrderEmail::where('order_id', $order->id)
                ->whereNotNull('mailin_mailbox_id')
                ->get();

            if ($orderEmails->isEmpty()) {
                Log::info("No Mailin.ai mailboxes found to delete for order", [
                    'action' => 'delete_order_mailboxes',
                    'order_id' => $order->id,
                ]);
                return;
            }

            Log::info("Deleting Mailin.ai mailboxes for cancelled order", [
                'action' => 'delete_order_mailboxes',
                'order_id' => $order->id,
                'mailbox_count' => $orderEmails->count(),
            ]);

            $mailinService = new MailinAiService();
            $deletedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($orderEmails as $orderEmail) {
                try {
                    $result = $mailinService->deleteMailbox($orderEmail->mailin_mailbox_id);
                    
                    if ($result['success']) {
                        $deletedCount++;
                        
                        // Delete the OrderEmail record from database after successful deletion from Mailin.ai
                        $orderEmail->delete();
                        
                        Log::channel('mailin-ai')->info('Mailbox deleted successfully from Mailin.ai and database during order cancellation', [
                            'action' => 'delete_order_mailboxes',
                            'order_id' => $order->id,
                            'order_email_id' => $orderEmail->id,
                            'mailin_mailbox_id' => $orderEmail->mailin_mailbox_id,
                            'email' => $orderEmail->email,
                        ]);
                    } else {
                        $failedCount++;
                        $errors[] = "Mailbox ID {$orderEmail->mailin_mailbox_id}: " . ($result['message'] ?? 'Unknown error');
                        
                        Log::channel('mailin-ai')->warning('Mailbox deletion from Mailin.ai failed, keeping OrderEmail record', [
                            'action' => 'delete_order_mailboxes',
                            'order_id' => $order->id,
                            'order_email_id' => $orderEmail->id,
                            'mailin_mailbox_id' => $orderEmail->mailin_mailbox_id,
                            'email' => $orderEmail->email,
                            'error' => $result['message'] ?? 'Unknown error',
                        ]);
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Mailbox ID {$orderEmail->mailin_mailbox_id}: " . $e->getMessage();
                    
                    Log::channel('mailin-ai')->error('Failed to delete mailbox during order cancellation', [
                        'action' => 'delete_order_mailboxes',
                        'order_id' => $order->id,
                        'order_email_id' => $orderEmail->id,
                        'mailin_mailbox_id' => $orderEmail->mailin_mailbox_id,
                        'email' => $orderEmail->email,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info("Mailin.ai mailbox deletion completed for cancelled order", [
                'action' => 'delete_order_mailboxes',
                'order_id' => $order->id,
                'total_mailboxes' => $orderEmails->count(),
                'deleted_count' => $deletedCount,
                'failed_count' => $failedCount,
                'errors' => $errors,
            ]);

            // Log activity for mailbox deletion
            if ($deletedCount > 0) {
                ActivityLogService::log(
                    'order-mailboxes-deleted',
                    "Deleted {$deletedCount} Mailin.ai mailbox(es) for cancelled order",
                    $order,
                    [
                        'order_id' => $order->id,
                        'deleted_count' => $deletedCount,
                        'failed_count' => $failedCount,
                        'errors' => $errors,
                    ]
                );
            }

        } catch (\Exception $e) {
            // Don't fail the entire cancellation if mailbox deletion fails
            Log::error('Error deleting Mailin.ai mailboxes during order cancellation', [
                'action' => 'delete_order_mailboxes',
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
