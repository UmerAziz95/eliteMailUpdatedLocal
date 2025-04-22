<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;
    protected $casts = [
        'metadata' => 'array',
    ];
    protected $fillable = [
        'chargebee_invoice_id',
        'chargebee_customer_id',
        'chargebee_subscription_id',
        'user_id',
        'plan_id',
        'order_id',
        'amount',
        'status',
        'paid_at',
        'metadata',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    // order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
