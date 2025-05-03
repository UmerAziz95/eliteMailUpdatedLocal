<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Invoice;
use App\Models\User;

class InvoiceGeneratedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invoice;
    public $user;
    public $isAdminNotification;

    public function __construct(Invoice $invoice, User $user, bool $isAdminNotification = false)
    {
        $this->invoice = $invoice;
        $this->user = $user;
        $this->isAdminNotification = $isAdminNotification;
    }

    public function build()
    {
        $subject = $this->isAdminNotification 
            ? "New Invoice Generated - #{$this->invoice->chargebee_invoice_id}"
            : "Your New Invoice is Ready - #{$this->invoice->chargebee_invoice_id}";

        return $this->subject($subject)
            ->view('emails.invoices.generated');
    }
}