<?php

namespace App\Http\Controllers\Contractor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DomainRemovalTask;
use App\Models\Order;
use App\Models\PanelReassignmentHistory;
use App\Services\PanelReassignmentService;
use Carbon\Carbon;
use App\Models\OrderEmail;

class TaskQueueController extends Controller
{
    public function index()
    {
        return view('contractor.taskInQueue.index');
    }

    public function getTasksData(Request $request)
    {
        try {
            $type = $request->get('type', 'pending'); // 'pending', 'in-progress', 'completed'
            
            $query = DomainRemovalTask::with(['user', 'order.reorderInfo', 'order.orderPanels.orderPanelSplits', 'assignedTo']);

            // Filter based on requirements: assigned_to = null and status = pending and started_queue_date >= now()
            if ($type === 'pending') {
                $query->whereNull('assigned_to')
                      ->where('status', 'pending')
                      ->whereDate('started_queue_date', '<=', now());
            } elseif ($type === 'in-progress') {
                $query->where('status', 'in-progress')
                      ->whereNotNull('assigned_to');
            } elseif ($type === 'completed') {
                $query->where('status', 'completed');
            }

            // Apply additional filters if provided
            if ($request->filled('status') && $type === 'pending') {
                // For pending tab, we can only filter by pending status
                $query->where('status', 'pending');
            } elseif ($request->filled('status') && $type !== 'pending') {
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
            \Log::error('Error fetching task queue data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching tasks data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function assignTaskToMe(Request $request, $taskId)
    {
        try {
            $contractorId = auth()->id();
            
            // Find the task
            $task = DomainRemovalTask::findOrFail($taskId);
            
            // Check if task is already assigned
            if ($task->assigned_to) {
                return response()->json([
                    'success' => false,
                    'message' => 'This task is already assigned to another contractor.'
                ], 400);
            }
            
            // Assign the task to the current contractor
            $task->assigned_to = $contractorId;
            $task->status = 'in-progress';
            $task->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Task assigned successfully!',
                'task' => [
                    'id' => $task->id,
                    'assigned_to' => $contractorId,
                    'status' => $task->status
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error assigning task {$taskId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign task: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateTaskStatus(Request $request, $taskId)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,in-progress,completed,failed'
            ]);

            $task = DomainRemovalTask::findOrFail($taskId);
            
            // Only allow status updates for assigned tasks or by the assigned contractor
            if ($task->assigned_to && $task->assigned_to !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update tasks assigned to you.'
                ], 403);
            }
            
            $task->status = $request->status;
            $task->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully!',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error updating task status {$taskId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shifted tasks (in-progress and completed) for contractors
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

            // Apply filters if provided
            if ($request->filled('user_id')) {
                $query->whereHas('order', function($q) use ($request) {
                    $q->where('user_id', $request->user_id);
                });
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('assigned_to')) {
                $query->where('assigned_to', $request->assigned_to);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('reassignment_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('reassignment_date', '<=', $request->date_to);
            }

            // Apply pagination
            $perPage = $request->get('per_page', 12);
            $page = $request->get('page', 1);
            
            $paginatedTasks = $query->orderBy('reassignment_date', 'desc')
                                   ->paginate($perPage, ['*'], 'page', $page);

            // Transform the data to include customer information
            $transformedTasks = $paginatedTasks->getCollection()->map(function ($task) {
                $order = $task->order;
                $task->customer_name = $order && $order->user ? $order->user->name : 'N/A';
                $task->customer_image = $order && $order->user && $order->user->profile_image 
                    ? asset('storage/profile_images/' . $order->user->profile_image) 
                    : null;
                
                // Add task_id for consistency with frontend
                $task->task_id = $task->id;
                
                // Add assigned_to_name for display
                $task->assigned_to_name = $task->assignedTo ? $task->assignedTo->name : 'N/A';
                
                // Add type for frontend identification
                $task->type = 'panel_reassignment';
                
                return $task;
            });

            return response()->json([
                'success' => true,
                'data' => $transformedTasks->toArray(),
                'pagination' => [
                    'current_page' => $paginatedTasks->currentPage(),
                    'last_page' => $paginatedTasks->lastPage(),
                    'per_page' => $paginatedTasks->perPage(),
                    'total' => $paginatedTasks->total(),
                    'has_more_pages' => $paginatedTasks->hasMorePages()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error("Error loading shifted tasks: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load shifted tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shifted pending tasks for contractors
     */
    public function getShiftedPendingTasks(Request $request)
    {
        try {
            $query = PanelReassignmentHistory::with([
                'order.user',
                'orderPanel.orderPanelSplits',
                'fromPanel',
                'toPanel',
                'reassignedBy',
                'assignedTo'
            ])->where('status', 'pending'); // Only pending tasks

            // Apply filters if provided
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

            // Get all tasks first, then group and filter
            $allTasks = $query->orderBy('reassignment_date', 'desc')->get();

            // Group tasks by unique combination of order_id, order_panel_id, from_panel_id, to_panel_id
            $groupedTasks = $allTasks->groupBy(function ($task) {
                return $task->order_id . '_' . $task->order_panel_id . '_' . $task->from_panel_id . '_' . $task->to_panel_id;
            });

            // For each group, show 'removed' action first, then 'added' only if 'removed' is completed
            $filteredTasks = collect();
            $groupedTasks->each(function ($group) use (&$filteredTasks) {
                $removedTask = $group->where('action_type', 'removed')->first();
                $addedTask = $group->where('action_type', 'added')->first();

                // Always show 'removed' if exists
                if ($removedTask) {
                    $filteredTasks->push($removedTask);
                    // If 'removed' is completed and 'added' exists, show 'added' after
                    if ($removedTask->status === 'completed' && $addedTask) {
                        $filteredTasks->push($addedTask);
                    }
                } elseif ($addedTask) {
                    // If no 'removed', show 'added'
                    $filteredTasks->push($addedTask);
                }
            });
            $filteredTasks = $filteredTasks->sortByDesc('reassignment_date')->values();

            // Apply pagination manually
            $perPage = $request->get('per_page', 12);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            
            $paginatedTasks = $filteredTasks->slice($offset, $perPage);
            $total = $filteredTasks->count();
            $lastPage = ceil($total / $perPage);

            // Transform the data to include customer information
            $transformedTasks = $paginatedTasks->map(function ($task) {
                $order = $task->order;
                $task->customer_name = $order && $order->user ? $order->user->name : 'N/A';
                $task->customer_image = $order && $order->user && $order->user->profile_image 
                    ? asset('storage/profile_images/' . $order->user->profile_image) 
                    : null;
                
                // Add task_id for consistency with frontend
                $task->task_id = $task->id;
                
                return $task;
            });

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $transformedTasks->values()->toArray(),
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                    'has_more_pages' => $page < $lastPage
                ]);
            }

            return view('contractor.taskInQueue.shifted-pending', ['shiftedTasks' => $transformedTasks]);
        } catch (\Exception $e) {
            \Log::error("Error loading shifted pending tasks: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load shifted pending tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Assign shifted task to contractor
     */
    public function assignShiftedTaskToMe(Request $request, $id)
    {
        try {
            // Check if user is authenticated
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $task = PanelReassignmentHistory::find($id);
            
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }
            
            if ($task->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Task is not available for assignment'
                ], 400);
            }

            // Get the service
            $service = new \App\Services\PanelReassignmentService();
            
            // Start the task (both removal and addition records)
            $startResult = $service->startPanelReassignmentTask($task, auth()->user()->id);
            
            if (!$startResult) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to assign task'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Panel reassignment task assigned successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error assigning shifted task ID {$id}: " . $e->getMessage());
            \Log::error("Stack trace: " . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update shifted task status
     */
    public function updateShiftedTaskStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status' => 'required|in:completed,in-progress',
                'completion_date' => 'nullable|date',
                'completion_notes' => 'nullable|string|max:1000'
            ]);

            $task = PanelReassignmentHistory::find($id);
            
            if (!$task) {
                return response()->json([
                    'success' => false,
                    'message' => 'Task not found'
                ], 404);
            }
            
            // Check if contractor is assigned to this task
            if ($task->assigned_to !== auth()->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update tasks assigned to you.'
                ], 403);
            }

            if ($request->status === 'completed') {
                // Complete the task (both removal and addition records)
                $task->markAsCompleted($request->input('completion_notes'));
            } else {
                $task->status = $request->status;
                $task->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Task status updated successfully'
            ]);
        } catch (\Exception $e) {
            \Log::error("Error updating shifted task status: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get task details for canvas display
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
                    'master_inbox_email' => $reorderInfo->master_inbox_email,
                    'master_inbox_confirmation' => $reorderInfo->master_inbox_confirmation,
                    'additional_info' => $reorderInfo->additional_info,
                    'backup_codes' => $reorderInfo->backup_codes,
                ];
            }

            // Get splits information (similar to regular task details)
            $splits = [];
            foreach ($order->orderPanels as $panel) {
                foreach ($panel->orderPanelSplits as $split) {
                    // Get domains from the domains array field (not a relationship)
                    $domains = $split->domains ? (is_array($split->domains) ? $split->domains : json_decode($split->domains, true)) : [];
                    // if($task->from_panel_id && $task->from_panel_id != $panel->panel_id){
                    //     continue;
                    // }
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
            \Log::error('Error fetching shifted task details (Contractor): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Panel reassignment task not found or error fetching details'
            ], 404);
        }
    }
}
