<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShortEncryptedLink extends Model
{
    use HasFactory;
     protected $fillable = [
        'slug',
        'encrypted_url',
    ];
}
