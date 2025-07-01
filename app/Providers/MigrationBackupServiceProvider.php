<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Console\Events\CommandStarting;

class MigrationBackupServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Event::listen(CommandStarting::class, function (CommandStarting $event) {
            $commandsToHook = ['migrate', 'migrate:fresh', 'migrate:refresh'];

            if (in_array($event->command, $commandsToHook)) {
                $this->backupDatabase();
            }
        });
    }

    protected function backupDatabase()
    {
        $filename = 'backup_' . now()->format('Y_m_d_His') . '.sql';
        $path = storage_path("app/backup/{$filename}");

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');

        $command = "mysqldump -h {$host} -u {$username} -p\"{$password}\" {$database} > \"{$path}\"";
        exec($command, $output, $returnVar);

        if ($returnVar === 0) {
            echo "\nDatabase backup created: {$path}\n";
        } else {
            echo "\nFailed to create database backup.\n";
        }
    }

    public function register()
    {
        //
    }
}
