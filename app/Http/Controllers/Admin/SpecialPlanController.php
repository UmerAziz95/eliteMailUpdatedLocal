<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SpecialPlanController extends Controller
{
    public function index()
    {
        $plans = Plan::where('is_discounted', 3)->with('features')->get();
        $features = Feature::all();
        $getMostlyUsed = Plan::where('is_discounted', 3)
            ->where('is_active', true)
            ->withCount('subscriptions')
            ->orderBy('subscriptions_count', 'desc')
            ->first();
        
        return view('admin.pricing.special-plans', compact('plans', 'features', 'getMostlyUsed'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'duration_type' => 'required|in:days,months,years',
            'features' => 'nullable|array',
            'features.*' => 'exists:features,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $plan = Plan::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'duration' => $request->duration,
            'duration_type' => $request->duration_type,
            'is_discounted' => 3,
            'status' => $request->status ?? 'active'
        ]);

        if ($request->features) {
            $plan->features()->sync($request->features);
        }

        return response()->json([
            'success' => true,
            'message' => 'Special plan created successfully!',
            'plan' => $plan->load('features')
        ]);
    }

    public function show($id)
    {
        $plan = Plan::where('is_discounted', 3)->with('features')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'plan' => $plan
        ]);
    }

    public function update(Request $request, $id)
    {
        $plan = Plan::where('is_discounted', 3)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'required|integer|min:1',
            'duration_type' => 'required|in:days,months,years',
            'features' => 'nullable|array',
            'features.*' => 'exists:features,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $plan->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'duration' => $request->duration,
            'duration_type' => $request->duration_type,
            'status' => $request->status ?? $plan->status
        ]);

        if ($request->has('features')) {
            $plan->features()->sync($request->features ?? []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Special plan updated successfully!',
            'plan' => $plan->load('features')
        ]);
    }

    public function destroy($id)
    {
        $plan = Plan::where('is_discounted', 3)->findOrFail($id);
        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Special plan deleted successfully!'
        ]);
    }

    public function getPlansWithFeatures()
    {
        $plans = Plan::where('is_discounted', 3)->with('features')->get();
        $getMostlyUsed = Plan::where('is_discounted', 3)
            ->where('is_active', true)
            ->withCount('subscriptions')
            ->orderBy('subscriptions_count', 'desc')
            ->first();
        
        return response()->json([
            'success' => true,
            'plans' => $plans,
            'mostlyUsed' => $getMostlyUsed
        ]);
    }
}
