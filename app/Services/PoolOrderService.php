<?php

namespace App\Services;

use App\Models\PoolOrder;
use Illuminate\Support\Facades\Log;

class PoolOrderService
{
    /**
     * Get all pool orders with relationships
     *
     * @param string $orderBy
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllPoolOrders($orderBy = 'created_at', $direction = 'desc')
    {
        return PoolOrder::with(['user', 'assignedTo'])
            ->orderBy($orderBy, $direction)
            ->get();
    }

    /**
     * Get assigned pool orders for a specific user (My Pool Orders)
     *
     * @param int $userId
     * @param string $orderBy
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssignedPoolOrders($userId, $orderBy = 'created_at', $direction = 'desc')
    {
        return PoolOrder::with(['user', 'assignedTo'])
            ->where('assigned_to', $userId)
            ->orderBy($orderBy, $direction)
            ->get();
    }

    /**
     * Get unassigned pool orders (in-queue)
     *
     * @param array $statuses
     * @param string $orderBy
     * @param string $direction
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUnassignedPoolOrders($statuses = ['pending', 'in_progress'], $orderBy = 'created_at', $direction = 'asc')
    {
        return PoolOrder::with(['user', 'assignedTo'])
            // ->whereIn('status', $statuses)
            ->whereNull('assigned_to')
            ->orderBy($orderBy, $direction)
            ->get();
    }

    /**
     * Cancel a pool order
     *
     * @param int $orderId
     * @return array
     */
    public function cancelPoolOrder($orderId)
    {
        try {
            $poolOrder = PoolOrder::findOrFail($orderId);
            
            // Only allow cancellation of pending or in_progress orders
            if (!in_array($poolOrder->status, ['pending', 'in_progress'])) {
                return [
                    'success' => false,
                    'message' => 'Only pending or in-progress orders can be cancelled'
                ];
            }

            $poolOrder->status = 'cancelled';
            $poolOrder->cancelled_at = now();
            $poolOrder->save();

            return [
                'success' => true,
                'message' => 'Pool order cancelled successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error cancelling pool order: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error cancelling pool order: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Assign pool order to a user
     *
     * @param int $orderId
     * @param int $userId
     * @return array
     */
    public function assignPoolOrder($orderId, $userId)
    {
        try {
            $poolOrder = PoolOrder::findOrFail($orderId);
            
            $poolOrder->assigned_to = $userId;
            $poolOrder->assigned_at = now();
            $poolOrder->save();

            return [
                'success' => true,
                'message' => 'Pool order assigned successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error assigning pool order: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error assigning pool order: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Unassign pool order
     *
     * @param int $orderId
     * @return array
     */
    public function unassignPoolOrder($orderId)
    {
        try {
            $poolOrder = PoolOrder::findOrFail($orderId);
            
            $poolOrder->assigned_to = null;
            $poolOrder->assigned_at = null;
            $poolOrder->save();

            return [
                'success' => true,
                'message' => 'Pool order unassigned successfully'
            ];

        } catch (\Exception $e) {
            Log::error('Error unassigning pool order: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error unassigning pool order: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get status badge HTML for DataTables
     *
     * @param string $status
     * @return string
     */
    public function getStatusBadge($status)
    {
        $colorMap = [
            'completed' => 'success',
            'in_progress' => 'warning',
            'pending' => 'info',
            'cancelled' => 'danger',
        ];
        
        $color = $colorMap[$status] ?? 'secondary';
        return '<span class="badge bg-' . $color . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
    }

    /**
     * Get assigned to badge HTML for DataTables
     *
     * @param \App\Models\PoolOrder $poolOrder
     * @return string
     */
    public function getAssignedToBadge($poolOrder)
    {
        if ($poolOrder->assignedTo) {
            return '<span class="badge bg-primary">' . $poolOrder->assignedTo->name . '</span>';
        }
        return '<span class="badge bg-secondary">Unassigned</span>';
    }

    /**
     * Format assigned at date for DataTables
     *
     * @param \App\Models\PoolOrder $poolOrder
     * @return string
     */
    public function formatAssignedAt($poolOrder)
    {
        return $poolOrder->assigned_at ? $poolOrder->assigned_at->format('Y-m-d H:i:s') : 'N/A';
    }

    /**
     * Get actions dropdown HTML for DataTables
     *
     * @param \App\Models\PoolOrder $poolOrder
     * @param array $options
     * @return string
     */
    public function getActionsDropdown($poolOrder, $options = [])
    {
        $showView = $options['showView'] ?? true;
        $showViewDomains = $options['showViewDomains'] ?? true;
        $showCancel = $options['showCancel'] ?? true;
        $showAssignToMe = $options['showAssignToMe'] ?? false;
        
        $orderId = $poolOrder->id;
        $viewRoute = route('admin.pool-orders.view', $poolOrder->id);
        
        $html = '
            <div class="dropdown">
                <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>
                <ul class="dropdown-menu">';
        
        if ($showView) {
            $html .= '
                    <li>
                        <a class="dropdown-item" href="' . $viewRoute . '">
                            <i class="fa-solid fa-eye me-1"></i>View Details
                        </a>
                    </li>';
        }
        
        if ($showAssignToMe && !$poolOrder->assigned_to) {
            $html .= '
                    <li>
                        <a class="dropdown-item text-success" href="javascript:void(0)" 
                           onclick="assignToMe(' . $poolOrder->id . ')">
                            <i class="fa-solid fa-user-check me-1"></i>Assign to Me
                        </a>
                    </li>';
        }
        
        if ($showViewDomains) {
            $html .= '
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" 
                           onclick="viewPoolOrderDomains(\'' . $orderId . '\')">
                            <i class="fa-solid fa-globe me-1"></i>View Domains
                        </a>
                    </li>';
        }
        
        if ($showCancel && in_array($poolOrder->status, ['pending', 'in_progress'])) {
            $html .= '
                    <li>
                        <a class="dropdown-item text-danger" href="javascript:void(0)" 
                           onclick="cancelPoolOrder(' . $poolOrder->id . ')">
                            <i class="fa-solid fa-times me-1"></i>Cancel Order
                        </a>
                    </li>';
        }
        
        $html .= '
                </ul>
            </div>';
        
        return $html;
    }
}
