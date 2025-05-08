@component('mail::message')
# Order Status Changed

Order #{{ $order->id }} status has been changed from **{{ ucfirst($oldStatus) }}** to **{{ ucfirst($newStatus) }}**.

**Customer Details:**
- Name: {{ $user->name }}
- Email: {{ $user->email }}

@if($reason)
**Reason for Change:** {{ $reason }}
@endif


Best regards,<br>
{{ config('app.name') }}
@endcomponent