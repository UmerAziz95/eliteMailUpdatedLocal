<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Plan;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::all();
        $getMostlyUsed = Plan::getMostlyUsed();
        // dd($getMostlyUsedPlan);
        $features = Feature::where('is_active', true)->get();
        return view('admin.pricing.pricing', compact('plans', 'features', 'getMostlyUsed'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'duration' => 'required|string',
                'description' => 'required|string',
                'min_inbox' => 'required|integer|min:1',
                'max_inbox' => 'required|integer|min:0',
                'feature_ids' => 'nullable|array',
                'feature_ids.*' => 'exists:features,id',
                'feature_values' => 'nullable|array',
                'currency_code' => 'nullable|string|size:3'
            ]);

            // Set default currency code if not provided
            $currencyCode = $request->currency_code ?? 'USD';

            // First create plan in ChargeBee
            $chargeBeePlan = $this->createChargeBeeItem([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'period' => $request->duration,
                'period_unit' => 1, // Assuming 1 as default unit
                'currency_code' => $currencyCode
            ]);
            
            if (!$chargeBeePlan['success']) {
                throw new \Exception($chargeBeePlan['message']);
            }

            // Create plan in database with ChargeBee ID
            $plan = Plan::create([
                'name' => $request->name,
                'chargebee_plan_id' => $chargeBeePlan['data']['price_id'],
                'price' => $request->price,
                'duration' => $request->duration,
                'description' => $request->description,
                'min_inbox' => $request->min_inbox,
                'max_inbox' => $request->max_inbox,
                'is_active' => true
            ]);

            // Attach features with their values
            if ($request->has('feature_ids')) {
                foreach ($request->feature_ids as $index => $featureId) {
                    $value = $request->feature_values[$index] ?? null;
                    $plan->features()->attach($featureId, ['value' => $value]);
                }
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Plan created successfully',
                    'plan' => $plan->load('features')
                ]);
            }

            return redirect()->back()->with('success', 'Plan created successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating plan: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error creating plan: ' . $e->getMessage());
        }
    }

    private function createChargeBeeItem($data)
    {
        try {
            // Generate unique ID for the item
            $uniqueId = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $data['name'])) . '_' . time() . '-monthly';

            // Create an item in ChargeBee
            $result = \ChargeBee\ChargeBee\Models\Item::create([
                'id' => $uniqueId,
                'name' => $data['name'],
                'description' => $data['description'],
                'type' => 'plan',
                'enabled_in_portal' => true,
                'item_family_id' => 'cbdemo_omnisupport-solutions',
                'status' => 'active'
            ]);

            if ($result && $result->item()) {
                // Create item price for the plan with proper period settings
                $priceParams = [
                    'id' => $uniqueId,  // Already includes -monthly suffix
                    'name' => $data['name'] . ' Monthly Price',
                    'item_id' => $result->item()->id,
                    'pricing_model' => 'flat_fee',
                    'price' => $data['price'] * 100, // Convert to cents
                    'external_name' => $data['name'] . ' ' . ucfirst($data['period']) . ' Plan',
                    'period_unit' => strtolower($data['period']) === 'monthly' ? 'month' : 'year',
                    'period' => 1,
                    'currency_code' => $data['currency_code'],
                    'status' => 'active',
                    'channel' => 'web'
                ];

                $priceResult = \ChargeBee\ChargeBee\Models\ItemPrice::create($priceParams);

                if ($priceResult && $priceResult->itemPrice()) {
                    return [
                        'success' => true,
                        'message' => 'Plan created successfully in ChargeBee',
                        'data' => [
                            'item_id' => $result->item()->id,
                            'price_id' => $priceResult->itemPrice()->id,
                            'name' => $result->item()->name,
                            'price' => $priceResult->itemPrice()->price / 100,
                            'currency' => $priceResult->itemPrice()->currencyCode,
                            'period' => $priceResult->itemPrice()->period,
                            'period_unit' => $priceResult->itemPrice()->periodUnit
                        ]
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Failed to create plan in ChargeBee'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating plan: ' . $e->getMessage()
            ];
        }
    }

    public function update(Request $request, Plan $plan)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'chargebee_plan_id' => 'nullable|string|max:255',
                'price' => 'required|numeric|min:0',
                'duration' => 'required|string',
                'description' => 'required|string',
                'min_inbox' => 'required|integer|min:1',
                'max_inbox' => 'required|integer|min:0',
                'feature_ids' => 'nullable|array',
                'feature_ids.*' => 'exists:features,id',
                'feature_values' => 'nullable|array',
                'currency_code' => 'nullable|string|size:3'
            ]);

            // Set default currency code if not provided
            $currencyCode = $request->currency_code ?? 'USD';

            // Update in ChargeBee first
            if ($plan->chargebee_plan_id) {
                // Extract base ID without -monthly suffix for the item
                $baseItemId = preg_replace('/-monthly$/', '', $plan->chargebee_plan_id);
                
                try {
                    // First check if the item and price exist and get their current status
                    $itemResponse = \ChargeBee\ChargeBee\Models\Item::retrieve($plan->chargebee_plan_id);
                    $priceResponse = \ChargeBee\ChargeBee\Models\ItemPrice::retrieve($plan->chargebee_plan_id);
                    // dd($itemResponse, $priceResponse);
                    // Generate new unique ID for the updated plan
                    $newName = $request->name;
                    $uniqueId = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $newName)) . '_' . time() . '-monthly';

                    // Only try to reactivate if they exist and are archived
                    if ($itemResponse && $itemResponse->item()->status === 'archived') {
                        $reactivateItem = \ChargeBee\ChargeBee\Models\Item::update($plan->chargebee_plan_id, [
                            'status' => 'active'
                        ]);
                    }
                    
                    if ($priceResponse && $priceResponse->itemPrice()->status === 'archived') {
                        $reactivatePrice = \ChargeBee\ChargeBee\Models\ItemPrice::update($plan->chargebee_plan_id, [
                            'status' => 'active'
                        ]);
                    }

                    // Create new item in ChargeBee
                    // $result = \ChargeBee\ChargeBee\Models\Item::update([
                    //     'id' => $plan->chargebee_plan_id,
                    //     // 'name' => $uniqueId,
                    //     'description' => $request->description,
                    //     'type' => 'plan',
                    //     'enabled_in_portal' => true,
                    //     'item_family_id' => 'cbdemo_omnisupport-solutions',
                    //     'status' => 'active'
                    // ]);
                    $result = \ChargeBee\ChargeBee\Models\Item::update($plan->chargebee_plan_id, [
                        'status' => 'active',
                        "description" => $request->description,
                        'name'=> $request->name,
                        'external_name' => $request->name,
                    ]);

                    if (!$result || !$result->item()) {
                        throw new \Exception('Failed to create new item in ChargeBee');
                    }

                    // Create new price for the new item
                    $priceResult = \ChargeBee\ChargeBee\Models\ItemPrice::update($plan->chargebee_plan_id,[
                        'name' => $request->name . ' Monthly Price',
                        'external_name' => $request->name . ' Monthly Price',
                        // 'item_id' => $uniqueId,
                        // 'pricing_model' => 'flat_fee',
                        'price' => $request->price * 100,
                        // 'external_name' => $request->name . ' ' . ucfirst($request->duration) . ' Plan',
                        // 'period_unit' => strtolower($request->duration) === 'monthly' ? 'month' : 'year',
                        // 'period' => 1,
                        // 'currency_code' => $currencyCode,
                        'status' => 'active'
                    ]);

                    // if (!$priceResult || !$priceResult->itemPrice()) {
                    //     throw new \Exception('Failed to create new price in ChargeBee');
                    // }

                    // // If everything is successful, archive the old item and price
                    // if ($itemResponse) {
                    //     \ChargeBee\ChargeBee\Models\Item::update($baseItemId, [
                    //         'status' => 'archived'
                    //     ]);
                    // }
                    
                    // if ($priceResponse) {
                    //     \ChargeBee\ChargeBee\Models\ItemPrice::update($plan->chargebee_plan_id, [
                    //         'status' => 'archived'
                    //     ]);
                    // }

                    // Update chargebee_plan_id in database with new ID
                    // $request->merge(['chargebee_plan_id' => $uniqueId]);
                } catch (\ChargeBee\ChargeBee\Exceptions\APIError $e) {
                    throw new \Exception('ChargeBee API Error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    throw new \Exception('Failed to update plan in ChargeBee: ' . $e->getMessage());
                }
            }

            // Update plan in database
            $plan->update([
                'name' => $request->name,
                'chargebee_plan_id' => $request->chargebee_plan_id,
                'price' => $request->price,
                'duration' => $request->duration,
                'description' => $request->description,
                'min_inbox' => $request->min_inbox,
                'max_inbox' => $request->max_inbox
            ]);

            // Sync features with their values
            $plan->features()->detach();
            if ($request->has('feature_ids')) {
                foreach ($request->feature_ids as $index => $featureId) {
                    $value = $request->feature_values[$index] ?? null;
                    $plan->features()->attach($featureId, ['value' => $value]);
                }
            }

            // Refresh the plan instance to get the updated data
            $plan->refresh();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Plan updated successfully',
                    'plan' => $plan->load('features')
                ]);
            }

            return redirect()->back()->with('success', 'Plan updated successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating plan: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error updating plan: ' . $e->getMessage());
        }
    }

    public function destroy(Plan $plan)
    {
        try {
            // Archive plan in ChargeBee first
            if ($plan->chargebee_plan_id) {
                // Extract base ID without -monthly suffix for the item
                $baseItemId = preg_replace('/-monthly$/', '', $plan->chargebee_plan_id);
                
                try {
                    // First check if the item and price exist and get their current status
                    $itemResponse = \ChargeBee\ChargeBee\Models\Item::retrieve($plan->chargebee_plan_id);
                    // dd($itemResponse);
                    $priceResponse = \ChargeBee\ChargeBee\Models\ItemPrice::retrieve($plan->chargebee_plan_id);
                    // dd($itemResponse, $priceResponse);
                    // Only try to reactivate if they exist and are archived
                    if ($itemResponse && $itemResponse->item()->status === 'archived') {
                        $reactivateItem = \ChargeBee\ChargeBee\Models\Item::update($plan->chargebee_plan_id, [
                            'status' => 'active'
                        ]);
                    }
                    
                    if ($priceResponse && $priceResponse->itemPrice()->status === 'archived') {
                        $reactivatePrice = \ChargeBee\ChargeBee\Models\ItemPrice::update($plan->chargebee_plan_id, [
                            'status' => 'active'
                        ]);
                    }

                    // Archive price first (as it depends on the item)
                    if ($priceResponse) {
                        $priceResult = \ChargeBee\ChargeBee\Models\ItemPrice::update($plan->chargebee_plan_id, [
                            'status' => 'archived'
                        ]);
                    }

                    // Then archive the item
                    if ($itemResponse) {
                        $result = \ChargeBee\ChargeBee\Models\Item::update($plan->chargebee_plan_id, [
                            'status' => 'archived'
                        ]);
                    }
                    // dd($result, $priceResult);
                    // Check if archival was successful
                    if ((!$itemResponse || ($result && $result->item())) && 
                        (!$priceResponse || ($priceResult && $priceResult->itemPrice()))) {
                        // Delete from local database only if ChargeBee operations were successful
                        $plan->features()->detach();
                        $plan->delete();
                        
                        if (request()->ajax()) {
                            return response()->json([
                                'success' => true,
                                'message' => 'Plan deleted successfully'
                            ]);
                        }

                        return redirect()->back()->with('success', 'Plan deleted successfully');
                    }
                    
                    throw new \Exception('Failed to archive plan in ChargeBee');
                } catch (\ChargeBee\ChargeBee\Exceptions\APIError $e) {
                    throw new \Exception('ChargeBee API Error: ' . $e->getMessage());
                } catch (\Exception $e) {
                    throw new \Exception('Failed to archive plan: ' . $e->getMessage());
                }
            } else {
                // If no ChargeBee ID exists, just delete from local database
                $plan->features()->detach();
                $plan->delete();
                
                if (request()->ajax()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Plan deleted successfully'
                    ]);
                }

                return redirect()->back()->with('success', 'Plan deleted successfully');
            }
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting plan: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error deleting plan: ' . $e->getMessage());
        }
    }
    
    public function getPlansWithFeatures()
    {
        $plans = Plan::with('features')->get();
        return response()->json($plans);
    }
}