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
        'order-page' => 'Order Page',
        'checkout-page' => 'Checkout Page',
        'pool-order-page' => 'Pool Order Page',
        'pool-checkout-page' => 'Pool Checkout Page',
        'customer-dashboard' => 'Customer Dashboard',
        'general' => 'General Disclaimer',
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
