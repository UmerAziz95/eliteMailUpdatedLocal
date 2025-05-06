<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MediaHandlerController extends Controller
{
    //
    public function ticket_conversation(Request $request){
        return view('admin.support.ticket_conversation');
    }
} 
