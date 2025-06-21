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
        // try {
        //     $orders = Order::query()
        //         ->with(['user', 'plan','reorderInfo'])
        //         ->select('orders.*')
        //         ->leftJoin('plans', 'orders.plan_id', '=', 'plans.id');

        //     if ($request->has('plan_id') && $request->plan_id != '') {
        //         $orders->where('orders.plan_id', $request->plan_id);
        //     }

        //     return DataTables::of($orders)
        //     ->addColumn('action', function ($order) {
        //         $user = auth()->user();
            
        //         $viewButton = '<a href="' . route('admin.orders.view', $order->id) . '" class="btn btn-sm btn-primary">View</a>';
            
        //         if ($user->hasPermissionTo('Mod')) {
        //             // Show only the View button if user has 'Mod' permission
        //             return '<div style="display: flex; gap: 6px;">' . $viewButton . '</div>';
        //         }
            
        //         $markStatusButton = '<a href="#" class="btn btn-sm btn-secondary markStatus" id="markStatus" data-id="' . $order->chargebee_subscription_id . '" data-status="' . $order->status_manage_by_admin . '" data-reason="' . $order->reason . '" >Mark Status</a>';
            
        //         return '
        //             <div style="display: flex; gap: 6px;">
        //                 ' . $viewButton . '
        //                 ' . $markStatusButton . '
        //             </div>
        //         ';
        //     })
            
        //         ->editColumn('created_at', function ($order) {
        //             return $order->created_at ? $order->created_at->format('d-F-Y') : '';
        //         })
        //         ->editColumn('status_manage_by_admin', function ($order) {
        //             return $order->status_manage_by_admin ? $order->status_manage_by_admin : '';
        //         })
        //         ->editColumn('total_inboxes', function ($order) {
        //             return $order->reorderInfo->first()?->total_inboxes ?? '';
        //         })               
        //         ->rawColumns(['status']) // Important to render HTML
                
        //         ->addColumn('email', function ($order) {
        //             return $order->user ? $order->user->email : 'N/A';
        //         })
        //         ->addColumn('name', function ($order) {
        //             return $order->user ? $order->user->name : 'N/A';
        //         })
        //         ->addColumn('domain_forwarding_url', function ($order) {
        //             return $order->user ? $order->user->domain_forwarding_url : 'N/A';
        //         })
        //         ->addColumn('plan_name', function ($order) {
        //             return $order->plan ? $order->plan->name : 'N/A';
        //         })
        //         ->filterColumn('email', function($query, $keyword) {
        //             $query->whereHas('user', function($q) use ($keyword) {
        //                 $q->where('email', 'like', "%{$keyword}%");
        //             });
        //         })
        //         ->filterColumn('domain_forwarding_url', function($query, $keyword) {
        //             $query->whereHas('user', function($q) use ($keyword) {
        //                 $q->where('domain_forwarding_url', 'like', "%{$keyword}%");
        //             });
        //         })
        //         ->filterColumn('plan_name', function($query, $keyword) {
        //             $query->whereHas('plan', function($q) use ($keyword) {
        //                 $q->where('name', 'like', "%{$keyword}%");
        //             });
        //         })
        //         ->filterColumn('total_inboxes', function($query, $keyword) {
        //             $query->whereHas('reorderInfo', function($q) use ($keyword) {
        //                 $q->where('total_inboxes', 'like', "%{$keyword}%");
        //             });
        //         })
        //         ->orderColumn('email', function($query, $direction) {
        //             $query->orderBy(
        //                 User::select('email')
        //                     ->whereColumn('users.id', 'orders.user_id')
        //                     ->latest()
        //                     ->take(1),
        //                 $direction
        //             );
        //         })
        //         ->orderColumn('domain_forwarding_url', function($query, $direction) {
        //             $query->orderBy(
        //                 User::select('domain_forwarding_url')
        //                     ->whereColumn('users.id', 'orders.user_id')
        //                     ->latest()
        //                     ->take(1),
        //                 $direction
        //             );
        //         })
        //         ->orderColumn('plan_name', function($query, $direction) {
        //             $query->orderBy(
        //                 Plan::select('name')
        //                     ->whereColumn('plans.id', 'orders.plan_id')
        //                     ->latest()
        //                     ->take(1),
        //                 $direction
        //             );
        //         })
        //         ->rawColumns(['action','status'])
        //         ->make(true);
        // } catch (Exception $e) {
        //     Log::error('Error in getOrders', [
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString()
        //     ]);

        //     return response()->json([
        //         'error' => true,
        //         'message' => 'Error loading orders: ' . $e->getMessage()
        //     ], 500);
        // }
        
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
                            <a href="#" class="dropdown-item markStatus" id="markStatus"
                                data-id="' . $order->chargebee_subscription_id . '"
                                data-status="' . $order->status_manage_by_admin . '"
                                data-reason="' . $order->reason . '">
                                <i class="fa-solid fa-flag"></i> &nbsp;Mark Status
                            </a>
                        </li>
                        <li>
                            <a href="#" class="dropdown-item splitView" id="splitView"
                                data-order-id="' . $order->id . '">
                                <i class="fa-solid fa-columns"></i> &nbsp;Split View
                            </a>
                        </li>
                        ') .
                    '</ul>
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
                        'order_id' => $order->id
                    ]);
                })
                ->rawColumns(['action', 'status', 'timer'])
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
            'Order status updated by admin', // Description
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
    
  

 
//   public function subscriptionCancelProcess(Request $request)
//   {
     
