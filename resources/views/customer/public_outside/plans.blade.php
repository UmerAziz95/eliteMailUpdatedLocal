@extends('customer.layouts.app')
@push('styles')
    <style>
        section::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background-image: url('https://img.freepik.com/premium-vector/futuristic-glowing-low-polygonal-mail-envelopes-copy-space-text-dark-blue-background_67515-685.jpg?ga=GA1.1.1410736458.1721019759&semt=ais_hybrid&w=740');
            /* background-position: center; */
            /* background-size: 100%; */
            opacity: .2;
        }

        .pricing-card {
            /* background-color: var(--secondary-color); */
            /* background-color: rebeccapurple; */
            /* box-shadow: rgba(167, 124, 252, 0.529) 0px 5px 10px 0px; */
            /* box-shadow: rgb(204, 219, 232) 3px 3px 6px 0px inset, rgba(255, 255, 255, 0.5) -3px -3px 6px 1px inset; */
            border-radius: 45px;
            padding: 40px 0px 60px 0px;
            transition: 0.3s ease-in-out;
            position: relative;
            /* overflow: hidden; */
            z-index: 2;
        }

        .pricing-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 90%;
            background-image: url('https://img.freepik.com/free-photo/3d-rendering-abstract-black-white-background_23-2150913897.jpg?ga=GA1.1.1410736458.1721019759&semt=ais_hybrid&w=740');
            background-position: center;
            background-size: 150%;
            z-index: 2;
            border-radius: 45px;
            opacity: .05;
            box-shadow: rgba(0, 0, 0, 0.4) 0px 2px 4px, rgba(0, 0, 0, 0.3) 0px 7px 13px -3px, rgba(0, 0, 0, 0.2) 0px -3px 0px inset;
        }

        .pricing-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 90%;
            /* background: linear-gradient(303deg, rgba(255, 255, 255, 1) 0%, rgb(98, 89, 234) 100%); */
            background-color: var(--secondary-color);
            z-index: 1;
            border-radius: 45px;
            box-shadow: rgba(0, 0, 0, 0.4) 0px 2px 4px, rgba(0, 0, 0, 0.3) 0px 7px 13px -3px, rgba(0, 0, 0, 0.2) 0px -3px 0px inset;
        }

        .inner-content {
            background-color: #000;
            height: 100%;
            width: 100%;
            position: relative;
            top: 0;
            right: -15%;
            z-index: 3;
            border-radius: 50px;
            padding: 0 20px 20px 20px;
            box-shadow: rgba(0, 0, 0, 0.4) 0px 2px 4px, rgba(0, 0, 0, 0.3) 0px 7px 13px -3px, rgba(0, 0, 0, 0.2) 0px -3px 0px inset;
        }

        .plan-header {
            border-radius: 0 0 25px 25px;
            background-color: var(--second-primary);
            width: fit-content;
            box-shadow: rgba(0, 0, 0, 0.4) 0px 2px 4px, rgba(0, 0, 0, 0.3) 0px 7px 13px -3px, rgba(0, 0, 0, 0.2) 0px -3px 0px inset;
            padding: 10px;
            color: #fff
        }

        a {
            text-decoration: none
        }

        .pricing-card:hover {
            /* box-shadow: 0px 5px 15px rgba(163, 163, 163, 0.15); */
            transform: translateY(-10px);
        }

        .popular {
            position: relative;
            /* background: linear-gradient(270deg, rgba(89, 74, 253, 0.7) 0%, #8d84f5 100%); */
            background-color: rgba(89, 74, 253, 0.236);
            border: 1px solid var(--second-primary);
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

        .plans {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 90px;
        }

        .price {
            /* background: linear-gradient(303deg,rgba(255, 255, 255, 1) 0%, rgb(101, 91, 234) 100%); */
            background-color: #000;
            border: 3px solid var(--secondary-color);
            width: fit-content;
            height: 150px;
            width: 150px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
            bottom: -55px;
        }

        .price::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid transparent;
            /* border-top-color: var(--second-primary); */
            animation: none;
            pointer-events: none;
            transition: opacity 0.3s;
            opacity: 0;
        }

        .pricing-card:hover .price::before {
            animation: spin 5s linear infinite;
            opacity: 1;
        }

        .pricing-card:hover .price {
            animation: wobble 2s linear infinite alternate;
            opacity: 1;
        }

        @keyframes wobble {
            0% {
                transform: scale(.9)
            }

            100% {
                transform: scale(1)
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
                border-top-color: var(--second-primary);
                border-right-color: var(--second-primary);
            }
            
            40% {
                /* transform: rotate(0deg); */
                border-top-color: var(--second-primary);
                border-right-color: var(--second-primary);
            }

            100% {
                transform: rotate(360deg);
                border-top-color: orange;
                border-right-color: orange;
                /* border: 3px solid var(--secondary-color) */
            }
        }

        .subscribe-btn {
            box-shadow: rgba(0, 0, 0, 0.4) 0px 2px 4px, rgba(0, 0, 0, 0.3) 0px 7px 13px -3px, rgba(0, 0, 0, 0.2) 0px -3px 0px inset;
            margin-bottom: 6rem
        }

        @media (max-width: 567px) {
            .inner-content {
                right: -5%
            }
        }

        @media (max-width: 1400px) {
            .pricing-card {
                padding: 40px 0px 60px 0px;
            }

            li {
                font-size: 10px !important
            }

            small {
                font-size: 10px !important
            }
        }

        @media (min-width: 1400px) {
            .plans {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 100px;
            }
        }
    </style>
