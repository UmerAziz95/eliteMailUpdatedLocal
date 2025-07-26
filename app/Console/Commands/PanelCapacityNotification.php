<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Panel;
use App\Models\PanelCapacityAlert;
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
    protected $signature = 'panels:capacity-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor panel capacity and send Slack notifications at specific thresholds';

    /**
     * Capacity thresholds that trigger notifications
     */
    private $thresholds = [0, 2000, 3000, 4000, 5000, 10000];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Panel Capacity Notification process...');
        
        try {
            // Calculate current total capacity
            $totalCapacity = $this->calculateTotalCapacity();
            $this->info("Current total panel capacity: {$totalCapacity}");

            // Check if any threshold is crossed
            $this->checkThresholds($totalCapacity);
            
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
     * Check thresholds and send notifications if needed
     */
    private function checkThresholds(int $currentCapacity): void
    {
        foreach ($this->thresholds as $threshold) {
            if ($this->shouldSendNotification($currentCapacity, $threshold)) {
                // Only send notification for the first (highest) threshold breached
                if ($threshold === $this->getHighestBreachedThreshold($currentCapacity)) {
                    $this->sendCapacityAlert($currentCapacity, $threshold);
                    $this->info("Capacity alert sent for threshold: {$threshold}");
                }
                
                // Always mark threshold as notified
                $this->markThresholdNotified($threshold, $currentCapacity);
            }
        }
    }

    /**
     * Get the highest threshold that is breached
     */
    private function getHighestBreachedThreshold(int $currentCapacity): ?int
    {
        foreach ($this->thresholds as $threshold) {
            if ($currentCapacity <= $threshold) {
                return $threshold;
            }
        }
        return null;
    }

    /**
     * Check if notification should be sent for a threshold
     */
    private function shouldSendNotification(int $currentCapacity, int $threshold): bool
    {
        // Don't send if capacity is above threshold
        if ($currentCapacity > $threshold) {
            return false;
        }

        // Check if this threshold was already alerted for current capacity level
        $existingAlert = PanelCapacityAlert::where('threshold', $threshold)
                                          ->where('capacity_when_sent', $currentCapacity)
                                          ->whereDate('created_at', Carbon::today())
                                          ->first();

        if ($existingAlert) {
            return false; // Already sent today for this exact capacity
        }

        // For handling the scenario where capacity goes down, then up, then down again
        // Check if we've sent an alert for this threshold recently (within last hour)
        $recentAlert = PanelCapacityAlert::where('threshold', $threshold)
                                        ->where('created_at', '>=', Carbon::now()->subHour())
                                        ->first();

        if ($recentAlert && $recentAlert->capacity_when_sent <= $currentCapacity) {
            return false; // Recently sent and capacity hasn't improved enough
        }

        return true;
    }

    /**
     * Send capacity alert to Slack
     */
    private function sendCapacityAlert(int $currentCapacity, int $threshold): void
    {
        try {
            $message = $this->formatCapacityMessage($currentCapacity, $threshold);
            
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
     * Mark threshold as notified in database
     */
    private function markThresholdNotified(int $threshold, int $currentCapacity): void
    {
        PanelCapacityAlert::create([
            'threshold' => $threshold,
            'capacity_when_sent' => $currentCapacity,
            'notification_sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now()
        ]);
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
                            'title' => 'Threshold Breached',
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
                        // [
                        //     'title' => 'Capacity Used',
                        //     'value' => number_format($panelStats['used_capacity']) . ' inboxes (' . 
                        //              round($panelStats['usage_percentage'], 1) . '%)',
                        //     'short' => true
                        // ],
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
