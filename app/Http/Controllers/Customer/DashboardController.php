<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\SupportTicket;
use App\Models\Subscription;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        // Get latest active order with reorder info
        $latestOrder = $user->orders()
            ->with(['reorderInfo', 'subscription', 'plan'])
            ->latest()
            ->first();

        // Get inbox statistics
        $totalInboxes = $user->orders()
            ->whereHas('reorderInfo')
            ->join('reorder_infos', 'orders.id', '=', 'reorder_infos.order_id')
            // not equal to 'cancelled' or 'reject'
            ->where('orders.status_manage_by_admin', '!=', 'cancelled')
            ->where('orders.status_manage_by_admin', '!=', 'reject')
            ->sum('reorder_infos.total_inboxes');
        // dd($totalInboxes);
        $activeInboxes = 0;
        $pendingInboxes = 0;
        // dd($latestOrder);
        if ($latestOrder) {
            // Active inboxes are those in completed orders
            $activeInboxes = $user->orders()
                ->where('status_manage_by_admin', 'completed')
                ->whereHas('reorderInfo')
                ->join('reorder_infos', 'orders.id', '=', 'reorder_infos.order_id')
                ->sum('reorder_infos.total_inboxes');

            // Pending inboxes are those in pending/in-progress orders
            $pendingInboxes = $user->orders()
                ->whereIn('status_manage_by_admin', ['pending', 'in-progress'])
                ->whereHas('reorderInfo')
                ->join('reorder_infos', 'orders.id', '=', 'reorder_infos.order_id')
                ->sum('reorder_infos.total_inboxes');
        }

        // Get subscription info
        $subscription = $user->subscription;
        $nextBillingInfo = null;
        
        if ($subscription && $subscription->status === 'active') {
            $subscriptionMeta = json_decode($subscription->meta ?? '[]', true);
            $nextBillingInfo = [
                'next_billing_at' => $subscription->next_billing_date ? Carbon::parse($subscription->next_billing_date)->format('M d, Y') : 'N/A',
                'amount' => $latestOrder->amount ?? '0.00'
            ];
        }
        // Get order statistics
        $totalOrders = $user->orders()->count();
        $pendingOrders = $user->orders()
            ->whereIn('status_manage_by_admin', ['pending', 'in-progress'])
            ->count();
        $completedOrders = $user->orders()
            ->where('status_manage_by_admin', 'completed')
            ->count();

        // Get ticket statistics
        $totalTickets = $user->tickets()->count();
        $newTickets = $user->tickets()
            ->where('status', 'open')
            ->count();
        $pendingTickets = $user->tickets()
            ->whereIn('status', ['in_progress'])
            ->count();
        $resolvedTickets = $user->tickets()
            ->where('status', 'closed')
            ->count();
        // dd($resolvedTickets, $pendingTickets, $totalTickets);
        return view('customer.dashboard', compact(
            'totalInboxes',
            'activeInboxes', 
            'pendingInboxes',
            'subscription',
            'nextBillingInfo',
            'totalOrders',
            'pendingOrders',
            'completedOrders',
            'totalTickets',
            'pendingTickets',
            'resolvedTickets',
            'newTickets',
        ));
    }
}