@endpush
@section('content')
    <section class="h-100">
        <div class="d-flex align-items-center justify-content-center">
            <img src="{{asset('assets/logo/redo.png')}}" width="200" class="mb-3" alt="">
        </div>
        <div class="container py-3">
            <div id="subscription-loader" class="d-none position-absolute top-50 start-50 translate-middle text-center"
                style="z-index: 9999; min-width: 200px;">
                <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <div class="mt-2 fw-bold text-dark" style="font-size: 1rem;">Loading...</div>
            </div>

            <div class="col-12">
                {{-- <div>
                    <div class="d-flex justify-content-center align-items-center">
                        <h2 class="mb-5 fw-bold"
                            style="
                        font-size: 2.2rem;
                        text-transform: uppercase;
                        color: var(--white-color);
                        text-shadow: 2px 1px 2px #887FF1;
                    ">
                            Select Your Plan
                        </h2>
                    </div>
                </div> --}}
            </div>

            <div class="">
                {{-- <h2 class="text-center mb-4">Choose Your Plan</h2> --}}
                @if (count($plans) > 0)
                    <div id="plans-container" class="plans">
                        @foreach ($plans as $plan)
                            <div id="plan-{{ $plan->id }}">
                                <div class="pricing-card h-100">
                                    <div class="inner-content d-flex flex-column justify-content-between">

                                        <div>
                                            <div class="d-flex align-items-center justify-content-center mb-0">
                                                <div class="plan-header">
                                                    <h6 class="text-uppercase fw-bold" style="font-size: 13px">
                                                        {{ $plan->name }}</h6>
                                                </div>
                                            </div>

                                            <div>
                                                {{-- <h4 class="fw-bold text-white plan-name text-uppercase fs-6">{{ $plan->name }}</h4> --}}

                                                {{-- <small class="plan-description text-capitalize opacity-75"
                                                    style="line-height: 1px !important">{{ $plan->description }}</small> --}}
                                                <hr>
                                                <div class="mb-3 ">
                                                    <span>
                                                        <span style="color: #FFCC00">{{ $plan->min_inbox }}
                                                            {{ $plan->max_inbox == 0 ? '+' : '- ' . $plan->max_inbox }}</span>
                                                        <strong>Inboxes</strong>
                                                    </span>

                                                    <div class="{{ $getMostlyUsed && $plan->id === $getMostlyUsed->id ? '' : 'd-none' }}"
                                                        style="position: absolute; top: 57px; right: 0;">
                                                        <img src="{{ asset('assets/logo/popular.png') }}" width="60"
                                                            alt="">
                                                    </div>
                                                </div>
                                                <ul class="list-unstyled features-list">
                                                    @foreach ($plan->features as $feature)
                                                        <li style="font-size: 11px"
                                                            class="mb-2 d-flex align-items-center gap-1">
                                                            <div>
                                                                {{-- <div class="d-flex align-items-center justify-content-center"
                                                                    style="height: 20px; width: 20px; border-radius: 50px; background-color: rgb(39, 200, 39);">
                                                                    <i class="fas fa-check"></i>
                                                                </div> --}}
                                                                <div>
                                                                    <img src="https://cdn-icons-png.flaticon.com/128/5290/5290058.png"
                                                                        width="20" alt="">
                                                                </div>
                                                            </div>
                                                            {{ $feature->title }} {{ $feature->pivot->value }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>

                                            <button class="btn btn-primary border-0 subscribe-btn w-100"
                                                data-plan-id="{{ $plan->id }}">
                                                <span class="btn-text">Subscribe Now</span>
                                                <span class="spinner-border spinner-border-sm d-none ms-2" role="status"
                                                    aria-hidden="true"></span>
                                            </button>
                                        </div>


                                        <div>
                                            <div class="price mt-4">
                                                <h2 class="fw-bold plan-price fs-4 d-flex flex-column align-items-center"
                                                    style="color: #7367ef">
                                                    ${{ number_format($plan->price, 2) }}
                                                    <span class="fw-light opacity-75" style="font-size: 12px;">
                                                        /{{ $plan->duration == 'monthly' ? 'mo' : $plan->duration }}
                                                        per Inboxes
                                                    </span>
                                                </h2>
                                            </div>
                                        </div>
                                        {{-- <div class="d-flex justify-content-between align-items-start gap-3">
    
    
                                        </div> --}}
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
    </section>

    @push('scripts')
        <script>
            $(document).ready(function() {
                $('.subscribe-btn').click(function() {
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
                        success: function(response) {
                            // Restore button state before redirect
                            $btn.prop('disabled', false);
                            $btn.find('.btn-text').text('Subscribe Now');
                            $btn.find('.spinner-border').addClass('d-none');

                            // Redirect
                            window.location.href = response.hosted_page_url;
                        },
                        error: function(xhr) {
                            const errorCode = xhr.status;
                            let errorMsg = 'Subscription failed. Please try again.';

                            // Try to get a message from the response JSON
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMsg = xhr.responseJSON.message;
                            }

                            toastr.info("Operation failed!")
                            if (errorCode == 419) {
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
