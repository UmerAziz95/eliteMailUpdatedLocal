<?php

namespace App\Observers;

use App\Models\DomainRemovalTask;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;

class DomainRemovalTaskObserver
{
    /**
     * Handle the DomainRemovalTask "updated" event.
     */
    public function updated(DomainRemovalTask $task): void
    {
        // Get the changes made to the task
        $changes = $task->getChanges();
        
        \Log::info('DomainRemovalTaskObserver: Task updated event triggered', [
            'task_id' => $task->id,
            'changes' => $changes
        ]);
        
        // Check if status was changed to completed
        if (isset($changes['status']) && $changes['status'] === 'completed') {
            $previousStatus = $task->getOriginal('status');
            $newStatus = $task->status;
            
            \Log::info('DomainRemovalTaskObserver: Task completion detected', [
                'task_id' => $task->id,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
                'order_id' => $task->order_id,
                'user_id' => $task->user_id
            ]);
            
            try {
                // Log the task completion
                ActivityLogService::log(
                    'domain_removal_task_completed', // Action type
                    'Domain removal task completed for Order #' . $task->order_id . ' - Task ID: ' . $task->id,
                    $task, // The model the action was performed on
                    [
                        'task_id' => $task->id,
                        'order_id' => $task->order_id,
                        'user_id' => $task->user_id,
                        'chargebee_subscription_id' => $task->chargebee_subscription_id,
                        'previous_status' => $previousStatus,
                        'new_status' => $newStatus,
                        'assigned_to' => $task->assigned_to,
                        'reason' => $task->reason,
                        'completed_at' => now()->toDateTimeString(),
                        'started_queue_date' => $task->started_queue_date ? $task->started_queue_date->toDateTimeString() : null,
                    ],
                    Auth::id() ?? $task->assigned_to ?? 1 // Who performed the action - fallback to assigned user or admin
                );
                
                \Log::info('DomainRemovalTaskObserver: Task completion logged successfully', [
                    'task_id' => $task->id,
                    'order_id' => $task->order_id
                ]);
                
            } catch (\Exception $e) {
                \Log::error('DomainRemovalTaskObserver: Failed to log task completion', [
                    'task_id' => $task->id,
                    'order_id' => $task->order_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // Also log when task status changes to other statuses for better tracking
        if (isset($changes['status'])) {
            $previousStatus = $task->getOriginal('status');
            $newStatus = $task->status;
            
            // Don't duplicate the completed status log
            if ($newStatus !== 'completed') {
                try {
                    ActivityLogService::log(
                        'domain_removal_task_status_updated',
                        'Domain removal task status updated from ' . $previousStatus . ' to ' . $newStatus . ' - Task ID: ' . $task->id,
                        $task,
                        [
                            'task_id' => $task->id,
                            'order_id' => $task->order_id,
                            'user_id' => $task->user_id,
                            'chargebee_subscription_id' => $task->chargebee_subscription_id,
                            'previous_status' => $previousStatus,
                            'new_status' => $newStatus,
                            'assigned_to' => $task->assigned_to,
                            'reason' => $task->reason,
                        ],
                        Auth::id() ?? $task->assigned_to ?? 1
                    );
                    
                    \Log::info('DomainRemovalTaskObserver: Task status change logged', [
                        'task_id' => $task->id,
                        'from' => $previousStatus,
                        'to' => $newStatus
                    ]);
                    
                } catch (\Exception $e) {
                    \Log::error('DomainRemovalTaskObserver: Failed to log task status change', [
                        'task_id' => $task->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
    
    /**
     * Handle the DomainRemovalTask "created" event.
     */
    // public function created(DomainRemovalTask $task): void
    // {
    //     \Log::info('DomainRemovalTaskObserver: Task created event triggered', [
    //         'task_id' => $task->id,
    //         'order_id' => $task->order_id,
    //         'status' => $task->status
    //     ]);
        
    //     try {
    //         // Log the task creation
    //         ActivityLogService::log(
    //             'domain_removal_task_created',
    //             'Domain removal task created for Order #' . $task->order_id . ' - Task ID: ' . $task->id,
    //             $task,
    //             [
    //                 'task_id' => $task->id,
    //                 'order_id' => $task->order_id,
    //                 'user_id' => $task->user_id,
    //                 'chargebee_subscription_id' => $task->chargebee_subscription_id,
    //                 'status' => $task->status,
    //                 'assigned_to' => $task->assigned_to,
    //                 'reason' => $task->reason,
    //                 'started_queue_date' => $task->started_queue_date ? $task->started_queue_date->toDateTimeString() : null,
    //             ],
    //             Auth::id() ?? 1 // Who performed the action
    //         );
            
    //         \Log::info('DomainRemovalTaskObserver: Task creation logged successfully', [
    //             'task_id' => $task->id
    //         ]);
            
    //     } catch (\Exception $e) {
    //         \Log::error('DomainRemovalTaskObserver: Failed to log task creation', [
    //             'task_id' => $task->id,
    //             'error' => $e->getMessage()
    //         ]);
    //     }
    // }
}
