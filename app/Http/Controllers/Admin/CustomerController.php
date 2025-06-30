<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;
use DataTables;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class CustomerController extends Controller
{
     public function customerList(Request $request)
     {
        if ($request->ajax()) {
            // Start query builder for listing
            $query = User::query()->where('role_id',3);
            if ($request->filled('user_name')) {
                $query->where('name', 'like', '%' . $request->input('user_name') . '%');
            }

            if ($request->filled('email')) {
                $query->where('email', 'like', '%' . $request->input('email') . '%');
            }

            if ($request->filled('status')) {
                $query->where('status', $request->input('status'));
            }
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
                    return '<i class="ti ti-contract me-2 text-primary"></i>Customer';
                })
                ->addColumn('status', function ($row) {
                    $checked = $row->status == 1 ? 'checked' : '';
                    $toggleClass = $row->status == 1 ? 'bg-success' : 'bg-danger';

                    // Check if the authenticated user has the 'Mod' role
                    $disabled =auth()->user()->hasPermissionTo('Mod') ? 'disabled' : '';

                    return '<div class="form-check form-switch">
                        <input class="form-check-input status-toggle ' . $toggleClass . '" type="checkbox" role="switch"
                            data-id="' . $row->id . '" ' . $checked . ' ' . $disabled . '>
                    </div>';
                })
                ->rawColumns(['role', 'status', 'action'])
                ->with([
                    'counters' => [
                        'total' => User::where('role_id', 3)->count(),
                        'active' => User::where('status', 1)->where('role_id', 3)->count(),
                        'inactive' => User::where('status', 0)->where('role_id', 3)->count(),
                    ]
                ])
                ->make(true);
        }
    
        $roles=Role::all();
        return view('admin.customers.customers',["roles"=>$roles]);
      }

    /**
     * Toggle customer status (active/inactive)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleStatus(Request $request)
    {
        try {
            $user = User::findOrFail($request->user_id);
            $user->status = $user->status == 1 ? 0 : 1;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Customer status updated successfully',
                'status' => $user->status,
                'status_text' => $user->status == 1 ? 'Active' : 'Inactive'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating customer status: ' . $e->getMessage()
            ], 500);
        }
    }
}
