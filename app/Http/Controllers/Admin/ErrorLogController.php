<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ErrorLog::with('user')->orderByDesc('created_at');
        
        // Filter by severity if provided
        if ($request->has('severity') && !empty($request->severity)) {
            $query->where('severity', $request->severity);
        }
        
        // Filter by date range if provided
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Search in message or exception class
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                  ->orWhere('exception_class', 'like', "%{$search}%")
                  ->orWhere('file', 'like', "%{$search}%");
            });
        }
        
        $errorLogs = $query->paginate(20);
        
        $severityOptions = ['error', 'warning', 'info', 'debug'];
        
        return view('admin.error-logs.index', compact('errorLogs', 'severityOptions'));
    }
    
    public function show(ErrorLog $errorLog)
    {
        return view('admin.error-logs.show', compact('errorLog'));
    }
    
    public function destroy(ErrorLog $errorLog)
    {
        $errorLog->delete();
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Error log deleted successfully.'
            ]);
        }
        
        return redirect()->route('admin.error-logs.index')
            ->with('success', 'Error log deleted successfully.');
    }
    
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'error_log_ids' => 'required|array',
            'error_log_ids.*' => 'exists:error_logs,id'
        ]);
        
        $deletedCount = ErrorLog::whereIn('id', $request->error_log_ids)->count();
        ErrorLog::whereIn('id', $request->error_log_ids)->delete();
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} error log(s)."
            ]);
        }
        
        return redirect()->route('admin.error-logs.index')
            ->with('success', 'Selected error logs deleted successfully.');
    }
    
    public function clearOld(Request $request)
    {
        $days = $request->input('days', 30);
        
        $deletedCount = ErrorLog::where('created_at', '<', now()->subDays($days))->delete();
        
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Deleted {$deletedCount} error logs older than {$days} days.",
                'deletedCount' => $deletedCount
            ]);
        }
        
        return redirect()->route('admin.error-logs.index')
            ->with('success', "Deleted {$deletedCount} error logs older than {$days} days.");
    }
}
