<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Plan;
use App\Models\Order;
use App\Models\Status;
use Carbon\Carbon;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\OrderPanelSplit;
use App\Models\OrderPanel;
use App\Models\OrderEmail;
use Illuminate\Support\Facades\Http;
use App\Models\DomainHealthCheck;


class DomainHealthDashboardController extends Controller
{
    //
    
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
    public function index(Request $request){  
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

        return view('admin.domain_health_dashboard.index', compact(
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

 public function getCardOrders(Request $request)
{
    try {
        $query = Order::query()
            ->select('orders.*')
            ->with(['user', 'plan', 'reorderInfo', 'orderPanels.orderPanelSplits', 'assignedTo'])
            ->leftJoin('plans', 'orders.plan_id', '=', 'plans.id')
            ->leftJoin('users', 'orders.user_id', '=', 'users.id');
            // ->where('orders.status_manage_by_admin', 'completed'); // ✅ Main filter

        // Additional filters
        if ($request->filled('plan_id')) {
            $query->where('orders.plan_id', $request->plan_id);
        }

        if ($request->filled('orderId')) {
            $query->where('orders.id', 'like', "%{$request->orderId}%");
        }

        if ($request->filled('status')) {
            $query->where('orders.status_manage_by_admin', $request->status);
        }

        if ($request->filled('email')) {
            $query->where('users.email', 'like', "%{$request->email}%");
        }

        if ($request->filled('name')) {
            $query->where('users.name', 'like', "%{$request->name}%");
        }

        if ($request->filled('domain')) {
            $query->whereHas('reorderInfo', function ($q) use ($request) {
                $q->where('forwarding_url', 'like', "%{$request->domain}%");
            });
        }

        if ($request->filled('totalInboxes')) {
            $query->whereHas('reorderInfo', function ($q) use ($request) {
                $q->whereRaw('(
                    CASE 
                        WHEN domains IS NOT NULL AND domains != "" THEN 
                            (LENGTH(domains) - LENGTH(REPLACE(REPLACE(REPLACE(domains, ",", ""), CHAR(10), ""), CHAR(13), "")) + 1) * inboxes_per_domain
                        ELSE total_inboxes 
                    END
                ) = ?', [$request->totalInboxes]);
            });
        }

        if ($request->filled('startDate')) {
            $query->whereDate('orders.created_at', '>=', $request->startDate);
        }

        if ($request->filled('endDate')) {
            $query->whereDate('orders.created_at', '<=', $request->endDate);
        }

        $orders = $query->get(); // ✅ Only fetch after applying filters

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
                    <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-ellipsis-vertical"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="' . route('admin.domains.orders.view', $order->id) . '">
                                <i class="fa-solid fa-eye"></i> &nbsp;View Domains Health
                            </a>
                        </li>
                        </ul>
                </div>';
            })
            ->editColumn('created_at', fn($order) => $order->created_at?->format('d F, Y'))
            ->editColumn('status', function ($order) {
                $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                $statusClass = $this->statuses[$status] ?? 'secondary';
                return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                    . ucfirst($status) . '</span>';
            })
            ->addColumn('name', fn($order) => $order->user->name ?? 'N/A')
            ->addColumn('email', fn($order) => $order->user->email ?? 'N/A')
            ->addColumn('split_counts', function ($order) {
                return $order->orderPanels->sum(fn($panel) => $panel->orderPanelSplits->count()) . ' split(s)';
            })
            ->addColumn('plan_name', fn($order) => $order->plan->name ?? 'N/A')
            ->addColumn('total_inboxes', function ($order) {
                $reorderInfo = $order->reorderInfo->first();
                if (!$reorderInfo) return 'N/A';

                $domains = $reorderInfo->domains ?? '';
                $inboxesPerDomain = $reorderInfo->inboxes_per_domain ?? 1;
                $domainsArray = [];

                $lines = preg_split('/\r\n|\r|\n/', $domains);
                foreach ($lines as $line) {
                    $lineItems = array_filter(explode(',', $line));
                    foreach ($lineItems as $item) {
                        if (trim($item)) $domainsArray[] = trim($item);
                    }
                }

                $totalDomains = count($domainsArray);
                $calculatedTotal = $totalDomains * $inboxesPerDomain;
                return $calculatedTotal > 0 ? $calculatedTotal : ($reorderInfo->total_inboxes ?? 'N/A');
            })
            ->addColumn('timer', function ($order) {
                return json_encode([
                    'created_at' => $order->created_at?->toISOString(),
                    'status' => strtolower($order->status_manage_by_admin ?? 'n/a'),
                    'completed_at' => $order->completed_at?->toISOString(),
                    'timer_started_at' => $order->timer_started_at?->toISOString(),
                    'timer_paused_at' => $order->timer_paused_at?->toISOString(),
                    'total_paused_seconds' => $order->total_paused_seconds ?? 0,
                    'order_id' => $order->id
                ]);
            })
            ->addColumn('contractor_name', fn($order) => $order->assignedTo->name ?? 'Unassigned')
            ->rawColumns(['action', 'status', 'timer'])
            ->make(true);

    } catch (Exception $e) {
        Log::error('Error in getCardOrders', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'error' => true,
            'message' => 'Error loading orders: ' . $e->getMessage()
        ], 500);
    }
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
    
        return view('admin.domain_health_dashboard.order-view', compact('order', 'nextBillingInfo'));
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



public function checkDomainHealth($orderId = null)
{
    if (!$orderId) {
        return false;
    }

    $orders = Order::with(['reorderInfo'])->where("status_manage_by_admin", "completed")->get();
    if (!$orders) {
        return false;
    }

    $overallResults = [];

    foreach ($orders as $order) {
        if ($order->reorderInfo->isEmpty()) {
            continue; // Skip orders without reorder info
        }

        $domains = explode(',', $order->reorderInfo->first()->domains);
        $apiKey = env('MXTOOLBOX_API_KEY');
        $baseUrl = "https://mxtoolbox.com/api/v1/lookup";
        $results = [];

        $unhealthyDomains = 0;
        $blacklistedDomains = 0;

        foreach ($domains as $domain) {
            try {
                $dnsUrl = "$baseUrl/dns/$domain?authorization=$apiKey";
                $blacklistUrl = "$baseUrl/blacklist/$domain?authorization=$apiKey";

                $dnsResponse = Http::get($dnsUrl)->json();
                $blacklistResponse = Http::get($blacklistUrl)->json();

                $dnsErrors = collect($dnsResponse['Failed'] ?? [])
                    ->pluck('Name')
                    ->toArray();
                $dnsStatus = empty($dnsErrors) ? "OK" : implode(", ", $dnsErrors);

                $listed = !empty($blacklistResponse['Failed']);
                $listedOn = array_map(function($item) {
                    return $item['Name'];
                }, $blacklistResponse['Failed'] ?? []);

                if ($listed && !empty($dnsErrors)) {
                    $status = "Critical Error";
                    $summary = "Domain has DNS issues and is blacklisted (" . implode(", ", $listedOn) . ")";
                } elseif ($listed) {
                    $status = "Blacklisted";
                    $summary = "Domain is blacklisted (" . implode(", ", $listedOn) . ")";
                } elseif (!empty($dnsErrors)) {
                    $status = "DNS Issues";
                    $summary = "DNS issues: " . implode(", ", $dnsErrors) . ". Not blacklisted.";
                } else {
                    $status = "Healthy";
                    $summary = "No DNS errors or blacklist issues detected.";
                }

                DomainHealthCheck::create([
                    'order_id' => $orderId,
                    'domain' => $domain,
                    'status' => $status,
                    'summary' => $summary,
                    'dns_status' => $dnsStatus,
                    'dns_errors' => $dnsErrors,
                    'blacklist_listed' => $listed,
                    'blacklist_listed_on' => $listedOn
                ]);

                $results[] = [
                    "domain" => $domain,
                    "status" => $status,
                    "summary" => $summary,
                    "dns" => [
                        "status" => empty($dnsErrors) ? "OK" : $dnsStatus,
                        "errors" => $dnsErrors,
                    ],
                    "blacklist" => [
                        "listed" => $listed,
                        "listed_on" => $listedOn,
                    ]
                ];

                if ($status !== "Healthy") {
                    $unhealthyDomains++;
                }
                if ($listed) {
                    $blacklistedDomains++;
                }

            } catch (\Exception $e) {
                $results[] = [
                    "domain" => $domain,
                    "status" => "Error",
                    "summary" => "Failed to fetch info: " . $e->getMessage(),
                    "dns" => ["status" => "Error", "errors" => []],
                    "blacklist" => ["listed" => false, "listed_on" => []]
                ];
                $unhealthyDomains++;
            }
        }

        $overallResults[] = [
            "order_id" => $order->id,
            "results" => $results,
            "total_domains" => count($domains),
            "unhealthy_domains" => $unhealthyDomains,
            "blacklisted_domains" => $blacklistedDomains,
        ];

        // Send Slack notification if there are any unhealthy domains
        if ($unhealthyDomains > 0) {
            $this->notifyOrderSlack(
                $order->id,
                count($domains),
                $unhealthyDomains,
                $blacklistedDomains,
                $results
            );
        }
    }

    return response()->json(['results' => $overallResults]);
}

// Batch Slack notification per order
private function notifyOrderSlack($orderId, $totalDomains, $unhealthyDomains, $blacklistedDomains, $domainResults)
{
    $message = "*Domain Health Alert*\n";
    $message .= "Order ID: $orderId\n";
    $message .= "Total Domains: $totalDomains\n";
    $message .= "Unhealthy Domains: $unhealthyDomains\n";
    $message .= "Blacklisted Domains: $blacklistedDomains\n\n";
    $message .= "*Domain Details:*\n";

    foreach ($domainResults as $result) {
        if ($result['status'] !== "Healthy") {
            $message .= "• Domain: {$result['domain']}\n";
            $message .= "  Status: {$result['status']}\n";
            $message .= "  Summary: {$result['summary']}\n";
            if (!empty($result['dns']['errors'])) {
                $message .= "  DNS Errors: " . implode(", ", $result['dns']['errors']) . "\n";
            }
            if (!empty($result['blacklist']['listed_on'])) {
                $message .= "  Blacklisted on: " . implode(", ", $result['blacklist']['listed_on']) . "\n";
            }
            $message .= "\n";
        }
    }

    Http::post('https://hooks.slack.com/services/your/webhook/url', ['text' => $message]);
}

    //  public function checkDomainHealth($orderId =null)
//     {
//         if (!$orderId) {
//             return false;
//         }

//        $order= Order::with(['reorderInfo'])->find($orderId);
//         if (!$order) {
//             return false;
//         }
  
//         // $domains = ["app.projectinbox.ai","test.projectinbox.ai","projectinbox.ai"];
//         $domains =explode(',', $order->reorderInfo->first()->domains);
//         $apiKey =env('MXTOOLBOX_API_KEY');
//         $baseUrl = "https://mxtoolbox.com/api/v1/lookup";
//         $results = [];

//         foreach ($domains as $domain) {
//             try {
//                 $dnsUrl = "$baseUrl/dns/$domain?authorization=$apiKey";
//                 $blacklistUrl = "$baseUrl/blacklist/$domain?authorization=$apiKey";

//                 $dnsResponse = Http::get($dnsUrl)->json();
//                 $blacklistResponse = Http::get($blacklistUrl)->json();

//                 // Determine DNS status & errors
//                 $dnsErrors = collect($dnsResponse['Failed'] ?? [])
//                     ->pluck('Name')
//                     ->toArray();
//                 $dnsStatus = empty($dnsErrors) ? "OK" : implode(", ", $dnsErrors);

//                 // Determine blacklist status & lists
//                 $listed = !empty($blacklistResponse['Failed']);
//                 $listedOn = array_map(function($item) {
//                     return $item['Name'];
//                 }, $blacklistResponse['Failed'] ?? []);

//                 // Set overall status and summary
//                 if ($listed && !empty($dnsErrors)) {
//                     $status = "Critical Error";
//                     $summary = "Domain has DNS issues and is blacklisted (" . implode(", ", $listedOn) . ")";
//                 } elseif ($listed) {
//                     $status = "Blacklisted";
//                     $summary = "Domain is blacklisted (" . implode(", ", $listedOn) . ")";
//                 } elseif (!empty($dnsErrors)) {
//                     $status = "DNS Issues";
//                     $summary = "DNS issues: " . implode(", ", $dnsErrors) . ". Not blacklisted.";
//                 } else {
//                     $status = "Healthy";
//                     $summary = "No DNS errors or blacklist issues detected.";
//                 }

//                 // Store in DB
//                 DomainHealthCheck::create([
//                     'order_id' => $orderId,
//                     'domain' => $domain,
//                     'status' => $status,
//                     'summary' => $summary,
//                     'dns_status' => $dnsStatus,
//                     'dns_errors' => $dnsErrors,
//                     'blacklist_listed' => $listed,
//                     'blacklist_listed_on' => $listedOn
//                 ]);

//                 // Assemble result
//                 $results[] = [
//                     "domain" => $domain,
//                     "status" => $status,
//                     "summary" => $summary,
//                     "dns" => [
//                         "status" => empty($dnsErrors) ? "OK" : $dnsStatus,
//                         "errors" => $dnsErrors,
//                     ],
//                     "blacklist" => [
//                         "listed" => $listed,
//                         "listed_on" => $listedOn,
//                     ]
//                 ];

//                 // Slack notification if problems
//                 if ($status !== "Healthy") {
//                     $this->notifySlack($domain, $status, $summary, $dnsErrors, $listedOn);
//                 }

//             } catch (\Exception $e) {
//                 $results[] = [
//                     "domain" => $domain,
//                     "status" => "Error",
//                     "summary" => "Failed to fetch info: " . $e->getMessage(),
//                     "dns" => ["status" => "Error", "errors" => []],
//                     "blacklist" => ["listed" => false, "listed_on" => []]
//                 ];
//             }
//         }

//         return response()->json(['results' => $results]);
//     }



  public function domainsListings($orderId =null)
{
    
    if (!$orderId) { 
        return response()->json(['message' => 'Order ID not found'], 404);
    }

    $records = \App\Models\DomainHealthCheck::where('order_id', $orderId)->get();

    // Transform for DataTable: map to columns you wish to show
    $data = $records->map(function ($item) {
        return [
            'domain' => $item->domain ?? '',
            'status' => $item->status ?? '',
            'summary' => $item->summary ?? '',
            // You can add more columns if needed, e.g.
            'dns_status' => $item->dns_status ?? '',
            'blacklist_listed' => $item->blacklist_listed ? 'Yes' : 'No',
        ];
    })->toArray();

    return response()->json([
        'success' => true,
        'data' => $data
    ]);
}


}
