<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Carbon\Carbon;
use DataTables;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogService;
class ContractorController extends Controller
{
    //
    public function index(Request $request)
    {
        if ($request->ajax()) {
           // Start query builder
           $query = User::query()->where('role_id',4);
           if ($request->filled('user_name')) {
            $query->where('name', 'like', '%' . $request->input('user_name') . '%');
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->input('email') . '%');
        }

        if ($request->filled('status')) {
            $statusValue = $request->input('status');
            // If status filter is 0 (inactive), query for -1
            if ($statusValue == '0') {
                $query->where('status', -1);
            } else {
                $query->where('status', $statusValue);
            }
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
                   return '<i class="ti ti-contract me-2 text-primary"></i>Contractor';
               })
               ->addColumn('status', function ($row) {
                   // Treat status -1 as inactive, 1 as active
                   $statusText = $row->status == 1 ? 'active' : 'inactive';
                   $statusClass = $row->status == 1 ? 'active_status' : 'inactive_status';
                   return '<span class="' . $statusClass . '">' . ucfirst($statusText) . '</span>';
               })
               ->addColumn('action', function ($row) {
                $user = auth()->user();
            
                // If the user has 'Mod' permission, restrict action buttons
                if ($user->hasPermissionTo('Mod')) {
                    return ' <button class="bg-transparent p-0 border-0 mx-2 edit-btn" data-id="' . $row->id . '">
                            <i class="fa-regular fa-eye"></i>'; // Or return only specific buttons, like 'view'
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
                                <li><a class="dropdown-item convert-to-subadmin" href="#" data-id="' . $row->id . '">Convert to Sub Admin</a></li>
                            </ul>
                        </div>
                    </div>
                ';
            })
            
               ->rawColumns(['role', 'status', 'action'])
               ->with([
                   'counters' => [
                       'total' => User::where('role_id', 4)->count(),
                       'active' => User::where('status', 1)->where('role_id', 4)->count(),
                       'inactive' => User::where('status', -1)->where('role_id', 4)->count(),
                   ]
               ])
               ->make(true);
       }
   
       return view('admin.contractor.contractor');
     }

    

     public function store(Request $request)
     {
         $validated = $request->validate([
             'full_name' => 'required|string|max:255',
             'email' => 'required|email|unique:users,email',
             'password' => 'required|min:6|confirmed',
             'status' => 'required|in:0,1',
         ]);
     
         // Convert status 0 (inactive) to -1
         $status = (int) $validated['status'];
         if ($status === 0) {
             $status = -1;
         }
     
         $user = User::create([
             'name'     => $validated['full_name'],
             'email'    => $validated['email'],
             'password' => Hash::make($validated['password']),
             'status'   => $status,
             'role_id'  => 4,
         ]);
     
         // Log the user creation
         ActivityLogService::log(
             'user_created',
             'A new user was created.',
             $user,
             [
                 'created_by' => Auth::id(),
                 'user_email' => $user->email,
                 'status' => $user->status,
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
       
       // Convert status -1 back to 0 for the form
       $userData = $user->toArray();
       if ($user->status == -1) {
           $userData['status'] = 0;
       }

       return response()->json($userData); // Used to populate the form
   }

   public function update(Request $request, $id)
   {
       $user = User::findOrFail($id);
   
       $validated = $request->validate([
           'full_name' => 'required|string|max:255',
           'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
           'password' => 'nullable|min:6|confirmed',
           'status' => 'required|in:0,1',
       ]);
   
       $oldData = $user->only(['name', 'email', 'status']);
   
       $user->name = $validated['full_name'];
       $user->email = $validated['email'];
       
       // Convert status 0 (inactive) to -1
       $status = (int) $validated['status'];
       if ($status === 0) {
           $status = -1;
       }
       $user->status = $status;
   
       if (!empty($validated['password'])) {
           $user->password = Hash::make($validated['password']);
       }
   
       $user->save();
   
       // Log the update action
       ActivityLogService::log(
           'user_updated',
           'User details were updated.',
           $user,
           [
               'updated_by' => Auth::id(),
               'old_data' => $oldData,
               'new_data' => $user->only(['name', 'email', 'status']),
               'ip' => $request->ip(),
               'user_agent' => $request->header('User-Agent'),
           ],
           Auth::id()
       );
   
       return response()->json(['message' => 'User updated successfully']);
   }
   
   

   public function destroy($id)
   {
       $user = User::findOrFail($id);
       $user->status = -1; // Set to -1 for inactive instead of 0
       $user->save();
   
       // Log the deactivation action
       ActivityLogService::log(
           'user_deactivated',
           'Contractor account was deactivated.',
           $user,
           [
               'deactivated_by' => Auth::id(),
               'email' => $user->email,
               'ip' => request()->ip(),
               'user_agent' => request()->header('User-Agent'),
           ],
           Auth::id()
       );
   
       return response()->json([
           'message' => 'User Deactivated successfully.'
       ]);
   }   
}   
