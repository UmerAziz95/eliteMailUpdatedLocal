<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PoolDomainService;
use App\Services\PoolOrderService;
use Yajra\DataTables\DataTables;

class PoolDomainController extends Controller
{
    protected $poolDomainService;
    protected $poolOrderService;

    public function __construct(PoolDomainService $poolDomainService, PoolOrderService $poolOrderService)
    {
        $this->poolDomainService = $poolDomainService;
        $this->poolOrderService = $poolOrderService;
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

    /**
     * Get list of assigned pool orders (My Pool Orders)
     */
    public function poolOrdersList(Request $request)
    {
        if ($request->ajax()) {
            // Get only orders assigned to the current user
            $poolOrders = $this->poolOrderService->getAssignedPoolOrders(auth()->id());

            return DataTables::of($poolOrders)
                ->addIndexColumn()
                ->addColumn('order_id', function ($row) {
                    return $row->id;
                })
                ->addColumn('customer_name', function ($row) {
                    return $row->user->name ?? 'N/A';
                })
                ->addColumn('customer_email', function ($row) {
                    return $row->user->email ?? 'N/A';
                })
                ->addColumn('status_badge', function ($row) {
                    return $this->poolOrderService->getStatusBadge($row->status);
                })
                ->addColumn('assigned_to_name', function ($row) {
                    return $this->poolOrderService->getAssignedToBadge($row);
                })
                ->addColumn('assigned_at_formatted', function ($row) {
                    return $this->poolOrderService->formatAssignedAt($row);
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('actions', function ($row) {
                    return $this->poolOrderService->getActionsDropdown($row);
                })
                ->rawColumns(['status_badge', 'assigned_to_name', 'actions'])
                ->make(true);
        }

        return view('admin.pool_domains.index');
    }

    /**
     * Get list of all pool orders (no filtering)
     */
    public function allPoolOrders(Request $request)
    {
        if ($request->ajax()) {
            $poolOrders = $this->poolOrderService->getAllPoolOrders();

            return DataTables::of($poolOrders)
                ->addIndexColumn()
                ->addColumn('order_id', function ($row) {
                    return $row->id;
                })
                ->addColumn('customer_name', function ($row) {
                    return $row->user->name ?? 'N/A';
                })
                ->addColumn('customer_email', function ($row) {
                    return $row->user->email ?? 'N/A';
                })
                ->addColumn('status_badge', function ($row) {
                    return $this->poolOrderService->getStatusBadge($row->status);
                })
                ->addColumn('assigned_to_name', function ($row) {
                    return $this->poolOrderService->getAssignedToBadge($row);
                })
                ->addColumn('assigned_at_formatted', function ($row) {
                    return $this->poolOrderService->formatAssignedAt($row);
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('actions', function ($row) {
                    return $this->poolOrderService->getActionsDropdown($row);
                })
                ->rawColumns(['status_badge', 'assigned_to_name', 'actions'])
                ->make(true);
        }

        return view('admin.pool_domains.index');
    }

    /**
     * Get list of in-queue pool orders (not assigned)
     */
    public function inQueueOrders(Request $request)
    {
        if ($request->ajax()) {
            $inQueueOrders = $this->poolOrderService->getUnassignedPoolOrders();

            return DataTables::of($inQueueOrders)
                ->addIndexColumn()
                ->addColumn('order_id', function ($row) {
                    return $row->id;
                })
                ->addColumn('customer_name', function ($row) {
                    return $row->user->name ?? 'N/A';
                })
                ->addColumn('customer_email', function ($row) {
                    return $row->user->email ?? 'N/A';
                })
                ->addColumn('status_badge', function ($row) {
                    return $this->poolOrderService->getStatusBadge($row->status);
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('actions', function ($row) {
                    return $this->poolOrderService->getActionsDropdown($row);
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        return view('admin.pool_domains.index');
    }

    /**
     * View pool order details
     */
    public function viewPoolOrder($id)
    {
        $poolOrder = \App\Models\PoolOrder::with([
            'user', 
            'assignedTo', 
            'poolPlan',
            'poolInvoices'
        ])->findOrFail($id);

        return view('admin.pool_orders.show', compact('poolOrder'));
    }

    /**
     * Cancel a pool order
     */
    public function cancelPoolOrder(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:pool_orders,id'
        ]);

        $result = $this->poolOrderService->cancelPoolOrder($request->order_id);

        if ($result['success']) {
            // Clear cache after cancellation
            $this->poolDomainService->clearCache();
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Download pool invoice as PDF
     */
    public function downloadPoolInvoice($invoiceId)
    {
        try {
            // Find the pool invoice by ID (admin can download any invoice)
            $poolInvoice = \App\Models\PoolInvoice::with(['user', 'poolOrder.poolPlan'])
                ->where('id', $invoiceId)
                ->firstOrFail();

            // Generate PDF using dompdf
            $pdf = \PDF::loadView('customer.pool-invoices.pdf', compact('poolInvoice'));
            
            // Generate filename
            $filename = 'pool_invoice_' . $poolInvoice->chargebee_invoice_id . '.pdf';

            // Return PDF file as download
            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('Error downloading pool invoice: ' . $e->getMessage());
            
            return redirect()->back()->with('error', 'Error downloading invoice');
        }
    }
}

