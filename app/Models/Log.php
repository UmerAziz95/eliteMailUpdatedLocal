<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $fillable = [
        'action_type',
        'performed_by',
        'performed_on_id',
        'performed_on_type',
        'description',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
    
    public function performedOn()
    {
        return $this->morphTo(__FUNCTION__, 'performed_on_type', 'performed_on_id');
    }
}
