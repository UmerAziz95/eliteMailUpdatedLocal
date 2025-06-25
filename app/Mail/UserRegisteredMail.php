<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
 

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
     
    }

    public function build()
    {
        return $this->subject('A New User Registered - ' . config('app.name'))
            ->view('emails.admin.user_registered');
    }
}
