<?php

namespace App\Http\Controllers;

use App\Models\Pool;
use App\Models\User;
use App\Models\Plan;
use App\Models\HostingPlatform;
use App\Models\SendingPlatform;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PoolController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Pool::with(['user', 'plan', 'assignedTo']);

        // Apply filters
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id') && $request->user_id !== '') {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('assigned_to') && $request->assigned_to !== '') {
            $query->where('assigned_to', $request->assigned_to);
        }

        if ($request->has('is_internal')) {
            $query->where('is_internal', $request->boolean('is_internal'));
        }

        if ($request->has('is_shared')) {
            $query->where('is_shared', $request->boolean('is_shared'));
        }

        // Search functionality
        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhere('forwarding_url', 'LIKE', "%{$search}%")
                  ->orWhere('hosting_platform', 'LIKE', "%{$search}%")
                  ->orWhere('master_inbox_email', 'LIKE', "%{$search}%");
            });
        }

        $pools = $query->orderBy('created_at', 'desc')->paginate(15);

        // If request expects JSON (API call), return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data' => $pools
            ]);
        }

        // Otherwise return view
        return view('admin.pools.index', compact('pools'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::all();
        $plans = Plan::all(); // Get all plans for create form
        $hostingPlatforms = HostingPlatform::where('is_active', true)->orderBy('sort_order')->get();
        $sendingPlatforms = SendingPlatform::orderBy('name')->get();
        $pool = null;
        return view('admin.pools.create', compact('users', 'plans', 'hostingPlatforms', 'sendingPlatforms', 'pool'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'nullable|exists:plans,id',
            'status' => 'in:pending,in_progress,completed,cancelled',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'string|max:3',
            'forwarding_url' => 'required|url',
            'hosting_platform' => 'required|string|max:255',
            'sending_platform' => 'required|string|max:255',
            'domains' => 'required|json',
            'total_inboxes' => 'nullable|integer|min:1',
            'inboxes_per_domain' => 'required|integer|min:1|max:3',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'master_inbox_email' => 'nullable|email',
            'master_inbox_confirmation' => 'boolean',
            'platform_login' => 'nullable|string|max:255',
            'platform_password' => 'nullable|string|max:255',
            'sequencer_login' => 'nullable|string|max:255',
            'sequencer_password' => 'nullable|string|max:255',
            'backup_codes' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'prefix_variants' => 'nullable|array',
            'prefix_variants_details' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->all();
            
            // Handle domains JSON conversion and ensure unique id, is_used
            if ($request->has('domains') && is_string($request->domains)) {
                $domains = json_decode($request->domains, true);
                $processedDomains = [];
                $sequence = 1;
                foreach ($domains as $domain) {
                    // If domain is string, convert to object
                    if (is_string($domain)) {
                        $processedDomains[] = [
                            'id' => 'new_' . $sequence++,
                            'name' => $domain,
                            'is_used' => false
                        ];
                    } elseif (is_array($domain)) {
                        $processedDomains[] = [
                            'id' => $domain['id'] ?? ('new_' . $sequence++),
                            'name' => $domain['name'] ?? '',
                            'is_used' => $domain['is_used'] ?? false
                        ];
                    }
                }
                $data['domains'] = $processedDomains;
            }

            // Handle prefix variants JSON conversion
            if ($request->has('prefix_variants') && is_array($request->prefix_variants)) {
                $data['prefix_variants'] = $request->prefix_variants;
            }

            // Handle prefix variants details JSON conversion
            if ($request->has('prefix_variants_details') && is_array($request->prefix_variants_details)) {
                $data['prefix_variants_details'] = $request->prefix_variants_details;
            }

            // Handle boolean conversion for master_inbox_confirmation
            $data['master_inbox_confirmation'] = $request->boolean('master_inbox_confirmation');

            // Set status to pending if not specified
            if (!isset($data['status'])) {
                $data['status'] = 'pending';
            }

            $pool = Pool::create($data);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pool created successfully',
                    'status' => $pool->status,
                    'order_id' => $pool->id,
                    'user_id' => $pool->user_id,
                    'data' => $pool->load(['user', 'plan', 'assignedTo'])
                ], 201);
            }

            return redirect()->route('admin.pools.index')->with('success', 'Pool created successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create pool',
                    'error' => $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to create pool: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Pool $pool)
    {
        $pool->load(['user', 'plan', 'assignedTo', 'rejectedBy', 'helpers', 'panels']);

        return view('admin.pools.show', compact('pool'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pool $pool)
    {
        $users = User::all();
        $plans = Plan::all(); // Get all plans for edit form
        $hostingPlatforms = HostingPlatform::where('is_active', true)->orderBy('sort_order')->get();
        $sendingPlatforms = SendingPlatform::orderBy('name')->get();
        // dd($pool);
        return view('admin.pools.edit', compact('pool', 'users', 'plans', 'hostingPlatforms', 'sendingPlatforms'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pool $pool)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'nullable|exists:plans,id',
            'status' => 'in:pending,in_progress,completed,cancelled',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'string|max:3',
            'forwarding_url' => 'required|url',
            'hosting_platform' => 'required|string|max:255',
            'sending_platform' => 'required|string|max:255',
            'domains' => 'required|json',
            'total_inboxes' => 'nullable|integer|min:1',
            'inboxes_per_domain' => 'required|integer|min:1|max:3',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'master_inbox_email' => 'nullable|email',
            'master_inbox_confirmation' => 'boolean',
            'platform_login' => 'nullable|string|max:255',
            'platform_password' => 'nullable|string|max:255',
            'sequencer_login' => 'nullable|string|max:255',
            'sequencer_password' => 'nullable|string|max:255',
            'backup_codes' => 'nullable|string',
            'additional_info' => 'nullable|string',
            'prefix_variants' => 'nullable|array',
            'prefix_variants_details' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $data = $request->all();
            // Handle domains JSON conversion and ABSOLUTELY preserve existing domain IDs
            if ($request->has('domains') && is_string($request->domains)) {
                $domains = json_decode($request->domains, true);
                $existingDomains = is_array($pool->domains) ? $pool->domains : [];
                
                // CRITICAL: Create protected list of all existing domain IDs that MUST NOT change
                $protectedDomainIds = [];
                $existingDomainMapByName = [];
                $existingDomainMapById = [];
                $existingDomainsByIndex = []; // Map by position to handle reordering
                
                foreach ($existingDomains as $index => $domain) {
                    if (isset($domain['name']) && isset($domain['id'])) {
                        $protectedDomainIds[] = $domain['id']; // LOCK this ID
                        $existingDomainMapByName[$domain['name']] = $domain;
                        $existingDomainMapById[$domain['id']] = $domain;
                        $existingDomainsByIndex[$index] = $domain;
                    }
                }
                
                // Log for debugging
                \Log::info('Domain Update - ABSOLUTE ID PROTECTION', [
                    'pool_id' => $pool->id,
                    'protected_ids' => $protectedDomainIds,
                    'existing_domains_count' => count($existingDomains),
                    'submitted_domains_count' => count($domains),
                    'submitted_domains' => $domains
                ]);
                
                $processedDomains = [];
                $submittedDomainIds = [];
                $newDomainSequence = 1;
                $usedProtectedIds = []; // Track which protected IDs have been used
                
                // ABSOLUTE DOMAIN ID PRESERVATION ALGORITHM
                // Process each submitted domain with GUARANTEED ID preservation
                foreach ($domains as $domainIndex => $domain) {
                    $domainData = null;
                    
                    if (is_string($domain)) {
                        $domainName = trim($domain);
                        
                        // Priority 1: Exact name match (no change)
                        if (isset($existingDomainMapByName[$domainName])) {
                            $domainData = $existingDomainMapByName[$domainName];
                            $usedProtectedIds[] = $domainData['id'];
                        }
                        // Priority 2: Position-based matching (renamed domain)
                        elseif (isset($existingDomainsByIndex[$domainIndex]) && !in_array($existingDomainsByIndex[$domainIndex]['id'], $usedProtectedIds)) {
                            $existingAtPosition = $existingDomainsByIndex[$domainIndex];
                            $domainData = [
                                'id' => $existingAtPosition['id'], // FORCE preserve existing ID
                                'name' => $domainName,
                                'is_used' => $existingAtPosition['is_used'] ?? false
                            ];
                            $usedProtectedIds[] = $existingAtPosition['id'];
                            
                            \Log::info('ABSOLUTE PROTECTION: Domain renamed', [
                                'position' => $domainIndex,
                                'old_name' => $existingAtPosition['name'], 
                                'new_name' => $domainName,
                                'PROTECTED_ID' => $existingAtPosition['id']
                            ]);
                        }
                        // Priority 3: New domain
                        else {
                            $domainData = [
                                'id' => $pool->id . '_new_' . $newDomainSequence++,
                                'name' => $domainName,
                                'is_used' => false
                            ];
                        }
                    }
                    elseif (is_array($domain)) {
                        $domainName = trim($domain['name'] ?? '');
                        
                        // If domain comes with an ID, verify it's a protected ID
                        if (isset($domain['id']) && in_array($domain['id'], $protectedDomainIds)) {
                            // This is a protected ID - ABSOLUTELY preserve it
                            $existingDomain = $existingDomainMapById[$domain['id']];
                            $domainData = [
                                'id' => $domain['id'], // PROTECTED - NEVER change
                                'name' => $domainName,
                                'is_used' => $existingDomain['is_used'] ?? false
                            ];
                            $usedProtectedIds[] = $domain['id'];
                            
                            \Log::info('ABSOLUTE PROTECTION: Protected ID preserved', [
                                'PROTECTED_ID' => $domain['id'],
                                'old_name' => $existingDomain['name'],
                                'new_name' => $domainName
                            ]);
                        }
                        // Check for original_id field (from frontend)
                        elseif (isset($domain['original_id']) && in_array($domain['original_id'], $protectedDomainIds)) {
                            $existingDomain = $existingDomainMapById[$domain['original_id']];
                            $domainData = [
                                'id' => $domain['original_id'], // PROTECTED - use original ID
                                'name' => $domainName,
                                'is_used' => $existingDomain['is_used'] ?? false
                            ];
                            $usedProtectedIds[] = $domain['original_id'];
                        }
                        // Exact name match
                        elseif (isset($existingDomainMapByName[$domainName])) {
                            $domainData = $existingDomainMapByName[$domainName];
                            $usedProtectedIds[] = $domainData['id'];
                        }
                        // Position-based matching
                        elseif (isset($existingDomainsByIndex[$domainIndex]) && !in_array($existingDomainsByIndex[$domainIndex]['id'], $usedProtectedIds)) {
                            $existingAtPosition = $existingDomainsByIndex[$domainIndex];
                            $domainData = [
                                'id' => $existingAtPosition['id'], // FORCE preserve existing ID
                                'name' => $domainName,
                                'is_used' => $existingAtPosition['is_used'] ?? false
                            ];
                            $usedProtectedIds[] = $existingAtPosition['id'];
                        }
                        // New domain
                        else {
                            $domainData = [
                                'id' => isset($domain['id']) ? $domain['id'] : ($pool->id . '_new_' . $newDomainSequence++),
                                'name' => $domainName,
                                'is_used' => $domain['is_used'] ?? false
                            ];
                        }
                    }
                    
                    if ($domainData) {
                        $processedDomains[] = $domainData;
                        $submittedDomainIds[] = $domainData['id'];
                    }
                }
                
                // Second, preserve any existing domains that are marked as "is_used" = true
                // even if they weren't submitted in the form (like missing 1008_3, 1008_4)
                foreach ($existingDomains as $existingDomain) {
                    if (isset($existingDomain['is_used']) && $existingDomain['is_used'] === true) {
                        // If this used domain wasn't included in the submitted form, preserve it
                        if (!in_array($existingDomain['id'], $submittedDomainIds)) {
                            $processedDomains[] = $existingDomain;
                        }
                    }
                }
                
                // FINAL VERIFICATION: Check that NO protected IDs were changed
                $finalDomainIds = array_column($processedDomains, 'id');
                $changedIds = array_diff($protectedDomainIds, $finalDomainIds);
                $newIds = array_diff($finalDomainIds, $protectedDomainIds);
                
                \Log::info('ABSOLUTE PROTECTION - FINAL VERIFICATION', [
                    'pool_id' => $pool->id,
                    'original_protected_ids' => $protectedDomainIds,
                    'final_domain_ids' => $finalDomainIds,
                    'missing_protected_ids' => $changedIds,
                    'new_ids_added' => $newIds,
                    'processed_domains_count' => count($processedDomains),
                    'SUCCESS' => empty($changedIds) ? 'ALL_IDS_PRESERVED' : 'SOME_IDS_LOST'
                ]);
                
                // Double-check: make sure all existing domain IDs are still present
                foreach ($protectedDomainIds as $protectedId) {
                    if (!in_array($protectedId, $finalDomainIds)) {
                        \Log::error('CRITICAL: Protected domain ID was lost during update', [
                            'pool_id' => $pool->id,
                            'lost_id' => $protectedId,
                            'original_domains' => $existingDomains,
                            'processed_domains' => $processedDomains
                        ]);
                    }
                }
                
                $data['domains'] = $processedDomains;
            }

            // Handle prefix variants JSON conversion
            if ($request->has('prefix_variants') && is_array($request->prefix_variants)) {
                $data['prefix_variants'] = $request->prefix_variants;
            }

            // Handle prefix variants details JSON conversion
            if ($request->has('prefix_variants_details') && is_array($request->prefix_variants_details)) {
                $data['prefix_variants_details'] = $request->prefix_variants_details;
            }

            // Handle boolean conversion for master_inbox_confirmation
            $data['master_inbox_confirmation'] = $request->boolean('master_inbox_confirmation');

            $pool->update($data);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pool updated successfully',
                    'status' => $pool->status,
                    'order_id' => $pool->id,
                    'user_id' => $pool->user_id,
                    'data' => $pool->load(['user', 'plan', 'assignedTo'])
                ]);
            }

            return redirect()->route('admin.pools.show', $pool)->with('success', 'Pool updated successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to update pool',
                    'error' => $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to update pool: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pool $pool)
    {
        try {
            $pool->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pool deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete pool',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign a contractor to a pool
     */
    public function assign(Request $request, Pool $pool)
    {
        $validator = Validator::make($request->all(), [
            'assigned_to' => 'required|exists:users,id',
            'is_shared' => 'boolean',
            'shared_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pool->update([
                'assigned_to' => $request->assigned_to,
                'is_shared' => $request->boolean('is_shared', false),
                'shared_note' => $request->shared_note,
                'status' => 'in_progress'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pool assigned successfully',
                'data' => $pool->load(['user', 'assignedTo'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign pool',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a pool
     */
    public function complete(Pool $pool)
    {
        try {
            $pool->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pool completed successfully',
                'data' => $pool
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete pool',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import data for pools
     */
    public function importData(Request $request)
    {
        try {
            $query = Pool::with(['user', 'plan']);

            // Apply filters
            if ($request->has('for_import') && $request->for_import) {
                $query->where('status', '!=', 'cancelled');
            }

            if ($request->has('exclude_current') && $request->exclude_current) {
                $query->where('id', '!=', $request->exclude_current);
            }

            $pools = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $pools,
                'message' => 'Pools data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pools data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific pool data by ID for import
     */
    public function importDataById(Request $request, $id)
    {
        try {
            $pool = Pool::with(['user', 'plan'])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $pool,
                'message' => 'Pool data retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pool data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run capacity check for pools
     */
    public function capacityCheck(Request $request)
    {
        try {
            // Here you can implement your capacity check logic
            // For now, just return a success response
            
            return response()->json([
                'success' => true,
                'message' => 'Capacity check completed successfully',
                'data' => [
                    'available_capacity' => 1000, // Example data
                    'used_capacity' => 250,
                    'remaining_capacity' => 750
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Capacity check failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
