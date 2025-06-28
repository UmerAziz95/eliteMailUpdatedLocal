<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subscription;
use DataTables;

class SubscriptionController extends Controller
{
    private function calculateNextBillingDate($startTimestamp, $billingPeriod, $billingPeriodUnit)
    {
        $startDate = \Carbon\Carbon::createFromTimestamp($startTimestamp);
        $currentDate = \Carbon\Carbon::now();
        
        $diffUnit = match($billingPeriodUnit) {
            'month' => $startDate->diffInMonths($currentDate),
            'year' => $startDate->diffInYears($currentDate),
            'week' => $startDate->diffInWeeks($currentDate),
            'day' => $startDate->diffInDays($currentDate),
            default => 0
        };
        
        $completePeriods = floor($diffUnit / $billingPeriod);
        $totalPeriods = $completePeriods + 1;
        
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
        
        if ($timestamp instanceof \Carbon\Carbon) {
            return $timestamp->format('F d, Y');
        }
        
        if (is_string($timestamp) && strtotime($timestamp) !== false) {
            return \Carbon\Carbon::parse($timestamp)->format('F d, Y');
        }
        
        if (is_numeric($timestamp)) {
            return \Carbon\Carbon::createFromTimestamp($timestamp)->format('F d, Y');
        }
        
        return 'N/A';
    }

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
                    return $this->formatTimestampToReadable($subscription->created_at);
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
                    // First check if the field exists directly in the subscription
                    if ($subscription->last_billing_date) {
                        return $this->formatTimestampToReadable($subscription->last_billing_date);
                    }
                    if (!$subscription->start_date) {
                        return 'N/A';
                    }

                    $meta = json_decode($subscription->meta, true);
                    $subscriptionData = $meta['subscription'] ?? [];
                    $billingPeriod = $subscriptionData['billing_period'] ?? 1;
                    $billingPeriodUnit = $subscriptionData['billing_period_unit'] ?? 'month';

                    $startTimestamp = \Carbon\Carbon::parse($subscription->start_date)->timestamp;
                    $currentDate = \Carbon\Carbon::now();
                    $startDate = \Carbon\Carbon::createFromTimestamp($startTimestamp);
                    
                    $diffUnit = match($billingPeriodUnit) {
                        'month' => $startDate->diffInMonths($currentDate),
                        'year' => $startDate->diffInYears($currentDate),
                        'week' => $startDate->diffInWeeks($currentDate),
                        'day' => $startDate->diffInDays($currentDate),
                        default => 0
                    };
                    
                    $completePeriods = floor($diffUnit / $billingPeriod);
                    
                    $lastBillingDate = match($billingPeriodUnit) {
                        'month' => $startDate->copy()->addMonths($completePeriods * $billingPeriod),
                        'year' => $startDate->copy()->addYears($completePeriods * $billingPeriod),
                        'week' => $startDate->copy()->addWeeks($completePeriods * $billingPeriod),
                        'day' => $startDate->copy()->addDays($completePeriods * $billingPeriod),
                        default => $startDate
                    };

