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
        
        if ($masterPlan) {
            $masterPlan->load('volumeItems');
        }
        
        return response()->json($masterPlan);
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
            }// Create or update on Chargebee
            $this->syncWithChargebee($masterPlan, $volumeItems);

            DB::commit();

            // Reload with volume items
            $masterPlan->load('volumeItems');

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $masterPlan
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
            // For updates, we'll just log and skip ChargeBee update for now
            // In a full implementation, you'd update the existing item and price
            Log::info('Updating existing master plan on ChargeBee: ' . $masterPlan->chargebee_plan_id);
            
            // TODO: Implement ChargeBee item and price update logic here
            // For now, just skip to avoid duplicate errors
            
            return true;

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
                    $masterPlan->volumeItems()->update(['chargebee_plan_id' => $priceId]);
                    
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
                    'feature_values' => $item->features->pluck('pivot.value')->toArray()
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
}
