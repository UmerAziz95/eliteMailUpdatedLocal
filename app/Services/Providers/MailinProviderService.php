<?php

namespace App\Services\Providers;

use App\Contracts\Providers\SmtpProviderInterface;
use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Services\ActivityLogService;
use App\Services\MailAutomation\DomainActivationService;
use App\Services\MailinAiService;
use Illuminate\Support\Facades\Log;

/**
 * Mailin.ai Provider Service
 * Wraps existing MailinAiService to implement SmtpProviderInterface
 */
class MailinProviderService implements SmtpProviderInterface
{
    private MailinAiService $service;
    private array $credentials;

    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;
        $this->service = new MailinAiService($credentials);
    }

    /**
     * Authenticate with Mailin.ai API
     */
    public function authenticate(): ?string
    {
        return $this->service->authenticate();
    }

    /**
     * Transfer domain to Mailin.ai
     */
    public function transferDomain(string $domain): array
    {
        return $this->service->transferDomain($domain);
    }

    /**
     * Check domain status on Mailin.ai
     */
    public function checkDomainStatus(string $domain): array
    {
        return $this->service->checkDomainStatus($domain);
    }

    /**
     * Create mailboxes on Mailin.ai
     */
    public function createMailboxes(array $mailboxes): array
    {
        return $this->service->createMailboxes($mailboxes);
    }

    /**
     * Delete mailbox from Mailin.ai
     */
    public function deleteMailbox(int $mailboxId): array
    {
        return $this->service->deleteMailbox($mailboxId);
    }

    /**
     * Get mailboxes by domain from Mailin.ai
     */
    public function getMailboxesByDomain(string $domain): array
    {
        return $this->service->getMailboxesByDomain($domain);
    }

    /**
     * Delete all Mailin.ai mailboxes for this order provider split.
     * Pre-fills missing mailbox_id via lookup; only sets deleted_at on success or not_found, never on timeout.
     */
    public function deleteMailboxesFromSplit(Order $order, OrderProviderSplit $split): array
    {
        $deletedCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        try {
            $mailboxes = $split->mailboxes ?? [];
            if (empty($mailboxes)) {
                Log::info("No mailboxes found in split for Mailin deletion", [
                    'action' => 'delete_mailboxes_from_split',
                    'provider' => 'mailin',
                    'order_id' => $order->id,
                    'split_id' => $split->id,
                ]);
                return ['deleted' => 0, 'failed' => 0, 'skipped' => 0];
            }

            // Pass 1: Pre-fill missing mailbox_id
            $needsSave = false;
            foreach ($mailboxes as $domain => $domainMailboxes) {
                foreach ($domainMailboxes as $prefixKey => $mailbox) {
                    if ($split->isMailboxDeleted($domain, $prefixKey)) {
                        continue;
                    }
                    $mailboxId = $mailbox['mailbox_id'] ?? null;
                    $email = $mailbox['mailbox'] ?? $mailbox['email'] ?? '';
                    if ($mailboxId || empty($email)) {
                        continue;
                    }
                    Log::info("Mailbox ID missing, looking up by email (pre-fill pass)", [
                        'action' => 'delete_mailboxes_from_split',
                        'provider' => 'mailin',
                        'order_id' => $order->id,
                        'email' => $email,
                    ]);
                    $lookupResult = $this->service->lookupMailboxIdByEmail($email);
                    if ($lookupResult['success'] && isset($lookupResult['mailbox_id'])) {
                        $mailboxes[$domain][$prefixKey]['mailbox_id'] = $lookupResult['mailbox_id'];
                        $needsSave = true;
                        Log::info("Mailbox ID found and stored (pre-fill)", [
                            'action' => 'delete_mailboxes_from_split',
                            'provider' => 'mailin',
                            'order_id' => $order->id,
                            'email' => $email,
                            'mailbox_id' => $lookupResult['mailbox_id'],
                        ]);
                    } elseif (isset($lookupResult['timeout']) && $lookupResult['timeout']) {
                        Log::warning("Connection timeout during pre-fill lookup - skipping, will retry later", [
                            'action' => 'delete_mailboxes_from_split',
                            'provider' => 'mailin',
                            'order_id' => $order->id,
                            'email' => $email,
                        ]);
                    } else {
                        $mailboxes[$domain][$prefixKey]['deleted_at'] = now()->toISOString();
                        $needsSave = true;
                        Log::info("Mailbox not found on Mailin.ai during pre-fill (will mark deleted)", [
                            'action' => 'delete_mailboxes_from_split',
                            'provider' => 'mailin',
                            'order_id' => $order->id,
                            'email' => $email,
                        ]);
                    }
                }
            }
            if ($needsSave) {
                $split->mailboxes = $mailboxes;
                $split->save();
            }

            // Pass 2: Delete using mailbox_id
            $mailboxes = $split->mailboxes ?? [];
            foreach ($mailboxes as $domain => $domainMailboxes) {
                foreach ($domainMailboxes as $prefixKey => $mailbox) {
                    if ($split->isMailboxDeleted($domain, $prefixKey)) {
                        $skippedCount++;
                        continue;
                    }
                    $email = $mailbox['mailbox'] ?? $mailbox['email'] ?? '';
                    $mailboxId = $mailbox['mailbox_id'] ?? null;
                    if (!$mailboxId) {
                        Log::warning("Cannot delete mailbox - no mailbox_id after pre-fill", [
                            'action' => 'delete_mailboxes_from_split',
                            'provider' => 'mailin',
                            'order_id' => $order->id,
                            'domain' => $domain,
                            'prefix_key' => $prefixKey,
                        ]);
                        $failedCount++;
                        continue;
                    }
                    try {
                        $result = $this->service->deleteMailbox((int) $mailboxId);
                        if ($result['success'] || !empty($result['not_found'])) {
                            $deletedCount++;
                            $split->markMailboxAsDeleted($domain, $prefixKey, $mailboxId);
                            Log::info(!empty($result['not_found']) ? "Mailin mailbox not found (already deleted)" : "Mailin mailbox deleted successfully", [
                                'action' => 'delete_mailboxes_from_split',
                                'provider' => 'mailin',
                                'order_id' => $order->id,
                                'email' => $email,
                                'mailbox_id' => $mailboxId,
                                'not_found' => $result['not_found'] ?? false,
                            ]);
                        } else {
                            $failedCount++;
                            Log::warning("Failed to delete Mailin mailbox", [
                                'action' => 'delete_mailboxes_from_split',
                                'provider' => 'mailin',
                                'order_id' => $order->id,
                                'email' => $email,
                                'mailbox_id' => $mailboxId,
                                'error' => $result['message'] ?? 'Unknown error',
                            ]);
                        }
                    } catch (\Exception $e) {
                        $errorMessage = $e->getMessage();
                        $isNotFound = str_contains(strtolower($errorMessage), 'not found')
                            || str_contains(strtolower($errorMessage), 'no query results')
                            || str_contains(strtolower($errorMessage), 'does not exist');
                        $isTimeout = str_contains(strtolower($errorMessage), 'timeout')
                            || str_contains(strtolower($errorMessage), 'connection')
                            || str_contains(strtolower($errorMessage), 'curl error 28')
                            || ($e instanceof \Illuminate\Http\Client\ConnectionException);
                        if ($isNotFound) {
                            $deletedCount++;
                            $split->markMailboxAsDeleted($domain, $prefixKey, $mailboxId);
                            Log::info("Mailin mailbox not found (already deleted) - marked as deleted", [
                                'action' => 'delete_mailboxes_from_split',
                                'provider' => 'mailin',
                                'order_id' => $order->id,
                                'email' => $email,
                                'mailbox_id' => $mailboxId,
                            ]);
                        } elseif ($isTimeout) {
                            $failedCount++;
                            Log::warning("Connection timeout when deleting Mailin mailbox - not marking deleted_at; will retry later", [
                                'action' => 'delete_mailboxes_from_split',
                                'provider' => 'mailin',
                                'order_id' => $order->id,
                                'email' => $email,
                                'mailbox_id' => $mailboxId,
                                'error' => $errorMessage,
                            ]);
                        } else {
                            $failedCount++;
                            Log::error("Exception deleting Mailin mailbox", [
                                'action' => 'delete_mailboxes_from_split',
                                'provider' => 'mailin',
                                'order_id' => $order->id,
                                'email' => $email,
                                'mailbox_id' => $mailboxId,
                                'error' => $errorMessage,
                            ]);
                        }
                    }
                }
            }

            Log::info("Mailin mailbox deletion completed for split", [
                'action' => 'delete_mailboxes_from_split',
                'provider' => 'mailin',
                'order_id' => $order->id,
                'split_id' => $split->id,
                'deleted_count' => $deletedCount,
                'failed_count' => $failedCount,
                'skipped_count' => $skippedCount,
            ]);

            if ($deletedCount > 0) {
                ActivityLogService::log(
                    'order-mailboxes-deleted',
                    "Deleted {$deletedCount} Mailin.ai mailbox(es) for cancelled order",
                    $order,
                    [
                        'order_id' => $order->id,
                        'split_id' => $split->id,
                        'provider' => 'mailin',
                        'deleted_count' => $deletedCount,
                        'failed_count' => $failedCount,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Error deleting Mailin mailboxes from split', [
                'action' => 'delete_mailboxes_from_split',
                'provider' => 'mailin',
                'order_id' => $order->id,
                'split_id' => $split->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return ['deleted' => $deletedCount, 'failed' => $failedCount, 'skipped' => $skippedCount];
    }

    /**
     * Get provider display name
     */
    public function getProviderName(): string
    {
        return 'Mailin.ai';
    }

    /**
     * Get provider slug
     */
    public function getProviderSlug(): string
    {
        return 'mailin';
    }

    /**
     * Check if provider is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->credentials['base_url'])
            && !empty($this->credentials['email'])
            && !empty($this->credentials['password']);
    }

    /**
     * Activate domains for this Mailin split (transfer/validate domains, update nameservers via activation service).
     */
    public function activateDomainsForSplit(
        Order $order,
        OrderProviderSplit $split,
        bool $bypassExistingMailboxCheck,
        DomainActivationService $activationService
    ): array {
        $results = [
            'rejected' => false,
            'reason' => null,
            'active' => [],
            'transferred' => [],
            'failed' => [],
        ];

        if (!$this->authenticate()) {
            Log::channel('mailin-ai')->error('Provider authentication failed', [
                'provider' => $split->provider_slug,
                'order_id' => $order->id,
            ]);
            $results['failed'] = $split->domains ?? [];
            return $results;
        }

        $reorderInfo = $order->reorderInfo->first();
        $prefixVariantsRaw = $reorderInfo ? ($reorderInfo->prefix_variants ?? []) : [];
        $prefixVariants = array_filter(array_values($prefixVariantsRaw));

        foreach ($split->domains ?? [] as $domain) {
            if ($split->getDomainStatus($domain) === 'active' && !empty($split->getNameservers($domain))) {
                $results['active'][] = $domain;
                Log::channel('mailin-ai')->debug("Skipping check for already active domain (nameservers present)", [
                    'domain' => $domain,
                    'order_id' => $order->id,
                ]);
                continue;
            }

            try {
                $existingMailboxes = $this->getMailboxesByDomain($domain);
                if (!$bypassExistingMailboxCheck && !empty($existingMailboxes['mailboxes']) && !empty($prefixVariants)) {
                    $existingPrefixes = [];
                    foreach ($existingMailboxes['mailboxes'] as $mb) {
                        $email = $mb['email'] ?? $mb['username'] ?? '';
                        if (strpos($email, '@') !== false) {
                            $existingPrefixes[] = strtolower(explode('@', $email)[0]);
                        }
                    }
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
                        $activationService->rejectOrder($order, "Same mailboxes already exist: " . implode(', ', $conflictingMailboxes));
                        return [
                            'rejected' => true,
                            'reason' => "Same mailboxes already exist: " . implode(', ', $conflictingMailboxes),
                            'active' => $results['active'],
                            'transferred' => $results['transferred'],
                            'failed' => $results['failed'],
                        ];
                    }
                }

                $status = $this->checkDomainStatus($domain);

                if ($status['success'] && $status['status'] === 'active') {
                    $results['active'][] = $domain;
                    $domainId = $status['data']['id'] ?? $status['domain_id'] ?? null;
                    $ns = $status['name_servers'] ?? $status['nameservers'] ?? $status['data']['nameservers'] ?? [];

                    if (empty($ns) && method_exists($this, 'getNameservers')) {
                        try {
                            $nsResult = $this->getNameservers([$domain]);
                            if (($nsResult['success'] ?? false) && !empty($nsResult['domains'][$domain]['nameservers'])) {
                                $ns = $nsResult['domains'][$domain]['nameservers'];
                                Log::channel('mailin-ai')->info('Fetched missing nameservers via fallback', [
                                    'domain' => $domain,
                                    'nameservers' => $ns,
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::channel('mailin-ai')->warning('Failed to fetch missing nameservers', ['domain' => $domain, 'error' => $e->getMessage()]);
                        }
                    }

                    $split->setDomainStatus($domain, 'active', $domainId, $ns);
                    Log::channel('mailin-ai')->info('Domain is active', [
                        'domain' => $domain,
                        'domain_id' => $domainId,
                        'provider' => $split->provider_slug,
                        'order_id' => $order->id,
                    ]);
                } else {
                    $transferResult = $this->transferDomain($domain);
                    if ($transferResult['success']) {
                        $ns = $transferResult['name_servers'] ?? [];
                        if (!empty($ns)) {
                            try {
                                $activationService->updateNameservers($order, $domain, $ns);
                            } catch (\Exception $e) {
                                $reason = "Nameserver update failed for {$domain}: " . $e->getMessage();
                                $activationService->rejectOrder($order, $reason);
                                return [
                                    'rejected' => true,
                                    'reason' => $reason,
                                    'active' => $results['active'],
                                    'transferred' => $results['transferred'],
                                    'failed' => array_merge($results['failed'], [$domain]),
                                ];
                            }
                        }
                        $results['transferred'][] = $domain;
                        $split->setDomainStatus($domain, 'pending', null, $ns);
                        Log::channel('mailin-ai')->info('Domain transferred', [
                            'domain' => $domain,
                            'provider' => $split->provider_slug,
                            'order_id' => $order->id,
                        ]);
                    } else {
                        $message = $transferResult['message'] ?? '';
                        $isRateLimit = str_contains(strtolower($message), 'rate limit')
                            || str_contains($message, '429')
                            || str_contains($message, 'Too Many Attempts');
                        if ($isRateLimit) {
                            Log::channel('mailin-ai')->warning('Domain transfer rate limit exceeded (will retry later)', [
                                'domain' => $domain,
                                'error' => $message,
                                'order_id' => $order->id,
                            ]);
                            $results['failed'][] = $domain;
                            $split->setDomainStatus($domain, 'failed');
                        } else {
                            $activationService->rejectOrder($order, "Domain transfer failed for: {$domain}. {$message}");
                            return [
                                'rejected' => true,
                                'reason' => "Domain transfer failed for: {$domain}. {$message}",
                                'active' => $results['active'],
                                'transferred' => $results['transferred'],
                                'failed' => array_merge($results['failed'], [$domain]),
                            ];
                        }
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

        $split->checkAndUpdateAllDomainsActive();
        return $results;
    }
}
