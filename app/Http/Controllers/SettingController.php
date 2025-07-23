<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiscordSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;


class SettingController extends Controller
{
    //

    public function index()
    {
        // Logic to display settings page
        return view('admin.discord.index');
    }

public function saveDiscordSettings(Request $request)
{
    $request->validate([
        'cron_message' => 'required',
    ]);

    // Save and capture the updated or created record
    $setting = DiscordSettings::updateOrCreate(
        ['setting_name' => 'discord_message'],
        [
            'setting_value' => $request->input('cron_message'),
            'discord_message_cron' => $request->input('enable_cron', false),
            'cron_start_from' => $request->input('cron_start'),
            'cron_occurrence' => $request->input('cron_occurrence', null) //
        ]
    );

    return response()->json([
        "status" => "success",
        "message" => "Discord settings saved successfully.",
        "data" => $setting
    ]);
}



public function sendDiscordMessage(Request $request)
{
    $request->validate([
        'message' => 'required|string|max:2000',
    ]);

    $webhookUrl = 'https://discord.com/api/webhooks/1397108980245073942/0woNwztt1BXW7jwq6u2mGWBbrMZFqbcvfiOSULUBkSJsmF-wRlKzkYEf1x_MFSEYYNUF';
    // $webhookUrl = 'https://discord.com/api/webhooks/1393571644597080205/BomZ2K7u84JZZPOdNBZiqVdSlhtUxCBokuXiGNfK4yJwwKDyTuubrHQqqmnIt0g3Hnd6';

    try {
        // Generate full URL to /plans/discounted
        $link = URL::to('/plans/discounted');

        // Combine message and link
        $fullMessage = $request->input('message') . "\n" . $link;

        // Send to Discord webhook
        Http::post($webhookUrl, [
            'content' => $fullMessage,
        ]);

        return response()->json([
            "status" => "success",
            "message" => "Message sent successfully to Discord."
        ]);
    } catch (\Exception $e) {
        return response()->json([
            "status" => "error",
            "message" => "Failed to send message to Discord: " . $e->getMessage()
        ]);
    }
}


public function toggleDiscordCron(Request $request)
{
    $isEnabled = $request->input('enable_cron') ? 1 : 0;
    DiscordSettings::updateOrCreate(
        ['setting_name' => 'discord_message'],
        ['discord_message_cron' => $isEnabled]
    );

    return response()->json([
        'status' => 'success',
        'message' => 'Cron setting updated successfully.'
    ]);
}


public function getCronSettings()
{
    $settings = DiscordSettings::where('setting_name', 'discord_message')->first();
    return response()->json([
        'enable_cron' => $settings->discord_message_cron,  // 1 or 0
        'cron_message' => $settings->setting_value ?? '',
        'cron_start' => $settings->cron_start_from ?? null,
        'cron_occurrence' => $settings->cron_occurrence ?? null,
    ]);
}

} 
