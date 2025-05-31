<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Panel extends Model
{
      use HasFactory;

    protected $fillable = ['title', 'description', 'status', 'limit', 'user_id', 'created_by'];

    protected $casts = [
        'user_id' => 'array', // important to cast as array
    ];

 protected static function boot()
    {
        parent::boot();

        static::creating(function ($panel) {
            $latestId = Panel::max('id') + 1;
            $panel->auto_generated_id = 'PNL-' . str_pad($latestId, 4, '0', STR_PAD_LEFT);
        });
    }

    public function users()
{
    return $this->belongsToMany(User::class)
                ->withTimestamps()
                ->withPivot('accepted_at', 'released_at');
}

public function orders()
{
    return $this->belongsToMany(Order::class, 'order_panel', 'panel_id', 'order_id')->withTimestamps();
}

}