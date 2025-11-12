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
        $systemConfigs = Configuration::getSystemConfigurations();
        $providerTypes = Configuration::getProviderTypes();
        return view('admin.config.index', compact('configurations', 'chargebeeConfigs', 'systemConfigs', 'providerTypes'));
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

    /**
     * Get System configurations
     */
    public function getSystemConfigurations()
    {
        $configurations = Configuration::getSystemConfigurations();
        
        return response()->json([
            'success' => true,
            'data' => $configurations
        ]);
    }

    /**
     * Update System configurations
     */
    public function updateSystemConfigurations(Request $request)
    {
        try {
            $request->validate([
                'SYSTEM_NAME' => 'required|string',
                'ADMIN_EMAIL' => 'required|email',
                'SUPPORT_EMAIL' => 'required|email',
                'FOOTER_TEXT' => 'required|string',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $configs = [
                'SYSTEM_NAME' => $request->SYSTEM_NAME,
                'ADMIN_EMAIL' => $request->ADMIN_EMAIL,
                'SUPPORT_EMAIL' => $request->SUPPORT_EMAIL,
                'FOOTER_TEXT' => $request->FOOTER_TEXT,
            ];

            // Handle logo upload
            if ($request->hasFile('logo')) {
                $image = $request->file('logo');
                $imageName = time() . '_' . $image->getClientOriginalName();
                $image->move(public_path('storage/system'), $imageName);
                $configs['SYSTEM_LOGO'] = 'storage/system/' . $imageName;
            }else{
                // If no logo uploaded, check if we need to remove existing logo
                if ($request->input('remove_logo') == '1') {
                    $configs['SYSTEM_LOGO'] = '';
                }
            }

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
                'message' => 'System configuration updated successfully'
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
                'message' => 'Failed to update System configuration: ' . $e->getMessage()
            ], 500);
        }
    }

}

