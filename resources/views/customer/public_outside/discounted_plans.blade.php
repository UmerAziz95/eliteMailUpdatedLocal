@extends('customer.layouts.app')
@push('styles')
    <style>
        h1 {
            font-family: "Montserrat";
            -webkit-text-fill-color: transparent;
            background-image: linear-gradient(0deg, #f6f6f7, #7e808f);
            -webkit-background-clip: text;
            background-clip: text;
            margin-top: 0;
            margin-bottom: 0;
            font-size: 3.5em;
            font-weight: 500;
            line-height: 1.2;
        }

        section {
            position: relative;
            height: 100%;
            width: 100%;
        }

        section::after {
            content: '';
            position: absolute;
            height: 100%;
            width: 100%;
            top: -56%;
            left: 0%;
            background-image: url('https://cdn.prod.website-files.com/68271f86a7dc3b457904455f/682746cfd17013f9e1df8695_Qi3BAFZ1SJONCSiPk3u43ulmBhw.png');
            background-position: 50% 80%;
            background-size: 65%;
            background-repeat: no-repeat;
        }

        /* header {
            background-image: url('https://cdn.prod.website-files.com/68271f86a7dc3b457904455f/682746cfd17013f9e1df8695_Qi3BAFZ1SJONCSiPk3u43ulmBhw.png');
            background-position: 50% 80%;
            background-size: 65%;
            background-repeat: no-repeat;
        } */

        p {
            color: var(--light-color);
            font-family: "Montserrat";
        }

        h4, h2 {
            font-family: "Montserrat"
        }

        li {
            font-family: "Montserrat"
        }

        .pricing-card {
            border-radius: 25px;
            padding: 40px 30px;
            transition: 0.3s ease-in-out;
            position: relative;
            z-index: 2;
            background-color: var(--primary-color);
            box-shadow: rgb(133, 133, 133) 0px 0px 8px;
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
            transform: translateY(-10px);
        }

        .popular {
            position: relative;
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
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .price {
            background-color: #000;
            border: 3px solid #fff;
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

        .subscribe-btn span {
            font-family: "Montserrat";
            font-weight: 500
        }

        /* .subscribe-btn {
            box-shadow: rgba(0, 0, 0, 0.4) 0px 2px 4px, rgba(0, 0, 0, 0.3) 0px 7px 13px -3px, rgba(0, 0, 0, 0.2) 0px -3px 0px inset;
        } */

        @media (max-width: 1400px) {
            .pricing-card {
                padding: 40px 30px;
            }

            li {
                font-size: 12px !important
            }

            small {
                font-size: 10px !important
            }
        }

        @media (min-width: 1400px) {
            .plans {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
                gap: 30px;
            }
        }
    </style>
@endpush
@section('content')
    <section>
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
                <header class="text-center pb-5">
                    <h1 class="text-center mb-3">Simple And Fexible Pricing</h1>
                    <p style="font-size: 18px" class="mb-0">Lightning-fast email setup. Unlimited Inboxes.</p>
                    <p style="font-size: 18px">Our email infrastructure is built for <strong>speed</strong> and <strong>reliability</strong>.</p>
                </header>
            </div>
    
            <div class="">
                @if (count($plans) > 0)
                    <div id="plans-container" class="plans px-xl-5">
                        @foreach ($plans as $plan)
                            <div id="plan-{{ $plan->id }}">
                                <div
                                    class="pricing-card h-100 {{ $getMostlyUsed && $plan->id === $getMostlyUsed->id ? '' : '' }}">
                                    <div class="inner-content d-flex flex-column justify-content-between">
                                        <div>
                                            {{-- <div class="d-flex align-items-center justify-content-center mb-0">
                                                <div class="plan-header">
                                                    <h6 class="fs-6 text-uppercase fw-bold">{{ $plan->name }}</h6>
                                                </div>
                                            </div> --}}
                                            <div>
                                                <h4 style="font-size: 28px; font-weight: 500" class="text-white plan-name text-capitalize">
                                                    {{ $plan->name }}
                                                </h4>
                                                <div class="mb-3 ">
                                                    <span style="font-family: Montserrat">
                                                        <span style="font-family: Montserrat">{{ $plan->min_inbox }}
                                                            {{ $plan->max_inbox == 0 ? '+' : '- ' . $plan->max_inbox }}</span>
                                                        Inboxes
                                                    </span>
                                                </div>
    
                                                {{-- <small class="plan-description text-capitalize opacity-75"
                                                    style="line-height: 1px !important">{{ $plan->description }}</small> --}}
                                                <h2 style="font-family: Space Grotesk; font-size: 56px;" class="fw-bold plan-price theme-text mb-4 d-flex gap-1 align-items-center mb-0">
                                                    ${{ number_format($plan->price, 2) }}
                                                    <span class="fw-light text-white mt-3 opacity-75" style="font-size: 17px; font-family: Space Grotesk;">
                                                        /{{ $plan->duration == 'monthly' ? 'mo' : $plan->duration }}
                                                        per Inboxes
                                                    </span>
                                                </h2>
                                                <ul class="list-unstyled features-list">
                                                    @foreach ($plan->features as $feature)
                                                        <li style="font-size: 16px; margin-bottom: 12px"
                                                            class="d-flex align-items-center gap-2">
                                                            <div>
                                                                <img src="https://cdn.prod.website-files.com/68271f86a7dc3b457904455f/682b27d387eda87e2ecf8ba5_checklist%20(1).png"
                                                                    width="25" alt="">
                                                            </div>
                                                            {{ $feature->title }} {{ $feature->pivot->value }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
    
                                            <button class="btn btn-primary py-2 border-0 subscribe-btn w-100 mt-4"
                                                data-plan-id="{{ $plan->id }}">
                                                <span class="btn-text">Get Started Now</span>
                                                <span class="spinner-border spinner-border-sm d-none ms-2" role="status"
                                                    aria-hidden="true"></span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert mt-5 p-4 text-center" style="background-color: var(--second-primary)">
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
                   
                    const token = $('meta[name="csrf-token"]').attr('content');
                    const url = `/customer/discounted/plans/${planId}/subscribe`;

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
