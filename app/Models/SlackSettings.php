<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SlackSettings extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'type',
        'url',
        'status'
    ];
    
    protected $casts = [
        'status' => 'boolean'
    ];
    
    // Define available types
    const TYPES = [
        'order-cancelled' => 'Order Cancelled',
        'panel-created' => 'Panel Created',
        'order-created' => 'Order Created',
        'order-updated' => 'Order Updated',
        // 'user-registered' => 'User Registered',
        // 'invoice-generated' => 'Invoice Generated'
    ];
    
    public static function getTypes()
    {
        return self::TYPES;
    }
}
