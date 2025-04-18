<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Plan;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $plans = Plan::all();
        return view('customer.orders.orders', compact('plans'));
    }

    public function reorder()
    {
        return view('customer.orders.reorder');
    }

    public function view()
    {
        return view('customer.orders.order-view');
    }

    public function getOrders(Request $request)
    {
        Log::info('Orders data request received', [
            'plan_id' => $request->plan_id,
            'request_data' => $request->all()
        ]);
        
        try {
            $orders = Order::with('user')
                ->select('orders.*');
            
            if ($request->has('plan_id') && $request->plan_id != '') {
                $orders->where('plan_id', $request->plan_id);
            }

            Log::debug('Orders query', [
                'sql' => $orders->toSql(),
                'bindings' => $orders->getBindings()
            ]);
            
            return DataTables::of($orders)
                ->addColumn('action', function($order) {
                    return '<button class="btn btn-sm btn-primary" onclick="viewOrder('.$order->id.')">View</button>';
                })
                ->editColumn('created_at', function($order) {
                    return $order->created_at ? $order->created_at->format('Y-m-d H:i:s') : '';
                })
                ->editColumn('status', function($order) {
                    return ucfirst($order->status);
                })
                ->addColumn('domain_forwarding_url', function($order) {
                    return $order->user->domain_forwarding_url;
                })
                ->addColumn('email', function($order) {
                    return $order->user->email;
                })
                ->rawColumns(['action'])
                ->make(true);
        } catch (Exception $e) {
            Log::error('Error in getOrders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => true,
                'message' => 'Error loading orders: ' . $e->getMessage()
            ], 500);
        }
    }
}
