<?php

// app/Models/Order.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    protected $casts = [
        'meta' => 'array',
    ];
    protected $fillable = [
        'user_id',
        'chargebee_invoice_id',
        'chargebee_customer_id',
        'chargebee_subscription_id',
        'amount',
        'status',
        'currency',
        'paid_at',
        'meta',
        'plan_id',
        'status_manage_by_admin',
        'reason'
    ];
    
    // status_manage_by_admin
    public function setStatusManageByAdminAttribute($value)
    {
        $this->attributes['status_manage_by_admin'] = strtolower($value);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice()
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function reorderInfo()
    {
        return $this->hasMany(ReorderInfo::class);
    }
    // plan
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
    public function panels()
    {
    return $this->belongsToMany(Panel::class, 'order_panel', 'order_id', 'panel_id')->withTimestamps();
    }
} 
