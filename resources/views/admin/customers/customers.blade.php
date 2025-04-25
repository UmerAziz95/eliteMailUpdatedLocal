@extends('admin.layouts.app')

@section('title', 'Customers')

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
                                <h6 class="text-heading">Total Users</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2" id="total_counter">0</h4>
                                    <p class="text-success mb-0"></p>
                                </div>
                                <small class="mb-0"></small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="ti ti-user-search"></i>
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
                                <h6 class="text-heading">Active Users</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2" id="active_counter" >0</h4>
                                    <p class="text-danger mb-0"></p>
                                </div>
                                <small class="mb-0"></small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="ti ti-user-check"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
        </div>
            <div class="col-sm-6 col-xl-4">
                <div class="card p-2">
                    <div class="card-body">  
                    {{-- //card body --}}
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <h6 class="text-heading">InActive Users</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2" id="inactive_counter">0</h4>
                                    <p class="text-success mb-0"></p>
                                </div>
                                <small class="mb-0"></small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class="ti ti-user-plus"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        
         
        </div>

        <div class="card py-3 px-4">
            <div class="row gy-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-3">Filters</h5>
                </div>
               <div class="col-md-4">
                            <input type="text" id="user_name_filter" class="form-control" placeholder="Enter username">
                </div>
               <div class="col-md-4">
                            <input type="text" id="email_filter" class="form-control" placeholder="Enter email">
                </div>
               
                <div class="col-md-4">
                    <select id="status_filter" class="form-select">
                        <option value="">Select Status</option>
                        <option value="1">active</option>
                        <option value="0">inactive</option>
                     
                    </select>
                </div>
            </div>
            <hr>
           @include('admin.customers._table')
        </div>


        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdmin"
            aria-labelledby="offcanvasAddAdminLabel" aria-modal="true" role="dialog">
            <div class="offcanvas-header" style="border-bottom: 1px solid var(--input-border)">
                <h5 id="offcanvasAddAdminLabel" class="offcanvas-title">Add User</h5>
                <button class="border-0 bg-transparent" type="button" data-bs-dismiss="offcanvas" aria-label="Close"><i
                        class="fa-solid fa-xmark fs-5"></i></button>
            </div>
            <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
                @include('modules.customers.add_new_form')
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
        window.location.href = "{{ route('admin.customerList') }}?id=" + id;
    }

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
            responsive: true,
            autoWidth: false,
            ajax: {
                url: "{{ route('admin.customerList') }}",
                type: "GET",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                data: function(d) {
                    d.plan_id = planId;
                    return d;
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
                        toastr.error('Error loading data: ' + error);
                    }
                }
            },
            columns: [
                { data: 'id', name: 'id' },
                { data: 'name', name: 'name' },
                { data: 'role', name: 'role', orderable: false, searchable: false },
                { data: 'email', name: 'email' },
                { data: 'status', name: 'status', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            columnDefs: [
                { width: '10%', targets: 0 }, // ID
                { width: '20%', targets: 1 }, // Name
                { width: '15%', targets: 2 }, // Role
                { width: '25%', targets: 3 }, // Email
                { width: '15%', targets: 4 }, // Status
                { width: '15%', targets: 5 }  // Action
            ],
            order: [[1, 'desc']],
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




    $(document).ready(function() {
        try {
            console.log('Document ready, initializing tables');
            window.orderTables = {};
            // Initialize table for all Subscriptions
            window.orderTables.all = initDataTable();

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
</script>
@endpush
