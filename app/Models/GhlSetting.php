<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GhlSetting extends Model
{
    use HasFactory;

    protected $table = 'ghl_settings';

    protected $fillable = [
        'enabled',
        'base_url',
        'api_token',
        'location_id',
        'auth_type',
        'api_version',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Get the current GHL settings
     */
    public static function getCurrentSettings()
    {
        return self::first() ?? new self([
            'enabled' => false,
            'base_url' => 'https://rest.gohighlevel.com/v1',
            'api_token' => '',
            'location_id' => '',
            'auth_type' => 'bearer',
            'api_version' => '2021-07-28'
        ]);
    }
    /**
     * Update or create GHL settings
     */
    public static function updateSettings(array $data, $userId = null)
    {
        $settings = self::first();
        
        if ($settings) {
            $data['updated_by'] = $userId;
            $settings->update($data);
        } else {
            $data['created_by'] = $userId;
            $data['updated_by'] = $userId;
            $settings = self::create($data);
        }

        return $settings;
    }
}
