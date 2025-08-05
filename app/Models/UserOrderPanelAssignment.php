<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserOrderPanelAssignment extends Model
{
    use HasFactory;

    protected $table = 'user_order_panel_assignment';

    protected $fillable = [
        'order_panel_id',
        'order_panel_split_id',
        'order_id',
        'contractor_id',
        'status',
        'original_order_panel_id',
        'reassigned_at',
        'reassignment_note',
    ];

    public function orderPanel()
    {
        return $this->belongsTo(OrderPanel::class, 'order_panel_id');
    }

    public function contractor()
    {
        return $this->belongsTo(User::class, 'contractor_id');
    }
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderPanelSplit()
    {
        return $this->belongsTo(OrderPanelSplit::class, 'order_panel_split_id');
    }
}
