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
            $password = $this->generatePassword($order->id, $index);

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

        // Fetch mailbox IDs from provider after creation
        $createdMailboxes = $provider->getMailboxesByDomain($domain);
        $mailboxIdMap = [];

        if (!empty($createdMailboxes['mailboxes'])) {
            foreach ($createdMailboxes['mailboxes'] as $mb) {
                $email = $mb['email'] ?? $mb['username'] ?? '';
                $mailboxIdMap[$email] = $mb['id'] ?? null;
            }
        }

        // Store in JSON column with mailbox IDs
        foreach ($mailboxDataMap as $prefixKey => $data) {
            $data['status'] = 'active';
            $data['mailbox_id'] = $mailboxIdMap[$data['mailbox']] ?? null;
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
