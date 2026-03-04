<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\User;
use App\Models\SmtpProviderSplit;
use Illuminate\Http\Request;
use Carbon\Carbon;

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
        $poolConfigs = Configuration::getPoolConfigurations();
        $providerTypes = Configuration::getProviderTypes();
        $providerSplits = SmtpProviderSplit::get();
        
        // Get backups from last 30 days
        $backups = $this->getBackupFiles();
        
        return view('admin.config.index', compact('configurations', 'chargebeeConfigs', 'systemConfigs', 'poolConfigs', 'providerTypes', 'backups', 'providerSplits'));
    }

    /**
     * Get backup files from storage/app/backup directory (last 30 days)
     */
    private function getBackupFiles()
    {
        $backupPath = storage_path('app/backup');
        $backups = [];
        
        if (!file_exists($backupPath)) {
            return $backups;
        }
        
        $files = \File::files($backupPath);
        $thirtyDaysAgo = now()->subDays(30)->timestamp;
        
        foreach ($files as $file) {
            $fileTime = $file->getMTime();
            
            // Only include files from last 30 days
            if ($fileTime >= $thirtyDaysAgo) {
                $backups[] = [
                    'name' => $file->getFilename(),
                    'path' => $file->getPathname(),
                    'size' => $this->formatBytes($file->getSize()),
                    'date' => date('d M Y, h:i A', $fileTime),
                    'timestamp' => $fileTime
                ];
            }
        }
        
        // Sort by timestamp descending (newest first)
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $backups;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Download backup file
     */
    public function downloadBackup(Request $request)
    {
        $filename = $request->input('file');
        $filePath = storage_path('app/backup/' . $filename);
        
        if (!file_exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'Backup file not found'
            ], 404);
        }
        
        return response()->download($filePath);
    }

    /**
     * Delete backup file
     */
    public function deleteBackup(Request $request)
    {
        try {
            $filename = $request->input('file');
            $filePath = storage_path('app/backup/' . $filename);
            
            if (!file_exists($filePath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], 404);
            }
            
            unlink($filePath);
            
            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List backup files with optional filters (AJAX for DataTables)
     * Filters:
     * - start_date (Y-m-d)
     * - end_date (Y-m-d)
     * - size_min (MB)
     * - size_max (MB)
     */
    public function listBackups(Request $request)
    {
        $backupPath = storage_path('app/backup');
        if (!file_exists($backupPath)) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $sizeMinMb = $request->input('size_min');
        $sizeMaxMb = $request->input('size_max');

        $startTs = null;
        $endTs = null;

        try {
            if ($startDate) {
                $startTs = Carbon::parse($startDate)->startOfDay()->timestamp;
            }
        } catch (\Throwable $e) { /* ignore parse error */ }

        try {
            if ($endDate) {
                $endTs = Carbon::parse($endDate)->endOfDay()->timestamp;
            }
        } catch (\Throwable $e) { /* ignore parse error */ }

        // Defaults: last 30 days
        if (!$startTs && !$endTs) {
            $startTs = now()->subDays(30)->startOfDay()->timestamp;
            $endTs = now()->endOfDay()->timestamp;
        } else {
            // Fill missing bound with very wide range
            if (!$startTs) { $startTs = 0; }
            if (!$endTs) { $endTs = PHP_INT_MAX; }
        }

        $minBytes = is_numeric($sizeMinMb) ? (int)($sizeMinMb * 1024 * 1024) : null;
        $maxBytes = is_numeric($sizeMaxMb) ? (int)($sizeMaxMb * 1024 * 1024) : null;

        $files = \File::files($backupPath);
        $rows = [];
        foreach ($files as $file) {
            $fileTime = $file->getMTime();
            $sizeBytes = $file->getSize();

            if ($fileTime < $startTs || $fileTime > $endTs) {
                continue;
            }
            if ($minBytes !== null && $sizeBytes < $minBytes) {
                continue;
            }
            if ($maxBytes !== null && $sizeBytes > $maxBytes) {
                continue;
            }

            $rows[] = [
                'name' => $file->getFilename(),
                'size_bytes' => $sizeBytes,
                'size_human' => $this->formatBytes($sizeBytes),
                'date' => date('d M Y, h:i A', $fileTime),
                'timestamp' => $fileTime,
            ];
        }

        // Sort newest first
        usort($rows, function($a, $b) { return $b['timestamp'] <=> $a['timestamp']; });

        return response()->json([
            'success' => true,
            'data' => $rows,
        ]);
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
     * Get pool configurations
     */
    public function getPoolConfigurations()
    {
        $configurations = Configuration::getPoolConfigurations();

        return response()->json([
            'success' => true,
            'data' => $configurations
        ]);
    }

    /**
     * Get configuration history by key
     */
    public function getConfigurationHistory(string $key)
    {
        $config = Configuration::where('key', $key)->first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Configuration not found.'
            ], 404);
        }

        $rawHistory = $config->last_change;
        if (is_string($rawHistory)) {
            $decoded = json_decode($rawHistory, true);
            $rawHistory = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        $history = collect($rawHistory ?? []);

        if ($history->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        $userIds = $history->pluck('user_id')->filter()->unique()->values();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $history = $history->map(function ($entry) use ($users) {
            $userId = $entry['user_id'] ?? null;
            $user = $userId ? $users->get($userId) : null;

            return [
                'previous_value' => $entry['previous_value'] ?? null,
                'previous_type' => $entry['previous_type'] ?? null,
                'new_value' => $entry['new_value'] ?? null,
                'new_type' => $entry['new_type'] ?? null,
                'previous_description' => $entry['previous_description'] ?? null,
                'new_description' => $entry['new_description'] ?? null,
                'user_id' => $userId,
                'user_name' => $user?->name,
                'changed_at' => $entry['changed_at'] ?? null,
            ];
        })->sortByDesc('changed_at')->values();

        return response()->json([
            'success' => true,
            'data' => $history
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
     * Update pool configurations
     */
    public function updatePoolConfigurations(Request $request)
    {
        try {
            $allowedKeys = ['POOL_WARMING_PERIOD', 'CANCELLATION_POOL_WARMING_PERIOD'];
            $key = $request->input('key', $allowedKeys[0]);

            if (!in_array($key, $allowedKeys, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid pool configuration key.'
                ], 422);
            }

            $value = $request->has('value')
                ? $request->input('value')
                : $request->input('POOL_WARMING_PERIOD');

            $validator = validator(
                ['value' => $value],
                ['value' => 'required|integer|min:0']
            );

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $type = $request->input('type', 'number');

            Configuration::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'type' => $type,
                    'description' => $request->input('description')
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Pool configuration updated successfully'
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
                'message' => 'Failed to update pool configuration: ' . $e->getMessage()
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

    /**
     * Get SMTP Provider Split configurations
     */
    public function getProviderSplits()
    {
        $providers = SmtpProviderSplit::orderBy('priority', 'asc')
            ->orderBy('name', 'asc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $providers
        ]);
    }

    /**
     * Update SMTP Provider Split configurations
     */
    public function updateProviderSplits(Request $request)
    {
        try {
            $request->validate([
                'providers' => 'required|array',
                'providers.*.id' => 'required|exists:smtp_provider_splits,id',
                'providers.*.api_endpoint' => 'nullable|string|max:500',
                'providers.*.email' => 'required|email|max:255',
                'providers.*.password' => 'required|string|max:255',
                'providers.*.split_percentage' => 'required|numeric|min:0|max:100',
                'providers.*.priority' => 'required|integer|min:0',
                'providers.*.is_active' => 'nullable|boolean',
            ], [
                'providers.*.priority.required' => 'Priority is required.',
                'providers.*.priority.integer' => 'Priority must be a valid number.',
                'providers.*.priority.min' => 'Priority must be 0 or greater.',
            ]);

            // Calculate total percentage for ACTIVE providers only
            $activeTotalPercentage = 0;
            $priorities = [];
            foreach ($request->providers as $providerData) {
                $isActive = isset($providerData['is_active']) && $providerData['is_active'];
                if ($isActive) {
                    $activeTotalPercentage += floatval($providerData['split_percentage'] ?? 0);
                }
                
                // Collect priorities for uniqueness check
                $priority = isset($providerData['priority']) && $providerData['priority'] !== '' 
                    ? (int) $providerData['priority'] 
                    : null;
                
                if ($priority !== null) {
                    $priorities[] = $priority;
                }
            }

            // Validate priorities are unique
            if (count($priorities) !== count(array_unique($priorities))) {
                $duplicates = array_diff_assoc($priorities, array_unique($priorities));
                $duplicateValues = array_unique($duplicates);
                return response()->json([
                    'success' => false,
                    'message' => 'Priority values must be unique. Duplicate priority found: ' . implode(', ', $duplicateValues)
                ], 422);
            }

            // Validate total percentage equals 100 for active providers only
            if (abs($activeTotalPercentage - 100.00) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total split percentage for active providers must equal 100%. Current total: ' . number_format($activeTotalPercentage, 2) . '%'
                ], 422);
            }

            // Update each provider
            foreach ($request->providers as $providerData) {
                $provider = SmtpProviderSplit::findOrFail($providerData['id']);
                
                // Handle priority - convert to integer, default to 0 if empty or invalid
                $priority = isset($providerData['priority']) && $providerData['priority'] !== '' 
                    ? (int) $providerData['priority'] 
                    : ($provider->priority ?? 0);
                
                $provider->update([
                    'api_endpoint' => $providerData['api_endpoint'] ?? null,
                    'email' => $providerData['email'] ?? '',
                    'password' => $providerData['password'] ?? '',
                    'split_percentage' => $providerData['split_percentage'],
                    'priority' => $priority,
                    'is_active' => $providerData['is_active'] ?? $provider->is_active,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Provider split configuration updated successfully'
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
                'message' => 'Failed to update provider split configuration: ' . $e->getMessage()
            ], 500);
        }
    }

}
