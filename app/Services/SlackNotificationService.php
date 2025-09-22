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
            Log::channel('slack_notifications')->info("test 1 notification service============================");

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
             Log::channel('slack_notifications')->info("test 2 notification service============================");

            if (is_array($message)) {
            Log::channel('slack_notifications')->info("test 3 notification service============================");

                // If message is an array (structured message), use it directly
                $payload = $message;
                $payload['username'] = config('app.name', 'ProjectInbox');
                $payload['icon_emoji'] = self::getEmojiForType($type);
            } else {
            Log::channel('slack_notifications')->info("test 4 notification service============================");

                // If message is a string, use simple format
                $payload = [
                    'text' => $message,
                    'username' => config('app.name', 'ProjectInbox'),
                    'icon_emoji' => self::getEmojiForType($type)
                ];
            }
            
            // Send to Slack cn
            $response = Http::post($setting->url, $payload);
            
            if ($response->successful()) {
                            Log::channel('slack_notifications')->info("test 5 notification service============================");

                Log::channel('slack_notifications')->info("Slack notification sent successfully for type: {$type}", [
                    'type' => $type,
                    'webhook_url' => $setting->url,
                    'response_status' => $response->status()
                ]);
                return true;
            } else {
                            Log::channel('slack_notifications')->info("test 6 notification service============================");

                Log::channel('slack_notifications')->error("Failed to send Slack notification. Response: " . $response->body(), [
                    'type' => $type,
                    'response_status' => $response->status(),
                    'webhook_url' => $setting->url
                ]);
                return false;
            }
            
        } catch (\Exception $e) {
                        Log::channel('slack_notifications')->info("test 7 notification service============================");

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
     * Send order created notification to Slack
     *
     * @param \App\Models\Order $order
     * @return bool
     */
    public static function sendOrderCreatedNotification($order, $inboxCount = 0)
    {
        // get plan name
        $planName = $order->plan ? $order->plan->name : 'N/A';

        // Calculate inbox count and split count
        $splitCount = 0;
        $data = [
            'order_id' => $order->id,
            'order_name' => 'Order #' . $order->id,
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'status' => ucfirst($order->status_manage_by_admin),
            'inbox_count' => $inboxCount,
            'plan_name' => $planName,
            // 'split_count' => $splitCount,
            'created_by' => auth()->user() ? auth()->user()->name : 'System',
            'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s T') : 'N/A'
        ];

        // Prepare the message based on type
        $message = self::formatMessage('order-created', $data);
        return self::send('inbox-setup', $message);
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
    Log::channel('slack_notifications')->info("test 1 slack notification sendOrderAssignmentNotification service============================");

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
            'assigned_by' => auth()->user() ? auth()->user()->name : 'System',
            'reassignment_note' => $order->reassignment_note
        ];

        // Prepare the message based on type
        $message = self::formatMessage('order-assignment', $data);
        Log::channel('slack_notifications')->info("test 1 slack notification sendOrderAssignmentNotification service============================");

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
     * Send invoice generated notification to Slack
     *
     * @param \App\Models\Invoice $invoice
     * @param \App\Models\User $user
     * @param bool $isPaymentFailed
     * @return bool
     */
    public static function sendInvoiceGeneratedNotification($invoice, $user, $isPaymentFailed = false)
    {
        // Check if this is the first invoice for this user (new payment) or recurring
        $previousInvoices = \App\Models\Invoice::where('user_id', $user->id)
            ->where('chargebee_invoice_id', '!=', $invoice->chargebee_invoice_id)
            ->where('order_id', $invoice->order_id)
            ->count();
        
        $isNewPayment = $previousInvoices === 0;
        $paymentType = $isNewPayment ? 'new' : 'recurring';
        
        // Get order information
        $order = \App\Models\Order::find($invoice->order_id);
        
        $data = [
            'invoice_id' => $invoice->chargebee_invoice_id,
            'order_id' => $invoice->order_id,
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'amount' => $invoice->amount,
            'status' => $invoice->status,
            'payment_type' => $paymentType,
            'is_payment_failed' => $isPaymentFailed,
            'paid_at' => $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('Y-m-d H:i:s T') : 'N/A',
            'plan_name' => $order && $order->plan ? $order->plan->name : 'N/A',
            'currency' => $order ? $order->currency : 'USD'
        ];

        // Determine message type based on payment failure and type
        if ($isPaymentFailed) {
            $messageType = $isNewPayment ? 'invoice-payment-failed-new' : 'invoice-payment-failed-recurring';
        } else {
            $messageType = $isNewPayment ? 'invoice-generated-new' : 'invoice-generated-recurring';
        }

        // Prepare the message based on type
        $message = self::formatMessage($messageType, $data);
        return self::send('inbox-subscriptions', $message);
    }

    /**
     * Send support ticket created notification to Slack
     *
     * @param \App\Models\SupportTicket $ticket
     * @return bool
     */
    public static function sendSupportTicketCreatedNotification($ticket)
    {
        $data = [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'category' => $ticket->category,
            'customer_name' => $ticket->user ? $ticket->user->name : 'Unknown',
            'customer_email' => $ticket->user ? $ticket->user->email : 'Unknown',
            'assigned_to_name' => $ticket->assignedTo ? $ticket->assignedTo->name : 'Unassigned',
            'assigned_to_email' => $ticket->assignedTo ? $ticket->assignedTo->email : 'N/A',
            'order_id' => $ticket->order_id,
            'attachments' => $ticket->attachments,
            'created_at' => $ticket->created_at ? $ticket->created_at->format('Y-m-d H:i:s T') : 'N/A'
        ];

        $message = self::formatMessage('support-ticket-created', $data);
        return self::send('inbox-tickets', $message);
    }

    /**
     * Send support ticket updated notification to Slack
     *
     * @param \App\Models\SupportTicket $ticket
     * @param array $changes
     * @return bool
     */
    public static function sendSupportTicketUpdatedNotification($ticket, $changes = [])
    {
        $data = [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'category' => $ticket->category,
            'customer_name' => $ticket->user ? $ticket->user->name : 'Unknown',
            'customer_email' => $ticket->user ? $ticket->user->email : 'Unknown',
            'assigned_to_name' => $ticket->assignedTo ? $ticket->assignedTo->name : 'Unassigned',
            'assigned_to_email' => $ticket->assignedTo ? $ticket->assignedTo->email : 'N/A',
            'order_id' => $ticket->order_id,
            'changes' => $changes,
            'updated_at' => $ticket->updated_at ? $ticket->updated_at->format('Y-m-d H:i:s T') : 'N/A',
            'updated_by' => auth()->user() ? auth()->user()->name : 'System'
        ];

        $message = self::formatMessage('support-ticket-updated', $data);
        return self::send('inbox-tickets', $message);
    }

    /**
     * Send support ticket reply notification to Slack
     *
     * @param \App\Models\TicketReply $reply
     * @return bool
     */
    public static function sendSupportTicketReplyNotification($reply)
    {
        $ticket = $reply->ticket;
        
        $data = [
            'ticket_id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'subject' => $ticket->subject,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'category' => $ticket->category,
            'reply_message' => $reply->message,
            'is_internal' => $reply->is_internal,
            'customer_name' => $ticket->user ? $ticket->user->name : 'Unknown',
            'customer_email' => $ticket->user ? $ticket->user->email : 'Unknown',
            'replied_by_name' => $reply->user ? $reply->user->name : 'Unknown',
            'replied_by_email' => $reply->user ? $reply->user->email : 'Unknown',
            'order_id' => $ticket->order_id,
            'reply_attachments' => $reply->attachments,
            'created_at' => $reply->created_at ? $reply->created_at->format('Y-m-d H:i:s T') : 'N/A'
        ];

        $message = self::formatMessage('support-ticket-reply', $data);
        return self::send('inbox-tickets', $message);
    }

    /**
     * Send customized email creation notification to Slack
     *
     * @param \App\Models\OrderPanel $orderPanel
     * @param int $emailCount
     * @param string|null $customizedNote
     * @return bool
     */
    public static function sendCustomizedEmailCreatedNotification($orderPanel, $emailCount, $customizedNote = null)
    {
        $data = [
            'order_id' => $orderPanel->order_id,
            'order_panel_id' => $orderPanel->id,
            'customer_name' => $orderPanel->order && $orderPanel->order->user ? $orderPanel->order->user->name : 'Unknown',
            'customer_email' => $orderPanel->order && $orderPanel->order->user ? $orderPanel->order->user->email : 'Unknown',
            'email_count' => $emailCount,
            'customized_note' => $customizedNote,
            'created_by' => auth()->user() ? auth()->user()->name : 'Admin',
            'created_at' => now()->format('Y-m-d H:i:s T')
        ];

        $message = self::formatMessage('customized-emails-created', $data);
        return self::send('inbox-setup', $message);
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
            case 'invoice-generated-new':
                Log::channel('slack_notifications')->info("Formatting message for type: {$type}", [
                    'data' => $data,
                    'amount_with_number_format' => ($data['currency'] ?? 'USD') . ' ' . number_format(floatval($data['amount'] ?? 0), 2, '.', ','),
                    'amount_without_format' => ($data['currency'] ?? 'USD') . ' ' . $data['amount'] ?? 0,
                ]);
                return [
                    'text' => "💳 *New Payment Invoice Generated*",
                    'attachments' => [
                        [
                            'color' => '#17a2b8',
                            'fields' => [
                                [
                                    'title' => 'Invoice ID',
                                    'value' => $data['invoice_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'] ?? 'N/A',
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
                                    'title' => 'Plan',
                                    'value' => $data['plan_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Amount',
                                    // 'value' => ($data['currency'] ?? 'USD') . ' ' . ($data['amount'] ?? '0'),
                                    'value' => ($data['currency'] ?? 'USD') . ' ' . number_format(floatval($data['amount'] ?? 0), 2, '.', ','),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Status',
                                    'value' => ucfirst($data['status'] ?? 'N/A'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Payment Type',
                                    'value' => 'New Customer Payment',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Generated At',
                                    'value' => now()->format('Y-m-d H:i:s T'),
                                    'short' => false
                                ]
                            ],
                            'footer' => config('app.name', 'ProjectInbox') . ' - Invoice System',
                            'ts' => time()
                        ]
                    ]
                ];

            case 'invoice-generated-recurring':
                return [
                    'text' => "🔄 *Recurring Payment Invoice Generated*",
                    'attachments' => [
                        [
                            'color' => '#28a745',
                            'fields' => [
                                [
                                    'title' => 'Invoice ID',
                                    'value' => $data['invoice_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'] ?? 'N/A',
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
                                    'title' => 'Plan',
                                    'value' => $data['plan_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Amount',
                                    'value' => ($data['currency'] ?? 'USD') . ' ' . ($data['amount'] ?? '0'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Status',
                                    'value' => ucfirst($data['status'] ?? 'N/A'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Payment Type',
                                    'value' => 'Recurring Payment',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Generated At',
                                    'value' => now()->format('Y-m-d H:i:s T'),
                                    'short' => false
                                ]
                            ],
                            'footer' => config('app.name', 'ProjectInbox') . ' - Invoice System',
                            'ts' => time()
                        ]
                    ]
                ];

            case 'invoice-payment-failed-new':
                return [
                    'text' => "❌ *New Payment Failed*",
                    'attachments' => [
                        [
                            'color' => '#dc3545',
                            'fields' => [
                                [
                                    'title' => 'Invoice ID',
                                    'value' => $data['invoice_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'] ?? 'N/A',
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
                                    'title' => 'Plan',
                                    'value' => $data['plan_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Amount',
                                    'value' => ($data['currency'] ?? 'USD') . ' ' . ($data['amount'] ?? '0'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Status',
                                    'value' => 'PAYMENT FAILED',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Payment Type',
                                    'value' => 'New Customer Payment',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Failed At',
                                    'value' => now()->format('Y-m-d H:i:s T'),
                                    'short' => false
                                ]
                            ],
                            'footer' => config('app.name', 'ProjectInbox') . ' - Payment Alert',
                            'ts' => time()
                        ]
                    ]
                ];

            case 'invoice-payment-failed-recurring':
                return [
                    'text' => "⚠️ *Recurring Payment Failed*",
                    'attachments' => [
                        [
                            'color' => '#ffc107',
                            'fields' => [
                                [
                                    'title' => 'Invoice ID',
                                    'value' => $data['invoice_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'] ?? 'N/A',
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
                                    'title' => 'Plan',
                                    'value' => $data['plan_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Amount',
                                    'value' => ($data['currency'] ?? 'USD') . ' ' . ($data['amount'] ?? '0'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Status',
                                    'value' => 'PAYMENT FAILED',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Payment Type',
                                    'value' => 'Recurring Payment',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Failed At',
                                    'value' => now()->format('Y-m-d H:i:s T'),
                                    'short' => false
                                ]
                            ],
                            'footer' => config('app.name', 'ProjectInbox') . ' - Payment Alert',
                            'ts' => time()
                        ]
                    ]
                ];

            case 'order-created':
                return [
                    'text' => "🎉 *New Order Created*",
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
                                    'title' => 'Status',
                                    'value' => 'Draft' ?? 'N/A',
                                    'short' => true
                                ],
                                // [
                                //     'title' => 'Order Name',
                                //     'value' => $data['order_name'] ?? 'N/A',
                                //     'short' => true
                                // ],
                                [
                                    'title' => 'Customer Name',
                                    'value' => $data['customer_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                // [
                                //     'title' => 'Customer Email',
                                //     'value' => $data['customer_email'] ?? 'N/A',
                                //     'short' => true
                                // ],
                                // Plan Name
                                [
                                    'title' => 'Plan Name',
                                    'value' => $data['plan_name'] ?? 'N/A',
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
                                    'title' => 'Created By',
                                    'value' => $data['created_by'] ?? 'System',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Created At',
                                    'value' => $data['created_at'] ?? 'N/A',
                                    'short' => true
                                ]
                            ],
                            'footer' => $appName . ' Slack Integration',
                            'ts' => time()
                        ]
                    ]
                ];
                
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
                                // [
                                //     'title' => 'Order Name',
                                //     'value' => $data['order_name'] ?? 'N/A',
                                //     'short' => true
                                // ],
                                [
                                    'title' => 'Customer Name',
                                    'value' => $data['customer_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                // [
                                //     'title' => 'Customer Email',
                                //     'value' => $data['customer_email'] ?? 'N/A',
                                //     'short' => true
                                // ],
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
                                // [
                                //     'title' => 'Customer Email',
                                //     'value' => $data['customer_email'] ?? 'N/A',
                                //     'short' => true
                                // ],
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
                                // [
                                //     'title' => 'Customer Email',
                                //     'value' => $data['customer_email'] ?? 'N/A',
                                //     'short' => true
                                // ],
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
    $fields = [
        [
            'title' => 'Order ID',
            'value' => $data['order_id'] ?? 'N/A',
            'short' => true
        ],
        [
            'title' => 'Current Status',
            'value' => $data['status_manage_by_admin'] ?? 'N/A',
            'short' => true
        ], 
        [
            'title' => 'Customer Name',
            'value' => $data['customer_name'] ?? 'N/A',
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
        ],
    ];

    // ✅ Add Reassignment Note only if not empty
    if (!empty($data['reassignment_note'])) {
        $fields[] = [
            'title' => 'Reassignment Note',
            'value' => $data['reassignment_note'],
            'short' => false
        ];
    }

    return [
        'text' => "👤 *Order Assignment Notification*",
        'attachments' => [
            [
                'color'  => '#007bff',
                'fields' => $fields,
                'footer' => $appName . ' Slack Integration',
                'ts'     => time(),
            ]
        ]
    ];


            case 'customized-emails-created':
                $attachments = [
                    [
                        'color' => '#17a2b8',
                        'fields' => [
                            [
                                'title' => 'Order ID',
                                'value' => '#' . ($data['order_id'] ?? 'N/A'),
                                'short' => true
                            ],
                            [
                                'title' => 'Split ID',
                                'value' => '#' . ($data['order_panel_id'] ?? 'N/A'),
                                'short' => true
                            ],
                            [
                                'title' => 'Customer Name',
                                'value' => $data['customer_name'] ?? 'Unknown',
                                'short' => true
                            ],
                            // [
                            //     'title' => 'Customer Email',
                            //     'value' => $data['customer_email'] ?? 'Unknown',
                            //     'short' => true
                            // ],
                            [
                                'title' => 'Email Count',
                                'value' => $data['email_count'] ?? '0',
                                'short' => true
                            ],
                            [
                                'title' => 'Created By',
                                'value' => $data['created_by'] ?? 'Admin',
                                'short' => true
                            ]
                        ],
                        'footer' => $appName . ' - Email Management System',
                        'ts' => time()
                    ]
                ];

                // Add customized note as a separate attachment if provided
                if (!empty($data['customized_note'])) {
                    $attachments[] = [
                        'color' => '#6c757d',
                        'fields' => [
                            [
                                'title' => 'Customized Note',
                                'value' => $data['customized_note'],
                                'short' => false
                            ]
                        ]
                    ];
                }

                return [
                    'text' => "📧 *Customized Emails Created*",
                    'attachments' => $attachments
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
                                // [
                                //     'title' => 'Customer Email',
                                //     'value' => $data['customer_email'] ?? 'N/A',
                                //     'short' => true
                                // ],
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
                
            case 'support-ticket-created':
                $fields = [
                    [
                        'title' => 'Ticket Number',
                        'value' => $data['ticket_number'] ?? 'N/A',
                        'short' => true
                    ],
                    [
                        'title' => 'Priority',
                        'value' => ucfirst($data['priority'] ?? 'N/A'),
                        'short' => true
                    ],
                    [
                        'title' => 'Status',
                        'value' => ucfirst($data['status'] ?? 'N/A'),
                        'short' => true
                    ],
                    [
                        'title' => 'Category',
                        'value' => ucfirst($data['category'] ?? 'N/A'),
                        'short' => true
                    ],
                    [
                        'title' => 'Customer Name',
                        'value' => $data['customer_name'] ?? 'Unknown',
                        'short' => true
                    ],
                    [
                        'title' => 'Customer Email',
                        'value' => $data['customer_email'] ?? 'Unknown',
                        'short' => true
                    ],
                    [
                        'title' => 'Assigned To',
                        'value' => $data['assigned_to_name'] ?? 'Unassigned',
                        'short' => true
                    ]
                ];

                // Add Order ID field only if category is 'order'
                if (isset($data['category']) && strtolower($data['category']) === 'order' && !empty($data['order_id'])) {
                    $fields[] = [
                        'title' => 'Order ID',
                        'value' => '#' . $data['order_id'],
                        'short' => true
                    ];
                }

                $fields = array_merge($fields, [
                    [
                        'title' => 'Subject',
                        'value' => $data['subject'] ?? 'N/A',
                        'short' => false
                    ],
                    [
                        'title' => 'Description',
                        'value' => isset($data['description']) ? 
                            (strlen(strip_tags($data['description'])) > 300 ? 
                                substr(strip_tags($data['description']), 0, 300) . '...' : 
                                strip_tags($data['description'])) : 'N/A',
                        'short' => false
                    ],
                    [
                        'title' => 'Created At',
                        'value' => $data['created_at'] ?? 'N/A',
                        'short' => false
                    ]
                ]);

                $attachments = [
                    [
                        'color' => '#007bff',
                        'fields' => $fields,
                        'footer' => $appName . ' - Support Ticket System',
                        'ts' => time()
                    ]
                ];

                // Add file attachments if available
                if (!empty($data['attachments']) && is_array($data['attachments'])) {
                    foreach ($data['attachments'] as $attachment) {
                        $fileAttachment = self::buildSlackAttachment($attachment);
                        $attachments[] = $fileAttachment;
                    }
                }

                return [
                    'text' => "🎫 *New Support Ticket Created*",
                    'attachments' => $attachments
                ];

            case 'support-ticket-updated':
                $changesText = '';
                if (!empty($data['changes'])) {
                    foreach ($data['changes'] as $field => $change) {
                        $fieldName = ucwords(str_replace('_', ' ', $field));
                        $changesText .= "• *{$fieldName}*: {$change['from']} → {$change['to']}\n";
                    }
                }
                
                return [
                    'text' => "🔄 *Support Ticket Updated*",
                    'attachments' => [
                        [
                            'color' => '#ffc107',
                            'fields' => [
                                [
                                    'title' => 'Ticket Number',
                                    'value' => $data['ticket_number'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Priority',
                                    'value' => ucfirst($data['priority'] ?? 'N/A'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Status',
                                    'value' => ucfirst(str_replace('_', ' ', $data['status'] ?? 'N/A')),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Category',
                                    'value' => ucfirst($data['category'] ?? 'N/A'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer Name',
                                    'value' => $data['customer_name'] ?? 'Unknown',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Assigned To',
                                    'value' => $data['assigned_to_name'] ?? 'Unassigned',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Subject',
                                    'value' => $data['subject'] ?? 'N/A',
                                    'short' => false
                                ],
                                // [
                                //     'title' => 'Changes Made',
                                //     'value' => $changesText ?: 'No specific changes tracked',
                                //     'short' => false
                                // ],
                                [
                                    'title' => 'Updated By',
                                    'value' => $data['updated_by'] ?? 'System',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Updated At',
                                    'value' => $data['updated_at'] ?? 'N/A',
                                    'short' => true
                                ]
                            ],
                            'footer' => $appName . ' - Support Ticket System',
                            'ts' => time()
                        ]
                    ]
                ];

            case 'support-ticket-reply':
                $replyType = ($data['is_internal'] ?? false) ? '🔒 Internal Note' : '💬 Customer Reply';
                $color = ($data['is_internal'] ?? false) ? '#6f42c1' : '#28a745';
                
                $fields = [
                    [
                        'title' => 'Ticket Number',
                        'value' => $data['ticket_number'] ?? 'N/A',
                        'short' => true
                    ],
                    // [
                    //     'title' => 'Reply Type',
                    //     'value' => $replyType,
                    //     'short' => true
                    // ],
                    [
                        'title' => 'Priority',
                        'value' => ucfirst($data['priority'] ?? 'N/A'),
                        'short' => true
                    ],
                    [
                        'title' => 'Status',
                        'value' => ucfirst($data['status'] ?? 'N/A'),
                        'short' => true
                    ],
                    [
                        'title' => 'Customer Name',
                        'value' => $data['customer_name'] ?? 'Unknown',
                        'short' => true
                    ],
                    [
                        'title' => 'Replied By',
                        'value' => $data['replied_by_name'] ?? 'Unknown',
                        'short' => true
                    ]
                ];

                // Add Order ID field only if category is 'order'
                if (isset($data['category']) && strtolower($data['category']) === 'order' && !empty($data['order_id'])) {
                    $fields[] = [
                        'title' => 'Order ID',
                        'value' => '#' . $data['order_id'],
                        'short' => true
                    ];
                }

                $fields = array_merge($fields, [
                    [
                        'title' => 'Subject',
                        'value' => $data['subject'] ?? 'N/A',
                        'short' => false
                    ],
                    [
                        'title' => 'Reply Message',
                        'value' => isset($data['reply_message']) ? 
                            (strlen(strip_tags($data['reply_message'])) > 500 ? 
                                substr(strip_tags($data['reply_message']), 0, 500) . '...' : 
                                strip_tags($data['reply_message'])) : 'N/A',
                        'short' => false
                    ],
                    [
                        'title' => 'Replied At',
                        'value' => $data['created_at'] ?? 'N/A',
                        'short' => false
                    ]
                ]);

                $attachments = [
                    [
                        'color' => $color,
                        'fields' => $fields,
                        'footer' => $appName . ' - Support Ticket System',
                        'ts' => time()
                    ]
                ];
                // Add reply file attachments if available
                if (!empty($data['reply_attachments']) && is_array($data['reply_attachments'])) {
                    foreach ($data['reply_attachments'] as $attachment) {
                        $fileAttachment = self::buildSlackAttachment($attachment);
                        $attachments[] = $fileAttachment;
                    }
                }
                
                return [
                    'text' => "💬 *New Reply Added to Support Ticket*",
                    'attachments' => $attachments
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
            'domain-removal-alerts' => ':warning:',
            'inbox-tickets' => ':ticket:',
            'support-ticket-created' => ':new:',
            'support-ticket-updated' => ':arrows_counterclockwise:',
            'support-ticket-reply' => ':speech_balloon:',
        ];
        
        return $emojis[$type] ?? ':bell:';
    }

    /**
     * Build Slack attachment for file with proper media support
     *
     * @param string $filePath
     * @param string|null $title
     * @return array
     */
    private static function buildSlackAttachment($filePath, $title = null): array
    {
        // Check if file exists
        $fullPath = storage_path('app/public/' . $filePath);
        if (!file_exists($fullPath)) {
            return [
                'color' => '#dc3545',
                'title' => $title ?? basename($filePath),
                'text' => '❌ File not found',
            ];
        }

        $mime = mime_content_type($fullPath);
        $fileName = basename($filePath);
        
        // Ensure the URL is publicly accessible and uses HTTPS for Slack previews
        $publicUrl = url('storage/' . $filePath);
        
        // Force HTTPS if not already (Slack requires HTTPS for image previews)
        if (!str_starts_with($publicUrl, 'https://')) {
            $publicUrl = str_replace('http://', 'http://', $publicUrl);
        }

        $attachment = [
            'color' => '#36a64f',
            'title' => $title ?? self::cleanAttachmentName($fileName),
            'title_link' => $publicUrl
        ];

        if (str_starts_with($mime, 'image/')) {
            // For image preview to work in Slack:
            // 1. URL must be publicly accessible
            // 2. Must be HTTPS 
            // 3. Must be direct image URL
            $attachment['image_url'] = $publicUrl;
            // $attachment['image_url'] = 'https://upload.wikimedia.org/wikipedia/commons/4/47/PNG_transparency_demonstration_1.png'; // Example image URL for testing
            
            // Make the title clickable for download
            $attachment['title'] = "📥 " . self::cleanAttachmentName($fileName);
            $attachment['title_link'] = $publicUrl . '?download=1';
            
            // Add fallback text for better accessibility
            $attachment['fallback'] = "Image: " . self::cleanAttachmentName($fileName);
            
        } elseif ($mime === 'application/pdf') {
            // Show clean file name instead of URL
            // $attachment['text'] = "📄 PDF Document: " . self::cleanAttachmentName($fileName);
            $attachment['fallback'] = "PDF: " . self::cleanAttachmentName($fileName);
            
        } elseif (str_starts_with($mime, 'audio/')) {
            // Show clean file name instead of URL
            // $attachment['text'] = "🎵 Audio File: " . self::cleanAttachmentName($fileName);
            $attachment['fallback'] = "Audio: " . self::cleanAttachmentName($fileName);
            
        } elseif (str_starts_with($mime, 'video/')) {
            // Show clean file name instead of URL
            // $attachment['text'] = "🎥 Video File: " . self::cleanAttachmentName($fileName);
            $attachment['fallback'] = "Video: " . self::cleanAttachmentName($fileName);
            
        } else {
            // Show clean file name instead of URL
            // $attachment['text'] = "📎 File: " . self::cleanAttachmentName($fileName);
            $attachment['fallback'] = "File: " . self::cleanAttachmentName($fileName);
        }

        return $attachment;
    }

    /**
     * Clean attachment filename for display
     *
     * @param string $attachment
     * @return string
     */
    private static function cleanAttachmentName($attachment): string
    {
        if (is_string($attachment)) {
            // Extract filename from path
            $filename = basename($attachment);
            
            // Remove Laravel's random hash prefixes if present
            // Pattern 1: Hash with underscore separator (hash_originalname.ext)
            $cleanName = preg_replace('/^[a-zA-Z0-9]{40,}_/', '', $filename);
            
            // Pattern 2: Hash with dot separator but preserve extension (hash.originalname.ext)
            if ($cleanName === $filename) {
                $cleanName = preg_replace('/^[a-zA-Z0-9]{40,}\.(?=.+\.)/', '', $filename);
            }
            
            // Pattern 3: Just hash with extension (hash.ext) - make it more readable
            if ($cleanName === $filename && preg_match('/^[a-zA-Z0-9]{40,}\.(png|jpg|jpeg|gif|pdf|doc|docx|txt|zip|mp4|mp3)$/i', $filename, $matches)) {
                $extension = strtolower($matches[1]);
                $fileTypes = [
                    'png' => 'Image (PNG)',
                    'jpg' => 'Image (JPG)', 
                    'jpeg' => 'Image (JPEG)',
                    'gif' => 'Image (GIF)',
                    'pdf' => 'PDF Document',
                    'doc' => 'Word Document',
                    'docx' => 'Word Document',
                    'txt' => 'Text File',
                    'zip' => 'ZIP Archive',
                    'mp4' => 'Video (MP4)',
                    'mp3' => 'Audio (MP3)'
                ];
                return $fileTypes[$extension] ?? "File ($extension)";
            }
            
            // If still no change or name became empty, use original basename
            if ($cleanName === $filename || empty($cleanName)) {
                return $filename;
            }
            
            return $cleanName;
        } elseif (isset($attachment['name'])) {
            return $attachment['name'];
        } else {
            return 'Unknown file';
        }
    }
    
    /**
     * Send task completion notification to Slack
     *
     * @param \App\Models\DomainRemovalTask $task
     * @param array $completionData
     * @return bool
     */
    public static function sendTaskCompletionNotification($task, $completionData = [])
    {
        $order = $task->order;
        $user = $task->user;
        $assignedTo = $task->assignedTo;
        
        $data = [
            'task_id' => $task->id,
            'customer_name' => $user ? $user->name : 'Unknown',
            'customer_email' => $user ? $user->email : 'Unknown', 
            'order_id' => $order ? $order->id : 'N/A',
            'assigned_to' => $assignedTo ? $assignedTo->name : 'System',
            'completed_at' => now()->format('Y-m-d H:i:s T'),
            'reason' => $task->reason ?? 'Domain removal task',
            'released_spaces' => $completionData['released_spaces'] ?? 0,
            'processed_splits' => $completionData['processed_splits'] ?? 0,
            'affected_panels_count' => isset($completionData['affected_panels']) ? count($completionData['affected_panels']) : 0,
        ];

        $attachments = [
            [
                'color' => 'good',
                'title' => "✅ Task Completed - #{$task->id}",
                'fields' => [
                    [
                        'title' => 'Customer',
                        'value' => $data['customer_name'] . ' (' . $data['customer_email'] . ')',
                        'short' => true
                    ],
                    [
                        'title' => 'Order ID',
                        'value' => $data['order_id'],
                        'short' => true
                    ],
                    [
                        'title' => 'Completed By',
                        'value' => $data['assigned_to'],
                        'short' => true
                    ],
                    [
                        'title' => 'Completed At',
                        'value' => $data['completed_at'],
                        'short' => true
                    ],
                    // [
                    //     'title' => 'Reason',
                    //     'value' => $data['reason'],
                    //     'short' => false
                    // ],
                    [
                        'title' => 'Released Spaces',
                        'value' => $data['released_spaces'],
                        'short' => true
                    ],
                    [
                        'title' => 'Processed Splits',
                        'value' => $data['processed_splits'],
                        'short' => true
                    ],
                    [
                        'title' => 'Affected Panels',
                        'value' => $data['affected_panels_count'],
                        'short' => true
                    ]
                ],
                'footer' => config('app.name', 'ProjectInbox'),
                'ts' => time()
            ]
        ];

        $message = [
            'text' => "✅ Domain removal task #{$task->id} has been completed successfully!",
            'attachments' => $attachments
        ];

        return self::send('inbox-cancellation', $message);
    }

    /**
     * Send panel reassignment task completion notification to Slack
     *
     * @param \App\Models\PanelReassignmentHistory $task
     * @return bool
     */
    public static function sendPanelReassignmentCompletionNotification($task)
    {
        $order = $task->order;
        $user = $order ? $order->user : null;
        $assignedTo = $task->assignedTo;
        $fromPanel = $task->fromPanel;
        $toPanel = $task->toPanel;
        
        $data = [
            'task_id' => $task->id,
            'customer_name' => $user ? $user->name : 'Unknown',
            'customer_email' => $user ? $user->email : 'Unknown', 
            'order_id' => $order ? $order->id : 'N/A',
            'assigned_to' => $assignedTo ? $assignedTo->name : 'System',
            'completed_at' => $task->task_completed_at ? $task->task_completed_at->format('Y-m-d H:i:s T') : now()->format('Y-m-d H:i:s T'),
            'action_type' => $task->action_type,
            'space_transferred' => $task->space_transferred ?? 0,
            'splits_count' => $task->splits_count ?? 0,
            'from_panel' => $fromPanel ? $fromPanel->title : 'N/A',
            'to_panel' => $toPanel ? $toPanel->title : 'N/A',
        ];

        $attachments = [
            [
                'color' => 'good',
                'title' => "🔄 Panel Reassignment Completed - #{$task->id}",
                'fields' => [
                    [
                        'title' => 'Customer',
                        'value' => $data['customer_name'] . ' (' . $data['customer_email'] . ')',
                        'short' => true
                    ],
                    [
                        'title' => 'Order ID',
                        'value' => $data['order_id'],
                        'short' => true
                    ],
                    [
                        'title' => 'Completed By',
                        'value' => $data['assigned_to'],
                        'short' => true
                    ],
                    [
                        'title' => 'Completed At',
                        'value' => $data['completed_at'],
                        'short' => true
                    ],
                    [
                        'title' => 'Action Type',
                        'value' => ucfirst($data['action_type']),
                        'short' => true
                    ],
                    [
                        'title' => 'Space Transferred',
                        'value' => $data['space_transferred'],
                        'short' => true
                    ],
                    [
                        'title' => 'Splits Count',
                        'value' => $data['splits_count'],
                        'short' => true
                    ],
                    [
                        'title' => 'From Panel',
                        'value' => $data['from_panel'],
                        'short' => true
                    ],
                    [
                        'title' => 'To Panel',
                        'value' => $data['to_panel'],
                        'short' => true
                    ]
                ],
                'footer' => config('app.name', 'ProjectInbox'),
                'ts' => time()
            ]
        ];

        $message = [
            'text' => "🔄 Panel reassignment task #{$task->id} has been completed successfully!",
            'attachments' => $attachments
        ];

        return self::send('inbox-setup', $message);
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
