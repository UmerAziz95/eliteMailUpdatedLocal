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

    // Helper contractors (multiple contractors assigned to shared pools)
    public function helpers()
    {
        return $this->belongsToMany(User::class, 'pool_helpers', 'pool_id', 'user_id')->withTimestamps();
    }


    // Mutator for status_manage_by_admin
    public function setStatusManageByAdminAttribute($value)
    {
        $this->attributes['status_manage_by_admin'] = strtolower($value);
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
}
