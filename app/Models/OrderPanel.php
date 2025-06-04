<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPanel extends Model
{
    protected $table = 'order_panel';
    use HasFactory;

    protected $fillable = [
        'panel_id',
        'order_id',
        'contractor_id',
        'space_assigned',
        'inboxes_per_domain',
        'status',
        'note',
        'accepted_at',
        'released_at',
    ];

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