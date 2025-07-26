<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainRemovalTask extends Model
{
    use HasFactory;

    protected $table = 'domain_removal_tasks';

    protected $fillable = [
        'started_queue_date',
        'user_id',
        'order_id',
        'chargebee_subscription_id',
        'reason',
        'assigned_to',
        'status',
        'broadcasted_at',
        'slack_alerts'
    ];

    protected $casts = [
        'started_queue_date' => 'datetime',
        'broadcasted_at' => 'datetime',
        'slack_alerts' => 'array',
    ];

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the order associated with this task
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the admin/user assigned to handle this task
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
