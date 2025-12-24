<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailinJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'type',
        'job_id',
        'status',
        'request_payload_json',
        'response_json',
    ];

    protected $casts = [
        'request_payload_json' => 'array',
        'response_json' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
