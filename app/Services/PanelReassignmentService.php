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
// this service not shows correct pannel because
class PanelReassignmentService
{
    /**
     * Get available panels for reassignment based on order_id and panel_id
     * This will show panels that belong to the same order but exclude the current panel
     *
     * @param int $orderId
     * @param int $currentPanelId
     * @return array
     */
    public function getAvailablePanelsForReassignment($orderId, $currentPanelId)
    {
        try {
            // Get all order panels for this order except the current one
            $availablePanels = OrderPanel::with(['panel', 'orderPanelSplits', 'userOrderPanelAssignments.contractor'])
                ->where('order_id', $orderId)
                ->where('panel_id', '!=', $currentPanelId)
                ->get();

            $panels = [];
            
            foreach ($availablePanels as $orderPanel) {
                // Calculate total domains and inboxes for this panel
                $totalDomains = $orderPanel->orderPanelSplits->sum(function ($split) {
                    return is_array($split->domains) ? count($split->domains) : 0;
                });
                
                $totalInboxes = $orderPanel->orderPanelSplits->sum(function ($split) {
                    return $split->inboxes_per_domain * (is_array($split->domains) ? count($split->domains) : 0);
                });

                // Get assigned contractor info
                $assignment = $orderPanel->userOrderPanelAssignments->first();
                $contractorInfo = null;
                
                if ($assignment && $assignment->contractor) {
                    $contractorInfo = [
                        'id' => $assignment->contractor->id,
                        'name' => $assignment->contractor->name,
                        'email' => $assignment->contractor->email
                    ];
                }

                $panels[] = [
                    'order_panel_id' => $orderPanel->id,
                    'panel_id' => $orderPanel->panel_id,
                    'panel_title' => $orderPanel->panel->title ?? 'N/A',
                    'panel_limit' => $orderPanel->panel->limit ?? 0,
                    'panel_remaining_limit' => $orderPanel->panel->remaining_limit ?? 0,
                    'space_assigned' => $orderPanel->space_assigned,
                    'status' => $orderPanel->status,
                    'total_domains' => $totalDomains,
                    'total_inboxes' => $totalInboxes,
                    'inboxes_per_domain' => $orderPanel->inboxes_per_domain,
                    'contractor' => $contractorInfo,
                    'created_at' => $orderPanel->created_at->format('Y-m-d H:i:s'),
                    'is_reassignable' => $this->isPanelReassignable($orderPanel)
                ];
            }

            return [
                'success' => true,
                'panels' => $panels,
                'total_count' => count($panels)
            ];
            
        } catch (Exception $e) {
            Log::error('Error getting available panels for reassignment', [
                'order_id' => $orderId,
                'current_panel_id' => $currentPanelId,
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
     * Reassign panel split from one panel to another
     *
     * @param int $fromOrderPanelId
     * @param int $toOrderPanelId
     * @param int $splitId (optional - if not provided, all splits will be moved)
     * @param int|null $contractorId (optional - the contractor performing the reassignment)
     * @return array
     */
    public function reassignPanelSplit($fromOrderPanelId, $toOrderPanelId, $splitId = null, $contractorId = null)
    {
        try {
            DB::beginTransaction();

            // Validate source order panel
            $fromOrderPanel = OrderPanel::with(['panel', 'orderPanelSplits', 'userOrderPanelAssignments'])
                ->findOrFail($fromOrderPanelId);

            // Validate destination order panel
            $toOrderPanel = OrderPanel::with(['panel', 'orderPanelSplits'])
                ->findOrFail($toOrderPanelId);

            // Ensure both panels belong to the same order
            if ($fromOrderPanel->order_id !== $toOrderPanel->order_id) {
                throw new Exception('Cannot reassign between different orders');
            }

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
            if ($toOrderPanel->panel->remaining_limit < $spaceToTransfer) {
                throw new Exception('Destination panel does not have enough capacity');
            }

            // Move splits
            $movedSplitsCount = 0;
            foreach ($splitsToMove as $split) {
                $split->update([
                    'order_panel_id' => $toOrderPanelId,
                    'panel_id' => $toOrderPanel->panel_id
                ]);
                $movedSplitsCount++;
            }

            // Update panel capacities
            $fromOrderPanel->panel->increment('remaining_limit', $spaceToTransfer);
            $toOrderPanel->panel->decrement('remaining_limit', $spaceToTransfer);

            // Update order panel space assignments
            $fromOrderPanel->decrement('space_assigned', $spaceToTransfer);
            $toOrderPanel->increment('space_assigned', $spaceToTransfer);

            // Update user assignments if needed
            $this->updateUserAssignments($fromOrderPanelId, $toOrderPanelId, $splitsToMove, $contractorId);

            // Log the reassignment
            Log::info('Panel split reassignment completed', [
                'from_order_panel_id' => $fromOrderPanelId,
                'to_order_panel_id' => $toOrderPanelId,
                'splits_moved' => $movedSplitsCount,
                'space_transferred' => $spaceToTransfer,
                'performed_by' => $contractorId
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => "Successfully reassigned {$movedSplitsCount} split(s) with {$spaceToTransfer} inbox capacity",
                'splits_moved' => $movedSplitsCount,
                'space_transferred' => $spaceToTransfer
            ];

        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Panel split reassignment failed', [
                'from_order_panel_id' => $fromOrderPanelId,
                'to_order_panel_id' => $toOrderPanelId,
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
     * Update user order panel assignments when splits are moved
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
