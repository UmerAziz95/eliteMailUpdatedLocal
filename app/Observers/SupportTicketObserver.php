<?php

namespace App\Observers;

use App\Models\SupportTicket;
use App\Models\Notification;
use App\Models\User;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\TicketCreatedMail;

class SupportTicketObserver
{
    public function created(SupportTicket $ticket)
    {
        // Notify assigned contractor if one is assigned
        if ($ticket->assigned_to) {
            $contractor = $ticket->assignedTo;
            Notification::create([
                'user_id' => $contractor->id,
                'title' => 'New Ticket Assigned',
                'message' => "A new ticket #{$ticket->ticket_number} has been assigned to you",
                'type' => 'ticket_assigned',
                'data' => [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number
                ]
            ]);
            // Send email to assigned contractor
            Mail::to($contractor->email)
                ->queue(new TicketCreatedMail(
                    $ticket,
                    $ticket->user,
                    $contractor
                ));
        }
        // not assigned to send all contractors and admins
        // else {
        //     $contractors = User::where('role_id', '4')->orWhere('role_id', '1')->get();
        //     foreach ($contractors as $contractor) {
        //         Notification::create([
        //             'user_id' => $contractor->id,
        //             'title' => 'New Ticket Created',
        //             'message' => "A new ticket #{$ticket->ticket_number} has been created",
        //             'type' => 'ticket_created',
        //             'data' => [
        //                 'ticket_id' => $ticket->id,
        //                 'ticket_number' => $ticket->ticket_number
        //             ]
        //         ]);
                
        //         // Send email to each contractor
        //         Mail::to($contractor->email)
        //             ->queue(new TicketCreatedMail(
        //                 $ticket,
        //                 $ticket->user,
        //                 null
        //             ));
        //     }
        // }

        // ticket category is not order send mail admin
        if ($ticket->category !== 'order') {
            // $admins = User::where('role_id', '1')->get();
            // foreach ($admins as $admin) {
            //     Mail::to($admin->email)
            //         ->queue(new TicketCreatedMail(
            //             $ticket,
            //             $ticket->user,
            //             $admin
            //         ));
            // }
            // Send email to env admin
            Mail::to(config('mail.admin_address', 'admin@example.com'))
                ->queue(new TicketCreatedMail(
                    $ticket,
                    $ticket->user,
                    null
                ));
        }

        // Send Slack notification for new ticket
        try {
            SlackNotificationService::sendSupportTicketCreatedNotification($ticket);
            Log::info('Slack notification sent for ticket created', [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Slack notification for ticket created: ' . $e->getMessage(), [
                'ticket_id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'exception' => $e->getTraceAsString()
            ]);
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

        // Track changes for Slack notification
        $changes = [];
        $trackableFields = ['status', 'priority', 'assigned_to', 'category'];
        
        foreach ($trackableFields as $field) {
            if ($ticket->isDirty($field)) {
                $oldValue = $ticket->getOriginal($field);
                $newValue = $ticket->$field;
                
                // Format values for better display
                if ($field === 'assigned_to') {
                    $oldValue = $oldValue ? User::find($oldValue)?->name ?? 'Unknown' : 'Unassigned';
                    $newValue = $newValue ? User::find($newValue)?->name ?? 'Unknown' : 'Unassigned';
                }
                
                $changes[$field] = [
                    'from' => ucfirst(str_replace('_', ' ', $oldValue ?? 'N/A')),
                    'to' => ucfirst(str_replace('_', ' ', $newValue ?? 'N/A'))
                ];
            }
        }

        // Send Slack notification if there are changes
        if (!empty($changes)) {
            try {
                SlackNotificationService::sendSupportTicketUpdatedNotification($ticket, $changes);
                Log::info('Slack notification sent for ticket updated', [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'changes' => $changes
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send Slack notification for ticket updated: ' . $e->getMessage(), [
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'changes' => $changes,
                    'exception' => $e->getTraceAsString()
                ]);
            }
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
