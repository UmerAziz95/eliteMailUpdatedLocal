<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SlackNotificationService
{
    /**
     * Send a notification to Slack channel
     *
     * @param string $message
     * @param array $additionalData
     * @return bool
     */
    public static function sendNotification($message, $additionalData = [])
    {
        $webhookUrl = env('SLACK_WEBHOOK_URL');
        
        if (!$webhookUrl) {
            Log::warning('Slack webhook URL not configured');
            return false;
        }

        try {
            $payload = [
                'text' => $message,
                'username' => 'Order Bot',
                'icon_emoji' => ':warning:',
            ];

            // Add additional data as attachment if provided
            if (!empty($additionalData)) {
                $payload['attachments'] = [
                    [
                        'color' => 'danger',
                        'fields' => []
                    ]
                ];

                foreach ($additionalData as $key => $value) {
                    $payload['attachments'][0]['fields'][] = [
                        'title' => ucfirst(str_replace('_', ' ', $key)),
                        'value' => is_array($value) ? json_encode($value) : (string) $value,
                        'short' => true
                    ];
                }
            }

            $response = Http::post($webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('Slack notification sent successfully', ['message' => $message]);
                return true;
            } else {
                Log::error('Failed to send Slack notification', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error sending Slack notification: ' . $e->getMessage(), [
                'message' => $message,
                'error' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send order cancellation notification to Slack
     *
     * @param \App\Models\Order $order
     * @param string|null $reason
     * @return bool
     */
    public static function sendOrderCancellationNotification($order, $reason = null)
    {
        $message = "🚨 Order #{$order->id} has been cancelled";
        
        $additionalData = [
            'order_id' => $order->id,
            'customer' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'cancelled_at' => now()->format('Y-m-d H:i:s'),
            'previous_status' => $order->getOriginal('status_manage_by_admin'),
            'reason' => $reason ?: 'No reason provided'
        ];

        if ($order->assigned_to && $order->assignedTo) {
            $additionalData['assigned_contractor'] = $order->assignedTo->name;
        }

        return self::sendNotification($message, $additionalData);
    }
}
