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
        $hideIfEmpty = $options['hideIfEmpty'] ?? false; // Only hide dropdown if explicitly requested (for in-queue tab)

        // Show N/A for draft orders
        $currentStatus = $poolOrder->status_manage_by_admin ?? $poolOrder->status;
        if ($currentStatus === 'draft' && empty($poolOrder->domains)) {
            return '<span class="">N/A</span>';
        }

        $orderId = $poolOrder->id;
        $viewRoute = route($routePrefix . '.pool-orders.view', $poolOrder->id);

        // Track if any menu items are added
        $hasMenuItems = false;
        $html = '';

        if ($showView && $poolOrder->assigned_to) {
            if (!$hasMenuItems) {
                $html = '
                    <div class="dropdown">
                        <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">';
                $hasMenuItems = true;
            }
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
                // $html .= '
                //         <li>
                //             <a class="dropdown-item text-primary" href="' . $editRoute . '">
                //                 <i class="fa-solid fa-edit me-1"></i>Assiged Domains
                //             </a>
                //         </li>';
            }
        }

        $currentStatus = $poolOrder->status_manage_by_admin ?? $poolOrder->status;

        // Only show "Assign to Me" if:
        // 1. showAssignToMe flag is true (controller already checks hasDomains() before setting this)
        // 2. Order is not already assigned
        // 3. Status is not 'draft'
        // Note: hasDomains() check is done in controller, not here, to avoid affecting other tabs
        if ($showAssignToMe && !$poolOrder->assigned_to && $currentStatus !== 'draft') {
            if (!$hasMenuItems) {
                $html = '
                    <div class="dropdown">
                        <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">';
                $hasMenuItems = true;
            }
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
                $hasDomains = $poolOrder->hasDomains();

                // Check if dropdown would have valid options
                // in-progress without domains = empty dropdown (only completed needs domains, cancelled is hidden)
                // So hide Change Status if in-progress and no domains
                $hasValidStatusOptions = true;
                if ($currentStatus === 'in-progress' && !$hasDomains) {
                    $hasValidStatusOptions = false;
                }

                if ($hasValidStatusOptions) {
                    if (!$hasMenuItems) {
                        $html = '
                            <div class="dropdown">
                                <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">';
                        $hasMenuItems = true;
                    }
                    $hasDomainsStr = $hasDomains ? 'true' : 'false';
                    $html .= '
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" 
                               onclick="changePoolOrderStatus(' . $poolOrder->id . ', \'' . $currentStatus . '\', ' . $hasDomainsStr . ')">
                                <i class="fa-solid fa-sync me-1"></i>Change Status
                            </a>
                        </li>';
                }
            }
        }

        if ($showViewDomains) {
            if (!$hasMenuItems) {
                $html = '
                    <div class="dropdown">
                        <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">';
                $hasMenuItems = true;
            }
            $html .= '
                    <li>
                        <a class="dropdown-item" href="javascript:void(0)" 
                           onclick="viewPoolOrderDomains(\'' . $orderId . '\')">
                            <i class="fa-solid fa-globe me-1"></i>View Domains
                        </a>
                    </li>';
        }

        if ($showCancel && in_array($poolOrder->status, ['pending', 'in_progress'])) {
            if (!$hasMenuItems) {
                $html = '
                    <div class="dropdown">
                        <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">';
                $hasMenuItems = true;
            }
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
            if (!$hasMenuItems) {
                $html = '
                    <div class="dropdown">
                        <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fa-solid fa-ellipsis-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">';
                $hasMenuItems = true;
            }
            $html .= '
                    <li>
                        <a class="dropdown-item text-warning" href="javascript:void(0)" 
                           onclick="lockOutOfInstantly(' . $poolOrder->id . ')">
                            <i class="fa-solid fa-lock me-1"></i>Locked Out of Instantly
                        </a>
                    </li>';
        }

        // Only return dropdown HTML if there are menu items
        if ($hasMenuItems) {
            $html .= '
                </ul>
            </div>';
            return $html;
        }

        // Only hide dropdown/ellipsis if hideIfEmpty flag is set (for in-queue tab only)
        // Other tabs should show "N/A" or empty string based on existing logic
        if ($hideIfEmpty) {
            return ''; // Hide dropdown and ellipsis for in-queue tab when no actions
        }

        // For other tabs, return "N/A" if no actions (maintains existing behavior)
        return '<span class="">N/A</span>';
    }

    /**
     * Download domains with prefixes as CSV
     * For SMTP pools: Uses Instantly format with all IMAP/SMTP fields from smtp_accounts_data
     * For Google/365 pools: Uses Google Workspace format
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

        $domains = $poolOrder->domains;
        
        // Check if this is an SMTP pool order and collect accounts data
        $isSmtpOrder = false;
        $smtpAccountsMap = []; // Map email => account data for SMTP pools
        $poolIds = [];
        
        // Collect all unique pool IDs from assigned domains
        foreach ($domains as $domainEntry) {
            $poolId = $domainEntry['pool_id'] ?? null;
            if ($poolId && !in_array($poolId, $poolIds)) {
                $poolIds[] = $poolId;
            }
        }
        
        // Check if any pool is SMTP type and load accounts data
        if (!empty($poolIds)) {
            $pools = \App\Models\Pool::whereIn('id', $poolIds)->get();
            
            foreach ($pools as $pool) {
                if ($pool->provider_type === 'SMTP' && $pool->smtp_accounts_data) {
                    $isSmtpOrder = true;
                    
                    // Get smtp_accounts_data - handle both structures
                    $smtpData = $pool->smtp_accounts_data;
                    
                    // Handle compressed data
                    if (isset($smtpData['_compressed']) && $smtpData['_compressed'] === true && isset($smtpData['_data'])) {
                        try {
                            $compressedData = base64_decode($smtpData['_data']);
                            $decompressedJson = gzuncompress($compressedData);
                            if ($decompressedJson !== false) {
                                $smtpData = json_decode($decompressedJson, true);
                            }
                        } catch (\Exception $e) {
                            \Log::error('Failed to decompress smtp_accounts_data', [
                                'pool_id' => $pool->id,
                                'error' => $e->getMessage()
                            ]);
                            $smtpData = null;
                        }
                    }
                    
                    // Extract accounts array - handle both structures
                    $accounts = [];
                    if (is_array($smtpData)) {
                        // Check if it's nested under 'accounts' key
                        if (isset($smtpData['accounts']) && is_array($smtpData['accounts'])) {
                            $accounts = $smtpData['accounts'];
                        } elseif (!empty($smtpData) && isset($smtpData[0])) {
                            // Check if first element has 'email' key (direct array of account objects)
                            if (isset($smtpData[0]['email'])) {
                                $accounts = $smtpData;
                            }
                        }
                    }
                    
                    // Build email => account map for quick lookup (case-insensitive)
                    foreach ($accounts as $account) {
                        $email = $account['email'] ?? '';
                        if ($email) {
                            $emailLower = strtolower(trim($email));
                            // Store with lowercase key for matching, but keep original account data
                            $smtpAccountsMap[$emailLower] = $account;
                        }
                    }
                    
                    \Log::info('SMTP accounts loaded for pool order CSV', [
                        'pool_id' => $pool->id,
                        'pool_order_id' => $poolOrder->id,
                        'accounts_count' => count($accounts),
                        'mapped_emails_count' => count($smtpAccountsMap)
                    ]);
                }
            }
        }

        $filename = 'pool_order_' . $poolOrder->id . '_domains_' . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($poolOrder, $domains, $isSmtpOrder, $smtpAccountsMap) {
            $file = fopen('php://output', 'w');

            if ($isSmtpOrder) {
                // Use separate function for SMTP CSV generation
                $this->generateSmtpCsv($file, $domains, $smtpAccountsMap, $poolOrder->id);
            } else {
                // Google Workspace format for Google/365 pools (existing logic)
                fputcsv($file, ['First Name', 'Last Name', 'Email Address', 'Password', 'Org Unit Path [Required]']);

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
                    if (is_string($selected))
                        $selected = json_decode($selected, true);

                    foreach ($selected as $key => $data) {
                        $prefixesToExport[$key] = [
                            'email' => $data['email'] ?? ($key . '@' . $domainName),
                            'key' => $key
                        ];
                    }
                } elseif (!empty($domainEntry['prefixes'])) {
                    // Semirich structure (just keys)
                    $prefixes = $domainEntry['prefixes'];
                    if (is_string($prefixes))
                        $prefixes = json_decode($prefixes, true);

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
                    if (is_string($variants))
                        $variants = json_decode($variants, true);
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
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate CSV in Instantly format for SMTP pool orders
     * Collects all emails from all domains and matches with smtp_accounts_data
     *
     * @param resource $file File handle for CSV output
     * @param array $domains Array of domain entries from pool order
     * @param array $smtpAccountsMap Map of email (lowercase) => account data
     * @param int $poolOrderId Pool order ID for logging
     * @return void
     */
    private function generateSmtpCsv($file, $domains, $smtpAccountsMap, $poolOrderId)
    {
        // Instantly CSV format headers
        fputcsv($file, [
            'Email',
            'First Name',
            'Last Name',
            'IMAP Username',
            'IMAP Password',
            'IMAP Host',
            'IMAP Port',
            'SMTP Username',
            'SMTP Password',
            'SMTP Host',
            'SMTP Port',
            'Daily Limit',
            'Warmup Enabled',
            'Warmup Limit',
            'Warmup Increment'
        ]);

        // Collect all assigned email addresses from ALL domains in the pool order
        $assignedEmails = [];
        $domainCount = 0;
        
        foreach ($domains as $domainEntry) {
            $domainCount++;
            $domainName = $domainEntry['domain_name'] ?? 'Unknown';
            
            // Get assigned emails from selected_prefixes
            if (!empty($domainEntry['selected_prefixes'])) {
                $selected = $domainEntry['selected_prefixes'];
                
                // Handle both string (JSON) and array formats
                if (is_string($selected)) {
                    $selected = json_decode($selected, true);
                }
                
                // selected_prefixes can be an object/associative array like:
                // {"prefix_variant_1": {"email": "henry@domain.com"}, "prefix_variant_2": {"email": "james@domain.com"}}
                if (is_array($selected) && !empty($selected)) {
                    $emailsInDomain = 0;
                    foreach ($selected as $key => $data) {
                        // $data should be an array with 'email' key
                        if (is_array($data) && isset($data['email'])) {
                            $email = $data['email'];
                            if (!empty(trim($email))) {
                                $emailLower = strtolower(trim($email));
                                $assignedEmails[] = $emailLower;
                                $emailsInDomain++;
                            }
                        }
                    }
                    
                    \Log::debug('Collected emails from domain for SMTP CSV', [
                        'pool_order_id' => $poolOrderId,
                        'domain_name' => $domainName,
                        'domain_index' => $domainCount,
                        'emails_count' => $emailsInDomain,
                        'total_emails_so_far' => count($assignedEmails)
                    ]);
                } else {
                    \Log::warning('selected_prefixes is not a valid array for domain', [
                        'pool_order_id' => $poolOrderId,
                        'domain_name' => $domainName,
                        'domain_index' => $domainCount,
                        'selected_type' => gettype($selected),
                        'selected_value' => is_string($selected) ? substr($selected, 0, 100) : $selected
                    ]);
                }
            } else {
                \Log::debug('No selected_prefixes found for domain', [
                    'pool_order_id' => $poolOrderId,
                    'domain_name' => $domainName,
                    'domain_index' => $domainCount
                ]);
            }
        }
        
        // Remove duplicates while preserving order
        $assignedEmails = array_values(array_unique($assignedEmails));

        \Log::info('All assigned emails collected for SMTP pool order CSV', [
            'pool_order_id' => $poolOrderId,
            'total_domains' => $domainCount,
            'assigned_emails_count' => count($assignedEmails),
            'smtp_accounts_map_count' => count($smtpAccountsMap),
            'sample_assigned_emails' => array_slice($assignedEmails, 0, 10), // First 10 for debugging
            'sample_map_keys' => array_slice(array_keys($smtpAccountsMap), 0, 10) // First 10 keys from map
        ]);
        
        // Check how many emails will match
        $matchedCount = 0;
        $unmatchedEmails = [];
        foreach ($assignedEmails as $email) {
            if (isset($smtpAccountsMap[$email])) {
                $matchedCount++;
            } else {
                $unmatchedEmails[] = $email;
            }
        }
        
        \Log::info('Email matching summary for SMTP CSV', [
            'pool_order_id' => $poolOrderId,
            'total_assigned_emails' => count($assignedEmails),
            'matched_emails' => $matchedCount,
            'unmatched_emails' => count($unmatchedEmails),
            'sample_unmatched' => array_slice($unmatchedEmails, 0, 5)
        ]);

                // Write rows for each assigned email using data from smtp_accounts_data
                $rowsWritten = 0;
                $rowsSkipped = 0;
        
        foreach ($assignedEmails as $email) {
            // Email is already lowercase from collection, map uses lowercase keys
            $account = $smtpAccountsMap[$email] ?? null;
            
            if ($account) {
                // Use data from stored CSV account - match exact field names from database
                fputcsv($file, [
                    $account['email'] ?? $email,
                    $account['first_name'] ?? '',
                    $account['last_name'] ?? '',
                    $account['imap_username'] ?? $account['email'] ?? $email,
                    $account['imap_password'] ?? $account['password'] ?? '',
                    $account['imap_host'] ?? '',
                    $account['imap_port'] ?? '',
                    $account['smtp_username'] ?? $account['email'] ?? $email,
                    $account['smtp_password'] ?? $account['password'] ?? '',
                    $account['smtp_host'] ?? '',
                    $account['smtp_port'] ?? '',
                    $account['daily_limit'] ?? '',
                    $account['warmup_enabled'] ?? 'TRUE',
                    $account['warmup_limit'] ?? '10',
                    $account['warmup_increment'] ?? '1'
                ]);
                $rowsWritten++;
            } else {
                // Fallback: if account not found in map, log warning and skip
                \Log::warning('SMTP account not found in smtp_accounts_data for pool order', [
                    'email' => $email,
                    'pool_order_id' => $poolOrderId,
                    'available_emails_count' => count($smtpAccountsMap)
                ]);
                $rowsSkipped++;
            }
        }
        
        \Log::info('SMTP CSV generation completed', [
            'pool_order_id' => $poolOrderId,
            'rows_written' => $rowsWritten,
            'rows_skipped' => $rowsSkipped,
            'total_emails' => count($assignedEmails)
        ]);
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
