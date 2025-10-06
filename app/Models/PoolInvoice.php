<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoolInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pool_order_id',
        'chargebee_invoice_id',
        'chargebee_customer_id',
        'amount',
        'currency',
        'status',
        'paid_at',
        'meta'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'meta' => 'array'
    ];

    /**
     * Get the user that owns the pool invoice
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pool order that owns the pool invoice
     */
    public function poolOrder(): BelongsTo
    {
        return $this->belongsTo(PoolOrder::class);
    }
}
