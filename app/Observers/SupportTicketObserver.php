<?php

namespace App\Observers;

use App\Models\SupportTicket;
use App\Notifications\TicketStatusChanged;

class SupportTicketObserver
{
    public function created(SupportTicket $ticket)
    {
        // Notify assigned contractor if one is assigned
        if ($ticket->assigned_to) {
            $contractor = $ticket->assignedTo;
            $contractor->notifications()->create([
                'title' => 'New Ticket Assigned',
                'message' => "You have been assigned ticket #{$ticket->ticket_number}",
                'type' => 'ticket_assigned',
                'user_id' => $contractor->id,
                'data' => [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number
                ]
            ]);
        }
    }

    public function updating(SupportTicket $ticket)
    {
        // Store the original status as a temporary property
        if ($ticket->isDirty('status')) {
            $ticket->temp_old_status = $ticket->getOriginal('status');
        }
    }

    public function updated(SupportTicket $ticket)
    {
        // Check if status has changed
        if (isset($ticket->temp_old_status) && $ticket->temp_old_status !== $ticket->status) {
            // Notify the customer
            $ticket->user->notify(new TicketStatusChanged(
                $ticket,
                $ticket->temp_old_status,
                $ticket->status
            ));

            // Create a notification record
            $ticket->user->notifications()->create([
                'title' => 'Ticket Status Updated',
                'message' => "Status of ticket #{$ticket->ticket_number} changed from {$ticket->temp_old_status} to {$ticket->status}",
                'type' => 'ticket_status_change',
                'user_id' => $ticket->user_id,
                'data' => [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'old_status' => $ticket->temp_old_status,
                    'new_status' => $ticket->status
                ]
            ]);
        }
    }

    public function deleted(SupportTicket $supportTicket): void
    {
        //
    }

    public function restored(SupportTicket $supportTicket): void
    {
        //
    }

    public function forceDeleted(SupportTicket $supportTicket): void
    {
        //
    }
}
