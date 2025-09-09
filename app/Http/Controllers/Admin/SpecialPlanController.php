<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MasterPlan;
use App\Models\Plan;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class SpecialPlanController extends Controller
{
    /**
     * Display the special plans index view
     */
    public function index()
    {
        // Get individual special plans (is_discounted = 3)
        $plans = Plan::where('is_discounted', 3)->get();
        $features = Feature::all();
        
        // Get special master plans (is_discounted = 3) for the master plan section
        $masterPlans = MasterPlan::where('is_discounted', 3)->with(['volumeItems.features'])->get();
        
        // Get the most used plan (you can customize this logic based on your needs)
        // For now, we'll get the first plan as the most used, or you can add logic to determine the most popular
        $getMostlyUsed = $plans->first(); // Or add your own logic to determine most used plan
        
        return view('admin.pricing.special-plans', compact('plans', 'features', 'getMostlyUsed', 'masterPlans'));
    }

    /**
     * Get special plans with their features for AJAX calls
     */
    public function getPlansWithFeatures()
    {
        $plans = Plan::where('is_discounted', 3)->with('features')->get();
        $features = Feature::all();
        
        // Get special master plans (is_discounted = 3)
        $masterPlans = MasterPlan::where('is_discounted', 3)->with(['volumeItems.features'])->get();
        
        // Get the most used plan (same logic as in index method)
        $mostlyUsed = $plans->first(); // Or add your own logic to determine most used plan
        
        return response()->json([
            'plans' => $plans,
            'features' => $features,
            'mostlyUsed' => $mostlyUsed,
            'masterPlans' => $masterPlans
        ]);
    }

    /**
     * Store a new special plan (allows multiple special plans)
     */
    public function storeSpecialPlan(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|string',
            'min_inbox' => 'sometimes|integer|min:0',
            'max_inbox' => 'sometimes|integer|min:0',
            'features' => 'sometimes|array',
            'features.*' => 'integer|exists:features,id',
            'feature_values' => 'sometimes|array',
            'feature_values.*' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $plan = Plan::create([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'duration' => $request->duration,
                'min_inbox' => $request->min_inbox ?? 1,
                'max_inbox' => $request->max_inbox ?? 0,
                'is_discounted' => 3, // Special plan
                'is_active' => true
            ]);

            // Attach features if provided
            if ($request->has('features')) {
                $featureData = [];
                $featureValues = $request->feature_values ?? [];
                
                foreach ($request->features as $index => $featureId) {
                    $featureData[$featureId] = [
                        'value' => $featureValues[$index] ?? ''
                    ];
                }
                
                $plan->features()->attach($featureData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Special plan created successfully',
                'plan' => $plan->load('features')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Special plan creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating special plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a special plan
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:1000',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|string',
            'features' => 'sometimes|array',
            'features.*' => 'integer|exists:features,id',
            'feature_values' => 'sometimes|array',
            'feature_values.*' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $plan = Plan::where('id', $id)->where('is_discounted', 3)->firstOrFail();

            $plan->update([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'duration' => $request->duration
            ]);

            // Update features
            $plan->features()->detach();
            
            if ($request->has('features')) {
                $featureData = [];
                $featureValues = $request->feature_values ?? [];
                
                foreach ($request->features as $index => $featureId) {
                    $featureData[$featureId] = [
                        'value' => $featureValues[$index] ?? ''
                    ];
                }
                
                $plan->features()->attach($featureData);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Special plan updated successfully',
                'plan' => $plan->load('features')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Special plan update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating special plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a special plan
     */
    public function destroy($id)
    {
        try {
            $plan = Plan::where('id', $id)->where('is_discounted', 3)->firstOrFail();
            
            DB::beginTransaction();
            
            // Detach features first
            $plan->features()->detach();
            
            // Delete the plan
            $plan->delete();
            
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Special plan deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Special plan deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting special plan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the special master plans with volume items
     */
    public function show($id=null)
{
    
    if($id !==null){
        // Show specific special master plan
        $masterPlans = MasterPlan::with('volumeItems.features')
            ->where('id', $id)
            ->where('is_discounted', 3)
            ->get();
    }else{
        // Show all special master plans
        $masterPlans = MasterPlan::with('volumeItems.features')
            ->where('is_discounted', 3)
            ->get();
    }

    $formattedData = $masterPlans->map(function ($plan) {
        return [ 
            'id' => $plan->id,
            'external_name' => $plan->external_name,
            'internal_name' => $plan->internal_name,
            'description' => $plan->description,
            'chargebee_plan_id' => $plan->chargebee_plan_id,
            'is_discounted' => $plan->is_discounted,
            'volume_items' => $plan->volumeItems->map(function ($item) {
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
                    'tier_discount_type' => $item->tier_discount_type,
                    'tier_discount_value' => $item->tier_discount_value,
                    'actual_price_before_discount' => $item->actual_price_before_discount,
                    'is_discounted' => $item->is_discounted
                ];
            })->toArray()
        ];
    });

    return response()->json($formattedData);
}

   /**
     * Create or update the master plan with volume items
     */
    public function storeMasterPlan(Request $request)

        { 
            
// dd($request->all());
            $request->validate([
                'external_name' => 'required|string|max:255',
                // 'internal_name' => 'required|string|max:255|regex:/^[a-z0-9_]+$/',
                'description' => 'required|string|max:1000',
                'volume_items' => 'required|array|min:1',
                'volume_items.*.id' => 'sometimes|integer|exists:plans,id',
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
                'volume_items.*.id.exists' => 'Selected volume item does not exist.',
                'volume_items.*.min_inbox.required' => 'Min inbox is required for all volume items.',
                'volume_items.*.min_inbox.integer' => 'Min inbox must be a valid integer.',
                'volume_items.*.max_inbox.required' => 'Max inbox is required for all volume items.',
                'volume_items.*.max_inbox.integer' => 'Max inbox must be a valid integer.',
                'volume_items.*.price.required' => 'Price is required for all volume items.',
                'volume_items.*.price.numeric' => 'Price must be a valid number.',
                'volume_items.*.features.*.exists' => 'Selected feature does not exist.'
            ]);        
            // Additional validation and cleaning of volume items data
           $volumeItems = collect($request->volume_items)->map(function ($item) use ($request) {
            // Determine discount value based on mode
            $discountMode = 0; // Default non-discounted
            if ($request->discountMode === 'Discounted') {
                $discountMode = 1;
            } elseif ($request->discountMode === 'Special') {
                $discountMode = 3;
            }
            
            $volumeItem = [
                'name' => trim($item['name'] ?? ''),
                'description' => trim($item['description'] ?? ''),
                'min_inbox' => is_numeric($item['min_inbox']) ? (int)$item['min_inbox'] : 0,
                'max_inbox' => is_numeric($item['max_inbox']) ? (int)$item['max_inbox'] : 0,
                'price' => is_numeric($item['price']) ? (float)$item['price'] : 0,
                'duration' => $item['duration'] ?? 'monthly',
                'features' => $item['features'] ?? [],
                'feature_values' => $item['feature_values'] ?? [],
                'discountMode' => $discountMode
            ];

            // Apply discount fields based on discount mode
            if ($request->discountMode === 'Discounted') {
                $volumeItem['tier_discount_value'] = is_numeric($item['tier_discount_value']) ? (float)$item['tier_discount_value'] : null;
                $volumeItem['tier_discount_type'] = $item['tier_discount_type'] ?? null;
                $volumeItem['actual_price_before_discount'] = is_numeric($item['actual_price_before_discount']) ? (float)$item['actual_price_before_discount'] : null;
            } elseif ($request->discountMode === 'Special') {
                // Special plans can have their own discount logic if needed
                $volumeItem['tier_discount_value'] = is_numeric($item['tier_discount_value']) ? (float)$item['tier_discount_value'] : null;
                $volumeItem['tier_discount_type'] = $item['tier_discount_type'] ?? null;
                $volumeItem['actual_price_before_discount'] = is_numeric($item['actual_price_before_discount']) ? (float)$item['actual_price_before_discount'] : null;
            } else {
                $volumeItem['tier_discount_value'] =  null;
                $volumeItem['tier_discount_type'] =  null;
                $volumeItem['actual_price_before_discount'] =  null;
            }

            // Include ID if present (for existing items)
            if (!empty($item['id'])) {
                $volumeItem['id'] = (int)$item['id'];
            }

            return $volumeItem;
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
            
            // Validate for overlapping ranges and gaps
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
                
                // Check for gaps between consecutive tiers (ChargeBee requirement)
                if ($current['max_inbox'] !== 0 && $next['min_inbox'] > $current['max_inbox'] + 1) {
                return response()->json([
                    'success' => false,
                    'message' => "Gap detected between tiers. Tier ending at {$current['max_inbox']} has a gap before the next tier starting at {$next['min_inbox']}. ChargeBee requires continuous tier ranges. Next tier should start at " . ($current['max_inbox'] + 1) . "."
                ], 422);
                }
            }
            
            // Check if the last tier doesn't have unlimited (max_inbox = 0)
            if (!empty($sortedItems) && $sortedItems[count($sortedItems) - 1]['max_inbox'] !== 0) {
                return response()->json([
                'success' => false,
                'message' => "The last tier must have unlimited inboxes (max_inbox = 0) to handle all cases beyond the defined ranges."
                ], 422);
            }
            
            // Additional validation: First tier should start at 1 (ChargeBee requirement)
            if (!empty($sortedItems) && $sortedItems[0]['min_inbox'] !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => "First tier must start at 1 inbox (ChargeBee requirement). Current first tier starts at {$sortedItems[0]['min_inbox']}."
                ], 422);
            }

            try {
                DB::beginTransaction();

                // Check if this is creating a new plan (not updating an existing one)
                $isNewPlan = !$request->masterPlanId;
                $isDiscounted = $request->discountMode === 'Discounted';
                $isSpecial = $request->discountMode === 'Special';
                
                // For special plans, allow multiple master plans with is_discounted = 3
                // Restriction only applies to regular discounted (1) and non-discounted (0) plans
                if ($isNewPlan && ($request->discountMode !== 'Special')) {
                    $existingPlan = MasterPlan::where('is_discounted', $isDiscounted ? 1 : 0)->first();
                    
                    if ($existingPlan) {
                        $planType = $isDiscounted ? 'discounted' : 'non-discounted';
                        return response()->json([
                            'success' => false,
                            'message' => "A {$planType} master plan already exists. Only one {$planType} plan is allowed. Please edit the existing plan instead."
                        ], 422);
                    }
                }

                // dd($request->masterPlanId);
                $masterPlan = MasterPlan::find($request->masterPlanId);
                
                
                if ($masterPlan) {
                    // Update existing master plan
                    $discountValue = 0; // Default non-discounted
                    if ($request->discountMode === 'Discounted') {
                        $discountValue = 1;
                    } elseif ($request->discountMode === 'Special') {
                        $discountValue = 3;
                    }
                    
                    $masterPlan->update([
                        'external_name' => $request->external_name,
                        'internal_name' => $request->internal_name,
                        'description' => $request->description,
                        'is_discounted' => $discountValue
                    ]);
                    
                    // Update volume items instead of deleting and recreating
                    $this->updateVolumeItems($masterPlan, $volumeItems);
                    
                    $message = 'Master plan updated successfully';
                } else {
                    // dd("2");
                    // exit();
                    // Create new master plan
                    $discountValue = 0; // Default non-discounted
                    if ($request->discountMode === 'Discounted') {
                        $discountValue = 1;
                    } elseif ($request->discountMode === 'Special') {
                        $discountValue = 3;
                    }
                    
                    $masterPlan = MasterPlan::create([
                        'external_name' => $request->external_name,
                        'internal_name' => $request->internal_name,
                        'description' => $request->description,
                        'is_discounted' => $discountValue
                    ]);
                    
                    // Create volume items in plans table
                    $this->createVolumeItems($masterPlan, $volumeItems);
                    
                    $message = 'Master plan created successfully';
                }            
                // Create or update on Chargebee
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

            // Load only special master plans with their volume items and features
        $allMasterPlans = MasterPlan::with(['volumeItems.features'])->where('is_discounted', 3)->get();

        // Format each master plan
        $formattedPlans = $allMasterPlans->map(function ($plan) {
        return [
            'id' => $plan->id,
            'external_name' => $plan->external_name,
            'internal_name' => $plan->internal_name,
            'description' => $plan->description,
            'chargebee_plan_id' => $plan->chargebee_plan_id,
            'volume_items' => $plan->volumeItems->map(function ($item) {
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
                    'tier_discount_type' => $item->tier_discount_type,
                    'tier_discount_value' => $item->tier_discount_value,
                    'actual_price_before_discount' => $item->actual_price_before_discount,
                    'is_discounted' => $item->is_discounted
                ];
            })->toArray()
        ];
      });

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $formattedPlans
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
                'name' => $masterPlan->external_name . ' Plan',
                'external_name' => $masterPlan->external_name,
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
    {        
        try {
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
                }                
                // Create item price for the master plan with volume pricing and tiers
                $priceParams = [
                    'id' => $uniqueId . '_price',
                    'name' => $masterPlan->external_name . ' Plan',
                    'external_name' => $masterPlan->external_name,
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
                        'chargebee_plan_id' => $uniqueId . '_price',
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
     * Get special master plan data with volume items for editing
     */
    public function data()
    {
       
        try {
            // Only get special master plans (is_discounted = 3)
            $masterPlan = MasterPlan::where('is_discounted', 3)->first();
            
            if (!$masterPlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No special master plan found'
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
     * Check if special master plan exists
     */
    public function exists()
    {
        return response()->json(['exists' => MasterPlan::where('is_discounted', 3)->exists()]);
    }

    /**
     * Store a new feature for special plans
     */
    public function storeFeature(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255|unique:features,title',
            'is_active' => 'sometimes|boolean'
        ]);

        try {
            $feature = Feature::create([
                'title' => $request->title,
                'is_active' => $request->is_active ?? true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Feature created successfully',
                'feature' => $feature
            ]);

        } catch (\Exception $e) {
            Log::error('Feature creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error creating feature: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate static link for special plans
     */
    public function generateStaticLink(Request $request)
    {
        $request->validate([
            'master_plan_id' => 'required|integer',
            'chargebee_plan_id' => 'required|string'
        ]);

        try {
            // Here you can add your static link generation logic
            // For now, returning a sample response
            $link = url('/special-plans/subscribe/' . $request->chargebee_plan_id);
            
            return response()->json([
                'success' => true,
                'link' => $link,
                'message' => 'Static link generated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Static link generation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error generating static link: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all special master plans for listing
     */
    public function getAllSpecialMasterPlans()
    {
        try {
            $masterPlans = MasterPlan::with(['volumeItems.features'])
                ->where('is_discounted', 3)
                ->get();

            $formattedPlans = $masterPlans->map(function ($plan) {
                return [
                    'id' => $plan->id,
                    'external_name' => $plan->external_name,
                    'internal_name' => $plan->internal_name,
                    'description' => $plan->description,
                    'chargebee_plan_id' => $plan->chargebee_plan_id,
                    'is_discounted' => $plan->is_discounted,
                    'volume_items_count' => $plan->volumeItems->count(),
                    'volume_items' => $plan->volumeItems->map(function ($item) {
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
                            'tier_discount_type' => $item->tier_discount_type,
                            'tier_discount_value' => $item->tier_discount_value,
                            'actual_price_before_discount' => $item->actual_price_before_discount,
                            'is_discounted' => $item->is_discounted
                        ];
                    })->toArray()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPlans
            ]);

        } catch (\Exception $e) {
            Log::error('Get all special master plans error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading special master plans'
            ], 500);
        }
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
     * Force sync special master plan with Chargebee
     */
    public function forceSync()
    {
        try {
            // Only sync special master plans (is_discounted = 3)
            $masterPlan = MasterPlan::where('is_discounted', 3)->first();
            if (!$masterPlan) {
                return response()->json([
                    'success' => false,
                    'message' => 'No special master plan found to sync'
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

    /**
     * Update volume items (intelligent update instead of delete/recreate)
     */
    private function updateVolumeItems($masterPlan, $volumeItems)
    {
        // Get existing volume items
        $existingItems = $masterPlan->volumeItems()->with('features')->get();
        
        // Create a map for tracking which items to keep
        $updatedItemIds = [];
        
        foreach ($volumeItems as $index => $volumeItem) {
            $existingItem = null;
            
            // If we have an ID, try to find by ID first
            if (!empty($volumeItem['id'])) {
                $existingItem = $existingItems->where('id', $volumeItem['id'])->first();
            }
            
            // If no ID or no match found by ID, try other matching strategies
            if (!$existingItem) {
                $existingItem = $this->findMatchingVolumeItem($existingItems, $volumeItem, $index);
            }
            
            if ($existingItem) {
                // Update existing item
                $existingItem->update([
                    'name' => $volumeItem['name'],
                    'description' => $volumeItem['description'] . ' pricing tier for ' . $volumeItem['min_inbox'] . '-' . ($volumeItem['max_inbox'] == 0 ? 'unlimited' : $volumeItem['max_inbox']) . ' inboxes',
                    'min_inbox' => $volumeItem['min_inbox'],
                    'max_inbox' => $volumeItem['max_inbox'],
                    'price' => $volumeItem['price'],
                    'duration' => 'monthly',
                    'is_active' => true,
                    'tier_discount_value' => $volumeItem['tier_discount_value'] ?? null,
                    'tier_discount_type' => $volumeItem['tier_discount_type'] ?? null,
                    'actual_price_before_discount' => $volumeItem['actual_price_before_discount'] ?? null,
                    'is_discounted' => $volumeItem['discountMode'] ?? 0,
                    
                ]);
                
                // Update features
                $this->updateVolumeItemFeatures($existingItem, $volumeItem);
                
                $updatedItemIds[] = $existingItem->id;
            } else {
                // Create new item
                $newItem = $this->createSingleVolumeItem($masterPlan, $volumeItem);
                $updatedItemIds[] = $newItem->id;
            }
        }
        
        // Delete items that are no longer needed
        $masterPlan->volumeItems()
            ->whereNotIn('id', $updatedItemIds)
            ->delete();
    }
    
    /**
     * Create volume items for a new master plan
     */
    private function createVolumeItems($masterPlan, $volumeItems)
    {
        foreach ($volumeItems as $volumeItem) {
            $this->createSingleVolumeItem($masterPlan, $volumeItem);
        }
    }
    
    /**
     * Create a single volume item
     */
    private function createSingleVolumeItem($masterPlan, $volumeItem)
    {
        
        $plan = Plan::create([
            'master_plan_id' => $masterPlan->id,
            'name' => $volumeItem['name'],
            'description' => $volumeItem['description'] . ' pricing tier for ' . $volumeItem['min_inbox'] . '-' . ($volumeItem['max_inbox'] == 0 ? 'unlimited' : $volumeItem['max_inbox']) . ' inboxes',
            'price' => $volumeItem['price'],
            'duration' => 'monthly',
            'min_inbox' => $volumeItem['min_inbox'],
            'max_inbox' => $volumeItem['max_inbox'],
            'is_active' => true,
            'tier_discount_value' => $volumeItem['tier_discount_value'] ?? null,
            'tier_discount_type' => $volumeItem['tier_discount_type'] ?? null,
            'actual_price_before_discount' => $volumeItem['actual_price_before_discount'] ?? null,
            'is_discounted' => $volumeItem['discountMode'] ?? 0
        ]); 
        
        // Attach features if any
        $this->updateVolumeItemFeatures($plan, $volumeItem);
        
        return $plan;
    }
    
    /**
     * Find a matching volume item for update
     */
    private function findMatchingVolumeItem($existingItems, $volumeItem, $index)
    {
        // First, try to match by min_inbox and max_inbox (range-based matching)
        foreach ($existingItems as $existing) {
            if ($existing->min_inbox == $volumeItem['min_inbox'] && 
                $existing->max_inbox == $volumeItem['max_inbox']) {
                return $existing;
            }
        }
        
        // If no range match, try to match by position/index if items exist
        if ($existingItems->count() > $index) {
            return $existingItems->get($index);
        }
        
        // No match found, will create new
        return null;
    }
    
    /**
     * Update features for a volume item
     */
    private function updateVolumeItemFeatures($plan, $volumeItem)
    {
        // Detach all existing features
        $plan->features()->detach();
        
        // Attach new features if any
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
    }
}
