<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmtpProviderSplit extends Model
{
    use SoftDeletes;

    protected $table = 'smtp_provider_splits';

    protected $fillable = [
        'name',
        'slug',
        'api_endpoint',
        'email',
        'password',
        'additional_config',
        'split_percentage',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'additional_config' => 'array',
        'split_percentage' => 'decimal:2',
        'priority' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get active provider splits ordered by priority
     */
    public static function getActiveProviders()
    {
        return self::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Get provider by slug
     */
    public static function getBySlug(string $slug)
    {
        return self::where('slug', $slug)->first();
    }

    /**
     * Validate that total percentages equal 100
     */
    public static function validatePercentages()
    {
        $total = self::where('is_active', true)->sum('split_percentage');
        return abs($total - 100.00) < 0.01; // Allow small floating point differences
    }
}

