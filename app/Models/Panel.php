<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Panel extends Model
{
    use HasFactory;

    protected $fillable = [
        'auto_generated_id',
        'title',
        'description',
        'limit',
        'remaining_limit',
        'is_active',
        'created_by',
    ];

    public function order_panels()
    {
        return $this->hasMany(OrderPanel::class);
    }
  
}
