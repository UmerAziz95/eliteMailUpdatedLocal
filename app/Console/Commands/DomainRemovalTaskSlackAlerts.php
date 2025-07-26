<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DomainRemovalTask;
use App\Services\SlackNotificationService;
use App\Services\DomainRemovalAlertService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DomainRemovalTaskSlackAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain-removal:send-slack-alerts 
                          {--dry-run : Show what alerts would be sent without actually sending them}
                          {--force : Force send alerts for all matching tasks regardless of last alert time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Slack alerts for domain removal tasks based on time elapsed since started_queue_date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        
        $this->info('🚨 Processing Domain Removal Task Slack Alerts...');
        $this->info('Time: ' . Carbon::now()->format('Y-m-d H:i:s'));
        
        if ($dryRun) {
            $this->warn('🧪 DRY RUN MODE - No alerts will be sent');
        }
        
        if ($force) {
            $this->warn('⚡ FORCE MODE - Ignoring last alert timestamps');
        }

        // Get all tasks that need alert checking
        $tasks = DomainRemovalAlertService::getTasksForAlertChecking();

        if ($tasks->isEmpty()) {
            $this->info('✅ No active domain removal tasks found that require alerts');
            return 0;
        }

        $this->info("📋 Found {$tasks->count()} active task(s) to check for alerts");

        $alertsSent = 0;
        
        foreach ($tasks as $task) {
            $alertsSent += $this->processTaskAlerts($task, $dryRun, $force);
        }

        if (!$dryRun) {
            $this->info("📡 Successfully sent {$alertsSent} alert(s)");
        } else {
            $this->info("🧪 Would send {$alertsSent} alert(s) in real run");
        }

        $this->info('✅ Domain removal task alert processing completed');
        
        return 0;
    }

    /**
     * Process alerts for a single task
     */
    private function processTaskAlerts(DomainRemovalTask $task, bool $dryRun, bool $force): int
    {
        $alertsSent = 0;
        $hoursElapsed = DomainRemovalAlertService::calculateHoursElapsed($task);
        
        // Get standard alert configuration
        $alertConfigs = DomainRemovalAlertService::getAlertConfiguration();
        
        // Check standard alerts
        foreach ($alertConfigs as $threshold => $config) {
            if ($hoursElapsed >= $threshold) {
                if (DomainRemovalAlertService::shouldSendAlert($task, $config['type'], $force)) {
                    $this->line("  📋 Task #{$task->id}: {$hoursElapsed}h elapsed - Sending '{$config['type']}' alert");
                    
                    if (!$dryRun) {
                        $success = $this->sendTaskAlert($task, $config, $hoursElapsed);
                        if ($success) {
                            DomainRemovalAlertService::markAlertSent($task, $config['type']);
                            $alertsSent++;
                        }
                    } else {
                        $alertsSent++; // Count for dry run
                    }
                } else {
                    $this->line("  ⏭️ Task #{$task->id}: '{$config['type']}' alert already sent");
                }
            }
        }

        // Check overdue alerts (every hour after 12h)
        if ($hoursElapsed >= 13) {
            $currentHour = floor($hoursElapsed);
            $overdueConfig = DomainRemovalAlertService::getOverdueAlertConfig($currentHour);
            
            if (DomainRemovalAlertService::shouldSendAlert($task, $overdueConfig['type'], $force)) {
                $this->line("  📋 Task #{$task->id}: {$hoursElapsed}h elapsed - Sending overdue alert");
                
                if (!$dryRun) {
                    $success = $this->sendTaskAlert($task, $overdueConfig, $hoursElapsed);
                    if ($success) {
                        DomainRemovalAlertService::markAlertSent($task, $overdueConfig['type']);
                        $alertsSent++;
                    }
                } else {
                    $alertsSent++; // Count for dry run
                }
            } else {
                $this->line("  ⏭️ Task #{$task->id}: Overdue alert for hour {$currentHour} already sent");
            }
        }

        return $alertsSent;
    }

    /**
     * Send Slack alert for task
     */
    private function sendTaskAlert(DomainRemovalTask $task, array $alertConfig, float $hoursElapsed): bool
    {
        try {
            // Get task data
            $taskData = DomainRemovalAlertService::getTaskAlertData($task);
            
            // Generate Slack message
            $message = DomainRemovalAlertService::generateSlackMessage($taskData, $alertConfig, $hoursElapsed);
            
            // Send to Slack using the inbox-admins channel
            $result = SlackNotificationService::send('inbox-admins', $message);
            
            if ($result) {
                $this->info("    📡 Alert sent successfully: Task #{$task->id} - {$alertConfig['type']}");
                Log::channel('slack_notifications')->info("Domain removal task alert sent", [
                    'task_id' => $task->id,
                    'alert_type' => $alertConfig['type'],
                    'hours_elapsed' => $hoursElapsed,
                    'order_id' => $task->order_id,
                    'urgency' => $alertConfig['urgency']
                ]);
                return true;
            } else {
                $this->warn("    ❌ Failed to send alert: Task #{$task->id} - {$alertConfig['type']}");
                return false;
            }
            
        } catch (\Exception $e) {
            $this->error("    ❌ Error sending alert for Task #{$task->id}: " . $e->getMessage());
            Log::error("Domain removal task alert error", [
                'task_id' => $task->id,
                'alert_type' => $alertConfig['type'],
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
