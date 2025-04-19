<?php
// app/Models/Subscription.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    use HasFactory;
    protected $casts = [
        'meta' => 'array',
    ];
    protected $fillable = [
        'user_id',
        'order_id',
        'chargebee_subscription_id',
        'chargebee_customer_id',
        'chargebee_invoice_id',
        'plan_id',
        'status',
        'start_date',
        'end_date',
        'meta'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function plan(){
        return $this->belongsTo(Plan::class);
    }
}
