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
    public static function send(string $type, array $data = []): bool
    {
        try {
            // Get the webhook settings for this type
            $setting = SlackSettings::where('type', $type)
                                   ->where('status', true)
                                   ->first();
            
            if (!$setting) {
                Log::info("No active Slack webhook found for type: {$type}");
                return false;
            }
            
            // Prepare the message based on type
            $message = self::formatMessage($type, $data);
            
            // Send to Slack
            $response = Http::post($setting->url, [
                'text' => $message,
                'username' => config('app.name', 'ProjectInbox'),
                'icon_emoji' => self::getEmojiForType($type)
            ]);
            
            if ($response->successful()) {
                Log::info("Slack notification sent successfully for type: {$type}");
                return true;
            } else {
                Log::error("Failed to send Slack notification. Response: " . $response->body());
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error("Error sending Slack notification: " . $e->getMessage());
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
        $data = [
            'order_id' => $order->id,
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'reason' => $reason ?: 'No reason provided'
        ];

        return self::send('inbox-cancellation', $data);
    }
    
    /**
     * Format message based on type and data
     *
     * @param string $type
     * @param array $data
     * @return string
     */
    private static function formatMessage(string $type, array $data): string
    {
        $baseUrl = config('app.url');
        
        switch ($type) {
            case 'order-cancelled':
                return "🚫 *Order Cancelled*\n" .
                       "Order ID: {$data['order_id']}\n" .
                       "Customer: {$data['customer_name']}\n" .
                       "Reason: {$data['reason']}\n" .
                       "Time: " . now()->format('Y-m-d H:i:s');
                       
            case 'panel-created':
                return "🆕 *New Panel Created*\n" .
                       "Panel ID: {$data['panel_id']}\n" .
                       "Name: {$data['panel_name']}\n" .
                       "Capacity: {$data['capacity']}\n" .
                       "Created by: {$data['created_by']}\n" .
                       "Time: " . now()->format('Y-m-d H:i:s');
                       
            case 'order-created':
                return "📦 *New Order Created*\n" .
                       "Order ID: {$data['order_id']}\n" .
                       "Customer: {$data['customer_name']}\n" .
                       "Plan: {$data['plan_name']}\n" .
                       "Amount: $" . number_format($data['amount'], 2) . "\n" .
                       "Time: " . now()->format('Y-m-d H:i:s');
                       
            case 'order-updated':
                return "📝 *Order Updated*\n" .
                       "Order ID: {$data['order_id']}\n" .
                       "Status: {$data['status']}\n" .
                       "Updated by: {$data['updated_by']}\n" .
                       "Time: " . now()->format('Y-m-d H:i:s');
                       
            case 'user-registered':
                return "👤 *New User Registered*\n" .
                       "Name: {$data['user_name']}\n" .
                       "Email: {$data['user_email']}\n" .
                       "Role: {$data['role']}\n" .
                       "Time: " . now()->format('Y-m-d H:i:s');
                       
            case 'invoice-generated':
                return "🧾 *Invoice Generated*\n" .
                       "Invoice ID: {$data['invoice_id']}\n" .
                       "Customer: {$data['customer_name']}\n" .
                       "Amount: $" . number_format($data['amount'], 2) . "\n" .
                       "Due Date: {$data['due_date']}\n" .
                       "Time: " . now()->format('Y-m-d H:i:s');
                       
            default:
                return "🔔 *Notification*\n" .
                       "Type: {$type}\n" .
                       "Data: " . json_encode($data) . "\n" .
                       "Time: " . now()->format('Y-m-d H:i:s');
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
            'order-cancelled' => ':x:',
            'panel-created' => ':new:',
            'order-created' => ':package:',
            'order-updated' => ':memo:',
            'user-registered' => ':bust_in_silhouette:',
            'invoice-generated' => ':receipt:'
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
