<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\PoolPlan;
use App\Models\Configuration;
use Illuminate\Http\Request;

class PoolPlanController extends Controller
{
    public function index()
    {
        $poolPlans = PoolPlan::where('is_active', 1)->get();
        $features = Feature::where('is_active', true)->get();
        $defaultProviderType = Configuration::get('PROVIDER_TYPE', 'Google');
        return view('admin.pool-pricing.pool-pricing', compact('poolPlans', 'features', 'defaultProviderType'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'duration' => 'required|string',
                'description' => 'required|string',
                'pricing_model' => 'required|string|in:per_unit,flat_fee',
                'billing_cycle' => 'required|string',
                'feature_ids' => 'nullable|array',
                'feature_ids.*' => 'exists:features,id',
                'feature_values' => 'nullable|array',
                'currency_code' => 'nullable|string|size:3',
                'provider_type' => 'nullable|string|in:Google,Microsoft 365,Private SMTP'
            ]);

            // Set default currency code if not provided
            $currencyCode = $request->currency_code ?? 'USD';
            
            // Get pricing model and billing cycle from request
            $pricingModel = $request->pricing_model;
            $billingCycle = $request->billing_cycle;
            
            // Set billing period unit based on duration
            $periodUnit = 'month'; // default
            $period = ($billingCycle === 'unlimited') ? 0 : (int)$billingCycle; // 0 means unlimited in ChargeBee
            
            switch(strtolower($request->duration)) {
                case 'monthly':
                    $periodUnit = 'month';
                    break;
                case 'weekly':
                    $periodUnit = 'week';
                    break;
                case 'daily':
                    $periodUnit = 'day';
                    break;
                case 'yearly':
                    $periodUnit = 'year';
                    break;
            }
            
            // dd($request->all());
            // ChargeBee Pool Plan Creation
            $chargeBeePlan = $this->createChargeBeeItem([
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'period' => $request->duration,
                'period_unit' => $periodUnit,
                'billing_cycles' => $period,
                'pricing_model' => $pricingModel,
                'currency_code' => $currencyCode,
                'item_family_id' => config('services.chargebee.item_family_id'),
            ]);

            if (!$chargeBeePlan['success']) {
                throw new \Exception($chargeBeePlan['message']);
            }

            // Get provider_type from Configuration table only (not from pool plan)
            // $providerType = $request->input('provider_type') 
            //     ?: \App\Models\Configuration::get('PROVIDER_TYPE', 'Google');
            $providerType = \App\Models\Configuration::get('PROVIDER_TYPE', 'Google');

            // Local Pool Plan Creation (with ChargeBee integration)
            $poolPlan = PoolPlan::create([
                'name' => $request->name,
                'price' => $request->price,
                'duration' => $request->duration,
                'description' => $request->description,
                'pricing_model' => $pricingModel,
                'billing_cycle' => $billingCycle,
                'currency_code' => $currencyCode,
                'chargebee_plan_id' => $chargeBeePlan['data']['price_id'],
                'is_chargebee_synced' => true,
                'is_active' => true,
                'provider_type' => $providerType
            ]);

            // Attach Features (if any)
            if ($request->has('feature_ids')) {
                foreach ($request->feature_ids as $index => $featureId) {
                    $value = $request->feature_values[$index] ?? null;
                    $poolPlan->features()->attach($featureId, ['value' => $value]);
                }
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pool plan created successfully',
                    'poolPlan' => $poolPlan->load('features')
                ]);
            }

            return redirect()->back()->with('success', 'Pool plan created successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error creating pool plan: ' . $e->getMessage()
                ], 500);
            }

            return redirect()->back()->with('error', 'Error creating pool plan: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing pool plan
     *
     * @param Request $request
     * @param PoolPlan $poolPlan
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, PoolPlan $poolPlan) 
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'duration' => 'nullable|string',
                'description' => 'required|string',
                'pricing_model' => 'nullable|string|in:per_unit,flat_fee',
                'billing_cycle' => 'nullable|string',
                'feature_ids' => 'nullable|array',
                'feature_ids.*' => 'exists:features,id',
                'feature_values' => 'nullable|array',
                'currency_code' => 'nullable|string|size:3',
                'provider_type' => 'nullable|string|in:Google,Microsoft 365,Private SMTP'
            ]);

            // Set default currency code if not provided
            $currencyCode = $request->currency_code ?? 'USD';
            
            // Get pricing model and billing cycle from request, or use existing values from database
            $pricingModel = $request->pricing_model ?? $poolPlan->pricing_model;
            $billingCycle = $request->billing_cycle ?? $poolPlan->billing_cycle;
            $duration = $request->duration ?? $poolPlan->duration;

            // ChargeBee Pool Plan Update (if synced)
            if ($poolPlan->is_chargebee_synced && $poolPlan->chargebee_plan_id) {
                $chargeBeePlan = $this->updateChargeBeeItem($poolPlan->chargebee_plan_id, [
                    'name' => $request->name,
                    'description' => $request->description,
                    'price' => $request->price,
                    'pricing_model' => $pricingModel,
                    'currency_code' => $currencyCode,
                ]);

                if (!$chargeBeePlan['success']) {
                    throw new \Exception('ChargeBee update failed: ' . $chargeBeePlan['message']);
                }
            }

            // Get provider_type from Configuration table only (not from pool plan)
            // $providerType = $request->input('provider_type') 
            //     ?: \App\Models\Configuration::get('PROVIDER_TYPE', 'Google');
            $providerType = \App\Models\Configuration::get('PROVIDER_TYPE', 'Google');

            // --- Local Pool Plan Update ---
            $poolPlan->update([
                'name' => $request->name,
                'price' => $request->price,
                'duration' => $duration,
                'description' => $request->description,
                'pricing_model' => $pricingModel,
                'billing_cycle' => $billingCycle,
                'currency_code' => $currencyCode,
                'provider_type' => $providerType
            ]);

            // Sync features
            $poolPlan->features()->detach();
            if ($request->has('feature_ids')) {
                foreach ($request->feature_ids as $index => $featureId) {
                    $value = $request->feature_values[$index] ?? null;
                    $poolPlan->features()->attach($featureId, ['value' => $value]);
                }
            }

            $poolPlan->refresh();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pool plan updated successfully',
                    'poolPlan' => $poolPlan->load('features')
                ]);
            }

            return redirect()->back()->with('success', 'Pool plan updated successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating pool plan: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error updating pool plan: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, PoolPlan $poolPlan)
    {
        try {
            // Detach features and mark as inactive (soft delete approach)
            $poolPlan->features()->detach();
            $poolPlan->is_active = 0; 
            $poolPlan->save();
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pool plan deleted successfully'
                ]);
            }

            return redirect()->back()->with('success', 'Pool plan deleted successfully');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error deleting pool plan: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error deleting pool plan: ' . $e->getMessage());
        }
    }
    
    public function getPlansWithFeatures()
    {
        $poolPlans = PoolPlan::with('features')->where('is_active', 1)->get();
        
        return response()->json([
            'poolPlans' => $poolPlans
        ]);
    }

    /**
     * Manually sync existing pool plans with ChargeBee
     */
    public function syncWithChargebee(Request $request)
    {
        try {
            $request->validate([
                'plan_ids' => 'required|array',
                'plan_ids.*' => 'exists:pool_plans,id'
            ]);

            $syncedPlans = [];
            $errors = [];
            $poolPlans = PoolPlan::whereIn('id', $request->plan_ids)->get();

            foreach ($poolPlans as $poolPlan) {
                // Skip if already synced
                if ($poolPlan->is_chargebee_synced) {
                    continue;
                }
                
                // Set billing period unit based on duration
                $periodUnit = 'month'; // default
                $billingCycle = $poolPlan->billing_cycle ?? '1';
                $period = ($billingCycle === 'unlimited') ? 0 : (int)$billingCycle;
                
                switch(strtolower($poolPlan->duration)) {
                    case 'monthly':
                        $periodUnit = 'month';
                        break;
                    case 'weekly':
                        $periodUnit = 'week';
                        break;
                    case 'daily':
                        $periodUnit = 'day';
                        break;
                    case 'yearly':
                        $periodUnit = 'year';
                        break;
                }
                
                // Create ChargeBee plan
                $chargeBeePlan = $this->createChargeBeeItem([
                    'name' => $poolPlan->name,
                    'description' => $poolPlan->description,
                    'price' => $poolPlan->price,
                    'period' => $poolPlan->duration,
                    'period_unit' => $periodUnit,
                    'billing_cycles' => $period,
                    'pricing_model' => $poolPlan->pricing_model ?? 'per_unit',
                    'currency_code' => $poolPlan->currency_code,
                    'item_family_id' => config('services.chargebee.item_family_id'),
                ]);

                if ($chargeBeePlan['success']) {
                    // Update local plan with ChargeBee data
                    $poolPlan->update([
                        'chargebee_plan_id' => $chargeBeePlan['data']['price_id'],
                        'is_chargebee_synced' => true
                    ]);
                    $syncedPlans[] = $poolPlan->name;
                } else {
                    $errors[] = $poolPlan->name . ': ' . $chargeBeePlan['message'];
                }
            }

            $message = 'Sync completed. ';
            if (count($syncedPlans) > 0) {
                $message .= 'Synced: ' . implode(', ', $syncedPlans) . '. ';
            }
            if (count($errors) > 0) {
                $message .= 'Errors: ' . implode('; ', $errors);
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => count($errors) === 0,
                    'message' => $message,
                    'synced_plans' => $syncedPlans,
                    'errors' => $errors
                ]);
            }

            return redirect()->back()->with(count($errors) === 0 ? 'success' : 'warning', $message);

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error syncing with ChargeBee: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error syncing with ChargeBee: ' . $e->getMessage());
        }
    }

    public function duplicate(Request $request)
    {
        try {
            $request->validate([
                'plan_ids' => 'required|array',
                'plan_ids.*' => 'exists:pool_plans,id'
            ]);

            $duplicatedPlans = [];
            $originalPlans = PoolPlan::with('features')->whereIn('id', $request->plan_ids)->get();

            foreach ($originalPlans as $originalPlan) {
                $duplicatedName = $originalPlan->name . ' (Copy)';
                
                // Create ChargeBee plan for duplicate if original was synced
                $chargebeePlanId = null;
                $isChargebeeSynced = false;
                
                if ($originalPlan->is_chargebee_synced) {
                    // Set billing period unit based on duration
                    $periodUnit = 'month'; // default
                    $billingCycle = $originalPlan->billing_cycle ?? '1';
                    $period = ($billingCycle === 'unlimited') ? 0 : (int)$billingCycle;
                    
                    switch(strtolower($originalPlan->duration)) {
                        case 'monthly':
                            $periodUnit = 'month';
                            break;
                        case 'weekly':
                            $periodUnit = 'week';
                            break;
                        case 'daily':
                            $periodUnit = 'day';
                            break;
                        case 'yearly':
                            $periodUnit = 'year';
                            break;
                    }
                    
                    $chargeBeePlan = $this->createChargeBeeItem([
                        'name' => $duplicatedName,
                        'description' => $originalPlan->description,
                        'price' => $originalPlan->price,
                        'period' => $originalPlan->duration,
                        'period_unit' => $periodUnit,
                        'billing_cycles' => $period,
                        'pricing_model' => $originalPlan->pricing_model ?? 'per_unit',
                        'currency_code' => $originalPlan->currency_code,
                        'item_family_id' => config('services.chargebee.item_family_id'),
                    ]);

                    if ($chargeBeePlan['success']) {
                        $chargebeePlanId = $chargeBeePlan['data']['price_id'];
                        $isChargebeeSynced = true;
                    }
                }

                // Create duplicate plan with modified name
                $duplicatedPlan = PoolPlan::create([
                    'name' => $duplicatedName,
                    'price' => $originalPlan->price,
                    'duration' => $originalPlan->duration,
                    'description' => $originalPlan->description,
                    'pricing_model' => $originalPlan->pricing_model ?? 'per_unit',
                    'billing_cycle' => $originalPlan->billing_cycle ?? '1',
                    'currency_code' => $originalPlan->currency_code,
                    'chargebee_plan_id' => $chargebeePlanId,
                    'is_chargebee_synced' => $isChargebeeSynced,
                    'is_active' => true
                ]);

                // Duplicate features
                foreach ($originalPlan->features as $feature) {
                    $duplicatedPlan->features()->attach($feature->id, [
                        'value' => $feature->pivot->value
                    ]);
                }

                $duplicatedPlans[] = $duplicatedPlan;
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Successfully duplicated ' . count($duplicatedPlans) . ' plan(s)',
                    'duplicated_plans' => $duplicatedPlans
                ]);
            }

            return redirect()->back()->with('success', 'Successfully duplicated ' . count($duplicatedPlans) . ' plan(s)');

        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error duplicating plans: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error duplicating plans: ' . $e->getMessage());
        }
    }

    /**
     * Create a new item/plan in ChargeBee for pool plans
     */
    private function createChargeBeeItem($data)
    {
        try {
            // Generate unique ID for the pool plan item with microseconds and random component
            $baseName = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $data['name']));
            $timestamp = microtime(true) * 10000; // Include microseconds
            $randomSuffix = mt_rand(1000, 9999);
            $period = strtolower($data['period']);
            
            $itemId = 'pool_' . $baseName . '_' . $timestamp . '_' . '_item';
            $priceId = 'pool_' . $baseName . '_' . $timestamp . '_' . $period . '_price';

            // Create an item in ChargeBee
            $result = \ChargeBee\ChargeBee\Models\Item::create([
                'id' => $itemId,
                'name' => $data['name'],
                'description' => $data['description'],
                'type' => 'plan',
                'enabled_in_portal' => true,
                'item_family_id' => $data['item_family_id'],
                'status' => 'active',
                'is_shippable' => false,
                'is_giftable' => false,
                'metadata' => [
                    'plan_type' => 'pool_plan',
                    'customer_facing_description' => $data['description'],
                    'show_on_checkout' => 'true',
                    'show_on_portal' => 'true'
                ]
            ]);

            if ($result && $result->item()) {
                // Create item price for the pool plan
                $priceParams = [
                    'id' => $priceId,
                    'name' => $data['name'] . ' ' . ucfirst($data['period']),
                    'item_id' => $result->item()->id,
                    'pricing_model' => $data['pricing_model'], // Use pricing_model from data
                    'price' => $data['price'] * 100, // Convert to cents
                    'external_name' => $data['name'] . ' ' . ucfirst($data['period']),
                    'period_unit' => $data['period_unit'], // Use the period_unit from data
                    'period' => 1, // Always 1, billing_cycles controls total duration
                    'currency_code' => $data['currency_code'],
                    'status' => 'active',
                    'channel' => 'web',
                    // 'is_taxable' => true,
                    'metadata' => [
                        'plan_type' => 'pool_plan',
                        'customer_facing_description' => $data['description'],
                        'show_on_checkout' => 'true',
                        'show_on_portal' => 'true'
                    ]
                ];
                
                // Add billing_cycles only if it's not 0 (unlimited)
                if ($data['billing_cycles'] > 0) {
                    $priceParams['billing_cycles'] = $data['billing_cycles'];
                }

                $priceResult = \ChargeBee\ChargeBee\Models\ItemPrice::create($priceParams);

                if ($priceResult && $priceResult->itemPrice()) {
                    return [
                        'success' => true,
                        'message' => 'Pool plan created successfully in ChargeBee',
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
                'message' => 'Failed to create pool plan in ChargeBee'
            ];

        } catch (\ChargeBee\ChargeBee\Exceptions\InvalidRequestException $e) {
            // Handle ChargeBee-specific validation errors
            $errorDetails = '';
            if ($e->getApiErrorCode()) {
                $errorDetails = ' (Code: ' . $e->getApiErrorCode() . ')';
            }
            
            return [
                'success' => false,
                'message' => 'ChargeBee validation error: ' . $e->getMessage() . $errorDetails
            ];
        } catch (\ChargeBee\ChargeBee\Exceptions\PaymentException $e) {
            return [
                'success' => false,
                'message' => 'ChargeBee payment error: ' . $e->getMessage()
            ];
        } catch (\ChargeBee\ChargeBee\Exceptions\OperationFailedException $e) {
            return [
                'success' => false,
                'message' => 'ChargeBee operation failed: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating pool plan in ChargeBee: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing ChargeBee item for pool plans
     */
    private function updateChargeBeeItem($chargebeeItemId, $data)
    {
        try {
            $updateParams = [
                'name' => $data['name'] . ' Pool Price',
                'price' => $data['price'] * 100, // Convert to cents
                'external_name' => $data['name'] . ' Pool Plan',
                'customer_facing_description' => $data['description'],
                'metadata' => [
                    'plan_type' => 'pool_plan',
                    'customer_facing_description' => $data['description'],
                    'show_on_checkout' => 'true',
                    'show_on_portal' => 'true'
                ]
            ];
            
            // Add pricing_model if provided
            if (isset($data['pricing_model'])) {
                $updateParams['pricing_model'] = $data['pricing_model'];
            }
            
            // Update the item in ChargeBee
            $result = \ChargeBee\ChargeBee\Models\ItemPrice::update($chargebeeItemId, $updateParams);

            if ($result && $result->itemPrice()) {
                return [
                    'success' => true,
                    'message' => 'Pool plan updated successfully in ChargeBee',
                    'data' => [
                        'price_id' => $result->itemPrice()->id,
                        'name' => $result->itemPrice()->name,
                        'price' => $result->itemPrice()->price / 100,
                        'currency' => $result->itemPrice()->currencyCode
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to update pool plan in ChargeBee'
            ];

        } catch (\ChargeBee\ChargeBee\Exceptions\InvalidRequestException $e) {
            // Handle ChargeBee-specific validation errors
            $errorDetails = '';
            if ($e->getApiErrorCode()) {
                $errorDetails = ' (Code: ' . $e->getApiErrorCode() . ')';
            }
            
            return [
                'success' => false,
                'message' => 'ChargeBee validation error: ' . $e->getMessage() . $errorDetails
            ];
        } catch (\ChargeBee\ChargeBee\Exceptions\PaymentException $e) {
            return [
                'success' => false,
                'message' => 'ChargeBee payment error: ' . $e->getMessage()
            ];
        } catch (\ChargeBee\ChargeBee\Exceptions\OperationFailedException $e) {
            return [
                'success' => false,
                'message' => 'ChargeBee operation failed: ' . $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating pool plan in ChargeBee: ' . $e->getMessage()
            ];
        }
    }
}