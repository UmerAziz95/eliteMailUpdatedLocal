<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PoolDomainService;
use Yajra\DataTables\DataTables;

class PoolDomainController extends Controller
{
    protected $poolDomainService;

    public function __construct(PoolDomainService $poolDomainService)
    {
        $this->poolDomainService = $poolDomainService;
    }

    /**
     * Display a listing of pool domains across pools and pool orders.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Get all pool domains without our custom pagination
            $allData = $this->poolDomainService->getPoolDomainsData(true); // Use cache
            
            return DataTables::of($allData)
                ->addIndexColumn()
                ->addColumn('prefixes_formatted', function ($row) {
                    return $this->poolDomainService->formatPrefixes($row['prefixes'], $row['domain_name']);
                })
                ->addColumn('status_badge', function ($row) {
                    $colorMap = [
                        'available' => 'success',
                        'subscribed' => 'primary',
                        'used' => 'warning',
                        'inactive' => 'secondary',
                        'unknown' => 'dark',
                    ];
                    $color = $colorMap[$row['status']] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row['status']) . '</span>';
                })
                ->addColumn('pool_order_status_badge', function ($row) {
                    if ($row['pool_order_status'] === 'no_order') {
                        return '<span class="badge bg-light text-dark">No Order</span>';
                    }
                    
                    $colorMap = [
                        'completed' => 'success',
                        'pending' => 'warning',
                        'cancelled' => 'danger',
                        'failed' => 'danger',
                        'unknown' => 'secondary',
                    ];
                    $color = $colorMap[$row['pool_order_status']] ?? 'secondary';
                    return '<span class="badge bg-' . $color . '">' . ucfirst($row['pool_order_status']) . '</span>';
                })
                ->addColumn('usage_badge', function ($row) {
                    $isUsed = $row['is_used'] ?? false;
                    if ($isUsed) {
                        return '<span class="badge bg-warning">Used</span>';
                    } else {
                        return '<span class="badge bg-light text-dark">Available</span>';
                    }
                })
                ->addColumn('actions', function ($row) {
                    $poolId = $row['pool_id'] ?? '';
                    $poolOrderId = $row['pool_order_id'] ?? '';
                    $domainId = $row['domain_id'] ?? '';
                    $domainName = addslashes($row['domain_name'] ?? '');
                    $status = $row['status'] ?? 'available';
                    
                    return '
                        <div class="dropdown">
                            <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="javascript:void(0)" 
                                       onclick="editDomain(\'' . $poolId . '\', \'' . $poolOrderId . '\', \'' . $domainId . '\', \'' . $domainName . '\', \'' . $status . '\')">
                                        <i class="fa-solid fa-edit me-1"></i>Edit Domain
                                    </a>
                                </li>
                            </ul>
                        </div>';
                })
                ->rawColumns(['status_badge', 'pool_order_status_badge', 'usage_badge', 'actions'])
                ->make(true);
        }

        return view('admin.pool_domains.index');
    }

    /**
     * Refresh the pool domains cache
     */
    public function refreshCache(Request $request)
    {
        $userId = $request->get('user_id');
        $poolId = $request->get('pool_id');
        
        $this->poolDomainService->refreshCache($userId, $poolId);
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true, 
                'message' => 'Cache refreshed successfully',
                'filters' => compact('userId', 'poolId')
            ]);
        }
        
        return redirect()->route('admin.pool-domains.index')
            ->with('success', 'Pool domains cache has been refreshed');
    }

    /**
     * Clear all pool domains cache
     */
    public function clearCache(Request $request)
    {
        $this->poolDomainService->clearCache();
        
        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'All cache cleared successfully']);
        }
        
        return redirect()->route('admin.pool-domains.index')
            ->with('success', 'All pool domains cache has been cleared');
    }

    /**
     * Debug method to check pool domains data
     */
    public function debug()
    {
        $debug = $this->poolDomainService->debugPoolDomains();
        return response()->json($debug);
    }

    /**
     * Simple test to check basic pool data
     */
    public function test()
    {
        $poolsCount = \App\Models\Pool::whereNotNull('domains')->count();
        $poolOrdersCount = \App\Models\PoolOrder::whereNotNull('domains')->count();
        
        $samplePool = \App\Models\Pool::whereNotNull('domains')->first();
        
        $data = [
            'pools_with_domains' => $poolsCount,
            'pool_orders_with_domains' => $poolOrdersCount,
            'sample_pool' => $samplePool ? [
                'id' => $samplePool->id,
                'user_id' => $samplePool->user_id,
                'domains_count' => is_array($samplePool->domains) ? count($samplePool->domains) : 0,
                'domains_sample' => is_array($samplePool->domains) ? array_slice($samplePool->domains, 0, 2) : null
            ] : null,
            'service_data_count' => count($this->poolDomainService->getPoolDomainsData(false))
        ];
        
        return response()->json($data);
    }

    /**
     * Update domain name and status
     */
    public function update(Request $request)
    {
        $request->validate([
            'pool_id' => 'nullable|integer',
            'pool_order_id' => 'nullable|integer',
            'domain_id' => 'required',
            'domain_name' => 'required|string',
            'status' => 'required|in:warming,available,subscribed'
        ]);

        try {
            $updated = false;
            $message = '';

            // Try to update in Pool first
            if ($request->pool_id) {
                $pool = \App\Models\Pool::find($request->pool_id);
                if ($pool && is_array($pool->domains)) {
                    $domains = $pool->domains;
                    foreach ($domains as &$domain) {
                        if (isset($domain['id']) && $domain['id'] == $request->domain_id) {
                            $domain['name'] = $request->domain_name;
                            $domain['status'] = $request->status;
                            $updated = true;
                            break;
                        }
                    }
                    if ($updated) {
                        $pool->domains = $domains;
                        $pool->save();
                        $message = 'Pool domain updated successfully';
                    }
                }
            }
            
            // Try to update in PoolOrder if not found in Pool or also exists there
            if ($request->pool_order_id) {
                $poolOrder = \App\Models\PoolOrder::find($request->pool_order_id);
                if ($poolOrder && is_array($poolOrder->domains)) {
                    $domains = $poolOrder->domains;
                    foreach ($domains as &$domain) {
                        // Check both 'domain_id' and 'id' keys for compatibility
                        $domainIdKey = isset($domain['domain_id']) ? 'domain_id' : (isset($domain['id']) ? 'id' : null);
                        
                        if ($domainIdKey && $domain[$domainIdKey] == $request->domain_id) {
                            $domain['domain_name'] = $request->domain_name;
                            $domain['status'] = $request->status;
                            $updated = true;
                            break;
                        }
                    }
                    if ($updated) {
                        $poolOrder->domains = $domains;
                        $poolOrder->save();
                        $message = $message ? 'Domain updated in both Pool and Pool Order' : 'Pool Order domain updated successfully';
                    }
                }
            }

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain not found or could not be updated'
                ], 404);
            }

            // Clear cache after update
            $this->poolDomainService->clearCache();

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating pool domain: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating domain: ' . $e->getMessage()
            ], 500);
        }
    }
}
