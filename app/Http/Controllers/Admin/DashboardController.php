<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Subscription;
use App\Models\Order;
use App\Models\SupportTicket;

class DashboardController extends Controller
{
    //
    public function getSubscriptionStats(Request $request)
    {
        try {
            $period = $request->query('type', 'day'); // 'day', 'week', or 'month'
            $month = $request->query('month', date('m')); // Numeric month (01-12)
            $year = $request->query('year', date('Y')); // Default to current year
            
            $data = [];
            $now = Carbon::now();
            $categories = [];
            $series = [];
            $total = 0;
            $growth = 0;
            // 
            switch ($period) {
            case 'month':
                // Ensure month is properly formatted as numeric (01-12)
                $monthNumber = (int)$month;
                if ($monthNumber < 1 || $monthNumber > 12) {
                    $monthNumber = (int)date('m'); // Fallback to current month if invalid
                }
                    
                $start = Carbon::create($year, $monthNumber, 1)->startOfDay();
                $end = $start->copy()->endOfMonth()->endOfDay();
                
                // For categories (x-axis)
                $daysInMonth = $start->daysInMonth;
                for ($i = 1; $i <= $daysInMonth; $i++) {
                    $categories[] = (string)$i; // Day numbers as strings
                }

                // Get subscription data
                $data = Subscription::whereBetween('created_at', [$start, $end])
                    ->selectRaw('DAY(created_at) as day, COUNT(*) as count')
                    ->groupBy('day')
                    ->pluck('count', 'day')
                    ->toArray();

                // Format series data
                for ($i = 1; $i <= $daysInMonth; $i++) {
                    $series[] = $data[$i] ?? 0;
                }
                
                // Calculate total and growth
                $total = array_sum($series);
                
                // Get previous month's total for comparison
                $prevMonthStart = $start->copy()->subMonth()->startOfMonth();
                $prevMonthEnd = $start->copy()->subMonth()->endOfMonth();
                $prevTotal = Subscription::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->count();
                
                // Calculate growth percentage
                if ($prevTotal > 0) {
                    $growth = round((($total - $prevTotal) / $prevTotal) * 100, 1);
                } else {
                    $growth = $total > 0 ? 100 : 0;
                }
                break;

            case 'week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                
                // For categories (x-axis)
                for ($i = 0; $i < 7; $i++) {
                    $day = $start->copy()->addDays($i);
                    $categories[] = $day->format('D'); // Mon, Tue, etc.
                }

                // Get subscription data grouped by weekday (MySQL returns 1=Sunday, 2=Monday, etc.)
                $data = Subscription::whereBetween('created_at', [$start, $end])
                    ->selectRaw('DAYOFWEEK(created_at) as day, COUNT(*) as count')
                    ->groupBy('day')
                    ->pluck('count', 'day')
                    ->toArray();

                // Convert from MySQL DAYOFWEEK to our array (0=Monday, 1=Tuesday, etc.)
                $seriesData = [];
                for ($i = 0; $i < 7; $i++) {
                    // Convert our index (0=Monday) to MySQL's DAYOFWEEK (2=Monday)
                    $dayOfWeek = $i + 2;
                    if ($dayOfWeek > 7) $dayOfWeek = 1; // Wrap around for Sunday
                    $seriesData[] = $data[$dayOfWeek] ?? 0;
                }
                $series = $seriesData;
                
                // Calculate total and growth
                $total = array_sum($series);
                
                // Get previous week's total for comparison
                $prevWeekStart = $start->copy()->subWeek();
                $prevWeekEnd = $end->copy()->subWeek();
                $prevTotal = Subscription::whereBetween('created_at', [$prevWeekStart, $prevWeekEnd])->count();
                
                // Calculate growth percentage
                if ($prevTotal > 0) {
                    $growth = round((($total - $prevTotal) / $prevTotal) * 100, 1);
                } else {
                    $growth = $total > 0 ? 100 : 0;
                }
                break;

            case 'day':
            default:
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                
                // For categories (x-axis) - hours of the day
                for ($i = 0; $i < 24; $i++) {
                    $categories[] = sprintf('%02d', $i); // 00, 01, 02, etc.
                }

                // Get subscription data
                $data = Subscription::whereBetween('created_at', [$start, $end])
                    ->selectRaw('HOUR(created_at) as hour, COUNT(*) as count')
                    ->groupBy('hour')
                    ->pluck('count', 'hour')
                    ->toArray();

                // Format series data
                $seriesData = [];
                for ($i = 0; $i < 24; $i++) {
                    $seriesData[] = $data[$i] ?? 0;
                }
                $series = $seriesData;
                
                // Calculate total and growth
                $total = array_sum($series);
                
                // Get previous day's total for comparison
                $prevDayStart = $start->copy()->subDay();
                $prevDayEnd = $end->copy()->subDay();
                $prevTotal = Subscription::whereBetween('created_at', [$prevDayStart, $prevDayEnd])->count();
                
                // Calculate growth percentage
                if ($prevTotal > 0) {
                    $growth = round((($total - $prevTotal) / $prevTotal) * 100, 1);
                } else {
                    $growth = $total > 0 ? 100 : 0;
                }
                break;
        }

        return response()->json([
            'series' => $series,
            'categories' => $categories,
            'total' => $total,
            'growth' => $growth
        ]);
    }
    catch (\Exception $e) {
        \Log::error('Error in getSubscriptionStats: ' . $e->getMessage());
        return response()->json([
            'series' => [],
            'categories' => [],
            'total' => 0,
            'growth' => 0,
            'error' => 'An error occurred while fetching subscription data'
        ], 500);
    }
}

