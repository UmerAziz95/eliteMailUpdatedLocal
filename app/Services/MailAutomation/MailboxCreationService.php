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
        array $prefixVariantsDetails
    ): array {
        // GATE: Check ALL domains across ALL splits are active
        if (!OrderProviderSplit::areAllDomainsActiveForOrder($order->id)) {
            return [
                'success' => false,
                'error' => 'Not all domains are active. Run mailin:activate-domains first.',
                'results' => [],
            ];
        }

        $splits = OrderProviderSplit::where('order_id', $order->id)->get();
        $allResults = [];
        $totalCreated = 0;
        $totalFailed = 0;
        $totalPending = 0;

        foreach ($splits as $split) {
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
                $order->update([
                    'status_manage_by_admin' => 'completed',
                    'completed_at' => now(),
                ]);

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

        // Get provider credentials
        $providerConfig = SmtpProviderSplit::getBySlug($split->provider_slug);
        if (!$providerConfig) {
            Log::channel('mailin-ai')->error('Provider not found for mailbox creation', [
                'provider_slug' => $split->provider_slug,
            ]);
            return $results;
        }

        $credentials = $providerConfig->getCredentials();
        $provider = $this->createProvider($split->provider_slug, $credentials);

        if (!$provider->authenticate()) {
            Log::channel('mailin-ai')->error('Provider auth failed for mailbox creation', [
                'provider' => $split->provider_slug,
            ]);
            return $results;
        }

        // Process each domain
        foreach ($split->domains ?? [] as $domain) {
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

            foreach ($split->domains ?? [] as $domain) {
                $statusResult = $provider->checkDomainStatus($domain);

                if ($statusResult['success'] && ($statusResult['is_active'] ?? false)) {
                    // Domain is active in Mailrun - check if it has existing mailboxes
                    $existingMailboxes = $provider->getMailboxesByDomain($domain);

                    if (!empty($existingMailboxes['mailboxes'])) {
                        // Domain already has mailboxes - check if they match our prefixes
                        $existingPrefixes = [];
                        foreach ($existingMailboxes['mailboxes'] as $mb) {
                            $email = $mb['email'] ?? $mb['username'] ?? '';
                            $existingPrefixes[] = explode('@', $email)[0] ?? '';
                        }

                        $expectedPrefixes = array_values($prefixVariants);
                        $matchCount = count(array_intersect($existingPrefixes, $expectedPrefixes));

                        if ($matchCount < count($expectedPrefixes)) {
                            // Mailboxes don't match - attempt re-enrollment with new prefixes
                            Log::channel('mailin-ai')->warning('Mailrun: Domain enrolled with different prefixes - attempting re-enrollment', [
                                'domain' => $domain,
                                'existing_prefixes' => $existingPrefixes,
                                'requested_prefixes' => $expectedPrefixes,
                                'order_id' => $order->id,
                            ]);
                            // Add to re-enrollment list - we'll call beginEnrollment with new senderPermutationOverride
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
                } else {
                    // Domain not active - safe to enroll
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

        // Enrollment already started - try to fetch mailboxes (this checks status internally)
        foreach ($split->domains ?? [] as $domain) {
            $mailboxesResult = $provider->getMailboxesByDomain($domain);

            if (!$mailboxesResult['success']) {
                Log::channel('mailin-ai')->warning('Mailrun: Failed to get mailboxes', [
                    'domain' => $domain,
                    'message' => $mailboxesResult['message'] ?? 'Unknown error',
                ]);
                continue;
            }

            // Check if still pending
            if (empty($mailboxesResult['mailboxes']) && ($mailboxesResult['enrollment_status'] ?? 'pending') !== 'complete') {
                Log::channel('mailin-ai')->info('Mailrun: Enrollment still pending', [
                    'domain' => $domain,
                    'status' => $mailboxesResult['enrollment_status'] ?? 'pending',
                ]);
                $results['pending'][] = $domain;
                continue;
            }

            // Validate returned prefixes match requested ones
            $expectedPrefixes = array_values($prefixVariants);
            $returnedPrefixes = [];
            foreach ($mailboxesResult['mailboxes'] as $mb) {
                $email = $mb['email'] ?? $mb['username'] ?? '';
                $returnedPrefixes[] = explode('@', $email)[0] ?? '';
            }

            // Check for prefix mismatch
            $matchedPrefixes = array_intersect($returnedPrefixes, $expectedPrefixes);
            $unmatchedReturned = array_diff($returnedPrefixes, $expectedPrefixes);
            $missingExpected = array_diff($expectedPrefixes, $returnedPrefixes);

            // Check for complete mismatch - ALL returned prefixes are wrong
            $hasCompleteMismatch = empty($matchedPrefixes) && !empty($unmatchedReturned);

            if (!empty($unmatchedReturned) || !empty($missingExpected)) {
                Log::channel('mailin-ai')->warning('Mailrun: Prefix mismatch detected!', [
                    'domain' => $domain,
                    'expected_prefixes' => $expectedPrefixes,
                    'returned_prefixes' => $returnedPrefixes,
                    'unmatched_returned' => array_values($unmatchedReturned),
                    'missing_expected' => array_values($missingExpected),
                    'complete_mismatch' => $hasCompleteMismatch,
                    'note' => 'Domain may have been enrolled previously with different settings',
                ]);

                // If complete mismatch (no matching prefixes), skip saving and mark as pending
                if ($hasCompleteMismatch) {
                    $reason = "mailrun provider this domains already processed with these mailboxes now second time new mailboxes not proceeded and order reject it";
                    Log::channel('mailin-ai')->error('Mailrun: Complete prefix mismatch - Rejecting Order', [
                        'domain' => $domain,
                        'returned_prefixes' => $returnedPrefixes,
                        'expected_prefixes' => $expectedPrefixes,
                        'reason' => $reason
                    ]);

                    $order->update([
                        'status_manage_by_admin' => 'reject',
                        'reason' => $reason,
                        'rejected_at' => now(),
                    ]);

                    // Stop processing for this split/order
                    return [
                        'created' => [],
                        'failed' => [],
                        'pending' => [], // Clear pending so it stops
                        'rejected' => true, // Signal rejection
                        'error' => $reason
                    ];
                }
            }

            // Check count mismatch
            if (count($mailboxesResult['mailboxes']) < count($prefixVariants)) {
                Log::channel('mailin-ai')->warning('Mailrun: Mailbox count mismatch!', [
                    'domain' => $domain,
                    'expected_count' => count($prefixVariants),
                    'returned_count' => count($mailboxesResult['mailboxes']),
                    'missing_count' => count($prefixVariants) - count($mailboxesResult['mailboxes']),
                ]);
            }

            // Store only mailboxes with MATCHING prefixes
            $domainVariantCounter = 1;
            $usedKeys = [];
            $savedCount = 0;
            $skippedCount = 0;

            foreach ($mailboxesResult['mailboxes'] as $mailbox) {
                $emailPrefix = explode('@', $mailbox['email'] ?? $mailbox['username'] ?? '')[0] ?? '';

                // Skip mailboxes with unexpected prefixes - don't save wrong data
                if (!in_array($emailPrefix, $expectedPrefixes)) {
                    Log::channel('mailin-ai')->warning('Mailrun: Skipping mailbox with wrong prefix - NOT saving', [
                        'domain' => $domain,
                        'email' => $mailbox['email'] ?? $mailbox['username'] ?? '',
                        'prefix' => $emailPrefix,
                        'expected_prefixes' => $expectedPrefixes,
                    ]);
                    $skippedCount++;
                    continue;
                }

                $prefixKey = $this->findPrefixKey($emailPrefix, $prefixVariants);

                // If key was already used (duplicate prefix returned by API), generate unique one
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
                    'originalPrefix' => $emailPrefix,
                ]);
            }

            Log::channel('mailin-ai')->info('Mailrun mailboxes stored', [
                'domain' => $domain,
                'saved_count' => $savedCount,
                'skipped_count' => $skippedCount,
                'expected_count' => count($prefixVariants),
            ]);

            // If we saved fewer than expected, mark as pending for missing mailboxes
            if ($savedCount < count($prefixVariants)) {
                $results['pending'][] = $domain;
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

        // Call provider API
        $apiResult = $provider->createMailboxes($mailboxes);

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

            // Wait until we find at least as many mailboxes as we just created
            // This prevents partial ID saving if the API returns them one by one
            if ($foundCount >= count($mailboxes)) {
                break;
            }

            Log::channel('mailin-ai')->info('Waiting for all mailboxes to appear in API...', [
                'domain' => $domain,
                'attempt' => $retryCount + 1,
                'found_count' => $foundCount,
                'expected_at_least' => count($mailboxes),
            ]);

            $retryCount++;
        } while ($retryCount < $maxRetries);

        $mailboxIdMap = [];

        Log::channel('mailin-ai')->debug('Mapping mailbox IDs', [
            'domain' => $domain,
            'api_response_count' => count($createdMailboxes['mailboxes'] ?? []),
            'created_mailboxes_preview' => array_slice($createdMailboxes['mailboxes'] ?? [], 0, 3),
        ]);

        if (!empty($createdMailboxes['mailboxes'])) {
            foreach ($createdMailboxes['mailboxes'] as $mb) {
                $email = $mb['email'] ?? $mb['username'] ?? '';
                // Use lowercase for reliable matching
                $mailboxIdMap[strtolower($email)] = $mb['id'] ?? null;
            }
        }

        $successfulMailboxes = [];
        $pendingMailboxes = [];

        // Store in JSON column with mailbox IDs
        foreach ($mailboxDataMap as $prefixKey => $data) {
            $targetEmail = strtolower($data['mailbox']);
            $mailboxId = $mailboxIdMap[$targetEmail] ?? null;
            
            if ($mailboxId) {
                // Mailbox found and has ID - mark as active
                $data['status'] = 'active';
                $data['mailbox_id'] = $mailboxId;
                $successfulMailboxes[] = $data['mailbox'];
            } else {
                // Mailbox NOT found in API yet - keep as pending
                // DO NOT mark as active without ID
                $data['status'] = 'pending_id_fetch';
                $data['mailbox_id'] = null;
                $pendingMailboxes[] = $data['mailbox'];

                Log::channel('mailin-ai')->warning('Could not find mailbox ID for email - marking as pending', [
                    'email' => $data['mailbox'],
                    'target_lookup' => $targetEmail,
                    'available_map_keys' => array_keys($mailboxIdMap),
                ]);
            }

            $split->addMailbox($domain, $prefixKey, $data);
        }

        Log::channel('mailin-ai')->info('Mailboxes processed for domain', [
            'domain' => $domain,
            'total_requested' => count($mailboxes),
            'successful' => count($successfulMailboxes),
            'pending_id' => count($pendingMailboxes),
            'provider' => $split->provider_slug,
        ]);

        // Return successfully created ones in 'created'
        // Return ones waiting for IDs in 'pending' (this handles the "created but not ready" state)
        // If we return them as 'created', the order might complete prematurely
        
        $result = [
            'created' => $successfulMailboxes,
            'failed' => [],
        ];

        // If we have pending mailboxes (created but no ID yet), add to a custom key or treat as pending?
        // The calling method (createMailboxesForOrder) checks 'pending' key from this return?
        // Let's check createMailboxesForSplit -> it aggregates 'created', 'failed', 'pending'
        
        if (!empty($pendingMailboxes)) {
            // We need to return these as pending so validateOrderMailboxCompletion 
            // and the main loop sees them as not fully done.
            // But createMailboxesForOrder structure is:
            // $results['created'], $results['failed']
            // It doesn't seem to explicitly handle 'pending' from createMailboxesForDomain return in the current code
            // currently createMailboxesForDomain returns ['created' => ..., 'failed' => ...]
            // Let's check createMailboxesForSplit (lines 102-159)
            // It mimics: $results['created'] = array_merge..., $results['failed'] = ...
            // It DOES NOT map 'pending' from createMailboxesForDomain result.
            // I need to update createMailboxesForSplit as well or change how I return this.
            
            // To be safe, I will treat them as FAILED for now so they are not counted as 'created'.
            // Or better, I should treat them as PENDING if I can modify the caller.
            // Let's look at createMailboxesForSplit again (I'll read it after this thought if needed, but I have it in context)
            // Line 154: $results['created'] = array_merge($results['created'], $domainResult['created']);
            // Line 155: $results['failed'] = array_merge($results['failed'], $domainResult['failed']);
            // It only looks for created and failed.
            
            // So I should return them as 'failed' (or rather, just NOT created) 
            // so the count doesn't match expected. 
            // If I return them as 'failed', the order will say "Failed: X".
            // If I return them as empty (neither created nor failed), the validation logic 
            // (validateOrderMailboxCompletion) will see count mismatch and report "Pending".
            
            // Let's check validateOrderMailboxCompletion (lines 805+).
            // It compares count($domainMailboxes) where they are stored in DB.
            // Wait, I AM storing them in DB: $split->addMailbox($domain, $prefixKey, $data).
            // $data['status'] = 'pending_id_fetch'.
            
            // The validation logic (lines 841) checks:
            // if ($createdCount < $expectedForDomain)
            // $createdCount comes from count($domainMailboxes).
            // So if I save them to DB, they COUNT as created in validation!
            
            // ERROR IN PLAN: simple "pending" status in JSON might still count as "created" 
            // if the validation just counts array entries.
            // I need to check how validation counts.
            
            // Validation: 
            // $domainMailboxes = $splitMailboxes[$domain] ?? [];
            // $createdCount = count($domainMailboxes);
            
            // YES, validation just counts entries in the JSON.
            // So if I add them to JSON, validation thinks they exist.
            
            // CORRECTION:
            // I should ONLY add them to JSON if they have an ID.
            // OR I should update validation to check status?
            // Modifying validation is risky/bigger scope.
            
            // Better approach:
            // DO NOT add them to the split via addMailbox if they don't have an ID?
            // If I don't add them, validation sees count < expected. -> Pending.
            // This is safer.
            // BUT, if I don't add them, the user won't see "pending" in UI? (Maybe they see nothing).
            // And next run?
            // Next run (CheckPendingDomains) calls createMailboxesForOrder again.
            // It calls createMailboxesForSplit -> createMailboxesForDomain.
            // createMailboxesForDomain generates the list again.
            // If I didn't save them, it creates them AGAIN via API?
            // The API provider check "already exists"?
            // Yes, Mailin provider returns "success" if already exists.
            
            // So, safer flow:
            // 1. Try create.
            // 2. Try fetch ID.
            // 3. If ID missing -> DO NOT SAVE to DB.
            // 4. Return as 'failed' (or 'pending' if I can pass that up).
            
            // If I don't save to DB:
            // -> Validation logic sees 0 created.
            // -> Validation says "Pending: 150".
            // -> Order NOT marked completed.
            // -> Status remains "In Progress".
            // -> Next schedule run (CheckPendingDomains) picks it up.
            // -> Calls createMailboxesForOrder.
            // -> Tries to create (API says "already exists OK").
            // -> Tries to fetch ID (Hopefully it works now).
            // -> ID found -> Saves to DB.
            // -> Success.
            
            // This seems correct and robust.
            
            // So, change in this block:
            // Only call $split->addMailbox if $mailboxId is present.
            
            if ($mailboxId) {
                // Mailbox found and has ID - mark as active
                $data['status'] = 'active';
                $data['mailbox_id'] = $mailboxId;
                $split->addMailbox($domain, $prefixKey, $data);
                $successfulMailboxes[] = $data['mailbox'];
            } else {
                // Mailbox NOT found in API yet
                // DO NOT save to DB so validation fails (correctly) logic
                $pendingMailboxes[] = $data['mailbox'];

                Log::channel('mailin-ai')->warning('Could not find mailbox ID for email - NOT saving to DB to force retry', [
                    'email' => $data['mailbox'],
                    'target_lookup' => $targetEmail,
                    'available_map_keys' => array_keys($mailboxIdMap),
                ]);
            }
        }

        Log::channel('mailin-ai')->info('Mailboxes processed for domain', [
            'domain' => $domain,
            'total_requested' => count($mailboxes),
            'successful' => count($successfulMailboxes),
            'pending_retry' => count($pendingMailboxes),
            'provider' => $split->provider_slug,
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