                    return $this->formatTimestampToReadable($lastBillingDate);
                })
                ->addColumn('next_billing', function ($subscription) {
                    // First check if the field exists directly in the subscription
                    if ($subscription->next_billing_date) {
                        return $this->formatTimestampToReadable($subscription->next_billing_date);
                    }
                    if (!$subscription->start_date || $subscription->status !== 'active') {
                        return 'N/A';
                    }

                    $meta = json_decode($subscription->meta, true);
                    $subscriptionData = $meta['subscription'] ?? [];
                    $billingPeriod = $subscriptionData['billing_period'] ?? 1;
                    $billingPeriodUnit = $subscriptionData['billing_period_unit'] ?? 'month';

                    $nextBillingDate = $this->calculateNextBillingDate(
                        \Carbon\Carbon::parse($subscription->start_date)->timestamp,
                        $billingPeriod,
                        $billingPeriodUnit
                    );

                    return $this->formatTimestampToReadable($nextBillingDate);
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
                                <button class="p-0 bg-transparent border-0"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        aria-expanded="false">

                                    <div class="actions d-flex align-items-center justify-content-between position-relative">
                                        <div class="board d-flex justify-content-start ps-2" style="background-color: var(--secondary-color); height: 18px;">
                                            <span class="text-white">Click</span>
                                        </div>

                                        <div class="action-icon"
                                            style="position: absolute; left: 0; top: -1px; z-index: 2; background-color: orange; height: 20px; width: 20px; border-radius: 50px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fa-solid fa-chevron-right text-dark font-bold"></i>
                                        </div>

                                    </div>
                                </button>
                                
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="CancelSubscription(\'' . $subscription->chargebee_subscription_id . '\')"><i class="fa-solid fa-ban"></i> &nbsp;Cancel Subscription</a></li>
                                    <li><a class="dropdown-item" href="' . route('customer.orders.view', $subscription->order_id ) . '"><i class="fa-solid fa-eye"></i> &nbsp;View Order</a></li>
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
                    return $this->formatTimestampToReadable($subscription->created_at);
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
                    // First check if the field exists directly in the subscription
                    if ($subscription->last_billing_date) {
                        return $this->formatTimestampToReadable($subscription->last_billing_date);
                    }
                    
                    // Fall back to calculation if field doesn't exist
                    if (!$subscription->start_date) {
                        return 'N/A';
                    }

                    $meta = json_decode($subscription->meta, true);
                    $subscriptionData = $meta['subscription'] ?? [];
                    $billingPeriod = $subscriptionData['billing_period'] ?? 1;
                    $billingPeriodUnit = $subscriptionData['billing_period_unit'] ?? 'month';

                    $startTimestamp = \Carbon\Carbon::parse($subscription->start_date)->timestamp;
                    $currentDate = \Carbon\Carbon::now();
                    $startDate = \Carbon\Carbon::createFromTimestamp($startTimestamp);
                    
                    $diffUnit = match($billingPeriodUnit) {
                        'month' => $startDate->diffInMonths($currentDate),
                        'year' => $startDate->diffInYears($currentDate),
                        'week' => $startDate->diffInWeeks($currentDate),
                        'day' => $startDate->diffInDays($currentDate),
                        default => 0
                    };
                    
                    $completePeriods = floor($diffUnit / $billingPeriod);
                    
                    $lastBillingDate = match($billingPeriodUnit) {
                        'month' => $startDate->copy()->addMonths($completePeriods * $billingPeriod),
                        'year' => $startDate->copy()->addYears($completePeriods * $billingPeriod),
                        'week' => $startDate->copy()->addWeeks($completePeriods * $billingPeriod),
                        'day' => $startDate->copy()->addDays($completePeriods * $billingPeriod),
                        default => $startDate
                    };

                    return $this->formatTimestampToReadable($lastBillingDate);
                })
                ->addColumn('next_billing', function ($subscription) {
                    // First check if the field exists directly in the subscription
                    if ($subscription->next_billing_date) {
                        return $this->formatTimestampToReadable($subscription->next_billing_date);
                    }
                    
                    if (!$subscription->start_date || $subscription->status !== 'active') {
                        return 'N/A';
                    }

                    $meta = json_decode($subscription->meta, true);
                    $subscriptionData = $meta['subscription'] ?? [];
                    $billingPeriod = $subscriptionData['billing_period'] ?? 1;
                    $billingPeriodUnit = $subscriptionData['billing_period_unit'] ?? 'month';

                    $nextBillingDate = $this->calculateNextBillingDate(
                        \Carbon\Carbon::parse($subscription->start_date)->timestamp,
                        $billingPeriod,
                        $billingPeriodUnit
                    );

                    return $this->formatTimestampToReadable($nextBillingDate);
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
