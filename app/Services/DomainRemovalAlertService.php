<?php

namespace App\Services;

use App\Models\DomainRemovalTask;
use Carbon\Carbon;

class DomainRemovalAlertService
{
    /**
     * Get alert configuration for domain removal tasks
     * Based on hours elapsed since started_queue_date
     */
    public static function getAlertConfiguration(): array
    {
        return [
            6 => [
                'type' => '6h',
                'title' => 'Domain Removal Task: 6 Hours Since Queue Start',
                'description' => '6 hours have elapsed since the task was queued for processing',
                'urgency' => 'warning',
                'icon' => '⚠️',
                'color' => '#ffc107', // Yellow
                'repeat' => false
            ],
            9 => [
                'type' => '3h',
                'title' => 'Domain Removal Task: 3 Hours Remaining',
                'description' => '3 hours remaining if following 12-hour processing timeline',
                'urgency' => 'urgent',
                'icon' => '🔥',
                'color' => '#fd7e14', // Orange
                'repeat' => false
            ],
            10 => [
                'type' => '2h',
                'title' => 'Domain Removal Task: 2 Hours Remaining',
                'description' => '2 hours remaining if following 12-hour processing timeline',
                'urgency' => 'critical',
                'icon' => '🚨',
                'color' => '#dc3545', // Red
                'repeat' => false
            ],
            11 => [
                'type' => '1h',
                'title' => 'Domain Removal Task: 1 Hour Remaining',
                'description' => '1 hour remaining if following 12-hour processing timeline',
                'urgency' => 'critical',
                'icon' => '🚨',
                'color' => '#dc3545', // Red
                'repeat' => false
            ],
            12 => [
                'type' => '0h',
                'title' => 'Domain Removal Task: Deadline Reached',
                'description' => '12-hour processing deadline has been reached',
                'urgency' => 'deadline',
                'icon' => '⏰',
                'color' => '#6f42c1', // Purple
                'repeat' => false
            ]
        ];
    }

    /**
     * Get overdue alert configuration
     */
    public static function getOverdueAlertConfig(int $hour): array
    {
        $overdueHours = $hour - 12;
        
        return [
            'type' => "overdue-{$hour}h",
            'title' => "Domain Removal Task: {$overdueHours}h Overdue",
            'description' => "Task is {$overdueHours} hours past the recommended 12-hour processing timeline",
            'urgency' => 'critical_overdue',
            'icon' => '🚨',
            'color' => '#dc3545', // Red
            'repeat' => true
        ];
    }

    /**
     * Calculate hours elapsed since started_queue_date
     */
    public static function calculateHoursElapsed(DomainRemovalTask $task): float
    {
        $startedQueueDate = Carbon::parse($task->started_queue_date);
        return $startedQueueDate->diffInHours(Carbon::now(), false);
    }

    /**
     * Get task data for alerts
     */
    public static function getTaskAlertData(DomainRemovalTask $task): array
    {
        $order = $task->order;
        $reorderInfo = $order ? $order->reorderInfo->first() : null;
        
        // Calculate inboxes and splits
        $totalInboxes = 0;
        $totalDomains = 0;
        $splitsCount = 0;
        
        if ($reorderInfo) {
            $totalInboxes = $reorderInfo->total_inboxes ?? 0;
            $inboxesPerDomain = $reorderInfo->inboxes_per_domain ?? 1;
            $totalDomains = $inboxesPerDomain > 0 ? intval($totalInboxes / $inboxesPerDomain) : 0;
        }

        if ($order && $order->orderPanels) {
            $splitsCount = $order->orderPanels->sum(function($panel) {
                return $panel->orderPanelSplits->count();
            });
        }

        return [
            'task_id' => $task->id,
            'order_id' => $order ? $order->id : 'N/A',
            'customer_name' => $task->user ? $task->user->name : 'Unknown',
            'customer_email' => $task->user ? $task->user->email : 'Unknown',
            'contractor_name' => $task->assignedTo ? $task->assignedTo->name : 'Unassigned',
            'contractor_email' => $task->assignedTo ? $task->assignedTo->email : 'N/A',
            'total_inboxes' => $totalInboxes,
            'total_domains' => $totalDomains,
            'splits_count' => $splitsCount,
            'started_queue_date' => $task->started_queue_date,
            'status' => $task->status,
            'reason' => $task->reason ?? 'N/A',
            'chargebee_subscription_id' => $task->chargebee_subscription_id,
        ];
    }

