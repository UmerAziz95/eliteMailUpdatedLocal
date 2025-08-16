<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Customer\PlanController; // adjust path
use Illuminate\Http\Request;

class SendFailedPaymentEmails extends Command
{
    protected $signature = 'emails:send-failed-payments';
    protected $description = 'Send one email per day for failed payments within 72 hours';

    public function handle()
    {
        $this->info('Starting to send failed payment emails...');

        // Optional: Use controller method directly
        $controller = new PlanController();
        $response = $controller->sendMailsTo72HoursFailedPayments(new Request());

        $this->info($response->getContent());
    }
}