//       $request->validate([
//           'chargebee_subscription_id' => 'required|string',
//           'reason' => 'nullable|string',
//           'marked_status'=>'required|string'
         
//       ]);

//       $user = auth()->user();
//       $subscription = Subscription::where('chargebee_subscription_id', $request->chargebee_subscription_id)
//           ->first();

//       if (!$subscription || $subscription->status !== 'active') {
//           return response()->json([
//               'success' => false,
//               'message' => 'No active subscription found'
//           ], 404);
//       }

//       try {
//           if($request->marked_status=="Reject" ||$request->marked_status=="Cancelled" ){
//           $result = \ChargeBee\ChargeBee\Models\Subscription::cancelForItems($request->chargebee_subscription_id, [
//               "end_of_term" => false,
//               "credit_option" => "none",
//               "unbilled_charges_option" => "delete",
//               "account_receivables_handling" => "no_action"
//           ]);

//           $subscriptionData = $result->subscription();
//           $invoiceData = $result->invoice();
//           $customerData = $result->customer();

//           if ($result->subscription()->status === 'cancelled') {
//               // Update subscription status and end date
//               $subscription->update([
//                   'status' => 'cancelled',
//                   'cancellation_at' => now(),
//                   'reason' => $request->reason,
//                   'end_date' => $this->getEndExpiryDate($subscription->start_date),
//               ]);

//               // Update user status
//               $user->update([
//                   'subscription_status' => 'cancelled',
//                   'subscription_id' => null,
//                   'plan_id' => null
//               ]);

//               // Update order status
//               $order = Order::where('chargebee_subscription_id', $request->chargebee_subscription_id)->first();
//               if ($order) {
//                   $order->update([
//                       'status_manage_by_admin' => 'cancelled',
//                   ]);
//               }

//               try {
//                   // Send email to user
//                   // Mail::to($user->email)
//                   //     ->queue(new SubscriptionCancellationMail(
//                   //         $subscription, 
//                   //         $user, 
//                   //         $request->reason
//                   //     ));

//                   // Send email to admin
//                   // Mail::to(config('mail.admin_address', 'admin@example.com'))
//                   //     ->queue(new SubscriptionCancellationMail(
//                   //         $subscription, 
//                   //         $user, 
//                   //         $request->reason,
//                   //         true
//                   //     ));
//               } catch (\Exception $e) {
//                   // \Log::error('Failed to send subscription cancellation emails: ' . $e->getMessage());
//                   // Continue execution since the subscription was already cancelled
//               }

//               return response()->json([
//                   'success' => true,
//                   'message' => 'Subscription cancelled successfully'
//               ]);
//           }
//         }
//         else{
           
//             // Update order status
//             $order = Order::where('chargebee_subscription_id', $request->chargebee_subscription_id)->first();
//             if ($order) {
//                 $order->update([
//                     'status_manage_by_admin' =>$request->marked_status,
//                 ]);
//             }


//         }

//           return response()->json([
//               'success' => false,
//               'message' => 'Failed to cancel subscription in payment gateway'
//           ], 500);
//       } catch (\Exception $e) {
//           \Log::error('Error cancelling subscription: ' . $e->getMessage());
//           return response()->json([
//               'success' => false,
//               'message' => 'Failed to cancel subscription: ' . $e->getMessage()
//           ], 500);
//       }
//   }


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
                    // 'timer_started_at' => $order->timer_started_at,
                    'timer_started_at' => $order->timer_started_at ? $order->timer_started_at->toISOString() : null,
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
                        'created_at' => $split->created_at
                        
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'customer_name' => $order->user->name ?? 'N/A',
                    'created_at' => $order->created_at,
                    'completed_at' => $order->completed_at,
                    'status' => $order->status_manage_by_admin ?? 'pending',
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
     * Get emails for a specific order panel split
     */
    public function getSplitEmails($orderPanelId)
    {
        try {
            // Verify the contractor has access to this order panel
            $orderPanel = OrderPanel::with(['order'])
                ->whereHas('userOrderPanelAssignments', function($query) {
                    // $query->where('contractor_id', auth()->id());
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
  
}
