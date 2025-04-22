<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Subscription;
use App\Models\User;

class SubscriptionCancellationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscription;
    public $user;
    public $reason;
    public $isAdmin;

    public function __construct(Subscription $subscription, User $user, string $reason, bool $isAdmin = false)
    {
        $this->subscription = $subscription;
        $this->user = $user;
        $this->reason = $reason;
        $this->isAdmin = $isAdmin;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->isAdmin ? 
                'Subscription Cancelled - ' . $this->user->name : 
                'Your Subscription Has Been Cancelled'
        );
    }

    public function content(): Content
    {
        $view = $this->isAdmin ? 'emails.subscription-cancelled-admin' : 'emails.subscription-cancelled-user';
        return new Content(
            markdown: $view,
            with: [
                'subscription' => $this->subscription,
                'user' => $this->user,
                'reason' => $this->reason
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
