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
            
            if ($request->filter_name) {
                $subscriptions->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->filter_name . '%');
                });
            }
            
            if ($request->filter_email) {
                $subscriptions->whereHas('user', function($q) use ($request) {
                    $q->where('email', 'like', '%' . $request->filter_email . '%');
                });
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
                ->addColumn('name', function ($subscription) {
                    return $subscription->user->name ?? 'N/A';
                })
                // status
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
                ->addColumn('email', function ($subscription) {
                    return $subscription->user->email ?? 'N/A';
                })
                ->addColumn('created_at', function ($subscription) {
                    return $subscription->created_at ? $subscription->created_at->format('d F, Y') : 'N/A';
                })
                ->addColumn('amount', function ($subscription) {
                    return $subscription->order && $subscription->order->amount
                        ? $subscription->order->amount
                        : 'N/A'; 
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
                ->orderColumn('name', function($query, $direction) {
                    $query->whereHas('user', function($q) use ($direction) {
                        $q->orderBy('name', $direction);
                    });
                })
                ->orderColumn('email', function($query, $direction) {
                    $query->whereHas('user', function($q) use ($direction) {
                        $q->orderBy('email', $direction);
                    });
                })
                ->orderColumn('amount', function($query, $direction) {
                    $query->whereHas('order', function($q) use ($direction) {
                        $q->orderBy('amount', $direction);
                    });
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && $request->input('search.value') != '') {
                        $search = $request->input('search.value');
                        $query->where(function ($q) use ($search) {
                            $q->where('id', 'like', "%{$search}%")
                              ->orWhere('status', 'like', "%{$search}%")
                              ->orWhereHas('user', function ($q2) use ($search) {
                                  $q2->where('email', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                              });
                        });
                    }
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
    
        return view("customer.subscriptions.subscriptions", ['plans' => []]);
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
            
            if ($request->filter_name) {
                $subscriptions->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->filter_name . '%');
                });
            }
            
            if ($request->filter_email) {
                $subscriptions->whereHas('user', function($q) use ($request) {
                    $q->where('email', 'like', '%' . $request->filter_email . '%');
                });
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
                ->addColumn('name', function ($subscription) {
                    return $subscription->user->name ?? 'N/A';
                })
                ->addColumn('email', function ($subscription) {
                    return $subscription->user->email ?? 'N/A';
                })
                ->addColumn('created_at', function ($subscription) {
                    return $subscription->created_at ? $subscription->created_at->format('d F, Y') : 'N/A';
                })
                ->addColumn('amount', function ($subscription) {
                    return $subscription->order && $subscription->order->amount
                        ? $subscription->order->amount
                        : 'N/A'; 
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
                ->orderColumn('name', function($query, $direction) {
                    $query->whereHas('user', function($q) use ($direction) {
                        $q->orderBy('name', $direction);
                    });
                })
                ->orderColumn('email', function($query, $direction) {
                    $query->whereHas('user', function($q) use ($direction) {
                        $q->orderBy('email', $direction);
                    });
                })
                ->orderColumn('amount', function($query, $direction) {
                    $query->whereHas('order', function($q) use ($direction) {
                        $q->orderBy('amount', $direction);
                    });
                })
                // status
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
                ->orderColumn('status', function($query, $direction) {
                    $query->orderBy('status', $direction);
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && $request->input('search.value') != '') {
                        $search = $request->input('search.value');
                        $query->where(function ($q) use ($search) {
                            $q->where('id', 'like', "%{$search}%")
                              ->orWhere('status', 'like', "%{$search}%")
                              ->orWhereHas('user', function ($q2) use ($search) {
                                  $q2->where('email', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                              });
                        });
                    }
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
}
