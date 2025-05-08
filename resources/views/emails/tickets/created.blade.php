@component('mail::message')
# New Support Ticket Created

@if($assignedTo)
Dear {{ $assignedTo->name }},

A new support ticket has been assigned to you.
@else
Dear Staff Member,

A new support ticket requires attention.
@endif

**Ticket Details:**
- Ticket Number: #{{ $ticket->ticket_number }}
- Subject: {{ $ticket->subject }}
- Category: {{ ucfirst($ticket->category) }}
- Priority: {{ ucfirst($ticket->priority) }}
- Created By: {{ $creator->name }}

**Description:**  
{!! $ticket->description !!}

@if($ticket->attachments && count($ticket->attachments) > 0)
**Attachments:**  
@foreach($ticket->attachments as $attachment)
- {{ basename($attachment) }}
@endforeach
@endif

Best regards,  
{{ config('app.name') }}
@endcomponent