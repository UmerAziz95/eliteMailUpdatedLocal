<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
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

    Setting::updateOrCreate(
        ['setting_name' => 'discord_message'],
        [
            'setting_value' => $request->input('cron_message'),
            'discord_message_cron' => $request->input('enable_cron', false)
        ]
    );

    return response()->json([
        "status" => "success",
        "message" => "Discord settings saved successfully."
    ]);
}


public function sendDiscordMessage(Request $request)
{
    $request->validate([
        'message' => 'required|string|max:2000',
    ]);

    $webhookUrl = 'https://discord.com/api/webhooks/1393571644597080205/BomZ2K7u84JZZPOdNBZiqVdSlhtUxCBokuXiGNfK4yJwwKDyTuubrHQqqmnIt0g3Hnd6';

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

}
