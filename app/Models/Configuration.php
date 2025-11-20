<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'description'
    ];

    /**
     * Get a configuration value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $config = self::where('key', $key)->first();
        
        if (!$config) {
            return $default;
        }

        return self::castValue($config->value, $config->type);
    }

    /**
     * Set a configuration value
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @return bool
     */
    public static function set(string $key, $value, string $type = 'string')
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type
            ]
        );
    }

    /**
     * Cast value to appropriate type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue($value, string $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'number':
            case 'integer':
                return is_numeric($value) ? (int) $value : $value;
            case 'float':
            case 'double':
                return is_numeric($value) ? (float) $value : $value;
            case 'json':
                return json_decode($value, true);
            case 'array':
                return is_array($value) ? $value : json_decode($value, true);
            default:
                return $value;
        }
    }

    /**
     * Get all panel configurations
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getPanelConfigurations()
    {
        return self::whereIn('key', [
            'GOOGLE_PANEL_CAPACITY',
            'MAX_SPLIT_CAPACITY',
            'ENABLE_MAX_SPLIT_CAPACITY',
            'PLAN_FLAT_QUANTITY',
            'PROVIDER_TYPE',
            'MICROSOFT_365_CAPACITY'
        ])->get();
    }

    /**
     * Get pool-related configurations.
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getPoolConfigurations()
    {
        return self::whereIn('key', [
            'POOL_WARMING_PERIOD',
            'CANCELLATION_POOL_WARMING_PERIOD',
        ])->get();
    }

    /**
     * Get available provider types
     *
     * @return array
     */
    public static function getProviderTypes()
    {
        return [
            'Google',
            'Microsoft 365',
            'Private SMTP'
        ];
    }

    /**
     * Get all Chargebee configurations
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getChargebeeConfigurations()
    {
        return self::whereIn('key', [
            'CHARGEBEE_PUBLISHABLE_API_KEY',
            'CHARGEBEE_SITE',
            'CHARGEBEE_API_KEY'
        ])->get();
    }

    /**
     * Get all System configurations
     *
     * @return \Illuminate\Support\Collection
     */
    public static function getSystemConfigurations()
    {
        return self::whereIn('key', [
            'SYSTEM_NAME',
            'ADMIN_EMAIL',
            'SUPPORT_EMAIL',
            'FOOTER_TEXT',
            'SYSTEM_LOGO'
        ])->get();
    }
}
