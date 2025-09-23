<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\Subscription;
use App\Models\ReorderInfo;
use App\Models\HostingPlatform;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use App\Models\Log as ModelLog;
use App\Models\Status;
use App\Mail\OrderStatusChangeMail;
use Illuminate\Support\Facades\Mail;
use App\Models\Notification;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit;
use App\Models\OrderEmail;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use App\Services\PanelReassignmentService;
use App\Services\OrderContractorReassignmentService;
use App\Services\SlackNotificationService;
class OrderController extends Controller
{
    private $statuses;
    private $paymentStatuses = [
        "Pending" => "warning",
        "Paid" => "success",
        "Failed" => "danger",
        "Refunded" => "secondary"
    ];
    // split statues
    private $splitStatuses = [
        'completed' => 'success',
        // 'unallocated' => 'warning',
        // 'allocated' => 'info',
        'rejected' => 'danger',
        'in-progress' => 'primary',
        // 'pending' => 'secondary'
    ];

    public function __construct()
    {
        $this->statuses = Status::pluck('badge', 'name')->toArray();
    }
    public function index()
    {
        $plans = Plan::all();
        $userId = auth()->id();
        $orders = Order::all();
        $statuses = $this->statuses;

        $totalOrders = $orders->count();

        $pendingOrders = $orders->where('status_manage_by_admin', 'pending')->count();
        $rejectOrders = $orders->where('status_manage_by_admin', 'reject')->count();
        $inProgressOrders = $orders->where('status_manage_by_admin', 'in-progress')->count();
        $cancelledOrders = $orders->where('status_manage_by_admin', 'cancelled')->count();
        $completedOrders = $orders->where('status_manage_by_admin', 'completed')->count();
        $draftOrders = $orders->where('status_manage_by_admin', 'draft')->count();
        $removedOrders = $orders->where('status_manage_by_admin', 'removed')->count();

        $lastWeek = [Carbon::now()->subWeek(), Carbon::now()];
        $previousWeek = [Carbon::now()->subWeeks(2), Carbon::now()->subWeek()];

        $lastWeekOrders = $orders->whereBetween('created_at', $lastWeek)->count();
        $previousWeekOrders = $orders->whereBetween('created_at', $previousWeek)->count();

        $percentageChange = $previousWeekOrders > 0 
            ? (($lastWeekOrders - $previousWeekOrders) / $previousWeekOrders) * 100 
            : 0;

        return view('admin.orders.orders', compact(
            'plans', 
            'totalOrders', 
            'pendingOrders', 
            'rejectOrders',
            'inProgressOrders',
            'cancelledOrders',
            'completedOrders',
            'draftOrders', 
            'percentageChange',
            'statuses',
            'removedOrders'
        ));
    }

    /**
     * Show shared orders page
     */
    public function sharedOrderRequests()
    {
        $plans = Plan::all();
        $statuses = $this->statuses;
        
        // Get shared orders statistics
        $sharedOrders = Order::where('is_shared', true);
        $totalSharedOrders = $sharedOrders->count();
        $pendingSharedOrders = $sharedOrders->clone()->where('status_manage_by_admin', 'pending')->count();
        $inProgressSharedOrders = $sharedOrders->clone()->where('status_manage_by_admin', 'in-progress')->count();
        $completedSharedOrders = $sharedOrders->clone()->where('status_manage_by_admin', 'completed')->count();
        
        // Calculate percentage change for last week vs previous week
        $lastWeek = [Carbon::now()->subWeek(), Carbon::now()];
        $previousWeek = [Carbon::now()->subWeeks(2), Carbon::now()->subWeek()];

        $lastWeekSharedOrders = $sharedOrders->clone()->whereBetween('created_at', $lastWeek)->count();
        $previousWeekSharedOrders = $sharedOrders->clone()->whereBetween('created_at', $previousWeek)->count();

        $percentageChange = $previousWeekSharedOrders > 0 
            ? (($lastWeekSharedOrders - $previousWeekSharedOrders) / $previousWeekSharedOrders) * 100 
            : 0;

        return view('admin.orders.shared_order_requests', compact(
            'plans',
            'totalSharedOrders',
            'pendingSharedOrders',
            'inProgressSharedOrders',
            'completedSharedOrders',
            'percentageChange',
            'statuses'
        ));
    }

    // neworder
    private function calculateNextBillingDate($currentDate, $billingPeriod, $billingPeriodUnit)
    {
        $date = Carbon::createFromTimestamp($currentDate);
        
        switch ($billingPeriodUnit) {
            case 'month':
                return $date->addMonths($billingPeriod);
            case 'year':
                return $date->addYears($billingPeriod);
            case 'week':
                return $date->addWeeks($billingPeriod);
            case 'day':
                return $date->addDays($billingPeriod);
            default:
                return $date;
        }
    }

    private function formatTimestampToReadable($timestamp)
    {
        if (!$timestamp) return 'N/A';
        // Ensure timestamp is an integer
        $timestamp = is_string($timestamp) ? strtotime($timestamp) : $timestamp;
        return Carbon::createFromTimestamp($timestamp)->format('F d, Y');
    }

    public function view($id)
    {
      
        $order = Order::with(['subscription', 'user', 'invoice', 'reorderInfo'])->findOrFail($id);
        // Retrieve subscription metadata if available to view subs
        $subscriptionMeta = $order->subscription ? json_decode($order->subscription->meta, true) : null;
        $nextBillingInfo = [];
        
        if (isset($subscriptionMeta['subscription'])) {
            $subscription = $subscriptionMeta['subscription'];
            $currentTermStart = $subscription['current_term_start'];
            
            // Calculate next billing date based on billing period
            $nextBillingDate = $this->calculateNextBillingDate(
                $currentTermStart,
                $subscription['billing_period'],
                $subscription['billing_period_unit']
            );

            $nextBillingInfo = [
                'status' => $subscription['status'] ?? null,
                'billing_period' => $subscription['billing_period'] ?? null,
                'billing_period_unit' => $subscription['billing_period_unit'] ?? null,
                'current_term_start' => $this->formatTimestampToReadable($currentTermStart),
                'current_term_end' => $this->formatTimestampToReadable($subscription['current_term_end']),
                'next_billing_at' => $this->formatTimestampToReadable($nextBillingDate->timestamp)
            ];
        }
    
        return view('admin.orders.order-view', compact('order', 'nextBillingInfo'));
    }

