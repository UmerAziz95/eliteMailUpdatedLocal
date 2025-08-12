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
        'timer_paused_at' => 'datetime',
        'rejected_at' => 'datetime',
        'is_internal' => 'boolean',
        'is_internal_order_assignment' => 'boolean'
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
        'timer_started_at',
        'timer_paused_at',
        'total_paused_seconds',
        'rejected_by',
        'rejected_at',
        'is_internal',
        'internal_order_id',
        'is_internal_order_assignment'
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

    // Assigned contractor (user)
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
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

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the effective working time in seconds (excluding paused time)
     */
    public function getEffectiveWorkingTimeSeconds()
    {
        if (is_null($this->timer_started_at)) {
            return 0;
        }

        $endTime = $this->completed_at ?? now();
        $totalTime = $endTime->diffInSeconds($this->timer_started_at);
        
        // Subtract total paused seconds
        $pausedTime = $this->total_paused_seconds ?? 0;
        
        // If currently paused, add current pause duration
        if (!is_null($this->timer_paused_at)) {
            $currentPauseDuration = now()->diffInSeconds($this->timer_paused_at);
            $pausedTime += $currentPauseDuration;
        }
        
        return max(0, $totalTime - $pausedTime);
    }

    /**
     * Get the effective working time formatted as human readable string
     */
    public function getEffectiveWorkingTimeFormatted()
    {
        $seconds = $this->getEffectiveWorkingTimeSeconds();
        
        if ($seconds < 60) {
            return $seconds . ' seconds';
        } elseif ($seconds < 3600) {
            $minutes = floor($seconds / 60);
            $remainingSeconds = $seconds % 60;
            return $minutes . ' minutes' . ($remainingSeconds > 0 ? ' ' . $remainingSeconds . ' seconds' : '');
        } else {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $remainingSeconds = $seconds % 60;
            
            $result = $hours . ' hours';
            if ($minutes > 0) {
                $result .= ' ' . $minutes . ' minutes';
            }
            if ($remainingSeconds > 0) {
                $result .= ' ' . $remainingSeconds . ' seconds';
            }
            
            return $result;
        }
    }

    /**
     * Check if the timer is currently paused
     */
    public function isTimerPaused()
    {
        return !is_null($this->timer_paused_at);
    }

    /**
     * Check if the timer is running (started but not paused or completed)
     */
    public function isTimerRunning()
    {
        return !is_null($this->timer_started_at) && 
               is_null($this->timer_paused_at) && 
               is_null($this->completed_at);
    }

    public function domainHealthChecks()
    {
        return $this->hasMany(DomainHealthCheck::class);
    }
    

}
