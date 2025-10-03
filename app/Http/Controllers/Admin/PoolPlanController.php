<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\PoolPlan;
use Illuminate\Http\Request;

class PoolPlanController extends Controller
{
    public function index()
    {
        $poolPlans = PoolPlan::where('is_active', 1)->get();
        $features = Feature::where('is_active', true)->get();
        return view('admin.pool-pricing.pool-pricing', compact('poolPlans', 'features'));
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'price' => 'required|numeric|min:0',
                'duration' => 'required|string',
                'description' => 'required|string',
                'feature_ids' => 'nullable|array',
                'feature_ids.*' => 'exists:features,id',
                'feature_values' => 'nullable|array',
                'currency_code' => 'nullable|string|size:3'
            ]);

            // Set default currency code if not provided
            $currencyCode = $request->currency_code ?? 'USD';

            // Local Pool Plan Creation (without ChargeBee integration)
            $poolPlan = PoolPlan::create([
                'name' => $request->name,
                'price' => $request->price,
                'duration' => $request->duration,
                'description' => $request->description,
                'currency_code' => $currencyCode,
                'is_active' => true
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
                'duration' => 'required|string',
                'description' => 'required|string',
                'feature_ids' => 'nullable|array',
                'feature_ids.*' => 'exists:features,id',
                'feature_values' => 'nullable|array',
                'currency_code' => 'nullable|string|size:3'
            ]);

            // Set default currency code if not provided
            $currencyCode = $request->currency_code ?? 'USD';

            // --- Local Pool Plan Update ---
            $poolPlan->update([
                'name' => $request->name,
                'price' => $request->price,
                'duration' => $request->duration,
                'description' => $request->description,
                'currency_code' => $currencyCode
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
                // Create duplicate plan with modified name
                $duplicatedPlan = PoolPlan::create([
                    'name' => $originalPlan->name . ' (Copy)',
                    'price' => $originalPlan->price,
                    'duration' => $originalPlan->duration,
                    'description' => $originalPlan->description,
                    'currency_code' => $originalPlan->currency_code,
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
}