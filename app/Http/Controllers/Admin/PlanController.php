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
        $features = Feature::where('is_active', true)->get();
        return view('admin.pricing.pricing', compact('plans', 'features'));
    }

    public function store(Request $request)
    {
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
        ]);

        $plan = Plan::create([
            'name' => $request->name,
            'chargebee_plan_id' => $request->chargebee_plan_id,
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
    }

    public function update(Request $request, Plan $plan)
    {
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
        ]);

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
    }

    public function destroy(Plan $plan)
    {
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
    
    public function getPlansWithFeatures()
    {
        $plans = Plan::with('features')->get();
        return response()->json($plans);
    }
}