    public function getRevenueStats(Request $request)
    {
        try {
            $period = $request->query('type', 'day'); // 'day', 'week', or 'month'
            $month = $request->query('month', date('m')); // Numeric month (01-12)
            $year = $request->query('year', date('Y')); // Default to current year
            
            $data = [];
            $now = Carbon::now();
            $categories = [];
            $series = [];
            $total = 0;
            $growth = 0;
            
            switch ($period) {
                case 'month':
                    // Ensure month is properly formatted as numeric (01-12)
                    $monthNumber = (int)$month;
                    if ($monthNumber < 1 || $monthNumber > 12) {
                        $monthNumber = (int)date('m'); // Fallback to current month if invalid
                    }
                        
                    $start = Carbon::create($year, $monthNumber, 1)->startOfDay();
                    $end = $start->copy()->endOfMonth()->endOfDay();
                    
                    // For categories (x-axis)
                    $daysInMonth = $start->daysInMonth;
                    for ($i = 1; $i <= $daysInMonth; $i++) {
                        $categories[] = (string)$i; // Day numbers as strings
                    }

                    // Get revenue data
                    $data = Order::whereBetween('created_at', [$start, $end])
                        ->selectRaw('DAY(created_at) as day, SUM(amount) as total')
                        ->groupBy('day')
                        ->pluck('total', 'day')
                        ->toArray();

                    // Format series data
                    for ($i = 1; $i <= $daysInMonth; $i++) {
                        $series[] = $data[$i] ?? 0;
                    }
                    
                    // Calculate total and growth
                    $total = array_sum($series);
                    
                    // Get previous month's total for comparison
                    $prevMonthStart = $start->copy()->subMonth()->startOfMonth();
                    $prevMonthEnd = $start->copy()->subMonth()->endOfMonth();
                    $prevTotal = Order::whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])->sum('amount');
                    
                    // Calculate growth percentage
                    if ($prevTotal > 0) {
                        $growth = round((($total - $prevTotal) / $prevTotal) * 100, 1);
                    } else {
                        $growth = $total > 0 ? 100 : 0;
                    }
                    break;

                case 'week':
                    $start = $now->copy()->startOfWeek();
                    $end = $now->copy()->endOfWeek();
                    
                    // For categories (x-axis)
                    for ($i = 0; $i < 7; $i++) {
                        $day = $start->copy()->addDays($i);
                        $categories[] = $day->format('D'); // Mon, Tue, etc.
                    }

                    // Get revenue data grouped by weekday (MySQL returns 1=Sunday, 2=Monday, etc.)
                    $data = Order::whereBetween('created_at', [$start, $end])
                        ->selectRaw('DAYOFWEEK(created_at) as day, SUM(amount) as total')
                        ->groupBy('day')
                        ->pluck('total', 'day')
                        ->toArray();

                    // Convert from MySQL DAYOFWEEK to our array (0=Monday, 1=Tuesday, etc.)
                    $seriesData = [];
                    for ($i = 0; $i < 7; $i++) {
                        // Convert our index (0=Monday) to MySQL's DAYOFWEEK (2=Monday)
                        $dayOfWeek = $i + 2;
                        if ($dayOfWeek > 7) $dayOfWeek = 1; // Wrap around for Sunday
                        $seriesData[] = $data[$dayOfWeek] ?? 0;
                    }
                    $series = $seriesData;
                    
                    // Calculate total and growth
                    $total = array_sum($series);
                    
                    // Get previous week's total for comparison
                    $prevWeekStart = $start->copy()->subWeek();
                    $prevWeekEnd = $end->copy()->subWeek();
                    $prevTotal = Order::whereBetween('created_at', [$prevWeekStart, $prevWeekEnd])->sum('amount');
                    
                    // Calculate growth percentage
                    if ($prevTotal > 0) {
                        $growth = round((($total - $prevTotal) / $prevTotal) * 100, 1);
                    } else {
                        $growth = $total > 0 ? 100 : 0;
                    }
                    break;

                case 'day':
                default:
                    $start = $now->copy()->startOfDay();
                    $end = $now->copy()->endOfDay();
                    
                    // For categories (x-axis) - hours of the day
                    for ($i = 0; $i < 24; $i++) {
                        $categories[] = sprintf('%02d', $i); // 00, 01, 02, etc.
                    }

                    // Get revenue data
                    $data = Order::whereBetween('created_at', [$start, $end])
                        ->selectRaw('HOUR(created_at) as hour, SUM(amount) as total')
                        ->groupBy('hour')
                        ->pluck('total', 'hour')
                        ->toArray();

                    // Format series data
                    $seriesData = [];
                    for ($i = 0; $i < 24; $i++) {
                        $seriesData[] = $data[$i] ?? 0;
                    }
                    $series = $seriesData;
                    
                    // Calculate total and growth
                    $total = array_sum($series);
                    
                    // Get previous day's total for comparison
                    $prevDayStart = $start->copy()->subDay();
                    $prevDayEnd = $end->copy()->subDay();
                    $prevTotal = Order::whereBetween('created_at', [$prevDayStart, $prevDayEnd])->sum('amount');
                    
                    // Calculate growth percentage
                    if ($prevTotal > 0) {
                        $growth = round((($total - $prevTotal) / $prevTotal) * 100, 1);
                    } else {
                        $growth = $total > 0 ? 100 : 0;
                    }
                    break;
            }

