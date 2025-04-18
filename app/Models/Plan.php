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
        'max_inbox'
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
}