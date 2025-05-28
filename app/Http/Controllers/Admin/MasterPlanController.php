<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterPlan;
use App\Models\Plan;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class MasterPlanController extends Controller
{    /**
     * Display the master plan with volume items
     */
    public function show()
    {
        $masterPlan = MasterPlan::getSingle();
        
        if (!$masterPlan) {
            return response()->json(null);
        }
        
        // Load volume items with features to match the format used in store method
        $masterPlan->load('volumeItems.features');
        
        // Format the response to match frontend expectations (same as store method)
        $formattedData = [
            'id' => $masterPlan->id,
            'external_name' => $masterPlan->external_name,
            'internal_name' => $masterPlan->internal_name,
            'description' => $masterPlan->description,
            'chargebee_plan_id' => $masterPlan->chargebee_plan_id,
            'volume_items' => $masterPlan->volumeItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'min_inbox' => $item->min_inbox,
                    'max_inbox' => $item->max_inbox,
                    'price' => $item->price,
                    'duration' => $item->duration,
                    'features' => $item->features->pluck('id')->toArray(),
                    'feature_values' => $item->features->pluck('pivot.value')->toArray()
                ];
            })->toArray()
        ];
        
        return response()->json($formattedData);
    }    /**
     * Create or update the master plan with volume items
     */
    public function store(Request $request)
    {        $request->validate([
            'external_name' => 'required|string|max:255',
            'internal_name' => 'required|string|max:255|regex:/^[a-z0-9_]+$/',
            'description' => 'required|string|max:1000',
            'volume_items' => 'required|array|min:1',
            'volume_items.*.min_inbox' => 'required|integer|min:0',
            'volume_items.*.max_inbox' => 'required|integer|min:0',
            'volume_items.*.price' => 'required|numeric|min:0',
            'volume_items.*.features' => 'sometimes|array',
            'volume_items.*.features.*' => 'integer|exists:features,id',
            'volume_items.*.feature_values' => 'sometimes|array',
            'volume_items.*.feature_values.*' => 'nullable|string|max:255'
        ], [
            'internal_name.regex' => 'Internal name can only contain lowercase letters, numbers, and underscores.',
            'volume_items.required' => 'At least one volume item is required.',
            'volume_items.*.min_inbox.required' => 'Min inbox is required for all volume items.',
            'volume_items.*.min_inbox.integer' => 'Min inbox must be a valid integer.',
            'volume_items.*.max_inbox.required' => 'Max inbox is required for all volume items.',
            'volume_items.*.max_inbox.integer' => 'Max inbox must be a valid integer.',
            'volume_items.*.price.required' => 'Price is required for all volume items.',
            'volume_items.*.price.numeric' => 'Price must be a valid number.',
            'volume_items.*.features.*.exists' => 'Selected feature does not exist.'
        ]);        // Additional validation and cleaning of volume items data
        $volumeItems = collect($request->volume_items)->map(function ($item) {
            return [
                'name' => trim($item['name'] ?? ''),
                'description' => trim($item['description'] ?? ''),
                'min_inbox' => is_numeric($item['min_inbox']) ? (int)$item['min_inbox'] : 0,
                'max_inbox' => is_numeric($item['max_inbox']) ? (int)$item['max_inbox'] : 0,
                'price' => is_numeric($item['price']) ? (float)$item['price'] : 0,
                'duration' => $item['duration'] ?? 'monthly',
                'features' => $item['features'] ?? [],
                'feature_values' => $item['feature_values'] ?? []
            ];
        })->toArray();

        // Custom validation for range logic
        foreach ($volumeItems as $index => $item) {
            // Check if min_inbox > max_inbox (when max_inbox is not 0 for unlimited)
            if ($item['max_inbox'] !== 0 && $item['min_inbox'] > $item['max_inbox']) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid range in tier " . ($index + 1) . ": Min inboxes ({$item['min_inbox']}) cannot be greater than max inboxes ({$item['max_inbox']}). Set max to 0 for unlimited or adjust the values."
                ], 422);
            }
            
            // Check for negative values
            if ($item['min_inbox'] < 0 || $item['max_inbox'] < 0 || $item['price'] < 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Negative values not allowed in tier " . ($index + 1) . "."
                ], 422);
            }
            
            // Check for empty name
            if (empty(trim($item['name']))) {
                return response()->json([
                    'success' => false,
                    'message' => "Tier " . ($index + 1) . " name is required."
                ], 422);
            }
        }

        // Sort items by min_inbox for range validation
        $sortedItems = collect($volumeItems)->sortBy('min_inbox')->values()->toArray();
        
        // Validate for overlapping ranges
        for ($i = 0; $i < count($sortedItems) - 1; $i++) {
            $current = $sortedItems[$i];
            $next = $sortedItems[$i + 1];
            
            // If current tier has unlimited (max_inbox = 0) and it's not the last tier
            if ($current['max_inbox'] === 0 && $i < count($sortedItems) - 1) {
                return response()->json([
                    'success' => false,
                    'message' => "Tier with unlimited inboxes (max_inbox = 0) can only be the last tier."
                ], 422);
            }
            
            // Check for overlapping ranges
            if ($current['max_inbox'] !== 0 && $next['min_inbox'] <= $current['max_inbox']) {
                return response()->json([
                    'success' => false,
                    'message' => "Overlapping ranges detected between tiers. Tier ending at {$current['max_inbox']} overlaps with tier starting at {$next['min_inbox']}."
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $masterPlan = MasterPlan::getSingle();
            
            if ($masterPlan) {
                // Update existing master plan
                $masterPlan->update([
                    'external_name' => $request->external_name,
                    'internal_name' => $request->internal_name,
                    'description' => $request->description,
                ]);
                
                // Delete existing volume items
                $masterPlan->volumeItems()->delete();
                
                $message = 'Master plan updated successfully';
            } else {
                // Create new master plan
                $masterPlan = MasterPlan::create([
                    'external_name' => $request->external_name,
                    'internal_name' => $request->internal_name,
                    'description' => $request->description,
                ]);
                
                $message = 'Master plan created successfully';
            }

            // Create volume items in plans table
            foreach ($volumeItems as $volumeItem) {
                $plan = Plan::create([
                    'master_plan_id' => $masterPlan->id,
                    'name' => $request->external_name . ' (' . $volumeItem['min_inbox'] . '-' . ($volumeItem['max_inbox'] == 0 ? '∞' : $volumeItem['max_inbox']) . ' inboxes)',
                    'description' => 'Volume pricing tier for ' . $volumeItem['min_inbox'] . '-' . ($volumeItem['max_inbox'] == 0 ? 'unlimited' : $volumeItem['max_inbox']) . ' inboxes',
                    'price' => $volumeItem['price'],
                    'duration' => 'monthly',
                    'min_inbox' => $volumeItem['min_inbox'],
                    'max_inbox' => $volumeItem['max_inbox'],
                    'is_active' => true,
                ]);                // Attach features to this volume tier if any
                if (!empty($volumeItem['features'])) {
                    $featureData = [];
                    $featureValues = $volumeItem['feature_values'] ?? [];
                    
                    foreach ($volumeItem['features'] as $index => $featureId) {
                        $featureData[$featureId] = [
                            'value' => $featureValues[$index] ?? ''
                        ];
                    }
                    
                    $plan->features()->attach($featureData);
                }
            }            // Create or update on Chargebee
            $chargebeeSuccess = $this->syncWithChargebee($masterPlan, $volumeItems);
            
            if (!$chargebeeSuccess) {
                // Log warning but don't fail the operation
                Log::warning('Master plan saved locally but Chargebee sync failed', [
                    'master_plan_id' => $masterPlan->id
                ]);
                $message .= ' (Note: Chargebee synchronization failed, please try again)';
            } else {
                $message .= ' and synchronized with Chargebee successfully';
            }

            DB::commit();

            // Reload with volume items and format response
            $masterPlan->load('volumeItems.features');
            
            // Format the response to match frontend expectations
            $formattedData = [
                'id' => $masterPlan->id,
                'external_name' => $masterPlan->external_name,
                'internal_name' => $masterPlan->internal_name,
                'description' => $masterPlan->description,
                'chargebee_plan_id' => $masterPlan->chargebee_plan_id,
                'volume_items' => $masterPlan->volumeItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'min_inbox' => $item->min_inbox,
                        'max_inbox' => $item->max_inbox,
                        'price' => $item->price,
                        'duration' => $item->duration,
                        'features' => $item->features->pluck('id')->toArray(),
                        'feature_values' => $item->features->pluck('pivot.value')->toArray()
                    ];
                })->toArray()
            ];

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $formattedData
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Master plan save error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }    /**
     * Sync plan with Chargebee (create or update)
     */
    private function syncWithChargebee($masterPlan, $volumeItems)
    {
        try {
            // Check if this master plan already has a ChargeBee plan ID
            if ($masterPlan->chargebee_plan_id) {
                // Update existing plan
                return $this->updateChargebeePlan($masterPlan, $volumeItems);
            } else {
                // Create new plan
                return $this->createChargebeePlan($masterPlan, $volumeItems);
            }
        } catch (\Exception $e) {
            Log::error('ChargeBee sync error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update existing plan on Chargebee
     */
    private function updateChargebeePlan($masterPlan, $volumeItems)
    {
        try {
            Log::info('Updating existing master plan on ChargeBee: ' . $masterPlan->chargebee_plan_id);
            
            // Update the item in ChargeBee
            $itemResult = \ChargeBee\ChargeBee\Models\Item::update($masterPlan->chargebee_plan_id, [
                'name' => $masterPlan->external_name,
                'description' => $masterPlan->description,
                'status' => 'active'
            ]);

            if (!$itemResult || !$itemResult->item()) {
                throw new \Exception('Failed to update item in ChargeBee');
            }

            // Find and update the existing price with new volume tiers
            $priceId = $masterPlan->chargebee_plan_id . '_price';
            
            // Prepare new tiers for volume pricing
            $tiers = [];
            foreach ($volumeItems as $index => $item) {
                $tier = [
                    'starting_unit' => (int)$item['min_inbox'],
                    'price' => (int)($item['price'] * 100) // Convert to cents
                ];
                
                // For unlimited tiers (max_inbox = 0), don't include ending_unit
                // For limited tiers, include ending_unit as integer
                if ($item['max_inbox'] > 0) {
                    $tier['ending_unit'] = (int)$item['max_inbox'];
                }
                
                $tiers[] = $tier;
            }

            // Update the price with new tiers
            $priceResult = \ChargeBee\ChargeBee\Models\ItemPrice::update($priceId, [
                'name' => $masterPlan->external_name . ' Volume Price',
                'tiers' => $tiers,
                'status' => 'active'
            ]);

            if ($priceResult && $priceResult->itemPrice()) {
                // Update volume items (plans) with the updated ChargeBee price ID
                $masterPlan->volumeItems()->update([
                    'chargebee_plan_id' => $priceResult->itemPrice()->id,
                    'is_chargebee_synced' => true
                ]);
                
                Log::info('Master plan updated successfully on Chargebee: ' . $masterPlan->chargebee_plan_id);
                Log::info('Volume items updated with ChargeBee price ID: ' . $priceResult->itemPrice()->id);
                return true;
            }

            throw new \Exception('Failed to update price in ChargeBee');

        } catch (\ChargeBee\ChargeBee\Exceptions\APIError $e) {
            Log::error('ChargeBee API Error (update): ' . $e->getMessage());
            throw new \Exception('ChargeBee API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Chargebee plan update error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create plan on Chargebee with volume pricing and tiers
     */
    private function createChargebeePlan($masterPlan, $volumeItems)
    {        try {
            // Generate unique ID for the master plan - include random string to ensure uniqueness
            $uniqueId = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $masterPlan->external_name)) . '_' . time() . '_' . uniqid();

            // Create an item in ChargeBee
            $result = \ChargeBee\ChargeBee\Models\Item::create([
                'id' => $uniqueId,
                'name' => $masterPlan->external_name,
                'description' => $masterPlan->description,
                'type' => 'plan',
                'enabled_in_portal' => true,
                'item_family_id' => 'cbdemo_omnisupport-solutions',
                'status' => 'active'
            ]);

            if ($result && $result->item()) {                // Prepare tiers for volume pricing
                $tiers = [];
                foreach ($volumeItems as $index => $item) {
                    $tier = [
                        'starting_unit' => (int)$item['min_inbox'],
                        'price' => (int)($item['price'] * 100) // Convert to cents
                    ];
                    
                    // For unlimited tiers (max_inbox = 0), don't include ending_unit
                    // For limited tiers, include ending_unit as integer
                    if ($item['max_inbox'] > 0) {
                        $tier['ending_unit'] = (int)$item['max_inbox'];
                    }
                    
                    $tiers[] = $tier;
                }                // Create item price for the master plan with volume pricing and tiers
                $priceParams = [
                    'id' => $uniqueId . '_price',
                    'name' => $masterPlan->external_name . ' Volume Price',
                    'item_id' => $result->item()->id,
                    'pricing_model' => 'volume',
                    'period_unit' => 'month',
                    'period' => 1,
                    'currency_code' => 'USD',
                    'status' => 'active',
                    'channel' => 'web',
                    'tiers' => $tiers
                ];

                $priceResult = \ChargeBee\ChargeBee\Models\ItemPrice::create($priceParams);                if ($priceResult && $priceResult->itemPrice()) {
                    // Update master plan with ChargeBee item ID
                    $masterPlan->update(['chargebee_plan_id' => $result->item()->id]);
                    
                    // Update volume items (plans) with ChargeBee price ID  
                    $priceId = $priceResult->itemPrice()->id;
                    $masterPlan->volumeItems()->update([
                        'chargebee_plan_id' => $priceId,
                        'is_chargebee_synced' => true
                    ]);
                    
                    Log::info('Master plan created successfully on Chargebee: ' . $result->item()->id);
                    Log::info('Volume items updated with ChargeBee price ID: ' . $priceId);
                    return true;
                }
            }

            throw new \Exception('Failed to create master plan on Chargebee');

        } catch (\ChargeBee\ChargeBee\Exceptions\APIError $e) {
            Log::error('ChargeBee API Error: ' . $e->getMessage());
            throw new \Exception('ChargeBee API Error: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Chargebee plan creation error: ' . $e->getMessage());
            throw $e;
        }
    }    /**
     * Get master plan data with volume items for editing
     */
    public function data()
    {
        try {
            $masterPlan = MasterPlan::getSingle();
            
            if (!$masterPlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No master plan found'
                ]);
            }            $volumeItems = $masterPlan->volumeItems()->with('features')->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'min_inbox' => $item->min_inbox,
                    'max_inbox' => $item->max_inbox,
                    'price' => $item->price,
                    'duration' => $item->duration,
                    'features' => $item->features->pluck('id')->toArray(),
                    'feature_values' => $item->features->pluck('pivot.value')->toArray(),
                    'chargebee_synced' => !empty($item->chargebee_plan_id),
                    'chargebee_plan_id' => $item->chargebee_plan_id
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $masterPlan->id,
                    'external_name' => $masterPlan->external_name,
                    'internal_name' => $masterPlan->internal_name,
                    'description' => $masterPlan->description,
                    'chargebee_plan_id' => $masterPlan->chargebee_plan_id,
                    'volume_items' => $volumeItems
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Master plan data error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading master plan data'
            ], 500);
        }
    }    /**
     * Check if master plan exists
     */
    public function exists()
    {
        return response()->json(['exists' => MasterPlan::exists()]);
    }

    /**
     * Handle Chargebee webhook for master plan and volume tier updates
     */
    public function handleChargebeeWebhook(Request $request)
    {
        try {
            $eventData = $request->all();
            $eventType = $eventData['event_type'] ?? '';

            Log::info('Chargebee webhook received', [
                'event_type' => $eventType,
                'event_id' => $eventData['id'] ?? null
            ]);

            switch ($eventType) {
                case 'item_price_updated':
                case 'item_price_created':
                    $this->handleVolumeTierUpdate($eventData);
                    break;
                case 'item_updated':
                case 'item_created':
                    $this->handleMasterPlanUpdate($eventData);
                    break;
                default:
                    Log::info('Unhandled webhook event type: ' . $eventType);
            }

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            Log::error('Chargebee webhook processing error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle master plan updates from Chargebee
     */
    private function handleMasterPlanUpdate($eventData)
    {
        $item = $eventData['content']['item'] ?? null;
        if (!$item) return;

        $chargebeeId = $item['id'];
        $masterPlan = MasterPlan::where('chargebee_plan_id', $chargebeeId)->first();
        
        if ($masterPlan) {
            $masterPlan->update([
                'external_name' => $item['name'] ?? $masterPlan->external_name,
                'description' => $item['description'] ?? $masterPlan->description
            ]);
            
            Log::info('Master plan updated from Chargebee', ['plan_id' => $masterPlan->id]);
        }
    }

    /**
     * Handle volume tier updates from Chargebee
     */
    private function handleVolumeTierUpdate($eventData)
    {
        $itemPrice = $eventData['content']['item_price'] ?? null;
        if (!$itemPrice) return;

        $chargebeePriceId = $itemPrice['id'];
        $volumeItems = Plan::where('chargebee_plan_id', $chargebeePriceId)->get();
        
        foreach ($volumeItems as $item) {
            // Update pricing information if needed
            $tiers = $itemPrice['tiers'] ?? [];
            // Process tier updates based on Chargebee data
            
            Log::info('Volume tier updated from Chargebee', ['item_id' => $item->id]);
        }
    }

    /**
     * Force sync master plan with Chargebee
     */
    public function forceSync()
    {
        try {
            $masterPlan = MasterPlan::getSingle();
            if (!$masterPlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No master plan found to sync'
                ]);
            }

            $volumeItems = $masterPlan->volumeItems->map(function($item) {
                return [
                    'name' => $item->name,
                    'description' => $item->description,
                    'min_inbox' => $item->min_inbox,
                    'max_inbox' => $item->max_inbox,
                    'price' => $item->price,
                    'duration' => $item->duration,
                    'features' => $item->features->pluck('id')->toArray(),
                    'feature_values' => $item->features->pluck('pivot.value')->toArray()
                ];
            })->toArray();

            $syncSuccess = $this->syncWithChargebee($masterPlan, $volumeItems);

            return response()->json([
                'success' => $syncSuccess,
                'message' => $syncSuccess ? 
                    'Master plan synchronized with Chargebee successfully' : 
                    'Failed to synchronize with Chargebee'
            ]);

        } catch (\Exception $e) {
            Log::error('Force sync error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
