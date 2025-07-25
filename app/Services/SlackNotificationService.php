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
        $message = self::formatMessage('inbox-cancellation', $data);
        return self::send('inbox-cancellation', $message);
    }

    /**
     * Send inbox setup notification to Slack
     *
     * @param array $inboxData
     * @return bool
     */
    public static function sendInboxSetupNotification($inboxData)
    {
        $data = [
            'inbox_id' => $inboxData['id'] ?? 'N/A',
            'inbox_name' => $inboxData['name'] ?? 'N/A',
            'customer_name' => $inboxData['customer_name'] ?? 'Unknown',
            'customer_email' => $inboxData['customer_email'] ?? 'Unknown',
            'setup_by' => auth()->user() ? auth()->user()->name : 'System'
        ];

        // Prepare the message based on type
        $message = self::formatMessage('inbox-setup', $data);
        return self::send('inbox-setup', $message);
    }

    /**
     * Send inbox admin update notification to Slack
     *
     * @param array $inboxData
     * @param string $action
     * @return bool
     */
    public static function sendInboxAdminNotification($inboxData, $action = 'Updated')
    {
        $data = [
            'inbox_id' => $inboxData['inbox_id'] ?? 'N/A',
            'inbox_name' => $inboxData['inbox_name'] ?? 'N/A',
            'action' => $action,
            'admin_name' => $inboxData['admin_name'] ?? 'N/A',
            'admin_email' => $inboxData['admin_email'] ?? 'N/A',
            'updated_by' => auth()->user() ? auth()->user()->name : 'System'
        ];

        // Prepare the message based on type
        $message = self::formatMessage('inbox-admins', $data);
        return self::send('inbox-admins', $message);
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
                
            case 'inbox-setup':
                return [
                    'text' => "📥 *Inbox Setup Notification*",
                    'attachments' => [
                        [
                            'color' => '#28a745',
                            'fields' => [
                                [
                                    'title' => 'Inbox ID',
                                    'value' => $data['inbox_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Inbox Name',
                                    'value' => $data['inbox_name'] ?? 'N/A',
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
                                    'title' => 'Setup By',
                                    'value' => $data['setup_by'] ?? 'System',
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
                
            case 'inbox-cancellation':
                return [
                    'text' => "❌ *Inbox Cancellation Notification*",
                    'attachments' => [
                        [
                            'color' => '#dc3545',
                            'fields' => [
                                [
                                    'title' => 'Inbox ID',
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
                
            case 'inbox-admins':
                return [
                    'text' => "👥 *Inbox Admin Update Notification*",
                    'attachments' => [
                        [
                            'color' => '#007bff',
                            'fields' => [
                                [
                                    'title' => 'Inbox ID',
                                    'value' => $data['inbox_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Inbox Name',
                                    'value' => $data['inbox_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Action',
                                    'value' => $data['action'] ?? 'Updated',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Admin',
                                    'value' => $data['admin_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Admin Email',
                                    'value' => $data['admin_email'] ?? 'N/A',
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
            'inbox-setup' => ':inbox_tray:',
            'inbox-cancellation' => ':x:',
            'inbox-admins' => ':busts_in_silhouette:'
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
