@extends('admin.layouts.app')

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
<section class="py-3">
    <div class="row gy-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card p-2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Subscriptions</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="total_counter">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-brand-booking"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="card p-2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Active Subscriptions</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="active_counter">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-brand-booking"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card p-2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Cancelled Subscriptions</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2" id="inactive_counter">0</h4>
                            </div>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-brand-booking"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                <div class="row gy-3 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-2">Filters</h5>
                        <div>
                            <button id="applyFilters" class="btn btn-primary btn-sm me-2 applyFilters">Filter</button>
                            <button id="clearFilters" class="btn btn-secondary btn-sm clearFilters">Clear</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="user_name_filter" class="form-control" placeholder="Enter name">
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="email_filter" class="form-control" placeholder="Enter email">
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="amount_filter" class="form-control" placeholder="Enter amount">
                    </div>

                    {{-- <div class="col-md-3">
                        <select id="status_filter" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">active</option>
                            <option value="0">inactive</option>

                        </select>
                    </div> --}}
                </div>
                @include('admin.subscriptions._subscriptions_table')
            </div>
            <div class="tab-pane fade" id="cancel-tab-pane" role="tabpanel" aria-labelledby="cancel-tab" tabindex="0">
                <div class="row gy-3 py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="mb-2">Filters</h5>
                        <div>
                            <button id="applyFilters2"  class="btn btn-primary btn-sm me-2 applyFilters">Filter</button>
                            <button id="clearFilters" class="btn btn-secondary btn-sm clearFilters">Clear</button>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="user_name_filter2" class="form-control" placeholder="Enter name">
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="email_filter2" class="form-control" placeholder="Enter email">
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="amount_filter2" class="form-control" placeholder="Enter amount">
                    </div>

                    {{-- <div class="col-md-3">
                        <select id="status_filter2" class="form-select">
                            <option value="">Select Status</option>
                            <option value="1">active</option>
                            <option value="0">inactive</option>

                        </select>
                    </div> --}}
                </div>
                @include('admin.subscriptions._cancelled_subscriptions_table')
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
        window.location.href = "{{ route('admin.subs.detail.view') }}?id=" + id;
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
        responsive: true,
        autoWidth: false,
        columnDefs: [
    { width: '10%', targets: 0 }, // ID
    { width: '15%', targets: 1 }, // Date
    { width: '10%', targets: 2 }, // Amount
    { width: '10%', targets: 2 }, // Name
    { width: '30%', targets: 3 }, // Email
    { width: '15%', targets: 4 }, // Status
    { width: '20%', targets: 5 }  // Actions
    ],
        ajax: {
            url: "{{ route('admin.subs.view') }}",
            type: "GET",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: function(d) {
                d.user_name = $('#user_name_filter').val();
                d.email = $('#email_filter').val();
                d.amount= $('#amount_filter').val();
                
            },
           
            dataSrc: function(json) {
                console.log('Server response:', json);
                return json.data;
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Server response:', xhr.responseText);

                if (xhr.status === 401) {
                    window.location.href = "{{ route('login') }}";
                } else if (xhr.status === 403) {
                    toastr.error('You do not have permission to view this data');
                } else {
                    toastr.error('Error loading Subscriptions data: ' + error);
                }
            }
        },
        columns: [
            { data: 'id', name: 'subscriptions.id' }, // Subscription ID
            { data: 'amount', name: 'amount' }, // Date
            { data: 'name', name: 'users.name' }, // Date
            { data: 'email', name: 'users.email' }, // From addColumn() in controller
            { data: 'status', name: 'subscriptions.status' }, // Subscription status
            { data: 'last_billing_date', name: 'last_billing_date' }, // Subscription status
            { data: 'next_billing_date', name: 'next_billing_date' }, // Subscription status
            { data: 'action', name: 'action', orderable: false, searchable: false } // Action buttons
        ],
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
            this.api().responsive?.recalc(); // avoid error if responsive not loaded
        },
                initComplete: function(settings, json) {
                    console.log('Table initialization complete');
                    this.api().columns.adjust();
                    this.api().responsive.recalc();
                }
            });

            // Optional loading indicator
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
        responsive: true,
        autoWidth: false,
       columns: [
            { data: 'id', name: 'subscriptions.id' }, // Subscription ID
            { data: 'created_at', name: 'subscriptions.created_at' }, // Date
            { data: 'amount', name: 'subscriptions.amount' }, // Date
            { data: 'name', name: 'users.name' }, // name
            { data: 'email', name: 'users.email' }, // From addColumn() in controller
            { data: 'status', name: 'subscriptions.status' }, // Subscription status
            { data: 'cancellation_at', name: 'cancellation_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false } // Action buttons
        ],
        ajax: {
            url: "{{ route('admin.subs.cancelled-subscriptions') }}",
            type: "GET",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: function(d) {
                d.search_filter_2 = "search filter test value";
                d.user_name = $('#user_name_filter2').val();
                d.email = $('#email_filter2').val();
                d.amount = $('#amount_filter2').val();
            },
            dataSrc: function(json) {
                console.log('Server response:', json);
                return json.data;
            }, 
            error: function(xhr, error, thrown) {
                console.error('DataTables error:', error);
                console.error('Server response:', xhr.responseText);

                if (xhr.status === 401) {
                    window.location.href = "{{ route('login') }}";
                } else if (xhr.status === 403) {
                    toastr.error('You do not have permission to view this data');
                } else {
                    toastr.error('Error loading Subscriptions data: ' + error);
                }
            }
        },
        columns: [
            { data: 'id', name: 'subscriptions.id' }, // Subscription ID
            { data: 'created_at', name: 'subscriptions.created_at' }, // Date
            { data: 'amount', name: 'subscriptions.amount' }, // Date
            { data: 'name', name: 'users.name' }, // name
            { data: 'email', name: 'users.email' }, // From addColumn() in controller
            { data: 'status', name: 'subscriptions.status' }, // Subscription status
            { data: 'action', name: 'action', orderable: false, searchable: false } // Action buttons
        ],
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
            this.api().responsive?.recalc(); // avoid error if responsive not loaded
        },
                initComplete: function(settings, json) {
                    console.log('Table initialization complete');
                    this.api().columns.adjust();
                    this.api().responsive.recalc();
                }
            });

            // Optional loading indicator
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

    $(document).ready(function() {
        try {   
            window.orderTables = {};

                // Initialize "All Subscriptions" table
                window.orderTables.allSubs = initDataTable();

                // Initialize "Cancelled Subscriptions" table
                window.orderTables.cancelledSubs = initCancelledDataTable();

            // Handle tab changes
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                const tabId = $(e.target).attr('id');
                console.log('Tab changed to:', tabId);
                
                // Force recalculation of column widths for visible tables
                setTimeout(function() {
                    Object.values(window.orderTables).forEach(function(table) {
                        if ($(table.table().node()).is(':visible')) {
                            table.columns.adjust();
                            table.responsive.recalc();
                            console.log('Adjusting columns for table:', table.table().node().id);
                        }
                    });
                }, 10);
            });

            // Initial column adjustment for the active tab
            setTimeout(function() {
                const activeTable = $('.tab-pane.active .table').DataTable();
                if (activeTable) {
                    activeTable.columns.adjust();
                    activeTable.responsive.recalc();
                    console.log('Initial column adjustment for active table');
                }
            }, 10);

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

      // Apply Filters
    $('.applyFilters').click(function() {
        refreshDataTable();
    });

    // Clear Filters
    $('.clearFilters').click(function() {
        $('#user_name_filter').val('');
        $('#email_filter').val('');
        $('#amount_filter').val('');
        $('#user_name_filter2').val('');
        $('#email_filter2').val('');
        $('#amount_filter2').val('');
        refreshDataTable();
    });

    function refreshDataTable(){
        if (window.orderTables) {
        if (window.orderTables.allSubs) {
            window.orderTables.allSubs.ajax.reload(null, false);
        }

        if (window.orderTables.cancelledSubs) {
            window.orderTables.cancelledSubs.ajax.reload(null, false);
        }
      }
    }

  

</script>


@endpush