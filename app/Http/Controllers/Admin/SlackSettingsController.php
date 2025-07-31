<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SlackSettings;
use Illuminate\Support\Facades\Http;

class SlackSettingsController extends Controller
{
    /**
     * Display the slack settings page
     */
    public function index()
    {
        $types = SlackSettings::getTypes();
        
        // Get existing settings for each type
        $settings = [];
        foreach ($types as $key => $label) {
            $setting = SlackSettings::where('type', $key)->first();
            $settings[$key] = $setting;
        }
        
        return view('admin.slack.index', compact('types', 'settings'));
    }
    
    /**
     * Store or update slack settings
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(SlackSettings::getTypes())),
            'url' => 'required|url|regex:/^https:\/\/hooks\.slack\.com\/services\/.+/',
            'status' => 'nullable|in:on,off,true,false,1,0'
        ], [
            'url.regex' => 'The webhook URL must be a valid Slack webhook URL starting with https://hooks.slack.com/services/',
            'type.in' => 'Invalid webhook type selected.'
        ]);
        
        try {
            // Convert status to boolean
            $status = false;
            if ($request->has('status')) {
                $statusValue = $request->status;
                $status = in_array($statusValue, ['on', 'true', '1', true], true);
            }
            
            $setting = SlackSettings::updateOrCreate(
                ['type' => $request->type],
                [
                    'url' => $request->url,
                    'status' => $status
                ]
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Slack settings saved successfully!',
                'data' => $setting->fresh()
            ]);
        } catch (\Exception $e) {
            \Log::error('Slack settings save error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to save settings. Please try again.'
            ], 500);
        }
    }
    
    /**
     * Test slack webhook
     */
    public function testWebhook(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(SlackSettings::getTypes())),
            'url' => 'required|url|regex:/^https:\/\/hooks\.slack\.com\/services\/.+/'
        ], [
            'url.regex' => 'The webhook URL must be a valid Slack webhook URL starting with https://hooks.slack.com/services/',
            'type.in' => 'Invalid webhook type selected.'
        ]);
        
        try {
            $typeLabel = SlackSettings::getTypes()[$request->type] ?? ucfirst(str_replace('-', ' ', $request->type));
            $appName = config('app.name', 'Application');
            
            $message = [
                'text' => "🔔 Test notification from {$appName}",
                'attachments' => [
                    [
                        'color' => '#28a745',
                        'fields' => [
                            [
                                'title' => 'Notification Type',
                                'value' => $typeLabel,
                                'short' => true
                            ],
                            [
                                'title' => 'Status',
                                'value' => 'Test Successful ✅',
                                'short' => true
                            ],
                            [
                                'title' => 'Timestamp',
                                'value' => now()->format('Y-m-d H:i:s T'),
                                'short' => false
                            ]
                        ],
                        'footer' => $appName . ' Slack Integration',
                        'ts' => time()
                    ]
                ]
            ];
            
            $response = Http::timeout(10)->post($request->url, $message);
            
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test message sent successfully! Check your Slack channel.'
                ]);
            } else {
                \Log::warning('Slack webhook test failed', [
                    'url' => $request->url,
                    'type' => $request->type,
                    'response_status' => $response->status(),
                    'response_body' => $response->body()
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test message. HTTP Status: ' . $response->status()
                ], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Slack webhook test error: ' . $e->getMessage(), [
                'url' => $request->url,
                'type' => $request->type
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Connection error: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Delete slack setting
     */
    public function destroy($id)
    {
        try {
            $setting = SlackSettings::findOrFail($id);
            $typeLabel = SlackSettings::getTypes()[$setting->type] ?? $setting->type;
            
            $setting->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Slack webhook for '{$typeLabel}' has been deleted successfully!"
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Webhook setting not found.'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Slack settings delete error: ' . $e->getMessage(), ['id' => $id]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete webhook setting. Please try again.'
            ], 500);
        }
    }
}
