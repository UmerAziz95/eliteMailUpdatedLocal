<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Customer\PlanController; // replace this
use Illuminate\Http\Request;

class ProcessFailedPaymentsSubscriptions extends Command
{
    protected $signature = 'payments:process-failures';
    protected $description = 'Call the controller method to process failed payments';

    public function handle()
    {
        // You may create a fake Request if your method depends on it
        $controller = new PlanController(); // replace with actual class

        // Call the method directly
        $response = $controller->handleCancelSubscriptionByCron(new Request());

        // Output message
        $data = $response->getData(true);
        $this->info($data['message'] ?? 'Done');
        $this->info('Cancelled count: ' . ($data['cancelled_count'] ?? 0));
    }
}
