<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;
use App\Models\User;
use App\Models\ReorderInfo;

class OrderEditedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $user;
    public $reorderInfo;
    public $isAdmin;
    public $changes;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(Order $order, User $user, ReorderInfo $reorderInfo, array $changes = [], bool $isAdmin = false)
    {
        $this->order = $order;
        $this->user = $user;
        $this->reorderInfo = $reorderInfo;
        $this->changes = $changes;
        $this->isAdmin = $isAdmin;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = $this->isAdmin 
            ? "Order Edited Notification - #{$this->order->id}"
            : "Order Edit Confirmation - #{$this->order->id}";

        $view = $this->isAdmin ? 'emails.orders.edited-admin' : 'emails.orders.edited-user';

        return $this->subject($subject)
            ->markdown($view)
            ->with([
                'order' => $this->order,
                'user' => $this->user,
                'reorderInfo' => $this->reorderInfo,
                'changes' => $this->changes
            ]);
    }
}
