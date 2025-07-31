@extends('customer.layouts.app')

@section('title', 'Subscriptions')

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
@endpush

@section('content')
<section class="py-3" data-page="subscription">

    @include('customer.chatbot.chat')

    <div class="row mb-4">
        <div class="counters col-xl-6">
            <div class="card p-3 counter_1">
                <div> 
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Subscriptions</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-4" id="total_counter">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-user-search"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/17905/17905131.gif" width="40"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-users fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Active Subscriptions</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-4" id="active_counter">0</h4>
                                <p class="text-danger mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/10970/10970316.gif" width="40"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-square-person-confined fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_1">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Cancel Subscriptions</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-4" id="inactive_counter">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/10399/10399011.gif" width="40"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-ban cancel fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Subscriptions Ammount</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-4" id="inactive_counter">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/14697/14697022.gif" width="40"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-money-check-dollar fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mt-4 mt-xl-0">
            <div class="p-4 h-100 filter">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-2 text-white">Filters</h5>

                    <div class="d-flex gap-2 justify-content-between">
                        <button id="applyFilters" class="btn btn-primary btn-sm me-2 px-4 shadow border-0 h-100">Filter</button>
                        <button id="clearFilters" class="btn btn-secondary btn-sm px-4 h-100">Clear</button>
                    </div>
                </div>

                <div class="row gy-3">
                    <div class="col-sm-6 col-md-3 col-xl-6">
                        <label for="subscriptionIdFilter" class="form-label mb-0">Subscription ID</label>
                        <input type="text" id="subscriptionIdFilter" class="form-control"
                            placeholder="Search by ID">
                    </div>
                    <div class="col-sm-6 col-md-3 col-xl-6">
                        <label for="subscriptionStatusFilter" class="form-label mb-0">Status</label>
                        <select id="subscriptionStatusFilter" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-3 col-xl-6">
                        <label for="startDate" class="form-label mb-0">Start Date</label>
                        <input type="date" id="startDate" class="form-control">
                    </div>
                    <div class="col-sm-6 col-md-3 col-xl-6">
                        <label for="endDate" class="form-label mb-0">End Date</label>
                        <input type="date" id="endDate" class="form-control">
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- <div class="row mb-4">
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
                            <label for="subscriptionIdFilter" class="form-label">Subscription ID</label>
                            <input type="text" id="subscriptionIdFilter" class="form-control"
                                placeholder="Search by ID">
                        </div>
                        <div class="col-md-3">
                            <label for="subscriptionStatusFilter" class="form-label">Status</label>
                            <select id="subscriptionStatusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
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
    </div> --}}

    <div class="card py-3 px-4">
        <ul class="nav nav-tabs border-0 mb-3" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-tab-pane"
                    type="button" role="tab" aria-controls="all-tab-pane" aria-selected="true">Active
                    Subscriptions</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cancel-tab" data-bs-toggle="tab" data-bs-target="#cancel-tab-pane"
                    type="button" role="tab" aria-controls="cancel-tab-pane" aria-selected="false">Cancelled
                    Subscriptions</button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="all-tab-pane" role="tabpanel" aria-labelledby="all-tab"
                tabindex="0">
                @include('customer.subscriptions._subscriptions_table')
            </div>
            <div class="tab-pane fade" id="cancel-tab-pane" role="tabpanel" aria-labelledby="cancel-tab" tabindex="0">
                @include('customer.subscriptions._cancelled_subscriptions_table')
            </div>
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
        window.location.href = "{{ route('customer.subs.detail.view') }}?id=" + id;
    }

    function initDataTable(planId = '') {
        console.log('Initializing DataTable for planId:', planId);
        var tableId = '#myTable';
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
                                return 'Subscription Details';
                            }
                        }),
                        renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                    }
                },
                autoWidth: false,
                columns: [
                    {
                        data: 'id',
                        name: 'subscriptions.id'
                    },
                    {
                        data: 'created_at',
                        name: 'subscriptions.created_at',
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
                        data: 'amount',
                        name: 'orders.amount',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                                <span class="text-warning">${data}</span>    
                            `;
                        }
                    },
                    {
                        data: 'chargebee_subscription_id',
                        name: 'subscriptions.chargebee_subscription_id'
                    },
                    {
                        data: 'last_billing',
                        name: 'subscriptions.start_date',
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
                        data: 'next_billing',
                        name: 'subscriptions.end_date',
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
                        data: 'order_id',
                        name: 'subscriptions.order_id'
                    },
                    {
                        data: 'status',
                        name: 'subscriptions.status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }
                ],
                columnDefs: [
                    { width: '10%', targets: 0 }, // ID
                    { width: '12%', targets: 1 }, // Date
                    { width: '10%', targets: 2 }, // Amount
                    { width: '15%', targets: 3 }, // Chargebee ID
                    { width: '12%', targets: 4 }, // Last Billing
                    { width: '12%', targets: 5 }, // Next Billing
                    { width: '10%', targets: 6 }, // Order ID
                    { width: '10%', targets: 7 }, // Status
                    { width: '9%', targets: 8 }  // Actions
                ],
                // ...rest of the configuration
                ajax: {
                    url: "{{ route('customer.subscriptions.view') }}",
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: function(d) {
                        d.plan_id = planId;
                        // Add filter parameters
                        d.filter_id = $('#subscriptionIdFilter').val();
                        // d.filter_name = $('#subscriptionNameFilter').val();
                        // d.filter_email = $('#subscriptionEmailFilter').val();
                        d.filter_status = $('#subscriptionStatusFilter').val();
                        d.filter_start_date = $('#startDate').val();
                        d.filter_end_date = $('#endDate').val();
                        return d;
                    },
                    dataSrc: function(json) {
                        console.log('Server response:', json);
                        return json.data;
                    }
                },
                order: [[1, 'desc']],
                drawCallback: function(settings) {
                    const counters = settings.json?.counters;
                    if (counters) {
                        $('#total_counter').text(counters.total);
                        $('#active_counter').text(counters.active);
                        $('#inactive_counter').text(counters.inactive);
                        $('#completed_counter').text(counters.completed);
                    }
                    $('[data-bs-toggle="tooltip"]').tooltip();
                    this.api().columns.adjust();
                    this.api().responsive?.recalc();
                }
            });

            table.on('processing.dt', function(e, settings, processing) {
                const wrapper = $(tableId + '_wrapper');
                if (processing) {
                    wrapper.addClass('loading');
                    wrapper.append('<div class="dt-loading">Loading...</div>');
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

    //inactive subs listings table
    function initCancelledDataTable() {
        console.log('Initializing DataTable for Cancelled subs');
        var tableId = '#cancelled_subs_table';
        var $table = $(tableId);
        if (!$table.length) {
            console.error('Table not found with cancelled table');
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
                                return 'Subscription Details';
                            }
                        }),
                        renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                    }
                },
                autoWidth: false,
                columns: [{
                        data: 'id',
                        name: 'subscriptions.id'
                    },
                    {
                        data: 'created_at',
                        name: 'subscriptions.created_at',
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
                        data: 'amount',
                        name: 'orders.amount',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            return `
                                <span class="text-warning">${data}</span>    
                            `;
                        }
                    },
                    {
                        data: 'chargebee_subscription_id',
                        name: 'subscriptions.chargebee_subscription_id'
                    },
                    {
                        data: 'last_billing',
                        name: 'subscriptions.start_date',
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
                        data: 'order_id',
                        name: 'subscriptions.order_id',
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
                        data: 'status',
                        name: 'subscriptions.status',
                        orderable: false,
                        searchable: false
                    }
                ],
                columnDefs: [
                    { width: '12%', targets: 0 }, // ID
                    { width: '15%', targets: 1 }, // Date
                    { width: '12%', targets: 2 }, // Amount
                    { width: '20%', targets: 3 }, // Chargebee ID
                    { width: '15%', targets: 4 }, // Last Billing
                    { width: '12%', targets: 5 }, // Order ID
                    { width: '14%', targets: 6 }  // Status
                ],
                ajax: {
                    url: "{{ route('customer.subs.cancelled-subscriptions') }}",
                    type: "GET",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    data: function(d) {
                        d.filter_id = $('#subscriptionIdFilter').val();
                        // d.filter_name = $('#subscriptionNameFilter').val();
                        // d.filter_email = $('#subscriptionEmailFilter').val();
                        d.filter_status = $('#subscriptionStatusFilter').val();
                        d.filter_start_date = $('#startDate').val();
                        d.filter_end_date = $('#endDate').val();
                        return d;
                    },
                    dataSrc: function(json) {
                        console.log('Server response:', json);
                        return json.data;
                    }
                },
                drawCallback: function(settings) {
                    const counters = settings.json?.counters;
                    if (counters) {
                        $('#total_counter').text(counters.total);
                        $('#active_counter').text(counters.active);
                        $('#inactive_counter').text(counters.inactive);
                        $('#completed_counter').text(counters.completed);
                    }
                    $('[data-bs-toggle="tooltip"]').tooltip();
                    this.api().columns.adjust();
                    this.api().responsive?.recalc();
                }
            });

            table.on('processing.dt', function(e, settings, processing) {
                const wrapper = $(tableId + '_wrapper');
                if (processing) {
                    wrapper.addClass('loading');
                    wrapper.append('<div class="dt-loading">Loading...</div>');
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

    function CancelSubscription(subscriptionId) {
        console.log('Cancel Subscription clicked for ID:', subscriptionId);
        // Get the subscription details
        const subscription = subscriptionId;
        
        // Set the subscription ID in the modal form
        $('#subscription_id_to_cancel').val(subscription);
        
        // Show the modal
        $('#cancel_subscription').modal('show');
    }

    // Handle form submission
    $('#cancelSubscriptionForm').on('submit', function(e) {
        e.preventDefault();
        console.log('Cancel Subscription form submitted');
        // Check if reason is provided
        const reason = $('#cancellation_reason').val().trim();
        if (!reason) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'The reason field is required.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        // Get form data and ensure remove_accounts is boolean
        const formData = new FormData(this);
        formData.set('remove_accounts', $('#remove_accounts').is(':checked'));
        
        // Show confirmation dialog
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, cancel it!'
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
                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while we cancel your subscription',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        // Close the modal
                        $('#cancel_subscription').modal('hide');
                        
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Your subscription has been cancelled successfully.',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            // Reload the page to reflect changes
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while cancelling your subscription.';
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

    $(document).ready(function() {
        try {
            console.log('Document ready, initializing tables');

            window.orderTables = {
                active: initDataTable(),
                cancelled: initCancelledDataTable()
            };

            // Handle tab changes
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
                const tabId = $(e.target).attr('id');
                console.log('Tab changed to:', tabId);

                // Force recalculation of column widths for visible tables
                setTimeout(function() {
                    if (tabId === 'all-tab') {
                        if (window.orderTables.active) {
                            window.orderTables.active.columns.adjust();
                            window.orderTables.active.responsive.recalc();
                        }
                    } else if (tabId === 'cancel-tab') {
                        if (window.orderTables.cancelled) {
                            window.orderTables.cancelled.columns.adjust();
                            window.orderTables.cancelled.responsive.recalc();
                        }
                    }
                }, 10);
            });

            // Add global error handler for AJAX requests
            $(document).ajaxError(function(event, xhr, settings, error) {
                console.error('AJAX Error:', error);
                if (xhr.status === 401) {
                    window.location.href = "{{ route('login') }}";
                } else if (xhr.status === 403) {
                    toastr.error('You do not have permission to perform this action');
                }
            });
        } catch (error) {
            console.error('Error in document ready:', error);
        }
    });
    // $(document).ready(function() {
    // Handle form submission
    $('#cancel_subscription form').on('submit', function(e) {
        e.preventDefault();

        // Check if reason is provided
        const reason = $('#cancellation_reason').val().trim();
        if (!reason) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'The reason field is required.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        // Get form data and ensure remove_accounts is boolean
        const formData = new FormData(this);
        formData.set('remove_accounts', $('#remove_accounts').is(':checked'));

        // Show confirmation dialog
        confirmCancellation(formData);
    });

    function confirmCancellation(formData) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, cancel it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: $('#cancel_subscription form').attr('action'),
                    method: 'POST',
                    data: Object.fromEntries(formData),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    beforeSend: function() {
                        // Show loading state
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while we cancel your subscription',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        // Close the modal
                        $('#cancel_subscription').modal('hide');

                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Your subscription has been cancelled successfully.',
                            confirmButtonColor: '#3085d6'
                        }).then(() => {
                            // Reload the page to reflect changes
                            window.location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while cancelling your subscription.';
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
    }
    // });

    // Filter functionality
    $(document).ready(function() {
        $('#applyFilters').on('click', function() {
            const filters = {
                id: $('#subscriptionIdFilter').val(),
                name: $('#subscriptionNameFilter').val(),
                email: $('#subscriptionEmailFilter').val(),
                status: $('#subscriptionStatusFilter').val(),
                startDate: $('#startDate').val(),
                endDate: $('#endDate').val()
            };

            // Apply filters to both tables
            Object.values(window.orderTables).forEach(function(table) {
                table.ajax.reload();
            });
        });

        $('#clearFilters').on('click', function() {
            // Clear all filter inputs
            $('#subscriptionIdFilter').val('');
            $('#subscriptionNameFilter').val('');
            $('#subscriptionEmailFilter').val('');
            $('#subscriptionStatusFilter').val('');
            $('#startDate').val('');
            $('#endDate').val('');

            // Reload tables with cleared filters
            Object.values(window.orderTables).forEach(function(table) {
                table.ajax.reload();
            });
        });
    });

    // Extend DataTables ajax data to include filters
    const originalAjaxData = window.orderTables?.all?.settings()?.ajax?.data || function(d) { return d; };
    if (window.orderTables?.all) {
        window.orderTables.all.settings()[0].ajax.data = function(d) {
            d = originalAjaxData(d);
            d.filter_id = $('#subscriptionIdFilter').val();
            d.filter_name = $('#subscriptionNameFilter').val();
            d.filter_email = $('#subscriptionEmailFilter').val();
            d.filter_status = $('#subscriptionStatusFilter').val();
            d.filter_start_date = $('#startDate').val();
            d.filter_end_date = $('#endDate').val();
            return d;
        };
    }
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