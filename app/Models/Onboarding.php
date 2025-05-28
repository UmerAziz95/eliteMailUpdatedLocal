<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Onboarding extends Model
{
    use HasFactory;
     protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'role',
        'company_name',
        'website',
        'company_size',
        'inboxes_tested_last_month',
        'monthly_spend',
    ]; 
}
