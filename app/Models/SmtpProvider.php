<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmtpProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get all pools using this SMTP provider
     */
    public function pools()
    {
        return $this->hasMany(Pool::class, 'smtp_provider_id');
    }

    /**
     * Scope to only get active providers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
