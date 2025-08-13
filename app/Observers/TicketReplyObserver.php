<?php

namespace App\Observers;

use App\Models\TicketReply;
use App\Services\SlackNotificationService;
use Illuminate\Support\Facades\Log;

class TicketReplyObserver
{
    /**
     * Handle the TicketReply "created" event.
     */
    public function created(TicketReply $reply): void
    {
        try {
            // Send Slack notification for new ticket reply
            SlackNotificationService::sendSupportTicketReplyNotification($reply);
            
            Log::info('Slack notification sent for ticket reply created', [
                'ticket_id' => $reply->ticket_id,
                'reply_id' => $reply->id,
                'user_id' => $reply->user_id,
                'is_internal' => $reply->is_internal
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Slack notification for ticket reply created: ' . $e->getMessage(), [
                'ticket_id' => $reply->ticket_id,
                'reply_id' => $reply->id,
                'exception' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the TicketReply "updated" event.
     */
    public function updated(TicketReply $reply): void
    {
        // Optional: You might want to send notifications for reply updates too
        // For now, we'll skip this to avoid too many notifications
    }

    /**
     * Handle the TicketReply "deleted" event.
     */
    public function deleted(TicketReply $reply): void
    {
        // Optional: You might want to send notifications for reply deletions
        // For now, we'll skip this
    }

    /**
     * Handle the TicketReply "restored" event.
     */
    public function restored(TicketReply $reply): void
    {
        //
    }

    /**
     * Handle the TicketReply "force deleted" event.
     */
    public function forceDeleted(TicketReply $reply): void
    {
        //
    }
}
