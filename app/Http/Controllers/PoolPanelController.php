<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use DataTables;

// Models
use App\Models\PoolPanel;
use App\Models\Pool;
use App\Models\PoolPanelSplit;
use App\Models\Configuration;
use App\Services\PoolPanelReassignmentService;

class PoolPanelController extends Controller
{
    public function getNextId(Request $request)
    {
        $allowedProviders = Configuration::getProviderTypes();
        if (empty($allowedProviders)) {
            $allowedProviders = ['Google', 'Microsoft 365', 'Private SMTP'];
        }

        $defaultProviderType = Configuration::get('PROVIDER_TYPE', $allowedProviders[0] ?? 'Google');
        $providerType = $request->query('provider_type', $defaultProviderType);

        if (!in_array($providerType, $allowedProviders, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid provider type selected.',
            ], 422);
        }

        $nextSerial = PoolPanel::getNextSerialForProvider($providerType);
        $capacity = $this->getProviderCapacity($providerType);

        return response()->json([
            'next_id' => 'PPN-' . $nextSerial,
            'panel_sr_no' => $nextSerial,
            'provider_type' => $providerType,
            'capacity' => $capacity,
        ]);
    }

    private function getProviderCapacity(?string $providerType): int
    {
        $fallback = (int) env('PANEL_CAPACITY', 1790);

        $key = match ($providerType) {
            'Microsoft 365' => 'MICROSOFT_365_CAPACITY',
            default => 'GOOGLE_PANEL_CAPACITY',
        };

        return (int) Configuration::get($key, $fallback);
    }

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
     * Provide pool panel listings for asynchronous requests.
     */
    public function getPoolPanelsData(Request $request)
    {
        try {
            $query = PoolPanel::with(['creator', 'poolPanelSplits'])->withCount('poolPanelSplits as total_splits');

            if ($request->filled('panel_id')) {
                $panelId = trim($request->panel_id);
                if (Str::startsWith($panelId, 'PPN-')) {
                    $panelId = substr($panelId, 4);
                }
                $query->where(function ($innerQuery) use ($panelId) {
                    $innerQuery->where('auto_generated_id', 'like', '%' . $panelId . '%')
                        ->orWhere('id', 'like', '%' . $panelId . '%');
                });
            }

            if ($request->filled('title')) {
                $query->where('title', 'like', '%' . $request->title . '%');
            }

            if ($request->filled('status')) {
                $query->where('is_active', (int) $request->status);
            }

            // Add provider type filter
            if ($request->filled('provider_type') && $request->provider_type !== 'all') {
                $query->where('provider_type', $request->provider_type);
            }

            $orderDirection = strtolower($request->get('order', 'desc'));
            if (! in_array($orderDirection, ['asc', 'desc'], true)) {
                $orderDirection = 'desc';
            }
            $query->orderBy('created_at', $orderDirection);

            $perPage = (int) $request->get('per_page', 12);
            if ($perPage <= 0) {
                $perPage = 12;
            }

            $paginatedPanels = $query->paginate($perPage);

            $panelsData = $paginatedPanels->getCollection()->map(function (PoolPanel $panel) {
                $splits = $panel->poolPanelSplits;

                $distinctPoolIds = $splits->pluck('pool_id')->filter()->unique();

                $totalInboxesAssigned = $splits->sum(function (PoolPanelSplit $split) {
                    $domainCount = is_array($split->domains) ? count($split->domains) : 0;
                    return ($split->inboxes_per_domain ?? 0) * $domainCount;
                });

                $totalAssignedSpace = $splits->sum(function (PoolPanelSplit $split) {
                    return $split->assigned_space ?? 0;
                });

                $used = ($panel->limit ?? 0) - ($panel->remaining_limit ?? 0);
                $hasFullCapacity = ($panel->remaining_limit ?? 0) === ($panel->limit ?? 0);

                return [
                    'id' => $panel->id,
                    'auto_generated_id' => $panel->auto_generated_id ?? ('PPN-' . $panel->id),
                    'title' => $panel->title,
                    'description' => $panel->description,
                    'provider_type' => $panel->provider_type,
                    'limit' => $panel->limit,
                    'remaining_limit' => $panel->remaining_limit,
                    'used_limit' => $panel->used_limit,
                    'used' => $used,
                    'is_active' => (bool) $panel->is_active,
                    'total_pools' => $distinctPoolIds->count(),
                    'total_splits' => $panel->total_splits ?? $splits->count(),
                    'total_inboxes_assigned' => $totalInboxesAssigned,
                    'total_assigned_space' => $totalAssignedSpace,
                    'show_edit_delete_buttons' => $hasFullCapacity,
                    'can_edit' => $hasFullCapacity,
                    'can_delete' => $hasFullCapacity,
                    'created_at' => optional($panel->created_at)->toDateTimeString(),
                    'creator' => $panel->creator ? [
                        'id' => $panel->creator->id,
                        'name' => $panel->creator->name,
                    ] : null,
                    'usage_percentage' => ($panel->limit ?? 0) > 0
                        ? round(($used / $panel->limit) * 100, 2)
                        : 0,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $panelsData,
                'pagination' => [
                    'current_page' => $paginatedPanels->currentPage(),
                    'last_page' => $paginatedPanels->lastPage(),
                    'per_page' => $paginatedPanels->perPage(),
                    'total' => $paginatedPanels->total(),
                    'has_more_pages' => $paginatedPanels->hasMorePages(),
                    'from' => $paginatedPanels->firstItem(),
                    'to' => $paginatedPanels->lastItem(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('PoolPanel data fetch error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching pool panels data: ' . $e->getMessage(),
            ], 500);
        }
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
                'provider_type' => 'required|string',
                'is_active' => 'boolean',
            ]);

            // Generate auto_generated_id
            $data['auto_generated_id'] = $this->generatePoolPanelId();
            $data['provider_type'] = $request->provider_type;
            
            // Set default values for limit fields (since we removed them from form)
            $data['limit'] = $request->panel_limit;
            $data['remaining_limit'] = $request->panel_limit;
            $data['used_limit'] = 0;

            // Set creator
            $data['created_by'] = Auth::id();
            $data['pool_panel_sr_no'] = PoolPanel::getNextSerialForProvider($request->provider_type);
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
     * Return pools and splits associated with the given pool panel.
     */
    public function getPoolPanelPools(Request $request, PoolPanel $poolPanel)
    {
        try {
            $poolPanel->load([
                'poolPanelSplits.pool.plan',
                'poolPanelSplits.panel',
                'poolPanelSplits.poolPanel',
            ]);
            $splits = $poolPanel->poolPanelSplits;

            if ($splits->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'pool_panel' => [
                        'id' => $poolPanel->id,
                        'auto_generated_id' => $poolPanel->auto_generated_id ?? ('PPN-' . $poolPanel->id),
                        'title' => $poolPanel->title,
                        'description' => $poolPanel->description,
                        'limit' => $poolPanel->limit,
                        'remaining_limit' => $poolPanel->remaining_limit,
                        'used_limit' => $poolPanel->used_limit,
                    ],
                    'pools' => [],
                ]);
            }

            $groupedSplits = $splits->groupBy('pool_id')->filter(function ($group, $poolId) {
                return ! empty($poolId);
            });

            $poolsData = $groupedSplits->map(function ($poolSplits, $poolId) use ($poolPanel) {
                /** @var PoolPanelSplit|null $firstSplit */
                $firstSplit = $poolSplits->first();
                $pool = $firstSplit?->pool;
                $panelForPool = $poolSplits->map(function (PoolPanelSplit $split) {
                    return $split->panel ?? $split->poolPanel;
                })->filter()->first();

                $splitsData = $poolSplits->map(function (PoolPanelSplit $split) {
                    $domainNames = $split->getDomainNames();
                    $domainCount = count($domainNames);

                    if ($domainCount === 0 && is_array($split->domains)) {
                        $domainCount = count($split->domains);
                        $domainNames = collect($split->domains)->map(function ($domain) {
                            if (is_array($domain)) {
                                return $domain['name']
                                    ?? $domain['domain']
                                    ?? $domain['id']
                                    ?? '';
                            }

                            return (string) $domain;
                        })->filter()->values()->all();
                    }

                    $totalInboxes = ($split->inboxes_per_domain ?? 0) * $domainCount;
                    $assignedSpace = $split->assigned_space ?? 0;
                    $availableSpace = max($totalInboxes - $assignedSpace, 0);

                    return [
                        'id' => $split->id,
                        'inboxes_per_domain' => $split->inboxes_per_domain,
                        'domains_count' => $domainCount,
                        'domain_names' => $domainNames,
                        'domain_details' => $split->getDomainDetails(),
                        'total_inboxes' => $totalInboxes,
                        'assigned_space' => $assignedSpace,
                        'available_space' => $availableSpace,
                        'uploaded_file_path' => $split->uploaded_file_path,
                        'created_at' => optional($split->created_at)->toDateTimeString(),
                        'panel' => $split->panel ? [
                            'id' => $split->panel->id,
                            'auto_generated_id' => $split->panel->auto_generated_id ?? ('PPN-' . $split->panel->id),
                            'title' => $split->panel->title,
                            'is_active' => (bool) $split->panel->is_active,
                        ] : null,
                    ];
                });

                $totalInboxes = $splitsData->sum('total_inboxes');
                $totalAssigned = $splitsData->sum('assigned_space');
                $totalAvailable = $splitsData->sum('available_space');

                return [
                    'pool_id' => $poolId,
                    'pool' => $pool ? [
                        'id' => $pool->id,
                        'plan_name' => optional($pool->plan)->name,
                        'status' => $pool->status_manage_by_admin ?? $pool->status,
                        'total_inboxes' => $pool->total_inboxes,
                        'inboxes_per_domain' => $pool->inboxes_per_domain,
                        'created_at' => optional($pool->created_at)->toDateTimeString(),
                        'prefix_variants' => $pool->prefix_variants,
                        'prefix_variants_details' => $pool->prefix_variants_details,
                        'prefix_variant_1' => $pool->prefix_variant_1,
                        'prefix_variant_2' => $pool->prefix_variant_2,
                        'profile_picture_link' => $pool->profile_picture_link,
                        'email_persona_password' => $pool->email_persona_password,
                        'persona_password' => $pool->persona_password,
                        'email_persona_picture_link' => $pool->email_persona_picture_link,
                        'additional_info' => $pool->additional_info,
                        'master_inbox_email' => $pool->master_inbox_email,
                        'forwarding_url' => $pool->forwarding_url,
                        'hosting_platform' => $pool->hosting_platform,
                        'other_platform' => $pool->other_platform,
                        'platform_login' => $pool->platform_login,
                        'platform_password' => $pool->platform_password,
                        'sending_platform' => $pool->sending_platform,
                        'sequencer_login' => $pool->sequencer_login,
                        'sequencer_password' => $pool->sequencer_password,
                        'backup_codes' => $pool->backup_codes,
                        'domains' => $pool->domains,
                        'provider_type' => $pool->provider_type,
                    ] : null,
                    'panel' => $panelForPool ? [
                        'id' => $panelForPool->id,
                        'auto_generated_id' => $panelForPool->auto_generated_id ?? ('PPN-' . $panelForPool->id),
                        'title' => $panelForPool->title,
                        'is_active' => (bool) $panelForPool->is_active,
                    ] : null,
                    'total_splits' => $splitsData->count(),
                    'total_domains' => $splitsData->sum('domains_count'),
                    'total_inboxes' => $totalInboxes,
                    'assigned_space' => $totalAssigned,
                    'available_space' => max($totalAvailable, 0),
                    'splits' => $splitsData->values(),
                    'other_panel_splits' => $this->getOtherPoolPanelSplits($poolId, $poolPanel->id),
                ];
            })->values();

            return response()->json([
                'success' => true,
                'pool_panel' => [
                    'id' => $poolPanel->id,
                    'auto_generated_id' => $poolPanel->auto_generated_id ?? ('PPN-' . $poolPanel->id),
                    'title' => $poolPanel->title,
                    'description' => $poolPanel->description,
                    'limit' => $poolPanel->limit,
                    'remaining_limit' => $poolPanel->remaining_limit,
                    'used_limit' => $poolPanel->used_limit,
                ],
                'pools' => $poolsData,
            ]);
        } catch (\Exception $e) {
            Log::error('PoolPanel pools fetch error', [
                'pool_panel_id' => $poolPanel->id ?? null,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching pool panel pools: ' . $e->getMessage(),
            ], 500);
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
     * Get available pool panels for reassignment.
     */
    public function getAvailablePoolPanelsForReassignment(int $poolId, int $poolPanelId)
    {
        try {
            $service = new PoolPanelReassignmentService();
            return response()->json(
                $service->getAvailablePoolPanelsForReassignment($poolId, $poolPanelId)
            );
        } catch (\Exception $e) {
            Log::error('Error getting available pool panels for reassignment', [
                'pool_id' => $poolId,
                'pool_panel_id' => $poolPanelId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get available pool panels: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reassign pool panel splits.
     */
    public function reassignPoolPanelSplit(Request $request)
    {
        try {
            $validated = $request->validate([
                'from_pool_panel_id' => 'required|integer|exists:pool_panels,id',
                'to_pool_panel_id' => 'required|integer|exists:pool_panels,id',
                'split_id' => 'nullable|integer|exists:pool_panel_splits,id',
                'pool_id' => 'required|integer|exists:pools,id',
                'reason' => 'nullable|string|max:500',
            ]);

            $service = new PoolPanelReassignmentService();
            $result = $service->reassignPoolPanelSplit(
                (int) $validated['from_pool_panel_id'],
                (int) $validated['to_pool_panel_id'],
                $validated['split_id'] ?? null,
                auth()->id(),
                $validated['reason'] ?? null
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Pool panel reassignment failed',
            ], 400);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . collect($e->errors())->flatten()->implode(', '),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Pool panel reassignment failed', [
                'payload' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Pool panel reassignment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pool panel reassignment history for a pool.
     */
    public function getReassignmentHistory(int $poolId)
    {
        try {
            $service = new PoolPanelReassignmentService();
            return response()->json($service->getReassignmentHistory($poolId));
        } catch (\Exception $e) {
            Log::error('Error getting pool panel reassignment history', [
                'pool_id' => $poolId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get reassignment history: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pool panel capacity alert data
     */
    public function getCapacityAlert(Request $request)
    {
        try {
            // Provider-aware capacities
            $providerType = Configuration::get('PROVIDER_TYPE', env('PROVIDER_TYPE', 'Google'));
            $poolPanelCapacity = strtolower($providerType) === 'microsoft 365'
                ? Configuration::get('MICROSOFT_365_CAPACITY', env('MICROSOFT_365_CAPACITY', env('PANEL_CAPACITY', 1790)))
                : Configuration::get('GOOGLE_PANEL_CAPACITY', env('GOOGLE_PANEL_CAPACITY', env('PANEL_CAPACITY', 1790)));
            $maxSplitCapacity = strtolower($providerType) === 'microsoft 365'
                ? Configuration::get('MICROSOFT_365_MAX_SPLIT_CAPACITY', env('MICROSOFT_365_MAX_SPLIT_CAPACITY', env('MAX_SPLIT_CAPACITY', 358)))
                : Configuration::get('GOOGLE_MAX_SPLIT_CAPACITY', env('GOOGLE_MAX_SPLIT_CAPACITY', env('MAX_SPLIT_CAPACITY', 358)));
            $enableMaxSplit = strtolower($providerType) === 'microsoft 365'
                ? Configuration::get('ENABLE_MICROSOFT_365_MAX_SPLIT_CAPACITY', env('ENABLE_MICROSOFT_365_MAX_SPLIT_CAPACITY', true))
                : Configuration::get('ENABLE_GOOGLE_MAX_SPLIT_CAPACITY', env('ENABLE_GOOGLE_MAX_SPLIT_CAPACITY', true));
            if (! $enableMaxSplit || $maxSplitCapacity <= 0) {
                $maxSplitCapacity = $poolPanelCapacity;
            }

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
            
            Log::info("Pool panel capacity alert calculation started", [
                'pending_pools_count' => $pendingPools->count(),
                'pool_panel_capacity' => $poolPanelCapacity,
                'max_split_capacity' => $maxSplitCapacity
            ]);
            
            foreach ($pendingPools as $pool) {
                // Calculate available space for this pool
                $inboxesPerDomain = $pool->inboxes_per_domain ?? 1;
                $totalInboxes += $pool->total_inboxes ?? 0;

                $availableSpace = $this->getAvailablePoolPanelSpace(
                    $pool->total_inboxes,
                    $inboxesPerDomain,
                    $poolPanelCapacity,
                    $maxSplitCapacity,
                    $providerType
                );
                
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
                ->where('limit', $poolPanelCapacity)
                ->where('provider_type', $providerType)
                ->where('remaining_limit', '>=', $maxSplitCapacity)
                ->count();

            $availablePanels = PoolPanel::where('is_active', true)
                ->where('limit', $poolPanelCapacity)
                ->where('provider_type', $providerType)
                ->where('remaining_limit', '>', 0)
                ->get();

            $totalSpaceAvailable = 0;
            foreach ($availablePanels as $panel) {
                $totalSpaceAvailable += min($panel->remaining_limit, $maxSplitCapacity);
            }

            $remainingAfterAvailable = max(0, $totalInboxes - $totalSpaceAvailable);
            $adjustedPoolPanelsNeeded = (int) max(0, ceil($remainingAfterAvailable / $maxSplitCapacity));
            
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
                'total_space_available' => $totalSpaceAvailable,
                'remaining_after_available' => $remainingAfterAvailable,
                'pool_panel_capacity' => $poolPanelCapacity,
                'max_split_capacity' => $maxSplitCapacity,
                'provider_type' => $providerType,
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
    private function getAvailablePoolPanelSpace(
        int $poolSize,
        int $inboxesPerDomain,
        int $poolPanelCapacity,
        int $maxSplitCapacity,
        string $providerType
    ): int {
        // For larger pools, prefer full-capacity panels
        if ($poolSize >= $poolPanelCapacity) {
            $fullCapacityPanels = PoolPanel::where('is_active', 1)
                ->where('limit', $poolPanelCapacity)
                ->where('provider_type', $providerType)
                ->where('remaining_limit', '>=', $inboxesPerDomain)
                ->get();

            $fullCapacitySpace = 0;
            foreach ($fullCapacityPanels as $panel) {
                $fullCapacitySpace += min($panel->remaining_limit, $maxSplitCapacity);
            }

            Log::info("Available pool panel space (large pool)", [
                'pool_size' => $poolSize,
                'available_pool_panels_count' => $fullCapacityPanels->count(),
                'total_available_space' => $fullCapacitySpace
            ]);

            return $fullCapacitySpace;
        }

        // Smaller pools: any active panel with room for at least one domain
        $availablePoolPanels = PoolPanel::where('is_active', 1)
            ->where('limit', $poolPanelCapacity)
            ->where('provider_type', $providerType)
            ->where('remaining_limit', '>=', $inboxesPerDomain)
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

    /**
     * Retrieve splits from other pool panels for the given pool.
     *
     * @return \Illuminate\Support\Collection<int,array<string,mixed>>
     */
    private function getOtherPoolPanelSplits(int $poolId, int $currentPoolPanelId)
    {
        $otherSplits = PoolPanelSplit::with(['panel', 'pool'])
            ->where('pool_id', $poolId)
            ->where('pool_panel_id', '!=', $currentPoolPanelId)
            ->get();

        if ($otherSplits->isEmpty()) {
            return collect();
        }

        return $otherSplits
            ->groupBy('pool_panel_id')
            ->map(function ($panelSplits) {
                /** @var PoolPanelSplit $firstSplit */
                $firstSplit = $panelSplits->first();
                $panel = $firstSplit?->panel ?? $firstSplit?->poolPanel;

                $splits = $panelSplits->map(function (PoolPanelSplit $split) {
                    $domainDetails = $split->getDomainDetails();
                    $domainCount = count($domainDetails);
                    if ($domainCount === 0) {
                        $domainCount = count($split->getDomainNames());
                    }
                    $totalInboxes = ($split->inboxes_per_domain ?? 0) * $domainCount;

                    return [
                        'id' => $split->id,
                        'inboxes_per_domain' => $split->inboxes_per_domain,
                        'domains_count' => $domainCount,
                        'domain_names' => $split->getDomainNames(),
                        'domain_details' => $domainDetails,
                        'total_inboxes' => $totalInboxes,
                        'assigned_space' => $split->assigned_space ?? 0,
                        'available_space' => max(($split->assigned_space ?? 0) - $totalInboxes, 0),
                        'created_at' => optional($split->created_at)->toDateTimeString(),
                    ];
                })->values();

                $totalDomains = $splits->sum('domains_count');
                $totalInboxes = $splits->sum('total_inboxes');

                return [
                    'panel' => $panel ? [
                        'id' => $panel->id,
                        'auto_generated_id' => $panel->auto_generated_id ?? ('PPN-' . $panel->id),
                        'title' => $panel->title,
                        'is_active' => (bool) $panel->is_active,
                        'inboxes_per_domain' => $panel->inboxes_per_domain ?? null,
                    ] : null,
                    'total_domains' => $totalDomains,
                    'total_inboxes' => $totalInboxes,
                    'total_splits' => $splits->count(),
                    'splits' => $splits,
                ];
            })
            ->values();
    }
}
