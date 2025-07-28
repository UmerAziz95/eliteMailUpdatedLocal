<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Panel;
use App\Models\PanelCapacityAlert;
use App\Models\PanelCapacityNotificationRecord;
use App\Services\SlackNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PanelCapacityNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    
    protected $signature = 'panels:capacity-notifications 
                            {--dry-run : Run without sending notifications}
                            {--force : Force send notification even if already sent for threshold}
                            {--cleanup : Clean up old alert records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor panel capacity and send Slack notifications at specific thresholds. Supports --dry-run, --force, and --cleanup options.';

    /**
     * Capacity thresholds that trigger notifications (in descending order for proper triggering)
     */
    private $thresholds = [10000, 5000, 4000, 3000, 2000, 1000, 0];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Panel Capacity Notification process...');
    
        try {
            // Handle cleanup option
            if ($this->option('cleanup')) {
                $this->cleanupOldRecords();
                return;
            }

            // Calculate current total capacity
            $currentCapacity = $this->calculateTotalCapacity();
            $this->info("Current total panel capacity: {$currentCapacity}");
            
            // Check each threshold and send notifications if needed
            $this->checkThresholdsAndNotify($currentCapacity);
            
            $this->info('Panel Capacity Notification process completed successfully');
            
        } catch (\Exception $e) {
            $this->error('Error in Panel Capacity Notification process: ' . $e->getMessage());
            Log::error('PanelCapacityNotification Command Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Calculate total remaining capacity across all active panels
     */
    private function calculateTotalCapacity(): int
    {
        return Panel::where('is_active', true)
                   ->sum('remaining_limit');
    }

    /**
     * Check all thresholds and send notifications if needed
     */
    private function checkThresholdsAndNotify(int $currentCapacity): void
    {
        // Find the appropriate threshold range we're currently in
        $currentThresholdRange = null;
        
        // Check thresholds in descending order to find which range we're in
        foreach ($this->thresholds as $threshold) {
            if ($currentCapacity > $threshold) {
                $currentThresholdRange = $threshold;
                break;
            }
        }
        
        // If we're below all thresholds, we're in the 0 range
        if ($currentThresholdRange === null) {
            $currentThresholdRange = 0;
        }
        
        // Process all thresholds to update their states and send notifications
        foreach ($this->thresholds as $threshold) {
            $this->info("Checking threshold: {$threshold}");
            
            // Get or create notification record for this threshold
            $notificationRecord = PanelCapacityNotificationRecord::getOrCreateForThreshold($threshold);
            
            // Check if this threshold should trigger a notification
            $shouldTrigger = ($threshold === $currentThresholdRange) && 
                            $this->shouldSendNotification($notificationRecord, $currentCapacity, $threshold);
            
            if ($shouldTrigger) {
                $this->sendCapacityAlert($currentCapacity, $threshold);
                
                // Mark threshold as triggered
                $notificationRecord->markAsTriggered($currentCapacity);
                
                $this->info("Notification sent for threshold: {$threshold}");
            } else {
                // Update state if needed (deactivate if capacity recovered)
                $notificationRecord->shouldTriggerNotification($currentCapacity);
                $this->info("No notification needed for threshold: {$threshold}");
            }
        }
    }

    /**
     * Determine if notification should be sent for a threshold
     */
    private function shouldSendNotification(PanelCapacityNotificationRecord $record, int $currentCapacity, int $threshold): bool
    {
        // Force option overrides all checks
        if ($this->option('force')) {
            return true;
        }

        // Check if threshold should trigger notification based on current state
        return $record->shouldTriggerNotification($currentCapacity);
    }

    /**
     * Clean up old notification records
     */
    private function cleanupOldRecords(): void
    {
        $this->info('Cleaning up old notification records...');
        
        try {
            // Clean up old panel capacity alert records (older than 30 days)
            $deletedAlerts = PanelCapacityAlert::where('created_at', '<', now()->subDays(30))->delete();
            $this->info("Deleted {$deletedAlerts} old panel capacity alert records");
            
            // Reset inactive notification records older than 7 days
            $resetRecords = PanelCapacityNotificationRecord::where('is_active', false)
                ->where('updated_at', '<', now()->subDays(7))
                ->update([
                    'current_capacity' => 0,
                    'last_triggered_at' => null,
                ]);
            $this->info("Reset {$resetRecords} inactive notification records");
            
            $this->info('Cleanup completed successfully');
            
        } catch (\Exception $e) {
            $this->error('Error during cleanup: ' . $e->getMessage());
            Log::error('PanelCapacityNotification Cleanup Error: ' . $e->getMessage());
        }
    }

    /**
     * Send capacity alert to Slack
     */
    private function sendCapacityAlert(int $currentCapacity, int $threshold): void
    {
        try {
            $message = $this->formatCapacityMessage($currentCapacity, $threshold);
            
            // Dry run mode - just log what would be sent
            if ($this->option('dry-run')) {
                $this->info("DRY RUN: Would send capacity alert for threshold {$threshold}");
                $this->info("Message: " . json_encode($message, JSON_PRETTY_PRINT));
                return;
            }
            
            // Send to Slack inbox-admins channel
            $result = SlackNotificationService::send('inbox-admins', $message);

            if ($result) {
                $this->info("Capacity alert sent successfully for threshold {$threshold}");
                Log::channel('slack_notifications')->info("Panel capacity alert sent", [
                    'threshold' => $threshold,
                    'current_capacity' => $currentCapacity
                ]);
            } else {
                $this->warn("Failed to send capacity alert for threshold {$threshold}");
            }
            
        } catch (\Exception $e) {
            $this->error("Error sending capacity alert for threshold {$threshold}: " . $e->getMessage());
            Log::error("PanelCapacityNotification: Error sending alert", [
                'threshold' => $threshold,
                'current_capacity' => $currentCapacity,
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
     * Format capacity alert message for Slack
     */
    private function formatCapacityMessage(int $currentCapacity, int $threshold): array
    {
        $appName = config('app.name', 'ProjectInbox');
        
        // Determine urgency level and color
        $urgencyData = $this->getUrgencyData($currentCapacity, $threshold);
        
        // Get panel statistics
        $panelStats = $this->getPanelStatistics();
        
        return [
            'text' => $urgencyData['icon'] . " *PANEL CAPACITY ALERT - " . strtoupper($urgencyData['level']) . "*",
            'attachments' => [
                [
                    'color' => $urgencyData['color'],
                    'fields' => [
                        [
                            'title' => 'Current Available Capacity',
                            'value' => number_format($currentCapacity) . ' inboxes',
                            'short' => true
                        ],
                        [
                            'title' => 'Threshold Hit',
                            'value' => number_format($threshold) . ' inboxes',
                            'short' => true
                        ],
                        [
                            'title' => 'Active Panels',
                            'value' => $panelStats['active_panels'],
                            'short' => true
                        ],
                        [
                            'title' => 'Total Panel Capacity',
                            'value' => number_format($panelStats['total_capacity']) . ' inboxes',
                            'short' => true
                        ],
                        [
                            'title' => 'Urgency Level',
                            'value' => $urgencyData['level'] . ' ' . $urgencyData['icon'],
                            'short' => true
                        ],
                        [
                            'title' => 'Recommended Action',
                            'value' => $urgencyData['action'],
                            'short' => false
                        ],
                        [
                            'title' => 'Alert Time',
                            'value' => now()->format('Y-m-d H:i:s T'),
                            'short' => true
                        ]
                    ],
                    'footer' => $appName . ' - Panel Capacity Monitor',
                    'ts' => time()
                ]
            ]
        ];
    }

    /**
     * Get urgency data based on current capacity and threshold
     */
    private function getUrgencyData(int $currentCapacity, int $threshold): array
    {
        if ($currentCapacity === 0) {
            return [
                'level' => 'CRITICAL',
                'icon' => '🚨',
                'color' => '#dc3545', // Red
                'action' => 'IMMEDIATE ACTION REQUIRED: Create new panels immediately! No capacity available for new orders.'
            ];
        } elseif ($currentCapacity <= 1000) {
            return [
                'level' => 'CRITICAL',
                'icon' => '🚨',
                'color' => '#dc3545', // Red
                'action' => 'CRITICAL: Very low capacity! Create new panels immediately.'
            ];
        } elseif ($currentCapacity <= 2000) {
            return [
                'level' => 'HIGH',
                'icon' => '⚠️',
                'color' => '#fd7e14', // Orange
                'action' => 'Create new panels soon. Very low capacity remaining.'
            ];
        } elseif ($currentCapacity <= 4000) {
            return [
                'level' => 'MEDIUM',
                'icon' => '⚡',
                'color' => '#ffc107', // Yellow
                'action' => 'Monitor closely and prepare to create new panels.'
            ];
        } else {
            return [
                'level' => 'LOW',
                'icon' => '📊',
                'color' => '#17a2b8', // Blue
                'action' => 'Capacity is getting low. Consider planning for new panels.'
            ];
        }
    }


    /**
     * Get panel statistics
     */
    private function getPanelStatistics(): array
    {
        $activePanels = Panel::where('is_active', true)->get();
        
        $totalCapacity = $activePanels->sum('limit');
        $remainingCapacity = $activePanels->sum('remaining_limit');
        $usedCapacity = $totalCapacity - $remainingCapacity;
        $usagePercentage = $totalCapacity > 0 ? ($usedCapacity / $totalCapacity) * 100 : 0;
        
        return [
            'active_panels' => $activePanels->count(),
            'total_capacity' => $totalCapacity,
            'remaining_capacity' => $remainingCapacity,
            'used_capacity' => $usedCapacity,
            'usage_percentage' => $usagePercentage
        ];
    }

    /**
     * Send notification when a new panel is added
     */
    public static function sendNewPanelNotification(Panel $panel): void
    {
        try {
            $currentCapacity = Panel::where('is_active', true)->sum('remaining_limit');
            $appName = config('app.name', 'ProjectInbox');
            
            $message = [
                'text' => "📈 *NEW PANEL ADDED*",
                'attachments' => [
                    [
                        'color' => '#28a745', // Green
                        'fields' => [
                            [
                                'title' => 'Panel ID',
                                'value' => 'PNL-' . str_pad($panel->id, 2, '0', STR_PAD_LEFT),
                                'short' => true
                            ],
                            [
                                'title' => 'Panel Capacity',
                                'value' => number_format($panel->limit) . ' inboxes',
                                'short' => true
                            ],
                            [
                                'title' => 'New Total Capacity',
                                'value' => number_format($currentCapacity) . ' inboxes',
                                'short' => true
                            ],
                            [
                                'title' => 'Panel Title',
                                'value' => $panel->title ?: 'Auto Generated Panel',
                                'short' => true
                            ],
                            [
                                'title' => 'Created By',
                                'value' => $panel->created_by ?: 'System',
                                'short' => true
                            ],
                            [
                                'title' => 'Created At',
                                'value' => $panel->created_at->format('Y-m-d H:i:s T'),
                                'short' => true
                            ]
                        ],
                        'footer' => $appName . ' - Panel Management',
                        'ts' => time()
                    ]
                ]
            ];

            SlackNotificationService::send('inbox-admins', $message);
            
            Log::channel('slack_notifications')->info("New panel notification sent", [
                'panel_id' => $panel->id,
                'panel_capacity' => $panel->limit,
                'new_total_capacity' => $currentCapacity
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error sending new panel notification", [
                'panel_id' => $panel->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
