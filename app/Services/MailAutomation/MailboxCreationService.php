<?php

namespace App\Services\MailAutomation;

use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Models\SmtpProviderSplit;
use App\Services\Providers\CreatesProviders;
use Illuminate\Support\Facades\Log;

/**
 * Service for mailbox creation and storage in JSON column
 */
class MailboxCreationService
{
    use CreatesProviders;

    /**
     * Create mailboxes for all splits in an order
     * 
     * @param Order $order
     * @param array $prefixVariants
     * @param array $prefixVariantsDetails
     * @return array ['success' => bool, 'error' => string|null, 'results' => array]
     */
    public function createMailboxesForOrder(
        Order $order,
        array $prefixVariants,
        array $prefixVariantsDetails,
        bool $force = false
    ): array {
        Log::channel('mailin-ai')->info('Mailin createMailboxesForOrder: started', [
            'order_id' => $order->id,
            'force' => $force,
            'prefix_variants_count' => count($prefixVariants),
        ]);

        // GATE: Check ALL domains across ALL splits are active (skip when force=true)
        $allActive = OrderProviderSplit::areAllDomainsActiveForOrder($order->id);
        if (!$allActive && !$force) {
            Log::channel('mailin-ai')->warning('Mailin createMailboxesForOrder: aborted - not all domains active', [
                'order_id' => $order->id,
                'reason' => 'Run mailin:activate-domains first or use --force',
            ]);
            return [
                'success' => false,
                'error' => 'Not all domains are active. Run mailin:activate-domains first.',
                'results' => [],
            ];
        }

        $splits = OrderProviderSplit::where('order_id', $order->id)->get();
        Log::channel('mailin-ai')->info('Mailin createMailboxesForOrder: splits loaded', [
            'order_id' => $order->id,
            'splits_count' => $splits->count(),
            'split_ids' => $splits->pluck('id')->toArray(),
            'provider_slugs' => $splits->pluck('provider_slug')->toArray(),
        ]);

        $allResults = [];
        $totalCreated = 0;
        $totalFailed = 0;
        $totalPending = 0;

        foreach ($splits as $split) {
            // New Logic: If force is true, only process splits that are fully active
            // Skip splits that are not active to avoid errors
            if ($force && !$split->all_domains_active) {
                Log::channel('mailin-ai')->info('Mailin createMailboxesForOrder: skipping inactive split (force mode)', [
                    'order_id' => $order->id,
                    'split_id' => $split->id,
                    'provider_slug' => $split->provider_slug,
                    'all_domains_active' => $split->all_domains_active,
                ]);
                continue;
            }

            $result = $this->createMailboxesForSplit($order, $split, $prefixVariants, $prefixVariantsDetails);
            $allResults[$split->provider_slug] = $result;
            $totalCreated += count($result['created'] ?? []);
            $totalFailed += count($result['failed'] ?? []);
            $totalPending += count($result['pending'] ?? []);
        }

        // Complete order if all mailboxes created (no failures AND no pending)
        if ($totalFailed === 0 && $totalPending === 0) {
            // Use new validation function to verify all expected mailboxes exist
            $validation = $this->validateOrderMailboxCompletion($order, $prefixVariants);

            if ($validation['is_complete']) {
                if ($order->status_manage_by_admin !== 'completed') {
                    $order->update([
                        'status_manage_by_admin' => 'completed',
                        'completed_at' => now(),
                    ]);
                }

                Log::channel('mailin-ai')->info('Order completed after mailbox creation', [
                    'order_id' => $order->id,
                    'total_mailboxes' => $validation['total_created'],
                    'expected_mailboxes' => $validation['total_expected'],
                ]);
            } else {
                // Mailboxes missing - don't mark as complete
                Log::channel('mailin-ai')->warning('Order has missing mailboxes - not completing', [
                    'order_id' => $order->id,
                    'total_created' => $validation['total_created'],
                    'total_expected' => $validation['total_expected'],
                    'pending_count' => $validation['pending_count'],
                    'pending_mailboxes' => array_map(fn($m) => $m['email'], $validation['pending_mailboxes']),
                ]);
                $totalPending = $validation['pending_count'];
            }
        } elseif ($totalPending > 0) {
            Log::channel('mailin-ai')->info('Order incomplete, pending mailboxes (async enrollment)', [
                'order_id' => $order->id,
                'pending_count' => $totalPending,
                'created_count' => $totalCreated,
            ]);
        }

        return [
            'success' => $totalFailed === 0 && $totalPending === 0, // Success only if FULLY complete
            'error' => $totalFailed > 0 ? "Failed: $totalFailed" : ($totalPending > 0 ? "Pending: $totalPending" : null),
            'results' => $allResults,
            'total_created' => $totalCreated,
            'total_failed' => $totalFailed,
            'total_pending' => $totalPending,
        ];
    }

