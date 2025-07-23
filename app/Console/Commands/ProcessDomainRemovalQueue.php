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
                          {--force : Force broadcast for all tasks regardless of date}
                          {--reset-broadcast : Reset broadcast flags for all tasks (use with caution)}
                          {--show-all : Show all tasks including already broadcasted ones}';

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
        $resetBroadcast = $this->option('reset-broadcast');
        $showAll = $this->option('show-all');
        
        $this->info('🔄 Processing Domain Removal Queue...');
        $this->info('Time: ' . Carbon::now()->format('Y-m-d H:i:s'));
        
        // Handle reset broadcast flags option
        if ($resetBroadcast) {
            $this->warn('🔄 Resetting broadcast flags for all tasks...');
            $resetCount = DomainRemovalTask::whereNotNull('broadcasted_at')->update(['broadcasted_at' => null]);
            $this->info("✅ Reset broadcast flags for {$resetCount} task(s)");
            
            if (!$force && !$showAll) {
                return 0;
            }
        }
        
        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No broadcasts will be sent');
        }
        
        if ($force) {
            $this->warn('⚡ FORCE MODE - Broadcasting for all tasks regardless of date');
        }
        
        if ($showAll) {
            $this->warn('👁️ SHOW ALL MODE - Displaying all tasks including already broadcasted');
        }

        // Get tasks that are ready to be processed
        $query = DomainRemovalTask::query();
        
        if (!$force) {
            // Only get tasks where started_queue_date is now or in the past
            $query->where('started_queue_date', '<=', Carbon::now());
        }
        
        // Get tasks that are still pending/in-progress
        $query->whereIn('status', ['pending', 'in-progress']);
        
        // Filter by broadcast status unless showing all
        if (!$showAll) {
            $query->whereNull('broadcasted_at'); // Only get tasks that haven't been broadcasted yet
        }
        
        $tasks = $query->orderBy('started_queue_date')->get();

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
            $alreadyBroadcast = !is_null($task->broadcasted_at);
            
            // Determine status
            if ($alreadyBroadcast) {
                $status = '📡 BROADCASTED';
                $broadcastTime = Carbon::parse($task->broadcasted_at)->format('Y-m-d H:i:s');
                $statusDetail = " (at {$broadcastTime})";
            } elseif ($isReady) {
                $status = '✅ READY';
                $statusDetail = '';
            } else {
                $status = '⏳ WAITING';
                $statusDetail = '';
            }
            
            $this->line("  - Task #{$task->task_id}: {$status}{$statusDetail} (Queue: {$queueDate->format('Y-m-d H:i:s')})");
            
            // Only broadcast if ready and not already broadcast and not in dry-run mode
            if ($isReady && !$alreadyBroadcast && !$dryRun) {
                try {
                    // Broadcast TaskStarted event for this task
                    broadcast(new TaskStarted($task));
                    
                    // Mark task as broadcasted to prevent duplicate processing
                    $task->update(['broadcasted_at' => Carbon::now()]);
                    
                    $this->info("    📡 Broadcast sent for Task #{$task->task_id}");
                    $broadcastCount++;
                    
                } catch (\Exception $e) {
                    $this->error("    ❌ Failed to broadcast Task #{$task->task_id}: " . $e->getMessage());
                }
            } elseif ($alreadyBroadcast && $isReady && !$dryRun) {
                $this->line("    ⏭️ Skipping Task #{$task->task_id} - already broadcasted");
            }
        }
        
        if (!$dryRun) {
            $this->info("📡 Successfully broadcast {$broadcastCount} task(s)");
        } else {
            $readyCount = $tasks->filter(function($task) use ($force) {
                $isReady = $force || Carbon::parse($task->started_queue_date)->lte(Carbon::now());
                $alreadyBroadcast = !is_null($task->broadcasted_at);
                return $isReady && !$alreadyBroadcast;
            })->count();
            $this->info("🧪 Would broadcast {$readyCount} task(s) in real run");
        }
        
        $this->info('✅ Domain removal queue processing completed');
        
        return 0;
    }
}
