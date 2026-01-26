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
        // Check provider type
        if ($split->provider_slug === 'premiuminboxes') {
            return $this->activateDomainsForPremiumInboxes($order, $split);
        }

        // Existing Mailin.ai logic
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
            try {
                $this->updateNameservers($order, $domain, $nameServers);
            } catch (\Exception $e) {
                // If nameserver update fails, we must reject the order because domain activation will fail
                $reason = "Nameserver update failed for {$domain}: " . $e->getMessage();
                $this->rejectOrder($order, $reason);

                return [
                    'success' => false,
                    'message' => $reason,
                    'active' => [],
                    'transferred' => [],
                    'failed' => [$domain]
                ];
            }
        }

        return $transferResult;
    }

    /**
     * Update nameservers via hosting platform
     * @throws \Exception
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
     * Handle PremiumInboxes order creation
     * 
     * @param Order $order
     * @param OrderProviderSplit $split
     * @return array ['rejected' => bool, 'reason' => string|null, 'active' => array, 'transferred' => array, 'failed' => array]
     */
    private function activateDomainsForPremiumInboxes(Order $order, OrderProviderSplit $split): array
    {
        $providerConfig = SmtpProviderSplit::getBySlug('premiuminboxes');
        if (!$providerConfig) {
            Log::channel('mailin-ai')->error('PremiumInboxes provider not found', [
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
        if (!$credentials) {
            Log::channel('mailin-ai')->error('PremiumInboxes credentials not configured', [
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

        $provider = $this->createProvider('premiuminboxes', $credentials);

        // Authenticate
        if (!$provider->authenticate()) {
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

        // Prepare persona from order/reorderInfo
        $reorderInfo = $order->reorderInfo()->first();
        $user = $order->user;

        // Extract first_name and last_name from the FIRST variant details if available
        // Fallback to reorderInfo columns, then user columns
        $firstName = 'User';
        $lastName = '';

        $prefixVariantsDetails = $reorderInfo->prefix_variants_details ?? [];
        if (is_string($prefixVariantsDetails)) {
            $prefixVariantsDetails = json_decode($prefixVariantsDetails, true) ?? [];
        }

        // Try getting name from first variant details
        // Details structure is likely ['prefix_variant_1' => ['first_name' => '...', 'last_name' => '...']]
        $firstVariant = reset($prefixVariantsDetails);
        if ($firstVariant && isset($firstVariant['first_name'])) {
            $firstName = $firstVariant['first_name'];
            $lastName = $firstVariant['last_name'] ?? '';
        } else {
            // Fallbacks
            $firstName = $reorderInfo->first_name ?? $user->first_name ?? 'User';
            $lastName = $reorderInfo->last_name ?? $user->last_name ?? '';
        }

        // Fix for "String should have at least 1 character" error
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

        // Get prefix variants
        $prefixVariants = $reorderInfo->prefix_variants ?? [];
        if (is_string($prefixVariants)) {
            $prefixVariants = json_decode($prefixVariants, true) ?? [];
        }
        $prefixVariants = array_values(array_filter($prefixVariants));

        // Build persona variations from prefix variants
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

        // Generate email password
        $emailPassword = $this->generatePassword($order->id);

        // Create client_order_id: "PI-order-{order_id}"
        $clientOrderId = "PI-order-{$order->id}";

        // Get sequencer config if available
        $sequencer = null;
        if ($reorderInfo && $reorderInfo->sequencer_login && $reorderInfo->sequencer_password) {
            $sequencer = [
                'platform' => $reorderInfo->sending_platform ?? 'instantly',
                'email' => $reorderInfo->sequencer_login,
                'password' => $reorderInfo->sequencer_password,
            ];
        }

        // Prepare additional parameters
        $additionalData = [];

        // Master Inbox - check reorderInfo first
        if ($reorderInfo && $reorderInfo->master_inbox_confirmation && !empty($reorderInfo->master_inbox_email)) {
            $additionalData['master_inbox'] = $reorderInfo->master_inbox_email;
        }

        // Profile Picture - check user profile image
        if ($user && !empty($user->profile_image)) {
            // Assuming profile_image contains a URL or path. If path, might need full URL.
            // Given it's a string column and user request mentioned profile_picture_url,
            // we'll pass it as is.
            $additionalData['profile_picture_url'] = $user->profile_image;
        }

        // Forwarding Domain - check user table
        if ($user && !empty($user->domain_forwarding_url)) {
            $additionalData['forwarding_domain'] = $user->domain_forwarding_url;
        }

        // Additional Info / Notes
        if (!empty($reorderInfo->additional_info)) {
            $additionalData['additional_info'] = $reorderInfo->additional_info;
        }

        Log::channel('mailin-ai')->info('Step 1: DomainActivationService - Calling createOrderWithDomains', [
            'order_id' => $order->id,
            'arguments' => [
                'domains' => $split->domains ?? [],
                'prefix_variants' => $prefixVariants,
                'persona' => $persona,
                'client_order_id' => $clientOrderId,
                'sequencer' => $sequencer, // CAUTION: Contains password
                'additional_data' => $additionalData
            ],
            'prefix_variants_count' => count($prefixVariants),
        ]);

        // Create order with PremiumInboxes
        /** @var \App\Services\Providers\PremiuminboxesProviderService $provider */
        $result = $provider->createOrderWithDomains(
            $split->domains ?? [],
            $prefixVariants,
            $persona,
            $emailPassword,
            $clientOrderId,
            $sequencer,
            $additionalData
        );

        if ($result['success']) {
            // Save order_id to split
            $split->update([
                'external_order_id' => $result['order_id'],
                'client_order_id' => $clientOrderId,
                'order_status' => $result['status'],
            ]);

            // Update domain statuses
            foreach ($split->domains ?? [] as $domain) {
                $split->setDomainStatus($domain, 'pending'); // ns_validation_pending
            }

            // Extract nameservers and update hosting provider
            $nameServers = $result['name_servers'] ?? [];
            if (!empty($nameServers)) {
                // Update nameservers for all domains in the split
                foreach ($split->domains ?? [] as $domain) {
                    try {
                        $this->updateNameservers($order, $domain, $nameServers);
                    } catch (\Exception $e) {
                        // If nameserver update fails, we must reject the order
                        $reason = "Nameserver update failed for {$domain}: " . $e->getMessage();
                        $this->rejectOrder($order, $reason);

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

        // Order creation failed
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
     * Generate password for email accounts
     * 
     * @param int $orderId Order ID
     * @param int $index Index for password variation
     * @return string Generated password
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
