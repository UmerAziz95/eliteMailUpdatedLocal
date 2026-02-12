<?php

namespace App\Services\Providers;

use App\Contracts\Providers\SmtpProviderInterface;
use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Services\ActivityLogService;
use App\Services\MailAutomation\DomainActivationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Mailrun Provider Service
 * 
 * Implements the multi-step Mailrun API enrollment flow:
 * 1. Domain Setup (POST /affiliate/domain/setup) - max 20 domains
 * 2. Nameserver Retrieval (POST /domain/nameservers)
 * 3. Enrollment Begin (POST /affiliate/enrollment/begin) - max 50 domains
 * 4. Enrollment Status (POST /affiliate/enrollment/status)
 * 5. Enrollment Provision (POST /affiliate/enrollment/provision)
 * 
 * Rate Limits:
 * - Domain setup: 5/min
 * - Nameservers: 30/min
 * - Enrollment begin: 20/hour
 * - Status check: 30/min
 * - Provision: 30/min
 */
class MailrunProviderService implements SmtpProviderInterface
{
    private const BASE_URL = 'https://api.mailrun.ai/api';
    private const MAX_DOMAINS_SETUP = 20;

    /**
     * Set to true when Mailrun provides deletion API documentation.
     * Then implement external API call in deleteMailboxesFromSplit and deleteMailbox;
     * keep log keys mailrun_deletion_mode ('api') and mailrun_api_available (true).
     */
    private const MAILRUN_DELETION_API_AVAILABLE = true;
    private const MAX_DOMAINS_ENROLLMENT = 50;
    private const ENROLLMENT_POLL_INTERVAL = 15; // minutes
    private const ENROLLMENT_MAX_WAIT = 120; // minutes (2 hours)

    private string $baseUrl;
    private string $apiToken;
    private ?string $customerId;
    private int $timeout = 60;

    public function __construct(array $credentials)
    {
        $this->baseUrl = $credentials['base_url'] ?? self::BASE_URL;
        $this->apiToken = $credentials['api_key'] ?? $credentials['api_token'] ?? $credentials['password'] ?? '';
        $this->customerId = $credentials['customer_id'] ?? null;

        Log::channel('mailin-ai')->debug('MailrunProviderService initialized', [
            'has_token' => !empty($this->apiToken),
            'has_customer_id' => !empty($this->customerId),
        ]);
    }

    /**
     * Authenticate - Mailrun uses Bearer token, no separate auth endpoint
     */
    public function authenticate(): ?string
    {
        if (empty($this->apiToken)) {
            Log::channel('mailin-ai')->error('Mailrun: API token not configured');
            return null;
        }

        // Mailrun uses static bearer token - no login endpoint
        Log::channel('mailin-ai')->info('Mailrun: Using Bearer token authentication');
        return $this->apiToken;
    }

