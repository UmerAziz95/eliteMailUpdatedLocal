<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Log;
use DataTables;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLogService;

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
                // performed_on_type
                ->addColumn('performed_on_type', function ($log) {
                    if ($log->performedOn) {
                        return class_basename($log->performed_on_type);
                    }
                    return 'N/A';
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
                // IP
                ->addColumn('ip', function ($log) {
                    return $log->data['ip'] ?? 'N/A';
                })
                // User Agent
                ->addColumn('user_agent', function ($log) {
                    return $log->data['user_agent'] ?? 'N/A';
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

        // return view('admin.logs.index'); // Make sure you create this Blade view  
    }

    public function specificLogs(Request $request)
    {
        if ($request->ajax()) {
            $logs = Log::with(['user', 'performedOn'])->where('performed_by', Auth::id())->latest();

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
                // performed_on_type
                ->addColumn('performed_on_type', function ($log) {
                    if ($log->performedOn) {
                        return class_basename($log->performed_on_type);
                    }
                    return 'N/A';
                })
                ->addColumn('performed_on', function ($log) {
                    if ($log->performedOn) {
                        return class_basename($log->performed_on_type) . ' (ID #' . $log->performed_on_id . ')';
                    } else {
                        return 'N/A';
                    }
                })
                // IP
                ->addColumn('ip', function ($log) {
                    return $log->data['ip'] ?? 'N/A';
                })
                // User Agent
                ->addColumn('user_agent', function ($log) {
                    return $log->data['user_agent'] ?? 'N/A';
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

        // return view('admin.logs.index'); // Make sure you create this Blade view  
    }

    public function getContractorActivity(Request $request)
    {
        if ($request->ajax()) {
            $logs = Log::with(['user', 'performedOn'])->where('performed_by', Auth::id())->latest();

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
                // performed_on_type
                ->addColumn('performed_on_type', function ($log) {
                    if ($log->performedOn) {
                        return class_basename($log->performed_on_type);
                    }
                    return 'N/A';
                })
                ->addColumn('performed_on', function ($log) {
                    if ($log->performedOn) {
                        return class_basename($log->performed_on_type) . ' (ID #' . $log->performed_on_id . ')';
                    } else {
                        return 'N/A';
                    }
                })
                // IP
                ->addColumn('ip', function ($log) {
                    return $log->data['ip'] ?? 'N/A';
                })
                // User Agent
                ->addColumn('user_agent', function ($log) {
                    return $log->data['user_agent'] ?? 'N/A';
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
        
        return response()->json(['error' => 'Not an AJAX request'], 400);
    }
}