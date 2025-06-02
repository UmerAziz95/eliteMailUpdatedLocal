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

                //  <span class="ms-2 ' . ($row->status == 1 ? 'text-success' : 'text-danger') . '">' . ucfirst($statusText) . '</span>
                // ->addColumn('action', function ($row) {
                //     $user = auth()->user();
                
                //     // If the user has 'Mod' permission, hide the action buttons
                //     if ($user->hasPermissionTo('Mod')) {
                //         return ' <button class="bg-transparent p-0 border-0 mx-2 view-btn" data-id="' . $row->id . '">
                //                 <i class="fa-regular fa-eye"></i>'; // or return only view icon if needed
                //     }
                
                //     return '
                //         <div class="d-flex align-items-center gap-2">

                //             <div class="dropdown">
                //                 <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                //                     <i class="fa-solid fa-ellipsis-vertical"></i>
                //                 </button>
                //                 <ul class="dropdown-menu">
                //                     <li><a class="dropdown-item edit-btn" href="#" data-id="' . $row->id . '">View</a></li>
                //                 </ul>
                //             </div>
                //         </div>
                //     ';
                // })
                
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
