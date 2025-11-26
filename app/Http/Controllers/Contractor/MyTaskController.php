<?php

namespace App\Http\Controllers\Contractor;

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
        return view('contractor.myTask.index');
    }

    public function getMyTasksData(Request $request)
    {
        try {
            $type = $request->get('type', 'my-tasks'); // 'my-tasks', 'all-tasks', or 'shifted-tasks'
            
            // Handle shifted tasks separately
            if ($type === 'shifted-tasks') {
                return $this->getShiftedTasksData($request);
            }
            
            $query = DomainRemovalTask::with(['user', 'order.reorderInfo', 'order.orderPanels.orderPanelSplits', 'assignedTo']);
            $query->whereDate('started_queue_date', '<=', now());
            
            if ($type === 'my-tasks') {
                // Show tasks assigned to current contractor
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
            $perPage = 100;
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
     * Get shifted tasks data for contractors
     */
    private function getShiftedTasksData(Request $request)
    {
        try {
            // Get shifted tasks data from TaskQueue controller
            $taskQueueController = new \App\Http\Controllers\Contractor\TaskQueueController();
            $shiftedTasksResponse = $taskQueueController->getShiftedTasks($request);
            
            // Decode the JSON response
            $responseData = json_decode($shiftedTasksResponse->getContent(), true);
            
            return response()->json($responseData);
            
        } catch (\Exception $e) {
            \Log::error('Error fetching shifted tasks data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shifted tasks data: ' . $e->getMessage()
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
                    'additional_info' => $reorderInfo->additional_info,
                    'master_inbox_email' => $reorderInfo->master_inbox_email,
                    'master_inbox_confirmation' => $reorderInfo->master_inbox_confirmation,
                    'backup_codes' => $reorderInfo->backup_codes,
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
     * Get shifted task details for the offcanvas view
     */
    public function getShiftedTaskDetails($taskId)
    {
        try {
            // Find the panel reassignment task by ID
            $task = PanelReassignmentHistory::with([
                'order.user',
                'order.reorderInfo',
                'order.orderPanels.orderPanelSplits',
                'order.orderPanels.panel',
                'orderPanel.orderPanelSplits',
                'fromPanel',
                'toPanel',
                'reassignedBy',
                'assignedTo'
            ])->findOrFail($taskId);

            // Get the full order with all relationships for splits data
            $order = \App\Models\Order::with([
                'user',
                'reorderInfo',
                'orderPanels.orderPanelSplits',
                'orderPanels.panel'
            ])->findOrFail($task->order_id);

            // Get order information (similar to regular task details)
            $orderInfo = [
                'id' => $order->id,
                'customer_name' => $order->user->name ?? 'N/A',
                'customer_image' => $order->user->profile_image ? asset('storage/profile_images/' . $order->user->profile_image) : null,
                'status' => $order->status_manage_by_admin ?? 'pending',
                'created_at' => $order->created_at,
                'completed_at' => $order->completed_at,
                'status_manage_by_admin' => $this->getOrderStatusBadge($order->status_manage_by_admin)
            ];

            // Get reorder info (similar to regular task details)
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
                    'additional_info' => $reorderInfo->additional_info,
                    'master_inbox_email' => $reorderInfo->master_inbox_email,
                    'master_inbox_confirmation' => $reorderInfo->master_inbox_confirmation,
                    'backup_codes' => $reorderInfo->backup_codes,
                ];
            }

            // Get splits information - show all splits for migration tasks
            $splits = [];
            
            // First try to get splits from the specific order panel that was reassigned
            $targetOrderPanel = null;
            if ($task->order_panel_id) {
                $targetOrderPanel = $order->orderPanels->where('id', $task->order_panel_id)->first();
            }
            
            if ($targetOrderPanel && $targetOrderPanel->orderPanelSplits->count() > 0) {
                // Show splits from the specific order panel that was reassigned
                foreach ($targetOrderPanel->orderPanelSplits as $split) {
                    $domains = $split->domains ? (is_array($split->domains) ? $split->domains : json_decode($split->domains, true)) : [];
                    
                    $splits[] = [
                        'id' => $split->id,
                        'panel_id' => $targetOrderPanel->panel_id,
                        'panel_title' => $targetOrderPanel->panel->title ?? 'N/A',
                        'order_panel_id' => $targetOrderPanel->id,
                        'status' => $split->status ?? 'unallocated',
                        'inboxes_per_domain' => $reorderInfo ? $reorderInfo->inboxes_per_domain : 1,
                        'domains_count' => count($domains),
                        'total_inboxes' => count($domains) * ($reorderInfo ? $reorderInfo->inboxes_per_domain : 1),
                        'domains' => $domains,
                        'order_panel' => [
                            'timer_started_at' => $targetOrderPanel->timer_started_at,
                            'completed_at' => $targetOrderPanel->completed_at,
                            'status' => $targetOrderPanel->status ?? 'pending'
                        ],
                        'customized_note' => $targetOrderPanel->customized_note,
                        'email_count' => OrderEmail::whereIn('order_split_id', [$targetOrderPanel->id])->count(),
                    ];
                }
            } else {
                // Fallback: show all splits from the order
                foreach ($order->orderPanels as $panel) {
                    foreach ($panel->orderPanelSplits as $split) {
                        $domains = $split->domains ? (is_array($split->domains) ? $split->domains : json_decode($split->domains, true)) : [];
                        
                        $splits[] = [
                            'id' => $split->id,
                            'panel_id' => $panel->panel_id,
                            'panel_title' => $panel->panel->title ?? 'N/A',
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
            }

            // Task-specific information for panel reassignment
            $taskInfo = [
                'id' => $task->id,
                'task_id' => $task->id,
                'order_id' => $task->order_id,
                'order_panel_id' => $task->order_panel_id,
                'from_panel_id' => $task->from_panel_id,
                'to_panel_id' => $task->to_panel_id,
                'action_type' => $task->action_type,
                'space_transferred' => $task->space_transferred,
                'reason' => $task->reason,
                'status' => $task->status,
                'reassignment_date' => $task->reassignment_date,
                'task_started_at' => $task->task_started_at,
                'task_completed_at' => $task->task_completed_at,
                'completion_notes' => $task->completion_notes,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
                'assigned_to' => $task->assigned_to,
                'assigned_to_name' => $task->assignedTo ? $task->assignedTo->name : null,
                'reassigned_by_name' => $task->reassignedBy ? $task->reassignedBy->name : null,
                'notes' => $task->notes,
                'started_queue_date' => $task->reassignment_date, // Use reassignment_date as queue date
            ];

            // From panel information
            $fromPanel = null;
            if ($task->fromPanel) {
                $fromPanel = [
                    'id' => $task->fromPanel->id,
                    'title' => $task->fromPanel->title,
                    'location' => $task->fromPanel->location ?? 'N/A',
                    'capacity' => $task->fromPanel->capacity ?? 'N/A',
                ];
            }

            // To panel information
            $toPanel = null;
            if ($task->toPanel) {
                $toPanel = [
                    'id' => $task->toPanel->id,
                    'title' => $task->toPanel->title,
                    'location' => $task->toPanel->location ?? 'N/A',
                    'capacity' => $task->toPanel->capacity ?? 'N/A',
                ];
            }

            return response()->json([
                'success' => true,
                'task' => $taskInfo,
                'order' => $orderInfo,
                'reorder_info' => $reorderData,
                'splits' => $splits,
                'from_panel' => $fromPanel,
                'to_panel' => $toPanel,
                'message' => 'Panel reassignment task details with order splits retrieved successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching shifted task details (MyTask): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Panel reassignment task not found or error fetching details'
            ], 404);
        }
    }

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
        
        // For backward compatibility with contractor frontend that expects 'tasks' instead of 'data'
        if ($result['success'] && isset($result['data'])) {
            $result['tasks'] = $result['data'];
            unset($result['data']);
        }
        
        $statusCode = $result['statusCode'] ?? 200;
        unset($result['statusCode']);
        
        return response()->json($result, $statusCode);
    }


    public function getPoolMigrationTaskDetails($taskId)
    {
        $service = new \App\Services\PoolMigrationTaskService();
        $result = $service->getTaskDetails($taskId, auth()->id(), false);
        
        $statusCode = $result['statusCode'] ?? 200;
        unset($result['statusCode']);
        
        return response()->json($result, $statusCode);
    }

    public function updatePoolMigrationTaskStatus(Request $request, $taskId)
    {
        try {
            $task = \App\Models\PoolOrderMigrationTask::where('assigned_to', auth()->id())
                ->findOrFail($taskId);

            $status = $request->input('status');
            $notes = $request->input('notes');
            $force = $request->input('force', false);

            // Use the PoolMigrationTaskService
            $service = new \App\Services\PoolMigrationTaskService();
            $result = $service->updateTaskStatus($task, $status, $notes, $force, auth()->id());

            $statusCode = $result['statusCode'] ?? 200;
            unset($result['statusCode']);
            
            return response()->json($result, $statusCode);

        } catch (\Exception $e) {
            \Log::error('Error updating pool migration task status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status'
            ], 500);
        }
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