<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Log;
use DataTables;
use Exception;

class AppLogController extends Controller
{
    public function getLogs(Request $request)
    {
        if ($request->ajax()) {
            $logs = Log::with(['user', 'performedOn'])->latest();

            return DataTables::of($logs)
                ->addColumn('action_type', function ($log) {
                    return $log->action_type ?? 'N/A';
                })
                ->addColumn('description', function ($log) {
                    return $log->description ?? 'N/A';
                })
                ->addColumn('performed_by', function ($log) {
                    return $log->user ? $log->user->name : 'System';
                })
                ->addColumn('performed_on', function ($log) {
                    if ($log->performedOn) {
                        return class_basename($log->performed_on_type) . ' (ID #' . $log->performed_on_id . ')';
                    } else {
                        return 'N/A';
                    }
                })
                ->addColumn('extra_data', function ($log) {
                    return json_encode($log->data ?? []);
                })
                ->addColumn('action', function ($row) {
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
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('admin.logs.index'); // Make sure you create this Blade view  
    }
}


// // log creation 
// $user=Auth::user();
// // Create a new activity log using the custom log service
// ActivityLogService::log(
//     'user_signup', // 🟢 Action Type: This is a short, unique identifier for the action (e.g. 'user_signup', 'order_created', 'payment_done', etc.)
    
//     'New user registered', // 🟢 Description: A human-readable message that describes what happened. This will be shown in logs.
    
//     $user, // 🟢 Performed By: The user (model instance) who performed this action. Can be null if action was performed by a system task.
    
//     [   // 🟢 Extra Data: (Optional) An array of additional data related to the action. This is stored as JSON.
//         'email' => $user->email,
//         // You can add any other contextual info here: IP, user role, referral code, etc.
//         // You can add any other contextual info here: IP, user role, referral code, etc.
//     ]
// );