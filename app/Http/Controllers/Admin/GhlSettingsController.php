<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GhlSetting;
use App\Services\AccountCreationGHL;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GhlSettingsController extends Controller
{
    /**
     * Display GHL settings page
     */
    public function index()
    {
        $settings = GhlSetting::getCurrentSettings();
        return view('admin.ghl-settings.index', compact('settings'));
    }

    /**
     * Update GHL settings
     */
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'enabled' => 'nullable',
            'base_url' => 'required|url',
            'api_token' => 'nullable|string',
            'location_id' => 'nullable|string',
            'auth_type' => 'required|in:bearer,api_key',
            'api_version' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->only([
                'enabled', 'base_url', 'api_token', 
                'location_id', 'auth_type', 'api_version'
            ]);
            // https://rest.gohighlevel.com/v1 set default base URL
            $data['base_url'] = 'https://rest.gohighlevel.com/v1';

            // Convert enabled to boolean properly
            $data['enabled'] = $request->has('enabled') && ($request->enabled === '1' || $request->enabled === 1 || $request->enabled === true);

            $settings = GhlSetting::updateSettings($data, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'GHL settings updated successfully',
                'settings' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test GHL API connection
     */
    public function testConnection()
    {
        try {
            $ghlService = new AccountCreationGHL();
            $isConnected = $ghlService->testConnection();

            return response()->json([
                'success' => $isConnected,
                'message' => $isConnected 
                    ? 'GHL API connection successful' 
                    : 'GHL API connection failed'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get current settings as JSON
     */
    public function getSettings()
    {
        try {
            $settings = GhlSetting::getCurrentSettings();
            
            return response()->json([
                'success' => true,
                'settings' => $settings
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings: ' . $e->getMessage()
            ], 500);
        }
    }
}
