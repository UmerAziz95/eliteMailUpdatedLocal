<?php

namespace App\Observers;

use App\Models\PoolOrderMigrationTask;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Log;

class PoolOrderMigrationTaskObserver
{
    /**
     * Handle the PoolOrderMigrationTask "updated" event.
     */
    public function updated(PoolOrderMigrationTask $task): void
    {
        // Check if task was assigned (assigned_to changed from null to a user ID)
        if ($task->isDirty('assigned_to') && $task->assigned_to !== null && $task->getOriginal('assigned_to') === null) {
            Log::info('Pool migration task assigned, sending Slack notification', [
                'task_id' => $task->id,
                'assigned_to' => $task->assigned_to,
                'task_type' => $task->task_type
            ]);
            
            $this->sendTaskAssignedNotification($task);
        }

        // Check if task status was updated get if last status was not 'pending'
        if ($task->isDirty('status') && $task->getOriginal('status') !== 'pending') {
            Log::info('Pool migration task status updated, sending Slack notification', [
                'task_id' => $task->id,
                'previous_status' => $task->getOriginal('status'),
                'new_status' => $task->status,
                'task_type' => $task->task_type
            ]);
            
            $this->sendTaskStatusUpdatedNotification($task);
        }
    }

    /**
     * Send Slack notification when task is assigned
     */
    protected function sendTaskAssignedNotification(PoolOrderMigrationTask $task): void
    {
        try {
            $poolOrder = $task->poolOrder;
            $assignedUser = $task->assignedTo;
            $customer = $poolOrder ? $poolOrder->user : null;
            $plan = $poolOrder && $poolOrder->poolPlan ? $poolOrder->poolPlan : null;

            $taskTypeLabel = $task->task_type === 'configuration' ? '⚙️ Configuration' : '🔧 Cancellation Cleanup';
            $taskTypeColor = $task->task_type === 'configuration' ? '#36a64f' : '#ff9800';

            $message = [
                'attachments' => [
                    [
                        'color' => $taskTypeColor,
                        'title' => "🎯 Pool Migration Task Assigned - {$taskTypeLabel}",
                        'title_link' => url("/admin/taskInQueue/pool-migration/{$task->id}/details"),
                        'fields' => [
                            [
                                'title' => 'Task ID',
                                'value' => "#{$task->id}",
                                'short' => true
                            ],
                            [
                                'title' => 'Task Type',
                                'value' => $taskTypeLabel,
                                'short' => true
                            ],
                            [
                                'title' => 'Assigned To',
                                'value' => $assignedUser ? $assignedUser->name : 'Unknown',
                                'short' => true
                            ],
                            [
                                'title' => 'Status',
                                'value' => ucfirst($task->status),
                                'short' => true
                            ],
                            [
                                'title' => 'Pool Order ID',
                                'value' => $poolOrder ? "#{$poolOrder->id}" : 'N/A',
                                'short' => true
                            ],
                            [
                                'title' => 'Plan',
                                'value' => $plan ? $plan->name : 'N/A',
                                'short' => true
                            ],
                            // [
                            //     'title' => 'Customer',
                            //     'value' => $customer ? "{$customer->name} ({$customer->email})" : 'N/A',
                            //     'short' => false
                            // ]
                        ],
                        'footer' => 'ProjectInbox Pool Migration System',
                        'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
                        'ts' => time()
                    ]
                ]
            ];

            $result = SlackNotificationService::send('inbox-trial-replacements', $message);
            
            if ($result) {
                Log::info('Slack notification sent successfully for task assignment', [
                    'task_id' => $task->id
                ]);
            } else {
                Log::warning('Failed to send Slack notification for task assignment', [
                    'task_id' => $task->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending Slack notification for task assignment: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send Slack notification when task status is updated
     */
    protected function sendTaskStatusUpdatedNotification(PoolOrderMigrationTask $task): void
    {
        try {
            $poolOrder = $task->poolOrder;
            $assignedUser = $task->assignedTo;
            $customer = $poolOrder ? $poolOrder->user : null;
            $plan = $poolOrder && $poolOrder->poolPlan ? $poolOrder->poolPlan : null;

            $previousStatus = $task->getOriginal('status');
            $newStatus = $task->status;

            $taskTypeLabel = $task->task_type === 'configuration' ? '⚙️ Configuration' : '🔧 Cancellation Cleanup';
            
            // Set color based on new status
            $statusColor = '#808080'; // default gray
            if ($newStatus === 'completed') {
                $statusColor = '#36a64f'; // green
            } elseif ($newStatus === 'in-progress') {
                $statusColor = '#2196F3'; // blue
            } elseif ($newStatus === 'cancelled') {
                $statusColor = '#f44336'; // red
            }

            $message = [
                'attachments' => [
                    [
                        'color' => $statusColor,
                        'title' => "📊 Pool Migration Task Status Updated - {$taskTypeLabel}",
                        'title_link' => url("/admin/taskInQueue/pool-migration/{$task->id}/details"),
                        'fields' => [
                            [
                                'title' => 'Task ID',
                                'value' => "#{$task->id}",
                                'short' => true
                            ],
                            [
                                'title' => 'Task Type',
                                'value' => $taskTypeLabel,
                                'short' => true
                            ],
                            [
                                'title' => 'Status Change',
                                'value' => "~~{$previousStatus}~~ → *{$newStatus}*",
                                'short' => true
                            ],
                            [
                                'title' => 'Assigned To',
                                'value' => $assignedUser ? $assignedUser->name : 'Unassigned',
                                'short' => true
                            ],
                            [
                                'title' => 'Pool Order ID',
                                'value' => $poolOrder ? "#{$poolOrder->id}" : 'N/A',
                                'short' => true
                            ],
                            [
                                'title' => 'Plan',
                                'value' => $plan ? $plan->name : 'N/A',
                                'short' => true
                            ],
                            // [
                            //     'title' => 'Customer',
                            //     'value' => $customer ? "{$customer->name} ({$customer->email})" : 'N/A',
                            //     'short' => false
                            // ]
                        ],
                        'footer' => 'ProjectInbox Pool Migration System',
                        'footer_icon' => 'https://platform.slack-edge.com/img/default_application_icon.png',
                        'ts' => time()
                    ]
                ]
            ];

            // Add notes if status is completed and notes exist
            if ($newStatus === 'completed' && $task->notes) {
                $message['attachments'][0]['fields'][] = [
                    'title' => 'Completion Notes',
                    'value' => $task->notes,
                    'short' => false
                ];
            }

            // Add timing information
            if ($task->started_at) {
                $message['attachments'][0]['fields'][] = [
                    'title' => 'Started At',
                    'value' => $task->started_at->format('Y-m-d H:i:s'),
                    'short' => true
                ];
            }

            if ($task->completed_at) {
                $message['attachments'][0]['fields'][] = [
                    'title' => 'Completed At',
                    'value' => $task->completed_at->format('Y-m-d H:i:s'),
                    'short' => true
                ];
            }

            $result = SlackNotificationService::send('inbox-trial-replacements', $message);
            
            if ($result) {
                Log::info('Slack notification sent successfully for task status update', [
                    'task_id' => $task->id,
                    'previous_status' => $previousStatus,
                    'new_status' => $newStatus
                ]);
            } else {
                Log::warning('Failed to send Slack notification for task status update', [
                    'task_id' => $task->id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error sending Slack notification for task status update: ' . $e->getMessage(), [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
