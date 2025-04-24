<?php

namespace App\Http\Controllers\Contractor;

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
use App\Models\Invoice;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    private $statuses = [
        "Pending" => "warning",
        "Approved" => "success",
        "Cancel" => "danger",
        "Expired" => "secondary",
        "In-progress" => "primary",
        "Completed" => "success",
        "Delivered" => "success",
    ];
    // payment-status
    private $paymentStatuses = [
        "Pending" => "warning",
        "Paid" => "success",
        "Failed" => "danger",
        "Refunded" => "secondary"
    ];
    public function index()
    {
        $plans = Plan::where('is_active', true)->get();;
        
        // Get order statistics
        $orders = Order::query();
        
        $totalOrders = $orders->count();
        
        // Get orders by admin status
        $pendingOrders = Order::where('status_manage_by_admin', 'Pending')
            ->count();
            
        $completedOrders = Order::where('status_manage_by_admin', 'Completed')
            ->count();
            
        $inProgressOrders = Order::where('status_manage_by_admin', 'In-Progress')
            ->count();
        $expiredOrders = Order::where('status_manage_by_admin', 'Expired')
            ->count();
        $approvedOrders = Order::where('status_manage_by_admin', 'Approved')
            ->count();
        // Calculate percentage changes (last week vs previous week)
        $lastWeek = [Carbon::now()->subWeek(), Carbon::now()];
        $previousWeek = [Carbon::now()->subWeeks(2), Carbon::now()->subWeek()];

        $lastWeekOrders = Order::whereBetween('created_at', $lastWeek)
            ->count();
            
        $previousWeekOrders = Order::whereBetween('created_at', $previousWeek)
            ->count();

        $percentageChange = $previousWeekOrders > 0 
            ? (($lastWeekOrders - $previousWeekOrders) / $previousWeekOrders) * 100 
            : 0;
        $statuses = $this->statuses;
        return view('contractor.orders.orders', compact(
            'plans', 
            'totalOrders', 
            'pendingOrders', 
            'completedOrders', 
            'inProgressOrders',
            'percentageChange',
            'statuses', // Pass statuses to the view,
            'expiredOrders',
            'approvedOrders'
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
        $order = Order::with(['subscription', 'user', 'invoice', 'reorderInfo','plan'])->findOrFail($id);
        // dd($order);
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
                'current_term_end' => $endDate ? Carbon::parse($endDate)->format('F d, Y') : null,
                'next_billing_at' => $endDate ? null : Carbon::parse($startDate)
                    ->add($billingPeriod, $billingPeriodUnit)
                    ->format('F d, Y')
            ];
        }
    
        return view('contractor.orders.order-view', compact('order', 'nextBillingInfo'));
    }

    public function getOrders(Request $request)
    {
        Log::info('Orders data request received', [
            'plan_id' => $request->plan_id,
            'request_data' => $request->all()
        ]);

        try {
            $orders = Order::query()
                ->with(['user', 'plan', 'reorderInfo'])
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
                                    <li><hr class="dropdown-divider"></li>
                                    <li class="dropdown-header">Manage Statuses</li>
                                    ' . $statusOptions . '
                                </ul>
                            </div>';
                })
                ->editColumn('created_at', function ($order) {
                    return $order->created_at ? $order->created_at->format('d F, Y') : '';
                })
                ->editColumn('status', function ($order) {
                    $statusClass = $this->statuses[ucfirst($order->status_manage_by_admin ?? 'N/A')] ?? 'secondary';
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($order->status_manage_by_admin ?? 'N/A') . '</span>';
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
                ->rawColumns(['action', 'status'])
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
            $order->status_manage_by_admin = strtolower($request->status);
            $order->save();

            // Log the status change
            Log::info('Order status updated', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $order->status_manage_by_admin,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully'
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
                    $statusKey = ucfirst($invoice->order->status_manage_by_admin ?? 'N/A');
                    return '<span class="py-1 px-2 text-' . ($this->statuses[$statusKey] ?? 'secondary') . ' border border-' . ($this->statuses[$statusKey] ?? 'secondary') . ' rounded-2 bg-transparent">' 
                        . $statusKey . '</span>';
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
}
