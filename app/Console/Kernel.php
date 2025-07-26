<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
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

        // Order countdown notifications every 5 minutes
        $schedule->command('orders:countdown-notifications')
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));

        // ⏰ Daily database backup at 3:00 AM (USA time)
        $schedule->command('backup:daily')
                ->dailyAt('03:00')
                ->timezone('America/New_York') // or America/Chicago, etc.
                ->withoutOverlapping()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));
    }


    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
