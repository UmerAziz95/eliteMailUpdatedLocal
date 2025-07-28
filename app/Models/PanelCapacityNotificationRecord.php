<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class PanelCapacityNotificationRecord extends Model
{
    use HasFactory;

    protected $table = 'panel_capacity_notifications';

    protected $fillable = [
        'threshold',
        'current_capacity',
        'is_active',
        'last_triggered_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    /**
     * Get or create a record for a specific threshold
     */
    public static function getOrCreateForThreshold(int $threshold): self
    {
        return self::firstOrCreate(
            ['threshold' => $threshold],
            [
                'current_capacity' => 0,
                'is_active' => false,
                'last_triggered_at' => null,
            ]
        );
    }

    /**
     * Check if this threshold should trigger a notification
     */
    public function shouldTriggerNotification(int $currentCapacity): bool
    {
        // If threshold is inactive and current capacity hits the threshold, trigger
        if (!$this->is_active && $currentCapacity <= $this->threshold) {
            return true;
        }

        // If threshold is active but capacity has recovered above the threshold, deactivate
        if ($this->is_active && $currentCapacity > $this->threshold) {
            $this->update([
                'is_active' => false,
                'current_capacity' => $currentCapacity,
            ]);
        }

        return false;
    }

    /**
     * Mark threshold as triggered
     */
    public function markAsTriggered(int $currentCapacity): void
    {
        $this->update([
            'current_capacity' => $currentCapacity,
            'is_active' => true,
            'last_triggered_at' => now(),
        ]);
    }
}
