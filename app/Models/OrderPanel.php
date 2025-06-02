<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPanel extends Model
{
    use HasFactory;



    public function panel()
    {
        return $this->belongsTo(Panel::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function contractor()
    {
        return $this->belongsTo(User::class , 'contractor_id');
    }

    public function orderPanelSplits()
    {
        return $this->hasMany(OrderPanelSplit::class);
    }

}
