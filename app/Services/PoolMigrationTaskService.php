<?php

namespace App\Services;

use App\Models\Pool;
use App\Models\PoolOrderMigrationTask;
use Illuminate\Support\Facades\Log;

class PoolMigrationTaskService
{
    /**
     * Validate domain statuses before completing a task
     * 
     * @param PoolOrderMigrationTask $task
     * @return array|null Returns array with error response data if validation fails, null if all domains are subscribed
     */
    public function validateDomainStatuses(PoolOrderMigrationTask $task): ?array
    {
        $poolOrder = $task->poolOrder;
        
        if (!$poolOrder || !$poolOrder->domains) {
            return null;
        }
        
        $domains = is_string($poolOrder->domains) 
            ? json_decode($poolOrder->domains, true) 
            : $poolOrder->domains;
        
        if (!is_array($domains)) {
            return null;
        }
        
        $nonSubscribedDomains = [];
        
        foreach ($domains as $domain) {
            // Get status, default to 'warming' if not set
            $domainStatus = $domain['status'] ?? 'warming';
            
            // Only flag if status is not 'subscribed'
            if ($domainStatus !== 'subscribed') {
                $domainName = $this->resolveDomainName($domain);
                
                $nonSubscribedDomains[] = [
                    'name' => $domainName,
                    'status' => $domainStatus
                ];
            }
        }
        
        if (empty($nonSubscribedDomains)) {
            return null;
        }
        
        return [
            'success' => false,
            'requiresConfirmation' => true,
            'message' => 'Some domains are not in subscribed status',
            'nonSubscribedDomains' => $nonSubscribedDomains,
            'totalDomains' => count($domains),
            'nonSubscribedCount' => count($nonSubscribedDomains)
        ];
    }
    
    /**
     * Resolve domain name from various sources
     * 
     * @param array $domain
     * @return string
     */
    private function resolveDomainName(array $domain): string
    {
        // Try multiple keys for domain name
        $domainName = $domain['domain_name'] ?? $domain['name'] ?? null;
        
        // If still no name, try to get it from the pool
        if (!$domainName && isset($domain['domain_id']) && isset($domain['pool_id'])) {
            $pool = Pool::find($domain['pool_id']);
            if ($pool && is_array($pool->domains)) {
                foreach ($pool->domains as $poolDomain) {
                    if (isset($poolDomain['id']) && $poolDomain['id'] == $domain['domain_id']) {
                        $domainName = $poolDomain['name'] ?? null;
                        break;
                    }
                }
            }
        }
        
        return $domainName ?: 'Domain #' . ($domain['domain_id'] ?? 'Unknown');
    }
    
    /**
     * Update pool migration task status with validation
     * 
     * @param PoolOrderMigrationTask $task
     * @param string $status
     * @param string|null $notes
     * @param bool $force
     * @param int|null $userId User ID for authorization check (optional)
     * @return array Response data
     */
    public function updateTaskStatus(
        PoolOrderMigrationTask $task, 
        string $status, 
        ?string $notes = null, 
        bool $force = false,
        ?int $userId = null
    ): array {
        try {
            // Authorization check if userId is provided
            if ($userId !== null && $task->assigned_to && $task->assigned_to !== $userId) {
                return [
                    'success' => false,
                    'message' => 'You can only update tasks assigned to you.',
                    'statusCode' => 403
                ];
            }
            
            // Check domain statuses before marking as completed (unless force is true)
            if ($status === 'completed' && !$force) {
                $validationError = $this->validateDomainStatuses($task);
                
                if ($validationError) {
                    return array_merge($validationError, ['statusCode' => 422]);
                }
            }
            
            // Prepare updates
            $updates = ['status' => $status];
            
            if ($notes !== null) {
                $updates['notes'] = $notes;
            }
            
            // Set timestamps based on status
            if ($status === 'completed') {
                $updates['completed_at'] = now();
            } elseif ($status === 'in-progress' && !$task->started_at) {
                $updates['started_at'] = now();
            }
            
            // Update the task
            $task->update($updates);
            
            Log::info("Pool migration task {$task->id} status updated to {$status} by user {$userId}");
            
            return [
                'success' => true,
                'message' => 'Task status updated successfully',
                'task' => [
                    'id' => $task->id,
                    'status' => $task->status,
                    'notes' => $task->notes,
                    'started_at' => $task->started_at,
                    'completed_at' => $task->completed_at
                ],
                'statusCode' => 200
            ];
            
        } catch (\Exception $e) {
            Log::error('Error updating pool migration task status: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to update task status: ' . $e->getMessage(),
                'statusCode' => 500
            ];
        }
    }
    
