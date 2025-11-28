<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\OrderEmail;
use Illuminate\Http\Request;
use App\Models\DomainRemovalTask;
use App\Http\Controllers\Controller;
use App\Models\PanelReassignmentHistory;
use App\Models\PoolPanelReassignmentHistory;
use App\Services\PoolPanelReassignmentService;

class MyTaskController extends Controller
{
    public function index()
    {
        return view('admin.myTask.index');
    }
        /**
     * Get shifted tasks (in-progress and completed) from panel reassignment history
     */
    public function getShiftedTasks(Request $request)
    {
        try {
            $query = PanelReassignmentHistory::with([
                'order.user',
                'orderPanel.orderPanelSplits',
                'fromPanel',
                'toPanel',
                'reassignedBy',
                'assignedTo'
            ])->whereIn('status', ['in-progress', 'completed']) // Only in-progress and completed tasks
            ->where('assigned_to', auth()->id()); // Only tasks assigned to the current contractor
            
            // Apply additional filters if provided
            if ($request->filled('user_id')) {
                $query->whereHas('order', function($q) use ($request) {
                    $q->where('user_id', $request->user_id);
                });
            }

            if ($request->filled('date_from')) {
                $query->whereDate('reassignment_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('reassignment_date', '<=', $request->date_to);
            }

            // Apply pagination parameters
            $perPage = $request->get('per_page', 12);
            $page = $request->get('page', 1);
            
            // Get paginated results
            $paginatedTasks = $query->orderBy('reassignment_date', 'desc')->paginate($perPage, ['*'], 'page', $page);

            // Format tasks data for the frontend
            $tasksData = $paginatedTasks->getCollection()->map(function ($task) {
                $order = $task->order;
                
                return [
                    'id' => $task->id,
                    'task_id' => $task->id,
                    'type' => 'panel_reassignment',
                    'customer_name' => $order && $order->user ? $order->user->name : 'N/A',
                    'customer_image' => $order && $order->user && $order->user->profile_image 
                        ? asset('storage/profile_images/' . $order->user->profile_image) 
                        : null,
                    'order_id' => $order ? $order->id : null,
                    'order_panel_id' => $task->order_panel_id,
                    'from_panel' => $task->fromPanel ? [
                        'id' => $task->fromPanel->id,
                        'title' => $task->fromPanel->title
                    ] : null,
                    'to_panel' => $task->toPanel ? [
                        'id' => $task->toPanel->id,
                        'title' => $task->toPanel->title
                    ] : null,
                    'action_type' => $task->action_type,
                    'space_transferred' => $task->space_transferred,
                    'splits_count' => $task->splits_count,
                    'reason' => $task->reason ?? 'Panel reassignment task',
                    'status' => $task->status,
                    'reassignment_date' => $task->reassignment_date,
                    'task_started_at' => $task->task_started_at,
                    'task_completed_at' => $task->task_completed_at,
                    'completion_notes' => $task->completion_notes,
                    'created_at' => $task->created_at,
                    'assigned_to' => $task->assigned_to,
                    'assigned_to_name' => $task->assignedTo ? $task->assignedTo->name : null,
                    'reassigned_by_name' => $task->reassignedBy ? $task->reassignedBy->name : null,
                    'notes' => $task->notes,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $tasksData,
                'pagination' => [
                    'current_page' => $paginatedTasks->currentPage(),
                    'last_page' => $paginatedTasks->lastPage(),
                    'per_page' => $paginatedTasks->perPage(),
                    'total' => $paginatedTasks->total(),
                    'has_more_pages' => $paginatedTasks->hasMorePages(),
                    'from' => $paginatedTasks->firstItem(),
                    'to' => $paginatedTasks->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching shifted tasks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shifted tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMyTasksData(Request $request)
    {
        try {
            $type = $request->get('type', 'my-tasks'); // 'my-tasks' or 'all-tasks'
            if ($type === 'shifted-tasks') {
                return $this->getShiftedTasks($request);
            }
            $query = DomainRemovalTask::with(['user', 'order.reorderInfo', 'order.orderPanels.orderPanelSplits', 'assignedTo']);
            $query->whereDate('started_queue_date', '<=', now());
            if ($type === 'my-tasks') {
                // Show tasks assigned to current admin
                $query->where('assigned_to', auth()->id())
                      ->whereIn('status', ['in-progress', 'completed']);
            } else {
                // Show all tasks
                $query->orderBy('created_at', 'desc');
            }

            // Apply filters if provided
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('started_queue_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('started_queue_date', '<=', $request->date_to);
            }

            // Apply ordering
            $order = $request->get('order', 'desc');
            $query->orderBy('started_queue_date', $order);

            // Pagination parameters
            $perPage = 100; // Default to 100 tasks per page
            $page = $request->get('page', 1);
            
            // Get paginated results
            $paginatedTasks = $query->paginate($perPage, ['*'], 'page', $page);

            // Format tasks data for the frontend
            $tasksData = $paginatedTasks->getCollection()->map(function ($task) {
                $order = $task->order;
                $reorderInfo = $order ? $order->reorderInfo->first() : null;
                
                // Calculate total domains and inboxes from order
                $totalInboxes = 0;
                $totalDomains = 0;
                $inboxesPerDomain = 1;
                
                if ($reorderInfo) {
                    $totalInboxes = $reorderInfo->total_inboxes ?? 0;
                    $inboxesPerDomain = $reorderInfo->inboxes_per_domain ?? 1;
                    $totalDomains = $inboxesPerDomain > 0 ? intval($totalInboxes / $inboxesPerDomain) : 0;
                }

                // Calculate splits count from order panels
                $splitsCount = 0;
                if ($order && $order->orderPanels) {
                    $splitsCount = $order->orderPanels->sum(function($panel) {
                        return $panel->orderPanelSplits->count();
                    });
                }

                return [
                    'id' => $task->id,
                    'task_id' => $task->id,
                    'customer_name' => $task->user->name ?? 'N/A',
                    'customer_image' => $task->user->profile_image ? asset('storage/profile_images/' . $task->user->profile_image) : null,
                    'order_id' => $order ? $order->id : null,
                    'total_inboxes' => $totalInboxes,
                    'inboxes_per_domain' => $inboxesPerDomain,
                    'total_domains' => $totalDomains,
                    'splits_count' => $splitsCount,
                    'status' => $task->status,
                    'reason' => $task->reason ?? 'N/A',
                    'chargebee_subscription_id' => $task->chargebee_subscription_id,
                    'started_queue_date' => $task->started_queue_date,
                    'created_at' => $task->created_at,
                    'assigned_to' => $task->assigned_to,
                    'assigned_to_name' => $task->assignedTo ? $task->assignedTo->name : null,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $tasksData,
                'pagination' => [
                    'current_page' => $paginatedTasks->currentPage(),
                    'last_page' => $paginatedTasks->lastPage(),
                    'per_page' => $paginatedTasks->perPage(),
                    'total' => $paginatedTasks->total(),
                    'has_more_pages' => $paginatedTasks->hasMorePages(),
                    'from' => $paginatedTasks->firstItem(),
                    'to' => $paginatedTasks->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching my tasks data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tasks data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task details for canvas display (similar to contractor order splits)
     */
    public function getTaskDetails($taskId)
    {
        try {
            // First find the DomainRemovalTask by ID
            $task = DomainRemovalTask::with(['order', 'user'])->findOrFail($taskId);
            
            // Then get the associated order with all relationships
            $order = \App\Models\Order::with([
                'user',
                'reorderInfo',
                'orderPanels.orderPanelSplits',
                'orderPanels.panel'
            ])->findOrFail($task->order_id);

            // Get order information
            $orderInfo = [
                'id' => $order->id,
                'customer_name' => $order->user->name ?? 'N/A',
                'customer_image' => $order->user->profile_image ? asset('storage/profile_images/' . $order->user->profile_image) : null,
                'status' => $order->status_manage_by_admin ?? 'pending',
                'created_at' => $order->created_at,
                'completed_at' => $order->completed_at,
                'status_manage_by_admin' => $this->getOrderStatusBadge($order->status_manage_by_admin)
            ];

            // Get reorder info
            $reorderInfo = $order->reorderInfo->first();
            $reorderData = null;
            if ($reorderInfo) {
                $reorderData = [
                    'hosting_platform' => $reorderInfo->hosting_platform,
                    'platform_login' => $reorderInfo->platform_login,
                    'platform_password' => $reorderInfo->platform_password,
                    'forwarding_url' => $reorderInfo->forwarding_url,
                    'sending_platform' => $reorderInfo->sending_platform,
                    'sequencer_login' => $reorderInfo->sequencer_login,
                    'sequencer_password' => $reorderInfo->sequencer_password,
                    'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
                    'prefix_variants' => $reorderInfo->prefix_variants,
                    'prefix_variant_1' => $reorderInfo->prefix_variant_1,
                    'prefix_variant_2' => $reorderInfo->prefix_variant_2,
                    'data_obj' => $reorderInfo->data_obj ? json_decode($reorderInfo->data_obj, true) : null,
                    'master_inbox_email' => $reorderInfo->master_inbox_email,
                    'master_inbox_confirmation' => $reorderInfo->master_inbox_confirmation,
                    'additional_info' => $reorderInfo->additional_info,
                    'backup_codes' => $reorderInfo->backup_codes
                ];
            }

            // Get splits information
            $splits = [];
            foreach ($order->orderPanels as $panel) {
                foreach ($panel->orderPanelSplits as $split) {
                    // Get domains from the domains array field (not a relationship)
                    $domains = $split->domains ? (is_array($split->domains) ? $split->domains : json_decode($split->domains, true)) : [];

                    $splits[] = [
                        'id' => $split->id,
                        'panel_id' => $panel->panel_id,
                        'panel_title' => $panel->panel->title ?? 'N/A',
                        'panel_sr_no' => optional($panel->panel)->panel_sr_no ?? $panel->panel_id ?? null,
                        'order_panel_id' => $panel->id,
                        'status' => $split->status ?? 'unallocated',
                        'inboxes_per_domain' => $reorderInfo ? $reorderInfo->inboxes_per_domain : 1,
                        'domains_count' => count($domains),
                        'total_inboxes' => count($domains) * ($reorderInfo ? $reorderInfo->inboxes_per_domain : 1),
                        'domains' => $domains,
                        'order_panel' => [
                            'timer_started_at' => $panel->timer_started_at,
                            'completed_at' => $panel->completed_at,
                            'status' => $panel->status ?? 'pending'
                        ],
                        'customized_note' => $panel->customized_note,
                        'email_count' => OrderEmail::whereIn('order_split_id', [$panel->id])->count(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'reason' => $task->reason,
                    'started_queue_date' => $task->started_queue_date,
                    'assigned_to' => $task->assigned_to,
                    'chargebee_subscription_id' => $task->chargebee_subscription_id
                ],
                'order' => $orderInfo,
                'reorder_info' => $reorderData,
                'splits' => $splits
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching task details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Task not found or error fetching details'
            ], 404);
        }
    }

    /**
     * Get status badge HTML for order
     */
    private function getOrderStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
            'in-progress' => '<span class="badge bg-info text-dark">In Progress</span>',
            'completed' => '<span class="badge bg-success">Completed</span>',
            'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
            'rejected' => '<span class="badge bg-danger">Rejected</span>'
        ];

        return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }

    /**
     * Get task completion summary for confirmation
     */
    public function getTaskCompletionSummary($taskId)
    {
        try {
            $panelService = new \App\Services\PanelReleasedSpacedService();
            $result = $panelService->getTaskCompletionSummary($taskId);
            
            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Error getting task completion summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting task completion summary'
            ], 500);
        }
    }

    /**
     * Complete task and release assigned spaces
     */
    public function completeTask($taskId)
    {
        try {
            $panelService = new \App\Services\PanelReleasedSpacedService();
            $result = $panelService->completeTaskAndReleaseSpaces($taskId);
            
            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Error completing task: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error completing task'
            ], 500);
        }
    }

    public function completePoolPanelReassignmentTask($taskId)
    {
        try {
            $poolPanelService = new PoolPanelReassignmentService();
            $result = $poolPanelService->completeReassignmentTask($taskId);
            
            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('Error completing task: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error completing task'
            ], 500);
        }
    }

    /**
     * Get pool migration tasks assigned to the current admin
     */
    public function getMyPoolMigrationTasks(Request $request)
    {
        $service = new \App\Services\PoolMigrationTaskService();
        
        $filters = [
            'status' => $request->filled('status') ? $request->status : null,
            'task_type' => $request->filled('task_type') ? $request->task_type : null,
            'date_from' => $request->filled('date_from') ? $request->date_from : null,
            'date_to' => $request->filled('date_to') ? $request->date_to : null,
        ];
        
        $perPage = $request->get('per_page', 12);
        $page = $request->get('page', 1);
        
        $result = $service->getMyPoolMigrationTasks(auth()->id(), $filters, $perPage, $page);
        
        $statusCode = $result['statusCode'] ?? 200;
        unset($result['statusCode']);
        
        return response()->json($result, $statusCode);
    }

    /**
     * Get pool panel reassignment tasks (pool_panels / pool_panel_splits).
     */
    public function getMyPoolPanelReassignmentTasks(Request $request)
    {
        try {
            $query = PoolPanelReassignmentHistory::with([
                'pool',
                'poolPanel',
                'fromPoolPanel',
                'toPoolPanel',
                'reassignedBy',
                'assignedTo',
            ]);

            // --------------------------------------------------
            // 1. SHOW ONLY TASKS ASSIGNED TO CURRENT USER
            // --------------------------------------------------
            $query->where('assigned_to', auth()->id());

            // --------------------------------------------------
            // 2. STATUS FILTER (Default: in-progress + completed)
            // --------------------------------------------------
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            } else {
                // Show in-progress and completed, exclude pending
                $query->whereIn('status', ['in-progress', 'completed']);
            }

            // --------------------------------------------------
            // 3. OTHER FILTERS
            // --------------------------------------------------
            if ($request->filled('pool_id')) {
                $query->where('pool_id', $request->pool_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('reassignment_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('reassignment_date', '<=', $request->date_to);
            }

            // --------------------------------------------------
            // 4. ORDER
            // --------------------------------------------------
            $query->orderBy('reassignment_date', 'desc');

            // --------------------------------------------------
            // 5. PAGINATION
            // --------------------------------------------------
            $perPage = (int) $request->get('per_page', 12);
            $page = (int) $request->get('page', 1);

            $paginatedTasks = $query->paginate($perPage, ['*'], 'page', $page);

            // --------------------------------------------------
            // 6. DIRECT TRANSFORM (NO action_type filtering)
            // --------------------------------------------------
            $tasksData = $paginatedTasks->getCollection()->map(function (PoolPanelReassignmentHistory $task) {

                return [
                    'id' => $task->id,
                    'task_id' => $task->id,
                    'type' => 'pool_panel_reassignment',
                    'pool_id' => $task->pool_id,
                    'pool_name' => $task->pool->plan->name ?? null,
                    'domain_url' => $task->pool->domain_url ?? null,

                    'from_panel' => $task->fromPoolPanel ? [
                        'id' => $task->fromPoolPanel->id,
                        'title' => $task->fromPoolPanel->title, // Fixed: fromPanel to fromPoolPanel
                    ] : null,

                    'to_panel' => $task->toPoolPanel ? [
                        'id' => $task->toPoolPanel->id,
                        'title' => $task->toPoolPanel->title, // Fixed: toPanel to toPoolPanel
                    ] : null,

                    'action_type' => $task->action_type,
                    'space_transferred' => $task->space_transferred,
                    'splits_count' => $task->splits_count,
                    'status' => $task->status,
                    'reason' => $task->reason ?? 'Pool panel reassignment task',
                    'reassignment_date' => $task->reassignment_date,
                    'created_at' => $task->created_at,

                    'assigned_to' => $task->assigned_to,
                    'assigned_to_name' => $task->assignedTo->name ?? null,
                    'reassigned_by_name' => $task->reassignedBy->name ?? null,

                    'notes' => $task->notes,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $tasksData->values()->toArray(),
                'pagination' => [
                    'current_page' => $paginatedTasks->currentPage(),
                    'last_page' => $paginatedTasks->lastPage(),
                    'per_page' => $paginatedTasks->perPage(),
                    'total' => $paginatedTasks->total(),
                    'has_more_pages' => $paginatedTasks->hasMorePages(),
                    'from' => $paginatedTasks->firstItem(),
                    'to' => $paginatedTasks->lastItem(),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching pool panel reassignment tasks: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error fetching pool panel reassignment tasks: ' . $e->getMessage(),
            ], 500);
        }
    }

}
