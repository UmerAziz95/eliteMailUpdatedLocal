<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Pool;

class PoolPanelSplit extends Model
{
    use HasFactory;

    protected $fillable = [
        'pool_panel_id',
        'pool_id',
        'inboxes_per_domain',
        'domains',
        'uploaded_file_path',
        'assigned_space',
    ];

    protected $casts = [
        'domains' => 'array',
    ];

    // Relationships
    public function poolPanel()
    {
        return $this->belongsTo(PoolPanel::class);
    }
    
    public function panel()
    {
        return $this->belongsTo(PoolPanel::class, 'pool_panel_id');
    }

    public function pool()
    {
        return $this->belongsTo(Pool::class, 'pool_id');
    }

    // Scopes
    public function scopeForPool($query, $poolId)
    {
        return $query->where('pool_id', $poolId);
    }

    public function scopeForPoolPanel($query, $poolPanelId)
    {
        return $query->where('pool_panel_id', $poolPanelId);
    }

    // Helper methods
    public function getDomainCount()
    {
        return is_array($this->domains) ? count($this->domains) : 0;
    }

    public function getTotalInboxes()
    {
        return $this->getDomainCount() * $this->inboxes_per_domain;
    }

    /**
     * Get the remaining available space (total inboxes - assigned space)
     */
    public function getAvailableSpace()
    {
        return $this->getTotalInboxes() - ($this->assigned_space ?? 0);
    }

    /**
     * Check if there's enough available space
     */
    public function hasAvailableSpace($requestedSpace)
    {
        return $this->getAvailableSpace() >= $requestedSpace;
    }

    /**
     * Assign space and update the assigned_space column
     */
    public function assignSpace($space)
    {
        $this->increment('assigned_space', $space);
        return $this;
    }

    /**
     * Release space and update the assigned_space column
     */
    public function releaseSpace($space)
    {
        $this->decrement('assigned_space', $space);
        return $this;
    }

    /**
     * Get domain names by looking up domain IDs from the associated pool
     * Returns array of domain names for display purposes
     */
    public function getDomainNames(): array
    {
        return array_map(
            static fn(array $detail) => $detail['name'] ?? '',
            $this->getDomainDetails()
        );
    }

