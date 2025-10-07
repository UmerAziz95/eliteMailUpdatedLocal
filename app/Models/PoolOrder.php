<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoolOrder extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_FAILED = 'failed';

    // Admin status constants
    const ADMIN_STATUS_PENDING = 'pending';
    const ADMIN_STATUS_IN_PROGRESS = 'in-progress';
    const ADMIN_STATUS_COMPLETED = 'completed';
    const ADMIN_STATUS_CANCELLED = 'cancelled';

    // Status configurations with colors and labels
    const STATUS_CONFIG = [
        self::STATUS_PENDING => [
            'label' => 'Pending',
            'color' => 'warning',
            'text_color' => 'text-warning'
        ],
        self::STATUS_COMPLETED => [
            'label' => 'Completed',
            'color' => 'success',
            'text_color' => 'text-success'
        ],
        self::STATUS_CANCELLED => [
            'label' => 'Cancelled',
            'color' => 'danger',
            'text_color' => 'text-danger'
        ],
        self::STATUS_FAILED => [
            'label' => 'Failed',
            'color' => 'danger',
            'text_color' => 'text-danger'
        ],
    ];

    // Admin status configurations with colors and labels
    const ADMIN_STATUS_CONFIG = [
        self::ADMIN_STATUS_PENDING => [
            'label' => 'Pending',
            'color' => 'secondary',
            'text_color' => 'text-secondary'
        ],
        self::ADMIN_STATUS_IN_PROGRESS => [
            'label' => 'In Progress',
            'color' => 'primary',
            'text_color' => 'text-primary'
        ],
        self::ADMIN_STATUS_COMPLETED => [
            'label' => 'Completed',
            'color' => 'success',
            'text_color' => 'text-success'
        ],
        self::ADMIN_STATUS_CANCELLED => [
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
        'paid_at',
        'meta',
        'completed_at',
        'cancelled_at',
        'reason'
    ];

    protected $casts = [
        'meta' => 'array',
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
}
