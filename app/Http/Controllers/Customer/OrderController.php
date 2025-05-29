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
use App\Mail\OrderEditedMail;
use Illuminate\Support\Facades\Mail;
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
                    return '<div class="dropdown">
                                <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="' . route('customer.orders.view', $order->id) . '">
                                        <i class="fa-solid fa-eye"></i> View</a></li>
                                    ' . (in_array(strtolower($order->status_manage_by_admin ?? 'n/a'), ['reject', 'draft']) ? '<li><a class="dropdown-item" href="' . route('customer.order.edit', $order->id) . '">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit Order</a></li>' : '') . '
                                </ul>
                            </div>';
                })
                ->editColumn('created_at', function ($order) {
                    return $order->created_at ? $order->created_at->format('d F, Y') : '';
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
                    // return $order->reorderInfo ? $order->reorderInfo->total_inboxes : 'N/A';
                    if(!$order->reorderInfo || $order->reorderInfo->isEmpty()) {
                        return 'N/A';
                    }
                    return $order->reorderInfo->first()->total_inboxes ?? 'N/A';
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
                'forwarding_url' => 'required|max:255',
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
                'forwarding_url.url' => 'Please enter a valid URL for domain forwarding',
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
            $request->session()->put('order_info', $request->all());
            // set new plan_id on session order_info
            $request->session()->put('order_info.plan_id', $request->plan_id);
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
                $order->update([
                    'status_manage_by_admin' => 'pending',
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
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'plan_id' => $request->plan_id,
                'user_id' => $request->user_id
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
}
