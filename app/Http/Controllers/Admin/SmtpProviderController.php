<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmtpProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SmtpProviderController extends Controller
{
    /**
     * Get list of SMTP providers for dropdown (Select2 compatible format)
     */
    public function index(Request $request)
    {
        $search = $request->get('search', '');

        $query = SmtpProvider::active()->orderBy('name');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $providers = $query->get(['id', 'name', 'url']);

        // Format for Select2
        $results = $providers->map(function ($provider) {
            return [
                'id' => $provider->id,
                'text' => $provider->name,
                'url' => $provider->url,
            ];
        });

        return response()->json([
            'results' => $results,
            'pagination' => ['more' => false]
        ]);
    }

    /**
     * Store a new SMTP provider
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'url' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $provider = SmtpProvider::create([
            'name' => $request->name,
            'url' => $request->url,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'SMTP Provider created successfully',
            'provider' => [
                'id' => $provider->id,
                'text' => $provider->name,
                'url' => $provider->url,
            ]
        ]);
    }

    /**
     * Get all providers for management page
     */
    public function all()
    {
        $providers = SmtpProvider::with(['pools' => function ($query) {
            // Load pools with necessary columns for email calculation
            $query->select('id', 'smtp_provider_id', 'domains', 'prefix_variants', 'prefix_variants_details', 'smtp_accounts_data');
        }])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'providers' => $providers
        ]);
    }

    /**
     * Update SMTP provider
     */
    public function update(Request $request, SmtpProvider $smtpProvider)
    {
        // Build validation rules - name is only required if it's being updated
        $rules = [
            'url' => 'nullable|url|max:255',
            'is_active' => 'boolean',
        ];

        // Only require name if it's present in the request (being updated)
        if ($request->has('name')) {
            $rules['name'] = 'required|string|max:255';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        // Prepare update data - only include fields that are present
        $updateData = [];
        if ($request->has('name')) {
            $updateData['name'] = $request->name;
        }
        if ($request->has('url')) {
            $updateData['url'] = $request->url;
        }
        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->is_active;
        }

        $smtpProvider->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'SMTP Provider updated successfully',
            'provider' => $smtpProvider
        ]);
    }

    /**
     * Delete SMTP provider
     */
    public function destroy(SmtpProvider $smtpProvider)
    {
        // Check if provider is being used
        $poolCount = $smtpProvider->pools()->count();

        if ($poolCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete provider. It is being used by {$poolCount} pool(s).",
            ], 422);
        }

        $smtpProvider->delete();

        return response()->json([
            'success' => true,
            'message' => 'SMTP Provider deleted successfully'
        ]);
    }

    /**
     * Display admin page listing all SMTP providers
     */
    public function indexView()
    {
        $providers = SmtpProvider::withCount('pools')
            ->with(['pools' => function ($query) {
                // Load pools with necessary columns for email calculation
                $query->select('id', 'smtp_provider_id', 'domains', 'prefix_variants', 'prefix_variants_details', 'smtp_accounts_data');
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($provider) {
                // Count total email accounts across all pools
                // Supports both smtp_accounts_data and domains+prefix_variants
                $totalEmails = 0;
                foreach ($provider->pools as $pool) {
                    $accounts = $this->extractAccountsFromPool($pool);
                    $totalEmails += count($accounts);
                }
                $provider->total_emails = $totalEmails;
                return $provider;
            });

        // Summary stats
        $totalProviders = $providers->count();
        $activeProviders = $providers->where('is_active', true)->count();
        $totalPools = $providers->sum('pools_count');
        $totalEmails = $providers->sum('total_emails');

        return view('admin.smtp_providers.index', compact(
            'providers',
            'totalProviders',
            'activeProviders',
            'totalPools',
            'totalEmails'
        ));
    }

    /**
     * Display details for a specific SMTP provider with pools and email accounts
     */
    public function show(SmtpProvider $smtpProvider)
    {
        // Load pools with their data (domains, prefix_variants, smtp_accounts_data)
        $smtpProvider->load([
            'pools' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'pools.user'
        ]);

        // Count total emails and process pool data
        // Also prepare accounts for each pool to pass to view
        $totalEmails = 0;
        $uniqueDomains = [];
        $poolAccountsMap = []; // Map pool_id => accounts array
        
        foreach ($smtpProvider->pools as $pool) {
            // Process each pool to extract accounts (from smtp_accounts_data OR domains+prefix_variants)
            $accounts = $this->extractAccountsFromPool($pool);
            $poolAccountsMap[$pool->id] = $accounts;
            
            if (!empty($accounts)) {
                $totalEmails += count($accounts);
                
                // Extract unique domains
                foreach ($accounts as $account) {
                    if (isset($account['domain'])) {
                        $uniqueDomains[$account['domain']] = true;
                    }
                }
            }
        }

        // Get all SMTP providers for provider type change modal and dropdown
        $allProviders = SmtpProvider::where('is_active', true)->orderBy('name')->get();

        return view('admin.smtp_providers.show', compact('smtpProvider', 'totalEmails', 'allProviders', 'uniqueDomains', 'poolAccountsMap'));
    }

    /**
     * Extract accounts from pool - supports both smtp_accounts_data and domains+prefix_variants
     */
    private function extractAccountsFromPool($pool)
    {
        $accounts = [];

        // First, try to get from smtp_accounts_data (for pools created directly as SMTP)
        if ($pool->smtp_accounts_data) {
            $smtpData = $pool->smtp_accounts_data;
            
            // Check if data is compressed
            if (isset($smtpData['_compressed']) && $smtpData['_compressed'] === true && isset($smtpData['_data'])) {
                // Decompress the data
                try {
                    $compressedData = base64_decode($smtpData['_data']);
                    $decompressedJson = gzuncompress($compressedData);
                    if ($decompressedJson !== false) {
                        $smtpData = json_decode($decompressedJson, true);
                        if ($smtpData === null) {
                            \Log::error('Failed to decode decompressed JSON for pool', ['pool_id' => $pool->id]);
                            $smtpData = null;
                        }
                    } else {
                        \Log::error('Failed to decompress smtp_accounts_data for pool', ['pool_id' => $pool->id]);
                        $smtpData = null;
                    }
                } catch (\Exception $e) {
                    \Log::error('Exception while decompressing smtp_accounts_data', [
                        'pool_id' => $pool->id,
                        'error' => $e->getMessage()
                    ]);
                    $smtpData = null;
                }
            }
            
            // If we have accounts after decompression, return them
            if ($smtpData && isset($smtpData['accounts'])) {
                return $smtpData['accounts'];
            }
        }

        // Fallback: Extract from domains + prefix_variants (for migrated pools)
        if ($pool->domains && is_array($pool->domains) && $pool->prefix_variants && is_array($pool->prefix_variants)) {
            $prefixVariants = $pool->prefix_variants;
            $prefixVariantsDetails = $pool->prefix_variants_details ?? [];
            
            foreach ($pool->domains as $domain) {
                $domainName = $domain['name'] ?? $domain['domain_name'] ?? null;
                if (!$domainName) {
                    continue;
                }

                // Check if domain has prefix_statuses (new format)
                if (isset($domain['prefix_statuses']) && is_array($domain['prefix_statuses'])) {
                    foreach ($domain['prefix_statuses'] as $prefixKey => $prefixData) {
                        // Get prefix value from prefix_variants
                        $prefixNumber = (int) preg_replace('/\D/', '', $prefixKey);
                        $prefixValue = $prefixVariants[$prefixKey] ?? $prefixVariants["prefix_variant_{$prefixNumber}"] ?? null;
                        
                        if ($prefixValue) {
                            // Get details from prefix_variants_details
                            $prefixDetails = $prefixVariantsDetails[$prefixKey] ?? $prefixVariantsDetails["prefix_variant_{$prefixNumber}"] ?? [];
                            
                            // Generate email
                            $email = $prefixValue . '@' . $domainName;
                            
                            // Generate password (use existing logic or default)
                            $password = $prefixDetails['password'] ?? $pool->email_persona_password ?? $pool->persona_password ?? '123';
                            
                            $accounts[] = [
                                'email' => $email,
                                'domain' => $domainName,
                                'prefix' => $prefixValue,
                                'password' => $password,
                                'first_name' => $prefixDetails['first_name'] ?? $pool->first_name ?? '',
                                'last_name' => $prefixDetails['last_name'] ?? $pool->last_name ?? ''
                            ];
                        }
                    }
                } else {
                    // Fallback: Use all prefix variants for this domain (old format)
                    foreach ($prefixVariants as $prefixKey => $prefixValue) {
                        if ($prefixValue) {
                            $prefixNumber = (int) preg_replace('/\D/', '', $prefixKey);
                            $prefixDetails = $prefixVariantsDetails[$prefixKey] ?? $prefixVariantsDetails["prefix_variant_{$prefixNumber}"] ?? [];
                            
                            $email = $prefixValue . '@' . $domainName;
                            $password = $prefixDetails['password'] ?? $pool->email_persona_password ?? $pool->persona_password ?? '123';
                            
                            $accounts[] = [
                                'email' => $email,
                                'domain' => $domainName,
                                'prefix' => $prefixValue,
                                'password' => $password,
                                'first_name' => $prefixDetails['first_name'] ?? $pool->first_name ?? '',
                                'last_name' => $prefixDetails['last_name'] ?? $pool->last_name ?? ''
                            ];
                        }
                    }
                }
            }
        }

        return $accounts;
    }

    /**
     * Get SMTP providers data for DataTable AJAX
     */
    public function dataTable(Request $request)
    {
        $query = SmtpProvider::query()
            ->withCount('pools')
            ->orderBy('name');

        return datatables()->of($query)
            ->addIndexColumn()
            ->addColumn('status_badge', function ($provider) {
                $badgeClass = $provider->is_active ? 'bg-success' : 'bg-secondary';
                $statusText = $provider->is_active ? 'Active' : 'Inactive';
                return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
            })
            ->addColumn('url_display', function ($provider) {
                if ($provider->url) {
                    return '<a href="' . e($provider->url) . '" target="_blank" class="text-info text-decoration-none">' . e($provider->url) . '</a>';
                }
                return '<span class="text-muted">-</span>';
            })
            ->addColumn('total_emails', function ($provider) {
                $totalEmails = 0;
                // Load pools if not already loaded with necessary columns
                if (!$provider->relationLoaded('pools')) {
                    $provider->load(['pools' => function ($query) {
                        $query->select('id', 'smtp_provider_id', 'domains', 'prefix_variants', 'prefix_variants_details', 'smtp_accounts_data');
                    }]);
                }
                
                // Use the same extraction method as indexView
                foreach ($provider->pools as $pool) {
                    $accounts = $this->extractAccountsFromPool($pool);
                    $totalEmails += count($accounts);
                }
                return '<span class="badge bg-info">' . $totalEmails . '</span>';
            })
            ->addColumn('pools_count_badge', function ($provider) {
                return '<span class="badge bg-primary">' . $provider->pools_count . '</span>';
            })
            ->addColumn('actions', function ($provider) {
                $viewUrl = route('admin.smtp-providers.show', $provider->id);
                $poolsCount = $provider->pools_count;

                return '
                    <div class="d-flex gap-2">
                        <a href="' . $viewUrl . '" class="btn btn-sm btn-outline-info" title="View Details">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <button class="btn btn-sm btn-outline-warning" onclick="editProvider(' . $provider->id . ', \'' . e($provider->name) . '\', \'' . e($provider->url ?? '') . '\', ' . ($provider->is_active ? 'true' : 'false') . ')" title="Edit">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteProvider(' . $provider->id . ', ' . $poolsCount . ')" title="Delete" ' . ($poolsCount > 0 ? 'disabled' : '') . '>
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                ';
            })
            ->rawColumns(['status_badge', 'url_display', 'total_emails', 'pools_count_badge', 'actions'])
            ->make(true);
    }
}

