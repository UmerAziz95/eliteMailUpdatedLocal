<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\SidebarNavigation;
use Carbon\Carbon;
use DataTables;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserWelcomeMail;
class AdminController extends Controller
{
    public function index(Request $request)
{
    if ($request->ajax()) {
        // Start query builder with eager-loaded roles
        $query = User::with('roles')->whereIn('role_id', [1, 2, 5]);

        // 🔍 Apply individual column filters
        if ($request->filled('user_name')) {
            $query->where('name', 'like', '%' . $request->input('user_name') . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->input('email') . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Global search
        if ($request->has('search') && $request->input('search.value') != '') {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->addColumn('name', function ($row) {
                return $row->name ?? 'N/A';
            })
            ->addColumn('role', function ($row) {
                $roleName = $row->getRoleNames()->first(); // Get the first assigned role
                return '<i class="ti ti-contract me-2 text-primary"></i>' . ucfirst($roleName ?? 'N/A');
            })
            ->addColumn('status', function ($row) {
                $statusText = $row->status == 1 ? 'active' : 'inactive';
                $statusClass = $row->status == 1 ? 'active_status' : 'inactive_status';
                return '<span class="' . $statusClass . '">' . ucfirst($statusText) . '</span>';
            })
            ->addColumn('action', function ($row) {
                $user = auth()->user();

                if ($user->hasPermissionTo('Mod')) {
                    return '<button class="bg-transparent p-0 border-0 mx-2 edit-btn" data-id="' . $row->id . '">
                            <i class="fa-regular fa-eye"></i>';
                }

                return '
                    <div class="d-flex align-items-center gap-2">
                        <button class="bg-transparent p-0 border-0 delete-btn" data-id="' . $row->id . '">
                            <i class="fa-regular fa-trash-can text-danger"></i>
                        </button>
                        <button class="bg-transparent p-0 border-0 mx-2 edit-btn" data-id="' . $row->id . '">
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

    $roles = Role::all();
    $permissions = Permission::all();
    return view('admin.admins.admins', ['roles' => $roles, 'permissions' => $permissions]);
}

    

    public function dashboard()
    {
        $user=Auth::user();
        if($user->hasPermissionTo('Internal Order Management') && !$user->hasRole('super-admin')){
            return redirect()->route('admin.internal_order_management.index');
        }
        // Get total customers (users with role_id 3)
        $totalCustomers = User::where('role_id', 3)->count();

        // Get total contractors (users with role_id 4)
        $totalContractors = User::where('role_id', 4)->count();

        // Get total inboxes sold (sum of total_inboxes from reorder_infos table where orders are not cancelled/rejected)
        $totalInboxesSold = \App\Models\Order::whereNotIn('status_manage_by_admin', ['cancelled', 'reject', 'pending'])
            ->join('reorder_infos', 'orders.id', '=', 'reorder_infos.order_id')
            ->sum('reorder_infos.total_inboxes');

        // get completed orders total inboxes
        $completedOrdersTotalInboxes = \App\Models\Order::where('status_manage_by_admin', 'completed')
            ->join('reorder_infos', 'orders.id', '=', 'reorder_infos.order_id')
            ->sum('reorder_infos.total_inboxes');

        // Get recent orders with their status from status_manage_by_admin column
        $recentOrders = \App\Models\Order::with(['plan','reorderInfo','user'])->latest()
            ->take(3)
            ->get()
            ->map(function($order) {
                $order->status_color = match($order->status_manage_by_admin) {
                    'pending' => 'warning',
                    'in-progress' => 'info',
                    'completed' => 'success',
                    'cancelled', 'reject' => 'danger',
                    default => 'secondary'
                };
                return $order;
            });
        return view('admin.dashboard.dashboard', compact(
            'totalCustomers',
            'totalContractors', 
            'totalInboxesSold',
            'completedOrdersTotalInboxes',
            'recentOrders'
        ));
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
        $validated = $request->validate([
            'full_name'   => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|min:6|confirmed',
            'status'      => 'required|in:0,1',
            'role_id'     => 'required',
        ]);
    
        // Create the user
        $user = User::create([
            'name'     => $validated['full_name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status'   => (int) $validated['status'],
        ]);
    
        // Log the user creation
        ActivityLogService::log(
            'user_created',
            'A new user was created',
            $user,
            [
                'email' => $user->email,
                'status' => $user->status,
                'role_id' => $validated['role_id'],
                'created_by' => Auth::id(),
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ],
            Auth::id() // Admin or creator performing this action
        );
      try {
            Mail::to($user->email)->queue(new UserWelcomeMail($user));
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email: ' . $e->getMessage());
        }
        // Assign role and permissions if role_id is provided 
        if ($validated['role_id']) {
            $role = Role::find($validated['role_id']);
            if ($role) {
                $user->assignRole($role);
    
                // Log role assignment
                ActivityLogService::log(
                    'role_assigned',
                    'Role assigned to user',
                    $user,
                    [
                        'role_name' => $role->name,
                        'assigned_by' => Auth::id(),
                    ],
                    Auth::id()
                );
    
                return response()->json([
                    'message' => 'User created and role has assigned successfully',
                    'user'    => $user
                ]);
            }
        }
    
        return response()->json([
            'message' => 'User created successfully but failed to assign role',
            'user'    => $user
        ]);
    }
    


    
    public function storeCustomer(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|min:6|confirmed',
            'status'    => 'required|in:0,1',
        ]);
    
        // Create the user
        $user = User::create([
            'name'     => $validated['full_name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status'   => (int) $validated['status'],
        ]);
    
        // Log activity
        ActivityLogService::log(
            'customer_created',
            'A new customer account was created.',
            $user,
            [
                'email' => $user->email,
                'status' => $user->status,
                'created_by' => Auth::id(),
                'ip' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ],
            Auth::id()
        );
    
        return response()->json([
            'message' => 'User created successfully',
            'user'    => $user
        ]);
    }
    
    

    public function edit($id)
    {
        $user = User::findOrFail($id);

        // Include roles
        $user->roles = $user->getRoleNames(); // Returns a collection of role names

        return response()->json($user);
    }

    public function userEdit($id)
    {
        $user = User::findOrFail($id);

        return response()->json($user); // Used to populate the form
    }

   public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'status' => 'required|in:0,1',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->name = $validated['full_name'];
        $user->email = $validated['email'];
        $user->status = $validated['status'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        // Update role to user (remove all and assign new)
        $newRole = Role::findById($validated['role_id']);
        if ($newRole) {
            $user->syncRoles([$newRole->name]); // remove existing and assign new

            ActivityLogService::log(
                'role_updated',
                'User role updated',
                $user,
                [
                    'new_role' => $newRole->name,
                    'assigned_by' => Auth::id()
                ],
                Auth::id()
            );
        }

        return response()->json(['message' => 'User updated successfully']);
    }



    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => 'nullable|min:6|confirmed',
            'status' => 'required|in:0,1',
        ]);

        $user->name = $validated['full_name'];
        $user->email = $validated['email'];
        $user->status = $validated['status'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return response()->json(['message' => 'User updated successfully']);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->status = 0;
        $user->save();
    
        // Log activity
        ActivityLogService::log(
            'user_deactivated',
            'User account was deactivated.',
            $user,
            [
                'email' => $user->email,
                'deactivated_by' => Auth::id(),
                'ip' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ],
            Auth::id()
        );
    
        return response()->json([
            'message' => 'User deactivated successfully.'
        ]);
    }
    
    
}
