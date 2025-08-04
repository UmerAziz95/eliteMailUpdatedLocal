<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\PaymentFailure;

class FailedPaymentNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    
    public $user;
    public $failure;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, PaymentFailure $failure)
    {
        $this->user = $user;
        $this->failure = $failure;
    }

      public function build()
    {
        return $this->subject('Payment Failure Notification')
                    ->markdown('emails.failed_payment_notification');
    }
}
   

