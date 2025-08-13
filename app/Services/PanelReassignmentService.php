<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Panel;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\UserOrderPanelAssignment;
use App\Models\PanelReassignmentHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
 
class PanelReassignmentService
{
    /**
     * Get available panels for reassignment based on order_id and current order_panel_id
     * This will show panels that are NOT used in this order and have sufficient capacity
     *
     * @param int $orderId
     * @param int $currentOrderPanelId
     * @return array
     */
    
    public function getAvailablePanelsForReassignment($orderId, $currentOrderPanelId)
    {
        try {
            // Get the current order panel to calculate space needed
            $currentOrderPanel = OrderPanel::with(['orderPanelSplits'])
                ->findOrFail($currentOrderPanelId);

            // Calculate space needed for reassignment (all splits from current order panel)
            $spaceNeeded = 0;
            foreach ($currentOrderPanel->orderPanelSplits as $split) {
                $domainCount = is_array($split->domains) ? count($split->domains) : 0;
                $spaceNeeded += $split->inboxes_per_domain * $domainCount;
            }

            // Get panel IDs that are already used in this order
            $usedPanelIds = OrderPanel::where('order_id', $orderId)
                ->pluck('panel_id')
                ->toArray();

            // Get available panels that are NOT used in this order and have sufficient capacity
            $availablePanels = Panel::with([
                'order_panels' => function($query) {
                    // $query->with(['userOrderPanelAssignments.contractor']);
                }
            ])
                ->where('is_active', true)
                ->whereNotIn('id', $usedPanelIds)
                ->where('remaining_limit', '>=', $spaceNeeded)
                ->get();

            $panels = [];
            
            foreach ($availablePanels as $panel) {
                // Get the latest assignment info if any
                $latestOrderPanel = $panel->order_panels->sortByDesc('created_at')->first();
                $contractorInfo = null;
                
                if ($latestOrderPanel) {
                    $assignment = $latestOrderPanel->userOrderPanelAssignments->first();
                    if ($assignment && $assignment->contractor) {
                        $contractorInfo = [
                            'id' => $assignment->contractor->id,
                            'name' => $assignment->contractor->name,
                            'email' => $assignment->contractor->email
                        ];
                    }
                }

                $panels[] = [
                    'panel_id' => $panel->id,
                    'panel_title' => $panel->title ?? 'N/A',
                    'panel_limit' => $panel->limit ?? 0,
                    'panel_remaining_limit' => $panel->remaining_limit ?? 0,
                    'status' => 'available', // Since it's not used in current order
                    'space_needed' => $spaceNeeded,
                    'contractor' => $contractorInfo,
                    'created_at' => $panel->created_at->format('Y-m-d H:i:s'),
                    'is_reassignable' => true, // All returned panels are reassignable
                    'total_orders' => $panel->order_panels->count()
                ];
            }

            return [
                'success' => true,
                'panels' => $panels,
                'total_count' => count($panels),
                'space_needed' => $spaceNeeded,
                'current_order_panel_id' => $currentOrderPanelId
            ];
            
        } catch (Exception $e) {
            Log::error('Error getting available panels for reassignment', [
                'order_id' => $orderId,
                'current_order_panel_id' => $currentOrderPanelId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => 'Failed to get available panels: ' . $e->getMessage(),
                'panels' => [],
                'total_count' => 0
            ];
        }
    }

    /**
     * Reassign panel split from one order panel to a new panel
     *
     * @param int $fromOrderPanelId
     * @param int $toPanelId (Panel ID, not OrderPanel ID)
     * @param int $splitId (optional - if not provided, all splits will be moved)
     * @param int|null $contractorId (optional - the contractor performing the reassignment)
     * @return array
     */
    public function reassignPanelSplit($fromOrderPanelId, $toPanelId, $splitId = null, $contractorId = null)
    {
        try {
            DB::beginTransaction();

            // Validate source order panel
            $fromOrderPanel = OrderPanel::with(['panel', 'orderPanelSplits', 'userOrderPanelAssignments'])
                ->findOrFail($fromOrderPanelId);

            // Validate destination panel
            $toPanel = Panel::findOrFail($toPanelId);

            // Get splits to move
            $splitsQuery = OrderPanelSplit::where('order_panel_id', $fromOrderPanelId);
            if ($splitId) {
                $splitsQuery->where('id', $splitId);
            }
            $splitsToMove = $splitsQuery->get();

            if ($splitsToMove->isEmpty()) {
                throw new Exception('No splits found to reassign');
            }

            // Calculate space to transfer
            $spaceToTransfer = 0;
            foreach ($splitsToMove as $split) {
                $domainCount = is_array($split->domains) ? count($split->domains) : 0;
                $spaceToTransfer += $split->inboxes_per_domain * $domainCount;
            }

            // Check if destination panel has enough capacity
            if ($toPanel->remaining_limit < $spaceToTransfer) {
                throw new Exception('Destination panel does not have enough capacity');
            }

            // Store original panel for capacity updates
            $originalPanel = $fromOrderPanel->panel;
            $originalPanelId = $fromOrderPanel->panel_id;

            // Update existing OrderPanel to point to the new panel instead of creating new record
            $fromOrderPanel->update([
                'panel_id' => $toPanel->id,
                'contractor_id' => $contractorId ?? $fromOrderPanel->contractor_id,
                'space_assigned' => $spaceToTransfer,
                'note' => ($fromOrderPanel->note ? $fromOrderPanel->note . ' | ' : '') . "Reassigned from Panel ID {$originalPanelId} to Panel ID {$toPanel->id} on " . now()->format('Y-m-d H:i:s')
            ]);

            // Move splits to updated order panel (update panel_id reference)
            $movedSplitsCount = 0;
            $movedSplitIds = [];
            foreach ($splitsToMove as $split) {
                $split->update([
                    'panel_id' => $toPanel->id
                ]);
                $movedSplitsCount++;
                $movedSplitIds[] = $split->id;
            }

            // Create history records for both removal and addition
            $this->createReassignmentHistory([
                'order_id' => $fromOrderPanel->order_id,
                'order_panel_id' => $fromOrderPanel->id,
                'from_panel_id' => $originalPanelId,
                'to_panel_id' => $toPanel->id,
                'reassigned_by' => $contractorId ?? auth()->id(),
                'space_transferred' => $spaceToTransfer,
                'splits_count' => $movedSplitsCount,
                'split_ids' => $movedSplitIds,
                'reason' => 'Panel reassignment via service'
            ]);

            // Update panel capacities
            $originalPanel->increment('remaining_limit', $spaceToTransfer);
            $toPanel->decrement('remaining_limit', $spaceToTransfer);

            // No need to update source order panel space assignment since we're updating the same record
            // No need to update status since we're keeping the same OrderPanel record

            // Log the reassignment
            Log::info('Panel split reassignment completed', [
                'order_panel_id' => $fromOrderPanel->id,
                'from_panel_id' => $originalPanelId,
                'to_panel_id' => $toPanelId,
                'splits_moved' => $movedSplitsCount,
                'space_transferred' => $spaceToTransfer,
                'performed_by' => $contractorId
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Successfully reassigned {$movedSplitsCount} split(s) with {$spaceToTransfer} inbox capacity to panel {$toPanel->title}",
                'splits_moved' => $movedSplitsCount,
                'space_transferred' => $spaceToTransfer,
                'updated_order_panel_id' => $fromOrderPanel->id
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Panel split reassignment failed', [
                'from_order_panel_id' => $fromOrderPanelId,
                'to_panel_id' => $toPanelId,
                'split_id' => $splitId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Create history records for panel reassignment
     * Creates two records: one for removal from old panel and one for addition to new panel
     *
     * @param array $data
     * @return void
     */
    private function createReassignmentHistory($data)
    {
        $baseData = [
            'order_id' => $data['order_id'],
            'order_panel_id' => $data['order_panel_id'],
            'reassigned_by' => $data['reassigned_by'],
            'reassignment_date' => now(),
            'space_transferred' => $data['space_transferred'],
            'splits_count' => $data['splits_count'],
            'split_ids' => $data['split_ids'],
            'reason' => $data['reason'] ?? null,
            'notes' => $data['notes'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'status' => 'pending' // Mark as pending
        ];

        // Handle removal record (from old panel)
        if (!empty($data['from_panel_id'])) {
            $existingRemovalRecord = PanelReassignmentHistory::where('status', 'pending')
                ->where('order_id', $data['order_id'])
                ->where('order_panel_id', $data['order_panel_id'])
                ->where('action_type', 'removed')
                ->first();

            if ($existingRemovalRecord) {
                $existingRemovalRecord->update(array_merge($baseData, [
                    'from_panel_id' => $data['from_panel_id'],
                    'to_panel_id' => $data['to_panel_id'] ?? null
                ]));
            } else {
                PanelReassignmentHistory::create(array_merge($baseData, [
                    'from_panel_id' => $data['from_panel_id'],
                    'to_panel_id' => $data['to_panel_id'] ?? null,
                    'action_type' => 'removed'
                ]));
            }
        }

        // Handle addition record (to new panel)
        if (!empty($data['to_panel_id'])) {
            $existingAdditionRecord = PanelReassignmentHistory::where('status', 'pending')
                ->where('order_id', $data['order_id'])
                ->where('order_panel_id', $data['order_panel_id'])
                ->where('action_type', 'added')
                ->first();

            if ($existingAdditionRecord) {
                $existingAdditionRecord->update(array_merge($baseData, [
                    'from_panel_id' => $data['from_panel_id'] ?? null,
                    'to_panel_id' => $data['to_panel_id'],
                    'action_type' => 'added'
                ]));
            } else {
                PanelReassignmentHistory::create(array_merge($baseData, [
                    'from_panel_id' => $data['from_panel_id'] ?? null,
                    'to_panel_id' => $data['to_panel_id'],
                    'action_type' => 'added'
                ]));
            }
        }
    }

    /**
     * Get reassignment history for an order
     *
     * @param int $orderId
     * @return array
     */
    public function getReassignmentHistory($orderId)
    {
        try {
            $history = PanelReassignmentHistory::with([
                'orderPanel',
                'fromPanel',
                'toPanel',
                'reassignedBy',
                'assignedTo'
            ])
                ->forOrder($orderId)
                ->orderBy('reassignment_date', 'desc')
                ->get();

            $formattedHistory = $history->map(function ($record) {
                return [
                    'id' => $record->id,
                    'order_panel_id' => $record->order_panel_id,
                    'action_type' => $record->action_type,
                    'from_panel' => $record->fromPanel ? [
                        'id' => $record->fromPanel->id,
                        'title' => $record->fromPanel->title
                    ] : null,
                    'to_panel' => $record->toPanel ? [
                        'id' => $record->toPanel->id,
                        'title' => $record->toPanel->title
                    ] : null,
                    'reassigned_by' => [
                        'id' => $record->reassignedBy->id,
                        'name' => $record->reassignedBy->name,
                        'email' => $record->reassignedBy->email
                    ],
                    'assigned_to' => $record->assignedTo ? [
                        'id' => $record->assignedTo->id,
                        'name' => $record->assignedTo->name,
                        'email' => $record->assignedTo->email
                    ] : null,
                    'status' => $record->status,
                    'space_transferred' => $record->space_transferred,
                    'splits_count' => $record->splits_count,
                    'reason' => $record->reason,
                    'notes' => $record->notes,
                    'reassignment_date' => $record->reassignment_date->format('Y-m-d H:i:s'),
                    'task_started_at' => $record->task_started_at?->format('Y-m-d H:i:s'),
                    'task_completed_at' => $record->task_completed_at?->format('Y-m-d H:i:s'),
                    'completion_notes' => $record->completion_notes
                ];
            });

            return [
                'success' => true,
                'history' => $formattedHistory,
                'total_count' => $history->count()
            ];
            
        } catch (Exception $e) {
            Log::error('Error getting reassignment history', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'history' => [],
                'total_count' => 0
            ];
        }
    }

    /**
     * Get pending reassignment tasks
     *
     * @param int|null $assignedTo Filter by assigned user
     * @return array
     */
    public function getPendingTasks($assignedTo = null)
    {
        try {
            $query = PanelReassignmentHistory::with([
                'order',
                'orderPanel',
                'fromPanel',
                'toPanel',
                'reassignedBy',
                'assignedTo'
            ])->pending();

            if ($assignedTo) {
                $query->where('assigned_to', $assignedTo);
            }

            $tasks = $query->orderBy('reassignment_date', 'asc')->get();

            return [
                'success' => true,
                'tasks' => $tasks,
                'total_count' => $tasks->count()
            ];
            
        } catch (Exception $e) {
            Log::error('Error getting pending reassignment tasks', [
                'assigned_to' => $assignedTo,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'tasks' => [],
                'total_count' => 0
            ];
        }
    }

    /**
     * Get reassignment statistics for an order
     *
     * @param int $orderId
     * @return array
     */
    public function getReassignmentStats($orderId)
    {
        try {
            $stats = [
                'total_reassignments' => PanelReassignmentHistory::forOrder($orderId)->count() / 2, // Divide by 2 since each reassignment creates 2 records
                'pending_tasks' => PanelReassignmentHistory::forOrder($orderId)->pending()->count(),
                'completed_tasks' => PanelReassignmentHistory::forOrder($orderId)->completed()->count(),
                'in_progress_tasks' => PanelReassignmentHistory::forOrder($orderId)->inProgress()->count(),
                'total_space_transferred' => PanelReassignmentHistory::forOrder($orderId)->byActionType('removed')->sum('space_transferred'),
                'total_splits_moved' => PanelReassignmentHistory::forOrder($orderId)->byActionType('removed')->sum('splits_count')
            ];

            return [
                'success' => true,
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            Log::error('Error getting reassignment stats', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stats' => []
            ];
        }
    }

    /**
     * Check if a panel is reassignable
     * 
     * @param OrderPanel $orderPanel
     * @return bool
     */
    private function isPanelReassignable($orderPanel)
    {
        // Panel is reassignable if:
        // 1. It's not completed
        // 2. It has splits that can be moved
        // 3. It's not in a locked state
        
        if (in_array($orderPanel->status, ['completed', 'cancelled'])) {
            return false;
        }

        return $orderPanel->orderPanelSplits->isNotEmpty();
    }

    /**
     * Start a panel reassignment task by assigning it to a contractor
     * 
     * @param PanelReassignmentHistory $task
     * @param int $contractorId
     * @return bool
     */
    public function startPanelReassignmentTask($task, $contractorId)
    {
        try {
            DB::beginTransaction();

            // Update the task status and assign to contractor
            $task->update([
                'status' => 'in-progress',
                'assigned_to' => $contractorId,
                'updated_at' => now()
            ]);

            // If this is a dual record system, update the paired record as well
            if ($task->action_type === 'removed') {
                // Find the corresponding 'added' record
                $pairedTask = PanelReassignmentHistory::where('order_id', $task->order_id)
                    ->where('from_panel_id', $task->from_panel_id)
                    ->where('to_panel_id', $task->to_panel_id)
                    ->where('action_type', 'added')
                    ->where('reassignment_date', $task->reassignment_date)
                    ->first();

                if ($pairedTask) {
                    $pairedTask->update([
                        'status' => 'in-progress',
                        'assigned_to' => $contractorId,
                        'updated_at' => now()
                    ]);
                }
            } elseif ($task->action_type === 'added') {
                // Find the corresponding 'removed' record
                $pairedTask = PanelReassignmentHistory::where('order_id', $task->order_id)
                    ->where('from_panel_id', $task->from_panel_id)
                    ->where('to_panel_id', $task->to_panel_id)
                    ->where('action_type', 'removed')
                    ->where('reassignment_date', $task->reassignment_date)
                    ->first();

                if ($pairedTask) {
                    $pairedTask->update([
                        'status' => 'in-progress',
                        'assigned_to' => $contractorId,
                        'updated_at' => now()
                    ]);
                }
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollback();
            Log::error("Error starting panel reassignment task: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update panel reassignment task status
     * 
     * @param PanelReassignmentHistory $task
     * @param string $status
     * @param array $additionalData
     * @return bool
     */
    public function updatePanelReassignmentTaskStatus($task, $status, $additionalData = [])
    {
        try {
            DB::beginTransaction();

            $updateData = [
                'status' => $status,
                'updated_at' => now()
            ];

            // Add any additional data
            $updateData = array_merge($updateData, $additionalData);

            // Update the main task
            $task->update($updateData);

            // Update the paired record as well
            if ($task->action_type === 'removed') {
                $pairedTask = PanelReassignmentHistory::where('order_id', $task->order_id)
                    ->where('from_panel_id', $task->from_panel_id)
                    ->where('to_panel_id', $task->to_panel_id)
                    ->where('action_type', 'added')
                    ->where('reassignment_date', $task->reassignment_date)
                    ->first();

                if ($pairedTask) {
                    $pairedTask->update($updateData);
                }
            } elseif ($task->action_type === 'added') {
                $pairedTask = PanelReassignmentHistory::where('order_id', $task->order_id)
                    ->where('from_panel_id', $task->from_panel_id)
                    ->where('to_panel_id', $task->to_panel_id)
                    ->where('action_type', 'removed')
                    ->where('reassignment_date', $task->reassignment_date)
                    ->first();

                if ($pairedTask) {
                    $pairedTask->update($updateData);
                }
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollback();
            Log::error("Error updating panel reassignment task status: " . $e->getMessage());
            return false;
        }
    }
}