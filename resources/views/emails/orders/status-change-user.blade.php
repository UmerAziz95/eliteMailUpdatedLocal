@component('mail::message')
# Order Status Update

Dear {{ $user->name }},

Your order #{{ $order->id }} status has been updated from **{{ ucfirst($oldStatus) }}** to **{{ ucfirst($newStatus) }}**.

@if($reason)
**Reason:** {{ $reason }}
@endif

@component('mail::button', ['url' => config('app.url').'/orders/'.$order->id])
View Order Details
@endcomponent

Thank you for using our service.

Best regards,<br>
{{ config('app.name') }}
@endcomponent