    /**
     * Create mailboxes for a single provider split
     */
    public function createMailboxesForSplit(
        Order $order,
        OrderProviderSplit $split,
        array $prefixVariants,
        array $prefixVariantsDetails
    ): array {
        // Check if PremiumInboxes
        if ($split->provider_slug === 'premiuminboxes') {
            return $this->fetchMailboxesFromPremiumInboxes($order, $split, $prefixVariants);
        }

        // Check if Mailrun (async enrollment)
        if ($split->provider_slug === 'mailrun') {
            return $this->createMailboxesForMailrun($order, $split, $prefixVariants, $prefixVariantsDetails);
        }

        // Existing Mailin.ai logic
        $results = [
            'created' => [],
            'failed' => [],
        ];

        Log::channel('mailin-ai')->info('Mailin createMailboxesForSplit: started', [
            'order_id' => $order->id,
            'split_id' => $split->id,
            'provider_slug' => $split->provider_slug,
            'domains' => $split->domains ?? [],
            'domains_count' => count($split->domains ?? []),
            'prefix_variants' => array_values($prefixVariants),
        ]);

        // Get provider credentials
        $providerConfig = SmtpProviderSplit::getBySlug($split->provider_slug);
        if (!$providerConfig) {
            Log::channel('mailin-ai')->error('Mailin createMailboxesForSplit: provider config not found', [
                'order_id' => $order->id,
                'provider_slug' => $split->provider_slug,
            ]);
            return $results;
        }

        $credentials = $providerConfig->getCredentials();
        $provider = $this->createProvider($split->provider_slug, $credentials);

        if (!$provider->authenticate()) {
            Log::channel('mailin-ai')->error('Mailin createMailboxesForSplit: provider auth failed', [
                'order_id' => $order->id,
                'provider_slug' => $split->provider_slug,
            ]);
            return $results;
        }

        // Process each domain with delay between domains to avoid Mailin.ai rate limits
        $delayBetweenDomains = (int) config('mailin_ai.mailbox_creation_delay_between_domains', 3);
        $domains = $split->domains ?? [];
        $domainIndex = 0;

        foreach ($domains as $domain) {
            if ($domainIndex > 0 && $delayBetweenDomains > 0) {
                sleep($delayBetweenDomains);
            }
            $domainIndex++;

            $domainResult = $this->createMailboxesForDomain(
                $order,
                $split,
                $domain,
                $prefixVariants,
                $prefixVariantsDetails,
                $provider
            );

            $results['created'] = array_merge($results['created'], $domainResult['created']);
            $results['failed'] = array_merge($results['failed'], $domainResult['failed']);
        }

        return $results;
    }

    /**
     * For PremiumInboxes, mailboxes are already created
     * We just need to fetch them from the order
     */
    private function fetchMailboxesFromPremiumInboxes(
        Order $order,
        OrderProviderSplit $split,
        array $prefixVariants
    ): array {
        $results = [
            'created' => [],
            'failed' => [],
        ];

        if (!$split->external_order_id) {
            Log::channel('mailin-ai')->error('PremiumInboxes order ID not found', [
                'order_id' => $order->id,
                'split_id' => $split->id,
            ]);
            return $results;
        }

        $providerConfig = SmtpProviderSplit::getBySlug('premiuminboxes');
        if (!$providerConfig) {
            Log::channel('mailin-ai')->error('PremiumInboxes provider not found', [
                'order_id' => $order->id,
            ]);
            return $results;
        }

        $credentials = $providerConfig->getCredentials();
        $provider = $this->createProvider('premiuminboxes', $credentials);
        $provider->setOrderId($split->external_order_id);

        if (!$provider->authenticate()) {
            Log::channel('mailin-ai')->error('PremiumInboxes authentication failed', [
                'order_id' => $order->id,
            ]);
            return $results;
        }

        // Check order status from split
        if ($split->order_status !== 'active') {
            Log::channel('mailin-ai')->warning('PremiumInboxes order not active yet', [
                'order_id' => $order->id,
                'premiuminboxes_order_id' => $split->external_order_id,
                'status' => $split->order_status ?? 'unknown',
            ]);
            return $results;
        }

        // Fetch mailboxes for each domain
        foreach ($split->domains ?? [] as $domain) {
            $mailboxesResult = $provider->getMailboxesByDomain($domain);

            if ($mailboxesResult['success']) {
                foreach ($mailboxesResult['mailboxes'] as $mailbox) {
                    // Extract prefix key from email (e.g., "john@domain.com" -> "john")
                    $emailPrefix = explode('@', $mailbox['email'])[0] ?? '';
                    $prefixKey = $this->findPrefixKey($emailPrefix, $prefixVariants);

                    // Map to our format
                    $mailboxData = [
                        'id' => $mailbox['id'],
                        'name' => $emailPrefix, // Use prefix as name
                        'mailbox' => $mailbox['email'],
                        'password' => $mailbox['password'] ?? null,
                        'status' => $mailbox['status'] ?? 'active',
                        'smtp_host' => $mailbox['smtp_host'] ?? null,
                        'smtp_port' => $mailbox['smtp_port'] ?? null,
                        'imap_host' => $mailbox['imap_host'] ?? null,
                        'imap_port' => $mailbox['imap_port'] ?? null,
                    ];

                    // Store in split
                    $split->addMailbox($domain, $prefixKey, $mailboxData);

                    $results['created'][] = $mailbox['email'];
                }

                Log::channel('mailin-ai')->info('PremiumInboxes mailboxes fetched for domain', [
                    'order_id' => $order->id,
                    'domain' => $domain,
                    'mailbox_count' => count($mailboxesResult['mailboxes']),
                ]);
            } else {
                $results['failed'][] = [
                    'domain' => $domain,
                    'error' => $mailboxesResult['message'] ?? 'Failed to fetch mailboxes',
                ];
            }
        }

        return $results;
    }

