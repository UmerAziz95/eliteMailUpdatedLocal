<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Yajra\DataTables\Facades\DataTables;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;
class CustomRolePermissionController extends Controller
{
    //
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Role::with('permissions')
                ->select('roles.*')
                ->latest(); // this makes sure newest entries appear at the top
    
            return DataTables::of($query)
                ->addColumn('permissions', function ($role) {
                    return $role->permissions->pluck('name')->implode(', ');
                })
                ->addColumn('created_at', function ($role) {
                    return $role->created_at ? $role->created_at->format('D_F_Y') : 'N/A';
                })
              ->addColumn('action', function ($role) {
                    $actions = '<div class="d-flex gap-2">';

                    if (!auth()->user()->hasPermissionTo('Mod')) {
                        $actions .= '<button class="btn btn-sm btn-primary" onclick="editRole(' . $role->id . ')">Edit</button>';
                    }

                    $actions .= '</div>';
                    return $actions;
                })

                ->make(true);
        }
    
            $roles = Role::with('users')->get();

            // foreach ($roles as $role) {
            //     echo "Role: " . $role->name . "<br>";
            //     foreach ($role->users as $user) {
            //         dd($user);
            //         echo " - " . $user->name . " (" . $user->email . ")<br>";
            //     }
            // }
        $permissions = Permission::all();
      
        return view('admin.roles.roles', ['roles' => $roles, 'permissions' => $permissions]);
    }
    

    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));
    }

    // public function store(Request $request)
    // {
        
    //     $validated = $request->validate([
    //         'name' => 'required|unique:roles,name',
    //         'permissions' => 'nullable|array',
    //         'permissions.*' => 'exists:permissions,id',
    //     ]);
    
    //     // Create role
    //     $Createdrole = Role::create(['name' => $validated['name']]);
    //     $role=Role::find($Createdrole->id);
    //     if (!empty($validated['permissions'])) {
    //         // Fetch Permission models by ID
        
    //         $permission= Permission::whereIn('id', $request->permissions)->get();

    //         $role->syncPermissions($permission);
    //       //  $role->syncPermissions($permissions); // Now passes Permission objects
        
    //     }
    
    //     return redirect()->route('admin.role.index')->with('success', 'Role created successfully.');
    // }

    public function store(Request $request)
{
    $roleId = $request->input('role_id');

    $rules = [
        'name' => [
            'required',
            Rule::unique('roles', 'name')->ignore($roleId)
        ],
        'permissions' => 'nullable|array',
        'permissions.*' => 'exists:permissions,id',
    ];

    $validated = $request->validate($rules);

    if ($roleId) {
        // Update existing role
        $role = Role::findOrFail($roleId);
        $role->name = $validated['name'];
        $role->save();
    } else {
        // Create new role
        $role = Role::create(['name' => $validated['name']]);
    }

    // Sync permissions
    $permissions = Permission::whereIn('id', $request->permissions ?? [])->get();
    $role->syncPermissions($permissions);

    return redirect()->route('admin.role.index')->with('success', $roleId ? 'Role updated successfully.' : 'Role created successfully.');
}
    
    public function getRole($id)
{
    $role = Role::with('permissions')->findOrFail($id);

    return response()->json([
        'success' => true,
        'role' => $role,
        'permissions' => $role->permissions->pluck('name'),
    ]);
}
    

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return redirect()->route('roles.index')->with('success', 'Role deleted successfully.');
    }

    public function assign(Request $request){
        // Step 1: Get the role
        $role = Role::find(1); // admin

        // Step 2: Get the permissions by IDs
        $permissions = Permission::whereIn('id', [1,2,3,4,5,6,7,8,9])->get();

        // Step 3: Assign permissions to role to user
        $role->syncPermissions($permissions);

        // Step 4: Assign role to user
        $user = User::find(1);
        $user->assignRole($role);
        return "done";
    }

}
