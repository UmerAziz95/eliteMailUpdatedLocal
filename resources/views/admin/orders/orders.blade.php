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
@endpush

@section('content')
<section class="py-3">

    <div class="row gy-3 mb-4">
        <div class="counters col-lg-6">
            <div class="card p-2 counter_1">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-1">{{ number_format($totalOrders) }}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-user-search"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/14385/14385008.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-2 counter_2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Pending Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-1">{{ number_format($pendingOrders) }}</h4>
                                <p class="text-danger mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/18873/18873804.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-2 counter_1">
                <div class="card-body">
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Complete Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-1">{{ number_format($inProgressOrders) }}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/6416/6416374.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-2 counter_2">
                <div class="card-body">
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">In-Progress Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-1">{{ number_format($inProgressOrders) }}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/10282/10282642.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-2 counter_1">
                <div class="card-body">
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Cancelled Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-1">{{ number_format($cancelledOrders) }}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/19005/19005362.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-2 counter_1">
                <div class="card-body">
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Rejected Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-1">{{ number_format($rejectOrders) }}</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/15332/15332434.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-2 counter_2">
                <div class="card-body">
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Draft Orders</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-1">{{ number_format($draftOrders) }}</h4>
                                <p class="text-warning mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-edit"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/10690/10690672.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card p-4 h-100 filter">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-2 text-white">Filters</h5>
                </div>

                <div class="d-flex align-items-start gap-4">
                    <div class="row gy-3">
                        <div class="col-md-6">
                            <label for="orderIdFilter" class="form-label text-dark">Order ID</label>
                            <input type="text" id="orderIdFilter" class="form-control" placeholder="Search by ID">
                        </div>
                        <div class="col-md-6">
                            <label for="statusFilter" class="form-label text-dark">Status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $key => $status)
                                <option value="{{ $key }}">{{ ucfirst(str_replace('_', ' ', $key)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="emailFilter" class="form-label text-dark">Email</label>
                            <input type="text" id="emailFilter" class="form-control" placeholder="Search by email">
                        </div>
                        <div class="col-md-6">
                            <label for="domainFilter" class="form-label text-dark">Domain URL</label>
                            <input type="text" id="domainFilter" class="form-control" placeholder="Search by domain">
                        </div>
                        <div class="col-md-6">
                            <label for="totalInboxesFilter" class="form-label text-dark">Total Inboxes</label>
                            <input type="number" id="totalInboxesFilter" class="form-control"
                                placeholder="Search by total inboxes" min="1">
                        </div>
                        <div class="col-md-6">
                            <label for="startDate" class="form-label text-dark">Start Date</label>
                            <input type="date" id="startDate" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label for="endDate" class="form-label text-dark">End Date</label>
                            <input type="date" id="endDate" class="form-control">
                        </div>
                        <div class="d-flex align-items-center justify-content-end">
                            <button id="applyFilters" class="btn btn-primary btn-sm me-2">Filter</button>
                            <button id="clearFilters" class="btn btn-secondary btn-sm">Clear</button>
                        </div>
                    </div>

                    <img src="https://cdn-icons-gif.flaticon.com/19009/19009016.gif" width="30%"
                        style="border-radius: 50%" class="d-none d-sm-block" alt="">
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


@endpush