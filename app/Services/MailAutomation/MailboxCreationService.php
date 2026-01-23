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

        foreach ($splits as $split) {
            $result = $this->createMailboxesForSplit($order, $split, $prefixVariants, $prefixVariantsDetails);
            $allResults[$split->provider_slug] = $result;
            $totalCreated += count($result['created'] ?? []);
            $totalFailed += count($result['failed'] ?? []);
        }

        // Complete order if all mailboxes created
        if ($totalFailed === 0) {
            $order->update([
                'status_manage_by_admin' => 'completed',
                'completed_at' => now(),
            ]);

            Log::channel('mailin-ai')->info('Order completed after mailbox creation', [
                'order_id' => $order->id,
                'total_mailboxes' => $totalCreated,
            ]);
        }

        return [
            'success' => $totalFailed === 0,
            'error' => $totalFailed > 0 ? "Failed to create {$totalFailed} mailboxes" : null,
            'results' => $allResults,
            'total_created' => $totalCreated,
            'total_failed' => $totalFailed,
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

        // Check if enrollment already initiated (uuid stored in split metadata)
        $enrollmentUuid = $split->getMetadata('mailrun_enrollment_uuid');

        if (!$enrollmentUuid) {
            // Start enrollment
            $enrollResult = $provider->createMailboxes($allMailboxes);

            if (!$enrollResult['success']) {
                Log::channel('mailin-ai')->error('Mailrun enrollment failed', [
                    'order_id' => $order->id,
                    'error' => $enrollResult['message'] ?? 'Unknown error',
                ]);
                $results['failed'] = array_column($allMailboxes, 'username');
                return $results;
            }

            // Store enrollment UUID for later polling
            $enrollmentUuid = $enrollResult['uuid'] ?? null;
            if ($enrollmentUuid) {
                $split->setMetadata('mailrun_enrollment_uuid', $enrollmentUuid);
            }

            Log::channel('mailin-ai')->info('Mailrun enrollment initiated', [
                'order_id' => $order->id,
                'uuid' => $enrollmentUuid,
                'mailbox_count' => count($allMailboxes),
            ]);

            // Enrollment is async - mark as pending, scheduler will poll
            $results['pending'] = array_column($allMailboxes, 'username');
            return $results;
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

            // Store mailboxes
            foreach ($mailboxesResult['mailboxes'] as $mailbox) {
                $emailPrefix = explode('@', $mailbox['email'] ?? $mailbox['username'] ?? '')[0] ?? '';
                $prefixKey = $this->findPrefixKey($emailPrefix, $prefixVariants);

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
            }

            Log::channel('mailin-ai')->info('Mailrun mailboxes stored', [
                'domain' => $domain,
                'count' => count($mailboxesResult['mailboxes']),
            ]);
        }

        return $results;
    }

    /**
     * Find prefix key from email prefix
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
            Log::channel('mailin-ai')->error('Mailbox creation failed', [
                'domain' => $domain,
                'error' => $apiResult['message'] ?? 'Unknown error',
            ]);

            return [
                'created' => [],
                'failed' => array_column($mailboxes, 'username'),
            ];
        }

        // Fetch mailbox IDs from provider after creation with retry
        $maxRetries = 5;
        $retryCount = 0;
        $createdMailboxes = [];

        do {
            if ($retryCount > 0) {
                // Wait 2 seconds before retry
                sleep(2);
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

        // Store in JSON column with mailbox IDs
        foreach ($mailboxDataMap as $prefixKey => $data) {
            $data['status'] = 'active';
            $targetEmail = strtolower($data['mailbox']);
            $data['mailbox_id'] = $mailboxIdMap[$targetEmail] ?? null;

            if (empty($data['mailbox_id'])) {
                Log::channel('mailin-ai')->warning('Could not find mailbox ID for email', [
                    'email' => $data['mailbox'],
                    'target_lookup' => $targetEmail,
                    'available_map_keys' => array_keys($mailboxIdMap),
                ]);
            }

            $split->addMailbox($domain, $prefixKey, $data);
        }

        Log::channel('mailin-ai')->info('Mailboxes created for domain', [
            'domain' => $domain,
            'count' => count($mailboxes),
            'provider' => $split->provider_slug,
        ]);

        return [
            'created' => array_column($mailboxes, 'username'),
            'failed' => [],
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
}
