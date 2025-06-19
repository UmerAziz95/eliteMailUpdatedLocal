<?php

namespace App\Http\Controllers\Contractor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\Status;
use App\Models\ReorderInfo;
use App\Models\HostingPlatform;
use App\Mail\OrderStatusChangeMail;
use Illuminate\Support\Facades\Mail;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\Invoice;
use Illuminate\Validation\ValidationException;
use App\Models\Subscription;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLogService;
use App\Models\OrderEmail;
use App\Models\Notification;
use App\Models\UserOrderPanelAssignment;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
class OrderController extends Controller
{
    private $statuses;
    // split statues
    private $splitStatuses = [
        'completed' => 'success',
        // 'unallocated' => 'warning',
        // 'allocated' => 'info',
        'rejected' => 'danger',
        'in-progress' => 'primary',
        // 'pending' => 'secondary'
    ];
    private $paymentStatuses = [
        "Pending" => "warning",
        "Paid" => "success",
        "Failed" => "danger",
        "Refunded" => "secondary"
    ];

    public function __construct()
    {
        $this->statuses = Status::where('name', '!=', 'draft')->pluck('badge', 'name')->toArray();
    }

    public function index()
    {
        $plans = Plan::where('is_active', true)->get();
        
        // Get orders that are either unassigned or assigned to the current contractor
        $orders = Order::where(function($query) {
            $query->whereNull('assigned_to')
                  ->orWhere('assigned_to', auth()->id());
        });
        
        $totalOrders = $orders->count();
        
        // Get orders by admin status using actual status names from Status model
        $pendingOrders = $orders->clone()->where('status_manage_by_admin', 'pending')->count();
        $completedOrders = $orders->clone()->where('status_manage_by_admin', 'completed')->count();
        $inProgressOrders = $orders->clone()->where('status_manage_by_admin', 'in-progress')->count();
        $expiredOrders = $orders->clone()->where('status_manage_by_admin', 'expired')->count();
        $rejectOrders = $orders->clone()->where('status_manage_by_admin', 'reject')->count();
        $cancelledOrders = $orders->clone()->where('status_manage_by_admin', 'cancelled')->count();
        $draftOrders = $orders->clone()->where('status_manage_by_admin', 'draft')->count();

        // Calculate percentage changes (last week vs previous week)
        $lastWeek = [Carbon::now()->subWeek(), Carbon::now()];
        $previousWeek = [Carbon::now()->subWeeks(2), Carbon::now()->subWeek()];

        $lastWeekOrders = $orders->clone()->whereBetween('created_at', $lastWeek)->count();
        $previousWeekOrders = $orders->clone()->whereBetween('created_at', $previousWeek)->count();

        $percentageChange = $previousWeekOrders > 0 
            ? (($lastWeekOrders - $previousWeekOrders) / $previousWeekOrders) * 100 
            : 0;

        $statuses = $this->statuses;
        $splitStatuses = $this->splitStatuses;
        $plans = [];
        return view('contractor.orders.orders', compact(
            'plans', 
            'totalOrders', 
            'pendingOrders',            'completedOrders',
            'inProgressOrders',
            'percentageChange',
            'statuses',
            'splitStatuses',
            'expiredOrders',
            'rejectOrders',
            'cancelledOrders',
            'draftOrders'
        ));
    }
    // neworder
    public function newOrder($id = 1)
    {
        $hostingPlatforms = HostingPlatform::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $plan = null; // No plan selected initially, will be determined based on inboxes
        if($id){
            $plan = Plan::findOrFail($id);
        } else {
            // If no plan is selected, default to the first plan
            $plan = Plan::first();
        }
        $order = null; // No existing order for new orders

        return view('contractor.orders.new-order', compact('plan', 'hostingPlatforms', 'order'));
    }
    public function reorder(Request $request, $order_id)
    {
        $order = Order::with(['plan', 'reorderInfo'])->findOrFail($order_id);
        $plan = $order->plan;
        $hostingPlatforms = HostingPlatform::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return view('contractor.orders.reorder', compact('plan', 'hostingPlatforms', 'order'));
    }

    private function calculateNextBillingDate($startTimestamp, $billingPeriod, $billingPeriodUnit)
    {
        $startDate = Carbon::createFromTimestamp($startTimestamp);
        $currentDate = Carbon::now();
        
        // Calculate how many billing periods have passed
        $diffUnit = match($billingPeriodUnit) {
            'month' => $startDate->diffInMonths($currentDate),
            'year' => $startDate->diffInYears($currentDate),
            'week' => $startDate->diffInWeeks($currentDate),
            'day' => $startDate->diffInDays($currentDate),
            default => 0
        };
        
        // Calculate how many complete billing periods have occurred
        $completePeriods = floor($diffUnit / $billingPeriod);
        // Add one more period to get the next billing date
        $totalPeriods = $completePeriods + 1;
        
        // Calculate next billing date from start date
        return match($billingPeriodUnit) {
            'month' => $startDate->copy()->addMonths($totalPeriods * $billingPeriod),
            'year' => $startDate->copy()->addYears($totalPeriods * $billingPeriod),
            'week' => $startDate->copy()->addWeeks($totalPeriods * $billingPeriod),
            'day' => $startDate->copy()->addDays($totalPeriods * $billingPeriod),
            default => $startDate
        };
    }

    private function formatTimestampToReadable($timestamp)
    {
        if (!$timestamp) return 'N/A';
        
        // If input is already a Carbon instance
        if ($timestamp instanceof \Carbon\Carbon) {
            return $timestamp->format('F d, Y');
        }
        
        // If input is a datetime string
        if (is_string($timestamp) && strtotime($timestamp) !== false) {
            return Carbon::parse($timestamp)->format('F d, Y');
        }
        
        // If input is a timestamp
        if (is_numeric($timestamp)) {
            return Carbon::createFromTimestamp($timestamp)->format('F d, Y');
        }
        
        return 'N/A';
    }

