<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderEmail;
use App\Jobs\MailinAi\CreateMailboxesOnOrderJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryOrderAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:retry-automation {order_id : The order ID to retry automation for} {--reset-status : Reset order status to in-progress before retrying} {--force : Force retry even if order is not in expected state}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-run order automation logic for an existing order (extracts data from database and re-dispatches automation job)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $orderId = $this->argument('order_id');
        $resetStatus = $this->option('reset-status');
        $force = $this->option('force');

        $this->info("Starting order automation retry for Order #{$orderId}...");
        $this->newLine();

        Log::info('RetryOrderAutomation: Starting order automation retry', [
            'action' => 'retry_order_automation',
            'order_id' => $orderId,
            'reset_status' => $resetStatus,
            'force' => $force,
        ]);

        // Load order with required relationships
        $order = Order::with(['plan', 'reorderInfo', 'platformCredentials'])->find($orderId);

        if (!$order) {
            $this->error("Order #{$orderId} not found.");
            Log::error('RetryOrderAutomation: Order not found', [
                'action' => 'retry_order_automation',
                'order_id' => $orderId,
            ]);
            return 1;
        }

        $this->info("✓ Order found: Order #{$order->id}");
        $this->line("  Status: {$order->status_manage_by_admin}");
        $this->line("  Provider Type: " . ($order->plan->provider_type ?? 'N/A'));

        // Validate provider type
        $providerType = $order->plan->provider_type ?? null;
        if ($providerType !== 'Private SMTP') {
            if (!$force) {
                $this->error("Order #{$orderId} is not a Private SMTP order (Provider Type: {$providerType}).");
                $this->warn("Use --force flag to proceed anyway.");
                return 1;
            } else {
                $this->warn("⚠ Warning: Order is not Private SMTP (Provider Type: {$providerType}). Proceeding with --force flag.");
            }
        }

        // Check if Mailin.ai automation is enabled
        if (!config('mailin_ai.automation_enabled', false)) {
            $this->error("Mailin.ai automation is not enabled. Please enable it in configuration.");
            return 1;
        }

        // Get reorderInfo
        $reorderInfo = $order->reorderInfo->first();
        if (!$reorderInfo) {
            $this->error("Order #{$orderId} does not have reorderInfo. Cannot proceed.");
            Log::error('RetryOrderAutomation: Order missing reorderInfo', [
                'action' => 'retry_order_automation',
                'order_id' => $orderId,
            ]);
            return 1;
        }

        $this->info("✓ ReorderInfo found");

        // Check if order already has mailboxes created
        $existingMailboxesCount = OrderEmail::where('order_id', $orderId)->count();
        if ($existingMailboxesCount > 0) {
            $this->newLine();
            $this->warn("⚠ Warning: Order #{$orderId} already has {$existingMailboxesCount} mailbox(es) created.");
            $this->warn("  Re-running automation may create duplicate mailboxes.");
            if (!$this->confirm('Do you want to proceed anyway?', false)) {
                $this->info("Operation cancelled.");
                return 0;
            }
        }

        // Extract domains from reorderInfo.domains (comma-separated string)
        $domainsString = $reorderInfo->domains ?? '';
        if (empty($domainsString)) {
            $this->error("No domains found in reorderInfo for Order #{$orderId}.");
            return 1;
        }

        // Parse domains (handle comma and newline separated)
        $domains = array_filter(
            preg_split('/[\r\n,]+/', $domainsString),
            function($domain) {
                return !empty(trim($domain));
            }
        );
        $domains = array_map('trim', $domains);

        if (empty($domains)) {
            $this->error("No valid domains found after parsing.");
            return 1;
        }

        $this->info("✓ Found " . count($domains) . " domain(s)");
        $this->line("  Domains: " . implode(', ', array_slice($domains, 0, 5)) . (count($domains) > 5 ? '...' : ''));

        // Extract prefix variants from reorderInfo.prefix_variants
        // Match the logic from OrderController::store() method
        $prefixVariants = [];
        $inboxesPerDomain = (int) ($reorderInfo->inboxes_per_domain ?? 1);
        
        if ($reorderInfo->prefix_variants) {
            $prefixVariantsData = null;
            
            if (is_string($reorderInfo->prefix_variants)) {
                $decoded = json_decode($reorderInfo->prefix_variants, true);
                if (is_array($decoded)) {
                    $prefixVariantsData = $decoded;
                }
            } elseif (is_array($reorderInfo->prefix_variants)) {
                $prefixVariantsData = $reorderInfo->prefix_variants;
            }
            
            // Extract prefix variants in the same way as OrderController::store()
            // Loop through inboxes_per_domain and extract prefix_variant_1, prefix_variant_2, etc.
            if ($prefixVariantsData) {
                for ($i = 1; $i <= $inboxesPerDomain; $i++) {
                    $prefixKey = "prefix_variant_{$i}";
                    if (!empty($prefixVariantsData[$prefixKey])) {
                        $prefixVariants[] = trim($prefixVariantsData[$prefixKey]);
                    }
                }
            }
        }

        if (empty($prefixVariants)) {
            $this->error("No prefix variants found in reorderInfo for Order #{$orderId}.");
            $this->line("  Expected format: {\"prefix_variant_1\": \"value1\", \"prefix_variant_2\": \"value2\", ...}");
            return 1;
        }

        $this->info("✓ Found " . count($prefixVariants) . " prefix variant(s)");
        $this->line("  Prefixes: " . implode(', ', $prefixVariants));

        // Get user ID
        $userId = $order->user_id;
        if (!$userId) {
            $this->error("Order #{$orderId} does not have a user_id.");
            return 1;
        }

        $this->info("✓ User ID: {$userId}");

        // Get provider type (use from plan, fallback to order)
        $finalProviderType = $providerType ?? $order->provider_type ?? 'Private SMTP';
        $this->info("✓ Provider Type: {$finalProviderType}");

        // Check hosting platform
        $hostingPlatform = $reorderInfo->hosting_platform ?? null;
        if (!in_array($hostingPlatform, ['spaceship', 'namecheap'])) {
            if (!$force) {
                $this->warn("⚠ Warning: Hosting platform '{$hostingPlatform}' is not spaceship or namecheap.");
                $this->warn("  Mailin.ai automation typically requires spaceship or namecheap.");
                if (!$this->confirm('Do you want to proceed anyway?', false)) {
                    $this->info("Operation cancelled.");
                    return 0;
                }
            }
        }

        $this->newLine();
        $this->info("=== Summary ===");
        $this->table(
            ['Field', 'Value'],
            [
                ['Order ID', $order->id],
                ['Current Status', $order->status_manage_by_admin ?? 'N/A'],
                ['Provider Type', $finalProviderType],
                ['Hosting Platform', $hostingPlatform ?? 'N/A'],
                ['Domains Count', count($domains)],
                ['Prefix Variants Count', count($prefixVariants)],
                ['Total Emails', count($domains) * count($prefixVariants)],
                ['User ID', $userId],
            ]
        );

        // Reset status if requested
        if ($resetStatus) {
            $this->newLine();
            $this->info("Resetting order status to 'in-progress'...");
            $order->update([
                'status_manage_by_admin' => 'in-progress'
            ]);
            $this->info("✓ Order status reset to 'in-progress'");
            
            Log::info('RetryOrderAutomation: Order status reset', [
                'action' => 'retry_order_automation',
                'order_id' => $orderId,
                'old_status' => $order->getOriginal('status_manage_by_admin'),
                'new_status' => 'in-progress',
            ]);
        }

        // Confirm before dispatching
        $this->newLine();
        if (!$this->confirm('Do you want to dispatch the mailbox creation job?', true)) {
            $this->info("Operation cancelled.");
            return 0;
        }

        // Dispatch the mailbox creation job
        try {
            $this->info("Dispatching CreateMailboxesOnOrderJob...");
            
            CreateMailboxesOnOrderJob::dispatch(
                $order->id,
                $domains,
                $prefixVariants,
                $userId,
                $finalProviderType
            );

            $this->newLine();
            $this->info("✓ Mailbox creation job dispatched successfully!");
            $this->info("  The job will process in the background queue.");
            $this->newLine();
            $this->line("  Order ID: {$order->id}");
            $this->line("  Domains: " . count($domains));
            $this->line("  Prefix Variants: " . count($prefixVariants));
            $this->line("  Total Emails: " . (count($domains) * count($prefixVariants)));

            Log::channel('mailin-ai')->info('RetryOrderAutomation: Mailbox creation job dispatched', [
                'action' => 'retry_order_automation',
                'order_id' => $orderId,
                'domain_count' => count($domains),
                'prefix_count' => count($prefixVariants),
                'total_emails' => count($domains) * count($prefixVariants),
                'reset_status' => $resetStatus,
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("✗ Failed to dispatch mailbox creation job: " . $e->getMessage());
            
            Log::channel('mailin-ai')->error('RetryOrderAutomation: Failed to dispatch mailbox creation job', [
                'action' => 'retry_order_automation',
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
