<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Models\ReorderInfo;
use App\Models\HostingPlatform;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrderController extends Controller
{
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
            ->where('status_manage_by_admin', 'processing')
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

        return view('customer.orders.orders', compact(
            'plans', 
            'totalOrders', 
            'pendingOrders', 
            'completedOrders', 
            'inProgressOrders',
            'percentageChange'
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

        return view('customer.orders.new-order', compact('plan', 'hostingPlatforms', 'order'));
    }
    public function reorder(Request $request, $order_id)
    {
        $order = Order::with(['plan', 'reorderInfo'])->findOrFail($order_id);
        $plan = $order->plan;
        $hostingPlatforms = HostingPlatform::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        return view('customer.orders.reorder', compact('plan', 'hostingPlatforms', 'order'));
    }

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
        
        // Retrieve subscription metadata if available
        $subscriptionMeta = json_decode($order->subscription->meta, true);
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
    
        return view('customer.orders.order-view', compact('order', 'nextBillingInfo'));
    }

    public function getOrders(Request $request)
    {
        Log::info('Orders data request received', [
            'plan_id' => $request->plan_id,
            'request_data' => $request->all()
        ]);

        try {
            $orders = Order::query()
                ->with(['user', 'plan'])
                ->select('orders.*')
                ->leftJoin('plans', 'orders.plan_id', '=', 'plans.id')
                ->where('orders.user_id', auth()->id());

            if ($request->has('plan_id') && $request->plan_id != '') {
                $orders->where('orders.plan_id', $request->plan_id);
            }


            return DataTables::of($orders)
                ->addColumn('action', function ($order) {
                    return '<a href="' . route('customer.orders.view', $order->id) . '" class="btn btn-sm btn-primary">View</a>';
                })
                ->editColumn('created_at', function ($order) {
                    return $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '';
                })
                ->editColumn('status', function ($order) {
                    return ucfirst($order->status_manage_by_admin);
                })
                ->addColumn('email', function ($order) {
                    return $order->user ? $order->user->email : 'N/A';
                })
                ->addColumn('domain_forwarding_url', function ($order) {
                    return $order->user ? $order->user->domain_forwarding_url : 'N/A';
                })
                ->addColumn('plan_name', function ($order) {
                    return $order->plan ? $order->plan->name : 'N/A';
                })
                ->filterColumn('email', function($query, $keyword) {
                    $query->whereHas('user', function($q) use ($keyword) {
                        $q->where('email', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('domain_forwarding_url', function($query, $keyword) {
                    $query->whereHas('user', function($q) use ($keyword) {
                        $q->where('domain_forwarding_url', 'like', "%{$keyword}%");
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
                ->rawColumns(['action'])
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
                'persona_password' => 'required',
                'email_persona_password' => 'required',
            ]);

            // Calculate number of domains and total inboxes
            $domains = array_filter(preg_split('/[\r\n,]+/', $request->domains));
            $domainCount = count($domains);
            $calculatedTotalInboxes = $domainCount * $request->inboxes_per_domain;

            // Get requested plan
            $plan = Plan::findOrFail($request->plan_id);
            
            // Verify plan can support the total inboxes
            $canHandle = ($plan->max_inbox >= $calculatedTotalInboxes || $plan->max_inbox === 0) && 
                        $plan->min_inbox <= $calculatedTotalInboxes;

            if (!$canHandle) {
                return response()->json([
                    'success' => false,
                    'message' => "Configuration exceeds available plan limits. Please contact support for a custom solution.",
                ], 422);
            }

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
}
