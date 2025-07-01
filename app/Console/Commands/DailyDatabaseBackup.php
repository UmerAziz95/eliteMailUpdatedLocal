<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DailyDatabaseBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:daily';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
  public function handle()
{
    $this->info('Starting daily database backup...');

    $filename = '3am_daily_backup_' . now()->format('Y_m_d_His') . '.sql';
    $path = storage_path("app/backup/{$filename}");

    if (!file_exists(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }

    $database = config('database.connections.mysql.database');
    $username = config('database.connections.mysql.username');
    $password = config('database.connections.mysql.password');
    $host = config('database.connections.mysql.host');

    // Avoid PROCESS privilege error
    $command = "mysqldump --no-tablespaces -h {$host} -u {$username} -p\"{$password}\" {$database} > \"{$path}\"";

    exec($command, $output, $returnVar);

    if ($returnVar === 0) {
        $this->info("✅ Database backup saved at: {$path}");
    } else {
        $this->error("❌ Failed to back up the database.");
    }
}

}
