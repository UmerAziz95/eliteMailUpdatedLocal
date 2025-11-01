<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoolOrder extends Model
{
    use HasFactory;
    // append ready_domains_prefix and pool_id
    protected $appends = ['ready_domains_prefix', 'pool_id'];
    // Status configurations with colors and labels
    const STATUS_CONFIG = [
        'pending' => [
            'label' => 'Pending',
            'color' => 'warning',
            'text_color' => 'text-warning'
        ],
        'completed' => [
            'label' => 'Completed',
            'color' => 'success',
            'text_color' => 'text-success'
        ],
        'cancelled' => [
            'label' => 'Cancelled',
            'color' => 'danger',
            'text_color' => 'text-danger'
        ],
        'failed' => [
            'label' => 'Failed',
            'color' => 'danger',
            'text_color' => 'text-danger'
        ],
    ];

    // Admin status configurations with colors and labels
    const ADMIN_STATUS_CONFIG = [
        'pending' => [
            'label' => 'Pending',
            'color' => 'warning',
            'text_color' => 'text-warning'
        ],
        'in-progress' => [
            'label' => 'In Progress',
            'color' => 'primary',
            'text_color' => 'text-primary'
        ],
        'completed' => [
            'label' => 'Completed',
            'color' => 'success',
            'text_color' => 'text-success'
        ],
        'cancelled' => [
            'label' => 'Cancelled',
            'color' => 'danger',
            'text_color' => 'text-danger'
        ],
    ];

    protected $fillable = [
        'user_id',
        'pool_plan_id',
        'quantity',
        'chargebee_subscription_id',
        'chargebee_customer_id',
        'chargebee_invoice_id',
        'amount',
        'currency',
        'status',
        'status_manage_by_admin',
        'assigned_to',
        'assigned_at',
        'domains',
        'hosting_platform',
        'hosting_platform_data',
        'sending_platform',
        'sending_platform_data',
        'paid_at',
        'meta',
        'completed_at',
        'cancelled_at',
        'locked_out_of_instantly',
        'locked_out_at',
        'reason'
    ];

    protected $casts = [
        'meta' => 'array',
        'domains' => 'array',
        'hosting_platform_data' => 'array',
        'sending_platform_data' => 'array',
        'paid_at' => 'datetime',
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'locked_out_at' => 'datetime',
        'amount' => 'decimal:2',
        'locked_out_of_instantly' => 'boolean'
    ];

    /**
     * Get the user that owns the pool order
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pool plan associated with this order
     */
    public function poolPlan(): BelongsTo
    {
        return $this->belongsTo(PoolPlan::class);
    }

    /**
     * Get the admin/user assigned to this pool order
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get pool_id attribute for easier access
     */
    public function getPoolIdAttribute()
    {
        if (is_array($this->domains) && !empty($this->domains)) {
            return $this->domains[0]['pool_id'] ?? null;
        }
        return null;
    }

    /**
     * Get pool attribute for easier access
     * This retrieves the Pool model from the first domain's pool_id
     */
    public function getPoolAttribute()
    {
        // Get the first pool_id from domains JSON
        $poolId = null;
        if (is_array($this->domains) && !empty($this->domains)) {
            $poolId = $this->domains[0]['pool_id'] ?? null;
        }
        
        if ($poolId) {
            // Cache the pool to avoid multiple queries
            if (!isset($this->relations['pool'])) {
                $this->setRelation('pool', Pool::find($poolId));
            }
            return $this->relations['pool'];
        }
        
        return null;
    }

    /**
     * Get the pool invoices for this pool order
     */
    public function poolInvoices()
    {
        return $this->hasMany(PoolInvoice::class, 'pool_order_id');
    }

    /**
     * Get the invoices for this pool order (legacy - keeping for compatibility)
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'order_id');
    }

    /**
     * Scope to get orders by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get orders by admin status
     */
    public function scopeByAdminStatus($query, $adminStatus)
    {
        return $query->where('status_manage_by_admin', $adminStatus);
    }

    /**
     * Get formatted amount with currency
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 2) . ' ' . strtoupper($this->currency);
    }

    /**
     * Get total value (quantity × unit price)
     */
    public function getTotalValueAttribute()
    {
        return $this->quantity * $this->amount;
    }

    /**
     * Get unit price (amount ÷ quantity)
     */
    public function getUnitPriceAttribute()
    {
        return $this->quantity > 0 ? $this->amount / $this->quantity : $this->amount;
    }

    /**
     * Check if order has specific status
     */
    public function hasStatus($status)
    {
        return $this->status === $status;
    }

    /**
     * Check if order has specific admin status
     */
    public function hasAdminStatus($adminStatus)
    {
        return $this->status_manage_by_admin === $adminStatus;
    }

    /**
     * Get all available statuses
     */
    public static function getStatuses()
    {
        return array_map(function($config) {
            return $config['label'];
        }, self::STATUS_CONFIG);
    }

    /**
     * Get all available admin statuses
     */
    public static function getAdminStatuses()
    {
        return array_map(function($config) {
            return $config['label'];
        }, self::ADMIN_STATUS_CONFIG);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute()
    {
        return self::STATUS_CONFIG[$this->status]['label'] ?? ucfirst($this->status);
    }

    /**
     * Get admin status label
     */
    public function getAdminStatusLabelAttribute()
    {
        return self::ADMIN_STATUS_CONFIG[$this->status_manage_by_admin]['label'] ?? ucfirst(str_replace('-', ' ', $this->status_manage_by_admin));
    }

    /**
     * Get status color (for badges)
     */
    public function getStatusColorAttribute()
    {
        return self::STATUS_CONFIG[$this->status]['color'] ?? 'secondary';
    }

    /**
     * Get admin status color (for badges)
     */
    public function getAdminStatusColorAttribute()
    {
        return self::ADMIN_STATUS_CONFIG[$this->status_manage_by_admin]['color'] ?? 'secondary';
    }

    /**
     * Get status text color (for text)
     */
    public function getStatusTextColorAttribute()
    {
        return self::STATUS_CONFIG[$this->status]['text_color'] ?? 'text-secondary';
    }

    /**
     * Get admin status text color (for text)
     */
    public function getAdminStatusTextColorAttribute()
    {
        return self::ADMIN_STATUS_CONFIG[$this->status_manage_by_admin]['text_color'] ?? 'text-secondary';
    }

    /**
     * Get complete status configuration
     */
    public function getStatusConfigAttribute()
    {
        return self::STATUS_CONFIG[$this->status] ?? [
            'label' => ucfirst($this->status),
            'color' => 'secondary',
            'text_color' => 'text-secondary'
        ];
    }

    /**
     * Get complete admin status configuration
     */
    public function getAdminStatusConfigAttribute()
    {
        return self::ADMIN_STATUS_CONFIG[$this->status_manage_by_admin] ?? [
            'label' => ucfirst(str_replace('-', ' ', $this->status_manage_by_admin)),
            'color' => 'secondary',
            'text_color' => 'text-secondary'
        ];
    }

    /**
     * Get all status configurations
     */
    public static function getStatusConfigurations()
    {
        return self::STATUS_CONFIG;
    }

    /**
     * Get all admin status configurations
     */
    public static function getAdminStatusConfigurations()
    {
        return self::ADMIN_STATUS_CONFIG;
    }

    /**
     * Get all status keys
     */
    public static function getStatusKeys()
    {
        return array_keys(self::STATUS_CONFIG);
    }

    /**
     * Get all admin status keys
     */
    public static function getAdminStatusKeys()
    {
        return array_keys(self::ADMIN_STATUS_CONFIG);
    }

    /**
     * Get status badge HTML for DataTables
     */
    public function getStatusBadgeAttribute()
    {
        $config = $this->status_config;
        return '<span class="py-1 px-2 text-' . $config['text_color'] . ' border border-' . $config['color'] . ' rounded-2 bg-' . $config['color'] . ' mt-1">' . $config['label'] . '</span>';
    }

    /**
     * Get admin status badge HTML for DataTables
     */
    public function getAdminStatusBadgeAttribute()
    {
        $config = $this->admin_status_config;
        return '<span class="py-1 px-2 text-' . $config['color'] . ' border border-' . $config['color'] . ' rounded-2 bg-transparent' . ' mt-1">' . $config['label'] . '</span>';
    }

    /**
     * Get combined status badges HTML for DataTables
     */
    public function getStatusBadgesAttribute()
    {
        return $this->admin_status_badge;
    }

    /**
     * Get selected domains count
     */
    public function getSelectedDomainsCountAttribute()
    {
        return is_array($this->domains) ? count($this->domains) : 0;
    }

    /**
     * Get total inboxes from selected domains
     */
    public function getTotalInboxesAttribute()
    {
        if (!is_array($this->domains)) {
            return 0;
        }

        return array_sum(array_column($this->domains, 'per_inbox'));
    }

    /**
     * Check if domains are assigned
     */
    public function hasDomains()
    {
        return is_array($this->domains) && count($this->domains) > 0;
    }

    /**
     * Get domains with their details
     */
    public function getDomainsWithDetailsAttribute()
    {
        if (!is_array($this->domains)) {
            return [];
        }

        // Assuming you have a Domain model - adjust based on your actual domain model
        $domainIds = array_column($this->domains, 'domain_id');
        
        // You'll need to adjust this based on your actual domain model structure
        // For now, returning the raw domain data
        return $this->domains;
    }

    /**
     * Get ready domains with prefix variants from pools table
     */
    public function getReadyDomainsPrefixAttribute()
    {
        if (!is_array($this->domains) || empty($this->domains)) {
            return [];
        }

        $readyDomains = [];

        foreach ($this->domains as $orderDomain) {
            $domainId = $orderDomain['domain_id'] ?? null;
            $poolId = $orderDomain['pool_id'] ?? null;

            if (!$domainId || !$poolId) {
                continue;
            }

            try {
                // Get the pool that contains this domain
                $pool = \App\Models\Pool::find($poolId);
                
                if ($pool && $pool->domains) {
                    $poolDomains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
                    
                    if (is_array($poolDomains)) {
                        // Find matching domain in pool's domains array
                        $matchingDomain = collect($poolDomains)->firstWhere('id', $domainId) 
                                       ?? collect($poolDomains)->firstWhere('domain_id', $domainId);
                        
                        if ($matchingDomain) {
                            $domainData = [
                                'domain_id' => $domainId,
                                'pool_id' => $poolId,
                                'domain_name' => $matchingDomain['name'] ?? $matchingDomain['domain_name'] ?? 'Unknown Domain',
                                'per_inbox' => $orderDomain['per_inbox'] ?? 1,
                                'available_inboxes' => $matchingDomain['available_inboxes'] ?? 0,
                                'status' => $matchingDomain['status'] ?? 'unknown',
                                'is_used' => $matchingDomain['is_used'] ?? false,
                            ];
                            
                            // Get prefix variants from pool level (not per domain)
                            $poolPrefixVariants = $pool->prefix_variants ?? [];
                            $poolPrefixDetails = $pool->prefix_variants_details ?? [];
                            
                            $domainData['prefix_variants'] = [];
                            $domainData['prefix_variants_details'] = [];
                            $domainData['formatted_prefixes'] = [];
                            
                            if (is_array($poolPrefixVariants) && !empty($poolPrefixVariants)) {
                                foreach ($poolPrefixVariants as $key => $prefix) {
                                    if (!empty($prefix)) {
                                        $domainData['prefix_variants'][$key] = $prefix;
                                        
                                        // Add corresponding details if they exist
                                        if (isset($poolPrefixDetails[$key])) {
                                            $domainData['prefix_variants_details'][$key] = $poolPrefixDetails[$key];
                                        }
                                        
                                        // Format as email
                                        $domainData['formatted_prefixes'][$key] = $prefix . '@' . $domainData['domain_name'];
                                    }
                                }
                            }
                            
                            // Add pool-level information
                            $domainData['pool_info'] = [
                                'first_name' => $pool->first_name ?? '',
                                'last_name' => $pool->last_name ?? '',
                                'total_inboxes' => $pool->total_inboxes ?? 0,
                                'inboxes_per_domain' => $pool->inboxes_per_domain ?? 0,
                            ];
                            
                            $readyDomains[] = $domainData;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Log error but continue processing other domains
                \Log::warning('Error fetching pool domain data', [
                    'domain_id' => $domainId,
                    'pool_id' => $poolId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $readyDomains;
    }

    /**
     * Add domain to pool order
     */
    public function addDomain($domainId, $perInbox)
    {
        $domains = $this->domains ?? [];
        
        // Check if domain already exists
        $existingIndex = array_search($domainId, array_column($domains, 'domain_id'));
        
        if ($existingIndex !== false) {
            // Update existing domain
            $domains[$existingIndex]['per_inbox'] = $perInbox;
        } else {
            // Add new domain
            $domains[] = [
                'domain_id' => $domainId,
                'per_inbox' => $perInbox
            ];
        }
        
        $this->domains = $domains;
        return $this;
    }

    /**
     * Remove domain from pool order
     */
    public function removeDomain($domainId)
    {
        $domains = $this->domains ?? [];
        
        $domains = array_filter($domains, function($domain) use ($domainId) {
            return $domain['domain_id'] != $domainId;
        });
        
        $this->domains = array_values($domains); // Re-index array
        return $this;
    }

    /**
     * Set domains from form data
     */
    public function setDomainsFromForm($domainsData)
    {
        $domains = [];
        
        foreach ($domainsData as $domainData) {
            if (isset($domainData['domain_id']) && isset($domainData['per_inbox']) && $domainData['per_inbox'] > 0) {
                $domains[] = [
                    'domain_id' => $domainData['domain_id'],
                    'pool_id' => $domainData['pool_id'] ?? null,
                    'domain_name' => $domainData['domain_name'] ?? null,
                    'per_inbox' => (int) $domainData['per_inbox'],
                    'status' => 'in-progress' // Save domain status (defaults to in-progress)
                ];
            }
        }
        
        \Log::info('Saving domains to pool order:', ['domains' => $domains]);
        $this->domains = $domains;
        return $this;
    }
}
