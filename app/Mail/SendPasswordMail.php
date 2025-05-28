<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Log;
class SendPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public $randomPassword; 
    public $user;
      public function __construct(User $user, $randomPassword)
    {
        $this->user = $user;
        $this->randomPassword = $randomPassword;
       
    }

    public function build()
    {
        return $this->subject('Your account password - ' . config('app.name'))
            ->view('emails.send_password');
    }
}