    /**
     * Transfer domain to Mailrun
     * Maps to: Step 1 (Domain Setup) + Step 2 (Nameserver Retrieval)
     */
    public function transferDomain(string $domain): array
    {
        try {
            // Step 1: Domain Setup
            $setupResult = $this->domainSetup([$domain]);

            if (!$setupResult['success']) {
                return $setupResult;
            }

            // Step 2: Get Nameservers
            $nsResult = $this->getNameservers([$domain]);

            if (!$nsResult['success']) {
                return [
                    'success' => false,
                    'message' => 'Domain setup succeeded but nameserver retrieval failed: ' . ($nsResult['message'] ?? 'Unknown error'),
                    'name_servers' => [],
                ];
            }

            // Extract nameservers for this domain
            $nameServers = $nsResult['domains'][$domain]['nameservers'] ?? [];

            Log::channel('mailin-ai')->info('Mailrun: Domain transferred successfully', [
                'domain' => $domain,
                'nameservers' => $nameServers,
            ]);

            return [
                'success' => true,
                'message' => 'Domain setup complete. Update nameservers at registrar.',
                'name_servers' => $nameServers,
                'domain' => $domain,
            ];

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailrun: Domain transfer failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'name_servers' => [],
            ];
        }
    }

    /**
     * Check domain status
     * Maps to: Nameserver Status Check (POST /affiliate/nameserver/status)
     */
    public function checkDomainStatus(string $domain): array
    {
        try {
            $response = $this->makeRequest('POST', '/affiliate/nameserver/status', [
                'domains' => [
                    ['domain' => $domain]
                ]
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'status' => 'unknown',
                    'message' => 'Failed to check domain status: ' . $response->body(),
                ];
            }

            $data = $response->json();
            $domainStatus = null;

            // Handle different response formats (keyed or list)
            $list = $data['domains'] ?? $data['domainNameservers'] ?? $data['data'] ?? null;

            if (is_array($list)) {
                foreach ($list as $item) {
                    if (($item['domain'] ?? '') === $domain) {
                        $domainStatus = $item;
                        break;
                    }
                }
                // Fallback: if we requested 1 domain and list has 1 item, assuming it's ours if domain key missing or mismatch (rare)
                if (!$domainStatus && count($list) === 1 && isset($list[0])) {
                    $domainStatus = $list[0];
                }
            }

            if (!$domainStatus) {
                // Try direct key access
                $domainStatus = $data['domains'][$domain] ?? $data[$domain] ?? null;
            }

            // Determine if nameservers are active
            // Determine if nameservers are active
            $isActive = false;
            if (is_array($domainStatus)) {
                $isActive = ($domainStatus['status'] ?? '') === 'active'
                    || ($domainStatus['nameservers_valid'] ?? false) === true
                    || (($domainStatus['cloudflare-status']['status'] ?? '') === 'active');
            }

            Log::channel('mailin-ai')->info('Mailrun: Domain status checked', [
                'domain' => $domain,
                'is_active' => $isActive,
                'response' => $domainStatus,
            ]);

            return [
                'success' => true,
                'status' => $isActive ? 'active' : 'pending',
                'name_servers' => $domainStatus['nameservers'] ?? $domainStatus['ns'] ?? [],
                'data' => $domainStatus,
                'domain_id' => $domainStatus['id'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailrun: Domain status check failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create mailboxes
     * Maps to: Step 3 (Enrollment Begin) + Step 4 (Status) + Step 5 (Provision)
     * 
     * @param array $mailboxes [['username' => 'user@domain.com', 'name' => 'Name', 'password' => 'pass']]
     */
    public function createMailboxes(array $mailboxes): array
    {
        try {
            if (empty($mailboxes)) {
                return ['success' => false, 'message' => 'No mailboxes provided'];
            }

            // Group mailboxes by domain
            $domainMailboxes = [];
            foreach ($mailboxes as $mb) {
                $email = $mb['username'] ?? '';
                if (strpos($email, '@') === false)
                    continue;

                [$prefix, $domain] = explode('@', $email, 2);
                if (!isset($domainMailboxes[$domain])) {
                    $domainMailboxes[$domain] = [];
                }
                $domainMailboxes[$domain][] = [
                    'senderAddress' => $email,
                    'senderDisplayName' => $mb['name'] ?? $prefix,
                ];
            }

            // check max domains limit - if greater than 50 we need to batch
            $domains = array_keys($domainMailboxes);
            $batches = array_chunk($domains, self::MAX_DOMAINS_ENROLLMENT);
            $uuids = [];
            $allProcessedDomains = [];

            if (count($batches) > 1) {
                Log::channel('mailin-ai')->info('Mailrun: Large enrollment, processing in batches', [
                    'total_domains' => count($domains),
                    'total_batches' => count($batches),
                ]);
            }

            foreach ($batches as $index => $batchDomains) {
                // Build enrollment request for this batch
                $enrollmentDomains = [];
                foreach ($batchDomains as $domain) {
                    $senders = $domainMailboxes[$domain] ?? [];
                    $enrollmentDomains[] = [
                        'domain' => $domain,
                        'senderPermutationOverride' => $senders,
                    ];
                }

                // Log what we're about to send
                Log::channel('mailin-ai')->info('Mailrun: Preparing enrollment batch', [
                    'batch_index' => $index + 1,
                    'domains_in_batch' => count($enrollmentDomains),
                    'aliasCountOverride' => (int) (count($mailboxes) / count($domains)),
                    'domain_details' => collect($enrollmentDomains)->map(fn($d) => [
                        'domain' => $d['domain'],
                        'sender_count' => count($d['senderPermutationOverride']),
                        'senders' => collect($d['senderPermutationOverride'])->pluck('senderAddress')->toArray(),
                    ])->toArray(),
                ]);

                // Step 3: Begin Enrollment for batch with local retry
                $batchSuccess = false;
                $enrollResult = [];
                $retryCount = 0;
                $maxBatchRetries = 2; // Retry twice (total 3 attempts)

                while (!$batchSuccess && $retryCount <= $maxBatchRetries) {
                    if ($retryCount > 0) {
                        Log::channel('mailin-ai')->warning("Mailrun: Retrying batch " . ($index + 1) . " (Attempt " . ($retryCount + 1) . ")");
                        // Exponential backoff: 3s, 6s
                        sleep(3 * $retryCount);
                    }

                    $enrollResult = $this->beginEnrollment($enrollmentDomains, count($mailboxes) / count($domains));

                    if ($enrollResult['success']) {
                        $batchSuccess = true;
                    } else {
                        $retryCount++;
                        Log::channel('mailin-ai')->error("Mailrun: Batch " . ($index + 1) . " failed attempt " . $retryCount . ": " . ($enrollResult['message'] ?? 'Unknown error'));
                    }
                }

                if (!$batchSuccess) {
                    Log::channel('mailin-ai')->error("Mailrun: Batch " . ($index + 1) . " failed after retries. Aborting all.", [
                        'error' => $enrollResult['message'] ?? 'Unknown error',
                    ]);

                    // If ANY batch fails definitively, fail the whole process.
                    // This allows the external scheduler to retry the whole order later.
                    return $enrollResult;
                }

                $uuid = $enrollResult['uuid'] ?? $enrollResult['id'] ?? null;
                if ($uuid) {
                    $uuids[] = $uuid;
                }
                $allProcessedDomains = array_merge($allProcessedDomains, $batchDomains);

                // Rate limit spacing between batches if needed (20 calls/hour for beginEnrollment)
                // Just add a small safety delay
                if ($index < count($batches) - 1) {
                    sleep(2);
                }
            }

            Log::channel('mailin-ai')->info('Mailrun: Enrollment initiated for all batches', [
                'uuids' => $uuids,
                'domain_count' => count($allProcessedDomains),
            ]);

            // NOTE: Enrollment is async and can take up to 2 hours
            // The caller should use checkEnrollmentStatus() and getProvisionedEmails() later

            return [
                'success' => true,
                'message' => 'Enrollment initiated. Check status periodically.',
                'domains' => $allProcessedDomains,
                'uuids' => $uuids, // Return array of UUIDs
                'uuid' => $uuids[0] ?? null, // Backwards compatibility (first UUID)
                'async' => true,
            ];

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailrun: Mailbox creation failed', [
                'error' => $e->getMessage(),
                'mailbox_count' => count($mailboxes),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Delete mailbox - Not supported by Mailrun API
     */
    public function deleteMailbox(int $mailboxId): array
    {
        Log::channel('mailin-ai')->warning('Mailrun: deleteMailbox not supported by API');
        return [
            'success' => false,
            'message' => 'Mailrun API does not support mailbox deletion',
        ];
    }

    /**
     * Delete all Mailrun mailboxes for this order provider split.
     *
     * Local-only mode: Mailrun API does not currently support mailbox deletion.
     * We only mark deleted_at in our split JSON. When Mailrun provides deletion
     * API documentation, implement the external API call here and:
     * - Keep the same log context keys (mailrun_deletion_mode, mailrun_api_available, etc.)
     * - Set mailrun_api_available => true and mailrun_deletion_mode => 'api' in logs
     * - Use storage/logs/mailrun.log for deletion-related logs (search: mailrun_deletion)
     */
    public function deleteMailboxesFromSplit(Order $order, OrderProviderSplit $split): array
    {
        $deletedCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        $logContext = [
            'action' => 'mailrun_deletion',
            'provider' => 'mailrun',
            'order_id' => $order->id,
            'split_id' => $split->id,
            'mailrun_deletion_mode' => self::MAILRUN_DELETION_API_AVAILABLE ? 'api' : 'local_only',
            'mailrun_api_available' => self::MAILRUN_DELETION_API_AVAILABLE,
        ];

        try {
            $mailboxes = $split->mailboxes ?? [];
            $totalMailboxes = 0;
            foreach ($mailboxes as $domain => $domainMailboxes) {
                $totalMailboxes += is_array($domainMailboxes) ? count($domainMailboxes) : 0;
            }
            $logContext['mailbox_count'] = $totalMailboxes;
            $logContext['domains'] = array_keys($mailboxes);

            Log::channel('mailin-ai')->warning('Mailrun: local-only deletion; external API not available', $logContext);

            foreach ($mailboxes as $domain => $domainMailboxes) {
                foreach ($domainMailboxes as $prefixKey => $mailbox) {
                    if (!$split->isMailboxDeleted($domain, $prefixKey)) {
                        $split->markMailboxAsDeleted($domain, $prefixKey);
                        $deletedCount++;
                    } else {
                        $skippedCount++;
                    }
                }
            }

            $logContext['deleted'] = $deletedCount;
            $logContext['skipped'] = $skippedCount;
            $logContext['failed'] = $failedCount;

            Log::channel('mailin-ai')->info('Mailrun: mailboxes marked deleted locally (no external API)', $logContext);

            // Also log to default channel for action-based search (search: mailrun_deletion)
            Log::info('Mailrun split deletion completed', [
                'action' => 'delete_mailboxes_from_split',
                'provider' => 'mailrun',
                'order_id' => $order->id,
                'split_id' => $split->id,
                'mailrun_deletion_mode' => $logContext['mailrun_deletion_mode'],
                'mailrun_api_available' => $logContext['mailrun_api_available'],
                'deleted' => $deletedCount,
                'skipped' => $skippedCount,
                'failed' => $failedCount,
            ]);

            if ($deletedCount > 0) {
                ActivityLogService::log(
                    'order-mailboxes-deleted',
                    "Marked Mailrun mailboxes as deleted (local-only; external API not yet available)",
                    $order,
                    [
                        'order_id' => $order->id,
                        'split_id' => $split->id,
                        'provider' => 'mailrun',
                        'note' => 'Mailrun: local-only deletion; external API deletion not supported yet',
                        'mailrun_deletion_mode' => 'local_only',
                    ]
                );
            }
        } catch (\Exception $e) {
            $logContext['error'] = $e->getMessage();
            $logContext['trace'] = $e->getTraceAsString();
            Log::channel('mailin-ai')->error('Mailrun: error during local-only deletion', $logContext);
            Log::error('Error processing Mailrun mailboxes from split', [
                'action' => 'delete_mailboxes_from_split',
                'provider' => 'mailrun',
                'order_id' => $order->id,
                'split_id' => $split->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return ['deleted' => $deletedCount, 'failed' => $failedCount, 'skipped' => $skippedCount];
    }

    /**
     * Get mailboxes by domain
     * Maps to: Step 5 (Enrollment Provision)
     */
    public function getMailboxesByDomain(string $domain): array
    {
        try {
            // First check if enrollment is complete
            $statusResult = $this->checkEnrollmentStatus([$domain]);

            if (!$statusResult['success']) {
                return [
                    'success' => false,
                    'mailboxes' => [],
                    'message' => 'Enrollment status check failed',
                ];
            }

            $domainStatus = $statusResult['domains'][$domain] ?? null;
            $status = strtolower($domainStatus['status'] ?? '');
            $isComplete = $status === 'complete' || $status === 'success'
                || ($domainStatus['provisioned'] ?? false) === true
                || ($domainStatus['enrollmentStep'] ?? '') === 'SetupComplete';

            if (!$isComplete) {
                Log::channel('mailin-ai')->info('Mailrun: Enrollment not yet complete', [
                    'domain' => $domain,
                    'status' => $domainStatus,
                ]);
                return [
                    'success' => true,
                    'mailboxes' => [],
                    'message' => 'Enrollment in progress',
                    'enrollment_status' => $domainStatus['status'] ?? 'pending',
                ];
            }

            // Get provisioned emails
            $provisionResult = $this->getProvisionedEmails([$domain]);

            if (!$provisionResult['success']) {
                return [
                    'success' => false,
                    'mailboxes' => [],
                    'message' => 'Failed to get provisioned emails',
                ];
            }

            // Format mailboxes for interface compatibility
            $mailboxes = [];
            $domainEmails = $provisionResult['domains'][$domain]['emails'] ?? [];

            foreach ($domainEmails as $email) {
                $mailboxes[] = [
                    'id' => $email['id'] ?? null,
                    'email' => $email['email'] ?? $email['address'] ?? '',
                    'username' => $email['email'] ?? $email['address'] ?? '',
                    'name' => $email['display_name'] ?? $email['name'] ?? '',
                    'password' => $email['password'] ?? '',
                    'smtp_host' => $email['smtp_host'] ?? $email['smtp']['host'] ?? '',
                    'smtp_port' => $email['smtp_port'] ?? $email['smtp']['port'] ?? 587,
                    'imap_host' => $email['imap_host'] ?? $email['imap']['host'] ?? '',
                    'imap_port' => $email['imap_port'] ?? $email['imap']['port'] ?? 993,
                ];
            }

            Log::channel('mailin-ai')->info('Mailrun: Mailboxes retrieved', [
                'domain' => $domain,
                'count' => count($mailboxes),
            ]);

            return [
                'success' => true,
                'mailboxes' => $mailboxes,
            ];

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailrun: Failed to get mailboxes', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'mailboxes' => [],
                'message' => $e->getMessage(),
            ];
        }
    }

    public function getProviderName(): string
    {
        return 'Mailrun';
    }

    public function getProviderSlug(): string
    {
        return 'mailrun';
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiToken);
    }

    /**
     * Activate domains for this Mailrun split (transfer/validate domains, update nameservers via activation service).
     * Same per-domain flow as Mailin: conflict check, checkDomainStatus, transferDomain, updateNameservers.
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

    // =========================================================================
    // INTERNAL API METHODS
    // =========================================================================

    /**
     * Step 1: Domain Setup (POST /affiliate/domain/setup)
     * Max 20 domains per call
     */
    public function domainSetup(array $domains): array
    {
        try {
            // Chunk if exceeds limit
            if (count($domains) > self::MAX_DOMAINS_SETUP) {
                Log::channel('mailin-ai')->warning('Mailrun: Domain setup limit exceeded, processing in batches', [
                    'total' => count($domains),
                    'max_per_batch' => self::MAX_DOMAINS_SETUP,
                ]);

                $allSuccess = true;
                $messages = [];

                foreach (array_chunk($domains, self::MAX_DOMAINS_SETUP) as $batch) {
                    $result = $this->domainSetup($batch);
                    if (!$result['success']) {
                        $allSuccess = false;
                        $messages[] = $result['message'] ?? 'Batch failed';
                    }
                    // Rate limit: 5/min, wait between batches
                    sleep(12);
                }

                return [
                    'success' => $allSuccess,
                    'message' => $allSuccess ? 'All batches processed' : implode('; ', $messages),
                ];
            }

            $payload = [
                'domains' => array_map(fn($d) => ['domain' => $d], $domains),
            ];

            if ($this->customerId) {
                $payload['customerId'] = $this->customerId;
            }

            $response = $this->makeRequest('POST', '/affiliate/domain/setup', $payload);

            if (!$response->successful()) {
                $error = $response->json('message') ?? $response->body();
                Log::channel('mailin-ai')->error('Mailrun: Domain setup failed', [
                    'domains' => $domains,
                    'error' => $error,
                    'status' => $response->status(),
                ]);

                return [
                    'success' => false,
                    'message' => "Domain setup failed: {$error}",
                ];
            }

            Log::channel('mailin-ai')->info('Mailrun: Domain setup successful', [
                'domains' => $domains,
            ]);

            return [
                'success' => true,
                'message' => 'Domains added to Mailrun',
                'data' => $response->json(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Step 2: Get Nameservers (POST /domain/nameservers)
     */
    public function getNameservers(array $domains): array
    {
        try {
            $response = $this->makeRequest('POST', '/domain/nameservers', [
                'domains' => array_map(fn($d) => ['domain' => $d], $domains),
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Nameserver retrieval failed: ' . $response->body(),
                    'domains' => [],
                ];
            }

            $data = $response->json();

            // Log raw response for debugging
            Log::channel('mailin-ai')->debug('Mailrun: Raw nameservers API response', [
                'response_keys' => is_array($data) ? array_keys($data) : 'not_array',
                'raw_data' => $data,
            ]);

            // Parse response into domain => nameservers map
            $result = ['success' => true, 'domains' => []];

            // Handle various response formats
            // Handle various response formats
            // Check for known keys where the list of domains might be
            $domainList = $data['domains'] ?? $data['domainNameservers'] ?? $data['data'] ?? null;

            if (is_array($domainList)) {
                foreach ($domainList as $domainData) {
                    // Try to finding 'domain' key, or if result is keyed by domain name (in parsing below)
                    // If items are arrays containing 'domain' key
                    if (is_array($domainData) && isset($domainData['domain'])) {
                        $domain = $domainData['domain'];
                        $ns = $domainData['nameservers'] ?? $domainData['ns'] ?? [];
                        $result['domains'][$domain] = [
                            'nameservers' => $ns,
                        ];
                        Log::channel('mailin-ai')->debug('Mailrun: Extracted nameservers for domain', [
                            'domain' => $domain,
                            'nameservers' => $ns,
                        ]);
                    }
                }
            }

            // Fallback: iterate top level keys if we didn't extract anything from a list
            if (empty($result['domains']) && is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_string($key) && is_array($value)) {
                        $ns = $value['nameservers'] ?? $value['ns'] ?? $value;
                        $result['domains'][$key] = [
                            'nameservers' => $ns,
                        ];
                        Log::channel('mailin-ai')->debug('Mailrun: Extracted nameservers via fallback', [
                            'domain_key' => $key,
                            'nameservers' => $ns,
                        ]);
                    }
                }
            }

            Log::channel('mailin-ai')->info('Mailrun: Nameservers retrieved', [
                'domains' => array_keys($result['domains']),
                'nameservers_count' => array_map(fn($d) => count($d['nameservers'] ?? []), $result['domains']),
            ]);

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'domains' => [],
            ];
        }
    }

    /**
     * Step 3: Begin Enrollment (POST /affiliate/enrollment/begin)
     * Max 50 domains per call
     * 
     * @param array $domains [['domain' => 'example.com', 'senderPermutationOverride' => [...]]]
     * @param int $aliasCount Aliases per domain (1-10)
     */
    public function beginEnrollment(array $domains, int $aliasCount = 10): array
    {
        try {
            // Validation warning only - actual chunking happens in createMailboxes
            if (count($domains) > self::MAX_DOMAINS_ENROLLMENT) {
                Log::channel('mailin-ai')->warning('Mailrun: beginEnrollment called with > 50 domains! Should use createMailboxes for auto-chunking.', [
                    'count' => count($domains),
                    'max' => self::MAX_DOMAINS_ENROLLMENT,
                ]);
            }

            $payload = [
                'domains' => $domains,
                'aliasCountOverride' => min(10, max(1, $aliasCount)),
            ];

            if ($this->customerId) {
                $payload['customerId'] = $this->customerId;
            }

            // Log the exact payload being sent
            Log::channel('mailin-ai')->debug('Mailrun: beginEnrollment payload', [
                'domains_count' => count($domains),
                'aliasCountOverride' => $payload['aliasCountOverride'],
                'domains_detail' => collect($domains)->map(fn($d) => [
                    'domain' => $d['domain'] ?? 'unknown',
                    'senderCount' => count($d['senderPermutationOverride'] ?? []),
                    'senders' => array_slice($d['senderPermutationOverride'] ?? [], 0, 5), // Show first 5
                ])->toArray(),
            ]);

            $response = $this->makeRequest('POST', '/affiliate/enrollment/begin', $payload);

            if (!$response->successful()) {
                $error = $response->json('message') ?? $response->json('error') ?? $response->body();
                return [
                    'success' => false,
                    'message' => "Enrollment begin failed: {$error}",
                ];
            }

            $responseData = $response->json();

            // Try to extract UUID/ID from various possible response keys
            // Mailrun API may return the enrollment ID in different formats
            $uuid = $responseData['uuid']
                ?? $responseData['id']
                ?? $responseData['enrollmentId']
                ?? $responseData['enrollment_id']
                ?? $responseData['jobId']
                ?? $responseData['job_id']
                ?? ($responseData['data']['uuid'] ?? null)
                ?? ($responseData['data']['id'] ?? null)
                ?? ($responseData['data']['enrollmentId'] ?? null)
                ?? null;

            Log::channel('mailin-ai')->info('Mailrun: Enrollment initiated', [
                'domain_count' => count($domains),
                'extracted_uuid' => $uuid,
                'response_keys' => array_keys($responseData),
                'response_dump' => $responseData,
            ]);

            return [
                'success' => true,
                'message' => 'Enrollment initiated',
                'uuid' => $uuid,
                'data' => $responseData,
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Step 4: Check Enrollment Status (POST /affiliate/enrollment/status)
     */
    public function checkEnrollmentStatus(array $domains): array
    {
        try {
            $response = $this->makeRequest('POST', '/affiliate/enrollment/status', [
                'domains' => array_map(fn($d) => ['domain' => $d], $domains),
            ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Enrollment status check failed: ' . $response->body(),
                    'domains' => [],
                ];
            }

            $data = $response->json();

            Log::channel('mailin-ai')->debug('Mailrun: Raw enrollment status response', [
                'data' => $data,
            ]);

            $result = ['success' => true, 'domains' => []];

            // Parse response
            // Parse response
            $list = $data['domains'] ?? $data['domainNameservers'] ?? $data['data'] ?? null;

            // If the root data is a list (indexed array) containing domain objects
            if (!$list && is_array($data) && isset($data[0]) && (isset($data[0]['domain']) || isset($data[0]['status']))) {
                $list = $data;
            }

            if (is_array($list)) {
                foreach ($list as $domainData) {
                    if (is_array($domainData) && isset($domainData['domain'])) {
                        $domain = $domainData['domain'];
                        $result['domains'][$domain] = $domainData;
                    }
                }
            }

            // Fallback: iterate top level keys
            if (empty($result['domains']) && is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_string($key) && is_array($value)) {
                        $result['domains'][$key] = $value;
                    }
                }
            }

            Log::channel('mailin-ai')->info('Mailrun: Enrollment status retrieved', [
                'domains' => array_keys($result['domains']),
            ]);

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'domains' => [],
            ];
        }
    }

    /**
     * Step 5: Get Provisioned Emails (POST /affiliate/enrollment/provision)
     */
    public function getProvisionedEmails(array $domains, bool $csv = false, ?string $sequencer = null): array
    {
        try {
            $payload = [
                'domains' => array_map(fn($d) => ['domain' => $d], $domains),
            ];

            if ($csv && $sequencer) {
                $payload['csv'] = true;
                $payload['sequencer'] = $sequencer;
            }

            $response = $this->makeRequest('POST', '/affiliate/enrollment/provision', $payload);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Provision retrieval failed: ' . $response->body(),
                    'domains' => [],
                ];
            }

            $data = $response->json();

            Log::channel('mailin-ai')->debug('Mailrun: Raw provision response', [
                'data' => $data,
            ]);

            $result = ['success' => true, 'domains' => []];

            // Parse response - contains sensitive credentials
            // Parse response - contains sensitive credentials
            $list = $data['domains'] ?? $data['domainNameservers'] ?? $data['data'] ?? null;

            // If the root data is a list (indexed array) containing domain objects
            if (!$list && is_array($data) && isset($data[0]) && (isset($data[0]['domain']) || isset($data[0]['emails']))) {
                $list = $data;
            }

            if (is_array($list)) {
                foreach ($list as $item) {
                    // Case 1: Item is a domain container with emails list
                    if (isset($item['domain']) && (isset($item['emails']) || isset($item['mailboxes']))) {
                        $domain = $item['domain'];
                        $result['domains'][$domain] = [
                            'emails' => $item['emails'] ?? $item['mailboxes'] ?? [],
                        ];
                    }
                    // Case 2: Item is a single mailbox object (extract domain from email)
                    elseif (isset($item['email']) || isset($item['username'])) {
                        $email = $item['email'] ?? $item['username'];
                        $parts = explode('@', $email);
                        if (count($parts) === 2) {
                            $domain = $parts[1];
                            if (!isset($result['domains'][$domain]['emails'])) {
                                $result['domains'][$domain]['emails'] = [];
                            }

                            // Normalize keys for MailboxCreationService
                            $normalizedItem = array_merge($item, [
                                'password' => $item['password'] ?? $item['imapPwd'] ?? $item['smtpPwd'] ?? null,
                                'smtp_host' => $item['smtp_host'] ?? $item['smtpHost'] ?? null,
                                'smtp_port' => $item['smtp_port'] ?? $item['smtpPort'] ?? 587,
                                'imap_host' => $item['imap_host'] ?? $item['imapHost'] ?? null,
                                'imap_port' => $item['imap_port'] ?? $item['imapPort'] ?? 993,
                                'mailbox_id' => $item['id'] ?? null,
                            ]);

                            $result['domains'][$domain]['emails'][] = $normalizedItem;
                        }
                    }
                }
            }

            // Fallback
            if (empty($result['domains']) && is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_string($key) && is_array($value)) {
                        $result['domains'][$key] = [
                            'emails' => $value['emails'] ?? $value['mailboxes'] ?? $value,
                        ];
                    }
                }
            }

            Log::channel('mailin-ai')->info('Mailrun: Provisioned emails retrieved', [
                'domain_count' => count($result['domains']),
                // Don't log credentials!
            ]);

            return $result;

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'domains' => [],
            ];
        }
    }

    /**
     * Delete domain from Mailrun
     * Endpoint: /affiliate/enrollment/delete
     */
    public function deleteDomain(string $domain): array
    {
        try {
            Log::channel('mailin-ai')->info('Mailrun: Deleting domain via API', ['domain' => $domain]);

            $payload = [
                'domains' => [
                    ['domain' => $domain]
                ]
            ];

            // Use makeRequest to reuse auth and error handling
            // Endpoint is absolute URL in user request but let's see if makeRequest handles base url.
            // makeRequest appends endpoint to baseUrl.
            // User request: https://api.mailrun.ai/api/affiliate/enrollment/delete
            // Service BASE_URL: https://api.mailrun.ai/api
            // So endpoint should be /affiliate/enrollment/delete

            // However, makeRequest does: $url = rtrim($this->baseUrl, '/') . $endpoint;
            // So we pass '/affiliate/enrollment/delete'

            // Wait, we need to handle the response format specifically.
            // makeRequest returns a Laravel Response object.

            // But I cannot call makeRequest directly if it's private and I am inside the class :) yes I can.
            // But wait, the previous tool call failed because I targeted line 1196 which is '}'.

            $url = '/affiliate/enrollment/delete';
            // Start Request
            $response = $this->makeRequest('DELETE', $url, $payload);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'API request failed: ' . $response->body(),
                ];
            }

            $data = $response->json();

            Log::channel('mailin-ai')->info('Mailrun: Delete response', ['domain' => $domain, 'response' => $data]);

            // success is true in the response example
            return [
                'success' => true,
                'data' => $data,
                'message' => 'Deletion request processed',
            ];

        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailrun: Domain deletion failed', [
                'domain' => $domain,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Make authenticated API request
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): \Illuminate\Http\Client\Response
    {
        $url = rtrim($this->baseUrl, '/') . $endpoint;
        $maxRetries = 3;
        $retryDelay = 2000; // start with 2 seconds

        Log::channel('mailin-ai')->debug('Mailrun: API request', [
            'method' => $method,
            'endpoint' => $endpoint,
            'has_data' => !empty($data),
        ]);

        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->retry($maxRetries, $retryDelay, function ($exception, $request) {
                // Retry on 429 Too Many Requests or 5xx Server Errors
                if ($exception instanceof \Illuminate\Http\Client\RequestException && $exception->response) {
                    $status = $exception->response->status();
                    if ($status === 429) {
                        Log::channel('mailin-ai')->warning('Mailrun: Rate limit hit (429), retrying...');
                        return true;
                    }
                    return $status >= 500;
                }
                return false;
            })
            ->send($method, $url, ['json' => $data]);
    }
}