    /**
     * Create mailboxes for Mailrun provider
     * Mailrun uses async enrollment - can take up to 2 hours
     * 
     * Flow:
     * 1. Check if enrollment already started (enrollment_uuid stored)
     * 2. If not, start enrollment via createMailboxes()
     * 3. Poll status via getMailboxesByDomain() which checks status first
     * 4. If complete, fetch credentials and store
     */
    private function createMailboxesForMailrun(
        Order $order,
        OrderProviderSplit $split,
        array $prefixVariants,
        array $prefixVariantsDetails
    ): array {
        $results = [
            'created' => [],
            'failed' => [],
            'pending' => [],
        ];

        $providerConfig = SmtpProviderSplit::getBySlug('mailrun');
        if (!$providerConfig) {
            Log::channel('mailin-ai')->error('Mailrun provider not found');
            return $results;
        }

        $credentials = $providerConfig->getCredentials();
        $provider = $this->createProvider('mailrun', $credentials);

        if (!$provider->authenticate()) {
            Log::channel('mailin-ai')->error('Mailrun authentication failed');
            return $results;
        }

        // Build mailbox list for all domains
        $allMailboxes = [];
        foreach ($split->domains ?? [] as $domain) {
            foreach ($prefixVariants as $prefixKey => $prefix) {
                $variantKey = is_numeric($prefixKey) ? 'prefix_variant_' . ($prefixKey + 1) : $prefixKey;
                $details = $prefixVariantsDetails[$variantKey] ?? [];

                $firstName = trim($details['first_name'] ?? '');
                $lastName = trim($details['last_name'] ?? '');
                $name = trim($firstName . ' ' . $lastName) ?: $prefix;

                $allMailboxes[] = [
                    'username' => $prefix . '@' . $domain,
                    'name' => $name,
                    'password' => $this->generatePassword($order->id),
                ];
            }
        }

        // Check if enrollment already initiated (uuid or timestamp stored)
        $enrollmentUuid = $split->getMetadata('mailrun_enrollment_uuid');
        $enrollmentStartedAt = $split->getMetadata('mailrun_enrollment_started_at');

        if (!$enrollmentUuid && !$enrollmentStartedAt) {
            // IMPORTANT: Check if domains are already enrolled in Mailrun from previous orders
            // If they have different prefixes, we'll attempt to re-enroll with the new prefixes
            $domainsToEnroll = [];
            $domainsNeedingReEnrollment = [];
            $activeDomains = [];

            // Batch check status for all domains (chunked to 20 to avoid API limit)
            $domainStatuses = [];
            $statusChunks = array_chunk($split->domains ?? [], 20);
            foreach ($statusChunks as $i => $chunk) {
                if ($i > 0) {
                    sleep(13); // Proactive rate limit spacing (5 req/min)
                }
                $batchStatusResult = $provider->checkDomainStatus($chunk);
                if ($batchStatusResult['success']) {
                    $domainStatuses = array_merge($domainStatuses, $batchStatusResult['results'] ?? []);
                }
            }

            foreach ($split->domains ?? [] as $domain) {
                $statusResult = $domainStatuses[$domain] ?? ['success' => false, 'is_active' => false];
                // Map 'active' status to is_active boolean if not already present
                if (!isset($statusResult['is_active']) && ($statusResult['status'] ?? '') === 'active') {
                    $statusResult['is_active'] = true;
                }

                if ($statusResult['success'] && ($statusResult['is_active'] ?? false)) {
                    $activeDomains[] = $domain;
                } else {
                    // Domain not active - safe to enroll
                    $domainsToEnroll[] = $domain;
                }
            }

            // Batch fetch mailboxes for all active domains (instead of per-domain calls)
            $activeDomainMailboxes = [];
            if (!empty($activeDomains)) {
                // Step 1: Batch check enrollment status
                $enrollmentStatuses = [];
                $enrollChunks = array_chunk($activeDomains, 20);
                foreach ($enrollChunks as $i => $chunk) {
                    if ($i > 0) {
                        sleep(13); // Proactive rate limit spacing
                    }
                    $esResult = $provider->checkEnrollmentStatus($chunk);
                    if ($esResult['success']) {
                        $enrollmentStatuses = array_merge($enrollmentStatuses, $esResult['domains'] ?? []);
                    }
                }

                // Step 2: Identify completed domains and batch fetch provisioned emails
                $completedActiveDomains = [];
                foreach ($activeDomains as $domain) {
                    $ds = $enrollmentStatuses[$domain] ?? null;
                    $st = strtolower($ds['status'] ?? '');
                    $isComplete = $st === 'complete' || $st === 'success'
                        || ($ds['provisioned'] ?? false) === true
                        || ($ds['enrollmentStep'] ?? '') === 'SetupComplete';
                    if ($isComplete) {
                        $completedActiveDomains[] = $domain;
                    } else {
                        // Not yet provisioned - still safe to enroll
                        $domainsToEnroll[] = $domain;
                    }
                }

                if (!empty($completedActiveDomains)) {
                    $provChunks = array_chunk($completedActiveDomains, 20);
                    foreach ($provChunks as $i => $chunk) {
                        if ($i > 0) {
                            sleep(13);
                        }
                        $provResult = $provider->getProvisionedEmails($chunk);
                        if ($provResult['success']) {
                            $activeDomainMailboxes = array_merge($activeDomainMailboxes, $provResult['domains'] ?? []);
                        }
                    }
                }
            }

            // Now process results locally (no API calls)
            foreach ($activeDomains ?? [] as $domain) {
                $domainEmails = $activeDomainMailboxes[$domain]['emails'] ?? [];
                if (!empty($domainEmails)) {
                    // Domain has existing mailboxes - check if they match our prefixes
                    $existingPrefixes = [];
                    foreach ($domainEmails as $email) {
                        $emailAddr = $email['email'] ?? $email['address'] ?? '';
                        $existingPrefixes[] = explode('@', $emailAddr)[0] ?? '';
                    }

                    $expectedPrefixes = array_values($prefixVariants);
                    // Case-insensitive comparison (Mailrun API returns lowercase prefixes)
                    $matchCount = count(array_intersect(
                        array_map('strtolower', $existingPrefixes),
                        array_map('strtolower', $expectedPrefixes)
                    ));

                    if ($matchCount < count($expectedPrefixes)) {
                        // Mailboxes don't match - attempt re-enrollment with new prefixes
                        Log::channel('mailin-ai')->warning('Mailrun: Domain enrolled with different prefixes - attempting re-enrollment', [
                            'domain' => $domain,
                            'existing_prefixes' => $existingPrefixes,
                            'requested_prefixes' => $expectedPrefixes,
                            'order_id' => $order->id,
                        ]);
                        $domainsNeedingReEnrollment[$domain] = $existingPrefixes;
                        $domainsToEnroll[] = $domain;
                    } else {
                        // Mailboxes match - just fetch them, no need to re-enroll
                        Log::channel('mailin-ai')->info('Mailrun: Domain already enrolled with matching prefixes', [
                            'domain' => $domain,
                        ]);
                    }
                } else {
                    // Domain is active but no mailboxes yet - safe to enroll
                    $domainsToEnroll[] = $domain;
                }
            }

            // Log re-enrollment attempts
            if (!empty($domainsNeedingReEnrollment)) {
                Log::channel('mailin-ai')->info('Mailrun: Attempting to re-enroll domains with new prefixes', [
                    'order_id' => $order->id,
                    'domains_to_reenroll' => array_keys($domainsNeedingReEnrollment),
                    'requested_prefixes' => array_values($prefixVariants),
                ]);
            }

            // Only enroll domains that are safe to enroll
            if (!empty($domainsToEnroll)) {
                // Filter allMailboxes to only include domains we can enroll
                $mailboxesToCreate = array_filter($allMailboxes, function ($mb) use ($domainsToEnroll) {
                    $email = $mb['username'] ?? '';
                    $domain = explode('@', $email)[1] ?? '';
                    return in_array($domain, $domainsToEnroll);
                });

                if (!empty($mailboxesToCreate)) {
                    $enrollResult = $provider->createMailboxes(array_values($mailboxesToCreate));

                    if (!$enrollResult['success']) {
                        Log::channel('mailin-ai')->error('Mailrun enrollment failed', [
                            'order_id' => $order->id,
                            'error' => $enrollResult['message'] ?? 'Unknown error',
                        ]);
                        $results['failed'] = array_merge($results['failed'], array_column($mailboxesToCreate, 'username'));
                        return $results;
                    }

                    // Store enrollment UUID for later polling
                    $enrollmentUuid = $enrollResult['uuid'] ?? null;
                    if ($enrollmentUuid) {
                        $split->setMetadata('mailrun_enrollment_uuid', $enrollmentUuid);
                    }

                    // Mark as started even if UUID missing to prevent infinite loops
                    $split->setMetadata('mailrun_enrollment_started_at', now()->toISOString());

                    Log::channel('mailin-ai')->info('Mailrun enrollment initiated', [
                        'order_id' => $order->id,
                        'uuid' => $enrollmentUuid,
                        'mailbox_count' => count($mailboxesToCreate),
                        'domains' => $domainsToEnroll,
                    ]);

                    // Enrollment is async - mark as pending, scheduler will poll
                    $results['pending'] = array_column($mailboxesToCreate, 'username');
                }
            }

            // If we have failures but nothing pending, return early
            if (!empty($results['failed']) && empty($results['pending'])) {
                return $results;
            }

            // If everything was already enrolled (with wrong prefixes), return
            // Note: now we attempt re-enrollment, so this check isn't needed
            // But keep for safety in case domainsToEnroll is empty for other reasons

            // If we started enrollment, return pending results
            if (!empty($results['pending'])) {
                return $results;
            }
        }

        // Enrollment already started - BATCH check status and fetch mailboxes
        // Instead of per-domain API calls (which hit rate limits), we batch:
        // 1. checkEnrollmentStatus for all domains at once
        // 2. getProvisionedEmails for all completed domains at once
        $allDomains = $split->domains ?? [];
        $completedDomains = [];
        $expectedPrefixes = array_values($prefixVariants);

        // Step 1: Batch check enrollment status (chunked to 20 to respect API limits)
        $allEnrollmentStatuses = [];
        $enrollmentChunks = array_chunk($allDomains, 20);
        foreach ($enrollmentChunks as $i => $chunk) {
            if ($i > 0) {
                sleep(13); // Proactive rate limit spacing (5 req/min)
            }
            $statusResult = $provider->checkEnrollmentStatus($chunk);
            if ($statusResult['success']) {
                $allEnrollmentStatuses = array_merge($allEnrollmentStatuses, $statusResult['domains'] ?? []);
            } else {
                Log::channel('mailin-ai')->warning('Mailrun: Batch enrollment status check failed', [
                    'chunk_count' => count($chunk),
                    'message' => $statusResult['message'] ?? 'Unknown error',
                ]);
            }
        }

        // Separate completed vs pending domains
        foreach ($allDomains as $domain) {
            $domainStatus = $allEnrollmentStatuses[$domain] ?? null;
            $status = strtolower($domainStatus['status'] ?? '');
            $isComplete = $status === 'complete' || $status === 'success'
                || ($domainStatus['provisioned'] ?? false) === true
                || ($domainStatus['enrollmentStep'] ?? '') === 'SetupComplete';

            if ($isComplete) {
                $completedDomains[] = $domain;
            } else {
                Log::channel('mailin-ai')->info('Mailrun: Enrollment still pending', [
                    'domain' => $domain,
                    'status' => $domainStatus['status'] ?? 'pending',
                ]);
                $results['pending'][] = $domain;
            }
        }

        // Step 2: Batch fetch provisioned emails for all completed domains
        if (!empty($completedDomains)) {
            $allProvisionedEmails = [];
            $provisionChunks = array_chunk($completedDomains, 20);
            foreach ($provisionChunks as $i => $chunk) {
                if ($i > 0) {
                    sleep(13); // Proactive rate limit spacing (5 req/min)
                }
                $provisionResult = $provider->getProvisionedEmails($chunk);
                if ($provisionResult['success']) {
                    $allProvisionedEmails = array_merge($allProvisionedEmails, $provisionResult['domains'] ?? []);
                } else {
                    Log::channel('mailin-ai')->warning('Mailrun: Batch provision fetch failed', [
                        'chunk_count' => count($chunk),
                        'message' => $provisionResult['message'] ?? 'Unknown error',
                    ]);
                }
            }

            Log::channel('mailin-ai')->info('Mailrun: Batch fetched provisioned emails', [
                'completed_domains' => count($completedDomains),
                'provisioned_domains' => count($allProvisionedEmails),
            ]);

            // Step 3: Process results per-domain (no API calls needed)
            foreach ($completedDomains as $domain) {
                $domainEmails = $allProvisionedEmails[$domain]['emails'] ?? [];

                // Format mailboxes for compatibility
                $mailboxes = [];
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

                if (empty($mailboxes)) {
                    $results['pending'][] = $domain;
                    continue;
                }

                // Validate returned prefixes match requested ones
                $returnedPrefixes = [];
                foreach ($mailboxes as $mb) {
                    $email = $mb['email'] ?? $mb['username'] ?? '';
                    $returnedPrefixes[] = explode('@', $email)[0] ?? '';
                }

                // Check for prefix mismatch
                $matchedPrefixes = array_intersect(
                    array_map('strtolower', $returnedPrefixes),
                    array_map('strtolower', $expectedPrefixes)
                );
                $unmatchedReturned = array_diff(
                    array_map('strtolower', $returnedPrefixes),
                    array_map('strtolower', $expectedPrefixes)
                );
                $missingExpected = array_diff(
                    array_map('strtolower', $expectedPrefixes),
                    array_map('strtolower', $returnedPrefixes)
                );
                $hasCompleteMismatch = empty($matchedPrefixes) && !empty($unmatchedReturned);

                if (!empty($unmatchedReturned) || !empty($missingExpected)) {
                    Log::channel('mailin-ai')->warning('Mailrun: Prefix mismatch detected!', [
                        'domain' => $domain,
                        'expected_prefixes' => $expectedPrefixes,
                        'returned_prefixes' => $returnedPrefixes,
                        'complete_mismatch' => $hasCompleteMismatch,
                    ]);

                    if ($hasCompleteMismatch) {
                        $deleteResult = $provider->deleteDomain($domain);
                        if (!$deleteResult['success']) {
                            $results['pending'][] = $domain;
                            continue;
                        }
                        $split->setMetadata('mailrun_enrollment_uuid', null);
                        $split->setMetadata('mailrun_enrollment_started_at', null);
                        $split->setDomainStatus($domain, 'pending');
                        Log::channel('mailin-ai')->info('Mailrun: Domain deleted and reset for re-enrollment', ['domain' => $domain]);
                        $results['pending'][] = $domain;
                        continue;
                    }
                }

                // Store only mailboxes with MATCHING prefixes
                $domainVariantCounter = 1;
                $usedKeys = [];
                $savedCount = 0;
                $skippedCount = 0;

                foreach ($mailboxes as $mailbox) {
                    $emailPrefix = explode('@', $mailbox['email'] ?? $mailbox['username'] ?? '')[0] ?? '';

                    if (!in_array(strtolower($emailPrefix), array_map('strtolower', $expectedPrefixes))) {
                        $skippedCount++;
                        continue;
                    }

                    $prefixKey = $this->findPrefixKey($emailPrefix, $prefixVariants);

                    if (isset($usedKeys[$prefixKey])) {
                        $prefixKey = 'prefix_variant_' . $domainVariantCounter;
                    }
                    $usedKeys[$prefixKey] = true;
                    $domainVariantCounter++;

                    $mailboxData = [
                        'id' => count($results['created']) + 1,
                        'name' => $mailbox['name'] ?? $emailPrefix,
                        'mailbox' => $mailbox['email'] ?? $mailbox['username'] ?? '',
                        'password' => $mailbox['password'] ?? '',
                        'status' => 'active',
                        'mailbox_id' => $mailbox['id'] ?? null,
                        'smtp_host' => $mailbox['smtp_host'] ?? '',
                        'smtp_port' => $mailbox['smtp_port'] ?? 587,
                        'imap_host' => $mailbox['imap_host'] ?? '',
                        'imap_port' => $mailbox['imap_port'] ?? 993,
                    ];

                    $split->addMailbox($domain, $prefixKey, $mailboxData);
                    $results['created'][] = $mailboxData['mailbox'];
                    $savedCount++;

                    Log::channel('mailin-ai')->debug('Mailrun: Stored mailbox', [
                        'domain' => $domain,
                        'email' => $mailboxData['mailbox'],
                        'prefixKey' => $prefixKey,
                    ]);
                }

                Log::channel('mailin-ai')->info('Mailrun mailboxes stored', [
                    'domain' => $domain,
                    'saved_count' => $savedCount,
                    'skipped_count' => $skippedCount,
                    'expected_count' => count($prefixVariants),
                ]);

                if ($savedCount < count($prefixVariants)) {
                    $results['pending'][] = $domain;
                }
            }
        }

        return $results;
    }

