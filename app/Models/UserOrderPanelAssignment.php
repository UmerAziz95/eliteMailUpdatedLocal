<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOrderPanelAssignment extends Model
{
    use HasFactory;



    public function orderPanel()
    {
        return $this->belongsTo(OrderPanel::class, 'order_panel_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
