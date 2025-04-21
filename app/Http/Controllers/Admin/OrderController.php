<?php

namespace App\Http\Controllers\Admin;

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
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Auth;
use App\Models\Log as ModelLog;
class OrderController extends Controller
{
    public function index()
    {

        $plans = Plan::all();
        // Get order statistics for authenticated user
        $userId = auth()->id();
        $orders = Order::all();
        
        $totalOrders = $orders->count();
        
        // Get orders by admin status
        $pendingOrders = Order::where('status_manage_by_admin', 'pending')
            ->count();
            
        $completedOrders = Order::where('status_manage_by_admin', 'completed')
            ->count();
            
        $inProgressOrders = Order::where('status_manage_by_admin', 'processing')
            ->count();

        // Calculate percentage changes (last week vs previous week)
        $lastWeek = [Carbon::now()->subWeek(), Carbon::now()];
        $previousWeek = [Carbon::now()->subWeeks(2), Carbon::now()->subWeek()];

        $lastWeekOrders = Order::where('user_id', $userId)
            ->whereBetween('created_at', $lastWeek)
            ->count();
            
        $previousWeekOrders = Order::whereBetween('created_at', $previousWeek)
            ->count();

        $percentageChange = $previousWeekOrders > 0 
            ? (($lastWeekOrders - $previousWeekOrders) / $previousWeekOrders) * 100 
            : 0;

        return view('admin.orders.orders', compact(
            'plans', 
            'totalOrders', 
            'pendingOrders', 
            'completedOrders', 
            'inProgressOrders',
            'percentageChange'
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
    
        return view('admin.orders.order-view', compact('order', 'nextBillingInfo'));
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
                ->leftJoin('plans', 'orders.plan_id', '=', 'plans.id');

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
                    $statuses = ["Pending","Approve", 'Cancel','Expired','In-Progress', 'Completed'];
                    $dropdown = '<select class="form-select status-dropdown" data-id="'.$order->id.'" name="status" style="border: 0px !important">';
                
                    foreach ($statuses as $status) {
                        $selected = $order->status_manage_by_admin === $status ? 'selected' : '';
                        $dropdown .= "<option value='{$status}' {$selected}>{$status}</option>";
                    }
                
                    $dropdown .= '</select>';
                    return $dropdown;
                })
                ->rawColumns(['status']) // Important to render HTML
                
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






  public function updateOrderStatus(Request $request){
   $request->validate([
        'order_id' => 'required|exists:orders,id',
        'status_manage_by_admin' => 'required|string',
    ]);

    $order = Order::findOrFail($request->order_id);
    $order->status_manage_by_admin = $request->status_manage_by_admin;
    $order->save();

    return response()->json(['success' => true, 'message' => 'Status updated']);
  }
}
