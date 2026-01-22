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
        'api_secret',
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

    /**
     * Get the active provider for mailbox creation
     * For now, returns Mailin (100% active provider)
     * Returns null if no active provider found
     * 
     * @return SmtpProviderSplit|null
     */
    public static function getActiveProvider()
    {
        // Get active providers ordered by priority
        $activeProviders = self::getActiveProviders();
        
        // For now, return Mailin (first active provider)
        // In future, this will handle split logic
        return $activeProviders->first();
    }

    /**
     * Get provider credentials for API usage
     * Returns array with baseUrl, email, password, or null if not configured
     * 
     * @return array|null ['base_url' => string, 'email' => string, 'password' => string, 'api_key' => string]
     */
    public function getCredentials()
    {
        // For PremiumInboxes, only password (API key) is required
        if ($this->slug === 'premiuminboxes') {
            if (empty($this->password)) {
                return null;
            }

            return [
                'base_url' => $this->api_endpoint ?: null,
                'api_key' => $this->password, // API key stored in password field
                'password' => $this->password, // Also include as password for compatibility
            ];
        }

        // For other providers (Mailin.ai), email and password are required
        if (empty($this->email) || empty($this->password)) {
            return null;
        }

        return [
            'base_url' => $this->api_endpoint ?: null,
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}

