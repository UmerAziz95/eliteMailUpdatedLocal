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

class OrderCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
        
        \Log::info('OrderCreated event instantiated', [
            'order_id' => $order->id,
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
        return 'order.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        // Load relationships needed for the contractor panel
        $this->order->load(['user', 'plan']);
        
        $broadcastData = [
            'order' => [
                'id' => $this->order->id,
                'status' => $this->order->status,
                'total' => $this->order->total,
                'created_at' => $this->order->created_at,
                'user' => [
                    'id' => $this->order->user->id,
                    'name' => $this->order->user->name,
                    'email' => $this->order->user->email
                ],
                'plan' => [
                    'id' => $this->order->plan->id,
                    'name' => $this->order->plan->name
                ]
            ],
            'message' => 'New order created',
            'type' => 'created'
        ];
        
        \Log::info('OrderCreated broadcasting data', [
            'channel' => 'orders',
            'event' => 'order.created',
            'order_id' => $this->order->id,
            'data_keys' => array_keys($broadcastData)
        ]);
        
        return $broadcastData;
    }
}
