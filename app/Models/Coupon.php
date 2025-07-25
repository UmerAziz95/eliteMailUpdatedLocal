<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'usage_limit',
        'used',
        'status',
        'expires_at',
        'plan_id',
    ];

    protected $dates = [
        'expires_at',
    ];

    // Relationships
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
    
    
}
