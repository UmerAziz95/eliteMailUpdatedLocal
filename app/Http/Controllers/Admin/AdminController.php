<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.dashboard.dashboard');
    }

    public function profile()
    {
        return view('admin.profile.profile');
    }

    public function settings()
    {
        return view('admin.settings.settings');
    }

}
