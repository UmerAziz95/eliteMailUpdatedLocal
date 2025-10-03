<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PoolPlan extends Model
{
    protected $table = 'pool_plans';

    protected $fillable = [
        'name',
        'description',
        'price',
        'duration',
        'is_active',
        'currency_code',
        'chargebee_plan_id',
        'is_chargebee_synced'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_chargebee_synced' => 'boolean',
        'price' => 'decimal:2'
    ];

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'pool_plan_feature')
            ->withPivot('value')
            ->withTimestamps();
    }

    public function poolSubscriptions()
    {
        return $this->hasMany(PoolSubscription::class);
    }

    // Helper methods
    public static function getMostlyUsed()
    {
        return self::where('is_active', true)
            ->withCount('poolSubscriptions')
            ->orderBy('pool_subscriptions_count', 'desc')
            ->first();
    }

    public function poolCoupons()
    {
        return $this->hasMany(PoolCoupon::class);
    }
}