            return response()->json([
                'series' => $series,
                'categories' => $categories,
                'total' => $total,
                'growth' => $growth
            ]);
        }
        catch (\Exception $e) {
            \Log::error('Error in getRevenueStats: ' . $e->getMessage());
            return response()->json([
                'series' => [],
                'categories' => [],
                'total' => 0,
                'growth' => 0,
                'error' => 'An error occurred while fetching revenue data'
            ], 500);
        }
    }
    
    public function getRevenueTotals()
    {
        try {
            $now = Carbon::now();
            
            // Get daily total (today)
            $dayStart = $now->copy()->startOfDay();
            $dayEnd = $now->copy()->endOfDay();
            $dayTotal = Order::whereBetween('created_at', [$dayStart, $dayEnd])->sum('amount');
            
            // Get weekly total (current week)
            $weekStart = $now->copy()->startOfWeek();
            $weekEnd = $now->copy()->endOfWeek();
            $weekTotal = Order::whereBetween('created_at', [$weekStart, $weekEnd])->sum('amount');
            
            // Get monthly total (current month)
            $monthStart = $now->copy()->startOfMonth();
            $monthEnd = $now->copy()->endOfMonth();
            $monthTotal = Order::whereBetween('created_at', [$monthStart, $monthEnd])->sum('amount');
            
            return response()->json([
                'day' => $dayTotal,
                'week' => $weekTotal,
                'month' => $monthTotal
            ]);
        }
        catch (\Exception $e) {
            \Log::error('Error in getRevenueTotals: ' . $e->getMessage());
            return response()->json([
                'day' => 0,
                'week' => 0,
                'month' => 0,
                'error' => 'An error occurred while fetching revenue totals'
            ], 500);
        }
    }
    
    public function getTicketStats(Request $request)
    {
        try {
            $period = $request->query('period', 'month'); // 'today', 'week', or 'month'
            // dd($period);
            $monthNo = $request->query('month', date('m')); // Numeric month (01-12)
            $now = Carbon::now();
            
            // Initialize stats
            $openTickets = 0;
            $inProgressTickets = 0;
            $closedTickets = 0;
            $total = 0;
            
            switch ($period) {
                case 'today':
                    $start = $now->copy()->startOfDay();
                    $end = $now->copy()->endOfDay();
                    break;
                
                case 'week':
                    $start = $now->copy()->startOfWeek();
                    $end = $now->copy()->endOfWeek();
                    break;
                
                case 'month':
                    $MonthNumber = (int)$monthNo;
                    if ($MonthNumber < 1 || $MonthNumber > 12) {
                        $MonthNumber = (int)date('m'); // Fallback to current month if invalid
                    }
                    $start = Carbon::create($now->year, $MonthNumber, 1)->startOfDay();
                    $end = Carbon::create($now->year, $MonthNumber, 1)->endOfMonth()->endOfDay();
                    // dd($start, $end);
                    break;
                default:
                    $start = $now->copy()->startOfMonth();
                    $end = $now->copy()->endOfMonth();
                    break;
            }
            // dd($start, $end);
            // Get tickets by status within the date range
            $openTickets = SupportTicket::whereBetween('created_at', [$start, $end])
                ->where(function($query) {
                    $query->where('status', 'new')
                          ->orWhere('status', 'open');
                })
                ->count();
                
            $inProgressTickets = SupportTicket::whereBetween('created_at', [$start, $end])
                ->where(function($query) {
                    $query->where('status', 'in_progress')
                          ->orWhere('status', 'pending');
                })
                ->count();
                
            $closedTickets = SupportTicket::whereBetween('created_at', [$start, $end])
                ->where(function($query) {
                    $query->where('status', 'resolved')
                          ->orWhere('status', 'closed')
                          ->orWhere('status', 'completed');
                })
                ->count();
            
            // Calculate total
            $total = $openTickets + $inProgressTickets + $closedTickets;
            
            return response()->json([
                'open' => $openTickets,
                'inProgress' => $inProgressTickets,
                'closed' => $closedTickets,
                'total' => $total,
                'period' => $period
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getTicketStats: ' . $e->getMessage());
            return response()->json([
                'open' => 0,
                'inProgress' => 0,
                'closed' => 0,
                'total' => 0,
                'error' => 'An error occurred while fetching ticket data'
            ], 500);
        }
    }
}
