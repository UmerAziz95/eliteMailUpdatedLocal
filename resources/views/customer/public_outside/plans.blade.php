@extends('customer.layouts.app')
@push('styles')

<style>
    .pricing-card {
        background-color: var(--secondary-color);
        box-shadow: rgba(167, 124, 252, 0.529) 0px 5px 10px 0px;
        border-radius: 10px;
        padding: 30px 60px;
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
        font-size: 12px;
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
<div class="py-3 ">
    <div id="subscription-loader" class="d-none position-absolute top-50 start-50 translate-middle text-center"
        style="z-index: 9999; min-width: 200px;">
        <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <div class="mt-2 fw-bold text-dark" style="font-size: 1rem;">Loading...</div>
    </div>

 <div class="col-12">
    <div>
        <div class="mb-3 d-flex justify-content-center align-items-center">
            <h2 class="mb-0 fw-bold"
                style="
                    font-family: 'Poppins', sans-serif;
                    font-size: 2.2rem;
                    letter-spacing: 2px;
                    text-transform: uppercase;
                    color: #fff;
                    text-shadow: 2px 2px 8px #5e50ee, 0 2px 8px #00000033;
                    background: linear-gradient(90deg, #fff 30%, #ffcc00 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    background-clip: text;
                ">
             
                Select Your Plan
             
            </h2>
        </div>
    </div>
</div>

    <div class="row justify-content-center align-items-center m-auto">
        <div class="col-md-12 mt-3 d-flex justify-content-center align-items-center">
            {{-- <h2 class="text-center mb-4">Choose Your Plan</h2> --}}
            @if(count($plans)>0)
            <div class="row g-5 mt-5" id="plans-container">
                @foreach($plans as $plan)
                <div class="col-md-4 mt-4 " id="plan-{{ $plan->id }}">
                    <div class="pricing-card {{ $getMostlyUsed && $plan->id === $getMostlyUsed->id ? 'popular' : '' }}">
                        <h4 class="fw-bold plan-name text-capitalize fs-6">{{ $plan->name }}</h4>
                        <h2 class="fw-bold plan-price fs-4">${{ number_format($plan->price, 2) }} <span class="fw-light"
                                style="font-size: 12px">/{{
                                $plan->duration == 'monthly' ? 'mo' : $plan->duration }} per
                                inboxes</span>
                        </h2>
                        <small class="plan-description text-capitalize opacity-75">{{ $plan->description }}</small>
                        <hr>
                        <div class="mb-3 ">
                            <span>
                                {{ $plan->min_inbox }} {{ $plan->max_inbox == 0 ? '+' : '- ' . $plan->max_inbox }}
                                <strong>Inboxes</strong>
                            </span>
                        </div>
                        <ul class="list-unstyled features-list">
                            @foreach ($plan->features as $feature)
                            <li style="font-size: 13px" class="mb-2">
                                <i class="fas fa-check text-success"></i>
                                {{ $feature->title }} {{ $feature->pivot->value }}
                            </li>
                            @endforeach
                        </ul>
                        <div class="text-center mt-4">

                            <button class="btn btn-primary subscribe-btn" data-plan-id="{{ $plan->id }}">
                                <span class="btn-text">Subscribe Now</span>
                                <span class="spinner-border spinner-border-sm d-none ms-2" role="status"
                                    aria-hidden="true"></span>
                            </button>


                        </div>
                    </div>
                </div>
                @endforeach
            </div>
            @else

            <div class="alert mt-5 p-4 text-center" style="background-color: #7367ef">
                <p
                    style="font-family: 'Poppins', sans-serif; font-weight: 900; font-size: 1.6rem; letter-spacing: 2px; text-transform: uppercase; margin: 0; color: #000000; text-shadow: 1px 1px 1px rgba(0,0,0,0.1);">
                    No plans available at the moment.
                </p>
                <p
                    style="font-family: 'Poppins', sans-serif; font-weight: 700; font-size: 1.1rem; margin-top: 15px; color: #000000;">
                    Please check back later.
                </p>
            </div>

            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function () {
        $('.subscribe-btn').click(function () {
            const $btn = $(this);
            const planId = $btn.data('plan-id');
            const encrypted = @json($encrypted);
            const token = $('meta[name="csrf-token"]').attr('content');
            const url = `/customer/plans/${planId}/subscribe/${encrypted}`;

            // Disable button and show spinner
            $btn.prop('disabled', true);
            $btn.find('.btn-text').text('Processing...');
            $btn.find('.spinner-border').removeClass('d-none');

            $.ajax({
                url: url,
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token
                },
                success: function (response) {
                    // Restore button state before redirect
                    $btn.prop('disabled', false);
                    $btn.find('.btn-text').text('Subscribe Now');
                    $btn.find('.spinner-border').addClass('d-none');

                    // Redirect
                    window.location.href = response.hosted_page_url;
                },
               error: function (xhr) {
    const errorCode = xhr.status;
    let errorMsg = 'Subscription failed. Please try again.';

    // Try to get a message from the response JSON
    if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMsg = xhr.responseJSON.message;
    }

        toastr.info("Operation failed!")
        if(errorCode==419){
            toastr.info("Oops! One time session has expired Try again.")
            setTimeout(() => {
                window.location.reload();
            }, 4000);
        }
    // Restore button state
    $btn.prop('disabled', false);
    $btn.find('.btn-text').text('Subscribe Now');
    $btn.find('.spinner-border').addClass('d-none');
    }
            });
        });
    }); 
</script>

@endpush
@endsection 