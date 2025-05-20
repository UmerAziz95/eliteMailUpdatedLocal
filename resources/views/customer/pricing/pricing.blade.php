@extends('customer.layouts.app')
@push('styles')
<style>
    .pricing-card {
        background-color: var(--secondary-color);
        box-shadow: rgba(167, 124, 252, 0.529) 0px 5px 10px 0px;
        border-radius: 10px;
        padding: 30px;
        text-align: center;
        transition: 0.3s ease-in-out;
    }

    a {
        text-decoration: none
    }

    .pricing-card:hover {
        box-shadow: 0px 5px 15px rgba(163, 163, 163, 0.15);
        transform: translateY(-10px);
    }

    .popular {
        position: relative;
        background: linear-gradient(270deg, rgba(89, 74, 253, 0.7) 0%, #8d84f5 100%);
        color: white;
    }

    .grey-btn {
        background-color: var(--secondary-color);
        color: #fff;
    }

    .popular::before {
        content: "Most Popular";
        position: absolute;
        top: -10px;
        left: 50%;
        transform: translateX(-50%);
        background: #ffcc00;
        color: #000;
        padding: 5px 10px;
        font-size: 14px;
        font-weight: bold;
        border-radius: 5px;
    }

    .feature-item {
        background-color: rgba(255, 255, 255, 0.1);
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
        position: relative;
    }

    .remove-feature-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        font-size: 12px;
        padding: 2px 5px;
    }

    select option {
        color: #fff;
        background-color: var(--primary-color);
        border: none !important;

    }

    .new-feature-form {
        background-color: rgba(255, 255, 255, 0.05);
        padding: 15px;
        border-radius: 5px;
        margin-top: 10px;
        border: 1px dashed rgba(255, 255, 255, 0.2);
    }
</style>
@endpush
@section('content')
<div class="py-3">
    <div class="row justify-content-center">
        <div class="col-md-12 mt-3">
            <h2 class="text-center mb-4">Choose Your Plan</h2>
            <div class="row" id="plans-container">
                @foreach($plans as $plan)
                <div class="col-md-4 mt-4" id="plan-{{ $plan->id }}">
                    <div class="pricing-card {{ $getMostlyUsed && $plan->id === $getMostlyUsed->id ? 'popular' : '' }}">
                        <h4 class="fw-bold plan-name text-capitalize">{{ $plan->name }}</h4>
                        <h2 class="fw-bold plan-price">${{ number_format($plan->price, 2) }} <span class="fs-6">/{{
                                $plan->duration == 'monthly' ? 'mo' : $plan->duration }} per
                                inboxes</span>
                        </h2>
                        <p class="plan-description text-capitalize">{{ $plan->description }}</p>
                        <hr>
                        <div class="mb-3">
                            {{ $plan->min_inbox }} {{ $plan->max_inbox == 0 ? '+' : '- ' . $plan->max_inbox }}
                            <strong>Inboxes</strong>
                        </div>
                        <ul class="list-unstyled features-list">
                            @foreach ($plan->features as $feature)
                            <li class="mb-2">
                                <i class="fas fa-check text-success"></i>
                                {{ $feature->title }} {{ $feature->pivot->value }}
                            </li>
                            @endforeach
                        </ul>
                        <div class="text-center mt-4">
                            @php
                            $activeSubscription = auth()->user()->subscription()
                            ->where('plan_id', $plan->id)
                            ->where('status', 'active')
                            ->first();
                            @endphp
                            @if($activeSubscription)
                            <button class="btn btn-success" disabled>
                                <i class="fas fa-check me-2"></i>Subscribed Plan
                            </button>
                            @else
                            <button class="btn btn-primary subscribe-btn" data-plan-id="{{ $plan->id }}">
                                Subscribe Now
                            </button>
                            @endif
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
        // reload to new order page
        window.location.href = `/customer/orders/new-order/${planId}`;
        // $.ajax({
        //     url: `/customer/plans/${planId}/subscribe`,
        //     type: 'POST',
        //     data: {
        //         _token: '{{ csrf_token() }}'
        //     },
        //     success: function(response) {
        //         if (response.success) {
        //             // Redirect to Chargebee hosted page
        //             window.location.href = response.hosted_page_url;
        //         } else {
        //             // Show error message
        //             alert(response.message || 'Failed to initiate subscription');
        //         }
        //     },
        //     error: function(xhr) {
        //         alert(xhr.responseJSON?.message || 'Failed to initiate subscription');
        //     }
        // });
    });
});
</script>
@endpush
@endsection