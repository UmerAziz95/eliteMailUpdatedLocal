<?php

namespace App\Console\Commands;
use App\Http\Controllers\Admin\DomainHealthDashboardController; 

use Illuminate\Console\Command;

class CheckDomainHealth extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'domain:check-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check domain health for all completed orders (requires DOMAIN_HEALTH_CHECK_ENABLED=true)';
   

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if domain health check is enabled via environment flag
        if (!env('DOMAIN_HEALTH_CHECK_ENABLED', false)) {
            $this->info('Domain health check is disabled. Set DOMAIN_HEALTH_CHECK_ENABLED=true to enable.');
            return Command::SUCCESS;
        }

        $this->info('Starting domain health check...');
        
        $controller = new DomainHealthDashboardController();
        $controller->checkDomainHealth();
        
        $this->info('Domain health check completed successfully.');
        return Command::SUCCESS;
    }
} 
