<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\DomainRemovalTask;

class TaskStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;

    /**
     * Create a new event instance.
     */
    public function __construct(DomainRemovalTask $task)
    {
        $this->task = $task;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('domain-removal-tasks'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'task.started';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'task' => [
                'task_id' => $this->task->task_id,
                'id' => $this->task->id,
                'started_queue_date' => $this->task->started_queue_date,
                'status' => $this->task->status,
                'customer_name' => $this->task->customer_name ?? $this->task->user->name ?? 'N/A',
                'order_id' => $this->task->order_id,
                'total_inboxes' => $this->task->total_inboxes,
                'total_domains' => $this->task->total_domains,
            ],
            'message' => "Task #{$this->task->task_id} has been queued for processing",
            'timestamp' => now()->toISOString(),
        ];
    }
}
