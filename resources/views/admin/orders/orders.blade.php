@extends('admin.layouts.app')

@section('title', 'Orders')

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
        display: flex;
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
</style>
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
<style>
    input,
    .form-control,
    .form-label {
        font-size: 12px
    }

    small {
        font-size: 11px
    }

    .total {
        color: var(--second-primary);
    }

    .used {
        color: #43C95C;
    }

    .remain {
        color: orange
    }

    .accordion {
        --bs-accordion-bg: transparent !important;
    }

    .accordion-button:focus {
        box-shadow: none !important
    }

    .button.collapsed {
        background-color: var(--slide-bg) !important;
        color: var(--light-color)
    }

    .button {
        background-color: var(--second-primary);
        color: var(--light-color);
        transition: all ease .4s
    }

    .accordion-body {
        color: var(--light-color)
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6c757d;
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    /* Loading state styling */
    #loadingState {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 4rem 2rem;
    }

    /* Fix offcanvas backdrop issues */
    .offcanvas-backdrop {
        transition: opacity 0.15s linear !important;
    }

    .offcanvas-backdrop.fade {
        opacity: 0;
    }

    .offcanvas-backdrop.show {
        opacity: 0.5;
    }

    /* Ensure body doesn't keep backdrop classes */
    body:not(.offcanvas-open) {
        overflow: visible !important;
        padding-right: 0 !important;
    }

    /* Fix any remaining backdrop elements */
    .modal-backdrop,
    .offcanvas-backdrop.fade:not(.show) {
        display: none !important;
    }

    /* Ensure offcanvas doesn't interfere with page interaction */
    .offcanvas.hiding,
    .offcanvas:not(.show) {
        pointer-events: none;
    }

    /* Force cleanup of backdrop opacity */
    .offcanvas-backdrop.fade {
        opacity: 0 !important;
        transition: opacity 0.15s linear;
    }

    /* Ensure page remains interactive */
    body:not(.offcanvas-open):not(.modal-open) {
        overflow: visible !important;
        padding-right: 0 !important;
    }

    /* Hide any orphaned backdrop elements */
    div[class*="backdrop"]:empty {
        display: none !important;
    }

    /* Timer badge styling */
    .timer-badge {
        font-family: 'Courier New', monospace;
        font-weight: bold;
        font-size: 11px;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        transition: all 0.3s ease;
        border: 1px solid transparent;
        letter-spacing: 0.5px;
        min-width: 70px;
        justify-content: center;
        margin-left: 8px;
    }

    /* Timer states */
    .timer-badge.positive {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border-color: rgba(40, 167, 69, 0.3);
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
    }

    .timer-badge.negative {
        background: linear-gradient(135deg, #dc3545, #fd7e14);
        color: white;
        border-color: rgba(220, 53, 69, 0.3);
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        animation: pulse-red 2s infinite;
    }

    .timer-badge.completed {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
        border-color: rgba(108, 117, 125, 0.3);
        box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);
    }

    /* Pulse animation for overdue timers */
    @keyframes pulse-red {
        0% {
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }
        50% {
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.4);
            transform: scale(1.02);
        }
        100% {
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }
    }

    /* Timer icon styling */
    .timer-icon {
        font-size: 10px;
        margin-right: 2px;
    }

    /* Hover effects */
    .timer-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }
</style>
@endpush

