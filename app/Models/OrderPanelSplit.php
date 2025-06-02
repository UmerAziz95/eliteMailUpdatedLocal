<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPanelSplit extends Model
{
    use HasFactory;



    public function orderPanel()
    {
        return $this->belongsTo(OrderPanel::class);
    }

    public function userPanelAssignment()
    {
        return $this->hasOne(UserOrderPanelAssignment::class, 'order_panel_id', 'order_panel_id');
    }
}
