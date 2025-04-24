<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEmail extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'name',
        'email',
        'password',
        'profile_picture',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
