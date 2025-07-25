<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DiscordSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use App\Models\ShortEncryptedLink;

class SettingController extends Controller
{
    public function index()
    {
        return view('admin.discord.index');
    }

    public function saveDiscordSettings(Request $request)
    {
        $request->validate([
            'cron_message' => 'required',
        ]);

        $setting = DiscordSettings::updateOrCreate(
            ['setting_name' => 'discord_message'],
            [
                'setting_value' => $request->input('cron_message'),
                'discord_message_cron' => $request->input('enable_cron', false),
                'cron_start_from' => $request->input('cron_start'),
                'cron_occurrence' => $request->input('cron_occurrence', null)
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
    $uuid = (string) Str::uuid();

    // Create your actual target URL
    $embeddedUrl = URL::to('/plans/' . $uuid . '/discounted');
    $settings->url_string = $uuid;
    $settings->embedded_url = $embeddedUrl;
    $settings->save();
    // Encrypt the long URL and store it in DB
    $encrypted = Crypt::encryptString($embeddedUrl);
    $short = Str::random(20); // shorter than UUID

    ShortEncryptedLink::create([
        'slug' => $short,
        'encrypted_url' => $encrypted,
    ]);

    // Now send short URL like /go/abc12345
    $shortUrl = URL::to('/go/' . $short);

    $cronMessage = $request->input('message');
    $fullMessage = $cronMessage . "\n" . $shortUrl;

    Http::post($webhookUrl, [
        'content' => $fullMessage,
    ]);

    return response()->json([
        "status" => "success",
        "message" => "Message sent successfully to Discord.",
    ]);
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
            'enable_cron' => $settings->discord_message_cron ?? 0,
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
        $cronStart       = $settings->cron_start_from ?? null;
        $cronOccurrence  = $settings->cron_occurrence ?? null;
        $lastRun         = $settings->last_run_at ?? null;

        if (!$cronMessage || !$cronStart || !$cronOccurrence) {
            return false;
        }

        $nowUtc = now()->setTimezone('UTC');
        $startAt = Carbon::createFromFormat('Y-m-d H:i:s', $cronStart, 'UTC');

    // // Skip if it's not time yet
    if ($nowUtc->lt($startAt)) {
        return false;
    }

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

    try {
        // 1. Generate UUID
    $settings = DiscordSettings::where('setting_name', 'discord_message')->first();
    $webhookUrl = env('DISCORD_WEBHOOK_URL', '');
    $uuid = (string) Str::uuid();

    // Create your actual target URL
    $embeddedUrl = URL::to('/plans/' . $uuid . '/discounted');
    $settings->url_string = $uuid;
    $settings->embedded_url = $embeddedUrl;
    $settings->save();
    // Encrypt the long URL and store it in DB
    $encrypted = Crypt::encryptString($embeddedUrl);
    $short = Str::random(20); // shorter than UUID

    ShortEncryptedLink::create([
        'slug' => $short,
        'encrypted_url' => $encrypted,
    ]);

    // Now send short URL like /go/abc12345
    $shortUrl = URL::to('/go/' . $short);
    // Log::info("short url----------------------------".$shortUrl);

   
    $fullMessage = $cronMessage . "\n" . $shortUrl;

    Http::post($webhookUrl, [
        'content' => $fullMessage,
    ]);

            if ($response->failed()) {
                Log::error('Discord webhook request failed: ' . $response->body());
                return false;
            }

            $settings->last_run_at = $nowUtc;
            $settings->save();

            Log::info('Discord message sent.', [
                'message' => $fullMessage,
                'cron_start' => $cronStart,
                'occurrence' => $cronOccurrence,
                'run_at' => $nowUtc,
            ]);

            return [
                'message' => 'Discord message sent.',
                'at' => $nowUtc->toDateTimeString()
            ];
        } catch (RequestException $e) {
            Log::error('Discord webhook request failed: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('Failed to send Discord message: ' . $e->getMessage());
            return false;
        }
    }
}