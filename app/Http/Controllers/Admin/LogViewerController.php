<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;

class LogViewerController extends Controller
{
    public function index(Request $request)
    {
        $logPath = storage_path('logs');
        $logs = [];
        
        if (File::exists($logPath)) {
            $files = File::files($logPath);
            
            foreach ($files as $file) {
                if ($file->getExtension() === 'log') {
                    $logs[] = [
                        'name' => $file->getFilename(),
                        'path' => $file->getPathname(),
                        'size' => $this->formatBytes($file->getSize()),
                        'modified' => Carbon::createFromTimestamp($file->getMTime())->format('Y-m-d H:i:s'),
                        'modified_diff' => Carbon::createFromTimestamp($file->getMTime())->diffForHumans(),
                    ];
                }
            }
            
            // Sort by modified date (newest first)
            usort($logs, function($a, $b) {
                return strcmp($b['modified'], $a['modified']);
            });
        }
        
        return view('admin.logs.index', compact('logs'));
    }
    
    public function show(Request $request, $filename)
    {
        $logPath = storage_path('logs/' . $filename);
        
        if (!File::exists($logPath) || !str_ends_with($filename, '.log')) {
            abort(404, 'Log file not found');
        }
        
        $lines = $request->get('lines', 100); // Default to last 100 lines
        $search = $request->get('search', '');
        
        $content = File::get($logPath);
        $logLines = explode("\n", $content);
        
        // Filter by search term if provided
        if ($search) {
            $logLines = array_filter($logLines, function($line) use ($search) {
                return stripos($line, $search) !== false;
            });
        }
        
        // Get the last N lines
        $logLines = array_slice(array_reverse($logLines), 0, $lines);
        $logLines = array_reverse($logLines);
        
        $logInfo = [
            'name' => $filename,
            'size' => $this->formatBytes(File::size($logPath)),
            'modified' => Carbon::createFromTimestamp(File::lastModified($logPath))->format('Y-m-d H:i:s'),
            'total_lines' => count(explode("\n", $content)),
            'showing_lines' => count($logLines),
        ];
        
        return view('admin.logs.show', compact('logLines', 'logInfo', 'search', 'lines'));
    }
    
    public function download($filename)
    {
        $logPath = storage_path('logs/' . $filename);
        
        if (!File::exists($logPath) || !str_ends_with($filename, '.log')) {
            abort(404, 'Log file not found');
        }
        
        return response()->download($logPath);
    }
    
    public function clear($filename)
    {
        $logPath = storage_path('logs/' . $filename);
        
        if (!File::exists($logPath) || !str_ends_with($filename, '.log')) {
            abort(404, 'Log file not found');
        }
        
        File::put($logPath, '');
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Log file {$filename} has been cleared successfully."
            ]);
        }
        
        return redirect()->route('admin.logs.index')->with('success', "Log file {$filename} has been cleared successfully.");
    }
    
    public function delete($filename)
    {
        $logPath = storage_path('logs/' . $filename);
        
        if (!File::exists($logPath) || !str_ends_with($filename, '.log')) {
            abort(404, 'Log file not found');
        }
        
        File::delete($logPath);
        
        if (request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Log file {$filename} has been deleted successfully."
            ]);
        }
        
        return redirect()->route('admin.logs.index')->with('success', "Log file {$filename} has been deleted successfully.");
    }
    
    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
