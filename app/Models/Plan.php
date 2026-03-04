<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'chargebee_plan_id',
        'description',
        'price',
        'duration',
        'is_active',
        'min_inbox',
        'max_inbox',
        'master_plan_id',
        'tier_discount_value',
        'tier_discount_type',
        'actual_price_before_discount',
        'is_discounted',
        'provider_type'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'min_inbox' => 'integer',
        'max_inbox' => 'integer'
    ];

    public function features()
    {
        return $this->belongsToMany(Feature::class)
            ->withPivot('value')
            ->withTimestamps();
    }



    public function subscriptions(){
        return $this->hasMany(Subscription::class);
    }

    // Master plan relationships
    public function masterPlan()
    {
        return $this->belongsTo(MasterPlan::class, 'master_plan_id');
    }

    // Scopes
    public function scopeVolumeItems($query)
    {
        return $query->whereNotNull('master_plan_id');
    }

    public function scopeMasterPlans($query)
    {
        return $query->whereNull('master_plan_id')->where('name', 'LIKE', '%master%');
    }

    // Helper methods
    public static function getMasterPlan()
    {
        return self::whereNull('master_plan_id')->where('name', 'LIKE', '%master%')->first();
    }

    public static function masterPlanExists()
    {
        return self::whereNull('master_plan_id')->where('name', 'LIKE', '%master%')->exists();
    }

    public function isMasterPlan()
    {
        return is_null($this->master_plan_id) && stripos($this->name, 'master') !== false;
    }

    public function isVolumeItem()
    {
        return !is_null($this->master_plan_id);
    }

    // get mostly used plan
    public static function getMostlyUsed()
    {
        return self::where('is_active', true)
            ->withCount('subscriptions')
            ->orderBy('subscriptions_count', 'desc')
            ->first();
    }

    public function coupons()
{
    return $this->hasMany(Coupon::class);
}


}