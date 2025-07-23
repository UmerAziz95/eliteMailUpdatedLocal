<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DomainRemovalTask;
use App\Events\TaskStarted;
use App\Events\TaskQueueUpdated;
use Carbon\Carbon;

class ProcessDomainRemovalQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domains:process-removal-queue 
                          {--dry-run : Show what would be processed without triggering broadcasts}
                          {--force : Force broadcast for all tasks regardless of date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process domain removal tasks that are ready to start and trigger WebSocket broadcasts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        $this->info('🔄 Processing Domain Removal Queue...');
        $this->info('Time: ' . Carbon::now()->format('Y-m-d H:i:s'));
        
        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No broadcasts will be sent');
        }
        
        if ($force) {
            $this->warn('⚡ FORCE MODE - Broadcasting for all tasks regardless of date');
        }

        // Get tasks that are ready to be processed
        $query = DomainRemovalTask::query();
        
        if (!$force) {
            // Only get tasks where started_queue_date is now or in the past
            $query->where('started_queue_date', '<=', Carbon::now());
        }
        
        // Get tasks that haven't been broadcast yet (you might want to add a flag for this)
        $tasks = $query->whereIn('status', ['pending', 'in-progress'])
                      ->orderBy('started_queue_date')
                      ->get();

        if ($tasks->isEmpty()) {
            $this->info('✅ No tasks ready for processing at this time');
            return 0;
        }
        
        $this->info("📋 Found {$tasks->count()} task(s) ready for processing:");
        
        $broadcastCount = 0;
        // dd("📋 Task details:", $tasks);
        foreach ($tasks as $task) {
            $queueDate = Carbon::parse($task->started_queue_date);
            $isReady = $force || $queueDate->lte(Carbon::now());
            
            $status = $isReady ? '✅ READY' : '⏳ WAITING';
            $this->line("  - Task #{$task->task_id}: {$status} (Queue: {$queueDate->format('Y-m-d H:i:s')})");
            
            if ($isReady && !$dryRun) {
                try {
                    // dd("    📡 Broadcasting Task #{$task->task_id} to WebSocket channel");
                    // Broadcast TaskStarted event for this task
                    broadcast(new TaskStarted($task));
                    
                    $this->info("    📡 Broadcast sent for Task #{$task->task_id}");
                    $broadcastCount++;
                    
                    // Optional: Mark task as broadcast (if you add a field for this)
                    // $task->update(['broadcast_sent_at' => Carbon::now()]);
                    
                } catch (\Exception $e) {
                    $this->error("    ❌ Failed to broadcast Task #{$task->task_id}: " . $e->getMessage());
                }
            }
        }
        
        if (!$dryRun) {
            $this->info("📡 Successfully broadcast {$broadcastCount} task(s)");
        } else {
            $readyCount = $tasks->where(function($task) use ($force) {
                return $force || Carbon::parse($task->started_queue_date)->lte(Carbon::now());
            })->count();
            $this->info("🧪 Would broadcast {$readyCount} task(s) in real run");
        }
        
        $this->info('✅ Domain removal queue processing completed');
        
        return 0;
    }
}
