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
        $plans = Plan::where('is_active',1)->get();
        $getMostlyUsed = Plan::getMostlyUsed();
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
                'min_inbox' => 'required|integer|min:0',
                'max_inbox' => 'required|integer|min:0',
                'feature_ids' => 'nullable|array',
                'feature_ids.*' => 'exists:features,id',
                'feature_values' => 'nullable|array',
                'currency_code' => 'nullable|string|size:3'
            ]);

            $min = $request->min_inbox;
            $max = $request->max_inbox;
            $newMax = ($max == 0) ? PHP_INT_MAX : $max;

            // ✅ CASE 0: Prevent multiple unlimited plans
            if ($min == 0 && $max == 0) {
                $existingInfinite = Plan::where('max_inbox', 0)
                    ->where('is_active', 1)
                    ->exists();

                if ($existingInfinite) {
                    throw new \Exception("A plan with unlimited inbox already exists.");
                }
            }

            // ✅ CASE 1: Prevent new plan if existing infinite active plan overlaps
            $infinitePlanConflict = Plan::where('max_inbox', 0)
                ->where('is_active', 1)
                ->where('min_inbox', '<=', $min)
                ->exists();

            if ($infinitePlanConflict) {
                throw new \Exception("An existing active plan allows unlimited inboxes starting from $min or lower.");
            }

            // ✅ CASE 2: Prevent overlapping ranges with active plans
            $overlappingPlan = Plan::where('is_active', 1)
                ->where(function ($query) use ($min, $newMax) {
                    $query->where(function ($sub) use ($min, $newMax) {
                        $sub->where('min_inbox', '<=', $newMax)
                            ->where(function ($q) use ($min) {
                                $q->where('max_inbox', '>=', $min)
                                ->orWhere('max_inbox', 0);
                            });
                    });
                })->exists();

            if ($overlappingPlan) {
                throw new \Exception("An active plan overlaps with the range $min - " . ($max == 0 ? '∞' : $max) . ".");
            }

            // Set default currency code if not provided
            $currencyCode = $request->currency_code ?? 'USD';

            // ChargeBee Plan Creation
            $chargeBeePlan = $this->createChargeBeeItem([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'period' => $request->duration,
                'period_unit' => 1,
                'currency_code' => $currencyCode,
                'min_usage' => $min,
                'max_usage' => $max,
                'item_family_id' => 'cbdemo_omnisupport-solutions',
            ]);

            if (!$chargeBeePlan['success']) {
                throw new \Exception($chargeBeePlan['message']);
            }

            // Local Plan Creation
            $plan = Plan::create([
                'name' => $request->name,
                'chargebee_plan_id' => $chargeBeePlan['data']['price_id'],
                'price' => $request->price,
                'duration' => $request->duration,
                'description' => $request->description,
                'min_inbox' => $min,
                'max_inbox' => $max,
                'is_active' => true
            ]);

            // Attach Features (if any)
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
                'status' => 'active',
                // ChargeBee settings for customer-facing visibility
                'customer_facing_description' => $data['description'],
                // 'customer_facing_info' => $data['description'],
                'show_description_in_invoices' => true,  // Show description on Invoices
                'show_description_in_quotes' => false,    // Show description on Quotes
                'metadata' => [
                    'customer_facing_description' => $data['description'],
                    'show_on_checkout' => 'true',
                    'show_on_portal' => 'true'
                ]
            ]);

            if ($result && $result->item()) {
                // Create item price for the plan with proper period settings
                $priceParams = [
                    'id' => $uniqueId,  // Already includes -monthly suffix
                    'name' => $data['name'] . ' Monthly Price',
                    'item_id' => $result->item()->id,
                    'pricing_model' => 'per_unit',
                    'price' => $data['price'] * 100, // Convert to cents
                    'external_name' => $data['name'] . ' ' . ucfirst($data['period']) . ' Plan',
                    'period_unit' => strtolower($data['period']) === 'monthly' ? 'month' : 'year',
                    'period' => 1,
                    'currency_code' => $data['currency_code'],
                    'status' => 'active',
                    'channel' => 'web',
                    // ChargeBee settings for customer-facing visibility
                    'customer_facing_description' => $data['description'],
                    'show_description_in_invoices' => true,  // Show description on Invoices
                    'show_description_in_quotes' => false,    // Show description on Quotes
                    'metadata' => [
                        'customer_facing_description' => $data['description'],
                        'show_on_checkout' => 'true',
                        'show_on_portal' => 'true'
                    ]
                ];
                // $priceParams['metadata'] = [
                //     'min_usage' => $data['min_usage'] ?? 0,
                //     'max_usage' => $data['max_usage'] ?? null // null means no upper limit
                // ];

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
    /**
     * Update an existing plan
     *
     * @param Request $request
     * @param Plan $plan
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Plan $plan) 
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'chargebee_plan_id' => 'nullable|string|max:255',
                'price' => 'required|numeric|min:0',
                'duration' => 'required|string',
                'description' => 'required|string',
                'min_inbox' => 'required|integer|min:0',
                'max_inbox' => 'required|integer|min:0',
                'feature_ids' => 'nullable|array',
                'feature_ids.*' => 'exists:features,id',
                'feature_values' => 'nullable|array',
                'currency_code' => 'nullable|string|size:3'
            ]);

            $min = $request->min_inbox;
            $max = $request->max_inbox;
            $newMax = ($max == 0) ? PHP_INT_MAX : $max;

            // ✅ CASE 0: Prevent multiple unlimited plans (excluding the current one)
            if ($min == 0 && $max == 0) {
                $existingInfinite = Plan::where('max_inbox', 0)
                    ->where('is_active', 1)
                    ->where('id', '!=', $plan->id)
                    ->exists();

                if ($existingInfinite) {
                    throw new \Exception("A plan with unlimited inbox already exists.");
                }
            }

            // ✅ CASE 1: Prevent new plan if existing infinite active plan overlaps
            $infinitePlanConflict = Plan::where('max_inbox', 0)
                ->where('is_active', 1)
                ->where('min_inbox', '<=', $min)
                ->where('id', '!=', $plan->id)
                ->exists();

            if ($infinitePlanConflict) {
                throw new \Exception("An existing active plan allows unlimited inboxes starting from $min or lower.");
            }

            // ✅ CASE 2: Prevent overlapping ranges with active plans (excluding current)
            $overlappingPlan = Plan::where('is_active', 1)
                ->where('id', '!=', $plan->id)
                ->where(function ($query) use ($min, $newMax) {
                    $query->where(function ($sub) use ($min, $newMax) {
                        $sub->where('min_inbox', '<=', $newMax)
                            ->where(function ($q) use ($min) {
                                $q->where('max_inbox', '>=', $min)
                                ->orWhere('max_inbox', 0);
                            });
                    });
                })->exists();

            if ($overlappingPlan) {
                throw new \Exception("An active plan overlaps with the range $min - " . ($max == 0 ? '∞' : $max) . ".");
            }

            // Set default currency code if not provided
            $currencyCode = $request->currency_code ?? 'USD';

            // --- Chargebee update logic (your existing code remains unchanged) ---
            if ($plan->chargebee_plan_id) {
                // Same logic as you had for updating in Chargebee
                // ...
            }

            // --- Local Plan Update ---
            $plan->update([
                'name' => $request->name,
                'chargebee_plan_id' => $request->chargebee_plan_id,
                'price' => $request->price,
                'duration' => $request->duration,
                'description' => $request->description,
                'min_inbox' => $min,
                'max_inbox' => $max
            ]);

            // Sync features
            $plan->features()->detach();
            if ($request->has('feature_ids')) {
                foreach ($request->feature_ids as $index => $featureId) {
                    $value = $request->feature_values[$index] ?? null;
                    $plan->features()->attach($featureId, ['value' => $value]);
                }
            }

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


    public function destroy(Request $request,Plan $plan)
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
                    // dd((!$itemResponse || ($result && $result->item())) && 
                    //     (!$priceResponse || ($priceResult && $priceResult->itemPrice())));
                    // dd($result, $priceResult);
                    // Check if archival was successful
                    if ((!$itemResponse || ($result && $result->item())) && 
                        (!$priceResponse || ($priceResult && $priceResult->itemPrice()))) {
                        // Delete from local database only if ChargeBee operations were successful
                        $plan->features()->detach();
                        
                        $plan->is_active=0; 
                        $plan->save();
                        
                        if ($request->ajax()) {
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
        $plans = Plan::with('features')->where('is_active', 1)->get();
        $mostlyUsed = Plan::getMostlyUsed();
        
        return response()->json([
            'plans' => $plans,
            'mostlyUsed' => $mostlyUsed
        ]);
    }
}