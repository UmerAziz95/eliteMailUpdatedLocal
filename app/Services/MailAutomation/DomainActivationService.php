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
    public function activateDomainsForOrder(Order $order, bool $bypassExistingMailboxCheck = false): array
    {
        $splits = OrderProviderSplit::where('order_id', $order->id)->get();
        $allResults = [];

        foreach ($splits as $split) {
            $result = $this->activateDomainsForSplit($order, $split, $bypassExistingMailboxCheck);

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
    public function activateDomainsForSplit(Order $order, OrderProviderSplit $split, bool $bypassExistingMailboxCheck = false): array
    {
        $providerConfig = SmtpProviderSplit::getBySlug($split->provider_slug);
        if (!$providerConfig) {
            Log::channel('mailin-ai')->error('Provider not found', [
                'provider_slug' => $split->provider_slug,
                'order_id' => $order->id,
            ]);
            return [
                'rejected' => false,
                'reason' => null,
                'active' => [],
                'transferred' => [],
                'failed' => $split->domains ?? [],
            ];
        }

        $credentials = $providerConfig->getCredentials();
        $provider = $this->createProvider($split->provider_slug, $credentials);

        return $provider->activateDomainsForSplit($order, $split, $bypassExistingMailboxCheck, $this);
    }

    /**
     * Update nameservers via hosting platform (used by providers during activation).
     * @throws \Exception
     */
    public function updateNameservers(Order $order, string $domain, array $nameServers): void
    {
        $hostingPlatform = $order->reorderInfo->first()?->hosting_platform;

        if ($hostingPlatform === 'spaceship') {
            $this->updateSpaceshipNameservers($order, $domain, $nameServers);
        } elseif ($hostingPlatform === 'namecheap') {
            $this->updateNamecheapNameservers($order, $domain, $nameServers);
        }
    }

    /**
     * @throws \Exception
     */
    private function updateSpaceshipNameservers(Order $order, string $domain, array $ns): void
    {
        try {
            $credential = $order->getPlatformCredential('spaceship');
            if (!$credential) {
                throw new \Exception('Spaceship credentials not found');
            }

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
            throw $e;
        }
    }

    /**
     * @throws \Exception
     */
    private function updateNamecheapNameservers(Order $order, string $domain, array $ns): void
    {
        try {
            $credential = $order->getPlatformCredential('namecheap');
            if (!$credential) {
                throw new \Exception('Namecheap credentials not found');
            }

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
            throw $e;
        }
    }

    /**
     * Reject order with reason (used by providers during activation).
     */
    public function rejectOrder(Order $order, string $reason): void
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
