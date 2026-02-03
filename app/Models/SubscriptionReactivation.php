<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionReactivation extends Model
{
    use HasFactory;

    protected $table = 'subscription_reactivations';

    protected $fillable = [
        'user_id',
        'order_id',
        'chargebee_subscription_id',
        'status',
        'message',
        'data',
        'latest_invoice_start_date',
        'latest_invoice_end_date',
        'retry_count',
    ];

    protected $casts = [
        'data' => 'array',
    ];
}
