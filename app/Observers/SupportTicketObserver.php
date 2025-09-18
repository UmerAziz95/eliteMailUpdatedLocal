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
    // Store original values before update
    private $originalValues = [];
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
        if (!app()->bound('slack.ticket.'.$ticket->id)) {
                app()->instance('slack.ticket.'.$ticket->id, true);

                    try {
                        SlackNotificationService::sendSupportTicketCreatedNotification($ticket);
                    } catch (\Exception $e) {
                        Log::error('Failed Slack notification: '.$e->getMessage());
                    }
                }
    }

    public function updating(SupportTicket $ticket)
    {
        // Store current values before the update (these will become the "old" values)
        $trackableFields = ['status', 'priority', 'assigned_to', 'category'];
        $this->originalValues[$ticket->id] = [];
        
        foreach ($trackableFields as $field) {
            // Get the current value (before update), not the original value
            $this->originalValues[$ticket->id][$field] = $ticket->$field;
        }
    }
    
  public function updated(SupportTicket $ticket)
{
    // Get the stored original values
    $originalValues = $this->originalValues[$ticket->id] ?? [];

    // Track only meaningful changes
    $trackableFields = ['status', 'priority', 'assigned_to', 'category'];
    $changes = [];

    foreach ($trackableFields as $field) {
        $oldValue = $originalValues[$field] ?? null;
        $newValue = $ticket->$field;

        if ($oldValue !== $newValue) {
            // Format values for better display
            if ($field === 'assigned_to') {
                $oldDisplayValue = $oldValue ? User::find($oldValue)?->name ?? 'Unknown' : 'Unassigned';
                $newDisplayValue = $newValue ? User::find($newValue)?->name ?? 'Unknown' : 'Unassigned';
            } else {
                $oldDisplayValue = $oldValue ? ucfirst(str_replace('_', ' ', $oldValue)) : 'Not Set';
                $newDisplayValue = $newValue ? ucfirst(str_replace('_', ' ', $newValue)) : 'Not Set';
            }

            $changes[$field] = [
                'from' => $oldDisplayValue,
                'to' => $newDisplayValue
            ];
        }
    }

    // Only send Slack if at least one *trackable* field changed
    if (!empty($changes)) {
        // Prevent double notification in the same request
        if (!app()->bound("slack.ticket.updated.{$ticket->id}")) {
            app()->instance("slack.ticket.updated.{$ticket->id}", true);

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

    unset($this->originalValues[$ticket->id]);
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