    public function getDomainDetails(): array
    {
        if (!is_array($this->domains) || empty($this->domains)) {
            return [];
        }

        $pool = $this->relationLoaded('pool') ? $this->pool : Pool::find($this->pool_id);

        $poolDomainLookup = [];
        if ($pool && is_array($pool->domains)) {
            foreach ($pool->domains as $poolDomain) {
                if (is_array($poolDomain)) {
                    $identifier = $poolDomain['id']
                        ?? $poolDomain['domain_id']
                        ?? $poolDomain['domain']
                        ?? $poolDomain['domain_name']
                        ?? $poolDomain['name']
                        ?? null;

                    $name = $poolDomain['domain']
                        ?? $poolDomain['domain_name']
                        ?? $poolDomain['name']
                        ?? $poolDomain['domain_url']
                        ?? $poolDomain['url']
                        ?? $poolDomain['value']
                        ?? null;

                    $status = $poolDomain['status']
                        ?? $poolDomain['domain_status']
                        ?? $poolDomain['status_manage_by_admin']
                        ?? null;

                    $record = [
                        'name' => \is_string($name) ? trim($name) : ($name ?? ''),
                        'status' => $status ?? 'unknown',
                        'prefix_statuses' => $poolDomain['prefix_statuses'] ?? null,
                    ];

                    if ($identifier !== null) {
                        $poolDomainLookup[(string) $identifier] = $record;
                    }

                    if (!empty($record['name'])) {
                        $poolDomainLookup[$record['name']] = $record;
                    }
                } elseif (is_string($poolDomain)) {
                    $trimmed = trim($poolDomain);
                    if ($trimmed !== '') {
                        $poolDomainLookup[$trimmed] = [
                            'name' => $trimmed,
                            'status' => 'unknown',
                            'prefix_statuses' => null,
                        ];
                    }
                }
            }
        }

        $details = [];

        foreach ($this->domains as $domainRef) {
            $name = '';
            $status = 'unknown';
            $prefixKey = null;

            if (is_array($domainRef)) {
                $name = $domainRef['domain']
                    ?? $domainRef['domain_name']
                    ?? $domainRef['name']
                    ?? $domainRef['domain_url']
                    ?? $domainRef['url']
                    ?? $domainRef['value']
                    ?? '';

                $status = $domainRef['status']
                    ?? $domainRef['domain_status']
                    ?? $domainRef['status_manage_by_admin']
                    ?? $status;

                // Check if this domain reference has a prefix_key (for split assignments)
                $prefixKey = $domainRef['prefix_key'] ?? $domainRef['prefix'] ?? null;

                $lookupKey = $domainRef['id'] ?? $domainRef['domain_id'] ?? null;
                if ($lookupKey !== null && isset($poolDomainLookup[(string) $lookupKey])) {
                    $lookup = $poolDomainLookup[(string) $lookupKey];
                    $name = $name ?: ($lookup['name'] ?? '');
                    
                    // Get status from prefix_statuses - check all variants and use the most relevant
                    $status = $this->getDomainStatusFromPrefixStatuses($lookup, $prefixKey);
                } elseif ($name && isset($poolDomainLookup[$name])) {
                    $lookup = $poolDomainLookup[$name];
                    
                    // Get status from prefix_statuses - check all variants and use the most relevant
                    $status = $this->getDomainStatusFromPrefixStatuses($lookup, $prefixKey);
                }
            } else {
                $key = trim((string) $domainRef);
                if ($key === '') {
                    continue;
                }

                if (isset($poolDomainLookup[$key])) {
                    $lookup = $poolDomainLookup[$key];
                    $name = $lookup['name'] ?? $key;
                    
                    // Get status from prefix_statuses - check all variants and use the most relevant
                    $status = $this->getDomainStatusFromPrefixStatuses($lookup, null);
                } else {
                    $name = $key;
                }
            }

            $name = is_string($name) ? trim($name) : '';
            if ($name === '') {
                continue;
            }

            $status = $status ?? 'unknown';

            $details[] = [
                'name' => $name,
                'status' => $status,
                'status_badge' => \function_exists('get_domain_status_badge')
                    ? \get_domain_status_badge($status, true)
                    : null,
            ];
        }

        return $details;
    }

    /**
     * Get domain status from prefix_statuses, intelligently choosing the most relevant status
     * 
     * @param array $lookup Domain lookup data containing prefix_statuses
     * @param string|null $prefixKey Specific prefix key if assigned
     * @return string The determined status
     */
    private function getDomainStatusFromPrefixStatuses(array $lookup, ?string $prefixKey = null): string
    {
        // If specific prefix key is provided and exists, use it
        if ($prefixKey && isset($lookup['prefix_statuses'][$prefixKey]['status'])) {
            return $lookup['prefix_statuses'][$prefixKey]['status'];
        }

        // If prefix_statuses exist, use intelligent status selection
        if (isset($lookup['prefix_statuses']) && is_array($lookup['prefix_statuses']) && !empty($lookup['prefix_statuses'])) {
            $statuses = array_column($lookup['prefix_statuses'], 'status');
            
            // Priority order: in-progress > warming > available > unknown
            if (in_array('in-progress', $statuses, true)) {
                return 'in-progress';
            }
            if (in_array('warming', $statuses, true)) {
                return 'warming';
            }
            if (in_array('available', $statuses, true)) {
                return 'available';
            }
            
            // Return first status if no priority match
            return reset($statuses) ?: 'unknown';
        }

        // Fall back to domain-level status
        return $lookup['status'] ?? 'unknown';
    }
}
