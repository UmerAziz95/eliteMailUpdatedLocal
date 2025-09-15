<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ImapTicketReplyMapperService;

class ProcessImapEmails extends Command
{
    protected $signature = 'tickets:process-imap';
    protected $description = 'Process IMAP inbox emails and map them to ticket replies';

    public function handle(ImapTicketReplyMapperService $service)
    {
        $this->info("Processing IMAP inbox...");
        $service->processInbox();
        $this->info("Done.");
    }
}
