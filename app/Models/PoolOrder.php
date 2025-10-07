<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoolOrder extends Model
{
    use HasFactory;

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
        'domains',
        'paid_at',
        'meta',
        'completed_at',
        'cancelled_at',
        'reason'
    ];

    protected $casts = [
        'meta' => 'array',
        'domains' => 'array',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'amount' => 'decimal:2'
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
                    'per_inbox' => (int) $domainData['per_inbox']
                ];
            }
        }
        
        $this->domains = $domains;
        return $this;
    }
}
