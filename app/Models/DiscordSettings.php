<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscordSettings extends Model
{
    use HasFactory;
    protected $fillable = [
        'setting_name',
        'setting_value',
        'discord_message_cron',
        'cron_start_from',
        'cron_occurrence',
        'last_run_at'
    ];
}