    /**
     * Assign pool migration task to a user
     * 
     * @param PoolOrderMigrationTask $task
     * @param int $userId
     * @return array Response data
     */
    public function assignTask(PoolOrderMigrationTask $task, int $userId): array
    {
        try {
            if ($task->assigned_to) {
                return [
                    'success' => false,
                    'message' => 'This task is already assigned to someone',
                    'statusCode' => 400
                ];
            }
            
            $task->update([
                'assigned_to' => $userId,
                'status' => 'in-progress',
                'started_at' => now()
            ]);
            
            Log::info("Pool migration task {$task->id} assigned to user {$userId}");
            
            return [
                'success' => true,
                'message' => 'Task assigned successfully',
                'task' => [
                    'id' => $task->id,
                    'assigned_to' => $task->assigned_to,
                    'status' => $task->status
                ],
                'statusCode' => 200
            ];
            
        } catch (\Exception $e) {
            Log::error('Error assigning pool migration task: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to assign task: ' . $e->getMessage(),
                'statusCode' => 500
            ];
        }
    }

    /**
     * Get pool migration task details with full order information
     * 
     * @param int $taskId
     * @param int|null $userId User ID for authorization check (null for admin, specific ID for contractor)
     * @param bool $adminAccess Whether this is admin access (admins can see all tasks)
     * @return array Response data
     */
    public function getTaskDetails(int $taskId, ?int $userId = null, bool $adminAccess = false): array
    {
        try {
            $query = PoolOrderMigrationTask::with([
                'poolOrder.poolPlan',
                'poolOrder.user',
                'user',
                'assignedTo'
            ]);

            // If not admin access and userId provided, ensure contractor can only view their assigned tasks
            if (!$adminAccess && $userId !== null) {
                $query->where('assigned_to', $userId);
            }

            $task = $query->find($taskId);

            if (!$task) {
                return [
                    'success' => false,
                    'message' => 'Task not found or access denied',
                    'statusCode' => 404
                ];
            }

            $poolOrder = $task->poolOrder;
            $user = $poolOrder ? $poolOrder->user : null;
            $metadata = $task->metadata ?? [];

            $taskInfo = [
                'id' => $task->id,
                'pool_order_id' => $task->pool_order_id,
                'task_type' => $task->task_type,
                'task_type_label' => $task->task_type === 'configuration' ? 'Configuration Task' : 'Cancellation Cleanup Task',
                'task_type_icon' => $task->task_type === 'configuration' ? '📋' : '🔧',
                'status' => $task->status,
                'previous_status' => $task->previous_status,
                'new_status' => $task->new_status,
                'assigned_to' => $task->assigned_to,
                'assigned_to_name' => $task->assignedTo ? $task->assignedTo->name : 'Unassigned',
                'notes' => $task->notes,
                'created_at' => $task->created_at->format('Y-m-d H:i:s'),
                'started_at' => $task->started_at ? $task->started_at->format('Y-m-d H:i:s') : null,
                'completed_at' => $task->completed_at ? $task->completed_at->format('Y-m-d H:i:s') : null,
            ];

            $orderInfo = [
                'order_id' => $poolOrder->order_id ?? null,
                'plan_name' => $metadata['plan_name'] ?? ($poolOrder && $poolOrder->poolPlan ? $poolOrder->poolPlan->name : 'N/A'),
                'selected_domains_count' => $metadata['selected_domains_count'] ?? ($poolOrder->selected_domains_count ?? 0),
                'total_inboxes' => $metadata['total_inboxes'] ?? ($poolOrder->total_inboxes ?? 0),
                'hosting_platform' => $metadata['hosting_platform'] ?? ($poolOrder->hosting_platform ?? 'N/A'),
                'customer_name' => $user ? $user->name : 'N/A',
                'customer_email' => $user ? $user->email : 'N/A',
            ];

            return [
                'success' => true,
                'task' => $taskInfo,
                'order' => $orderInfo,
                'statusCode' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching pool migration task details: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Failed to fetch task details: ' . $e->getMessage(),
                'statusCode' => 500
            ];
        }
    }

