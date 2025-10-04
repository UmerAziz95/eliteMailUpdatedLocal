<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoolPanelSplit extends Model
{
    use HasFactory;

    protected $fillable = [
        'pool_panel_id',
        'pool_id',
        'inboxes_per_domain',
        'domains',
        'uploaded_file_path',
    ];

    protected $casts = [
        'domains' => 'array',
    ];

    // Relationships
    public function poolPanel()
    {
        return $this->belongsTo(PoolPanel::class);
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
     * Get domain names by looking up domain IDs from the associated pool
     * Returns array of domain names for display purposes
     */
    public function getDomainNames()
    {
        if (!is_array($this->domains) || empty($this->domains)) {
            return [];
        }

        // Get the pool to look up domain names
        $pool = \App\Models\Pool::find($this->pool_id);
        if (!$pool || !is_array($pool->domains)) {
            // Fallback: if domains are already names (backward compatibility)
            return $this->domains;
        }

        $domainNames = [];
        $poolDomainsById = [];
        
        // Create a lookup map from pool domains (id => name)
        foreach ($pool->domains as $poolDomain) {
            if (is_array($poolDomain) && isset($poolDomain['id'], $poolDomain['name'])) {
                $poolDomainsById[$poolDomain['id']] = $poolDomain['name'];
            }
        }

        // Map stored domain IDs to domain names
        foreach ($this->domains as $domainId) {
            if (isset($poolDomainsById[$domainId])) {
                $domainNames[] = $poolDomainsById[$domainId];
            } else {
                // Fallback: if ID not found, use the ID itself (might be a domain name in legacy data)
                $domainNames[] = $domainId;
            }
        }

        return $domainNames;
    }
}
