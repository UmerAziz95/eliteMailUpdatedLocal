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
        // For range-based notifications:
        // - Trigger if we're currently in this threshold range and it's not active
        // - Deactivate if we're no longer in this threshold range and it's active
        
        $isInThresholdRange = $this->isCapacityInThresholdRange($currentCapacity);
        
        // If we're in this threshold range and it's not active, trigger
        if ($isInThresholdRange && !$this->is_active) {
            return true;
        }
        
        // If we're not in this threshold range but it's active, deactivate
        if (!$isInThresholdRange && $this->is_active) {
            $this->update([
                'is_active' => false,
                'current_capacity' => $currentCapacity,
            ]);
        }
        
        return false;
    }
    
    /**
     * Check if current capacity is in this threshold range
     */
    private function isCapacityInThresholdRange(int $currentCapacity): bool
    {
        // Get all thresholds in descending order
        $allThresholds = [10000, 5000, 4000, 3000, 2000, 1000, 0];
        
        // Find the current threshold index
        $currentIndex = array_search($this->threshold, $allThresholds);
        
        if ($currentIndex === false) {
            return false;
        }
        
        // For the highest threshold (10000), check if capacity is above it
        if ($currentIndex === 0) {
            return $currentCapacity > $this->threshold;
        }
        
        // Special case for the 0 threshold (lowest threshold)
        if ($this->threshold === 0) {
            return $currentCapacity === 0;
        }
        
        // For other thresholds, check if capacity is in the range
        $upperThreshold = $allThresholds[$currentIndex];
        return $currentCapacity > $this->threshold && $currentCapacity <= $upperThreshold;
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
