<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use DataTables;

// Models
use App\Models\PoolPanel;

class PoolPanelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            try {
                // Handle counters request
                if ($request->has('counters')) {
                    $counters = [
                        'total' => PoolPanel::count(),
                        'active' => PoolPanel::where('is_active', 1)->count(),
                        'inactive' => PoolPanel::where('is_active', 0)->count(),
                        'today' => PoolPanel::whereDate('created_at', today())->count(),
                    ];
                    
                    return response()->json(['counters' => $counters]);
                }

                // Handle next ID request
                if ($request->has('next_id')) {
                    // Generate a preview ID similar to what will be created
                    $nextId = $this->generatePoolPanelId();
                    return response()->json(['next_id' => $nextId]);
                }

                // Build query
                $query = PoolPanel::with(['creator', 'updater']);

                // Apply filters
                if ($request->filled('panel_id')) {
                    $query->where('auto_generated_id', 'like', '%' . $request->panel_id . '%');
                }

                if ($request->filled('title')) {
                    $query->where('title', 'like', '%' . $request->title . '%');
                }

                if ($request->filled('status')) {
                    $query->where('is_active', $request->status);
                }

                // Apply ordering
                $order = $request->get('order', 'desc');
                $query->orderBy('created_at', $order);

                // Paginate results
                $perPage = $request->get('per_page', 12);
                $poolPanels = $query->paginate($perPage);

                return response()->json($poolPanels);

            } catch (\Exception $e) {
                Log::error('PoolPanel Index Error: ' . $e->getMessage());
                return response()->json(['message' => 'Something went wrong while fetching pool panels.'], 500);
            }
        }

        return view('admin.pool_panels.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // For offcanvas, we don't need to return anything special
        // The form is already in the index view
        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }
        
        return redirect()->route('admin.pool-panels.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            // Generate auto_generated_id
            $data['auto_generated_id'] = $this->generatePoolPanelId();
            
            // Set default values for limit fields (since we removed them from form)
            $data['limit'] = env('PANEL_CAPACITY', 1790);
            $data['remaining_limit'] = env('PANEL_CAPACITY', 1790);
            $data['used_limit'] = 0;

            // Set creator
            $data['created_by'] = Auth::id();

            $poolPanel = PoolPanel::create($data);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Pool Panel created successfully', 
                    'pool_panel' => $poolPanel
                ], 201);
            }

            return redirect()->route('admin.pool-panels.index')
                ->with('success', 'Pool Panel created successfully');

        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('PoolPanel Store Error: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['message' => 'Failed to create pool panel'], 500);
            }
            return back()->with('error', 'Failed to create pool panel')->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $poolPanel = PoolPanel::with(['creator', 'updater'])->findOrFail($id);
            return view('admin.pool_panels.show', compact('poolPanel'));
        } catch (ModelNotFoundException $e) {
            return redirect()->route('admin.pool-panels.index')
                ->with('error', 'Pool Panel not found');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $poolPanel = PoolPanel::findOrFail($id);
            
            // Return JSON for offcanvas
            if (request()->ajax()) {
                return response()->json([
                    'poolPanel' => $poolPanel
                ]);
            }
            
            return redirect()->route('admin.pool-panels.index');
        } catch (ModelNotFoundException $e) {
            if (request()->ajax()) {
                return response()->json(['message' => 'Pool Panel not found'], 404);
            }
            return redirect()->route('admin.pool-panels.index')
                ->with('error', 'Pool Panel not found');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $poolPanel = PoolPanel::findOrFail($id);

            $data = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'is_active' => 'boolean',
            ]);

            // Set updater
            $data['updated_by'] = Auth::id();

            $poolPanel->update($data);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Pool Panel updated successfully', 
                    'pool_panel' => $poolPanel
                ], 200);
            }

            return redirect()->route('admin.pool-panels.index')
                ->with('success', 'Pool Panel updated successfully');

        } catch (ValidationException $e) {
            if ($request->ajax()) {
                return response()->json(['errors' => $e->errors()], 422);
            }
            return back()->withErrors($e->errors())->withInput();
        } catch (ModelNotFoundException $e) {
            if ($request->ajax()) {
                return response()->json(['message' => 'Pool Panel not found'], 404);
            }
            return redirect()->route('admin.pool-panels.index')
                ->with('error', 'Pool Panel not found');
        } catch (\Exception $e) {
            Log::error('PoolPanel Update Error: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json(['message' => 'Failed to update pool panel'], 500);
            }
            return back()->with('error', 'Failed to update pool panel')->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $poolPanel = PoolPanel::findOrFail($id);
            $poolPanel->delete();

            return response()->json(['message' => 'Pool Panel deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pool Panel not found'], 404);
        } catch (\Exception $e) {
            Log::error('PoolPanel Delete Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to delete pool panel'], 500);
        }
    }

    /**
     * Get pool panel by ID for AJAX requests
     */
    public function getPoolPanel($id)
    {
        try {
            $poolPanel = PoolPanel::findOrFail($id);
            return response()->json($poolPanel, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pool Panel not found'], 404);
        }
    }

    /**
     * Toggle active status
     */
    public function toggleStatus($id)
    {
        try {
            $poolPanel = PoolPanel::findOrFail($id);
            $poolPanel->is_active = !$poolPanel->is_active;
            $poolPanel->updated_by = Auth::id();
            $poolPanel->save();

            return response()->json([
                'message' => 'Pool Panel status updated successfully',
                'status' => $poolPanel->is_active
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Pool Panel not found'], 404);
        } catch (\Exception $e) {
            Log::error('PoolPanel Toggle Status Error: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to update status'], 500);
        }
    }

    /**
     * Generate a unique pool panel ID
     */
    private function generatePoolPanelId()
    {
        // Get the next database ID to make it more predictable
        $lastPoolPanel = PoolPanel::orderBy('id', 'desc')->first();
        $nextDbId = $lastPoolPanel ? $lastPoolPanel->id + 1 : 1;
        
        // Generate ID with format: PP_[NEXT_DB_ID]_[TIMESTAMP]
        return 'PP_' . str_pad($nextDbId, 6, '0', STR_PAD_LEFT);
    }
}
