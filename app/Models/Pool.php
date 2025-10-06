<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pool extends Model
{
    use HasFactory;

    protected $fillable = [
        // Common columns
        'user_id',
        'plan_id',
        
        // Orders columns
        'chargebee_invoice_id',
        'chargebee_customer_id',
        'chargebee_subscription_id',
        'amount',
        'status',
        'currency',
        'paid_at',
        'meta',
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
        'is_internal_order_assignment',
        'is_shared',
        'helpers_ids',
        'shared_note',
        'reassignment_note',
        'assigned_to',
        
        // ReorderInfo columns
        'forwarding_url',
        'hosting_platform',
        'other_platform',
        'bison_url',
        'bison_workspace',
        'backup_codes',
        'platform_login',
        'platform_password',
        'domains',
        'sending_platform',
        'sequencer_login',
        'sequencer_password',
        'total_inboxes',
        'inboxes_per_domain',
        'initial_total_inboxes',
        'first_name',
        'last_name',
        'prefix_variant_1',
        'prefix_variant_2',
        'prefix_variants',
        'prefix_variants_details',
        'persona_password',
        'profile_picture_link',
        'email_persona_password',
        'email_persona_picture_link',
        'master_inbox_email',
        'master_inbox_confirmation',
        'additional_info',
        'coupon_code',
        'is_splitting',
    ];

    protected $casts = [
        'meta' => 'array',
        'helpers_ids' => 'array',
        'domains' => 'array',
        'prefix_variants' => 'array',
        'prefix_variants_details' => 'array',
        'last_draft_notification_sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'timer_started_at' => 'datetime',
        'timer_paused_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_at' => 'datetime',
        'is_internal' => 'boolean',
        'is_internal_order_assignment' => 'boolean',
        'is_shared' => 'boolean',
        'master_inbox_confirmation' => 'boolean',
        'is_splitting' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Assigned contractor (user)
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // User who rejected the pool
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
    // Call to undefined relationship [plan] on model [App\Models\Pool].
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
    // Helper contractors accessor - returns collection of User models
    public function getHelpersAttribute()
    {
        if (empty($this->helpers_ids)) {
            return collect();
        }
        
        return User::whereIn('id', $this->helpers_ids)->get();
    }
    
    // Get helper users as a method (not a relationship)
    public function getHelperUsers()
    {
        if (empty($this->helpers_ids)) {
            return collect();
        }
        
        return User::whereIn('id', $this->helpers_ids)->get();
    }


    // Status management methods
    public static function getAvailableStatuses()
    {
        return [
            'warming' => 'Warming',
            'available' => 'Available'
        ];
    }

    public function isWarming()
    {
        return $this->status_manage_by_admin === 'warming';
    }

    public function isAvailable()
    {
        return $this->status_manage_by_admin === 'available';
    }

    public function getStatusLabelAttribute()
    {
        $statuses = self::getAvailableStatuses();
        return $statuses[$this->status_manage_by_admin] ?? 'Unknown';
    }

    // Mutator for status_manage_by_admin
    public function setStatusManageByAdminAttribute($value)
    {
        // Ensure only valid statuses are set, default to warming
        $validStatuses = ['warming', 'available'];
        $this->attributes['status_manage_by_admin'] = in_array($value, $validStatuses) ? $value : 'warming';
    }

    // Relationships
    public function poolPanelSplits()
    {
        return $this->hasMany(\App\Models\PoolPanelSplit::class, 'pool_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeWarming($query)
    {
        return $query->where('status_manage_by_admin', 'warming');
    }

    public function scopeAvailable($query)
    {
        return $query->where('status_manage_by_admin', 'available');
    }

    public function scopeByAdminStatus($query, $status)
    {
        return $query->where('status_manage_by_admin', $status);
    }
}
