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
            border: 1px solid #746af5;
            backdrop-filter: blur(10px);
            border-radius: 6px;
        }

        /* Draft Alert Animations */
        .draft-alert {
            animation: slideInFromLeft 0.8s ease-out, pulse 2s infinite;
            position: relative;
            overflow: hidden;
            margin-top: 15px;
        }

        .draft-alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 3s infinite;
        }

        .draft-alert:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

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
            0%, 100% {
                border-color: var(--second-primary);
            }
            50% {
                border-color: #ffc107;
                box-shadow: 0 0 20px rgba(255, 193, 7, 0.3);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
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
    </style>
@endpush

@section('content')
    <!-- Draft Orders Notification -->
    @if(isset($draftOrders) && $draftOrders > 0)
        <div class="alert alert-warning alert-dismissible fade show draft-alert" role="alert" style="background-color: var(--second-primary); color: #fff; border: 2px solid var(--second-primary);">
            <i class="ti ti-alert-triangle me-2 alert-icon"></i>
            <strong>Draft Orders Alert:</strong> You have {{ $draftOrders }} order{{ $draftOrders > 1 ? 's' : '' }} available in drafts. 
            <a href="{{ route('customer.orders') }}" class="text-warning alert-link">View your orders</a> to complete them.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <section class="py-3">
        <div class="row gy-4">
            <!-- Inbox Statistics -->
            <div class="col-4">
                <div class=" inbox overflow-hidden h-100">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center gap-1 mb-3">
                            <span class="rounded-1 d-flex align-items-center justify-content-center">
                                <i class="ti ti-inbox fs-5"></i>
                            </span>
                            <div>
                                <h5 class="mb-0">Inbox Status</h5>
                            </div>
                        </div>
                        <div class="d-flex flex-column gap-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="">
                                    <span class="d-block opacity-75">Total Inboxes</span>
                                    <h3 class="mb-0 mt-1">{{ $totalInboxes ?? 0 }}</h3>
                                </div>
                                <span style="background-color: var(--second-primary)" class="bg-opacity-25  px-3 py-1 rounded-pill">
                                    <i class="ti ti-inbox-off me-1"></i> Total
                                </span>
                            </div>
                            <div class="progress bg-white bg-opacity-25" style="height: 3px;">
                                <div class="progress-bar bg-danger" style="width: 50%; background-color: rgb(7, 191, 62) !important"></div>
                            </div>
                            <div class="row g-0 mt-1">
                                <div class="col-6">
                                    <div class="">
                                        <span class="d-block success">Active</span>
                                        <h4 class="mb-0 success">{{ $activeInboxes ?? 0 }}</h4>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class=" text-end">
                                        <span class="d-block text-warning">Pending/Issue</span>
                                        <h4 class="mb-0 text-warning">{{ $pendingInboxes ?? 0 }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription Info -->
            <!-- <div class="col-xl-3 col-sm-6">
                                <div class="card overflow-hidden" style="background: linear-gradient(45deg, #11998e, #38ef7d);">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-white bg-opacity-25 rounded-circle">
                                                    <i class="ti ti-currency-dollar fs-4 text-white"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="text-white mb-1">Subscription</h5>
                                            </div>
                                        </div>
                                        @if (isset($nextBillingInfo))
    <div class="d-flex flex-column gap-3">
                                            <div class="text-white">
                                                <span class="d-block opacity-75">Next Payment</span>
                                                <h4 class="mb-0 mt-1">{{ $nextBillingInfo['next_billing_at'] ?? 'N/A' }}</h4>
                                            </div>
                                            <div class="progress bg-white bg-opacity-25" style="height: 2px;">
                                                <div class="progress-bar bg-white" style="width: 100%"></div>
                                            </div>
                                            <div class="row g-0">
                                                <div class="col-6">
                                                    <div class="text-white">
                                                        <span class="d-block opacity-75">Amount</span>
                                                        <h4 class="mb-0">${{ $nextBillingInfo['amount'] ?? '0.00' }}</h4>
                                                    </div>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <div class="text-white">
                                                        <span class="d-block opacity-75">Status</span>
                                                        <span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill mt-1">
                                                            {{ ucfirst($subscription->status ?? 'N/A') }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
@else
    <div class="text-white text-center py-3">
                                            No active subscription
                                        </div>
    @endif
                                    </div>
                                </div>
                            </div> -->

            <!-- Order Statistics -->
            <!-- <div class="col-xl-3 col-sm-6">
                                <div class="card overflow-hidden" style="background: linear-gradient(45deg, #FF512F, #F09819);">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-white bg-opacity-25 rounded-circle">
                                                    <i class="ti ti-shopping-cart fs-4 text-white"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="text-white mb-1">Orders</h5>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column gap-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="text-white">
                                                    <span class="d-block opacity-75">Total Orders</span>
                                                    <h3 class="mb-0 mt-1">{{ $totalOrders ?? 0 }}</h3>
                                                </div>
                                                <span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill">
                                                    <i class="ti ti-shopping me-1"></i> Total
                                                </span>
                                            </div>
                                            <div class="progress bg-white bg-opacity-25" style="height: 2px;">
                                                <div class="progress-bar bg-white" style="width: 100%"></div>
                                            </div>
                                            <div class="row g-0 mt-1">
                                                <div class="col-6">
                                                    <div class="text-white">
                                                        <span class="d-block opacity-75">Pending</span>
                                                        <h4 class="mb-0">{{ $pendingOrders ?? 0 }}</h4>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-white text-end">
                                                        <span class="d-block opacity-75">Completed</span>
                                                        <h4 class="mb-0">{{ $completedOrders ?? 0 }}</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> -->

            <!-- Support Tickets -->
            <!-- <div class="col-xl-3 col-sm-6">
                                <div class="card overflow-hidden" style="background: linear-gradient(45deg, #834d9b, #d04ed6);">
                                    <div class="card-body p-4">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-sm flex-shrink-0">
                                                <span class="avatar-title bg-white bg-opacity-25 rounded-circle">
                                                    <i class="ti ti-ticket fs-4 text-white"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="text-white mb-1">Support</h5>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column gap-3">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="text-white">
                                                    <span class="d-block opacity-75">Total Tickets</span>
                                                    <h3 class="mb-0 mt-1">{{ $totalTickets ?? 0 }}</h3>
                                                </div>
                                                <span class="badge bg-white bg-opacity-25 text-white px-3 py-2 rounded-pill">
                                                    <i class="ti ti-ticket me-1"></i> Total
                                                </span>
                                            </div>
                                            <div class="progress bg-white bg-opacity-25" style="height: 2px;">
                                                <div class="progress-bar bg-white" style="width: 100%"></div>
                                            </div>
                                            <div class="row g-0 mt-1">
                                                <div class="col-6">
                                                    <div class="text-white">
                                                        <span class="d-block opacity-75">Pending</span>
                                                        <h4 class="mb-0">{{ $pendingTickets ?? 0 }}</h4>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="text-white text-end">
                                                        <span class="d-block opacity-75">Resolved</span>
                                                        <h4 class="mb-0">{{ $resolvedTickets ?? 0 }}</h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> -->

            <!-- <div class="col-md-6">
                                <div class="swiper">
                                    <div class="swiper-wrapper">
                                        <div class="swiper-slide d-flex align-items-center p-4 justify-content-between">
                                            <div>
                                                <h5 class="mb-0">Websites Analytics</h5>
                                                <span>Total 28.5% conversation rate</span>
                                                <div class="mt-5">
                                                    <h6>Spending</h6>
                                                    <div class="row gy-4">
                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>

                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>

                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>

                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/card-website-analytics-1.png"
                                                width="160" alt="Slide 1">
                                        </div>

                                        <div class="swiper-slide d-flex align-items-center p-4 justify-content-between">
                                            <div>
                                                <h5 class="mb-0">Websites Analytics</h5>
                                                <span>Total 28.5% conversation rate</span>
                                                <div class="mt-5">
                                                    <h6>Spending</h6>
                                                    <div class="row gy-4">
                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>

                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>

                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>

                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/card-website-analytics-2.png"
                                                width="160" alt="Slide 1">
                                        </div>

                                        <div class="swiper-slide d-flex align-items-center p-4 justify-content-between">
                                            <div>
                                                <h5 class="mb-0">Websites Analytics</h5>
                                                <span>Total 28.5% conversation rate</span>
                                                <div class="mt-5">
                                                    <h6>Spending</h6>
                                                    <div class="row gy-4">
                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>

                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>

                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>

                                                        <div class="col-6">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <span class="py-1 px-2 rounded-1 slider_span_bg">268</span>
                                                                <h6 class="mb-0">Session</h6>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/card-website-analytics-3.png"
                                                width="160" alt="Slide 1">
                                        </div>
                                    </div>
                                    <div class="swiper-pagination"></div>
                                </div>
                            </div> -->


            <div class="col-4">
                <div class="card h-100">
                    <div class="card-header border-0 px-3 pt-3 pb-0">
                        <h6 class="mb-1">Orders Overview</h6>
                        <div class="d-flex justify-content-between align-items-center">
                            <p class="mb-0 small">All Time Orders</p>
                            <span class="badge icon">
                                <i class="ti ti-shopping-cart fs-5"></i>
                            </span>
                        </div>
                        <h4 class="mb-0 mt-2">{{ $totalOrders ?? 0 }}</h4>
                    </div>
                    <div class="card-body px-3 pt-0">
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <p class="mb-0">Pending Orders</p>
                                <p class="mb-0 fw-semibold">{{ $pendingOrders ?? 0 }}</p>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning"
                                    style="width: {{ $totalOrders > 0 ? ($pendingOrders / $totalOrders) * 100 : 0 }}%"
                                    role="progressbar"></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <p class="mb-0">Completed Orders</p>
                                <p class="mb-0 fw-semibold">{{ $completedOrders ?? 0 }}</p>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar"
                                    style=" background-color: rgb(7, 191, 62); width: {{ $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0 }}%"
                                    role="progressbar"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- <div class="col-xl-3 col-sm-6">
                                <div class="card h-100 p-2">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between">
                                            <p class="mb-0">Inboxes Overview</p>
                                        </div>
                                        <h4 class="card-title mb-1">{{ $totalInboxes ?? 0 }} Total Inboxes</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-label-success p-1 rounded me-2">
                                                            <i class="ti ti-inbox-filled text-success fs-5"></i>
                                                        </span>
                                                        <p class="mb-0">Active Inboxes</p>
                                                    </div>
                                                    <p class="mb-0 fw-semibold">{{ $activeInboxes ?? 0 }}</p>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-success" style="width: {{ $totalInboxes > 0 ? ($activeInboxes / $totalInboxes) * 100 : 0 }}%" role="progressbar"></div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge bg-label-warning p-1 rounded me-2">
                                                            <i class="ti ti-inbox text-warning fs-5"></i>
                                                        </span>
                                                        <p class="mb-0">Pending Inboxes</p>
                                                    </div>
                                                    <p class="mb-0 fw-semibold">{{ $pendingInboxes ?? 0 }}</p>
                                                </div>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar bg-warning" style="width: {{ $totalInboxes > 0 ? ($pendingInboxes / $totalInboxes) * 100 : 0 }}%" role="progressbar"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div> -->


            <div class="col-4">
                <div class="card h-100 p-3">
                    <div class="border-0 pb-0 d-flex justify-content-between">
                        <div class="card-title mb-0">
                            <h6 class="mb-0">Current Subscription Overview</h6>
                            <p class="small">Subscription and billing details</p>
                        </div>
                    </div>
                    <div class="pt-0">
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
                                    <h5 class="mb-0">${{ $nextBillingInfo['amount'] ?? '0.00' }}</h5>
                                    @if (isset($subscription) && $subscription->status === 'active')
                                        <div class="badge rounded bg-label-success success">Active</div>
                                    @else
                                        <div class="badge rounded bg-label-warning">No Active Plan</div>
                                    @endif
                                </div>
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small style="font-size: 12px" class="theme-text">Next Billing Date</small>
                                        <span
                                            class="mb-0">{{ isset($subscription) && $subscription->next_billing_date
                                                ? \Carbon\Carbon::parse($subscription->next_billing_date)->format('M d, Y')
                                                : 'N/A' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small style="font-size: 12px" class="theme-text">Last Billing Date</small>
                                        <span
                                            class="mb-0">{{ isset($subscription) && $subscription->last_billing_date
                                                ? \Carbon\Carbon::parse($subscription->last_billing_date)->format('M d, Y')
                                                : 'N/A' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small style="font-size: 12px" class="theme-text">Billing Period</small>
                                        <span class="mb-0">Monthly</span>
                                    </div>
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



            <div class="col-4">
                <div class="card h-100 p-3">
                    <div class="border-0">
                        <div class="card-title mb-0">
                            <h6 class="mb-1">Support Tracker</h6>
                            <!-- <p>Last 7 Days</p> -->
                        </div>
                        <!-- <div class="dropdown">
                                            <button class="border-0 bg-transparent" type="button" id="supportTrackerMenu"
                                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis-vertical fs-4"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="supportTrackerMenu">
                                                <a class="dropdown-item" href="javascript:void(0);">View
                                                    More</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                            </div>
                                        </div> -->
                    </div>
                    <div class="row pt-0">
                        <div class="">
                            <div class="">
                                <h1 class="mb-0 success">{{ $totalTickets ?? 0 }}</h1>
                                {{-- <p class="mb-0">Total Tickets</p> --}}
                            </div>
                            <div id="ticketPieChart"></div>
                            <ul class="p-0 m-0 d-flex align-items-center justify-content-between">
                                <li class="d-flex gap-2 align-items-center mb-2">
                                    <div class="badge rounded bg-label-primary p-1">
                                        <i class="ti ti-ticket theme-text fs-5"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap small">Open</h6>
                                        <p class="small opacity-75 mb-0">{{ $newTickets ?? 0 }}</p>
                                    </div>
                                </li>
                                <li class="d-flex gap-2 align-items-center mb-2">
                                    <div class="badge rounded bg-label-info p-1">
                                        <i class="ti ti-circle-check fs-5 text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap small">In-Progress</h6>
                                        <p class="small opacity-75 mb-0">{{ $pendingTickets ?? 0 }}</p>
                                    </div>
                                </li>
                                <li class="d-flex gap-2 align-items-center pb-1">
                                    <div class="badge rounded bg-label-success p-1">
                                        <i class="ti ti-check fs-5 success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap small">Closed</h6>
                                        <p class="small opacity-75 mb-0">{{ $resolvedTickets ?? 0 }}</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>



            <!-- <div class="col-xxl-4 col-md-6">
                                <div class="card h-100 p-2">
                                    <div class="card-header border-0 d-flex justify-content-between">
                                        <div class="card-title mb-0">
                                            <h5 class="mb-1">Sales by Countries</h5>
                                            <p>Monthly Sales Overview</p>
                                        </div>
                                        <div class="dropdown">
                                            <button class="border-0 bg-transparent" type="button" id="supportTrackerMenu"
                                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis-vertical fs-4"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="supportTrackerMenu">
                                                <a class="dropdown-item" href="javascript:void(0);">View More</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                                                <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <ul class="p-0 m-0">
                                            <li class="d-flex align-items-center mb-3">
                                                <div class="avatar flex-shrink-0 me-4">
                                                    <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                                        class="object-fit-cover" width="35" height="35"
                                                        style="border-radius: 50px;" alt="">
                                                </div>
                                                <div class="d-flex w-100 flex-wrap align-items-center justify-content-between gap-2">
                                                    <div class="me-2">
                                                        <div class="d-flex align-items-center">
                                                            <h6 class="mb-0 me-1">$8,567k</h6>
                                                        </div>
                                                        <small class="opacity-50">United states</small>
                                                    </div>
                                                    <div class="user-progress">
                                                        <p class="text-success fw-medium mb-0 d-flex align-items-center gap-1">
                                                            <i class="fa fa-chevron-up"></i>
                                                            25.8%
                                                        </p>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div> -->

            <!-- Order History -->
            <div class="col-8">
                <div class="card p-3" style="max-height: 28.4rem">
                    <div class="border-0 d-flex justify-content-between">
                        <div class="card-title mb-0">
                            <h6 class="mb-1">Order History</h6>
                            <p class="small">Your orders and their current status</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="ordersTable" class=" w-100">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
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

            <div class="col-12">
                <div class="card p-3">
                    <!-- heading  -->
                    <div class="card-header border-0 d-flex justify-content-between">
                        <div class="card-title mb-0">
                            <h5 class="mb-1">Recent Activity</h5>
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
            </div>
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
                                    <span>${data}</span>    
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
                                    <img src="https://cdn-icons-png.flaticon.com/128/11890/11890970.png" style="width: 20px" alt="">
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
                        name: 'orders.status'
                    }
                ],
                order: [
                    [1, 'desc']
                ]
            });
        });

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
                                        <div class="me-1 rounded-1 d-flex align-items-center justify-content-center" style="background-color: rgba(85, 255, 78, 0.4); height: 20px; width: 20px">
                                            <i style="color: #A6FF00" class="ti ti-file-description fs-6"></i>
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
                                    <div class="d-flex align-items-center text-nowrap px-2 py-1 rounded-2" style= "border: 1px solid #00F2FF">
                                        <span style="color: #00F2FF">${data}</span>
                                    </div>
                                `;
                            }
                        },
                        {
                            data: 'performed_on_type',
                            name: 'performed_on_type',
                            render: function(data, type, row) {
                                return `
                                    <img src="https://cdn-icons-png.flaticon.com/128/3641/3641988.png" style="width: 25px" alt="">
                                    <span>${data}</span>
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
    </script>
@endpush