    public function view($id)
    {
        $order = Order::with([
            'subscription', 
            'user', 
            'invoice', 
            'reorderInfo',
            'plan',
            'orderPanels.userOrderPanelAssignments' => function($query) {
                $query->with(['orderPanel', 'orderPanelSplit'])
                      ->where('contractor_id', auth()->id());
            }
        ])->findOrFail($id);
        $order->status2 = strtolower($order->status_manage_by_admin);
        $order->color_status2 = $this->statuses[$order->status2] ?? 'secondary';
        
        
        // Retrieve subscription metadata if available
        $subscriptionMeta = json_decode($order->subscription->meta ?? '[]', true);
        $nextBillingInfo = [];
        
        if ($order->subscription) {
            $subscription = $subscriptionMeta['subscription'] ?? [];
            $startDate = $order->subscription->start_date;
            $endDate = $order->subscription->end_date;
            
            $periodStart = Carbon::parse($startDate);
            $periodEnd = $endDate ? Carbon::parse($endDate) : Carbon::now();
            
            // Get the billing period and unit from metadata
            $billingPeriod = $subscription['billing_period'] ?? 1;
            $billingPeriodUnit = $subscription['billing_period_unit'] ?? 'month';
            $nextBillingInfo = [
                'status' => $order->subscription->status,
                'billing_period' => $billingPeriod,
                'billing_period_unit' => $billingPeriodUnit,
                'current_term_start' => $startDate ? Carbon::parse($startDate)->format('F d, Y') : null,
                // 'current_term_end' => $endDate ? Carbon::parse($endDate)->format('F d, Y') : null,
                // 'next_billing_at' => $endDate ? null : Carbon::parse($startDate)
                //     ->add($billingPeriod, $billingPeriodUnit)
                //     ->format('F d, Y'),
                'current_term_end' => $order->subscription->last_billing_date ? Carbon::parse($order->subscription->last_billing_date)->format('F d, Y') : ($endDate ? Carbon::parse($endDate)->format('F d, Y') : null),
                'next_billing_at' => $order->subscription->next_billing_date ? Carbon::parse($order->subscription->next_billing_date)->format('F d, Y') : null
            ];
        }
    
        return view('contractor.orders.order-view', compact('order', 'nextBillingInfo'));
    }
    public function splitView($order_panel_id)
    {
        // Get the order panel with all necessary relationships including the order
        $orderPanel = OrderPanel::with([
            'order.user',
            'order.reorderInfo', 
            'order.plan',
            'orderPanelSplits', // Load the split relationship
            'order.userOrderPanelAssignments' => function($query) {
                $query->with(['orderPanel', 'orderPanelSplit'])
                      ->where('contractor_id', auth()->id());
            }
        ])->findOrFail($order_panel_id);
        
        // Get the order from the panel
        $order = $orderPanel->order;
        
        $order->status2 = strtolower($order->status_manage_by_admin);
        $order->color_status2 = $this->statuses[$order->status2] ?? 'secondary';
        
        // Add split status color to orderPanel
        $orderPanel->split_status_color = $this->splitStatuses[$orderPanel->status ?? 'pending'] ?? 'secondary';
        
        $splitStatuses = $this->splitStatuses;
        
        return view('contractor.orders.split-view', compact('order', 'orderPanel', 'splitStatuses'));
    }

