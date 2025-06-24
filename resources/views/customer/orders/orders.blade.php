@extends('customer.layouts.app')

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

    /* .draft-alert:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        transition: all 0.3s ease;
    } */

    .draft-alert .alert-icon {
        animation: bounce 1s infinite;
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
            /* border-color: var(--second-primary); */
        }
        50% {
            /* border-color: #ffc107; */
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
    <div class="alert alert-warning alert-dismissible fade show draft-alert py-2" role="alert" style="background-color: rgba(255, 166, 0, 0.359); color: #fff; border: 2px solid orange;">
        <i class="ti ti-alert-triangle me-2 alert-icon"></i>
        <strong>Draft Order{{ $draftOrders != 1 ? 's' : '' }} Alert:</strong> 
        You have {{ $draftOrders }} draft order{{ $draftOrders != 1 ? 's' : '' }} available in drafts. 
        Please submit the relevant details to complete the order{{ $draftOrders != 1 ? 's' : '' }}.
        <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif


<section class="py-3">

    <div class="counters mb-4">
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

        <div class="card p-3 counter_2">
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

        <div class="card p-3 counter_1">
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

        <div class="card p-3 counter_2">
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
        <div class="card p-3 counter_1">
            <div>
                <!-- {{-- //card body --}} -->
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <h6 class="text-heading">Expired Orders</h6>
                        <div class="d-flex align-items-center mt-1">
                            <h4 class="mb-0 me-2 fs-5">{{ number_format($expiredOrders) }}</h4>
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

        <div class="card p-3 counter_2">
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

        <div class="card p-3 counter_1">
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

        <div class="card p-3 counter_2">
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

    <div class="row mb-4" style="display: none;">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
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
                            <input type="text" id="orderIdFilter" class="form-control" placeholder="Search by ID">
                        </div>
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $key => $status)
                                <option value="{{ $key }}">{{ ucfirst(str_replace('_', ' ', $key)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="emailFilter" class="form-label">Email</label>
                            <input type="text" id="emailFilter" class="form-control" placeholder="Search by email">
                        </div>
                        <div class="col-md-3">
                            <label for="domainFilter" class="form-label">Domain URL</label>
                            <input type="text" id="domainFilter" class="form-control" placeholder="Search by domain">
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
        {{-- <h2 class="mb-3">Orders</h2> --}}
        <ul class="nav nav-tabs border-0 mb-3" id="myTab" role="tablist" style="display: none;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-tab-pane"
                    type="button" role="tab" aria-controls="all-tab-pane" aria-selected="true">All Orders</button>
            </li>
            @foreach($plans as $plan)
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="plan-{{ $plan->id }}-tab" data-bs-toggle="tab"
                    data-bs-target="#plan-{{ $plan->id }}-tab-pane" type="button" role="tab"
                    aria-controls="plan-{{ $plan->id }}-tab-pane" aria-selected="false">{{ $plan->name }}</button>
            </li>
            @endforeach
        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab"
                tabindex="0">
                @include('customer.orders._orders_table')
            </div>
            @foreach($plans as $plan)
            <div class="tab-pane fade" id="plan-{{ $plan->id }}-tab-pane" role="tabpanel"
                aria-labelledby="plan-{{ $plan->id }}-tab" tabindex="0">
                @include('customer.orders._orders_table', ['plan_id' => $plan->id])
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
        window.location.href = `{{ url('/customer/orders/${id}/view') }}`;
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
                        // width: '10%',
                        targets: 0
                    }, // ID 
                    {
                        // width: '15%',
                        targets: 1
                    }, // Date
                    ...(planId ? [] : [{
                        // width: '15%',
                        targets: 2
                    }]), // Plan (only for All Orders) 
                    {
                        // width: '20%',
                        targets: planId ? 2 : 3
                    }, // Domain URL
                    {
                        // width: '15%',
                        targets: planId ? 3 : 4
                    }, // Total Inboxes 
                    {
                        // width: '15%',
                        targets: planId ? 4 : 5
                    }, // Status
                    {
                        // width: '10%',
                        targets: planId ? 5 : 6
                    } // Actions
                ],
                ajax: {
                    url: "{{ route('customer.orders.data') }}",
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: function(d) {
                        // Make sure planId is properly set in the request
                        d.plan_id = planId || '';

                        // Add other parameters
                        d.orderId = $('#orderIdFilter').val();
                        d.status = $('#statusFilter').val();
                        d.email = $('#emailFilter').val();
                        d.domain = $('#domainFilter').val();
                        d.totalInboxes = $('#totalInboxesFilter').val();
                        d.startDate = $('#startDate').val();
                        d.endDate = $('#endDate').val();

                        console.log('DataTables request parameters:', d);
                        return d;
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
                                <div class="d-flex gap-1 align-items-center opacity-50">
                                    <i class="ti ti-calendar-month"></i>
                                    <span>${data}</span>    
                                </div>
                            `;
                        }
                    },
                    ...(planId ? [] : [{
                        data: 'plan_name',
                        name: 'plans.name',
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex gap-1 align-items-center">
                                    <img data-bs-toggle="tooltip" data-bs-title="Disabled tooltip" src="https://cdn-icons-png.flaticon.com/128/11890/11890970.png" style="width: 15px" alt="">
                                    <span>${data}</span>    
                                </div>
                            `;
                        }
                    }]),
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
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                    },
                ],
                order: [
                    [1, 'desc']
                ]
            });

            return table;
        } catch (error) {
            console.error('Error initializing DataTable:', error);
            toastr.error('Error initializing table. Please refresh the page.');
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

        } catch (error) {
            console.error('Error in document ready:', error);
        }
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