    /**
     * Find prefix key from email prefix
     * 
     * Returns the variant key (e.g., 'prefix_variant_1') if exact match found,
     * otherwise returns a unique key based on the email prefix to prevent overwrites.
     */
    private function findPrefixKey(string $emailPrefix, array $prefixVariants): string
    {
        // Try to find exact match
        foreach ($prefixVariants as $key => $prefix) {
            if (is_numeric($key)) {
                $variantKey = 'prefix_variant_' . ($key + 1);
            } else {
                $variantKey = $key;
            }

            if ($prefix === $emailPrefix) {
                return $variantKey;
            }
        }

        // If no exact match, use the first variant key or default
        if (!empty($prefixVariants)) {
            $firstKey = array_key_first($prefixVariants);
            return is_numeric($firstKey) ? 'prefix_variant_' . ($firstKey + 1) : $firstKey;
        }

        return 'prefix_variant_1';
    }

    /**
     * Create mailboxes for a single domain
     */
    private function createMailboxesForDomain(
        Order $order,
        OrderProviderSplit $split,
        string $domain,
        array $prefixVariants,
        array $prefixVariantsDetails,
        $provider
    ): array {
        $mailboxes = [];
        $mailboxDataMap = [];
        $index = 0;

        // Generate mailbox data
        foreach ($prefixVariants as $prefixKey => $prefix) {
            $index++;
            $variantKey = is_numeric($prefixKey) ? 'prefix_variant_' . ($prefixKey + 1) : $prefixKey;
            $details = $prefixVariantsDetails[$variantKey] ?? [];

            $firstName = trim($details['first_name'] ?? '');
            $lastName = trim($details['last_name'] ?? '');
            $name = trim($firstName . ' ' . $lastName) ?: $prefix;
            $username = $prefix . '@' . $domain;
            $password = $this->generatePassword($order->id);

            $mailboxes[] = [
                'username' => $username,
                'name' => $name,
                'password' => $password,
            ];

            $mailboxDataMap[$prefixKey] = [
                'id' => $index,
                'name' => $name,
                'mailbox' => $username,
                'password' => $password,
                'status' => 'pending',
            ];
        }

        $expectedEmails = array_column($mailboxes, 'username');
        Log::channel('mailin-ai')->info('Mailin createMailboxesForDomain: starting', [
            'order_id' => $order->id,
            'split_id' => $split->id,
            'domain' => $domain,
            'provider_slug' => $split->provider_slug,
            'expected_emails' => $expectedEmails,
            'expected_count' => count($expectedEmails),
        ]);

        // Call provider API
        try {
            $apiResult = $provider->createMailboxes($mailboxes);
        } catch (\Exception $e) {
            Log::channel('mailin-ai')->error('Mailin createMailboxesForDomain: API exception', [
                'order_id' => $order->id,
                'domain' => $domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'created' => [],
                'failed' => array_column($mailboxes, 'username'),
            ];
        }

        Log::channel('mailin-ai')->info('Mailin createMailboxesForDomain: createMailboxes API response', [
            'order_id' => $order->id,
            'domain' => $domain,
            'api_success' => $apiResult['success'] ?? false,
            'api_message' => $apiResult['message'] ?? $apiResult['error'] ?? null,
            'domain_not_registered' => $apiResult['domain_not_registered'] ?? false,
        ]);

        if (!$apiResult['success']) {
            // Handle specific case where domain is not registered
            if (($apiResult['domain_not_registered'] ?? false) === true) {
                Log::channel('mailin-ai')->warning('MailboxCreationService: Domain not registered, marking as inactive. Please retry manually or wait for auto-run in 5 minutes.', [
                    'domain' => $domain,
                    'order_id' => $order->id,
                    'split_id' => $split->id
                ]);

                // Update domain status to inactive
                $split->setDomainStatus($domain, 'inactive');

                // Set all_domains_active to 0 (false)
                $split->all_domains_active = false;
                $split->save();
            }

            Log::channel('mailin-ai')->error('Mailbox creation failed', [
                'domain' => $domain,
                'error' => $apiResult['message'] ?? $apiResult['error'] ?? 'Unknown error',
            ]);

            return [
                'created' => [],
                'failed' => array_column($mailboxes, 'username'),
            ];
        }

        // Fetch mailbox IDs from provider after creation with retry
        // We limit this to ~15 seconds per domain to prevent web timeouts.
        // If IDs are not found, they will be marked as PENDING and retry via background scheduler.
        $maxRetries = 5;
        $retryCount = 0;
        $createdMailboxes = [];

        do {
            if ($retryCount > 0) {
                // Wait 3 seconds before retry
                sleep(3);
            }

            $createdMailboxes = $provider->getMailboxesByDomain($domain);
            $foundCount = count($createdMailboxes['mailboxes'] ?? []);

            Log::channel('mailin-ai')->info('Mailin createMailboxesForDomain: getMailboxesByDomain response', [
                'order_id' => $order->id,
                'domain' => $domain,
                'attempt' => $retryCount + 1,
                'found_count' => $foundCount,
                'expected_at_least' => count($mailboxes),
                'api_success' => $createdMailboxes['success'] ?? null,
                'api_emails_sample' => array_slice(array_map(function ($mb) {
                    return $mb['email'] ?? $mb['username'] ?? null;
                }, $createdMailboxes['mailboxes'] ?? []), 0, 5),
            ]);

            // Wait until we find at least as many mailboxes as we just created
            // This prevents partial ID saving if the API returns them one by one
            if ($foundCount >= count($mailboxes)) {
                break;
            }

            Log::channel('mailin-ai')->info('Mailin createMailboxesForDomain: waiting for mailboxes to appear in API', [
                'order_id' => $order->id,
                'domain' => $domain,
                'attempt' => $retryCount + 1,
                'found_count' => $foundCount,
                'expected_at_least' => count($mailboxes),
            ]);

            $retryCount++;
        } while ($retryCount < $maxRetries);

        $mailboxIdMap = [];

        if (!empty($createdMailboxes['mailboxes'])) {
            foreach ($createdMailboxes['mailboxes'] as $mb) {
                $email = $mb['email'] ?? $mb['username'] ?? '';
                // Use lowercase for reliable matching
                $mailboxIdMap[strtolower($email)] = $mb;
            }
        }

        Log::channel('mailin-ai')->info('Mailin createMailboxesForDomain: mailboxIdMap built', [
            'order_id' => $order->id,
            'domain' => $domain,
            'map_keys_count' => count($mailboxIdMap),
            'map_keys_lowercase' => array_keys($mailboxIdMap),
            'expected_emails_lowercase' => array_map('strtolower', $expectedEmails),
            'match_hint' => 'Lookup uses strtolower(data[mailbox]); if API returns different email format/case, lookup fails.',
        ]);

        $successfulMailboxes = [];
        $pendingMailboxes = [];
        $storeChecks = [];

        // Store in JSON column with mailbox IDs
        foreach ($mailboxDataMap as $prefixKey => $data) {
            $targetEmail = strtolower($data['mailbox']);
            $mailboxInfo = $mailboxIdMap[$targetEmail] ?? null;
            $mailboxId = $mailboxInfo['id'] ?? null;
            $inMap = array_key_exists($targetEmail, $mailboxIdMap);

            $storeChecks[] = [
                'prefix_key' => $prefixKey,
                'expected_email' => $data['mailbox'],
                'target_email_lower' => $targetEmail,
                'in_map' => $inMap,
                'mailbox_id' => $mailboxId,
            ];

            if ($mailboxId) {
                // Mailbox found and has ID - mark as active
                $data['status'] = 'active';
                $data['mailbox_id'] = $mailboxId;

                // Map SMTP/IMAP details
                $data['smtp_host'] = $mailboxInfo['smtp_host'] ?? null;
                $data['smtp_port'] = $mailboxInfo['smtp_port'] ?? null;
                $data['imap_host'] = $mailboxInfo['imap_host'] ?? null;
                $data['imap_port'] = $mailboxInfo['imap_port'] ?? null;

                $successfulMailboxes[] = $data['mailbox'];
                $split->addMailbox($domain, $prefixKey, $data);

                Log::channel('mailin-ai')->info('Mailin createMailboxesForDomain: stored to split', [
                    'order_id' => $order->id,
                    'domain' => $domain,
                    'prefix_key' => $prefixKey,
                    'mailbox_email' => $data['mailbox'],
                    'mailbox_id' => $mailboxId,
                ]);
            } else {
                // Mailbox NOT found in API yet - do NOT save to DB so validation sees pending
                $pendingMailboxes[] = $data['mailbox'];

                Log::channel('mailin-ai')->warning('Mailin createMailboxesForDomain: NOT storing - no mailbox ID from API', [
                    'order_id' => $order->id,
                    'domain' => $domain,
                    'prefix_key' => $prefixKey,
                    'expected_email' => $data['mailbox'],
                    'target_email_lower' => $targetEmail,
                    'in_map' => $inMap,
                    'available_map_keys' => array_keys($mailboxIdMap),
                    'reason' => $inMap ? 'mailbox_id missing in API response' : 'email not found in getMailboxesByDomain response (case/format mismatch?)',
                ]);
            }
        }

        Log::channel('mailin-ai')->info('Mailin createMailboxesForDomain: store check summary', [
            'order_id' => $order->id,
            'domain' => $domain,
            'total_requested' => count($mailboxes),
            'stored_count' => count($successfulMailboxes),
            'skipped_no_id_count' => count($pendingMailboxes),
            'store_checks' => $storeChecks,
            'provider_slug' => $split->provider_slug,
        ]);

        return [
            'created' => $successfulMailboxes,
            'failed' => [], // We don't mark them as failed in the sense of "error", just not created yet
        ];
    }

    /**
     * Generate password
     */
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

    /**
     * Validate if all mailboxes are created for an order before marking as completed
     * 
     * @param Order $order
     * @param array $prefixVariants Array of expected prefixes
     * @return array ['is_complete' => bool, 'pending_mailboxes' => array, 'summary' => array]
     */
    public function validateOrderMailboxCompletion(Order $order, array $prefixVariants): array
    {
        $splits = OrderProviderSplit::where('order_id', $order->id)->get();

        $expectedPrefixCount = count($prefixVariants);
        $totalExpected = 0;
        $totalCreated = 0;
        $pendingMailboxes = [];
        $summary = [
            'by_provider' => [],
            'by_domain' => [],
        ];

        foreach ($splits as $split) {
            $providerSlug = $split->provider_slug;
            $domains = $split->domains ?? [];
            $splitMailboxes = $split->mailboxes ?? [];

            $providerSummary = [
                'provider' => $providerSlug,
                'total_domains' => count($domains),
                'expected_mailboxes' => count($domains) * $expectedPrefixCount,
                'created_mailboxes' => 0,
                'pending_domains' => [],
            ];

            foreach ($domains as $domain) {
                $domainMailboxes = $splitMailboxes[$domain] ?? [];
                $createdCount = count($domainMailboxes);
                $expectedForDomain = $expectedPrefixCount;

                $totalExpected += $expectedForDomain;
                $totalCreated += $createdCount;
                $providerSummary['created_mailboxes'] += $createdCount;

                // Check if domain has all expected mailboxes
                if ($createdCount < $expectedForDomain) {
                    $missingCount = $expectedForDomain - $createdCount;

                    // Find which prefixes are missing
                    $createdPrefixes = [];
                    foreach ($domainMailboxes as $variantKey => $mailboxData) {
                        $email = $mailboxData['mailbox'] ?? '';
                        $prefix = explode('@', $email)[0] ?? '';
                        $createdPrefixes[] = $prefix;
                    }

                    $expectedPrefixList = array_values($prefixVariants);
                    $missingPrefixes = array_diff($expectedPrefixList, $createdPrefixes);

                    foreach ($missingPrefixes as $prefix) {
                        $pendingMailboxes[] = [
                            'email' => $prefix . '@' . $domain,
                            'provider' => $providerSlug,
                            'domain' => $domain,
                            'prefix' => $prefix,
                        ];
                    }

                    $providerSummary['pending_domains'][] = [
                        'domain' => $domain,
                        'created' => $createdCount,
                        'expected' => $expectedForDomain,
                        'missing' => $missingCount,
                        'missing_prefixes' => array_values($missingPrefixes),
                    ];

                    $summary['by_domain'][$domain] = [
                        'provider' => $providerSlug,
                        'created' => $createdCount,
                        'expected' => $expectedForDomain,
                        'missing' => $missingCount,
                        'missing_prefixes' => array_values($missingPrefixes),
                        'created_prefixes' => $createdPrefixes,
                    ];
                }
            }

            $summary['by_provider'][$providerSlug] = $providerSummary;
        }

        $isComplete = empty($pendingMailboxes);

        // Log validation result
        if ($isComplete) {
            Log::channel('mailin-ai')->info('Order mailbox validation: COMPLETE', [
                'order_id' => $order->id,
                'total_mailboxes' => $totalCreated,
                'total_expected' => $totalExpected,
            ]);
        } else {
            Log::channel('mailin-ai')->warning('Order mailbox validation: INCOMPLETE', [
                'order_id' => $order->id,
                'total_created' => $totalCreated,
                'total_expected' => $totalExpected,
                'pending_count' => count($pendingMailboxes),
                'pending_mailboxes' => array_map(fn($m) => $m['email'], $pendingMailboxes),
            ]);
        }

        return [
            'is_complete' => $isComplete,
            'total_expected' => $totalExpected,
            'total_created' => $totalCreated,
            'pending_count' => count($pendingMailboxes),
            'pending_mailboxes' => $pendingMailboxes,
            'summary' => $summary,
        ];
    }

    /**
     * Check and update order status based on mailbox completion
     * 
     * @param Order $order
     * @param array $prefixVariants
     * @return array ['status_updated' => bool, 'new_status' => string|null, 'validation' => array]
     */
    public function checkAndUpdateOrderStatus(Order $order, array $prefixVariants): array
    {
        $validation = $this->validateOrderMailboxCompletion($order, $prefixVariants);

        if ($validation['is_complete']) {
            // All mailboxes created - mark as completed
            if ($order->status_manage_by_admin !== 'completed') {
                $order->update([
                    'status_manage_by_admin' => 'completed',
                    'completed_at' => now(),
                ]);

                Log::channel('mailin-ai')->info('Order status updated to COMPLETED', [
                    'order_id' => $order->id,
                    'total_mailboxes' => $validation['total_created'],
                ]);

                return [
                    'status_updated' => true,
                    'new_status' => 'completed',
                    'validation' => $validation,
                ];
            }

            return [
                'status_updated' => false,
                'new_status' => 'completed',
                'validation' => $validation,
            ];
        } else {
            // Still pending - log details
            Log::channel('mailin-ai')->warning('Order cannot be completed - mailboxes pending', [
                'order_id' => $order->id,
                'pending_count' => $validation['pending_count'],
                'pending_list' => array_map(fn($m) => $m['email'], $validation['pending_mailboxes']),
            ]);

            return [
                'status_updated' => false,
                'new_status' => null,
                'validation' => $validation,
            ];
        }
    }
}
