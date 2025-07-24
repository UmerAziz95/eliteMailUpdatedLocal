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
            'type' => 'required|string',
            'url' => 'required|url',
            'status' => 'nullable|in:on,off,true,false,1,0'
        ]);
        
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
            'message' => 'Slack settings saved successfully',
            'data' => $setting
        ]);
    }
    
    /**
     * Test slack webhook
     */
    public function testWebhook(Request $request)
    {
        $request->validate([
            'type' => 'required|string',
            'url' => 'required|url'
        ]);
        
        try {
            $response = Http::post($request->url, [
                'text' => 'Test message from ' . config('app.name') . ' - ' . SlackSettings::getTypes()[$request->type] ?? $request->type
            ]);
            
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test message sent successfully!'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test message. Response: ' . $response->body()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete slack setting
     */
    public function destroy($id)
    {
        $setting = SlackSettings::findOrFail($id);
        $setting->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Slack setting deleted successfully'
        ]);
    }
}
