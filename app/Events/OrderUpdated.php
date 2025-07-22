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

class OrderUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $changes;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, array $changes = [])
    {
        $this->order = $order;
        $this->changes = $changes;
        
        \Log::info('OrderUpdated event instantiated', [
            'order_id' => $order->id,
            'changes' => $changes,
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
        return 'order.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        $broadcastData = [
            'order' => [
                'id' => $this->order->id,
                'status' => $this->order->status,
                'total' => $this->order->total,
                'user_id' => $this->order->user_id,
                'plan_id' => $this->order->plan_id,
                'updated_at' => $this->order->updated_at,
            ],
            'changes' => $this->changes,
            'message' => 'Order updated',
            'type' => 'updated'
        ];
        
        \Log::info('OrderUpdated broadcasting data', [
            'channel' => 'orders',
            'event' => 'order.updated',
            'order_id' => $this->order->id,
            'changes' => $this->changes,
            'data_keys' => array_keys($broadcastData)
        ]);
        
        return $broadcastData;
    }
}
