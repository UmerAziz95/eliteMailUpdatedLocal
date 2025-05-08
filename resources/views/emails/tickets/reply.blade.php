@component('mail::message')
# New Ticket Reply

Dear {{ $assignedStaff->name }},

{{ $repliedBy->name }} has replied to ticket #{{ $ticket->ticket_number }}.

**Subject:** {{ $ticket->subject }}  
**Category:** {{ ucfirst($ticket->category) }}  
**Priority:** {{ ucfirst($ticket->priority) }}  
**Status:** {{ ucfirst($ticket->status) }}

**Reply:**  
{!! $reply->message !!}

@if($reply->attachments && count($reply->attachments) > 0)
**Attachments:**  
@foreach($reply->attachments as $attachment)
- {{ basename($attachment) }}
@endforeach
@endif

Best regards,  
{{ config('app.name') }}
@endcomponent