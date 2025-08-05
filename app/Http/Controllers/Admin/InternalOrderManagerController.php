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

class InternalOrderManagerController extends Controller
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

        $lastWeek = [Carbon::now()->subWeek(), Carbon::now()];
        $previousWeek = [Carbon::now()->subWeeks(2), Carbon::now()->subWeek()];

        $lastWeekOrders = $orders->whereBetween('created_at', $lastWeek)->count();
        $previousWeekOrders = $orders->whereBetween('created_at', $previousWeek)->count();

        $percentageChange = $previousWeekOrders > 0 
            ? (($lastWeekOrders - $previousWeekOrders) / $previousWeekOrders) * 100 
            : 0;

        return view('admin.internal_order_manager.orders', compact(
            'plans', 
            'totalOrders', 
            'pendingOrders', 
            'rejectOrders',
            'inProgressOrders',
            'cancelledOrders',
            'completedOrders',
            'draftOrders', 
            'percentageChange',
            'statuses'
        ));
    }


    public function newOrder(Request $request){
       

         $order =null;
         $plan =\App\Models\Plan::first();
        //  dd($plan);
        // dd($order);
        $hostingPlatforms = \App\Models\HostingPlatform::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
        $sendingPlatforms = \App\Models\SendingPlatform::get();
        
        return view('admin.internal_order_manager.edit-order', compact('plan', 'hostingPlatforms', 'sendingPlatforms', 'order'));

       
    }

    public function store(Request $request)
    {
        
    }   

}
