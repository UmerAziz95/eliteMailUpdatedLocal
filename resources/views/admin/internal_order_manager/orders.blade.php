@extends('admin.layouts.app')

@section('title', 'Internal Orders')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <style>
        .avatar {
            position: relative;
            block-size: 2.5rem;
            cursor: pointer;
            inline-size: 2.5rem;
        }

        .avatar .avatar-initial {
            position: absolute;
                    <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab"
                    tabindex="0">
                    @include('admin.internal_order_manager._orders_table')
                </div>
                @foreach ($plans as $plan)
                    <div class="tab-pane fade" id="plan-{{ $plan->id }}-tab-pane" role="tabpanel"
                        aria-labelledby="plan-{{ $plan->id }}-tab" tabindex="0">
                        @include('admin.internal_order_manager._orders_table', ['plan_id' => $plan->id])
                    </div>
                @endforeach
            </div> flex;
            align-items: center;
            justify-content: center;
            color: var(--second-primary);
            font-size: 1.5rem;
            font-weight: 500;
            inset: 0;
            text-transform: uppercase;
        }

        .nav-tabs .nav-link {
            color: var(--extra-light);
            border: none
        }

        .nav-tabs .nav-link:hover {
            color: var(--white-color);
            border: none
        }

        .nav-tabs .nav-link.active {
            background-color: var(--second-primary);
            color: #fff;
            border: none;
            border-radius: 6px
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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: shimmer 3s infinite;
        }

        /* .draft-alert:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        } */

        /* .draft-alert .alert-icon {
            animation: bounce 1s infinite;
        }

        .draft-alert .btn-close {
            transition: all 0.3s ease;
        }

        .draft-alert .btn-close:hover {
            transform: rotate(90deg) scale(1.1);
        } */

        /* @keyframes slideInFromLeft {
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
            }
            50% {
                box-shadow: 0 0 20px rgba(255, 193, 7, 0.3);
            }
        } */

        /* @keyframes bounce {
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
        } */

        /* Fade out animation for dismiss */
        .draft-alert.fade {
            transition: opacity 0.5s ease-out, transform 0.5s ease-out;
        }

        .draft-alert.fade:not(.show) {
            opacity: 0;
            transform: translateX(-100%);
        }

        /* Select2 Dropdown Styling */
        .select2-container--default .select2-selection--single {
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            height: calc(2.25rem + 2px);
            color: #212529;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #212529;
            line-height: 2.25rem;
            padding-left: 0.75rem;
            padding-right: 0.75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #6c757d;
        }

        .select2-dropdown {
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            color: #212529;
        }

        .select2-container--default .select2-results__option {
            background-color: #fff;
            color: #212529;
            padding: 0.5rem 0.75rem;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #0d6efd;
            color: #fff;
        }

        .select2-container--default .select2-results__option[aria-selected="true"] {
            background-color: #e9ecef;
            color: #212529;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            color: #212529 !important;
            padding: 0.375rem 0.75rem;
        }

        .select2-container--default .select2-results__message {
            color: #6c757d;
            background-color: #fff;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(2.25rem + 2px);
            right: 0.75rem;
        }

        /* Add New User Section Styling */
        #addNewUserSection {
            position: relative;
            z-index: 1;
            margin-top: 0.5rem;
        }

        #addNewUserSection .alert {
            margin-bottom: 0;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid #b3d7ff;
            background-color: #d1ecf1;
            color: #0c5460;
        }

        #addNewUserBtn {
            transition: all 0.2s ease-in-out;
        }

        #addNewUserBtn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        /* Dark mode compatibility if needed */
        @media (prefers-color-scheme: dark) {
            .select2-container--default .select2-selection--single,
            .select2-dropdown,
            .select2-container--default .select2-results__option,
            .select2-container--default .select2-search--dropdown .select2-search__field,
            .select2-container--default .select2-results__message {
                background-color: #212529;
                color: #0000 !important;
                border-color: #495057;
            }

            .select2-container--default .select2-selection--single .select2-selection__placeholder {
                color: #adb5bd;
            }

            .select2-container--default .select2-results__option--highlighted[aria-selected] {
                background-color: #0d6efd;
                color: #fff;
            }

            .select2-container--default .select2-results__option[aria-selected="true"] {
                background-color: #495057;
                color: #fff;
            }
        }
    </style>
@endpush

