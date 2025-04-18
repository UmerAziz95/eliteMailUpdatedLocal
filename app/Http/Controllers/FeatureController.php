<?php

namespace App\Http\Controllers;

use App\Models\Feature;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $features = Feature::all();
        return view('features.index', compact('features'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('features.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'value' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $feature = Feature::create([
            'title' => $request->title,
            'value' => $request->value,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('features.index')
            ->with('success', 'Feature created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Feature $feature)
    {
        return view('features.show', compact('feature'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Feature $feature)
    {
        return view('features.edit', compact('feature'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Feature $feature)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'value' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $feature->update([
            'title' => $request->title,
            'value' => $request->value,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        return redirect()->route('features.index')
            ->with('success', 'Feature updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Feature $feature)
    {
        $feature->delete();

        return redirect()->route('features.index')
            ->with('success', 'Feature deleted successfully.');
    }

    /**
     * Get all active features as JSON (for AJAX requests)
     */
    public function getActiveFeatures()
    {
        $features = Feature::where('is_active', true)->get();
        return response()->json($features);
    }
}
