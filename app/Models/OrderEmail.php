<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEmail extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'order_split_id',
        'contractor_id',
        'batch_id',
        'name',
        'last_name',
        'email',
        'password',
        'profile_picture',
        'provider_type',
        'provisioned_at',
        'mailin_status',
        'mailin_mailbox_id',
        'mailin_domain_id',
        'is_migrated_to_mailin',
        'mailin_ai_inbox_id',
        'domain',
        'provider_slug',
    ];

    protected $casts = [
        'provisioned_at' => 'datetime',
        'is_migrated_to_mailin' => 'boolean',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderSplit()
    {
        return $this->belongsTo(OrderPanelSplit::class, 'order_split_id');
    }

    public function contractor()
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }
}
