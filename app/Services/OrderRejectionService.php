<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\Panel;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderRejectionService
{
    /**
     * Reject an order and handle all related cleanup
     *
     * @param int $orderId
     * @param int $adminId
     * @param string|null $reason
     * @return array
     * @throws Exception
     */
    public function rejectOrder($orderId, $adminId, $reason = null)
    {
        return DB::transaction(function () use ($orderId, $adminId, $reason) {
            // Find the order
            $order = Order::findOrFail($orderId);
            
            // Validate order status
            $this->validateOrderForRejection($order);
            
            // Update order status to rejected
            $order->update([
                'status_manage_by_admin' => 'reject',
                'rejected_by' => $adminId,
                'rejected_at' => now(),
                'reason' => $reason
            ]);
            // reorderInfo update this total_inboxes
            $reorderInfo = $order->reorderInfo()->first();
            if ($reorderInfo && $reorderInfo->initial_total_inboxes !== null) {
                $reorderInfo->update(['total_inboxes' => $reorderInfo->initial_total_inboxes]);
            }

            // // Update order panels status
            $updatedPanels = $this->updateOrderPanelsStatus($orderId);
            
            // Rollback splits and restore panel capacity
            $rollbackInfo = $this->rollbackOrderSplits($order);
            
            Log::info("Order #{$orderId} rejected successfully", [
                'order_id' => $orderId,
                'rejected_by' => $adminId,
                'rejection_reason' => $reason,
                'updated_panels' => $updatedPanels,
                'rollback_info' => $rollbackInfo
            ]);
            
            return [
                'success' => true,
                'message' => 'Order rejected successfully!',
                'order_id' => $orderId,
                'rejection_reason' => $reason,
                'updated_panels' => $updatedPanels,
                'rollback_info' => $rollbackInfo
            ];
        });
    }
    
    /**
     * Validate if order can be rejected
     *
     * @param Order $order
     * @throws Exception
     */
    private function validateOrderForRejection($order)
    {
        if ($order->status_manage_by_admin === 'reject') {
            throw new Exception('Order is already rejected.');
        }
        
        if ($order->status_manage_by_admin === 'completed') {
            throw new Exception('Cannot reject a completed order.');
        }
        
        // Additional validation can be added here
        // For example: check if order is within rejection timeframe
        // if ($order->created_at->diffInHours(now()) > 24) {
        //     throw new Exception('Cannot reject order after 24 hours.');
        // }
    }
    
    /**
     * Update order panels status to rejected
     *
     * @param int $orderId
     * @return int Number of updated panels
     */
    private function updateOrderPanelsStatus($orderId)
    {
        $orderPanels = OrderPanel::where('order_id', $orderId)
            ->whereIn('status', ['unallocated', 'allocated'])
            ->get();
        
        $updatedCount = 0;
        foreach ($orderPanels as $panel) {
            $panel->update(['status' => 'rejected']);
            $updatedCount++;
        }
        
        return $updatedCount;
    }
    
    /**
     * Rollback all splits for an order - restore panel capacity and delete records
     *
     * @param Order $order
     * @return array Rollback information
     * @throws Exception
     */
    private function rollbackOrderSplits($order)
    {
        try {
            // Get all order panels for this order
            $orderPanels = OrderPanel::where('order_id', $order->id)->get();
            
            if ($orderPanels->isEmpty()) {
                Log::info("No order panels found to rollback for order #{$order->id}");
                return [
                    'splits_rolled_back' => 0,
                    'capacity_restored' => 0,
                    'panels_deleted' => 0
                ];
            }
            
            $rollbackCount = 0;
            $totalCapacityRestored = 0;
            $panelsDeleted = 0;
            
            foreach ($orderPanels as $orderPanel) {
                // Restore panel capacity
                $panel = Panel::find($orderPanel->panel_id);
                if ($panel && $orderPanel->space_assigned > 0) {
                    $panel->increment('remaining_limit', $orderPanel->space_assigned);
                    $totalCapacityRestored += $orderPanel->space_assigned;
                    
                    Log::info("Restored panel capacity", [
                        'panel_id' => $panel->id,
                        'space_restored' => $orderPanel->space_assigned,
                        'panel_remaining_limit' => $panel->remaining_limit
                    ]);
                }
                
                // Delete order panel splits first (foreign key constraint)
                $splitsDeleted = OrderPanelSplit::where('order_panel_id', $orderPanel->id)->count();
                OrderPanelSplit::where('order_panel_id', $orderPanel->id)->delete();
                
                // Delete order panel
                $orderPanel->delete();
                
                $rollbackCount += $splitsDeleted;
                $panelsDeleted++;
            }
            
            $rollbackInfo = [
                'splits_rolled_back' => $rollbackCount,
                'capacity_restored' => $totalCapacityRestored,
                'panels_deleted' => $panelsDeleted
            ];
            
            Log::info("Successfully rolled back all splits for order #{$order->id}", array_merge([
                'order_id' => $order->id,
            ], $rollbackInfo));
            
            return $rollbackInfo;
            
        } catch (Exception $e) {
            Log::error("Failed to rollback splits for order #{$order->id}", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception("Failed to rollback order splits: " . $e->getMessage());
        }
    }
    
    /**
     * Get rejection statistics for an order
     *
     * @param int $orderId
     * @return array
     */
    public function getRejectionStats($orderId)
    {
        $order = Order::with(['rejectedBy'])->find($orderId);
        
        if (!$order || $order->status_manage_by_admin !== 'rejected') {
            return [
                'is_rejected' => false,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null
            ];
        }
        
        return [
            'is_rejected' => true,
            'rejected_at' => $order->rejected_at,
            'rejected_by' => $order->rejectedBy ? $order->rejectedBy->name : 'Unknown',
            'rejected_by_id' => $order->rejected_by,
            'rejection_reason' => $order->reason ?? 'No reason provided'
        ];
    }
    
    /**
     * Check if an order can be rejected
     *
     * @param int $orderId
     * @return array
     */
    public function canRejectOrder($orderId)
    {
        try {
            $order = Order::findOrFail($orderId);
            $this->validateOrderForRejection($order);
            
            return [
                'can_reject' => true,
                'message' => 'Order can be rejected'
            ];
        } catch (Exception $e) {
            return [
                'can_reject' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
