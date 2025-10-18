<?php

namespace App\Observers;

use App\Models\PoolOrder;
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
    }

    /**
     * Handle the PoolOrder "updated" event.
     */
    public function updated(PoolOrder $poolOrder): void
    {
        // Clear caches
        $this->clearRelatedCaches($poolOrder);
        
        // Check if status_manage_by_admin changed to 'in-progress'
        if ($poolOrder->isDirty('status_manage_by_admin') && 
            $poolOrder->status_manage_by_admin === 'in-progress') {
            
            Log::info('PoolOrder status changed to in-progress, sending Slack notification', [
                'pool_order_id' => $poolOrder->id,
                'previous_status' => $poolOrder->getOriginal('status_manage_by_admin'),
                'new_status' => $poolOrder->status_manage_by_admin
            ]);
            
            $this->sendConfigurationNotification($poolOrder);
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
            $domainsList = '';
            if ($poolOrder->domains && is_array($poolOrder->domains)) {
                $domainNames = array_map(function($domain) {
                    return $domain['domain_name'] ?? 'Unknown';
                }, array_slice($poolOrder->domains, 0, 5)); // Show first 5 domains
                
                $domainsList = "\n*Domains:* " . implode(', ', $domainNames);
                
                if (count($poolOrder->domains) > 5) {
                    $remaining = count($poolOrder->domains) - 5;
                    $domainsList .= " (and {$remaining} more)";
                }
            }

            $message = [
                'text' => '🎯 Pool Order Configuration Completed',
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => '🎯 Pool Order Configuration Completed',
                            'emoji' => true
                        ]
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Order ID:*\n#{$poolOrder->id}"
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Status:*\n✅ In-Progress"
                            ],
                            // [
                            //     'type' => 'mrkdwn',
                            //     'text' => "*Customer:*\n{$user->first_name} {$user->last_name}"
                            // ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Email:*\n{$user->email}"
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Plan:*\n{$poolOrder->poolPlan->name}"
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Quantity:*\n{$poolOrder->quantity} inboxes"
                            ]
                        ]
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Domains Selected:*\n{$poolOrder->selected_domains_count}"
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Total Inboxes:*\n{$poolOrder->total_inboxes}"
                            ],
                            // [
                            //     'type' => 'mrkdwn',
                            //     'text' => "*Hosting Platform:*\n" . ($poolOrder->hosting_platform ?? 'Not specified')
                            // ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Amount:*\n$" . number_format($poolOrder->amount, 2)
                            ]
                        ]
                    ]
                ]
            ];

            // Add domains list if available
            if ($domainsList) {
                $message['blocks'][] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $domainsList
                    ]
                ];
            }

            // Add hosting platform details if available
            if ($poolOrder->hosting_platform_data && is_array($poolOrder->hosting_platform_data)) {
                $platformDetails = [];
                foreach ($poolOrder->hosting_platform_data as $key => $value) {
                    // Skip sensitive fields
                    if (in_array($key, ['password', 'api_key', 'secret', 'backup_codes'])) {
                        $platformDetails[] = "*" . ucwords(str_replace('_', ' ', $key)) . ":* [Hidden]";
                    } else {
                        $platformDetails[] = "*" . ucwords(str_replace('_', ' ', $key)) . ":* " . $value;
                    }
                }
                
                // if (!empty($platformDetails)) {
                //     $message['blocks'][] = [
                //         'type' => 'section',
                //         'text' => [
                //             'type' => 'mrkdwn',
                //             'text' => "*Platform Details:*\n" . implode("\n", $platformDetails)
                //         ]
                //     ];
                // }
            }

            // Add context with timestamp
            $message['blocks'][] = [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "⏰ " . now()->format('F j, Y \a\t g:i A')
                    ]
                ]
            ];

            // Send to Slack
            $result = SlackNotificationService::send('inbox-setup', $message);
            
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
            $cancellationDetails = '';
            if ($poolOrder->meta && is_array($poolOrder->meta) && isset($poolOrder->meta['cancellation'])) {
                $cancellation = $poolOrder->meta['cancellation'];
                $cancelledBy = $cancellation['cancelled_by_name'] ?? 'Unknown';
                $cancelledAt = $cancellation['cancelled_at'] ?? now()->toDateTimeString();
                $reason = $cancellation['reason'] ?? $poolOrder->reason ?? 'No reason provided';
                
                $cancellationDetails = "\n*Cancelled By:* {$cancelledBy}\n*Cancelled At:* {$cancelledAt}\n*Reason:* {$reason}";
            } elseif ($poolOrder->reason) {
                $cancellationDetails = "\n*Reason:* {$poolOrder->reason}";
            }

            // Calculate refund amount or subscription details
            $subscriptionInfo = '';
            if ($poolOrder->chargebee_subscription_id) {
                $subscriptionInfo = "\n*ChargeBee Subscription:* {$poolOrder->chargebee_subscription_id}";
            }

            // Prepare domain list
            $domainsList = '';
            if ($poolOrder->domains && is_array($poolOrder->domains) && count($poolOrder->domains) > 0) {
                $domainNames = array_map(function($domain) {
                    return $domain['domain_name'] ?? 'Unknown';
                }, array_slice($poolOrder->domains, 0, 3)); // Show first 3 domains
                
                $domainsList = "\n*Affected Domains:* " . implode(', ', $domainNames);
                
                if (count($poolOrder->domains) > 3) {
                    $remaining = count($poolOrder->domains) - 3;
                    $domainsList .= " (and {$remaining} more)";
                }
            }

            $message = [
                'text' => '🚫 Pool Order Cancelled',
                'blocks' => [
                    [
                        'type' => 'header',
                        'text' => [
                            'type' => 'plain_text',
                            'text' => '🚫 Pool Order Cancelled',
                            'emoji' => true
                        ]
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Order ID:*\n#{$poolOrder->id}"
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Status:*\n❌ Cancelled"
                            ],
                            // [
                            //     'type' => 'mrkdwn',
                            //     'text' => "*Customer:*\n{$user->first_name} {$user->last_name}"
                            // ],
                            // [
                            //     'type' => 'mrkdwn',
                            //     'text' => "*Email:*\n{$user->email}"
                            // ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Plan:*\n{$poolOrder->poolPlan->name}"
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Amount:*\n$" . number_format($poolOrder->amount, 2)
                            ]
                        ]
                    ],
                    [
                        'type' => 'section',
                        'fields' => [
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Domains Count:*\n{$poolOrder->selected_domains_count}"
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Total Inboxes:*\n{$poolOrder->total_inboxes}"
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Order Date:*\n" . $poolOrder->created_at->format('M d, Y')
                            ],
                            [
                                'type' => 'mrkdwn',
                                'text' => "*Duration:*\n" . $poolOrder->created_at->diffForHumans(now(), true)
                            ]
                        ]
                    ]
                ]
            ];

            // Add cancellation details section
            if ($cancellationDetails || $subscriptionInfo || $domainsList) {
                $detailsText = "📋 *Cancellation Details*" . $cancellationDetails . $subscriptionInfo . $domainsList;
                
                $message['blocks'][] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => $detailsText
                    ]
                ];
            }

            // Add divider
            $message['blocks'][] = [
                'type' => 'divider'
            ];

            // Add important note about domain status
            if ($poolOrder->selected_domains_count > 0) {
                $message['blocks'][] = [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => "⚠️ *Action Required:* {$poolOrder->selected_domains_count} domain(s) have been freed and set to 'available' status in the pool."
                    ]
                ];
            }

            // Add context with timestamp
            $message['blocks'][] = [
                'type' => 'context',
                'elements' => [
                    [
                        'type' => 'mrkdwn',
                        'text' => "⏰ " . now()->format('F j, Y \a\t g:i A')
                    ]
                ]
            ];

            // Send to Slack - use 'inbox-cancellation' channel for cancellations
            $result = SlackNotificationService::send('inbox-cancellation', $message);
            
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
}