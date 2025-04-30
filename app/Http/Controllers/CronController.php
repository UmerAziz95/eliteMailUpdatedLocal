<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;

class CronController extends Controller
{
    public function updateOrderStatus(){
        $orders=Order::where('status_manage_by_admin','Pending')->get();

    }
}
