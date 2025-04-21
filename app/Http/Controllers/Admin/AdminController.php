<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;
use DataTables;

class AdminController extends Controller
{
    public function index(Request $request)
{
    if ($request->ajax()) {
        // Start query builder
        $query = User::query()->whereIn('role_id', [1, 2, 5]);

        // Only apply search to the query builder — NOT to a Collection
        if ($request->has('search') && $request->input('search.value') != '') {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // DO NOT call ->get()
        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('name', function ($row) {
                return $row->name ?? 'N/A';
            })
            ->addColumn('role', function ($row) {
                return '<i class="ti ti-contract me-2 text-primary"></i>Admin';
            })
            ->addColumn('status', function ($row) {
                $statusText = $row->status == 1 ? 'active' : 'inactive';
                $statusClass = $row->status == 1 ? 'active_status' : 'inactive_status';
                return '<span class="' . $statusClass . '">' . ucfirst($statusText) . '</span>';
            })
            ->addColumn('action', function ($row) {
                return '
                    <div class="d-flex align-items-center gap-2">
                        <button class="bg-transparent p-0 border-0 delete-btn" data-id="' . $row->id . '">
                            <i class="fa-regular fa-trash-can text-danger"></i>
                        </button>
                        <button class="bg-transparent p-0 border-0 mx-2 view-btn" data-id="' . $row->id . '">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                        <div class="dropdown">
                            <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item edit-btn" href="#" data-id="' . $row->id . '">Edit</a></li>
                            </ul>
                        </div>
                    </div>
                ';
            })
            ->rawColumns(['role', 'status', 'action'])
            ->with([
                'counters' => [
                    'total' => User::whereIn('role_id', [1, 2, 5])->count(),
                    'active' => User::where('status', 1)->whereIn('role_id', [1, 2, 5])->count(),
                    'inactive' => User::where('status', 0)->whereIn('role_id', [1, 2, 5])->count(),
                ]
            ])
            ->make(true);
    }

    return view('admin.admins.admins');
}

    

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


    public function store(Request $request)
    {
        // dd($request->all());
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'status' => 'required|in:0,1',
        ]);
    
        $user = User::create([
            'name' => $validated['full_name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
        ]);
    
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ]);
    }
    
}
