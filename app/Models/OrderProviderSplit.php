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
        'priority',
    ];

    protected $casts = [
        'split_percentage' => 'decimal:2',
        'domain_count' => 'integer',
        'domains' => 'array', // Store as JSON array
        'priority' => 'integer',
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
}

