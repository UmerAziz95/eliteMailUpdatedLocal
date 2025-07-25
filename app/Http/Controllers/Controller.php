<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\DiscordSettings;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;


    public function discordMessageCron(Request $request){
        $settings = DiscordSettings::where('setting_name', 'discord_message')->first();
        $cronEnabled = $settings ? $settings->discord_message_cron : false;
        $cronStartFrom = $settings ? $settings->cron_start_from : null;
        $cronOccurrence = $settings ? $settings->cron_occurrence : null;
        $cronMessage = $settings ? $settings->setting_value : null;
        $currentTime = now();
        if($cronEnabled && $cronMessage) {
            if ($cronStartFrom && $currentTime->greaterThanOrEqualTo($cronStartFrom)) {
                // Check if the current time matches the cron occurrence
                if ($cronOccurrence === 'daily' || $cronOccurrence === 'weekly' || $cronOccurrence === 'monthly') {
                    
                }
            }
        }



       
    }
}
