<?php

namespace App\Services\Providers;

use App\Contracts\Providers\SmtpProviderInterface;
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
            $isComplete = ($domainStatus['status'] ?? '') === 'complete'
                || ($domainStatus['provisioned'] ?? false) === true;

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
                        $result['domains'][$domain] = [
                            'nameservers' => $domainData['nameservers'] ?? $domainData['ns'] ?? [],
                        ];
                    }
                }
            }

            // Fallback: iterate top level keys if we didn't extract anything from a list
            if (empty($result['domains']) && is_array($data)) {
                foreach ($data as $key => $value) {
                    if (is_string($key) && is_array($value)) {
                        $result['domains'][$key] = [
                            'nameservers' => $value['nameservers'] ?? $value['ns'] ?? $value,
                        ];
                    }
                }
            }

            Log::channel('mailin-ai')->info('Mailrun: Nameservers retrieved', [
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

            $response = $this->makeRequest('POST', '/affiliate/enrollment/begin', $payload);

            if (!$response->successful()) {
                $error = $response->json('message') ?? $response->json('error') ?? $response->body();
                return [
                    'success' => false,
                    'message' => "Enrollment begin failed: {$error}",
                ];
            }

            Log::channel('mailin-ai')->info('Mailrun: Enrollment initiated', [
                'domain_count' => count($domains),
                'response_dump' => $response->json(),
            ]);

            return [
                'success' => true,
                'message' => 'Enrollment initiated',
                'uuid' => $response->json('uuid') ?? $response->json('id'),
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
            $result = ['success' => true, 'domains' => []];

            // Parse response
            if (isset($data['domains'])) {
                foreach ($data['domains'] as $domainData) {
                    $domain = $domainData['domain'] ?? '';
                    $result['domains'][$domain] = $domainData;
                }
            } elseif (is_array($data)) {
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
            $result = ['success' => true, 'domains' => []];

            // Parse response - contains sensitive credentials
            if (isset($data['domains'])) {
                foreach ($data['domains'] as $domainData) {
                    $domain = $domainData['domain'] ?? '';
                    $result['domains'][$domain] = [
                        'emails' => $domainData['emails'] ?? $domainData['mailboxes'] ?? [],
                    ];
                }
            } elseif (is_array($data)) {
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
