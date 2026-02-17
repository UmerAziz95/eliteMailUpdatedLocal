<?php

namespace App\Services\Providers;

use App\Contracts\Providers\SmtpProviderInterface;
use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Services\ActivityLogService;
use App\Services\MailAutomation\DomainActivationService;
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
            'infra_type' => 'Google',
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
            'name_servers' => $domainData['nameservers'] ?? [],
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

        $mailboxes = [];
        // Assuming $this->service->getEmailAccountsByDomain is a new method that fetches accounts for a given order and domain
        // and that $this->currentOrderId is the orderId
        $emailAccounts = collect($order['data']['email_accounts'] ?? [])
            ->filter(function ($account) use ($domain) {
                return ($account['domain'] ?? '') === $domain;
            })
            ->values()
            ->toArray();

        foreach ($emailAccounts as $account) {
            $mailboxes[] = [
                'id' => $account['id'] ?? null,
                'email' => $account['email'] ?? $account['username'] ?? '',
                'username' => $account['email'] ?? $account['username'] ?? '',
                'name' => $account['first_name'] ?? $account['name'] ?? '', // API uses first_name/last_name
                'password' => $account['password'] ?? '', // Password might not be returned in list
                // Map SMTP/IMAP details without fallback (save null if not available)
                'smtp_host' => $account['smtp_host'] ?? $account['smtpHost'] ?? null,
                'smtp_port' => $account['smtp_port'] ?? $account['smtpPort'] ?? null,
                'imap_host' => $account['imap_host'] ?? $account['imapHost'] ?? null,
                'imap_port' => $account['imap_port'] ?? $account['imapPort'] ?? null,
            ];
        }

        return [
            'success' => true,
            'mailboxes' => $mailboxes,
        ];
    }

    public function deleteMailbox(int $mailboxId): array
    {
        // PremiumInboxes uses email_account_id (string UUID); interface expects int so we cast to string for API
        $result = $this->service->cancelEmailAccount((string) $mailboxId);

        return [
            'success' => $result['success'] ?? false,
            'message' => $result['error'] ?? 'Mailbox deleted',
        ];
    }

    /**
     * Delete all PremiumInboxes mailboxes for this order provider split.
     * Prefers bulk cancel by external_order_id; fallback to single mailbox deletion. Only sets deleted_at on success or not_found.
     */
    public function deleteMailboxesFromSplit(Order $order, OrderProviderSplit $split): array
    {
        $deletedCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        try {
            if ($split->external_order_id) {
                $listResult = $this->service->listEmailAccountsByOrderId($split->external_order_id, 50, 0);
                $emailToId = [];
                if ($listResult['success'] && !empty($listResult['email_accounts'])) {
                    foreach ($listResult['email_accounts'] as $acc) {
                        $email = $acc['email_address'] ?? $acc['email'] ?? '';
                        if ($email && isset($acc['id'])) {
                            $emailToId[strtolower($email)] = $acc['id'];
                        }
                    }
                    $mailboxes = $split->mailboxes ?? [];
                    $needsSave = false;
                    foreach ($mailboxes as $domain => $domainMailboxes) {
                        foreach ($domainMailboxes as $prefixKey => $mailbox) {
                            if ($split->isMailboxDeleted($domain, $prefixKey)) {
                                continue;
                            }
                            $email = $mailbox['mailbox'] ?? $mailbox['email'] ?? '';
                            $mailboxId = $mailbox['mailbox_id'] ?? null;
                            if (!$mailboxId && $email && isset($emailToId[strtolower($email)])) {
                                $mailboxes[$domain][$prefixKey]['mailbox_id'] = $emailToId[strtolower($email)];
                                $needsSave = true;
                            }
                        }
                    }
                    if ($needsSave) {
                        $split->mailboxes = $mailboxes;
                        $split->save();
                    }
                } elseif (!empty($listResult['timeout'])) {
                    Log::warning("PremiumInboxes list email accounts timed out; skipping pre-fill", [
                        'action' => 'delete_mailboxes_from_split',
                        'provider' => 'premiuminboxes',
                        'order_id' => $order->id,
                        'split_id' => $split->id,
                    ]);
                }

                Log::info("Cancelling PremiumInboxes order (bulk)", [
                    'action' => 'delete_mailboxes_from_split',
                    'provider' => 'premiuminboxes',
                    'order_id' => $order->id,
                    'split_id' => $split->id,
                    'external_order_id' => $split->external_order_id,
                ]);

                $result = $this->service->cancelOrder($split->external_order_id);

                if ($result['success']) {
                    $mailboxes = $split->mailboxes ?? [];
                    foreach ($mailboxes as $domain => $domainMailboxes) {
                        foreach ($domainMailboxes as $prefixKey => $mailbox) {
                            if (!$split->isMailboxDeleted($domain, $prefixKey)) {
                                $split->markMailboxAsDeleted($domain, $prefixKey);
                                $deletedCount++;
                            }
                        }
                    }
                    Log::info("PremiumInboxes order cancelled successfully", [
                        'action' => 'delete_mailboxes_from_split',
                        'provider' => 'premiuminboxes',
                        'order_id' => $order->id,
                        'split_id' => $split->id,
                        'external_order_id' => $split->external_order_id,
                    ]);
                    ActivityLogService::log(
                        'order-mailboxes-deleted',
                        "Cancelled PremiumInboxes order for cancelled order",
                        $order,
                        [
                            'order_id' => $order->id,
                            'split_id' => $split->id,
                            'provider' => 'premiuminboxes',
                            'external_order_id' => $split->external_order_id,
                        ]
                    );
                } else {
                    $errorMsg = $result['error'] ?? 'Unknown error';
                    $isTimeout = str_contains(strtolower($errorMsg), 'timeout')
                        || str_contains(strtolower($errorMsg), 'connection');
                    if ($isTimeout) {
                        Log::warning("PremiumInboxes bulk cancel failed due to timeout/connection - not marking deleted_at; will retry later", [
                            'action' => 'delete_mailboxes_from_split',
                            'provider' => 'premiuminboxes',
                            'order_id' => $order->id,
                            'split_id' => $split->id,
                            'external_order_id' => $split->external_order_id,
                            'error' => $errorMsg,
                        ]);
                    } else {
                        Log::error("Failed to cancel PremiumInboxes order", [
                            'action' => 'delete_mailboxes_from_split',
                            'provider' => 'premiuminboxes',
                            'order_id' => $order->id,
                            'split_id' => $split->id,
                            'external_order_id' => $split->external_order_id,
                            'error' => $errorMsg,
                        ]);
                    }
                }
                return ['deleted' => $deletedCount, 'failed' => $failedCount, 'skipped' => $skippedCount];
            }

            Log::info("No external_order_id, deleting PremiumInboxes mailboxes individually", [
                'action' => 'delete_mailboxes_from_split',
                'provider' => 'premiuminboxes',
                'order_id' => $order->id,
                'split_id' => $split->id,
            ]);

            $mailboxes = $split->mailboxes ?? [];
            foreach ($mailboxes as $domain => $domainMailboxes) {
                foreach ($domainMailboxes as $prefixKey => $mailbox) {
                    if ($split->isMailboxDeleted($domain, $prefixKey)) {
                        $skippedCount++;
                        continue;
                    }
                    $emailAccountId = $mailbox['mailbox_id'] ?? null;
                    if (!$emailAccountId) {
                        Log::warning("No email account ID (mailbox_id) for PremiumInboxes mailbox", [
                            'action' => 'delete_mailboxes_from_split',
                            'provider' => 'premiuminboxes',
                            'order_id' => $order->id,
                            'domain' => $domain,
                            'prefix_key' => $prefixKey,
                        ]);
                        $failedCount++;
                        continue;
                    }
                    try {
                        $result = $this->deleteMailbox(is_numeric($emailAccountId) ? (int) $emailAccountId : 0);
                        if ($result['success']) {
                            $deletedCount++;
                            $split->markMailboxAsDeleted($domain, $prefixKey, $emailAccountId);
                        } else {
                            $msg = $result['message'] ?? 'Unknown error';
                            $isNotFound = str_contains(strtolower($msg), 'not found')
                                || str_contains(strtolower($msg), 'already cancelled')
                                || str_contains(strtolower($msg), 'does not exist');
                            if ($isNotFound) {
                                $deletedCount++;
                                $split->markMailboxAsDeleted($domain, $prefixKey, $emailAccountId);
                                Log::info("PremiumInboxes mailbox not found / already cancelled - marked deleted", [
                                    'action' => 'delete_mailboxes_from_split',
                                    'provider' => 'premiuminboxes',
                                    'order_id' => $order->id,
                                    'email_account_id' => $emailAccountId,
                                ]);
                            } else {
                                $failedCount++;
                                Log::warning("Failed to delete PremiumInboxes mailbox", [
                                    'action' => 'delete_mailboxes_from_split',
                                    'provider' => 'premiuminboxes',
                                    'order_id' => $order->id,
                                    'email_account_id' => $emailAccountId,
                                    'error' => $msg,
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        $errorMessage = $e->getMessage();
                        $isTimeout = str_contains(strtolower($errorMessage), 'timeout')
                            || str_contains(strtolower($errorMessage), 'connection')
                            || ($e instanceof \Illuminate\Http\Client\ConnectionException);
                        if ($isTimeout) {
                            $failedCount++;
                            Log::warning("Connection timeout when deleting PremiumInboxes mailbox - not marking deleted_at; will retry later", [
                                'action' => 'delete_mailboxes_from_split',
                                'provider' => 'premiuminboxes',
                                'order_id' => $order->id,
                                'email_account_id' => $emailAccountId,
                                'error' => $errorMessage,
                            ]);
                        } else {
                            $failedCount++;
                            Log::error("Exception deleting PremiumInboxes mailbox", [
                                'action' => 'delete_mailboxes_from_split',
                                'provider' => 'premiuminboxes',
                                'order_id' => $order->id,
                                'email_account_id' => $emailAccountId,
                                'error' => $errorMessage,
                            ]);
                        }
                    }
                }
            }

            Log::info("PremiumInboxes mailbox deletion completed for split", [
                'action' => 'delete_mailboxes_from_split',
                'provider' => 'premiuminboxes',
                'order_id' => $order->id,
                'split_id' => $split->id,
                'deleted_count' => $deletedCount,
                'failed_count' => $failedCount,
                'skipped_count' => $skippedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting PremiumInboxes mailboxes from split', [
                'action' => 'delete_mailboxes_from_split',
                'provider' => 'premiuminboxes',
                'order_id' => $order->id,
                'split_id' => $split->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return ['deleted' => $deletedCount, 'failed' => $failedCount, 'skipped' => $skippedCount];
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

    /**
     * Get order from PremiumInboxes API (for idempotent sync when external_order_id already exists).
     *
     * @param string $orderId PremiumInboxes order ID (UUID / external_order_id)
     * @return array ['success' => bool, 'status_code' => int, 'data' => array, 'error' => string|null]
     */
    public function getOrder(string $orderId): array
    {
        return $this->service->getOrder($orderId);
    }

    /**
     * Activate domains for this PremiumInboxes split (create/sync order, update nameservers via activation service).
     */
    public function activateDomainsForSplit(
        Order $order,
        OrderProviderSplit $split,
        bool $bypassExistingMailboxCheck,
        DomainActivationService $activationService
    ): array {
        if (!$this->authenticate()) {
            Log::channel('mailin-ai')->error('PremiumInboxes authentication failed', [
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

        // Idempotent: if PI order already exists (external_order_id stored), do NOT call /purchase again
        if (!empty($split->external_order_id)) {
            Log::channel('mailin-ai')->info('PremiumInboxes: external_order_id already set, triggering verify-ns then syncing (skip purchase)', [
                'order_id' => $order->id,
                'split_id' => $split->id,
                'external_order_id' => $split->external_order_id,
            ]);

            $verifyResult = $this->service->verifyNameservers($split->external_order_id);

            if ($verifyResult['success']) {
                $this->syncFromVerifyNsResponse($order, $split, $verifyResult['data'] ?? []);
                $activeDomains = [];
                foreach ($split->domains ?? [] as $domain) {
                    if (($split->getDomainStatus($domain) ?? '') === 'active') {
                        $activeDomains[] = $domain;
                    }
                }
                Log::channel('mailin-ai')->info('PremiumInboxes order synced from verify-ns', [
                    'order_id' => $order->id,
                    'split_id' => $split->id,
                    'active_domains_count' => count($activeDomains),
                ]);
                return [
                    'rejected' => false,
                    'reason' => null,
                    'active' => $activeDomains,
                    'transferred' => [],
                    'failed' => [],
                ];
            }

            // Fall back to getOrder when verify-ns fails (network/API error)
            Log::channel('mailin-ai')->warning('PremiumInboxes verify-ns failed, falling back to GetOrder', [
                'order_id' => $order->id,
                'external_order_id' => $split->external_order_id,
                'error' => $verifyResult['error'] ?? 'Unknown error',
            ]);
            $getResult = $this->getOrder($split->external_order_id);

            if (!$getResult['success']) {
                Log::channel('mailin-ai')->error('PremiumInboxes GetOrder failed during sync (will not purchase again)', [
                    'order_id' => $order->id,
                    'external_order_id' => $split->external_order_id,
                    'error' => $getResult['error'] ?? 'Unknown error',
                ]);
                return [
                    'rejected' => false,
                    'reason' => null,
                    'active' => [],
                    'transferred' => [],
                    'failed' => $split->domains ?? [],
                ];
            }

            return $this->syncOrderFromGetOrder($order, $split, $getResult['data'] ?? []);
        }

        $reorderInfo = $order->reorderInfo()->first();
        $user = $order->user;

        $firstName = 'User';
        $lastName = '';
        $prefixVariantsDetails = $reorderInfo->prefix_variants_details ?? [];
        if (is_string($prefixVariantsDetails)) {
            $prefixVariantsDetails = json_decode($prefixVariantsDetails, true) ?? [];
        }
        $firstVariant = reset($prefixVariantsDetails);
        if ($firstVariant && isset($firstVariant['first_name'])) {
            $firstName = $firstVariant['first_name'];
            $lastName = $firstVariant['last_name'] ?? '';
        } else {
            $firstName = $reorderInfo->first_name ?? $user->first_name ?? 'User';
            $lastName = $reorderInfo->last_name ?? $user->last_name ?? '';
        }
        if (empty(trim($lastName))) {
            $lastName = !empty(trim($firstName)) && $firstName !== 'User' ? $firstName : 'Customer';
        }

        Log::channel('mailin-ai')->info('Preparing PremiumInboxes Persona', [
            'order_id' => $order->id,
            'source_first_name' => $reorderInfo->first_name ?? $user->first_name ?? null,
            'source_last_name' => $reorderInfo->last_name ?? $user->last_name ?? null,
            'final_first_name' => $firstName,
            'final_last_name' => $lastName,
        ]);

        $prefixVariants = $reorderInfo->prefix_variants ?? [];
        if (is_string($prefixVariants)) {
            $prefixVariants = json_decode($prefixVariants, true) ?? [];
        }
        $prefixVariants = array_values(array_filter($prefixVariants));

        $variations = [];
        foreach ($prefixVariants as $prefix) {
            if (!empty($prefix)) {
                $variations[] = trim($prefix);
            }
        }
        $persona = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'variations' => $variations,
        ];

        Log::channel('mailin-ai')->info('PremiumInboxes Persona Constructed', [
            'order_id' => $order->id,
            'persona' => $persona,
            'variations_count' => count($variations),
        ]);

        $emailPassword = $this->generatePassword($order->id);
        $clientOrderId = "PI-order-{$order->id}-" . \Carbon\Carbon::now();

        $sequencer = null;
        if ($reorderInfo && $reorderInfo->sequencer_login && $reorderInfo->sequencer_password) {
            $sequencer = [
                'platform' => $reorderInfo->sending_platform ?? 'instantly',
                'email' => $reorderInfo->sequencer_login,
                'password' => $reorderInfo->sequencer_password,
            ];
        }

        $additionalData = [];
        if ($reorderInfo && $reorderInfo->master_inbox_confirmation && !empty($reorderInfo->master_inbox_email)) {
            $additionalData['master_inbox'] = $reorderInfo->master_inbox_email;
        }
        if ($user && !empty($user->profile_image)) {
            $additionalData['profile_picture_url'] = $user->profile_image;
        }
        if ($user && !empty($user->domain_forwarding_url)) {
            $additionalData['forwarding_domain'] = $user->domain_forwarding_url;
        }
        if (!empty($reorderInfo->additional_info)) {
            $additionalData['additional_info'] = $reorderInfo->additional_info;
        }

        Log::channel('mailin-ai')->info('Step 1: PremiumInboxes - Calling createOrderWithDomains', [
            'order_id' => $order->id,
            'arguments' => [
                'domains' => $split->domains ?? [],
                'prefix_variants' => $prefixVariants,
                'persona' => $persona,
                'client_order_id' => $clientOrderId,
                'sequencer' => $sequencer,
                'additional_data' => $additionalData,
            ],
            'prefix_variants_count' => count($prefixVariants),
        ]);

        $result = $this->createOrderWithDomains(
            $split->domains ?? [],
            $prefixVariants,
            $persona,
            $emailPassword,
            $clientOrderId,
            $sequencer,
            $additionalData
        );

        if ($result['success']) {
            $split->update([
                'external_order_id' => $result['order_id'],
                'client_order_id' => $clientOrderId,
                'order_status' => $result['status'],
            ]);

            $orderDomains = collect($result['domains'] ?? []);
            foreach ($split->domains ?? [] as $domain) {
                $domainData = $orderDomains->firstWhere('domain', $domain);
                $domainNs = $domainData['nameservers'] ?? $result['name_servers'] ?? [];
                $nsStatus = $domainData['ns_status'] ?? 'pending';
                $ourStatus = (strtolower($nsStatus) === 'validated') ? 'active' : 'inactive';
                $split->setDomainStatus($domain, $ourStatus, null, $domainNs);
            }

            $nameServers = $result['name_servers'] ?? [];
            if (!empty($nameServers)) {
                foreach ($split->domains ?? [] as $domain) {
                    try {
                        $activationService->updateNameservers($order, $domain, $nameServers);
                    } catch (\Exception $e) {
                        $reason = "Nameserver update failed for {$domain}: " . $e->getMessage();
                        $activationService->rejectOrder($order, $reason);
                        return [
                            'rejected' => true,
                            'reason' => $reason,
                            'active' => [],
                            'transferred' => [],
                            'failed' => $split->domains ?? [],
                        ];
                    }
                }
            }

            Log::channel('mailin-ai')->info('PremiumInboxes order created successfully', [
                'order_id' => $order->id,
                'premiuminboxes_order_id' => $result['order_id'],
                'client_order_id' => $clientOrderId,
                'domain_count' => count($split->domains ?? []),
            ]);

            return [
                'rejected' => false,
                'reason' => null,
                'active' => [],
                'transferred' => $split->domains ?? [],
                'failed' => [],
            ];
        }

        $rawError = $result['message'] ?? 'Unknown error';
        $errorMessage = is_array($rawError) ? json_encode($rawError) : $rawError;
        Log::channel('mailin-ai')->error('PremiumInboxes order creation failed', [
            'order_id' => $order->id,
            'error' => $errorMessage,
        ]);
        return [
            'rejected' => true,
            'reason' => $errorMessage,
            'active' => [],
            'transferred' => [],
            'failed' => $split->domains ?? [],
        ];
    }

    /**
     * Sync PremiumInboxes order state from verify-ns API response (order_status and domain ns_status only).
     */
    private function syncFromVerifyNsResponse(Order $order, OrderProviderSplit $split, array $verifyData): void
    {
        $orderStatus = $verifyData['order_status'] ?? null;
        if ($orderStatus !== null) {
            $split->update(['order_status' => $orderStatus]);
        }

        $splitDomains = $split->domains ?? [];
        foreach ($verifyData['domains'] ?? [] as $domainItem) {
            $domain = $domainItem['domain'] ?? null;
            if ($domain === null || !in_array($domain, $splitDomains, true)) {
                continue;
            }
            $nsStatus = $domainItem['ns_status'] ?? 'pending';
            $ourStatus = (strtolower($nsStatus) === 'validated') ? 'active' : 'inactive';
            $nameservers = $domainItem['expected_nameservers'] ?? [];
            $split->setDomainStatus($domain, $ourStatus, null, $nameservers);
        }

        $split->checkAndUpdateAllDomainsActive();
    }

    /**
     * Sync PremiumInboxes order state from GetOrder response (idempotent path).
     */
    private function syncOrderFromGetOrder(Order $order, OrderProviderSplit $split, array $orderData): array
    {
        $activeDomains = [];
        $orderDomains = $orderData['domains'] ?? [];
        $orderStatus = $orderData['status'] ?? null;

        if ($orderStatus !== null) {
            $split->update(['order_status' => $orderStatus]);
        }

        foreach ($split->domains ?? [] as $domain) {
            $domainData = collect($orderDomains)->firstWhere('domain', $domain);
            $nsStatus = $domainData['ns_status'] ?? 'pending';
            $nameservers = $domainData['nameservers'] ?? [];
            $ourStatus = (strtolower($nsStatus) === 'validated') ? 'active' : 'inactive';
            $split->setDomainStatus($domain, $ourStatus, null, $nameservers);
            if ($ourStatus === 'active') {
                $activeDomains[] = $domain;
            }
        }

        $reorderInfo = $order->reorderInfo()->first();
        $prefixVariants = $reorderInfo ? ($reorderInfo->prefix_variants ?? []) : [];
        if (is_string($prefixVariants)) {
            $prefixVariants = json_decode($prefixVariants, true) ?? [];
        }
        $prefixVariants = array_values(array_filter($prefixVariants));

        $emailAccounts = $orderData['email_accounts'] ?? [];
        foreach ($emailAccounts as $account) {
            $email = $account['email'] ?? $account['email_address'] ?? '';
            if (empty($email)) {
                continue;
            }
            $parts = explode('@', $email);
            $prefix = $parts[0] ?? '';
            $domain = $account['domain'] ?? ($parts[1] ?? '');
            if (empty($domain)) {
                continue;
            }
            $piStatus = strtolower($account['status'] ?? '');
            $ourMailboxStatus = ($piStatus === 'active') ? 'active' : 'inactive';
            $prefixKey = $this->findPrefixKeyForSync($prefix, $prefixVariants);
            $mailboxData = [
                'id' => $account['id'] ?? null,
                'mailbox_id' => $account['id'] ?? null,
                'name' => $prefix,
                'mailbox' => $email,
                'email' => $email,
                'password' => $account['password'] ?? null,
                'status' => $ourMailboxStatus,
            ];
            $split->addMailbox($domain, $prefixKey, $mailboxData);
        }

        $split->checkAndUpdateAllDomainsActive();

        Log::channel('mailin-ai')->info('PremiumInboxes order synced from GetOrder', [
            'order_id' => $order->id,
            'split_id' => $split->id,
            'external_order_id' => $split->external_order_id,
            'active_domains_count' => count($activeDomains),
            'email_accounts_synced' => count($emailAccounts),
        ]);

        return [
            'rejected' => false,
            'reason' => null,
            'active' => $activeDomains,
            'transferred' => [],
            'failed' => [],
        ];
    }

    private function findPrefixKeyForSync(string $emailPrefix, array $prefixVariants): string
    {
        foreach ($prefixVariants as $key => $prefix) {
            $p = is_string($prefix) ? $prefix : (string) ($prefix['name'] ?? $prefix['prefix'] ?? '');
            if (strtolower(trim($p)) === strtolower(trim($emailPrefix))) {
                return is_numeric($key) ? 'prefix_variant_' . ($key + 1) : (string) $key;
            }
        }
        if (!empty($prefixVariants)) {
            $firstKey = array_key_first($prefixVariants);
            return is_numeric($firstKey) ? 'prefix_variant_' . ($firstKey + 1) : (string) $firstKey;
        }
        return 'prefix_variant_1';
    }

    private function generatePassword(int $orderId, int $index = 0): string
    {
        $upperCase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerCase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';
        mt_srand($orderId + $index);
        $password = '';
        $password .= $upperCase[mt_rand(0, strlen($upperCase) - 1)];
        $password .= $lowerCase[mt_rand(0, strlen($lowerCase) - 1)];
        $password .= $numbers[mt_rand(0, strlen($numbers) - 1)];
        $password .= $specialChars[mt_rand(0, strlen($specialChars) - 1)];
        $allChars = $upperCase . $lowerCase . $numbers . $specialChars;
        for ($i = 4; $i < 8; $i++) {
            $password .= $allChars[mt_rand(0, strlen($allChars) - 1)];
        }
        $passwordArray = str_split($password);
        for ($i = count($passwordArray) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            $temp = $passwordArray[$i];
            $passwordArray[$i] = $passwordArray[$j];
            $passwordArray[$j] = $temp;
        }
        return implode('', $passwordArray);
    }
}
