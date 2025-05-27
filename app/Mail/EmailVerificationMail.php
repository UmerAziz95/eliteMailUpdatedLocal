<?php

namespace App\Mail;

use App\Models\User; // ✅ Fix: Use the correct User model
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $verificationLink;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $verificationLink)
    {
        $this->user = $user;
        $this->verificationLink = $verificationLink;
    }

    public function build()
    {
        return $this->subject('Verify Your Email Address - ' . config('app.name'))
            ->view('emails.email_verification');
    }
}
