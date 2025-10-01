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

        return view('admin.pools.create', compact('users', 'plans', 'hostingPlatforms', 'sendingPlatforms'));
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
            'domains' => 'required',
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
            
            // Handle domains JSON conversion
            if ($request->has('domains') && is_string($request->domains)) {
                $data['domains'] = json_decode($request->domains, true);
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
            'domains' => 'required',
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
            
            // Handle domains JSON conversion
            if ($request->has('domains') && is_string($request->domains)) {
                $data['domains'] = json_decode($request->domains, true);
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
}
