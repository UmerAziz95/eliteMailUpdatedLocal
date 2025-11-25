<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Panel;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\Order;
use App\Models\OrderEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use App\Models\UserOrderPanelAssignment; 
use App\Models\OrderTracking;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Configuration;
use Illuminate\Validation\Rule;

class PanelController extends Controller
{
    // getNextId
    public function getNextId(Request $request)
    {
        $allowedProviders = Configuration::getProviderTypes();
        if (empty($allowedProviders)) {
            $allowedProviders = ['Google', 'Microsoft 365', 'Private SMTP'];
        }

        $defaultProviderType = Configuration::get('PROVIDER_TYPE', $allowedProviders[0] ?? 'Google');
        $providerType = $request->query('provider_type', $defaultProviderType);

        if (!in_array($providerType, $allowedProviders, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid provider type selected.',
            ], 422);
        }

        $nextSerial = Panel::getNextSerialForProvider($providerType);
        $capacity = $this->getProviderCapacity($providerType);

        return response()->json([
            'next_id' => 'PNL-' . $nextSerial,
            'panel_sr_no' => $nextSerial,
            'provider_type' => $providerType,
            'capacity' => $capacity,
        ]);
    }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return $this->getPanelsData($request);
        } 

        $configuredProviderTypes = Configuration::getProviderTypes();
        if (empty($configuredProviderTypes)) {
            $configuredProviderTypes = ['Google', 'Microsoft 365', 'Private SMTP'];
        }

        $providerTypes = array_values(array_filter($configuredProviderTypes, function ($type) {
            return $type !== 'Private SMTP';
        }));
        if (empty($providerTypes)) {
            $providerTypes = ['Google', 'Microsoft 365'];
        }

        $defaultProviderType = Configuration::get('PROVIDER_TYPE', $configuredProviderTypes[0] ?? 'Google');
        if (!in_array($defaultProviderType, $providerTypes, true)) {
            $defaultProviderType = $providerTypes[0];
        }

        $providerCapacities = [];
        foreach ($providerTypes as $provider) {
            $providerCapacities[$provider] = $this->getProviderCapacity($provider);
        }

        return view('admin.panels.index', compact('defaultProviderType', 'providerTypes', 'providerCapacities'));
    }    
    
    public function getPanelsData(Request $request)
    {
        try {
            $query = Panel::with(['order_panels.order', 'order_panels.orderPanelSplits'])
                ->withCount('order_panels as total_orders');

            // Apply is_active filter (default to active panels)
            if ($request->filled('is_active')) {
                $query->where('is_active', $request->is_active);
            }

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

            // Apply provider_type filter
            if ($request->filled('provider_type') && $request->provider_type !== 'all') {
                $query->where('provider_type', $request->provider_type);
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
                return [
                    'id' => $panel->id,
                    'auto_generated_id' => $panel->auto_generated_id,
                    'title' => $panel->title,
                    'description' => $panel->description,
                    'provider_type' => $panel->provider_type,
                    'limit' => $panel->limit,
                    'used' => $used,
                    'panel_sr_no' => $panel->panel_sr_no,
                    'remaining_limit' => $panel->remaining_limit,
                    'is_active' => $panel->is_active,
                    'created_by' => $panel->created_by,
                    'created_at' => $panel->created_at,
                    'total_orders' => $panel->total_orders,
                    'can_edit' => $used === 0, // Can edit only if no space is used
                    'can_delete' => $used === 0, // Can delete only if no space is used
                    'show_edit_delete_buttons' => $used === 0, // Show buttons only when no space is used
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
                            'order_panel_data'=> $remainingPanel,
                            'order_panel_id' => $remainingPanel->id,
                            'panel_id' => $remainingPanel->panel_id,
                            'panel_title' => $remainingPanel->panel->title ?? 'N/A',
                            'space_assigned' => $remainingPanel->space_assigned,
                            'inboxes_per_domain' => $remainingPanel->inboxes_per_domain,
                            'status' => $remainingPanel->status,
                            'contractor_id' => $remainingAssignment ? $remainingAssignment->contractor_id : null,
                            'is_assigned' => $remainingAssignment ? true : false,
                            'customized_note' => $remainingPanel->customized_note,
                            'email_count' => OrderEmail::whereIn('order_split_id', [$remainingPanel->id])->count(),
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
                    'order_panel_data' => $orderPanel,
                    'order_panel_id' => $orderPanel->id,
                    'panel_id' => $orderPanel->panel_id,
                    'panel_title' => $orderPanel->panel->title ?? 'N/A',
                    'order_id' => $order->id ?? 'N/A',
                    'provider_type'=>$order->provider_type ??'N/A',
                    'timer_order'=>$order,
                    'customer_name' => $order->user->name ?? 'N/A',
                    'space_assigned' => $orderPanel->space_assigned,
                    'inboxes_per_domain' => $orderPanel->inboxes_per_domain,
                    'status' => $orderPanel->status,
                    'assignment_status' => $assignmentStatus,
                    'is_assigned_to_current_user' => $isAssignedToCurrentUser,
                    'customized_note' => $orderPanel->customized_note,
                    'email_count' => OrderEmail::whereIn('order_split_id', [$orderPanel->id])->count(),
                    'domains_count' => $splits->sum(function ($split) {
                        return is_array($split->domains) ? count($split->domains) : 0;
                    }),
                    'created_at' => $orderPanel->created_at->format('Y-m-d H:i:s'),
                    'accepted_at' => $orderPanel->accepted_at,
                    'released_at' => $orderPanel->released_at,
                    'order_status'=>$order->status_manage_by_admin ?? 'N/A',
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
                        'master_inbox_email' => $reorderInfo->master_inbox_email,
                        'backup_codes' => $reorderInfo->backup_codes,
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


    public function getOrdersSplits(Request $request, $orderId)
    {
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
            $providerTypes = Configuration::getProviderTypes();
            if (empty($providerTypes)) {
                $providerTypes = ['Google', 'Microsoft 365', 'Private SMTP'];
            }

            $data = $request->validate([
                'panel_title' => [
                    'required',
                    'string',
                    'max:255',
                    'unique:panels,title'
                ],
                'panel_description' => 'nullable|string',
                'panel_status' => 'in:0,1',
                'panel_limit' => 'required|integer|min:1',
                // Accept provider type from the form; this will drive panel_sr_no via the Observer
                'provider_type' => [
                    'required',
                    'string',
                    Rule::in($providerTypes),
                ],
            ]);

            $panel = Panel::create([
                'title' => $data['panel_title'],
                'description' => $data['panel_description'],
                'is_active' => $data['panel_status'] ?? 1,
                'limit' => $data['panel_limit'],
                'remaining_limit' => $data['panel_limit'], // Use the actual panel limit instead of env
                'created_by' => auth()->user()->name,
                // These are persisted; panel_sr_no gets assigned in PanelObserver::creating
                'provider_type' => $data['provider_type'],
            ]);

            // Calculate needed panels after creation
            $panelCapacityData = $this->calculatePanelCapacityNeeds();
            // runPanelCapacityCheck
            $this->runPanelCapacityCheck($request);
            return response()->json([
                'success' => true,
                'message' => 'Panel created successfully', 
                'panel' => $panel,
                'capacity_data' => $panelCapacityData
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to create panel: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create panel: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getProviderCapacity(?string $providerType): int
    {
        $fallback = (int) env('PANEL_CAPACITY', 1790);

        $key = match ($providerType) {
            'Microsoft 365' => 'MICROSOFT_365_CAPACITY',
            default => 'GOOGLE_PANEL_CAPACITY',
        };

        return (int) Configuration::get($key, $fallback);
    }

    private function getProviderMaxSplitCapacity(?string $providerType): int
    {
        $fallback = (int) env('MAX_SPLIT_CAPACITY', 358);

        $key = match (strtolower((string) $providerType)) {
            'microsoft 365' => 'MICROSOFT_365_MAX_SPLIT_CAPACITY',
            default => 'GOOGLE_MAX_SPLIT_CAPACITY',
        };

        return (int) Configuration::get($key, $fallback);
    }

    /**
     * Calculate panel capacity needs - extracted from the blade template logic
     */
    private function calculatePanelCapacityNeeds()
    {
        try {
            // Get pending orders that require panel capacity
            $providerType = Configuration::get('PROVIDER_TYPE', env('PROVIDER_TYPE', 'Google'));
            $panelCapacity = $this->getProviderCapacity($providerType);
            $maxSplitCapacity = $this->getProviderMaxSplitCapacity($providerType);

            $pendingOrders = OrderTracking::where('status', 'pending')
                ->whereNotNull('total_inboxes')
                ->where('total_inboxes', '>', 0)
                ->get();
            
            $insufficientSpaceOrders = [];
            $totalPanelsNeeded = 0;
            
            foreach ($pendingOrders as $order) {
                // Get inboxes per domain from order details or use default
                $inboxesPerDomain = $order->inboxes_per_domain ?? 1;
                
                // Calculate available space for this order based on logic
                $availableSpace = $this->getAvailablePanelSpaceForOrder(
                    $order->total_inboxes,
                    $inboxesPerDomain,
                    $panelCapacity,
                    $maxSplitCapacity,
                    $providerType
                );
                
                if ($order->total_inboxes > $availableSpace) {
                    // Calculate panels needed for this order (same logic as Console Command)
                    $panelsNeeded = ceil($order->total_inboxes / $maxSplitCapacity);
                    $insufficientSpaceOrders[] = $order;
                    $totalPanelsNeeded += $panelsNeeded;
                }
            }
            
            // Adjust total panels needed based on available panels (same logic as Console Command)
            $availablePanelCount = Panel::where('is_active', true)
                ->where('limit', $panelCapacity)
                ->where('provider_type', $providerType)
                ->where('remaining_limit', '>=', $maxSplitCapacity)
                ->count();
            
            // Panels required are based on total pending need; available panels are informational
            $adjustedPanelsNeeded = max(0, $totalPanelsNeeded);

            return [
                'total_panels_needed' => $totalPanelsNeeded,
                'available_panel_count' => $availablePanelCount,
                'adjusted_panels_needed' => $adjustedPanelsNeeded,
                'insufficient_orders_count' => count($insufficientSpaceOrders),
                'panel_capacity' => $panelCapacity,
                'max_split_capacity' => $maxSplitCapacity,
                'show_alert' => $adjustedPanelsNeeded > 0
            ];

        } catch (\Exception $e) {
            Log::error('Error calculating panel capacity needs: ' . $e->getMessage());
            return [
                'error' => 'Failed to calculate panel capacity needs',
                'show_alert' => false
            ];
        }
    }
    public function update(Request $request, $id)
    {
        try {
            Log::info('Panel update request received', [
                'panel_id' => $id,
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);

            $panel = Panel::findOrFail($id);
            
            // Check if panel can be edited (only if remaining_limit equals original limit - meaning no space is used)
            $usedSpace = $panel->limit - $panel->remaining_limit;
            if ($usedSpace > 0) {
                Log::warning('Panel edit blocked - has used space', [
                    'panel_id' => $id,
                    'used_space' => $usedSpace,
                    'limit' => $panel->limit,
                    'remaining_limit' => $panel->remaining_limit
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit panel. Panel has used space and is assigned to orders.'
                ], 422);
            }

            $data = $request->validate([
                'panel_title' => [
                    'required',
                    'string',
                    'max:255',
                    \Illuminate\Validation\Rule::unique('panels', 'title')->ignore($id)
                ],
                'panel_description' => 'nullable|string',
                'panel_limit' => 'nullable|integer|min:1',
                'panel_status' => 'nullable|in:0,1',
            ]);

            // Map frontend field names to database field names
            $updateData = [
                'title' => $data['panel_title'],
                'description' => $data['panel_description'] ?? null,
                'is_active' => isset($data['panel_status']) ? (int)$data['panel_status'] : 1,
            ];

            // For panel updates, we generally don't allow changing the limit
            // Only update limit if it's different and no space has been used
            if (isset($data['panel_limit']) && $data['panel_limit'] != $panel->limit) {
                $updateData['limit'] = $data['panel_limit'];
                $updateData['remaining_limit'] = $data['panel_limit'];
                
                Log::info('Panel limit being updated', [
                    'panel_id' => $id,
                    'old_limit' => $panel->limit,
                    'new_limit' => $data['panel_limit']
                ]);
            }

            $panel->update($updateData);

            Log::info('Panel updated successfully', [
                'panel_id' => $id,
                'updated_data' => $updateData,
                'panel_after_update' => $panel->fresh()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Panel updated successfully', 
                'panel' => $panel->fresh(),
                'capacity_data' => $this->calculatePanelCapacityNeeds() // Recalculate capacity needs after update
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Panel not found'
            ], 404);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Failed to update panel: ' . $e->getMessage(), [
                'panel_id' => $id,
                'request_data' => $request->all(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update panel: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $panel = Panel::findOrFail($id);
            
            // Check if panel can be deleted (only if remaining_limit equals original limit - meaning no space is used)
            $usedSpace = $panel->limit - $panel->remaining_limit;
            if ($usedSpace > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete panel. Panel has used space and is assigned to orders.'
                ], 422);
            }

            // Check if panel has any order assignments
            $hasOrders = $panel->order_panels()->count() > 0;
            if ($hasOrders) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete panel. Panel is assigned to orders.'
                ], 422);
            }

            $panel->delete();

            return response()->json([
                'success' => true,
                'message' => 'Panel deleted successfully'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Panel not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to delete panel: ' . $e->getMessage(), [
                'panel_id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete panel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Archive or unarchive a panel
     * 
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function archive(Request $request, $id)
    {
        try {
            $panel = Panel::findOrFail($id);
            
            // Validate the request - accept both boolean and string values
            $validated = $request->validate([
                'is_active' => 'required|in:0,1,true,false'
            ]);
            
            // Convert to boolean
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            
            // If conversion failed, try to convert from string/int
            if ($isActive === null) {
                $isActive = in_array($request->input('is_active'), [1, '1', 'true', true], true);
            }
            
            $action = $isActive ? 'unarchived' : 'archived';
            
            // Update panel status
            $panel->is_active = $isActive ? 1 : 0;
            $panel->save();
            
            Log::info("Panel {$action} successfully", [
                'panel_id' => $id,
                'is_active' => $panel->is_active,
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => "Panel {$action} successfully",
                'panel' => $panel
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error: ' . collect($e->errors())->flatten()->implode(', ')
            ], 422);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Panel not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to archive/unarchive panel: ' . $e->getMessage(), [
                'panel_id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update panel: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $panel = Panel::with('users')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'panel' => $panel
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Panel not found'
            ], 404);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve panel: ' . $e->getMessage(), [
                'panel_id' => $id,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve panel: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getOrderTrackingData(Request $request)
    {
        try {
            // \Log::info('OrderTracking API called');
            
            $query = OrderTracking::with(['order.user', 'order.plan', 'order.reorderInfo'])
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc');

            $orderTrackingRecords = $query->get();
            \Log::info('Found ' . $orderTrackingRecords->count() . ' order tracking records');

            // Calculate counters
            $totalOrders = $orderTrackingRecords->count();
            $totalInboxes = $orderTrackingRecords->sum('total_inboxes');
            
            // Calculate panels needed using the same logic as the console command
            $providerType = Configuration::get('PROVIDER_TYPE', env('PROVIDER_TYPE', 'Google'));
            $panelCapacity = $this->getProviderCapacity($providerType);
            $maxSplitCapacity = $this->getProviderMaxSplitCapacity($providerType);
            $totalPanelsNeeded = 0;
            $totalSpaceAvailable = 0;
            
            foreach ($orderTrackingRecords as $tracking) {
                $orderInboxes = (int) ($tracking->total_inboxes ?? 0);
                if ($orderInboxes > 0) {
                    $totalPanelsNeeded += (int) ceil($orderInboxes / $maxSplitCapacity);
                }
            }
            
            // Available panels and usable space (provider-aware)
            $availablePanels = Panel::where('is_active', true)
                ->where('limit', $panelCapacity)
                ->where('provider_type', $providerType)
                ->where('remaining_limit', '>', 0)
                ->get();
            $availablePanelCount = $availablePanels->filter(function ($panel) use ($maxSplitCapacity) {
                return $panel->remaining_limit >= $maxSplitCapacity;
            })->count();
            foreach ($availablePanels as $panel) {
                $totalSpaceAvailable += min($panel->remaining_limit, $maxSplitCapacity);
            }
            
            // Panels required after applying available space
            $remainingAfterAvailable = max(0, $totalInboxes - $totalSpaceAvailable);
            $panelsRequired = (int) ceil($remainingAfterAvailable / $maxSplitCapacity);

            // Get paginated results
            $orderTrackingData = $orderTrackingRecords->map(function ($tracking) {
                $order = $tracking->order;
                $domainUrl = 'N/A';
                
                // First try to get domain from reorder_infos table
                if ($order && $order->reorderInfo && $order->reorderInfo->isNotEmpty()) {
                    $reorderInfo = $order->reorderInfo->first();
                    // If still no domain found, try forwarding_url
                    if ($reorderInfo->forwarding_url) {
                        $domainUrl = $reorderInfo->forwarding_url;
                    }
                }
                return [
                    'id' => $order->id ?? 'N/A',
                    'order_id' => $tracking->order_id,
                    'date' => $order && $order->created_at ? $order->created_at->format('Y-m-d') : 'N/A',
                    'plan' => $order && $order->plan ? $order->plan->name : 'N/A',
                    'domain_url' => $domainUrl,
                    'total' => $tracking->total_inboxes ?? 0,
                    'inboxes_per_domain' => $tracking->inboxes_per_domain ?? 0,
                    'status' => $tracking->status ?? 'pending',
                    'cron_run_time' => $tracking->cron_run_time ? $tracking->cron_run_time->format('Y-m-d H:i:s') : 'N/A',
                ];
            });

            \Log::info('Processed ' . $orderTrackingData->count() . ' records');

            return response()->json([
                'success' => true,
                'data' => $orderTrackingData,
                'counters' => [
                    'total_orders' => $totalOrders,
                    'total_inboxes' => $remainingAfterAvailable,
                    'raw_total_inboxes' => $totalInboxes,
                    'panels_required' => $panelsRequired,
                    'total_panels_needed_raw' => $totalPanelsNeeded,
                    'available_panel_count' => $availablePanelCount,
                    'max_split_capacity' => $maxSplitCapacity,
                    'panel_capacity' => $panelCapacity,
                    'last_updated' => now()->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getOrderTrackingData: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching order tracking data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Run panel capacity check after successful panel creation
     */
    public function runPanelCapacityCheck(Request $request)
    {
        try {
            if ($request->input('panel_id')) {
                $panelId = (int) str_replace('PNL-', '', $request->input('panel_id'));
                $request->merge(['panel_id' => $panelId]);
            }
            // Validate that the request has optional fields
            $request->validate([
                'panel_id' => 'nullable|integer',
                'admin_id' => 'nullable|integer'
            ]);
            // PNL-4 removed PNL- and set to integer
            
            
            Log::info('Panel capacity check requested via admin AJAX', [
                'panel_id' => $request->input('panel_id'),
                'admin_id' => $request->input('admin_id'),
                'authenticated_admin' => auth()->id(),
                'timestamp' => now()
            ]);
            
            // Run the artisan command
            Artisan::call('panels:check-capacity');
            
            // Get the command output
            $output = Artisan::output();
            
            Log::info('Panel capacity check completed successfully via admin request', [
                'output' => $output
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Panel capacity check completed successfully',
                'output' => $output
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to run panel capacity check from admin panel: ' . $e->getMessage(), [
                'panel_id' => $request->input('panel_id'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to run panel capacity check',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get panel capacity alert data for AJAX refresh
     * This method mirrors the logic from Console\Commands\CheckPanelCapacity.php
     */
    public function getCapacityAlert(Request $request)
    {
        try {
            $providerType = Configuration::get('PROVIDER_TYPE', env('PROVIDER_TYPE', 'Google'));
            $panelCapacity = $this->getProviderCapacity($providerType);
            $maxSplitCapacity = $this->getProviderMaxSplitCapacity($providerType);

            // Get pending orders that require panel capacity
            $pendingOrders = OrderTracking::where('status', 'pending')
                ->whereNotNull('total_inboxes')
                ->where('total_inboxes', '>', 0)
                ->get();
            
            $insufficientSpaceOrders = [];
            $totalPanelsNeeded = 0;
            
            Log::info("Panel capacity alert calculation started", [
                'pending_orders_count' => $pendingOrders->count(),
                'panel_capacity' => $panelCapacity,
                'max_split_capacity' => $maxSplitCapacity,
                'provider_type' => $providerType
            ]);
            
            foreach ($pendingOrders as $order) {
                // Get inboxes per domain from order details or use default
                $inboxesPerDomain = $order->inboxes_per_domain ?? 1;
                
                // Calculate available space for this order based on logic
                $availableSpace = $this->getAvailablePanelSpaceForOrder(
                    $order->total_inboxes,
                    $inboxesPerDomain,
                    $panelCapacity,
                    $maxSplitCapacity,
                    $providerType
                );
                
                if ($order->total_inboxes > $availableSpace) {
                    // Calculate panels needed for this order (same logic as Console Command)
                    $panelsNeeded = ceil($order->total_inboxes / $maxSplitCapacity);
                    
                    $insufficientSpaceOrders[] = [
                        'order_id' => $order->order_id,
                        'required_space' => $order->total_inboxes,
                        'available_space' => $availableSpace,
                        'panels_needed' => $panelsNeeded,
                        'status' => 'pending'
                    ];
                    
                    $totalPanelsNeeded += $panelsNeeded;
                    
                    Log::warning("Order requires additional panels", [
                        'order_id' => $order->order_id,
                        'total_inboxes' => $order->total_inboxes,
                        'available_space' => $availableSpace,
                        'space_deficit' => $order->total_inboxes - $availableSpace,
                        'panels_needed' => $panelsNeeded
                    ]);
                }
            }
            
            // Adjust total panels needed based on available panels (same logic as Console Command)
            $availablePanelCount = Panel::where('is_active', true)
                ->where('limit', $panelCapacity)
                ->where('provider_type', $providerType)
                ->where('remaining_limit', '>=', $maxSplitCapacity)
                ->count();
            
            $adjustedPanelsNeeded = max(0, $totalPanelsNeeded);
            
            Log::info("Panel capacity alert calculation completed", [
                'total_panels_needed_raw' => $totalPanelsNeeded,
                'available_panel_count' => $availablePanelCount,
                'adjusted_panels_needed' => $adjustedPanelsNeeded,
                'insufficient_orders_count' => count($insufficientSpaceOrders)
            ]);
            
            return response()->json([
                'success' => true,
                'show_alert' => $adjustedPanelsNeeded > 0,
                'total_panels_needed' => $adjustedPanelsNeeded,
                'total_panels_needed_raw' => $totalPanelsNeeded,
                'available_panel_count' => $availablePanelCount,
                'insufficient_orders_count' => count($insufficientSpaceOrders),
                'insufficient_orders' => $insufficientSpaceOrders,
                'panel_capacity' => $panelCapacity,
                'max_split_capacity' => $maxSplitCapacity,
                'provider_type' => $providerType,
                'last_updated' => now()->toDateTimeString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error getting panel capacity alert data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error getting capacity alert data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available panel space for specific order size
     * This method mirrors the logic from Console\Commands\CheckPanelCapacity.php
     */
    private function getAvailablePanelSpaceForOrder(int $orderSize, int $inboxesPerDomain, int $panelCapacity, int $maxSplitCapacity, ?string $providerType): int
    {
        if ($orderSize >= $panelCapacity) {
            // For large orders, prioritize full capacity panels
            $fullCapacityPanels = Panel::where('is_active', 1)
                                        ->where('limit', $panelCapacity)
                                        ->where('provider_type', $providerType)
                                        ->where('remaining_limit', '>=', $inboxesPerDomain)
                                        ->get();
            
            $fullCapacitySpace = 0;
            foreach ($fullCapacityPanels as $panel) {
                $fullCapacitySpace += min($panel->remaining_limit, $maxSplitCapacity);
            }

            Log::info("Available space calculation for large order", [
                'order_size' => $orderSize,
                'inboxes_per_domain' => $inboxesPerDomain,
                'full_capacity_panels_count' => $fullCapacityPanels->count(),
                'total_available_space' => $fullCapacitySpace
            ]);
            
            return $fullCapacitySpace;
            
        } else {
            // For smaller orders, use any panel with remaining space that can accommodate at least one domain
            $availablePanels = Panel::where('is_active', 1)
                                    ->where('limit', $panelCapacity)
                                    ->where('provider_type', $providerType)
                                    ->where('remaining_limit', '>=', $inboxesPerDomain)
                                    ->get();
            
            $totalSpace = 0;
            foreach ($availablePanels as $panel) {
                $totalSpace += min($panel->remaining_limit, $maxSplitCapacity);
            }
            
            Log::info("Available space calculation for small order", [
                'order_size' => $orderSize,
                'inboxes_per_domain' => $inboxesPerDomain,
                'available_panels_count' => $availablePanels->count(),
                'total_available_space' => $totalSpace
            ]);

            return $totalSpace;
        }
    }

    /**
     * Get counters data for AJAX calls
     */
    public function getCounters(Request $request)
    {
        try {
            $orderTrackingRecords = OrderTracking::where('status', 'pending')->get();
            
            $totalOrders = $orderTrackingRecords->count();
            $totalInboxes = $orderTrackingRecords->sum('total_inboxes');
            
            $providerType = Configuration::get('PROVIDER_TYPE', env('PROVIDER_TYPE', 'Google'));
            $panelCapacity = $this->getProviderCapacity($providerType);
            $maxSplitCapacity = $this->getProviderMaxSplitCapacity($providerType);
            $totalPanelsNeeded = 0;
            $totalSpaceAvailable = 0;
            
            foreach ($orderTrackingRecords as $tracking) {
                $orderInboxes = (int) ($tracking->total_inboxes ?? 0);
                if ($orderInboxes > 0) {
                    $totalPanelsNeeded += (int) ceil($orderInboxes / $maxSplitCapacity);
                }
            }
            
            // Available panels and usable space (provider-aware)
            $availablePanels = Panel::where('is_active', true)
                ->where('limit', $panelCapacity)
                ->where('provider_type', $providerType)
                ->where('remaining_limit', '>', 0)
                ->get();
            $availablePanelCount = $availablePanels->filter(function ($panel) use ($maxSplitCapacity) {
                return $panel->remaining_limit >= $maxSplitCapacity;
            })->count();
            foreach ($availablePanels as $panel) {
                $totalSpaceAvailable += min($panel->remaining_limit, $maxSplitCapacity);
            }
            
            $remainingAfterAvailable = max(0, $totalInboxes - $totalSpaceAvailable);
            $panelsRequired = (int) ceil($remainingAfterAvailable / $maxSplitCapacity);
            return response()->json([
                'success' => true,
                'counters' => [
                    'total_orders' => $totalOrders,
                    'total_inboxes' => $remainingAfterAvailable,
                    'raw_total_inboxes' => $totalInboxes,
                    'panels_required' => $panelsRequired,
                    'total_panels_needed_raw' => $totalPanelsNeeded,
                    'available_panel_count' => $availablePanelCount,
                    'last_updated' => now()->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getCounters: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching counters: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get panel statistics for dashboard counters
     */
    public function getStatistics(Request $request)
    {
        try {
            // Get all panel statistics
            $panelCapacity = env('PANEL_CAPACITY', 1790);
            
            // Build base query
            $baseQuery = Panel::query();
            
            // Apply provider_type filter if provided
            if ($request->filled('provider_type') && $request->provider_type !== 'all') {
                $baseQuery->where('provider_type', $request->provider_type);
            }
            
            // Total panels count
            $totalPanels = (clone $baseQuery)->count();
            
            // Available capacity (sum of all remaining limits)
            $availableCapacity = (clone $baseQuery)->where('is_active', true)->sum('remaining_limit');
            
            // Used capacity (sum of all used space)
            $usedCapacity = (clone $baseQuery)->where('is_active', true)
                ->selectRaw('SUM(CASE WHEN `remaining_limit` <= `limit` THEN `limit` - `remaining_limit` ELSE 0 END) as used')
                ->value('used') ?? 0;
            
            // Archived panels count (inactive panels)
            $archivedPanels = (clone $baseQuery)->where('is_active', false)->count();
            
            // Additional statistics
            $activePanels = (clone $baseQuery)->where('is_active', true)->count();
            $totalCapacity = (clone $baseQuery)->where('is_active', true)->sum('limit');
            
            return response()->json([
                'success' => true,
                'statistics' => [
                    'total_panels' => $totalPanels,
                    'available_capacity' => $availableCapacity,
                    'used_capacity' => $usedCapacity,
                    'archived_panels' => $archivedPanels,
                    'closed_panels' => $archivedPanels,
                    'active_panels' => $activePanels,
                    'total_capacity' => $totalCapacity,
                    'utilization_percentage' => $totalCapacity > 0 ? round(($usedCapacity / $totalCapacity) * 100, 2) : 0,
                    'last_updated' => now()->toDateTimeString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getStatistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching panel statistics: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available panel count for AJAX calls
     */
    public function getAvailablePanelCount(Request $request)
    {
        try {
            $providerType = Configuration::get('PROVIDER_TYPE', env('PROVIDER_TYPE', 'Google'));
            $panelCapacity = $this->getProviderCapacity($providerType);
            $maxSplitCapacity = $this->getProviderMaxSplitCapacity($providerType);
            
            $availablePanelCount = Panel::where('is_active', true)
                ->where('limit', $panelCapacity)
                ->where('provider_type', $providerType)
                ->where('remaining_limit', '>=', $maxSplitCapacity)
                ->count();
            
            return response()->json([
                'success' => true,
                'available_panels' => $availablePanelCount,
                'panel_capacity' => $panelCapacity,
                'max_split_capacity' => $maxSplitCapacity,
                'last_updated' => now()->toDateTimeString()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getAvailablePanelCount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching available panel count: ' . $e->getMessage()
            ], 500);
        }
    }
}
