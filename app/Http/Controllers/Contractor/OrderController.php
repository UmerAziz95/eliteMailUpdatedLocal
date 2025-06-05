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
        $plans = [];
        return view('contractor.orders.orders', compact(
            'plans', 
            'totalOrders', 
            'pendingOrders',            'completedOrders',
            'inProgressOrders',
            'percentageChange',
            'statuses',
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

    public function getOrders(Request $request)
    {
        try {
            $orders = Order::query()
                ->with([
                    'user', 
                    'plan', 
                    'reorderInfo',
                    'orderPanels.userOrderPanelAssignments' => function($query) {
                        $query->with(['orderPanel', 'orderPanelSplit']);
                    }
                ])
                ->select('orders.*')
                ->leftJoin('plans', 'orders.plan_id', '=', 'plans.id')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id')
                ->where('status_manage_by_admin', '!=', 'draft')
                ->where(function($query) {
                    $query->whereNull('assigned_to')
                          ->orWhere('assigned_to', auth()->id());
                });

            // Apply plan filter if provided
            if ($request->has('plan_id') && $request->plan_id != '') {
                $orders->where('orders.plan_id', $request->plan_id);
            }

            // Apply filters
            if ($request->has('orderId') && $request->orderId != '') {
                $orders->where('orders.id', 'like', "%{$request->orderId}%");
            }

            if ($request->has('status') && $request->status != '') {
                $orders->where('orders.status_manage_by_admin', $request->status);
            }

            if ($request->has('email') && $request->email != '') {
                $orders->where('users.email', 'like', "%{$request->email}%");
            }

            if ($request->has('name') && $request->name != '') {
                $orders->where('users.name', 'like', "%{$request->name}%");
            }

            if ($request->has('domain') && $request->domain != '') {
                $orders->whereHas('reorderInfo', function($query) use ($request) {
                    $query->where('forwarding_url', 'like', "%{$request->domain}%");
                });
            }

            if ($request->has('totalInboxes') && $request->totalInboxes != '') {
                $orders->whereHas('reorderInfo', function($query) use ($request) {
                    $query->where('total_inboxes', $request->totalInboxes);
                });
            }

            if ($request->has('startDate') && $request->startDate != '') {
                $orders->whereDate('orders.created_at', '>=', $request->startDate);
            }

            if ($request->has('endDate') && $request->endDate != '') {
                $orders->whereDate('orders.created_at', '<=', $request->endDate);
            }

            return DataTables::of($orders)
                ->addColumn('action', function ($order) {
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
                                    <li><a class="dropdown-item" href="' . route('contractor.orders.view', $order->id) . '">
                                        <i class="fa-solid fa-eye"></i> &nbsp;View</a></li>
                                        <li><a href="#" class="dropdown-item markStatus" id="markStatus" data-id="'.$order->chargebee_subscription_id.'" data-status="'.$order->status_manage_by_admin.'" data-reason="'.$order->reason.'" ><i class="fa-solid fa-flag"></i> &nbsp;Mark Status</a></li>
                                </ul>
                            </div>';
                })
                ->addColumn('assignment', function ($order) {
                    // Check if order has split domains data
                    $hasSplitDomains = false;
                    $domainCount = 0;
                    
                    foreach ($order->orderPanels as $orderPanel) {
                        foreach ($orderPanel->userOrderPanelAssignments as $assignment) {
                            if ($assignment->contractor_id == auth()->id() && $assignment->orderPanelSplit && $assignment->orderPanelSplit->domains) {
                                $hasSplitDomains = true;
                                $domains = is_array($assignment->orderPanelSplit->domains) 
                                    ? $assignment->orderPanelSplit->domains 
                                    : [$assignment->orderPanelSplit->domains];
                                $domainCount += count($domains);
                            }
                        }
                    }
                    
                    if ($hasSplitDomains) {
                        $downloadUrl = route('contractor.orders.export.csv.split.domains', $order->id);
                        return '<a href="' . $downloadUrl . '" class="btn btn-sm btn-primary" title="Download CSV with ' . $domainCount . ' domains">
                                    <i class="fa-solid fa-download me-1"></i> Download CSV
                                </a>';
                    }
                    
                    return '<span class="badge bg-secondary">No split domains</span>';
                })
                ->editColumn('created_at', function ($order) {
                    return $order->created_at ? $order->created_at->format('d F, Y') : '';
                })
                ->editColumn('status', function ($order) {
                    $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                    $statusKey = $status;
                    $statusClass = $this->statuses[$statusKey] ?? 'secondary';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($status) . '</span>';
                })
                ->addColumn('name', function ($order) {
                    return $order->user ? $order->user->name : 'N/A';
                })
                ->addColumn('email', function ($order) {
                    return $order->user ? $order->user->email : 'N/A';
                })
                ->addColumn('domain_forwarding_url', function ($order) {
                    return $order->reorderInfo->first() ? $order->reorderInfo->first()->forwarding_url : 'N/A';
                })
                ->addColumn('plan_name', function ($order) {
                    return $order->plan ? $order->plan->name : 'N/A';
                })
                ->addColumn('total_inboxes', function ($order) {
                    return $order->reorderInfo->first() ? $order->reorderInfo->first()->total_inboxes : 'N/A';
                })
                ->addColumn('split_status', function ($order) {
                    $contractorId = auth()->id();
                    $splitStatuses = [];
                    
                    foreach ($order->orderPanels as $orderPanel) {
                        foreach ($orderPanel->userOrderPanelAssignments as $assignment) {
                            if ($assignment->contractor_id == $contractorId) {
                                $status = $orderPanel->status ?? 'pending';
                                $statusClass = $this->getStatusClass($status);
                                $splitStatuses[] = '<span class="badge bg-' . $statusClass . '">' . ucfirst($status) . '</span>';
                            }
                        }
                    }
                    
                    return !empty($splitStatuses) ? implode(' ', array_unique($splitStatuses)) : '<span class="badge bg-secondary">No assignment</span>';
                })
                ->addColumn('total_inboxes_split', function ($order) {
                    $contractorId = auth()->id();
                    $totalInboxesSplit = 0;
                    
                    foreach ($order->orderPanels as $orderPanel) {
                        foreach ($orderPanel->userOrderPanelAssignments as $assignment) {
                            if ($assignment->contractor_id == $contractorId && $assignment->orderPanelSplit) {
                                $split = $assignment->orderPanelSplit;
                                $domains = is_array($split->domains) ? $split->domains : (json_decode($split->domains, true) ?? []);
                                $totalInboxesSplit += count($domains) * ($split->inboxes_per_domain ?? 0);
                            }
                        }
                    }
                    
                    return $totalInboxesSplit > 0 ? $totalInboxesSplit : '<span class="text-muted">0</span>';
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

            // Collect split domains data
            $splitDomainsData = [];
            foreach ($order->orderPanels as $orderPanel) {
                foreach ($orderPanel->userOrderPanelAssignments as $assignment) {
                    if ($assignment->contractor_id == auth()->id() && $assignment->orderPanelSplit) {
                        $split = $assignment->orderPanelSplit;
                        $domains = is_array($split->domains) ? $split->domains : [$split->domains];
                        
                        foreach ($domains as $domain) {
                            $splitDomainsData[] = [
                                'panel_id' => $assignment->orderPanel->id ?? 'N/A',
                                'panel_space_assigned' => $assignment->orderPanel->space_assigned ?? 'N/A',
                                'domain' => $domain,
                                'inboxes_per_domain' => $split->inboxes_per_domain,
                                'total_inboxes_for_domain' => $split->inboxes_per_domain
                            ];
                        }
                    }
                }
            }

            if (empty($splitDomainsData)) {
                return back()->with('error', 'No split domains data found for this order.');
            }

            $filename = "order_{$order->id}_split_domains.csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($splitDomainsData) {
                $file = fopen('php://output', 'w');

                // Add CSV header
                fputcsv($file, [
                    'Panel ID',
                    'Panel Space Assigned', 
                    'Domain',
                    'Inboxes Per Domain',
                    'Total Inboxes For Domain'
                ]);

                // Add data rows
                foreach ($splitDomainsData as $data) {
                    fputcsv($file, [
                        $data['panel_id'],
                        $data['panel_space_assigned'],
                        $data['domain'],
                        $data['inboxes_per_domain'],
                        $data['total_inboxes_for_domain']
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting CSV split domains: ' . $e->getMessage());
            return back()->with('error', 'Error exporting CSV: ' . $e->getMessage());
        }
    }

    /**
     * Get CSS class for status display
     */
    private function getStatusClass($status)
    {
        $statusClasses = [
            'pending' => 'warning',
            'accepted' => 'success', 
            'completed' => 'primary',
            'cancelled' => 'danger',
            'rejected' => 'danger',
            'in_progress' => 'info',
            'released' => 'secondary'
        ];
        
        return $statusClasses[strtolower($status)] ?? 'secondary';
    }

}
