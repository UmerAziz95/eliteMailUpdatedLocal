<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Carbon;

class LogViewerController extends Controller
{
    private const LARGE_LOG_THRESHOLD_BYTES = 50 * 1024 * 1024; // 50 MB
    private const LINE_COUNT_OPTIONS = [
        10, 25, 50,
        100,
        200,
        500,
        1000,
        5000,
        10000,
        25000,
        50000,
        100000,
        200000,
        300000,
        400000,
        500000,
        1000000,
    ];

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

        $lines = (int) $request->get('lines', 100);
        if (!in_array($lines, self::LINE_COUNT_OPTIONS, true)) {
            $lines = 100; // Fallback to default if invalid
        }
        $search = trim((string) $request->get('search', ''));

        $fileSize = File::size($logPath);
        $lastModified = Carbon::createFromTimestamp(File::lastModified($logPath))->format('Y-m-d H:i:s');
        $isLargeFile = $fileSize >= self::LARGE_LOG_THRESHOLD_BYTES;

        if ($request->ajax()) {
            $logData = $this->prepareLogLines($logPath, $lines, $search, $fileSize);
            $logLines = $logData['lines'];

            $logInfo = [
                'name' => $filename,
                'size' => $this->formatBytes($fileSize),
                'modified' => $lastModified,
                'total_lines' => $logData['total_lines'],
                'showing_lines' => count($logLines),
                'is_large_file' => $logData['is_large_file'],
            ];

            return response()->json([
                'success' => true,
                'log_lines' => $logLines,
                'log_info' => $logInfo,
            ]);
        }

        $logInfo = [
            'name' => $filename,
            'size' => $this->formatBytes($fileSize),
            'modified' => $lastModified,
            'total_lines' => null,
            'showing_lines' => 0,
            'is_large_file' => $isLargeFile,
        ];

        return view('admin.logs.show', [
            'logLines' => [],
            'logInfo' => $logInfo,
            'search' => $search,
            'lines' => $lines,
            'lineOptions' => self::LINE_COUNT_OPTIONS,
        ]);
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
        // dd($logPath);
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
    
    private function prepareLogLines(string $logPath, int $lines, string $search, int $fileSize): array
    {
        $threshold = self::LARGE_LOG_THRESHOLD_BYTES;
        $isLargeFile = $fileSize >= $threshold;

        if (!$isLargeFile) {
            $content = File::get($logPath);
            $allLines = explode("\n", $content);
            $totalLines = count($allLines);

            $filteredLines = $allLines;

            if ($search !== '') {
                $filteredLines = array_values(array_filter($filteredLines, function ($line) use ($search) {
                    return stripos($line, $search) !== false;
                }));
            }

            $displayLines = array_slice($filteredLines, -$lines);
            $displayLines = array_map(function ($line) {
                return rtrim($line, "\r\n");
            }, $displayLines);

            return [
                'lines' => $displayLines,
                'total_lines' => $totalLines,
                'is_large_file' => false,
            ];
        }

        if ($search !== '') {
            return $this->streamSearchLogLines($logPath, $search, $lines);
        }

        return $this->streamTailLogLines($logPath, $lines);
    }

    private function streamTailLogLines(string $logPath, int $linesToReturn): array
    {
        if ($linesToReturn <= 0) {
            return [
                'lines' => [],
                'total_lines' => $this->countFileLines($logPath),
                'is_large_file' => true,
            ];
        }

        $handle = fopen($logPath, 'rb');

        if ($handle === false) {
            return [
                'lines' => [],
                'total_lines' => null,
                'is_large_file' => true,
            ];
        }

        $chunkSize = 256 * 1024; // 256 KB chunks
        $bufferRemainder = '';
        $tailBuffer = new \SplDoublyLinkedList();
        $tailBuffer->setIteratorMode(\SplDoublyLinkedList::IT_MODE_FIFO);
        $totalLines = 0;

        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSize);

            if ($chunk === false) {
                break;
            }

            if ($chunk === '') {
                continue;
            }

            $combined = $bufferRemainder . $chunk;
            $lines = explode("\n", $combined);
            $bufferRemainder = array_pop($lines);

            foreach ($lines as $line) {
                $totalLines++;
                $normalizedLine = rtrim($line, "\r");
                $tailBuffer->push($normalizedLine);

                if ($tailBuffer->count() > $linesToReturn) {
                    $tailBuffer->shift();
                }
            }
        }

        if ($bufferRemainder !== '') {
            $totalLines++;
            $normalizedLine = rtrim($bufferRemainder, "\r");
            $tailBuffer->push($normalizedLine);

            if ($tailBuffer->count() > $linesToReturn) {
                $tailBuffer->shift();
            }
        }

        fclose($handle);

        $linesArray = [];
        for ($tailBuffer->rewind(); $tailBuffer->valid(); $tailBuffer->next()) {
            $linesArray[] = $tailBuffer->current();
        }

        return [
            'lines' => $linesArray,
            'total_lines' => $totalLines,
            'is_large_file' => true,
        ];
    }

    private function countFileLines(string $logPath): ?int
    {
        $handle = fopen($logPath, 'rb');

        if ($handle === false) {
            return null;
        }

        $chunkSize = 256 * 1024;
        $lineCount = 0;
        $bufferRemainder = '';

        while (!feof($handle)) {
            $chunk = fread($handle, $chunkSize);

            if ($chunk === false) {
                break;
            }

            if ($chunk === '') {
                continue;
            }

            $combined = $bufferRemainder . $chunk;
            $lines = explode("\n", $combined);
            $bufferRemainder = array_pop($lines);

            $lineCount += count($lines);
        }

        if ($bufferRemainder !== '') {
            $lineCount++;
        }

        fclose($handle);

        return $lineCount;
    }

    private function streamSearchLogLines(string $logPath, string $search, int $lines): array
    {
        $handle = fopen($logPath, 'rb');

        if ($handle === false) {
            return [
                'lines' => [],
                'total_lines' => 'Unavailable for large files',
                'is_large_file' => true,
            ];
        }

        $matches = [];
        $totalLines = 0;

        while (($line = fgets($handle)) !== false) {
            $totalLines++;

            if (stripos($line, $search) !== false) {
                $matches[] = rtrim($line, "\r\n");

                if (count($matches) > $lines) {
                    array_shift($matches);
                }
            }
        }

        fclose($handle);

        return [
            'lines' => $matches,
            'total_lines' => $totalLines,
            'is_large_file' => true,
        ];
    }

    private function formatBytes($size, $precision = 2)
    {
        if ($size <= 0) {
            return '0 B';
        }

        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
}
