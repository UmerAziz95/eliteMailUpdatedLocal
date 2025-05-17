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
    <div class="counters mb-4">
        <div class="card p-2 counter_1">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <h6 class="text-heading">Total Subscriptions</h6>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2 fs-1" id="total_counter">0</h4>
                            <p class="text-success mb-0"></p>
                        </div>
                        <small class="mb-0"></small>
                    </div>
                    <div class="avatar">
                        {{-- <span class="avatar-initial rounded bg-label-warning">
                            <i class="ti ti-user-search"></i>
                        </span> --}}
                        <img src="https://cdn-icons-gif.flaticon.com/17905/17905131.gif" width="50"
                            style="border-radius: 50px" alt="">
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-2 counter_2">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <h6 class="text-heading">Active Subscriptions</h6>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2 fs-1" id="active_counter">0</h4>
                            <p class="text-danger mb-0"></p>
                        </div>
                        <small class="mb-0"></small>
                    </div>
                    <div class="avatar">
                        {{-- <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-user-check"></i>
                        </span> --}}
                        <img src="https://cdn-icons-gif.flaticon.com/10970/10970316.gif" width="50"
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
                        <h6 class="text-heading">Cancel Subscriptions</h6>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2 fs-1" id="inactive_counter">0</h4>
                            <p class="text-success mb-0"></p>
                        </div>
                        <small class="mb-0"></small>
                    </div>
                    <div class="avatar">
                        {{-- <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-user-plus"></i>
                        </span> --}}
                        <img src="https://cdn-icons-gif.flaticon.com/10399/10399011.gif" width="50"
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
                        <h6 class="text-heading">Subscriptions Ammount</h6>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2 fs-1" id="inactive_counter">0</h4>
                            <p class="text-success mb-0"></p>
                        </div>
                        <small class="mb-0"></small>
                    </div>
                    <div class="avatar">
                        {{-- <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-user-plus"></i>
                        </span> --}}
                        <img src="https://cdn-icons-gif.flaticon.com/14697/14697022.gif" width="50"
                            style="border-radius: 50px" alt="">
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
                <div class="row gy-3 mt-0">
                    <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse"
                        href="#filter_1" role="button" aria-expanded="false" aria-controls="filter_1">
                        <div>
                            <div class="d-flex gap-2 align-items-center">
                                <h5 class="text-uppercase fs-6 mb-0">Filters</h5>
                                <img src="https://static.vecteezy.com/system/resources/previews/052/011/341/non_2x/3d-white-down-pointing-backhand-index-illustration-png.png"
                                    width="30" alt="">
                            </div>
                            <small>Click here to open advance search for a table</small>
                        </div>
                    </div>
                    <div class="row collapse" id="filter_1">
                        <div class="col-md-4 mt-3">
                            <input type="text" id="user_name_filter" class="form-control" placeholder="Enter name">
                        </div>
                        <div class="col-md-4 mt-3">
                            <input type="text" id="email_filter" class="form-control" placeholder="Enter email">
                        </div>
                        <div class="col-md-4 mt-3">
                            <input type="text" id="amount_filter" class="form-control" placeholder="Enter amount">
                        </div>
                        <div class="d-flex justify-content-end my-3">
                            <button id="applyFilters"
                                class="btn btn-primary btn-sm me-2 px-4 applyFilters">Filter</button>
                            <button id="clearFilters" class="btn btn-secondary btn-sm px-4 clearFilters">Clear</button>
                        </div>
                    </div>
                </div>
                @include('admin.subscriptions._subscriptions_table')
            </div>
            <div class="tab-pane fade" id="cancel-tab-pane" role="tabpanel" aria-labelledby="cancel-tab" tabindex="0">
                <div class="row gy-3 py-3">
                    <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse"
                        href="#filter_2" role="button" aria-expanded="false" aria-controls="filter_2">
                        <div>
                            <div class="d-flex gap-2 align-items-center">
                                <h5 class="text-uppercase fs-6 mb-0">Filters</h5>
                                <img src="https://static.vecteezy.com/system/resources/previews/052/011/341/non_2x/3d-white-down-pointing-backhand-index-illustration-png.png"
                                    width="30" alt="">
                            </div>
                            <small>Click here to open advance search for a table</small>
                        </div>
                    </div>

                    <div class="row collapse" id="filter_2">
                        <div class="col-md-4 mt-3">
                            <input type="text" id="user_name_filter2" class="form-control" placeholder="Enter name">
                        </div>
                        <div class="col-md-4 mt-3">
                            <input type="text" id="email_filter2" class="form-control" placeholder="Enter email">
                        </div>
                        <div class="col-md-4 mt-3">
                            <input type="text" id="amount_filter2" class="form-control" placeholder="Enter amount">
                        </div>

                        <div class="d-flex justify-content-end my-3">
                            <button id="applyFilters2"
                                class="btn btn-primary btn-sm me-2 px-4 applyFilters">Filter</button>
                            <button id="clearFilters" class="btn btn-secondary btn-sm px-4 clearFilters">Clear</button>
                        </div>
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
     window.location.href = "{{ url('/') }}/admin/orders/" + id + "/view";
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
        dom: '<"top"f>rt<"bottom"lip><"clear">',
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
            { data: 'chargebee_subscription_id', name: 'subscriptions.chargebee_susbscription_id' }, // Subscription ID
            { 
                data: 'amount', name: 'amount' ,
                render: function(data, type, row) {
                    return `
                        <i class="ti ti-currency-dollar text-warning"></i>
                        <span class="text-warning">${data}</span>
                    `;
                }
            },
            { 
                data: 'name', name: 'name' ,
                render: function(data, type, row) {
                    return `
                        <img src="https://cdn-icons-png.flaticon.com/128/2202/2202112.png" style="width: 35px" alt="">
                        <span>${data}</span>
                    `;
                }
            },
            {
                data: 'email', name: 'users.email' ,
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center gap-1">
                                <i style= "color: #00BBFF"; class="ti ti-mail fs-5"></i>
                                <span style= "color: #00BBFF";>${data}</span>    
                            </div>
                        `;
                    }
            },
            {
                data: 'status',
                name: 'subscriptions.status',
                render: function(data, type, row) {
                    let statusClass = '';

                    switch (data.toLowerCase()) {
                        case 'active':
                            statusClass = 'active_status';
                            break;
                        case 'inactive':
                            statusClass = 'inactive_status';
                            break;
                        case 'pending':
                            statusClass = 'pending_status';
                            break;
                        default:
                            statusClass = '';
                            break;
                    }

                    return `<span class="${statusClass} text-capitalize">${data}</span>`;
                }
            },

            {
                data: 'last_billing_date',
                name: 'last_billing_date',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex align-items-center gap-1">
                            <i class="ti ti-calendar-month fs-5"></i>
                            <span>${data}</span>
                        </div>
                    `;
                }
            },
            {
                data: 'next_billing_date',
                name: 'next_billing_date',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex align-items-center gap-1">
                            <i class="ti ti-calendar-month fs-5"></i>
                            <span>${data}</span>
                        </div>
                    `;
                }
            },

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
        dom: '<"top"f>rt<"bottom"lip><"clear">',
       columns: [
            { data: 'id', name: 'subscriptions.chargebee_susbscription_id' }, // Subscription ID
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
            { data: 'chargebee_subscription_id', name: 'subscriptions.chargebee_susbscription_id' }, // Subscription ID
            { data: 'created_at', name: 'subscriptions.created_at' }, // Date
            { 
                data: 'amount', name: 'amount' ,
                render: function(data, type, row) {
                    return `
                        <i class="ti ti-currency-dollar text-warning"></i>
                        <span class="text-warning">${data}</span>
                    `;
                }
            },
            { 
                data: 'name', name: 'name' ,
                render: function(data, type, row) {
                    return `
                        <img src="https://cdn-icons-png.flaticon.com/128/2202/2202112.png" style="width: 35px" alt="">
                        <span>${data}</span>
                    `;
                }
            },
            {
                data: 'email', name: 'users.email' ,
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center gap-1">
                                <i style= "color: #00BBFF"; class="ti ti-mail fs-5"></i>
                                <span style= "color: #00BBFF";>${data}</span>    
                            </div>
                        `;
                    }
            },
            {
                data: 'status',
                name: 'subscriptions.status',
                render: function(data, type, row) {
                    let statusClass = '';

                    switch (data.toLowerCase()) {
                        case 'active':
                            statusClass = 'active_status';
                            break;
                        case 'inactive':
                            statusClass = 'inactive_status';
                            break;
                        case 'pending':
                            statusClass = 'pending_status';
                            break;
                         case 'cancelled':
                            statusClass = 'cancel-btn py-1 px-2 rounded-2';
                            break;
                        default:
                            statusClass = '';
                            break;
                    }

                    return `<span class="${statusClass} text-capitalize">${data}</span>`;
                }
            },
            { data: 'cancellation_at', name: 'cancellation_at' }, // Subscription status
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