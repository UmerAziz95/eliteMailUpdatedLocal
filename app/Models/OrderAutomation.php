<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAutomation extends Model
{
    use HasFactory;

    protected $table = 'order_automations';

    protected $fillable = [
        'order_id',
        'provider_type',
        'job_uuid',
        'status',
        'response_data',
        'error_message',
    ];

    protected $casts = [
        'response_data' => 'array',
    ];

    /**
     * Get the order that owns this automation
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

