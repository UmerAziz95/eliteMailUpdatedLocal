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
                    return get_domain_status_badge($row['status'], true);
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
        $editableStatuses = config('domain_statuses.editable', ['warming', 'available', 'in-progress', 'used']);
        
        $request->validate([
            'pool_id' => 'nullable|integer',
            'pool_order_id' => 'nullable|integer',
            'domain_id' => 'required',
            'domain_name' => 'required|string',
            'status' => 'required|in:' . implode(',', array_keys($editableStatuses))
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
                            // is_used updated based on status
                            $domain['is_used'] = $request->status === 'available' ? false : true;
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
                            // is_used updated based on status
                            $domain['is_used'] = $request->status === 'available' ? false : true;
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
     * Update domain status
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'domain_id' => 'required|integer',
            'status' => 'required|in:' . implode(',', array_keys(config('domain_statuses.statuses')))
        ]);

        try {
            $domainId = $request->domain_id;
            $newStatus = $request->status;
            $updated = false;

            // Update in all pools that have this domain
            $pools = \App\Models\Pool::whereNotNull('domains')->get();
            foreach ($pools as $pool) {
                if (is_array($pool->domains)) {
                    $domains = $pool->domains;
                    foreach ($domains as &$domain) {
                        if (isset($domain['id']) && $domain['id'] == $domainId) {
                            $domain['status'] = $newStatus;
                            // is_used updated based on status
                            $domain['is_used'] = $newStatus === 'available' ? false : true;
                            $updated = true;
                        }
                    }
                    if ($updated) {
                        $pool->domains = $domains;
                        $pool->save();
                    }
                }
            }

            // Update in all pool orders that have this domain
            $poolOrders = \App\Models\PoolOrder::whereNotNull('domains')->get();
            foreach ($poolOrders as $poolOrder) {
                if (is_array($poolOrder->domains)) {
                    $domains = $poolOrder->domains;
                    foreach ($domains as &$domain) {
                        $domainIdKey = isset($domain['domain_id']) ? 'domain_id' : (isset($domain['id']) ? 'id' : null);
                        if ($domainIdKey && $domain[$domainIdKey] == $domainId) {
                            $domain['status'] = $newStatus;
                            // is_used updated based on status
                            $domain['is_used'] = $newStatus === 'available' ? false : true;
                            $updated = true;
                        }
                    }
                    if ($updated) {
                        $poolOrder->domains = $domains;
                        $poolOrder->save();
                    }
                }
            }

            if (!$updated) {
                return response()->json([
                    'success' => false,
                    'message' => 'Domain not found'
                ], 404);
            }

            // Clear cache after update
            $this->poolDomainService->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Domain status updated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating domain status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating status: ' . $e->getMessage()
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
                    return $this->poolOrderService->getStatusBadge($row->status_manage_by_admin);
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
                    return $this->poolOrderService->getActionsDropdown($row, [
                        'showChangeStatus' => true
                    ]);
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
                    return $this->poolOrderService->getStatusBadge($row->status_manage_by_admin);
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
                    return $this->poolOrderService->getActionsDropdown($row, [
                        'showChangeStatus' => true
                    ]);
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
                    return $this->poolOrderService->getStatusBadge($row->status_manage_by_admin);
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->addColumn('actions', function ($row) {
                    return $this->poolOrderService->getActionsDropdown($row, [
                        'showAssignToMe' => true
                    ]);
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

    /**
     * Assign pool order to current admin user
     */
    public function assignToMe(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer|exists:pool_orders,id'
        ]);

        try {
            $poolOrder = \App\Models\PoolOrder::findOrFail($request->order_id);

            // Check if order is already assigned
            if ($poolOrder->assigned_to) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order is already assigned to ' . ($poolOrder->assignedTo->name ?? 'another user')
                ], 400);
            }

            // Assign to current user
            $poolOrder->assigned_to = auth()->id();
            $poolOrder->assigned_at = now();
            $poolOrder->save();

            \Log::info('Pool order #' . $poolOrder->id . ' assigned to admin user #' . auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Pool order assigned to you successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error assigning pool order: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error assigning pool order'
            ], 500);
        }
    }

    /**
     * Change pool order status
     */

    public function changePoolOrderStatus(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|integer|exists:pool_orders,id',
                'status' => 'required|string|in:pending,in-progress,completed,cancelled'
            ]);

            $poolOrder = \App\Models\PoolOrder::findOrFail($request->order_id);

            // Check if order is already cancelled
            if ($poolOrder->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot change status of a cancelled order'
                ], 400);
            }

            $oldStatus = $poolOrder->status_manage_by_admin ?? $poolOrder->status;
            
            // Convert status format: underscore to hyphen for database ENUM compatibility
            $statusValue = str_replace('_', '-', $request->status);
            
            // If changing to cancelled, use the cancellation service to handle subscription cancellation
            if ($statusValue === 'cancelled') {
                $cancellationService = new \App\Services\PoolOrderCancelledService();
                $reason = 'Admin cancelled the order';
                $result = $cancellationService->cancelSubscription($request->order_id, $poolOrder->user_id, $reason);
                
                if ($result['success']) {
                    \Log::info('Pool order #' . $poolOrder->id . ' cancelled (status_manage_by_admin and subscription) by admin user #' . auth()->id());
                    return response()->json([
                        'success' => true,
                        'message' => 'Pool order and subscription cancelled successfully'
                    ]);
                } else {
                    return response()->json($result, 400);
                }
            }
            
            // Use status_manage_by_admin column for admin status changes
            $poolOrder->status_manage_by_admin = $statusValue;
            $poolOrder->save();

            \Log::info('Pool order #' . $poolOrder->id . ' status_manage_by_admin changed from ' . $oldStatus . ' to ' . $statusValue . ' by admin user #' . auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Pool order status updated successfully'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error changing pool order status: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error changing pool order status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download domains with prefixes as CSV
     */
    public function downloadDomainsCsv($id)
    {
        try {
            return $this->poolOrderService->downloadDomainsCsv($id);
        } catch (\Exception $e) {
            \Log::error('Error downloading domains CSV: ' . $e->getMessage());
            return back()->with('error', 'Error downloading CSV file.');
        }
    }

    /**
     * Mark pool order as locked out of Instantly
     */
    public function lockOutOfInstantly(Request $request)
    {
        try {
            $orderId = $request->input('id');
            $result = $this->poolOrderService->lockOutOfInstantly($orderId);

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Error locking out of Instantly: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error marking pool order as locked out: ' . $e->getMessage()
            ], 500);
        }
    }
}




