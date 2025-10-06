<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoolOrder extends Model
{
    use HasFactory;

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
     * Check if order is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if order is in warming status
     */
    public function isWarming()
    {
        return $this->status_manage_by_admin === 'warming';
    }

    /**
     * Check if order is available
     */
    public function isAvailable()
    {
        return $this->status_manage_by_admin === 'available';
    }
}