@section('content')
    <!-- Draft Orders Notification -->
    <!-- @if (isset($draftOrders) && $draftOrders > 0)
    <div class="alert alert-warning alert-dismissible fade show draft-alert py-2" role="alert" style="background-color: rgba(255, 166, 0, 0.359); color: #fff; border: 2px solid orange;">
            <i class="ti ti-alert-triangle me-2 alert-icon"></i>
            <strong>Draft Order{{ $draftOrders != 1 ? 's' : '' }} Alert:</strong>
            You have {{ $draftOrders }} draft order{{ $draftOrders != 1 ? 's' : '' }} available in drafts.
            Please submit the relevant details to complete the order{{ $draftOrders != 1 ? 's' : '' }}.
            <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif -->
    <!-- Draft Orders Notification -->
    @if (isset($draftOrders) && $draftOrders > 0)
        <div id="draftAlert" class="alert alert-warning alert-dismissible fade show draft-alert py-2 rounded-1"
            role="alert" style="background-color: rgba(255, 166, 0, 0.414); color: #fff; border: 2px solid orange;">
            <i class="ti ti-alert-triangle me-2 alert-icon"></i>
            <strong>Draft Order{{ $draftOrders != 1 ? 's' : '' }} Alert:</strong>
            You have {{ $draftOrders }} draft order{{ $draftOrders != 1 ? 's' : '' }}.
            <a href="{{ route('admin.orders') }}" class="text-warning alert-link">View your
                order{{ $draftOrders != 1 ? 's' : '' }}</a> to complete {{ $draftOrders != 1 ? 'them' : 'it' }}.
            <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert"
                aria-label="Close"></button>
        </div>
    @endif

    <!-- Rejected Orders Notification (Initially Hidden) -->
    @if (isset($rejectOrders) && $rejectOrders > 0)
        <div id="rejectedAlert" class="alert alert-danger alert-dismissible fade draft-alert py-2 rounded-1 mt-2"
            role="alert"
            style="background-color: rgba(255, 82, 82, 0.414); color: #fff; border: 2px solid #dc3545; display: none;">
            <i class="ti ti-x-circle me-2 alert-icon"></i>
            <strong>Rejected Order{{ $rejectOrders != 1 ? 's' : '' }} Alert:</strong>
            You have {{ $rejectOrders }} rejected order{{ $rejectOrders != 1 ? 's' : '' }}.
            <a href="{{ route('admin.orders') }}" class="text-danger alert-link" style="color: #ffcccb !important;">View
                your order{{ $rejectOrders != 1 ? 's' : '' }}</a> for more details.
            <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert"
                aria-label="Close"></button>
        </div>
    @endif


    <section class="py-3" data-page="orders">

        <div class="counters mb-4" style="display: none;">
            <div class="card p-3 counter_1">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Orders</h6>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="mb-0 me-2 fs-5">{{ number_format($totalOrders) }}</h4>
                                <!-- <p class="text-{{ $percentageChange >= 0 ? 'success' : 'danger' }} mb-0">({{ $percentageChange >= 0 ? '+' : '' }}{{ number_format($percentageChange, 1) }}%)</p> -->
                            </div>
                            <small class="mb-0" style="font-size: 10px">Total orders placed</small>
                            <!-- <small class="mb-0" style="font-size: 10px">Last week vs previous week</small> -->
                        </div>
                        <div class="avatar">
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/14385/14385008.gif" width="40"
                            style="border-radius: 50px" alt=""> --}}
                            <i class="fa-regular fa-file-lines fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2" style="display: none;">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Pending Orders</h6>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="mb-0 me-2 fs-5">{{ number_format($pendingOrders) }}</h4>
                            </div>
                            <small class="mb-0" style="font-size: 10px">Awaiting admin review</small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-user-check"></i>
                        </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/18873/18873804.gif" width="40"
                            style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-spinner fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_1" style="display: none;">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Completed Orders</h6>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="mb-0 me-2 fs-5">{{ number_format($completedOrders) }}</h4>
                            </div>
                            <small class="mb-0" style="font-size: 10px">Fully processed orders</small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-user-plus"></i>
                        </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/6416/6416374.gif" width="40"
                            style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-check-double fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2" style="display: none;">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">In-Progress Orders</h6>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="mb-0 me-2 fs-5">{{ number_format($inProgressOrders) }}</h4>
                            </div>
                            <small class="mb-0" style="font-size: 10px">Currently processing</small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-user-plus"></i>
                        </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/10282/10282642.gif" width="40"
                            style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-bars-progress fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card p-3 counter_1" style="display: none;">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Expired Orders</h6>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="mb-0 me-2 fs-5">{{ number_format($expiredOrders ?? 0) }}</h4>
                            </div>
                            <small class="mb-0" style="font-size: 10px">Expired orders</small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-user-plus"></i>
                        </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/19005/19005362.gif" width="40"
                            style="border-radius: 50px" alt=""> --}}
                            <i class="fa-brands fa-empire fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2" style="display: none;">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Rejected Orders</h6>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="mb-0 me-2 fs-5">{{ number_format($rejectOrders) }}</h4>
                            </div>
                            <small class="mb-0" style="font-size: 10px">Not approved</small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-user-plus"></i>
                        </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/14697/14697022.gif" width="40"
                            style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-book-skull fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_1" style="display: none;">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Cancelled Orders</h6>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="mb-0 me-2 fs-5">{{ number_format($cancelledOrders) }}</h4>
                            </div>
                            <small class="mb-0" style="font-size: 10px">Orders cancelled</small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-user-plus"></i>
                        </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/15332/15332434.gif" width="40"
                            style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-ban fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2" style="display: none;">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Draft Orders</h6>
                            <div class="d-flex align-items-center mt-1">
                                <h4 class="mb-0 me-2 fs-5">{{ number_format($draftOrders) }}</h4>
                            </div>
                            <small class="mb-0" style="font-size: 10px">Orders in draft</small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-warning">
                            <i class="ti ti-edit"></i>
                        </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/10690/10690672.gif" width="40"
                            style="border-radius: 50px" alt=""> --}}
                            <i class="fa-brands fa-firstdraft fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm p-3">
                    <div>
                        <div class="row gy-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <h5 class="mb-2">Filters</h5>
                                <div>
                                    <button id="applyFilters" class="btn btn-primary btn-sm me-2">Filter</button>
                                    <button id="clearFilters" class="btn btn-secondary btn-sm">Clear</button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="orderIdFilter" class="form-label">Order ID</label>
                                <input type="text" id="orderIdFilter" class="form-control"
                                    placeholder="Search by ID">
                            </div>
                            <div class="col-md-3">
                                <label for="statusFilter" class="form-label">Status</label>
                                <select id="statusFilter" class="form-select">
                                    <option value="">All Statuses</option>
                                    @foreach ($statuses as $key => $status)
                                        <option value="{{ $key }}">{{ ucfirst(str_replace('_', ' ', $key)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3" style="display: none;">
                                <label for="emailFilter" class="form-label">Email</label>
                                <input type="text" id="emailFilter" class="form-control"
                                    placeholder="Search by email">
                            </div>
                            <div class="col-md-3">
                                <label for="domainFilter" class="form-label">Forwarding URL</label>
                                <input type="text" id="domainFilter" class="form-control"
                                    placeholder="Search by forwarding URL">
                            </div>
                            <div class="col-md-3">
                                <label for="totalInboxesFilter" class="form-label">Total Inboxes</label>
                                <input type="number" id="totalInboxesFilter" class="form-control"
                                    placeholder="Search by total inboxes" min="1">
                            </div>
                            <div class="col-md-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" id="startDate" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" id="endDate" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card py-3 px-4">
            <!-- {{-- <h2 class="mb-3">Orders</h2> --}} -->
            <button class="btn btn-primary btn-sm float-start" style="width: fit-content" type="button" id="new-order-btn">
                <a href="{{ url('/admin/internal-order-manager/order') }}">Add Order</a>

            </button>
            <ul class="nav nav-tabs border-0 mb-3" id="myTab" role="tablist" style="display: none;">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-tab-pane"
                        type="button" role="tab" aria-controls="all-tab-pane" aria-selected="true">All
                        Orders</button>
                </li>
                @foreach ($plans as $plan)
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="plan-{{ $plan->id }}-tab" data-bs-toggle="tab"
                            data-bs-target="#plan-{{ $plan->id }}-tab-pane" type="button" role="tab"
                            aria-controls="plan-{{ $plan->id }}-tab-pane"
                            aria-selected="false">{{ $plan->name }}</button>
                    </li>
                @endforeach
            </ul>
                                
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab"
                    tabindex="0">
                    
                    @include('admin.internal_order_manager._orders_table')
                </div>
                @foreach ($plans as $plan)
                    <div class="tab-pane fade" id="plan-{{ $plan->id }}-tab-pane" role="tabpanel"
                        aria-labelledby="plan-{{ $plan->id }}-tab" tabindex="0">
                        @include('admin.internal_order_manager._orders_table', ['plan_id' => $plan->id])
                    </div>
                @endforeach
            </div>
        </div>

        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdmin"
            aria-labelledby="offcanvasAddAdminLabel" aria-modal="true" role="dialog">
            <div class="offcanvas-header" style="border-bottom: 1px solid var(--input-border)">
                <h5 id="offcanvasAddAdminLabel" class="offcanvas-title">View Detail</h5>
                <button class="border-0 bg-transparent" type="button" data-bs-dismiss="offcanvas" aria-label="Close"><i
                        class="fa-solid fa-xmark fs-5"></i></button>
            </div>
            <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">

            </div>
        </div>
        <!-- Action Log Offcanvas -->
        <div class="offcanvas offcanvas-end text-bg-dark" style="min-width: 30rem" tabindex="-1" id="actionLogCanvas"
            aria-labelledby="actionLogLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="actionLogLabel">Action Log</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"
                    aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div >

                    <div class="timeline">
                        <!-- Admin Entry -->
                        <div class="timeline-item">
                            <div class="timeline-icon admin">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>
                            <small class="opacity-75" >July 15, 2025 — 02:30 PM</small>
                            <h6 class="mt-1">Admin</h6>
                            <p class="mb-0">Approved the order and sent confirmation email.</p>
                        </div>

                        <!-- admin Entry -->
                        <div class="timeline-item">
                            <div class="timeline-icon admin">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <small class="opacity-75" >July 14, 2025 — 05:20 PM</small>
                            <h6 class="mt-1">admin</h6>
                            <p class="mb-0">Submitted the order form with custom domain details.</p>
                        </div>

                        <!-- Contractor Entry -->
                        <div class="timeline-item">
                            <div class="timeline-icon contractor">
                                <i class="fa-solid fa-hard-hat"></i>
                            </div>
                            <small class="opacity-75" >July 14, 2025 — 10:00 AM</small>
                            <h6 class="mt-1">Contractor</h6>
                            <p class="mb-0">Started domain configuration and mailbox setup.</p>
                        </div>

                        <!-- Admin Rejected -->
                        <div class="timeline-item">
                            <div class="timeline-icon admin">
                                <i class="fa-solid fa-user-shield"></i>
                            </div>
                            <small class="opacity-75" >July 13, 2025 — 07:45 PM</small>
                            <h6 class="mt-1">Admin</h6>
                            <p class="mb-0">Rejected order due to incorrect MX record submission.</p>
                        </div>

                        <!-- admin Entry -->
                        <div class="timeline-item">
                            <div class="timeline-icon admin">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <small class="opacity-75" >July 14, 2025 — 05:20 PM</small>
                            <h6 class="mt-1">admin</h6>
                            <p class="mb-0">Submitted the order form with custom domain details.</p>
                        </div>

                        <!-- Contractor Entry -->
                        <div class="timeline-item">
                            <div class="timeline-icon contractor">
                                <i class="fa-solid fa-hard-hat"></i>
                            </div>
                            <small class="opacity-75" >July 14, 2025 — 10:00 AM</small>
                            <h6 class="mt-1">Contractor</h6>
                            <p class="mb-0">Started domain configuration and mailbox setup.</p>
                        </div>

                        <!-- admin Entry -->
                        <div class="timeline-item">
                            <div class="timeline-icon admin">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <small class="opacity-75" >July 14, 2025 — 05:20 PM</small>
                            <h6 class="mt-1">admin</h6>
                            <p class="mb-0">Submitted the order form with custom domain details.</p>
                        </div>

                        <!-- Contractor Entry -->
                        <div class="timeline-item">
                            <div class="timeline-icon contractor">
                                <i class="fa-solid fa-hard-hat"></i>
                            </div>
                            <small class="opacity-75" >July 14, 2025 — 10:00 AM</small>
                            <h6 class="mt-1">Contractor</h6>
                            <p class="mb-0">Started domain configuration and mailbox setup.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </section>

    <!-- Assign User Offcanvas -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="assignUserOffcanvas" aria-labelledby="assignUserOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="assignUserOffcanvasLabel">Assign Order to User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <form id="assignUserForm">
                <input type="hidden" id="assignOrderId" name="order_id">
                
                <div class="mb-3">
                    <label for="userSelect" class="form-label">Select User</label>
                    <div class="position-relative">
                        <select class="form-select" id="userSelect" name="user_id" required>
                            <option value="">Search for a user...</option>
                        </select>
                        <button type="button" class="btn btn-sm btn-outline-primary position-absolute" 
                                id="addNewUserBtn" 
                                style="top: 2px; right: 2px; z-index: 1000; padding: 0.25rem 0.5rem; display: none;">
                            <i class="fa-solid fa-user-plus"></i> Add New
                        </button>
                    </div>
                    <div class="form-text">Start typing to search for internal users</div>
                    <div id="addNewUserMessage" class="text-muted small mt-1" style="display: none;">
                        <i class="fa-solid fa-info-circle"></i> No users found. Click "Add New" to create a user.
                    </div>
                </div>
                
                <!-- Selected User Information -->
                <div id="selectedUserInfo" class="mb-3" style="display: none;">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fa-solid fa-user"></i> Selected User Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Name:</strong> <span id="selectedUserName">-</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Email:</strong> <span id="selectedUserEmail">-</span>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <strong>Phone:</strong> <span id="selectedUserPhone">-</span>
                                </div>
                                <div class="col-md-6 mt-2">
                                    <strong>User Type:</strong> <span id="selectedUserType">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New User Creation Form -->
                <div id="newUserFormSection" class="mb-3" style="display: none;">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0">Create New Internal User</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="newUserName" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="newUserName" name="new_user_name">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="newUserEmail" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="newUserEmail" name="new_user_email">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="newUserPhone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="newUserPhone" name="new_user_phone" placeholder="+1234567890">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="newUserPassword" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="newUserPassword" name="new_user_password">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="newUserPasswordConfirmation" class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" id="newUserPasswordConfirmation" name="new_user_password_confirmation">
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-secondary btn-sm" id="cancelNewUserBtn">
                                    <i class="fa-solid fa-times"></i> Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="mt-4 pt-3 border-top">
                    <div class="row g-2">
                        <div class="col-12">
                            <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="offcanvas">
                                <i class="fa-solid fa-times"></i> Cancel
                            </button>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary w-100" id="assignUserBtn">
                                <i class="fa-solid fa-user-plus"></i> <span id="assignBtnText">Assign User</span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Debug AJAX calls
        $(document).ajaxSend(function(event, jqXHR, settings) {
            console.log('AJAX Request:', {
                url: settings.url,
                type: settings.type,
                data: settings.data,
                headers: jqXHR.headers
            });
        });

        function viewOrder(id) {
            window.location.href = `{{ url('/admin/orders/${id}/view') }}`;
        }
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
                            rejectedAlert.style.transition =
                                'opacity 0.5s ease-out, transform 0.5s ease-out';
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

        function initDataTable(planId = '') {
            const tableId = planId ? `#myTable-${planId}` : '#myTable';
            const $table = $(tableId);

            if (!$table.length) return;

            try {
                const table = $table.DataTable({
                    processing: true,
                    serverSide: true,
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
                    autoWidth: false,
                    dom: '<"top"f>rt<"bottom"lip><"clear">',
                    columnDefs: [{
                            targets: 0
                        },
                        {
                            targets: 1
                        },
                        ...(planId ? [] : [{
                            targets: 2
                        }]),
                        {
                            targets: planId ? 2 : 3
                        },
                        {
                            targets: planId ? 3 : 4
                        },
                        {
                            targets: planId ? 4 : 5
                        },
                        {
                            targets: planId ? 5 : 6
                        }
                    ],
                    ajax: {
                        url: "{{ route('admin.internal_order_management.data') }}",
                        type: "GET",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                            'Accept': 'application/json'
                        },
                        data: function(d) {
                            d.plan_id = planId || '';
                            d.orderId = $('#orderIdFilter').val();
                            d.status = $('#statusFilter').val();
                            d.email = $('#emailFilter').val();
                            d.domain = $('#domainFilter').val();
                            d.totalInboxes = $('#totalInboxesFilter').val();
                            d.startDate = $('#startDate').val();
                            d.endDate = $('#endDate').val();
                            return d;
                        }
                    },
                    columns: [{
                            data: 'id',
                            name: 'internal_orders.id'
                        },
                        {
                            data: 'created_at_formatted',
                            name: 'internal_orders.created_at',
                            render: function(data) {
                                return `
                            <div class="d-flex gap-1 align-items-center opacity-50">
                                <i class="ti ti-calendar-month"></i>
                                <span>${data}</span>
                            </div>
                        `;
                            }
                        },
                        ...(planId ? [] : [{
                            data: 'plan_name',
                            name: 'plan_name',
                            render: function(data) {
                                return `
                            <div class="d-flex gap-1 align-items-center">
                                <img src="https://cdn-icons-png.flaticon.com/128/11890/11890970.png" style="width: 15px" alt="">
                                <span>${data}</span>
                            </div>
                        `;
                            }
                        }]),
                        {
                            data: 'assigned_to',
                            name: 'assigned_to',
                            render: function(data) {
                                return data || 'Unassigned';
                            }
                        },
                        {
                            data: 'total_inboxes',
                            name: 'total_inboxes'
                        },
                        {
                            data: 'status_badge',
                            name: 'internal_orders.status_manage_by_admin'
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        }
                    ],
                    order: [
                        [1, 'desc']
                    ],
                    createdRow: function(row, data) {
                        const $tooltipAnchor = $(row).find(`#tooltip-anchor-${data.id}`);
                        if ($tooltipAnchor.length) {
                            const tooltipInstance = new bootstrap.Tooltip($tooltipAnchor[0], {
                                trigger: 'manual',
                                placement: 'right'
                            });

                            $(row).on('mouseenter', () => tooltipInstance.show());
                            $(row).on('mouseleave', () => tooltipInstance.hide());
                        }
                    },
                    drawCallback: function() {
                        $('[data-bs-toggle="tooltip"]').tooltip(); // Bootstrap native init
                    }
                });

                return table;

            } catch (error) {
                console.error('Error initializing DataTable:', error);
                toastr.error('Error initializing table. Please refresh the page.');
            }
        }





        $(document).ready(function() {
            try {

                // Initialize DataTables object to store all table instances
                window.orderTables = {};

                // Initialize table for all orders
                window.orderTables.all = initDataTable();

                // Initialize tables for each plan
                @foreach ($plans as $plan)
                    window.orderTables['plan{{ $plan->id }}'] = initDataTable('{{ $plan->id }}');
                @endforeach

                // Handle tab changes
                $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                    const tabId = $(e.target).attr('id');
                    console.log('Tab changed to:', tabId);

                    // Clear DataTables events before reapplying
                    Object.values(window.orderTables).forEach(function(table) {
                        if (table) {
                            table.off('preXhr.dt');
                        }
                    });

                    // Force recalculation of column widths for visible tables
                    setTimeout(function() {
                        Object.values(window.orderTables).forEach(function(table) {
                            if (table && $(table.table().node()).is(':visible')) {
                                try {
                                    // Add filter parameters before redraw
                                    table.on('preXhr.dt', function(e, settings, data) {
                                        data.orderId = $('#orderIdFilter').val();
                                        data.status = $('#statusFilter').val();
                                        data.email = $('#emailFilter').val();
                                        data.domain = $('#domainFilter').val();
                                        data.totalInboxes = $('#totalInboxesFilter')
                                            .val();
                                        data.startDate = $('#startDate').val();
                                        data.endDate = $('#endDate').val();
                                    });

                                    table.columns.adjust();
                                    if (table.responsive && typeof table.responsive
                                        .recalc === 'function') {
                                        table.responsive.recalc();
                                    }
                                    table.draw();
                                } catch (error) {
                                    console.error('Error adjusting table:', error);
                                }
                            }
                        });
                    }, 100); // Increased timeout to ensure DOM is ready
                });

                // Initial column adjustment for the active tab
                setTimeout(function() {
                    try {
                        const activeTable = $('.tab-pane.active .table').DataTable();
                        if (activeTable) {
                            activeTable.columns.adjust();
                            if (activeTable.responsive && typeof activeTable.responsive.recalc ===
                                'function') {
                                activeTable.responsive.recalc();
                            }
                            console.log('Initial column adjustment for active table completed');
                        }
                    } catch (error) {
                        console.error('Error in initial column adjustment:', error);
                    }
                }, 100);

                // Add global error handler for AJAX requests
                $(document).ajaxError(function(event, xhr, settings, error) {
                    console.error('AJAX Error:', error);
                    if (xhr.status === 401) {
                        window.location.href = "{{ route('login') }}";
                    } else if (xhr.status === 403) {
                        toastr.error('You do not have permission to perform this action');
                    }
                });

                // Filter functionality
                function applyFilters() {
                    // Clear previous event handlers
                    Object.values(window.orderTables).forEach(function(table) {
                        table.off('preXhr.dt');
                    });

                    Object.values(window.orderTables).forEach(function(table) {
                        if ($(table.table().node()).is(':visible')) {
                            // Add filter parameters
                            table.on('preXhr.dt', function(e, settings, data) {
                                data.orderId = $('#orderIdFilter').val();
                                data.status = $('#statusFilter').val();
                                data.email = $('#emailFilter').val();
                                data.domain = $('#domainFilter').val();
                                data.totalInboxes = $('#totalInboxesFilter').val();
                                data.startDate = $('#startDate').val();
                                data.endDate = $('#endDate').val();
                            });

                            table.draw();
                        }
                    });
                }

                // Apply filters button click handler
                $('#applyFilters').on('click', function() {
                    applyFilters();
                });

                // Clear filters
                $('#clearFilters').on('click', function() {
                    $('#orderIdFilter, #emailFilter, #domainFilter').val('');
                    $('#statusFilter').val('');
                    $('#startDate, #endDate').val('');
                    applyFilters();
                });

                // Handle status updates for internal orders
                $(document).on('click', '.update-status', function(e) {
                    e.preventDefault();
                    
                    const orderId = $(this).data('order-id');
                    const status = $(this).data('status');
                    
                    if (!orderId || !status) {
                        toastr.error('Invalid order or status data');
                        return;
                    }
                    
                    if (confirm(`Are you sure you want to change the status to "${status}"?`)) {
                        $.ajax({
                            url: "{{ route('admin.internal_order_management.update_status') }}",
                            type: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                'Accept': 'application/json'
                            },
                            data: {
                                order_id: orderId,
                                status: status
                            },
                            success: function(response) {
                                if (response.success) {
                                    toastr.success(response.message || 'Status updated successfully');
                                    // Refresh all active tables
                                    Object.values(window.orderTables).forEach(function(table) {
                                        if (table && $(table.table().node()).is(':visible')) {
                                            table.draw();
                                        }
                                    });
                                } else {
                                    toastr.error(response.message || 'Failed to update status');
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Status update error:', error);
                                let message = 'Failed to update status';
                                
                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                    message = xhr.responseJSON.message;
                                } else if (xhr.status === 422) {
                                    message = 'Invalid data provided';
                                } else if (xhr.status === 403) {
                                    message = 'You do not have permission to perform this action';
                                }
                                
                                toastr.error(message);
                            }
                        });
                    }
                });

                // Handle delete functionality for internal orders
                $(document).on('click', '.delete-order', function(e) {
                    e.preventDefault();
                    
                    const orderId = $(this).data('order-id');
                    const orderUser = $(this).data('order-user');
                    
                    if (!orderId) {
                        toastr.error('Invalid order data');
                        return;
                    }
                    
                    // Use SweetAlert2 for better confirmation dialog
                    Swal.fire({
                        title: 'Are you sure?',
                        html: `You are about to delete internal order <strong>#${orderId}</strong> for <strong>${orderUser}</strong>.<br><br>This action cannot be undone!`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel',
                        focusCancel: true
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading
                            Swal.fire({
                                title: 'Deleting...',
                                text: 'Please wait while we delete the internal order.',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false,
                                showConfirmButton: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            $.ajax({
                                url: "{{ route('admin.internal_order_management.delete') }}",
                                type: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                                    'Accept': 'application/json'
                                },
                                data: {
                                    order_id: orderId
                                },
                                success: function(response) {
                                    Swal.close();
                                    if (response.success) {
                                        Swal.fire({
                                            title: 'Deleted!',
                                            text: response.message || 'Internal order has been deleted successfully.',
                                            icon: 'success',
                                            timer: 3000,
                                            showConfirmButton: false
                                        });
                                        
                                        // Refresh all active tables
                                        Object.values(window.orderTables).forEach(function(table) {
                                            if (table && $(table.table().node()).is(':visible')) {
                                                table.draw();
                                            }
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: response.message || 'Failed to delete internal order',
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                },
                                error: function(xhr, status, error) {
                                    Swal.close();
                                    console.error('Delete error:', error);
                                    let message = 'Failed to delete internal order';
                                    
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        message = xhr.responseJSON.message;
                                    } else if (xhr.status === 422) {
                                        message = 'Invalid data provided';
                                    } else if (xhr.status === 403) {
                                        message = 'You do not have permission to perform this action';
                                    } else if (xhr.status === 404) {
                                        message = 'Internal order not found';
                                    }
                                    
                                    Swal.fire({
                                        title: 'Error!',
                                        text: message,
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            });
                        }
                    });
                });

            } catch (error) {
                console.error('Error in document ready:', error);
            }
        });
        
        // Assign User Functionality
        $(document).ready(function() {
            // Initialize Select2 for user selection
            $('#userSelect').select2({
                ajax: {
                    url: '{{ route('admin.internal_order_management.get_users') }}',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            search: params.term,
                            page: params.page || 1
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        
                        // Store the search term and results count for later use
                        window.lastSearchTerm = params.term || '';
                        window.lastResultsCount = data.results ? data.results.length : 0;
                        
                        // Auto-reset dropdown when new results are found
                        if (params.term && params.term.length > 2 && window.lastResultsCount > 0) {
                            // Hide user info if showing
                            $('#selectedUserInfo').hide();
                        }
                        
                        // Show/hide Add New User button and message based on results
                        setTimeout(() => {
                            if (params.term && params.term.length > 2 && window.lastResultsCount === 0) {
                                $('#addNewUserBtn').show();
                                $('#addNewUserMessage').show();
                                console.log('Showing Add New User button - no results found for:', params.term);
                            } else if (!params.term || params.term.length <= 2) {
                                $('#addNewUserBtn').hide();
                                $('#addNewUserMessage').hide();
                            } else if (window.lastResultsCount > 0) {
                                $('#addNewUserBtn').hide();
                                $('#addNewUserMessage').hide();
                            }
                        }, 300);
                        
                        return {
                            results: data.results,
                            pagination: {
                                more: data.pagination.more
                            }
                        };
                    },
                    cache: true
                },
                placeholder: 'Search for a user...',
                minimumInputLength: 0,
                allowClear: true,
                dropdownParent: $('#assignUserOffcanvas')
            });

            // Handle user selection
            $('#userSelect').on('select2:select', function(e) {
                const data = e.params.data;
                console.log('User selected:', data);
                
                // Show selected user information
                if (data.id && data.text) {
                    $('#selectedUserName').text(data.name || data.text);
                    $('#selectedUserEmail').text(data.email || 'N/A');
                    $('#selectedUserPhone').text(data.phone || 'N/A');
                    $('#selectedUserType').text(data.is_internal ? 'Internal User' : 'External User');
                    $('#selectedUserInfo').show();
                    
                    // Hide add new user elements
                    $('#addNewUserBtn').hide();
                    $('#addNewUserMessage').hide();
                }
            });

            // Handle Select2 events for better UX
            $('#userSelect').on('select2:open', function() {
                // Don't hide the button/message when opening, let the user continue interaction
                console.log('Select2 opened');
            });

            $('#userSelect').on('select2:close', function() {
                // Keep the Add New User button visible even after closing if no results were found
                console.log('Select2 closed, last search term:', window.lastSearchTerm, 'results count:', window.lastResultsCount);
                
                // Only hide if we have results or no search was performed
                if (!window.lastSearchTerm || window.lastSearchTerm.length <= 2 || window.lastResultsCount > 0) {
                    $('#addNewUserBtn').hide();
                    $('#addNewUserMessage').hide();
                }
                // If we searched for something and found no results, keep the "Add New User" button visible
            });

            // Handle clearing of the select
            $('#userSelect').on('select2:clear', function() {
                $('#addNewUserBtn').hide();
                $('#addNewUserMessage').hide();
                $('#selectedUserInfo').hide();
                window.lastSearchTerm = '';
                window.lastResultsCount = 0;
            });

            // Handle Add New User button click
            $('#addNewUserBtn').on('click', function() {
                console.log('Add New User button clicked');
                $('#addNewUserBtn').hide();
                $('#addNewUserMessage').hide();
                $('#newUserFormSection').show();
                $('#userSelect').prop('disabled', true);
                $('#assignBtnText').text('Create & Assign User');
                
                // Hide selected user info and reset dropdown
                $('#selectedUserInfo').hide();
                $('#userSelect').val(null).trigger('change');
                
                // Clear existing values and focus on name field
                $('#newUserName').val('');
                $('#newUserEmail').val('');
                $('#newUserPhone').val('');
                $('#newUserPassword').val('');
                $('#newUserPasswordConfirmation').val('');
                $('#newUserInternal').prop('checked', true);
                
                // Enable required attributes for new user form fields
                $('#newUserName, #newUserEmail, #newUserPassword, #newUserPasswordConfirmation').attr('required', true);
                
                // Focus on the first field after a short delay
                setTimeout(() => {
                    $('#newUserName').focus();
                }, 200);
                
                console.log('New user form section should now be visible');
            });

            // Handle Cancel New User button click
            $('#cancelNewUserBtn').on('click', function() {
                $('#newUserFormSection').hide();
                $('#userSelect').prop('disabled', false);
                $('#assignBtnText').text('Assign User');
                
                // Clear form fields
                $('#newUserName').val('');
                $('#newUserEmail').val('');
                $('#newUserPhone').val('');
                $('#newUserPassword').val('');
                $('#newUserPasswordConfirmation').val('');
                
                // Disable required attributes for new user form fields when hiding
                $('#newUserName, #newUserEmail, #newUserPassword, #newUserPasswordConfirmation').removeAttr('required');
                
                // Show the button again if we still have no results
                if (window.lastSearchTerm && window.lastSearchTerm.length > 2 && window.lastResultsCount === 0) {
                    $('#addNewUserBtn').show();
                    $('#addNewUserMessage').show();
                }
            });
            // Handle assign user button click
            $(document).on('click', '.assign-user-btn', function(e) {
                e.preventDefault();
                const orderId = $(this).data('order-id');
                $('#assignOrderId').val(orderId);
                $('#userSelect').val(null).trigger('change');
                $('#addNewUserBtn').hide();
                $('#addNewUserMessage').hide();
                $('#selectedUserInfo').hide();
                $('#newUserFormSection').hide();
                $('#userSelect').prop('disabled', false);
                $('#assignBtnText').text('Assign User');
                
                // Remove required attributes from new user form fields since form is hidden
                $('#newUserName, #newUserEmail, #newUserPassword, #newUserPasswordConfirmation').removeAttr('required');
            });

            // Handle assign user form submission
            $('#assignUserForm').on('submit', function(e) {
                e.preventDefault();
                
                const isCreatingNewUser = $('#newUserFormSection').is(':visible');
                let formData;
                
                if (isCreatingNewUser) {
                    // Validate new user fields
                    const name = $('#newUserName').val().trim();
                    const email = $('#newUserEmail').val().trim();
                    const phone = $('#newUserPhone').val().trim();
                    const password = $('#newUserPassword').val();
                    const passwordConfirmation = $('#newUserPasswordConfirmation').val();
                    
                    if (!name || !email || !password || !passwordConfirmation) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Please fill in all required fields',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    if (password !== passwordConfirmation) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Passwords do not match',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    // Create and assign new user
                    formData = {
                        order_id: $('#assignOrderId').val(),
                        create_new_user: true,
                        new_user_name: name,
                        new_user_email: email,
                        new_user_phone: phone,
                        new_user_password: password,
                        new_user_password_confirmation: passwordConfirmation,
                        new_user_internal: $('#newUserInternal').is(':checked') ? 1 : 0,
                        _token: '{{ csrf_token() }}'
                    };
                    
                    $('#assignUserBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Creating User...');
                } else {
                    // Assign existing user
                    if (!$('#userSelect').val()) {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Please select a user to assign',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        return;
                    }
                    
                    formData = {
                        order_id: $('#assignOrderId').val(),
                        user_id: $('#userSelect').val(),
                        _token: '{{ csrf_token() }}'
                    };
                    
                    $('#assignUserBtn').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Assigning...');
                }

                $.ajax({
                    url: '{{ route('admin.internal_order_management.assign_user') }}',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            // Close offcanvas
                            const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('assignUserOffcanvas'));
                            offcanvas.hide();

                            // Show success message
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });

                            // Refresh all DataTables
                            Object.values(window.orderTables).forEach(function(table) {
                                if (table && typeof table.ajax.reload === 'function') {
                                    table.ajax.reload(null, false);
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        let message = 'Failed to assign user to order';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            title: 'Error!',
                            text: message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    },
                    complete: function() {
                        // Re-enable submit button
                        const btnText = $('#newUserFormSection').is(':visible') ? 'Create & Assign User' : 'Assign User';
                        $('#assignUserBtn').prop('disabled', false).html('<i class="fa-solid fa-user-plus"></i> ' + btnText);
                    }
                });
            });

            // Reset form when offcanvas is hidden
            $('#assignUserOffcanvas').on('hidden.bs.offcanvas', function() {
                $('#assignUserForm')[0].reset();
                $('#userSelect').val(null).trigger('change');
                $('#addNewUserBtn').hide();
                $('#addNewUserMessage').hide();
                $('#selectedUserInfo').hide();
                $('#newUserFormSection').hide();
                $('#userSelect').prop('disabled', false);
                $('#assignBtnText').text('Assign User');
                
                // Clear new user form fields
                $('#newUserName').val('');
                $('#newUserEmail').val('');
                $('#newUserPhone').val('');
                $('#newUserPassword').val('');
                $('#newUserInternal').prop('checked', true);
                
                // Remove required attributes from new user form fields
                $('#newUserName, #newUserEmail, #newUserPassword, #newUserPasswordConfirmation').removeAttr('required');
                
                // Reset search variables
                window.lastSearchTerm = '';
                window.lastResultsCount = 0;
            });

            // Password confirmation validation
            $('#newUserPassword, #newUserPasswordConfirmation').on('input', function() {
                const password = $('#newUserPassword').val();
                const confirmation = $('#newUserPasswordConfirmation').val();
                const confirmationField = $('#newUserPasswordConfirmation');
                
                if (confirmation) {
                    if (password === confirmation) {
                        confirmationField.removeClass('is-invalid').addClass('is-valid');
                        confirmationField.next('.invalid-feedback').hide();
                    } else {
                        confirmationField.removeClass('is-valid').addClass('is-invalid');
                        if (!confirmationField.next('.invalid-feedback').length) {
                            confirmationField.after('<div class="invalid-feedback">Passwords do not match</div>');
                        }
                        confirmationField.next('.invalid-feedback').show();
                    }
                } else {
                    confirmationField.removeClass('is-valid is-invalid');
                    confirmationField.next('.invalid-feedback').hide();
                }
            });
        });
    </script>

    <style>
        .dt-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 1rem;
            border-radius: 4px;
        }

        .loading {
            position: relative;
            pointer-events: none;
            opacity: 0.6;
        }
    </style>
@endpush
