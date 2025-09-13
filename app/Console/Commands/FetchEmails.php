<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailImapService;

class FetchEmails extends Command
{
    protected $signature = 'emails:fetch';
    protected $description = 'Fetch emails from PrivateEmail IMAP and store them in DB';

    public function handle(EmailImapService $emailService)
    {
        $this->info("Fetching emails...");
        $emailService->fetchAndStoreEmails();
        $this->info("Emails synced successfully.");
    }
}
