<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SidebarNavigation extends Model
{
    protected $table = "sidebar_navigations";
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'route',
        'sub_menu',
        'nested_menu',
        'order',
        'is_active',
        'parent_id',
        'permission'
    ];

    protected $casts = [
        'sub_menu' => 'array',
        'nested_menu' => 'array',
        'is_active' => 'boolean'
    ];

    /**
     * Default ordering by order column
     */
    protected static function boot()
    {
        parent::boot();
        
        static::addGlobalScope('ordered', function ($builder) {
            $builder->orderBy('order');
        });
    }
}
