<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InternalOrder extends Model
{
    use HasFactory;

    protected $table = 'internal_orders';

    protected $casts = [
        'meta' => 'array',
        'prefix_variants' => 'array',
        'prefix_variants_details' => 'array',
        'last_draft_notification_sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'timer_started_at' => 'datetime',
        'timer_paused_at' => 'datetime',
        'rejected_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    protected $fillable = [
        'user_id',
        'plan_id',
        'chargebee_invoice_id',
        'amount',
        'status',
        'timer_started_at',
        'timer_paused_at',
        'total_paused_seconds',
        'completed_at',
        'status_manage_by_admin',
        'assigned_to',
        'rejected_by',
        'rejected_at',
        'meta',
        'chargebee_subscription_id',
        'chargebee_customer_id',
        'currency',
        'paid_at',
        'last_draft_notification_sent_at',
        'reason',
        // Reorder columns
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
        'initial_total_inboxes',
        'inboxes_per_domain',
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
        'additional_info',
        'coupon_code',
        'tutorial_section',
        'is_internal',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
