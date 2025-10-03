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
                $existingInfinite = PoolPlan::where('max_inbox', 0)
                    ->where('is_active', 1)
                    ->exists();

                if ($existingInfinite) {
                    throw new \Exception("A pool plan with unlimited inbox already exists.");
                }
            }

            // ✅ CASE 1: Prevent new plan if existing infinite active plan overlaps
            $infinitePlanConflict = PoolPlan::where('max_inbox', 0)
                ->where('is_active', 1)
                ->where('min_inbox', '<=', $min)
                ->exists();

            if ($infinitePlanConflict) {
                throw new \Exception("An existing active pool plan allows unlimited inboxes starting from $min or lower.");
            }

            // ✅ CASE 2: Prevent overlapping ranges with active plans
            $overlappingPlan = PoolPlan::where('is_active', 1)
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
                throw new \Exception("An active pool plan overlaps with the range $min - " . ($max == 0 ? '∞' : $max) . ".");
            }

            // Set default currency code if not provided
            $currencyCode = $request->currency_code ?? 'USD';

            // Local Pool Plan Creation (without ChargeBee integration)
            $poolPlan = PoolPlan::create([
                'name' => $request->name,
                'price' => $request->price,
                'duration' => $request->duration,
                'description' => $request->description,
                'min_inbox' => $min,
                'max_inbox' => $max,
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
                $existingInfinite = PoolPlan::where('max_inbox', 0)
                    ->where('is_active', 1)
                    ->where('id', '!=', $poolPlan->id)
                    ->exists();

                if ($existingInfinite) {
                    throw new \Exception("A pool plan with unlimited inbox already exists.");
                }
            }

            // ✅ CASE 1: Prevent new plan if existing infinite active plan overlaps
            $infinitePlanConflict = PoolPlan::where('max_inbox', 0)
                ->where('is_active', 1)
                ->where('min_inbox', '<=', $min)
                ->where('id', '!=', $poolPlan->id)
                ->exists();

            if ($infinitePlanConflict) {
                throw new \Exception("An existing active pool plan allows unlimited inboxes starting from $min or lower.");
            }

            // ✅ CASE 2: Prevent overlapping ranges with active plans (excluding current)
            $overlappingPlan = PoolPlan::where('is_active', 1)
                ->where('id', '!=', $poolPlan->id)
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
                throw new \Exception("An active pool plan overlaps with the range $min - " . ($max == 0 ? '∞' : $max) . ".");
            }

            // Set default currency code if not provided
            $currencyCode = $request->currency_code ?? 'USD';

            // --- Local Pool Plan Update ---
            $poolPlan->update([
                'name' => $request->name,
                'price' => $request->price,
                'duration' => $request->duration,
                'description' => $request->description,
                'min_inbox' => $min,
                'max_inbox' => $max,
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
}