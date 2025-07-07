<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
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
            
            // If status is changing from pending to something else (except rejected)
            if ($oldStatus === 'pending' && $newStatus !== 'pending' && $newStatus !== 'reject' && !is_null($order->timer_started_at)) {
                // Keep timer_started_at as is - don't reset it
                // This allows us to track when the timer originally started
            }
        }
    }
}
