<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\TicketsImapEmails;

class ImapTicketReplyMapperService
{
    public function processInbox()
    {
        $imapInboxEmails = TicketsImapEmails::where('folder', 'INBOX')
            ->get();
            Log::info("Found " . $imapInboxEmails->count() . " emails in INBOX.");

        foreach ($imapInboxEmails as $email) {
            $this->mapEmailToTicketReply($email);
        }
    }

    private function mapEmailToTicketReply($email)
    {
        Log::info("Mapping email ID {$email->id} to ticket reply.");

        $subjectTicketId = $this->extractTicketIdFromSubject($email->subject);

        if (!$subjectTicketId) {
            Log::warning("Email {$email->id} has no ticket ID in subject: {$email->subject}");
            return;
        }

        $ticket = SupportTicket::where('ticket_number', $subjectTicketId)->first();

        Log::info("Found ticket: " . ($ticket ? $ticket->id : 'None'));

        if ($ticket) {
            TicketReply::updateOrCreate(
                [
                    'ticket_id' => $ticket->id,
                    // 'external_message_id' => $email->message_id, // must exist in table
                ],
                [
                    'message' => $email->body,
                    'user_id' => $ticket->assigned_to,
                   
                ]
            );

            // ✅ mark email as processed
           
        }
    }

    private function extractTicketIdFromSubject($subject)
    {
        // Example: "Re: [12345] Issue with login"
        if (preg_match('/\[([A-Za-z0-9\-]+)\]/', $subject, $matches)) {
        return $matches[1];
       }
        return null;
    }
}
