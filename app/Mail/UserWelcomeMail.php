<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class UserWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(User $user, $password = null)
    {
        $this->user = $user;
        $this->password = $password;
    }
    

    public function build()
    {
        return $this->subject('Welcome to ' . config('app.name'))
            ->view('emails.users.welcome');
    }
}