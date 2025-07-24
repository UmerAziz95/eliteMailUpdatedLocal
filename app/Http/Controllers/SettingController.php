<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiscordSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

        $settings = DiscordSettings::where('setting_name', 'discord_message')->first();
        $webhookUrl = env('DISCORD_WEBHOOK_URL', '');

    try {
         $uuid = (string) Str::uuid();

                // 2. Build the embedded URL
                $embeddedUrl = URL::to('/plans/'.$uuid.'/discounted');

                // 3. Update the database
                $settings->url_string = $uuid;
                $settings->embedded_url = $embeddedUrl;
                $settings->save();

                // 4. Use in your message
                $cronMessage = $request->input('message') ?? '🔥 Don’t miss your chance to upgrade at a reduced price.
                    💡 Supercharge your email & inbox productivity with AI today.
                    👉 Click the link below to grab the offer now:';
                $fullMessage = $cronMessage . "\n" . $embeddedUrl;

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


public static function discorSendMessageCron()
{
    Log::info('✅ discorSendMessageCron method triggered.');
    $settings = DiscordSettings::where('setting_name', 'discord_message')->first();

    if (!$settings || !$settings->discord_message_cron) {
        return false;
    }

    $cronMessage     = $settings->setting_value ?? "🔥 Don’t miss your chance to upgrade at a reduced price.";
    $cronStart       = $settings->cron_start_from ?? null; // '2025-07-24 08:25:00' UTC
    $cronOccurrence  = $settings->cron_occurrence ?? null; // 'daily', 'weekly', 'monthly'
    $lastRun         = $settings->last_run_at ?? null;     // Optional: last run timestamp (add to your DB if needed)

    if (!$cronMessage || !$cronStart || !$cronOccurrence) {
        return false;
    }

    // Convert cronStart string to Carbon instance (assumed UTC)
    $nowUtc = now()->setTimezone('UTC');
    $startAt = Carbon::createFromFormat('Y-m-d H:i:s', $cronStart, 'UTC');

    // Skip if it's not time yet
    if ($nowUtc->lt($startAt)) {
        return false;
    }

    // Optional: avoid duplicate sends — skip if already sent today/this week/month
    if ($lastRun) {
        $lastRunAt = Carbon::parse($lastRun, 'UTC');

        $shouldSkip = match ($cronOccurrence) {
            'daily'   => $lastRunAt->isToday(),
            'weekly'  => $lastRunAt->diffInDays($nowUtc) < 7,
            'monthly' => $lastRunAt->month === $nowUtc->month && $lastRunAt->year === $nowUtc->year,
            default   => true,
        };

        if ($shouldSkip) {
            return false;
        }
    }

    $webhookUrl = env('DISCORD_WEBHOOK_URL', '');
    try {
       // 1. Generate UUID
            $uuid = (string) Str::uuid();

            // 2. Build the embedded URL
            $embeddedUrl = URL::to('/plans/'.$uuid.'/discounted');

            // 3. Update the database
            $settings->url_string = $uuid;
            $settings->embedded_url = $embeddedUrl;
            $settings->save();

                // 4. Use in your message
                $cronMessage = $settings->cron_message ?? 'No message set.';
                $fullMessage = $cronMessage . "\n" . $embeddedUrl;

                Http::post($webhookUrl, [
                    'content' => $fullMessage,
                ]);

        // Save last run time to avoid duplicates (if DB has such a column)
                $settings->last_run_at = $nowUtc;
                $settings->save();

                Log::info('Discord message sent.', [
                    'message' => $fullMessage,
                    'cron_start' => $cronStart,
                    'occurrence' => $cronOccurrence,
                    'run_at' => $nowUtc,
                ]);

                return response()->json([
                    'message' => 'Discord message sent.',
                    'at' => $nowUtc->toDateTimeString()
                ], 200);
    } catch (\Exception $e) {
        Log::error('Failed to send Discord message: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to send message.'], 500);
    }
}

} 
