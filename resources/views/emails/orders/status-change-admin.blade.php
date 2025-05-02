@component('mail::message')
# Order Status Changed

Order #{{ $order->id }} status has been changed from **{{ ucfirst($oldStatus) }}** to **{{ ucfirst($newStatus) }}**.

**Customer Details:**
- Name: {{ $user->name }}
- Email: {{ $user->email }}

@if($reason)
**Reason for Change:** {{ $reason }}
@endif

@component('mail::button', ['url' => config('app.url').'/admin/orders/'.$order->id.'/view'])
View Order Details
@endcomponent

Best regards,<br>
{{ config('app.name') }}
@endcomponent