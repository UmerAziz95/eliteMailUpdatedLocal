<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Plan;
use App\Models\Pool;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\Configuration;
use App\Models\HostingPlatform;
use App\Models\SendingPlatform;
use Illuminate\Support\Facades\DB;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use App\Services\PoolSplitResetService;
use Illuminate\Support\Facades\Validator;
use App\Services\PoolSplitCapacityService;
use App\Services\ManualPanelAssignmentService;
use App\Models\PoolPanel;

class PoolController extends Controller
{
    /**
     * Get start and end dates for domain warming period
     *
     * @return array ['start_date' => string, 'end_date' => string]
     */
    private function getDomainWarmingDates()
    {
        $startDate = Carbon::now();
        $warmingPeriodDays = (int) Configuration::get('POOL_WARMING_PERIOD', 21);
        $endDate = $startDate->copy()->addDays($warmingPeriodDays);

        return [
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d')
        ];
    }

    /**
     * Build prefix_statuses object for a domain based on inboxes_per_domain
     * Each prefix variant gets its own status, start_date, and end_date
     *
     * @param int $inboxesPerDomain Number of prefix variants (1, 2, or 3)
     * @param array|null $existingPrefixStatuses Existing prefix statuses to preserve
     * @param array|null $warmingDates Default warming dates if not provided
     * @return array
     */
    private function buildPrefixStatuses(int $inboxesPerDomain, ?array $existingPrefixStatuses = null, ?array $warmingDates = null): array
    {
        $warmingDates = $warmingDates ?? $this->getDomainWarmingDates();
        $prefixStatuses = [];

        for ($i = 1; $i <= $inboxesPerDomain; $i++) {
            $prefixKey = "prefix_variant_{$i}";

            // Preserve existing status if available, otherwise use defaults
            if ($existingPrefixStatuses && isset($existingPrefixStatuses[$prefixKey])) {
                $prefixStatuses[$prefixKey] = [
                    'status' => $existingPrefixStatuses[$prefixKey]['status'] ?? 'warming',
                    'start_date' => $existingPrefixStatuses[$prefixKey]['start_date'] ?? $warmingDates['start_date'],
                    'end_date' => $existingPrefixStatuses[$prefixKey]['end_date'] ?? $warmingDates['end_date']
                ];
            } else {
                $prefixStatuses[$prefixKey] = [
                    'status' => 'warming',
                    'start_date' => $warmingDates['start_date'],
                    'end_date' => $warmingDates['end_date']
                ];
            }
        }

        return $prefixStatuses;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Handle DataTable AJAX request
        if ($request->has('datatable')) {
            return $this->getDataTableData($request);
        }

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
     * Apply domain-status-based filtering so pools can appear in multiple tabs.
     * Checks both old domain-level status and new prefix_statuses format.
     */
    private function applyDomainStatusFilter($query, string $statusFilter): void
    {
        $statusFilter = strtolower($statusFilter);
        $poolTable = (new Pool())->getTable();

        // Old format: domains[*].status
        $jsonStatusesPath = "JSON_EXTRACT({$poolTable}.domains, '$[*].status')";

        // New format: search in domains[*].prefix_statuses.*.status (all prefix variants)
        // We need to check if any prefix_statuses contains the target status
        $prefixStatusesPath = "JSON_EXTRACT({$poolTable}.domains, '$[*].prefix_statuses')";

        if ($statusFilter === 'warming') {
            $query->where(function ($q) use ($jsonStatusesPath, $prefixStatusesPath, $poolTable) {
                // Check old format: domain-level status
                $q->whereRaw("JSON_SEARCH({$jsonStatusesPath}, 'one', ?) IS NOT NULL", ['warming'])
                    // Check new format: any prefix_statuses have 'warming' status
                    ->orWhereRaw("JSON_SEARCH({$prefixStatusesPath}, 'one', ?) IS NOT NULL", ['warming'])
                    // Fallback: admin-set status
                    ->orWhereNull("{$poolTable}.status_manage_by_admin")
                    ->orWhere("{$poolTable}.status_manage_by_admin", 'warming');
            });
        } elseif ($statusFilter === 'available') {
            $query->where(function ($q) use ($jsonStatusesPath, $prefixStatusesPath, $poolTable) {
                // Check old format: domain-level status
                $q->whereRaw("JSON_SEARCH({$jsonStatusesPath}, 'one', ?) IS NOT NULL", ['available'])
                    // Check new format: any prefix_statuses have 'available' status
                    ->orWhereRaw("JSON_SEARCH({$prefixStatusesPath}, 'one', ?) IS NOT NULL", ['available'])
                    // Fallback: admin-set status
                    ->orWhere("{$poolTable}.status_manage_by_admin", 'available');
            });
        }
    }

    /**
     * Handle DataTable AJAX requests
     */
    private function getDataTableData(Request $request)
    {
        try {
            $query = Pool::with(['user', 'assignedTo']);

            // Handle status filtering for tabs
            if ($request->has('status_filter')) {
                $this->applyDomainStatusFilter($query, $request->status_filter);
            }

            // Handle DataTable search
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('id', 'LIKE', "%{$search}%")
                        ->orWhere('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('forwarding_url', 'LIKE', "%{$search}%")
                        ->orWhere('hosting_platform', 'LIKE', "%{$search}%")
                        ->orWhere('sending_platform', 'LIKE', "%{$search}%")
                        ->orWhere('master_inbox_email', 'LIKE', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'LIKE', "%{$search}%");
                        })
                        ->orWhereHas('assignedTo', function ($assignedQuery) use ($search) {
                            $assignedQuery->where('name', 'LIKE', "%{$search}%");
                        });
                });
            }

            // Handle column sorting
            if ($request->has('order')) {
                $columnIndex = $request->order[0]['column'];
                $sortDirection = $request->order[0]['dir'];

                $columns = [
                    0 => 'id',
                    1 => 'user.name',
                    3 => 'status',
                    4 => 'hosting_platform',
                    5 => 'sending_platform',
                    6 => 'total_inboxes',
                    7 => 'assignedTo.name',
                    9 => 'created_at'
                ];

                if (isset($columns[$columnIndex])) {
                    $column = $columns[$columnIndex];

                    if (strpos($column, '.') !== false) {
                        // Handle relationship sorting
                        $relation = explode('.', $column);
                        $query->join($relation[0] === 'user' ? 'users' : 'users as assigned_users', function ($join) use ($relation) {
                            if ($relation[0] === 'user') {
                                $join->on('pools.user_id', '=', 'users.id');
                            } else {
                                $join->on('pools.assigned_to', '=', 'assigned_users.id');
                            }
                        })->orderBy($relation[0] === 'user' ? 'users.name' : 'assigned_users.name', $sortDirection);
                    } else {
                        $query->orderBy($column, $sortDirection);
                    }
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }

            // Get total count before pagination
            $totalRecords = Pool::count();

            // Clone query for counting filtered records (without pagination)
            $countQuery = clone $query;
            $filteredRecords = $countQuery->count();

            // Handle pagination
            $start = $request->start ?? 0;
            $length = $request->length ?? 25;

            $pools = $query->skip($start)->take($length)->get();

            // Format data for DataTable
            $data = $pools->map(function ($pool) {
                return [
                    'id' => $pool->id,
                    'user' => [
                        'name' => $pool->user->name ?? 'N/A'
                    ],
                    'first_name' => $pool->first_name,
                    'last_name' => $pool->last_name,
                    'status' => $pool->status,
                    'status_manage_by_admin' => $pool->status_manage_by_admin ?? 'warming',
                    'hosting_platform' => $pool->hosting_platform,
                    'sending_platform' => $pool->sending_platform,
                    'total_inboxes' => $pool->total_inboxes,
                    'assigned_to_name' => $pool->assignedTo->name ?? null,
                    'is_internal' => $pool->is_internal,
                    'is_shared' => $pool->is_shared,
                    'created_at' => $pool->created_at->toISOString(),
                ];
            })->toArray();

            return response()->json([
                'draw' => intval($request->draw),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('DataTable Error: ' . $e->getMessage());

            return response()->json([
                'draw' => intval($request->draw ?? 0),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => 'An error occurred while loading data.'
            ], 500);
        }
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
        // Determine if this is SMTP mode
        $isSmtpMode = $request->boolean('smtp_mode');

        // Build validation rules based on mode
        $rules = [
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'nullable|exists:plans,id',
            'status' => 'in:pending,in_progress,completed,cancelled',
            'status_manage_by_admin' => 'nullable|in:warming,available',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'string|max:3',
            'hosting_platform' => 'required|string|max:255',
            'sending_platform' => 'required|string|max:255',
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
            'purchase_date' => 'required|date',
        ];

        // Add mode-specific validation rules
        if ($isSmtpMode) {
            // SMTP mode: require SMTP-specific fields, domains/inboxes come from CSV
            $rules['smtp_provider_url'] = 'required|url|max:255';
            $rules['smtp_accounts_data'] = 'required|json';
            $rules['domains'] = 'nullable';
            $rules['inboxes_per_domain'] = 'nullable|integer|min:1';
        } else {
            // Standard mode: require domains and inboxes_per_domain
            $rules['domains'] = 'required|json';
            $rules['total_inboxes'] = 'nullable|integer|min:1';
            $rules['inboxes_per_domain'] = 'required|integer|min:1|max:3';
        }

        $validator = Validator::make($request->all(), $rules);

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

            // Handle SMTP mode: process smtp_accounts_data
            if ($isSmtpMode && $request->has('smtp_accounts_data')) {
                $smtpData = json_decode($request->smtp_accounts_data, true);

                if ($smtpData && isset($smtpData['accounts'])) {
                    \Log::info('Creating SMTP Pool - Processing SMTP accounts data', [
                        'total_accounts' => count($smtpData['accounts']),
                        'unique_domains' => $smtpData['unique_domains'] ?? 0
                    ]);

                    // Build domains array from SMTP accounts
                    $domainsFromCsv = [];
                    $domainPrefixes = [];
                    $warmingDates = $this->getDomainWarmingDates();

                    // Group accounts by domain
                    foreach ($smtpData['accounts'] as $account) {
                        $domain = $account['domain'] ?? '';
                        $prefix = $account['prefix'] ?? '';

                        if (!isset($domainPrefixes[$domain])) {
                            $domainPrefixes[$domain] = [];
                        }
                        if (!in_array($prefix, $domainPrefixes[$domain])) {
                            $domainPrefixes[$domain][] = $prefix;
                        }
                    }

                    // Build domains array with prefix_statuses
                    $sequence = 1;
                    foreach ($domainPrefixes as $domainName => $prefixes) {
                        $prefixStatuses = [];
                        $prefixIndex = 1;

                        foreach ($prefixes as $prefix) {
                            $prefixKey = "prefix_variant_{$prefixIndex}";
                            $prefixStatuses[$prefixKey] = [
                                'status' => 'warming',
                                'start_date' => $warmingDates['start_date'],
                                'end_date' => $warmingDates['end_date'],
                                'prefix_value' => $prefix  // Store actual prefix value from CSV
                            ];
                            $prefixIndex++;
                        }

                        $domainsFromCsv[] = [
                            'id' => 'new_' . $sequence++,  // Use 'new_' prefix to match standard pool format
                            'name' => $domainName,
                            'is_used' => false,
                            'prefix_statuses' => $prefixStatuses
                        ];
                    }

                    $data['domains'] = $domainsFromCsv;
                    $data['total_inboxes'] = count($smtpData['accounts']);
                    $data['inboxes_per_domain'] = $smtpData['max_per_domain'] ?? 1;

                    // Store SMTP-specific data
                    $data['smtp_provider_url'] = $request->smtp_provider_url;
                    $data['provider_type'] = 'SMTP';
                    $data['smtp_accounts_data'] = $smtpData; // Store full CSV accounts data with all credentials

                    // Store raw CSV file content and filename
                    $data['smtp_csv_file'] = $request->smtp_csv_file ?? null;
                    $data['smtp_csv_filename'] = $request->smtp_csv_filename ?? null;

                    // Store prefix_variants in standard format: {"prefix_variant_1": "bob", "prefix_variant_2": "eva", ...}
                    $allPrefixes = [];
                    foreach ($smtpData['accounts'] as $account) {
                        if (!empty($account['prefix']) && !in_array($account['prefix'], $allPrefixes)) {
                            $allPrefixes[] = $account['prefix'];
                        }
                    }
                    // Build prefix_variants as JSON object with keys prefix_variant_1, prefix_variant_2, etc.
                    $prefixVariants = [];
                    foreach (array_slice($allPrefixes, 0, 3) as $index => $prefix) {
                        $prefixVariants['prefix_variant_' . ($index + 1)] = $prefix;
                    }
                    $data['prefix_variants'] = $prefixVariants;

                    // Store prefix_variants_details in standard format
                    $prefixDetails = [];
                    $usedPrefixes = [];
                    foreach ($smtpData['accounts'] as $account) {
                        $prefix = $account['prefix'] ?? '';
                        if (!empty($prefix) && !in_array($prefix, $usedPrefixes) && count($prefixDetails) < 3) {
                            $prefixKey = 'prefix_variant_' . (count($prefixDetails) + 1);
                            $prefixDetails[$prefixKey] = [
                                'first_name' => $account['first_name'] ?? null,
                                'last_name' => $account['last_name'] ?? null,
                                'profile_link' => null,
                                'password' => $account['password'] ?? null
                            ];
                            $usedPrefixes[] = $prefix;
                        }
                    }
                    $data['prefix_variants_details'] = $prefixDetails;

                    \Log::info('SMTP Pool - Domains built from CSV', [
                        'domains_count' => count($domainsFromCsv),
                        'total_inboxes' => $data['total_inboxes']
                    ]);
                }
            }
            // Handle standard domains JSON conversion and ensure unique id, is_used, prefix_statuses
            elseif ($request->has('domains') && is_string($request->domains)) {
                $domains = json_decode($request->domains, true);
                $processedDomains = [];
                $sequence = 1;
                $warmingDates = $this->getDomainWarmingDates();
                $inboxesPerDomain = (int) ($request->inboxes_per_domain ?? 1);

                foreach ($domains as $domain) {
                    // If domain is string, convert to object
                    if (is_string($domain)) {
                        $processedDomains[] = [
                            'id' => 'new_' . $sequence++,
                            'name' => $domain,
                            'is_used' => false,
                            'prefix_statuses' => $this->buildPrefixStatuses($inboxesPerDomain, null, $warmingDates)
                        ];
                    } elseif (is_array($domain)) {
                        $isUsed = $domain['is_used'] ?? false;

                        // Build prefix_statuses - use existing if available, otherwise create new
                        $existingPrefixStatuses = $domain['prefix_statuses'] ?? null;
                        $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $existingPrefixStatuses, $warmingDates);

                        // If domain is used, set all prefix statuses to in-progress
                        if ($isUsed) {
                            foreach ($prefixStatuses as $key => &$prefixStatus) {
                                $prefixStatus['status'] = 'in-progress';
                            }
                        }

                        $processedDomains[] = [
                            'id' => $domain['id'] ?? ('new_' . $sequence++),
                            'name' => $domain['name'] ?? '',
                            'is_used' => $isUsed,
                            'prefix_statuses' => $prefixStatuses
                        ];
                    }
                }
                $data['domains'] = $processedDomains;
            }
            // log domains being created
            \Log::info('Creating Pool - Domains Processed', [
                'domains' => $data['domains']
            ]);
            // Handle prefix variants JSON conversion (skip if SMTP mode - already set from CSV)
            if (!$isSmtpMode && $request->has('prefix_variants') && is_array($request->prefix_variants)) {
                $data['prefix_variants'] = $request->prefix_variants;
            }

            // Handle prefix variants details JSON conversion (skip if SMTP mode - already set from CSV)
            if (!$isSmtpMode && $request->has('prefix_variants_details') && is_array($request->prefix_variants_details)) {
                $data['prefix_variants_details'] = $request->prefix_variants_details;
            }

            // Handle boolean conversion for master_inbox_confirmation
            $data['master_inbox_confirmation'] = $request->boolean('master_inbox_confirmation');

            // Set provider_type from Configuration table (same as manual assignment uses)
            if (!isset($data['provider_type']) || empty($data['provider_type'])) {
                $data['provider_type'] = Configuration::get('PROVIDER_TYPE', 'Google');
            }

            // Set status to pending if not specified
            if (!isset($data['status'])) {
                $data['status'] = 'pending';
            }

            $pool = Pool::create($data);

            // Skip panel assignment for SMTP pools (SMTP doesn't use panel assignment)
            if (!$isSmtpMode) {
                // Check if manual assignment data is provided
                if ($request->has('manual_assignments') && !empty($request->manual_assignments)) {
                    // Use manual assignment service
                    try {
                        $assignmentService = new ManualPanelAssignmentService();
                        $assignmentService->processManualAssignments($pool, $request->manual_assignments);
                        \Log::info('Manual panel assignment completed for pool ' . $pool->id);
                    } catch (\Exception $e) {
                        \Log::error('Failed to manually assign panels for pool ' . $pool->id . ': ' . $e->getMessage());
                        // Delete the pool if manual assignment fails
                        $pool->delete();
                        throw $e;
                    }
                } else {
                    // Use existing automatic assignment
                    try {
                        \Artisan::call('pool:assigned-panel');
                    } catch (\Exception $e) {
                        \Log::error('Failed to auto-assign panel for pool ' . $pool->id . ': ' . $e->getMessage());
                    }
                }
            } else {
                \Log::info('SMTP Pool created - skipping panel assignment', ['pool_id' => $pool->id]);
            }


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
        // Load relationships needed for the show view
        $pool->load([
            'user',           // Pool creator (used as createdBy in view)
            'assignedTo',     // User assigned to this pool
            'plan'            // Associated plan if any
        ]);

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

        // Self-Healing Logic: Fix "stuck" locked domains that are actually available
        try {
            $currentDomains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;

            if (is_array($currentDomains)) {
                $hasFixes = false;
                $fixedDomains = [];

                foreach ($currentDomains as $d) {
                    if (!is_array($d)) {
                        $fixedDomains[] = $d;
                        continue;
                    }

                    $isUsed = $d['is_used'] ?? false;
                    $status = $d['status'] ?? 'warming'; // Legacy status

                    // Check for granular prefix statuses (new format)
                    $hasActivePrefixes = false;
                    if (isset($d['prefix_statuses']) && is_array($d['prefix_statuses'])) {
                        foreach ($d['prefix_statuses'] as $variant) {
                            $vStatus = $variant['status'] ?? 'warming';
                            if ($vStatus === 'in-progress' || $vStatus === 'used') {
                                $hasActivePrefixes = true;
                                break;
                            }
                        }
                    } else {
                        // Fallback to legacy status check if no prefix_statuses
                        if ($status === 'in-progress' || $status === 'used') {
                            $hasActivePrefixes = true;
                        }
                    }

                    // Logic: If marked is_used=true, but NO active prefixes/status found -> Unlock it
                    if ($isUsed && !$hasActivePrefixes) {
                        $d['is_used'] = false;
                        $hasFixes = true;
                    }

                    $fixedDomains[] = $d;
                }

                if ($hasFixes) {
                    // Update DB directly to preserve the fix
                    DB::table('pools')->where('id', $pool->id)->update([
                        'domains' => json_encode($fixedDomains)
                    ]);

                    // Update the model instance for the view
                    $pool->domains = $fixedDomains;

                    \Log::info("PoolController@edit: Self-healed locked domains for Pool ID {$pool->id}");
                }
            }
        } catch (\Exception $e) {
            \Log::error("PoolController@edit: Self-healing failed: " . $e->getMessage());
        }

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
            'status_manage_by_admin' => 'nullable|in:warming,available',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'string|max:3',
            // 'forwarding_url' => 'required|url',
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
            'purchase_date' => 'required|date',
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
            // Handle domains JSON conversion and ABSOLUTELY preserve existing domain IDs with prefix_statuses
            if ($request->has('domains') && is_string($request->domains)) {
                $domains = json_decode($request->domains, true);
                $existingDomains = is_array($pool->domains) ? $pool->domains : [];
                $inboxesPerDomain = (int) ($request->inboxes_per_domain ?? $pool->inboxes_per_domain ?? 1);

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
                \Log::info('Domain Update - ABSOLUTE ID PROTECTION with prefix_statuses', [
                    'pool_id' => $pool->id,
                    'protected_ids' => $protectedDomainIds,
                    'existing_domains_count' => count($existingDomains),
                    'submitted_domains_count' => count($domains),
                    'inboxes_per_domain' => $inboxesPerDomain
                ]);

                $processedDomains = [];
                $submittedDomainIds = [];
                $newDomainSequence = 1;
                $usedProtectedIds = []; // Track which protected IDs have been used

                // ABSOLUTE DOMAIN ID PRESERVATION ALGORITHM with prefix_statuses
                // Process each submitted domain with GUARANTEED ID preservation
                $warmingDates = $this->getDomainWarmingDates();

                foreach ($domains as $domainIndex => $domain) {
                    $domainData = null;

                    if (is_string($domain)) {
                        $domainName = trim($domain);

                        // Priority 1: Exact name match (no change)
                        if (isset($existingDomainMapByName[$domainName])) {
                            $existingDomain = $existingDomainMapByName[$domainName];
                            $isUsed = $existingDomain['is_used'] ?? false;

                            // Preserve or build prefix_statuses
                            $existingPrefixStatuses = $existingDomain['prefix_statuses'] ?? null;
                            $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $existingPrefixStatuses, $warmingDates);

                            // If domain is used, set all prefix statuses to in-progress
                            if ($isUsed) {
                                foreach ($prefixStatuses as $key => &$prefixStatus) {
                                    $prefixStatus['status'] = 'in-progress';
                                }
                            }

                            $domainData = [
                                'id' => $existingDomain['id'],
                                'name' => $domainName,
                                'is_used' => $isUsed,
                                'prefix_statuses' => $prefixStatuses
                            ];
                            $usedProtectedIds[] = $existingDomain['id'];
                        }
                        // Priority 2: Position-based matching (renamed domain)
                        elseif (isset($existingDomainsByIndex[$domainIndex]) && !in_array($existingDomainsByIndex[$domainIndex]['id'], $usedProtectedIds)) {
                            $existingAtPosition = $existingDomainsByIndex[$domainIndex];
                            $isUsed = $existingAtPosition['is_used'] ?? false;

                            // Preserve or build prefix_statuses
                            $existingPrefixStatuses = $existingAtPosition['prefix_statuses'] ?? null;
                            $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $existingPrefixStatuses, $warmingDates);

                            // If domain is used, set all prefix statuses to in-progress
                            if ($isUsed) {
                                foreach ($prefixStatuses as $key => &$prefixStatus) {
                                    $prefixStatus['status'] = 'in-progress';
                                }
                            }

                            $domainData = [
                                'id' => $existingAtPosition['id'], // FORCE preserve existing ID
                                'name' => $domainName,
                                'is_used' => $isUsed,
                                'prefix_statuses' => $prefixStatuses
                            ];
                            $usedProtectedIds[] = $existingAtPosition['id'];

                            \Log::info('ABSOLUTE PROTECTION: Domain renamed with prefix_statuses', [
                                'position' => $domainIndex,
                                'old_name' => $existingAtPosition['name'],
                                'new_name' => $domainName,
                                'PROTECTED_ID' => $existingAtPosition['id']
                            ]);
                        }
                        // Priority 3: This should not happen in edit mode (no new domains)
                        else {
                            $domainData = [
                                'id' => $pool->id . '_new_' . $newDomainSequence++,
                                'name' => $domainName,
                                'is_used' => false,
                                'prefix_statuses' => $this->buildPrefixStatuses($inboxesPerDomain, null, $warmingDates)
                            ];
                        }
                    } elseif (is_array($domain)) {
                        $domainName = trim($domain['name'] ?? '');

                        // If domain comes with an ID, verify it's a protected ID
                        if (isset($domain['id']) && in_array($domain['id'], $protectedDomainIds)) {
                            // This is a protected ID - ABSOLUTELY preserve it
                            $existingDomain = $existingDomainMapById[$domain['id']];
                            $isUsed = $domain['is_used'] ?? $existingDomain['is_used'] ?? false;

                            // Handle prefix_statuses - preserve existing or use submitted
                            $existingPrefixStatuses = $existingDomain['prefix_statuses'] ?? null;
                            $submittedPrefixStatuses = $domain['prefix_statuses'] ?? null;

                            // If submitted prefix_statuses exist, use them; otherwise build from existing
                            if ($submittedPrefixStatuses) {
                                $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $submittedPrefixStatuses, $warmingDates);
                            } else {
                                $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $existingPrefixStatuses, $warmingDates);
                            }

                            // If domain is used, set all prefix statuses to in-progress
                            if ($isUsed) {
                                foreach ($prefixStatuses as $key => &$prefixStatus) {
                                    $prefixStatus['status'] = 'in-progress';
                                }
                            }

                            $domainData = [
                                'id' => $domain['id'], // PROTECTED - NEVER change
                                'name' => $domainName,
                                'is_used' => $isUsed,
                                'prefix_statuses' => $prefixStatuses
                            ];
                            $usedProtectedIds[] = $domain['id'];

                            \Log::info('ABSOLUTE PROTECTION: Protected ID preserved with prefix_statuses', [
                                'PROTECTED_ID' => $domain['id'],
                                'old_name' => $existingDomain['name'],
                                'new_name' => $domainName
                            ]);
                        }
                        // Check for original_id field (from frontend)
                        elseif (isset($domain['original_id']) && in_array($domain['original_id'], $protectedDomainIds)) {
                            $existingDomain = $existingDomainMapById[$domain['original_id']];
                            $isUsed = $domain['is_used'] ?? $existingDomain['is_used'] ?? false;

                            // Handle prefix_statuses
                            $existingPrefixStatuses = $existingDomain['prefix_statuses'] ?? null;
                            $submittedPrefixStatuses = $domain['prefix_statuses'] ?? null;

                            if ($submittedPrefixStatuses) {
                                $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $submittedPrefixStatuses, $warmingDates);
                            } else {
                                $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $existingPrefixStatuses, $warmingDates);
                            }

                            // If domain is used, set all prefix statuses to in-progress
                            if ($isUsed) {
                                foreach ($prefixStatuses as $key => &$prefixStatus) {
                                    $prefixStatus['status'] = 'in-progress';
                                }
                            }

                            $domainData = [
                                'id' => $domain['original_id'], // PROTECTED - use original ID
                                'name' => $domainName,
                                'is_used' => $isUsed,
                                'prefix_statuses' => $prefixStatuses
                            ];
                            $usedProtectedIds[] = $domain['original_id'];
                        }
                        // Exact name match
                        elseif (isset($existingDomainMapByName[$domainName])) {
                            $existingDomain = $existingDomainMapByName[$domainName];
                            $isUsed = $domain['is_used'] ?? $existingDomain['is_used'] ?? false;

                            // Handle prefix_statuses
                            $existingPrefixStatuses = $existingDomain['prefix_statuses'] ?? null;
                            $submittedPrefixStatuses = $domain['prefix_statuses'] ?? null;

                            if ($submittedPrefixStatuses) {
                                $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $submittedPrefixStatuses, $warmingDates);
                            } else {
                                $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $existingPrefixStatuses, $warmingDates);
                            }

                            // If domain is used, set all prefix statuses to in-progress
                            if ($isUsed) {
                                foreach ($prefixStatuses as $key => &$prefixStatus) {
                                    $prefixStatus['status'] = 'in-progress';
                                }
                            }

                            $domainData = [
                                'id' => $existingDomain['id'],
                                'name' => $domainName,
                                'is_used' => $isUsed,
                                'prefix_statuses' => $prefixStatuses
                            ];
                            $usedProtectedIds[] = $existingDomain['id'];
                        }
                        // Position-based matching
                        elseif (isset($existingDomainsByIndex[$domainIndex]) && !in_array($existingDomainsByIndex[$domainIndex]['id'], $usedProtectedIds)) {
                            $existingAtPosition = $existingDomainsByIndex[$domainIndex];
                            $isUsed = $domain['is_used'] ?? $existingAtPosition['is_used'] ?? false;

                            // Handle prefix_statuses
                            $existingPrefixStatuses = $existingAtPosition['prefix_statuses'] ?? null;
                            $submittedPrefixStatuses = $domain['prefix_statuses'] ?? null;

                            if ($submittedPrefixStatuses) {
                                $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $submittedPrefixStatuses, $warmingDates);
                            } else {
                                $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $existingPrefixStatuses, $warmingDates);
                            }

                            // If domain is used, set all prefix statuses to in-progress
                            if ($isUsed) {
                                foreach ($prefixStatuses as $key => &$prefixStatus) {
                                    $prefixStatus['status'] = 'in-progress';
                                }
                            }

                            $domainData = [
                                'id' => $existingAtPosition['id'], // FORCE preserve existing ID
                                'name' => $domainName,
                                'is_used' => $isUsed,
                                'prefix_statuses' => $prefixStatuses
                            ];
                            $usedProtectedIds[] = $existingAtPosition['id'];
                        }
                        // New domain (should not happen in edit mode)
                        else {
                            $isUsed = $domain['is_used'] ?? false;
                            $prefixStatuses = $this->buildPrefixStatuses($inboxesPerDomain, $domain['prefix_statuses'] ?? null, $warmingDates);

                            // If domain is used, set all prefix statuses to in-progress
                            if ($isUsed) {
                                foreach ($prefixStatuses as $key => &$prefixStatus) {
                                    $prefixStatus['status'] = 'in-progress';
                                }
                            }

                            $domainData = [
                                'id' => isset($domain['id']) ? $domain['id'] : ($pool->id . '_new_' . $newDomainSequence++),
                                'name' => $domainName,
                                'is_used' => $isUsed,
                                'prefix_statuses' => $prefixStatuses
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
                            // Ensure prefix_statuses exist and are set to in-progress for used domains
                            $prefixStatuses = $existingDomain['prefix_statuses'] ?? $this->buildPrefixStatuses($inboxesPerDomain, null, $warmingDates);
                            foreach ($prefixStatuses as $key => &$prefixStatus) {
                                $prefixStatus['status'] = 'in-progress';
                            }

                            $existingDomain['prefix_statuses'] = $prefixStatuses;
                            $processedDomains[] = $existingDomain;
                        }
                    }
                }

                // FINAL VERIFICATION: Check that NO protected IDs were changed
                $finalDomainIds = array_column($processedDomains, 'id');
                $changedIds = array_diff($protectedDomainIds, $finalDomainIds);
                $newIds = array_diff($finalDomainIds, $protectedDomainIds);

                \Log::info('ABSOLUTE PROTECTION - FINAL VERIFICATION with prefix_statuses', [
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

            // Manual panel assignment is only supported during creation; ignore on edit to avoid unexpected reassignment
            if ($request->has('manual_assignments') && !empty($request->manual_assignments)) {
                \Log::info('Manual panel assignments were provided on edit but are ignored to preserve existing assignments', [
                    'pool_id' => $pool->id,
                ]);
            } elseif ($request->has('reassign_automatically') && $request->reassign_automatically) {
                // Re-run automatic assignment if requested
                try {
                    \Artisan::call('pool:assigned-panel', [
                        '--provider' => $pool->provider_type
                    ]);
                    \Log::info('Automatic panel reassignment completed for pool ' . $pool->id);
                } catch (\Exception $e) {
                    \Log::error('Failed to auto-reassign panels for pool ' . $pool->id . ': ' . $e->getMessage());
                }
            }

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
     * Get available panels for provider type (for manual assignment)
     * Provider type is fetched from Configuration table, not from request
     */
    public function getAvailablePanels(Request $request)
    {
        try {
            // Get provider type from Configuration table (same as auto-assignment command)
            $providerType = Configuration::get('PROVIDER_TYPE', 'Google');

            $assignmentService = new ManualPanelAssignmentService();
            $panels = $assignmentService->getAvailablePanels($providerType);

            return response()->json([
                'success' => true,
                'panels' => $panels,
                'provider_type' => $providerType // Return provider type for reference
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get available panels: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to load available panels',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate manual assignments before submission
     */
    public function validateManualAssignments(Request $request)
    {
        try {
            $poolData = $request->input('pool_data', []);
            $assignments = $request->input('manual_assignments', []);

            $assignmentService = new ManualPanelAssignmentService();
            $validation = $assignmentService->validateManualAssignments($poolData, $assignments);

            return response()->json($validation);
        } catch (\Exception $e) {
            \Log::error('Failed to validate manual assignments: ' . $e->getMessage());
            return response()->json([
                'valid' => false,
                'errors' => ['Failed to validate assignments: ' . $e->getMessage()],
                'warnings' => []
            ], 500);
        }
    }

    /**
     * Get panel capacity info
     */
    public function getPanelCapacity($panelId)
    {
        try {
            $panel = PoolPanel::findOrFail($panelId);

            return response()->json([
                'success' => true,
                'panel' => [
                    'id' => $panel->id,
                    'title' => $panel->title,
                    'auto_generated_id' => $panel->auto_generated_id,
                    'limit' => $panel->limit,
                    'used_limit' => $panel->used_limit,
                    'remaining_limit' => $panel->remaining_limit,
                    'provider_type' => $panel->provider_type
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get panel capacity: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to get panel capacity',
                'error' => $e->getMessage()
            ], 404);
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

    /**
     * Change order provider type (Google or Microsoft 365)
     */
    public function changeProviderType(Request $request, $poolId)
    {

        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'provider_type' => 'required|in:Google,Microsoft 365',
                'reason' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $adminId = Auth::id();
            $newProviderType = $request->input('provider_type');
            $reason = $request->input('reason');

            // Find the pool
            $pool = Pool::with(['poolPanelSplits'])->findOrFail($poolId);
            $oldProviderType = $pool->provider_type;

            if ($oldProviderType === $newProviderType) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pool already has the selected provider type.'
                ], 400);
            }
            // Validate capacity
            $capacityService = app(PoolSplitCapacityService::class);
            $capacityCheck = $capacityService->validateProviderCapacity($pool, $newProviderType);

            if (!$capacityCheck['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $capacityCheck['message'] ?? 'Insufficient capacity for provider change.',
                    'data' => $capacityCheck['data'] ?? []
                ], 422);
            }

            $splitResetService = app(PoolSplitResetService::class);

            // Perform the change in a transaction
            $splitCleanup = DB::transaction(function () use ($pool, $newProviderType, $splitResetService, $adminId, $reason) {
                // Clear existing splits first
                $cleanupResult = $splitResetService->resetOrderSplits($pool, $adminId, $reason, false);

                // Update provider type
                $pool->provider_type = $newProviderType;
                $pool->save();

                return $cleanupResult;
            });

            $pool->update(['status' => 'pending', 'is_splitting' => 0]);
            // Call capacity check command
            \Artisan::call('pool:assigned-panel', [
                '--provider' => $newProviderType
            ]);

            // Log the activity
            ActivityLogService::log(
                'admin_pool_provider_type_updated',
                "Admin changed pool provider type from '{$oldProviderType}' to '{$newProviderType}'" . ($reason ? " with reason: {$reason}" : ""),
                $pool,
                [
                    'pool_id' => $poolId,
                    'old_provider_type' => $oldProviderType,
                    'new_provider_type' => $newProviderType,
                    'reason' => $reason,
                    'split_cleanup' => $splitCleanup,
                    'changed_by' => $adminId,
                    'changed_by_type' => 'admin'
                ],
                $adminId
            );

            // Create notification for customer if applicable
            if ($pool->user_id) {
                Notification::create([
                    'user_id' => $pool->user_id,
                    'type' => 'pool_provider_type_change',
                    'title' => 'Pool Provider Type Updated',
                    'message' => "Your pool #{$poolId} provider type has been changed to {$newProviderType}",
                    'data' => [
                        'pool_id' => $poolId,
                        'old_provider_type' => $oldProviderType,
                        'new_provider_type' => $newProviderType,
                        'reason' => $reason,
                        'split_cleanup' => $splitCleanup
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Provider type successfully changed from '{$oldProviderType}' to '{$newProviderType}'",
                'data' => [
                    'pool_id' => $poolId,
                    'old_provider_type' => $oldProviderType,
                    'new_provider_type' => $newProviderType,
                    'reason' => $reason
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
            ], 422);

        } catch (Exception $e) {
            Log::error("Error in changeProviderType for pool {$poolId}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to change provider type: ' . $e->getMessage()
            ], 500);
        }
    }
}
