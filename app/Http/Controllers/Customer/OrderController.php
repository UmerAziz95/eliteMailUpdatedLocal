<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\Status;
use App\Models\ReorderInfo;
use App\Models\HostingPlatform;
use App\Models\Panel;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Mail\OrderEditedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\ActivityLogService;

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
        $this->statuses = Status::pluck('badge', 'name')->toArray();
    }
    
    public function index()
    {
        $plans = Plan::all();        
        // Get order statistics for authenticated user
        $userId = auth()->id();
        $orders = Order::where('user_id', $userId);
        
        $totalOrders = $orders->count();
        
        // Get orders by admin status
        $pendingOrders = Order::where('user_id', $userId)
            ->where('status_manage_by_admin', 'pending')
            ->count();
            
        $completedOrders = Order::where('user_id', $userId)
            ->where('status_manage_by_admin', 'completed')
            ->count();
            
        $inProgressOrders = Order::where('user_id', $userId)
            ->where('status_manage_by_admin', 'in-progress')
            ->count();
        $expiredOrders = Order::where('user_id', $userId)
            ->where('status_manage_by_admin', 'expired')
            ->count();

        $rejectOrders = Order::where('user_id', $userId)
            ->where('status_manage_by_admin', 'reject')
            ->count();

        $cancelledOrders = Order::where('user_id', $userId)
            ->where('status_manage_by_admin', 'cancelled')
            ->count();

        $draftOrders = Order::where('user_id', $userId)
            ->where('status_manage_by_admin', 'draft')
            ->count();
            
        // Calculate percentage changes (last week vs previous week)
        $lastWeek = [Carbon::now()->subWeek(), Carbon::now()];
        $previousWeek = [Carbon::now()->subWeeks(2), Carbon::now()->subWeek()];

        $lastWeekOrders = Order::where('user_id', $userId)
            ->whereBetween('created_at', $lastWeek)
            ->count();
            
        $previousWeekOrders = Order::where('user_id', $userId)
            ->whereBetween('created_at', $previousWeek)
            ->count();

        $percentageChange = $previousWeekOrders > 0 
            ? (($lastWeekOrders - $previousWeekOrders) / $previousWeekOrders) * 100 
            : 0;
        $statuses = $this->statuses;
        $plans = [];
        return view('customer.orders.orders', compact(
            'plans', 
            'totalOrders', 
            'pendingOrders', 
            'completedOrders', 
            'inProgressOrders',
            'percentageChange',
            'statuses',
            'expiredOrders',
            'rejectOrders',
            'cancelledOrders',
            'draftOrders'
        ));
    }
    // edit
    public function edit($id)
    {
        $order = Order::with(['plan', 'reorderInfo'])->findOrFail($id);
        $plan = $order->plan;
        // dd($order);
        $hostingPlatforms = HostingPlatform::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $sendingPlatforms = \App\Models\SendingPlatform::get();
        
        return view('customer.orders.edit-order', compact('plan', 'hostingPlatforms', 'sendingPlatforms', 'order'));
    }
    // neworder
    public function newOrder(Request $request, $id = 1)
    {
        $hostingPlatforms = HostingPlatform::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $sendingPlatforms = \App\Models\SendingPlatform::get();

        $plan = null; // No plan selected initially, will be determined based on inboxes
        if($id){
            $plan = Plan::findOrFail($id);
        } else {
            // If no plan is selected, default to the first plan
            $plan = Plan::first();
        }
        $order = null; // No existing order for new orders
        // Store session data if validation passes
        // $request->session()->put('order_info', [
        //     'plan_id' => $plan->id,
        //     'user_id' => auth()->id(),
        //     'forwarding_url' => '',
        //     'hosting_platform' => '',
        //     'other_platform' => '',
        //     'platform_login' => '',
        //     'platform_password' => '',
        //     'backup_codes' => '',
        //     'domains' => '',
        //     'sending_platform' => '',
        //     'sequencer_login' => '',
        //     'sequencer_password' => '',
        //     'total_inboxes' => $plan->max_inbox =="0" ? $plan->min_inbox : $plan->max_inbox,
        //     'inboxes_per_domain' => 0,
        //     'first_name' => '',
        //     'last_name' => '',
        //     'prefix_variant_1' => '',
        //     'prefix_variant_2' => '',
        //     'persona_password' => '',
        //     'profile_picture_link' => '',
        //     'email_persona_password' => '',
        //     'email_persona_picture_link' => '',
        //     'master_inbox_email' => '',
        //     'additional_info' => '',
        //     'coupon_code' => ''
        // ]);
        return view('customer.orders.open-chargebee', compact('plan', 'hostingPlatforms', 'sendingPlatforms', 'order'));
        
        // return view('customer.orders.new-order', compact('plan', 'hostingPlatforms', 'sendingPlatforms', 'order'));
    }
    public function reorder(Request $request, $order_id)
    {
        $order = Order::with(['plan', 'reorderInfo'])->findOrFail($order_id);
        $plan = $order->plan;
        $hostingPlatforms = HostingPlatform::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $sendingPlatforms = \App\Models\SendingPlatform::get();
            
        return view('customer.orders.reorder', compact('plan', 'hostingPlatforms', 'sendingPlatforms', 'order'));
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
        $order = Order::with(['subscription', 'user', 'invoice', 'reorderInfo'])->findOrFail($id);
        $order->status2 = strtolower($order->status_manage_by_admin);
        $order->color_status2 = $this->statuses[$order->status2] ?? 'secondary';
        // Retrieve subscription metadata if available
        $subscriptionMeta = [];
        if ($order->subscription) {
            $subscriptionMeta = json_decode($order->subscription->meta ?? '{}', true);
        }
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
            
            // Calculate period based on billing unit
            $period = match($billingPeriodUnit) {
                'month' => $periodStart->diffInMonths($periodEnd),
                'year' => $periodStart->diffInYears($periodEnd),
                'week' => $periodStart->diffInWeeks($periodEnd),
                'day' => $periodStart->diffInDays($periodEnd),
                default => $periodStart->diffInDays($periodEnd)
            };
            
            $nextBillingInfo = [
                'status' => $order->subscription->status,
                'billing_period' => $billingPeriod,
                'billing_period_unit' => $billingPeriodUnit,
                'current_term_start' => $this->formatTimestampToReadable($startDate),
                // 'current_term_end' => $this->formatTimestampToReadable($endDate),
                'period' => $period,
                'period_unit' => $billingPeriodUnit,
                // 'next_billing_at' => $endDate ? null : $this->calculateNextBillingDate(
                //     Carbon::parse($startDate)->timestamp,
                //     $billingPeriod,
                //     $billingPeriodUnit
                // )->format('F d, Y')
                'current_term_end' => $order->subscription->last_billing_date ? Carbon::parse($order->subscription->last_billing_date)->format('F d, Y') : ($endDate ? Carbon::parse($endDate)->format('F d, Y') : null),
                'next_billing_at' => $order->subscription->next_billing_date ? Carbon::parse($order->subscription->next_billing_date)->format('F d, Y') : null
            ];
            // dd($nextBillingInfo);
        }
        $statuses = $this->statuses;
        return view('customer.orders.order-view', compact('order', 'nextBillingInfo', 'statuses'));
    }

    public function getOrders(Request $request)
    {

        try {
            $orders = Order::query()
                ->with(['user', 'plan', 'reorderInfo'])
                ->select('orders.*')
                ->leftJoin('plans', 'orders.plan_id', '=', 'plans.id')
                ->where('orders.user_id', auth()->id());

            // Apply plan filter if provided
            if ($request->has('plan_id') && $request->plan_id !== '' && $request->plan_id != null) {   
                $orders->where('orders.plan_id', $request->plan_id);
            }

            // Apply other filters
            if ($request->has('orderId') && $request->orderId != '') {
                $orders->where('orders.id', 'like', "%{$request->orderId}%");
            }

            if ($request->has('status') && $request->status != '') {
                $orders->where('orders.status_manage_by_admin', $request->status);
            }

            if ($request->has('email') && $request->email != '') {
                $orders->whereHas('user', function($query) use ($request) {
                    $query->where('email', 'like', "%{$request->email}%");
                });
            }

            if ($request->has('domain') && $request->domain != '') {
                $orders->whereHas('reorderInfo', function($query) use ($request) {
                    $query->where('forwarding_url', 'like', "%{$request->domain}%");
                });
            }
             
            if ($request->has('totalInboxes') && $request->totalInboxes != '') {
                $orders->whereHas('reorderInfo', function($query) use ($request) {
                    $query->whereRaw('(
                        CASE 
                            WHEN domains IS NOT NULL AND domains != "" THEN 
                                (LENGTH(domains) - LENGTH(REPLACE(REPLACE(REPLACE(domains, ",", ""), CHAR(10), ""), CHAR(13), "")) + 1) * inboxes_per_domain
                            ELSE total_inboxes 
                        END
                    ) = ?', [$request->totalInboxes]);
                });
            }

            // Special filters for import functionality
            if ($request->has('for_import') && $request->for_import) {
                // Only show orders with reorder info for import
                $orders->whereHas('reorderInfo');
                
                // Exclude current order if editing
                if ($request->has('exclude_current') && $request->exclude_current) {
                    $orders->where('orders.id', '!=', $request->exclude_current);
                }
            }

            if ($request->has('startDate') && $request->startDate != '') {
                $orders->whereDate('orders.created_at', '>=', $request->startDate);
            }

            if ($request->has('endDate') && $request->endDate != '') {
                $orders->whereDate('orders.created_at', '<=', $request->endDate);
            }

            return DataTables::of($orders)
                ->addColumn('action', function ($order) use ($request) {
                    if ($request->has('for_import') && $request->for_import) {
                        // Import action button
                        return '<button class="btn btn-sm btn-primary import-order-btn" data-order-id="' . $order->id . '" title="Import this order data">
                                    <i class="fa-solid fa-file-import"></i> Import
                                </button>';
                    } else {
                        // Check if order has rejected order panels for conditional button
                        $hasRejectedPanels = $order->orderPanels()->where('status', 'rejected')->exists();
                        // Default action buttons
                        return '<div class="dropdown">
                                    <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="' . route('customer.orders.view', $order->id) . '">
                                            <i class="fa-solid fa-eye"></i> View</a></li>
                                        ' . (in_array(strtolower($order->status_manage_by_admin ?? 'n/a'), ['draft']) ? '<li><a class="dropdown-item" href="' . route('customer.order.edit', $order->id) . '">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit Order</a></li>' : '') . '
                                        ' . ($hasRejectedPanels ? '<li><a class="dropdown-item" href="' . route('customer.orders.fix-domains', $order->id) . '">
                                            <i class="fa-solid fa-tools"></i> Fixed Domains Split</a></li>' : '') . '
                                    </ul>
                                </div>';
                    }
                })
                ->editColumn('created_at', function ($order) {
                    return $order->created_at ? $order->created_at->format('d F, Y') : '';
                })
                ->addColumn('created_at_formatted', function ($order) {
                    return $order->created_at ? $order->created_at->format('d F, Y') : '';
                })
                ->addColumn('status_badge', function ($order) {
                    $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                    $statusKey = $status;
                    $statusClass = $this->statuses[$statusKey] ?? 'secondary';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($status) . '</span>';
                })
                ->addColumn('domains_preview', function ($order) {
                    if (!$order->reorderInfo || $order->reorderInfo->isEmpty()) {
                        return 'N/A';
                    }
                    $domains = $order->reorderInfo->first()->domains ?? '';
                    return str_replace(',', "\n", $domains);
                })
                ->editColumn('status', function ($order) {
                    $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                    $statusKey = $status;
                    // dd($order->status_manage_by_admin, $statusKey);
                    $statusClass = $this->statuses[$statusKey] ?? 'secondary';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($status) . '</span>';
                })
                ->addColumn('domain_forwarding_url', function ($order) {
                    // Since reorderInfo is a hasMany relationship, we need to get the first item from the collection
                    if (!$order->reorderInfo || $order->reorderInfo->isEmpty()) {
                        return 'N/A';
                    }
                    return $order->reorderInfo->first()->forwarding_url ?? 'N/A';
                })
                ->addColumn('plan_name', function ($order) {
                    return $order->plan ? $order->plan->name : 'N/A';
                })
                ->addColumn('total_inboxes', function ($order) {
                    if(!$order->reorderInfo || $order->reorderInfo->isEmpty()) {
                        return 'N/A';
                    }
                    
                    $reorderInfo = $order->reorderInfo->first();
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
                ->filterColumn('domain_forwarding_url', function($query, $keyword) {
                    $query->whereHas('reorderInfo', function($q) use ($keyword) {
                        $q->where('forwarding_url', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('plan_name', function($query, $keyword) {
                    $query->whereHas('plan', function($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('email', function($query, $direction) {
                    $query->orderBy(
                        User::select('email')
                            ->whereColumn('users.id', 'orders.user_id')
                            ->latest()
                            ->take(1),
                        $direction
                    );
                })
                ->orderColumn('domain_forwarding_url', function($query, $direction) {
                    $query->orderBy(
                        User::select('domain_forwarding_url')
                            ->whereColumn('users.id', 'orders.user_id')
                            ->latest()
                            ->take(1),
                        $direction
                    );
                })
                ->orderColumn('plan_name', function($query, $direction) {
                    $query->orderBy(
                        Plan::select('name')
                            ->whereColumn('plans.id', 'orders.plan_id')
                            ->latest()
                            ->take(1),
                        $direction
                    );
                })
                ->rawColumns(['action','status'])
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
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'plan_id' => 'required|exists:plans,id',
                'forwarding_url' => 'required|string|max:255',
                'hosting_platform' => 'required|string|max:50',
                'other_platform' => 'nullable|required_if:hosting_platform,other|string|max:50',
                'platform_login' => 'nullable|string|max:255',
                'platform_password' => 'nullable|string|min:3',
                'backup_codes' => 'required_if:hosting_platform,namecheap|string',
                'domains' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        $domains = array_filter(preg_split('/[\r\n,]+/', $value));
                        if (count($domains) !== count(array_unique($domains))) {
                            $fail('Duplicate domains are not allowed.');
                        }
                        foreach ($domains as $domain) {
                            if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/', trim($domain))) {
                                $fail('Invalid domain format: ' . trim($domain));
                            }
                        }
                    }
                ],
                'sending_platform' => 'required|string|max:50',
                'sequencer_login' => 'required|email|max:255',
                'sequencer_password' => 'required|string|min:3',
                'total_inboxes' => 'required|integer|min:1',
                'inboxes_per_domain' => 'required|integer|min:1|max:3',
                'first_name' => 'required|string|max:50',
                'last_name' => 'required|string|max:50',
                'prefix_variants' => 'required|array|min:1',
                'prefix_variants.prefix_variant_1' => 'required|string|max:50',
                'prefix_variants.prefix_variant_2' => 'nullable|string|max:50',
                'prefix_variants.prefix_variant_3' => 'nullable|string|max:50',
                // 'persona_password' => 'required|string|min:3',
                'profile_picture_link' => 'nullable|url|max:255',
                'email_persona_password' => 'required|string|min:3',
                'email_persona_picture_link' => 'nullable|url|max:255',
                'master_inbox_email' => 'nullable|email|max:255',
                'additional_info' => 'nullable|string',
                'coupon_code' => 'nullable|string|max:50'
            ], [
                'other_platform.required_if' => 'Please specify the hosting platform when selecting "Other" option',
                'backup_codes.required_if' => 'Backup codes are required when using Namecheap',
                'inboxes_per_domain.max' => 'You can have maximum 3 inboxes per domain',
                'sequencer_login.email' => 'Sequencer login must be a valid email address',
                'master_inbox_email.email' => 'Master inbox email must be a valid email address',
                'profile_picture_link.url' => 'Profile picture link must be a valid URL',
                'email_persona_picture_link.url' => 'Email persona picture link must be a valid URL'
            ]);
            
            // Additional validation for prefix variants based on inboxes_per_domain
            $inboxesPerDomain = (int) $request->inboxes_per_domain;
            $prefixVariants = $request->prefix_variants ?? [];
            
            // Validate required prefix variants based on inboxes_per_domain
            for ($i = 1; $i <= $inboxesPerDomain; $i++) {
                $prefixKey = "prefix_variant_{$i}";
                if ($i === 1 && empty($prefixVariants[$prefixKey])) {
                    return response()->json([
                        'success' => false,
                        'errors' => ['prefix_variants.prefix_variant_1' => ['The first prefix variant is required.']]
                    ], 422);
                }
                
                // Validate format if value exists
                if (!empty($prefixVariants[$prefixKey])) {
                    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $prefixVariants[$prefixKey])) {
                        return response()->json([
                            'success' => false,
                            'errors' => ["prefix_variants.{$prefixKey}" => ['Only letters, numbers, dots, hyphens and underscores are allowed.']]
                        ], 422);
                    }
                }
            }
            $status = 'draft'; // Default status for new orders
            // persona_password set 123
            $request->persona_password = '123';
            // Calculate number of domains and total inboxes
            $domains = array_filter(preg_split('/[\r\n,]+/', $request->domains));
            $domainCount = count($domains);
            $calculatedTotalInboxes = $domainCount * $request->inboxes_per_domain;

            // Get requested plan
            $plan = Plan::findOrFail($request->plan_id);
            
            // // Verify plan can support the total inboxes
            // $canHandle = ($plan->max_inbox >= $calculatedTotalInboxes || $plan->max_inbox === 0);
                        
            // if (!$canHandle) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => "Configuration exceeds available plan limits. Please contact support for a custom solution.",
            //     ], 422);
            // }

            // Store session data if validation passes
            // $request->session()->put('order_info', $request->all());
            // set new plan_id on session order_info
            // $request->session()->put('order_info.plan_id', $request->plan_id);
            $message = 'Order information saved successfully.';
            
            // for edit order
            if($request->edit_id && $request->order_id){
                $temp_order = Order::with('reorderInfo')->findOrFail($request->order_id);
                $TOTAL_INBOXES = $temp_order->reorderInfo->first()->total_inboxes;
                if($plan && $calculatedTotalInboxes > $TOTAL_INBOXES){
                    return response()->json([
                        'success' => false,
                        'message' => "Configuration exceeds available plan limits. Please contact support for a custom solution.",
                    ], 422);
                }
                $order = Order::with('reorderInfo')->findOrFail($request->order_id);
                
                // Set status based on whether total_inboxes equals calculated total from request domains
                $status = ($TOTAL_INBOXES == $calculatedTotalInboxes) ? 'pending' : 'draft';
                
                $order->update([
                    'status_manage_by_admin' => $status,
                ]);
                // Get the current session data
                $orderInfo = $request->session()->get('order_info', []);
                
                // Update session with reorder info data (preserve new form input over old data)
                if($order->reorderInfo && !$order->reorderInfo->isEmpty()) {
                    $reorderInfo = $order->reorderInfo->first();
                    // update data on table ReorderInfo
                    ReorderInfo::where('id', $reorderInfo->id)->update([
                        'user_id' => $request->user_id,
                        'plan_id' => $request->plan_id,
                        'forwarding_url' => $request->forwarding_url,
                        'hosting_platform' => $request->hosting_platform,
                        'other_platform' => $request->other_platform,
                        'bison_url' => $request->bison_url,
                        'bison_workspace' => $request->bison_workspace,
                        'backup_codes' => $request->backup_codes,
                        'platform_login' => $request->platform_login,
                        'platform_password' => $request->platform_password,
                        'domains' => implode(',', array_filter($domains)),
                        'sending_platform' => $request->sending_platform,
                        'sequencer_login' => $request->sequencer_login,
                        'sequencer_password' => $request->sequencer_password,
                        // 'total_inboxes' => $calculatedTotalInboxes,
                        'inboxes_per_domain' => $request->inboxes_per_domain,
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                        'prefix_variants' => $request->prefix_variants,
                        'persona_password' => $request->persona_password,
                        'profile_picture_link' => $request->profile_picture_link,
                        'email_persona_password' => $request->email_persona_password,
                        'email_persona_picture_link' => $request->email_persona_picture_link,
                        'master_inbox_email' => $request->master_inbox_email,
                        'additional_info' => $request->additional_info,
                        'coupon_code' => $request->coupon_code,
                    ]);
                   $message = 'Order information updated successfully.';
                   // Create a new activity log using the custom log service
                    ActivityLogService::log(
                        'customer-order-update',
                        'Order updated: '. $order->id,
                        $order, 
                        [
                            'user_id' => $request->user_id,
                            'plan_id' => $request->plan_id,
                            'forwarding_url' => $request->forwarding_url,
                            'hosting_platform' => $request->hosting_platform,
                            'other_platform' => $request->other_platform,
                            'bison_url' => $request->bison_url,
                            'bison_workspace' => $request->bison_workspace,
                            'backup_codes' => $request->backup_codes,
                            'platform_login' => $request->platform_login,
                            'platform_password' => $request->platform_password,
                            'domains' => implode(',', array_filter($domains)),
                            'sending_platform' => $request->sending_platform,
                            'sequencer_login' => $request->sequencer_login,
                            'sequencer_password' => $request->sequencer_password,
                            'total_inboxes' => $calculatedTotalInboxes,
                            'inboxes_per_domain' => $request->inboxes_per_domain,
                            'first_name' => $request->first_name,
                            'last_name' => $request->last_name,
                            'prefix_variants' => $request->prefix_variants,
                            'persona_password' => $request->persona_password,
                            'profile_picture_link' => $request->profile_picture_link,
                            'email_persona_password' => $request->email_persona_password,
                            'email_persona_picture_link' => $request->email_persona_picture_link,
                            'master_inbox_email' => $request->master_inbox_email,
                            'additional_info' => $request->additional_info,
                        ]
                    );
                }
                // 
                // Send email to admin and customer when order is edited
                try {
                    // Get user information
                    $user = User::findOrFail($request->user_id);
                    $reorderInfo = $order->reorderInfo->first();
                    
                    // Send notification to the customer
                    Mail::to($user->email)
                        ->queue(new OrderEditedMail($order, $user, $reorderInfo, [], false));
                    // dd(config('mail.admin_address', 'admin@example.com'));
                    // Send notification to admin
                    Mail::to(config('mail.admin_address', 'admin@example.com'))
                        ->queue(new OrderEditedMail($order, $user, $reorderInfo, [], true));
                    
                    // Check if the order has an assigned contractor
                    if ($order->assigned_to ) {
                        // Get the assigned contractor
                        $contractor = User::find($order->assigned_to);
                        // dd($contractor);
                        // Send notification to the assigned contractor if found
                        if ($contractor) {
                            Mail::to($contractor->email)
                                ->queue(new OrderEditedMail($order, $user, $reorderInfo, [], true));
                        }
                    } else {
                        // No assigned contractor, log this information
                        Log::info('No contractor assigned to order #' . $order->id . ' for edit notification');
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send order edit notification emails: ' . $e->getMessage());
                    // Continue execution - don't let email failure stop the process
                }
            }
            
            // status is pending then pannelCreationAndOrderSplitOnPannels
            if($status == 'pending'){
                // panel creation
                $this->pannelCreationAndOrderSplitOnPannels($order);
            }
            // First check 
            return response()->json([
                'success' => true,
                'message' => $message,
                'plan_id' => $request->plan_id,
                'user_id' => $request->user_id,
                'status' => $status
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 422);
        }
    }
    // pannelCreationAndOrderSplitOnPannels
    public function pannelCreationAndOrderSplitOnPannels($order)
    {
        try {
            // Wrap everything in a database transaction for consistency
            DB::beginTransaction();
            
            // Get the reorder info for this order
            $reorderInfo = $order->reorderInfo()->first();
            
            if (!$reorderInfo) {
                Log::warning("No reorder info found for order #{$order->id}");
                DB::rollBack();
                return;
            }
            
            // Calculate total space needed
            $domains = array_filter(preg_split('/[\r\n,]+/', $reorderInfo->domains));
            $domainCount = count($domains);
            $totalSpaceNeeded = $domainCount * $reorderInfo->inboxes_per_domain;
            
            Log::info("Panel creation started for order #{$order->id}", [
                'total_space_needed' => $totalSpaceNeeded,
                'domain_count' => $domainCount,
                'inboxes_per_domain' => $reorderInfo->inboxes_per_domain
            ]);
            
            // Decision point: >= 1790 creates new panels, < 1790 tries to use existing panels
            // if ($totalSpaceNeeded >= 1790) {
            //     $this->createNewPanel($order, $reorderInfo, $domains, $totalSpaceNeeded);
            // } else {
                // Try to find existing panel with sufficient space
                $suitablePanel = $this->findSuitablePanel($totalSpaceNeeded);
                
                if ($suitablePanel) {
                    // Assign entire order to this panel
                    $this->assignDomainsToPanel($suitablePanel, $order, $reorderInfo, $domains, $totalSpaceNeeded, 1);
                    Log::info("Order #{$order->id} assigned to existing panel #{$suitablePanel->id}");
                } else {
                    // No single panel can fit, try intelligent splitting across available panels
                    $this->handleOrderSplitAcrossAvailablePanels($order, $reorderInfo, $domains, $totalSpaceNeeded);
                }
            // }
            
            DB::commit();
            Log::info("Panel creation completed successfully for order #{$order->id}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Panel creation failed for order #{$order->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
    
    /**
     * Create new panel(s) for orders >= 1790 inboxes
     */
    private function createNewPanel($order, $reorderInfo, $domains, $spaceNeeded)
    {
        if ($spaceNeeded > 1790) {
            // Split across multiple panels
            $this->splitOrderAcrossMultiplePanels($order, $reorderInfo, $domains, $spaceNeeded);
        } else {
            // Create exactly one panel
            $panel = $this->createSinglePanel($spaceNeeded);
            $this->assignDomainsToPanel($panel, $order, $reorderInfo, $domains, $spaceNeeded, 1);
            Log::info("Created single panel #{$panel->id} for order #{$order->id}");
        }
    }
    
    /**
     * Split large orders across multiple new panels
    */

    private function splitOrderAcrossMultiplePanels($order, $reorderInfo, $domains, $totalSpaceNeeded)
    {
        $remainingSpace = $totalSpaceNeeded;
        $splitNumber = 1;
        $domainsProcessed = 0;
        
        while ($remainingSpace > 0 && $domainsProcessed < count($domains) && $splitNumber <= 20) { // Safety check to prevent infinite loops
            $spaceForThisPanel = min(1790, $remainingSpace);
            
            // Calculate maximum domains that can fit in this panel without exceeding capacity
            $maxDomainsForThisPanel = floor($spaceForThisPanel / $reorderInfo->inboxes_per_domain);
            
            // Ensure we don't process more domains than remaining
            $remainingDomains = count($domains) - $domainsProcessed;
            $domainsForThisPanel = min($maxDomainsForThisPanel, $remainingDomains);
            
            Log::info("Panel split calculation", [
                'split_number' => $splitNumber,
                'space_for_panel' => $spaceForThisPanel,
                'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
                'max_domains_for_panel' => $maxDomainsForThisPanel,
                'remaining_domains' => $remainingDomains,
                'domains_for_this_panel' => $domainsForThisPanel,
                'domains_processed_so_far' => $domainsProcessed
            ]);
            
            // Extract domains for this panel
            $domainsToAssign = array_slice($domains, $domainsProcessed, $domainsForThisPanel);
            $actualSpaceUsed = count($domainsToAssign) * $reorderInfo->inboxes_per_domain;
            
            $panel = null;

            // If remaining space is less than 1790, first try to fill existing panels
            if ($remainingSpace < 1790) {
                // Try to find existing panel with sufficient space
                $existingPanel = Panel::where('is_active', true)
                    ->where('remaining_limit', '>=', $actualSpaceUsed)
                    ->orderBy('remaining_limit', 'desc') // Use panel with least available space first
                    ->first();
                
                if ($existingPanel) {
                    $panel = $existingPanel;
                    Log::info("Using existing panel #{$panel->id} for remaining space < 1790", [
                        'remaining_space' => $remainingSpace,
                        'space_needed' => $actualSpaceUsed,
                        'panel_available_space' => $panel->remaining_limit
                    ]);
                }
            }
            
            // If no suitable existing panel found or remaining space >= 1790, create new panel
            if (!$panel) {
                $panel = $this->createSinglePanel(1790);
                Log::info("Created new panel #{$panel->id} (split #{$splitNumber}) for order #{$order->id}", [
                    'remaining_space' => $remainingSpace,
                    'space_needed' => $actualSpaceUsed,
                    'reason' => $remainingSpace >= 1790 ? 'remaining_space >= 1790' : 'no_existing_panel_available'
                ]);
            }
            
            // Assign domains to this panel
            $this->assignDomainsToPanel($panel, $order, $reorderInfo, $domainsToAssign, $actualSpaceUsed, $splitNumber);
            
            Log::info("Assigned to panel #{$panel->id} (split #{$splitNumber}) for order #{$order->id}", [
                'space_used' => $actualSpaceUsed,
                'domains_count' => count($domainsToAssign),
                'remaining_space' => $remainingSpace - $actualSpaceUsed,
                'panel_type' => $panel->wasRecentlyCreated ? 'new' : 'existing'
            ]);
            
            $remainingSpace -= $actualSpaceUsed;
            $domainsProcessed += count($domainsToAssign);
            $splitNumber++;
        }
        
        // Check if all domains have been processed
        $totalDomainsToProcess = count($domains);
        if ($domainsProcessed < $totalDomainsToProcess) {
            $remainingDomains = array_slice($domains, $domainsProcessed);
            $remainingSpace = count($remainingDomains) * $reorderInfo->inboxes_per_domain;
            
            Log::warning("Some domains were not processed, creating additional panel", [
                'order_id' => $order->id,
                'domains_processed' => $domainsProcessed,
                'total_domains' => $totalDomainsToProcess,
                'remaining_domains' => count($remainingDomains),
                'remaining_space' => $remainingSpace
            ]);
            
            // Create additional panel for remaining domains
            $panel = $this->createSinglePanel(1790);
            $this->assignDomainsToPanel($panel, $order, $reorderInfo, $remainingDomains, $remainingSpace, $splitNumber);
        }
        
        if ($remainingSpace > 0) {
            Log::warning("Still have remaining space after panel creation", [
                'order_id' => $order->id,
                'remaining_space' => $remainingSpace
            ]);
        }
    }
    
    /**
     * Handle intelligent splitting across existing available panels
     */
    private function handleOrderSplitAcrossAvailablePanels($order, $reorderInfo, $domains, $totalSpaceNeeded)
    {
        // Get all panels with available space, ordered by remaining space (least first for optimal allocation)
        $availablePanels = Panel::where('is_active', true)
            ->where('remaining_limit', '>', 0)
            ->orderBy('remaining_limit', 'desc')
            ->get();
        
        if ($availablePanels->isEmpty()) {
            // No available panels, create new one
            $panel = $this->createSinglePanel(1790);
            $this->assignDomainsToPanel($panel, $order, $reorderInfo, $domains, $totalSpaceNeeded, 1);
            Log::info("No available panels found, created new panel #{$panel->id} for order #{$order->id}");
            return;
        }
        
        $remainingSpace = $totalSpaceNeeded;
        $domainsProcessed = 0;
        $splitNumber = 1;
        
        foreach ($availablePanels as $panel) {
            if ($remainingSpace <= 0) break;
            
            $availableSpace = $panel->remaining_limit;
            $spaceToUse = min($availableSpace, $remainingSpace);
            
            // Calculate maximum domains that can fit in available space without exceeding capacity
            $maxDomainsForSpace = floor($spaceToUse / $reorderInfo->inboxes_per_domain);
            
            // Ensure we don't process more domains than remaining
            $remainingDomains = count($domains) - $domainsProcessed;
            $domainsToAssign = min($maxDomainsForSpace, $remainingDomains);
            
            // Extract domains for this panel
            $domainSlice = array_slice($domains, $domainsProcessed, $domainsToAssign);
            $actualSpaceUsed = count($domainSlice) * $reorderInfo->inboxes_per_domain;
            
            // Only proceed if we can actually use this panel
            if ($actualSpaceUsed <= $availableSpace && count($domainSlice) > 0) {
                $this->assignDomainsToPanel($panel, $order, $reorderInfo, $domainSlice, $actualSpaceUsed, $splitNumber);
                Log::info("Assigned to existing panel #{$panel->id} (split #{$splitNumber}) for order #{$order->id}", [
                    'space_used' => $actualSpaceUsed,
                    'domains_count' => count($domainSlice),
                    'panel_remaining_before' => $availableSpace,
                    'panel_remaining_after' => $availableSpace - $actualSpaceUsed
                ]);
                
                $remainingSpace -= $actualSpaceUsed;
                $domainsProcessed += count($domainSlice);
                $splitNumber++;
            }
        }
        
        // Check if all domains have been processed
        $totalDomainsToProcess = count($domains);
        if ($domainsProcessed < $totalDomainsToProcess) {
            $remainingDomains = array_slice($domains, $domainsProcessed);
            $remainingSpace = count($remainingDomains) * $reorderInfo->inboxes_per_domain;
            
            Log::info("Processing remaining domains not assigned to existing panels", [
                'order_id' => $order->id,
                'domains_processed' => $domainsProcessed,
                'total_domains' => $totalDomainsToProcess,
                'remaining_domains' => count($remainingDomains),
                'remaining_space' => $remainingSpace
            ]);
            
            if (!empty($remainingDomains)) {
                $panel = $this->createSinglePanel(1790);
                $this->assignDomainsToPanel($panel, $order, $reorderInfo, $remainingDomains, $remainingSpace, $splitNumber);
                Log::info("Created additional panel #{$panel->id} for remaining domains in order #{$order->id}", [
                    'remaining_domains' => count($remainingDomains),
                    'remaining_space' => $remainingSpace
                ]);
            }
        }
        
        // Legacy check for remaining space (should be covered by domain check above)
        if ($remainingSpace > 0 && $domainsProcessed >= $totalDomainsToProcess) {
            Log::warning("Remaining space detected but all domains processed - this should not happen", [
                'order_id' => $order->id,
                'remaining_space' => $remainingSpace,
                'domains_processed' => $domainsProcessed,
                'total_domains' => $totalDomainsToProcess
            ]);
        }
    }
    
    /**
     * Find suitable existing panel with sufficient space
     */
    private function findSuitablePanel($spaceNeeded)
    {
        return Panel::where('is_active', true)
            ->where('remaining_limit', '>=', $spaceNeeded)
            ->orderBy('remaining_limit', 'desc') // Use panel with least available space first
            ->first();
    }
    
    /**
     * Create a single panel with specified capacity
     */
    private function createSinglePanel($capacity = 1790)
    {
        $panel = Panel::create([
            'auto_generated_id' => 'PANEL_' . strtoupper(uniqid()),
            'title' => 'Auto Generated Panel - ' . date('Y-m-d H:i:s'),
            'description' => 'Automatically created panel for order processing',
            'limit' => $capacity,
            'remaining_limit' => $capacity,
            'is_active' => true,
            'created_by' => 'system'
        ]);
        
        Log::info("Created new panel #{$panel->id} with capacity {$capacity}");
        return $panel;
    }
    
    /**
     * Assign domains to a specific panel and create all necessary records
     */
    private function assignDomainsToPanel($panel, $order, $reorderInfo, $domainsToAssign, $spaceToAssign, $splitNumber)
    {
        try {
            // Create order_panel record
            $orderPanel = OrderPanel::create([
                'panel_id' => $panel->id,
                'order_id' => $order->id,
                'contractor_id' => null, // Will be assigned later
                'space_assigned' => $spaceToAssign,
                'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
                'status' => 'unallocated',
                'note' => "Auto-assigned split #{$splitNumber} - {$spaceToAssign} inboxes across " . count($domainsToAssign) . " domains"
            ]);
            
            // Create order_panel_split record
            OrderPanelSplit::create([
                'panel_id' => $panel->id,
                'order_panel_id' => $orderPanel->id,
                'order_id' => $order->id,
                'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
                'domains' => $domainsToAssign
            ]);
            
            // Update panel remaining capacity
            $panel->decrement('remaining_limit', $spaceToAssign);
            // Ensure remaining_limit never goes below 0
            if ($panel->remaining_limit < 0) {
                $panel->update(['remaining_limit' => 0]);
            }
            
            Log::info("Successfully assigned domains to panel", [
                'panel_id' => $panel->id,
                'order_id' => $order->id,
                'order_panel_id' => $orderPanel->id,
                'space_assigned' => $spaceToAssign,
                'domains_count' => count($domainsToAssign),
                'panel_remaining_limit' => $panel->remaining_limit - $spaceToAssign
            ]);
            
        } catch (\Exception $e) {
            Log::error("Failed to assign domains to panel", [
                'panel_id' => $panel->id,
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    /**
     * Get order data for import functionality
     */
    public function getOrderImportData($orderId)
    {
        try {
            $order = Order::with(['plan', 'reorderInfo'])
                ->where('id', $orderId)
                ->where('user_id', auth()->id())
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or you do not have permission to access it.'
                ], 404);
            }

            if (!$order->reorderInfo || $order->reorderInfo->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No detailed information available for this order.'
                ], 404);
            }

            $reorderInfo = $order->reorderInfo->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                    'plan' => $order->plan,
                    'reorder_info' => $reorderInfo
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching order import data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching order data.'
            ], 500);
        }
    }

    // deleteAllOrderNullPlanID
    public function deleteAllOrderNullPlanID(Request $request)
    {
        try {
            $orders = Order::where('plan_id', null)->get();
            foreach ($orders as $order) {
                // Delete related reorder_info records first
                ReorderInfo::where('order_id', $order->id)->delete();
                // Then delete the order
                $order->delete();
            }
            return response()->json([
                'success' => true,
                'message' => 'All orders with null plan_id and their reorder info have been deleted successfully.'
            ]);
        } catch (Exception $e) {
            Log::error('Error deleting orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete orders: ' . $e->getMessage()
            ], 422);
        }
    }
    // updateOrderStatusToLowerCase
    public function updateOrderStatusToLowerCase(Request $request)
    {
        try {
            $orders = Order::all();
            foreach ($orders as $order) {
                $order->status_manage_by_admin = strtolower($order->status_manage_by_admin);
                $order->save();
            }
            return response()->json([
                'success' => true,
                'message' => 'All order statuses have been updated to lowercase successfully.'
            ]);
        } catch (Exception $e) {
            Log::error('Error updating order statuses: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order statuses: ' . $e->getMessage()
            ], 422);
        }
    }

    private function getOrderCounts()
    {
        $userId = auth()->id();
        
        return [
            'totalOrders' => Order::where('user_id', $userId)->count(),
            'pendingOrders' => Order::where('user_id', $userId)->where('status_manage_by_admin', 'pending')->count(),
            'completedOrders' => Order::where('user_id', $userId)->where('status_manage_by_admin', 'completed')->count(),
            'inProgressOrders' => Order::where('user_id', $userId)->where('status_manage_by_admin', 'in-progress')->count(),
            'expiredOrders' => Order::where('user_id', $userId)->where('status_manage_by_admin', 'expired')->count(),
            'rejectOrders' => Order::where('user_id', $userId)->where('status_manage_by_admin', 'reject')->count(), 
            'cancelledOrders' => Order::where('user_id', $userId)->where('status_manage_by_admin', 'cancelled')->count(),
            'draftOrders' => Order::where('user_id', $userId)->where('status_manage_by_admin', 'draft')->count()
        ];
    }

    /**
     * Get orders for import modal (DataTables format)
     */
    public function getOrdersForImport(Request $request)
    {
        try {
            $query = Order::with(['plan', 'reorderInfo'])
                ->where('user_id', auth()->id())
                ->where('status_manage_by_admin', '!=', 'draft');

            // Exclude current order if editing
            if ($request->has('exclude_current') && $request->exclude_current) {
                $query->where('id', '!=', $request->exclude_current);
            }

            return DataTables::of($query)
                ->addColumn('domains_preview', function ($order) {
                    if (!$order->reorderInfo || !$order->reorderInfo->first()) {
                        return 'N/A';
                    }
                    
                    $domains = $order->reorderInfo->first()->domains ?? '';
                    return $domains;
                })
                ->addColumn('total_inboxes', function ($order) {
                    if (!$order->reorderInfo || !$order->reorderInfo->first()) {
                        return 0;
                    }
                    
                    $reorderInfo = $order->reorderInfo->first();
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
                    
                    return $calculatedTotalInboxes > 0 ? $calculatedTotalInboxes : ($reorderInfo->total_inboxes ?? 0);
                })
                ->addColumn('status_badge', function ($order) {
                    $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                    $statusClass = $this->statuses[$status] ?? 'secondary';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($status) . '</span>';
                })
                ->addColumn('created_at_formatted', function ($order) {
                    return $order->created_at->format('M d, Y');
                })
                ->addColumn('action', function ($order) use ($request) {
                    if ($request->has('for_import') && $request->for_import) {
                        // Import action button
                        return '<button class="btn btn-sm btn-primary import-order-btn" data-order-id="' . $order->id . '" title="Import this order data">
                                    <i class="fa-solid fa-file-import"></i> Import
                                </button>';
                    } else {
                        // Default action buttons
                        return '<div class="dropdown">
                                    <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown"
                                        aria-expanded="false">
                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="' . route('customer.orders.view', $order->id) . '">
                                            <i class="fa-solid fa-eye"></i> View</a></li>
                                        <li><a class="dropdown-item" href="' . route('customer.orders.reorder', $order->id) . '">
                                            <i class="fa-solid fa-repeat"></i> Reorder</a></li>
                                    </ul>
                                </div>';
                    }
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);

        } catch (Exception $e) {
            Log::error('Error getting orders for import: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load orders'], 500);
        }
    }

    /**
     * Get specific order data for import
     */
    public function importOrderData($id)
    {
        try {
            $order = Order::with(['plan', 'reorderInfo'])
                ->where('id', $id)
                ->where('user_id', auth()->id())
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or access denied.'
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $order->id,
                    'plan' => $order->plan,
                    'reorder_info' => $order->reorderInfo->first()
                ]
            ]);

        } catch (Exception $e) {
            Log::error('Error importing order data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to import order data.'
            ]);
        }
    }

    /**
     * Show the domains fixing interface for rejected order panels
     */
     
    public function showFixDomains($orderId)
    {
        try {
            // Validate orderId
            if (!$orderId || !is_numeric($orderId)) {
                return redirect()->route('customer.orders')
                    ->with('error', 'Invalid order ID.');
            }

            $order = Order::with(['orderPanels.orderPanelSplits', 'user', 'reorderInfo'])
                ->where('id', $orderId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Get only rejected order panels
            $rejectedPanels = $order->orderPanels()->where('status', 'rejected')->get();

            if ($rejectedPanels->isEmpty()) {
                return redirect()->route('customer.orders')
                    ->with('error', 'No rejected panels found for this order.');
            }

            // Get hosting and sending platforms for the configuration form
            try {
                $hostingPlatforms = \App\Models\HostingPlatform::orderBy('sort_order')->get();
            } catch (\Exception $e) {
                Log::warning('Failed to load hosting platforms: ' . $e->getMessage());
                $hostingPlatforms = collect(); // Empty collection as fallback
            }

            try {
                $sendingPlatforms = \App\Models\SendingPlatform::orderBy('id')->get();
            } catch (\Exception $e) {
                Log::warning('Failed to load sending platforms: ' . $e->getMessage());
                $sendingPlatforms = collect(); // Empty collection as fallback
            }

            return view('customer.orders.fix-domains', [
                'order' => $order,
                'rejectedPanels' => $rejectedPanels,
                'hostingPlatforms' => $hostingPlatforms,
                'sendingPlatforms' => $sendingPlatforms
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Order not found in showFixDomains', [
                'orderId' => $orderId,
                'userId' => auth()->id()
            ]);
            return redirect()->route('customer.orders')
                ->with('error', 'Order not found or you do not have permission to access it.');
        } catch (Exception $e) {
            Log::error('Error showing fix domains page: ' . $e->getMessage(), [
                'orderId' => $orderId,
                'userId' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('customer.orders')
                ->with('error', 'Failed to load domain fixing interface: ' . $e->getMessage());
        }
    }

    /**
     * Update domains for rejected order panel splits
     */
    public function updateFixedDomains(Request $request, $orderId)
    {
        try {
            
            $order = Order::with(['orderPanels.orderPanelSplits', 'reorderInfo'])
                ->where('id', $orderId)
                ->where('user_id', auth()->id())
                ->firstOrFail();
            // Validate the request
            $request->validate([
                'panel_splits' => 'required|array',
                'panel_splits.*' => 'required|array',
                'panel_splits.*.domains' => 'required|array|min:1',
                'panel_splits.*.domains.*' => 'required|string',
                // Platform configuration validation
                'forwarding_url' => 'nullable|string|max:255',
                'hosting_platform' => 'nullable|string|max:50',
                'sending_platform' => 'nullable|string|max:50',
                'platform_login' => 'nullable|string|max:255',
                'platform_password' => 'nullable|string|max:255',
                'sequencer_login' => 'nullable|email|max:255',
                'sequencer_password' => 'nullable|string|max:255',
                'backup_codes' => 'nullable|string',
                'bison_url' => 'nullable|url|max:255',
                'bison_workspace' => 'nullable|string|max:255',
                'other_platform' => 'nullable|string|max:255',
                'access_tutorial' => 'nullable|string',
            ]);

            DB::beginTransaction();

            // Update platform configuration in reorder_info if provided
            if ($order->reorderInfo && $order->reorderInfo->isNotEmpty()) {
                $reorderInfo = $order->reorderInfo->first();
                $platformData = [];

                // Collect platform configuration fields that were submitted
                $platformFields = [
                    'forwarding_url', 'hosting_platform', 'sending_platform',
                    'platform_login', 'platform_password', 'sequencer_login', 'sequencer_password',
                    'backup_codes', 'bison_url', 'bison_workspace', 'other_platform', 'access_tutorial'
                ];

                foreach ($platformFields as $field) {
                    if ($request->has($field) && $request->filled($field)) {
                        $platformData[$field] = $request->input($field);
                    }
                }

                // Only update reorder_info if we have platform data to update
                if (!empty($platformData)) {
                    $reorderInfo->update($platformData);
                    Log::info("Updated platform configuration for order #{$order->id}", [
                        'updated_fields' => array_keys($platformData)
                    ]);
                }
            }

            // Collect all updated domains for merging into reorder_info
            $allUpdatedDomains = [];
            
            foreach ($request->panel_splits as $splitId => $splitData) {
                $split = OrderPanelSplit::where('id', $splitId)
                    ->whereHas('orderPanel', function($query) use ($orderId) {
                        $query->where('order_id', $orderId)
                              ->where('status', 'rejected');
                    })
                    ->first();

                if (!$split) {
                    throw new Exception("Invalid split ID: {$splitId}");
                }

                // Validate domain count remains the same
                $originalDomainCount = is_array($split->domains) ? count($split->domains) : 0;
                $newDomainCount = count($splitData['domains']);

                if ($originalDomainCount !== $newDomainCount) {
                    throw new Exception("Domain count must remain the same. Expected: {$originalDomainCount}, Got: {$newDomainCount}");
                }
                $existingDomains = OrderPanelSplit::where('id', '!=', $splitId)
                    ->where('order_panel_id', $split->orderPanel->id)
                    ->pluck('domains')
                    ->flatten()
                    ->toArray();

                $newDomains = $splitData['domains'];

                $duplicates = array_intersect($existingDomains, $newDomains);
                if (!empty($duplicates)) {
                    throw new Exception("Duplicate domains found: " . implode(', ', $duplicates));
                }

                // Update the domains
                $split->domains = $splitData['domains'];
                $split->save();

                // Collect domains for reorder_info update
                $allUpdatedDomains = array_merge($allUpdatedDomains, $splitData['domains']);

                // Update the order panel status to 'allocated' for reprocessing
                $split->orderPanel->status = 'allocated';

                // orders table status update
                $order->status_manage_by_admin = 'pending';
                $order->save();
                $split->orderPanel->save();
            }

            // Get all remaining split domains that weren't updated (non-rejected panels)
            $allOrderSplits = OrderPanelSplit::whereHas('orderPanel', function($query) use ($orderId) {
                $query->where('order_id', $orderId);
            })->get();

            $allDomains = [];
            foreach ($allOrderSplits as $split) {
                if (is_array($split->domains)) {
                    $allDomains = array_merge($allDomains, $split->domains);
                }
            }

            // Remove duplicates and ensure we have a clean array
            $allDomains = array_unique($allDomains);
            $totalDomainsCount = count($allDomains); // Count before converting to string
            $allDomains = implode(',', $allDomains); // Convert array to comma-separated string

            // Update reorder_info domains column with merged domains
            if ($order->reorderInfo && $order->reorderInfo->isNotEmpty()) {
                $reorderInfo = $order->reorderInfo->first();
                $reorderInfo->update(['domains' => $allDomains]);
                
                Log::info("Updated reorder_info domains for order #{$order->id}", [
                    'total_domains' => $totalDomainsCount,
                    'updated_splits' => count($request->panel_splits)
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Domains updated successfully. The order panels have been resubmitted for processing.'
            ]);

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error updating fixed domains: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update domains: ' . $e->getMessage()
            ]);
        }
    }
}