    /**
     * Get paginated pool migration tasks for a specific user
     * 
     * @param int $userId User ID to filter tasks by
     * @param array $filters Additional filters (status, task_type, date_from, date_to)
     * @param int $perPage Number of items per page
     * @param int $page Current page number
     * @return array Response data with tasks and pagination
     */
    public function getMyPoolMigrationTasks(int $userId, array $filters = [], int $perPage = 12, int $page = 1): array
    {
        try {
            $query = PoolOrderMigrationTask::with([
                'poolOrder.poolPlan',
                'poolOrder.user',
                'user',
                'assignedTo'
            ])
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->where('assigned_to', $userId);
            
            // Filter by status if provided
            if (!empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            // Filter by task type if provided
            if (!empty($filters['task_type'])) {
                $query->where('task_type', $filters['task_type']);
            }

            // Filter by date range
            if (!empty($filters['date_from'])) {
                $query->whereDate('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $query->whereDate('created_at', '<=', $filters['date_to']);
            }

            // Apply ordering
            $query->orderBy('created_at', 'desc');
            
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
                    'task_type_label' => ucfirst($task->task_type),
                    'task_type_icon' => $task->task_type === 'configuration' ? '⚙️' : '❌',
                    'status' => $task->status,
                    'customer_name' => $user ? $user->name : 'N/A',
                    'customer_email' => $user ? $user->email : 'N/A',
                    'customer_image' => $user && $user->profile_image 
                        ? asset('storage/profile_images/' . $user->profile_image) 
                        : null,
                    'plan_name' => $metadata['plan_name'] ?? ($poolOrder && $poolOrder->poolPlan ? $poolOrder->poolPlan->name : 'N/A'),
                    'amount' => $metadata['amount'] ?? 0,
                    'quantity' => $metadata['quantity'] ?? 0,
                    'domains_count' => $metadata['selected_domains_count'] ?? 0,
                    'total_inboxes' => $metadata['total_inboxes'] ?? 0,
                    'hosting_platform' => $metadata['hosting_platform'] ?? 'N/A',
                    'assigned_to' => $task->assigned_to,
                    'assigned_to_name' => $task->assignedTo ? $task->assignedTo->name : 'N/A',
                    'notes' => $task->notes,
                    'created_at' => $task->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $task->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return [
                'success' => true,
                'data' => $tasksData,
                'pagination' => [
                    'current_page' => $paginatedTasks->currentPage(),
                    'per_page' => $paginatedTasks->perPage(),
                    'total' => $paginatedTasks->total(),
                    'last_page' => $paginatedTasks->lastPage(),
                    'has_more_pages' => $paginatedTasks->hasMorePages()
                ],
                'statusCode' => 200
            ];

        } catch (\Exception $e) {
            Log::error('Error fetching my pool migration tasks: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error fetching pool migration tasks: ' . $e->getMessage(),
                'statusCode' => 500
            ];
        }
    }
}
