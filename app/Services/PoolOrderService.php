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
            if (!in_array($poolOrder->status_manage_by_admin, ['pending', 'in-progress'])) {
                return [
                    'success' => false,
                    'message' => 'Only pending or in-progress orders can be cancelled'
                ];
            }

            $poolOrder->status = 'cancelled';
            $poolOrder->status_manage_by_admin = 'cancelled';
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
     * Generate actions dropdown HTML for a pool order
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
        $showChangeStatus = $options['showChangeStatus'] ?? false;
        $routePrefix = $options['routePrefix'] ?? 'admin'; // Default to admin for backward compatibility
        
        $orderId = $poolOrder->id;
        $viewRoute = route($routePrefix . '.pool-orders.view', $poolOrder->id);
        
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
        
        if ($showChangeStatus) {
            // Only allow status change if order is not cancelled
            if ($poolOrder->status !== 'cancelled') {
                // Use status_manage_by_admin if set, otherwise use status
                $currentStatus = $poolOrder->status_manage_by_admin ?? $poolOrder->status;
                $html .= '
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" 
                           onclick="changePoolOrderStatus(' . $poolOrder->id . ', \'' . $currentStatus . '\')">
                            <i class="fa-solid fa-sync me-1"></i>Change Status
                        </a>
                    </li>';
            }
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

    /**
     * Download domains with prefixes as CSV
     *
     * @param int $poolOrderId
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    
    public function downloadDomainsCsv($poolOrderId)
    {
        $poolOrder = PoolOrder::findOrFail($poolOrderId);
        
        if (!$poolOrder->hasDomains()) {
            throw new \Exception('No domains available for this pool order.');
        }

        $filename = 'pool_order_' . $poolOrder->id . '_domains_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($poolOrder) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers matching Google Workspace format
            fputcsv($file, ['First Name', 'Last Name', 'Email address', 'Password', 'Org Unit Path [Required]']);
            
            // Get pool data for password fallback
            $pool = $poolOrder->pool;
            $poolPassword = '';
            
            if ($pool) {
                $poolPassword = $pool->email_persona_password ?? $pool->persona_password ?? '';
            }
            
            // Get prefix_variants_details from pool table as fallback
            $prefixVariantsDetails = [];
            if ($pool && $pool->prefix_variants_details) {
                $prefixVariantsDetails = is_string($pool->prefix_variants_details) 
                    ? json_decode($pool->prefix_variants_details, true) 
                    : $pool->prefix_variants_details;
            }
            
            // Add domain data
            foreach ($poolOrder->ready_domains_prefix as $domain) {
                $domainName = $domain['domain_name'] ?? 'Unknown';
                
                // Get first name and last name from pool_info in ready_domains_prefix
                $poolFirstName = $domain['pool_info']['first_name'] ?? '';
                $poolLastName = $domain['pool_info']['last_name'] ?? '';
                
                // Get prefix variants from domain
                $prefixVariants = $domain['prefix_variants'] ?? [];
                $prefixVariantsDetailsFromDomain = $domain['prefix_variants_details'] ?? [];
                
                if (!empty($prefixVariants)) {
                    $counter = 0;
                    foreach ($prefixVariants as $key => $prefix) {
                        // Build email from prefix and domain name
                        $email = $prefix . '@' . $domainName;
                        
                        // Try to get first/last name from domain's prefix_variants_details first
                        $variantFirstName = '';
                        $variantLastName = '';
                        
                        if (isset($prefixVariantsDetailsFromDomain[$key])) {
                            $variantFirstName = $prefixVariantsDetailsFromDomain[$key]['first_name'] ?? '';
                            $variantLastName = $prefixVariantsDetailsFromDomain[$key]['last_name'] ?? '';
                        }
                        
                        // Fallback to pool_info if variant details are empty
                        if (empty($variantFirstName)) {
                            $variantFirstName = $poolFirstName;
                        }
                        if (empty($variantLastName)) {
                            $variantLastName = $poolLastName;
                        }
                        
                        // Final fallback to prefix_variants_details from pool table
                        // if ((empty($variantFirstName) || empty($variantLastName)) && !empty($prefixVariantsDetails)) {
                        //     $prefixKey = 'prefix_variant_' . ($counter + 1);
                        //     if (isset($prefixVariantsDetails[$prefixKey])) {
                        //         if (empty($variantFirstName)) {
                        //             $variantFirstName = $prefixVariantsDetails[$prefixKey]['first_name'] ?? '';
                        //         }
                        //         if (empty($variantLastName)) {
                        //             $variantLastName = $prefixVariantsDetails[$prefixKey]['last_name'] ?? '';
                        //         }
                        //     }
                        // }
                        
                        // Use the password from pool, or generate if not available
                        $password = $poolPassword;
                        
                        if (empty($password)) {
                            $password = $this->customEncrypt($poolOrder->id, $counter);
                        }
                        
                        fputcsv($file, [
                            $variantFirstName,
                            $variantLastName,
                            $email,
                            $password,
                            '/'
                        ]);
                        
                        $counter++;
                    }
                } else {
                    // If no prefixes, still add a row with pool_info data
                    $password = $poolPassword ?: $this->customEncrypt($poolOrder->id, 0);
                    
                    fputcsv($file, [
                        $poolFirstName,
                        $poolLastName,
                        '',
                        $password,
                        '/'
                    ]);
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate a custom encrypted password based on order ID and index
     *
     * @param int $orderId
     * @param int $index
     * @return string
     */
    private function customEncrypt($orderId, $index = 0)
    {
        // Create a hash from order ID and index
        $hash = md5($orderId . '-' . $index . '-' . config('app.key'));
        
        // Take first 8 characters and add a special character prefix
        $password = '#' . substr($hash, 0, 7);
        
        // Make it mixed case
        $password = ucfirst($password);
        
        return $password;
    }
}