@section('content')
<section class="py-3">

    <div class="row gy-3 mb-4">
        <div class="counters col-lg-6" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)) !important">
            <div class="card p-3 counter_1">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-3">{{ number_format($totalOrders) }}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-user-search"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/14385/14385008.gif" width="40"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-regular fa-file-lines fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Pending</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-3">{{ number_format($pendingOrders) }}</h4>
                                <p class="text-danger mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
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

            <div class="card p-3 counter_1">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Complete</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-3">{{ number_format($inProgressOrders) }}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
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

            <div class="card p-3 counter_2">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">In-Progress</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-3">{{ number_format($inProgressOrders) }}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
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

            <div class="card p-3 counter_1">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Cancelled</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-3">{{ number_format($cancelledOrders) }}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
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

            <div class="card p-3 counter_1">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Rejected</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-3">{{ number_format($rejectOrders) }}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/15332/15332434.gif" width="40"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-book-skull fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Draft</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-3">{{ number_format($draftOrders) }}</h4>
                                <p class="text-warning mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-edit"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/10690/10690672.gif" width="40"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-ban fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="p-3 h-100 filter">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="mb-2 text-white">Filters</h6>
                </div>

                <div class="d-flex align-items-start gap-4">
                    <div class="row gy-3">
                        <div class="col-md-6 col-lg-4">
                            <label for="orderIdFilter" class="form-label mb-0">Order ID</label>
                            <input type="text" id="orderIdFilter" class="form-control" placeholder="Search by ID">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="statusFilter" class="form-label mb-0">Status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $key => $status)
                                <option value="{{ $key }}">{{ ucfirst(str_replace('_', ' ', $key)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="emailFilter" class="form-label mb-0">Email</label>
                            <input type="text" id="emailFilter" class="form-control" placeholder="Search by email">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="domainFilter" class="form-label mb-0">Domain URL</label>
                            <input type="text" id="domainFilter" class="form-control" placeholder="Search by domain">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="totalInboxesFilter" class="form-label mb-0">Total Inboxes</label>
                            <input type="number" id="totalInboxesFilter" class="form-control"
                                placeholder="Search by total inboxes" min="1">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="startDate" class="form-label mb-0">Start Date</label>
                            <input type="date" id="startDate" class="form-control">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="endDate" class="form-label mb-0">End Date</label>
                            <input type="date" id="endDate" class="form-control">
                        </div>
                        <div class="d-flex align-items-center justify-content-end">
                            <button id="applyFilters"
                                class="btn btn-primary btn-sm me-2 px-4 border-0">Filter</button>
                            <button id="clearFilters" class="btn btn-secondary btn-sm px-4">Clear</button>
                        </div>
                    </div>

                    {{-- <img src="https://cdn-icons-gif.flaticon.com/19009/19009016.gif" width="30%"
                        style="border-radius: 50%" class="d-none d-sm-block" alt=""> --}}
                </div>

            </div>
        </div>
    </div>


    <div class="card py-3 px-4">
        <ul class="nav nav-tabs border-0 mb-3" id="myTab" role="tablist">
            <div class="dropdown">
                <button class="btn btn-primary shadow dropdown-toggle" style="width: fit-content" type="button"
                    id="plansDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    Select Plan
                </button>
                <ul class="dropdown-menu" aria-labelledby="plansDropdown">
                    <li>
                        <a class="dropdown-item active text-capitalize" id="all-tab" data-bs-toggle="tab"
                            href="#all-tab-pane" role="tab" aria-controls="all-tab-pane" aria-selected="true">All
                            Orders</a>
                    </li>
                    @foreach($plans as $plan)
                    <li>
                        <a class="dropdown-item text-capitalize" id="plan-{{ $plan->id }}-tab" data-bs-toggle="tab"
                            href="#plan-{{ $plan->id }}-tab-pane" role="tab"
                            aria-controls="plan-{{ $plan->id }}-tab-pane" aria-selected="false">{{ $plan->name }}</a>
                    </li>
                    @endforeach
                </ul>
            </div>

        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab"
                tabindex="0">
                @include('admin.orders._orders_table')
            </div>
            @foreach($plans as $plan)
            <div class="tab-pane fade" id="plan-{{ $plan->id }}-tab-pane" role="tabpanel"
                aria-labelledby="plan-{{ $plan->id }}-tab" tabindex="0">
                @include('admin.orders._orders_table', ['plan_id' => $plan->id])
            </div>
            @endforeach
        </div>
    </div>

    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdmin" aria-labelledby="offcanvasAddAdminLabel"
        aria-modal="true" role="dialog">
        <div class="offcanvas-header" style="border-bottom: 1px solid var(--input-border)">
            <h5 id="offcanvasAddAdminLabel" class="offcanvas-title">View Detail</h5>
            <button class="border-0 bg-transparent" type="button" data-bs-dismiss="offcanvas" aria-label="Close"><i
                    class="fa-solid fa-xmark fs-5"></i></button>
        </div>
        <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">

        </div>
    </div>
</section>
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

    // Timer calculation functions
    function calculateOrderTimer(createdAt, status, completedAt = null, timerStartedAt = null) {
        const now = new Date();
        
        // Use timer_started_at if available, otherwise fall back to created_at
        const startTime = timerStartedAt ? new Date(timerStartedAt) : new Date(createdAt);
        const twelveHoursLater = new Date(startTime.getTime() + (12 * 60 * 60 * 1000));
        
        // If order is completed, timer is paused - show the time it took to complete
        if (status === 'completed' && completedAt) {
            const completionDate = new Date(completedAt);
            const timeTaken = completionDate - startTime;
            const isOverdue = completionDate > twelveHoursLater;
            
            return {
                display: formatTimeDuration(timeTaken),
                isNegative: isOverdue,
                isCompleted: true,
                class: 'completed'
            };
        }
        
        // If order is completed but no completion date, just show completed
        if (status === 'completed') {
            return {
                display: 'Completed',
                isNegative: false,
                isCompleted: true,
                class: 'completed'
            };
        }
        
        // For active orders: 12-hour countdown from timer_started_at (or created_at as fallback)
        // - Counts down from 12:00:00 to 00:00:00
        // - After reaching zero, continues in negative time (overtime)
        const timeDiff = now - twelveHoursLater;
        
        if (timeDiff > 0) {
            // Order is overdue (negative time - overtime)
            return {
                display: '-' + formatTimeDuration(timeDiff),
                isNegative: true,
                isCompleted: false,
                class: 'negative'
            };
        } else {
            // Order still has time remaining (countdown)
            return {
                display: formatTimeDuration(-timeDiff),
                isNegative: false,
                isCompleted: false,
                class: 'positive'
            };
        }
    }

    // Format time duration in countdown format (HH:MM:SS)
    function formatTimeDuration(milliseconds) {
        const totalSeconds = Math.floor(Math.abs(milliseconds) / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        
        // Format with leading zeros for proper countdown display
        const hoursStr = hours.toString().padStart(2, '0');
        const minutesStr = minutes.toString().padStart(2, '0');
        const secondsStr = seconds.toString().padStart(2, '0');
        
        return `${hoursStr}:${minutesStr}:${secondsStr}`;
    }

    // Format date for display
    function formatDate(dateString) {
        const date = new Date(dateString);
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('en-US', options);
    }

    // Create timer badge HTML
    function createTimerBadge(timerData) {
        const timer = calculateOrderTimer(timerData.created_at, timerData.status, timerData.completed_at, timerData.timer_started_at);
        const iconClass = timer.isCompleted ? 'fas fa-check' : (timer.isNegative ? 'fas fa-exclamation-triangle' : 'fas fa-clock');
        
        // Create tooltip text
        let tooltip = '';
        if (timer.isCompleted) {
            tooltip = timerData.completed_at 
                ? `Order completed on ${formatDate(timerData.completed_at)}` 
                : 'Order is completed';
        } else if (timer.isNegative) {
            tooltip = `Order is overdue by ${timer.display.substring(1)} (overtime). Created on ${formatDate(timerData.created_at)}`;
        } else {
            tooltip = `Time remaining: ${timer.display} (12-hour countdown). Order created on ${formatDate(timerData.created_at)}`;
        }
        
        return `
            <span class="timer-badge ${timer.class}" 
                  data-order-id="${timerData.order_id}" 
                  data-created-at="${timerData.created_at}" 
                  data-status="${timerData.status}" 
                  data-completed-at="${timerData.completed_at || ''}"
                  title="${tooltip}">
                <i class="${iconClass} timer-icon"></i>
                ${timer.display}
            </span>
        `;
    }

    function initDataTable(planId = '') {
        console.log('Initializing DataTable for planId:', planId);
        var tableId = planId ? `#myTable-${planId}` : '#myTable';
        console.log('Looking for table with selector:', tableId);
        var $table = $(tableId);
        if (!$table.length) {
            console.error('Table not found with selector:', tableId);
            return null;
        }
        console.log('Found table:', $table);

        try {
            var table = $table.DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,
                dom: '<"top"f>rt<"bottom"lip><"clear">',
                columnDefs: [{
                        width: '10%',
                        targets: 0
                    }, // ID 
                    {
                        width: '15%',
                        targets: 1
                    }, // Date
                    {
                        width: '15%',
                        targets: 2
                    }, // Name
                    {
                        width: '15%',
                        targets: 3
                    }, // Email
                    ...(planId ? [] : [{
                        width: '15%',
                        targets: 4
                    }]), // Plan (only for All Orders) 
                    {
                        width: '20%',
                        targets: planId ? 4 : 5
                    }, // Domain URL
                    {
                        width: '15%',
                        targets: planId ? 5 : 6
                    }, // Total Inboxes 
                    {
                        width: '15%',
                        targets: planId ? 6 : 7
                    }, // Status
                    {
                        width: '10%',
                        targets: planId ? 7 : 8
                    } // Actions
                ],
                ajax: {
                    url: "{{ route('admin.orders.data') }}",
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: function(d) {
                        d.draw = d.draw || 1;
                        d.length = d.length || 10;
                        d.start = d.start || 0;
                        d.search = d.search || {
                            value: '',
                            regex: false
                        };
                        d.plan_id = planId;

                        // Add filter parameters
                        d.orderId = $('#orderIdFilter').val();
                        d.name = $('#nameFilter').val();
                        d.status = $('#statusFilter').val();
                        d.email = $('#emailFilter').val();
                        d.domain = $('#domainFilter').val();
                        d.totalInboxes = $('#totalInboxesFilter').val();
                        d.startDate = $('#startDate').val();
                        d.endDate = $('#endDate').val();

                        console.log('DataTables request parameters:', d);
                        return d;
                    },
                    dataSrc: function(json) {
                        console.log('Server response:', json);
                        return json.data;
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables error:', error);
                        console.error('Server response:', xhr.responseText);
                        console.error('Status:', xhr.status);
                        console.error('Full XHR:', xhr);

                        if (xhr.status === 401) {
                            window.location.href = "{{ route('login') }}";
                        } else if (xhr.status === 403) {
                            toastr.error('You do not have permission to view this data');
                        } else {
                            toastr.error('Error loading orders data: ' + error);
                        }
                    }
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
                                <div class="d-flex align-items-center gap-1 text-nowrap">
                                    <i class="ti ti-calendar-month fs-5"></i>
                                    <span>${data}</span>
                                </div>
                            `;
                        }
                    },

                    { 
                        data: 'name', name: 'name' ,
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex gap-1">
                                    <div>
                                        <img src="https://cdn-icons-png.flaticon.com/128/2202/2202112.png" style="width: 35px" alt="">
                                    </div>
                                    <span>${data}</span>    
                                </div>
                            `;
                        }
                    },
                    { 
                        data: 'email', name: 'email' ,
                            render: function(data, type, row) {
                                return `
                                    <div class="d-flex align-items-center gap-1">
                                        <i style= "color: #00BBFF"; class="ti ti-mail fs-5"></i>
                                        <span style= "color: #00BBFF";>${data}</span>    
                                    </div>
                                `;
                            }
                    },
                    ...(planId ? [] : [{
                        data: 'plan_name',
                        name: 'plans.name',
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex gap-1">
                                    <div>
                                        <img src="https://cdn-icons-png.flaticon.com/128/7756/7756169.png" style="width: 25px" alt="">
                                    </div>
                                    <span>${data}</span>    
                                </div>
                            `;
                        }
                    }]),
                    {
                        data: 'split_counts',
                        name: 'split_counts',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'total_inboxes',
                        name: 'total_inboxes'
                    },
                    {
                        data: 'timer',
                        name: 'timer',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            try {
                                const timerData = JSON.parse(data);
                                return createTimerBadge(timerData);
                            } catch (e) {
                                return '<span class="timer-badge completed">N/A</span>';
                            }
                        }
                    },
                    {
                        data: 'status',
                        name: 'orders.status'
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
                drawCallback: function(settings) {
                    console.log('Table draw complete. Response:', settings.json);
                    if (settings.json && settings.json.error) {
                        toastr.error(settings.json.message || 'Error loading data');
                    }
                    $('[data-bs-toggle="tooltip"]').tooltip();

                    // Only adjust columns
                    this.api().columns.adjust();
                },
                initComplete: function(settings, json) {
                    console.log('Table initialization complete');
                    this.api().columns.adjust();
                }
            });

            // Handle processing state visually
            table.on('processing.dt', function(e, settings, processing) {
                const wrapper = $(tableId + '_wrapper');
                if (processing) {
                    console.log('DataTable processing started');
                    wrapper.addClass('loading');
                    wrapper.append('<div class="dt-loading">Loading...</div>');
                } else {
                    console.log('DataTable processing completed');
                    wrapper.removeClass('loading');
                    wrapper.find('.dt-loading').remove();
                }
            });

            return table;
        } catch (error) {
            console.error('Error initializing DataTable:', error);
            toastr.error('Error initializing table. Please refresh the page.');
            return null;
        }
    }

    $(document).ready(function() {
        try {
            console.log('Document ready, initializing tables');

            // Initialize DataTables object to store all table instances
            window.orderTables = {};

            // Initialize table for all orders
            window.orderTables.all = initDataTable();

            // Initialize tables for each plan
            @foreach($plans as $plan)
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
                                    data.name = $('#nameFilter').val();
                                    data.status = $('#statusFilter').val();
                                    data.email = $('#emailFilter').val();
                                    data.domain = $('#domainFilter').val();
                                    data.totalInboxes = $('#totalInboxesFilter').val();
                                    data.startDate = $('#startDate').val();
                                    data.endDate = $('#endDate').val();
                                });

                                table.columns.adjust();
                                if (table.responsive && typeof table.responsive.recalc === 'function') {
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
                        if (activeTable.responsive && typeof activeTable.responsive.recalc === 'function') {
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
                            data.name = $('#nameFilter').val();
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
                $('#orderIdFilter, #nameFilter, #emailFilter, #domainFilter').val('');
                $('#statusFilter').val('');
                $('#startDate, #endDate').val('');
                applyFilters();
            });

        } catch (error) {
            console.error('Error in document ready:', error);
        }
    });



    $(document).on('change', '.status-dropdown', function() {
        let selectedStatus = $(this).val();
        let orderId = $(this).data('id');

        $.ajax({
            url: '/admin/update-order-status',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                order_id: orderId,
                status_manage_by_admin: selectedStatus
            },
            success: function(response) {
                // Reload the correct table instead of re-initializing it
                if (window.orderTables && window.orderTables.all) {
                    window.orderTables.all.ajax.reload(null, false); // false to stay on the current page
                }

                alert('Status updated successfully!');
            },
            error: function(xhr) {
                if (window.orderTables && window.orderTables.all) {
                    window.orderTables.all.ajax.reload(null, false); // false to stay on the current page
                }
                alert('Something went wrong!');
                console.error(xhr.responseText);
            }
        });
    });


    //open the modal for cancel subscription
    $(document).on('click', '.markStatus', function() {
        const chargebee_subscription_id = $(this).data('id');
        const status = $(this).data('status');
        const reason = $(this).data('reason');

        // Set subscription ID in the hidden input
        $('#subscription_id_to_cancel').val(chargebee_subscription_id);

        // Uncheck all first to reset previous state
        $('input[name="marked_status"]').prop('checked', false);

        // Check the radio button that matches the status
        $('input[name="marked_status"][value="' + status + '"]').prop('checked', true);

        // Show or hide reason field depending on status
        if (status === 'Reject') {
            $('#reason_wrapper').removeClass('d-none');
            $('#cancellation_reason').attr('required', true);
            $('#cancellation_reason').val(reason);
        } else {
            $('#reason_wrapper').addClass('d-none');
            $('#cancellation_reason').removeAttr('required');
            $('#cancellation_reason').val('');
        }

        // Show the modal
        $('#cancel_subscription').modal('show');
    });


    //handle the reason field on status change
    $('.marked_status').on('change', function() {
        const selected = $(this).val();
        if (selected === 'reject') {
            $('#reason_wrapper').removeClass('d-none');
            $('#cancellation_reason').attr('required', true);
        } else {
            $('#reason_wrapper').addClass('d-none');
            $('#cancellation_reason').val('');
            $('#cancellation_reason').removeAttr('required');
        }
    });


    // Handle form submission
    $('#cancelSubscriptionForm').on('submit', function(e) {
        e.preventDefault();

        const selectedStatus = $('input[name="marked_status"]:checked').val();
        const reason = $('#cancellation_reason').val().trim();


        // If status is required
        if (!selectedStatus) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select a status.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // If Reject is selected but no reason
        if (selectedStatus === 'Reject' && !reason) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'The reason field is required for rejection.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // Gather form data manually
        const formData = new FormData(this);
        formData.append('marked_status', selectedStatus);

        // Confirm dialog
        Swal.fire({
            title: 'Are you sure?',
            text: "",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: Object.fromEntries(formData),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait a while...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        $('#cancel_subscription').modal('hide');
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Status has been updated successfully.',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            $('#cancellation_reason').val('');
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while updating status.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: errorMessage,
                            confirmButtonColor: '#3085d6'
                        });
                    }
                });
            }
        });
    });


    //filters 
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
</script>

//split view
<script>
    $('body').on('click', '.splitView', async function (e) {
        e.preventDefault();
        
        const orderId = $(this).data('order-id');
        const offcanvasElement = document.getElementById('order-view');
        const offcanvas = new bootstrap.Offcanvas(offcanvasElement);

        // Add event listener to clean up backdrop
        offcanvasElement.addEventListener('hidden.bs.offcanvas', function () {
            const backdrops = document.querySelectorAll('.offcanvas-backdrop, .modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());

            document.body.classList.remove('offcanvas-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, { once: true });

        offcanvas.show();

        try {
            const response = await fetch(`/admin/splits/${orderId}/orders`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            // if (!response.success) throw new Error('Failed to fetch orders');

            const data = await response.json();
            renderPanelOrders(data);
        } catch (error) {
            console.error('Error loading order splits:', error);
            const container = document.getElementById('panelOrdersContainer');
            if (container) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle text-danger fs-3 mb-3"></i>
                        <h5>Error Loading Orders</h5>
                        <p>Failed to load order splits. Please try again.</p>
                        <button class="btn btn-primary" onclick="viewPanelOrders(${orderId})">Retry</button>
                    </div>
                `;
            }
        }

          
    });

    // Function to render panel orders in the offcanvas
     function renderPanelOrders(data) {
            const panel=data?.data?.[0]?.panel;
            const orders=data?.data;
            const container = document.getElementById('panelOrdersContainer');
            
            if (!data || !data.data || data.data.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-inbox text-muted fs-3 mb-3"></i>
                        <h5>No split found</h5> 
                        <p>This order does not have split details.</p>
                    </div>
                `;
                return;
            }
           

      
            const ordersHtml = `
                <div class="mb-4">
                    <h6>PNL- ${panel?.id || 'N/A'}</h6>
                    <p class="">${panel?.description || 'No description'}</p>
                </div>
                
                <div class="accordion accordion-flush" id="panelOrdersAccordion">
                    ${orders.map((order, index) => `
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <div class="button p-3 collapsed d-flex align-items-center justify-content-between" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#order-collapse-${order?.id}" aria-expanded="false"
                                    aria-controls="order-collapse-${order?.id}">
                                    <small>ID: #${order?.id || 0 }</small>
                                    <small>Inboxes: ${order?.space_assigned || order?.inboxes_per_domain || 0}</small>
                                    <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary" href="javascript:;">
                                        View
                                    </button>
                                </div>
                            </h2>
                            <div id="order-collapse-${order?.id}" class="accordion-collapse collapse" data-bs-parent="#panelOrdersAccordion">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th scope="col">#</th>
                                                    <th scope="col">Order ID</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Space Assigned</th>
                                                    <th scope="col">Inboxes/Domain</th>
                                                    <th scope="col">Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th scope="row">${index + 1}</th>
                                                    <td>${order?.order_id || 0}</td>
                                                    <td>
                                                        <span class="badge ${getStatusBadgeClass(order?.status)}">${order?.status || 'Unknown'}</span>
                                                    </td>
                                                    <td>${order?.space_assigned || 'N/A'}</td>
                                                    <td>${order?.inboxes_per_domain || 'N/A'}</td>
                                                    <td>${formatDate(order?.created_at)}</td>
                                                </tr>
                                                ${order?.splits && order?.splits?.length > 0 ? order?.splits.map((split, splitIndex) => ``).join('') : ''}
                                            </tbody>
                                        </table>
                                    </div>




                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card p-3 mb-3">
                                                <h6 class="d-flex align-items-center gap-2">
                                                    <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                                        <i class="fa-regular fa-envelope"></i>
                                                    </div>
                                                    Email configurations
                                                </h6>

                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span>Total Inboxes <br> ${order?.order?.reorder_info[0]?.total_inboxes || 'N/A'}</span>
                                                    <span>Inboxes per domain <br> ${order?.order?.reorder_info[0]?.inboxes_per_domain || 'N/A'}</span>
                                                </div>
                                                <hr>
                                                <div class="d-flex flex-column">
                                                    <span class="opacity-50">Prefix Variants</span>
                                                    ${renderPrefixVariants(order?.order?.reorder_info[0].prefix_variants)}
                                                </div>
                                                <div class="d-flex flex-column mt-3">
                                                    <span class="opacity-50">Profile Picture URL</span>
                                                    <span>${order?.order?.reorder_info[0]?.profile_picture_link || 'N/A'}</span>
                                                </div>
                                                <div class="d-flex flex-column mt-3">
                                                    <span class="opacity-50">Email Persona Password</span>
                                                    <span>${order?.order?.reorder_info[0]?.email_persona_password || 'N/A'}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card p-3 overflow-y-auto" style="max-height: 30rem">
                                                <h6 class="d-flex align-items-center gap-2">
                                                    <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                                        <i class="fa-solid fa-earth-europe"></i>
                                                    </div>
                                                    Domains &amp; Configuration
                                                </h6>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Hosting Platform</span>
                                                    <span>${order?.order?.reorder_info[0]?.hosting_platform || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Platform Login</span>
                                                    <span>${order?.order?.reorder_info[0]?.platform_login || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Platform Password</span>
                                                    <span>${order?.order?.reorder_info[0]?.platform_password || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Domain Forwarding Destination URL</span>
                                                    <span>${order?.order?.reorder_info[0]?.forwarding_url || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Sending Platform</span>
                                                    <span>${order?.order?.reorder_info[0]?.sending_platform || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Sending platform Sequencer - Login</span>
                                                    <span>${order?.order?.reorder_info[0]?.sequencer_login || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Sending platform Sequencer - Password</span>
                                                    <span>${order?.order?.reorder_info[0]?.sequencer_password || 'N/A'}</span>
                                                </div>
                                                
                                                <div class="d-flex flex-column">
                                                    <span class="opacity-50">Domains</span>
                                                    ${renderDomains(order?.order_panel_splits)}
                                                </div>
                                            </div> 
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            
            container.innerHTML = ordersHtml;
        }


           function getStatusBadgeClass(status) {
               switch(status) {
                case 'completed': return 'bg-success';
                case 'unallocated': return 'bg-warning text-dark';
                case 'allocated': return 'bg-info';
                case 'rejected': return 'bg-danger';
                case 'in-progress': return 'bg-primary';
                default: return 'bg-secondary';
            }
        }
           // Helper function to format date
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            try {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit'
                });
            } catch (error) {
                return 'Invalid Date';
            }
        }

        // Helper function to render prefix variants
        function renderPrefixVariants(reorderInfo) {
            if (!reorderInfo) return '<span>N/A</span>';
            
            let variants = [];
            
            // Check if we have the new prefix_variants JSON format
            if (reorderInfo.prefix_variants) {
                try {
                    const prefixVariants = typeof reorderInfo.prefix_variants === 'string' 
                        ? JSON.parse(reorderInfo.prefix_variants) 
                        : reorderInfo.prefix_variants;
                    
                    Object.keys(prefixVariants).forEach((key, index) => {
                        if (prefixVariants[key]) {
                            variants.push(`<span>Variant ${index + 1}: ${prefixVariants[key]}</span>`);
                        }
                    });
                } catch (e) {
                    console.warn('Could not parse prefix variants:', e);
                }
            }
            
            // Fallback to old individual fields if new format is empty
            if (variants.length === 0) {
                if (reorderInfo.prefix_variant_1) {
                    variants.push(`<span>Variant 1: ${reorderInfo.prefix_variant_1}</span>`);
                }
                if (reorderInfo.prefix_variant_2) {
                    variants.push(`<span>Variant 2: ${reorderInfo.prefix_variant_2}</span>`);
                }
            }
            
            return variants.length > 0 ? variants.join('') : '<span>N/A</span>';
        }

        // Helper function to render domains from splits
        function renderDomains(splits) {
            if (!splits || splits.length === 0) {
                return '<span>N/A</span>';
            }
            
            let allDomains = [];
            
            splits.forEach(split => {
                if (split.domains && Array.isArray(split.domains)) {
                    allDomains = allDomains.concat(split.domains);
                }
            });
            
            if (allDomains.length === 0) {
                return '<span>N/A</span>';
            }
            
            return allDomains.map(domain => `<span class="d-block">${domain}</span>`).join('');
        }

        // Live timer update function
        function updateTimers() {
            $('.timer-badge').each(function() {
                const $badge = $(this);
                const createdAt = $badge.data('created-at');
                const status = $badge.data('status');
                const completedAt = $badge.data('completed-at');
                const timerStartedAt = $badge.data('timer-started-at');
                
                if (createdAt && status !== 'completed') {
                    const timer = calculateOrderTimer(createdAt, status, completedAt, timerStartedAt);
                    const iconClass = timer.isCompleted ? 'fas fa-check' : (timer.isNegative ? 'fas fa-exclamation-triangle' : 'fas fa-clock');
                    
                    $badge.removeClass('positive negative completed').addClass(timer.class);
                    $badge.html(`<i class="${iconClass} timer-icon"></i>${timer.display}`);
                }
            });
        }

        // Start timer updates
        $(document).ready(function() {
            // Update timers every second
            setInterval(updateTimers, 1000);
        });
</script>



@endpush