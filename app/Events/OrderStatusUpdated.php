<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $previousStatus;
    public $newStatus;
    public $reason;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, string $previousStatus = null, string $newStatus = null, string $reason = null)
    {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus ?? $order->status_manage_by_admin;
        $this->reason = $reason;
        
        \Log::info('OrderStatusUpdated event instantiated', [
            'order_id' => $order->id,
            'previous_status' => $previousStatus,
            'new_status' => $this->newStatus,
            'reason' => $reason,
            'broadcast_driver' => config('broadcasting.default')
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Load relationships needed for the contractor panel
        $this->order->load(['user', 'plan']);
        
        $broadcastData = [
            'order' => $this->order->toArray(),
            'previous_status' => $this->previousStatus,
            'status' => $this->newStatus,
            'reason' => $this->reason,
            'message' => 'Order status updated',
            'type' => 'status_updated'
        ];
        
        \Log::info('OrderStatusUpdated broadcasting data', [
            'channel' => 'orders',
            'event' => 'order.status.updated',
            'order_id' => $this->order->id,
            'data_keys' => array_keys($broadcastData)
        ]);
        
        return $broadcastData;
    }
}
