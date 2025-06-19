<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderEmail extends Model
{
    protected $fillable = [
        'order_id',
        'user_id',
        'order_split_id',
        'contractor_id',
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

    public function orderSplit()
    {
        return $this->belongsTo(OrderPanelSplit::class, 'order_split_id');
    }

    public function contractor()
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }
}
