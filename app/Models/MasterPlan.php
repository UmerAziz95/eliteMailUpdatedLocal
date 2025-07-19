<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Exception;

class MasterPlan extends Model
{
    use HasFactory;    protected $fillable = [
        'external_name',
        'internal_name',
        'description',
        'chargebee_plan_id'
    ];

    /**
     * Get volume items (plans) for this master plan
     */
    public function volumeItems()
    {
        return $this->hasMany(Plan::class, 'master_plan_id');
    }/**
     * Boot the model
     */
    // protected static function boot()
    // {
    //     parent::boot();

    //     // Prevent creating multiple master plans
    //     static::creating(function ($model) {
    //         if (static::count() > 0) {
    //             throw new \Exception('Only one master plan is allowed. Please edit the existing master plan instead of creating a new one.');
    //         }
    //     });
    // }

    /**
     * Get the single master plan instance
     */
    public static function getSingle()
    {
        return static::first();
    }

    /**
     * Check if a master plan exists
     */
    public static function exists()
    {
        return static::count() > 0;
    }

    /**
     * Create or get the master plan
     */
    public static function getOrCreate()
    {
        $masterPlan = static::first();
        
        if (!$masterPlan) {
            $masterPlan = new static([
                'external_name' => 'Master Plan',
                'internal_name' => 'master_plan',
                'description' => 'Master plan for the system'
            ]);
            $masterPlan->save();
        }
        
        return $masterPlan;
    }
}
