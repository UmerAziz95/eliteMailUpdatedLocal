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
    protected $description = 'Check domain health for all completed orders';
      
    /**
     * The console command description.
     *
     * @var string
     */
   

    /**
     * Execute the console command.
     */
    public function handle()
    {
         $controller = new DomainHealthDashboardController();
        $controller->checkDomainHealth();
        return Command::SUCCESS;
        //
    }
} 
