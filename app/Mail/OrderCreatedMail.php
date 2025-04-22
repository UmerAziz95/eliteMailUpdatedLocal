<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\User;

class OrderCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;
    public $isAdminNotification;

    public function __construct(Order $order, User $user, bool $isAdminNotification = false)
    {
        $this->order = $order;
        $this->user = $user;
        $this->isAdminNotification = $isAdminNotification;
    }

    public function build()
    {
        $subject = $this->isAdminNotification 
            ? "New Order Created - #{$this->order->chargebee_invoice_id}"
            : "Your Order Confirmation - #{$this->order->chargebee_invoice_id}";

        return $this->subject($subject)
            ->view('emails.orders.created');
    }
}