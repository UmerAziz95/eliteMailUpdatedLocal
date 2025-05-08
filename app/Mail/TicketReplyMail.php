<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SupportTicket;
use App\Models\TicketReply;
use App\Models\User;

class TicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $reply;
    public $repliedBy;
    public $assignedStaff;

    public function __construct(SupportTicket $ticket, TicketReply $reply, User $repliedBy, User $assignedStaff)
    {
        $this->ticket = $ticket;
        $this->reply = $reply;
        $this->repliedBy = $repliedBy;
        $this->assignedStaff = $assignedStaff;
    }

    public function build()
    {
        return $this->subject("New Reply on Ticket #{$this->ticket->ticket_number}")
            ->markdown('emails.tickets.reply');
    }
}