<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTracking extends Model
{
    use HasFactory;

    protected $table = 'order_tracking';

    protected $fillable = [
        'order_id',
        'status',
        'cron_run_time',
        'inboxes_per_domain',
        'total_inboxes'
    ];

    protected $casts = [
        'cron_run_time' => 'datetime',
        'inboxes_per_domain' => 'integer',
        'total_inboxes' => 'integer'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
