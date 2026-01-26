<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Services\MailAutomation\DomainActivationService;
use App\Services\MailAutomation\MailboxCreationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled command to check pending domains and create mailboxes
 * when all domains are active for an order
 */
class CheckPendingDomainsCommand extends Command
{
    protected $signature = 'mailin:check-pending-domains 
                            {--dry-run : Show what would be done without making changes}';

    protected $description = 'Check orders with pending domains and create mailboxes when all active';

    public function handle(DomainActivationService $activationService, MailboxCreationService $mailboxService)
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Checking orders with pending domains...' . ($isDryRun ? ' (DRY RUN)' : ''));

        // Find orders with pending domain activations (in-progress status with splits)
        // Find orders that are in-progress and have automation splits
        // We look at the Orders table to capture those with pending mailboxes too
        $orderIds = Order::where('status_manage_by_admin', 'in-progress')
            ->whereHas('orderProviderSplits')
            ->pluck('id');

        if ($orderIds->isEmpty()) {
            $this->info('No orders with pending domains found.');
            return 0;
        }

        $this->info("Found {$orderIds->count()} order(s) with pending domains");
        $this->newLine();

        $processed = 0;
        $completed = 0;

        foreach ($orderIds as $orderId) {
            $order = Order::with(['reorderInfo', 'platformCredentials'])
                ->where('status_manage_by_admin', 'in-progress')
                ->find($orderId);

            if (!$order) {
                continue; // Order not found or not in-progress
            }

            $this->info("Processing Order #{$orderId}");

            if ($isDryRun) {
                $this->line("  Would check domain status for order #{$orderId}");
                continue;
            }

            try {
                // Step 1: Re-check domain activation
                $result = $activationService->activateDomainsForOrder($order);

                if ($result['rejected']) {
                    $this->warn("  Order rejected: {$result['reason']}");
                    continue;
                }

                // Step 2: Check if all domains are now active
                $allActive = OrderProviderSplit::areAllDomainsActiveForOrder($orderId);

                if ($allActive) {
                    $this->info("  All domains active! Creating mailboxes...");

                    // Step 3: Create mailboxes
                    $reorderInfo = $order->reorderInfo->first();
                    if ($reorderInfo) {
                        $prefixVariants = $reorderInfo->prefix_variants ?? [];
                        $prefixVariantsDetails = $reorderInfo->prefix_variants_details ?? [];

                        $mailboxResult = $mailboxService->createMailboxesForOrder(
                            $order,
                            $prefixVariants,
                            $prefixVariantsDetails
                        );

                        if ($mailboxResult['success']) {
                            $this->info("  ✓ Mailboxes created: {$mailboxResult['total_created']}");
                            $this->info("  ✓ Order completed");
                            $completed++;
                        } else {
                            $this->warn("  Mailbox creation failed: {$mailboxResult['error']}");
                        }
                    }
                } else {
                    $this->line("  Still waiting for domains to activate");
                }

                $processed++;

            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
                Log::channel('mailin-ai')->error('Check pending domains error', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ]);
            }

            $this->newLine();
        }

        $this->info("Processed: {$processed}, Completed: {$completed}");

        return 0;
    }
}
