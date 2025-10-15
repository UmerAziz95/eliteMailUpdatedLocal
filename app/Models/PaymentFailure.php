<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentFailure extends Model
{
    use HasFactory;

    protected $fillable = [
    'chargebee_customer_id',
    'chargebee_subscription_id',
    'type',
    'status',
    'user_id',
    'plan_id',
    'failed_at',
    'invoice_data',
];

}
