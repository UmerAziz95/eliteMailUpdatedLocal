<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use DataTables;
use Carbon\Carbon; 
class SubscriptionController extends Controller
{
    //
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $oneMonthAgo = Carbon::now()->subMonth()->startOfMonth(); // 2025-03-01 00:00:00
        $today = Carbon::today(); // 2025-04-26 00:00:00

        $subscriptions = Subscription::with(['user', 'plan', 'order'])
            ->where('status', 'active')
            ->whereHas('order', function ($query) use ($oneMonthAgo, $today) {
                $query->whereBetween('paid_at', [$oneMonthAgo, $today]);
            })
            ->orderBy('created_at', 'asc');
    
            // Apply filters (user_name, email, amount, status)
            if ($request->filled('user_name')) {
                $subscriptions->whereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->input('user_name') . '%');
                });
            }
    
            if ($request->filled('email')) {
                $subscriptions->whereHas('user', function ($q) use ($request) {
                    $q->where('email', 'like', '%' . $request->input('email') . '%');
                });
            }
    
            if ($request->filled('amount')) {
                $subscriptions->whereHas('order', function ($q) use ($request) {
                    $q->where('amount', 'like', '%' . $request->input('amount') . '%');
                });
            }
    
    
            // Calculate counters
            $total = Subscription::count();
            $active = Subscription::where('status', 'active')->count();
            $inactive = Subscription::where('status', 'cancelled')->count();
            $completed = Subscription::where('status', 'completed')->count();
    
            return DataTables::of($subscriptions)
            ->addColumn('last_billing_date', function ($subscription) {
                return $subscription->order && $subscription->order->paid_at
                    ? \Carbon\Carbon::parse($subscription->order->paid_at)->format('Y-F-d')
                    : 'N/A';
            })
            ->addColumn('next_billing_date', function ($subscription) {
                return $subscription->order && $subscription->order->paid_at
                    ? \Carbon\Carbon::parse($subscription->order->paid_at)->addMonth()->format('Y-F-d')
                    : 'N/A';
            })
                ->addColumn('name', function ($subscription) {
                    return $subscription->user->name ?? 'N/A';
                })
                ->addColumn('email', function ($subscription) {
                    return $subscription->user->email ?? 'N/A';
                })
                ->addColumn('amount', function ($subscription) {
                    return $subscription->order && $subscription->order->amount
                        ? $subscription->order->amount
                        : 'N/A';
                })
                ->addColumn('action', function ($subscription) {
                    return '<div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary" onclick="viewOrder(' . $subscription->order->id . ')">View</button>
                            </div>';
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && $request->input('search.value') != '') {
                        $search = $request->input('search.value');
                        $query->where(function ($q) use ($search) {
                            $q->where('subscriptions.id', 'like', "%{$search}%")
                              ->orWhereHas('user', function ($q2) use ($search) {
                                  $q2->where('email', 'like', "%{$search}%")
                                     ->orWhere('name', 'like', "%{$search}%");
                              })
                              ->orWhereHas('order', function ($q3) use ($search) {
                                  $q3->where('amount', 'like', "%{$search}%");
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
    
        return view('admin.subscriptions.subscriptions', ['plans' => []]);
    }
    
    
    public function cancelled_subscriptions(Request $request) //inactive subscriptions
    {
       
        if ($request->ajax()) {
            $subscriptions = Subscription::with(['user', 'plan', 'order'])->where('status',"cancelled")
           ->orderBy('created_at', 'asc');
      // Apply filters (user_name, email, amount, status)
      if ($request->filled('user_name')) {
        $subscriptions->whereHas('user', function ($q) use ($request) {
            $q->where('name', 'like', '%' . $request->input('user_name') . '%');
        });
    }

    if ($request->filled('email')) {
        $subscriptions->whereHas('user', function ($q) use ($request) {
            $q->where('email', 'like', '%' . $request->input('email') . '%');
        });
    }

    if ($request->filled('amount')) {
        $subscriptions->whereHas('order', function ($q) use ($request) {
            $q->where('amount', 'like', '%' . $request->input('amount') . '%');
        });
    }

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
                ->editColumn('cancellation_at', function ($subscription) {  // 👈 ADD THIS
                    return $subscription->cancellation_at 
                        ? \Carbon\Carbon::parse($subscription->cancellation_at)->format('Y-F-d') 
                        : 'N/A';
                })
                ->addColumn('action', function ($subscription) {
                    return '<div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary" onclick="viewOrder(' . $subscription->order->id . ')">View</button>
                            </div>';
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && $request->input('search.value') != '') {
                        $search = $request->input('search.value');
                        $query->where(function ($q) use ($search) {
                            $q->where('subscriptions.id', 'like', "%{$search}%")
                              ->orWhereHas('user', function ($q2) use ($search) {
                                  $q2->where('email', 'like', "%{$search}%");
                              });
                        });
                    }
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && $request->input('search.value') != '') {
                        $search = $request->input('search.value');
                        $query->where(function ($q) use ($search) {
                            $q->where('subscriptions.id', 'like', "%{$search}%")
                              ->orWhere('subscriptions.status', 'like', "%{$search}%")
                              ->orWhereHas('user', function ($q2) use ($search) {
                                  $q2->where('email', 'like', "%{$search}%")
                                     ->orWhere('name', 'like', "%{$search}%");
                              })
                              ->orWhereHas('order', function ($q3) use ($search) {
                                  $q3->where('amount', 'like', "%{$search}%");
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
    
        return view("admin/subscriptions/subscriptions", ['plans' => []]);
    }


   
}
 