<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Services\MailAutomation\MailboxCreationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateMailboxesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mailin:create-mailboxes 
                            {order_id : The order ID to process}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Create mailboxes even if not all domains are active}';

    /**
     * The console command description.
     */
    protected $description = 'Create mailboxes for an order (requires all domains to be active)';

    /**
     * Execute the console command.
     */
    public function handle(MailboxCreationService $service)
    {
        $orderId = $this->argument('order_id');
        $isDryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info("Processing Order #{$orderId}" . ($isDryRun ? ' (DRY RUN)' : ''));

        // Load order
        $order = Order::with(['reorderInfo'])->find($orderId);
        if (!$order) {
            $this->error("Order #{$orderId} not found");
            return 1;
        }

        // Get splits
        $splits = OrderProviderSplit::where('order_id', $orderId)->get();
        if ($splits->isEmpty()) {
            $this->error("No provider splits found for order #{$orderId}");
            return 1;
        }

        // GATE CHECK: All domains must be active
        $allActive = OrderProviderSplit::areAllDomainsActiveForOrder($orderId);

        if (!$allActive && !$force) {
            $this->error("Not all domains are active. Run mailin:activate-domains first.");
            $this->info("Or use --force to create mailboxes anyway.");
            $this->newLine();

            // Show status
            foreach ($splits as $split) {
                $this->info("Provider: {$split->provider_slug}");
                $this->info("  All Active: " . ($split->all_domains_active ? 'YES' : 'NO'));
                foreach ($split->domains ?? [] as $domain) {
                    $status = $split->getDomainStatus($domain) ?? 'unknown';
                    $icon = $status === 'active' ? '✓' : '✗';
                    $this->line("  {$icon} {$domain}: {$status}");
                }
                $this->newLine();
            }

            return 1;
        }

        if ($force && !$allActive) {
            $this->warn("Force mode: Creating mailboxes even though not all domains are active");
        }

        // Get prefix variants from reorder info
        $reorderInfo = $order->reorderInfo->first();
        if (!$reorderInfo) {
            $this->error("No reorder info found for order");
            return 1;
        }

        $prefixVariants = $reorderInfo->prefix_variants ?? [];
        $prefixVariantsDetails = $reorderInfo->prefix_variants_details ?? [];

        if (empty($prefixVariants)) {
            $this->error("No prefix variants found in reorder info");
            return 1;
        }

        $this->info("Prefix variants: " . count($prefixVariants));
        $this->newLine();

        // Dry run
        if ($isDryRun) {
            $this->warn("DRY RUN - Would create mailboxes:");
            foreach ($splits as $split) {
                $this->info("Provider: {$split->provider_slug}");
                foreach ($split->domains ?? [] as $domain) {
                    foreach ($prefixVariants as $prefix) {
                        $this->line("  Would create: {$prefix}@{$domain}");
                    }
                }
            }
            return 0;
        }

        // Create mailboxes
        $this->info("Creating mailboxes...");
        $this->newLine();

        $result = $service->createMailboxesForOrder($order, $prefixVariants, $prefixVariantsDetails, $force);

        if (!$result['success']) {
            $this->error("Mailbox creation failed: " . ($result['error'] ?? 'Unknown error'));
            return 1;
        }

        // Display results
        foreach ($result['results'] ?? [] as $providerSlug => $providerResult) {
            $this->info("Provider: {$providerSlug}");
            $this->info("  Created: " . count($providerResult['created'] ?? []));

            if (!empty($providerResult['failed'])) {
                $this->warn("  Failed: " . count($providerResult['failed']));
            }
            $this->newLine();
        }

        $this->info("Total mailboxes created: " . ($result['total_created'] ?? 0));

        // Refresh order
        $order->refresh();
        if ($order->status_manage_by_admin === 'completed') {
            $this->info("✓ Order marked as COMPLETED");
        }

        return 0;
    }
}
