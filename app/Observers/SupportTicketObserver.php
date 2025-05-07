<?php

namespace App\Observers;

use App\Models\SupportTicket;
use App\Models\Notification;
use App\Models\User;

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
        // not assigned to send all contractors and admins
        else {
            $contractors = User::where('role_id', '4')->orWhere('role_id', '1')->get();
            // dd($contractors);
            foreach ($contractors as $contractor) {
                Notification::create([
                    'user_id' => $contractor->id,
                    'title' => 'New Ticket Created',
                    'message' => "A new ticket #{$ticket->ticket_number} has been created",
                    'type' => 'ticket_created',
                    'data' => [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number
                    ]
                ]);
            }
        }
    }

    public function updating(SupportTicket $ticket)
    {
        // No need to set old_status here as it's now handled by the model
    }

    public function updated(SupportTicket $ticket)
    {
        if ($ticket->isDirty('status') && $ticket->user) {
            $notification = (new Notification())->create([
                'user_id' => $ticket->user->id,
                'title' => 'Ticket Status Updated',
                'message' => "Your ticket #{$ticket->ticket_number} status has changed from {$ticket->getOriginal('status')} to {$ticket->status}",
                'type' => 'ticket_status_updated',
                'data' => [
                    'ticket_id' => $ticket->id,
                    'old_status' => $ticket->getOriginal('status'),
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
