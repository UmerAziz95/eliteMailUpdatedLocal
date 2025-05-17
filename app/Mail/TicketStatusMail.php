<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SupportTicket;
use App\Models\User;

class TicketStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $updatedBy;
    public $oldStatus;
    public $newStatus;

    public function __construct(SupportTicket $ticket, User $updatedBy, $oldStatus, $newStatus)
    {
        $this->ticket = $ticket;
        $this->updatedBy = $updatedBy;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function build()
    {
        return $this->subject("Ticket #{$this->ticket->ticket_number} Status Updated")
            ->markdown('emails.tickets.status');
    }
}
