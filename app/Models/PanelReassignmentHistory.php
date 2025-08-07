<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PanelReassignmentHistory extends Model
{
    use HasFactory;

    protected $table = 'panel_reassignment_history';

    protected $fillable = [
        'order_id',
        'order_panel_id',
        'from_panel_id',
        'to_panel_id',
        'reassigned_by',
        'reassignment_date',
        'status',
        'assigned_to',
        'action_type',
        'space_transferred',
        'splits_count',
        'split_ids',
        'reason',
        'notes',
        'task_started_at',
        'task_completed_at',
        'completion_notes'
    ];

    protected $casts = [
        'split_ids' => 'array',
        'space_transferred' => 'decimal:2',
        'reassignment_date' => 'datetime',
        'task_started_at' => 'datetime',
        'task_completed_at' => 'datetime'
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderPanel()
    {
        return $this->belongsTo(OrderPanel::class);
    }

    public function fromPanel()
    {
        return $this->belongsTo(Panel::class, 'from_panel_id');
    }

    public function toPanel()
    {
        return $this->belongsTo(Panel::class, 'to_panel_id');
    }

    public function reassignedBy()
    {
        return $this->belongsTo(User::class, 'reassigned_by');
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Scopes
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByActionType($query, $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in-progress');
    }

    // Helper methods
    public function markAsStarted($userId = null)
    {
        $this->update([
            'status' => 'in-progress',
            'task_started_at' => now(),
            'assigned_to' => $userId ?? $this->assigned_to
        ]);
    }

    public function markAsCompleted($notes = null)
    {
        $this->update([
            'status' => 'completed',
            'task_completed_at' => now(),
            'completion_notes' => $notes
        ]);
    }

    public function isRemoval()
    {
        return $this->action_type === 'removed';
    }

    public function isAddition()
    {
        return $this->action_type === 'added';
    }
}
