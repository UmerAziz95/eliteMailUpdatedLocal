<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use DataTables;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $subscriptions = Subscription::with(['user', 'plan', 'order'])
                ->where('status', 'active')
                ->where('user_id', auth()->user()->id);

            // Apply filters
            if ($request->filter_id) {
                $subscriptions->where('id', 'like', '%' . $request->filter_id . '%');
            }
            
            if ($request->filter_status) {
                $subscriptions->where('status', $request->filter_status);
            }
            
            if ($request->filter_start_date) {
                $subscriptions->whereDate('created_at', '>=', $request->filter_start_date);
            }
            
            if ($request->filter_end_date) {
                $subscriptions->whereDate('created_at', '<=', $request->filter_end_date);
            }
    
            // Calculate counters
            $total = Subscription::where('user_id', auth()->user()->id)->count();
            $active = Subscription::where('status', 'active')->where('user_id', auth()->user()->id)->count();
            $inactive = Subscription::where('status', 'cancelled')->where('user_id', auth()->user()->id)->count();
            $completed = Subscription::where('status', 'completed')->where('user_id', auth()->user()->id)->count();
    
            return DataTables::of($subscriptions)
                ->addColumn('created_at', function ($subscription) {
                    return $subscription->created_at ? $subscription->created_at->format('d F, Y') : 'N/A';
                })
                ->addColumn('amount', function ($subscription) {
                    return $subscription->order && $subscription->order->amount
                        ? '$' . number_format($subscription->order->amount, 2)
                        : 'N/A'; 
                })
                ->addColumn('chargebee_subscription_id', function ($subscription) {
                    return $subscription->chargebee_subscription_id ?? 'N/A';
                })
                ->addColumn('last_billing', function ($subscription) {
                    return $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d F, Y') : 'N/A';
                })
                ->addColumn('next_billing', function ($subscription) {
                    return $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('d F, Y') : 'N/A';
                })
                ->addColumn('order_id', function ($subscription) {
                    return $subscription->order_id ?? 'N/A';
                })
                ->addColumn('status', function ($subscription) {
                    $status = $subscription->status ?? 'N/A';
                    $statusClass = match ($status) {
                        'active' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'primary',
                        default => 'secondary',
                    };
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($status) . '</span>';
                })
                ->addColumn('action', function ($subscription) {
                    return '<div class="dropdown">
                                <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <i class="fa-solid fa-ellipsis-vertical"></i>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="CancelSubscription(\'' . $subscription->chargebee_subscription_id . '\')">Cancel Subscription</a></li>
                                </ul>
                            </div>';
                })
                ->rawColumns(['action', 'status'])
                ->with([
                    'counters' => [
                        'total' => $total,
                        'active' => $active,
                        'inactive' => $inactive,
                        'completed' => $completed,
                    ]
                ])
                ->make(true);
        }
    
        return view("customer/subscriptions/subscriptions", ['plans' => []]);
    }
    
    public function cancelled_subscriptions(Request $request)
    {
        if ($request->ajax()) {
            $subscriptions = Subscription::with(['user', 'plan', 'order'])
                ->where('status', 'cancelled')
                ->where('user_id', auth()->user()->id);

            // Apply filters
            if ($request->filter_id) {
                $subscriptions->where('id', 'like', '%' . $request->filter_id . '%');
            }
            
            if ($request->filter_start_date) {
                $subscriptions->whereDate('created_at', '>=', $request->filter_start_date);
            }
            
            if ($request->filter_end_date) {
                $subscriptions->whereDate('created_at', '<=', $request->filter_end_date);
            }
    
            // Calculate counters
            $total = Subscription::where('user_id', auth()->user()->id)->count();
            $active = Subscription::where('status', 'active')->where('user_id', auth()->user()->id)->count();
            $inactive = Subscription::where('status', 'cancelled')->where('user_id', auth()->user()->id)->count();
            $completed = Subscription::where('status', 'completed')->where('user_id', auth()->user()->id)->count();
    
            return DataTables::of($subscriptions)
                ->addColumn('created_at', function ($subscription) {
                    return $subscription->created_at ? $subscription->created_at->format('d F, Y') : 'N/A';
                })
                ->addColumn('amount', function ($subscription) {
                    return $subscription->order && $subscription->order->amount
                        ? '$' . number_format($subscription->order->amount, 2)
                        : 'N/A'; 
                })
                ->addColumn('chargebee_subscription_id', function ($subscription) {
                    return $subscription->chargebee_subscription_id ?? 'N/A';
                })
                ->addColumn('last_billing', function ($subscription) {
                    return $subscription->start_date ? \Carbon\Carbon::parse($subscription->start_date)->format('d F, Y') : 'N/A';
                })
                ->addColumn('order_id', function ($subscription) {
                    return $subscription->order_id ?? 'N/A';
                })
                ->addColumn('status', function ($subscription) {
                    $status = $subscription->status ?? 'N/A';
                    $statusClass = match ($status) {
                        'active' => 'success',
                        'cancelled' => 'danger',
                        'completed' => 'primary',
                        default => 'secondary',
                    };
                    return '<span class="py-1 px-2 text-' . $statusClass . ' border border-' . $statusClass . ' rounded-2 bg-transparent">' 
                        . ucfirst($status) . '</span>';
                })
                ->rawColumns(['status'])
                ->with([
                    'counters' => [
                        'total' => $total,
                        'active' => $active,
                        'inactive' => $inactive,
                        'completed' => $completed,
                    ]
                ])
                ->make(true);
        }
    
        return view("customer/subscriptions/subscriptions", ['plans' => []]);
    }
}
