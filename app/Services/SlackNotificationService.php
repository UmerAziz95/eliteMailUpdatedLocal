<?php

namespace App\Services;

use App\Models\SlackSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotificationService
{
    /**
     * Send notification to Slack based on event type
     *
     * @param string $type
     * @param array $data
     * @return bool
     */
    public static function send(string $type, $message): bool
    {
        try {
            // Get the webhook settings for this type
            $setting = SlackSettings::where('type', $type)
                                   ->where('status', true)
                                   ->first();
            
            if (!$setting) {
                Log::channel('slack_notifications')->info("No active Slack webhook found for type: {$type}");
                return false;
            }
            
            // Prepare payload based on message type
            $payload = [];
            if (is_array($message)) {
                // If message is an array (structured message), use it directly
                $payload = $message;
                $payload['username'] = config('app.name', 'ProjectInbox');
                $payload['icon_emoji'] = self::getEmojiForType($type);
            } else {
                // If message is a string, use simple format
                $payload = [
                    'text' => $message,
                    'username' => config('app.name', 'ProjectInbox'),
                    'icon_emoji' => self::getEmojiForType($type)
                ];
            }
            
            // Send to Slack
            $response = Http::post($setting->url, $payload);
            
            if ($response->successful()) {
                Log::channel('slack_notifications')->info("Slack notification sent successfully for type: {$type}", [
                    'type' => $type,
                    'webhook_url' => $setting->url,
                    'response_status' => $response->status()
                ]);
                return true;
            } else {
                Log::channel('slack_notifications')->error("Failed to send Slack notification. Response: " . $response->body(), [
                    'type' => $type,
                    'response_status' => $response->status(),
                    'webhook_url' => $setting->url
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
            Log::channel('slack_notifications')->error("Error sending Slack notification: " . $e->getMessage(), [
                'type' => $type,
                'exception' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            return false;
        }
    }


    /**
     * Send new order available notification to Slack
     *
     * @param array $orderData
     * @return bool
     */
    public static function sendNewOrderAvailableNotification($orderData)
    {
        $data = [
            'order_id' => $orderData['order_id'] ?? $orderData['id'] ?? 'N/A',
            'order_name' => $orderData['name'] ?? 'N/A',
            'customer_name' => $orderData['customer_name'] ?? 'Unknown',
            'customer_email' => $orderData['customer_email'] ?? 'Unknown',
            'contractor_name' => $orderData['contractor_name'] ?? 'Unassigned',
            'inbox_count' => $orderData['inbox_count'] ?? 0,
            'split_count' => $orderData['split_count'] ?? 0,
            'previous_status' => $orderData['previous_status'] ?? 'N/A',
            'new_status' => $orderData['new_status'] ?? 'N/A',
            'updated_by' => auth()->user() ? auth()->user()->name : 'System'
        ];

        // Prepare the message based on type
        $message = self::formatMessage('new-order-available', $data);
        return self::send('inbox-setup', $message);
    }

    /**
     * Send order rejection notification to Slack
     *
     * @param \App\Models\Order $order
     * @param string|null $reason
     * @return bool
     */
    public static function sendOrderRejectionNotification($order, $reason = null)
    {
        // Calculate inbox count and split count
        $inboxCount = 0;
        $splitCount = 0;
        
        if ($order->orderPanels && $order->orderPanels->count() > 0) {
            foreach ($order->orderPanels as $orderPanel) {
                $splitCount += $orderPanel->orderPanelSplits ? $orderPanel->orderPanelSplits->count() : 0;
                
                foreach ($orderPanel->orderPanelSplits as $split) {
                    if ($split->domains && is_array($split->domains)) {
                        $inboxCount += count($split->domains) * ($split->inboxes_per_domain ?? 1);
                    }
                }
            }
        }
        
        // If no splits found, try to get from reorderInfo
        if ($inboxCount === 0 && $order->reorderInfo && $order->reorderInfo->first()) {
            $inboxCount = $order->reorderInfo->first()->total_inboxes ?? 0;
        }

        $data = [
            'order_id' => $order->id,
            'order_name' => 'Order #' . $order->id,
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'contractor_name' => $order->assignedTo ? $order->assignedTo->name : 'Unassigned',
            'inbox_count' => $inboxCount,
            'split_count' => $splitCount,
            'reason' => $reason ?: 'No reason provided',
            'rejected_by' => auth()->user() ? auth()->user()->name : 'System'
        ];

        // Prepare the message based on type
        $message = self::formatMessage('order-rejection', $data);
        return self::send('inbox-setup', $message);
    }

    /**
     * Send order completion notification to Slack
     *
     * @param \App\Models\Order $order
     * @return bool
     */
    public static function sendOrderCompletionNotification($order)
    {
        // Calculate inbox count and split count
        $inboxCount = 0;
        $splitCount = 0;
        
        if ($order->orderPanels && $order->orderPanels->count() > 0) {
            foreach ($order->orderPanels as $orderPanel) {
                $splitCount += $orderPanel->orderPanelSplits ? $orderPanel->orderPanelSplits->count() : 0;
                
                foreach ($orderPanel->orderPanelSplits as $split) {
                    if ($split->domains && is_array($split->domains)) {
                        $inboxCount += count($split->domains) * ($split->inboxes_per_domain ?? 1);
                    }
                }
            }
        }
        
        // If no splits found, try to get from reorderInfo
        if ($inboxCount === 0 && $order->reorderInfo && $order->reorderInfo->first()) {
            $inboxCount = $order->reorderInfo->first()->total_inboxes ?? 0;
        }

        // Calculate completion time if available
        $completionTime = null;
        $workingTime = null;
        if ($order->completed_at && $order->timer_started_at) {
            $completionTime = $order->completed_at->format('Y-m-d H:i:s T');
            $workingTimeSeconds = $order->getEffectiveWorkingTimeSeconds();
            $hours = floor($workingTimeSeconds / 3600);
            $minutes = floor(($workingTimeSeconds % 3600) / 60);
            $workingTime = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
        }

        $data = [
            'order_id' => $order->id,
            'order_name' => 'Order #' . $order->id,
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'contractor_name' => $order->assignedTo ? $order->assignedTo->name : 'Unassigned',
            'inbox_count' => $inboxCount,
            'split_count' => $splitCount,
            'completed_at' => $completionTime ?: 'N/A',
            'working_time' => $workingTime ?: 'N/A',
            'completed_by' => auth()->user() ? auth()->user()->name : 'System'
        ];

        // Prepare the message based on type
        $message = self::formatMessage('order-completion', $data);
        return self::send('inbox-setup', $message);
    }

    /**
     * Send order assignment notification to Slack
     *
     * @param \App\Models\Order $order
     * @return bool
     */
    public static function sendOrderAssignmentNotification($order)
    {
        // Calculate inbox count and split count
        $inboxCount = 0;
        $splitCount = 0;
        
        if ($order->orderPanels && $order->orderPanels->count() > 0) {
            foreach ($order->orderPanels as $orderPanel) {
                $splitCount += $orderPanel->orderPanelSplits ? $orderPanel->orderPanelSplits->count() : 0;
                
                foreach ($orderPanel->orderPanelSplits as $split) {
                    if ($split->domains && is_array($split->domains)) {
                        $inboxCount += count($split->domains) * ($split->inboxes_per_domain ?? 1);
                    }
                }
            }
        }
        
        // If no splits found, try to get from reorderInfo
        if ($inboxCount === 0 && $order->reorderInfo && $order->reorderInfo->first()) {
            $inboxCount = $order->reorderInfo->first()->total_inboxes ?? 0;
        }

        $data = [
            'order_id' => $order->id,
            'order_name' => 'Order #' . $order->id,
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'contractor_name' => $order->assignedTo ? $order->assignedTo->name : 'Unassigned',
            'contractor_email' => $order->assignedTo ? $order->assignedTo->email : 'N/A',
            'inbox_count' => $inboxCount,
            'split_count' => $splitCount,
            'assigned_by' => auth()->user() ? auth()->user()->name : 'System'
        ];

        // Prepare the message based on type
        $message = self::formatMessage('order-assignment', $data);
        return self::send('inbox-setup', $message);
    }

    /**
     * Send inbox cancellation notification to Slack
     *
     * @param \App\Models\Order $order
     * @param string|null $reason
     * @return bool
     */
    public static function sendOrderCancellationNotification($order, $reason = null)
    {
        $data = [
            'inbox_id' => $order->id,
            'order_id' => $order->id, // Keep for backward compatibility
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'reason' => $reason ?: 'No reason provided',
            'cancelled_by' => auth()->user() ? auth()->user()->name : 'System'
        ];
        // Prepare the message based on type
        $message = self::formatMessage('order-cancellation', $data);
        return self::send('inbox-cancellation', $message);
    }
    
    /**
     * Format message based on type and data
     *
     * @param string $type
     * @param array $data
     * @return array
     */
    private static function formatMessage(string $type, array $data): array
    {
        $appName = config('app.name', 'ProjectInbox');
        
        switch ($type) {
            case 'new-order-available':
                return [
                    'text' => "🆕 *New Order Available Notification*",
                    'attachments' => [
                        [
                            'color' => '#17a2b8',
                            'fields' => [
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Order Name',
                                    'value' => $data['order_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer Name',
                                    'value' => $data['customer_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer Email',
                                    'value' => $data['customer_email'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Contractor Name',
                                    'value' => $data['contractor_name'] ?? 'Unassigned',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Inbox Count',
                                    'value' => $data['inbox_count'] ?? '0',
                                    'short' => true
                                ],
                                // [
                                //     'title' => 'Split Count',
                                //     'value' => $data['split_count'] ?? '0',
                                //     'short' => true
                                // ],
                                [
                                    'title' => 'Previous Status',
                                    'value' => ucfirst($data['previous_status'] ?? 'N/A'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'New Status',
                                    'value' => ucfirst($data['new_status'] ?? 'N/A'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Updated By',
                                    'value' => $data['updated_by'] ?? 'System',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Timestamp',
                                    'value' => now()->format('Y-m-d H:i:s T'),
                                    'short' => false
                                ]
                            ],
                            'footer' => $appName . ' Slack Integration',
                            'ts' => time()
                        ]
                    ]
                ];
                
            case 'order-rejection':
                return [
                    'text' => "❌ *Order Rejection Notification*",
                    'attachments' => [
                        [
                            'color' => '#dc3545',
                            'fields' => [
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Order Name',
                                    'value' => $data['order_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer Name',
                                    'value' => $data['customer_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer Email',
                                    'value' => $data['customer_email'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Contractor Name',
                                    'value' => $data['contractor_name'] ?? 'Unassigned',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Inbox Count',
                                    'value' => $data['inbox_count'] ?? '0',
                                    'short' => true
                                ],
                                // [
                                //     'title' => 'Split Count',
                                //     'value' => $data['split_count'] ?? '0',
                                //     'short' => true
                                // ],
                                [
                                    'title' => 'Rejected By',
                                    'value' => $data['rejected_by'] ?? 'System',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Reason',
                                    'value' => $data['reason'] ?? 'No reason provided',
                                    'short' => false
                                ],
                                [
                                    'title' => 'Timestamp',
                                    'value' => now()->format('Y-m-d H:i:s T'),
                                    'short' => true
                                ]
                            ],
                            'footer' => $appName . ' Slack Integration',
                            'ts' => time()
                        ]
                    ]
                ];
                
            case 'order-completion':
                return [
                    'text' => "✅ *Order Completion Notification*",
                    'attachments' => [
                        [
                            'color' => '#28a745',
                            'fields' => [
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Order Name',
                                    'value' => $data['order_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer Name',
                                    'value' => $data['customer_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer Email',
                                    'value' => $data['customer_email'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Contractor Name',
                                    'value' => $data['contractor_name'] ?? 'Unassigned',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Inbox Count',
                                    'value' => $data['inbox_count'] ?? '0',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Split Count',
                                    'value' => $data['split_count'] ?? '0',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Working Time',
                                    'value' => $data['working_time'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Completed By',
                                    'value' => $data['completed_by'] ?? 'System',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Completed At',
                                    'value' => $data['completed_at'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Timestamp',
                                    'value' => now()->format('Y-m-d H:i:s T'),
                                    'short' => false
                                ]
                            ],
                            'footer' => $appName . ' Slack Integration',
                            'ts' => time()
                        ]
                    ]
                ];
                
            case 'order-assignment':
                return [
                    'text' => "👤 *Order Assignment Notification*",
                    'attachments' => [
                        [
                            'color' => '#007bff',
                            'fields' => [
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Order Name',
                                    'value' => $data['order_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer Name',
                                    'value' => $data['customer_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer Email',
                                    'value' => $data['customer_email'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Assigned To',
                                    'value' => $data['contractor_name'] ?? 'Unassigned',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Contractor Email',
                                    'value' => $data['contractor_email'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Inbox Count',
                                    'value' => $data['inbox_count'] ?? '0',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Split Count',
                                    'value' => $data['split_count'] ?? '0',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Assigned By',
                                    'value' => $data['assigned_by'] ?? 'System',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Timestamp',
                                    'value' => now()->format('Y-m-d H:i:s T'),
                                    'short' => true
                                ]
                            ],
                            'footer' => $appName . ' Slack Integration',
                            'ts' => time()
                        ]
                    ]
                ];

            case 'order-cancellation':
                return [
                    'text' => "❌ *Order Cancellation Notification*",
                    'attachments' => [
                        [
                            'color' => '#dc3545',
                            'fields' => [
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['inbox_id'] ?? $data['order_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer',
                                    'value' => $data['customer_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer Email',
                                    'value' => $data['customer_email'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Cancelled By',
                                    'value' => $data['cancelled_by'] ?? 'System',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Reason',
                                    'value' => $data['reason'] ?? 'No reason provided',
                                    'short' => false
                                ],
                                [
                                    'title' => 'Timestamp',
                                    'value' => now()->format('Y-m-d H:i:s T'),
                                    'short' => true
                                ]
                            ],
                            'footer' => $appName . ' Slack Integration',
                            'ts' => time()
                        ]
                    ]
                ];
                
            
                
            default:
                return [
                    'text' => "🔔 *Notification*",
                    'attachments' => [
                        [
                            'color' => '#6c757d',
                            'fields' => [
                                [
                                    'title' => 'Type',
                                    'value' => $type,
                                    'short' => true
                                ],
                                [
                                    'title' => 'Data',
                                    'value' => json_encode($data),
                                    'short' => false
                                ],
                                [
                                    'title' => 'Timestamp',
                                    'value' => now()->format('Y-m-d H:i:s T'),
                                    'short' => true
                                ]
                            ],
                            'footer' => $appName . ' Slack Integration',
                            'ts' => time()
                        ]
                    ]
                ];
        }
    }
    
    /**
     * Get emoji for notification type
     *
     * @param string $type
     * @return string
     */
    private static function getEmojiForType(string $type): string
    {
        $emojis = [
            'new-order-available' => ':new:',
            'order-rejection' => ':no_entry_sign:',
            'order-completion' => ':white_check_mark:',
            'order-assignment' => ':bust_in_silhouette:',
            'order-cancellation' => ':x:',
            'order-countdown' => ':alarm_clock:',
        ];
        
        return $emojis[$type] ?? ':bell:';
    }
    
    /**
     * Get available notification types
     *
     * @return array
     */
    public static function getAvailableTypes(): array
    {
        return SlackSettings::getTypes();
    }
}
