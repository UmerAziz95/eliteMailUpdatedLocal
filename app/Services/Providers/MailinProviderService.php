<?php

namespace App\Services\Providers;

use App\Contracts\Providers\SmtpProviderInterface;
use App\Services\MailinAiService;

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
}
