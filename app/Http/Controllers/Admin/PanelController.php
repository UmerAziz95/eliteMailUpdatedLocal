<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Panel;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use App\Models\UserOrderPanelAssignment; 

class PanelController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getPanelsData($request);
        } 

        return view('admin.panels.index');
    }    
    // public function getPanelsData(Request $request)
    // {
    //     try {
    //         $query = Panel::with(['order_panels.order', 'order_panels.orderPanelSplits'])
    //             ->withCount('order_panels as total_orders');

    //         // Apply filters if provided
    //         if ($request->filled('panel_id')) {
    //             // PNL-35 remove string PNL
    //             $request->panel_id = str_replace('PNL-', '', $request->panel_id);
    //             $query->where('id', 'like', '%' . $request->panel_id . '%');
    //         }

    //         if ($request->filled('min_inbox_limit')) {
    //             $query->where('limit', '>=', $request->min_inbox_limit);
    //         }

    //         if ($request->filled('max_inbox_limit')) {
    //             $query->where('limit', '<=', $request->max_inbox_limit);
    //         }

    //         if ($request->filled('min_remaining')) {
    //             $query->where('remaining_limit', '>=', $request->min_remaining);
    //         }

    //         if ($request->filled('max_remaining')) {
    //             $query->where('remaining_limit', '<=', $request->max_remaining);
    //         }

    //         // Apply ordering
    //         $order = $request->get('order', 'desc');
    //         $query->orderBy('created_at', $order);

    //         // Pagination parameters
    //         $perPage = $request->get('per_page', 12); // Default 12 panels per page
    //         $page = $request->get('page', 1);
            
    //         // Get paginated results
    //         $paginatedPanels = $query->paginate($perPage, ['*'], 'page', $page);

    //         // Format panels data for the frontend
    //         $panelsData = $paginatedPanels->getCollection()->map(function ($panel) {
    //             $used = $panel->limit - $panel->remaining_limit;
                
    //             // Get recent orders for this panel
    //             $recentOrders = OrderPanel::with('order')
    //                 ->where('panel_id', $panel->id)
    //                 ->orderBy('created_at', 'desc')
    //                 // ->limit(5)
    //                 ->get();
    //             // dd('ok');
    //             return [
    //                 'id' => $panel->id,
    //                 'auto_generated_id' => $panel->auto_generated_id,
    //                 'title' => $panel->title,
    //                 'description' => $panel->description,
    //                 'limit' => $panel->limit,
    //                 'used' => $used,
    //                 'remaining_limit' => $panel->remaining_limit,
    //                 'is_active' => $panel->is_active,
    //                 'created_by' => $panel->created_by,
    //                 'created_at' => $panel->created_at,
    //                 'total_orders' => $panel->total_orders,
    //                 'recent_orders' => $recentOrders->map(function ($orderPanel) {
    //                     return [
    //                         'id' => $orderPanel->order->id ?? 'N/A',
    //                         'space_assigned' => $orderPanel->space_assigned,
    //                         'status' => $orderPanel->status,
    //                         'created_at' => $orderPanel->created_at,
    //                         'order_id' => $orderPanel->order->id ?? null,
    //                     ];
    //                 }),
    //                 'usage_percentage' => $panel->limit > 0 ? round(($used / $panel->limit) * 100, 2) : 0,
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'data' => $panelsData,
    //             'pagination' => [
    //                 'current_page' => $paginatedPanels->currentPage(),
    //                 'last_page' => $paginatedPanels->lastPage(),
    //                 'per_page' => $paginatedPanels->perPage(),
    //                 'total' => $paginatedPanels->total(),
    //                 'has_more_pages' => $paginatedPanels->hasMorePages(),
    //                 'from' => $paginatedPanels->firstItem(),
    //                 'to' => $paginatedPanels->lastItem()
    //             ]
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error fetching panels data: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
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

    // public function getPanelOrders($panelId, Request $request)
    // {
    //     try {
    //         $panel = Panel::findOrFail($panelId);
            
    //         $orders = OrderPanel::with(['order.user', 'order.reorderInfo', 'orderPanelSplits'])
    //             ->where('panel_id', $panelId)
    //             ->orderBy('created_at', 'desc')
    //             ->get();

    //         $ordersData = $orders->map(function ($orderPanel) {
    //             $order = $orderPanel->order;
    //             $splits = $orderPanel->orderPanelSplits;
    //             $reorderInfo = $order->reorderInfo->first();
                
    //             return [
    //                 'order_panel_id' => $orderPanel->id,
    //                 'order_id' => $order->id ?? 'N/A',
    //                 'customer_name' => $order->user->name ?? 'N/A',
    //                 'space_assigned' => $orderPanel->space_assigned,
    //                 'inboxes_per_domain' => $orderPanel->inboxes_per_domain,
    //                 'status' => $orderPanel->status,
    //                 'domains_count' => $splits->sum(function ($split) {
    //                     return is_array($split->domains) ? count($split->domains) : 0;
    //                 }),
    //                 'created_at' => $orderPanel->created_at->format('Y-m-d H:i:s'),
    //                 'accepted_at' => $orderPanel->accepted_at,
    //                 'released_at' => $orderPanel->released_at,
    //                 // Add comprehensive order information
    //                 'reorder_info' => $reorderInfo ? [
    //                     'total_inboxes' => $reorderInfo->total_inboxes,
    //                     'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
    //                     'hosting_platform' => $reorderInfo->hosting_platform,
    //                     'platform_login' => $reorderInfo->platform_login,
    //                     'platform_password' => $reorderInfo->platform_password,
    //                     'forwarding_url' => $reorderInfo->forwarding_url,
    //                     'sending_platform' => $reorderInfo->sending_platform,
    //                     'sequencer_login' => $reorderInfo->sequencer_login,
    //                     'sequencer_password' => $reorderInfo->sequencer_password,
    //                     'first_name' => $reorderInfo->first_name,
    //                     'last_name' => $reorderInfo->last_name,
    //                     'email_persona_password' => $reorderInfo->email_persona_password,
    //                     'profile_picture_link' => $reorderInfo->profile_picture_link,
    //                     'prefix_variants' => $reorderInfo->prefix_variants,
    //                     'prefix_variant_1' => $reorderInfo->prefix_variant_1,
    //                     'prefix_variant_2' => $reorderInfo->prefix_variant_2,
    //                 ] : null,
    //                 // Add splits with domain information
    //                 'splits' => $splits->map(function ($split) {
    //                     return [
    //                         'id' => $split->id,
    //                         'space_assigned' => $split->inboxes_per_domain * (is_array($split->domains) ? count($split->domains) : 0),
    //                         'inboxes_per_domain' => $split->inboxes_per_domain,
    //                         'domains' => $split->domains,
    //                         'domains_count' => is_array($split->domains) ? count($split->domains) : 0,
    //                         'created_at' => $split->created_at->format('Y-m-d H:i:s'),
    //                     ];
    //                 }),
    //             ];
    //         });

    //         return response()->json([
    //             'success' => true,
    //             'panel' => [
    //                 'id' => $panel->id,
    //                 'auto_generated_id' => $panel->auto_generated_id,
    //                 'title' => $panel->title,
    //                 'limit' => $panel->limit,
    //                 'remaining_limit' => $panel->remaining_limit,
    //             ],
    //             'orders' => $ordersData
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error fetching panel orders: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
   public function getPanelOrders($panelId, Request $request)
    {
        try {
            $panel = Panel::findOrFail($panelId);
            
            $orders = OrderPanel::with(['order.user','order', 'order.reorderInfo', 'orderPanelSplits'])
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

                // Get remaining order panels for the same order_id
                $remainingOrderPanels = OrderPanel::with(['orderPanelSplits','order', 'panel'])
                    ->where('order_id', $order->id)
                    ->where('id', '!=', $orderPanel->id) // Exclude current order panel
                    ->get()
                    ->map(function ($remainingPanel) {
                        $remainingSplits = $remainingPanel->orderPanelSplits;
                        
                        // Check assignment status for remaining panels
                        $remainingAssignment = UserOrderPanelAssignment::where('order_panel_id', $remainingPanel->id)->first();
                        
                        return [
                            'order_panel_id' => $remainingPanel->id,
                            'panel_id' => $remainingPanel->panel_id,
                            'panel_title' => $remainingPanel->panel->title ?? 'N/A',
                            'space_assigned' => $remainingPanel->space_assigned,
                            'inboxes_per_domain' => $remainingPanel->inboxes_per_domain,
                            'status' => $remainingPanel->status,
                            'contractor_id' => $remainingAssignment ? $remainingAssignment->contractor_id : null,
                            'is_assigned' => $remainingAssignment ? true : false,
                            'domains_count' => $remainingSplits->sum(function ($split) {
                                return is_array($split->domains) ? count($split->domains) : 0;
                            }),
                            'total_inboxes' => $remainingSplits->sum(function ($split) {
                                return $split->inboxes_per_domain * (is_array($split->domains) ? count($split->domains) : 0);
                            }),
                            'created_at' => $remainingPanel->created_at->format('Y-m-d H:i:s'),
                            'splits' => $remainingSplits->map(function ($split) use ($remainingPanel) {
                                // Get contractor assignment for this split
                                $contractorAssignment = UserOrderPanelAssignment::where('order_panel_id', $remainingPanel->id)->first();
                                
                                return [
                                    'id' => $split->id,
                                    'panel_id' => $remainingPanel->panel_id,
                                    'space_assigned' => $split->inboxes_per_domain * (is_array($split->domains) ? count($split->domains) : 0),
                                    'inboxes_per_domain' => $split->inboxes_per_domain,
                                    'domains' => is_array($split->domains) ? collect($split->domains)->map(function($domain) {
                                        return [
                                            'domain' => is_string($domain) ? $domain : ($domain['domain'] ?? $domain),
                                            'id' => is_array($domain) ? ($domain['id'] ?? null) : null
                                        ];
                                    })->toArray() : [],
                                    'domains_count' => is_array($split->domains) ? count($split->domains) : 0,
                                    'status' => $split->status ?? 'unallocated',
                                    'contractor_id' => $contractorAssignment ? $contractorAssignment->contractor_id : null,
                                    'created_at' => $split->created_at ? $split->created_at->format('Y-m-d H:i:s') : null,
                                ];
                            }),
                        ];
                    });
                
                return [
                    'order_panel_id' => $orderPanel->id,
                    'panel_id' => $orderPanel->panel_id,
                    'order_id' => $order->id ?? 'N/A',
                    'timer_order'=>$order,
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
                    // Add splits with enhanced domain information and status
                    'splits' => $splits->map(function ($split) use ($orderPanel) {
                        // Get contractor assignment for this split
                        $contractorAssignment = UserOrderPanelAssignment::where('order_panel_id', $orderPanel->id)->first();
                        
                        return [
                            'id' => $split->id,
                            'panel_id' => $orderPanel->panel_id,
                            'space_assigned' => $split->inboxes_per_domain * (is_array($split->domains) ? count($split->domains) : 0),
                            'inboxes_per_domain' => $split->inboxes_per_domain,
                            'domains' => is_array($split->domains) ? collect($split->domains)->map(function($domain) {
                                return [
                                    'domain' => is_string($domain) ? $domain : ($domain['domain'] ?? $domain),
                                    'id' => is_array($domain) ? ($domain['id'] ?? null) : null
                                ];
                            })->toArray() : [],
                            'domains_count' => is_array($split->domains) ? count($split->domains) : 0,
                            'status' => $split->status ?? 'unallocated',
                            'contractor_id' => $contractorAssignment ? $contractorAssignment->contractor_id : null,
                            'created_at' => $split->created_at ? $split->created_at->format('Y-m-d H:i:s') : null,
                        ];
                    }),
                    // Add remaining order panels for the same order
                    'remaining_order_panels' => $remainingOrderPanels,
                    'remaining_panels_count' => $remainingOrderPanels->count(),
                    'total_order_panels' => $remainingOrderPanels->count() + 1, // Including current panel
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
                'orders' => $ordersData,
                

            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching panel orders: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getOrdersSplits(Request $request, $orderId){

        try{
           
            $order= Order::find($orderId);
            if(!$order){
                return response()->json([
                    'success'=>false,
                    'message'=>'Order not found'
                ],404);
            } 
            $orderPanel = OrderPanel::with(['panel','orderPanelSplits','order.reorderInfo'])
                ->where('order_id', $orderId)
                ->get(); 
            //  $panel = Panel::findOrFail($panelId);
            
            // $orders = OrderPanel::with(['order.user', 'order.reorderInfo', 'orderPanelSplits'])
            //     ->where('panel_id', $panelId)
            //     ->orderBy('created_at', 'desc')
            //     ->get();
            return response()->json([
                'success'=>true,
                'data'=>$orderPanel
            ],200);

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

    
    public function createPanel(Request $request)
    {
        try {
            $data = $request->validate([
                'panel_title' => 'nullable|string|max:255',
                'panel_description' => 'nullable|string',
                'panel_status' => 'in:0,1',
                'panel_limit' => 'required|integer|min:1',
                
            ]);

            $panel = Panel::create([
                'title' => $data['panel_title'] ?? null,
                'description' => $data['panel_description'] ?? null,
                'is_active' => $data['panel_status'] ?? 1,
                'limit' => $data['panel_limit'],
                'created_by' => auth()->user()->name, // Assuming the user is authenticated
            ]);
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
}
