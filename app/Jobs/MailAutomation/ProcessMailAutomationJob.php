<?php

namespace App\Jobs\MailAutomation;

use App\Models\Order;
use App\Models\OrderProviderSplit;
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

        foreach ($domainSplit as $providerSlug => $providerDomains) {
            if (empty($providerDomains)) {
                continue;
            }

            $provider = \App\Models\SmtpProviderSplit::getBySlug($providerSlug);
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
