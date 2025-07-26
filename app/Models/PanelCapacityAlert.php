<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PanelCapacityAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'threshold',
        'capacity_when_sent',
        'notification_sent_at',
    ];

    protected $casts = [
        'notification_sent_at' => 'datetime',
    ];

    /**
     * Get alerts for a specific threshold
     */
    public static function getAlertsForThreshold(int $threshold)
    {
        return self::where('threshold', $threshold)
                  ->orderBy('created_at', 'desc')
                  ->get();
    }

    /**
     * Get recent alerts (last 24 hours)
     */
    public static function getRecentAlerts()
    {
        return self::where('created_at', '>=', Carbon::now()->subDay())
                  ->orderBy('created_at', 'desc')
                  ->get();
    }

    /**
     * Check if threshold was alerted recently
     */
    public static function wasAlertedRecently(int $threshold, int $hours = 1): bool
    {
        return self::where('threshold', $threshold)
                  ->where('created_at', '>=', Carbon::now()->subHours($hours))
                  ->exists();
    }

    /**
     * Clean up old alerts
     */
    public static function cleanupOldAlerts(): int
    {
        return self::query()->delete();
    }
}
