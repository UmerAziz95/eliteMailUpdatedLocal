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
use App\Models\Pool;

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
                    $nextId = 'PPN-' . PoolPanel::getNextAvailableId();
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
     * Archive/Unarchive pool panel
     */
    public function archive(Request $request, $id)
    {
        try {
            $poolPanel = PoolPanel::findOrFail($id);
            
            // Validate the request - accept both boolean and string values
            $validated = $request->validate([
                'is_active' => 'required|in:0,1,true,false'
            ]);
            
            // Convert to boolean
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            
            // If conversion failed, try to convert from string/int
            if ($isActive === null) {
                $isActive = in_array($request->input('is_active'), [1, '1', 'true', true], true);
            }
            
            $action = $isActive ? 'unarchived' : 'archived';
            
            // Update pool panel status
            $poolPanel->is_active = $isActive ? 1 : 0;
            $poolPanel->updated_by = Auth::id();
            $poolPanel->save();
            
            Log::info("Pool Panel {$action} successfully", [
                'pool_panel_id' => $id,
                'is_active' => $poolPanel->is_active,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Pool Panel {$action} successfully",
                'pool_panel' => $poolPanel
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . collect($e->errors())->flatten()->implode(', ')
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pool Panel not found'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Pool Panel Archive Error: ' . $e->getMessage(), [
                'pool_panel_id' => $id,
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive/unarchive pool panel'
            ], 500);
        }
    }

    /**
     * Get pool panel capacity alert data
     */
    public function getCapacityAlert(Request $request)
    {
        try {
            // Get pending pools that require pool panel capacity
            $pendingPools = \App\Models\Pool::where('status', 'pending')
                ->where('is_splitting', 0) // Only get pools that are not currently being split
                ->whereNotNull('total_inboxes')
                ->where('total_inboxes', '>', 0)
                ->orderBy('created_at', 'asc') // Process older pools first
                ->get();
            
            $insufficientSpacePools = [];
            $totalPoolPanelsNeeded = 0;
            $totalInboxes = 0;
            $poolPanelCapacity = env('PANEL_CAPACITY', 1790);
            $maxSplitCapacity = 1790;
            
            Log::info("Pool panel capacity alert calculation started", [
                'pending_pools_count' => $pendingPools->count(),
                'pool_panel_capacity' => $poolPanelCapacity,
                'max_split_capacity' => $maxSplitCapacity
            ]);
            
            foreach ($pendingPools as $pool) {
                // Calculate available space for this pool
                $availableSpace = $this->getAvailablePoolPanelSpace($pool->total_inboxes, $poolPanelCapacity, $maxSplitCapacity);
                
                if ($pool->total_inboxes > $availableSpace) {
                    // Calculate pool panels needed for this pool
                    $poolPanelsNeeded = ceil($pool->total_inboxes / $maxSplitCapacity);
                    
                    $insufficientSpacePools[] = [
                        'id' => $pool->id,
                        'created_at' => $pool->created_at,
                        'plan_name' => $pool->plan->name ?? 'N/A',
                        'domain_url' => $pool->domain_url ?? 'N/A',
                        'total_inboxes' => $pool->total_inboxes,
                        'available_space' => $availableSpace,
                        'pool_panels_needed' => $poolPanelsNeeded,
                        'status' => 'pending'
                    ];
                    
                    $totalPoolPanelsNeeded += $poolPanelsNeeded;
                    $totalInboxes += $pool->total_inboxes;
                    
                    Log::warning("Pool requires additional pool panels", [
                        'pool_id' => $pool->id,
                        'total_inboxes' => $pool->total_inboxes,
                        'available_space' => $availableSpace,
                        'space_deficit' => $pool->total_inboxes - $availableSpace,
                        'pool_panels_needed' => $poolPanelsNeeded
                    ]);
                }
            }
            
            // Adjust total pool panels needed based on available pool panels
            $availablePoolPanelCount = PoolPanel::where('is_active', true)
                ->where('remaining_limit', '>=', $maxSplitCapacity)
                ->count();
            
            $adjustedPoolPanelsNeeded = max(0, $totalPoolPanelsNeeded - $availablePoolPanelCount);
            
            Log::info("Pool panel capacity alert calculation completed", [
                'total_pool_panels_needed_raw' => $totalPoolPanelsNeeded,
                'available_pool_panel_count' => $availablePoolPanelCount,
                'adjusted_pool_panels_needed' => $adjustedPoolPanelsNeeded,
                'insufficient_pools_count' => count($insufficientSpacePools)
            ]);
            
            return response()->json([
                'success' => true,
                'show_alert' => $adjustedPoolPanelsNeeded > 0,
                'total_pool_panels_needed' => $adjustedPoolPanelsNeeded,
                'total_pool_panels_needed_raw' => $totalPoolPanelsNeeded,
                'available_pool_panel_count' => $availablePoolPanelCount,
                'insufficient_pools_count' => count($insufficientSpacePools),
                'insufficient_pools' => $insufficientSpacePools,
                'total_inboxes' => $totalInboxes,
                'pool_panel_capacity' => $poolPanelCapacity,
                'max_split_capacity' => $maxSplitCapacity,
                'last_updated' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting pool panel capacity alert data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error getting capacity alert data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available pool panel space for specific pool size
     */
    private function getAvailablePoolPanelSpace(int $poolSize, int $poolPanelCapacity, int $maxSplitCapacity): int
    {
        // Get active pool panels with remaining capacity
        $availablePoolPanels = PoolPanel::where('is_active', 1)
                                    ->where('remaining_limit', '>', 0)
                                    ->get();
        
        $totalSpace = 0;
        foreach ($availablePoolPanels as $poolPanel) {
            $totalSpace += min($poolPanel->remaining_limit, $maxSplitCapacity);
        }

        Log::info("Available pool panel space calculation", [
            'pool_size' => $poolSize,
            'available_pool_panels_count' => $availablePoolPanels->count(),
            'total_available_space' => $totalSpace
        ]);
        
        return $totalSpace;
    }

    /**
     * Generate a unique pool panel ID
     */
    private function generatePoolPanelId()
    {
        return 'PPN-' . PoolPanel::getNextAvailableId();
    }
}

