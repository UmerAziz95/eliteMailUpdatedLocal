<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;
use DataTables;

class DomainHealthDashboardController extends Controller
{
    //
    public function index(Request $request){  
        return view('admin.domain_health_dashboard.index');
    }

    public function getDomainHealthData(Request $request)
    {
         $order = Order::with([
            'user',
            'reorderInfo',
            'orderPanels.orderPanelSplits',
            'orderPanels.panel'
        ])
        ->where('status_manage_by_admin', 'completed')
        ->whereHas('subscription', function ($query) {
            $query->where('status', 'active');
        })
        ->get();

        $data = []; // Replace with actual data fetching logic
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
}
