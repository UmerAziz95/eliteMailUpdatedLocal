<?php

namespace App\Http\Controllers\Contractor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\Status;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        
       
        // Get tickets assigned to the contractor
        $assignedTickets = SupportTicket::where('assigned_to', Auth::id())
            ->get();
        
    
            
        // Calculate ticket statistics
        $totalTickets = $assignedTickets->count();
        $newTickets = $assignedTickets->where('status', 'open')->count();
        $inProgressTickets = $assignedTickets->where('status', 'in_progress')->count();
        $resolvedTickets = $assignedTickets->where('status', 'closed')->count();
        // dd($totalTickets, $newTickets, $inProgressTickets, $resolvedTickets);

        // Get orders assigned to the contractor or unassigned
        $orders = Order::where('assigned_to', Auth::id());

        $totalOrders = $orders->count();
        
        // Get orders by status
        $pendingOrders = $orders->clone()->where('status_manage_by_admin', 'pending')->where('assigned_to', Auth::id())->count();
        $inProgressOrders = $orders->clone()->where('status_manage_by_admin', 'in-progress')->count();
        $completedOrders = $orders->clone()->where('status_manage_by_admin', 'completed')->count();
        $rejectedOrders = $orders->clone()->where('status_manage_by_admin', 'reject')->count();
        $cancelledOrders = $orders->clone()->whereIn('status_manage_by_admin', ['cancelled','removed'])->count();
        
        // Get queued orders (not assigned to any contractor)    
        $queuedOrders = Order::whereNull('assigned_to')
            ->where('status_manage_by_admin', 'pending')
            ->count();
        
        // Calculate percentage changes (last week vs previous week)
        $lastWeek = [Carbon::now()->subWeek(), Carbon::now()];
        
        $previousWeek = [Carbon::now()->subWeeks(2), Carbon::now()->subWeek()];
        // dd($lastWeek, $previousWeek);
        $lastWeekOrders = $orders->clone()->whereBetween('created_at', $lastWeek)->count();
        $previousWeekOrders = $orders->clone()->whereBetween('created_at', $previousWeek)->count();

        $percentageChange = $previousWeekOrders > 0 
            ? (($lastWeekOrders - $previousWeekOrders) / $previousWeekOrders) * 100 
            : 0;

        return view('contractor.dashboard', compact(
            'totalTickets',
            'newTickets',
            'inProgressTickets', 
            'resolvedTickets',
            'totalOrders',
            'pendingOrders',
            'inProgressOrders',
            'completedOrders',
            'rejectedOrders',
            'cancelledOrders',
            'queuedOrders',
            'percentageChange'
        ));
    }

    public function getOrdersHistory(Request $request)
    {
        try {
            // Get orders assigned to the contractor
            $orders = Order::with(['user', 'plan', 'reorderInfo'])
                ->where('assigned_to', Auth::id())
                ->select('orders.*')
                ->orderBy('orders.updated_at', 'desc')
                ->limit(5); // Get only last 5 orders

            if ($request->ajax()) {
                return datatables()
                    ->of($orders)
                    ->addColumn('name', function ($order) {
                        return $order->user ? $order->user->name : 'N/A';
                    })
                    ->addColumn('plan_name', function ($order) {
                        return $order->plan ? $order->plan->name : 'N/A';
                    })
                    ->addColumn('total_inboxes', function ($order) {
                        if (!$order->reorderInfo || $order->reorderInfo->isEmpty()) {
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
                    ->editColumn('status', function ($order) {
                        $status = strtolower($order->status_manage_by_admin ?? 'n/a');
                        $statusColors = [
                            'pending' => 'warning',
                            'in-progress' => 'info',
                            'completed' => 'success',
                            'reject' => 'danger',
                            'cancelled' => 'secondary',
                            'draft' => 'secondary'
                        ];
                        $statusClass = $statusColors[$status] ?? 'secondary';
                        return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                            . ucfirst($status) . '</span>';
                    })
                    ->editColumn('created_at', function ($order) {
                        return $order->created_at ? $order->created_at->format('d M, Y') : '';
                    })
                    ->rawColumns(['status'])
                    ->make(true);
            }

            return response()->json(['error' => 'Invalid request'], 400);
        } catch (\Exception $e) {
            \Log::error('Error in getOrdersHistory: ' . $e->getMessage());
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}