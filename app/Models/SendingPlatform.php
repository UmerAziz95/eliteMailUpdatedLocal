<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SendingPlatform extends Model
{
    protected $fillable = [
        'name',
        'value',
        'fields'
    ];

    protected $casts = [
        'fields' => 'json'
    ];
}