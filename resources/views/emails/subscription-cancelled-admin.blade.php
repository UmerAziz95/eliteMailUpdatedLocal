@component('mail::message')
# Subscription Cancellation Alert

A subscription has been cancelled by a customer.

**Customer Details:**  
Name: {{ $user->name }}  
Email: {{ $user->email }}  
Phone: {{ $user->phone }}

**Subscription Details:**  
Plan: {{ $subscription->plan->name ?? 'N/A' }}  
Subscription ID: {{ $subscription->chargebee_subscription_id }}  
Cancellation Date: {{ $subscription->cancellation_at ? \Carbon\Carbon::parse($subscription->cancellation_at)->format('F j, Y') : 'N/A' }}  
End Date: {{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('F j, Y') : 'N/A' }}

**Cancellation Reason:**  
{{ $reason }}

@component('mail::button', ['url' => url('/admin/subscriptions')])
View Subscription Details
@endcomponent

Best regards,  
{{ config('app.name') }}
@endcomponent