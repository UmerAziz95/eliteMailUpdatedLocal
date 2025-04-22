@component('mail::message')
# Subscription Cancelled

Dear {{ $user->name }},

Your subscription has been cancelled successfully. Here are the details:

**Plan:** {{ $subscription->plan->name ?? 'N/A' }}  
**Cancellation Date:** {{ $subscription->cancellation_at ? \Carbon\Carbon::parse($subscription->cancellation_at)->format('F j, Y') : 'N/A' }}  
**End Date:** {{ $subscription->end_date ? \Carbon\Carbon::parse($subscription->end_date)->format('F j, Y') : 'N/A' }}

Your access to the services will continue until the end date mentioned above.

If you change your mind or wish to subscribe again in the future, you can always visit our pricing page to select a new plan.

Thank you for being our customer.

Best regards,  
{{ config('app.name') }}
@endcomponent