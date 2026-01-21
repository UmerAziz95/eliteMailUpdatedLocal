<?php

namespace App\Contracts\Providers;

/**
 * Interface for SMTP provider services
 * All providers (Mailin, Premiuminboxes, Mailrun) must implement this interface
 */
interface SmtpProviderInterface
{
    /**
     * Authenticate with provider API
     * @return string|null Token if successful, null otherwise
     */
    public function authenticate(): ?string;

    /**
     * Transfer domain to provider
     * @param string $domain Domain name to transfer
     * @return array ['success' => bool, 'name_servers' => array, 'message' => string]
     */
    public function transferDomain(string $domain): array;

    /**
     * Check domain registration status
     * @param string $domain Domain name to check
     * @return array ['success' => bool, 'status' => string, 'data' => array]
     */
    public function checkDomainStatus(string $domain): array;

    /**
     * Create mailboxes on provider
     * @param array $mailboxes [['username' => 'user@domain.com', 'name' => 'Name', 'password' => 'pass']]
     * @return array ['success' => bool, 'uuid' => string, 'message' => string]
     */
    public function createMailboxes(array $mailboxes): array;

    /**
     * Delete a mailbox from provider
     * @param int $mailboxId Provider's mailbox ID
     * @return array ['success' => bool, 'message' => string]
     */
    public function deleteMailbox(int $mailboxId): array;

    /**
     * Get mailboxes by domain
     * @param string $domain Domain name
     * @return array ['success' => bool, 'mailboxes' => array]
     */
    public function getMailboxesByDomain(string $domain): array;

    /**
     * Get provider display name
     * @return string e.g., "Mailin.ai"
     */
    public function getProviderName(): string;

    /**
     * Get provider slug identifier
     * @return string e.g., "mailin"
     */
    public function getProviderSlug(): string;

    /**
     * Check if provider is available and configured
     * @return bool
     */
    public function isAvailable(): bool;
}
