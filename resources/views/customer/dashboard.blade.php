@extends('customer.layouts.app')

@section('title', 'Dashboard')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .swiper {
            width: 100%;
            max-width: 600px;
        }

        .swiper-slide {
            background-color: var(--second-primary);
            border-radius: 6px;
            color: #fff
        }

        .swiper-slide img {
            max-width: 100%;
            height: auto;
        }

        span {
            font-size: 13px;
        }

        h5 {
            font-weight: 600
        }

        .slider_span_bg {
            background-color: #33333332;
        }

        .swiper-pagination {
            top: 10px !important;
            left: 260px !important
        }

        .swiper-pagination-bullet-active {
            background-color: #fff !important;
        }

        .bg-label-info {
            background-color: rgba(0, 255, 255, 0.143);
        }

        .bg-label-primary {
            background-color: rgba(79, 0, 128, 0.203);
        }

        .divider.divider-vertical {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: unset;
            block-size: 100%;
        }

        .divider {
            --bs-divider-color: #ffffff30;
            display: block;
            overflow: hidden;
            margin-block: 1rem;
            margin-inline: 0;
            text-align: center;
            white-space: nowrap;
        }

        .divider.divider-vertical:has(.badge-divider-bg)::before {
            inset-inline-start: 49%;
        }

        .divider.divider-vertical::before {
            inset-block: 0 50%;
        }

        .divider.divider-vertical::before,
        .divider.divider-vertical::after {
            position: absolute;
            border-inline-start: 1px solid var(--bs-divider-color);
            content: "";
            inset-inline-start: 50%;
        }

        .divider.divider-vertical:has(.badge-divider-bg)::after,
        .divider.divider-vertical:has(.badge-divider-bg)::before {
            inset-inline-start: 49%;
        }

        .divider.divider-vertical::after {
            inset-block: 50% 0;
        }

        .divider.divider-vertical::before,
        .divider.divider-vertical::after {
            position: absolute;
            border-inline-start: 1px solid var(--bs-divider-color);
            content: "";
            inset-inline-start: 50%;
        }

        .divider.divider-vertical .divider-text {
            z-index: 1;
            padding: .5125rem;
            background-color: var(--secondary-color);
        }

        .divider .divider-text {
            position: relative;
            display: inline-block;
            font-size: .9375rem;
            padding-block: 0;
            padding-inline: 1rem;
        }

        .divider.divider-vertical .divider-text .badge-divider-bg {
            border-radius: 50%;
            background-color: #8c8c8c47;
            color: var(--extra-light);
            /* color: var(--bs-secondary-color); */
            font-size: .75rem;
            padding-block: .313rem;
            padding-inline: .252rem;
        }

        .inbox {
            background-color: var(--slide-bg);
            border: 1px solid var(--second-primary);
            backdrop-filter: blur(10px);
            border-radius: 6px;
        }

        /* Draft Alert Animations */
        .draft-alert {
            animation: slideInFromLeft 0.8s ease-out, pulse 2s infinite;
            position: relative;
            overflow: hidden;
            margin-top: 15px;
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }

        .draft-alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shimmer 3s infinite;
        }

        /* Smooth transitions for alert switching */
        .draft-alert.fade-out {
            opacity: 0;
            transform: translateX(-100%);
        }

        .draft-alert.fade-in {
            opacity: 1;
            transform: translateX(0);
        }

        /* Rejected Orders Alert - Red variant */
        .alert-danger.draft-alert {
            border-color: #dc3545 !important;
        }

        .alert-danger.draft-alert .alert-link {
            color: #ffcccb !important;
            text-decoration: none;
        }

        .alert-danger.draft-alert .alert-link::after {
            background-color: #dc3545 !important;
        }

        .alert-danger.draft-alert .alert-link:hover {
            color: #fff !important;
        }

        /* .draft-alert:hover {
                                    transform: translateY(-2px);
                                    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
                                    transition: all 0.3s ease;
                                } */

        .draft-alert .alert-icon {
            animation: bounce 1s infinite;
        }

        .draft-alert .alert-link {
            position: relative;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .draft-alert .alert-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: -2px;
            left: 0;
            background-color: #ffc107;
            transition: width 0.3s ease;
        }

        .draft-alert .alert-link:hover::after {
            width: 100%;
        }

        .draft-alert .btn-close {
            transition: all 0.3s ease;
        }

        .draft-alert .btn-close:hover {
            transform: rotate(90deg) scale(1.1);
        }

        @keyframes slideInFromLeft {
            0% {
                opacity: 0;
                transform: translateX(-100%);
            }

            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                /* border-color: var(--second-primary); */
            }

            50% {
                /* border-color: #ffc107; */
                box-shadow: 0 0 20px rgba(255, 193, 7, 0.3);
            }
        }

        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-8px);
            }

            60% {
                transform: translateY(-4px);
            }
        }

        @keyframes shimmer {
            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }

        /* Fade out animation for dismiss */
        .draft-alert.fade {
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }

        .draft-alert.fade:not(.show) {
            opacity: 0;
            transform: translateX(-100%);
        }

        /* h6 {
                                font-family: "Montserrat"
                            } */
    </style>
