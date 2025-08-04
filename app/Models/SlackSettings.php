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
        'inbox-setup' => 'Inbox Setup',
        'inbox-cancellation'=> 'Inbox Cancellation',
        'inbox-admins' => 'Inbox Admins',
        'inbox-subscriptions' => 'Inbox Subscriptions',
    ];
    
    public static function getTypes()
    {
        return self::TYPES;
    }
}


