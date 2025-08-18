<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPaymentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'hosted_page_id',
        'user_id',
        'is_exception',
        'chargebee_invoice_id',
        'chargebee_subscription_id',
        'customer_id',
        'invoice_data',
        'customer_data',
        'subscription_data',
        'plan_id',
        'amount',
        'response',
        'payment_status',
    ];

    protected $casts = [
        'is_exception' => 'boolean',
        'invoice_data' => 'array',
        'customer_data' => 'array',
        'subscription_data' => 'array',
        'response' => 'array',
        'amount' => 'decimal:2',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
