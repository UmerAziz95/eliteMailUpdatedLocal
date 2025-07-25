<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\Notification;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Events\OrderStatusUpdated;
use App\Services\ActivityLogService;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Auth;
class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        \Log::info('OrderObserver: Order created event triggered', [
            'order_id' => $order->id,
            'status' => $order->status_manage_by_admin
        ]);
        
        // Fire the OrderCreated event for real-time updates
        event(new OrderCreated($order));
        
        \Log::info('OrderObserver: OrderCreated event fired', [
            'order_id' => $order->id
        ]);
    }

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        // Get the changes made to the order
        $changes = $order->getChanges();
        
        \Log::info('OrderObserver: Order updated event triggered', [
            'order_id' => $order->id,
            'changes' => $changes
        ]);
        
        // Check if status_manage_by_admin was changed
        if (isset($changes['status_manage_by_admin'])) {
            $previousStatus = $order->getOriginal('status_manage_by_admin');
            $newStatus = $order->status_manage_by_admin;
            $reason = $order->reason ?? null;
            
            \Log::info('OrderObserver: Status change detected', [
                'order_id' => $order->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'reason' => $reason
            ]);
            
            // Send Slack notification if order status changes from draft to any other status
            if (strtolower($previousStatus) === 'draft' && strtolower($newStatus) !== 'draft') {
                try {
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
                    
                    $orderData = [
                        'id' => $order->id,
                        'order_id' => $order->id,
                        'name' => 'Order #' . $order->id,
                        'customer_name' => $order->user ? $order->user->name : 'Unknown',
                        'customer_email' => $order->user ? $order->user->email : 'Unknown',
                        'contractor_name' => $order->assignedTo ? $order->assignedTo->name : 'Unassigned',
                        'inbox_count' => $inboxCount,
                        'split_count' => $splitCount,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus
                    ];
                    
                    SlackNotificationService::sendNewOrderAvailableNotification($orderData);
                    \Log::channel('slack_notifications')->info('OrderObserver: Slack notification sent for new order available', [
                        'order_id' => $order->id,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus
                    ]);
                } catch (\Exception $e) {
                    \Log::channel('slack_notifications')->error('OrderObserver: Failed to send Slack notification for new order available', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Send Slack notification if order is cancelled
            if (strtolower($newStatus) === 'cancelled') {
                try {
                    SlackNotificationService::sendOrderCancellationNotification($order, $reason);
                    \Log::channel('slack_notifications')->info('OrderObserver: Slack notification sent for cancelled order', [
                        'order_id' => $order->id
                    ]);
                } catch (\Exception $e) {
                    \Log::channel('slack_notifications')->error('OrderObserver: Failed to send Slack notification for cancelled order', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Fire the OrderStatusUpdated event for real-time updates
            event(new OrderStatusUpdated($order, $previousStatus, $newStatus, $reason));
            
            \Log::info('OrderObserver: OrderStatusUpdated event fired', [
                'order_id' => $order->id
            ]);
        }
        
        // Fire the general OrderUpdated event for real-time updates
        event(new OrderUpdated($order, $changes));
        
        \Log::info('OrderObserver: OrderUpdated event fired', [
            'order_id' => $order->id
        ]);

        // Check if assigned_to was changed
        if (isset($changes['assigned_to'])) {
            $newAssignedTo = $order->assigned_to;
            $oldAssignedTo = $order->getOriginal('assigned_to');

            \Log::info('OrderObserver: Assignment change detected', [
                'order_id' => $order->id,
                'old_assigned_to' => $oldAssignedTo,
                'new_assigned_to' => $newAssignedTo
            ]);
            
            // Create notification when order is assigned to contractor (from null to assigned)
            if ($newAssignedTo && !$oldAssignedTo) {
                \Log::info('OrderObserver: Creating notification for contractor assignment', [
                    'order_id' => $order->id,
                    'assigned_to' => $newAssignedTo,
                    'auth_user_id' => Auth::id()
                ]);

                try {
                    // Create a notification for the contractor
                    Notification::create([
                        'user_id' => $newAssignedTo,
                        'type' => 'order_assigned',
                        'title' => 'Order Assigned',
                        'message' => 'You have been assigned to order #' . $order->id,
                        'data' => [
                            'order_id' => $order->id,
                            'assigned_to' => $newAssignedTo,
                            'assigned_by' => Auth::id(),
                        ],
                        'is_read' => false
                    ]);

                    // Also create a log entry
                    ActivityLogService::log(
                        'order-assigned',
                        'Order assigned to contractor: ' . $order->id . ' - ' . ($order->assignedTo ? $order->assignedTo->name : 'Unknown'),
                        $order,
                        [
                            'order_id' => $order->id,
                            'assigned_to' => $newAssignedTo,
                        ],
                        Auth::id() ?? 1 // Performed By - fallback to admin if no auth
                    );

                    \Log::info('OrderObserver: Order assignment notification created successfully', [
                        'order_id' => $order->id,
                        'assigned_to' => $newAssignedTo
                    ]);
                } catch (\Exception $e) {
                    \Log::error('OrderObserver: Failed to create assignment notification', [
                        'order_id' => $order->id,
                        'assigned_to' => $newAssignedTo,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            } else {
                \Log::info('OrderObserver: Assignment condition not met', [
                    'order_id' => $order->id,
                    'new_assigned_to' => $newAssignedTo,
                    'old_assigned_to' => $oldAssignedTo,
                    'condition_met' => false
                ]);
            }
        }
    }

    /**
     * Handle the Order "updating" event.
     */
    // 2025-06-14 08:38:30
    // 2025-06-16 11:33:39
    public function updating(Order $order): void
    {
        // Check if status_manage_by_admin is being changed to 'completed'
        if ($order->isDirty('status_manage_by_admin')) {
            $newStatus = strtolower($order->status_manage_by_admin);
            $oldStatus = strtolower($order->getOriginal('status_manage_by_admin') ?? '');
            
            // If status is changing to completed and wasn't completed before
            if ($newStatus === 'completed' && $oldStatus !== 'completed') {
                $order->completed_at = now();
                
                // If timer was paused when completing, add the paused time to total
                if (!is_null($order->timer_paused_at)) {
                    $pausedDuration = now()->diffInSeconds($order->timer_paused_at);
                    $order->total_paused_seconds += $pausedDuration;
                    $order->timer_paused_at = null;
                }
            }
            
            // If status is changing from completed to something else, clear completed_at
            if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                $order->completed_at = null;
            }
        }

        // Handle timer_started_at and timer pausing/resuming for status changes
        if ($order->isDirty('status_manage_by_admin')) {
            $newStatus = strtolower($order->status_manage_by_admin);
            $oldStatus = strtolower($order->getOriginal('status_manage_by_admin') ?? '');
            
            // If status is changing to pending
            if ($newStatus === 'pending') {
                // If timer hasn't started yet, start it
                if (is_null($order->timer_started_at)) {
                    $order->timer_started_at = now();
                }
                // If timer was paused (coming from rejected), resume it
                else if (!is_null($order->timer_paused_at)) {
                    $pausedDuration = now()->diffInSeconds($order->timer_paused_at);
                    $order->total_paused_seconds += $pausedDuration;
                    $order->timer_paused_at = null;
                }
            }
            
            // If status is changing to rejected, pause the timer
            if ($newStatus === 'reject' && !is_null($order->timer_started_at) && is_null($order->timer_paused_at)) {
                $order->timer_paused_at = now();
            }
            
            if ($newStatus === 'cancelled' && !is_null($order->timer_started_at) && is_null($order->timer_paused_at)) {
                $order->timer_paused_at = now();
            }
            
            // If status is changing from pending to something else (except rejected)
            if ($oldStatus === 'pending' && $newStatus !== 'pending' && $newStatus !== 'reject' && !is_null($order->timer_started_at)) {
                // Keep timer_started_at as is - don't reset it
                // This allows us to track when the timer originally started
            }
        }
    }
}
