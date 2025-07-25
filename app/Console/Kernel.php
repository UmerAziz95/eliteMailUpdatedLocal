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

        // Process domain removal queue every minute
        $schedule->command('domains:process-removal-queue')
                ->everyMinute()
                ->withoutOverlapping()
                ->runInBackground()
                ->emailOutputOnFailure(config('mail.admin_email', 'admin@example.com'));

        // Daily database backup at 3:00 AM (USA time)
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
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
