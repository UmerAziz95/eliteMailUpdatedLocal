<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\User;

class OrderStatusChangeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;
    public $oldStatus;
    public $newStatus;
    public $reason;
    public $isAdmin;

    public function __construct(Order $order, User $user, string $oldStatus, string $newStatus, ?string $reason = null, bool $isAdmin = false)
    {
        $this->order = $order;
        $this->user = $user;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->reason = $reason;
        $this->isAdmin = $isAdmin;
    }

    public function build()
    {
        $subject = $this->isAdmin 
            ? "Order Status Changed - #{$this->order->id}"
            : "Your Order Status Has Been Updated - #{$this->order->id}";

        $view = $this->isAdmin ? 'emails.orders.status-change-admin' : 'emails.orders.status-change-user';

        return $this->subject($subject)
            ->markdown($view)
            ->with([
                'order' => $this->order,
                'user' => $this->user,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'reason' => $this->reason
            ]);
    }
}