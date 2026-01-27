<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Services\MailAutomation\DomainActivationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ActivateDomainsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'mailin:activate-domains 
                            {order_id : The order ID to process}
                            {--dry-run : Show what would be done without making changes}
                            {--bypass-check : Bypass existing mailbox check}';

    /**
     * The console command description.
     */
    protected $description = 'Activate domains for an order (transfer if needed, reject if unavailable)';

    /**
     * Execute the console command.
     */
    public function handle(DomainActivationService $service)
    {
        $orderId = $this->argument('order_id');
        $isDryRun = $this->option('dry-run');
        $bypassCheck = $this->option('bypass-check');

        $this->info("Processing Order #{$orderId}" . ($isDryRun ? ' (DRY RUN)' : '') . ($bypassCheck ? ' (BYPASS CHECK)' : ''));

        // Load order
        $order = Order::with(['reorderInfo', 'platformCredentials'])->find($orderId);
        if (!$order) {
            $this->error("Order #{$orderId} not found");
            return 1;
        }

        // Get provider splits
        $splits = OrderProviderSplit::where('order_id', $orderId)->get();
        if ($splits->isEmpty()) {
            $this->error("No provider splits found for order #{$orderId}");
            $this->warn("Run the job to create provider splits first.");
            return 1;
        }

        $this->info("Found {$splits->count()} provider split(s)");
        $this->newLine();

        // Display splits info
        foreach ($splits as $split) {
            $this->info("Provider: {$split->provider_name} ({$split->provider_slug})");
            $this->info("Domains: " . implode(', ', $split->domains ?? []));
            $this->info("All Active: " . ($split->all_domains_active ? 'YES' : 'NO'));
            $this->newLine();
        }

        if ($isDryRun) {
            $this->warn("DRY RUN - Would check and activate domains");
            foreach ($splits as $split) {
                foreach ($split->domains ?? [] as $domain) {
                    $this->line("  Would check: {$domain} on {$split->provider_slug}");
                }
            }
            return 0;
        }

        // Run activation
        $this->info("Activating domains...");
        $this->newLine();

        $result = $service->activateDomainsForOrder($order, $bypassCheck);

        if ($result['rejected']) {
            $this->error("Order REJECTED: {$result['reason']}");

            Log::channel('mailin-ai')->warning('Order rejected by activate-domains command', [
                'order_id' => $orderId,
                'reason' => $result['reason'],
            ]);

            return 1;
        }

        // Display results per provider
        foreach ($result['results'] ?? [] as $providerSlug => $providerResult) {
            $this->info("Provider: {$providerSlug}");
            $this->info("  Active: " . count($providerResult['active'] ?? []));
            $this->info("  Transferred: " . count($providerResult['transferred'] ?? []));

            if (!empty($providerResult['failed'])) {
                $this->warn("  Failed: " . count($providerResult['failed']));
            }
            $this->newLine();
        }

        // Check if all domains are active
        $allActive = OrderProviderSplit::areAllDomainsActiveForOrder($orderId);

        if ($allActive) {
            $this->info("✓ All domains are ACTIVE across all providers");
            $this->info("  You can now run: php artisan mailin:create-mailboxes {$orderId}");
        } else {
            $this->warn("Some domains are still pending activation");
            $this->warn("  Run this command again later to check status");
        }

        return 0;
    }
}
