<?php

namespace App\Services\Providers;

use App\Contracts\Providers\SmtpProviderInterface;
use App\Services\PremiumInboxesService;
use Illuminate\Support\Facades\Log;

/**
 * PremiumInboxes Provider Service
 * Adapts PremiumInboxes order-based flow to match SmtpProviderInterface
 */
class PremiuminboxesProviderService implements SmtpProviderInterface
{
    private PremiumInboxesService $service;
    private array $credentials;
    private ?string $currentOrderId = null; // Track order for this provider instance

    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
        $this->service = new PremiumInboxesService($credentials);
    }

    public function authenticate(): ?string
    {
        // PremiumInboxes doesn't need authentication
        // Return API key for consistency with interface
        $apiKey = $this->credentials['api_key'] ?? null;

        if ($apiKey) {
            Log::channel('mailin-ai')->debug('PremiumInboxes authentication (API key)', [
                'action' => 'authenticate',
                'api_key_preview' => substr($apiKey, 0, 10) . '...',
            ]);
        }

        return $apiKey;
    }

    public function transferDomain(string $domain): array
    {
        // For PremiumInboxes, we can't transfer a single domain
        // This will be called during order creation
        // Return standard format for compatibility
        Log::channel('mailin-ai')->warning('PremiumInboxes: transferDomain() called directly', [
            'domain' => $domain,
            'message' => 'PremiumInboxes requires order creation, not single domain transfer',
        ]);

        return [
            'success' => false,
            'message' => 'PremiumInboxes requires order creation, not single domain transfer',
            'name_servers' => [],
        ];
    }

    /**
     * Create order with domains and mailboxes
     * This replaces both transferDomain() and createMailboxes() for PremiumInboxes
     * 
     * @param array $domains Array of domain names
     * @param array $prefixVariants Array of prefix variants (e.g., ['john', 'jsmith'])
     * @param array $persona Persona data: ['first_name' => string, 'last_name' => string, 'variations' => array]
     * @param string $emailPassword Email password for all mailboxes
     * @param string $clientOrderId Our order reference (e.g., "order-123-premiuminboxes")
     * @param array|null $sequencer Sequencer config: ['platform' => string, 'email' => string, 'password' => string]
     * @param array $additionalData Additional optional parameters
     * @return array ['success' => bool, 'order_id' => string|null, 'status' => string, 'name_servers' => array, 'domains' => array, 'message' => string|null]
     */
    public function createOrderWithDomains(
        array $domains,
        array $prefixVariants,
        array $persona,
        string $emailPassword,
        string $clientOrderId,
        ?array $sequencer = null,
        array $additionalData = []
    ): array {
        $orderData = array_merge([
            'client_order_id' => $clientOrderId,
            'domains' => $domains,
            'inboxes_per_domain' => count($prefixVariants),
            'persona' => $persona,
            'email_password' => $emailPassword,
        ], $additionalData);

        if ($sequencer) {
            $orderData['sequencer'] = $sequencer;
        }

        // Log complete request data as requested
        Log::channel('mailin-ai')->info('PremiumInboxes Request Payload', [
            'action' => 'createOrderWithDomains',
            'order_data' => $orderData,
        ]);

        $result = $this->service->createOrder($orderData);

        if ($result['success']) {
            $this->currentOrderId = $result['data']['order_id'] ?? null;

            // Extract nameservers from response
            $nameServers = [];
            foreach ($result['data']['domains'] ?? [] as $domainData) {
                $nameServers = array_merge($nameServers, $domainData['nameservers'] ?? []);
            }

            Log::channel('mailin-ai')->info('PremiumInboxes order created successfully', [
                'action' => 'createOrderWithDomains',
                'order_id' => $this->currentOrderId,
                'client_order_id' => $clientOrderId,
                'domain_count' => count($domains),
                'nameserver_count' => count($nameServers),
            ]);

            return [
                'success' => true,
                'order_id' => $this->currentOrderId,
                'status' => $result['data']['status'] ?? 'ns_validation_pending',
                'name_servers' => array_unique($nameServers),
                'domains' => $result['data']['domains'] ?? [],
            ];
        }

        return [
            'success' => false,
            'order_id' => null,
            'status' => null,
            'name_servers' => [],
            'domains' => [],
            'message' => $result['error'] ?? 'Failed to create order',
        ];
    }

    public function checkDomainStatus(string $domain): array
    {
        // PremiumInboxes doesn't have direct domain status check
        // We need order_id to check status
        // This will be called with order context

        if (!$this->currentOrderId) {
            Log::channel('mailin-ai')->warning('PremiumInboxes: checkDomainStatus() called without order context', [
                'domain' => $domain,
            ]);

            return [
                'success' => false,
                'status' => 'unknown',
                'message' => 'Order ID not available',
            ];
        }

        $order = $this->service->getOrder($this->currentOrderId);

        if (!$order['success']) {
            return [
                'success' => false,
                'status' => 'unknown',
                'message' => $order['error'] ?? 'Failed to get order',
            ];
        }

        // Find domain in order
        $orderData = $order['data'];
        $domainData = collect($orderData['domains'] ?? [])
            ->firstWhere('domain', $domain);

        if (!$domainData) {
            return [
                'success' => false,
                'status' => 'not_found',
                'message' => 'Domain not found in order',
            ];
        }

        // Map PremiumInboxes status to our status
        $orderStatus = $orderData['status'] ?? 'unknown';
        $nsStatus = $domainData['ns_status'] ?? 'pending';

        $status = 'pending';
        if ($orderStatus === 'active' && $nsStatus === 'validated') {
            $status = 'active';
        } elseif ($orderStatus === 'buildout_issue') {
            $status = 'failed';
        }

        return [
            'success' => true,
            'status' => $status,
            'data' => [
                'order_status' => $orderStatus,
                'ns_status' => $nsStatus,
                'domain' => $domain,
            ],
        ];
    }

    public function createMailboxes(array $mailboxes): array
    {
        // For PremiumInboxes, mailboxes are created as part of order creation
        // This method should not be called separately
        // If called, return success with order_id reference

        if ($this->currentOrderId) {
            Log::channel('mailin-ai')->info('PremiumInboxes: createMailboxes() called, mailboxes already created in order', [
                'order_id' => $this->currentOrderId,
                'mailbox_count' => count($mailboxes),
            ]);

            return [
                'success' => true,
                'uuid' => $this->currentOrderId,
                'message' => 'Mailboxes created as part of order',
                'mailboxes' => [],
            ];
        }

        Log::channel('mailin-ai')->warning('PremiumInboxes: createMailboxes() called without order', [
            'mailbox_count' => count($mailboxes),
        ]);

        return [
            'success' => false,
            'message' => 'Order must be created first',
        ];
    }

    public function getMailboxesByDomain(string $domain): array
    {
        if (!$this->currentOrderId) {
            Log::channel('mailin-ai')->warning('PremiumInboxes: getMailboxesByDomain() called without order context', [
                'domain' => $domain,
            ]);

            return [
                'success' => false,
                'mailboxes' => [],
                'message' => 'Order ID not available',
            ];
        }

        $order = $this->service->getOrder($this->currentOrderId);

        if (!$order['success']) {
            return [
                'success' => false,
                'mailboxes' => [],
                'message' => $order['error'] ?? 'Failed to get order',
            ];
        }

        // Filter email_accounts by domain
        $emailAccounts = collect($order['data']['email_accounts'] ?? [])
            ->filter(function ($account) use ($domain) {
                return ($account['domain'] ?? '') === $domain;
            })
            ->map(function ($account) {
                return [
                    'id' => $account['id'] ?? null,
                    'email' => $account['email'] ?? '',
                    'domain' => $account['domain'] ?? '',
                    'status' => $account['status'] ?? 'unknown',
                    'password' => $account['password'] ?? null,
                ];
            })
            ->values()
            ->toArray();

        return [
            'success' => true,
            'mailboxes' => $emailAccounts,
        ];
    }

    public function deleteMailbox(int $mailboxId): array
    {
        // PremiumInboxes uses email_account_id (string UUID), not int
        // This method signature needs to be flexible
        $result = $this->service->cancelEmailAccount((string) $mailboxId);

        return [
            'success' => $result['success'] ?? false,
            'message' => $result['error'] ?? 'Mailbox deleted',
        ];
    }

    public function getProviderName(): string
    {
        return 'PremiumInboxes';
    }

    public function getProviderSlug(): string
    {
        return 'premiuminboxes';
    }

    public function isAvailable(): bool
    {
        // Check for api_key (from api_secret field) or password (for backward compatibility)
        $apiKey = $this->credentials['api_secret'] ?? '';
        $baseUrl = $this->credentials['base_url'] ?? config('premiuminboxes.api_url', 'https://api.piwhitelabel.dev/api/v1');

        return !empty($apiKey) && !empty($baseUrl);
    }

    /**
     * Set current order ID (for context)
     * 
     * @param string $orderId PremiumInboxes order ID
     */
    public function setOrderId(string $orderId): void
    {
        $this->currentOrderId = $orderId;
    }

    /**
     * Get current order ID
     * 
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->currentOrderId;
    }
}
