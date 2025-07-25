<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Services\SlackNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class OrderCountdownNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:countdown-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send order countdown notifications to Slack based on timer_started_at';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Order Countdown Notification process...');
        
        try {
            // Get orders that have timer_started_at and are not completed or rejected
            $orders = Order::whereNotNull('timer_started_at')
                          ->whereNotIn('status_manage_by_admin', ['completed', 'reject', 'cancelled'])
                          ->where(function($query) {
                              $query->whereRaw('
                                  (TIMESTAMPDIFF(SECOND, timer_started_at, NOW()) - COALESCE(total_paused_seconds, 0)) < ?
                              ', [12 * 3600]); // 12 hours in seconds
                          })
                          ->get();
            $this->info("Found {$orders->count()} orders to check for countdown notifications");

            foreach ($orders as $order) {
                $this->processOrderCountdown($order);
            }

            $this->info('Order Countdown Notification process completed successfully');
            
        } catch (\Exception $e) {
            $this->error('Error in Order Countdown Notification process: ' . $e->getMessage());
            Log::error('OrderCountdownNotification Command Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Process countdown notifications for a single order
     */
    private function processOrderCountdown(Order $order)
    {
        try {
            $timerStarted = Carbon::parse($order->timer_started_at);
            $now = Carbon::now();
            
            // Calculate elapsed time accounting for paused time
            $totalPausedSeconds = $order->total_paused_seconds ?? 0;
            
            // If currently paused, don't include current pause duration in calculation
            $currentPauseDuration = 0;
            if ($order->timer_paused_at) {
                $currentPauseDuration = $now->diffInSeconds(Carbon::parse($order->timer_paused_at));
            }
            
            $elapsedSeconds = $now->diffInSeconds($timerStarted) - $totalPausedSeconds - $currentPauseDuration;
            $elapsedHours = (int) ($elapsedSeconds / 3600);
            // dd($elapsedHours);
            // 12-hour timer milestones (in hours elapsed)
            $milestones = [
                0 => 'start',       // Start: 0h elapsed (timer just started)
                6 => '6h',          // 6h elapsed (6h remaining)
                9 => '3h',          // 9h elapsed (3h remaining)
                10 => '2h',         // 10h elapsed (2h remaining)
                11 => '1h',         // 11h elapsed (1h remaining)
                12 => '0h'          // 12h elapsed (deadline passed)
            ];
            foreach ($milestones as $hours => $type) {
                if ($this->shouldSendNotification($order, $elapsedHours, $hours, $type)) {
                    $this->sendCountdownNotification($order, $type, $elapsedHours);
                    
                    // Mark this notification as sent to avoid duplicates
                    $this->markNotificationSent($order, $type);
                    $this->info("Notification sent for Order #{$order->id} - Type: {$type}");
                }else{
                    $this->info("No notification sent for Order #{$order->id} - Type: {$type} (Elapsed: {$elapsedHours}h)");
                    Log::info("OrderCountdownNotification: No notification sent for Order #{$order->id} - Type: {$type}", [
                        'order_id' => $order->id,
                        'type' => $type,
                        'elapsed_hours' => $elapsedHours
                    ]);

                }
            }
            
        } catch (\Exception $e) {
            $this->error("Error processing order {$order->id}: " . $e->getMessage());
            Log::error("OrderCountdownNotification: Error processing order {$order->id}", [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if notification should be sent
     */
    private function shouldSendNotification(Order $order, float $elapsedHours, int $milestoneHours, string $type): bool
    {
        // Don't send if order is paused
        if ($order->timer_paused_at) {
            return false;
        }
        
        // Check if this notification was already sent
        $notificationKey = "countdown_{$type}";
        
        // Handle both string and array cases for meta field
        $meta = $order->meta;
        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?? [];
        } elseif (!is_array($meta)) {
            $meta = [];
        }
        
        if (isset($meta['countdown_notifications'][$notificationKey])) {
            return false; // Already sent
        }
        
        // For start notification (12h timer started)
        if ($type === 'start' && $elapsedHours >= 0 && $elapsedHours <= 0.05) { // Within 3 minutes of start
            return true;
        }
        
        // For milestone notifications (6h, 3h, 2h, 1h remaining)
        if ($type !== 'start' && $type !== '0h') {
            $targetHours = $milestoneHours;
            // Send notification when we reach or pass the milestone (with 5-minute tolerance)
            return $elapsedHours >= ($targetHours - 0.083) && $elapsedHours <= ($targetHours + 0.083);
        }
        
        // For deadline notification (0h - deadline passed)
        if ($type === '0h' && $elapsedHours >= 12) {
            return true;
        }
        
        return false;
    }

    /**
     * Send countdown notification to Slack
     */
    private function sendCountdownNotification(Order $order, string $type, float $elapsedHours)
    {
        try {
            // Calculate remaining time
            $remainingHours = max(0, 12 - $elapsedHours);
            $remainingMinutes = ($remainingHours - floor($remainingHours)) * 60;
            
            // Prepare notification data
            $data = [
                'order_id' => $order->id,
                'order_name' => 'Order #' . $order->id,
                'customer_name' => $order->user ? $order->user->name : 'Unknown',
                'customer_email' => $order->user ? $order->user->email : 'Unknown',
                'contractor_name' => $order->assignedTo ? $order->assignedTo->name : 'Unassigned',
                'status' => ucfirst($order->status_manage_by_admin),
                'elapsed_time' => $this->formatTime($elapsedHours),
                'remaining_time' => $this->formatTime($remainingHours),
                'notification_type' => $type,
                'is_deadline_passed' => $elapsedHours >= 12
            ];

            // Format message based on notification type
            $message = $this->formatCountdownMessage($type, $data);
            
            // Send to Slack
            $result = SlackNotificationService::send('inbox-setup', $message);

            if ($result) {
                $this->info("Countdown notification sent for Order #{$order->id} - Type: {$type}");
                Log::channel('slack_notifications')->info("Order countdown notification sent", [
                    'order_id' => $order->id,
                    'type' => $type,
                    'elapsed_hours' => $elapsedHours,
                    'remaining_hours' => $remainingHours
                ]);
            } else {
                $this->warn("Failed to send countdown notification for Order #{$order->id} - Type: {$type}");
            }
            
        } catch (\Exception $e) {
            $this->error("Error sending countdown notification for Order #{$order->id}: " . $e->getMessage());
            Log::error("OrderCountdownNotification: Error sending notification", [
                'order_id' => $order->id,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Mark notification as sent in order meta
     */
    
    private function markNotificationSent(Order $order, string $type)
    {
        // Handle both string and array cases for meta field
        $meta = $order->meta;
        if (is_string($meta)) {
            $meta = json_decode($meta, true) ?? [];
        } elseif (!is_array($meta)) {
            $meta = [];
        }
        
        // Ensure countdown_notifications key exists
        if (!isset($meta['countdown_notifications'])) {
            $meta['countdown_notifications'] = [];
        }
        
        $meta['countdown_notifications']["countdown_{$type}"] = now()->toDateTimeString();
        
        $order->update(['meta' => $meta]);
    }

    /**
     * Format time in hours and minutes
     */
    private function formatTime(float $hours): string
    {
        $h = floor($hours);
        $m = floor(($hours - $h) * 60);
        
        if ($h > 0 && $m > 0) {
            return "{$h}h {$m}m";
        } elseif ($h > 0) {
            return "{$h}h";
        } elseif ($m > 0) {
            return "{$m}m";
        } else {
            return "0m";
        }
    }

    /**
     * Format countdown message for different types
     */
    private function formatCountdownMessage(string $type, array $data): array
    {
        $appName = config('app.name', 'ProjectInbox');
        
        switch ($type) {
            case 'start':
                return [
                    'text' => "⏰ *Order Timer Started - 12 Hour Countdown*",
                    'attachments' => [
                        [
                            'color' => '#17a2b8',
                            'fields' => [
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer',
                                    'value' => $data['customer_name'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Contractor',
                                    'value' => $data['contractor_name'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Status',
                                    'value' => $data['status'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Timer',
                                    'value' => '12 hours countdown started',
                                    'short' => false
                                ]
                            ],
                            'footer' => $appName . ' - Order Countdown',
                            'ts' => time()
                        ]
                    ]
                ];

            case '6h':
            case '3h':
            case '2h':
            case '1h':
                $urgencyColor = $type === '1h' ? '#ffc107' : '#28a745';
                $urgencyIcon = $type === '1h' ? '⚠️' : '⏳';
                
                return [
                    'text' => "{$urgencyIcon} *Order Countdown Alert - {$data['remaining_time']} Remaining*",
                    'attachments' => [
                        [
                            'color' => $urgencyColor,
                            'fields' => [
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer',
                                    'value' => $data['customer_name'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Contractor',
                                    'value' => $data['contractor_name'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Current Status',
                                    'value' => $data['status'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Time Elapsed',
                                    'value' => $data['elapsed_time'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Time Remaining',
                                    'value' => $data['remaining_time'],
                                    'short' => true
                                ]
                            ],
                            'footer' => $appName . ' - Order Countdown Alert',
                            'ts' => time()
                        ]
                    ]
                ];

            case '0h':
                return [
                    'text' => "🚨 *ORDER DEADLINE PASSED - IMMEDIATE ACTION REQUIRED*",
                    'attachments' => [
                        [
                            'color' => '#dc3545',
                            'fields' => [
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer',
                                    'value' => $data['customer_name'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Contractor',
                                    'value' => $data['contractor_name'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Current Status',
                                    'value' => $data['status'] . ' ❌',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Total Time Elapsed',
                                    'value' => $data['elapsed_time'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Status',
                                    'value' => 'DEADLINE EXCEEDED',
                                    'short' => true
                                ]
                            ],
                            'footer' => $appName . ' - CRITICAL ALERT',
                            'ts' => time()
                        ]
                    ]
                ];

            default:
                return [
                    'text' => "⏰ *Order Countdown Notification*",
                    'attachments' => [
                        [
                            'color' => '#6c757d',
                            'fields' => [
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'],
                                    'short' => true
                                ],
                                [
                                    'title' => 'Type',
                                    'value' => $type,
                                    'short' => true
                                ]
                            ],
                            'footer' => $appName . ' - Order Countdown',
                            'ts' => time()
                        ]
                    ]
                ];
        }
    }
}
