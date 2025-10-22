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
}
