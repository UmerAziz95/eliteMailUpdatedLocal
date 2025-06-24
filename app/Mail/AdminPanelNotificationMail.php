<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminPanelNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $panelsNeeded;
    public $totalInboxes;
    public $availableSpace;

    /**
     * Create a new message instance.
     */
    public function __construct($panelsNeeded, $totalInboxes, $availableSpace)
    {
        $this->panelsNeeded = $panelsNeeded;
        $this->totalInboxes = $totalInboxes;
        $this->availableSpace = $availableSpace;
    }    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->panelsNeeded > 0 
            ? 'Urgent: New Panels Required for Inbox Allocation'
            : 'Panel Capacity Status Report - All Good';
            
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-panel-notification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
