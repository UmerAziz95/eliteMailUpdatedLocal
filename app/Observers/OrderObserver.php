<?php

namespace App\Observers;

use App\Models\Order;
use App\Models\User;
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
    // 
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

        // Send Slack notification for new order created
        try {
            // Calculate inbox count and split count
            $inboxCount = 0;
            // session variable for observer_total_inboxes get
            if (session()->has('observer_total_inboxes')) {
                $inboxCount = session()->get('observer_total_inboxes', 0);
            } else {
                // Fallback to 0 if not set
                $inboxCount = 0;
            }
            
            // SlackNotificationService::sendOrderCreatedNotification($order, $inboxCount);
            // \Log::channel('slack_notifications')->info('OrderObserver: Slack notification sent for new order created', [
            //     'order_id' => $order->id,
            //     'inbox_count' => $inboxCount,
            // ]);
        } catch (\Exception $e) {
            \Log::channel('slack_notifications')->error('OrderObserver: Failed to send Slack notification for new order created', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
        }
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
            $reason = $order->reason ?? $order->subscription->reason ?? null;
            
            \Log::info('OrderObserver: Status change detected', [
                'order_id' => $order->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'reason' => $reason
            ]);
            
            // Send Slack notification if order status changes from reject to in-progress (customer fixed order)
            if (strtolower($previousStatus) === 'reject' && strtolower($newStatus) === 'in-progress') {
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
                    
                    // Get provider type from order or plan
                    $providerType = $order->provider_type ?? ($order->plan ? $order->plan->provider_type : null);
                    
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
                        'new_status' => $newStatus,
                        'provider_type' => $providerType,
                        'updated_by' => auth()->user() ? auth()->user()->name : 'System'
                    ];
                    
                    SlackNotificationService::sendOrderFixedNotification($orderData);
                    \Log::channel('slack_notifications')->info('OrderObserver: Slack notification sent for order fixed (reject to in-progress)', [
                        'order_id' => $order->id,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus
                    ]);
                } catch (\Exception $e) {
                    \Log::channel('slack_notifications')->error('OrderObserver: Failed to send Slack notification for order fixed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            // Send Slack notification if order status changes from draft to any other status
            elseif ((strtolower($previousStatus) === 'draft' && strtolower($newStatus) !== 'draft') || 
                (strtolower($previousStatus) === 'reject' && strtolower($newStatus) === 'pending')) {
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
                    
                    // Get provider type from order or plan
                    $providerType = $order->provider_type ?? ($order->plan ? $order->plan->provider_type : null);
                    
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
                        'new_status' => $newStatus,
                        'provider_type' => $providerType,
                        'updated_by' => auth()->user() ? auth()->user()->name : 'System'
                    ];
                    // if new status is not equal to cancelled
                    if (strtolower($newStatus) !== 'cancelled') {
                        SlackNotificationService::sendNewOrderAvailableNotification($orderData);
                        \Log::channel('slack_notifications')->info('OrderObserver: Slack notification sent for new order available', [
                            'order_id' => $order->id,
                            'previous_status' => $previousStatus,
                            'new_status' => $newStatus
                        ]);
                    }
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
            
            // Send Slack notification if order is rejected
            if (strtolower($newStatus) === 'reject') {
                try {
                    SlackNotificationService::sendOrderRejectionNotification($order, $reason);
                    \Log::channel('slack_notifications')->info('OrderObserver: Slack notification sent for rejected order', [
                        'order_id' => $order->id,
                        'reason' => $reason
                    ]);
                } catch (\Exception $e) {
                    \Log::channel('slack_notifications')->error('OrderObserver: Failed to send Slack notification for rejected order', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
                
                // Delete mailboxes from Mailin.ai when order is rejected
                try {
                    $orderId = $order->id;
                    dispatch(function () use ($orderId) {
                        \Illuminate\Support\Facades\Artisan::call('order:delete-mailboxes', [
                            'order_id' => $orderId
                        ]);
                    })->afterResponse();
                    
                    \Log::channel('mailin-ai')->info('OrderObserver: Dispatched command to delete mailboxes for rejected order', [
                        'order_id' => $order->id,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus
                    ]);
                } catch (\Exception $e) {
                    \Log::channel('mailin-ai')->error('OrderObserver: Failed to dispatch mailbox deletion command', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Send Slack notification if order is completed
            if (strtolower($newStatus) === 'completed') {
                try {
                    SlackNotificationService::sendOrderCompletionNotification($order);
                    \Log::channel('slack_notifications')->info('OrderObserver: Slack notification sent for completed order', [
                        'order_id' => $order->id
                    ]);
                } catch (\Exception $e) {
                    \Log::channel('slack_notifications')->error('OrderObserver: Failed to send Slack notification for completed order', [
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
        
        // Check if is_shared was changed
        if (isset($changes['is_shared'])) {
            $previousSharedStatus = $order->getOriginal('is_shared');
            $newSharedStatus = $order->is_shared;
            ActivityLogService::log(
                'Order Shared Status Changed',
                "Order #{$order->id} shared status changed: " . ($order->is_shared ? 'Helper Request Added' : 'Helper Request Removed') . '. Note: ' . ($order->shared_note ?? 'No note provided'),
                $order,
                [
                    'shared_status' => $order->is_shared,
                    'shared_note' => $order->shared_note ?? 'No note provided',
                    'helper_request_added' => $order->is_shared ? 'Helper request added' : 'Helper request removed'
                ]
            );
            \Log::info('OrderObserver: Shared status change detected', [
                'order_id' => $order->id,
                'previous_shared_status' => $previousSharedStatus,
                'new_shared_status' => $newSharedStatus
            ]);
            
            // Send Slack notification when shared status changes
            try {
                SlackNotificationService::sendOrderSharedStatusNotification($order, $previousSharedStatus, $newSharedStatus);
                \Log::channel('slack_notifications')->info('OrderObserver: Slack notification sent for shared status change', [
                    'order_id' => $order->id,
                    'previous_shared_status' => $previousSharedStatus,
                    'new_shared_status' => $newSharedStatus
                ]);
            } catch (\Exception $e) {
                \Log::channel('slack_notifications')->error('OrderObserver: Failed to send Slack notification for shared status change', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
            }
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
            
            // Handle first assignment (from null to assigned)
            if (is_null($oldAssignedTo) && !is_null($newAssignedTo)) {
                \Log::info('OrderObserver: Creating notification for FIRST contractor assignment', [
                    'order_id' => $order->id,
                    'assigned_to' => $newAssignedTo,
                    'auth_user_id' => Auth::id(),
                    'assignment_type' => 'first_assignment'
                ]);

                try {
                    // Create a notification for the contractor
                    Notification::create([
                        'user_id' => $newAssignedTo,
                        'type' => 'order_assigned',
                        'title' => 'New Order Assigned',
                        'message' => 'You have been assigned to order #' . $order->id,
                        'data' => [
                            'order_id' => $order->id,
                            'assigned_to' => $newAssignedTo,
                            'assigned_by' => Auth::id(),
                            'assignment_type' => 'first_assignment'
                        ],
                        'is_read' => false
                    ]);

                    // Create a log entry for first assignment
                    ActivityLogService::log(
                        'order-assigned',
                        'Order assigned to contractor: ' . $order->id . ' - ' . ($order->assignedTo ? $order->assignedTo->name : 'Unknown'),
                        $order,
                        [
                            'order_id' => $order->id,
                            'assigned_to' => $newAssignedTo,
                            'assignment_type' => 'first_assignment'
                        ],
                        Auth::id() ?? 1 // Performed By - fallback to admin if no auth
                    );
                    
                    // Send Slack notification for first assignment
                    SlackNotificationService::sendOrderAssignmentNotification($order, $newAssignedTo);
                    \Log::channel('slack_notifications')->info('OrderObserver: Slack notification sent for FIRST order assignment', [
                        'order_id' => $order->id,
                        'assigned_to' => $newAssignedTo,
                        'assignment_type' => 'first_assignment'
                    ]);
                    
                    \Log::info('OrderObserver: First order assignment notification created successfully', [
                        'order_id' => $order->id,
                        'assigned_to' => $newAssignedTo
                    ]);
                } catch (\Exception $e) {
                    \Log::error('OrderObserver: Failed to create first assignment notification', [
                        'order_id' => $order->id,
                        'assigned_to' => $newAssignedTo,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            // Handle reassignment (from one contractor to another)
            elseif (!is_null($oldAssignedTo) && !is_null($newAssignedTo) && $oldAssignedTo !== $newAssignedTo) {
                \Log::info('OrderObserver: Creating notification for contractor REASSIGNMENT', [
                    'order_id' => $order->id,
                    'old_assigned_to' => $oldAssignedTo,
                    'new_assigned_to' => $newAssignedTo,
                    'auth_user_id' => Auth::id(),
                    'assignment_type' => 'reassignment'
                ]);

                try {
                    // Create notification for the NEW contractor
                    Notification::create([
                        'user_id' => $newAssignedTo,
                        'type' => 'order_reassigned',
                        'title' => 'Order Reassigned to You',
                        'message' => 'Order #' . $order->id . ' has been reassigned to you',
                        'data' => [
                            'order_id' => $order->id,
                            'assigned_to' => $newAssignedTo,
                            'previous_assigned_to' => $oldAssignedTo,
                            'assigned_by' => Auth::id(),
                            'assignment_type' => 'reassignment'
                        ],
                        'is_read' => false
                    ]);

                    // Create notification for the OLD contractor (order removed)
                    Notification::create([
                        'user_id' => $oldAssignedTo,
                        'type' => 'order_unassigned',
                        'title' => 'Order Reassigned',
                        'message' => 'Order #' . $order->id . ' has been reassigned to another contractor',
                        'data' => [
                            'order_id' => $order->id,
                            'previous_assigned_to' => $oldAssignedTo,
                            'new_assigned_to' => $newAssignedTo,
                            'assigned_by' => Auth::id(),
                            'assignment_type' => 'reassignment'
                        ],
                        'is_read' => false
                    ]);

                    // Create a log entry for reassignment
                    ActivityLogService::log(
                        'order-reassigned',
                        'Order reassigned from ' . (\App\Models\User::find($oldAssignedTo)->name ?? 'Unknown') . ' to ' . ($order->assignedTo ? $order->assignedTo->name : 'Unknown') . ': Order #' . $order->id,
                        $order,
                        [
                            'order_id' => $order->id,
                            'old_assigned_to' => $oldAssignedTo,
                            'new_assigned_to' => $newAssignedTo,
                            'assignment_type' => 'reassignment'
                        ],
                        Auth::id() ?? 1 // Performed By - fallback to admin if no auth
                    );
                    
                    // Send Slack notification for reassignment
                    SlackNotificationService::sendOrderReassignmentNotification($order, $oldAssignedTo, $newAssignedTo);
                    \Log::channel('slack_notifications')->info('OrderObserver: Slack notification sent for order REASSIGNMENT', [
                        'order_id' => $order->id,
                        'old_assigned_to' => $oldAssignedTo,
                        'new_assigned_to' => $newAssignedTo,
                        'assignment_type' => 'reassignment'
                    ]);
                    
                    \Log::info('OrderObserver: Order reassignment notifications created successfully', [
                        'order_id' => $order->id,
                        'old_assigned_to' => $oldAssignedTo,
                        'new_assigned_to' => $newAssignedTo
                    ]);
                } catch (\Exception $e) {
                    \Log::error('OrderObserver: Failed to create reassignment notifications', [
                        'order_id' => $order->id,
                        'old_assigned_to' => $oldAssignedTo,
                        'new_assigned_to' => $newAssignedTo,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
            // Handle unassignment (from assigned to null)
            // elseif (!is_null($oldAssignedTo) && is_null($newAssignedTo)) {
            //     \Log::info('OrderObserver: Creating notification for contractor UNASSIGNMENT', [
            //         'order_id' => $order->id,
            //         'old_assigned_to' => $oldAssignedTo,
            //         'auth_user_id' => Auth::id(),
            //         'assignment_type' => 'unassignment'
            //     ]);

            //     try {
            //         // Create notification for the contractor who was unassigned
            //         Notification::create([
            //             'user_id' => $oldAssignedTo,
            //             'type' => 'order_unassigned',
            //             'title' => 'Order Unassigned',
            //             'message' => 'Order #' . $order->id . ' has been unassigned from you',
            //             'data' => [
            //                 'order_id' => $order->id,
            //                 'previous_assigned_to' => $oldAssignedTo,
            //                 'assigned_by' => Auth::id(),
            //                 'assignment_type' => 'unassignment'
            //             ],
            //             'is_read' => false
            //         ]);

            //         // Create a log entry for unassignment
            //         ActivityLogService::log(
            //             'order-unassigned',
            //             'Order unassigned from contractor: ' . $order->id . ' - ' . (\App\Models\User::find($oldAssignedTo)->name ?? 'Unknown'),
            //             $order,
            //             [
            //                 'order_id' => $order->id,
            //                 'previous_assigned_to' => $oldAssignedTo,
            //                 'assignment_type' => 'unassignment'
            //             ],
            //             Auth::id() ?? 1 // Performed By - fallback to admin if no auth
            //         );
                    
            //         // Send Slack notification for unassignment
            //         SlackNotificationService::sendOrderUnassignmentNotification($order, $oldAssignedTo);
            //         \Log::channel('slack_notifications')->info('OrderObserver: Slack notification sent for order UNASSIGNMENT', [
            //             'order_id' => $order->id,
            //             'old_assigned_to' => $oldAssignedTo,
            //             'assignment_type' => 'unassignment'
            //         ]);
                    
            //         \Log::info('OrderObserver: Order unassignment notification created successfully', [
            //             'order_id' => $order->id,
            //             'old_assigned_to' => $oldAssignedTo
            //         ]);
            //     } catch (\Exception $e) {
            //         \Log::error('OrderObserver: Failed to create unassignment notification', [
            //             'order_id' => $order->id,
            //             'old_assigned_to' => $oldAssignedTo,
            //             'error' => $e->getMessage(),
            //             'trace' => $e->getTraceAsString()
            //         ]);
            //     }
            // } 
            else {
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
            // if ($newStatus === 'reject' && !is_null($order->timer_started_at) && is_null($order->timer_paused_at)) {
            if ($newStatus === 'reject') {
                $order->timer_paused_at = now();
            }
            
            if ($newStatus === 'cancelled') {
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
