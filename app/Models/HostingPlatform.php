<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HostingPlatform extends Model
{
    protected $fillable = [
        'name',
        'value',
        'is_active',
        'requires_tutorial',
        'tutorial_link',
        'import_note',
        'fields',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_tutorial' => 'boolean',
        'sort_order' => 'integer',
        'fields' => 'json'
    ];
}
