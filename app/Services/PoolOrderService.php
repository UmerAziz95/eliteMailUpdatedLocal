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
        $showViewDomains = $options['showViewDomains'] ?? false;
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
        
        // Add Edit option for editable status orders
        $user = auth()->user();
        // Check if user role is allowed to edit
        $editableRoles = config('pool_orders.editable_roles', [1, 2, 4]);
        
        if (in_array($user->role_id, $editableRoles)) {
            // Get editable statuses from config
            $editableStatuses = config('pool_orders.editable_statuses', ['pending']);
            $currentStatus = $poolOrder->status_manage_by_admin ?? $poolOrder->status;
            
            if (in_array($currentStatus, $editableStatuses) && $poolOrder->assigned_to != null) {
                $editRoute = route($routePrefix . '.pool-orders.edit', $poolOrder->id);
                $html .= '
                        <li>
                            <a class="dropdown-item text-primary" href="' . $editRoute . '">
                                <i class="fa-solid fa-edit me-1"></i>Assiged Domains
                            </a>
                        </li>';
            }
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
            if ($poolOrder->status_manage_by_admin !== 'cancelled') {
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
        
        // Add "Locked Out of Instantly" option for non-cancelled orders
        if (!$poolOrder->locked_out_of_instantly && $poolOrder->status !== 'cancelled' && $poolOrder->assigned_to != null) {
            $html .= '
                    <li>
                        <a class="dropdown-item text-warning" href="javascript:void(0)" 
                           onclick="lockOutOfInstantly(' . $poolOrder->id . ')">
                            <i class="fa-solid fa-lock me-1"></i>Locked Out of Instantly
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
            fputcsv($file, ['First Name', 'Last Name', 'Email Address', 'Password', 'Org Unit Path [Required]']);


            $domains = $poolOrder->domains;
            
            // Collect all unique pool IDs to eager load if possible (though we do it in loop for now)
            // Or just rely on caching if getPoolAttribute supports it.
            
            foreach ($domains as $domainEntry) {
                // Determine Pool ID and Domain Name
                $poolId = $domainEntry['pool_id'] ?? null;
                $domainName = $domainEntry['domain_name'] ?? 'Unknown';

                // Try to load Pool
                $pool = null;
                if ($poolId) {
                    $pool = \App\Models\Pool::find($poolId);
                }
                
                // Get Pool Defaults
                $poolFirstName = $pool->first_name ?? '';
                $poolLastName = $pool->last_name ?? '';
                $poolPassword = '';
                if ($pool) {
                    $poolPassword = $pool->email_persona_password ?? $pool->persona_password ?? '';
                }
                
                // Get Pool Prefix Variants Details (for passwords/names lookup)
                $poolPrefixDetails = [];
                if ($pool && $pool->prefix_variants_details) {
                    $poolPrefixDetails = is_string($pool->prefix_variants_details) 
                        ? json_decode($pool->prefix_variants_details, true) 
                        : $pool->prefix_variants_details;
                }

                // Determine Prefixes to export
                // Priority: selected_prefixes (Rich) -> prefixes (List) -> Pool Prefixes (Legacy/Fallback)
                
                $prefixesToExport = [];
                
                if (!empty($domainEntry['selected_prefixes'])) {
                     // Rich structure from migration
                     $selected = $domainEntry['selected_prefixes'];
                     if (is_string($selected)) $selected = json_decode($selected, true);
                     
                     foreach ($selected as $key => $data) {
                         $prefixesToExport[$key] = [
                             'email' => $data['email'] ?? ($key . '@' . $domainName),
                             'key' => $key
                         ];
                     }
                } elseif (!empty($domainEntry['prefixes'])) {
                    // Semirich structure (just keys)
                    $prefixes = $domainEntry['prefixes'];
                    if (is_string($prefixes)) $prefixes = json_decode($prefixes, true);
                    
                    // prefixes might be array of strings (keys) or key=>val. Migration made it array of strings if I recall? 
                    // Migration: $prefixes[] = $key; (Indexed array of keys)
                    // But legacy might be key=>val.
                    
                    foreach ($prefixes as $k => $v) {
                        // If indexed array, v is key. If assoc, k is key.
                        $key = is_int($k) ? $v : $k;
                        $prefixesToExport[$key] = [
                            'email' => $key . '@' . $domainName, // Construct on fly
                            'key' => $key
                        ];
                    }
                } elseif ($pool && $pool->prefix_variants) {
                    // Legacy Fallback: Use all pool variants
                    $variants = $pool->prefix_variants;
                    if (is_string($variants)) $variants = json_decode($variants, true);
                     if (is_array($variants)) {
                        foreach ($variants as $key => $v) {
                            $prefixesToExport[$key] = [
                                'email' => $key . '@' . $domainName,
                                'key' => $key
                            ];
                        }
                    }
                }
                
                // If still empty (no prefixes), maybe just domain root?
                if (empty($prefixesToExport)) {
                     // Single row for domain?
                     $password = $poolPassword ?: $this->customEncrypt($poolOrder->id, 0);
                     fputcsv($file, [
                        $poolFirstName,
                        $poolLastName,
                        '', // No email? Or clean domain?
                        $password,
                        '/'
                    ]);
                    continue;
                }
                
                // iterate and write rows
                $counter = 0;
                foreach ($prefixesToExport as $variantKey => $info) {
                    $email = $info['email'];
                    
                    // Lookup Details (First/Last/Password)
                    // 1. From Pool Prefix Details
                    $variantFirstName = $poolPrefixDetails[$variantKey]['first_name'] ?? $poolFirstName;
                    $variantLastName = $poolPrefixDetails[$variantKey]['last_name'] ?? $poolLastName;
                    $password = $poolPrefixDetails[$variantKey]['password'] ?? $poolPassword;
                    
                    // 2. Custom Encrypt Fallback
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

    /**
     * Mark pool order as locked out of Instantly
     * This will cancel the order and remove the subscription
     *
     * @param int $orderId
     * @return array
     */
    
    public function lockOutOfInstantly($orderId)
    {
        try {
            $poolOrder = PoolOrder::findOrFail($orderId);
            
            // Check if already locked out
            if ($poolOrder->locked_out_of_instantly) {
                return [
                    'success' => false,
                    'message' => 'This pool order is already marked as locked out of Instantly'
                ];
            }

            // Mark as locked out before cancellation
            $poolOrder->locked_out_of_instantly = true;
            $poolOrder->locked_out_at = now();
            $poolOrder->reason = 'Locked out of Instantly';
            $poolOrder->save();

            Log::info('Pool order marked as locked out of Instantly', [
                'pool_order_id' => $poolOrder->id,
                'user_id' => $poolOrder->user_id
            ]);

            // Use PoolOrderCancelledService to handle the cancellation
            $cancelService = new PoolOrderCancelledService();
            $result = $cancelService->cancelSubscription(
                $poolOrder->id, 
                $poolOrder->user_id, 
                'Locked out of Instantly'
            );

            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => 'Pool order marked as locked out of Instantly and cancelled successfully'
                ];
            } else {
                // If cancellation failed, still keep the locked out flag
                Log::warning('Pool order marked as locked out but cancellation failed', [
                    'pool_order_id' => $poolOrder->id,
                    'error' => $result['message']
                ]);
                
                return [
                    'success' => true,
                    'message' => 'Pool order marked as locked out of Instantly. Note: ' . $result['message']
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error marking pool order as locked out of Instantly: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error marking pool order as locked out: ' . $e->getMessage()
            ];
        }
    }
}
