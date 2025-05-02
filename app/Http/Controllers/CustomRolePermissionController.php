<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;
use App\Models\User;
class CustomRolePermissionController extends Controller
{
    //
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Role::with('permissions')->select('roles.*');
    
            return DataTables::of($query)
                ->addColumn('permissions', function ($role) {
                    return $role->permissions->pluck('name')->implode(', ');
                })
                // ->addColumn('action', function ($role) {
                //     return view('admin.roles.partials.actions', compact('role'))->render();
                // })
                //->rawColumns(['action'])
                ->make(true);
        }
    
        return view('admin.roles.roles');
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $request->name]);
        if ($request->has('permissions')) {
            $role->givePermissionTo($request->permissions);
        }

        return redirect()->route('roles.index')->with('success', 'Role created successfully.');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
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
        $permissions = Permission::whereIn('id', [3, 4, 5])->get();

        // Step 3: Assign permissions to role
        $role->syncPermissions($permissions);

        // Step 4: Assign role to user
        $user = User::find(1);
        $user->assignRole($role);
        return "done";
    }
}
