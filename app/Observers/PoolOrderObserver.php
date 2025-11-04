<?php

namespace App\Observers;

use App\Models\PoolOrder;
use App\Models\PoolOrderMigrationTask;
use App\Services\PoolDomainService;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Log;

class PoolOrderObserver
{
    protected $poolDomainService;

    public function __construct(PoolDomainService $poolDomainService)
    {
        $this->poolDomainService = $poolDomainService;
    }

    /**
     * Handle the PoolOrder "created" event.
     */
    public function created(PoolOrder $poolOrder): void
    {
        $this->clearRelatedCaches($poolOrder);
        
        // Send Slack notification for new pool order
        Log::info('New PoolOrder created, sending Slack notification', [
            'pool_order_id' => $poolOrder->id,
            'user_id' => $poolOrder->user_id,
            'status' => $poolOrder->status
        ]);
        
        $this->sendCreationNotification($poolOrder);
    }

    /**
     * Handle the PoolOrder "updated" event.
     */
    
    public function updated(PoolOrder $poolOrder): void
    {
        // Clear caches
        $this->clearRelatedCaches($poolOrder);
        
        // Check if status changed from 'draft' to 'pending'
        if ($poolOrder->isDirty('status_manage_by_admin') && 
            $poolOrder->getOriginal('status_manage_by_admin') === 'draft' && 
            $poolOrder->status_manage_by_admin === 'pending') {

            Log::info('PoolOrder status changed from draft to pending, sending Slack notification', [
                'pool_order_id' => $poolOrder->id,
                'previous_status' => $poolOrder->getOriginal('status'),
                'new_status' => $poolOrder->status
            ]);
            
            $this->sendDraftToPendingNotification($poolOrder);
        }
        
        // Check if status_manage_by_admin changed to 'in-progress'
        if ($poolOrder->isDirty('status_manage_by_admin') && 
            $poolOrder->status_manage_by_admin === 'in-progress') {
            
            Log::info('PoolOrder status changed to in-progress, sending Slack notification', [
                'pool_order_id' => $poolOrder->id,
                'previous_status' => $poolOrder->getOriginal('status_manage_by_admin'),
                'new_status' => $poolOrder->status_manage_by_admin
            ]);
            
            $this->sendConfigurationNotification($poolOrder);
            
            // Create migration task for configuration
            $this->createMigrationTask($poolOrder, 'configuration', $poolOrder->getOriginal('status_manage_by_admin'));
        }
        
        // Check if status or status_manage_by_admin changed to 'cancelled'
        if (($poolOrder->isDirty('status') && $poolOrder->status === 'cancelled') ||
            ($poolOrder->isDirty('status_manage_by_admin') && $poolOrder->status_manage_by_admin === 'cancelled')) {
            
            Log::info('PoolOrder cancelled, sending Slack notification', [
                'pool_order_id' => $poolOrder->id,
                'previous_status' => $poolOrder->getOriginal('status'),
                'new_status' => $poolOrder->status,
                'previous_status_admin' => $poolOrder->getOriginal('status_manage_by_admin'),
                'new_status_admin' => $poolOrder->status_manage_by_admin
            ]);
            
            $this->sendCancellationNotification($poolOrder);
            
            // Create migration task for cancellation
            $previousStatus = $poolOrder->isDirty('status') 
                ? $poolOrder->getOriginal('status') 
                : $poolOrder->getOriginal('status_manage_by_admin');
            $this->createMigrationTask($poolOrder, 'cancellation', $previousStatus);
        }
    }

    /**
     * Handle the PoolOrder "deleted" event.
     */
    public function deleted(PoolOrder $poolOrder): void
    {
        $this->clearRelatedCaches($poolOrder);
    }

    /**
     * Clear related caches when pool order changes
     */
    private function clearRelatedCaches(PoolOrder $poolOrder): void
    {
        // Clear cache for the user and any pools that might be affected
        $this->poolDomainService->clearRelatedCache(null, $poolOrder->user_id);
        
        // If the pool order has domains, we might need to clear pool-specific caches too
        if (is_array($poolOrder->domains)) {
            foreach ($poolOrder->domains as $domain) {
                $poolId = $domain['pool_id'] ?? null;
                if ($poolId) {
                    $this->poolDomainService->clearRelatedCache($poolId, $poolOrder->user_id);
                }
            }
        }
    }

