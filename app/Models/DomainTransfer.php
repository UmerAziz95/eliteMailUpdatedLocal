<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainTransfer extends Model
{
    use HasFactory;

    protected $table = 'domain_transfers';

    protected $fillable = [
        'order_id',
        'domain_name',
        'name_servers',
        'status',
        'error_message',
        'response_data',
    ];

    protected $casts = [
        'name_servers' => 'array',
        'response_data' => 'array',
    ];

    /**
     * Get the order that owns this domain transfer
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

