<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsedEmailsInOrder extends Model
{
    protected $table = 'used_emails_in_order';

    protected $fillable = [
        'order_id',
        'emails',
        'count',
    ];

    protected $casts = [
        'emails' => 'array', // Automatically cast JSON to array
    ];

    /**
     * Get the order that owns the used emails record
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
