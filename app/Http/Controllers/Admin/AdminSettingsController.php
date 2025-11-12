<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use Illuminate\Http\Request;

class AdminSettingsController extends Controller
{
    //


    public function index()
    {
        // Logic to display admin settings page
        return view('admin.settings.index');
    }

    public function sysConfing(Request $request){
        $configurations = Configuration::getPanelConfigurations();
        $chargebeeConfigs = Configuration::getChargebeeConfigurations();
        $providerTypes = Configuration::getProviderTypes();
        return view('admin.config.index', compact('configurations', 'chargebeeConfigs', 'providerTypes'));
    }

    /**
     * Get panel configurations
     */
    public function getPanelConfigurations()
    {
        $configurations = Configuration::getPanelConfigurations();
        
        return response()->json([
            'success' => true,
            'data' => $configurations
        ]);
    }

    /**
     * Update a configuration value
     */
    public function updateConfiguration(Request $request)
    {
        try {
            $request->validate([
                'key' => 'required|string',
                'value' => 'required',
                'type' => 'nullable|string|in:string,number,boolean,json,select',
                'description' => 'nullable|string'
            ]);

            $type = $request->type ?? 'string';
            
            $config = Configuration::updateOrCreate(
                ['key' => $request->key],
                [
                    'value' => $request->value,
                    'type' => $type,
                    'description' => $request->description
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Configuration updated successfully',
                'data' => $config
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update configuration: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Chargebee configurations
     */
    public function getChargebeeConfigurations()
    {
        $configurations = Configuration::getChargebeeConfigurations();
        
        return response()->json([
            'success' => true,
            'data' => $configurations
        ]);
    }

    /**
     * Update Chargebee configurations
     */
    public function updateChargebeeConfigurations(Request $request)
    {
        try {
            $request->validate([
                'CHARGEBEE_PUBLISHABLE_API_KEY' => 'required|string',
                'CHARGEBEE_SITE' => 'required|string',
                'CHARGEBEE_API_KEY' => 'required|string',
            ]);

            $configs = [
                'CHARGEBEE_PUBLISHABLE_API_KEY' => $request->CHARGEBEE_PUBLISHABLE_API_KEY,
                'CHARGEBEE_SITE' => $request->CHARGEBEE_SITE,
                'CHARGEBEE_API_KEY' => $request->CHARGEBEE_API_KEY,
            ];

            foreach ($configs as $key => $value) {
                Configuration::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $value,
                        'type' => 'string'
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Chargebee configuration updated successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update Chargebee configuration: ' . $e->getMessage()
            ], 500);
        }
    }

}

