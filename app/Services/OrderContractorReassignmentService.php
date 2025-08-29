<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\OrderEmail;
use App\Models\UserOrderPanelAssignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class OrderContractorReassignmentService
{
    /**
     * Reassign contractor for an order and all related tables
     *
     * @param int $orderId
     * @param int $newContractorId
     * @return array
     */
    public function reassignContractor($orderId, $newContractorId)
    {
        try {
            DB::beginTransaction();

            $order = Order::findOrFail($orderId);
            $oldContractorId = $order->assigned_to;
            $order->assigned_to = $newContractorId;
            $order->save();

            // Update all order panels
            OrderPanel::where('order_id', $orderId)
                ->update(['contractor_id' => $newContractorId]);

            // Update all order emails
            OrderEmail::where('order_id', $orderId)
                ->update(['contractor_id' => $newContractorId]);

            // Update all user order panel assignments
            UserOrderPanelAssignment::where('order_id', $orderId)
                ->update(['contractor_id' => $newContractorId]);

            DB::commit();
            return [
                'success' => true,
                'message' => 'Contractor reassigned successfully',
                'old_contractor_id' => $oldContractorId,
                'new_contractor_id' => $newContractorId
            ];
        } catch (Exception $e) {
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
