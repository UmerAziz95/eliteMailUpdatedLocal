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
        $assignedTickets = SupportTicket::where('category', 'order')
            ->where('assigned_to', Auth::id())
            ->get();
            
        // Calculate ticket statistics
        $totalTickets = $assignedTickets->count();
        $newTickets = $assignedTickets->where('status', 'open')->count();
        $inProgressTickets = $assignedTickets->where('status', 'in_progress')->count();
        $resolvedTickets = $assignedTickets->where('status', 'closed')->count();

        // Get orders assigned to the contractor or unassigned
        $orders = Order::where(function($query) {
            $query->whereNull('assigned_to')
                  ->orWhere('assigned_to', Auth::id());
        });

        $totalOrders = $orders->count();
        
        // Get orders by status
        $pendingOrders = $orders->clone()->where('status_manage_by_admin', 'pending')->count();
        $inProgressOrders = $orders->clone()->where('status_manage_by_admin', 'in-progress')->count();
        $completedOrders = $orders->clone()->where('status_manage_by_admin', 'completed')->count();
        $cancelledOrders = $orders->clone()->where('status_manage_by_admin', 'cancelled')->count();
        
        // Calculate percentage changes (last week vs previous week)
        $lastWeek = [Carbon::now()->subWeek(), Carbon::now()];
        
        $previousWeek = [Carbon::now()->subWeeks(2), Carbon::now()->subWeek()];
        // dd($lastWeek, $previousWeek);
        $lastWeekOrders = $orders->clone()->whereBetween('created_at', $lastWeek)->count();
        $previousWeekOrders = $orders->clone()->whereBetween('created_at', $previousWeek)->count();

        $percentageChange = $previousWeekOrders > 0 
            ? (($lastWeekOrders - $previousWeekOrders) / $previousWeekOrders) * 100 
            : 0;
        // dd([
        //     'lastWeekOrders' => $lastWeekOrders,
        //     'previousWeekOrders' => $previousWeekOrders,
        //     'percentageChange' => $percentageChange,
        //     'lastWeek' => $lastWeek,
        //     'previousWeek' => $previousWeek,
        //     'orders' => $orders->get(),
        //     'assignedTickets' => $assignedTickets,
        //     'totalOrders' => $totalOrders,
        //     'pendingOrders' => $pendingOrders,
        //     'inProgressOrders' => $inProgressOrders,
        //     'completedOrders' => $completedOrders,
        //     'cancelledOrders' => $cancelledOrders,
        //     'totalTickets' => $totalTickets,
        //     'newTickets' => $newTickets,
        //     'inProgressTickets' => $inProgressTickets,
        //     'resolvedTickets' => $resolvedTickets
        // ]);
        return view('contractor.dashboard', compact(
            'totalTickets',
            'newTickets',
            'inProgressTickets', 
            'resolvedTickets',
            'totalOrders',
            'pendingOrders',
            'inProgressOrders',
            'completedOrders',
            'cancelledOrders',
            'percentageChange'
        ));
    }
}