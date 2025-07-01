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
        'last_draft_notification_sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'timer_started_at' => 'datetime',
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
        'reason',
        'last_draft_notification_sent_at',
        'completed_at',
        'timer_started_at'
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

    public function orderPanels()
    {
        return $this->hasMany(OrderPanel::class);
    }

    public function userOrderPanelAssignments()
    {
        return $this->hasMany(UserOrderPanelAssignment::class, 'order_id');
    }

    public function orderTracking()
    {
        return $this->hasOne(OrderTracking::class);
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }
    
}
