<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderRejectionService;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\Panel;
use App\Models\ReorderInfo;
use App\Models\Status;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class OrderQueueController extends Controller
{
    private $statuses;

    public function __construct()
    {
        $this->statuses = Status::pluck('badge', 'name')->toArray();
    }

    public function index()
    {
        return view('admin.orderQueue.order_queue');
    }
    
    public function getOrdersData(Request $request)
    {
        try {
            $type = $request->get('type', 'in-queue'); // 'in-queue' or 'in-draft'
            
            $query = Order::with(['user', 'reorderInfo', 'orderPanels.orderPanelSplits']);
            // type in-queue not get assiged orders
            if ($type === 'in-queue') {
                $query->whereNull('assigned_to');
            }

            // Filter by type
            if ($type === 'in-draft') {
                $query->where('status_manage_by_admin', 'draft');
            } else {
                // In-queue: all orders except draft
                $query->where('status_manage_by_admin', '!=', 'draft');
                // For queue orders, only include orders that have splits
                $query->whereHas('orderPanels.orderPanelSplits');
            }

            // Apply filters if provided
            if ($request->filled('order_id')) {
                $query->where('id', 'like', '%' . $request->order_id . '%');
            }

            if ($request->filled('status')) {
                $query->where('status_manage_by_admin', $request->status);
            }

            if ($request->filled('min_inboxes')) {
                $query->whereHas('reorderInfo', function($q) use ($request) {
                    $q->where('total_inboxes', '>=', $request->min_inboxes);
                });
            }

            if ($request->filled('max_inboxes')) {
                $query->whereHas('reorderInfo', function($q) use ($request) {
                    $q->where('total_inboxes', '<=', $request->max_inboxes);
                });
            }

            // Apply ordering
            $order = $request->get('order', 'desc');
            $query->orderBy('id', $order);

            // Pagination parameters
            $perPage = $request->get('per_page', 12);
            $page = $request->get('page', 1);
            
            // Get paginated results
            $paginatedOrders = $query->paginate($perPage, ['*'], 'page', $page);

            // Format orders data for the frontend
            $ordersData = $paginatedOrders->getCollection()->map(function ($order) {
                $reorderInfo = $order->reorderInfo->first();
                $orderPanels = $order->orderPanels;
                
                // Calculate total domains count from all splits
                $totalDomainsCount = 0;
                $totalInboxes = 0;
                
                foreach ($orderPanels as $orderPanel) {
                    foreach ($orderPanel->orderPanelSplits as $split) {
                        if ($split->domains && is_array($split->domains)) {
                            $totalDomainsCount += count($split->domains);
                        }
                        $totalInboxes += $split->inboxes_per_domain * (is_array($split->domains) ? count($split->domains) : 0);
                    }
                }
                
                $inboxesPerDomain = $reorderInfo ? $reorderInfo->inboxes_per_domain : 0;
                $finalTotalInboxes = $reorderInfo ? $reorderInfo->total_inboxes : $totalInboxes;
                $finalTotalDomains = $reorderInfo && $reorderInfo->total_inboxes && $inboxesPerDomain > 0 
                    ? intval($reorderInfo->total_inboxes / $inboxesPerDomain) 
                    : $totalDomainsCount;

                return [
                    'id' => $order->id,
                    'order_id' => $order->id,
                    'customer_name' => $order->user->name ?? 'N/A',
                    'customer_image' => $order->user->profile_image ? asset('storage/profile_images/' . $order->user->profile_image) : null,
                    'total_inboxes' => $finalTotalInboxes,
                    'inboxes_per_domain' => $inboxesPerDomain,
                    'total_domains' => $finalTotalDomains,
                    'status' => $order->status_manage_by_admin ?? 'pending',
                    'status_manage_by_admin' => $order->status_manage_by_admin ?? 'pending',
                    'created_at' => $order->created_at,
                    'completed_at' => $order->completed_at,
                    'timer_started_at' => $order->timer_started_at ? $order->timer_started_at->toISOString() : null,
                    'timer_paused_at' => $order->timer_paused_at ? $order->timer_paused_at->toISOString() : null,
                    'total_paused_seconds' => $order->total_paused_seconds ?? 0,
                    'order_panels_count' => $orderPanels->count(),
                    'splits_count' => $orderPanels->sum(function($panel) {
                        return $panel->orderPanelSplits->count();
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ordersData,
                'pagination' => [
                    'current_page' => $paginatedOrders->currentPage(),
                    'last_page' => $paginatedOrders->lastPage(),
                    'per_page' => $paginatedOrders->perPage(),
                    'total' => $paginatedOrders->total(),
                    'has_more_pages' => $paginatedOrders->hasMorePages(),
                    'from' => $paginatedOrders->firstItem(),
                    'to' => $paginatedOrders->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching admin orders data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching orders data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getOrderSplits($orderId)
    {
        try {
            $order = Order::with([
                'user',
                'reorderInfo',
                'orderPanels.orderPanelSplits',
                'orderPanels.panel'
            ])->findOrFail($orderId);

            $reorderInfo = $order->reorderInfo->first();
            $orderPanels = $order->orderPanels;

            // Format splits data
            $splits = [];
            foreach ($orderPanels as $orderPanel) {
                foreach ($orderPanel->orderPanelSplits as $split) {
                    $domains = [];
                    if ($split->domains && is_array($split->domains)) {
                        $domains = $split->domains;
                    }
                    
                    $splits[] = [
                        'id' => $split->id,
                        'order_panel_id' => $orderPanel->id,
                        'panel_id' => $orderPanel->panel_id,
                        'panel_title' => $orderPanel->panel->title ?? 'N/A',
                        'inboxes_per_domain' => $split->inboxes_per_domain,
                        'domains' => $domains,
                        'domains_count' => count($domains),
                        'total_inboxes' => $split->inboxes_per_domain * count($domains),
                        'status' => $orderPanel->status ?? 'unallocated',
                        'created_at' => $split->created_at
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'status_manage_by_admin' => $order->status_manage_by_admin,
                    'customer_name' => $order->user->name ?? 'N/A',
                    'created_at' => $order->created_at,
                    'timer_started_at' => $order->timer_started_at ? $order->timer_started_at->toISOString() : null,
                    'timer_paused_at' => $order->timer_paused_at ? $order->timer_paused_at->toISOString() : null,
                    'total_paused_seconds' => $order->total_paused_seconds ?? 0,
                ],
                'reorder_info' => $reorderInfo ? [
                    'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
                    'total_inboxes' => $reorderInfo->total_inboxes,
                    'hosting_platform' => $reorderInfo->hosting_platform,
                    'platform_login' => $reorderInfo->platform_login,
                    'platform_password' => $reorderInfo->platform_password,
                    'forwarding_url' => $reorderInfo->forwarding_url,
                    'sending_platform' => $reorderInfo->sending_platform,
                    'sequencer_login' => $reorderInfo->sequencer_login,
                    'sequencer_password' => $reorderInfo->sequencer_password,
                    'prefix_variants' => $reorderInfo->prefix_variants,
                    'prefix_variant_1' => $reorderInfo->prefix_variant_1,
                    'prefix_variant_2' => $reorderInfo->prefix_variant_2,
                    'backup_codes' => $reorderInfo->backup_codes,
                    'additional_info' => $reorderInfo->additional_info,
                    'data_obj' => json_decode($reorderInfo->data_obj, true) ?: [
                        'backup_codes' => $reorderInfo->backup_codes,
                        'additional_info' => $reorderInfo->additional_info,
                        'prefix_variants_details' => $reorderInfo->prefix_variants_details
                    ]
                ] : null,
                'splits' => $splits
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching order splits: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching order splits: ' . $e->getMessage()
            ], 500);
        }
    }

    public function assignOrderToMe(Request $request, $orderId)
    {
        try {
            $adminId = auth()->id();
            
            // Find the order
            $order = Order::findOrFail($orderId);
            
            // Get all order panels (splits) for this order that are unallocated
            $unallocatedPanels = OrderPanel::where('order_id', $orderId)
                ->where('status', 'unallocated')
                ->get();
            
            if ($unallocatedPanels->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No unallocated splits found for this order.'
                ], 400);
            }
            
            $assignedCount = 0;
            $errors = [];
            
            // Assign each unallocated panel to the admin
            foreach ($unallocatedPanels as $panel) {
                try {
                    // Get the order panel split id
                    $orderPanelSplit = OrderPanelSplit::where('order_panel_id', $panel->id)->first();
                    
                    if ($orderPanelSplit) {
                        // Update panel status to allocated
                        $panel->update([
                            'status' => 'allocated',
                            'contractor_id' => $adminId
                        ]);
                        
                        $assignedCount++;
                    }
                    
                } catch (Exception $e) {
                    $errors[] = "Failed to assign panel {$panel->id}: " . $e->getMessage();
                    Log::error("Error assigning panel {$panel->id} to admin {$adminId}: " . $e->getMessage());
                }
            }
            
            // Assign the order to the current admin
            $order->assigned_to = $adminId;
            $order->save();
            
            // Check remaining unallocated panels
            $remainingUnallocated = OrderPanel::where('order_id', $orderId)
                ->where('status', 'unallocated')
                ->count();
            
            $message = $assignedCount > 0 
                ? "Successfully assigned {$assignedCount} split(s) to you!" 
                : "No new assignments were made.";
            
            if (!empty($errors)) {
                $message .= " However, some errors occurred: " . implode(', ', $errors);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'assigned_count' => $assignedCount,
                'remaining_unallocated' => $remainingUnallocated,
                'errors' => $errors
            ]);
            
        } catch (Exception $e) {
            Log::error("Error in assignOrderToMe for order {$orderId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign order: ' . $e->getMessage()
            ], 500);
        }
    }
    public function rejectOrder(Request $request, $orderId)
    {
        try {
            $adminId = auth()->id();
            
            // Use the OrderRejectionService to handle the rejection
            $rejectionService = new OrderRejectionService();
            $result = $rejectionService->rejectOrder($orderId, $adminId);
            
            return response()->json($result);
            
        } catch (Exception $e) {
            Log::error("Error in rejectOrder for order {$orderId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function canRejectOrder($orderId)
    {
        try {
            $rejectionService = new OrderRejectionService();
            $result = $rejectionService->canRejectOrder($orderId);
            
            return response()->json($result);
            
        } catch (Exception $e) {
            Log::error("Error checking if order {$orderId} can be rejected: " . $e->getMessage());
            
            return response()->json([
                'can_reject' => false,
                'message' => 'Error checking order status: ' . $e->getMessage()
            ], 500);
        }
    }

    // my orders
    public function myOrders()
    {
        return view('admin.myorders.index');
    }

    public function getAssignedOrdersData(Request $request)
    {
        try {
            // For admin, get orders that are assigned to current admin user
            $query = Order::with(['reorderInfo', 'orderPanels.orderPanelSplits', 'orderPanels.panel', 'user'])
                ->where('assigned_to', auth()->id());

            // Apply filters if provided
            if ($request->filled('order_id')) {
                $query->where('id', 'like', '%' . $request->order_id . '%');
            }

            if ($request->filled('status')) {
                $query->where('status_manage_by_admin', $request->status);
            }

            if ($request->filled('min_inboxes')) {
                $query->whereHas('reorderInfo', function($q) use ($request) {
                    $q->where('total_inboxes', '>=', $request->min_inboxes);
                });
            }

            if ($request->filled('max_inboxes')) {
                $query->whereHas('reorderInfo', function($q) use ($request) {
                    $q->where('total_inboxes', '<=', $request->max_inboxes);
                });
            }

            // Apply ordering
            $order = $request->get('order', 'desc');
            $query->orderBy('id', $order);

            // Pagination parameters
            $perPage = $request->get('per_page', 12);
            $page = $request->get('page', 1);
            
            // Get paginated results
            $paginatedOrders = $query->paginate($perPage, ['*'], 'page', $page);

            // Format orders data for the frontend
            $ordersData = $paginatedOrders->getCollection()->map(function ($order) {
                $reorderInfo = $order->reorderInfo->first();
                $orderPanels = $order->orderPanels;
                
                // Calculate total domains count from all splits
                $totalDomainsCount = 0;
                $totalInboxes = 0;
                
                foreach ($orderPanels as $orderPanel) {
                    foreach ($orderPanel->orderPanelSplits as $split) {
                        if ($split->domains && is_array($split->domains)) {
                            $totalDomainsCount += count($split->domains);
                        }
                        $totalInboxes += $split->inboxes_per_domain * (is_array($split->domains) ? count($split->domains) : 0);
                    }
                }
                
                $inboxesPerDomain = $reorderInfo ? $reorderInfo->inboxes_per_domain : 0;
                $finalTotalInboxes = $reorderInfo ? $reorderInfo->total_inboxes : $totalInboxes;
                $finalTotalDomains = $reorderInfo && $reorderInfo->total_inboxes && $inboxesPerDomain > 0 
                    ? intval($reorderInfo->total_inboxes / $inboxesPerDomain) 
                    : $totalDomainsCount;

                // Format splits data for the frontend
                $splitsData = [];
                foreach ($orderPanels as $orderPanel) {
                    foreach ($orderPanel->orderPanelSplits as $split) {
                        $domains = [];
                        if ($split->domains && is_array($split->domains)) {
                            $domains = $split->domains;
                        }
                        
                        $splitsData[] = [
                            'id' => $split->id,
                            'order_panel_id' => $orderPanel->id,
                            'panel_id' => $orderPanel->panel_id,
                            'panel_title' => $orderPanel->panel->title ?? 'N/A',
                            'inboxes_per_domain' => $split->inboxes_per_domain,
                            'domains' => $domains,
                            'domains_count' => count($domains),
                            'total_inboxes' => $split->inboxes_per_domain * count($domains),
                            'status' => $orderPanel->status ?? 'unallocated',
                            'created_at' => $split->created_at
                        ];
                    }
                }
                // Calculate status HTML before the return array
                $statusKey = strtolower($order->status_manage_by_admin ?? 'N/A');
                $statusHtml = '<span style="font-size:11px !important" class="py-1 px-1 text-' . ($this->statuses[$statusKey] ?? 'warning') . ' border border-' . ($this->statuses[$statusKey] ?? 'warning') . ' rounded-2 bg-transparent fs-6">' . ucfirst($statusKey) . '</span>';

                return [
                    'id' => $order->id,
                    'order_id' => $order->id,
                    'customer_name' => $order->user->name ?? 'N/A',
                    'customer_image' => $order->user->profile_image ? asset('storage/profile_images/' . $order->user->profile_image) : null,
                    'total_inboxes' => $finalTotalInboxes,
                    'inboxes_per_domain' => $inboxesPerDomain,
                    'total_domains' => $finalTotalDomains,
                    'status' => $order->status_manage_by_admin ?? 'pending',
                    'status_manage_by_admin_html' => $statusHtml,
                    'status_manage_by_admin' => $statusHtml,
                    'created_at' => $order->created_at,
                    'completed_at' => $order->completed_at,
                    'timer_started_at' => $order->timer_started_at ? $order->timer_started_at->toISOString() : null,
                    'timer_paused_at' => $order->timer_paused_at ? $order->timer_paused_at->toISOString() : null,
                    'total_paused_seconds' => $order->total_paused_seconds ?? 0,
                    'order_panels_count' => $orderPanels->count(),
                    'splits_count' => $orderPanels->sum(function($panel) {
                        return $panel->orderPanelSplits->count();
                    }),
                    'splits' => $splitsData
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ordersData,
                'pagination' => [
                    'current_page' => $paginatedOrders->currentPage(),
                    'last_page' => $paginatedOrders->lastPage(),
                    'per_page' => $paginatedOrders->perPage(),
                    'total' => $paginatedOrders->total(),
                    'has_more_pages' => $paginatedOrders->hasMorePages(),
                    'from' => $paginatedOrders->firstItem(),
                    'to' => $paginatedOrders->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching assigned orders data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching orders data: ' . $e->getMessage()
            ], 500);
        }
    }
}