    /**
     * Check if alert should be sent
     */
    public static function shouldSendAlert(DomainRemovalTask $task, string $alertType, bool $force = false): bool
    {
        if ($force) {
            return true;
        }

        $lastAlerts = $task->slack_alerts ?? [];
        
        if (!isset($lastAlerts[$alertType])) {
            return true; // Never sent this type of alert
        }

        $lastAlertTime = Carbon::parse($lastAlerts[$alertType]);
        
        // For overdue alerts, send hourly
        if (str_starts_with($alertType, 'overdue-')) {
            return $lastAlertTime->diffInHours(Carbon::now()) >= 1;
        }
        
        // For regular alerts, send only once
        return false;
    }

    /**
     * Mark alert as sent
     */
    public static function markAlertSent(DomainRemovalTask $task, string $alertType): void
    {
        $alerts = $task->slack_alerts ?? [];
        $alerts[$alertType] = Carbon::now()->toDateTimeString();
        
        $task->update(['slack_alerts' => $alerts]);
    }

    /**
     * Get tasks that need alert checking
     */
    public static function getTasksForAlertChecking(): \Illuminate\Database\Eloquent\Collection
    {
        return DomainRemovalTask::with(['user', 'order.reorderInfo', 'order.orderPanels.orderPanelSplits', 'assignedTo'])
            ->whereIn('status', ['in-progress', 'pending'])
            ->where('started_queue_date', '<=', Carbon::now())
            ->orderBy('started_queue_date', 'asc')
            ->get();
    }

    /**
     * Generate comprehensive Slack message
     */
    public static function generateSlackMessage(array $taskData, array $alertConfig, float $hoursElapsed): array
    {
        $appName = config('app.name', 'ProjectInbox');
        
        return [
            'text' => $alertConfig['icon'] . " *DOMAIN REMOVAL TASK ALERT - " . strtoupper($alertConfig['urgency']) . "*",
            'attachments' => [
                [
                    'color' => $alertConfig['color'],
                    'title' => $alertConfig['title'],
                    'text' => $alertConfig['description'],
                    'fields' => [
                        [
                            'title' => 'Task ID',
                            'value' => $taskData['task_id'],
                            'short' => true
                        ],
                        [
                            'title' => 'Order ID',
                            'value' => $taskData['order_id'],
                            'short' => true
                        ],
                        [
                            'title' => 'Customer Name',
                            'value' => $taskData['customer_name'],
                            'short' => true
                        ],
                        [
                            'title' => 'Customer Email',
                            'value' => $taskData['customer_email'],
                            'short' => true
                        ],
                        [
                            'title' => 'Contractor',
                            'value' => $taskData['contractor_name'],
                            'short' => true
                        ],
                        [
                            'title' => 'Contractor Email',
                            'value' => $taskData['contractor_email'],
                            'short' => true
                        ],
                        [
                            'title' => 'Total Inboxes',
                            'value' => number_format($taskData['total_inboxes']),
                            'short' => true
                        ],
                        [
                            'title' => 'Total Domains',
                            'value' => number_format($taskData['total_domains']),
                            'short' => true
                        ],
                        [
                            'title' => 'Splits Count',
                            'value' => $taskData['splits_count'],
                            'short' => true
                        ],
                        [
                            'title' => 'Status',
                            'value' => ucfirst($taskData['status']),
                            'short' => true
                        ],
                        [
                            'title' => 'Hours Elapsed',
                            'value' => round($hoursElapsed, 1) . 'h',
                            'short' => true
                        ],
                        [
                            'title' => 'Started Queue Date',
                            'value' => Carbon::parse($taskData['started_queue_date'])->format('Y-m-d H:i:s T'),
                            'short' => true
                        ],
                        [
                            'title' => 'Subscription ID',
                            'value' => $taskData['chargebee_subscription_id'],
                            'short' => false
                        ],
                        [
                            'title' => 'Cancellation Reason',
                            'value' => $taskData['reason'],
                            'short' => false
                        ]
                    ],
                    'footer' => $appName . ' Domain Removal Alert System',
                    'ts' => time()
                ]
            ]
        ];
    }
}
