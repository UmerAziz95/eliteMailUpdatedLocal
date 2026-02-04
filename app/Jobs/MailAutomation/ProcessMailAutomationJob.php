<?php

namespace App\Jobs\MailAutomation;

use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Models\SmtpProviderSplit;
use App\Services\DomainSplitService;
use App\Services\MailAutomation\DomainActivationService;
use App\Services\MailAutomation\MailboxCreationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job to handle mail automation using the new two-step flow:
 * 1. Split domains across providers and save to order_provider_splits
 * 2. Activate domains (transfer if needed)
 * 3. If all domains active → create mailboxes and complete order
 */
class ProcessMailAutomationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $orderId;
    public array $domains;
    public array $prefixVariants;
    public int $userId;
    public string $providerType;

    public $tries = 3;
    public $backoff = 30;

    public function __construct(
        int $orderId,
        array $domains,
        array $prefixVariants,
        int $userId,
        string $providerType
    ) {
        $this->orderId = $orderId;
        $this->domains = $domains;
        $this->prefixVariants = $prefixVariants;
        $this->userId = $userId;
        $this->providerType = $providerType;
    }

    public function handle(): void
    {
        try {
            Log::channel('mailin-ai')->info('Starting mail automation job', [
                'order_id' => $this->orderId,
                'domain_count' => count($this->domains),
                'prefix_count' => count($this->prefixVariants),
            ]);

            // Load order
            $order = Order::with(['reorderInfo', 'platformCredentials'])->find($this->orderId);
            if (!$order) {
                Log::channel('mailin-ai')->error('Order not found', ['order_id' => $this->orderId]);
                return;
            }

            // Step 1: Split domains across providers and save to order_provider_splits
            $this->splitAndSaveDomains($order);

            // Step 2: Activate domains (transfer if needed)
            $activationService = new DomainActivationService();
            $result = $activationService->activateDomainsForOrder($order);

            if ($result['rejected']) {
                Log::channel('mailin-ai')->warning('Order rejected during domain activation', [
                    'order_id' => $this->orderId,
                    'reason' => $result['reason'],
                ]);
                return;
            }

            // Step 3: Check if all domains are active
            $allActive = OrderProviderSplit::areAllDomainsActiveForOrder($this->orderId);

            if ($allActive) {
                // All domains active → create mailboxes
                $this->createMailboxes($order);
            } else {
                Log::channel('mailin-ai')->info('Not all domains active, waiting for activation', [
                    'order_id' => $this->orderId,
                ]);
                // Domains are being transferred, scheduler will check and create mailboxes later
            }

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mail automation job failed', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Split domains across providers and save to order_provider_splits
     */
    private function splitAndSaveDomains(Order $order): void
    {
        $domainSplitService = new DomainSplitService();
        $domainSplit = $domainSplitService->splitDomains($this->domains);

        if (empty($domainSplit)) {
            throw new \Exception('Failed to split domains across providers');
        }

        // Get list of active provider slugs from the new split
        $activeProviderSlugs = array_keys($domainSplit);

        // Delete any existing splits that are NOT in the new active list
        // This handles the case where a provider was disabled and should be removed
        OrderProviderSplit::where('order_id', $order->id)
            ->whereNotIn('provider_slug', $activeProviderSlugs)
            ->delete();

        foreach ($domainSplit as $providerSlug => $providerDomains) {
            if (empty($providerDomains)) {
                continue;
            }

            $provider = SmtpProviderSplit::getBySlug($providerSlug);
            $providerName = $provider ? $provider->name : $providerSlug;

            OrderProviderSplit::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'provider_slug' => $providerSlug,
                ],
                [
                    'provider_name' => $providerName,
                    'split_percentage' => count($providerDomains) / count($this->domains) * 100,
                    'domain_count' => count($providerDomains),
                    'domains' => $providerDomains,
                    'mailboxes' => null,
                    'domain_statuses' => null,
                    'all_domains_active' => false,
                    'priority' => $provider ? $provider->priority : 0,
                ]
            );
        }

        Log::channel('mailin-ai')->info('Domain split saved', [
            'order_id' => $order->id,
            'splits' => array_map(fn($d) => count($d), $domainSplit),
        ]);
    }

    /**
     * Sync order_provider_splits for an order from current reorder_info (domains).
     * Use when an order is "fixed" after rejection (admin changes status reject → in-progress).
     *
     * Splits are built from current provider configuration: only active (enabled) providers
     * are included. If a provider was disabled, its split is deleted and domains are
     * reassigned to the remaining active providers according to their split percentages.
     *
     * @param Order $order Order (with reorderInfo loaded)
     * @return bool True if sync was performed, false if skipped
     */
    public static function syncSplitsForOrder(Order $order): bool
    {
        $order->loadMissing('reorderInfo', 'plan');

        $reorderInfo = $order->reorderInfo?->first();
        if (!$reorderInfo) {
            Log::channel('mailin-ai')->debug('OrderProviderSplitSync: no reorderInfo', ['order_id' => $order->id]);
            return false;
        }

        $plan = $order->plan;
        if (!$plan || $plan->provider_type !== 'Private SMTP') {
            Log::channel('mailin-ai')->debug('OrderProviderSplitSync: not Private SMTP', ['order_id' => $order->id]);
            return false;
        }

        $domains = self::parseDomainsFromReorderInfo($reorderInfo);
        if (empty($domains)) {
            Log::channel('mailin-ai')->warning('OrderProviderSplitSync: no domains in reorderInfo', ['order_id' => $order->id]);
            return false;
        }

        $domainSplitService = new DomainSplitService();
        $domainSplit = $domainSplitService->splitDomains($domains);

        if (empty($domainSplit)) {
            Log::channel('mailin-ai')->warning('OrderProviderSplitSync: split returned empty', ['order_id' => $order->id]);
            return false;
        }

        $activeProviderSlugs = array_keys($domainSplit);

        $toDelete = OrderProviderSplit::where('order_id', $order->id)
            ->whereNotIn('provider_slug', $activeProviderSlugs)
            ->get();

        if ($toDelete->isNotEmpty()) {
            Log::channel('mailin-ai')->info('OrderProviderSplitSync: removing splits for disabled/removed providers', [
                'order_id' => $order->id,
                'removed_providers' => $toDelete->pluck('provider_slug')->toArray(),
                'new_active_providers' => $activeProviderSlugs,
            ]);
            foreach ($toDelete as $split) {
                $split->delete();
            }
        }

        $totalDomains = count($domains);
        foreach ($domainSplit as $providerSlug => $providerDomains) {
            if (empty($providerDomains)) {
                continue;
            }

            $provider = SmtpProviderSplit::getBySlug($providerSlug);
            $providerName = $provider ? $provider->name : $providerSlug;

            OrderProviderSplit::updateOrCreate(
                [
                    'order_id' => $order->id,
                    'provider_slug' => $providerSlug,
                ],
                [
                    'provider_name' => $providerName,
                    'split_percentage' => count($providerDomains) / $totalDomains * 100,
                    'domain_count' => count($providerDomains),
                    'domains' => $providerDomains,
                    'mailboxes' => null,
                    'domain_statuses' => null,
                    'all_domains_active' => false,
                    'priority' => $provider ? (int) $provider->priority : 0,
                ]
            );
        }

        Log::channel('mailin-ai')->info('OrderProviderSplitSync: splits synced for order', [
            'order_id' => $order->id,
            'domain_count' => $totalDomains,
            'splits' => array_map(fn($d) => count($d), $domainSplit),
        ]);

        return true;
    }

    /**
     * Parse domains string from reorder_info into array (newline/comma separated).
     */
    private static function parseDomainsFromReorderInfo($reorderInfo): array
    {
        $domainsRaw = $reorderInfo->domains ?? '';
        if (is_array($domainsRaw)) {
            return array_values(array_filter(array_map('trim', $domainsRaw)));
        }
        $domains = preg_split('/[\r\n,]+/', (string) $domainsRaw);
        return array_values(array_filter(array_map('trim', $domains)));
    }

    /**
     * Create mailboxes for order
     */
    private function createMailboxes(Order $order): void
    {
        // Check if order has PremiumInboxes splits
        $premiumInboxesSplits = OrderProviderSplit::where('order_id', $order->id)
            ->where('provider_slug', 'premiuminboxes')
            ->get();

        // For PremiumInboxes, mailboxes are created via order creation
        // We just need to wait for webhook or poll status
        // Check if all PremiumInboxes orders are active
        $allPremiumInboxesActive = true;
        foreach ($premiumInboxesSplits as $split) {
            if ($split->order_status !== 'active') {
                $allPremiumInboxesActive = false;
                break;
            }
        }

        // Only proceed if PremiumInboxes orders are active (or no PremiumInboxes splits)
        if (!$allPremiumInboxesActive && $premiumInboxesSplits->isNotEmpty()) {
            Log::channel('mailin-ai')->info('Waiting for PremiumInboxes order activation', [
                'order_id' => $order->id,
            ]);
            return;
        }

        $reorderInfo = $order->reorderInfo->first();
        if (!$reorderInfo) {
            Log::channel('mailin-ai')->error('No reorder info found', ['order_id' => $order->id]);
            return;
        }

        $prefixVariants = $reorderInfo->prefix_variants ?? [];
        $prefixVariantsDetails = $reorderInfo->prefix_variants_details ?? [];

        $mailboxService = new MailboxCreationService();
        $result = $mailboxService->createMailboxesForOrder($order, $prefixVariants, $prefixVariantsDetails);

        if ($result['success']) {
            Log::channel('mailin-ai')->info('Mailboxes created successfully', [
                'order_id' => $order->id,
                'total_created' => $result['total_created'],
            ]);
        } else {
            // Log detailed pending mailboxes if any
            if ($result['total_pending'] > 0) {
                $validation = $mailboxService->validateOrderMailboxCompletion($order, $prefixVariants);
                Log::channel('mailin-ai')->warning('Mailbox creation incomplete - pending mailboxes', [
                    'order_id' => $order->id,
                    'error' => $result['error'],
                    'total_created' => $result['total_created'],
                    'total_pending' => $result['total_pending'],
                    'pending_mailboxes' => array_map(fn($m) => $m['email'], $validation['pending_mailboxes'] ?? []),
                ]);
            } else {
                Log::channel('mailin-ai')->error('Mailbox creation failed', [
                    'order_id' => $order->id,
                    'error' => $result['error'],
                ]);
            }
        }
    }
}
