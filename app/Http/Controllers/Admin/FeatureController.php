<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    /**
     * Get a list of all features for AJAX requests.
     */
    public function list()
    {
        $features = Feature::where('is_active', true)->get();
        
        return response()->json([
            'success' => true,
            'features' => $features
        ]);
    }

    /**
     * Store a newly created feature via AJAX.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'value' => 'nullable|string',
            'is_active' => 'required|in:true,false,0,1'
        ]);

        $feature = Feature::create([
            'title' => $request->title,
            'value' => $request->value,
            'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feature created successfully',
            'feature' => $feature
        ]);
    }

    /**
     * Update the specified feature via AJAX.
     */
    public function update(Request $request, Feature $feature)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'value' => 'nullable|string',
            'is_active' => 'required|in:true,false,0,1'
        ]);

        $feature->update([
            'title' => $request->title,
            'value' => $request->value,
            'is_active' => filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Feature updated successfully',
            'feature' => $feature
        ]);
    }

    /**
     * Remove the specified feature via AJAX.
     */
    public function destroy(Feature $feature)
    {
        // Check if feature is associated with any plans before deleting
        if ($feature->plans()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'This feature is associated with plans and cannot be deleted.'
            ], 422);
        }

        $feature->delete();

        return response()->json([
            'success' => true,
            'message' => 'Feature deleted successfully'
        ]);
    }
}
