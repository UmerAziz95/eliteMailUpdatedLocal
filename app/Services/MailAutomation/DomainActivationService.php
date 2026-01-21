<?php

namespace App\Services\MailAutomation;

use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Models\SmtpProviderSplit;
use App\Contracts\Providers\SmtpProviderInterface;
use App\Services\Providers\CreatesProviders;
use App\Services\SpaceshipService;
use App\Services\NamecheapService;
use Illuminate\Support\Facades\Log;

/**
 * Service for domain activation and transfer
 */
class DomainActivationService
{
    use CreatesProviders;

    /**
     * Activate domains for all splits in an order
     * 
     * @param Order $order
     * @return array ['rejected' => bool, 'reason' => string|null, 'results' => array]
     */
    public function activateDomainsForOrder(Order $order): array
    {
        $splits = OrderProviderSplit::where('order_id', $order->id)->get();
        $allResults = [];

        foreach ($splits as $split) {
            $result = $this->activateDomainsForSplit($order, $split);

            if ($result['rejected']) {
                return $result;
            }

            $allResults[$split->provider_slug] = $result;
        }

        return [
            'rejected' => false,
            'reason' => null,
            'results' => $allResults,
        ];
    }

    /**
     * Activate domains for a single provider split
     * 
     * @param Order $order
     * @param OrderProviderSplit $split
     * @return array ['rejected' => bool, 'reason' => string|null, 'active' => array, 'transferred' => array, 'failed' => array]
     */
    public function activateDomainsForSplit(Order $order, OrderProviderSplit $split): array
    {
        $results = [
            'rejected' => false,
            'reason' => null,
            'active' => [],
            'transferred' => [],
            'failed' => [],
        ];

        // Get provider credentials
        $providerConfig = SmtpProviderSplit::getBySlug($split->provider_slug);
        if (!$providerConfig) {
            Log::channel('mailin-ai')->error('Provider not found', [
                'provider_slug' => $split->provider_slug,
                'order_id' => $order->id,
            ]);
            $results['failed'] = $split->domains ?? [];
            return $results;
        }

        $credentials = $providerConfig->getCredentials();
        $provider = $this->createProvider($split->provider_slug, $credentials);

        // Authenticate
        if (!$provider->authenticate()) {
            Log::channel('mailin-ai')->error('Provider authentication failed', [
                'provider' => $split->provider_slug,
                'order_id' => $order->id,
            ]);
            $results['failed'] = $split->domains ?? [];
            return $results;
        }

        // Get prefix variants from order for mailbox comparison
        $reorderInfo = $order->reorderInfo->first();
        $prefixVariantsRaw = $reorderInfo ? ($reorderInfo->prefix_variants ?? []) : [];
        // Extract just the prefix values (array may be associative like ['prefix_variant_1' => 'john'])
        $prefixVariants = array_filter(array_values($prefixVariantsRaw));

        // Process each domain
        foreach ($split->domains ?? [] as $domain) {
            try {
                // CHECK 1: Do SAME mailboxes already exist? (check by prefix variants) → REJECT
                $existingMailboxes = $provider->getMailboxesByDomain($domain);
                if (!empty($existingMailboxes['mailboxes']) && !empty($prefixVariants)) {
                    // Extract existing email prefixes
                    $existingPrefixes = [];
                    foreach ($existingMailboxes['mailboxes'] as $mb) {
                        $email = $mb['email'] ?? $mb['username'] ?? '';
                        if (strpos($email, '@') !== false) {
                            $existingPrefixes[] = strtolower(explode('@', $email)[0]);
                        }
                    }

                    // Check if any of our prefix variants already exist
                    $conflictingMailboxes = [];
                    foreach ($prefixVariants as $prefix) {
                        if (in_array(strtolower(trim($prefix)), $existingPrefixes)) {
                            $conflictingMailboxes[] = $prefix . '@' . $domain;
                        }
                    }

                    Log::channel('mailin-ai')->debug('Mailbox conflict check', [
                        'domain' => $domain,
                        'order_prefixes' => $prefixVariants,
                        'existing_prefixes' => $existingPrefixes,
                        'conflicts' => $conflictingMailboxes,
                    ]);

                    if (!empty($conflictingMailboxes)) {
                        $this->rejectOrder($order, "Same mailboxes already exist: " . implode(', ', $conflictingMailboxes));
                        return [
                            'rejected' => true,
                            'reason' => "Same mailboxes already exist: " . implode(', ', $conflictingMailboxes),
                            'active' => $results['active'],
                            'transferred' => $results['transferred'],
                            'failed' => $results['failed'],
                        ];
                    }
                }

                // CHECK 2: Is domain active?
                $status = $provider->checkDomainStatus($domain);

                if ($status['success'] && $status['status'] === 'active') {
                    $results['active'][] = $domain;
                    $domainId = $status['data']['id'] ?? $status['domain_id'] ?? null;
                    $split->setDomainStatus($domain, 'active', $domainId);

                    Log::channel('mailin-ai')->info('Domain is active', [
                        'domain' => $domain,
                        'domain_id' => $domainId,
                        'provider' => $split->provider_slug,
                        'order_id' => $order->id,
                    ]);
                } else {
                    // Domain not active, try to transfer
                    $transferResult = $this->transferDomain($order, $provider, $domain);

                    if ($transferResult['success']) {
                        $results['transferred'][] = $domain;
                        $split->setDomainStatus($domain, 'pending');

                        Log::channel('mailin-ai')->info('Domain transferred', [
                            'domain' => $domain,
                            'provider' => $split->provider_slug,
                            'order_id' => $order->id,
                        ]);
                    } else {
                        // Transfer failed → REJECT
                        $this->rejectOrder($order, "Domain transfer failed for: {$domain}. {$transferResult['message']}");
                        return [
                            'rejected' => true,
                            'reason' => "Domain transfer failed for: {$domain}. {$transferResult['message']}",
                            'active' => $results['active'],
                            'transferred' => $results['transferred'],
                            'failed' => array_merge($results['failed'], [$domain]),
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::channel('mailin-ai')->error('Domain activation error', [
                    'domain' => $domain,
                    'error' => $e->getMessage(),
                    'order_id' => $order->id,
                ]);
                $results['failed'][] = $domain;
                $split->setDomainStatus($domain, 'failed');
            }
        }

        // Update all_domains_active flag
        $split->checkAndUpdateAllDomainsActive();

        return $results;
    }

    /**
     * Transfer domain to provider
     */
    private function transferDomain(Order $order, SmtpProviderInterface $provider, string $domain): array
    {
        $transferResult = $provider->transferDomain($domain);

        if (!$transferResult['success']) {
            return $transferResult;
        }

        // Update nameservers on hosting platform
        $nameServers = $transferResult['name_servers'] ?? [];
        if (!empty($nameServers)) {
            $this->updateNameservers($order, $domain, $nameServers);
        }

        return $transferResult;
    }

    /**
     * Update nameservers via hosting platform
     */
    private function updateNameservers(Order $order, string $domain, array $nameServers): void
    {
        $hostingPlatform = $order->reorderInfo->first()?->hosting_platform;

        if ($hostingPlatform === 'spaceship') {
            $this->updateSpaceshipNameservers($order, $domain, $nameServers);
        } elseif ($hostingPlatform === 'namecheap') {
            $this->updateNamecheapNameservers($order, $domain, $nameServers);
        }
    }

    private function updateSpaceshipNameservers(Order $order, string $domain, array $ns): void
    {
        try {
            $credential = $order->getPlatformCredential('spaceship');
            if (!$credential)
                return;

            $service = new SpaceshipService();
            $service->updateNameservers(
                $domain,
                $ns,
                $credential->getCredential('api_key'),
                $credential->getCredential('api_secret_key')
            );
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Spaceship nameserver update failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function updateNamecheapNameservers(Order $order, string $domain, array $ns): void
    {
        try {
            $credential = $order->getPlatformCredential('namecheap');
            if (!$credential)
                return;

            $service = new NamecheapService();
            $service->updateNameservers(
                $domain,
                $ns,
                $credential->getCredential('api_user'),
                $credential->getCredential('api_key')
            );
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Namecheap nameserver update failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Reject order with reason
     */
    private function rejectOrder(Order $order, string $reason): void
    {
        $order->update([
            'status_manage_by_admin' => 'reject',
            'reason' => $reason,
        ]);

        Log::channel('mailin-ai')->warning('Order rejected', [
            'order_id' => $order->id,
            'reason' => $reason,
        ]);
    }
}
