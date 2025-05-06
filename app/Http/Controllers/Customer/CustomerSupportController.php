<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CustomerSupportController extends Controller
{

    public function index(Request $request){
        return view('customer.support.ticket_conversation');
    }
}