    /**
     * Send Slack notification for pool order configuration completion
     * 
     * @param \App\Models\PoolOrder $poolOrder
     * @return void
     */
    private function sendConfigurationNotification(PoolOrder $poolOrder): void
    {
        try {
            $user = $poolOrder->user;
            
            if (!$user) {
                Log::warning('User not found for pool order', [
                    'pool_order_id' => $poolOrder->id
                ]);
                return;
            }

            // Prepare domain list for notification
            $domainsList = 'N/A';
            if ($poolOrder->domains && is_array($poolOrder->domains)) {
                $domainNames = array_map(function($domain) {
                    return $domain['domain_name'] ?? 'Unknown';
                }, array_slice($poolOrder->domains, 0, 5)); // Show first 5 domains
                
                $domainsList = implode(', ', $domainNames);
                
                if (count($poolOrder->domains) > 5) {
                    $remaining = count($poolOrder->domains) - 5;
                    $domainsList .= " (and {$remaining} more)";
                }
            }

            $message = [
                'text' => '🎯 *Pool Order Status Updated: Pending → In-Progress*',
                'attachments' => [
                    [
                        'color' => '#007bff',
                        'fields' => [
                            [
                                'title' => 'Order ID',
                                'value' => '#' . $poolOrder->id,
                                'short' => true
                            ],
                            [
                                'title' => 'Status',
                                'value' => 'In-Progress',
                                'short' => true
                            ],
                            // [
                            //     'title' => 'Customer Email',
                            //     'value' => $user->email,
                            //     'short' => true
                            // ],
                            [
                                'title' => 'Plan',
                                'value' => $poolOrder->poolPlan->name,
                                'short' => true
                            ],
                            [
                                'title' => 'Quantity',
                                'value' => $poolOrder->quantity . ' inboxes',
                                'short' => true
                            ],
                            [
                                'title' => 'Amount',
                                'value' => '$' . number_format($poolOrder->amount, 2),
                                'short' => true
                            ],
                            [
                                'title' => 'Domains Selected',
                                'value' => $poolOrder->selected_domains_count,
                                'short' => true
                            ],
                            [
                                'title' => 'Total Inboxes',
                                'value' => $poolOrder->total_inboxes,
                                'short' => true
                            ],
                            [
                                'title' => 'Migration Task',
                                'value' => '📋 Configuration task created and pending assignment',
                                'short' => false
                            ],
                            [
                                'title' => 'Domains',
                                'value' => $domainsList,
                                'short' => false
                            ]
                        ],
                        'footer' => config('app.name', 'ProjectInbox') . ' - Pool Order System | Migration Task Created',
                        'ts' => time()
                    ]
                ]
            ];

            // Send to Slack
            $result = SlackNotificationService::send('inbox-trial-setup', $message);
            
            if ($result) {
                Log::info('Slack notification sent successfully for pool order', [
                    'pool_order_id' => $poolOrder->id,
                    'domains_count' => $poolOrder->selected_domains_count,
                    'total_inboxes' => $poolOrder->total_inboxes
                ]);
            } else {
                Log::warning('Slack notification failed to send for pool order', [
                    'pool_order_id' => $poolOrder->id
                ]);
            }

        } catch (\Exception $e) {
            // Non-critical, just log the error
            Log::error('Exception sending Slack notification for pool order', [
                'error' => $e->getMessage(),
                'pool_order_id' => $poolOrder->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send Slack notification for pool order cancellation
     * 
     * @param \App\Models\PoolOrder $poolOrder
     * @return void
     */
    private function sendCancellationNotification(PoolOrder $poolOrder): void
    {
        try {
            $user = $poolOrder->user;
            
            if (!$user) {
                Log::warning('User not found for cancelled pool order', [
                    'pool_order_id' => $poolOrder->id
                ]);
                return;
            }

            // Extract cancellation details from meta
            $cancelledBy = 'Unknown';
            $cancelledAt = now()->format('Y-m-d H:i:s T');
            $reason = 'No reason provided';
            
            if ($poolOrder->meta && is_array($poolOrder->meta) && isset($poolOrder->meta['cancellation'])) {
                $cancellation = $poolOrder->meta['cancellation'];
                $cancelledBy = $cancellation['cancelled_by_name'] ?? 'Unknown';
                $cancelledAt = $cancellation['cancelled_at'] ?? $cancelledAt;
                $reason = $cancellation['reason'] ?? $poolOrder->reason ?? 'No reason provided';
            } elseif ($poolOrder->reason) {
                $reason = $poolOrder->reason;
            }
            
            // Fallback: If cancelled_by is still Unknown, try to get from auth user or pool order user
            if ($cancelledBy === 'Unknown') {
                if (auth()->check()) {
                    $cancelledBy = auth()->user()->name;
                } elseif ($user) {
                    $cancelledBy = $user->name;
                }
            }

            // Prepare domain list
            $domainsList = 'N/A';
            if ($poolOrder->domains && is_array($poolOrder->domains) && count($poolOrder->domains) > 0) {
                $domainNames = array_map(function($domain) {
                    return $domain['domain_name'] ?? 'Unknown';
                }, array_slice($poolOrder->domains, 0, 3)); // Show first 3 domains
                
                $domainsList = implode(', ', $domainNames);
                
                if (count($poolOrder->domains) > 3) {
                    $remaining = count($poolOrder->domains) - 3;
                    $domainsList .= " (and {$remaining} more)";
                }
            }

            $message = [
                'text' => '🚫 *Pool Order Cancelled*',
                'attachments' => [
                    [
                        'color' => '#dc3545',
                        'fields' => [
                            [
                                'title' => 'Order ID',
                                'value' => '#' . $poolOrder->id,
                                'short' => true
                            ],
                            [
                                'title' => 'Status',
                                'value' => '❌ Cancelled',
                                'short' => true
                            ],
                            // [
                            //     'title' => 'Customer Email',
                            //     'value' => $user->email,
                            //     'short' => true
                            // ],
                            [
                                'title' => 'Plan',
                                'value' => $poolOrder->poolPlan->name,
                                'short' => true
                            ],
                            [
                                'title' => 'Amount',
                                'value' => '$' . number_format($poolOrder->amount, 2),
                                'short' => true
                            ],
                            [
                                'title' => 'Order Date',
                                'value' => $poolOrder->created_at->format('M d, Y'),
                                'short' => true
                            ],
                            [
                                'title' => 'Domains Count',
                                'value' => $poolOrder->selected_domains_count,
                                'short' => true
                            ],
                            [
                                'title' => 'Total Inboxes',
                                'value' => $poolOrder->total_inboxes,
                                'short' => true
                            ],
                            [
                                'title' => 'Cancelled By',
                                'value' => $cancelledBy,
                                'short' => true
                            ],
                            [
                                'title' => 'Cancelled At',
                                'value' => $cancelledAt,
                                'short' => true
                            ],
                            [
                                'title' => 'Duration',
                                'value' => $poolOrder->created_at->diffForHumans(now(), true),
                                'short' => true
                            ],
                            [
                                'title' => 'ChargeBee Subscription',
                                'value' => $poolOrder->chargebee_subscription_id ?? 'N/A',
                                'short' => true
                            ],
                            [
                                'title' => 'Migration Task',
                                'value' => '🔧 Cancellation cleanup task created and pending assignment',
                                'short' => false
                            ],
                            [
                                'title' => 'Cancellation Reason',
                                'value' => $reason,
                                'short' => false
                            ],
                            [
                                'title' => 'Affected Domains',
                                'value' => $domainsList,
                                'short' => false
                            ]
                        ],
                        'footer' => config('app.name', 'ProjectInbox') . ' - Pool Order Cancellation | ' . $poolOrder->selected_domains_count . ' domain(s) freed | Migration Task Created',
                        'ts' => time()
                    ]
                ]
            ];

            // Send to Slack - use 'inbox-trial-cancellations' channel for cancellations
            $result = SlackNotificationService::send('inbox-trial-cancellations', $message);
            
            if ($result) {
                Log::info('Slack cancellation notification sent successfully', [
                    'pool_order_id' => $poolOrder->id,
                    'domains_freed' => $poolOrder->selected_domains_count
                ]);
            } else {
                Log::warning('Slack cancellation notification failed to send', [
                    'pool_order_id' => $poolOrder->id
                ]);
            }

        } catch (\Exception $e) {
            // Non-critical, just log the error
            Log::error('Exception sending Slack cancellation notification', [
                'error' => $e->getMessage(),
                'pool_order_id' => $poolOrder->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send Slack notification for new pool order creation
     * 
     * @param \App\Models\PoolOrder $poolOrder
     * @return void
     */
    private function sendCreationNotification(PoolOrder $poolOrder): void
    {
        try {
            $user = $poolOrder->user;
            
            if (!$user) {
                Log::warning('User not found for new pool order', [
                    'pool_order_id' => $poolOrder->id
                ]);
                return;
            }

            // Prepare domain list for notification
            $domainsList = 'N/A';
            if ($poolOrder->domains && is_array($poolOrder->domains) && count($poolOrder->domains) > 0) {
                $domainNames = array_map(function($domain) {
                    return $domain['domain_name'] ?? 'Unknown';
                }, array_slice($poolOrder->domains, 0, 5)); // Show first 5 domains
                
                $domainsList = implode(', ', $domainNames);
                
                if (count($poolOrder->domains) > 5) {
                    $remaining = count($poolOrder->domains) - 5;
                    $domainsList .= " (and {$remaining} more)";
                }
            }

            $message = [
                'text' => '🆕 *New Pool Order Created*',
                'attachments' => [
                    [
                        'color' => '#36a64f',
                        'fields' => [
                            [
                                'title' => 'Order ID',
                                'value' => '#' . $poolOrder->id,
                                'short' => true
                            ],
                            [
                                'title' => 'Status',
                                'value' => ucfirst($poolOrder->status_manage_by_admin),
                                'short' => true
                            ],
                            [
                                'title' => 'Customer Name',
                                'value' => $user->name,
                                'short' => true
                            ],
                            // [
                            //     'title' => 'Customer Email',
                            //     'value' => $user->email,
                            //     'short' => true
                            // ],
                            [
                                'title' => 'Plan',
                                'value' => $poolOrder->poolPlan->name ?? 'N/A',
                                'short' => true
                            ],
                            [
                                'title' => 'Quantity',
                                'value' => $poolOrder->quantity . ' inboxes',
                                'short' => true
                            ],
                            [
                                'title' => 'Amount',
                                'value' => '$' . number_format($poolOrder->amount, 2),
                                'short' => true
                            ],
                            [
                                'title' => 'ChargeBee Subscription',
                                'value' => $poolOrder->chargebee_subscription_id ?? 'Pending',
                                'short' => true
                            ]
                        ],
                        'footer' => config('app.name', 'ProjectInbox') . ' - New Pool Order',
                        'ts' => time()
                    ]
                ]
            ];

            // Send to Slack
            $result = SlackNotificationService::send('inbox-trial-setup', $message);
            
            if ($result) {
                Log::info('Slack notification sent successfully for new pool order', [
                    'pool_order_id' => $poolOrder->id,
                    'user_email' => $user->email,
                    'amount' => $poolOrder->amount
                ]);
            } else {
                Log::warning('Slack notification failed to send for new pool order', [
                    'pool_order_id' => $poolOrder->id
                ]);
            }

        } catch (\Exception $e) {
            // Non-critical, just log the error
            Log::error('Exception sending Slack notification for new pool order', [
                'error' => $e->getMessage(),
                'pool_order_id' => $poolOrder->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send Slack notification when pool order status changes from draft to pending
     * 
     * @param \App\Models\PoolOrder $poolOrder
     * @return void
     */
    private function sendDraftToPendingNotification(PoolOrder $poolOrder): void
    {
        try {
            $user = $poolOrder->user;
            
            if (!$user) {
                Log::warning('User not found for draft to pending pool order', [
                    'pool_order_id' => $poolOrder->id
                ]);
                return;
            }

            // Prepare domain list for notification
            $domainsList = 'N/A';
            if ($poolOrder->domains && is_array($poolOrder->domains) && count($poolOrder->domains) > 0) {
                $domainNames = array_map(function($domain) {
                    return $domain['domain_name'] ?? 'Unknown';
                }, array_slice($poolOrder->domains, 0, 5)); // Show first 5 domains
                
                $domainsList = implode(', ', $domainNames);
                
                if (count($poolOrder->domains) > 5) {
                    $remaining = count($poolOrder->domains) - 5;
                    $domainsList .= " (and {$remaining} more)";
                }
            }

            $message = [
                'text' => '✅ *Pool Order Status Updated: Draft → Pending*',
                'attachments' => [
                    [
                        'color' => '#ffc107',
                        'fields' => [
                            [
                                'title' => 'Order ID',
                                'value' => '#' . $poolOrder->id,
                                'short' => true
                            ],
                            [
                                'title' => 'Status',
                                'value' => '⏳ Pending',
                                'short' => true
                            ],
                            [
                                'title' => 'Customer Name',
                                'value' => $user->name,
                                'short' => true
                            ],
                            // [
                            //     'title' => 'Customer Email',
                            //     'value' => $user->email,
                            //     'short' => true
                            // ],
                            [
                                'title' => 'Plan',
                                'value' => $poolOrder->poolPlan->name ?? 'N/A',
                                'short' => true
                            ],
                            [
                                'title' => 'Quantity',
                                'value' => $poolOrder->quantity . ' inboxes',
                                'short' => true
                            ],
                            [
                                'title' => 'Amount',
                                'value' => '$' . number_format($poolOrder->amount, 2),
                                'short' => true
                            ],
                            // [
                            //     'title' => 'Domains Selected',
                            //     'value' => $poolOrder->selected_domains_count ?? 0,
                            //     'short' => true
                            // ],
                            // [
                            //     'title' => 'Total Inboxes',
                            //     'value' => $poolOrder->total_inboxes ?? 0,
                            //     'short' => true
                            // ],
                            [
                                'title' => 'ChargeBee Subscription',
                                'value' => $poolOrder->chargebee_subscription_id ?? 'Pending',
                                'short' => true
                            ],
                            // [
                            //     'title' => 'Domains',
                            //     'value' => $domainsList,
                            //     'short' => false
                            // ]
                        ],
                        'footer' => config('app.name', 'ProjectInbox') . ' - Pool Order Status Update',
                        'ts' => time()
                    ]
                ]
            ];

            // Send to Slack
            $result = SlackNotificationService::send('inbox-trial-setup', $message);
            
            if ($result) {
                Log::info('Slack notification sent successfully for draft to pending status change', [
                    'pool_order_id' => $poolOrder->id,
                    'user_email' => $user->email,
                    'amount' => $poolOrder->amount
                ]);
            } else {
                Log::warning('Slack notification failed to send for draft to pending status change', [
                    'pool_order_id' => $poolOrder->id
                ]);
            }

        } catch (\Exception $e) {
            // Non-critical, just log the error
            Log::error('Exception sending Slack notification for draft to pending status change', [
                'error' => $e->getMessage(),
                'pool_order_id' => $poolOrder->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Create a migration task for pool order status change
     * 
     * @param \App\Models\PoolOrder $poolOrder
     * @param string $taskType
     * @param string|null $previousStatus
     * @return void
     */
    private function createMigrationTask(PoolOrder $poolOrder, string $taskType, ?string $previousStatus = null): void
    {
        try {
            // Prepare metadata based on task type
            $metadata = [
                'order_id' => $poolOrder->id,
                'plan_name' => $poolOrder->poolPlan->name ?? 'N/A',
                'amount' => $poolOrder->amount,
                'quantity' => $poolOrder->quantity,
                'selected_domains_count' => $poolOrder->selected_domains_count ?? 0,
                'total_inboxes' => $poolOrder->total_inboxes ?? 0,
                'hosting_platform' => $poolOrder->hosting_platform ?? 'N/A',
            ];

            // Add task-specific metadata
            if ($taskType === 'cancellation') {
                $metadata['cancellation_reason'] = $poolOrder->reason ?? 'No reason provided';
                $metadata['chargebee_subscription_id'] = $poolOrder->chargebee_subscription_id;
                
                if ($poolOrder->meta && is_array($poolOrder->meta) && isset($poolOrder->meta['cancellation'])) {
                    $metadata['cancelled_by'] = $poolOrder->meta['cancellation']['cancelled_by_name'] ?? 'Unknown';
                    $metadata['cancelled_at'] = $poolOrder->meta['cancellation']['cancelled_at'] ?? now()->toDateTimeString();
                }
            } elseif ($taskType === 'configuration') {
                $metadata['domains_selected'] = $poolOrder->domains ?? [];
                $metadata['hosting_platform_data'] = $poolOrder->hosting_platform_data ?? [];
            }

            // Create the migration task
            $task = PoolOrderMigrationTask::create([
                'pool_order_id' => $poolOrder->id,
                'user_id' => $poolOrder->user_id,
                'assigned_to' => null, // Can be assigned later by admin
                'task_type' => $taskType,
                'status' => 'pending',
                'previous_status' => $previousStatus,
                'new_status' => $taskType === 'configuration' ? 'in-progress' : 'cancelled',
                'notes' => null,
                'metadata' => $metadata,
                'started_at' => null,
                'completed_at' => null,
            ]);

            Log::info('Migration task created successfully', [
                'task_id' => $task->id,
                'pool_order_id' => $poolOrder->id,
                'task_type' => $taskType,
                'status' => 'pending'
            ]);

        } catch (\Exception $e) {
            // Non-critical, just log the error
            Log::error('Exception creating migration task', [
                'error' => $e->getMessage(),
                'pool_order_id' => $poolOrder->id,
                'task_type' => $taskType,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}