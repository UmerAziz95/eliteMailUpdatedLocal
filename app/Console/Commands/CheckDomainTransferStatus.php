<?php

namespace App\Console\Commands;

use App\Models\DomainTransfer;
use App\Models\Order;
use App\Models\SmtpProviderSplit;
use App\Services\MailinAiService;
use App\Jobs\MailinAi\CreateMailboxesOnOrderJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CheckDomainTransferStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:check-transfer-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check domain transfer status and create mailboxes when domains become active';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting domain transfer status check...');

        try {
            // Get all pending domain transfers (including rate-limited ones)
            $pendingTransfers = DomainTransfer::where('status', 'pending')
                ->with('order')
                ->get();
            
            // Separate rate-limited transfers that need retry
            $rateLimitedTransfers = $pendingTransfers->filter(function($transfer) {
                return $transfer->error_message && 
                       (str_contains($transfer->error_message, 'Rate limit') || 
                        str_contains($transfer->error_message, 'Too Many Attempts'));
            });
            
            // Retry rate-limited transfers
            if ($rateLimitedTransfers->count() > 0) {
                $this->info("Found {$rateLimitedTransfers->count()} rate-limited domain transfer(s) to retry...");
                Log::channel('mailin-ai')->info('Retrying rate-limited domain transfers', [
                    'action' => 'check_domain_transfer_status',
                    'rate_limited_count' => $rateLimitedTransfers->count(),
                    'rate_limited_ids' => $rateLimitedTransfers->pluck('id')->toArray(),
                ]);
                
                $this->retryRateLimitedTransfers($rateLimitedTransfers);
            }
            
            Log::channel('mailin-ai')->info('Found pending domain transfers', [
                'action' => 'check_domain_transfer_status',
                'pending_count' => $pendingTransfers->count(),
                'rate_limited_count' => $rateLimitedTransfers->count(),
                'pending_ids' => $pendingTransfers->pluck('id')->toArray(),
            ]);
            
            // Also get completed transfers that haven't triggered mailbox creation yet
            $allCompleted = DomainTransfer::where('status', 'completed')
                ->with('order')
                ->get();
            
            $completedTransfers = $allCompleted->filter(function ($transfer) {
                // Check if mailboxes have been successfully created for this order
                $hasCompletedMailboxes = \App\Models\OrderAutomation::where('order_id', $transfer->order_id)
                    ->where('action_type', 'mailbox')
                    ->where('status', 'completed')
                    ->exists();
                
                // Also check if there are any OrderEmail records (actual mailboxes created)
                $hasOrderEmails = \App\Models\OrderEmail::where('order_id', $transfer->order_id)->exists();
                
                // Only process if mailboxes haven't been created yet (no completed automation AND no order emails)
                $shouldProcess = !$hasCompletedMailboxes && !$hasOrderEmails;
                
                if (!$shouldProcess) {
                    Log::channel('mailin-ai')->debug('Skipping completed transfer - mailboxes already exist', [
                        'action' => 'check_domain_transfer_status',
                        'domain_transfer_id' => $transfer->id,
                        'order_id' => $transfer->order_id,
                        'has_completed_automation' => $hasCompletedMailboxes,
                        'has_order_emails' => $hasOrderEmails,
                    ]);
                }
                
                return $shouldProcess;
            });
            
            Log::channel('mailin-ai')->info('Found completed domain transfers without mailboxes', [
                'action' => 'check_domain_transfer_status',
                'total_completed' => $allCompleted->count(),
                'without_mailboxes' => $completedTransfers->count(),
                'completed_ids' => $completedTransfers->pluck('id')->toArray(),
            ]);
            
            // Merge both collections
            $allTransfers = $pendingTransfers->merge($completedTransfers);

            if ($allTransfers->isEmpty()) {
                $this->info('No domain transfers found that need processing.');
                return 0;
            }

            $this->info("Found {$allTransfers->count()} domain transfer(s) to process ({$pendingTransfers->count()} pending, {$completedTransfers->count()} completed without mailboxes).");

            // Get active provider credentials (or fallback to config)
            $activeProvider = SmtpProviderSplit::getActiveProvider();
            $credentials = $activeProvider ? $activeProvider->getCredentials() : null;
            $mailinService = new MailinAiService($credentials);
            $processedOrders = [];

            foreach ($allTransfers as $domainTransfer) {
                try {
                    $this->line("Checking domain: {$domainTransfer->domain_name}");

                    // Check domain status via Mailin.ai public API
                    $statusResult = $mailinService->checkDomainStatus($domainTransfer->domain_name);

                    // Handle network errors (will retry on next run)
                    if (isset($statusResult['network_error']) && $statusResult['network_error']) {
                        $this->warn("  Network error checking {$domainTransfer->domain_name} - will retry on next run");
                        continue; // Skip to next domain
                    }

                    // Handle case where domain is not found yet (404 - still transferring)
                    if (isset($statusResult['not_found']) && $statusResult['not_found']) {
                        $this->line("  Domain {$domainTransfer->domain_name} not found in Mailin.ai yet (still transferring)");
                        continue; // Skip to next domain
                    }

                    if ($statusResult['success'] && isset($statusResult['status'])) {
                        $domainStatus = $statusResult['status'];
                        $nameServerStatus = $statusResult['name_server_status'] ?? null;
                        $nameServers = $statusResult['name_servers'] ?? [];

                        // Ensure nameServers is an array for JSON storage
                        if (!is_array($nameServers)) {
                            // If it's a string, convert to array
                            if (is_string($nameServers)) {
                                $nameServers = array_map('trim', explode(',', $nameServers));
                            } else {
                                $nameServers = [];
                            }
                        }
                        
                        // Update domain transfer record with latest status
                        $domainTransfer->update([
                            'domain_status' => $domainStatus,
                            'name_server_status' => $nameServerStatus,
                            'name_servers' => $nameServers, // Store as array (will be cast to JSON)
                        ]);

                        // If domain is active, mark transfer as completed
                        if ($domainStatus === 'active') {
                            $domainTransfer->update([
                                'status' => 'completed',
                            ]);

                            $this->info("✓ Domain {$domainTransfer->domain_name} is now active!");
                            
                            Log::channel('mailin-ai')->info('Domain transfer marked as completed', [
                                'action' => 'check_domain_transfer_status',
                                'domain_transfer_id' => $domainTransfer->id,
                                'domain_name' => $domainTransfer->domain_name,
                                'order_id' => $domainTransfer->order_id,
                                'status' => 'completed',
                            ]);

                            // Track order for mailbox creation
                            if ($domainTransfer->order_id && !in_array($domainTransfer->order_id, $processedOrders)) {
                                $processedOrders[] = $domainTransfer->order_id;
                                $this->info("  → Order #{$domainTransfer->order_id} queued for mailbox creation check");
                                
                                Log::channel('mailin-ai')->info('Order queued for mailbox creation check', [
                                    'action' => 'check_domain_transfer_status',
                                    'order_id' => $domainTransfer->order_id,
                                    'domain_name' => $domainTransfer->domain_name,
                                ]);
                            }
                        } else {
                            $this->line("  Domain {$domainTransfer->domain_name} status: {$domainStatus} (still pending)");
                        }
                    } else {
                        $this->warn("  Could not check status for domain: {$domainTransfer->domain_name}");
                    }
                } catch (\Exception $e) {
                    $this->error("Error checking domain {$domainTransfer->domain_name}: " . $e->getMessage());
                    Log::channel('mailin-ai')->error('Error checking domain transfer status', [
                        'action' => 'check_domain_transfer_status',
                        'domain_transfer_id' => $domainTransfer->id,
                        'domain_name' => $domainTransfer->domain_name,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Process orders with all domains completed
            if (count($processedOrders) > 0) {
                $this->info("Processing " . count($processedOrders) . " order(s) for mailbox creation...");
                Log::channel('mailin-ai')->info('Processing orders for mailbox creation', [
                    'action' => 'check_domain_transfer_status',
                    'order_count' => count($processedOrders),
                    'order_ids' => $processedOrders,
                ]);
            }
            
            foreach ($processedOrders as $orderId) {
                $this->processOrderMailboxCreation($orderId);
            }

            // Check for orders that have been in-progress for too long
            $this->checkDelayedOrders();

            $this->info('Domain transfer status check completed.');
            return 0;

        } catch (\Exception $e) {
            $this->error('Error in domain transfer status check: ' . $e->getMessage());
            Log::channel('mailin-ai')->error('Domain transfer status check command failed', [
                'action' => 'check_domain_transfer_status',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * Process mailbox creation for an order when all domains are active
     */
    private function processOrderMailboxCreation(int $orderId)
    {
        try {
            Log::channel('mailin-ai')->info('Processing order for mailbox creation', [
                'action' => 'process_order_mailbox_creation',
                'order_id' => $orderId,
            ]);
            
            $order = Order::with(['reorderInfo', 'plan'])->find($orderId);

            if (!$order) {
                $this->warn("Order #{$orderId} not found.");
                Log::channel('mailin-ai')->warning('Order not found for mailbox creation', [
                    'action' => 'process_order_mailbox_creation',
                    'order_id' => $orderId,
                ]);
                return;
            }

            // Check if all domains for this order are completed
            $allDomainTransfers = DomainTransfer::where('order_id', $orderId)->get();
            $completedCount = $allDomainTransfers->where('status', 'completed')->count();
            $totalCount = $allDomainTransfers->count();

            Log::channel('mailin-ai')->info('Checking domain transfer completion status', [
                'action' => 'process_order_mailbox_creation',
                'order_id' => $orderId,
                'completed_count' => $completedCount,
                'total_count' => $totalCount,
            ]);

            if ($completedCount < $totalCount || $totalCount === 0) {
                $this->line("Order #{$orderId}: Not all domains are completed yet ({$completedCount}/{$totalCount}).");
                Log::channel('mailin-ai')->info('Not all domains completed yet', [
                    'action' => 'process_order_mailbox_creation',
                    'order_id' => $orderId,
                    'completed_count' => $completedCount,
                    'total_count' => $totalCount,
                ]);
                return;
            }

            // Check if order already has mailboxes created
            $existingAutomation = \App\Models\OrderAutomation::where('order_id', $orderId)
                ->where('action_type', 'mailbox')
                ->where('status', 'completed')
                ->first();

            if ($existingAutomation) {
                $this->line("Order #{$orderId}: Mailboxes already created.");
                return;
            }

            $this->info("Order #{$orderId}: All domains are active. Creating mailboxes...");

            // Get domains and prefix variants from reorderInfo
            if (!$order->reorderInfo || $order->reorderInfo->count() === 0) {
                $this->warn("Order #{$orderId}: No reorder info found.");
                return;
            }

            $reorderInfo = $order->reorderInfo->first();
            $domains = array_filter(
                array_map('trim', explode("\n", $reorderInfo->domains ?? ''))
            );

            if (empty($domains)) {
                $this->warn("Order #{$orderId}: No domains found in reorder info.");
                return;
            }

            // Get prefix variants
            $prefixVariants = [];
            $prefixVariantsData = json_decode($reorderInfo->prefix_variants ?? '{}', true);
            if (is_array($prefixVariantsData)) {
                foreach ($prefixVariantsData as $key => $value) {
                    if (!empty($value)) {
                        $prefixVariants[] = $value;
                    }
                }
            }

            if (empty($prefixVariants)) {
                $this->warn("Order #{$orderId}: No prefix variants found.");
                return;
            }

            // Dispatch mailbox creation job
            CreateMailboxesOnOrderJob::dispatch(
                $orderId,
                $domains,
                $prefixVariants,
                $order->user_id,
                $order->provider_type ?? 'Private SMTP'
            );

            $this->info("Order #{$orderId}: Mailbox creation job dispatched.");

            Log::channel('mailin-ai')->info('Mailbox creation job dispatched for completed domain transfer', [
                'action' => 'process_order_mailbox_creation',
                'order_id' => $orderId,
                'domains' => $domains,
                'prefix_variants' => $prefixVariants,
            ]);

        } catch (\Exception $e) {
            $this->error("Error processing order #{$orderId}: " . $e->getMessage());
            Log::channel('mailin-ai')->error('Error processing order mailbox creation', [
                'action' => 'process_order_mailbox_creation',
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Retry rate-limited domain transfers
     * 
     * @param \Illuminate\Support\Collection $rateLimitedTransfers
     * @return void
     */
    private function retryRateLimitedTransfers($rateLimitedTransfers)
    {
        $mailinService = new MailinAiService();
        $delayBetweenRetries = config('mailin_ai.domain_transfer_delay', 2);
        $retryCount = 0;
        
        foreach ($rateLimitedTransfers as $transfer) {
            try {
                // Add delay between retries
                if ($retryCount > 0) {
                    sleep($delayBetweenRetries);
                }
                
                $this->line("Retrying transfer for domain: {$transfer->domain_name}");
                
                Log::channel('mailin-ai')->info('Retrying rate-limited domain transfer', [
                    'action' => 'retry_rate_limited_transfer',
                    'domain_transfer_id' => $transfer->id,
                    'domain_name' => $transfer->domain_name,
                    'order_id' => $transfer->order_id,
                ]);
                
                // Clear the error message and retry
                $transferResult = $mailinService->transferDomain($transfer->domain_name);
                
                if ($transferResult['success']) {
                    $nameServers = $transferResult['name_servers'] ?? [];
                    
                    // Ensure nameServers is an array
                    if (!is_array($nameServers)) {
                        if (is_string($nameServers)) {
                            $nameServers = array_filter(array_map('trim', explode(',', $nameServers)));
                            $nameServers = array_values($nameServers);
                        } else {
                            $nameServers = [];
                        }
                    }
                    
                    // Update transfer record - clear error and update nameservers
                    $transfer->update([
                        'name_servers' => $nameServers,
                        'status' => 'pending',
                        'error_message' => null, // Clear the rate limit error
                        'response_data' => $transferResult['response'] ?? null,
                    ]);
                    
                    $this->info("✓ Successfully retried transfer for {$transfer->domain_name}");
                    
                    Log::channel('mailin-ai')->info('Rate-limited domain transfer retry successful', [
                        'action' => 'retry_rate_limited_transfer',
                        'domain_transfer_id' => $transfer->id,
                        'domain_name' => $transfer->domain_name,
                        'order_id' => $transfer->order_id,
                    ]);
                }
                
                $retryCount++;
                
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                $isRateLimitError = $e->getCode() === 429 
                    || str_contains($errorMessage, 'rate limit') 
                    || str_contains($errorMessage, 'Too Many Attempts')
                    || str_contains($errorMessage, '429');
                
                if ($isRateLimitError) {
                    // Still rate limited - update error message with timestamp
                    $transfer->update([
                        'error_message' => 'Rate limit exceeded. Will retry automatically: ' . $errorMessage . ' (Last retry: ' . now()->toDateTimeString() . ')',
                    ]);
                    
                    $this->warn("  Rate limit still active for {$transfer->domain_name} - will retry later");
                    
                    Log::channel('mailin-ai')->warning('Rate-limited domain transfer still rate limited on retry', [
                        'action' => 'retry_rate_limited_transfer',
                        'domain_transfer_id' => $transfer->id,
                        'domain_name' => $transfer->domain_name,
                        'order_id' => $transfer->order_id,
                        'error' => $errorMessage,
                    ]);
                } else {
                    // Other error - mark as failed
                    $transfer->update([
                        'status' => 'failed',
                        'error_message' => $errorMessage,
                    ]);
                    
                    $this->error("  Failed to retry transfer for {$transfer->domain_name}: {$errorMessage}");
                    
                    Log::channel('mailin-ai')->error('Rate-limited domain transfer retry failed', [
                        'action' => 'retry_rate_limited_transfer',
                        'domain_transfer_id' => $transfer->id,
                        'domain_name' => $transfer->domain_name,
                        'order_id' => $transfer->order_id,
                        'error' => $errorMessage,
                    ]);
                }
            }
        }
    }

    /**
     * Check for orders that have been in-progress for more than configured hours
     * and send Slack notifications if they're waiting for domain transfer completion
     */
    private function checkDelayedOrders()
    {
        try {
            $delayHours = config('mailin_ai.order_delay_notification_hours', 24);
            $delayThreshold = now()->subHours($delayHours);

            // Get orders that:
            // 1. Are in 'in-progress' status
            // 2. Have pending domain transfers (waiting for Mailin/other provider)
            // 3. Were updated more than X hours ago
            // 4. Are Private SMTP orders (automation enabled)
            $delayedOrders = Order::where('status_manage_by_admin', 'in-progress')
                ->whereHas('domainTransfers', function ($query) {
                    $query->where('status', 'pending');
                })
                ->where('updated_at', '<=', $delayThreshold)
                ->where(function ($query) {
                    $query->where('provider_type', 'Private SMTP')
                        ->orWhereHas('plan', function ($planQuery) {
                            $planQuery->where('provider_type', 'Private SMTP');
                        });
                })
                ->with(['user', 'domainTransfers', 'plan'])
                ->get();

            if ($delayedOrders->isEmpty()) {
                return;
            }

            $this->info("Found {$delayedOrders->count()} order(s) that have been in-progress for more than {$delayHours} hours.");

            foreach ($delayedOrders as $order) {
                // Verify order still has pending domain transfers
                $pendingTransfers = $order->domainTransfers->where('status', 'pending');
                if ($pendingTransfers->isEmpty()) {
                    continue; // No pending transfers, skip
                }

                // IMPORTANT: Check if notification should be sent
                // Since this command runs every 5 minutes (12 times per hour), we need to ensure
                // notifications are sent only once per delay interval (e.g., once every 24 hours)
                $lastNotificationSent = $order->last_draft_notification_sent_at;
                $shouldSendNotification = false;
                $reason = '';

                if (!$lastNotificationSent) {
                    // First notification: Order has been in-progress for more than X hours
                    // and notification was never sent before
                    $shouldSendNotification = true;
                    $reason = 'First notification - order delayed for more than ' . $delayHours . ' hours';
                } else {
                    // Subsequent notifications: Check if it's been at least X hours since last notification
                    // This ensures we only send once per delay interval, even though command runs every 5 minutes
                    $hoursSinceLastNotification = now()->diffInHours($lastNotificationSent);
                    $minutesSinceLastNotification = now()->diffInMinutes($lastNotificationSent);
                    
                    if ($hoursSinceLastNotification >= $delayHours) {
                        $shouldSendNotification = true;
                        $reason = 'Recurring notification - ' . $hoursSinceLastNotification . ' hours since last notification (threshold: ' . $delayHours . ' hours)';
                    } else {
                        $reason = 'Skipped - Only ' . $minutesSinceLastNotification . ' minutes since last notification (need ' . ($delayHours * 60) . ' minutes)';
                    }
                }

                if (!$shouldSendNotification) {
                    // Log why we're skipping (for debugging)
                    Log::channel('mailin-ai')->debug('Skipping delayed order notification', [
                        'action' => 'check_delayed_orders',
                        'order_id' => $order->id,
                        'reason' => $reason,
                        'last_notification_sent_at' => $lastNotificationSent ? $lastNotificationSent->toDateTimeString() : 'Never',
                        'delay_hours' => $delayHours,
                    ]);
                    continue; // Skip if not enough time has passed since last notification
                }

                // Send Slack notification (only reaches here if shouldSendNotification is true)
                $this->sendDelayedOrderNotification($order, $pendingTransfers, $delayHours);

                // Update last notification sent timestamp immediately to prevent duplicate sends
                // This is critical since command runs every 5 minutes
                $order->update([
                    'last_draft_notification_sent_at' => now(),
                ]);

                Log::channel('mailin-ai')->info('Sent delayed order notification', [
                    'action' => 'check_delayed_orders',
                    'order_id' => $order->id,
                    'delay_hours' => $delayHours,
                    'pending_transfers_count' => $pendingTransfers->count(),
                    'reason' => $reason,
                    'hours_since_last_notification' => $lastNotificationSent ? now()->diffInHours($lastNotificationSent) : 'N/A (first notification)',
                    'notification_sent_at' => now()->toDateTimeString(),
                ]);
            }

        } catch (\Exception $e) {
            $this->error('Error checking delayed orders: ' . $e->getMessage());
            Log::channel('mailin-ai')->error('Error checking delayed orders', [
                'action' => 'check_delayed_orders',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send Slack notification for delayed order
     */
    private function sendDelayedOrderNotification($order, $pendingTransfers, $delayHours)
    {
        try {
            $pendingDomains = $pendingTransfers->pluck('domain_name')->toArray();
            $domainCount = count($pendingDomains);

            $orderData = [
                'order_id' => $order->id,
                'customer_name' => $order->user ? $order->user->name : 'Unknown',
                'customer_email' => $order->user ? $order->user->email : 'Unknown',
                'delay_hours' => $delayHours,
                'pending_domains_count' => $domainCount,
                'pending_domains' => $pendingDomains,
                'provider_type' => $order->provider_type ?? ($order->plan ? $order->plan->provider_type : null),
            ];

            \App\Services\SlackNotificationService::sendDelayedOrderNotification($orderData);

            $this->info("Sent delayed order notification for Order #{$order->id}");

        } catch (\Exception $e) {
            $this->error("Failed to send delayed order notification for Order #{$order->id}: " . $e->getMessage());
            Log::channel('mailin-ai')->error('Failed to send delayed order notification', [
                'action' => 'send_delayed_order_notification',
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
