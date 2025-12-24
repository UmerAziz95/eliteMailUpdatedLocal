<?php

namespace App\Services\Mailin;

use App\Models\MailinJob;
use App\Models\Order;
use App\Models\OrderEmail;
use App\Models\ReorderInfo;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MailinProvisioningService
{
    public function __construct(private readonly MailinClient $client)
    {
    }

    /**
     * Provision domains and mailboxes for a given order.
     */
    public function provisionOrder(int $orderId): void
    {
        $order = Order::with('reorderInfo')->find($orderId);

        if (!$order) {
            Log::warning('Mailin provisioning skipped, order not found', ['order_id' => $orderId]);
            return;
        }

        if (strtolower((string) $order->status_manage_by_admin) === 'draft') {
            Log::info('Mailin provisioning skipped for draft order', ['order_id' => $orderId]);
            return;
        }

        /** @var ReorderInfo|null $reorderInfo */
        $reorderInfo = $order->reorderInfo->first();

        if (!$reorderInfo) {
            Log::warning('Mailin provisioning skipped, reorder info missing', ['order_id' => $orderId]);
            return;
        }

        $domains = $this->prepareDomains($reorderInfo);

        if (empty($domains)) {
            $domainsNeeded = (int) ceil(($reorderInfo->total_inboxes ?? 0) / max(1, $reorderInfo->inboxes_per_domain ?? 1));
            $domains = $this->generateDomains($orderId, $domainsNeeded);
        }

        if (empty($domains)) {
            Log::warning('Mailin provisioning skipped, no domains determined', ['order_id' => $orderId]);
            return;
        }

        $domains = array_values(array_unique(array_map('strtolower', $domains)));

        $domainInventory = $this->mapDomains($this->client->listDomains());
        $missingDomains = $this->getMissingDomains($domains, $domainInventory['by_name']);

        if (!empty($missingDomains)) {
            $jobId = $this->client->buyDomains($missingDomains);
            $this->recordJob($orderId, 'domains', $jobId, ['domains' => $missingDomains]);
            $jobPayload = $this->client->waitForDomainJob($jobId);
            $this->updateJob($orderId, 'domains', $jobId, $jobPayload);
            $domainInventory = $this->mapDomains($this->client->listDomains());
        }

        $desiredEmails = $this->buildDesiredEmails(
            $order,
            $reorderInfo,
            $domains,
            $domainInventory['by_name']
        );

        $mailinMailboxes = $this->mapMailboxes($this->client->listMailboxes(), $domainInventory['by_id']);
        $existingEmails = OrderEmail::where('order_id', $orderId)
            ->get()
            ->keyBy(fn ($email) => strtolower($email->email));

        $mailboxesToCreate = [];

        foreach ($desiredEmails as &$emailData) {
            $emailKey = strtolower($emailData['email']);
            $existingMailbox = $mailinMailboxes[$emailKey] ?? null;
            $existingOrderEmail = $existingEmails[$emailKey] ?? null;

            if ($existingMailbox) {
                $emailData['mailin_mailbox_id'] = (string) data_get($existingMailbox, 'id');
                $emailData['mailin_status'] = data_get($existingMailbox, 'status');
                $emailData['mailin_domain_id'] = $emailData['mailin_domain_id'] ?: (string) data_get($existingMailbox, 'domain_id');
            }

            $shouldCreateMailbox = !$existingMailbox && (!$existingOrderEmail || empty($existingOrderEmail->mailin_mailbox_id));

            if ($shouldCreateMailbox) {
                if (empty($emailData['mailin_domain_id'])) {
                    Log::warning('Skipping mailbox creation because mailin_domain_id is missing', [
                        'order_id' => $orderId,
                        'email' => $emailData['email'],
                    ]);
                    continue;
                }

                $mailboxesToCreate[] = [
                    'username' => $emailData['email'],
                    'name' => trim($emailData['first_name'] . ' ' . $emailData['last_name']),
                    'password' => $emailData['password'],
                    'domain_id' => $emailData['mailin_domain_id'],
                ];
            }
        }

        unset($emailData);

        if (!empty($mailboxesToCreate)) {
            $chunkSize = (int) config('services.mailin.mailbox_chunk_size', 50);
            $chunkSize = max(1, $chunkSize);

            foreach (array_chunk($mailboxesToCreate, $chunkSize) as $chunk) {
                $jobId = $this->client->createMailboxes($chunk);
                $this->recordJob($orderId, 'mailboxes', $jobId, ['mailboxes' => $chunk]);
                $jobPayload = $this->client->waitForMailboxJob($jobId);
                $this->updateJob($orderId, 'mailboxes', $jobId, $jobPayload);
            }

            $mailinMailboxes = $this->mapMailboxes($this->client->listMailboxes(), $domainInventory['by_id']);
        }

        $this->persistOrderEmails($order, $desiredEmails, $mailinMailboxes);
    }

    private function prepareDomains(ReorderInfo $reorderInfo): array
    {
        if (empty($reorderInfo->domains)) {
            return [];
        }

        $domains = is_array($reorderInfo->domains)
            ? $reorderInfo->domains
            : preg_split('/[\r\n,]+/', (string) $reorderInfo->domains);

        return array_filter(array_map(fn ($domain) => strtolower(trim($domain)), $domains));
    }

    private function generateDomains(int $orderId, int $count): array
    {
        $domains = [];
        $count = max(1, $count);
        $tld = ltrim((string) config('services.mailin.auto_domain_tld', 'mailin.ai'), '.');
        $prefix = (string) config('services.mailin.auto_domain_prefix', 'order-mailin');

        for ($i = 0; $i < $count; $i++) {
            $domains[] = sprintf(
                '%s-%s-%s.%s',
                $prefix,
                $orderId,
                Str::lower(Str::random(6)),
                $tld
            );
        }

        return $domains;
    }

    private function mapDomains(array $domains): array
    {
        $byName = [];
        $byId = [];

        foreach ($domains as $domain) {
            $name = strtolower((string) data_get($domain, 'name'));
            $id = (string) data_get($domain, 'id', '');
            $status = strtolower((string) data_get($domain, 'status'));

            if ($name) {
                $byName[$name] = [
                    'id' => $id,
                    'status' => $status,
                ];
            }

            if ($id) {
                $byId[$id] = [
                    'name' => $name,
                    'status' => $status,
                ];
            }
        }

        return ['by_name' => $byName, 'by_id' => $byId];
    }

    private function getMissingDomains(array $requestedDomains, array $mailinDomains): array
    {
        $missing = [];

        foreach ($requestedDomains as $domain) {
            $details = $mailinDomains[$domain] ?? null;
            $isActive = $details && (!isset($details['status']) || in_array($details['status'], ['active', 'ready'], true));

            if (!$details || !$isActive) {
                $missing[] = $domain;
            }
        }

        return $missing;
    }

    private function buildDesiredEmails(Order $order, ReorderInfo $reorderInfo, array $domains, array $domainsByName): array
    {
        $prefixVariants = $this->normalizeVariants($reorderInfo->prefix_variants);
        $prefixDetails = $this->normalizeVariants($reorderInfo->prefix_variants_details);
        $inboxesPerDomain = (int) $reorderInfo->inboxes_per_domain;
        $inboxesPerDomain = max(1, min(3, $inboxesPerDomain));

        $password = $this->customEncrypt($order->id);
        $emails = [];

        foreach ($domains as $domain) {
            $domainKey = strtolower($domain);
            $domainId = $domainsByName[$domainKey]['id'] ?? null;
            for ($i = 1; $i <= $inboxesPerDomain; $i++) {
                $prefixKey = "prefix_variant_{$i}";
                $prefix = trim((string) Arr::get($prefixVariants, $prefixKey, ''));

                if (empty($prefix)) {
                    continue;
                }

                $details = (array) Arr::get($prefixDetails, $prefixKey, []);
                $firstName = $details['first_name'] ?? $prefix;
                $lastName = $details['last_name'] ?? $prefix;

                $emails[] = [
                    'email' => "{$prefix}@{$domain}",
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'password' => $password,
                    'mailin_domain_id' => $domainId,
                    'mailin_mailbox_id' => null,
                    'mailin_status' => null,
                ];
            }
        }

        return $emails;
    }

    private function normalizeVariants($variants): array
    {
        if (is_array($variants)) {
            return $variants;
        }

        if (is_string($variants)) {
            $decoded = json_decode($variants, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function mapMailboxes(array $mailboxes, array $domainsById): array
    {
        $mapped = [];

        foreach ($mailboxes as $mailbox) {
            $email = data_get($mailbox, 'email') ?: data_get($mailbox, 'username');

            $domainId = data_get($mailbox, 'domain_id');

            if (!$email && $domainId && isset($domainsById[$domainId])) {
                $email = data_get($mailbox, 'username') . '@' . ($domainsById[$domainId]['name'] ?? '');
            }

            if ($email) {
                $mapped[strtolower($email)] = $mailbox;
            }
        }

        return $mapped;
    }

    private function persistOrderEmails(Order $order, array $emails, array $mailinMailboxes): void
    {
        $timestamp = Carbon::now();
        $payloads = [];

        foreach ($emails as $emailData) {
            $emailKey = strtolower($emailData['email']);
            $mailbox = $mailinMailboxes[$emailKey] ?? null;

            $payloads[] = [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'name' => $emailData['first_name'],
                'last_name' => $emailData['last_name'],
                'email' => $emailData['email'],
                'password' => $emailData['password'],
                'mailin_domain_id' => $emailData['mailin_domain_id'] ?? data_get($mailbox, 'domain_id'),
                'mailin_mailbox_id' => $emailData['mailin_mailbox_id'] ?? data_get($mailbox, 'id'),
                'mailin_status' => $emailData['mailin_status'] ?? data_get($mailbox, 'status') ?? 'completed',
                'provisioned_at' => $timestamp,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'contractor_id' => null,
                'order_split_id' => null,
                'batch_id' => null,
                'profile_picture' => null,
            ];
        }

        DB::transaction(function () use ($payloads) {
            OrderEmail::upsert(
                $payloads,
                ['order_id', 'email'],
                [
                    'name',
                    'last_name',
                    'password',
                    'mailin_domain_id',
                    'mailin_mailbox_id',
                    'mailin_status',
                    'provisioned_at',
                    'user_id',
                    'updated_at',
                ]
            );
        });
    }

    private function recordJob(int $orderId, string $type, string $jobId, array $requestPayload): void
    {
        MailinJob::updateOrCreate(
            [
                'order_id' => $orderId,
                'type' => $type,
                'job_id' => $jobId,
            ],
            [
                'status' => 'pending',
                'request_payload_json' => $requestPayload,
            ]
        );
    }

    private function updateJob(int $orderId, string $type, string $jobId, array $responsePayload): void
    {
        MailinJob::where([
            'order_id' => $orderId,
            'type' => $type,
            'job_id' => $jobId,
        ])->update([
            'status' => data_get($responsePayload, 'status'),
            'response_json' => $responsePayload,
        ]);
    }

    /**
     * Custom encryption function for passwords.
     * Matches EmailExportService::customEncrypt.
     */
    private function customEncrypt(int $orderId): string
    {
        $upperCase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerCase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';

        mt_srand($orderId);

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
