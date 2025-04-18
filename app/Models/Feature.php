<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'value',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function plans()
    {
        return $this->belongsToMany(Plan::class)
            ->withPivot('value')
            ->withTimestamps();
    }
}
