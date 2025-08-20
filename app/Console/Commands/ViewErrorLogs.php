<?php

namespace App\Console\Commands;

use App\Models\ErrorLog;
use Illuminate\Console\Command;

class ViewErrorLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'error-logs:view {--limit=10 : Number of logs to display} {--severity= : Filter by severity level}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View recent error logs from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $severity = $this->option('severity');

        $query = ErrorLog::with('user')
            ->orderByDesc('created_at');

        if ($severity) {
            $query->where('severity', $severity);
        }

        $errorLogs = $query->limit($limit)->get();

        if ($errorLogs->isEmpty()) {
            $this->info('No error logs found.');
            return;
        }

        $this->info("Showing last {$errorLogs->count()} error logs:");
        $this->line('');

        foreach ($errorLogs as $log) {
            $this->line("ID: {$log->id}");
            $this->line("Time: {$log->created_at}");
            $this->line("Exception: {$log->exception_class}");
            $this->line("Message: {$log->message}");
            $this->line("File: {$log->file}:{$log->line}");
            $this->line("URL: {$log->url}");
            $this->line("User: " . ($log->user ? $log->user->name : 'Guest'));
            $this->line("Severity: {$log->severity}");
            $this->line(str_repeat('-', 80));
        }
    }
}
