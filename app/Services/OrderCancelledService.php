<?php

namespace App\Services;

use App\Models\Subscription as UserSubscription;
use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\User;
use App\Models\DomainRemovalTask;
use App\Models\OrderEmail;
use App\Models\SmtpProviderSplit;
use App\Models\UsedEmailsInOrder;
use App\Mail\SubscriptionCancellationMail;
use App\Services\ActivityLogService;
use App\Services\MailinAiService;
use App\Services\Providers\CreatesProviders;
use Illuminate\Support\Facades\Mail;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrderCancelledService
{
    use CreatesProviders;
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
                $mailboxDeletionInProgress = false;
                
                if ($order) {
                    // For Google/365 orders with force cancel, status will be set to 'cancellation-in-process' in deleteOrderMailboxes
                    // For other orders or EOBC, set to 'cancelled' immediately
                    $isGoogle365ForceCancel = $force_cancel && in_array($order->provider_type, ['Google', 'Microsoft 365']);
                    
                    if (!$isGoogle365ForceCancel) {
                        $order->update([
                            'status_manage_by_admin' => 'cancelled',
                            'reason' => $reason,
                        ]);
                        Log::info("Updated order record to cancelled: Order ID {$order->id}, User ID {$user_id}");
                    }
                    
                    // Delete mailboxes from Mailin.ai immediately only for Force Cancel
                    // For EOBC, mailboxes should remain active until subscription end date
                    if ($force_cancel) {
                        $mailboxDeletionInProgress = $this->deleteOrderMailboxes($order, $reason);
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
                
                $message = 'Subscription cancelled successfully';
                if ($mailboxDeletionInProgress) {
                    $message = 'Subscription cancellation is in process. Mailbox deletion is running in the background.';
                }
                
                return [
                    'success' => true,
                    'message' => $message,
                    'order_id' => $order ? $order->id : null,
                    'cancellation_reason' => $reason,
                    'mailbox_deletion_in_progress' => $mailboxDeletionInProgress ?? false,
                    'order_status' => $order ? $order->status_manage_by_admin : null,
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
     * Delete all mailboxes for an order from providers.
     * Routes to provider-specific methods based on order provider type.
     *
     * @param Order $order The order to delete mailboxes for
     * @param string|null $reason Cancellation reason (stored on order when status is set to cancelled / cancellation-in-process)
     * @return bool|void Returns true if deletion is in progress (async), void otherwise
     */
    public function deleteOrderMailboxes(Order $order, $reason = null)
    {
        try {
            // Check if order is already in cancellation process
            if ($order->status_manage_by_admin === 'cancellation-in-process') {
                Log::info("Order already in cancellation process, skipping duplicate deletion", [
                    'action' => 'delete_order_mailboxes',
                    'order_id' => $order->id,
                ]);
                return true; // Indicate that deletion is already in progress
            }

            // Check if Mailin.ai automation is enabled
            $automationEnabled = config('mailin_ai.automation_enabled', false);
            
            if (!$automationEnabled) {
                Log::info("Skipping mailbox deletion - automation not enabled", [
                    'action' => 'delete_order_mailboxes',
                    'order_id' => $order->id,
                    'automation_enabled' => $automationEnabled,
                ]);
                return;
            }

            $providerType = $order->provider_type;
            
            // Route to provider-specific deletion methods
            if (in_array(strtolower($providerType ?? ''), ['private smtp', 'smtp'])) {
                // Check for order_provider_splits first (new multi-provider system)
                $providerSplits = \App\Models\OrderProviderSplit::where('order_id', $order->id)->get();

                if ($providerSplits->isNotEmpty()) {
                    // New system: Use order_provider_splits
                    return $this->deleteMailboxesFromProviderSplits($order, $providerSplits);
                }

                // Legacy path: no provider splits – delete SMTP order mailboxes directly
                $this->deleteSmtpOrderMailboxes($order);
                return;
            } elseif (in_array($providerType, ['Google', 'Microsoft 365'])) {
                // Update status to 'cancellation-in-process' before dispatching job; store reason on order
                $order->update(array_filter([
                    'status_manage_by_admin' => 'cancellation-in-process',
                    'reason' => $reason,
                ]));
                
                Log::info('Updated order status to cancellation-in-process before mailbox deletion', [
                    'action' => 'delete_order_mailboxes',
                    'order_id' => $order->id,
                    'provider_type' => $providerType,
                ]);
                
                // Dispatch job for Google/365 orders (batch processing)
                \App\Jobs\MailinAi\DeleteGoogle365MailboxesJob::dispatch(
                    $order->id,
                    50, // batch size
                    0    // offset
                );
                
                Log::info('Google/365 mailbox deletion job dispatched for background processing', [
                    'action' => 'delete_order_mailboxes',
                    'order_id' => $order->id,
                    'provider_type' => $providerType,
                ]);
                
                return true; // Indicate that mailbox deletion is in progress
            } else {
                Log::info("Skipping mailbox deletion - unsupported provider type", [
                    'action' => 'delete_order_mailboxes',
                    'order_id' => $order->id,
                    'provider_type' => $providerType,
                ]);
            }

        } catch (\Exception $e) {
            // Don't fail the entire cancellation if mailbox deletion fails
            Log::error('Error deleting mailboxes during order cancellation', [
                'action' => 'delete_order_mailboxes',
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Delete mailboxes from order_provider_splits (new multi-provider system).
     * Structure mirrors order creation: one provider per split via createProvider(slug, credentials),
     * then SmtpProviderInterface::deleteMailboxesFromSplit per provider (Mailin, PremiumInboxes, Mailrun).
     *
     * @param Order $order The order to delete mailboxes for
     * @param \Illuminate\Support\Collection $splits Collection of OrderProviderSplit models
     * @return bool Returns true if any deletion is in progress (async)
     */
    public function deleteMailboxesFromProviderSplits(Order $order, $splits)
    {
        try {
            Log::info("Starting mailbox deletion from order_provider_splits", [
                'action' => 'delete_mailboxes_from_provider_splits',
                'order_id' => $order->id,
                'splits_count' => $splits->count(),
            ]);

            $hasAsyncOperations = false;

            foreach ($splits as $split) {
                $providerSlug = $split->provider_slug;
                
                Log::info("Processing provider split for deletion", [
                    'action' => 'delete_mailboxes_from_provider_splits',
                    'order_id' => $order->id,
                    'split_id' => $split->id,
                    'provider_slug' => $providerSlug,
                ]);

                // Get provider credentials
                $providerConfig = \App\Models\SmtpProviderSplit::getBySlug($providerSlug);
                if (!$providerConfig) {
                    Log::warning('Provider config not found for split', [
                        'action' => 'delete_mailboxes_from_provider_splits',
                        'order_id' => $order->id,
                        'split_id' => $split->id,
                        'provider_slug' => $providerSlug,
                    ]);
                    continue;
                }

                $credentials = $providerConfig->getCredentials();

                try {
                    $provider = $this->createProvider($providerSlug, $credentials);
                    $result = $provider->deleteMailboxesFromSplit($order, $split);
                    Log::info('Provider split deletion completed', [
                        'action' => 'delete_mailboxes_from_provider_splits',
                        'order_id' => $order->id,
                        'split_id' => $split->id,
                        'provider_slug' => $providerSlug,
                        'deleted' => $result['deleted'] ?? 0,
                        'failed' => $result['failed'] ?? 0,
                        'skipped' => $result['skipped'] ?? 0,
                    ]);
                } catch (\InvalidArgumentException $e) {
                    Log::warning('Unknown provider slug for deletion', [
                        'action' => 'delete_mailboxes_from_provider_splits',
                        'order_id' => $order->id,
                        'split_id' => $split->id,
                        'provider_slug' => $providerSlug,
                        'error' => $e->getMessage(),
                    ]);
                } catch (\Exception $e) {
                    Log::error('Provider split deletion failed; continuing with other splits', [
                        'action' => 'delete_mailboxes_from_provider_splits',
                        'order_id' => $order->id,
                        'split_id' => $split->id,
                        'provider_slug' => $providerSlug,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            Log::info("Completed mailbox deletion from order_provider_splits", [
                'action' => 'delete_mailboxes_from_provider_splits',
                'order_id' => $order->id,
            ]);

            return $hasAsyncOperations;

        } catch (\Exception $e) {
            Log::error('Error deleting mailboxes from provider splits', [
                'action' => 'delete_mailboxes_from_provider_splits',
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Delete mailboxes for SMTP orders
     * 
     * @param Order $order The order to delete mailboxes for
     * @return void
     */
    public function deleteSmtpOrderMailboxes(Order $order)
    {
        try {
            // Get all OrderEmail records for this order that have Mailin.ai mailbox IDs
            $orderEmails = OrderEmail::where('order_id', $order->id)
                ->whereNotNull('mailin_mailbox_id')
                ->get();

            if ($orderEmails->isEmpty()) {
                Log::info("No Mailin.ai mailboxes found to delete for SMTP order", [
                    'action' => 'delete_smtp_order_mailboxes',
                    'order_id' => $order->id,
                ]);
                return;
            }

            // Collect all email addresses
            $allEmailAddresses = $orderEmails->pluck('email')->unique()->values();

            // Log emails fetched (with count and full list) - BEFORE deletion
            Log::info("Fetched emails for deletion from SMTP order", [
                'order_id' => $order->id,
                'provider_type' => $order->provider_type,
                'total_emails_count' => $allEmailAddresses->count(),
                'emails_from_order_emails_count' => $orderEmails->count(),
                'emails' => $allEmailAddresses->toArray(), // Full list of emails to be deleted
            ]);

            // Store emails in used_emails_in_order table - BEFORE deletion
            $usedEmailsRecord = UsedEmailsInOrder::create([
                'order_id' => $order->id,
                'emails' => $allEmailAddresses->toArray(),
                'count' => $allEmailAddresses->count(),
            ]);

            Log::info("Stored emails in used_emails_in_order table before deletion", [
                'order_id' => $order->id,
                'provider_type' => $order->provider_type,
                'used_emails_record_id' => $usedEmailsRecord->id,
                'emails_count' => $usedEmailsRecord->count,
            ]);

            Log::info("Starting mailbox deletion for SMTP order", [
                'order_id' => $order->id,
                'provider_type' => $order->provider_type,
                'total_emails' => $allEmailAddresses->count(),
                'emails_stored_in_used_emails_table' => true,
            ]);

            // Get active provider credentials (or fallback to config)
            $activeProvider = SmtpProviderSplit::getActiveProvider();
            $credentials = $activeProvider ? $activeProvider->getCredentials() : null;
            $mailinService = new MailinAiService($credentials);
            $deletedCount = 0;
            $failedCount = 0;
            $errors = [];
            $emailsChecked = [];
            $emailsDeleted = [];
            $emailsFailed = [];

            Log::info("Starting to check emails on Mailin.ai for SMTP order", [
                'order_id' => $order->id,
                'provider_type' => $order->provider_type,
                'total_emails_to_check' => $orderEmails->count(),
            ]);

            foreach ($orderEmails as $orderEmail) {
                $email = $orderEmail->email;
                $emailsChecked[] = $email;

                Log::info("Checking email on Mailin.ai", [
                    'order_id' => $order->id,
                    'email' => $email,
                    'mailin_mailbox_id' => $orderEmail->mailin_mailbox_id,
                ]);

                try {
                    Log::info("Attempting to delete mailbox from Mailin.ai", [
                        'order_id' => $order->id,
                        'email' => $email,
                        'mailin_mailbox_id' => $orderEmail->mailin_mailbox_id,
                    ]);

                    $result = $mailinService->deleteMailbox($orderEmail->mailin_mailbox_id);
                    
                    if ($result['success']) {
                        $deletedCount++;
                        $emailsDeleted[] = $email;
                        
                        // Delete the OrderEmail record from database after successful deletion from Mailin.ai
                        $orderEmail->delete();
                        
                        Log::info('Mailbox deleted successfully from Mailin.ai and database', [
                            'action' => 'delete_smtp_order_mailboxes',
                            'order_id' => $order->id,
                            'order_email_id' => $orderEmail->id,
                            'mailin_mailbox_id' => $orderEmail->mailin_mailbox_id,
                            'email' => $email,
                        ]);
                    } else {
                        $failedCount++;
                        $emailsFailed[] = $email;
                        $errors[] = "Mailbox ID {$orderEmail->mailin_mailbox_id}: " . ($result['message'] ?? 'Unknown error');
                        
                        Log::warning('Mailbox deletion from Mailin.ai failed, keeping OrderEmail record', [
                            'action' => 'delete_smtp_order_mailboxes',
                            'order_id' => $order->id,
                            'order_email_id' => $orderEmail->id,
                            'mailin_mailbox_id' => $orderEmail->mailin_mailbox_id,
                            'email' => $orderEmail->email,
                            'error' => $result['message'] ?? 'Unknown error',
                        ]);
                    }
                } catch (\Exception $e) {
                    $failedCount++;                      
                    $emailsFailed[] = $email;
                    $errors[] = "Mailbox ID {$orderEmail->mailin_mailbox_id}: " . $e->getMessage();
                    
                    Log::error('Exception occurred while deleting mailbox from Mailin.ai', [
                        'action' => 'delete_smtp_order_mailboxes',
                        'order_id' => $order->id,
                        'order_email_id' => $orderEmail->id,
                        'mailin_mailbox_id' => $orderEmail->mailin_mailbox_id,
                        'email' => $email,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }

            Log::info("SMTP order mailbox deletion completed", [
                'order_id' => $order->id,
                'provider_type' => $order->provider_type,
                'total_emails' => $orderEmails->count(),
                'deleted_count' => $deletedCount,
                'failed_count' => $failedCount,
                'emails_stored_in_used_emails_table_id' => $usedEmailsRecord->id,
            ]);

            // Log detailed lists
            if (!empty($emailsChecked)) {
                Log::info("List of all emails checked on Mailin.ai for SMTP order", [
                    'order_id' => $order->id,
                    'emails_checked_count' => count($emailsChecked),
                    'emails_checked' => $emailsChecked,
                ]);
            }

            if (!empty($emailsDeleted)) {
                Log::info("List of emails successfully deleted from Mailin.ai for SMTP order", [
                    'order_id' => $order->id,
                    'emails_deleted_count' => count($emailsDeleted),
                    'emails_deleted' => $emailsDeleted,
                ]);
            }

            if (!empty($emailsFailed)) {
                Log::warning("List of emails that failed to delete from Mailin.ai for SMTP order", [
                    'order_id' => $order->id,
                    'emails_failed_count' => count($emailsFailed),
                    'emails_failed' => $emailsFailed,
                ]);
            }

            // Log activity for mailbox deletion
            if ($deletedCount > 0) {
                ActivityLogService::log(
                    'order-mailboxes-deleted',
                    "Deleted {$deletedCount} Mailin.ai mailbox(es) for cancelled SMTP order",
                    $order,
                    [
                        'order_id' => $order->id,
                        'provider_type' => $order->provider_type,
                        'deleted_count' => $deletedCount,
                        'failed_count' => $failedCount,
                        'errors' => $errors,
                        'used_emails_record_id' => $usedEmailsRecord->id,
                    ]
                );
            }

        } catch (\Exception $e) {
            Log::error('Error deleting SMTP order mailboxes during order cancellation', [
                'action' => 'delete_smtp_order_mailboxes',
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Delete mailboxes for Google/365 orders (with panels and splits)
     * Supports batch processing when called from command
     * 
     * @param Order|int $order The order object or order ID
     * @param int|null $batchSize Number of mailboxes to process per batch (null = process all)
     * @param int $offset Starting offset for batch processing
     * @return array|null Results array if batch processing, void if processing all
     */
    public function deleteGoogle365OrderMailboxes($order, ?int $batchSize = null, int $offset = 0)
    {
        try {
            // Handle both Order object and order ID
            if (is_int($order)) {
                $order = Order::with(['reorderInfo'])->findOrFail($order);
            }
            $orderId = $order->id;
            $isBatchMode = $batchSize !== null;

            // Get all panels for the order with eager-loaded splits
            $orderPanels = OrderPanel::where('order_id', $orderId)
                ->with('orderPanelSplits')
                ->get();

            if ($orderPanels->isEmpty()) {
                Log::info("No panels found for Google/365 order", [
                    'action' => 'delete_google365_order_mailboxes',
                    'order_id' => $orderId,
                    'offset' => $offset,
                ]);
                
                if ($isBatchMode) {
                    return [
                        'processed' => 0,
                        'deleted' => 0,
                        'failed' => 0,
                        'not_found' => 0,
                        'lookup_failed' => 0,
                        'has_more' => false,
                        'next_offset' => $offset,
                        'total_remaining' => 0,
                    ];
                }
                return;
            }

            // Collect all emails from all splits (following exportCsvSplitDomainsSmartById pattern)
            $allEmails = collect();
            $splitIdsWithCustomizedEmails = collect();

            foreach ($orderPanels as $panel) {
                // Get ALL split IDs for this panel at once
                $splitIds = $panel->orderPanelSplits->pluck('id');
                
                if ($splitIds->isEmpty()) {
                    continue;
                }

                // Fetch emails for ALL splits at once (same as exportSmartZip)
                $panelEmails = OrderEmail::whereIn('order_split_id', $splitIds)->get();
                
                if ($panelEmails->isNotEmpty()) {
                    $allEmails = $allEmails->merge($panelEmails);
                    $splitIdsWithCustomizedEmails = $splitIdsWithCustomizedEmails->merge($splitIds);
                }
            }

            // Generate emails for splits without customized data
            $generatedEmails = $this->generateEmailsForSplitsWithoutCustomizedData($orderPanels, $order, $splitIdsWithCustomizedEmails);

            // Collect all email addresses (from order_emails and generated)
            $allEmailAddresses = $allEmails->pluck('email')
                ->merge($generatedEmails->pluck('email'))
                ->unique()
                ->values();

            $totalCount = $allEmailAddresses->count();

            // Store emails in used_emails_in_order table - BEFORE deletion
            // In batch mode, only store on first batch (offset = 0)
            if (!$isBatchMode || $offset === 0) {
                // Check if record already exists (for batch mode)
                $existingRecord = $isBatchMode ? UsedEmailsInOrder::where('order_id', $orderId)->first() : null;
                
                if (!$existingRecord) {
                    $usedEmailsRecord = UsedEmailsInOrder::create([
                        'order_id' => $orderId,
                        'emails' => $allEmailAddresses->toArray(),
                        'count' => $totalCount,
                    ]);

                    Log::info("Stored emails in used_emails_in_order table before deletion", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'provider_type' => $order->provider_type,
                        'used_emails_record_id' => $usedEmailsRecord->id,
                        'emails_count' => $usedEmailsRecord->count,
                    ]);
                } else {
                    Log::info("Used emails record already exists, skipping creation", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'existing_record_id' => $existingRecord->id,
                    ]);
                    $usedEmailsRecord = $existingRecord;
                }

                // Log emails fetched (with count and full list) - BEFORE deletion
                Log::info("Fetched emails for deletion from Google/365 order", [
                    'action' => 'delete_google365_order_mailboxes',
                    'order_id' => $orderId,
                    'provider_type' => $order->provider_type,
                    'total_emails_count' => $totalCount,
                    'emails_from_order_emails_count' => $allEmails->count(),
                    'generated_emails_count' => $generatedEmails->count(),
                    'emails' => $allEmailAddresses->toArray(), // Full list of emails to be deleted
                    'panels_count' => $orderPanels->count(),
                    'batch_mode' => $isBatchMode,
                ]);

                Log::info("Starting mailbox deletion for Google/365 order", [
                    'action' => 'delete_google365_order_mailboxes',
                    'order_id' => $orderId,
                    'provider_type' => $order->provider_type,
                    'panels_count' => $orderPanels->count(),
                    'total_emails' => $totalCount,
                    'batch_mode' => $isBatchMode,
                    'batch_size' => $batchSize,
                    'emails_stored_in_used_emails_table' => true,
                ]);
            }

            // Apply batch limits if in batch mode
            if ($isBatchMode) {
                $emailsToProcess = $allEmailAddresses->slice($offset, $batchSize);
                
                if ($emailsToProcess->isEmpty()) {
                    Log::info("No emails in batch range", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'offset' => $offset,
                        'batch_size' => $batchSize,
                        'total_count' => $totalCount,
                    ]);
                    
                    return [
                        'processed' => 0,
                        'deleted' => 0,
                        'failed' => 0,
                        'not_found' => 0,
                        'lookup_failed' => 0,
                        'has_more' => false,
                        'next_offset' => $offset,
                        'total_remaining' => 0,
                    ];
                }
            } else {
                // Process all emails in non-batch mode
                $emailsToProcess = $allEmailAddresses;
            }

            // Get active provider credentials (or fallback to config)
            $activeProvider = SmtpProviderSplit::getActiveProvider();
            $credentials = $activeProvider ? $activeProvider->getCredentials() : null;
            $mailinService = new MailinAiService($credentials);

            $deletedCount = 0;
            $failedCount = 0;
            $notFoundCount = 0;
            $lookupFailedCount = 0;
            $errors = [];
            $emailsChecked = [];
            $emailsNotFound = [];
            $emailsFound = [];
            $emailsDeleted = [];
            $emailsLookupFailed = [];

            Log::info("Starting to check emails on Mailin.ai for Google/365 order", [
                'action' => 'delete_google365_order_mailboxes',
                'order_id' => $orderId,
                'provider_type' => $order->provider_type,
                'total_emails_to_check' => $isBatchMode ? $emailsToProcess->count() : $totalCount,
                'batch_mode' => $isBatchMode,
                'offset' => $offset,
            ]);

            // Process emails from order_emails table (with or without mailin_mailbox_id)
            // In batch mode, only process emails in the current batch
            foreach ($allEmails as $orderEmail) {
                $email = $orderEmail->email;
                
                // Skip if not in current batch
                if ($isBatchMode && !$emailsToProcess->contains($email)) {
                    continue;
                }
                
                $emailsChecked[] = $email;

                Log::info("Checking email on Mailin.ai", [
                    'action' => 'delete_google365_order_mailboxes',
                    'order_id' => $orderId,
                    'email' => $email,
                    'has_mailin_mailbox_id' => !empty($orderEmail->mailin_mailbox_id),
                    'mailin_mailbox_id' => $orderEmail->mailin_mailbox_id,
                ]);

                $result = $this->processEmailForDeletion($orderEmail, $mailinService, $order);
                
                if ($result['success']) {
                    $deletedCount++;
                    $emailsDeleted[] = $email;
                    $emailsFound[] = $email;
                    
                    Log::info("Email successfully deleted from Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'email' => $email,
                    ]);
                } elseif ($result['not_found']) {
                    $notFoundCount++;
                    $emailsNotFound[] = $email;
                    
                    Log::info("Email not found on Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'email' => $email,
                    ]);
                } elseif ($result['lookup_failed']) {
                    $lookupFailedCount++;
                    $emailsLookupFailed[] = $email;
                    
                    Log::warning("Failed to lookup email on Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'email' => $email,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                } else {
                    $failedCount++;
                    $errors[] = $result['error'] ?? 'Unknown error';
                    
                    Log::error("Failed to delete email from Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'email' => $email,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }
            }

            // Process generated emails (not in order_emails table)
            // In batch mode, only process emails in the current batch
            foreach ($generatedEmails as $generatedEmail) {
                $email = $generatedEmail->email;
                
                // Skip if not in current batch
                if ($isBatchMode && !$emailsToProcess->contains($email)) {
                    continue;
                }
                
                $emailsChecked[] = $email;

                Log::info("Checking generated email on Mailin.ai", [
                    'action' => 'delete_google365_order_mailboxes',
                    'order_id' => $orderId,
                    'email' => $email,
                    'domain' => $generatedEmail->domain,
                    'prefix' => $generatedEmail->prefix,
                ]);

                $result = $this->processGeneratedEmailForDeletion($generatedEmail, $mailinService, $order);
                
                if ($result['success']) {
                    $deletedCount++;
                    $emailsDeleted[] = $email;
                    $emailsFound[] = $email;
                    
                    Log::info("Generated email successfully deleted from Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'email' => $email,
                    ]);
                } elseif ($result['not_found']) {
                    $notFoundCount++;
                    $emailsNotFound[] = $email;
                    
                    Log::info("Generated email not found on Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'email' => $email,
                    ]);
                } elseif ($result['lookup_failed']) {
                    $lookupFailedCount++;
                    $emailsLookupFailed[] = $email;
                    
                    Log::warning("Failed to lookup generated email on Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'email' => $email,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                } else {
                    $failedCount++;
                    $errors[] = $result['error'] ?? 'Unknown error';
                    
                    Log::error("Failed to delete generated email from Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'email' => $email,
                        'error' => $result['error'] ?? 'Unknown error',
                    ]);
                }
            }

            // Calculate next batch info for batch mode
            if ($isBatchMode) {
                $nextOffset = $offset + $batchSize;
                $hasMore = $nextOffset < $totalCount;
                $totalRemaining = max(0, $totalCount - $nextOffset);

                Log::info("Batch processing completed", [
                    'action' => 'delete_google365_order_mailboxes',
                    'order_id' => $orderId,
                    'offset' => $offset,
                    'processed' => $emailsToProcess->count(),
                    'deleted' => $deletedCount,
                    'failed' => $failedCount,
                    'not_found' => $notFoundCount,
                    'lookup_failed' => $lookupFailedCount,
                    'has_more' => $hasMore,
                    'next_offset' => $hasMore ? $nextOffset : $offset,
                    'total_remaining' => $totalRemaining,
                ]);

                // Log activity for mailbox deletion (only on last batch)
                if (!$hasMore && $deletedCount > 0) {
                    ActivityLogService::log(
                        'order-mailboxes-deleted',
                        "Deleted mailboxes for cancelled Google/365 order (batch processing)",
                        $order,
                        [
                            'order_id' => $orderId,
                            'provider_type' => $order->provider_type,
                            'deleted_count' => $deletedCount,
                            'failed_count' => $failedCount,
                            'not_found_count' => $notFoundCount,
                            'lookup_failed_count' => $lookupFailedCount,
                            'errors' => $errors,
                        ]
                    );
                }

                return [
                    'processed' => $emailsToProcess->count(),
                    'deleted' => $deletedCount,
                    'failed' => $failedCount,
                    'not_found' => $notFoundCount,
                    'lookup_failed' => $lookupFailedCount,
                    'has_more' => $hasMore,
                    'next_offset' => $hasMore ? $nextOffset : $offset,
                    'total_remaining' => $totalRemaining,
                ];
            }

            // Log summary with detailed lists (non-batch mode)
            Log::info("Google/365 order mailbox deletion completed", [
                'action' => 'delete_google365_order_mailboxes',
                'order_id' => $orderId,
                'provider_type' => $order->provider_type,
                'total_emails' => $totalCount,
                'total_emails_checked' => count($emailsChecked),
                'deleted_count' => $deletedCount,
                'failed_count' => $failedCount,
                'not_found_count' => $notFoundCount,
                'lookup_failed_count' => $lookupFailedCount,
                'emails_stored_in_used_emails_table_id' => $usedEmailsRecord->id ?? null,
            ]);

            // Log detailed lists (only in non-batch mode to avoid excessive logging)
            if (!$isBatchMode) {
                if (!empty($emailsChecked)) {
                    Log::info("List of all emails checked on Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'emails_checked_count' => count($emailsChecked),
                        'emails_checked' => $emailsChecked,
                    ]);
                }

                if (!empty($emailsFound)) {
                    Log::info("List of emails found on Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'emails_found_count' => count($emailsFound),
                        'emails_found' => $emailsFound,
                    ]);
                }

                if (!empty($emailsNotFound)) {
                    Log::info("List of emails NOT found on Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'emails_not_found_count' => count($emailsNotFound),
                        'emails_not_found' => $emailsNotFound,
                    ]);
                }

                if (!empty($emailsDeleted)) {
                    Log::info("List of emails successfully deleted from Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'emails_deleted_count' => count($emailsDeleted),
                        'emails_deleted' => $emailsDeleted,
                    ]);
                }

                if (!empty($emailsLookupFailed)) {
                    Log::warning("List of emails with lookup failures on Mailin.ai", [
                        'action' => 'delete_google365_order_mailboxes',
                        'order_id' => $orderId,
                        'emails_lookup_failed_count' => count($emailsLookupFailed),
                        'emails_lookup_failed' => $emailsLookupFailed,
                    ]);
                }

                // Log activity for mailbox deletion (non-batch mode)
                if ($deletedCount > 0) {
                    ActivityLogService::log(
                        'order-mailboxes-deleted',
                        "Deleted {$deletedCount} Mailin.ai mailbox(es) for cancelled Google/365 order",
                        $order,
                        [
                            'order_id' => $orderId,
                            'provider_type' => $order->provider_type,
                            'deleted_count' => $deletedCount,
                            'failed_count' => $failedCount,
                            'not_found_count' => $notFoundCount,
                            'lookup_failed_count' => $lookupFailedCount,
                            'errors' => $errors,
                            'used_emails_record_id' => $usedEmailsRecord->id ?? null,
                        ]
                    );
                }
            }

        } catch (\Exception $e) {
            Log::error('Error deleting Google/365 order mailboxes during order cancellation', [
                'action' => 'delete_google365_order_mailboxes',
                'order_id' => is_int($order) ? $order : $order->id,
                'offset' => $offset,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            if ($isBatchMode) {
                return [
                    'processed' => 0,
                    'deleted' => 0,
                    'failed' => 0,
                    'not_found' => 0,
                    'lookup_failed' => 0,
                    'has_more' => false,
                    'next_offset' => $offset,
                    'total_remaining' => 0,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }


    /**
     * Generate emails for splits without customized data
     * 
     * @param \Illuminate\Support\Collection $orderPanels
     * @param Order $order
     * @param \Illuminate\Support\Collection $splitIdsWithCustomizedEmails
     * @return \Illuminate\Support\Collection
     */
    private function generateEmailsForSplitsWithoutCustomizedData($orderPanels, Order $order, $splitIdsWithCustomizedEmails)
    {
        $generatedEmails = collect();
        $reorderInfo = $order->reorderInfo->first();

        if (!$reorderInfo) {
            return $generatedEmails;
        }

        // Extract prefix variants
        $prefixVariants = $this->extractPrefixVariants($reorderInfo);

        foreach ($orderPanels as $panel) {
            foreach ($panel->orderPanelSplits as $split) {
                // Skip splits that have customized emails
                if ($splitIdsWithCustomizedEmails->contains($split->id)) {
                    continue;
                }

                // Extract domains from split
                $domains = $this->extractDomainsFromSplit($split);

                if (empty($domains) || empty($prefixVariants)) {
                    continue;
                }

                // Generate emails for this split
                foreach ($domains as $domain) {
                    foreach ($prefixVariants as $prefix) {
                        $email = $prefix . '@' . $domain;
                        $generatedEmails->push((object)[
                            'email' => $email,
                            'domain' => $domain,
                            'prefix' => $prefix,
                        ]);
                    }
                }
            }
        }

        return $generatedEmails;
    }

    /**
     * Extract prefix variants from reorder info
     * 
     * @param object $reorderInfo
     * @return array
     */
    private function extractPrefixVariants($reorderInfo)
    {
        $prefixVariants = [];

        if ($reorderInfo && $reorderInfo->prefix_variants) {
            if (is_string($reorderInfo->prefix_variants)) {
                $decoded = json_decode($reorderInfo->prefix_variants, true);
                if (is_array($decoded)) {
                    // It's a JSON object like {"prefix_variant_1": "Ryan", "prefix_variant_2": "RyanL"}
                    $prefixVariants = array_values($decoded);
                } else {
                    // It's a comma-separated string
                    $prefixVariants = explode(',', $reorderInfo->prefix_variants);
                    $prefixVariants = array_map('trim', $prefixVariants);
                }
            } elseif (is_array($reorderInfo->prefix_variants)) {
                // Already an array, extract values if it's associative
                $prefixVariants = array_values($reorderInfo->prefix_variants);
            }
        }

        // Default prefixes if none found
        if (empty($prefixVariants)) {
            $prefixVariants = ['info', 'contact'];
        }

        return array_filter($prefixVariants);
    }

    /**
     * Extract domains from split
     * 
     * @param OrderPanelSplit $split
     * @return array
     */
    private function extractDomainsFromSplit(OrderPanelSplit $split)
    {
        $domains = [];

        if ($split->domains) {
            // Handle both JSON string and array
            $domainsData = is_string($split->domains) 
                ? json_decode($split->domains, true) 
                : $split->domains;

            if (is_array($domainsData)) {
                foreach ($domainsData as $domain) {
                    if (is_array($domain) && isset($domain['domain'])) {
                        $domains[] = $domain['domain'];
                    } elseif (is_string($domain)) {
                        $domains[] = $domain;
                    }
                }
            }
        }

        return array_filter($domains);
    }

    /**
     * Process a single email from order_emails table for deletion
     * 
     * @param OrderEmail $orderEmail
     * @param MailinAiService $mailinService
     * @param Order $order
     * @return array
     */
    private function processEmailForDeletion(OrderEmail $orderEmail, MailinAiService $mailinService, Order $order)
    {
        $mailboxId = $orderEmail->mailin_mailbox_id;
        $email = $orderEmail->email;

        // If no mailbox ID, try to lookup
        if (!$mailboxId) {
            Log::info("Email has no mailbox ID, attempting lookup on Mailin.ai", [
                'order_id' => $order->id,
                'order_email_id' => $orderEmail->id,
                'email' => $email,
            ]);

            $lookupResult = $mailinService->lookupMailboxIdByEmail($email);
            
            if ($lookupResult['success'] && $lookupResult['mailbox_id']) {
                $mailboxId = $lookupResult['mailbox_id'];
                
                Log::info("Mailbox ID found via lookup, updating OrderEmail record", [
                    'order_id' => $order->id,
                    'order_email_id' => $orderEmail->id,
                    'email' => $email,
                    'mailbox_id' => $mailboxId,
                ]);

                // Optionally update OrderEmail record with found ID
                $orderEmail->update(['mailin_mailbox_id' => $mailboxId]);
            } else {
                // Check if it's a "not found" vs "lookup failed"
                if (isset($lookupResult['not_found']) && $lookupResult['not_found']) {
                    return [
                        'success' => false,
                        'not_found' => true,
                        'error' => $lookupResult['message'] ?? 'Mailbox not found on Mailin.ai',
                    ];
                }

                return [
                    'success' => false,
                    'lookup_failed' => true,
                    'error' => $lookupResult['message'] ?? 'Failed to lookup mailbox ID',
                ];
            }
        } else {
            Log::info("Email has existing mailbox ID, proceeding with deletion", [
                'order_id' => $order->id,
                'order_email_id' => $orderEmail->id,
                'email' => $email,
                'mailbox_id' => $mailboxId,
            ]);
        }

        // Delete mailbox from Mailin.ai
        try {
            Log::info("Attempting to delete mailbox from Mailin.ai", [
                'order_id' => $order->id,
                'order_email_id' => $orderEmail->id,
                'email' => $email,
                'mailbox_id' => $mailboxId,
            ]);

            $result = $mailinService->deleteMailbox($mailboxId);

            if ($result['success']) {
                // Delete OrderEmail record after successful deletion
                $orderEmail->delete();

                Log::info('Mailbox deleted successfully from Mailin.ai and database', [
                    'action' => 'process_email_for_deletion',
                    'order_id' => $order->id,
                    'order_email_id' => $orderEmail->id,
                    'mailin_mailbox_id' => $mailboxId,
                    'email' => $email,
                ]);

                return ['success' => true];
            } else {
                Log::warning('Mailbox deletion from Mailin.ai failed', [
                    'action' => 'process_email_for_deletion',
                    'order_id' => $order->id,
                    'order_email_id' => $orderEmail->id,
                    'mailin_mailbox_id' => $mailboxId,
                    'email' => $email,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'Unknown error',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception occurred while deleting mailbox from Mailin.ai', [
                'action' => 'process_email_for_deletion',
                'order_id' => $order->id,
                'order_email_id' => $orderEmail->id,
                'mailin_mailbox_id' => $mailboxId,
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process a generated email (not in order_emails table) for deletion
     * 
     * @param object $generatedEmail
     * @param MailinAiService $mailinService
     * @param Order $order
     * @return array
     */
    private function processGeneratedEmailForDeletion($generatedEmail, MailinAiService $mailinService, Order $order)
    {
        $email = $generatedEmail->email;

        Log::info("Processing generated email for deletion (not in order_emails table)", [
            'order_id' => $order->id,
            'email' => $email,
            'domain' => $generatedEmail->domain ?? null,
            'prefix' => $generatedEmail->prefix ?? null,
        ]);

        // Lookup mailbox ID by email
        $lookupResult = $mailinService->lookupMailboxIdByEmail($email);

        if (!$lookupResult['success'] || !$lookupResult['mailbox_id']) {
            if (isset($lookupResult['not_found']) && $lookupResult['not_found']) {
                return [
                    'success' => false,
                    'not_found' => true,
                    'error' => $lookupResult['message'] ?? 'Mailbox not found on Mailin.ai',
                ];
            }

            return [
                'success' => false,
                'lookup_failed' => true,
                'error' => $lookupResult['message'] ?? 'Failed to lookup mailbox ID',
            ];
        }

        $mailboxId = $lookupResult['mailbox_id'];

        Log::info("Mailbox ID found for generated email, proceeding with deletion", [
            'order_id' => $order->id,
            'email' => $email,
            'mailbox_id' => $mailboxId,
        ]);

        // Delete mailbox from Mailin.ai
        try {
            Log::info("Attempting to delete generated mailbox from Mailin.ai", [
                'order_id' => $order->id,
                'email' => $email,
                'mailbox_id' => $mailboxId,
            ]);

            $result = $mailinService->deleteMailbox($mailboxId);

            if ($result['success']) {
                Log::info('Generated mailbox deleted successfully from Mailin.ai', [
                    'action' => 'process_generated_email_for_deletion',
                    'order_id' => $order->id,
                    'mailin_mailbox_id' => $mailboxId,
                    'email' => $email,
                ]);

                return ['success' => true];
            } else {
                Log::warning('Generated mailbox deletion from Mailin.ai failed', [
                    'action' => 'process_generated_email_for_deletion',
                    'order_id' => $order->id,
                    'mailin_mailbox_id' => $mailboxId,
                    'email' => $email,
                    'error' => $result['message'] ?? 'Unknown error',
                ]);

                return [
                    'success' => false,
                    'error' => $result['message'] ?? 'Unknown error',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Exception occurred while deleting generated mailbox from Mailin.ai', [
                'action' => 'process_generated_email_for_deletion',
                'order_id' => $order->id,
                'mailin_mailbox_id' => $mailboxId,
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
