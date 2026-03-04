<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Disclaimer extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'content',
        'status'
    ];
    
    protected $casts = [
        'status' => 'boolean'
    ];
    
    // Define available types
    const TYPES = [
        'trial-new-order' => 'Trial New Order Disclaimer',
    ];
    
    public static function getTypes()
    {
        return self::TYPES;
    }
    
    /**
     * Get active disclaimer by type
     */
    public static function getActiveByType($type)
    {
        return self::where('type', $type)
            ->where('status', true)
            ->first();
    }
}
