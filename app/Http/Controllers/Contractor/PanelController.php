<?php

namespace App\Http\Controllers\Contractor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Panel;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\UserOrderPanelAssignment; 
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use DataTables;
use App\Models\Status; // Assuming you have a Status model for order statuses
//models

class PanelController extends Controller
{
    private $statuses;
    public function __construct()
    {
        $this->statuses = Status::where('name', '!=', 'draft')->pluck('badge', 'name')->toArray();
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getOrdersData($request);
        }

        return view('contractor.panel.index');
    }    
    public function getOrdersData(Request $request)
    {
        try {
            $query = Order::with(['reorderInfo', 'orderPanels.orderPanelSplits', 'orderPanels.panel'])
                ->whereHas('orderPanels')
                ->whereNull('assigned_to') // Only show unassigned orders
                // order status_manage_by_admin pending
                ->where('status_manage_by_admin', 'pending');

            // Apply filters if provided
            if ($request->filled('order_id')) {
                $query->where('id', 'like', '%' . $request->order_id . '%');
            }

            if ($request->filled('status')) {
                $query->whereHas('orderPanels', function($q) use ($request) {
                    $q->where('status', $request->status);
                });
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
            $perPage = $request->get('per_page', 12); // Default 12 orders per page
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
                return [
                    'id' => $order->id,
                    'order_id' => $order->id,
                    'customer_name' => $order->user->name ?? 'N/A',
                    'customer_profile_image' => $order->user->profile_image ? url('storage/profile_images/' . $order->user->profile_image) : null,
                    'total_inboxes' => $reorderInfo ? $reorderInfo->total_inboxes : $totalInboxes,
                    'inboxes_per_domain' => $inboxesPerDomain,
                    'total_domains' => $totalDomainsCount,
                    'status' => $order->status_manage_by_admin ?? 'pending',
                    'status_manage_by_admin' => (function() use ($order) {
                        $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                        $statusKey = $status;
                        $statusClass = $this->statuses[$statusKey] ?? 'secondary';
                        return '<span class="py-1 px-1 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent fs-6" style="font-size: 11px !important;">' 
                            . ucfirst($status) . '</span>';
                    })(),
                    'created_at' => $order->created_at,
                    'timer_started_at' => $order->timer_started_at ? $order->timer_started_at->toISOString() : null,
                    'timer_started_at_1' => $order->timer_started_at ? $order->timer_started_at->copy()->subSeconds($order->total_paused_seconds)->toISOString() : null,
                    'completed_at' => $order->completed_at,
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
            return response()->json([
                'success' => false,
                'message' => 'Error fetching orders data: ' . $e->getMessage()
            ], 500);
        }
    }
    

    public function getOrderSplits($orderId, Request $request)
    {
        try {
            $order = Order::with(['user', 'reorderInfo', 'orderPanels.orderPanelSplits', 'orderPanels.panel'])
                ->findOrFail($orderId);
            
            $reorderInfo = $order->reorderInfo->first();
            $orderPanels = $order->orderPanels;
            
            // Format splits data
            $splitsData = [];
            
            foreach ($orderPanels as $orderPanel) {
                foreach ($orderPanel->orderPanelSplits as $split) {
                    $domains = [];
                    if ($split->domains && is_array($split->domains)) {
                        $domains = $split->domains;
                    }
                    
                    $splitsData[] = [
                        'id' => $split->id,
                        'panel_id' => $orderPanel->panel_id,
                        'panel_title' => $orderPanel->panel->title ?? 'N/A',
                        'order_panel_id' => $orderPanel->id,
                        'inboxes_per_domain' => $split->inboxes_per_domain,
                        'order_panel'=>$orderPanel,
                        'domains' => $domains,
                        'domains_count' => count($domains),
                        'total_inboxes' => $split->inboxes_per_domain * count($domains),
                        'status' => $orderPanel->status,
                        'created_at' => $split->created_at
                        
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'customer_name' => $order->user->name ?? 'N/A',
                    'created_at' => $order->created_at,
                    'completed_at' => $order->completed_at,
                    'status' => $order->status_manage_by_admin ?? 'pending',
                    'status_manage_by_admin' => (function() use ($order) {
                        $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                        $statusKey = $status;
                        $statusClass = $this->statuses[$statusKey] ?? 'secondary';
                        return '<span style="font-size: 11px !important;" class="py-1 px-1 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                            . ucfirst($status) . '</span>';
                    })(),
                ],
                'reorder_info' => $reorderInfo ? [
                    'total_inboxes' => $reorderInfo->total_inboxes,
                    'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
                    'hosting_platform' => $reorderInfo->hosting_platform,
                    'platform_login' => $reorderInfo->platform_login,
                    'platform_password' => $reorderInfo->platform_password,
                    'forwarding_url' => $reorderInfo->forwarding_url,
                    'sending_platform' => $reorderInfo->sending_platform,
                    'sequencer_login' => $reorderInfo->sequencer_login,
                    'sequencer_password' => $reorderInfo->sequencer_password,
                    'first_name' => $reorderInfo->first_name,
                    'last_name' => $reorderInfo->last_name,
                    'email_persona_password' => $reorderInfo->email_persona_password,
                    'profile_picture_link' => $reorderInfo->profile_picture_link,
                    'prefix_variants' => $reorderInfo->prefix_variants,
                    'prefix_variant_1' => $reorderInfo->prefix_variant_1,
                    'prefix_variant_2' => $reorderInfo->prefix_variant_2,
                ] : null,
                'splits' => $splitsData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching order splits: ' . $e->getMessage()
            ], 500);
        }
    }

   
    
    /**
     * Test method to verify database connectivity and basic data retrieval
     */
    public function test()
    {
        try {
            $panelCount = Panel::count();
            $orderPanelCount = OrderPanel::count();
            $orderPanelSplitCount = OrderPanelSplit::count();
            
            return response()->json([
                'success' => true,
                'message' => 'Database connection successful',
                'data' => [
                    'panels_count' => $panelCount,
                    'order_panels_count' => $orderPanelCount,
                    'order_panel_splits_count' => $orderPanelSplitCount,
                ],
                'sample_panels' => Panel::with(['orderPanels'])->limit(3)->get()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], 500);
        }
    }




    //extras
   


    public function Contractorindex(Request $request)
    {
        if ($request->ajax()) {
            try {
                $data = Panel::where('is_active',true)->with(['order_panels.orderPanelSplit'])->get(); 
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('actions', function ($row) {
                        return '
                            <button data-id="'.$row->id.'" class="btn btn-sm btn-primary editBtn">Edit</button>
                            <button data-id="'.$row->id.'" class="btn btn-sm btn-danger deleteBtn">Delete</button>
                        ';
                    })
                    ->rawColumns(['actions'])
                    ->make(true);
            } catch (\Exception $e) {
                Log::error('Panel DataTable Error: ' . $e->getMessage());
                return response()->json(['message' => 'Something went wrong while fetching panels.'.$e->getMessage()], 500);
            }
        }

        return view('contractor.panels.index');
    }

   

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'created_by' => 'nullable|string|max:255',
            ]);

            $panel = Panel::create($data);
            return response()->json(['message' => 'Panel created successfully', 'panel' => $panel], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create panel'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $panel = Panel::findOrFail($id);

            $data = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'limit' => 'nullable|integer|min:1',
                'is_active' => 'boolean',
                'created_by' => 'nullable|string|max:255',
            ]);

            $panel->update($data);

            return response()->json(['message' => 'Panel updated successfully', 'panel' => $panel]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Panel not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update panel'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $panel = Panel::findOrFail($id);
            $panel->delete();
            return response()->json(['message' => 'Panel deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Panel not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete panel'], 500);
        }
    }

    public function show($id)
    {
        try {
            $panel = Panel::with('users')->findOrFail($id);
            return response()->json($panel);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Panel not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve panel'], 500);
        }
    }

    public function assignUserToPanel(Request $request, $panelId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $panel = Panel::findOrFail($panelId);

        $panel->users()->syncWithoutDetaching([
            $request->user_id => ['accepted_at' => now()],
        ]);

        return response()->json(['message' => 'User assigned.']);
    }

    public function releaseUserFromPanel(Request $request, $panelId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $panel = Panel::findOrFail($panelId);

        $panel->users()->updateExistingPivot($request->user_id, ['released_at' => now()]);

        return response()->json(['message' => 'User released.']);
    }


 public function assignPanelToUser(Request $request, $order_panel_id)
 {
    $user = Auth::user();

    // Find the order panel with relationships of.....
    $order_panel = OrderPanel::where('id', $order_panel_id)
        ->with(['panel', 'order.orderInfo'])
        ->first();

    if (!$order_panel) {
        return response()->json(['message' => 'Order panel not found.'], 404);
    }

       $order_panel_split = OrderPanelSplit::where('order_panel_id', $order_panel->id)
        ->where('order_id', $order_panel->order_id)
        ->first();

    if (!$order_panel_split) {
        return response()->json(['message' => 'Order panel split not found.'], 404);
    }

    // Check if this panel is already assigned to another user
    $existingAssignment = UserOrderPanelAssignment::where([
        'order_panel_id' => $order_panel->id,
        'order_id' => $order_panel->order_id,                       
        'order_panel_split_id' => $order_panel_split->id,
    ])->first();

    if ($existingAssignment && $existingAssignment->contractor_id !== $user->id) {
        return response()->json(['message' => 'This panel is already assigned to another user.'], 403);
    }

    // Create or update the assignment
    UserOrderPanelAssignment::updateOrCreate(
        [
            'order_panel_id' => $order_panel->id,
            'contractor_id' => $user->id,
        ],
        [
            'order_id' => $order_panel->order_id,
            'order_panel_split_id' => $order_panel_split->id,
        ]
    );

    // Update the status of the order panel (example: to "assigned")
    $order_panel->status = 'assigned'; // or a status code like 1
    $order_panel->save();

      return response()->json(['message' => 'Panel assigned successfully.'], 200);
    }


 public function showAssingedSplitDetail(Request $request, $assigned_panel_id ){
            $assignedPanel=UserOrderPanelAssignment::where('id',$assigned_panel_id)->first();
            if (!$assignedPanel) {
                return response()->json(['message' => 'Assigned panel not found.'], 404);
            }
            $orderPanel = OrderPanel::with(['panel', 'order.orderInfo'])
                ->where('id', $assignedPanel->order_panel_id)
                ->first();

            if (!$orderPanel) {
                return response()->json(['message' => 'Order panel not found.'], 404);
            }

            $orderPanelSplit = OrderPanelSplit::where('id', $assignedPanel->order_panel_split_id)
                ->first();

            if(!$orderPanelSplit){
            return repsonse()->json(['message' => 'Order panel split not found.'], 404);
            }else{
                return response()->json(['orderPanelSplit'=>$orderPanelSplit],200);
            }

            
        }

        //mark panel as completed
 public function markOrderPanelAsStatus(Request $request, $assigned_panel_id)
 {
    $assignedPanel = UserOrderPanelAssignment::where('id', $assigned_panel_id)->first();
    if (!$assignedPanel) {
        return response()->json(['message' => 'Assigned panel not found.'], 404);
    }

    // Update the status of the order panel to 'completed'
    $orderPanel = OrderPanel::find($assignedPanel->order_panel_id);
    if (!$orderPanel) {
        return response()->json(['message' => 'Order panel not found.'], 404);
    }

    $orderPanel->status = 'completed'; // or a status code like 2
    $orderPanel->save();

    return response()->json(['message' => 'Panel marked as completed successfully.'], 200);

 }

    public function getPanelById($id)
    {
        try {
            $panel = Panel::with('users')->findOrFail($id);
            return response()->json($panel);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Panel not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve panel'], 500);
        }
    }

    public function getOrderPanelById($id)
    {
        try {
            $orderPanel = OrderPanel::with(['panel', 'order.orderInfo'])->findOrFail($id);
            return response()->json($orderPanel);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Order panel not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve order panel'], 500);
        }
    }

    public function getOrderPanelSplitById($id)
    {
        try {
            $orderPanelSplit = OrderPanelSplit::with(['orderPanel', 'orderPanel.order'])->findOrFail($id);
            return response()->json($orderPanelSplit);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Order panel split not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve order panel split'], 500);
        }
    }
    public function getUserOrderPanelAssignmentById($id)
    {
        try {
            $assignment = UserOrderPanelAssignment::with(['orderPanel', 'orderPanelSplit'])->findOrFail($id);
            return response()->json($assignment);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User order panel assignment not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve user order panel assignment'], 500);
        }
    }


    public function getAssignedPanelsByUserId($userId)
    {
        try {
            $assignments = UserOrderPanelAssignment::with(['orderPanel', 'orderPanelSplit'])
                ->where('contractor_id', $userId)
                ->get();

            return response()->json($assignments);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve assigned panels'], 500);
        }
    }

    /**
     * Assign an order panel to the current contractor
     */
    public function assignOrderToMe(Request $request, $orderPanelId)
    {
        try {
            $contractorId = Auth::id();
            
            // Check if the order panel exists
            $orderPanel = OrderPanel::find($orderPanelId);
            if (!$orderPanel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order panel not found'
                ], 404);
            }

            // Get the order panel split - fix the query
            $order_panel_split = OrderPanelSplit::where('order_panel_id', $orderPanelId)
                ->where('order_id', $orderPanel->order_id)
                ->first();

            if (!$order_panel_split) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order panel split not found'
                ], 404);
            }

            // Check if already assigned to someone
            $existingAssignment = UserOrderPanelAssignment::where('order_panel_id', $orderPanelId)->first();
            if ($existingAssignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'This order is already assigned to another contractor'
                ], 409);
            }

            // Create the assignment using updateOrCreate for safety
            $assignment = UserOrderPanelAssignment::updateOrCreate(
                [
                    'order_panel_id' => $orderPanelId,
                    'contractor_id' => $contractorId,
                ],
                [
                    'order_id' => $orderPanel->order_id,
                    'order_panel_split_id' => $order_panel_split->id,
                ]
            );

            // Update the order panel status to allocated
            $orderPanel->status = 'allocated';
            // order_panel add contractor_id
            $orderPanel->contractor_id = $contractorId; // Assuming you want to track who allocated it
            $orderPanel->save();

            return response()->json([
                'success' => true,
                'message' => 'Order successfully assigned to you',
                'assignment' => $assignment
            ]);

        } catch (\Exception $e) {
            Log::error('Order assignment failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign order. Please try again.'
            ], 500);
        }
    }

}