    public function getOrders(Request $request)
    {
        try {
            $contractorId = auth()->id();
            
            // Determine if we need to join users table
            $needsUserJoin = ($request->has('email') && $request->email != '') || 
                           ($request->has('name') && $request->name != '');
            
            // Query from user_order_panel_assignment as main table to get assignments for logged-in contractor
            $assignments = UserOrderPanelAssignment::query()
                ->select('user_order_panel_assignment.*')
                ->join('orders', 'user_order_panel_assignment.order_id', '=', 'orders.id');
            
            // Join users table if needed for email/name filters
            if ($needsUserJoin) {
                $assignments->join('users', 'orders.user_id', '=', 'users.id');
            }
            
            $assignments = $assignments->with([
                    'order.user',
                    'order.plan', 
                    'order.reorderInfo',
                    'orderPanel.panel',
                    'orderPanelSplit'
                ])
                ->where('user_order_panel_assignment.contractor_id', $contractorId)
                ->where('orders.status_manage_by_admin', '!=', 'draft')
                ->orderBy('orders.created_at', 'desc');

            // Apply plan filter if provided
            if ($request->has('plan_id') && $request->plan_id != '') {
                $assignments->where('orders.plan_id', $request->plan_id);
            }

            // Apply filters
            if ($request->has('orderId') && $request->orderId != '') {
                $assignments->where('orders.id', 'like', "%{$request->orderId}%");
            }

            if ($request->has('status') && $request->status != '') {
                $assignments->where('orders.status_manage_by_admin', $request->status);
            }

            if ($request->has('email') && $request->email != '') {
                $assignments->where('users.email', 'like', "%{$request->email}%");
            }

            if ($request->has('name') && $request->name != '') {
                $assignments->where('users.name', 'like', "%{$request->name}%");
            }

            if ($request->has('domain') && $request->domain != '') {
                $assignments->whereHas('order.reorderInfo', function($query) use ($request) {
                    $query->where('forwarding_url', 'like', "%{$request->domain}%");
                });
            }

            // if ($request->has('totalInboxes') && $request->totalInboxes != '') {
            //     $assignments->whereHas('order.reorderInfo', function($query) use ($request) {
            //         $query->where('total_inboxes', $request->totalInboxes);
            //     });
            // }
            
            if ($request->has('totalInboxes') && $request->totalInboxes != '') {
                $assignments->whereHas('order.reorderInfo', function($query) use ($request) {
                    $query->whereRaw('(
                        CASE 
                            WHEN domains IS NOT NULL AND domains != "" THEN 
                                (LENGTH(domains) - LENGTH(REPLACE(REPLACE(REPLACE(domains, ",", ""), CHAR(10), ""), CHAR(13), "")) + 1) * inboxes_per_domain
                            ELSE total_inboxes 
                        END
                    ) = ?', [$request->totalInboxes]);
                });
            }

            if ($request->has('startDate') && $request->startDate != '') {
                $assignments->whereDate('orders.created_at', '>=', $request->startDate);
            }

            if ($request->has('endDate') && $request->endDate != '') {
                $assignments->whereDate('orders.created_at', '<=', $request->endDate);
            }

            return DataTables::of($assignments)
                ->addColumn('action', function ($assignment) {
                    $order = $assignment->order;
                    $statuses = $this->statuses;
                    $statusOptions = '';

                    foreach ($statuses as $status => $color) {
                        $statusOptions .= '<li>
                            <a class="dropdown-item status-change" href="javascript:void(0)" data-order-id="' . $order->id . '" data-status="' . strtolower($status) . '">
                                <span class="py-1 px-2 text-' . $color . ' border border-' . $color . ' rounded-2 bg-transparent">' . $status . '</span>
                            </a>
                        </li>';
                    }
                    return '<div class="dropdown">
                                <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="' . route('contractor.orders.split.view', $assignment->orderPanel->id) . '">
                                        <i class="fa-solid fa-eye"></i> &nbsp;View</a></li>
                                        <li><a href="#" class="dropdown-item markStatus" id="markStatus" data-id="'.$assignment->orderPanel->id.'" data-status="'.($assignment->orderPanel->status ?? 'pending').'" data-reason="'.(isset($assignment->orderPanel->reason) ? $assignment->orderPanel->reason : '').'" ><i class="fa-solid fa-flag"></i> &nbsp;Mark Status</a></li>
                                </ul>
                            </div>';
                })
                ->addColumn('assignment', function ($assignment) {
                    // Check if assignment has split domains data
                    $hasSplitDomains = false;
                    $domainCount = 0;
                    
                    if ($assignment->orderPanelSplit && $assignment->orderPanelSplit->domains) {
                        $hasSplitDomains = true;
                        $domains = is_array($assignment->orderPanelSplit->domains) 
                            ? $assignment->orderPanelSplit->domains 
                            : [$assignment->orderPanelSplit->domains];
                        $domainCount = count($domains);
                    }
                    
                    if ($hasSplitDomains) {
                        $downloadUrl = route('contractor.orders.export.csv.split.domains', $assignment->order->id);
                        return '<a href="' . $downloadUrl . '" class="btn btn-sm btn-primary" title="Download CSV with ' . $domainCount . ' domains">
                                    <i class="fa-solid fa-download me-1"></i> Download CSV
                                </a>';
                    }
                    
                    return '<span class="badge bg-secondary">No split domains</span>';
                })
                ->editColumn('created_at', function ($assignment) {
                    return $assignment->order->created_at ? $assignment->order->created_at->format('d F, Y') : '';
                })
                ->editColumn('status', function ($assignment) {
                    $order = $assignment->order;
                    $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                    $statusKey = $status;
                    $statusClass = $this->statuses[$statusKey] ?? 'secondary';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($status) . '</span>';
                })
                ->addColumn('name', function ($assignment) {
                    return $assignment->order->user ? $assignment->order->user->name : 'N/A';
                })
                ->addColumn('email', function ($assignment) {
                    return $assignment->order->user ? $assignment->order->user->email : 'N/A';
                })
                ->addColumn('domain_forwarding_url', function ($assignment) {
                    return $assignment->order->reorderInfo->first() ? $assignment->order->reorderInfo->first()->forwarding_url : 'N/A';
                })
                ->addColumn('plan_name', function ($assignment) {
                    return $assignment->order->plan ? $assignment->order->plan->name : 'N/A';
                })
                ->addColumn('total_inboxes', function ($assignment) {
                    if (!$assignment->order->reorderInfo || !$assignment->order->reorderInfo->first()) {
                        return 'N/A';
                    }
                    
                    $reorderInfo = $assignment->order->reorderInfo->first();
                    $domains = $reorderInfo->domains ?? '';
                    $inboxesPerDomain = $reorderInfo->inboxes_per_domain ?? 1;
                    
                    // Parse domains and count them
                    $domainsArray = [];
                    $lines = preg_split('/\r\n|\r|\n/', $domains);
                    foreach ($lines as $line) {
                        if (trim($line)) {
                            $lineItems = explode(',', $line);
                            foreach ($lineItems as $item) {
                                if (trim($item)) {
                                    $domainsArray[] = trim($item);
                                }
                            }
                        }
                    }
                    
                    $totalDomains = count($domainsArray);
                    $calculatedTotalInboxes = $totalDomains * $inboxesPerDomain;
                    
                    return $calculatedTotalInboxes > 0 ? $calculatedTotalInboxes : ($reorderInfo->total_inboxes ?? 'N/A');
                })
                ->addColumn('split_status', function ($assignment) {
                    $status = $assignment->orderPanel->status ?? 'pending';
                    $statusClass = $this->getStatusClass($status);
                    return '<span class="badge bg-' . $statusClass . '">' . ucfirst($status) . '</span>';
                })
                ->addColumn('total_inboxes_split', function ($assignment) {
                    $totalInboxesSplit = 0;
                    
                    if ($assignment->orderPanelSplit) {
                        $split = $assignment->orderPanelSplit;
                        $domains = is_array($split->domains) ? $split->domains : (json_decode($split->domains, true) ?? []);
                        $totalInboxesSplit = count($domains) * ($split->inboxes_per_domain ?? 0);
                    }
                    
                    return $totalInboxesSplit > 0 ? $totalInboxesSplit : '<span class="text-muted">0</span>';
                })
                ->addColumn('order_id', function ($assignment) {
                    return $assignment->order->id;
                })
                ->addColumn('panel_id', function ($assignment) {
                    return $assignment->orderPanel && $assignment->orderPanel->panel ? 'PNL-' . $assignment->orderPanel->panel->id : 'N/A';
                })
                ->addColumn('assignment_id', function ($assignment) {
                    return $assignment->id;
                })
                ->rawColumns(['action', 'status', 'assignment', 'split_status', 'total_inboxes_split'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Error in getOrders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error loading orders: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validate the request data
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'plan_id' => 'required|exists:plans,id',
                'forwarding_url' => 'required|url',
                'hosting_platform' => 'required',
                'platform_login' => 'required',
                'platform_password' => 'required',
                'domains' => 'required',
                'sending_platform' => 'required',
                'sequencer_login' => 'required|email',
                'sequencer_password' => 'required',
                'total_inboxes' => 'required|integer|min:1',
                'inboxes_per_domain' => 'required|integer|min:1',
                'first_name' => 'required',
                'last_name' => 'required',
                'prefix_variant_1' => 'required',
                'prefix_variant_2' => 'required',
                // 'persona_password' => 'required',
                'email_persona_password' => 'required',
            ]);
            // persona_password set 123
            $request->persona_password = '123';
            // Calculate number of domains and total inboxes
            $domains = array_filter(preg_split('/[\r\n,]+/', $request->domains));
            $domainCount = count($domains);
            $calculatedTotalInboxes = $domainCount * $request->inboxes_per_domain;

            // Get requested plan
            $plan = Plan::findOrFail($request->plan_id);
            // dd($domainCount, $request->inboxes_per_domain, $calculatedTotalInboxes);
            // Verify plan can support the total inboxes
            // $canHandle = ($plan->max_inbox >= $calculatedTotalInboxes || $plan->max_inbox === 0) && 
            //             $plan->min_inbox <= $calculatedTotalInboxes;
            $canHandle = ($plan->max_inbox >= $calculatedTotalInboxes || $plan->max_inbox === 0);
                        
            if (!$canHandle) {
                // dd($domainCount, $request->inboxes_per_domain, $calculatedTotalInboxes);
                return response()->json([
                    'success' => false,
                    'message' => "Configuration exceeds available plan limits. Please contact support for a custom solution.",
                ], 422);
            }
            // dd($domainCount, $request->inboxes_per_domain, $calculatedTotalInboxes,"pass");
            // Store session data if validation passes
            $request->session()->put('order_info', $request->all());
            // set new plan_id on session order_info
            $request->session()->put('order_info.plan_id', $request->plan_id);
            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'plan_id' => $request->plan_id,
                'user_id' => $request->user_id
            ]);

        } catch (\Exception $e) {
            \Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'status' => 'required|in:' . implode(',', array_map('strtolower', array_keys($this->statuses)))
            ]);

            $order = Order::findOrFail($request->order_id);
            $oldStatus = $order->status_manage_by_admin;
            
            // Auto-assign the order if it's not already assigned
            if (!$order->assigned_to) {
                $order->assigned_to = auth()->id();
            }
            
            $order->status_manage_by_admin = strtolower($request->status);
            $order->save();

            // Create activity log
            ActivityLogService::log(
                'contractor-order-status-update',
                'Order status updated : ' . $order->id,
                $order,
                [
                    'order_id' => $order->id,
                    'old_status' => $oldStatus,
                    'new_status' => $order->status_manage_by_admin,
                    'updated_by' => auth()->id(),
                    'assigned_to' => $order->assigned_to
                ]
            );

            // Log the status change
            Log::info('Order status updated', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $order->status_manage_by_admin,
                'updated_by' => auth()->id(),
                'assigned_to' => $order->assigned_to
            ]);

            $orderCounts = $this->getOrderCounts();
            $counts = $this->getOrderCounts();
            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'counts' => $counts,
                'counts' => $orderCounts
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating order status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the order status'
            ], 500);
        }
    }
    public function getInvoices(Request $request)
    {
        try {
            $invoices = Invoice::with(['user', 'order'])
                ->select([
                    'invoices.id',
                    'invoices.chargebee_invoice_id',
                    'invoices.order_id',
                    'invoices.amount',
                    'invoices.status',
                    'invoices.paid_at',
                    'invoices.chargebee_subscription_id',
                    'invoices.created_at',
                    'invoices.updated_at',
                ]);

            // Filter by order_id if provided
            if ($request->has('order_id') && $request->order_id != '') {
                $invoices->where('order_id', $request->order_id);
            }

            // Filter by invoice status
            if ($request->has('status') && $request->status != '') {
                $invoices->where('status', $request->status);
            }

            // Filter by order status
            if ($request->has('order_status') && $request->order_status != '') {
                $invoices->whereHas('order', function($q) use ($request) {
                    $q->where('status_manage_by_admin', $request->order_status);
                });
            }

            // Filter by date range
            if ($request->has('start_date') && $request->start_date != '') {
                $invoices->whereDate('created_at', '>=', $request->start_date);
            }
            if ($request->has('end_date') && $request->end_date != '') {
                $invoices->whereDate('created_at', '<=', $request->end_date);
            }

            // Filter by price range
            if ($request->has('price_range') && $request->price_range != '') {
                list($min, $max) = explode('-', str_replace('$', '', $request->price_range));
                if ($max === '1000+') {
                    $invoices->where('amount', '>=', 1000);
                } else {
                    $invoices->whereBetween('amount', [(float)$min, (float)$max]);
                }
            }
            
            return DataTables::of($invoices)
                // ->addColumn('action', function($invoice) {
                //     $viewUrl = route('customer.invoices.show', $invoice->chargebee_invoice_id);
                //     $downloadUrl = route('customer.invoices.download', $invoice->chargebee_invoice_id);
                //     return '<div class="dropdown">
                //         <button class="bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                //             <i class="fa-solid fa-ellipsis-vertical"></i>
                //         </button>
                //         <ul class="dropdown-menu">
                //             <li><a class="dropdown-item" href="' . $viewUrl . '">View</a></li>
                //             <li><a class="dropdown-item" href="' . $downloadUrl . '">Download</a></li>
                //         </ul>
                //     </div>';
                // })
                ->editColumn('created_at', function($invoice) {
                    return $invoice->created_at ? $invoice->created_at->format('d F, Y') : '';
                })
                ->editColumn('paid_at', function($invoice) {
                    return $invoice->paid_at ? date('d F, Y', strtotime($invoice->paid_at)) : '';
                })
                ->editColumn('amount', function($invoice) {
                    return '$' . number_format($invoice->amount, 2);
                })
                ->editColumn('status', function($invoice) {
                    $statusKey = ucfirst(strtolower($invoice->status ?? 'N/A'));
                    return '<span class="py-1 px-2 text-' . ($this->paymentStatuses[$statusKey] ?? 'secondary') . ' border border-' . ($this->statuses[$statusKey] ?? 'secondary') . ' rounded-2 bg-transparent">' 
                        . $statusKey . '</span>';
                })
                ->editColumn('status_manage_by_admin', function($invoice) {
                    $statusKey = strtolower($invoice->order->status_manage_by_admin ?? 'N/A');
                    return '<span class="py-1 px-2 text-' . ($this->statuses[$statusKey] ?? 'secondary') . ' border border-' . ($this->statuses[$statusKey] ?? 'secondary') . ' rounded-2 bg-transparent">' 
                        . ucfirst($statusKey) . '</span>';
                })
                ->filterColumn('status_manage_by_admin', function($query, $keyword) {
                    $query->whereHas('order', function($q) use ($keyword) {
                        $q->where('status_manage_by_admin', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('status_manage_by_admin', function($query, $direction) {
                    $query->whereHas('order', function($q) use ($direction) {
                        $q->orderBy('status_manage_by_admin', $direction);
                    });
                })
                ->rawColumns(['action', 'status', 'status_manage_by_admin'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Error in getInvoices: ' . $e->getMessage());
            return response()->json(['error' => 'Error loading invoices'], 500);
        }
    }
    public function orderStatusProcess(Request $request)
    {
        $request->validate([
            'chargebee_subscription_id' => 'required|string',
            'marked_status' => 'required|string',
            'reason' => 'nullable|string',
        ]);
        $subscription = Subscription::where('chargebee_subscription_id', $request->chargebee_subscription_id)->first();
    
        if (!$subscription || $subscription->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'No active subscription found.'
            ], 404);
        }
    
        try {
            $order = Order::where('chargebee_subscription_id', $request->chargebee_subscription_id)->first();
            if ($order) {
                $oldStatus = $order->status_manage_by_admin;
                $newStatus = $request->marked_status;
                $reason = $request->reason ? $request->reason."(Reason given by) ".Auth::user()->name : null;
                
                // Auto-assign the order if it's not already assigned
                if (!$order->assigned_to) {
                    $order->assigned_to = auth()->id();
                }
                
                $order->update([
                    'status_manage_by_admin' => $newStatus,
                    'reason' => $reason,
                ]);

                // Create a new activity log using the custom log service
                ActivityLogService::log(
                    'contractor-order-status-update',
                    'Order status updated : ' . $order->id,
                    $order,
                    [
                        'order_id' => $order->id,
                        'old_status' => $oldStatus,
                        'new_status' => $order->status_manage_by_admin,
                        'updated_by' => auth()->id(),
                        'assigned_to' => $order->assigned_to
                    ]
                );
                // Notification for customer
                Notification::create([
                    'user_id' => $order->user_id,
                    'type' => 'order_status_change',
                    'title' => 'Order Status Changed',
                    'message' => 'Your order #' . $order->id . ' status has been changed to ' . $newStatus,
                    'data' => [
                        'order_id' => $order->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'reason' => $reason,
                        'assigned_to' => $order->assigned_to
                    ]
                ]);
                // Notification for contractor
                Notification::create([
                    'user_id' => Auth::id(),
                    'type' => 'order_status_change',
                    'title' => 'Order Status Changed',
                    'message' => 'Order #' . $order->id . ' status changed to ' . $newStatus,
                    'data' => [
                        'order_id' => $order->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'reason' => $reason,
                        'assigned_to' => $order->assigned_to
                    ]
                ]);

                // Get user details and send email
                $user = $order->user;
                try {
                    Mail::to($user->email)
                        ->queue(new OrderStatusChangeMail(
                            $order,
                            $user,
                            $oldStatus,
                            $newStatus,
                            $reason,
                            false
                        ));

                    // Only send email to admin
                    Mail::to(config('mail.admin_address', 'admin@example.com'))
                        ->queue(new OrderStatusChangeMail(
                            $order,
                            $user,
                            $oldStatus,
                            $newStatus,
                            $reason,
                            true
                        ));
                    Log::info('Order status change email sent', [
                        'order_id' => $order->id,
                        'assigned_to' => $order->assigned_to
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send order status change emails: ' . $e->getMessage());
                }
            }
            $orderCounts = $this->getOrderCounts();
            return response()->json([
                'success' => true,
                'message' => 'Order Status Updated Successfully.',
                'counts' => $orderCounts
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Error While Updating The Status: ' . $e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'Failed To Update The Status: ' . $e->getMessage()
            ], 500);
        }
    }


    public function orderImportProcess(Request $request)
    {
        // Validate file and order_id
        $validator = Validator::make($request->all(), [
            'bulk_file' => 'required|file|mimes:csv,txt',
            'order_id' => 'required|exists:orders,id',
            'order_total_inboxes' => 'required|integer'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Retrieve order
        $order = Order::find($request->order_id);
    
        // Read the uploaded CSV file
        $file = $request->file('bulk_file');
        $filePath = $file->getRealPath();
        $csv = array_map('str_getcsv', file($filePath));
    
        if (empty($csv) || count($csv) < 2) {
            return response()->json([
                'message' => 'The uploaded file is empty or lacks data.'
            ], 400);
        }
    
        // Get the header and remove it from data
        $headers = array_map('trim', $csv[0]);
        unset($csv[0]);
       

        if (count($csv) > $request->order_total_inboxes) {
            return response()->json([
                'message' => 'Oops! Limit exceeded. File contains more emails than allowed.',
                'count' => count($csv)
            ], 400);
        }
    
        $emails = [];
    
        foreach ($csv as $row) {
            if (count($row) !== count($headers)) {
                continue; // Skip malformed row
            }  
    
            $data = array_combine($headers, $row);
    
            $emails[] = [
                'order_id' => $order->id,
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'password' => $data['password'] ?? null,
                'user_id' => $order->user_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    
        // Insert all at once
        OrderEmail::insert($emails);
        // if order not assigned then assign the order to the contractor
        $order = Order::where('id', $request->order_id)->first();
        // assigned_to is null then assign the order to the contractor
        if ($order->assigned_to == null) {
            $order->assigned_to = auth()->id();
            $order->save();
        }
        return response()->json([
            'message' => 'Emails imported successfully.',
            'count' => count($emails)
        ]);
    }
    
    private function getOrderCounts()
    {
        $orders = Order::where(function($query) {
            $query->whereNull('assigned_to')
                  ->orWhere('assigned_to', auth()->id());
        });
        
        return [
            'totalOrders' => $orders->count(),
            'pendingOrders' => $orders->clone()->where('status_manage_by_admin', 'pending')->count(),
            'completedOrders' => $orders->clone()->where('status_manage_by_admin', 'completed')->count(),
            'inProgressOrders' => $orders->clone()->where('status_manage_by_admin', 'in-progress')->count(),
            'expiredOrders' => $orders->clone()->where('status_manage_by_admin', 'expired')->count(),
            'rejectOrders' => $orders->clone()->where('status_manage_by_admin', 'reject')->count(),
            'cancelledOrders' => $orders->clone()->where('status_manage_by_admin', 'cancelled')->count(),
            'draftOrders' => $orders->clone()->where('status_manage_by_admin', 'draft')->count(),
        ];
    }

    /**
     * Export CSV file with split domains data for a specific order
     */
    public function exportCsvSplitDomains($orderId)
    {
        try {
            $order = Order::with([
                'orderPanels.userOrderPanelAssignments' => function($query) {
                    $query->where('contractor_id', auth()->id())
                          ->with(['orderPanelSplit', 'orderPanel']);
                }
            ])->findOrFail($orderId);

            // Check if contractor has access to this order
            $hasAccess = $order->assigned_to == auth()->id() || 
                        $order->orderPanels->flatMap->userOrderPanelAssignments->where('contractor_id', auth()->id())->count() > 0;

            if (!$hasAccess) {
                return back()->with('error', 'You do not have access to this order.');
            }

            // Collect split domains data (only domains)
            $domains = [];
            foreach ($order->orderPanels as $orderPanel) {
                foreach ($orderPanel->userOrderPanelAssignments as $assignment) {
                    if ($assignment->contractor_id == auth()->id() && $assignment->orderPanelSplit) {
                        $split = $assignment->orderPanelSplit;
                        $splitDomains = is_array($split->domains) ? $split->domains : [$split->domains];
                        
                        foreach ($splitDomains as $domain) {
                            $domains[] = $domain;
                        }
                    }
                }
            }

            if (empty($domains)) {
                return back()->with('error', 'No domains data found for this order.');
            }

            $filename = "order_{$order->id}_domains.csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($domains) {
                $file = fopen('php://output', 'w');

                // Add CSV header
                fputcsv($file, ['Domain']);

                // Add data rows
                foreach ($domains as $domain) {
                    fputcsv($file, [$domain]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting CSV domains: ' . $e->getMessage());
            return back()->with('error', 'Error exporting CSV: ' . $e->getMessage());
        }
    }

    /**
     * Get CSS class for status display
     */
    private function getStatusClass($status)
    {
        return $this->splitStatuses[strtolower($status)] ?? 'secondary';
    }
    public function orderPanelStatusProcess(Request $request)
    {
        $request->validate([
            'order_panel_id' => 'required|exists:order_panel,id',
            'marked_status' => 'required|string|in:' . implode(',', array_keys($this->splitStatuses)),
            'reason' => 'nullable|string',
        ]);

        try {
            // Ensure contractor is authenticated
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be logged in to perform this action.'
                ], 401);
            }

            $contractorId = auth()->id();
            
            // Get the order panel with all necessary relationships
            $orderPanel = OrderPanel::with(['order', 'order.user', 'userOrderPanelAssignments'])->findOrFail($request->order_panel_id);
            $order = $orderPanel->order;
            
            // Get the current contractor's assignment for this order panel
            $assignment = UserOrderPanelAssignment::where('order_panel_id', $orderPanel->id)
                ->where('contractor_id', $contractorId)
                ->first();

            if (!$assignment) {
                // Check if this order panel has any assignments at all
                $allAssignments = UserOrderPanelAssignment::where('order_panel_id', $orderPanel->id)->get();
                $contractorAssignments = UserOrderPanelAssignment::where('contractor_id', $contractorId)->get();
                
                \Log::info('Assignment lookup failed', [
                    'order_panel_id' => $orderPanel->id,
                    'contractor_id' => $contractorId,
                    'total_assignments_for_panel' => $allAssignments->count(),
                    'total_assignments_for_contractor' => $contractorAssignments->count(),
                    'panel_assignments' => $allAssignments->pluck('contractor_id')->toArray(),
                    'contractor_panels' => $contractorAssignments->pluck('order_panel_id')->toArray()
                ]);
                
                if ($allAssignments->isEmpty()) {
                    $errorMessage = 'This order panel has not been assigned to any contractor yet.';
                } else {
                    $assignedContractors = $allAssignments->pluck('contractor_id')->unique();
                    $errorMessage = 'This order panel is assigned to contractor ID(s): ' . $assignedContractors->implode(', ') . '. You are contractor ID: ' . $contractorId;
                }
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'debug' => [
                        'order_panel_id' => $orderPanel->id,
                        'contractor_id' => $contractorId,
                        'panel_has_assignments' => !$allAssignments->isEmpty(),
                        'assigned_contractors' => $allAssignments->pluck('contractor_id')->toArray()
                    ]
                ], 403);
            }

            $oldStatus = $orderPanel->status;
            $newStatus = strtolower($request->marked_status);
            $reason = $request->reason ? $request->reason . " (Reason given by) " . Auth::user()->name : null;

            // Update order panel status
            $orderPanel->update([
                'status' => $newStatus,
            ]);

            // If rejected, also update with reason (you might want to add a reason field to order_panel table)
            if ($newStatus === 'rejected' && $reason) {
                // Add reason to note field or create a separate reason field
                $orderPanel->update(['note' => $reason]);
            }

            // Update order status_manage_by_admin based on panel status changes
            $this->updateOrderStatusBasedOnPanelStatus($order, $newStatus);

            // Create activity log
            ActivityLogService::log(
                'contractor-order-panel-status-update',
                'Order panel status updated: Panel ID ' . $orderPanel->id . ' for Order #' . $order->id,
                $orderPanel,
                [
                    'order_panel_id' => $orderPanel->id,
                    'order_id' => $order->id,
                    'assignment_id' => $assignment->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'updated_by' => auth()->id(),
                    'reason' => $reason
                ]
            );

            // Create notifications
            Notification::create([
                'user_id' => $order->user_id,
                'type' => 'order_panel_status_change',
                'title' => 'Order Panel Status Changed',
                'message' => 'Your order #' . $order->id . ' panel status has been changed to ' . $newStatus,
                'data' => [
                    'order_id' => $order->id,
                    'order_panel_id' => $orderPanel->id,
                    'assignment_id' => $assignment->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason,
                    'updated_by' => Auth::id()
                ]
            ]);

            // Notification for contractor
            Notification::create([
                'user_id' => Auth::id(),
                'type' => 'order_panel_status_change',
                'title' => 'Order Panel Status Changed',
                'message' => 'Order #' . $order->id . ' panel status changed to ' . $newStatus,
                'data' => [
                    'order_id' => $order->id,
                    'order_panel_id' => $orderPanel->id,
                    'assignment_id' => $assignment->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason,
                    'updated_by' => Auth::id()
                ]
            ]);

            // Send emails if needed
            try {
                $user = $order->user;
                Mail::to($user->email)
                    ->queue(new OrderStatusChangeMail(
                        $order,
                        $user,
                        $oldStatus,
                        $newStatus,
                        $reason,
                        false
                    ));

                // Send email to admin
                Mail::to(config('mail.admin_address', 'admin@example.com'))
                    ->queue(new OrderStatusChangeMail(
                        $order,
                        $user,
                        $oldStatus,
                        $newStatus,
                        $reason,
                        true
                    ));

                Log::info('Order panel status change email sent', [
                    'order_id' => $order->id,
                    'order_panel_id' => $orderPanel->id,
                    'assignment_id' => $assignment->id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send order panel status change emails: ' . $e->getMessage());
            }

            $orderCounts = $this->getOrderCounts();
            return response()->json([
                'success' => true,
                'message' => 'Order Panel Status Updated Successfully.',
                'counts' => $orderCounts
            ]);

        } catch (\Exception $e) {
            \Log::error('Error While Updating The Panel Status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed To Update The Panel Status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order's status_manage_by_admin based on panel status changes
     */
    
    private function updateOrderStatusBasedOnPanelStatus($order, $newPanelStatus)
    {
        // If a panel is set to "in-progress", update order status to "in-progress"
        if ($newPanelStatus === 'in-progress') {
            if ($order->status_manage_by_admin !== 'in-progress') {
                $order->update(['status_manage_by_admin' => 'in-progress']);
            }
        }
        
        // If a panel is set to "rejected", update order status to "reject"
        if ($newPanelStatus === 'rejected') {
            if ($order->status_manage_by_admin !== 'reject') {
                $order->update(['status_manage_by_admin' => 'reject']);
            }
        }
        
        // If a panel is set to "completed", check if all panels are completed
        if ($newPanelStatus === 'completed') {
            // Get all panels for this order
            $allPanels = OrderPanel::where('order_id', $order->id)->get();
            
            // Check if any panel is rejected
            $hasRejected = $allPanels->contains(function ($panel) {
                return $panel->status === 'rejected';
            });
            
            // If any panel is rejected, update order status to "reject"
            if ($hasRejected) {
                if ($order->status_manage_by_admin !== 'reject') {
                    $order->update(['status_manage_by_admin' => 'reject']);
                }
            } else {
                // Check if all panels are completed
                $allCompleted = $allPanels->every(function ($panel) {
                    return $panel->status === 'completed';
                });
                
                // If all panels are completed, update order status to "completed"
                // Otherwise, update order status to "in-progress"
                if ($allCompleted) {
                    if ($order->status_manage_by_admin !== 'completed') {
                    $order->update(['status_manage_by_admin' => 'completed']);
                    }
                } else {
                    if ($order->status_manage_by_admin !== 'in-progress') {
                    $order->update(['status_manage_by_admin' => 'in-progress']);
                    }
                }
            }
        }
    }

    /**
     * Assign all unallocated splits of an order to the logged-in contractor
     */
    public function assignOrderToMe(Request $request, $orderId)
    {
        try {
            $contractorId = Auth::id();
            
            // Find the order
            $order = Order::findOrFail($orderId);
            
            // Get all order panels (splits) for this order that are unallocated
            $unallocatedPanels = OrderPanel::where('order_id', $orderId)
                ->where('status', 'unallocated')
                ->get();
            
            if ($unallocatedPanels->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No unallocated splits found for this order.'
                ], 400);
            }
            
            $assignedCount = 0;
            $errors = [];
            
            // Assign each unallocated panel to the contractor
            foreach ($unallocatedPanels as $panel) {
                try {
                    // Check if there's already an assignment for this panel
                    $existingAssignment = UserOrderPanelAssignment::where('order_panel_id', $panel->id)
                        ->where('contractor_id', $contractorId)
                        ->first();
                    
                    if (!$existingAssignment) {
                        // order_panel_split_id
                        $order_panel_split_id = OrderPanelSplit::where('order_panel_id', $panel->id)->value('id');
                        // Create new assignment
                        UserOrderPanelAssignment::create([
                            'order_panel_id' => $panel->id,
                            'contractor_id' => $contractorId,
                            'order_panel_split_id' => $order_panel_split_id,
                            'order_id' => $orderId
                        ]);
                    }
                    
                    // Update panel status to allocated
                    $panel->update([
                        'status' => 'allocated',
                        'contractor_id' => $contractorId
                    ]);
                    
                    $assignedCount++;
                    
                } catch (Exception $e) {
                    $errors[] = "Failed to assign panel {$panel->id}: " . $e->getMessage();
                    Log::error("Error assigning panel {$panel->id} to contractor {$contractorId}: " . $e->getMessage());
                }
            }
            $order->assigned_to = $contractorId;
            $order->save();
            // Update order status if all panels are now allocated
            $remainingUnallocated = OrderPanel::where('order_id', $orderId)
                ->where('status', 'unallocated')
                ->count();
            
            // Log the activity
            $activityLogService = new ActivityLogService();
            $activityLogService->log(
                'Order Assignment',
                "Contractor assigned {$assignedCount} splits of Order #{$orderId} to themselves",
                $order,
                [
                    'order_id' => $orderId,
                    'assigned_count' => $assignedCount,
                    'contractor_id' => $contractorId,
                    'remaining_unallocated' => $remainingUnallocated
                ],
                $contractorId
            );
            
            $message = $assignedCount > 0 
                ? "Successfully assigned {$assignedCount} split(s) to you!" 
                : "No new assignments were made.";
            
            if (!empty($errors)) {
                $message .= " However, some errors occurred: " . implode(', ', $errors);
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'assigned_count' => $assignedCount,
                'errors' => $errors
            ]);
            
        } catch (Exception $e) {
            Log::error("Error in assignOrderToMe for order {$orderId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign order: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getAssignedOrdersData(Request $request)
    {
        try {
            $query = Order::with(['reorderInfo', 'orderPanels.orderPanelSplits', 'orderPanels.panel', 'user'])
                ->whereHas('orderPanels.userOrderPanelAssignments', function($q) {
                    $q->where('contractor_id', auth()->id());
                });

            // Apply filters if provided
            if ($request->filled('order_id')) {
                $query->where('id', 'like', '%' . $request->order_id . '%');
            }

            if ($request->filled('status')) {
                $query->whereHas('orderPanels', function($q) use ($request) {
                    $q->where('status', $request->status);
                });
            }

            if ($request->filled('min_inboxes')) {
                $query->whereHas('reorderInfo', function($q) use ($request) {
                    $q->where('total_inboxes', '>=', $request->min_inboxes);
                });
            }

            if ($request->filled('max_inboxes')) {
                $query->whereHas('reorderInfo', function($q) use ($request) {
                    $q->where('total_inboxes', '<=', $request->max_inboxes);
                });
            }

            // Apply ordering
            $order = $request->get('order', 'desc');
            $query->orderBy('id', $order);

            // Pagination parameters
            $perPage = $request->get('per_page', 12); // Default 12 orders per page
            $page = $request->get('page', 1);
            
            // Get paginated results
            $paginatedOrders = $query->paginate($perPage, ['*'], 'page', $page);

            // Format orders data for the frontend
            $ordersData = $paginatedOrders->getCollection()->map(function ($order) {
                $reorderInfo = $order->reorderInfo->first();
                $orderPanels = $order->orderPanels;
                
                // Calculate total domains count from all splits
                $totalDomainsCount = 0;
                $totalInboxes = 0;
                
                foreach ($orderPanels as $orderPanel) {
                    foreach ($orderPanel->orderPanelSplits as $split) {
                        if ($split->domains && is_array($split->domains)) {
                            $totalDomainsCount += count($split->domains);
                        }
                        $totalInboxes += $split->inboxes_per_domain * (is_array($split->domains) ? count($split->domains) : 0);
                    }
                }
                
                $inboxesPerDomain = $reorderInfo ? $reorderInfo->inboxes_per_domain : 0;
                
                return [
                    'id' => $order->id,
                    'order_id' => $order->id,
                    'customer_name' => $order->user->name ?? 'N/A',
                    'total_inboxes' => $reorderInfo ? $reorderInfo->total_inboxes : $totalInboxes,
                    'inboxes_per_domain' => $inboxesPerDomain,
                    'total_domains' => $totalDomainsCount,
                    'status' => $order->status_manage_by_admin ?? 'pending',
                    'status_manage_by_admin' => (function() use ($order) {
                        $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                        $statusKey = $status;
                        $statusClass = $this->statuses[$statusKey] ?? 'secondary';
                        return '<span class="py-1 px-1 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent fs-6" style="font-size: 11px !important;">' 
                            . ucfirst($status) . '</span>';
                    })(),
                    'created_at' => $order->created_at,
                    'completed_at' => $order->completed_at,
                    'timer_started_at' => $order->timer_started_at,
                    'order_panels_count' => $orderPanels->count(),
                    'splits_count' => $orderPanels->sum(function($panel) {
                        return $panel->orderPanelSplits->count();
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ordersData,
                'pagination' => [
                    'current_page' => $paginatedOrders->currentPage(),
                    'last_page' => $paginatedOrders->lastPage(),
                    'per_page' => $paginatedOrders->perPage(),
                    'total' => $paginatedOrders->total(),
                    'has_more_pages' => $paginatedOrders->hasMorePages(),
                    'from' => $paginatedOrders->firstItem(),
                    'to' => $paginatedOrders->lastItem()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching orders data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get emails for a specific order panel split
     */
    public function getSplitEmails($orderPanelId)
    {
        try {
            // Verify the contractor has access to this order panel
            $orderPanel = OrderPanel::with(['order'])
                ->whereHas('userOrderPanelAssignments', function($query) {
                    $query->where('contractor_id', auth()->id());
                })
                ->findOrFail($orderPanelId);

            // Get emails for this specific order panel split
            $emails = OrderEmail::with(['orderSplit'])
                ->where('order_id', $orderPanel->order_id)
                ->whereHas('orderSplit', function($query) use ($orderPanelId) {
                    $query->where('order_panel_id', $orderPanelId);
                })
                ->select('id', 'name', 'email', 'password', 'order_split_id', 'contractor_id')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $emails,
                'order_panel_id' => $orderPanelId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching emails: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store emails for a specific order panel split
     */
    public function storeSplitEmails(Request $request)
    {
        try {
            // Log the raw request data for debugging
            \Log::info('Store Split Emails Request', [
                'all_data' => $request->all(),
                'content_type' => $request->header('Content-Type'),
                'method' => $request->method()
            ]);

            $validator = Validator::make($request->all(), [
                'order_panel_id' => 'required|exists:order_panel,id',
                'emails' => 'required|array',
                'emails.*.name' => 'required|string|max:255',
                'emails.*.email' => 'required|email|max:255',
                'emails.*.password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed', ['errors' => $validator->errors()]);
                return response()->json(['errors' => $validator->errors()], 422);
            }

            // Verify the contractor has access to this order panel
            $orderPanel = OrderPanel::with(['order', 'orderPanelSplits'])
                ->whereHas('userOrderPanelAssignments', function($query) {
                    $query->where('contractor_id', auth()->id());
                })
                ->findOrFail($request->order_panel_id);

            // Debug: Log order panel information
            \Log::info('Order Panel Debug', [
                'order_panel_id' => $orderPanel->id,
                'order_id' => $orderPanel->order_id,
                'splits_count' => $orderPanel->orderPanelSplits->count(),
                'splits_data' => $orderPanel->orderPanelSplits->toArray()
            ]);

            // Get the first order panel split (assuming one split per panel for now)
            $orderPanelSplit = $orderPanel->orderPanelSplits->first();

            if (!$orderPanelSplit) {
                // Try to create an order panel split if none exists
                $orderPanelSplit = OrderPanelSplit::create([
                    'order_panel_id' => $orderPanel->id,
                    'order_id' => $orderPanel->order_id,
                    'panel_id' => $orderPanel->panel_id,
                    'inboxes_per_domain' => $orderPanel->inboxes_per_domain ?? 1,
                    'domains' => [] // Empty array for now
                ]);
                
                \Log::info('Created new order panel split', ['split_id' => $orderPanelSplit->id]);
            }

            // Delete existing emails for this specific order panel split
            $deletedCount = OrderEmail::where('order_id', $orderPanel->order_id)
                ->where('order_split_id', $orderPanelSplit->id)
                ->count();
                
            OrderEmail::where('order_id', $orderPanel->order_id)
                ->where('order_split_id', $orderPanelSplit->id)
                ->delete();

            \Log::info('Deleted existing emails', [
                'deleted_count' => $deletedCount,
                'order_id' => $orderPanel->order_id,
                'split_id' => $orderPanelSplit->id
            ]);

            // Create new emails
            $emails = collect($request->emails)->map(function ($emailData) use ($orderPanel, $orderPanelSplit) {
                return OrderEmail::create([
                    'order_id' => $orderPanel->order_id,
                    'user_id' => $orderPanel->order->user_id,
                    'order_split_id' => $orderPanelSplit->id,
                    'contractor_id' => auth()->id(),
                    'name' => $emailData['name'],
                    'email' => $emailData['email'],
                    'password' => $emailData['password'],
                    'profile_picture' => $emailData['profile_picture'] ?? null,
                ]);
            });

            \Log::info('Created new emails', [
                'email_count' => $emails->count(),
                'order_panel_id' => $orderPanel->id
            ]);

            // Update order assignment if not already assigned
            if ($orderPanel->order->assigned_to == null) {
                $orderPanel->order->assigned_to = auth()->id();
                $orderPanel->order->save();
            }

            // Create notification for customer
            Notification::create([
                'user_id' => $orderPanel->order->user_id,
                'type' => 'email_created',
                'title' => 'New Email Accounts Created',
                'message' => 'New email accounts have been created for your order #' . $orderPanel->order_id . ' panel #' . $orderPanel->id,
                'data' => [
                    'order_id' => $orderPanel->order_id,
                    'order_panel_id' => $orderPanel->id,
                    'email_count' => count($request->emails)
                ]
            ]);

            // Create notification for contractor
            Notification::create([
                'user_id' => auth()->id(),
                'type' => 'email_created',
                'title' => 'Email Accounts Created',
                'message' => 'You have created new email accounts for order #' . $orderPanel->order_id . ' panel #' . $orderPanel->id,
                'data' => [
                    'order_id' => $orderPanel->order_id,
                    'order_panel_id' => $orderPanel->id,
                    'email_count' => count($request->emails)
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Emails saved successfully',
                'data' => $emails
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving split emails', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'order_panel_id' => $request->order_panel_id ?? 'N/A',
                'contractor_id' => auth()->id() ?? 'N/A',
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Error saving emails: ' . $e->getMessage(),
                'debug' => [
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'order_panel_id' => $request->order_panel_id ?? 'N/A'
                ]
            ], 500);
        }
    }

    /**
     * Delete a specific email from order panel split
     */
    public function deleteSplitEmail($id)
    {
        try {
            // Find the email and verify contractor access
            $email = OrderEmail::with(['orderSplit.orderPanel'])
                ->whereHas('orderSplit.orderPanel.userOrderPanelAssignments', function($query) {
                    $query->where('contractor_id', auth()->id());
                })
                ->findOrFail($id);

            $email->delete();

            return response()->json([
                'success' => true,
                'message' => 'Email deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting email: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk import emails for split panel
     */
    public function orderSplitImportProcess(Request $request)
    {
        // Validate file and order_panel_id
        $validator = Validator::make($request->all(), [
            'bulk_file' => 'required|file|mimes:csv,txt',
            'order_panel_id' => 'required|exists:order_panel,id',
            'split_total_inboxes' => 'required|integer'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }
    
        try {
            // Verify the contractor has access to this order panel
            $orderPanel = OrderPanel::with(['order', 'orderPanelSplits'])
                ->whereHas('userOrderPanelAssignments', function($query) {
                    $query->where('contractor_id', auth()->id());
                })
                ->findOrFail($request->order_panel_id);

            // Get the first order panel split (assuming one split per panel for now)
            $orderPanelSplit = $orderPanel->orderPanelSplits->first();

            if (!$orderPanelSplit) {
                return response()->json(['message' => 'No order panel split found'], 404);
            }
    
            // Read the uploaded CSV file
            $file = $request->file('bulk_file');
            $filePath = $file->getRealPath();
            $csv = array_map('str_getcsv', file($filePath));
    
            if (empty($csv) || count($csv) < 2) {
                return response()->json([
                    'message' => 'The uploaded file is empty or lacks data.'
                ], 400);
            }
    
            // Get the header and remove it from data
            $headers = array_map('trim', $csv[0]);
            unset($csv[0]);
           
            if (count($csv) > $request->split_total_inboxes) {
                return response()->json([
                    'message' => 'Oops! Limit exceeded. File contains more emails than allowed for this panel split.',
                    'count' => count($csv)
                ], 400);
            }
    
            // Delete existing emails for this specific order panel split
            OrderEmail::where('order_id', $orderPanel->order_id)
                ->where('order_split_id', $orderPanelSplit->id)
                ->delete();

            $emails = [];
    
            foreach ($csv as $row) {
                if (count($row) !== count($headers)) {
                    continue; // Skip malformed row
                }  
    
                $data = array_combine($headers, $row);
    
                $emails[] = [
                    'order_id' => $orderPanel->order_id,
                    'user_id' => $orderPanel->order->user_id,
                    'order_split_id' => $orderPanelSplit->id,
                    'contractor_id' => auth()->id(),
                    'name' => $data['name'] ?? null,
                    'email' => $data['email'] ?? null,
                    'password' => $data['password'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
    
            // Insert all at once
            OrderEmail::insert($emails);
            
            // Update order assignment if not already assigned
            if ($orderPanel->order->assigned_to == null) {
                $orderPanel->order->assigned_to = auth()->id();
                $orderPanel->order->save();
            }

            // Create notification for customer
            Notification::create([
                'user_id' => $orderPanel->order->user_id,
                'type' => 'email_created',
                'title' => 'Bulk Email Accounts Created',
                'message' => 'Bulk email accounts have been imported for your order #' . $orderPanel->order_id . ' panel #' . $orderPanel->id,
                'data' => [
                    'order_id' => $orderPanel->order_id,
                    'order_panel_id' => $orderPanel->id,
                    'email_count' => count($emails)
                ]
            ]);
            
            return response()->json([
                'message' => 'Emails imported successfully for panel split.',
                'count' => count($emails)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error importing emails: ' . $e->getMessage()
            ], 500);
        }
    }
}