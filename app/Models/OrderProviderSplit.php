<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderProviderSplit extends Model
{
    protected $table = 'order_provider_splits';

    protected $fillable = [
        'order_id',
        'provider_slug',
        'provider_name',
        'split_percentage',
        'domain_count',
        'domains',
        'mailboxes',
        'domain_statuses',
        'all_domains_active',
        'priority',
        'external_order_id',
        'client_order_id',
        'order_status',
        'webhook_received_at',
        'metadata',
    ];

    protected $casts = [
        'split_percentage' => 'decimal:2',
        'domain_count' => 'integer',
        'domains' => 'array',
        'mailboxes' => 'array',
        'domain_statuses' => 'array',
        'all_domains_active' => 'boolean',
        'priority' => 'integer',
        'webhook_received_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the order that owns this provider split
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the SMTP provider split configuration
     */
    public function smtpProviderSplit()
    {
        return $this->belongsTo(SmtpProviderSplit::class, 'provider_slug', 'slug');
    }

    /**
     * Update domain activation status
     * 
     * @param string $domain Domain name
     * @param string $status Status: 'active', 'pending', 'failed'
     * @param int|null $domainId Domain ID from provider API
     */
    public function setDomainStatus(string $domain, string $status, ?int $domainId = null, array $nameServers = []): void
    {
        $statuses = $this->domain_statuses ?? [];
        $domainData = [
            'status' => $status,
            'domain_id' => $domainId ?? ($statuses[$domain]['domain_id'] ?? null),
            'updated_at' => now()->toISOString(),
        ];

        if (!empty($nameServers)) {
            $domainData['nameservers'] = $nameServers;
        } elseif (isset($statuses[$domain]['nameservers'])) {
            // Preserve existing nameservers if not provided
            $domainData['nameservers'] = $statuses[$domain]['nameservers'];
        }

        $statuses[$domain] = $domainData;
        $this->domain_statuses = $statuses;
        $this->save();
    }

    /**
     * Get domain status
     * 
     * @param string $domain Domain name
     * @return string|null Status or null if not set
     */
    public function getDomainStatus(string $domain): ?string
    {
        $statuses = $this->domain_statuses ?? [];
        return $statuses[$domain]['status'] ?? null;
    }

    /**
     * Check if all domains are active and update flag
     * 
     * @return bool True if all domains are active
     */
    public function checkAndUpdateAllDomainsActive(): bool
    {
        $domains = $this->domains ?? [];
        $statuses = $this->domain_statuses ?? [];

        if (empty($domains)) {
            return false;
        }

        foreach ($domains as $domain) {
            if (!isset($statuses[$domain]) || $statuses[$domain]['status'] !== 'active') {
                $this->all_domains_active = false;
                $this->save();
                return false;
            }
        }

        $this->all_domains_active = true;
        $this->save();
        return true;
    }

    /**
     * Add mailbox to JSON storage
     * 
     * @param string $domain Domain name
     * @param string $prefixKey Prefix variant key
     * @param array $mailboxData Mailbox data: ['id' => int, 'name' => string, 'mailbox' => string, 'password' => string, 'status' => string]
     */
    public function addMailbox(string $domain, string $prefixKey, array $mailboxData): void
    {
        $mailboxes = $this->mailboxes ?? [];

        if (!isset($mailboxes[$domain])) {
            $mailboxes[$domain] = [];
        }

        $mailboxes[$domain][$prefixKey] = $mailboxData;
        $this->mailboxes = $mailboxes;
        $this->save();
    }

    /**
     * Get mailboxes for a domain
     * 
     * @param string $domain Domain name
     * @return array Mailboxes for the domain
     */
    public function getMailboxesForDomain(string $domain): array
    {
        return $this->mailboxes[$domain] ?? [];
    }

    /**
     * Get all mailboxes as flat array
     * 
     * @return array All mailboxes
     */
    public function getAllMailboxes(): array
    {
        $result = [];
        $mailboxes = $this->mailboxes ?? [];

        foreach ($mailboxes as $domain => $domainMailboxes) {
            foreach ($domainMailboxes as $prefixKey => $mailbox) {
                $result[] = $mailbox;
            }
        }

        return $result;
    }

    /**
     * Check if all domains across ALL splits for an order are active
     * 
     * @param int $orderId Order ID
     * @return bool True if all domains in all splits are active
     */
    public static function areAllDomainsActiveForOrder(int $orderId): bool
    {
        return static::where('order_id', $orderId)
            ->where('all_domains_active', false)
            ->doesntExist();
    }

    /**
     * Get metadata value
     */
    public function getMetadata(string $key, $default = null)
    {
        $metadata = $this->metadata ?? [];
        return $metadata[$key] ?? $default;
    }

    /**
     * Set metadata value
     */
    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        $metadata[$key] = $value;
        $this->metadata = $metadata;
        $this->save();
    }
}

