@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ $plan->name }} - Plan Details</div>

                <div class="card-body">
                    <div class="text-center mb-4">
                        <h2 class="mb-3">${{ number_format($plan->price, 2) }} <span class="fs-6">/{{ $plan->duration }}</span></h2>
                        <p class="mb-3">{{ $plan->description }}</p>
                        <div class="mb-3">{{ $plan->min_inbox }}{{ $plan->max_inbox == 0 ? '+' : ' - ' . $plan->max_inbox }} <strong>Inboxes</strong></div>
                    </div>

                    <div class="features-list">
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
                        <button class="btn btn-primary subscribe-btn" data-plan-id="{{ $plan->id }}">
                            Subscribe Now
                        </button>
                    </div>
                </div>
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
                    window.location.href = response.redirect_url;
                } else {
                    showErrorToast(response.message || 'Failed to initiate subscription');
                }
            },
            error: function(xhr) {
                showErrorToast(xhr.responseJSON?.message || 'Failed to initiate subscription');
            }
        });
    });
});
</script>
@endpush
@endsection