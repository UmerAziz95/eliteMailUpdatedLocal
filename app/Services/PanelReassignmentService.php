<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Panel;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\UserOrderPanelAssignment;
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
                    $query->with(['userOrderPanelAssignments.contractor']);
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

            // Create new OrderPanel for the destination panel
            $toOrderPanel = OrderPanel::create([
                'panel_id' => $toPanel->id,
                'order_id' => $fromOrderPanel->order_id,
                'contractor_id' => $contractorId,
                'space_assigned' => $spaceToTransfer,
                'inboxes_per_domain' => $fromOrderPanel->inboxes_per_domain,
                'status' => 'unallocated',
                'note' => "Reassigned from Panel ID {$fromOrderPanel->panel_id}"
            ]);

            // Move splits to new order panel
            $movedSplitsCount = 0;
            foreach ($splitsToMove as $split) {
                $split->update([
                    'order_panel_id' => $toOrderPanel->id,
                    'panel_id' => $toPanel->id
                ]);
                $movedSplitsCount++;
            }

            // Update panel capacities
            $fromOrderPanel->panel->increment('remaining_limit', $spaceToTransfer);
            $toPanel->decrement('remaining_limit', $spaceToTransfer);

            // Update source order panel space assignment
            $fromOrderPanel->decrement('space_assigned', $spaceToTransfer);

            // Update user assignments
            $this->updateUserAssignmentsForNewPanel($fromOrderPanelId, $toOrderPanel->id, $splitsToMove, $contractorId);

            // If source order panel has no more splits, delete it
            if ($fromOrderPanel->orderPanelSplits()->count() === 0) {
                $fromOrderPanel->delete();
            }

            // Log the reassignment
            Log::info('Panel split reassignment completed', [
                'from_order_panel_id' => $fromOrderPanelId,
                'to_panel_id' => $toPanelId,
                'new_order_panel_id' => $toOrderPanel->id,
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
                'new_order_panel_id' => $toOrderPanel->id
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
     * Update user order panel assignments when splits are moved to a new panel
     *
     * @param int $fromOrderPanelId
     * @param int $toOrderPanelId
     * @param \Illuminate\Database\Eloquent\Collection $movedSplits
     * @param int|null $contractorId
     * @return void
     */
    private function updateUserAssignmentsForNewPanel($fromOrderPanelId, $toOrderPanelId, $movedSplits, $contractorId = null)
    {
        // Get existing assignments for the source panel
        $fromAssignments = UserOrderPanelAssignment::where('order_panel_id', $fromOrderPanelId)->get();
        
        foreach ($fromAssignments as $assignment) {
            // Check if the moved splits include this assignment's split
            $movedSplitIds = $movedSplits->pluck('id')->toArray();
            
            if (in_array($assignment->order_panel_split_id, $movedSplitIds)) {
                // Create new assignment for the new order panel
                UserOrderPanelAssignment::create([
                    'order_panel_id' => $toOrderPanelId,
                    'order_panel_split_id' => $assignment->order_panel_split_id,
                    'order_id' => $assignment->order_id,
                    'contractor_id' => $assignment->contractor_id
                ]);
                
                // Delete the old assignment
                $assignment->delete();
            }
        }
    }

    /**
     * Update user order panel assignments when splits are moved (legacy method for existing panels)
     *
     * @param int $fromOrderPanelId
     * @param int $toOrderPanelId
     * @param \Illuminate\Database\Eloquent\Collection $movedSplits
     * @param int|null $contractorId
     * @return void
     */
    private function updateUserAssignments($fromOrderPanelId, $toOrderPanelId, $movedSplits, $contractorId = null)
    {
        // Get existing assignments for the source panel
        $fromAssignments = UserOrderPanelAssignment::where('order_panel_id', $fromOrderPanelId)->get();
        
        foreach ($fromAssignments as $assignment) {
            // Check if the moved splits include this assignment's split
            $movedSplitIds = $movedSplits->pluck('id')->toArray();
            
            if (in_array($assignment->order_panel_split_id, $movedSplitIds)) {
                // Update the assignment to point to the new order panel
                $assignment->update(['order_panel_id' => $toOrderPanelId]);
            }
        }

        // If no contractor is assigned to the destination panel but there are moved assignments,
        // update the destination panel's contractor_id
        $toOrderPanel = OrderPanel::find($toOrderPanelId);
        if (!$toOrderPanel->contractor_id && $contractorId) {
            $toOrderPanel->update(['contractor_id' => $contractorId]);
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
            // This would require a reassignment history table to track changes
            // For now, return basic panel information
            
            $orderPanels = OrderPanel::with(['panel', 'orderPanelSplits'])
                ->where('order_id', $orderId)
                ->orderBy('created_at', 'desc')
                ->get();

            return [
                'success' => true,
                'history' => $orderPanels->toArray()
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'history' => []
            ];
        }
    }
}
