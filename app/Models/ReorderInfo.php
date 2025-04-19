<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReorderInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'order_id',
        'forwarding_url',
        'hosting_platform',
        'backup_codes',
        'platform_login',
        'platform_password',
        'domains',
        'sending_platform',
        'sequencer_login',
        'sequencer_password',
        'total_inboxes',
        'inboxes_per_domain',
        'first_name',
        'last_name',
        'prefix_variant_1',
        'prefix_variant_2',
        'persona_password',
        'profile_picture_link',
        'email_persona_password',
        'email_persona_picture_link',
        'master_inbox_email',
        'additional_info',
        'coupon_code'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