    public function getOrders(Request $request)
    {
        try {
            $orders = Order::query()
                ->with(['user', 'plan', 'reorderInfo', 'orderPanels.orderPanelSplits'])
                ->select('orders.*')
                ->leftJoin('plans', 'orders.plan_id', '=', 'plans.id')
                ->leftJoin('users', 'orders.user_id', '=', 'users.id');

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

            // if ($request->has('totalInboxes') && $request->totalInboxes != '') {
            //     $orders->whereHas('reorderInfo', function($query) use ($request) {
            //         $query->where('total_inboxes', $request->totalInboxes);
            //     });
            // }
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

                    $sharedIcon = $order->is_shared ? '<i class="fa-solid fa-share-nodes text-warning me-2" title="Shared Order"></i>' : '';
                    $shareToggleText = $order->is_shared ? 'Unshare' : 'Share';
                    $shareToggleIcon = $order->is_shared ? 'fa-unlink' : 'fa-share-nodes';

                    return '<div class="dropdown">
                    <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="' . route('admin.orders.view', $order->id) . '">
                                <i class="fa-solid fa-eye"></i> &nbsp;View
                            </a>
                        </li>'
                        . (auth()->user()->hasPermissionTo('Mod') ? '' : '
                        
                        <li>
                            <a href="#" class="dropdown-item"  onclick="viewOrderSplits(' . $order->id . ')" 
                                data-order-id="' . $order->id . '">
                                <i class="fa-solid fa-columns"></i> &nbsp;Panel View
                            </a>
                        </li>
                        ') .

                        '<li>
                            <a href="javascript:;" class="dropdown-item" data-bs-toggle="offcanvas" data-bs-target="#actionLogCanvas" aria-controls="actionLogCanvas" data-order-id="' . $order->id . '">
                                <i class="fa-solid fa-history"></i> &nbsp;Log View
                            </a>
                        </li>
                    </ul>
                </div>';

                })
                ->editColumn('id', function ($order) {
                    $sharedIcon = $order->is_shared ? '<i class="fa-solid fa-share-nodes text-warning me-2" title="Shared Order"></i>' : '';
                    $share_request_link = '<a href="' . route('admin.orders.shared-order-requests') . '" class="text-primary">' . $sharedIcon . '</a>';
                    $order_view_link = '<a href="' . route('admin.orders.view', $order->id) . '">' . $order->id . '</a>';
                    return $order_view_link . ' ' . $share_request_link;
                })
                ->editColumn('created_at', function ($order) {
                    return $order->created_at ? $order->created_at->format('d M, Y') : '';
                })
                ->editColumn('status', function ($order) {
                    $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                    $statusKey = $status;
                    // dd($order->status_manage_by_admin, $statusKey);
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
                ->addColumn('split_counts', function ($order) {
                    // Count the number of order panel splits for this order
                    $splitCount = 0;
                    if ($order->orderPanels && $order->orderPanels->count() > 0) {
                        foreach ($order->orderPanels as $orderPanel) {
                            $splitCount += $orderPanel->orderPanelSplits ? $orderPanel->orderPanelSplits->count() : 0;
                        }
                    }
                    return $splitCount > 0 ? $splitCount . ' split(s)' : 'No splits';
                })
                ->addColumn('plan_name', function ($order) {
                    return $order->plan ? $order->plan->name : 'N/A';
                })
                ->addColumn('total_inboxes', function ($order) {
                    if (!$order->reorderInfo || !$order->reorderInfo->first()) {
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
                ->addColumn('timer', function ($order) {
                    // Return timer data as JSON for JavaScript processing
                    return json_encode([
                        'created_at' => $order->created_at ? $order->created_at->toISOString() : null,
                        'status' => strtolower($order->status_manage_by_admin ?? 'n/a'),
                        'completed_at' => $order->completed_at ? $order->completed_at->toISOString() : null,
                        'timer_started_at' => $order->timer_started_at ? $order->timer_started_at->toISOString() : null,
                        'timer_paused_at' => $order->timer_paused_at ? $order->timer_paused_at->toISOString() : null,
                        'total_paused_seconds' => $order->total_paused_seconds ?? 0,
                        'order_id' => $order->id
                    ]);
                })
                // contractor name
                ->addColumn('contractor_name', function ($order) {
                    return $order->assignedTo ? $order->assignedTo->name : 'Unassigned';
                })
                ->rawColumns(['action', 'status', 'timer','id'])
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

    public function updateOrderStatus(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status_manage_by_admin' => 'required|string',
        ]);
    
        $order = Order::findOrFail($request->order_id);
    
        // Log before updating order status
        ActivityLogService::log(
            'order_status_updated', // Action type
            'Order status updated from ' . $order->status_manage_by_admin . ' to ' . $request->status_manage_by_admin . ' : ' . $order->id,
            $order, // The model the action was performed on
            [
                'order_id' => $order->id,
                'previous_status' => $order->status_manage_by_admin, // Previous status before update
                'new_status' => $request->status_manage_by_admin, // New status to be updated
                'admin_user' => Auth::id(), // The admin who is updating the order status
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent')
            ],
            Auth::id() // Who performed the action
        );
    
        // Update the order status
        $order->status_manage_by_admin = $request->status_manage_by_admin;
        $order->save();
    
        return response()->json(['success' => true, 'message' => 'Status updated']);
    }
    

    public function subscriptionCancelProcess(Request $request)
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
                $order->update([
                    'status_manage_by_admin' => $request->marked_status,
                    'reason' => $request->reason ? $request->reason . " (Reason given by " . Auth::user()->name . ")" : null,
                ]);
                $reason =$request->reason;
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

                    // send email to assigned contractor
                    if($order->assigned_to){
                        $assignedUser = User::find($order->assigned_to);
                        if ($assignedUser) {
                            Mail::to($assignedUser->email)
                                ->queue(new OrderStatusChangeMail(
                                    $order,
                                    $user,
                                    $oldStatus,
                                    $newStatus,
                                    $reason,
                                    true
                                ));
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to send order status change emails: ' . $e->getMessage());
                }
    
                // Log the activity
                ActivityLogService::log(
                    'subscription_cancelled', // Action Type
                    'Admin cancelled a subscription order', // Description
                    $order, // Performed On (Order model)
                    [
                        'chargebee_subscription_id' => $request->chargebee_subscription_id,
                        'previous_status' => $oldStatus,
                        'new_status' => $request->marked_status,
                        'reason' => $request->reason,
                        'admin_user' => Auth::user()->email,
                        'ip' => $request->ip(),
                        'user_agent' => $request->header('User-Agent')
                    ],
                    Auth::id() // Performed By
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
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Order Status Updated Successfully.'
            ]);
    
        } catch (\Exception $e) {
            \Log::error('Error While Updating The Status: ' . $e->getMessage());
    
            return response()->json([
                'success' => false,
                'message' => 'Failed To Update The Status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getEndExpiryDate($startDate)
    {
        // $startDate = '2025-04-21 07:02:48'; // Example start date
        $currentDate = Carbon::now(); // Get current date
        $startDateCarbon = Carbon::parse($startDate);

        // Calculate the difference in months
        $monthsToAdd = $currentDate->diffInMonths($startDateCarbon); // Difference in months

        // Calculate the next expiry date
        $expiryDate = $startDateCarbon
            ->addMonths(++$monthsToAdd) // Add the dynamic number of months
            ->subDay()  // Subtract 1 day
            ->format('Y-m-d H:i:s');

        return $expiryDate; // Outputs the dynamically calculated expiry date
    }
    public function indexCard()
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
        return view('admin.orders.card.orders', compact(
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

    public function getCardOrders(Request $request)
    {
        try {
            $query = Order::with(['reorderInfo', 'orderPanels.orderPanelSplits', 'orderPanels.panel', 'user']);
                // ->whereHas('orderPanels.userOrderPanelAssignments', function($q) {
                //     // $q->where('contractor_id', auth()->id());
                // });

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
                
                // Format splits data for the frontend
                $splitsData = [];
                foreach ($orderPanels as $orderPanel) {
                    foreach ($orderPanel->orderPanelSplits as $split) {
                        $domains = [];
                        if ($split->domains && is_array($split->domains)) {
                            $domains = $split->domains;
                        }
                        
                        $splitsData[] = [
                            'id' => $split->id,
                            'order_panel_id' => $orderPanel->id,
                            'panel_id' => $orderPanel->panel_id,
                            'inboxes_per_domain' => $split->inboxes_per_domain,
                            'domains' => $domains,
                            'domains_count' => count($domains),
                            'total_inboxes' => $split->inboxes_per_domain * count($domains),
                            'status' => $orderPanel->status ?? 'unallocated',
                            'created_at' => $split->created_at
                        ];
                    }
                }
                
                return [
                    'id' => $order->id,
                    'order_id' => $order->id,
                    'customer_name' => $order->user->name ?? 'N/A',
                    'customer_image' => $order->user->profile_image ? asset('storage/profile_images/' . $order->user->profile_image) : null,
                    'total_inboxes' => $reorderInfo ? $reorderInfo->total_inboxes : $totalInboxes,
                    'inboxes_per_domain' => $inboxesPerDomain,
                    'contractor_name' => $order->assignedTo ? $order->assignedTo->name : null,
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
                    'timer_started_at' => $order->timer_started_at ? $order->timer_started_at->toISOString() : null,
                    'timer_paused_at' => $order->timer_paused_at ? $order->timer_paused_at->toISOString() : null,
                    'total_paused_seconds' => $order->total_paused_seconds ?? 0,
                    'order_panels_count' => $orderPanels->count(),
                    'splits_count' => $orderPanels->sum(function($panel) {
                        return $panel->orderPanelSplits->count();
                    }),
                    'splits' => $splitsData
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
    public function getOrderSplits($orderId, Request $request)
    {
        try {
            $order = Order::with(['user', 'reorderInfo', 'orderPanels.orderPanelSplits', 'orderPanels.panel'])
                ->findOrFail($orderId);
            
            $reorderInfo = $order->reorderInfo->first();
            $orderPanels = $order->orderPanels;
            
            // Format splits data
            $splitsData = [];
            
            foreach ($orderPanels as $orderPanel) {
                foreach ($orderPanel->orderPanelSplits as $split) {
                    $domains = [];
                    if ($split->domains && is_array($split->domains)) {
                        $domains = $split->domains;
                    }
                    
                    $splitsData[] = [
                        'id' => $split->id,
                        'panel_id' => $orderPanel->panel_id,
                        'panel_title' => $orderPanel->panel->title ?? 'N/A',
                        'order_panel_id' => $orderPanel->id,
                        'inboxes_per_domain' => $split->inboxes_per_domain,
                        'order_panel'=>$orderPanel,
                        'domains' => $domains,
                        'domains_count' => count($domains),
                        'total_inboxes' => $split->inboxes_per_domain * count($domains),
                        'status' => $orderPanel->status,
                        'created_at' => $split->created_at,
                        'customized_note' => $orderPanel->customized_note,
                        'email_count' => OrderEmail::whereIn('order_split_id', [$orderPanel->id])->count(),
                        
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'order_id' => $order->id,
                    'customer_name' => $order->user->name ?? 'N/A',
                    'created_at' => $order->created_at,
                    'completed_at' => $order->completed_at,
                    'timer_started_at' => $order->timer_started_at ? $order->timer_started_at->toISOString() : null,
                    'timer_paused_at' => $order->timer_paused_at ? $order->timer_paused_at->toISOString() : null,
                    'total_paused_seconds' => $order->total_paused_seconds ?? 0,
                    'status' => $order->status_manage_by_admin ?? 'pending',
                    'is_shared' => $order->is_shared ?? false,
                    'status_manage_by_admin' => (function() use ($order) {
                        $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                        $statusKey = $status;
                        $statusClass = $this->statuses[$statusKey] ?? 'secondary';
                        return '<span style="font-size: 11px !important;" class="py-1 px-1 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                            . ucfirst($status) . '</span>';
                    })(),
                ],
                'reorder_info' => $reorderInfo ? [
                    'total_inboxes' => $reorderInfo->total_inboxes,
                    'inboxes_per_domain' => $reorderInfo->inboxes_per_domain,
                    'hosting_platform' => $reorderInfo->hosting_platform,
                    'platform_login' => $reorderInfo->platform_login,
                    'platform_password' => $reorderInfo->platform_password,
                    'forwarding_url' => $reorderInfo->forwarding_url,
                    'sending_platform' => $reorderInfo->sending_platform,
                    'sequencer_login' => $reorderInfo->sequencer_login,
                    'sequencer_password' => $reorderInfo->sequencer_password,
                    'first_name' => $reorderInfo->first_name,
                    'last_name' => $reorderInfo->last_name,
                    'email_persona_password' => $reorderInfo->email_persona_password,
                    'profile_picture_link' => $reorderInfo->profile_picture_link,
                    'prefix_variants' => $reorderInfo->prefix_variants,
                    'prefix_variant_1' => $reorderInfo->prefix_variant_1,
                    'prefix_variant_2' => $reorderInfo->prefix_variant_2,
                    'master_inbox_email' => $reorderInfo->master_inbox_email,
                    'additional_info' => $reorderInfo->additional_info
                ] : null,
                'splits' => $splitsData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching order splits: ' . $e->getMessage()
            ], 500);
        }
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
                $query->with(['orderPanel', 'orderPanelSplit']);
                    //   ->where('contractor_id', auth()->id());
            }
        ])->findOrFail($order_panel_id);
        
        // Get the order from the panel
        $order = $orderPanel->order;
        
        $order->status2 = strtolower($order->status_manage_by_admin);
        $order->color_status2 = $this->statuses[$order->status2] ?? 'secondary';
        
        // Add split status color to orderPanel
        $orderPanel->split_status_color = $this->splitStatuses[$orderPanel->status ?? 'pending'] ?? 'secondary';
        
        $splitStatuses = $this->splitStatuses;
        
        return view('admin.orders.split-view', compact('order', 'orderPanel', 'splitStatuses'));
    }

    /**
     * Process panel status update
     */
    public function processPanelStatus(Request $request)
    {
        $request->validate([
            'order_panel_id' => 'required|integer|min:1',
            'marked_status' => 'required|string|in:' . implode(',', array_keys($this->splitStatuses)),
            'reason' => 'nullable|string|max:1000'
        ]);

        try {
            // Ensure user is authenticated
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must be logged in to perform this action.'
                ], 401);
            }

            $orderPanelId = $request->order_panel_id;
            $newStatus = strtolower($request->marked_status);
            $reason = $request->reason;

            // Find the order panel with relationships
            $orderPanel = OrderPanel::with(['order', 'order.user'])->findOrFail($orderPanelId);
            $oldStatus = $orderPanel->status;
            $order = $orderPanel->order;

            // Set timestamps based on status
            if ($newStatus == 'in-progress') {
                $orderPanel->timer_started_at = now();
            }
            if ($newStatus == 'completed') {
                $orderPanel->completed_at = now();
            }

            // Update the order panel status
            $orderPanel->status = $newStatus;

            // If rejected, also update with reason
            if ($newStatus === 'rejected' && $reason) {
                $orderPanel->note = $reason;
            }

            $orderPanel->save();
            // Update order status_manage_by_admin based on panel status changes
            $this->updateOrderStatusBasedOnPanelStatus($order, $newStatus);
            // Log the status change
            Log::info("Order Panel Status Updated", [
                'order_panel_id' => $orderPanelId,
                'order_id' => $orderPanel->order_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $reason,
                'updated_by' => auth()->id(),
                'updated_by_name' => auth()->user()->name ?? 'Unknown'
            ]);

            // Create activity log
            try {
                ActivityLogService::log(
                    'order_panel_status_updated',
                    'Order panel status updated: Panel ID ' . $orderPanel->id . ' for Order #' . $order->id,
                    $orderPanel,
                    [
                        'order_panel_id' => $orderPanel->id,
                        'order_id' => $order->id,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'updated_by' => auth()->id(),
                        'reason' => $reason,
                        'ip' => $request->ip(),
                        'user_agent' => $request->header('User-Agent')
                    ],
                    auth()->id()
                );
            } catch (Exception $e) {
                Log::warning("Failed to create activity log: " . $e->getMessage());
            }

            // Create notifications
            Notification::create([
                'user_id' => $order->user_id,
                'type' => 'order_panel_status_change',
                'title' => 'Order Panel Status Changed',
                'message' => 'Your order #' . $order->id . ' panel status has been changed to ' . $newStatus,
                'data' => [
                    'order_id' => $order->id,
                    'order_panel_id' => $orderPanel->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason,
                    'updated_by' => auth()->id()
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
                    'order_panel_id' => $orderPanel->id
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send order panel status change emails: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => "Panel status successfully updated to '{$newStatus}'.",
                'data' => [
                    'order_panel_id' => $orderPanelId,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason
                ]
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (Exception $e) {
            Log::error("Error updating panel status: " . $e->getMessage(), [
                'order_panel_id' => $request->order_panel_id ?? null,
                'new_status' => $request->marked_status ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update panel status: ' . $e->getMessage(),
                'debug' => [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
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
     * Get emails for a specific order panel split
     */
    public function getSplitEmails($orderPanelId)
    {
        try {
            // Log the request for debugging
            Log::info('Getting split emails for order panel ID: ' . $orderPanelId);

            // Validate input
            if (!is_numeric($orderPanelId) || $orderPanelId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid order panel ID',
                    'data' => []
                ], 400);
            }

            // Verify the order panel exists
            $orderPanel = OrderPanel::with(['order', 'orderPanelSplits'])->find($orderPanelId);
            
            if (!$orderPanel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order panel not found',
                    'data' => []
                ], 404);
            }

            // Check if order exists
            if (!$orderPanel->order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found for this panel',
                    'data' => []
                ], 404);
            }

            // Initialize emails collection
            $emails = collect();
            
            // Try different approaches to get emails
            try {
                // Method 1: Try with relationship
                $emails = OrderEmail::query()
                    ->where('order_id', $orderPanel->order_id)
                    ->whereHas('orderSplit', function($query) use ($orderPanelId) {
                        $query->where('order_panel_id', $orderPanelId);
                    })
                    ->select('id', 'name', 'email', 'password', 'order_split_id', 'contractor_id', 'last_name')
                    ->get();
                    
            } catch (\Exception $relationshipError) {
                Log::warning('Relationship query failed, trying alternative approach: ' . $relationshipError->getMessage());
                
                // Method 2: Fallback - Get emails by split IDs
                if ($orderPanel->orderPanelSplits && $orderPanel->orderPanelSplits->count() > 0) {
                    $splitIds = $orderPanel->orderPanelSplits->pluck('id')->toArray();
                    
                    $emails = OrderEmail::query()
                        ->where('order_id', $orderPanel->order_id)
                        ->whereIn('order_split_id', $splitIds)
                        ->select('id', 'name', 'last_name', 'email', 'password', 'order_split_id', 'contractor_id')
                        ->get();
                } else {
                    // Method 3: Get all emails for this order as last resort
                    $emails = OrderEmail::query()
                        ->where('order_id', $orderPanel->order_id)
                        ->select('id', 'name', 'last_name', 'email', 'password', 'order_split_id', 'contractor_id')
                        ->get();
                }
            }

            Log::info('Found ' . $emails->count() . ' emails for order panel ID: ' . $orderPanelId);

            return response()->json([
                'success' => true,
                'data' => $emails,
                'order_panel_id' => (int)$orderPanelId,
                'count' => $emails->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getSplitEmails function: ' . $e->getMessage(), [
                'order_panel_id' => $orderPanelId,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching emails: ' . $e->getMessage(),
                'data' => [],
                'error_details' => [
                    'file' => basename($e->getFile()),
                    'line' => $e->getLine()
                ]
            ], 500);
        }
    }

    /**
     * Alternative method to get emails with better error handling
     */
    public function getSplitEmailsAlternative($orderPanelId)
    {
        try {
            Log::info('Getting split emails (alternative method) for order panel ID: ' . $orderPanelId);

            // Verify the order panel exists
            $orderPanel = OrderPanel::with(['order', 'orderPanelSplits'])->find($orderPanelId);
            
            if (!$orderPanel) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order panel not found',
                    'data' => []
                ], 404);
            }

            // Check if order exists
            if (!$orderPanel->order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found for this panel',
                    'data' => []
                ], 404);
            }

            // First try to get emails through the relationship
            $emails = collect();
            
            try {
                $emails = OrderEmail::query()
                    ->where('order_id', $orderPanel->order_id)
                    ->whereHas('orderSplit', function($query) use ($orderPanelId) {
                        $query->where('order_panel_id', $orderPanelId);
                    })
                    ->select('id', 'name', 'email', 'password', 'order_split_id', 'contractor_id')
                    ->get();
            } catch (\Exception $relationshipError) {
                Log::warning('Relationship query failed, trying alternative approach: ' . $relationshipError->getMessage());
                
                // Fallback: Get all emails for this order and filter by split
                $splitIds = $orderPanel->orderPanelSplits->pluck('id')->toArray();
                
                $emails = OrderEmail::query()
                    ->where('order_id', $orderPanel->order_id)
                    ->whereIn('order_split_id', $splitIds)
                    ->select('id', 'name', 'email', 'password', 'order_split_id', 'contractor_id')
                    ->get();
            }

            Log::info('Found ' . $emails->count() . ' emails for order panel ID: ' . $orderPanelId);

            return response()->json([
                'success' => true,
                'data' => $emails,
                'order_panel_id' => $orderPanelId,
                'count' => $emails->count(),
                'method' => 'alternative'
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getSplitEmailsAlternative function: ' . $e->getMessage(), [
                'order_panel_id' => $orderPanelId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching emails: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    /**
     * Export CSV file with domains data for a specific order panel split
     */
    // public function exportCsvSplitDomainsById($splitId)
    // {
    //     try {
    //         // Find the order panel split
    //         $orderPanelSplit = OrderPanelSplit::with([
    //             'orderPanel.order.orderPanels.userOrderPanelAssignments' => function($query) {
    //                 // $query->where('contractor_id', auth()->id());
    //             }
    //         ])->findOrFail($splitId);

    //         // Check if contractor has access to this split
    //         $hasAccess = false;
    //         $order = $orderPanelSplit->orderPanel->order;
            
    //         // Allow access if order is unassigned (available for all contractors)
    //         if ($order->assigned_to === null) {
    //             $hasAccess = true;
    //         }
    //         // Or if the contractor is assigned to this order
    //         else if ($order->assigned_to == auth()->id()) {
    //             $hasAccess = true;
    //         } else {
    //             // Check if contractor has access to any split of this order
    //             foreach ($order->orderPanels as $orderPanel) {
    //                 if ($orderPanel->userOrderPanelAssignments->where('contractor_id', auth()->id())->count() > 0) {
    //                     $hasAccess = true;
    //                     break;
    //                 }
    //             }
    //         }

    //         if (!$hasAccess) {
    //             return back()->with('error', 'You do not have access to this order split.');
    //         }

    //         // Get domains from the split
    //         $domains = [];
    //         if ($orderPanelSplit->domains) {
    //             if (is_array($orderPanelSplit->domains)) {
    //                 $domains = $orderPanelSplit->domains;
    //             } else if (is_string($orderPanelSplit->domains)) {
    //                 // Handle case where domains might be stored as comma-separated string
    //                 $domains = array_map('trim', explode(',', $orderPanelSplit->domains));
    //                 $domains = array_filter($domains); // Remove empty values
    //             }
                
    //             // Flatten array if it contains nested arrays or objects
    //             $flatDomains = [];
    //             foreach ($domains as $domain) {
    //                 if (is_array($domain) || is_object($domain)) {
    //                     // Handle case where domain data is nested
    //                     if (is_object($domain) && isset($domain->domain)) {
    //                         $flatDomains[] = $domain->domain;
    //                     } else if (is_array($domain) && isset($domain['domain'])) {
    //                         $flatDomains[] = $domain['domain'];
    //                     } else if (is_string($domain)) {
    //                         $flatDomains[] = $domain;
    //                     }
    //                 } else if (is_string($domain)) {
    //                     $flatDomains[] = $domain;
    //                 }
    //             }
    //             $domains = $flatDomains;
    //         }

    //         if (empty($domains)) {
    //             return back()->with('error', 'No domains data found for this split.');
    //         }

    //         $filename = "order_{$order->id}_split_{$splitId}_domains.csv";

    //         $headers = [
    //             'Content-Type' => 'text/csv',
    //             'Content-Disposition' => "attachment; filename=\"$filename\"",
    //         ];

    //         $callback = function () use ($domains, $orderPanelSplit, $order) {
    //             $file = fopen('php://output', 'w');

    //             // Add CSV headers with more detailed information
    //             fputcsv($file, [
    //                 'Domain', 
    //                 // 'Order ID', 
    //                 // 'Split ID', 
    //                 // 'Panel ID', 
    //                 // 'Inboxes per Domain'
    //             ]);

    //             // Add data rows
    //             foreach ($domains as $domain) {
    //                 fputcsv($file, [
    //                     $domain,
    //                     // $order->id,
    //                     // $orderPanelSplit->id,
    //                     // $orderPanelSplit->panel_id,
    //                     // $orderPanelSplit->inboxes_per_domain ?? 'N/A'
    //                 ]);
    //             }

    //             fclose($file);
    //         };

    //         return Response::stream($callback, 200, $headers);

    //     } catch (\Exception $e) {
    //         Log::error('Error exporting CSV domains by split ID: ' . $e->getMessage());
    //         return back()->with('error', 'Error exporting CSV: ' . $e->getMessage());
    //     }
    // }
    /**
     * Export CSV file with domains data for a specific order panel split
     */
    // Add these columns at the end with empty data
    // Password Hash Function [UPLOAD ONLY],
    // Org Unit Path [Required, 
    // New Primary Email [UPLOAD ONLY], 
    // Recovery Email, 
    // Home Secondary Email,
    // Work Secondary Email,
    // Recovery Phone [MUST BE IN THE E.164 FORMAT],
    // Work Phone,
    // Home Phone,
    // Mobile Phone,
    // Work Address,
    // Home Address,
    // Employee ID,
    // Employee Type,
    // Employee Title,
    // Manager Email,
    // Department,
    // Cost Center,
    // Building ID,
    // Floor Name,
    // Floor Section,
    // Change Password at Next Sign-In,
    // New Status [UPLOAD ONLY],
    // New Licenses [UPLOAD ONLY],
    // Advanced Protection Program enrollment

/**
     * Export CSV file with domains data for a specific order panel split
     */
    
    public function exportCsvSplitDomainsById($splitId)
    {
        try {
            // Find the order panel split
            $orderPanelSplit = OrderPanelSplit::with([
                'orderPanel.order.orderPanels.userOrderPanelAssignments' => function($query) {
                    $query->where('contractor_id', auth()->id());
                },
                'orderPanel.order.reorderInfo',
                'orderPanel.panel'
            ])->findOrFail($splitId);

            // Check if contractor has access to this split
            $hasAccess = false;
            $order = $orderPanelSplit->orderPanel->order;

            // Allow access if order is unassigned (available for all contractors)
            if ($order->assigned_to === null) {
                $hasAccess = true;
            }
            // // Or if the contractor is assigned to this order
            // else if ($order->assigned_to == auth()->id()) {
            //     $hasAccess = true;
            // } else {
            //     // Check if contractor has access to any split of this order
            //     foreach ($order->orderPanels as $orderPanel) {
            //         if ($orderPanel->userOrderPanelAssignments->where('contractor_id', auth()->id())->count() > 0) {
            //             $hasAccess = true;
            //             break;
            //         }
            //     }
            // }

            // if (!$hasAccess) {
            //     return back()->with('error', 'You do not have access to this order split.');
            // }

            // Get prefix variants and their details from reorder info
            $prefixVariants = [];
            $prefixVariantDetails = [];
            $reorderInfo = $order->reorderInfo->first();
            
            if ($reorderInfo) {
                // Get prefix variants
                if ($reorderInfo->prefix_variants) {
                    if (is_array($reorderInfo->prefix_variants)) {
                        $prefixVariants = array_values(array_filter($reorderInfo->prefix_variants));
                    } else if (is_string($reorderInfo->prefix_variants)) {
                        $decodedPrefixes = json_decode($reorderInfo->prefix_variants, true);
                        if (is_array($decodedPrefixes)) {
                            $prefixVariants = array_values(array_filter($decodedPrefixes));
                        }
                    }
                }
                
                // Get prefix variant details
                if ($reorderInfo->prefix_variants_details) {
                    $decodedDetails = is_string($reorderInfo->prefix_variants_details) 
                        ? json_decode($reorderInfo->prefix_variants_details, true) 
                        : $reorderInfo->prefix_variants_details;
                        
                    if (is_array($decodedDetails)) {
                        $prefixVariantDetails = $decodedDetails;
                    }
                }
            }

            // Default prefixes if none found
            if (empty($prefixVariants)) {
                $prefixVariants = ['pre01', 'pre02', 'pre03'];
            }

            // Get domains from the split
            $domains = [];
            if ($orderPanelSplit->domains) {
                if (is_array($orderPanelSplit->domains)) {
                    $domains = $orderPanelSplit->domains;
                } else if (is_string($orderPanelSplit->domains)) {
                    // Handle case where domains might be stored as comma-separated string
                    $domains = array_map('trim', explode(',', $orderPanelSplit->domains));
                    $domains = array_filter($domains); // Remove empty values
                }
                
                // Flatten array if it contains nested arrays or objects
                $flatDomains = [];
                foreach ($domains as $domain) {
                    if (is_array($domain) || is_object($domain)) {
                        // Handle case where domain data is nested
                        if (is_object($domain) && isset($domain->domain)) {
                            $flatDomains[] = $domain->domain;
                        } else if (is_array($domain) && isset($domain['domain'])) {
                            $flatDomains[] = $domain['domain'];
                        } else if (is_string($domain)) {
                            $flatDomains[] = $domain;
                        }
                    } else if (is_string($domain)) {
                        $flatDomains[] = $domain;
                    }
                }
                $domains = $flatDomains;
            }

            if (empty($domains)) {
                return back()->with('error', 'No domains data found for this split.');
            }

            // Generate emails with prefixes and corresponding first/last names
            $emailData = [];
            foreach ($domains as $domain) {
                foreach ($prefixVariants as $index => $prefix) {
                    // Get first and last name for this prefix variant
                    $prefixKey = 'prefix_variant_' . ($index + 1);
                    $firstName = 'N/A';
                    $lastName = 'N/A';
                    
                    if (isset($prefixVariantDetails[$prefixKey])) {
                        $firstName = $prefixVariantDetails[$prefixKey]['first_name'] ?? 'N/A';
                        $lastName = $prefixVariantDetails[$prefixKey]['last_name'] ?? 'N/A';
                    }
                    
                    $emailData[] = [
                        'domain' => $domain,
                        'email' => $prefix . '@' . $domain,
                        'password' => $this->customEncrypt($order->id), // Custom encryption for password
                        'first_name' => $firstName,
                        'last_name' => $lastName
                    ];
                }
            }

            // Calculate totals
            $domainsCount = count($domains);
            $inboxesPerDomain = $order->reorderInfo->first()->inboxes_per_domain ?? 1; // Default to 1 if not set
            if ($inboxesPerDomain <= 0) {
                $inboxesPerDomain = 1; // Ensure at least 1 inbox per domain
            }
            $totalInboxes = $domainsCount * $inboxesPerDomain;

            $filename = "order_{$order->id}_split_{$splitId}_emails.csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($emailData, $orderPanelSplit, $order, $domainsCount, $inboxesPerDomain, $totalInboxes) {
                $file = fopen('php://output', 'w');
                // Add CSV headers once at the top
                // fputcsv($file, [
                //     '',
                //     'Order_ID: ' . $order->id,
                // ]);

                // fputcsv($file, [
                //     '',
                //     'Panel_ID: ' . $orderPanelSplit->orderPanel->id,
                // ]);
                // fputcsv($file, [
                //     '',
                //     'Panel_Name: ' . ($orderPanelSplit->orderPanel->panel->title ?? 'N/A')
                // ]);
                // fputcsv($file, [
                //     '',
                //     'Inboxes Per Domain: ' . $inboxesPerDomain.' | Domains_Count: ' . $domainsCount.' | Total_Inboxes: ' . $totalInboxes.' | Status: ' . ($orderPanelSplit->orderPanel->status ?? 'pending').' | Created_At: ' . ($orderPanelSplit->created_at ? $orderPanelSplit->created_at->format('Y-m-d H:i:s') : '')
                // ]);

                // // Add empty row for separation
                // fputcsv($file, []);
                
                // Add email data headers with additional columns
                fputcsv($file, [
                    'First Name', 
                    'Last Name',
                    'Email address', 
                    'Password',
                    // 'Password Hash Function [UPLOAD ONLY]',
                    'Org Unit Path [Required]',
                    // 'New Primary Email [UPLOAD ONLY]',
                    // 'Recovery Email',
                    // 'Home Secondary Email',
                    // 'Work Secondary Email',
                    // 'Recovery Phone [MUST BE IN THE E.164 FORMAT]',
                    // 'Work Phone',
                    // 'Home Phone',
                    // 'Mobile Phone',
                    // 'Work Address',
                    // 'Home Address',
                    // 'Employee ID',
                    // 'Employee Type',
                    // 'Employee Title',
                    // 'Manager Email',
                    // 'Department',
                    // 'Cost Center',
                    // 'Building ID',
                    // 'Floor Name',
                    // 'Floor Section',
                    // 'Change Password at Next Sign-In',
                    // 'New Status [UPLOAD ONLY]',
                    // 'New Licenses [UPLOAD ONLY]',
                    // 'Advanced Protection Program enrollment'
                ]);
                
                // Add email data with corresponding first/last names for each prefix variant
                foreach ($emailData as $data) {
                    fputcsv($file, [
                        $data['first_name'], // First Name from prefix variant details
                        $data['last_name'], // Last Name from prefix variant details
                        $data['email'], 
                        $data['password'],
                        // '', // Password Hash Function [UPLOAD ONLY]
                        '/', // Org Unit Path [Required]
                        // '', // New Primary Email [UPLOAD ONLY]
                        // '', // Recovery Email
                        // '', // Home Secondary Email
                        // '', // Work Secondary Email
                        // '', // Recovery Phone [MUST BE IN THE E.164 FORMAT]
                        // '', // Work Phone
                        // '', // Home Phone
                        // '', // Mobile Phone
                        // '', // Work Address
                        // '', // Home Address
                        // '', // Employee ID
                        // '', // Employee Type
                        // '', // Employee Title
                        // '', // Manager Email
                        // '', // Department
                        // '', // Cost Center
                        // '', // Building ID
                        // '', // Floor Name
                        // '', // Floor Section
                        // '', // Change Password at Next Sign-In
                        // '', // New Status [UPLOAD ONLY]
                        // '', // New Licenses [UPLOAD ONLY]
                        // ''  // Advanced Protection Program enrollment
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting CSV domains by split ID: ' . $e->getMessage());
            return back()->with('error', 'Error exporting CSV: ' . $e->getMessage());
        }
    }
    // Custom encryption function for passwords
    
    private function customEncrypt($orderId)
    {
        // Convert order ID to exactly 8 character password with one uppercase, lowercase, special char, and number
        $upperCase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowerCase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $specialChars = '!@#$%^&*';
        
        // Use order ID as seed for consistent password generation
        mt_srand($orderId);
        
        // Generate password with requirements
        $password = '';
        $password .= $upperCase[mt_rand(0, strlen($upperCase) - 1)]; // 1 uppercase
        $password .= $lowerCase[mt_rand(0, strlen($lowerCase) - 1)]; // 1 lowercase
        $password .= $numbers[mt_rand(0, strlen($numbers) - 1)];     // 1 number
        $password .= $specialChars[mt_rand(0, strlen($specialChars) - 1)]; // 1 special char
        
        // Fill remaining 4 characters with mix of all character types
        $allChars = $upperCase . $lowerCase . $numbers . $specialChars;
        for ($i = 4; $i < 8; $i++) {
            $password .= $allChars[mt_rand(0, strlen($allChars) - 1)];
        }
        
        // Shuffle using seeded random generator instead of str_shuffle
        $passwordArray = str_split($password);
        for ($i = count($passwordArray) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            // Swap characters
            $temp = $passwordArray[$i];
            $passwordArray[$i] = $passwordArray[$j];
            $passwordArray[$j] = $temp;
        }
        
        return implode('', $passwordArray);
    }
    /**
     * Debug method to check relationships and data integrity
     */
    public function debugSplitEmails($orderPanelId)
    {
        try {
            $debug = [
                'order_panel_id' => $orderPanelId,
                'order_panel_exists' => false,
                'order_exists' => false,
                'splits_count' => 0,
                'emails_count' => 0,
                'relationships' => []
            ];

            // Check if order panel exists
            $orderPanel = OrderPanel::find($orderPanelId);
            if ($orderPanel) {
                $debug['order_panel_exists'] = true;
                $debug['order_panel_data'] = [
                    'id' => $orderPanel->id,
                    'order_id' => $orderPanel->order_id,
                    'panel_id' => $orderPanel->panel_id,
                    'status' => $orderPanel->status
                ];

                // Check if order exists
                if ($orderPanel->order) {
                    $debug['order_exists'] = true;
                    $debug['order_data'] = [
                        'id' => $orderPanel->order->id,
                        'user_id' => $orderPanel->order->user_id,
                        'status' => $orderPanel->order->status_manage_by_admin
                    ];
                }

                // Check splits
                $splits = OrderPanelSplit::where('order_panel_id', $orderPanelId)->get();
                $debug['splits_count'] = $splits->count();
                $debug['splits_data'] = $splits->toArray();

                // Check emails
                if ($orderPanel->order_id) {
                    $allEmails = OrderEmail::where('order_id', $orderPanel->order_id)->get();
                    $debug['emails_count'] = $allEmails->count();
                    $debug['all_emails'] = $allEmails->toArray();

                    // Try to get emails with relationship
                    try {
                        $relationshipEmails = OrderEmail::whereHas('orderSplit', function($query) use ($orderPanelId) {
                            $query->where('order_panel_id', $orderPanelId);
                        })->get();
                        $debug['relationship_emails_count'] = $relationshipEmails->count();
                        $debug['relationship_emails'] = $relationshipEmails->toArray();
                    } catch (\Exception $e) {
                        $debug['relationship_error'] = $e->getMessage();
                    }
                }
            }

            return response()->json([
                'success' => true,
                'debug_info' => $debug
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Debug error: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function assignOrderToMe(Request $request, $orderId)
    {
        try {
            $adminId = auth()->id();
            
            // Find the order
            $order = Order::findOrFail($orderId);
            
            // Check if order is already assigned
            if ($order->assigned_to && $order->assigned_to != $adminId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already assigned to another admin.'
                ], 400);
            }
            
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
            
            // Assign each unallocated panel to the admin
            foreach ($unallocatedPanels as $panel) {
                try {
                    // Update panel status to allocated
                    $panel->update([
                        'status' => 'allocated',
                        'contractor_id' => $adminId
                    ]);
                    
                    $assignedCount++;
                    
                } catch (Exception $e) {
                    $errors[] = "Failed to assign panel {$panel->id}: " . $e->getMessage();
                    Log::error("Error assigning panel {$panel->id} to admin {$adminId}: " . $e->getMessage());
                }
            }
            
            // Assign the order to the current admin
            // Assign the order to the current admin
            $order->assigned_to = $adminId;
            $order->status_manage_by_admin = 'in-progress'; // Set status to in-progress
            $order->save();
            
            // Check remaining unallocated panels
            $remainingUnallocated = OrderPanel::where('order_id', $orderId)
                ->where('status', 'unallocated')
                ->count();
            
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
                'remaining_unallocated' => $remainingUnallocated,
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

    /**
     * Change order status with reason and activity logging
     */
    public function changeStatus(Request $request, $orderId)
    {
        try {
            // Validate the request
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,completed,cancelled,rejected,in-progress,reject,cancelled_force',
                'reason' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $adminId = Auth::id();
            $newStatus = $request->input('status');
            $reason = $request->input('reason');
            if($newStatus == 'reject' || $newStatus == 'cancelled') {
                if(!$reason) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Reason is required for reject or cancel status'
                    ], 422);
                }
            }
            // If status is reject, use the OrderRejectionService
            if ($newStatus === 'reject' || $newStatus === 'rejected') {
                $rejectionService = new \App\Services\OrderRejectionService();
                $result = $rejectionService->rejectOrder($orderId, $adminId, $reason);
                
                return response()->json($result);
            }
            // if status is cancelled then also remove customer subscriptoins create service
            if($newStatus === 'cancelled' || $newStatus === 'cancelled_force') {
                $order = Order::findOrFail($orderId);
                $subscriptionService = new \App\Services\OrderCancelledService();
                $result = $subscriptionService->cancelSubscription(
                    $order->chargebee_subscription_id,
                    $order->user_id,
                    $reason,
                    false,
                    $newStatus === 'cancelled_force' ? true : false
                );
                
                return response()->json($result);
            }
            // Find the order
            $order = Order::findOrFail($orderId);
            $oldStatus = $order->status_manage_by_admin;
            
            // Don't allow status change if it's the same
            if ($oldStatus === $newStatus) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order is already in the selected status.'
                ], 400);
            }
            
            // Update order status using the correct column
            $order->status_manage_by_admin = $newStatus;
            
            // Set completion timestamp if status is completed
            if ($newStatus === 'completed') {
                if (!$order->assigned_to) {
                    $order->assigned_to = $adminId;
                }
                $order->completed_at = now();
            }
            
            // Add reason if provided
            if ($reason) {
                $order->reason = $reason . " (Reason given by " . Auth::user()->name . ")";
            }
            
            $order->save();
            
            // Log the activity
            ActivityLogService::log(
                'admin_order_status_updated',
                "Admin changed order status from '{$oldStatus}' to '{$newStatus}'" . ($reason ? " with reason: {$reason}" : ""),
                $order,
                [
                    'order_id' => $orderId,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason,
                    'changed_by' => $adminId,
                    'changed_by_type' => 'admin'
                ],
                $adminId
            );
            
            // Create notification for customer
            Notification::create([
                'user_id' => $order->user_id,
                'type' => 'order_status_change',
                'title' => 'Order Status Updated',
                'message' => "Your order #{$orderId} status has been changed to {$newStatus}",
                'data' => [
                    'order_id' => $orderId,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason
                ]
            ]);
            
            // Send email notifications
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

                Log::info('Order status change email sent', [
                    'order_id' => $order->id,
                    'assigned_to' => $order->assigned_to
                ]);

                // Send email to assigned contractor if exists
                if($order->assigned_to){
                    $assignedUser = User::find($order->assigned_to);
                    if ($assignedUser) {
                        Mail::to($assignedUser->email)
                            ->queue(new OrderStatusChangeMail(
                                $order,
                                $user,
                                $oldStatus,
                                $newStatus,
                                $reason,
                                true
                            ));
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to send order status change emails: ' . $e->getMessage());
            }
            
            return response()->json([
                'success' => true,
                'message' => "Order status successfully changed from '{$oldStatus}' to '{$newStatus}'",
                'data' => [
                    'order_id' => $orderId,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'reason' => $reason
                ]
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
            
        } catch (Exception $e) {
            Log::error("Error in changeStatus for order {$orderId}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to change order status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available panels for reassignment
     */
    public function getAvailablePanelsForReassignment($orderId, $orderPanelId)
    {
        try {
            $reassignmentService = new PanelReassignmentService();
            $result = $reassignmentService->getAvailablePanelsForReassignment($orderId, $orderPanelId);
            
            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Error getting available panels for reassignment', [
                'order_id' => $orderId,
                'order_panel_id' => $orderPanelId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get available panels: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process panel split reassignment
     */
    public function reassignPanelSplit(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'from_order_panel_id' => 'required|integer|exists:order_panel,id',
                'to_panel_id' => 'required|integer|exists:panels,id',
                'split_id' => 'nullable|integer|exists:order_panel_split,id',
                'reason' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $reassignmentService = new PanelReassignmentService();
            $result = $reassignmentService->reassignPanelSplit(
                $request->from_order_panel_id,
                $request->to_panel_id, // Now panel_id instead of order_panel_id
                $request->split_id,
                auth()->id()
            );

            if ($result['success']) {
                // Log the action
                Log::info('Panel reassignment completed by admin', [
                    'admin_id' => auth()->id(),
                    'from_order_panel_id' => $request->from_order_panel_id,
                    'to_panel_id' => $request->to_panel_id,
                    'split_id' => $request->split_id,
                    'reason' => $request->reason
                ]);

                return response()->json([
                    'success' => true,
                    'message' => $result['message'],
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }

        } catch (Exception $e) {
            Log::error('Panel reassignment failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Panel reassignment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reassignment history for an order
     */
    public function getReassignmentHistory($orderId)
    {
        try {
            $reassignmentService = new PanelReassignmentService();
            $result = $reassignmentService->getReassignmentHistory($orderId);
            
            return response()->json($result);
        } catch (Exception $e) {
            Log::error('Error getting reassignment history', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get reassignment history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reassign contractor for an order
     */
    public function reassignContractor(Request $request, $orderId)
    {
        try {
            $request->validate([
                'contractor_id' => 'required|exists:users,id',
                'remove_from_helpers' => 'nullable|in:true,false,1,0',
                'reassignment_note' => 'nullable|string|max:255'
            ]);
           Log::channel('slack_notifications')->info("test 1 controller============================".$orderId.'-'. $request->contractor_id);

            // Convert remove_from_helpers to boolean
            $removeFromHelpers = filter_var($request->remove_from_helpers, FILTER_VALIDATE_BOOLEAN);

            $service = new OrderContractorReassignmentService();
            $result = $service->reassignContractor($orderId, $request->reassignment_note, $request->contractor_id, $removeFromHelpers);
            if ($result['success']) {
                // Log the activity
                ActivityLogService::log(
                    'order_contractor_reassigned',
                    "Order #{$orderId} contractor reassigned to user ID {$request->contractor_id}",
                    Order::find($orderId),
                    [
                        'order_id' => $orderId,
                        'old_contractor_id' => $result['old_contractor_id'],
                        'new_contractor_id' => $result['new_contractor_id'],
                        'reassigned_by' => auth()->id(),
                        'removed_from_helpers' => $removeFromHelpers
                    ],
                    auth()->id()
                );

                return response()->json([
                    'success' => true, 
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false, 
                    'message' => $result['error']
                ], 400);
            }
        } catch (Exception $e) {
            Log::error('Error reassigning contractor', [
                'order_id' => $orderId,
                'contractor_id' => $request->contractor_id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reassign contractor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if contractor is already in helpers list
     */
    public function checkContractorInHelpers(Request $request, $orderId)
    {
        try {
            $request->validate([
                'contractor_id' => 'required|exists:users,id'
            ]);

            $order = Order::findOrFail($orderId);
            $contractorId = $request->contractor_id;
            
            // Check if contractor is in helpers_ids array
            $helpers_ids = $order->helpers_ids ?? [];
            $isInHelpers = in_array($contractorId, $helpers_ids);
            
            // Get contractor name for the message
            $contractor = User::find($contractorId);
            
            return response()->json([
                'success' => true,
                'is_in_helpers' => $isInHelpers,
                'contractor_name' => $contractor ? $contractor->name : 'Unknown',
                'message' => $isInHelpers 
                    ? "This contractor is already added as a helper. Reassigning will remove from helpers list."
                    : "Contractor is not in helpers list."
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error checking contractor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**  
     * Export CSV file with smart data selection based on order_emails availability
     * If order_emails data exists for order panels, usev that data
     * Otherwise, fall back to the existing domain-based generation method
     */
    public function exportCsvSplitDomainsSmartById($splitId)
    {
        try {
            // Find the order panel split
            $orderPanelSplit = OrderPanelSplit::with([
                'orderPanel.order.orderPanels.userOrderPanelAssignments' => function($query) {
                    $query->where('contractor_id', auth()->id());
                },
                'orderPanel.order.reorderInfo',
                'orderPanel.panel'
            ])->findOrFail($splitId);

            $order = $orderPanelSplit->orderPanel->order;
            $orderPanelId = $orderPanelSplit->order_panel_id;

            // Check if order_emails data is available for this order panel
            $orderEmails = OrderEmail::whereHas('orderSplit', function($query) use ($orderPanelId) {
                $query->where('order_panel_id', $orderPanelId);
            })->get();

            // If order_emails data exists, use it for CSV generation
            if ($orderEmails->count() > 0) {
                return $this->exportCsvFromOrderEmails($splitId, $orderEmails);
            }

            // Otherwise, fall back to the existing domain-based method
            return $this->exportCsvSplitDomainsById($splitId);

        } catch (\Exception $e) {
            Log::error('Error exporting CSV with smart selection: ' . $e->getMessage());
            return back()->with('error', 'Error exporting CSV: ' . $e->getMessage());
        }
    }

    /**
     * Export CSV using existing order_emails data
     */
    private function exportCsvFromOrderEmails($splitId, $orderEmails)
    {
        try {
            $orderPanelSplit = OrderPanelSplit::with([
                'orderPanel.order',
                'orderPanel.panel'
            ])->findOrFail($splitId);

            $order = $orderPanelSplit->orderPanel->order;

            $filename = "order_{$order->id}_split_{$splitId}_emails_from_database.csv";

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"$filename\"",
            ];

            $callback = function () use ($orderEmails, $orderPanelSplit, $order) {
                $file = fopen('php://output', 'w');
                
                // Add CSV headers matching the existing format
                fputcsv($file, [
                    'First Name', 
                    'Last Name',
                    'Email address', 
                    'Password',
                    'Org Unit Path [Required]',
                ]);
                
                // Add email data from database
                foreach ($orderEmails as $orderEmail) {
                    fputcsv($file, [
                        $orderEmail->name ?? 'N/A', // First Name
                        $orderEmail->last_name ?? 'N/A', // Last Name
                        $orderEmail->email, // Email address
                        $orderEmail->password, // Password
                        '/', // Org Unit Path [Required]
                    ]);
                }

                fclose($file);
            };

            return Response::stream($callback, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Error exporting CSV from order emails: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Toggle shared status for an order //
     */
    public function toggleSharedStatus(Request $request, $orderId)
    {
        try {
            // Validate the note input
            $request->validate([
                'note' => 'required|string|max:1000'
            ]);

            $order = Order::findOrFail($orderId);
            $order->is_shared = !$order->is_shared;
            
            // Save the shared note
            $order->shared_note = $request->input('note');
            
            // Clear helpers_ids when unsharing the order
            if (!$order->is_shared) {
                $order->helpers_ids = null;
            }
            
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Order shared status updated successfully',
                'is_shared' => $order->is_shared,
                'shared_note' => $order->shared_note
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . implode(', ', $e->validator->errors()->all())
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error toggling shared status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating shared status'
            ], 500);
        }
    }

    /**
     * Assign contractors to shared order
     */
    
    public function assignContractors(Request $request, $orderId)
    {
        try {
            $request->validate([
                'contractor_ids' => 'required|array',
                'contractor_ids.*' => 'exists:users,id'
            ]);

            // Verify that all selected users are contractors (role_id = 4)
            $contractorCount = User::whereIn('id', $request->contractor_ids)
                ->where('role_id', 4)
                ->count();
            
            if ($contractorCount !== count($request->contractor_ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more selected users are not contractors'
                ], 422);
            }

            $order = Order::findOrFail($orderId);
            $currentHelperIds = $order->helpers_ids ?? [];
            $newContractorIds = $request->contractor_ids;

            // Merge new contractor IDs with existing ones
            // $updatedHelperIds = array_unique(array_merge($currentHelperIds, $newContractorIds));
            
            $order->helpers_ids =$newContractorIds;
            $order->save();

            ActivityLogService::log(
                'Contractors Assigned',
                "Contractors assigned to order #{$order->id}: " . implode(', ', $newContractorIds),
                $order,
                [
                    'new_contractor_ids' => $newContractorIds,
                    'all_helper_ids' => $newContractorIds,
                    'helpers_count' => count($newContractorIds)
                ]
            );

            // Send Slack notification for contractor assignment
            try {
                $contractorNames = User::whereIn('id', $newContractorIds)
                    ->pluck('name')
                    ->toArray();
                
                SlackNotificationService::sendContractorAssignmentNotification(
                    $order,
                    $newContractorIds,
                    $contractorNames
                );
            } catch (\Exception $e) {
                Log::error('Failed to send Slack notification for contractor assignment: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Contractors assigned successfully',
                'helpers_count' => count($newContractorIds)
            ]);
        } catch (\Exception $e) {
            Log::error('Error assigning contractors: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error assigning contractors'.$e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shared orders
     */
    public function getSharedOrders(Request $request)
    {
        try {
            $query = Order::where('is_shared', true)
                ->with(['user', 'plan', 'assignedTo'])
                ->select('orders.*'); // Ensure we get all order fields

            // Filter based on tab type
            if ($request->has('tab')) {
                if ($request->tab === 'requests') {
                    // Requests tab: orders without helpers assigned
                    // Check for null, empty string, empty JSON array, or actual empty array
                    $query->where(function($q) {
                        $q->whereNull('helpers_ids')
                          ->orWhere('helpers_ids', '')
                          ->orWhere('helpers_ids', '[]')
                          ->orWhere('helpers_ids', 'null');
                    });
                } elseif ($request->tab === 'confirmed') {
                    // Confirmed tab: orders with helpers assigned
                    // Must have actual helper IDs (not null, not empty)
                    $query->whereNotNull('helpers_ids')
                          ->where('helpers_ids', '!=', '')
                          ->where('helpers_ids', '!=', '[]')
                          ->where('helpers_ids', '!=', 'null');
                }
            }

            // Apply additional filters if provided
            if ($request->has('status') && $request->status != '') {
                $query->where('status_manage_by_admin', $request->status);
            }

            if ($request->has('plan_id') && $request->plan_id != '') {
                $query->where('plan_id', $request->plan_id);
            }

            if ($request->has('search') && $request->search != '') {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('id', 'like', "%{$search}%")
                      ->orWhereHas('user', function($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            $sharedOrders = $query->orderBy('created_at', 'desc')
                ->get();

            // Filter results after retrieval for better accuracy with array casting
            if ($request->has('tab')) {
                $sharedOrders = $sharedOrders->filter(function ($order) use ($request) {
                    if ($request->tab === 'requests') {
                        // Requests: no helpers assigned
                        return empty($order->helpers_ids) || !is_array($order->helpers_ids) || count($order->helpers_ids) === 0;
                    } elseif ($request->tab === 'confirmed') {
                        // Confirmed: has helpers assigned
                        return !empty($order->helpers_ids) && is_array($order->helpers_ids) && count($order->helpers_ids) > 0;
                    }
                    return true;
                })->values(); // Reset array keys
            }

            // Transform the data to include helper names
            $sharedOrders->transform(function ($order) {
                // Add helper names
                if ($order->helpers_ids && is_array($order->helpers_ids) && count($order->helpers_ids) > 0) {
                    $order->helpers_names = \App\Models\User::whereIn('id', $order->helpers_ids)
                        ->pluck('name')
                        ->toArray();
                } else {
                    $order->helpers_names = [];
                }
                
                // Ensure plan name is available
                if ($order->plan) {
                    $order->plan_name = $order->plan->name;
                } else if ($order->plan_id) {
                    // If plan relation failed, try to get plan name directly
                    $plan = \App\Models\Plan::find($order->plan_id);
                    $order->plan_name = $plan ? $plan->name : 'Unknown Plan';
                    $order->plan = $plan; // Also set the plan object
                }
                
                return $order;
            });

            return response()->json([
                'success' => true,
                'data' => $sharedOrders,
                'tab' => $request->tab,
                'total_count' => $sharedOrders->count(),
                'debug' => [
                    'tab_filter' => $request->tab ?? 'none',
                    'raw_count_before_filter' => $query->count()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting shared orders: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching shared orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get contractors for assignment
     */
    public function getContractors(Request $request)
    {
        try {
            $query = User::where('role_id', 4)
                ->select('id', 'name', 'email')
                ->orderBy('name');

            // Exclude assigned contractor if order_id is provided
            if ($request->has('order_id')) {
                $orderId = $request->get('order_id');
                $order = Order::find($orderId);
                if ($order && $order->assigned_to) {
                    $query->where('id', '!=', $order->assigned_to);
                }
            }

            $contractors = $query->get();

            return response()->json([
                'success' => true,
                'data' => $contractors
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting contractors: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching contractors'
            ], 500);
        }
    }
}
