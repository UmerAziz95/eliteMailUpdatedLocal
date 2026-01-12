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
            
            // Send to Slack cn
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
     * Format number with ordinal suffix (1st, 2nd, 3rd, etc.)
     *
     * @param int $number
     * @return string
     */
    private static function getOrdinal($number)
    {
        $number = (int) $number;
        
        // Handle special cases for 11th, 12th, 13th
        if (in_array(($number % 100), [11, 12, 13])) {
            return $number . 'th';
        }
        
        // Handle regular cases
        switch ($number % 10) {
            case 1:
                return $number . 'st';
            case 2:
                return $number . 'nd';
            case 3:
                return $number . 'rd';
            default:
                return $number . 'th';
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
            'created_at' => $order->created_at ? $order->created_at->format('Y-m-d H:i:s T') : 'N/A',
            'provider_type' => $order->provider_type ?? ($order->plan ? $order->plan->provider_type : null)
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

        // Get provider type from order or plan
        $providerType = $order->provider_type ?? ($order->plan ? $order->plan->provider_type : null);
        
        $data = [
            'order_id' => $order->id,
            'order_name' => 'Order #' . $order->id,
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'contractor_name' => $order->assignedTo ? $order->assignedTo->name : 'Unassigned',
            'inbox_count' => $inboxCount,
            'split_count' => $splitCount,
            'reason' => $reason ?: 'No reason provided',
            'rejected_by' => auth()->user() ? auth()->user()->name : 'System',
            'provider_type' => $providerType
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

        // Get provider type from order or plan
        $providerType = $order->provider_type ?? ($order->plan ? $order->plan->provider_type : null);
        
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
            'completed_by' => auth()->user() ? auth()->user()->name : 'System',
            'provider_type' => $providerType
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

        // Get provider type from order or plan
        $providerType = $order->provider_type ?? ($order->plan ? $order->plan->provider_type : null);
        
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
            'status_manage_by_admin' => ucfirst($order->status_manage_by_admin),
            'reassignment_note' => $order->reassignment_note,
            'provider_type' => $providerType
        ];

        // Prepare the message based on type
        $message = self::formatMessage('order-assignment', $data);

        return self::send('inbox-setup', $message);
    }

    /**
     * Send order reassignment notification to Slack
     *
     * @param \App\Models\Order $order
     * @param int $oldAssignedTo
     * @param int $newAssignedTo
     * @return bool
     */
    public static function sendOrderReassignmentNotification($order, $oldAssignedTo, $newAssignedTo)
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

        $oldContractor = \App\Models\User::find($oldAssignedTo);
        $newContractor = \App\Models\User::find($newAssignedTo);
        // if order is is_shared and helper_ids then added helpers
        $helpers = collect();
        if ($order->is_shared && $order->helpers_ids) {
            $helperIds = is_string($order->helpers_ids) ? json_decode($order->helpers_ids, true) : $order->helpers_ids;
            if (is_array($helperIds) && !empty($helperIds)) {
                $helpers = \App\Models\User::whereIn('id', $helperIds)->get();
            }
        }
        // get names
        $helpersNames = $helpers->pluck('name')->toArray();
        // Get provider type from order or plan
        $providerType = $order->provider_type ?? ($order->plan ? $order->plan->provider_type : null);
        
        $data = [
            'order_id' => $order->id,
            'order_name' => 'Order #' . $order->id,
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'old_contractor_name' => $oldContractor ? $oldContractor->name : 'Unknown',
            'old_contractor_email' => $oldContractor ? $oldContractor->email : 'N/A',
            'new_contractor_name' => $newContractor ? $newContractor->name : 'Unknown',
            'new_contractor_email' => $newContractor ? $newContractor->email : 'N/A',
            'inbox_count' => $inboxCount,
            'split_count' => $splitCount,
            'reassigned_by' => auth()->user() ? auth()->user()->name : 'System',
            'status_manage_by_admin' => ucfirst($order->status_manage_by_admin),
            'reassignment_note' => $order->reassignment_note,
            'helpers_names' => $helpersNames,
            'provider_type' => $providerType
        ];

        // Prepare the message based on type
        $message = self::formatMessage('order-reassignment', $data);

        return self::send('inbox-setup', $message);
    }

    /**
     * Send order unassignment notification to Slack
     *
     * @param \App\Models\Order $order
     * @param int $oldAssignedTo
     * @return bool
     */
    public static function sendOrderUnassignmentNotification($order, $oldAssignedTo)
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

        $oldContractor = \App\Models\User::find($oldAssignedTo);

        // Get provider type from order or plan
        $providerType = $order->provider_type ?? ($order->plan ? $order->plan->provider_type : null);
        
        $data = [
            'order_id' => $order->id,
            'order_name' => 'Order #' . $order->id,
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'old_contractor_name' => $oldContractor ? $oldContractor->name : 'Unknown',
            'old_contractor_email' => $oldContractor ? $oldContractor->email : 'N/A',
            'inbox_count' => $inboxCount,
            'split_count' => $splitCount,
            'unassigned_by' => auth()->user() ? auth()->user()->name : 'System',
            'status_manage_by_admin' => ucfirst($order->status_manage_by_admin),
            'unassignment_note' => $order->unassignment_note,
            'provider_type' => $providerType
        ];

        // Prepare the message based on type
        $message = self::formatMessage('order-unassignment', $data);

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
        // Get cancellation type and domain removal task date from subscription
        $cancellationType = 'N/A';
        $removalTaskDate = 'N/A';
        
        if ($order->subscription) {
            // Determine cancellation type based on is_cancelled_force flag
            $cancellationType = $order->subscription->is_cancelled_force ? 'Force' : 'EOBC';
            
            // Get domain removal task date if exists
            $domainRemovalTask = \App\Models\DomainRemovalTask::where('order_id', $order->id)
                ->where('chargebee_subscription_id', $order->chargebee_subscription_id)
                ->first();
                
            if ($domainRemovalTask && $domainRemovalTask->started_queue_date) {
                $removalTaskDate = $domainRemovalTask->started_queue_date->format('Y-m-d H:i:s T');
            }
        }
        // fallback removalTaskDate if still N/A
        if ($removalTaskDate === 'N/A' ) {
            if($cancellationType === 'Force')
                $removalTaskDate = 'Removal task starts immediately';
            else{
                $removalTaskDate = 'Removal task shows at end of billing cycle';
            }
        }
        $data = [
            'inbox_id' => $order->id,
            'order_id' => $order->id, // Keep for backward compatibility
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'reason' => $reason ?: 'No reason provided',
            'cancelled_by' => auth()->user() ? auth()->user()->name : 'System',
            'cancellation_type' => $cancellationType,
            'removal_domains_task_date' => $removalTaskDate
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
     * @param int $attemptNumber
     * @return bool
     */
    public static function sendInvoiceGeneratedNotification($invoice, $user, $isPaymentFailed = false, $attemptNumber = 1)
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
            'currency' => $order ? $order->currency : 'USD',
            'attempt_number' => $attemptNumber
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
     * Send invoice updated notification to Slack
     *
     * @param \App\Models\Invoice $invoice
     * @param \App\Models\User $user
     * @param bool $isPaymentFailed
     * @param int $attemptNumber
     * @return bool
     */
    
    public static function sendInvoiceUpdatedNotification($invoice, $user, $isPaymentFailed = false, $attemptNumber = 1)
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
            'old_status' => $invoice->getOriginal('status'),
            'is_payment_failed' => $isPaymentFailed,
            'paid_at' => $invoice->paid_at ? \Carbon\Carbon::parse($invoice->paid_at)->format('Y-m-d H:i:s T') : 'N/A',
            'plan_name' => $order && $order->plan ? $order->plan->name : 'N/A',
            'currency' => $order ? $order->currency : 'USD',
            'updated_at' => $invoice->updated_at ? $invoice->updated_at->format('Y-m-d H:i:s T') : 'N/A',
            'attempt_number' => $attemptNumber,
            'payment_type' => $paymentType
        ];

        // Determine message type based on status change and payment type
        if ($isPaymentFailed) {
            $messageType = $isNewPayment ? 'invoice-payment-failed-updated-new' : 'invoice-payment-failed-updated-recurring';
        } else {
            $messageType = 'invoice-status-updated';
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
     * Send assignment failed notification to Slack
     *
     * @param \App\Models\PoolOrder $poolOrder
     * @param string $reason
     * @return bool
     */
    public static function sendAssignmentFailedNotification($poolOrder, $reason)
    {
        $data = [
            'order_id' => $poolOrder->id,
            'customer_name' => $poolOrder->user ? $poolOrder->user->name : 'Unknown',
            'quantity' => $poolOrder->quantity,
            'reason' => $reason,
            'created_at' => now()->format('Y-m-d H:i:s T')
        ];

        $message = self::formatMessage('assignment-failed', $data);
        return self::send('trial-orders', $message);
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
            case 'assignment-failed':
                return [
                    'text' => "⚠️ *Auto-Assignment Failed*",
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
                                    'title' => 'Customer',
                                    'value' => $data['customer_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Required Quantity',
                                    'value' => $data['quantity'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Reason',
                                    'value' => $data['reason'] ?? 'N/A',
                                    'short' => false
                                ]
                            ],
                            'footer' => config('app.name', 'ProjectInbox') . ' - System Alert',
                            'ts' => time()
                        ]
                    ]
                ];
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
                                // [
                                //     'title' => 'Attempt Number',
                                //     'value' => $data['attempt_number'] ?? 1,
                                //     'short' => true
                                // ],
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
                                    'value' => '✅ ' . ucfirst($data['status'] ?? 'N/A'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Payment Type',
                                    'value' => 'Recurring Payment',
                                    'short' => true
                                ],
                                // [
                                //     'title' => 'Attempt Number',
                                //     'value' => $data['attempt_number'] ?? 1,
                                //     'short' => true
                                // ],
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
                // Use purple color for failed attempts after the first attempt
                $color = ($data['attempt_number'] ?? 1) > 1 ? '#6f42c1' : '#dc3545';
                $attemptText = isset($data['attempt_number']) && $data['attempt_number'] > 1 
                    ? ' - ' . self::getOrdinal($data['attempt_number']) . ' Attempt' 
                    : '';
                return [
                    'text' => "❌ *New Payment Failed{$attemptText}*",
                    'attachments' => [
                        [
                            'color' => $color,
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
                                    'title' => 'Attempt Number',
                                    'value' => self::getOrdinal($data['attempt_number'] ?? 1),
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
                // Use purple color for failed attempts after the first attempt
                $color = '#ffc107';
                $attemptText = isset($data['attempt_number']) && $data['attempt_number'] > 1 
                    ? ' - ' . self::getOrdinal($data['attempt_number']) . ' Attempt' 
                    : '';
                return [
                    'text' => "⚠️ *Recurring Payment Failed {$attemptText}*",
                    'attachments' => [
                        [
                            'color' => $color,
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
                                    'title' => 'Attempt Number',
                                    'value' => self::getOrdinal($data['attempt_number'] ?? 1),
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

            case 'invoice-status-updated':
                return [
                    'text' => "🔄 *Invoice Status Updated*",
                    'attachments' => [
                        [
                            'color' => '#6f42c1',
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
                                    'value' => ($data['currency'] ?? 'USD') . ' ' . number_format(floatval($data['amount'] ?? 0), 2, '.', ','),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Previous Status',
                                    'value' => ucfirst($data['old_status'] ?? 'N/A'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'New Status',
                                    'value' => '✅ ' . ucfirst($data['status'] ?? 'N/A'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Updated At',
                                    'value' => $data['updated_at'] ?? now()->format('Y-m-d H:i:s T'),
                                    'short' => false
                                ]
                            ],
                            'footer' => config('app.name', 'ProjectInbox') . ' - Invoice Update',
                            'ts' => time()
                        ]
                    ]
                ];

            case 'invoice-payment-failed-updated-new':
                // New payment failed (updated status)
                $color = '#dc3545';
                $attemptText = isset($data['attempt_number']) && $data['attempt_number'] > 1 
                    ? ' - ' . self::getOrdinal($data['attempt_number']) . ' Attempt' 
                    : '';
                return [
                    'text' => "❌ *New Payment Failed (Updated){$attemptText}*",
                    'attachments' => [
                        [
                            'color' => $color,
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
                                    'value' => ($data['currency'] ?? 'USD') . ' ' . number_format(floatval($data['amount'] ?? 0), 2, '.', ','),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Previous Status',
                                    'value' => ucfirst($data['old_status'] ?? 'N/A'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Current Status',
                                    'value' => 'PAYMENT FAILED',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Attempt Number',
                                    'value' => self::getOrdinal($data['attempt_number'] ?? 1),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Payment Type',
                                    'value' => 'New Customer Payment',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Updated At',
                                    'value' => $data['updated_at'] ?? now()->format('Y-m-d H:i:s T'),
                                    'short' => false
                                ]
                            ],
                            'footer' => config('app.name', 'ProjectInbox') . ' - Payment Failed Alert',
                            'ts' => time()
                        ]
                    ]
                ];

            case 'invoice-payment-failed-updated-recurring':
                // Recurring payment failed (updated status)
                $color = '#ffc107';
                $attemptText = isset($data['attempt_number']) && $data['attempt_number'] > 1 
                    ? ' - ' . self::getOrdinal($data['attempt_number']) . ' Attempt' 
                    : '';
                return [
                    'text' => "⚠️ *Recurring Payment Failed{$attemptText}*",
                    'attachments' => [
                        [
                            'color' => $color,
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
                                    'value' => ($data['currency'] ?? 'USD') . ' ' . number_format(floatval($data['amount'] ?? 0), 2, '.', ','),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Previous Status',
                                    'value' => ucfirst($data['old_status'] ?? 'N/A'),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Current Status',
                                    'value' => 'PAYMENT FAILED',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Attempt Number',
                                    'value' => self::getOrdinal($data['attempt_number'] ?? 1),
                                    'short' => true
                                ],
                                [
                                    'title' => 'Payment Type',
                                    'value' => 'Recurring Payment',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Updated At',
                                    'value' => $data['updated_at'] ?? now()->format('Y-m-d H:i:s T'),
                                    'short' => false
                                ]
                            ],
                            'footer' => config('app.name', 'ProjectInbox') . ' - Payment Failed Alert',
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
                // Check if this is a Private SMTP order
                $isPrivateSMTP = isset($data['provider_type']) && strtolower($data['provider_type']) === 'private smtp';
                
                // Build fields array
                $fields = [
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
                ];
                
                // For Private SMTP: Show "Assigned to: Automation", otherwise show "Contractor Name"
                if ($isPrivateSMTP) {
                    $fields[] = [
                        'title' => 'Assigned To',
                        'value' => 'Automation',
                        'short' => true
                    ];
                } else {
                    $fields[] = [
                        'title' => 'Contractor Name',
                        'value' => $data['contractor_name'] ?? 'Unassigned',
                        'short' => true
                    ];
                }
                
                $fields[] = [
                    'title' => 'Inbox Count',
                    'value' => $data['inbox_count'] ?? '0',
                    'short' => true
                ];
                
                // For Private SMTP: Don't show Previous Status
                if (!$isPrivateSMTP) {
                    $fields[] = [
                        'title' => 'Previous Status',
                        'value' => ucfirst($data['previous_status'] ?? 'N/A'),
                        'short' => true
                    ];
                }
                
                $fields[] = [
                    'title' => 'New Status',
                    'value' => ucfirst($data['new_status'] ?? 'N/A'),
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Updated By',
                    'value' => $data['updated_by'] ?? 'System',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Timestamp',
                    'value' => now()->format('Y-m-d H:i:s T'),
                    'short' => false
                ];
                
                return [
                    'text' => "🆕 *New Order Available Notification*",
                    'attachments' => [
                        [
                            'color' => '#17a2b8',
                            'fields' => $fields,
                            'footer' => $appName . ' Slack Integration',
                            'ts' => time()
                        ]
                    ]
                ];
                
            case 'order-rejection':
                // Check if this is a Private SMTP order
                $isPrivateSMTP = isset($data['provider_type']) && strtolower($data['provider_type']) === 'private smtp';
                
                $fields = [
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
                ];
                
                // For Private SMTP: Show "Assigned To: Automation", otherwise show "Contractor Name"
                if ($isPrivateSMTP) {
                    $fields[] = [
                        'title' => 'Assigned To',
                        'value' => 'Automation',
                        'short' => true
                    ];
                } else {
                    $fields[] = [
                        'title' => 'Contractor Name',
                        'value' => $data['contractor_name'] ?? 'Unassigned',
                        'short' => true
                    ];
                }
                
                $fields[] = [
                    'title' => 'Inbox Count',
                    'value' => $data['inbox_count'] ?? '0',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Rejected By',
                    'value' => $data['rejected_by'] ?? 'System',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Reason',
                    'value' => $data['reason'] ?? 'No reason provided',
                    'short' => false
                ];
                
                $fields[] = [
                    'title' => 'Timestamp',
                    'value' => now()->format('Y-m-d H:i:s T'),
                    'short' => true
                ];
                
                return [
                    'text' => "❌ *Order Rejection Notification*",
                    'attachments' => [
                        [
                            'color' => '#dc3545',
                            'fields' => $fields,
                            'footer' => $appName . ' Slack Integration',
                            'ts' => time()
                        ]
                    ]
                ];
                
            case 'order-completion':
                // Check if this is a Private SMTP order
                $isPrivateSMTP = isset($data['provider_type']) && strtolower($data['provider_type']) === 'private smtp';
                
                $fields = [
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
                ];
                
                // For Private SMTP: Show "Assigned To: Automation", otherwise show "Contractor Name"
                if ($isPrivateSMTP) {
                    $fields[] = [
                        'title' => 'Assigned To',
                        'value' => 'Automation',
                        'short' => true
                    ];
                } else {
                    $fields[] = [
                        'title' => 'Contractor Name',
                        'value' => $data['contractor_name'] ?? 'Unassigned',
                        'short' => true
                    ];
                }
                
                $fields[] = [
                    'title' => 'Inbox Count',
                    'value' => $data['inbox_count'] ?? '0',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Split Count',
                    'value' => $data['split_count'] ?? '0',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Working Time',
                    'value' => $data['working_time'] ?? 'N/A',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Completed By',
                    'value' => $data['completed_by'] ?? 'System',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Completed At',
                    'value' => $data['completed_at'] ?? 'N/A',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Timestamp',
                    'value' => now()->format('Y-m-d H:i:s T'),
                    'short' => false
                ];
                
                return [
                    'text' => "✅ *Order Completion Notification*",
                    'attachments' => [
                        [
                            'color' => '#28a745',
                            'fields' => $fields,
                            'footer' => $appName . ' Slack Integration',
                            'ts' => time()
                        ]
                    ]
                ];
                
            case 'order-assignment':
                // Check if this is a Private SMTP order
                $isPrivateSMTP = isset($data['provider_type']) && strtolower($data['provider_type']) === 'private smtp';
                
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
                ];
                
                // For Private SMTP: Show "Assigned To: Automation", otherwise show contractor info
                if ($isPrivateSMTP) {
                    $fields[] = [
                        'title' => 'Assigned To',
                        'value' => 'Automation',
                        'short' => true
                    ];
                } else {
                    $fields[] = [
                        'title' => 'Assigned To',
                        'value' => $data['contractor_name'] ?? 'Unassigned',
                        'short' => true
                    ];
                    $fields[] = [
                        'title' => 'Contractor Email',
                        'value' => $data['contractor_email'] ?? 'N/A',
                        'short' => true
                    ];
                }
                
                $fields[] = [
                    'title' => 'Inbox Count',
                    'value' => $data['inbox_count'] ?? '0',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Split Count',
                    'value' => $data['split_count'] ?? '0',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Assigned By',
                    'value' => $data['assigned_by'] ?? 'System',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Timestamp',
                    'value' => now()->format('Y-m-d H:i:s T'),
                    'short' => true
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

            case 'contractor-assignment':
                // Check if this is a Private SMTP order
                $isPrivateSMTP = isset($data['provider_type']) && strtolower($data['provider_type']) === 'private smtp';
                
                $fields = [
                    [
                        'title' => 'Order ID',
                        'value' => $data['order_id'] ?? 'N/A',
                        'short' => true
                    ],
                    [
                        'title' => 'Order Status',
                        'value' => $data['order_status'] ?? 'N/A',
                        'short' => true
                    ],
                    [
                        'title' => 'Customer Name',
                        'value' => $data['customer_name'] ?? 'N/A',
                        'short' => true
                    ],
                ];
                
                // For Private SMTP: Show "Assigned To: Automation", otherwise show contractor info
                if ($isPrivateSMTP) {
                    $fields[] = [
                        'title' => 'Assigned To',
                        'value' => 'Automation',
                        'short' => true
                    ];
                } else {
                    $fields[] = [
                        'title' => 'Contractor Name',
                        'value' => $data['assigned_to'] ?? 'N/A',
                        'short' => true
                    ];
                    $fields[] = [
                        'title' => 'Assigned Helper(s)',
                        'value' => $data['contractor_names'] ?? 'None',
                        'short' => false
                    ];
                }
                
                $fields[] = [
                    'title' => 'Inbox Count',
                    'value' => $data['inbox_count'] ?? '0',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Request Status',
                    'value' => '✅ ' . ($data['request_status'] ?? 'N/A'),
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Assigned By',
                    'value' => $data['assigned_by'] ?? 'System',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Assigned At',
                    'value' => $data['assigned_at'] ?? 'N/A',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Note',
                    'value' => $data['shared_note'] ?? 'No note provided',
                    'short' => false
                ];
                
                return [
                    'text' => "👥 *Helper Assigned Notification*",
                    'attachments' => [
                        [
                            'color' => '#28a745',
                            'fields' => $fields,
                            'footer' => $appName . ' - Contractor Management',
                            'ts' => time()
                        ]
                    ]
                ];

            case 'order-shared-status-change':
                $statusColor = $data['new_shared_status'] === 'Pending' ? '#ffc107' : '#dc3545';
                $statusIcon = $data['new_shared_status'] === 'Pending' ? '🔗' : '❌';

                return [
                    'text' => "{$statusIcon} *Helper Request Notification*",
                    'attachments' => [
                        [
                            'color' => $statusColor,
                            'fields' => [
                                [
                                    'title' => 'Order ID',
                                    'value' => $data['order_id'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Order Status',
                                    'value' => $data['order_status'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Customer Name',
                                    'value' => $data['customer_name'] ?? 'N/A',
                                    'short' => true
                                ],
                                // [
                                //     'title' => 'Plan Name',
                                //     'value' => $data['plan_name'] ?? 'N/A',
                                //     'short' => true
                                // ],
                                // [
                                //     'title' => 'Previous Status',
                                //     'value' => $data['previous_shared_status'] ?? 'N/A',
                                //     'short' => true
                                // ],
                                [
                                    'title' => 'Request Status',
                                    'value' => ($data['new_shared_status'] === 'Pending' ? '🔗 ' : '❌ ') . ($data['new_shared_status'] ?? 'N/A'),
                                    'short' => true
                                ],
                                
                                [
                                    'title' => 'Initiated By',
                                    'value' => $data['changed_by'] ?? 'System',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Initiated At',
                                    'value' => $data['changed_at'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Inbox Count',
                                    'value' => $data['inbox_count'] ?? '0',
                                    'short' => false
                                ],
                                [
                                    'title' => 'Note',
                                    'value' => $data['shared_note'] ?? 'No note provided',
                                    'short' => false
                                ],
                            ],
                            'footer' => $appName . ' - Order Sharing Management',
                            'ts' => time()
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
                if ($data['reason'] != 'Auto-cancel due to repeated failure after 72 hours') {
                    $data['reason'] .= '. ' . $data['removal_domains_task_date'];
                }
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
                                    'title' => 'Cancelled By',
                                    'value' => $data['cancelled_by'] ?? 'System',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Cancellation Type',
                                    'value' => $data['cancellation_type'] ?? 'N/A',
                                    'short' => true
                                ],
                                [
                                    'title' => 'Reason',
                                    'value' => $data['reason'] ?? 'No reason provided',
                                    'short' => false
                                ],
                                // [
                                //     'title' => 'Note',
                                //     'value' => $data['removal_domains_task_date'] ?? 'N/A',
                                //     'short' => false
                                // ],
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
                
            case 'order-reassignment':
                // Check if this is a Private SMTP order
                $isPrivateSMTP = isset($data['provider_type']) && strtolower($data['provider_type']) === 'private smtp';
                
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
                ];
                
                // For Private SMTP: Show "Assigned To: Automation", otherwise show contractor info
                if ($isPrivateSMTP) {
                    $fields[] = [
                        'title' => 'Assigned To',
                        'value' => 'Automation',
                        'short' => true
                    ];
                } else {
                    $fields[] = [
                        'title' => 'Previous Contractor',
                        'value' => $data['old_contractor_name'] ?? 'Unknown',
                        'short' => true
                    ];
                    $fields[] = [
                        'title' => 'New Contractor',
                        'value' => $data['new_contractor_name'] ?? 'Unknown',
                        'short' => true
                    ];
                    $fields[] = [
                        'title' => 'New Contractor Email',
                        'value' => $data['new_contractor_email'] ?? 'N/A',
                        'short' => true
                    ];
                }
                
                $fields[] = [
                    'title' => 'Inbox Count',
                    'value' => $data['inbox_count'] ?? '0',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Split Count',
                    'value' => $data['split_count'] ?? '0',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Reassigned By',
                    'value' => $data['reassigned_by'] ?? 'System',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Timestamp',
                    'value' => now()->format('Y-m-d H:i:s T'),
                    'short' => true
                ];

                // Add Reassignment Note only if not empty
                if (!empty($data['reassignment_note'])) {
                    $fields[] = [
                        'title' => 'Reassignment Note',
                        'value' => $data['reassignment_note'],
                        'short' => false
                    ];
                }
                // helpersNames
                if (!empty($data['helpers_names']) && is_array($data['helpers_names'])) {
                    $fields[] = [
                        'title' => 'Assigned Helpers',
                        'value' => implode(', ', $data['helpers_names']),
                        'short' => false
                    ];
                }

                return [
                    'text' => "🔄 *Order Reassignment Notification*",
                    'attachments' => [
                        [
                            'color'  => '#ffc107',
                            'fields' => $fields,
                            'footer' => $appName . ' Slack Integration',
                            'ts'     => time(),
                        ]
                    ]
                ];

            case 'order-unassignment':
                // Check if this is a Private SMTP order
                $isPrivateSMTP = isset($data['provider_type']) && strtolower($data['provider_type']) === 'private smtp';
                
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
                ];
                
                // For Private SMTP: Show "Assigned To: Automation", otherwise show contractor info
                if ($isPrivateSMTP) {
                    $fields[] = [
                        'title' => 'Assigned To',
                        'value' => 'Automation',
                        'short' => true
                    ];
                } else {
                    $fields[] = [
                        'title' => 'Previously Assigned To',
                        'value' => $data['old_contractor_name'] ?? 'Unknown',
                        'short' => true
                    ];
                    $fields[] = [
                        'title' => 'Previous Contractor Email',
                        'value' => $data['old_contractor_email'] ?? 'N/A',
                        'short' => true
                    ];
                }
                
                $fields[] = [
                    'title' => 'Inbox Count',
                    'value' => $data['inbox_count'] ?? '0',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Split Count',
                    'value' => $data['split_count'] ?? '0',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Unassigned By',
                    'value' => $data['unassigned_by'] ?? 'System',
                    'short' => true
                ];
                
                $fields[] = [
                    'title' => 'Timestamp',
                    'value' => now()->format('Y-m-d H:i:s T'),
                    'short' => true
                ];

                // Add Unassignment Note only if not empty
                if (!empty($data['unassignment_note'])) {
                    $fields[] = [
                        'title' => 'Unassignment Note',
                        'value' => $data['unassignment_note'],
                        'short' => false
                    ];
                }

                return [
                    'text' => "❌ *Order Unassignment Notification*",
                    'attachments' => [
                        [
                            'color'  => '#dc3545',
                            'fields' => $fields,
                            'footer' => $appName . ' Slack Integration',
                            'ts'     => time(),
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
     * Send contractor assignment notification to Slack
     *
     * @param \App\Models\Order $order
     * @param array $contractorIds
     * @param array $contractorNames
     * @return bool
     */
    public static function sendContractorAssignmentNotification($order, $contractorIds, $contractorNames)
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

        // Get provider type from order or plan
        $providerType = $order->provider_type ?? ($order->plan ? $order->plan->provider_type : null);
        
        $data = [
            'order_id' => $order->id,
            'order_name' => 'Order #' . $order->id,
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'plan_name' => $order->plan ? $order->plan->name : 'N/A',
            'contractor_names' => implode(', ', $contractorNames),
            'contractor_count' => count($contractorIds),
            // assigned_to
            'assigned_to' => $order->assignedTo ? $order->assignedTo->name : 'Unassigned',
            'shared_note' => $order->shared_note ?? 'No note provided',
            'order_status' => $order->status_manage_by_admin ? ucfirst(str_replace('_', ' ', $order->status_manage_by_admin)) : 'N/A',
            'inbox_count' => $inboxCount,
            'split_count' => $splitCount,
            'assigned_by' => auth()->user() ? auth()->user()->name : 'System',
            'request_status' => 'Confirmed',
            'assigned_at' => now()->format('Y-m-d H:i:s T'),
            'provider_type' => $providerType
        ];

        // Prepare the message based on type
        $message = self::formatMessage('contractor-assignment', $data);
        return self::send('inbox-setup', $message);
    }

    /**
     * Send order shared status change notification to Slack
     *
     * @param \App\Models\Order $order
     * @param bool $previousStatus
     * @param bool $newStatus
     * @return bool
     */
    public static function sendOrderSharedStatusNotification($order, $previousStatus, $newStatus)
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

        // Get provider type from order or plan
        $providerType = $order->provider_type ?? ($order->plan ? $order->plan->provider_type : null);
        
        $data = [
            'order_id' => $order->id,
            'order_name' => 'Order #' . $order->id,
            'customer_name' => $order->user ? $order->user->name : 'Unknown',
            'customer_email' => $order->user ? $order->user->email : 'Unknown',
            'plan_name' => $order->plan ? $order->plan->name : 'N/A',
            'previous_shared_status' => $previousStatus ? 'Shared' : 'Not Shared',
            'new_shared_status' => $newStatus ? 'Pending' : 'Cancelled',
            'shared_note' => $order->shared_note ?? 'No note provided',
            'inbox_count' => $inboxCount,
            'split_count' => $splitCount,
            'changed_by' => auth()->user() ? auth()->user()->name : 'System',
            'order_status' => $order->status_manage_by_admin ? ucfirst(str_replace('_', ' ', $order->status_manage_by_admin)) : 'N/A',
            'changed_at' => now()->format('Y-m-d H:i:s T'),
            'provider_type' => $providerType
        ];

        // Prepare the message based on type
        $message = self::formatMessage('order-shared-status-change', $data);
        return self::send('inbox-setup', $message);
    }

    /**
     * Send domain transfer task notification to Slack
     *
     * @param int $orderId
     * @param array $transferredDomains Array of ['domain' => string, 'name_servers' => array, 'transfer_id' => int]
     * @param \App\Models\Order $order
     * @return bool
     */
    public static function sendDomainTransferTaskNotification($orderId, $transferredDomains, $order)
    {
        try {
            // Build domains list with nameservers
            $domainsList = '';
            foreach ($transferredDomains as $index => $domainData) {
                $nameServersList = !empty($domainData['name_servers']) 
                    ? implode(', ', $domainData['name_servers']) 
                    : 'N/A';
                $domainsList .= ($index + 1) . ". *{$domainData['domain']}*\n";
                $domainsList .= "   Nameservers: {$nameServersList}\n";
                $domainsList .= "   Status: Pending\n\n";
            }

            $customer = $order->user;
            $planName = $order->plan ? $order->plan->name : 'N/A';

            $message = [
                'text' => "🔄 *Domain Transfer Task Created*",
                'attachments' => [
                    [
                        'color' => '#FFA500', // Orange color for pending tasks
                        'title' => "Order #{$orderId} - Domain Transfer Required",
                        'fields' => [
                            [
                                'title' => 'Order ID',
                                'value' => "#{$orderId}",
                                'short' => true
                            ],
                            [
                                'title' => 'Customer',
                                'value' => $customer ? "{$customer->name} ({$customer->email})" : 'N/A',
                                'short' => true
                            ],
                            [
                                'title' => 'Plan',
                                'value' => $planName,
                                'short' => true
                            ],
                            [
                                'title' => 'Domains to Transfer',
                                'value' => count($transferredDomains),
                                'short' => true
                            ],
                            [
                                'title' => 'Domain Details',
                                'value' => trim($domainsList),
                                'short' => false
                            ]
                        ],
                        'footer' => config('app.name', 'ProjectInbox') . ' - Domain Transfer System',
                        'ts' => time()
                    ]
                ]
            ];

            $result = self::send('inbox-setup', $message);
            
            if ($result) {
                Log::channel('slack_notifications')->info('Domain transfer task notification sent successfully', [
                    'order_id' => $orderId,
                    'domains_count' => count($transferredDomains),
                ]);
            } else {
                Log::channel('slack_notifications')->warning('Failed to send domain transfer task notification', [
                    'order_id' => $orderId,
                ]);
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::channel('slack_notifications')->error('Error sending domain transfer task notification', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
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
