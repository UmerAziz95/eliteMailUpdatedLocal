@extends('admin.layouts.app')

@section('title', 'Admins')

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
                    <select id="select1" class="form-select">
                        <option value="">Select Role</option>
                        <option value="1">Option 1</option>
                        <option value="2">Option 2</option>
                        <option value="3">Option 3</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select id="select2" class="form-select">
                        <option value="">Select Plan</option>
                        <option value="1">Option A</option>
                        <option value="2">Option B</option>
                        <option value="3">Option C</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select id="select3" class="form-select">
                        <option value="">Select Status</option>
                        <option value="1">Choice X</option>
                        <option value="2">Choice Y</option>
                        <option value="3">Choice Z</option>
                    </select>
                </div>
            </div>
            <hr>
           @include('admin.admins._table')
        </div>


        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdmin"
            aria-labelledby="offcanvasAddAdminLabel" aria-modal="true" role="dialog">
            <div class="offcanvas-header" style="border-bottom: 1px solid var(--input-border)">
                <h5 id="offcanvasAddAdminLabel" class="offcanvas-title">Add User</h5>
                <button class="border-0 bg-transparent" type="button" data-bs-dismiss="offcanvas" aria-label="Close"><i
                        class="fa-solid fa-xmark fs-5"></i></button>
            </div>
            <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
                @include('admin.admins.add_new_form')
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
        window.location.href = "{{ route('admin.index') }}?id=" + id;
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
        dom: '<"top"f>rt<"bottom"lip><"clear">', // expose filter (f) and move others
        ajax: {
            url: "{{ route('admin.index') }}",
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
            { width: '10%', targets: 0 },
            { width: '20%', targets: 1 },
            { width: '15%', targets: 2 },
            { width: '25%', targets: 3 },
            { width: '15%', targets: 4 },
            { width: '15%', targets: 5 }
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

            // 🔽 Append your custom button next to the search bar
            const button = `
                <button class="m-btn fw-semibold border-0 rounded-1 ms-2 text-white"
                        style="padding: .4rem 1rem"
                        type="button"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasAddAdmin"
                        aria-controls="offcanvasAddAdmin">
                    + Add New Record
                </button>
            `;

            $('.dataTables_filter').append(button);
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


<script>
    $('#createUserForm').on('submit', function (e) {
        e.preventDefault();

        // Get the actual form element
        let form = this;

        // Make sure password matches confirmation
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();

        if (password !== confirmPassword) {
            toastr.error('Passwords do not match!');
            return;
        }

        // Create FormData object to automatically capture all fields
        let formData = new FormData(form);

        $.ajax({
            url: "{{ route('admin.users.store') }}",
            method: "POST",
            data: formData,
            processData: false,  // prevent jQuery from converting data into a query string
            contentType: false,  // let the browser set the content-type including boundary
            success: function (response) {
                toastr.success('User created successfully!');
                $('#createUserForm')[0].reset();
            },
            error: function (xhr) {
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessages = Object.values(errors).map(err => err.join(', ')).join('<br>');
                    toastr.error(errorMessages);
                } else {
                    toastr.error('Something went wrong.');
                }
            }
        });
    });
</script>



@endpush
