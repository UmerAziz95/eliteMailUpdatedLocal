<?php

namespace App\Console\Commands\Fixes;

use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\OrderProviderSplit;
use App\Models\ReorderInfo;
use App\Models\DomainTransfer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * Migrate existing Private SMTP orders to the order_provider_splits table
 * 
 * This command:
 * 1. Finds all Private SMTP orders
 * 2. Gets domains from reorder_infos table
 * 3. Gets mailboxes from order_emails table
 * 4. Creates/updates order_provider_splits records with mailin provider
 */
class MigrateOrdersToProviderSplits extends Command
{
    protected $signature = 'fixes:migrate-orders-to-provider-splits 
                            {--order-id= : Process specific order ID only}
                            {--status= : Filter by order status (e.g., completed, in-progress)}
                            {--dry-run : Show what would be done without making changes}
                            {--force : Overwrite existing provider splits}';

    protected $description = 'Migrate existing Private SMTP orders to order_provider_splits table with domains and mailboxes';

    public function handle()
    {
        $this->info('Starting migration of Private SMTP orders to order_provider_splits...');

        $orderId = $this->option('order-id');
        $status = $this->option('status');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Build query for Private SMTP orders
        $ordersQuery = Order::where(function ($query) {
            $query->where('provider_type', 'Private SMTP')
                ->orWhereHas('plan', function ($planQuery) {
                    $planQuery->where('provider_type', 'Private SMTP');
                });
        })
            ->with(['reorderInfo', 'orderProviderSplits']);

        if ($orderId) {
            $ordersQuery->where('id', $orderId);
        }

        if ($status) {
            $ordersQuery->where('status_manage_by_admin', $status);
        }

        $orders = $ordersQuery->get();

        if ($orders->isEmpty()) {
            $this->info('No Private SMTP orders found matching criteria.');
            return 0;
        }

        $this->info("Found {$orders->count()} Private SMTP order(s) to process.");
        $this->newLine();

        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($orders as $order) {
            try {
                $result = $this->processOrder($order, $dryRun, $force);

                $stats['processed']++;
                if ($result === 'created') {
                    $stats['created']++;
                } elseif ($result === 'updated') {
                    $stats['updated']++;
                } elseif ($result === 'skipped') {
                    $stats['skipped']++;
                }

            } catch (\Exception $e) {
                $this->error("Error processing Order #{$order->id}: {$e->getMessage()}");
                Log::channel('mailin-ai')->error('Migration error', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
                $stats['errors']++;
            }
        }

        $this->newLine();
        $this->info('Migration Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Orders Processed', $stats['processed']],
                ['Splits Created', $stats['created']],
                ['Splits Updated', $stats['updated']],
                ['Skipped (already exists)', $stats['skipped']],
                ['Errors', $stats['errors']],
            ]
        );

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes were made. Run without --dry-run to apply changes.');
        }

        return 0;
    }

    /**
     * Process a single order
     */
    private function processOrder(Order $order, bool $dryRun, bool $force): string
    {
        $this->info("Processing Order #{$order->id}...");

        // Check if split already exists
        $existingSplit = OrderProviderSplit::where('order_id', $order->id)
            ->where('provider_slug', 'mailin')
            ->first();

        if ($existingSplit && !$force) {
            // Only update if existing split is missing data (mailboxes or domain_statuses are empty)
            $isMissingData = empty($existingSplit->mailboxes) || empty($existingSplit->domain_statuses);

            if ($isMissingData) {
                $this->line("  [UPDATE] Provider split incomplete for Order #{$order->id} (Missing Data) - Updating...");
            } else {
                $this->line("  [SKIP] Provider split exists and is complete for Order #{$order->id} - Skipping...");
                return 'skipped';
            }
        }

        // Get domains from reorder_info
        $reorderInfo = $order->reorderInfo->first();
        if (!$reorderInfo) {
            $this->warn("  [SKIP] No reorder_info found for Order #{$order->id}");
            return 'skipped';
        }

        $domains = $this->extractDomains($reorderInfo);
        if (empty($domains)) {
            $this->warn("  [SKIP] No domains found for Order #{$order->id}");
            return 'skipped';
        }

        $this->line("  Domains found: " . count($domains));

        // Get mailboxes from order_emails
        $orderEmails = OrderEmail::where('order_id', $order->id)
            ->where(function ($q) {
                $q->where('provider_slug', 'mailin')
                    ->orWhereNull('provider_slug');
            })
            ->get();

        // SKIP if no emails found in order_emails table
        if ($orderEmails->isEmpty()) {
            $this->warn("  [SKIP] No email records found in order_emails for Order #{$order->id}");
            return 'skipped';
        }

        $mailboxes = $this->formatMailboxes($orderEmails, $domains, $reorderInfo);
        $this->line("  Mailboxes found: " . $orderEmails->count());

        // Build domain statuses
        $existingStatuses = $existingSplit ? $existingSplit->domain_statuses : [];
        $domainStatuses = [];
        foreach ($domains as $domain) {
            // Priority 1: Get from DomainTransfer
            $transfer = DomainTransfer::where('order_id', $order->id)
                ->where('domain_name', $domain)
                ->first();

            $nameservers = [];
            if ($transfer && !empty($transfer->name_servers)) {
                $nameservers = $transfer->name_servers;
            } else {
                // Priority 2: Preserve existing
                $nameservers = isset($existingStatuses[$domain]['nameservers'])
                    ? $existingStatuses[$domain]['nameservers']
                    : [];
            }

            $domainStatuses[$domain] = [
                'status' => 'active', // Assume active for completed orders
                'domain_id' => null,
                'updated_at' => now()->toISOString(),
                'nameservers' => $nameservers,
            ];
        }

        // Check if all domains have active mailboxes
        $allDomainsActive = !empty($mailboxes) && count($mailboxes) === count($domains);

        if ($dryRun) {
            $this->info("  [DRY RUN] Would " . ($existingSplit ? 'update' : 'create') . " provider split:");
            $this->line("    Provider: mailin");
            $this->line("    Domains: " . implode(', ', $domains));
            $this->line("    Mailboxes: " . count($mailboxes) . " domain(s) with mailboxes");
            $this->line("    All Domains Active: " . ($allDomainsActive ? 'YES' : 'NO'));
            return $existingSplit ? 'updated' : 'created';
        }

        // Create or update the provider split
        $splitData = [
            'provider_name' => 'Mailin.ai',
            'split_percentage' => 100,
            'domain_count' => count($domains),
            'domains' => $domains,
            'mailboxes' => empty($mailboxes) ? null : $mailboxes,
            'domain_statuses' => empty($domainStatuses) ? null : $domainStatuses,
            'all_domains_active' => $allDomainsActive,
            'priority' => 1,
        ];

        if ($existingSplit) {
            $existingSplit->update($splitData);
            $this->info("  [UPDATED] Provider split for Order #{$order->id}");
            return 'updated';
        } else {
            OrderProviderSplit::create(array_merge($splitData, [
                'order_id' => $order->id,
                'provider_slug' => 'mailin',
            ]));
            $this->info("  [CREATED] Provider split for Order #{$order->id}");
            return 'created';
        }
    }

    /**
     * Extract domains from reorder_info
     * Handles both text format (newline/comma separated) and other formats
     */
    private function extractDomains(ReorderInfo $reorderInfo): array
    {
        $domainsRaw = $reorderInfo->domains ?? '';

        if (empty($domainsRaw)) {
            return [];
        }

        // Try to parse as JSON first (in case it's stored as JSON)
        if (is_string($domainsRaw) && str_starts_with(trim($domainsRaw), '[')) {
            $decoded = json_decode($domainsRaw, true);
            if (is_array($decoded)) {
                return array_filter(array_map('trim', $decoded));
            }
        }

        // Parse as text (newline or comma separated)
        $domains = preg_split('/[\r\n,]+/', $domainsRaw);
        $domains = array_filter(array_map('trim', $domains));

        return array_values($domains);
    }

    /**
     * Format mailboxes from order_emails to the new JSON structure
     * 
     * New format:
     * {
     *   "domain.com": {
     *     "prefix_variant_1": {
     *       "id": 1,
     *       "name": "John Doe",
     *       "mailbox": "john@domain.com",
     *       "password": "xxx",
     *       "status": "active",
     *       "mailbox_id": 12345
     *     }
     *   }
     * }
     */
    private function formatMailboxes($orderEmails, array $domains, ReorderInfo $reorderInfo): array
    {
        $mailboxes = [];
        $prefixVariants = $reorderInfo->prefix_variants ?? [];

        // Group emails by domain
        $emailsByDomain = [];
        foreach ($orderEmails as $email) {
            $emailAddress = $email->email;
            $parts = explode('@', $emailAddress);
            if (count($parts) !== 2)
                continue;

            $domain = strtolower($parts[1]);
            $prefix = $parts[0];

            if (!isset($emailsByDomain[$domain])) {
                $emailsByDomain[$domain] = [];
            }

            $emailsByDomain[$domain][] = [
                'prefix' => $prefix,
                'email' => $emailAddress,
                'name' => trim(($email->name ?? '') . ' ' . ($email->last_name ?? '')),
                'password' => $email->password,
                'mailbox_id' => $email->mailin_mailbox_id,
                'domain_id' => $email->mailin_domain_id,
            ];
        }

        // Format for each domain
        foreach ($domains as $domain) {
            $domainLower = strtolower($domain);
            $domainEmails = $emailsByDomain[$domainLower] ?? [];

            if (empty($domainEmails)) {
                continue;
            }

            $mailboxes[$domain] = [];
            $index = 0;

            foreach ($domainEmails as $emailData) {
                $index++;

                // Try to find matching prefix variant key
                $prefixKey = $this->findPrefixKey($emailData['prefix'], $prefixVariants, $index);

                $mailboxes[$domain][$prefixKey] = [
                    'id' => $index,
                    'name' => $emailData['name'] ?: $emailData['prefix'],
                    'mailbox' => $emailData['email'],
                    'password' => $emailData['password'],
                    'status' => 'active',
                    'mailbox_id' => $emailData['mailbox_id'],
                    'domain_id' => $emailData['domain_id'],
                ];
            }
        }

        return $mailboxes;
    }

    /**
     * Find prefix key from prefix variants or generate one
     */
    private function findPrefixKey(string $prefix, array $prefixVariants, int $fallbackIndex): string
    {
        // Check if prefix matches any variant
        foreach ($prefixVariants as $key => $variant) {
            if (strtolower($variant) === strtolower($prefix)) {
                // Return proper key format
                if (is_numeric($key)) {
                    return 'prefix_variant_' . ($key + 1);
                }
                return $key;
            }
        }

        // Fallback to index-based key
        return 'prefix_variant_' . $fallbackIndex;
    }
}
