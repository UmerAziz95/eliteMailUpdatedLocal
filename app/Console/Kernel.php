<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Send draft order notifications every 10 minutes
        $schedule->command('orders:send-draft-notifications')
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->runInBackground()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));

        // Check panel capacity every 10 minutes
        $schedule->command('panels:check-capacity')
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->runInBackground()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));

        // Panel capacity notifications every 5 minutes
        $schedule->command('panels:capacity-notifications')
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));

        // Process domain removal queue every minute
        $schedule->command('domains:process-removal-queue')
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));

        // Daily database backup at 3:00 AM (USA time)
        // Order countdown notifications every 5 minutes
        $schedule->command('orders:countdown-notifications')
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));

        // Domain removal task Slack alerts every 10 minutes
        $schedule->command('domain-removal:send-slack-alerts')
                ->everyTenMinutes()
                ->withoutOverlapping()
                ->runInBackground()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));

        // ⏰ Daily database backup at 3:00 AM (USA time)
        $schedule->command('backup:daily')
                ->dailyAt('03:00')
                ->timezone('America/New_York')
                ->withoutOverlapping()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));

        // 🔔 Discord message sender (every 5 minutes)
        $schedule->call(function () {
                app()->call([\App\Http\Controllers\SettingController::class, 'discorSendMessageCron']);
                })
                ->name('Send Discord Message') // ✅ MUST come BEFORE withoutOverlapping()
                ->everyMinute()
                ->withoutOverlapping()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));

        // Process failed invoice notifications daily at 9:00 AM
        // $schedule->command('invoices:process-failed-notifications')
        //         ->dailyAt('09:00')
        //         ->withoutOverlapping()
        //         ->runInBackground()
        //         ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));
        
        $schedule->command('domain:check-health')
                ->dailyAt('22:00') // 10:00 PM UTC
                ->timezone('UTC')
                ->withoutOverlapping()
                ->runInBackground()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));
               
                $schedule->command('payments:process-failures')->hourly();

                // Send failed payment emails every hour for payments that failed within the last 72 hours
                $schedule->command('emails:send-failed-payments')->hourly();
                
                // Fix pending invoices by checking with ChargeBee (runs every hour)
                // $schedule->command('invoices:fix-pending --days=1')
                //         ->hourly()
                //         ->withoutOverlapping()
                //         ->runInBackground()
                //         ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));
                
                //tickets imap fetch and process every 5 minutes
                 $schedule->command('emails:fetch')
                                ->everyMinute()
                                ->then(function () {
                                // Run tickets:process-imap immediately after emails:fetch finishes
                                \Artisan::call('tickets:process-imap');
                                });

                // Update pool status from warming to available every 2 hours
                $schedule->command('pools:update-status --force')
                        ->everyTwoHours()
                        ->withoutOverlapping()
                        ->runInBackground()
                        ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));
                }

        protected function commands(): void
        {
                $this->load(__DIR__.'/Commands');
                require base_path('routes/console.php');
        }
}
