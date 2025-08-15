<?php

namespace App\Services;

use App\Models\DomainRemovalTask;
use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\Panel;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class PanelReleasedSpacedService
{
    /**
     * Mark task as completed and release assigned spaces from panels
     *
     * @param int $taskId
     * @return array
     */
    public function completeTaskAndReleaseSpaces($taskId)
    {
        try {
            DB::beginTransaction();

            // Find the domain removal task
            $task = DomainRemovalTask::with(['order.orderPanels.orderPanelSplits'])->findOrFail($taskId);
            
            if ($task->status === 'completed') {
                return [
                    'success' => false,
                    'message' => 'Task is already completed'
                ];
            }

            // Get the order
            $order = $task->order;
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Order not found for this task'
                ];
            }

            $releasedSpaces = 0;
            $processedSplits = 0;
            $processedPanels = [];

            // Process all order panels and their splits
            foreach ($order->orderPanels as $orderPanel) {
                // orderPanel status to set deleted
                $orderPanel->status = 'deleted';
                $orderPanel->save();
                
                foreach ($orderPanel->orderPanelSplits as $split) {
                    
                    // Calculate the space to release based on domains count
                    $domainsCount = 0;
                    if ($split->domains) {
                        if (is_array($split->domains)) {
                            $domainsCount = count($split->domains);
                        } elseif (is_string($split->domains)) {
                            $domains = json_decode($split->domains, true);
                            $domainsCount = is_array($domains) ? count($domains) : 0;
                        }
                    }
                    // Release space from the panel
                    if ($domainsCount > 0) {
                        // current domains multiple with $split->inboxes_per_domain
                        $domainsCount *= $split->inboxes_per_domain ?? 1; // Ensure we multiply by inboxes per domain
                        $panel = Panel::find($orderPanel->panel_id);
                        if ($panel) {
                            // Add the domains count back to available space
                            $currentAvailable = $panel->remaining_limit ?? 0;
                            $panel->remaining_limit = $currentAvailable + $domainsCount;
                            $panel->save();
                            
                            $releasedSpaces += $domainsCount;
                            $processedPanels[] = $panel->id;
                            
                            Log::info("Released {$domainsCount} spaces to Panel {$panel->id} (new available: {$panel->remaining_limit})");
                        }
                    }

                    $processedSplits++;
                }
            }

            // Update task status to completed
            $task->status = 'completed';
            $task->save();
            // set order status to removed
            $order->status_manage_by_admin = 'removed';
            $order->save();

            // Log the completion
            Log::info("Task {$taskId} completed successfully. Released {$releasedSpaces} spaces from " . count(array_unique($processedPanels)) . " panels");

            // Send Slack notification for task completion
            $completionData = [
                'task_id' => $taskId,
                'released_spaces' => $releasedSpaces,
                'processed_splits' => $processedSplits,
                'affected_panels' => array_unique($processedPanels)
            ];
            
            try {
                SlackNotificationService::sendTaskCompletionNotification($task, $completionData);
            } catch (\Exception $e) {
                Log::error("Failed to send Slack notification for task completion: " . $e->getMessage());
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Task completed successfully',
                'data' => [
                    'task_id' => $taskId,
                    'released_spaces' => $releasedSpaces,
                    'processed_splits' => $processedSplits,
                    'affected_panels' => array_unique($processedPanels)
                ]
            ];

        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error completing task {$taskId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error completing task: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get task completion summary for confirmation dialog
     *
     * @param int $taskId
     * @return array
     */
    public function getTaskCompletionSummary($taskId)
    {
        try {
            $task = DomainRemovalTask::with(['order.orderPanels.orderPanelSplits', 'order.orderPanels.panel'])->findOrFail($taskId);
            
            $totalSpacesToRelease = 0;
            $panelsAffected = [];
            $splitsCount = 0;

            if ($task->order && $task->order->orderPanels) {
                foreach ($task->order->orderPanels as $orderPanel) {
                    foreach ($orderPanel->orderPanelSplits as $split) {
                        $domainsCount = 0;
                        if ($split->domains) {
                            if (is_array($split->domains)) {
                                $domainsCount = count($split->domains);
                            } elseif (is_string($split->domains)) {
                                $domains = json_decode($split->domains, true);
                                $domainsCount = is_array($domains) ? count($domains) : 0;
                            }
                        }

                        if ($domainsCount > 0) {
                            $domainsCount *= $split->inboxes_per_domain ?? 1; // Ensure we multiply by inboxes per domain
                            $totalSpacesToRelease += $domainsCount;
                            if ($orderPanel->panel) {
                                $panelsAffected[] = [
                                    'id' => $orderPanel->panel->id,
                                    'title' => $orderPanel->panel->title ?? 'Panel ' . $orderPanel->panel->id,
                                    'current_available' => $orderPanel->panel->remaining_limit ?? 0,
                                    'spaces_to_release' => $domainsCount,
                                    'new_available' => ($orderPanel->panel->remaining_limit ?? 0) + $domainsCount
                                ];
                            }
                        }
                        $splitsCount++;
                    }
                }
            }

            return [
                'success' => true,
                'data' => [
                    'task_id' => $taskId,
                    'task_status' => $task->status,
                    'total_spaces_to_release' => $totalSpacesToRelease,
                    'splits_count' => $splitsCount,
                    'panels_affected' => $panelsAffected
                ]
            ];

        } catch (Exception $e) {
            Log::error("Error getting task completion summary for {$taskId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error getting task summary: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Calculate spaces used by a task without completing it
     *
     * @param int $taskId
     * @return array
     */
    public function calculateTaskSpaces($taskId)
    {
        try {
            $task = DomainRemovalTask::with(['order.orderPanels.orderPanelSplits'])->findOrFail($taskId);
            
            $totalSpaces = 0;
            $splitsData = [];

            if ($task->order && $task->order->orderPanels) {
                foreach ($task->order->orderPanels as $orderPanel) {
                    foreach ($orderPanel->orderPanelSplits as $split) {
                        $domainsCount = 0;
                        if ($split->domains) {
                            if (is_array($split->domains)) {
                                $domainsCount = count($split->domains);
                            } elseif (is_string($split->domains)) {
                                $domains = json_decode($split->domains, true);
                                $domainsCount = is_array($domains) ? count($domains) : 0;
                            }
                        }

                        $totalSpaces += $domainsCount;
                        $splitsData[] = [
                            'split_id' => $split->id,
                            'panel_id' => $orderPanel->panel_id,
                            'domains_count' => $domainsCount
                        ];
                    }
                }
            }

            return [
                'success' => true,
                'data' => [
                    'task_id' => $taskId,
                    'total_spaces' => $totalSpaces,
                    'splits_data' => $splitsData
                ]
            ];

        } catch (Exception $e) {
            Log::error("Error calculating task spaces for {$taskId}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error calculating task spaces: ' . $e->getMessage()
            ];
        }
    }
}
