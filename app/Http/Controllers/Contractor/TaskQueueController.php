<?php

namespace App\Http\Controllers\Contractor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DomainRemovalTask;
use App\Models\Order;
use App\Models\PanelReassignmentHistory;
use App\Services\PanelReassignmentService;
use Carbon\Carbon;

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
     * Get shifted pending tasks for contractors
     */
    public function getShiftedPendingTasks(Request $request)
    {
        try {
            // $query = PanelReassignmentHistory::with([
            //     'order.user',
            //     'fromPanel',
            //     'toPanel',
            //     'reassignedBy',
            //     'assignedTo'
            // ])->where('status', 'pending');
            $query = PanelReassignmentHistory::with([
                'order.user',
                'orderPanel.orderPanelSplits',
                'fromPanel',
                'toPanel',
                'reassignedBy',
                'assignedTo'
            ])->pending(); // Only pending tasks

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

            $perPage = $request->get('per_page', 12);
            $shiftedTasks = $query->orderBy('reassignment_date', 'desc')->paginate($perPage);

            // Transform the data to include customer information
            $shiftedTasks->getCollection()->transform(function ($task) {
                $order = $task->order;
                $task->customer_name = $order && $order->user ? $order->user->name : 'N/A';
                $task->customer_image = $order && $order->user && $order->user->profile_image 
                    ? asset('storage/profile_images/' . $order->user->profile_image) 
                    : null;
                return $task;
            });

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $shiftedTasks->items(),
                    'current_page' => $shiftedTasks->currentPage(),
                    'last_page' => $shiftedTasks->lastPage(),
                    'per_page' => $shiftedTasks->perPage(),
                    'total' => $shiftedTasks->total(),
                    'has_more_pages' => $shiftedTasks->hasMorePages()
                ]);
            }

            return view('contractor.taskInQueue.shifted-pending', compact('shiftedTasks'));
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
                'completion_date' => 'nullable|date'
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
                $completeResult = app(PanelReassignmentService::class)->completePanelReassignmentTask($task);
                
                if (!$completeResult) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to complete task'
                    ], 500);
                }
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
}
