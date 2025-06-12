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
//models

class PanelController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getPanelsData($request);
        }

        return view('contractor.panel.index');
    }    
    public function getPanelsData(Request $request)
    {
        try {
            $query = Panel::with(['order_panels.order', 'order_panels.orderPanelSplits'])
                ->withCount('order_panels as total_orders');

            // Apply filters if provided
            if ($request->filled('panel_id')) {
                // PNL-00 remove string PNL
                $request->panel_id = str_replace('PNL-', '', $request->panel_id);
                $query->where('id', 'like', '%' . $request->panel_id . '%');
            }

            if ($request->filled('min_inbox_limit')) {
                $query->where('limit', '>=', $request->min_inbox_limit);
            }

            if ($request->filled('max_inbox_limit')) {
                $query->where('limit', '<=', $request->max_inbox_limit);
            }

            if ($request->filled('min_remaining')) {
                $query->where('remaining_limit', '>=', $request->min_remaining);
            }

            if ($request->filled('max_remaining')) {
                $query->where('remaining_limit', '<=', $request->max_remaining);
            }

            // Apply ordering
            $order = $request->get('order', 'desc');
            $query->orderBy('id', $order);

            // Pagination parameters
            $perPage = $request->get('per_page', 12); // Default 12 panels per page
            $page = $request->get('page', 1);
            
            // Get paginated results
            $paginatedPanels = $query->paginate($perPage, ['*'], 'page', $page);

            // Format panels data for the frontend
            $panelsData = $paginatedPanels->getCollection()->map(function ($panel) {
                $used = $panel->limit - $panel->remaining_limit;
                
                // Get recent orders for this panel
                $recentOrders = OrderPanel::with('order')
                    ->where('panel_id', $panel->id)
                    ->orderBy('created_at', 'desc')
                    // ->limit(5)
                    ->get();
                // dd('ok');
                return [
                    'id' => $panel->id,
                    'auto_generated_id' => $panel->auto_generated_id,
                    'title' => $panel->title,
                    'description' => $panel->description,
                    'limit' => $panel->limit,
                    'used' => $used,
                    'remaining_limit' => $panel->remaining_limit,
                    'is_active' => $panel->is_active,
                    'created_by' => $panel->created_by,
                    'created_at' => $panel->created_at,
                    'total_orders' => $panel->total_orders,
                    'recent_orders' => $recentOrders->map(function ($orderPanel) {
                        return [
                            'id' => $orderPanel->order->id ?? 'N/A',
                            'space_assigned' => $orderPanel->space_assigned,
                            'status' => $orderPanel->status,
                            'created_at' => $orderPanel->created_at,
                            'order_id' => $orderPanel->order->id ?? null,
                        ];
                    }),
                    'usage_percentage' => $panel->limit > 0 ? round(($used / $panel->limit) * 100, 2) : 0,
                ];
            });

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
                    'to' => $paginatedPanels->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching panels data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getPanelOrders($panelId, Request $request)
    {
        try {
            $panel = Panel::findOrFail($panelId);
            
            $orders = OrderPanel::with(['order.user', 'order.reorderInfo', 'orderPanelSplits'])
                ->where('panel_id', $panelId)
                ->orderBy('created_at', 'desc')
                ->get();

            $ordersData = $orders->map(function ($orderPanel) use ($request) {
                $order = $orderPanel->order;
                $splits = $orderPanel->orderPanelSplits;
                $reorderInfo = $order->reorderInfo->first();
                
                // Check if this order is assigned to the current user
                $isAssignedToCurrentUser = false;
                $assignmentStatus = $orderPanel->status;
                
                if (auth()->check()) {
                    $userAssignment = UserOrderPanelAssignment::where('order_panel_id', $orderPanel->id)
                        ->where('contractor_id', auth()->id())
                        ->first();
                    
                    if ($userAssignment) {
                        $isAssignedToCurrentUser = true;
                        $assignmentStatus = 'assigned_to_me';
                    }
                }
                
                return [
                    'order_panel_id' => $orderPanel->id,
                    'order_id' => $order->id ?? 'N/A',
                    'customer_name' => $order->user->name ?? 'N/A',
                    'space_assigned' => $orderPanel->space_assigned,
                    'inboxes_per_domain' => $orderPanel->inboxes_per_domain,
                    'status' => $orderPanel->status,
                    'assignment_status' => $assignmentStatus,
                    'is_assigned_to_current_user' => $isAssignedToCurrentUser,
                    'domains_count' => $splits->sum(function ($split) {
                        return is_array($split->domains) ? count($split->domains) : 0;
                    }),
                    'created_at' => $orderPanel->created_at->format('Y-m-d H:i:s'),
                    'accepted_at' => $orderPanel->accepted_at,
                    'released_at' => $orderPanel->released_at,
                    // Add comprehensive order information
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
                    // Add splits with domain information
                    'splits' => $splits->map(function ($split) {
                        return [
                            'id' => $split->id,
                            'space_assigned' => $split->inboxes_per_domain * (is_array($split->domains) ? count($split->domains) : 0),
                            'inboxes_per_domain' => $split->inboxes_per_domain,
                            'domains' => $split->domains,
                            'domains_count' => is_array($split->domains) ? count($split->domains) : 0,
                            'created_at' => $split->created_at->format('Y-m-d H:i:s'),
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'panel' => [
                    'id' => $panel->id,
                    'auto_generated_id' => $panel->auto_generated_id,
                    'title' => $panel->title,
                    'limit' => $panel->limit,
                    'remaining_limit' => $panel->remaining_limit,
                ],
                'orders' => $ordersData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching panel orders: ' . $e->getMessage()
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
