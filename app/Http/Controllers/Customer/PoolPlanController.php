<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\PoolPlan;
use App\Models\PoolOrder;
use App\Models\Pool;
use App\Models\User;
use App\Models\Invoice;
use App\Models\PoolInvoice;
use Illuminate\Http\Request;
use ChargeBee\ChargeBee\Models\HostedPage;
use ChargeBee\ChargeBee\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PoolPlanController extends Controller
{
    /**
     * Handle successful pool plan subscription
     */
    public function subscriptionSuccess(Request $request)
    {
        try {
            $hostedPageId = $request->query('id');
            
            if (!$hostedPageId) {
                return redirect()->route('login')->withErrors(['error' => 'Invalid subscription response']);
            }

            // Retrieve the hosted page from ChargeBee
            $result = HostedPage::retrieve($hostedPageId);
            $hostedPage = $result->hostedPage();

            if ($hostedPage->state !== 'succeeded') {
                return redirect()->route('customer.pool-subscription.cancel')
                    ->withErrors(['error' => 'Subscription was not completed successfully']);
            }

            // Get subscription and invoice data
            $subscription = $hostedPage->content()->subscription();
            $customer = $hostedPage->content()->customer();
            $invoice = $hostedPage->content()->invoice();
        
            // Get authenticated user
            $user = Auth::user();

            // First check if user exists in system by email, if not logged in
            if (!$user) {
                $existingUser = User::where('email', $customer->email)->first();
                if ($existingUser) {
                    // Log in the existing user
                    Auth::login($existingUser);
                    $user = $existingUser;
                } else {
                    Log::warning('Pool subscription - User not found', [
                        'chargebee_email' => $customer->email
                    ]);
                    return redirect()->route('login')->withErrors(['error' => 'User account not found. Please register first.']);
                }
            } else {
                // Security check: Verify customer email matches authenticated user
                if ($customer->email !== $user->email) {
                    Log::warning('Pool subscription security mismatch', [
                        'chargebee_email' => $customer->email,
                        'user_email' => $user->email,
                        'user_id' => $user->id
                    ]);
                    return redirect()->route('login')->withErrors(['error' => 'Security verification failed']);
                }
            }
            // dd($subscription, $customer, $invoice, $user);
            // Get pool plan from ChargeBee subscription plan ID
            $subscriptionItems = $subscription->subscriptionItems;
            $chargebeePlanId = $subscriptionItems[0]->itemPriceId ?? null;
            $quantity = $subscriptionItems[0]->quantity ?? 1;
            $poolPlan = PoolPlan::where('chargebee_plan_id', $chargebeePlanId)->first();
            
            if (!$poolPlan) {
                Log::error('Pool plan not found for ChargeBee plan ID: ' . $chargebeePlanId);
                return redirect()->route('login')->withErrors(['error' => 'Pool plan not found']);
            }

            // Update user's ChargeBee customer ID if not set
            if (!$user->chargebee_customer_id) {
                $user->update(['chargebee_customer_id' => $customer->id]);
            }

            // Create or update pool order
            $poolOrder = $this->createPoolOrder($subscription, $customer, $invoice, $poolPlan, $user);

            // Create invoice record
            $poolInvoice = $this->createInvoiceRecord($invoice, $poolOrder, $user);

            // Send notification emails
            $this->sendPoolOrderNotifications($poolOrder, $user);

            return view('customer.pool-plans.subscription-success', compact('poolOrder', 'poolPlan', 'user', 'poolInvoice'));

        } catch (\Exception $e) {
            Log::error('Pool Subscription Success Error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('login')->withErrors([
                'error' => 'Failed to process pool subscription: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Handle cancelled pool plan subscription
     */
    public function subscriptionCancel(Request $request)
    {
        // Clear static plan session data if exists
        session()->forget(['pool_static_plan_data', 'static_type']);

        return view('customer.pool-plans.subscription-cancel');
    }

    /**
     * Create pool order record
     */
    private function createPoolOrder($subscription, $customer, $invoice, $poolPlan, $user)
    {
        // Check if order already exists
        $existingOrder = PoolOrder::where('chargebee_subscription_id', $subscription->id)->first();
        
        if ($existingOrder) {
            return $existingOrder;
        }

        // Extract quantity from subscription items
        $quantity = 1; // Default quantity
        if (isset($subscription->subscriptionItems) && is_array($subscription->subscriptionItems)) {
            foreach ($subscription->subscriptionItems as $item) {
                if (isset($item->quantity)) {
                    $quantity = $item->quantity;
                    break; // Use the first item's quantity
                }
            }
        }

        // Create new pool order
        $poolOrder = PoolOrder::create([
            'user_id' => $user->id,
            'pool_plan_id' => $poolPlan->id,
            'quantity' => $quantity,
            'chargebee_subscription_id' => $subscription->id,
            'chargebee_customer_id' => $customer->id,
            'chargebee_invoice_id' => $invoice->id,
            'amount' => $invoice->total / 100, // Convert cents to dollars
            'currency' => $invoice->currencyCode,
            'status' => 'completed',
            'status_manage_by_admin' => 'pending', // Default status
            'paid_at' => Carbon::createFromTimestamp($invoice->paidAt)->toDateTimeString(),
            'meta' => json_encode([
                'subscription_data' => [
                    'id' => $subscription->id,
                    'plan_id' => $subscription->planId,
                    'status' => $subscription->status,
                    'current_term_start' => $subscription->currentTermStart,
                    'current_term_end' => $subscription->currentTermEnd,
                    'trial_end' => $subscription->trialEnd ?? null,
                    'billing_period' => $subscription->billingPeriod ?? null,
                    'billing_period_unit' => $subscription->billingPeriodUnit ?? null,
                ],
                'customer_data' => [
                    'id' => $customer->id,
                    'email' => $customer->email,
                    'first_name' => $customer->firstName,
                    'last_name' => $customer->lastName ?? '',
                ],
                'invoice_data' => [
                    'id' => $invoice->id,
                    'total' => $invoice->total,
                    'amount_paid' => $invoice->amountPaid,
                    'currency_code' => $invoice->currencyCode,
                    'status' => $invoice->status,
                    'paid_at' => $invoice->paidAt,
                ],
                'order_details' => [
                    'quantity' => $quantity,
                    'unit_price' => ($invoice->total / 100) / $quantity, // Calculate unit price
                ],
                'pool_plan_type' => 'static_link_subscription',
                'independent_from_master' => true
            ])
        ]);

        return $poolOrder;
    }

    /**
     * Create pool invoice record
     */
    private function createInvoiceRecord($invoice, $poolOrder, $user)
    {
        // Check if pool invoice already exists
        $existingInvoice = PoolInvoice::where('chargebee_invoice_id', $invoice->id)->first();
        
        if (!$existingInvoice) {
            $invoiceRecord = PoolInvoice::create([
                'user_id' => $user->id,
                'pool_order_id' => $poolOrder->id, // Pool order ID
                'chargebee_invoice_id' => $invoice->id,
                'chargebee_customer_id' => $invoice->customerId,
                'amount' => $invoice->total / 100,
                'currency' => $invoice->currencyCode,
                'status' => $invoice->status,
                'paid_at' => Carbon::createFromTimestamp($invoice->paidAt)->toDateTimeString(),
                'meta' => [
                    'invoice_data' => [
                        'id' => $invoice->id,
                        'total' => $invoice->total,
                        'amount_paid' => $invoice->amountPaid,
                        'currency_code' => $invoice->currencyCode,
                        'status' => $invoice->status,
                        'paid_at' => $invoice->paidAt,
                    ]
                ]
            ]);

            return $invoiceRecord;
        }

        return $existingInvoice;
    }

    /**
     * Send pool order notification emails
     */
    private function sendPoolOrderNotifications($poolOrder, $user)
    {
        try {
            // Send order confirmation email to user
            // Mail::to($user->email)->send(new PoolOrderCreatedMail($poolOrder, $user));

            // Send admin notification
            // $adminEmail = config('mail.admin_email', 'admin@example.com');
            // Mail::to($adminEmail)->send(new AdminPoolOrderNotificationMail($poolOrder, $user));

            Log::info('Pool order notifications sent', [
                'pool_order_id' => $poolOrder->id,
                'user_id' => $user->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send pool order notifications: ' . $e->getMessage());
        }
    }

    /**
     * List pool orders for a user
     */
    public function myPoolOrders()
    {
        // DataTables will handle data loading via AJAX
        return view('customer.pool-orders.index');
    }

    /**
     * Show specific pool order details
     */
    public function showPoolOrder($id)
    {
        $user = Auth::user();
        $poolOrder = PoolOrder::where('user_id', $user->id)
            ->where('id', $id)
            ->with(['poolPlan', 'poolInvoices'])
            ->firstOrFail();

        return view('customer.pool-orders.show', compact('poolOrder'));
    }
     
    /**
     * AJAX: Get pool orders for DataTables
     */
    public function getPoolOrdersData(Request $request)
    {
        $user = Auth::user();
        $query = PoolOrder::with('poolPlan')
            ->where('user_id', $user->id)
            ->select('pool_orders.*');

        return \DataTables::eloquent($query)
            ->addColumn('pool_plan', function($order) {
                return $order->poolPlan->name ?? 'N/A';
            })
            ->addColumn('capacity', function($order) {
                return $order->poolPlan->capacity ?? 'N/A';
            })
            ->addColumn('quantity', function($order) {
                return $order->quantity ?? 1;
            })
            ->addColumn('domains', function($order) {
                $domainCount = $order->selected_domains_count;
                $totalInboxes = $order->total_inboxes;
                
                if ($domainCount > 0) {
                    return '<div class="text-center">
                        <span class="badge bg-primary">' . $domainCount . ' domains</span>
                        <br><small class="text-muted">' . $totalInboxes . ' inboxes</small>
                    </div>';
                } else {
                    return '<div class="text-center">
                        <span class="badge bg-light text-muted">Not configured</span>
                    </div>';
                }
            })
            ->addColumn('amount', function($order) {
                return '$' . number_format($order->amount, 2);
            })
            ->addColumn('status', function($order) {
                return $order->status_badges;
            })
            ->addColumn('order_date', function($order) {
                return $order->created_at->format('M d, Y') . '<br><small class="text-muted">' . $order->created_at->format('h:i A') . '</small>';
            })
            ->addColumn('actions', function($order) {
                $viewUrl = route('customer.pool-orders.show', $order->id);
                $editUrl = route('customer.pool-orders.edit', $order->id);
                $cancelUrl = route('customer.pool-orders.cancel', $order->id);
                
                // Check if order can be cancelled (not already cancelled)
                $canCancel = $order->status !== 'cancelled' && $order->status_manage_by_admin !== 'cancelled';
                
                // Check if order can be edited (only pending orders)
                $canEdit = $order->status_manage_by_admin === 'pending';
                
                $cancelButton = '';
                if ($canCancel) {
                    $cancelButton = '<li>
                            <a class="dropdown-item text-danger" href="javascript:void(0)" onclick="cancelPoolSubscription(\'' . $order->id . '\')">
                                <i class="ti ti-x me-1"></i>Cancel Subscription
                            </a>
                        </li>';
                }
                
                $editButton = '';
                if ($canEdit) {
                    $editButton = '<li>
                            <a class="dropdown-item" href="' . $editUrl . '">
                                <i class="ti ti-edit me-1"></i>Edit Domains
                            </a>
                        </li>';
                }
                
                return '
                <div class="dropdown">
                    <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="' . $viewUrl . '">
                                <i class="ti ti-eye me-1"></i>View
                            </a>
                        </li>
                        ' . $editButton . '
                        <li>
                            <a class="dropdown-item" href="javascript:void(0)" onclick="downloadInvoice(\'' . $order->id . '\')">
                                <i class="ti ti-download me-1"></i>Download
                            </a>
                        </li>
                        ' . $cancelButton . '
                    </ul>
                </div>
                ';
            })
            ->rawColumns(['status', 'domains', 'order_date', 'actions'])
            ->make(true);
    }

    /**
     * Show pool order edit form for domain selection
     */
    
    public function editPoolOrder($id, Request $request)
    {
        $user = Auth::user();
        $poolOrder = PoolOrder::where('user_id', $user->id)
            ->where('id', $id)
            ->with(['poolPlan'])
            ->firstOrFail();

        // Check if order status is pending - only pending orders can be edited
        if ($poolOrder->status_manage_by_admin !== 'pending') {
            Log::warning('Attempt to edit non-pending pool order', [
                'pool_order_id' => $poolOrder->id,
                'status' => $poolOrder->status,
                'status_manage_by_admin' => $poolOrder->status_manage_by_admin,
                'user_id' => $user->id
            ]);

            return redirect()
                ->route('customer.pool-orders.show', $poolOrder->id)
                ->with('error', 'This order cannot be edited. Only pending orders are editable.');
        }

        // Handle AJAX request for domains with server-side pagination
        if ($request->ajax() || $request->wantsJson()) {
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 50);
            $search = $request->get('search', '');

            return response()->json($this->getAvailableDomainsWithPagination($page, $perPage, $search));
        }

        // ========================================
        // PLATFORM SETTINGS CONFIGURATION
        // ========================================
        // Toggle these flags to enable/disable platform sections in the edit page
        // Set to true to show the section, false to hide it

        // ========================================
        
        // Settings flags for enabling/disabling platform sections
        $enableHostingPlatform = false; // Set to false to disable Domain Hosting Platform section
        $enableSendingPlatform = true; // Set to false to disable Cold Email Platform section
        
        // Get hosting platforms for the dropdown (only if enabled)
        $hostingPlatforms = [];
        if ($enableHostingPlatform) {
            $hostingPlatforms = \App\Models\HostingPlatform::where('is_active', true)
                ->orderBy('sort_order')
                ->get();
        }
        
        // Get sending platforms for the dropdown (only if enabled)
        $sendingPlatforms = [];
        if ($enableSendingPlatform) {
            $sendingPlatforms = \App\Models\SendingPlatform::orderBy('name')
                ->get();
        }

        // Get active trial-new-order disclaimer
        $trialNewOrderDisclaimer = \App\Models\Disclaimer::getActiveByType('trial-new-order');

        // Get full domain details for existing selections (with prefix_variants)
        $existingDomainDetails = [];
        
        Log::info('Pool Order Domains:', ['domains' => $poolOrder->domains]);
        
        if ($poolOrder->domains && is_array($poolOrder->domains)) {
            Log::info('Processing existing domains', ['count' => count($poolOrder->domains)]);
            
            foreach ($poolOrder->domains as $savedDomain) {
                $poolId = $savedDomain['pool_id'] ?? null;
                $domainId = $savedDomain['domain_id'] ?? null;
                
                Log::info('Processing domain:', ['pool_id' => $poolId, 'domain_id' => $domainId]);
                
                if ($poolId && $domainId) {
                    $pool = \App\Models\Pool::find($poolId);
                    if ($pool && $pool->domains) {
                        $poolDomains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
                        
                        Log::info('Pool domains:', ['pool_id' => $poolId, 'domains_count' => count($poolDomains)]);
                        
                        // Find the specific domain in pool's domains
                        // Pool domains use 'id' field, not 'domain_id'
                        
                        foreach ($poolDomains as $poolDomain) {
                            $poolDomainId = $poolDomain['id'] ?? $poolDomain['domain_id'] ?? null;
                            
                            if ($poolDomainId && $poolDomainId == $domainId) {
                                
                                // Get prefix_variants from Pool level (not individual domain)
                                $prefixVariants = $pool->prefix_variants ?? [];
                                
                                $domainDetail = [
                                    'id' => $domainId,
                                    'domain_id' => $domainId,
                                    'pool_id' => $poolId,
                                    'name' => $poolDomain['name'] ?? $savedDomain['domain_name'],
                                    'available_inboxes' => $savedDomain['per_inbox'],
                                    'prefix_variants' => $prefixVariants
                                ];
                                
                                $existingDomainDetails[] = $domainDetail;
                                Log::info('Added domain to existing details:', $domainDetail);
                                break;
                            }
                        }
                    } else {
                        Log::warning('Pool not found or has no domains', ['pool_id' => $poolId]);
                    }
                } else {
                    Log::warning('Missing pool_id or domain_id in saved domain', $savedDomain);
                }
            }
        } else {
            Log::info('No domains in pool order or not an array');
        }
        
        Log::info('Final existing domain details:', ['count' => count($existingDomainDetails), 'details' => $existingDomainDetails]);

        // For regular page load, don't load all domains immediately
        // Pass empty array for initial load to avoid template errors
        $availableDomains = [];
        return view('customer.pool-orders.edit', compact(
            'poolOrder', 
            'availableDomains', 
            'hostingPlatforms', 
            'sendingPlatforms', 
            'existingDomainDetails',
            'enableHostingPlatform',
            'enableSendingPlatform',
            'trialNewOrderDisclaimer'
        ));
    }

    /**
     * Update pool order with selected domains
     */
    
    public function updatePoolOrder(Request $request, $id)
    {
        $user = Auth::user();
        $poolOrder = PoolOrder::where('user_id', $user->id)
            ->where('id', $id)
            ->firstOrFail();

        // Check if order status is pending - only pending orders can be updated
        if ($poolOrder->status_manage_by_admin !== 'pending') {
            Log::warning('Attempt to update non-pending pool order', [
                'pool_order_id' => $poolOrder->id,
                'status' => $poolOrder->status,
                'status_manage_by_admin' => $poolOrder->status_manage_by_admin,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'This order cannot be updated. Only pending orders are editable.'
            ], 403);
        }

        // Store previously selected domains BEFORE any processing
        $previousDomains = $poolOrder->domains ?? [];

        // Validation rules
        $rules = [
            'domains' => 'required|array|min:1',
            'domains.*' => 'required|string',
            'hosting_platform' => 'nullable|string|max:255',
        ];

        // Validate
        $validated = $request->validate($rules);

        // Check if selected domains count exceeds quantity
        if (count($request->domains) > $poolOrder->quantity) {
            return response()->json([
                'success' => false,
                'message' => "You can only select up to {$poolOrder->quantity} domains (your order quantity)."
            ], 422);
        }

        // Get available domains to map selected IDs to actual pool data
        $availableDomains = $this->getAvailableDomainsOptimized();
        $selectedDomainIds = $request->domains;
        // dd($availableDomains, $selectedDomainIds);
        // Map selected domain IDs to their corresponding pool data
        $selectedDomains = [];
        $poolIds = [];
        $totalInboxes = 0;
        
        Log::info('Selected domain IDs:', $selectedDomainIds);
        Log::info('Available domains count:', ['count' => count($availableDomains)]);
        
        foreach ($selectedDomainIds as $domainId) {
            foreach ($availableDomains as $domain) {
                // Compare as strings since domain IDs are strings like "1000_1"
                if ((string)$domain['id'] === (string)$domainId) {
                    $selectedDomains[] = $domain;
                    $poolIds[] = $domain['pool_id'];
                    $totalInboxes += $domain['available_inboxes'];
                    Log::info('Found matching domain:', ['domain_id' => $domainId, 'domain_name' => $domain['name']]);
                    break;
                }
            }
        }
        
        Log::info('Selected domains found:', ['count' => count($selectedDomains)]);
        
        // Check if no domains were found
        if (empty($selectedDomains)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid domains found for the selected IDs. Please refresh the page and try again.'
            ], 422);
        }
        
        // Check if total inboxes exceed quantity limit
        if ($totalInboxes > $poolOrder->quantity) {
            return response()->json([
                'success' => false,
                'message' => "Total inboxes ({$totalInboxes}) cannot exceed your order quantity ({$poolOrder->quantity}). Please select domains with fewer inboxes."
            ], 422);
        }

        try {
            // Prepare domains data for saving
            $domainsData = [];
            foreach ($selectedDomains as $domain) {
                $domainsData[] = [
                    'domain_id' => $domain['id'], // Use unique domain ID
                    'pool_id' => $domain['pool_id'], // Store pool reference
                    'domain_name' => $domain['name'], // Store domain name
                    'per_inbox' => $domain['available_inboxes']
                ];
            }

            Log::info('Domains data prepared for saving:', $domainsData);

            // Update domain usage in pools using previously stored domain selection
            $this->updateDomainUsageInPools($previousDomains, $selectedDomains);

            // Set domains from form data
            $poolOrder->setDomainsFromForm($domainsData);
            
            // Save hosting platform data
            if ($request->has('hosting_platform')) {
                $poolOrder->hosting_platform = $request->hosting_platform;
                
                // Collect all dynamic hosting platform fields
                $hostingPlatformData = [];
                
                // Get the hosting platform configuration
                $hostingPlatform = \App\Models\HostingPlatform::where('value', $request->hosting_platform)->first();
                
                if ($hostingPlatform && $hostingPlatform->fields) {
                    // Iterate through defined fields and collect their values
                    foreach ($hostingPlatform->fields as $fieldName => $fieldConfig) {
                        if ($request->has($fieldName)) {
                            $value = $request->input($fieldName);
                            
                            // Special handling for Namecheap backup codes - save as comma-separated array
                            if ($fieldName === 'backup_codes' && $request->hosting_platform === 'namecheap') {
                                // Split by newlines and filter empty values
                                $codes = array_filter(array_map('trim', explode("\n", $value)));
                                // Join with comma and space
                                $value = implode(', ', $codes);
                            }
                            
                            $hostingPlatformData[$fieldName] = $value;
                        }
                    }
                }
                
                // Store all collected data as JSON
                $poolOrder->hosting_platform_data = $hostingPlatformData;
                
                Log::info('Hosting platform data saved:', [
                    'platform' => $poolOrder->hosting_platform,
                    'data' => $hostingPlatformData
                ]);
            }
            
            // Save sending platform data
            if ($request->has('sending_platform')) {
                $poolOrder->sending_platform = $request->sending_platform;
                
                // Collect all dynamic sending platform fields
                $sendingPlatformData = [];
                
                // Get the sending platform configuration
                $sendingPlatform = \App\Models\SendingPlatform::where('value', $request->sending_platform)->first();
                
                if ($sendingPlatform && $sendingPlatform->fields) {
                    // Iterate through defined fields and collect their values
                    foreach ($sendingPlatform->fields as $fieldName => $fieldConfig) {
                        if ($request->has($fieldName)) {
                            $sendingPlatformData[$fieldName] = $request->input($fieldName);
                        }
                    }
                }
                
                // Store all collected data as JSON
                $poolOrder->sending_platform_data = $sendingPlatformData;
                
                Log::info('Sending platform data saved:', [
                    'platform' => $poolOrder->sending_platform,
                    'data' => $sendingPlatformData
                ]);
            }
            
            Log::info('Before saving pool order - domains:', ['domains' => $poolOrder->domains]);
            
            // Update status to in-progress when configuration is saved
            // Observer will handle Slack notification automatically
            $poolOrder->status_manage_by_admin = 'in-progress';
            
            $poolOrder->save();
            
            Log::info('After saving pool order - domains and status:', [
                'domains' => $poolOrder->fresh()->domains,
                'status_manage_by_admin' => $poolOrder->status_manage_by_admin
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Configuration saved successfully',
                'total_domains' => $poolOrder->selected_domains_count,
                'total_inboxes' => $poolOrder->total_inboxes,
                'hosting_platform' => $poolOrder->hosting_platform,
                'sending_platform' => $poolOrder->sending_platform
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update pool order domains: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update domains: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available domains for pool order selection from pools table
     */
    private function getAvailableDomains()
    {
        // Get pools that are available and have domains
        $pools = Pool::where('status_manage_by_admin', 'available')
            ->whereNotNull('domains')
            ->whereNotNull('inboxes_per_domain')
            ->where('inboxes_per_domain', '>', 0)
            ->select('id', 'domains', 'inboxes_per_domain', 'prefix_variants')
            ->get();
        // dd($pools);
        $availableDomains = [];
        
        foreach ($pools as $pool) {
            // Decode domains if it's JSON string, otherwise treat as array
            $domains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
            
            if (is_array($domains)) {
                foreach ($domains as $domainIndex => $domain) {
                    // Handle domains as objects with id, name, is_used fields
                    if (is_array($domain) && isset($domain['id'], $domain['name'])) {
                        // Use the actual domain ID from database
                        $domainId = $domain['id'];
                        $domainName = $domain['name'];
                        $isUsed = $domain['is_used'] ?? false;
                        
                        // Only include domains that are not used
                        if (!$isUsed) {
                            $availableDomains[] = [
                                'id' => $domainId, // Use actual domain ID from database
                                'pool_id' => $pool->id, // Reference to pool
                                'domain_index' => $domainIndex, // Index within pool
                                'name' => $domainName,
                                'status' => $domain['status'] ?? 'active',
                                'available_inboxes' => $pool->inboxes_per_domain,
                                'prefix_variants' => $pool->prefix_variants ?? []
                            ];
                        }
                    }
                }
            } elseif (is_string($domains)) {
                // Handle single domain as string
                $availableDomains[] = [
                    'id' => $domainCounter++, // Unique domain ID
                    'pool_id' => $pool->id, // Reference to pool
                    'domain_index' => 0, // Single domain index
                    'name' => $domains,
                    'status' => 'active',
                    'available_inboxes' => $pool->inboxes_per_domain
                ];
            }
        }

        // Remove duplicates based on domain name and keep the one with highest available inboxes
        $uniqueDomains = [];
        foreach ($availableDomains as $domain) {
            $key = $domain['name'];
            if (!isset($uniqueDomains[$key]) || $uniqueDomains[$key]['available_inboxes'] < $domain['available_inboxes']) {
                $uniqueDomains[$key] = $domain;
            }
        }

        return array_values($uniqueDomains);
    }

    /**
     * Get available domains optimized for large datasets
     */
    private function getAvailableDomainsOptimized()
    {
        // Use database query with indexes for better performance
        $pools = Pool::select('id', 'domains', 'inboxes_per_domain', 'prefix_variants')
            ->where('status_manage_by_admin', 'available')
            ->whereNotNull('domains')
            ->whereNotNull('inboxes_per_domain')
            ->where('inboxes_per_domain', '>', 0)
            ->get();

        $availableDomains = [];
        
        foreach ($pools as $pool) {
            // Use direct array access for better performance
            $domains = $pool->domains;
            
            if (is_array($domains)) {
                foreach ($domains as $domainIndex => $domain) {
                    if (is_array($domain) && isset($domain['id'], $domain['name'])) {
                        $isUsed = $domain['is_used'] ?? false;
                        
                        // Only include unused domains
                        if (!$isUsed) {
                            $availableDomains[] = [
                                'id' => $domain['id'],
                                'pool_id' => $pool->id,
                                'name' => $domain['name'],
                                'status' => $domain['status'] ?? 'active',
                                'available_inboxes' => $pool->inboxes_per_domain,
                                'prefix_variants' => $pool->prefix_variants ?? []
                            ];
                        }
                    }
                }
            }
        }

        // Sort by domain name for consistent ordering
        usort($availableDomains, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        return $availableDomains;
    }

    /**
     * Get available domains with server-side pagination
     */
    private function getAvailableDomainsWithPagination($page = 1, $perPage = 50, $search = '')
    {
        $query = Pool::select('id', 'domains', 'inboxes_per_domain', 'prefix_variants')
            ->where('status_manage_by_admin', 'available')
            ->whereNotNull('domains')
            ->whereNotNull('inboxes_per_domain')
            ->where('inboxes_per_domain', '>', 0);

        // Get all pools first to process domains
        $pools = $query->get();
        $availableDomains = [];
        
        foreach ($pools as $pool) {
            $domains = $pool->domains;
            
            if (is_array($domains)) {
                foreach ($domains as $domainIndex => $domain) {
                    if (is_array($domain) && isset($domain['id'], $domain['name'])) {
                        $isUsed = $domain['is_used'] ?? false;
                        
                        // Only include unused domains
                        if (!$isUsed) {
                            // Apply search filter
                            if (empty($search) || stripos($domain['name'], $search) !== false) {
                                $availableDomains[] = [
                                    'id' => $domain['id'],
                                    'pool_id' => $pool->id,
                                    'name' => $domain['name'],
                                    'status' => $domain['status'] ?? 'active',
                                    'available_inboxes' => $pool->inboxes_per_domain,
                                    'prefix_variants' => $pool->prefix_variants ?? []
                                ];
                            }
                        }
                    }
                }
            }
        }

        // Sort by domain name for consistent ordering
        usort($availableDomains, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        // Manual pagination
        $total = count($availableDomains);
        $lastPage = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($availableDomains, $offset, $perPage);
        
        return [
            'data' => $paginatedData,
            'current_page' => (int) $page,
            'last_page' => $lastPage,
            'per_page' => (int) $perPage,
            'total' => $total,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total)
        ];
    }

    /**
     * Update domain usage in pools - mark new domains as used and unmark deselected domains as unused
     */
    private function updateDomainUsageInPools($previousDomains, $selectedDomains)
    {
        Log::info('=== updateDomainUsageInPools START ===');
        Log::info('Input data:', ['previousDomains' => $previousDomains, 'selectedDomains' => $selectedDomains]);
        
        // Get previously selected domain IDs from the saved domains
        $previouslySelectedDomainIds = [];
        if ($previousDomains && is_array($previousDomains)) {
            foreach ($previousDomains as $domain) {
                if (isset($domain['domain_id'])) {
                    $previouslySelectedDomainIds[] = $domain['domain_id'];
                }
            }
        }

        // Get currently selected domain IDs from the array of domain objects
        $currentlySelectedDomainIds = [];
        
        Log::info('Selected domains array details:', [
            'type' => gettype($selectedDomains),
            'count' => is_array($selectedDomains) ? count($selectedDomains) : 'not_array',
            'keys' => is_array($selectedDomains) ? array_keys($selectedDomains) : 'not_array',
            'first_element' => is_array($selectedDomains) && !empty($selectedDomains) ? reset($selectedDomains) : 'empty_or_not_array'
        ]);
        
        if (is_array($selectedDomains)) {
            foreach ($selectedDomains as $index => $domain) {
                Log::info('Processing selected domain:', ['index' => $index, 'domain' => $domain]);
                
                // Try multiple ways to extract domain ID
                $domainId = null;
                if (is_array($domain)) {
                    if (isset($domain['id'])) {
                        $domainId = $domain['id'];
                    } elseif (isset($domain['domain_id'])) {
                        $domainId = $domain['domain_id'];
                    }
                } elseif (is_string($domain)) {
                    $domainId = $domain;
                }
                
                if ($domainId !== null) {
                    $currentlySelectedDomainIds[] = $domainId;
                    Log::info('Added domain ID:', ['domain_id' => $domainId]);
                } else {
                    Log::warning('Could not extract domain ID:', ['domain' => $domain, 'type' => gettype($domain)]);
                }
            }
        } else {
            Log::error('Selected domains is not an array:', ['selectedDomains' => $selectedDomains]);
        }

        Log::info('Domain usage update:', [
            'previously_selected' => $previouslySelectedDomainIds,
            'currently_selected' => $currentlySelectedDomainIds
        ]);

        // Find domains that were deselected (previously selected but not currently selected)
        $deselectedDomainIds = array_diff($previouslySelectedDomainIds, $currentlySelectedDomainIds);
        
        // Find domains that were newly selected (currently selected but not previously selected)
        $newlySelectedDomainIds = array_diff($currentlySelectedDomainIds, $previouslySelectedDomainIds);

        Log::info('Domain changes:', [
            'deselected' => $deselectedDomainIds,
            'newly_selected' => $newlySelectedDomainIds
        ]);

        // Get all pools that might contain the domains we need to update
        $allRelevantDomainIds = array_merge($previouslySelectedDomainIds, $currentlySelectedDomainIds);
        
        if (!empty($allRelevantDomainIds)) {
            $pools = Pool::whereNotNull('domains')->get();
            
            foreach ($pools as $pool) {
                Log::info('Processing pool:', ['pool_id' => $pool->id]);
                
                // Get the domains array - force fresh decode
                $domains = $pool->domains;
                if (is_string($domains)) {
                    $domains = json_decode($domains, true);
                }
                
                Log::info('Pool domains before update:', ['pool_id' => $pool->id, 'domains_count' => is_array($domains) ? count($domains) : 'not_array']);
                
                $hasChanges = false;
                $updatedDomains = [];
                
                if (is_array($domains)) {
                    // Create a new array instead of using references
                    foreach ($domains as $index => $domain) {
                        $updatedDomain = $domain; // Copy the domain
                        
                        if (is_array($domain) && isset($domain['id'])) {
                            $domainId = $domain['id'];
                            
                            // Mark deselected domains as unused
                            if (in_array($domainId, $deselectedDomainIds)) {
                                $updatedDomain['is_used'] = false;
                                // Set status based on usage - when not used, revert to available (or warming if not set)
                                $updatedDomain['status'] = $updatedDomain['status'] ?? 'warming';
                                if ($updatedDomain['status'] === 'subscribed') {
                                    $updatedDomain['status'] = 'available';
                                }
                                $hasChanges = true;
                                Log::info('Marking domain as unused:', ['domain_id' => $domainId, 'pool_id' => $pool->id]);
                            }
                            
                            // Mark newly selected domains as used
                            if (in_array($domainId, $newlySelectedDomainIds)) {
                                $updatedDomain['is_used'] = true;
                                // Set status to subscribed when used
                                $updatedDomain['status'] = 'subscribed';
                                $hasChanges = true;
                                Log::info('Marking domain as used and subscribed:', ['domain_id' => $domainId, 'pool_id' => $pool->id]);
                            }
                        }
                        
                        $updatedDomains[] = $updatedDomain;
                    }
                    
                    // Save pool only if there were changes
                    if ($hasChanges) {
                        Log::info('Before saving pool:', ['pool_id' => $pool->id, 'changes_detected' => true]);
                        
                        // Force update using DB query to avoid model casting issues
                        DB::table('pools')
                            ->where('id', $pool->id)
                            ->update([
                                'domains' => json_encode($updatedDomains),
                                'updated_at' => now()
                            ]);
                        
                        Log::info('Pool updated via DB query:', ['pool_id' => $pool->id]);
                        
                        // Verify the update
                        $verifyPool = Pool::find($pool->id);
                        $verifyDomains = $verifyPool->domains;
                        
                        Log::info('Pool update verification:', [
                            'pool_id' => $pool->id,
                            'domains_updated' => is_array($verifyDomains) ? count($verifyDomains) : 'not_array',
                            'sample_domain' => is_array($verifyDomains) && !empty($verifyDomains) ? $verifyDomains[0] : 'no_domains'
                        ]);
                    } else {
                        Log::info('No changes needed for pool:', ['pool_id' => $pool->id]);
                    }
                } else {
                    Log::warning('Pool domains is not an array:', ['pool_id' => $pool->id, 'domains' => $domains]);
                }
            }
        }
        
        Log::info('=== updateDomainUsageInPools END ===');
    }

    /**
     * Cancel pool order subscription
     */
    public function cancelPoolSubscription(Request $request, $id)
    {
        $user = Auth::user();
        $reason = $request->input('reason', 'Customer requested cancellation');

        // Use the PoolOrderCancelledService to handle cancellation
        $cancellationService = new \App\Services\PoolOrderCancelledService();
        $result = $cancellationService->cancelSubscription($id, $user->id, $reason);
        
        // Return appropriate HTTP status code based on result
        if ($result['success']) {
            return response()->json($result);
        } else {
            // Determine appropriate HTTP status code based on error message
            $statusCode = 500; // Default to internal server error
            
            if (str_contains($result['message'], 'already cancelled')) {
                $statusCode = 400; // Bad request
            } elseif (str_contains($result['message'], 'Failed to find')) {
                $statusCode = 404; // Not found
            } elseif (str_contains($result['message'], 'No ChargeBee subscription')) {
                $statusCode = 400; // Bad request
            }
            
            return response()->json($result, $statusCode);
        }
    }
}
