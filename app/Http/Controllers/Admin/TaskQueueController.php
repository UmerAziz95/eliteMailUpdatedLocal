<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DomainRemovalTask;
use App\Models\Order;
use App\Models\PanelReassignmentHistory;
use App\Models\PoolPanelReassignmentHistory;
use App\Models\Pool;
use App\Models\PoolPanel;
use App\Services\PanelReassignmentService;
use App\Services\PoolMigrationTaskService;
use Carbon\Carbon;
use App\Models\OrderEmail;

class TaskQueueController extends Controller
{
    public function index()
    {
        return view('admin.taskInQueue.index');
    }

    public function getTasksData(Request $request)
    {
        try {
            $type = $request->get('type', 'pending'); // 'pending', 'in-progress', 'completed', 'shifted-pending', 'shifted-tasks'
            
            // Handle shifted pending tasks differently
            if ($type === 'shifted-pending') {
                return $this->getShiftedPendingTasks($request);
            }

            // Handle shifted tasks (in-progress and completed)
            if ($type === 'shifted-tasks') {
                return $this->getShiftedTasks($request);
            }
            $query = DomainRemovalTask::with(['user', 'order.reorderInfo', 'order.orderPanels.orderPanelSplits', 'assignedTo']);
            
            // Filter based on requirements: assigned_to = null and status = pending and started_queue_date >= now()
            if ($type === 'pending') {
                $query->whereNull('assigned_to')
                    ->where('status', 'pending')
                    ->where('started_queue_date', '<=', Carbon::now());
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

    public function assignTaskToMe(Request $request, $taskId)
    {
        try {
            $adminId = auth()->id();
            
            // Find the task
            $task = DomainRemovalTask::findOrFail($taskId);
            
            // Check if task is already assigned
            if ($task->assigned_to) {
                return response()->json([
                    'success' => false,
                    'message' => 'This task is already assigned to another admin.'
                ], 400);
            }
            
            // Assign the task to the current admin
            $task->assigned_to = $adminId;
            $task->status = 'in-progress';
            $task->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Task assigned successfully!',
                'task' => [
                    'id' => $task->id,
                    'assigned_to' => $adminId,
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
            
            // Only allow status updates for assigned tasks or by the assigned admin
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
     * Get shifted pending tasks from panel reassignment history
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
            // })->values(); // Reset array keys

            // Apply pagination manually
            $perPage = $request->get('per_page', 12);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            
            $paginatedTasks = $filteredTasks->slice($offset, $perPage);
            $total = $filteredTasks->count();
            $lastPage = ceil($total / $perPage);

            // Format tasks data for the frontend
            $tasksData = $paginatedTasks->map(function ($task) {
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
                        'title' => $task->fromPanel->title,
                        'panel_sr_no' => optional($task->fromPanel)->panel_sr_no ?? $task->from_panel_id ?? null
                    ] : null,
                    'to_panel' => $task->toPanel ? [
                        'id' => $task->toPanel->id,
                        'title' => $task->toPanel->title,
                        'panel_sr_no' => optional($task->toPanel)->panel_sr_no ?? $task->to_panel_id ?? null
                    ] : null,
                    'action_type' => $task->action_type,
                    'space_transferred' => $task->space_transferred,
                    'splits_count' => $task->splits_count,
                    'reason' => $task->reason ?? 'Panel reassignment required',
                    'status' => $task->status,
                    'reassignment_date' => $task->reassignment_date,
                    'created_at' => $task->created_at,
                    'assigned_to' => $task->assigned_to,
                    'assigned_to_name' => $task->assignedTo ? $task->assignedTo->name : null,
                    'reassigned_by_name' => $task->reassignedBy ? $task->reassignedBy->name : null,
                    'notes' => $task->notes,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $tasksData->values()->toArray(),
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                    'has_more_pages' => $page < $lastPage,
                    'from' => $offset + 1,
                    'to' => min($offset + $perPage, $total)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error fetching shifted pending tasks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shifted pending tasks: ' . $e->getMessage()
            ], 500);
        }
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

            // Apply ordering and pagination
            $query->orderBy('reassignment_date', 'desc');

            // Pagination parameters
            $perPage = $request->get('per_page', 12);
            $page = $request->get('page', 1);
            
            // Get paginated results
            $paginatedTasks = $query->paginate($perPage, ['*'], 'page', $page);

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
                        'title' => $task->fromPanel->title,
                        'panel_sr_no' => optional($task->fromPanel)->panel_sr_no ?? $task->from_panel_id ?? null
                    ] : null,
                    'to_panel' => $task->toPanel ? [
                        'id' => $task->toPanel->id,
                        'title' => $task->toPanel->title,
                        'panel_sr_no' => optional($task->toPanel)->panel_sr_no ?? $task->to_panel_id ?? null
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

    /**
     * Assign a panel reassignment task to current admin
     */
    public function assignShiftedTaskToMe(Request $request, $taskId)
    {
        try {
            $adminId = auth()->id();
            
            // Find the panel reassignment task
            $task = PanelReassignmentHistory::findOrFail($taskId);
            
            // Check if task is already assigned
            if ($task->assigned_to) {
                return response()->json([
                    'success' => false,
                    'message' => 'This panel reassignment task is already assigned to another admin.'
                ], 400);
            }
            
            // Assign the task to the current admin and mark as started
            $task->markAsStarted($adminId);
            
            return response()->json([
                'success' => true,
                'message' => 'Panel reassignment task assigned successfully!',
                'task' => [
                    'id' => $task->id,
                    'assigned_to' => $adminId,
                    'status' => $task->status
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error assigning shifted task {$taskId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign panel reassignment task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update panel reassignment task status
     */ 
    public function updateShiftedTaskStatus(Request $request, $taskId)
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,in-progress,completed',
                'completion_notes' => 'nullable|string|max:1000'
            ]);

            $task = PanelReassignmentHistory::findOrFail($taskId);
            
            // Only allow status updates for assigned tasks or by the assigned admin
            if ($task->assigned_to && $task->assigned_to !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update panel reassignment tasks assigned to you.'
                ], 403);
            }
            
            if ($request->status === 'completed') {
                $task->markAsCompleted($request->completion_notes);
            } else {
                $task->update(['status' => $request->status]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Panel reassignment task status updated successfully!',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error("Error updating shifted task status {$taskId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update panel reassignment task status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shifted task details for admin
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
                    'panel_sr_no' => optional($task->fromPanel)->panel_sr_no ?? $task->from_panel_id ?? null,
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
                    'panel_sr_no' => optional($task->toPanel)->panel_sr_no ?? $task->to_panel_id ?? null,
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
            \Log::error('Error fetching shifted task details: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Panel reassignment task not found or error fetching details'
            ], 404);
        }
    }

    /**
     * Get pool migration tasks
     */
    public function getPoolMigrationTasks(Request $request)
    {
        try {
            $query = \App\Models\PoolOrderMigrationTask::with([
                'poolOrder.poolPlan',
                'poolOrder.user',
                'user',
                'assignedTo'
            ]);
            
            // For Task Queue page: Show only unassigned tasks by default
            // This can be overridden by passing assigned_status parameter
            if (!$request->filled('assigned_status')) {
                $query->whereNull('assigned_to');
            } else {
                // Filter by assigned status if explicitly provided
                if ($request->assigned_status === 'unassigned') {
                    $query->whereNull('assigned_to');
                } elseif ($request->assigned_status === 'assigned') {
                    $query->whereNotNull('assigned_to');
                }
            }
            
            // Filter by status if provided (default: all statuses)
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }
            
            // Filter by task type if provided
            if ($request->filled('task_type')) {
                $query->where('task_type', $request->task_type);
            }

            // Filter by date range
            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Apply ordering
            $query->orderBy('created_at', 'desc');

            // Pagination parameters
            $perPage = $request->get('per_page', 12);
            $page = $request->get('page', 1);
            
            // Get paginated results
            $paginatedTasks = $query->paginate($perPage, ['*'], 'page', $page);

            // Format tasks data for the frontend
            $tasksData = $paginatedTasks->getCollection()->map(function ($task) {
                $poolOrder = $task->poolOrder;
                $user = $poolOrder ? $poolOrder->user : null;
                $metadata = $task->metadata ?? [];
                
                return [
                    'id' => $task->id,
                    'task_id' => $task->id,
                    'type' => 'pool_migration',
                    'pool_order_id' => $task->pool_order_id,
                    'task_type' => $task->task_type,
                    'status' => $task->status,
                    'customer_name' => $user ? $user->name : 'N/A',
                    'customer_email' => $user ? $user->email : 'N/A',
                    'customer_image' => $user && $user->profile_image 
                        ? asset('storage/profile_images/' . $user->profile_image) 
                        : null,
                    'plan_name' => $metadata['plan_name'] ?? 'N/A',
                    'amount' => $metadata['amount'] ?? 0,
                    'quantity' => $metadata['quantity'] ?? 0,
                    'domains_count' => $metadata['selected_domains_count'] ?? 0,
                    'total_inboxes' => $metadata['total_inboxes'] ?? 0,
                    'hosting_platform' => $metadata['hosting_platform'] ?? 'N/A',
                    'previous_status' => $task->previous_status,
                    'new_status' => $task->new_status,
                    'assigned_to' => $task->assigned_to,
                    'assigned_to_name' => $task->assignedTo ? $task->assignedTo->name : null,
                    'notes' => $task->notes,
                    'created_at' => $task->created_at,
                    'started_at' => $task->started_at,
                    'completed_at' => $task->completed_at,
                    'task_type_label' => $task->task_type === 'configuration' ? 'Configuration' : 'Cancellation',
                    'task_type_icon' => $task->task_type === 'configuration' ? '📋' : '🔧',
                    'status_badge' => $this->getStatusBadge($task->status),
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
            \Log::error('Error fetching pool migration tasks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching pool migration tasks: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pool migration task details
     */
    public function getPoolMigrationTaskDetails($taskId)
    {
        $service = new \App\Services\PoolMigrationTaskService();
        $result = $service->getTaskDetails($taskId, null, true);
        
        $statusCode = $result['statusCode'] ?? 200;
        unset($result['statusCode']);
        
        return response()->json($result, $statusCode);
    }

    /**
     * Assign a pool migration task to current admin
     */
    public function assignPoolMigrationTaskToMe(Request $request, $taskId)
    {
        try {
            $task = \App\Models\PoolOrderMigrationTask::findOrFail($taskId);
            $service = new PoolMigrationTaskService();
            
            $result = $service->assignTask($task, auth()->id());
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'task' => $result['task'] ?? null
            ], $result['statusCode']);
            
        } catch (\Exception $e) {
            \Log::error("Error assigning pool migration task {$taskId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign pool migration task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pool migration task status
     */
    public function updatePoolMigrationTaskStatus(Request $request, $taskId)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,in-progress,completed,failed,cancelled',
                'notes' => 'nullable|string|max:1000',
                'force' => 'nullable|boolean'
            ]);

            $task = \App\Models\PoolOrderMigrationTask::findOrFail($taskId);
            $service = new PoolMigrationTaskService();
            
            $result = $service->updateTaskStatus(
                $task,
                $validated['status'],
                $validated['notes'] ?? null,
                $request->boolean('force'),
                auth()->id()
            );
            
            $statusCode = $result['statusCode'] ?? 200;
            unset($result['statusCode']);
            
            return response()->json($result, $statusCode);
            
        } catch (\Exception $e) {
            \Log::error("Error updating pool migration task status for task {$taskId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update pool migration task status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper function to get status badge HTML
     */
    private function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
            'in-progress' => '<span class="badge bg-info">In Progress</span>',
            'completed' => '<span class="badge bg-success">Completed</span>',
            'failed' => '<span class="badge bg-danger">Failed</span>',
        ];
        
        return $badges[$status] ?? '<span class="badge bg-secondary">Unknown</span>';
    }

    /**
     * Reassign a pool migration task to another user.
     *
     * @param Request $request
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function reassignPoolMigrationTask(Request $request, $taskId)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|integer|exists:users,id',
                'notes' => 'nullable|string|max:1000',
            ]);

            $task = \App\Models\PoolOrderMigrationTask::findOrFail($taskId);
            $service = new PoolMigrationTaskService();

            $result = $service->reassignTask($task, $validated['user_id'], $validated['notes'] ?? null);

            return response()->json($result, $result['statusCode']);

        } catch (\Exception $e) {
            Log::error("Error reassigning pool migration task {$taskId}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to reassign task: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get pool panel reassignment tasks (pool_panels / pool_panel_splits).
     */
    public function getPoolPanelReassignmentTasks(Request $request)
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

            // Query ALL statuses initially to check relationships
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            } else {
                // Don't filter by status here - we'll filter later
            }

            // Filter by assigned status if provided
            if ($request->filled('assigned_status')) {
                if ($request->assigned_status === 'unassigned') {
                    $query->whereNull('assigned_to');
                } elseif ($request->assigned_status === 'assigned') {
                    $query->whereNotNull('assigned_to');
                }
            }

            if ($request->filled('pool_id')) {
                $query->where('pool_id', $request->pool_id);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('reassignment_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('reassignment_date', '<=', $request->date_to);
            }

            $query->orderBy('reassignment_date', 'desc');

            $perPage = (int) $request->get('per_page', 12);
            $page = (int) $request->get('page', 1);

            $paginatedTasks = $query->paginate($perPage, ['*'], 'page', $page);

            // Group tasks by pool_id and filter based on action_type and status
            $groupedTasks = $paginatedTasks->getCollection()->groupBy('pool_id');
            
            $filteredTasks = collect();
            
            foreach ($groupedTasks as $poolId => $tasks) {
                // If there's only one task for this pool, check if it's pending
                if ($tasks->count() === 1) {
                    $singleTask = $tasks->first();
                    // Only include if it's pending
                    if ($singleTask->status === 'pending') {
                        $filteredTasks->push($singleTask);
                    }
                    continue;
                }
                
                // Find removed and added tasks
                $removedTask = $tasks->firstWhere('action_type', 'removed');
                $addedTask = $tasks->firstWhere('action_type', 'added');

                // If there's a removed task and it's NOT completed, check if it's pending
                if ($removedTask && $removedTask->status !== 'completed') {
                    // Only show removed task if it's pending
                    if ($removedTask->status === 'pending') {
                        $filteredTasks->push($removedTask);
                    }
                } 
                // If removed task is completed and there's an added task, check if added task is pending
                elseif ($removedTask && $removedTask->status === 'completed' && $addedTask) {
                    // Only show added task if it's pending
                    if ($addedTask->status === 'pending') {
                        $filteredTasks->push($addedTask);
                    }
                }
                // If no removed task but there's an added task, check if added task is pending
                elseif (!$removedTask && $addedTask) {
                    // Only show added task if it's pending
                    if ($addedTask->status === 'pending') {
                        $filteredTasks->push($addedTask);
                    }
                }
                // Default: show all pending tasks if none of the above conditions match
                else {
                    foreach ($tasks as $task) {
                        if ($task->status === 'pending') {
                            $filteredTasks->push($task);
                        }
                    }
                }
            }

            // Transform the filtered tasks (all should be pending now)
            $tasksData = $filteredTasks->map(function (PoolPanelReassignmentHistory $task) {
                $pool = $task->pool;
                $fromPanel = $task->fromPoolPanel;
                $toPanel = $task->toPoolPanel;

                return [
                    'id' => $task->id,
                    'task_id' => $task->id,
                    'type' => 'pool_panel_reassignment',
                    'pool_id' => $task->pool_id,
                    'pool_name' => $pool && method_exists($pool, 'plan') && $pool->relationLoaded('plan') ? ($pool->plan->name ?? null) : null,
                    'domain_url' => $pool->domain_url ?? null,
                    'from_panel' => $fromPanel ? [
                        'id' => $fromPanel->id,
                        'title' => $fromPanel->title,
                    ] : null,
                    'to_panel' => $toPanel ? [
                        'id' => $toPanel->id,
                        'title' => $toPanel->title,
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

    /**
     * Assign a pool panel reassignment task to current admin.
     */
    public function assignPoolPanelReassignmentTaskToMe(Request $request, $taskId)
    {
        try {
            $adminId = auth()->id();

            $task = PoolPanelReassignmentHistory::findOrFail($taskId);

            if ($task->assigned_to) {
                return response()->json([
                    'success' => false,
                    'message' => 'This pool panel reassignment task is already assigned to another user.',
                ], 400);
            }

            $task->markAsStarted($adminId);

            return response()->json([
                'success' => true,
                'message' => 'Pool panel reassignment task assigned successfully!',
                'task' => [
                    'id' => $task->id,
                    'assigned_to' => $adminId,
                    'status' => $task->status,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error("Error assigning pool panel reassignment task {$taskId}: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to assign pool panel reassignment task: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pool panel reassignment task details for offcanvas view.
     */
    public function getPoolPanelReassignmentTaskDetails($taskId)
    {
        try {
            $task = PoolPanelReassignmentHistory::with([
                'pool',
                'poolPanel',
                'fromPoolPanel',
                'toPoolPanel',
                'reassignedBy',
                'assignedTo',
            ])->findOrFail($taskId);

            $pool = $task->pool;

            $taskInfo = [
                'id' => $task->id,
                'task_id' => $task->id,
                'pool_id' => $task->pool_id,
                'from_pool_panel_id' => $task->from_pool_panel_id,
                'to_pool_panel_id' => $task->to_pool_panel_id,
                'action_type' => $task->action_type,
                'space_transferred' => $task->space_transferred,
                'splits_count' => $task->splits_count,
                'status' => $task->status,
                'reason' => $task->reason,
                'reassignment_date' => $task->reassignment_date,
                'task_started_at' => $task->task_started_at,
                'task_completed_at' => $task->task_completed_at,
                'completion_notes' => $task->completion_notes,
                'created_at' => $task->created_at,
                'updated_at' => $task->updated_at,
                'assigned_to' => $task->assigned_to,
                'assigned_to_name' => $task->assignedTo->name ?? null,
                'reassigned_by_name' => $task->reassignedBy->name ?? null,
                'notes' => $task->notes,
            ];

            $poolInfo = null;
            if ($pool) {
                $poolInfo = [
                    'id' => $pool->id,
                    'plan_name' => method_exists($pool, 'plan') && $pool->relationLoaded('plan') ? ($pool->plan->name ?? null) : null,
                    'domain_url' => $pool->domain_url ?? null,
                    'total_inboxes' => $pool->total_inboxes ?? null,
                    'status' => $pool->status ?? null,
                    'created_at' => $pool->created_at,
                ];
            }

            $fromPanel = $task->fromPoolPanel ? [
                'id' => $task->fromPoolPanel->id,
                'title' => $task->fromPoolPanel->title,
                'auto_generated_id' => $task->fromPoolPanel->auto_generated_id ?? null,
                'limit' => $task->fromPoolPanel->limit ?? null,
                'remaining_limit' => $task->fromPoolPanel->remaining_limit ?? null,
            ] : null;

            $toPanel = $task->toPoolPanel ? [
                'id' => $task->toPoolPanel->id,
                'title' => $task->toPoolPanel->title,
                'auto_generated_id' => $task->toPoolPanel->auto_generated_id ?? null,
                'limit' => $task->toPoolPanel->limit ?? null,
                'remaining_limit' => $task->toPoolPanel->remaining_limit ?? null,
            ] : null;

            return response()->json([
                'success' => true,
                'task' => $taskInfo,
                'pool' => $poolInfo,
                'from_panel' => $fromPanel,
                'to_panel' => $toPanel,
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching pool panel reassignment task details: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Pool panel reassignment task not found or error fetching details',
            ], 404);
        }
    }
}
