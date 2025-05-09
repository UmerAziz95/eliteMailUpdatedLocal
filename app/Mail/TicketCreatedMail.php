<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SupportTicket;
use App\Models\User;

class TicketCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $assignedTo;
    public $creator;

    public function __construct(SupportTicket $ticket, User $creator, ?User $assignedTo = null)
    {
        $this->ticket = $ticket;
        $this->creator = $creator;
        $this->assignedTo = $assignedTo;
    }

    public function build()
    {
        return $this->subject("New Ticket Created #{$this->ticket->ticket_number}")
            ->markdown('emails.tickets.created');
    }
}