@endpush

@section('content')

    <!-- Draft Orders Notification -->
    @if (isset($draftOrders) && $draftOrders > 0)
        <div id="draftAlert" class="alert alert-warning alert-dismissible fade show draft-alert py-2 rounded-1" role="alert"
            style="background-color: rgba(255, 166, 0, 0.414); color: #fff; border: 2px solid orange;">
            <i class="ti ti-alert-triangle me-2 alert-icon"></i>
            <strong>Draft Order{{ $draftOrders != 1 ? 's' : '' }} Alert:</strong>
            You have {{ $draftOrders }} draft order{{ $draftOrders != 1 ? 's' : '' }}.
            <a href="{{ route('customer.orders') }}" class="text-warning alert-link">View your order{{ $draftOrders != 1 ? 's' : '' }}</a> to complete {{ $draftOrders != 1 ? 'them' : 'it' }}.
            <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert"
                aria-label="Close"></button>
        </div>
    @endif

    <!-- Rejected Orders Notification (Initially Hidden) -->
    @if (isset($rejectedOrders) && $rejectedOrders > 0)
        <div id="rejectedAlert" class="alert alert-danger alert-dismissible fade draft-alert py-2 rounded-1 mt-2" 
             role="alert" style="background-color: rgba(255, 82, 82, 0.414); color: #fff; border: 2px solid #dc3545; display: none;">
            <i class="ti ti-x-circle me-2 alert-icon"></i>
            <strong>Rejected Order{{ $rejectedOrders != 1 ? 's' : '' }} Alert:</strong>
            You have {{ $rejectedOrders }} rejected order{{ $rejectedOrders != 1 ? 's' : '' }}.
            <a href="{{ route('customer.orders') }}" class="text-danger alert-link" style="color: #ffcccb !important;">View your orders</a> for more details.
            <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert"
                aria-label="Close"></button>
        </div>
    @endif


    <button class="tour-btn border-0 animate-gradient pb-1" id="start-tour">
        <i class="fa-solid fa-arrow-down text-white" style="font-size: 16px"></i>
    </button>


    <section class="py-0" data-page="dashboard">

        <h4>Dashboard</h4>
        <p>Welcome to your dashbaord. Here you can see your stats.</p>

        <div class="reward p-3 rounded-2 mb-4"
            style="background-color: #4a3aff36; border: 1px solid var(--second-primary);">
            <h5>
                Earn Rewards with Project Inbox!
            </h5>
            <p>Signup for our affiliate program today and earn monthly recurring revenue for LIFETIME of your referrals!</p>
            <a href="https://64gytyjw1wv.typeform.com/to/RekicwnC">
                <button class="btn btn-primary btn-sm rounded-1 border-0 px-3">Join Now!</button>
            </a>
        </div>

        <div class="row gy-4">
            <!-- Inbox Statistics -->
            <div class="col-md-6 col-lg-4">
                <div class="inbox overflow-hidden h-100">
                    <div class="p-3 h-100 d-flex flex-column justify-content-between">
                        <div class="d-flex align-items-center gap-1 mb-3">
                            <span
                                style="border: 1px solid var(--second-primary); border-radius: 50px; padding: 6px; color: var(--second-primary); height: 30px; width: 30px;"
                                class="d-flex align-items-center justify-content-center">
                                <i class="ti ti-inbox fs-6"></i>
                            </span>
                            <div>
                                <h6 class="mb-0 fw-bold">Inbox Status</h6>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div style="background-color: #8d8d8d2c"
                                class="d-flex align-items-center gap-2 rounded-1 py-1 px-2">
                                <div>
                                    <div class="d-flex align-items-center justify-content-center"
                                        style="border: 1px solid #8d8d8de5; border-radius: 50px; height: 30px; width: 30px;">
                                        <i class="fa-solid fa-inbox" style="font-size: 14px"></i>
                                    </div>
                                </div>
                                <div class="d-flex flex-column gap-0">
                                    <small class="opacity-75">
                                        Total Inboxes
                                    </small>
                                    <h5 style="font-size: 16px" class="mb-0 number">{{ $totalInboxes ?? 0 }}</h5>
                                </div>
                            </div>

                            {{-- <span style="background-color: var(--second-primary)"
                                class="text-white px-2 py-1 rounded-1 d-flex align-items-center gap-1">
                                <i class="ti ti-inbox-off"></i> Total
                            </span> --}}
                        </div>


                        <div class="progress bg-white bg-opacity-25" style="height: 5px;">
                            <div class="progress-bar bg-danger rounded-5"
                                style="width: 50%; background-color: rgb(7, 191, 62) !important"></div>
                        </div>


                        <div class="row g-0 mt-1">
                            <div class="col-6">
                                <div style="background-color: #2ae6112c; width: fit-content;"
                                    class="px-2 py-1 rounded-1 d-flex align-items-center gap-2">
                                    <div>
                                        <div class="d-flex align-items-center justify-content-center"
                                            style="height: 30px; width: 30px; border-radius: 50px; border: 1px solid #04e59ad6;">
                                            <i class="fa-solid fa-inbox success" style="font-size: 12px"></i>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column gap-0">
                                        <small class="d-block success">Active</small>
                                        <h5 style="font-size: 16px" class="mb-0 success number">{{ $activeInboxes ?? 0 }}
                                        </h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 d-flex justify-content-end">
                                <div style="background-color: #e6b11138; width: fit-content;"
                                    class="px-2 py-1 rounded-1 d-flex align-items-center gap-2">

                                    <div>
                                        <div class="d-flex align-items-center justify-content-center"
                                            style="height: 30px; width: 30px; border-radius: 50px; border: 1px solid rgba(255, 166, 0, 0.905);">
                                            <i class="fa-solid fa-spinner text-warning" style="font-size: 12px"></i>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-column gap-0">
                                        <small class="d-block text-warning">Pending/Issue</small>
                                        <h5 style="font-size: 16px" class="mb-0 text-warning number">
                                            {{ $pendingInboxes ?? 0 }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="col-md-6 col-lg-5">
                <div class="card recent h-100 p-3">
                    <div class="border-0 pb-0 d-flex justify-content-between">
                        <div class="mb-0">
                            <h6 class="mb-1 fw-bold d-flex align-items-center gap-1">
                                <span
                                    style="border: 1px solid var(--second-primary); border-radius: 50px; padding: 6px; color: var(--second-primary); height: 30px; width: 30px;"
                                    class="d-flex align-items-center justify-content-center">
                                    <i class="fa-solid fa-binoculars"></i>
                                </span>
                                Most Recent Subscription Overview
                            </h6>
                            <p class="small mb-2">Subscription and billing details</p>
                        </div>
                    </div>
                    <div class="pt-0">
                        <div class="row g-0">
                            <div class="col-12">
                                <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
                                    <h5 class="mb-0 number">${{ $nextBillingInfo['amount'] ?? '0.00' }}</h5>
                                    @if (isset($subscription) && $subscription->status === 'active')
                                        <div class="badge rounded bg-label-success success">Active</div>
                                    @else
                                        <div class="badge rounded bg-label-warning">No Active Plan</div>
                                    @endif
                                </div>
                                <div class="d-flex flex-column gap-1">
                                    <a href="{{ route('customer.subscriptions.view') }}" class="d-flex justify-content-between align-items-center">
                                        <small style="font-size: 12px" class="theme-text">Next Billing Date</small>
                                        <small
                                            class="mb-0">{{ isset($subscription) && $subscription->next_billing_date
                                                ? \Carbon\Carbon::parse($subscription->next_billing_date)->format('M d, Y')
                                                : 'N/A' }}</small>
                                    </a>
                                    <a href="{{ route('customer.subscriptions.view') }}" class="d-flex justify-content-between align-items-center">
                                        <small style="font-size: 12px" class="theme-text">Last Billing Date</small>
                                        <small
                                            class="mb-0">{{ isset($subscription) && $subscription->last_billing_date
                                                ? \Carbon\Carbon::parse($subscription->last_billing_date)->format('M d, Y')
                                                : 'N/A' }}</small>
                                    </a>
                                    <a href="{{ route('customer.subscriptions.view') }}" class="d-flex justify-content-between align-items-center">
                                        <small style="font-size: 12px" class="theme-text">Billing Period</small>
                                        <small class="mb-0">Monthly</small>
                                    </a>
                                </div>
                            </div>
                            <div class="col-12">
                                <hr class="my-2">
                                {{-- <h6 class="mb-3" >Plan Features</h6> --}}

                                @if (isset($subscription) && $subscription->plan && $subscription->plan->features)
                                    @php
                                        $featuresList = $subscription->plan->features
                                            ->map(function ($feature) {
                                                return $feature->title . ' ' . $feature->pivot->value;
                                            })
                                            ->implode(', ');
                                    @endphp

                                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $featuresList }}"
                                        class="d-inline-flex align-items-center">
                                        <i class="ti ti-info-circle theme-text fs-6 me-1" style="cursor: pointer"></i>
                                        Hover to view features
                                    </span>
                                @else
                                    <p class="opacity-75 mb-0">No active plan features</p>
                                @endif
                            </div>

                        </div>
                    </div>
                </div>
            </div>


            <div class="col-md-6 col-lg-3">
                <div class="card review h-100">
                    <div class="card-header border-0 px-3 pt-3 pb-0">
                        <h6 class="mb-1 fw-bold d-flex align-items-center gap-1">
                            <span
                                style="border: 1px solid var(--second-primary); border-radius: 50px; padding: 6px; color: var(--second-primary); height: 30px; width: 30px;"
                                class="d-flex align-items-center justify-content-center">
                                <i class="fa-brands fa-first-order"></i>
                            </span>
                            Orders Overview
                        </h6>
                        <div style="background-color: #8d8d8d2c; border: 1px solid #8d8d8da0; padding: 0px 12px 5px 12px;"
                            class="rounded-1 d-flex align-items-center justify-content-between mt-3">
                            <div>
                                <small class="mb-0 opacity-75">All Time Orders</small>
                                <h5 class="mb-0 number">{{ $totalOrders ?? 0 }}</h5>
                            </div>
                            <span class="badge icon rounded-1">
                                <i class="ti ti-shopping-cart fs-5"></i>
                            </span>
                        </div>
                    </div>
                    <a href="{{ route('customer.orders') }}" class="card-body px-3 pt-0">
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="mb-0 opacity-75">Pending Orders</small>
                                <small class="mb-0 fw-semibold">{{ $pendingOrders ?? 0 }}</small>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar bg-warning"
                                    style="width: {{ $totalOrders > 0 ? ($pendingOrders / $totalOrders) * 100 : 0 }}%"
                                    role="progressbar"></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="mb-0 opacity-75">Completed Orders</small>
                                <small class="mb-0 fw-semibold">{{ $completedOrders ?? 0 }}</small>
                            </div>
                            <div class="progress" style="height: 5px;">
                                <div class="progress-bar"
                                    style=" background-color: rgb(7, 191, 62); width: {{ $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0 }}%"
                                    role="progressbar"></div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>



            <div class="col-md-6 col-lg-4">
                <div class="card support h-100 p-3">
                    <div class="border-0">
                        <div class="card-title mb-1">
                            <h6 class="mb-1 fw-bold d-flex align-items-center gap-1">
                                <span
                                    style="border: 1px solid var(--second-primary); border-radius: 50px; padding: 6px; color: var(--second-primary); height: 30px; width: 30px;"
                                    class="d-flex align-items-center justify-content-center">
                                    <i class="fa-solid fa-headset fs-6"></i>
                                </span>
                                Support Tracker
                            </h6>
                        </div>
                    </div>
                    <div class="row pt-2">
                        <div class="">
                            <div class="">
                                <h1 style="background-color: #1926e32c; width: fit-content;"
                                    class="mb-0 theme-text fs-3 py-1 px-3 rounded-1">{{ $totalTickets ?? 0 }}</h1>
                            </div>
                            <div id="ticketPieChart"></div>

                            <ul class="p-0 m-0 gap-1"
                                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(105px, 1fr));">
                                <a href="{{ route('customer.support') }}" style="background-color: #8d8d8d2c"
                                    class="d-flex gap-2 align-items-start p-1 rounded-1 w-100">
                                    <div class="badge rounded-1 bg-label-primary p-1">
                                        <i class="ti ti-ticket theme-text fs-5"></i>
                                    </div>
                                    <div>
                                        <h6 style="font-size: 11px" class="mb-0 text-nowrap small">Open</h6>
                                        <p class="small opacity-75 mb-0">{{ $newTickets ?? 0 }}</p>
                                    </div>
                                </a>
                                <a href="{{ route('customer.support') }}" style="background-color: #8d8d8d2c"
                                    class="d-flex gap-2 align-items-start p-1 rounded-1 w-100">
                                    <div class="badge rounded-1 bg-label-info p-1">
                                        <i class="ti ti-circle-check fs-5 text-info"></i>
                                    </div>
                                    <div>
                                        <h6 style="font-size: 11px" class="mb-0 text-nowrap small">In-Progress</h6>
                                        <p class="small opacity-75 mb-0">{{ $pendingTickets ?? 0 }}</p>
                                    </div>
                                </a>
                                <a href="{{ route('customer.support') }}" style="background-color: #8d8d8d2c"
                                    class="d-flex gap-2 align-items-start p-1 rounded-1 w-100">
                                    <div class="badge rounded-1 bg-label-success p-1">
                                        <i class="ti ti-circle-check fs-5 success"></i>
                                    </div>
                                    <div>
                                        <h6 style="font-size: 11px" class="mb-0 text-nowrap small">Closed</h6>
                                        <p class="small opacity-75 mb-0">{{ $resolvedTickets ?? 0 }}</p>
                                    </div>
                                </a>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Order History -->
            <div class="col-lg-8">
                <div class="card history p-3 h-100" style="max-height: 28.7rem">
                    <div class="border-0 d-flex justify-content-between">
                        <div class="card-title mb-0">
                            <h6 class="mb-1 fw-bold d-flex align-items-center gap-1">
                                <span
                                    style="border: 1px solid var(--second-primary); border-radius: 50px; padding: 6px; color: var(--second-primary); height: 30px; width: 30px;"
                                    class="d-flex align-items-center justify-content-center">
                                    <i class="fa-solid fa-landmark-dome"></i>
                                </span>
                                Order History
                            </h6>
                            <p class="small mb-1">Your orders and their current status</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="ordersTable" class=" w-100">
                            <thead>
                                <tr>
                                    <th style="min-width: 1.4rem !important">ID</th>
                                    <th>Date</th>
                                    <th>Plan</th>
                                    <th>Domain Url</th>
                                    <th>Total Inboxes</th>
                                    <th>Status</th>
                                    <!-- <th>Actions</th> -->
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- <div class="col-12">
                <div class="card p-3">
                    <div class=" border-0 d-flex justify-content-between">
                        <div class=" mb-0">
                            <h6 class="mb-1 fw-bold d-flex align-items-center gap-1">
                                <span style="border: 1px solid var(--second-primary); border-radius: 50px; padding: 6px; color: var(--second-primary); height: 30px; width: 30px;" class="d-flex align-items-center justify-content-center">
                                    <i class="fa-solid fa-square-person-confined"></i>
                                </span>
                                Recent Activity
                            </h6>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="myTable" class="table table-striped w-100 nowrap">
                            <thead>
                                <tr>
                                    <th class="text-start">ID</th>
                                    <th>Action Type</th>
                                    <th>Description</th>
                                    <th>Performed By</th>
                                    <th>Performed On Type</th>
                                    <th>Performed On Id</th>
                                    <th>IP</th>
                                    <th>User Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> --}}
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // Initialize Swiper
            var swiper = new Swiper(".swiper", {
                loop: true,
                speed: 1000,
                autoplay: {
                    delay: 3000,
                    disableOnInteraction: false,
                },
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev",
                },
            });

            // Initialize all charts with error handling
            function initializeChart(selector, options) {
                const element = document.querySelector(selector);
                if (!element) {
                    console.warn(`Chart container ${selector} not found`);
                    return null;
                }
                try {
                    const chart = new ApexCharts(element, options);
                    chart.render();
                    return chart;
                } catch (error) {
                    console.error(`Error initializing chart ${selector}:`, error);
                    return null;
                }
            }

            // Ticket distribution pie chart
            const ticketOptions = {
                series: [
                    {{ $newTickets ?? 0 }},
                    {{ $pendingTickets ?? 0 }},
                    {{ $resolvedTickets ?? 0 }}
                ],
                chart: {
                    type: 'pie',
                    height: 300,
                    dropShadow: {
                        enabled: true,
                        color: '#000',
                        top: -1,
                        left: 3,
                        blur: 5,
                        opacity: 0.2
                    }
                },
                labels: ["Open", "In-Progress", "Closed"],
                colors: ['#7367ef', '#00CFE8', '#28C76F'],
                legend: {
                    position: 'bottom',
                    fontSize: '14px'
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val, opts) {
                        return opts.w.config.series[opts.seriesIndex];
                    },
                    style: {
                        fontSize: '14px'
                    },
                    dropShadow: {
                        enabled: false
                    }
                },
                stroke: {
                    width: 0 // Removing white lines between slices
                },
                states: {
                    hover: {
                        filter: {
                            type: 'darken',
                            value: 0.15
                        }
                    }
                },
                plotOptions: {
                    pie: {
                        expandOnClick: false,
                        donut: {
                            size: '0%'
                        },
                        offsetX: 0,
                        offsetY: 0,
                        customScale: 0.95,
                        startAngle: 0,
                        endAngle: 360,
                        hover: {
                            offsetX: 0,
                            offsetY: 0,
                            size: '10%' // This creates the separation effect on hover
                        }
                    }
                },
                fill: {
                    type: 'gradient'
                },
                tooltip: {
                    enabled: true,
                    theme: 'dark',
                    style: {
                        fontSize: '14px'
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            height: 250
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            // Sales chart options
            const salesOptions = {
                series: [{
                    data: [0, 40, 35, 70, 60, 80, 50]
                }],
                chart: {
                    type: 'area',
                    height: 135,
                    sparkline: {
                        enabled: true
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2,
                    colors: ['#00e396']
                },
                fill: {
                    colors: ['rgba(0,227,150,0.6162114504004728)'],
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0,
                        stops: [0, 90, 100]
                    }
                },
                tooltip: {
                    enabled: false
                }
            };

            // Week bar chart options
            const weekBarOptions = {
                series: [{
                    data: [20, 40, 35, 30, 60, 40, 45]
                }],
                chart: {
                    type: 'bar',
                    height: 180,
                    toolbar: {
                        show: false
                    },
                },
                plotOptions: {
                    bar: {
                        borderRadius: 3,
                        columnWidth: '40%',
                        distributed: true
                    }
                },
                colors: [
                    '#3D3D66', '#3D3D66', '#3D3D66', '#3D3D66', '#7F6CFF', '#3D3D66', '#3D3D66'
                ],
                dataLabels: {
                    enabled: false
                },
                xaxis: {
                    categories: ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'Su'],
                    labels: {
                        style: {
                            colors: '#A3A9BD',
                            fontSize: '12px'
                        }
                    },
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    show: false
                },
                grid: {
                    show: false
                },
                tooltip: {
                    enabled: false
                }
            };

            // Task gauge chart options
            const taskGaugeOptions = {
                series: [85],
                chart: {
                    height: 400,
                    type: 'radialBar',
                },
                plotOptions: {
                    radialBar: {
                        startAngle: -135,
                        endAngle: 135,
                        hollow: {
                            margin: 0,
                            size: '60%',
                            background: 'transparent',
                        },
                        track: {
                            background: 'transparent',
                            strokeWidth: '100%',
                        },
                        dataLabels: {
                            show: true,
                            name: {
                                offsetY: 20,
                                show: true,
                                color: '#A3A9BD',
                                fontSize: '14px',
                                text: 'Completed Task'
                            },
                            value: {
                                offsetY: -10,
                                color: '#fff',
                                fontSize: '28px',
                                show: true,
                                formatter: function(val) {
                                    return val + "%";
                                }
                            }
                        },
                    }
                },
                stroke: {
                    dashArray: 12
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'dark',
                        type: 'horizontal',
                        gradientToColors: ['#7F6CFF'],
                        stops: [0, 100]
                    }
                },
                colors: ['#3D3D66'],
                labels: ['Completed Task']
            };

            // Initialize all charts and tables
            initializeChart("#ticketPieChart", ticketOptions);
            initializeChart("#salesChart", salesOptions);
            initializeChart("#weekBarChart", weekBarOptions);
            initializeChart("#taskGaugeChart", taskGaugeOptions);

            // Initialize orders DataTable
            const ordersTable = $('#ordersTable').DataTable({
                searching: false,
                responsive: {
                    details: {
                        display: $.fn.dataTable.Responsive.display.modal({
                            header: function(row) {
                                return 'Order Details';
                            }
                        }),
                        renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                    }
                },
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('customer.orders.data') }}"
                },
                columns: [{
                        data: 'id',
                        name: 'orders.id'
                    },
                    {
                        data: 'created_at',
                        name: 'orders.created_at',
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex gap-1 align-items-center opacity-50">
                                    <i class="ti ti-calendar-month"></i>
                                    <span class="text-nowrap">${data}</span>    
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'plan_name',
                        name: 'plans.name',
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex gap-1 align-items-center">
                                    <img src="https://cdn-icons-png.flaticon.com/128/11890/11890970.png" style="width: 15px" alt="">
                                    <span>${data}</span>    
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'domain_forwarding_url',
                        name: 'domain_forwarding_url'
                    },
                    {
                        data: 'total_inboxes',
                        name: 'total_inboxes'
                    },
                    {
                        data: 'status',
                        name: 'orders.status',
                    }
                ],
                order: [
                    [1, 'desc']
                ]
            });

            // Handle alert transitions - Repeating cycle every 3 seconds
            const draftAlert = document.getElementById('draftAlert');
            const rejectedAlert = document.getElementById('rejectedAlert');
            let alertInterval = null;
            
            // Check if both alerts exist
            if (draftAlert && rejectedAlert) {
                let currentAlert = 'draft'; // Start with draft
                
                // Function to switch between alerts
                function switchAlerts() {
                    if (currentAlert === 'draft') {
                        // Switch to rejected alert
                        draftAlert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                        draftAlert.style.opacity = '0';
                        draftAlert.style.transform = 'translateX(-100%)';
                        
                        setTimeout(function() {
                            draftAlert.style.display = 'none';
                            rejectedAlert.style.display = 'block';
                            rejectedAlert.style.opacity = '0';
                            rejectedAlert.style.transform = 'translateX(-100%)';
                            
                            setTimeout(function() {
                                rejectedAlert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                                rejectedAlert.style.opacity = '1';
                                rejectedAlert.style.transform = 'translateX(0)';
                                rejectedAlert.classList.add('show');
                            }, 50);
                        }, 500);
                        
                        currentAlert = 'rejected';
                    } else {
                        // Switch to draft alert
                        rejectedAlert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                        rejectedAlert.style.opacity = '0';
                        rejectedAlert.style.transform = 'translateX(-100%)';
                        
                        setTimeout(function() {
                            rejectedAlert.style.display = 'none';
                            rejectedAlert.classList.remove('show');
                            draftAlert.style.display = 'block';
                            draftAlert.style.opacity = '0';
                            draftAlert.style.transform = 'translateX(-100%)';
                            
                            setTimeout(function() {
                                draftAlert.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                                draftAlert.style.opacity = '1';
                                draftAlert.style.transform = 'translateX(0)';
                            }, 50);
                        }, 500);
                        
                        currentAlert = 'draft';
                    }
                }
                
                // Start the repeating cycle every 3 seconds
                alertInterval = setInterval(switchAlerts, 20000);
                
                // Add click handlers to stop cycling when user dismisses an alert
                const draftCloseBtn = draftAlert.querySelector('.btn-close');
                const rejectedCloseBtn = rejectedAlert.querySelector('.btn-close');
                
                if (draftCloseBtn) {
                    draftCloseBtn.addEventListener('click', function() {
                        clearInterval(alertInterval);
                    });
                }
                
                if (rejectedCloseBtn) {
                    rejectedCloseBtn.addEventListener('click', function() {
                        clearInterval(alertInterval);
                    });
                }
            }
            // If only rejected alert exists but no draft alert, show it immediately
            else if (rejectedAlert && !draftAlert) {
                rejectedAlert.style.display = 'block';
                setTimeout(function() {
                    rejectedAlert.classList.add('show');
                }, 50);
            }

            // DataTable initialization code
            function initDataTable(planId = '') {
                console.log('Initializing DataTable for planId:', planId);
                const tableId = '#myTable';
                const $table = $(tableId);

                if (!$table.length) {
                    console.error('Table not found with selector:', tableId);
                    return null;
                }

                try {
                    const table = $table.DataTable({
                        processing: true,
                        serverSide: true,
                        responsive: {
                            details: {
                                display: $.fn.dataTable.Responsive.display.modal({
                                    header: function(row) {
                                        return 'Activity Details';
                                    }
                            }),
                            renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                        }
                        },
                        autoWidth: false,
                        dom: '<"top"f>rt<"bottom"lip><"clear">', // expose filter (f) and move others
                        ajax: {
                            url: "{{ route('specific.logs') }}",
                            type: "GET",
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json'
                            },
                            data: function(d) {
                                d.plan_id = planId;
                                d.user_name = $('#user_name_filter').val();
                                d.email = $('#email_filter').val();
                                d.status = $('#status_filter').val();
                            },
                            error: function(xhr, error, thrown) {
                                console.error('DataTables error:', error);
                                console.error('Server response:', xhr.responseText);

                                if (xhr.status === 401) {
                                    window.location.href = "{{ route('login') }}";
                                } else if (xhr.status === 403) {
                                    toastr.error('You do not have permission to view this data');
                                } else {
                                    toastr.error('Error loading data: ' + error);
                                }
                            }
                        },
                        columns: [{
                                data: 'id',
                                name: 'id'
                            },
                            {
                                data: 'action_type',
                                name: 'action_type'
                            },
                            {
                                data: 'description',
                                name: 'description',
                                render: function(data, type, row) {
                                    return `
                                        <div class="d-flex align-items-center text-nowrap">
                                            <div class="me-1 rounded-1 d-flex align-items-center justify-content-center">
                                                <i style="color: var(--green-color); font-size: 14px" class="ti ti-file-description "></i>
                                            </div>
                                            <span>${data}</span>
                                        </div>
                                    `;
                                }
                            },
                            {
                                data: 'performed_by',
                                name: 'performed_by',
                                render: function(data, type, row) {
                                    return `
                                        <div class="d-flex align-items-center text-nowrap px-2 rounded-1" style= "border: 1px solid #00F2FF; width: fit-content">
                                            <span style="color: #00F2FF; font-size: 11px">${data}</span>
                                        </div>
                                    `;
                                }
                            },
                            {
                                data: 'performed_on_type',
                                name: 'performed_on_type',
                                render: function(data, type, row) {
                                    return `
                                        <div class="d-flex align-items-start gap-1">
                                            <img src="https://cdn-icons-png.flaticon.com/128/3641/3641988.png" style="width: 15px" alt="">
                                            <span>${data}</span>    
                                        </div>
                                    `;
                                }
                            },
                            {
                                data: 'performed_on',
                                name: 'performed_on'
                            },
                            {
                                data: 'ip',
                                name: 'ip'
                            },
                            {
                                data: 'user_agent',
                                name: 'user_agent'
                            },
                            // { data: 'action', name: 'action', orderable: false, searchable: false }
                        ],
                        columnDefs: [{
                                width: '10%',
                                targets: 0
                            }, // ID
                            {
                                width: '15%',
                                targets: 1
                            }, // Action Type
                            {
                                width: '20%',
                                targets: 2
                            }, // Description 
                            {
                                width: '10%',
                                targets: 3
                            }, // Performed By
                            {
                                width: '10%',
                                targets: 4
                            }, // Performed On Type
                            {
                                width: '10%',
                                targets: 5
                            }, // Performed On Id
                            {
                                width: '15%',
                                targets: 6
                            }, // Extra Data
                            {
                                width: '10%',
                                targets: 7
                            } // User Agent
                        ],
                        order: [
                            [1, 'desc']
                        ],
                        drawCallback: function(settings) {
                            const counters = settings.json?.counters;

                            if (counters) {
                                $('#total_counter').text(counters.total);
                                $('#active_counter').text(counters.active);
                                $('#inactive_counter').text(counters.inactive);
                            }

                            $('[data-bs-toggle="tooltip"]').tooltip();
                            this.api().columns.adjust();
                            this.api().responsive?.recalc();
                        },
                        initComplete: function() {
                            console.log('Table initialization complete');
                            this.api().columns.adjust();
                            this.api().responsive?.recalc();
                        }
                    });

                    // Optional loading indicator
                    table.on('processing.dt', function(e, settings, processing) {
                        const wrapper = $(tableId + '_wrapper');
                        if (processing) {
                            wrapper.addClass('loading');
                            if (!wrapper.find('.dt-loading').length) {
                                wrapper.append('<div class="dt-loading">Loading...</div>');
                            }
                        } else {
                            wrapper.removeClass('loading');
                            wrapper.find('.dt-loading').remove();
                        }
                    });

                    return table;
                } catch (error) {
                    console.error('Error initializing DataTable:', error);
                    toastr.error('Error initializing table. Please refresh the page.');
                }

            }
            initDataTable();

            document.addEventListener('DOMContentLoaded', function() {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            });
        });
    </script>
@endpush
