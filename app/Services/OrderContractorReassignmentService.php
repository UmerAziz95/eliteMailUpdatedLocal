<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\OrderEmail;
use App\Models\UserOrderPanelAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\SlackNotificationService;

class OrderContractorReassignmentService
{
    /**
     * Reassign contractor for an order and all related tables
     *
     * @param int $orderId
     * @param int $newContractorId
     * @param bool $removeFromHelpers
     * @return array
     */
    public function reassignContractor($orderId, $reassign_note=null, $newContractorId, $removeFromHelpers = false)
    {
        try {
           Log::channel('slack_notifications')->info("test 2 service ============================");

            DB::beginTransaction();

            $order = Order::findOrFail($orderId);
            $oldContractorId = $order->assigned_to;
            
            // Remove from helpers_ids if requested and contractor is in helpers
            if ($removeFromHelpers) {
                $helpers_ids = $order->helpers_ids ?? [];
                if (in_array($newContractorId, $helpers_ids)) {
                    $helpers_ids = array_values(array_filter($helpers_ids, function($id) use ($newContractorId) {
                        return $id != $newContractorId;
                    }));
                    $order->helpers_ids = $helpers_ids;
                }
            }
            
            // Check if helpers_ids are null or empty, set is_shared to 0
            if (empty($order->helpers_ids)) {
                $order->is_shared = 0;
                // shared_note
                $order->shared_note = $reassign_note;
            }
            
            $order->assigned_to = $newContractorId;
            $order->reassignment_note = $reassign_note;
            $order->save();
            //SlackNotificationService::sendOrderAssignmentNotification($order, $newContractorId);
        
            OrderPanel::where('order_id', $orderId)
                ->update(['contractor_id' => $newContractorId]);

            OrderEmail::where('order_id', $orderId)
                ->update(['contractor_id' => $newContractorId]);

            UserOrderPanelAssignment::where('order_id', $orderId)
                ->update(['contractor_id' => $newContractorId]);

            Log::channel('slack_notifications')->info("after UserOrderPanelAssignment update");
            
            DB::commit();

            return [
                'success' => true,
                'message' => 'Contractor reassigned successfully',
                'old_contractor_id' => $oldContractorId,
                'new_contractor_id' => $newContractorId
            ];
        } catch (Exception $e) {
        Log::channel('slack_notifications')->info("test 4 service============================");

            DB::rollBack();
            Log::error('Order contractor reassignment failed', [
                'order_id' => $orderId,
                'new_contractor_id' => $newContractorId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
