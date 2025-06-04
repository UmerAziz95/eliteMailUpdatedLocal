<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPanelSplit extends Model
{
    // order_panel_splits
    protected $table = 'order_panel_split';
    use HasFactory;

    protected $fillable = [
        'panel_id',
        'order_panel_id',
        'order_id',
        'inboxes_per_domain',
        'domains',
    ];

    protected $casts = [
        'domains' => 'array',
    ];

    public function orderPanel()
    {
        return $this->belongsTo(OrderPanel::class);
    }

    public function userPanelAssignment()
    {
        return $this->hasOne(UserOrderPanelAssignment::class, 'order_panel_id', 'order_panel_id');
    }
}