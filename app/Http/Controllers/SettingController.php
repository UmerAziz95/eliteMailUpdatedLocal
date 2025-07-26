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
use Illuminate\Http\Client\RequestException;

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
        try {
            $request->validate([
                'message' => 'required|string|max:2000',
            ]);

            $settings = DiscordSettings::where('setting_name', 'discord_message')->first();
            if (!$settings) {
                return response()->json([
                    "status" => "error",
                    "message" => "Settings not found."
                ], 404);
            }

            $webhookUrl = env('DISCORD_WEBHOOK_URL', '');
            
            if (empty($webhookUrl)) {
                return response()->json([
                    "status" => "error",
                    "message" => "Discord Webhook URL is missing."
                ], 500);
            }

            $uuid = Str::uuid();
            $embeddedUrl = URL::to('/plans/' . $uuid . '/discounted');

            $settings->url_string = $uuid;
            $settings->embedded_url = $embeddedUrl;
            $settings->save();

            $encrypted = Crypt::encryptString($embeddedUrl);
            $redirectUrl = URL::to('/go?encrypted=' . urlencode($encrypted));

            $cronMessage = $request->input('message') ?? '🔥 Don’t miss your chance...';
            $fullMessage = $cronMessage . "\n" . $redirectUrl;

            $response = Http::post($webhookUrl, [
                'content' => $fullMessage,
            ]);
         

            if ($response->failed()) {
                return response()->json([
                    "status" => "error",
                    "message" => "Discord webhook request failed.",
                    "response" => $response->body()
                ], 500);
            }

            return response()->json([
                "status" => "success",
                "message" => "Message sent successfully to Discord."
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                "status" => "error",
                "message" => "Validation failed.",
                "errors" => $e->errors()
            ], 422);

        } catch (RequestException $e) {
            return response()->json([
                "status" => "error",
                "message" => "Discord webhook request failed: " . $e->getMessage(),
                "response" => $e->response ? $e->response->body() : null
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to send message to Discord: " . $e->getMessage()
            ], 500);
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

        $webhookUrl = env('DISCORD_WEBHOOK_URL', '');
        if (empty($webhookUrl)) {
            Log::error('Discord Webhook URL is missing.');
            return false;
        }

        try {
            $uuid = (string) Str::uuid();
            $embeddedUrl = URL::to('/plans/'.$uuid.'/discounted');
            $settings->url_string = $uuid;
            $settings->embedded_url = $embeddedUrl;
            $settings->save();
            $encrypted = Crypt::encryptString($embeddedUrl);
            $redirectUrl = URL::to('/go?encrypted=' . urlencode($encrypted));

            $messageText = $settings->setting_value ?? 'No message set.';
            $fullMessage = $messageText . "\n" . $redirectUrl;

            $response = Http::post($webhookUrl, [
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