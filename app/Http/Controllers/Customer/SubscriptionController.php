<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use DataTables;
class SubscriptionController extends Controller
{
    //
    public function index(Request $request)   //active subscriptions
    {
        if ($request->ajax()) {
            $subscriptions = Subscription::with(['user', 'plan', 'order'])->where('status','active')->where('user_id', auth()->user()->id)
           ->orderBy('created_at', 'asc');
    
            // Calculate counters
            $total = Subscription::count();
            $active = Subscription::where('status', 'active')->count();
            $inactive = Subscription::where('status', 'cancelled')->count();
            $completed = Subscription::where('status', 'completed')->count();
    
            return DataTables::of($subscriptions)
                ->addColumn('name', function ($subscription) {
                    return $subscription->user->name ?? 'N/A';
                })
                ->addColumn('email', function ($subscription) {
                    return $subscription->user->email ?? 'N/A';
                })
                ->addColumn('created_at', function ($subscription) {
                    return $subscription->created_at ? $subscription->created_at->format('y/m/d') : 'N/A';
                })
                ->addColumn('amount', function ($subscription) {
                    return $subscription->order && $subscription->order->amount
                        ? $subscription->order->amount
                        : 'N/A'; 
                })
                ->addColumn('action', function ($subscription) {
                    return '<div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary" onclick="viewSubscription(' . $subscription->id . ')">View</button>
                            </div>';
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && $request->input('search.value') != '') {
                        $search = $request->input('search.value');
                        $query->where(function ($q) use ($search) {
                            $q->where('subscriptions.id', 'like', "%{$search}%")
                              ->orWhere('subscriptions.status', 'like', "%{$search}%")
                              ->orWhereHas('user', function ($q2) use ($search) {
                                  $q2->where('email', 'like', "%{$search}%");
                              });
                        });
                    }
                })
                ->rawColumns(['action'])
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
    
    public function cancelled_subscriptions(Request $request) //inactive subscriptions
    {
        if ($request->ajax()) {
            $subscriptions = Subscription::with(['user', 'plan', 'order'])->where('status',"cancelled")
           ->orderBy('created_at', 'asc');
    
            // Calculate counters
            $total = Subscription::count();
            $active = Subscription::where('status', 'active')->count();
            $inactive = Subscription::where('status', 'cancelled')->count();
            $completed = Subscription::where('status', 'completed')->count();
    
            return DataTables::of($subscriptions)
            ->addColumn('name', function ($subscription) {
                return $subscription->user->name ?? 'N/A';
            })
                ->addColumn('email', function ($subscription) {
                    return $subscription->user->email ?? 'N/A';
                })
                ->addColumn('created_at', function ($subscription) {
                    return $subscription->created_at ? $subscription->created_at->format('y/m/d') : 'N/A';
                })
                ->addColumn('amount', function ($subscription) {
                    return $subscription->order && $subscription->order->amount
                        ? $subscription->order->amount
                        : 'N/A'; 
                })
                ->addColumn('action', function ($subscription) {
                    return '<div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary" onclick="viewSubscription(' . $subscription->id . ')">View</button>
                            </div>';
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && $request->input('search.value') != '') {
                        $search = $request->input('search.value');
                        $query->where(function ($q) use ($search) {
                            $q->where('subscriptions.id', 'like', "%{$search}%")
                              ->orWhere('subscriptions.status', 'like', "%{$search}%")
                              ->orWhereHas('user', function ($q2) use ($search) {
                                  $q2->where('email', 'like', "%{$search}%");
                              });
                        });
                    }
                })
                ->rawColumns(['action'])
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
 