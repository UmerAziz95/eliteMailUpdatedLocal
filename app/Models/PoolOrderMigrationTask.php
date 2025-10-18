<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoolOrderMigrationTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'pool_order_id',
        'user_id',
        'assigned_to',
        'task_type',
        'status',
        'previous_status',
        'new_status',
        'notes',
        'metadata',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the pool order that owns the migration task
     */
    public function poolOrder()
    {
        return $this->belongsTo(PoolOrder::class);
    }

    /**
     * Get the customer user that owns the order
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the admin/staff assigned to handle this task
     */
    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope to filter tasks by type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('task_type', $type);
    }

    /**
     * Scope to filter tasks by status
     */
    public function scopeOfStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending tasks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get assigned tasks
     */
    public function scopeAssigned($query)
    {
        return $query->whereNotNull('assigned_to');
    }

    /**
     * Scope to get unassigned tasks
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }
}
