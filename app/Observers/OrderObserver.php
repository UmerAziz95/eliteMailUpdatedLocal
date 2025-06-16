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
            }
            
            // If status is changing from completed to something else, clear completed_at
            if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                $order->completed_at = null;
            }
        }
    }
}
