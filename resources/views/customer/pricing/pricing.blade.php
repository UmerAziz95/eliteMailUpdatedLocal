@extends('customer.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Choose Your Plan</h2>
            <div class="row">
                @foreach($plans as $plan)
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-header text-center">
                            <h3>{{ $plan->name }}</h3>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <div class="text-center mb-4">
                                <h2 class="mb-3">${{ number_format($plan->price, 2) }} <span class="fs-6">/{{ $plan->duration }}</span></h2>
                                <p class="mb-3">{{ $plan->description }}</p>
                                <div class="mb-3">{{ $plan->min_inbox }}{{ $plan->max_inbox == 0 ? '+' : ' - ' . $plan->max_inbox }} <strong>Inboxes</strong></div>
                            </div>

                            <div class="features-list flex-grow-1">
                                @if($plan->features->count() > 0)
                                    @foreach($plan->features as $feature)
                                        <div class="feature-item mb-2">
                                            <i class="fas fa-check text-success"></i>
                                            {{ $feature->title }}
                                            @if($feature->pivot->value)
                                                {{ $feature->pivot->value }}
                                            @endif
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-muted text-center">No additional features</p>
                                @endif
                            </div>

                            <div class="text-center mt-4">
                                @php
                                    $userSubscription = auth()->user()->subscription_id;
                                    $isSubscribed = $userSubscription && auth()->user()->subscription_status === 'active';
                                @endphp
                                @if($isSubscribed && auth()->user()->plan_id == $plan->id)
                                    <button class="btn btn-success" disabled>
                                        <i class="fas fa-check me-2"></i>Current Plan
                                    </button>
                                @elseif($isSubscribed)
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-info-circle me-2"></i>Already Subscribed
                                    </button>
                                @else
                                    <button class="btn btn-primary subscribe-btn" data-plan-id="{{ $plan->id }}">
                                        Subscribe Now
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    $('.subscribe-btn').click(function() {
        const planId = $(this).data('plan-id');
        $.ajax({
            url: `/customer/plans/${planId}/subscribe`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to Chargebee hosted page
                    window.location.href = response.hosted_page_url;
                } else {
                    // Show error message
                    alert(response.message || 'Failed to initiate subscription');
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Failed to initiate subscription');
            }
        });
    });
});
</script>
@endpush
@endsection
