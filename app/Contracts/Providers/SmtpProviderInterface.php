<?php

namespace App\Contracts\Providers;

use App\Models\Order;
use App\Models\OrderProviderSplit;
use App\Services\MailAutomation\DomainActivationService;

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
     * Delete all mailboxes for this provider from an order provider split.
     * Updates split JSON (deleted_at, mailbox_id) and calls provider API as needed.
     *
     * @param Order $order
     * @param OrderProviderSplit $split
     * @return array ['deleted' => int, 'failed' => int, 'skipped' => int]
     */
    public function deleteMailboxesFromSplit(Order $order, OrderProviderSplit $split): array;

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

    /**
     * Activate domains for this provider split (transfer/validate domains, update nameservers via activation service).
     *
     * @param Order $order
     * @param OrderProviderSplit $split
     * @param bool $bypassExistingMailboxCheck
     * @param DomainActivationService $activationService For updateNameservers and rejectOrder callbacks
     * @return array ['rejected' => bool, 'reason' => string|null, 'active' => array, 'transferred' => array, 'failed' => array]
     */
    public function activateDomainsForSplit(
        Order $order,
        OrderProviderSplit $split,
        bool $bypassExistingMailboxCheck,
        DomainActivationService $activationService
    ): array;
}
