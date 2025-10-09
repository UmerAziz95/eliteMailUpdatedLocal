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
    public $isCustomerReply;

    public function __construct(SupportTicket $ticket, TicketReply $reply, User $repliedBy, User $assignedStaff, $isCustomerReply = false)
    {
        $this->ticket = $ticket;
        $this->reply = $reply;
        $this->repliedBy = $repliedBy;
        $this->assignedStaff = $assignedStaff;
        $this->isCustomerReply = $isCustomerReply;
    }

    public function build()
    {
        $subjectPrefix = $this->isCustomerReply ? 'Customer Reply' : 'New Reply';
        return $this->subject("{$subjectPrefix} on Ticket #{$this->ticket->ticket_number}")
            ->markdown('emails.tickets.reply');
    }
}