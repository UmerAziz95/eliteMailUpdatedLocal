<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Models\Order;
use App\Models\OrderEmail;
use App\Services\OrderCancelledService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DeleteExpiredMailboxes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mailboxes:delete-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Mailin.ai mailboxes for EOBC cancelled subscriptions that have reached their end date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting expired mailbox deletion process...');

        try {
            // Check if Mailin.ai automation is enabled
            $automationEnabled = config('mailin_ai.automation_enabled', false);
            
            if (!$automationEnabled) {
                $this->info('Mailin.ai automation is disabled. Skipping mailbox deletion.');
                Log::channel('mailin-ai')->info('Skipping expired mailbox deletion - automation disabled', [
                    'action' => 'delete_expired_mailboxes',
                ]);
                return 0;
            }

            // Find all cancelled subscriptions that:
            // 1. Are EOBC cancellations (is_cancelled_force = false)
            // 2. Have reached their end_date (end_date <= now)
            // 3. Status is 'cancelled'
            // 4. Still have mailboxes to delete (have OrderEmail records with mailin_mailbox_id)
            // 5. Associated order has provider_type = 'Private SMTP'
            $expiredSubscriptions = Subscription::where('subscriptions.status', 'cancelled')
                ->where('subscriptions.is_cancelled_force', false)
                ->whereNotNull('subscriptions.end_date')
                ->where('subscriptions.end_date', '<=', now())
                ->join('orders', 'orders.chargebee_subscription_id', '=', 'subscriptions.chargebee_subscription_id')
                ->where('orders.provider_type', 'Private SMTP')
                ->join('order_emails', 'order_emails.order_id', '=', 'orders.id')
                ->whereNotNull('order_emails.mailin_mailbox_id')
                ->select('subscriptions.*')
                ->distinct()
                ->with('order')
                ->get();

            $this->info("Found {$expiredSubscriptions->count()} expired EOBC subscriptions to process.");

            Log::channel('mailin-ai')->info('Found expired EOBC subscriptions for mailbox deletion', [
                'action' => 'delete_expired_mailboxes',
                'count' => $expiredSubscriptions->count(),
                'subscription_ids' => $expiredSubscriptions->pluck('id')->toArray(),
            ]);

            if ($expiredSubscriptions->isEmpty()) {
                $this->info('No expired subscriptions found.');
                return 0;
            }

            $cancelledService = new OrderCancelledService();
            $processedCount = 0;
            $skippedCount = 0;
            $deletedCount = 0;

            foreach ($expiredSubscriptions as $subscription) {
                try {
                    // Get the associated order (relationship is eager-loaded)
                    $order = $subscription->order;

                    if (!$order) {
                        $this->warn("No order found for subscription ID: {$subscription->id}");
                        Log::channel('mailin-ai')->warning('No order found for expired subscription', [
                            'action' => 'delete_expired_mailboxes',
                            'subscription_id' => $subscription->id,
                            'chargebee_subscription_id' => $subscription->chargebee_subscription_id,
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    // Check if provider_type is "Private SMTP"
                    if ($order->provider_type !== 'Private SMTP') {
                        $this->info("Skipping order #{$order->id} - provider_type is '{$order->provider_type}' (not Private SMTP)");
                        Log::channel('mailin-ai')->info('Skipping order - not Private SMTP', [
                            'action' => 'delete_expired_mailboxes',
                            'order_id' => $order->id,
                            'subscription_id' => $subscription->id,
                            'provider_type' => $order->provider_type,
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    // Double-check: Verify that mailboxes still exist (defense in depth)
                    $hasMailboxes = OrderEmail::where('order_id', $order->id)
                        ->whereNotNull('mailin_mailbox_id')
                        ->exists();

                    if (!$hasMailboxes) {
                        $this->info("Skipping order #{$order->id} - no mailboxes found (already deleted)");
                        Log::channel('mailin-ai')->info('Skipping order - mailboxes already deleted', [
                            'action' => 'delete_expired_mailboxes',
                            'order_id' => $order->id,
                            'subscription_id' => $subscription->id,
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    $this->info("Processing order #{$order->id} for subscription #{$subscription->id} (end_date: {$subscription->end_date})");

                    // Delete mailboxes for this order
                    // The deleteOrderMailboxes method will check automation_enabled and provider_type again
                    $cancelledService->deleteOrderMailboxes($order);

                    $processedCount++;
                    $deletedCount++;

                    Log::channel('mailin-ai')->info('Successfully processed expired subscription for mailbox deletion', [
                        'action' => 'delete_expired_mailboxes',
                        'order_id' => $order->id,
                        'subscription_id' => $subscription->id,
                        'end_date' => $subscription->end_date,
                    ]);

                } catch (\Exception $e) {
                    $this->error("Error processing subscription #{$subscription->id}: " . $e->getMessage());
                    Log::channel('mailin-ai')->error('Error processing expired subscription for mailbox deletion', [
                        'action' => 'delete_expired_mailboxes',
                        'subscription_id' => $subscription->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $skippedCount++;
                }
            }

            $this->info("Processed: {$processedCount} orders, Skipped: {$skippedCount} subscriptions");
            
            Log::channel('mailin-ai')->info('Expired mailbox deletion process completed', [
                'action' => 'delete_expired_mailboxes',
                'total_subscriptions' => $expiredSubscriptions->count(),
                'processed_count' => $processedCount,
                'skipped_count' => $skippedCount,
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('Fatal error in expired mailbox deletion: ' . $e->getMessage());
            Log::channel('mailin-ai')->error('Fatal error in expired mailbox deletion process', [
                'action' => 'delete_expired_mailboxes',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }
}

