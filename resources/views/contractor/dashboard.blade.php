@extends('contractor.layouts.app')

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
    </style>
@endpush

@section('content')
    <section class="py-3 overflow-hidden">
        <div class="row gy-4">



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



            <!-- <div class="col-xl-3 col-sm-6">
                <div class="card h-100">
                    <div class="card-header border-0 px-3 pt-3 pb-0">
                        <h6 class="mb-2 ">Average Daily Sales</h6>
                        <p class="mb-0">Total Sales This Month</p>
                        <h4 class="mb-0">$28,450</h4>
                    </div>
                    <div class="card-body px-0 pt-0 border-0" style="margin-top: -1rem;">
                        <div id="salesChart"></div>
                    </div>
                </div>
            </div>



            <div class="col-xl-3 col-sm-6">
                <div class="card h-100 p-2">
                    <div class="card-header">
                        <div class="d-flex justify-content-between">
                            <p class="mb-0 small">Sales Overview</p>
                            <p class="card-text fw-medium text-success">+18.2%</p>
                        </div>
                        <h4 class="card-title mb-1">$42.5k</h4>
                    </div>
                    <div class="card-body d-flex flex-column justify-content-between gap-3">
                        <div class="row">
                            <div class="col-4">
                                <div class="d-flex gap-2 align-items-center mb-2">
                                    <span class="badge bg-label-info p-1 rounded">
                                        <i class="ti ti-shopping-cart text-info fs-5"></i>
                                    </span>
                                    <p class="mb-0">Order</p>
                                </div>
                                <h5 class="mb-0 pt-1">62.2%</h5>
                                <small class="opacity-50 fw-light">6,440</small>
                            </div>
                            <div class="col-4">
                                <div class="divider divider-vertical">
                                    <div class="divider-text">
                                        <span class="badge-divider-bg bg-label-secondary">VS</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="d-flex gap-2 justify-content-end align-items-center mb-2">
                                    <p class="mb-0">Visits</p>
                                    <span class="badge bg-label-primary p-1 rounded">
                                        <i class="ti ti-link theme-text fs-5"></i>
                                    </span>
                                </div>
                                <h5 class="mb-0 pt-1">25.5%</h5>
                                <small class="opacity-50 fw-light">12,749</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mt-6">
                            <div class="progress w-100" style="height: 10px;">
                                <div class="progress-bar bg-info" style="width: 70%" role="progressbar"
                                    aria-valuenow="70" aria-valuemin="0" aria-valuemax="100"></div>
                                <div class="progress-bar" role="progressbar" style="width: 30%" aria-valuenow="30"
                                    aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->



            <!-- <div class="col-md-6">
                <div class="card h-100 p-2">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between">
                        <div class="card-title mb-0">
                            <h5 class="mb-1">Earning Reports</h5>
                            <p>Weekly Earnings Overview</p>
                        </div>
                        <div class="dropdown">
                            <button class="border-0 bg-transparent" type="button" id="supportTrackerMenu"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa-solid fa-ellipsis-vertical fs-4"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="supportTrackerMenu">
                                <a class="dropdown-item" href="javascript:void(0);">View
                                    More</a>
                                <a class="dropdown-item" href="javascript:void(0);">Delete</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row align-items-center g-md-8">
                            <div class="col-12 col-md-5 d-flex flex-column">
                                <div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
                                    <h1 class="mb-2">$468</h1>
                                    <div class="badge rounded bg-label-success">+4.2%</div>
                                </div>
                                <small class="">You informed of this week compared to last
                                    week</small>
                            </div>
                            <div class="col-12 col-md-7">
                                <div id="weekBarChart"></div>
                            </div>
                        </div>
                        <div class="rounded p-4 mt-4" style="border: 1px solid var(--input-border);">
                            <div class="row gap-4 gap-sm-0">
                                <div class="col-12 col-sm-4">
                                    <div class="d-flex gap-2 align-items-center">
                                        <div class="badge rounded bg-label-primary p-1">
                                            <i class="ti ti-currency-dollar theme-text fs-5"></i>
                                        </div>
                                        <h6 class="mb-0 fw-normal">Earnings</h6>
                                    </div>
                                    <h4 class="my-2">$545.69</h4>
                                    <div class="progress w-75" style="height:4px">
                                        <div class="progress-bar" role="progressbar" style="width: 65%"
                                            aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4">
                                    <div class="d-flex gap-2 align-items-center">
                                        <div class="badge rounded bg-label-info p-1">
                                            <i class="ti ti-clock-share text-info fs-5"></i>
                                        </div>
                                        <h6 class="mb-0 fw-normal">Profit</h6>
                                    </div>
                                    <h4 class="my-2">$256.34</h4>
                                    <div class="progress w-75" style="height:4px">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: 50%"
                                            aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4">
                                    <div class="d-flex gap-2 align-items-center">
                                        <div class="badge rounded bg-label-danger p-1">
                                            <i class="ti ti-brand-paypal text-danger fs-5"></i>
                                        </div>
                                        <h6 class="mb-0 fw-normal">Expense</h6>
                                    </div>
                                    <h4 class="my-2">$74.19</h4>
                                    <div class="progress w-75" style="height:4px">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: 65%"
                                            aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="col-xxl-6">
                <div class="card h-100 p-2">
                    <div class="card-header border-0 d-flex justify-content-between">
                        <div class="card-title mb-0">
                            <h5 class="mb-1">Orders Overview</h5>
                            <p>Distribution of orders by status</p>
                        </div>
                    </div>
                    <div class="card-body row">
                        <div class="col-12 col-sm-4">
                            <div class="mt-lg-4 mt-lg-2 mb-lg-4 mb-2">
                                <h1 class="mb-0">{{ $totalOrders ?? 0 }}</h1>
                                <p class="mb-0">Total Orders</p>
                            </div>
                            <ul class="p-0 m-0">
                                <li class="d-flex gap-3 align-items-start mb-2">
                                    <div class="badge rounded bg-label-primary mt-1">
                                        <i class="ti ti-clock-play fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap">Pending Orders</h6>
                                        <p class="small opacity-75">{{ $pendingOrders ?? 0 }}</p>
                                    </div>
                                </li>
                                <li class="d-flex gap-3 align-items-start mb-2">
                                    <div class="badge rounded bg-label-warning mt-1">
                                        <i class="ti ti-loader fs-4 text-warning"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap">In Progress</h6>
                                        <p class="small opacity-75">{{ $inProgressOrders ?? 0 }}</p>
                                    </div>
                                </li>
                                <li class="d-flex gap-3 align-items-start mb-2">
                                    <div class="badge rounded bg-label-success mt-1">
                                        <i class="ti ti-check fs-4 text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap">Completed</h6>
                                        <p class="small opacity-75">{{ $completedOrders ?? 0 }}</p>
                                    </div>
                                </li>
                                <li class="d-flex gap-3 align-items-start mb-2">
                                    <div class="badge rounded bg-label-info mt-1">
                                        <i class="ti ti-thumb-up fs-4 text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap">Approved</h6>
                                        <p class="small opacity-75">{{ $approvedOrders ?? 0 }}</p>
                                    </div>
                                </li>
                                <li class="d-flex gap-3 align-items-start">
                                    <div class="badge rounded bg-label-danger mt-1">
                                        <i class="ti ti-alert-circle fs-4 text-danger"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap">Cancelled</h6>
                                        <p class="small opacity-75">{{ $expiredOrders ?? 0 }}</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="col-12 col-sm-8">
                            <div id="orderStatusChart"></div>
                        </div>
                    </div>
                </div>
            </div>


            

            <div class="col-12 col-md-6">
                <div class="card h-100 p-2">
                    <div class="card-header border-0 d-flex justify-content-between">
                        <div class="card-title mb-0">
                            <h5 class="mb-1">Support Tracker</h5>
                            <p>Assigned Tickets Overview</p>
                        </div>
                        <!-- <div class="dropdown">
                            <button class="border-0 bg-transparent" type="button" id="supportTrackerMenu"
                                data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fa-solid fa-ellipsis-vertical fs-4"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="supportTrackerMenu">
                                <a class="dropdown-item" href="javascript:void(0);">View All</a>
                            </div>
                        </div> -->
                    </div>
                    <div class="card-body row pt-0">
                        <div class="col-12 col-sm-4 d-flex flex-column justify-content-between">
                            <div class="mt-lg-4 mt-lg-2 mb-lg-6 mb-2">
                                <h1 class="mb-0">{{ $totalTickets ?? 0 }}</h1>
                                <p class="mb-0">Total Tickets</p>
                            </div>
                            <ul class="p-0 m-0">
                                <li class="d-flex gap-3 align-items-start mb-2">
                                    <div class="badge rounded bg-label-primary mt-1">
                                        <i class="ti ti-ticket theme-text fs-4"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap">Open Tickets</h6>
                                        <p class="small opacity-75">{{ $newTickets ?? 0 }}</p>
                                    </div>
                                </li>
                                <li class="d-flex gap-3 align-items-start mb-2">
                                    <div class="badge rounded bg-label-info mt-1">
                                        <i class="ti ti-clock fs-4 text-info"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap">In Progress</h6>
                                        <p class="small opacity-75">{{ $inProgressTickets ?? 0 }}</p>
                                    </div>
                                </li>
                                <li class="d-flex gap-3 align-items-start pb-1">
                                    <div class="badge rounded bg-label-success mt-1">
                                        <i class="ti ti-check fs-4 text-success"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 text-nowrap">Closed</h6>
                                        <p class="small opacity-75">{{ $resolvedTickets ?? 0 }}</p>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div class="col-12 col-md-8">
                            <div id="ticketPieChart"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card p-3">
                    <div class="card-header border-0 pb-0 d-flex justify-content-between">
                        <div class="card-title mb-0">
                            <!-- History -->
                            <h5 class="mb-1">Orders History</h5>
                            <p>History of orders</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="ordersTable" class="display">  <!-- Changed from myTable to ordersTable -->
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Plan</th>
                                    <th>Total Inboxes</th>
                                    <th>Status</th>
                                    <th>Created At</th>
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
                        <table id="activityTable" class="display w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Action</th>
                                    <th>Description</th>
                                    <th>Performed On</th>
                                    <th>Data</th>
                                    <th>Date</th>
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

            $(document).ready(function() {
                var table = $('#usersTable').DataTable();

                $(".dt-search").append(
                    '<button class="m-btn fw-semibold border-0 rounded-1 ms-2 text-white" style="padding: .4rem 1rem" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddAdmin" aria-controls="offcanvasAddAdmin"> + Add New Record </button>'
                );
            });

            // Initialize ticket chart with proper error handling
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

            // Ticket Pie Chart configuration
            const ticketOptions = {
                series: [{{ $newTickets ?? 0 }}, {{ $inProgressTickets ?? 0 }}, {{ $resolvedTickets ?? 0 }}],
                chart: {
                    type: 'pie',
                    height: 350,
                },
                labels: ['Open', 'In-Progress', 'Closed'],
                colors: ['#9b86e4', '#dc3545', '#df7040'],
                legend: {
                    position: 'bottom',
                    labels: {
                        colors: '#a3a9bd'
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: false,
                                total: {
                                    show: true,
                                    label: 'Total Tickets',
                                    formatter: function(w) {
                                        return {{ $totalTickets ?? 0 }};
                                    }
                                }
                            }
                        }
                    }
                }
            };

            // Initialize ticket chart
            initializeChart("#ticketPieChart", ticketOptions);

            // Order Status Chart configuration
            const orderStatusOptions = {
                series: [{{ $pendingOrders ?? 0 }}, {{ $inProgressOrders ?? 0 }}, {{ $completedOrders ?? 0 }}, {{ $approvedOrders ?? 0 }}, {{ $expiredOrders ?? 0 }}],
                chart: {
                    type: 'pie',
                    height: 350
                },
                labels: ['Pending', 'In Progress', 'Completed', 'Approved', 'Expired'],
                colors: ['#7367f0', '#ff9f43', '#28c76f', '#00cfe8', '#ea5455'],
                legend: {
                    position: 'bottom',
                    labels: {
                        colors: '#a3a9bd'
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                show: false,
                                total: {
                                    show: true,
                                    label: 'Total Orders',
                                    formatter: function() {
                                        return {{ $totalOrders ?? 0 }};
                                    }
                                }
                            }
                        }
                    }
                }
            };

            // Initialize order status chart
            const orderStatusChart = new ApexCharts(document.querySelector("#orderStatusChart"), orderStatusOptions);
            orderStatusChart.render();

            // Initialize orders DataTable
            const ordersTable = $('#ordersTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('contractor.orders.data') }}",
                    data: function(d) {
                        // Add any filters here if needed
                    }
                },
                columns: [
                    {data: 'id', name: 'orders.id'},
                    {data: 'name', name: 'users.name'},
                    {data: 'plan_name', name: 'plans.name'},
                    {data: 'total_inboxes', name: 'total_inboxes'},
                    {data: 'status', name: 'orders.status'},
                    {data: 'created_at', name: 'orders.created_at'},
                    // {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                order: [[5, 'desc']]
            });

            // Initialize activity log DataTable
            const activityTable = $('#activityTable').DataTable({
                responsive:true,
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('contractor.activity.data') }}"
                },
                columns: [
                    {data: 'id', name: 'logs.id'},
                    {data: 'action_type', name: 'logs.action_type', render: function(data) {
                        return `<span class="badge bg-label-primary">${data.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>`;
                    }},
                    {data: 'description', name: 'logs.description'},
                    {data: 'performed_on', name: 'performed_on', render: function(data, type, row) {
                        return `${row.performed_on_type.split('\\').pop()} #${row.performed_on_id}`;
                    }},
                    {data: 'data', name: 'logs.data', render: function(data) {
                        return data ? JSON.stringify(data) : '-';
                    }},
                    {data: 'created_at', name: 'logs.created_at'}
                ],
                order: [[0, 'desc']]
            });

            var options = {
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
                    enabled: false,
                    enabledOnSeries: undefined,
                    shared: true,
                    followCursor: true,
                    intersect: false,
                    inverseOrder: false,
                    custom: undefined,
                    hideEmptySeries: true,
                    fillSeriesColor: false,
                    theme: false,
                    style: {
                        fontSize: '12px',
                        fontFamily: undefined
                    },
                    onDatasetHover: {
                        highlightDataSeries: false,
                    },
                    x: {
                        show: true,
                        format: 'dd MMM',
                        formatter: undefined,
                    },
                    y: {
                        formatter: undefined,
                        // title: {
                        //     formatter: (seriesName) => seriesName,
                        // },
                    },
                    z: {
                        formatter: undefined,
                        title: 'Size: '
                    },
                    marker: {
                        show: true,
                    },
                    // items: {
                    //     display: flex,
                    // },
                    fixed: {
                        enabled: false,
                        position: 'topRight',
                        offsetX: 0,
                        offsetY: 0,
                    },
                }
            };

            // Only initialize salesChart if the element exists
            const salesChartElement = document.querySelector("#salesChart");
            if (salesChartElement) {
                var chart = new ApexCharts(salesChartElement, options);
                chart.render();
            }


            // Only initialize weekBarChart if the element exists
            const weekBarElement = document.querySelector("#weekBarChart");
            if (weekBarElement) {
                var options = {
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

                var chart = new ApexCharts(weekBarElement, options);
                chart.render();
            }




            // Only initialize taskGaugeChart if the element exists
            const taskGaugeElement = document.querySelector("#taskGaugeChart");
            if (taskGaugeElement) {
                var options = {
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

                var chart = new ApexCharts(taskGaugeElement, options);
                chart.render();
            }

        });
    </script>
@endpush
