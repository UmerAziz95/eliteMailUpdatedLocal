@extends('admin.layouts.app')

@section('title', 'Admins')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
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

    <div class="row gy-3">
        <div class="counters col-lg-6">
            <div class="card p-2 counter_1">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Users</h6>
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
                            <img src="https://cdn-icons-gif.flaticon.com/17556/17556487.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-2 counter_2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Active Users</h6>
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
                            <img src="https://cdn-icons-gif.flaticon.com/16045/16045954.gif" width="50"
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
                            <h6 class="text-heading">InActive Users</h6>
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

            <div class="card p-2 counter_1">
                <div class="card-body">
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between" data-bs-toggle="offcanvas"
                        data-bs-target="#offcanvasAddAdmin" aria-controls="offcanvasAddAdmin">
                        <div class="content-left">
                            <h6 class="text-heading">Add New Record</h6>
                            <div class="d-flex align-items-center my-1">
                                <small class="opacity-75">Click here to add a new record</small>
                                {{-- <h4 class="mb-0 me-2 fs-1" id="inactive_counter">0</h4> --}}
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/17626/17626903.gif" width="50"
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
                        <div class="col-md-12">
                            <input type="text" id="user_name_filter" class="form-control" placeholder="Enter username">
                        </div>
                        <div class="col-md-12">
                            <input type="text" id="email_filter" class="form-control" placeholder="Enter email">
                        </div>

                        <div class="col-md-12">
                            <select id="status_filter" class="form-select">
                                <option value="">Select Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="d-flex align-item-center gap-2">
                            <button id="applyFilters" class="btn btn-primary btn-sm px-4 me-2">Filter</button>
                            <button id="clearFilters" class="btn btn-secondary px-4 btn-sm">Clear</button>
                        </div>
                    </div>

                    <img src="https://cdn-icons-gif.flaticon.com/19009/19009016.gif" width="30%"
                        style="border-radius: 50%" alt="">
                </div>
            </div>
        </div>
    </div>

    {{-- <div class="col-sm-6 col-xl-4">
        <div class="card p-2">
            <div class="card-body">
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
    </div> --}}


    </div>

    <div class="card py-3 px-4">
        @include('admin.admins._table')
    </div>


    <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdmin" aria-labelledby="offcanvasAddAdminLabel"
        aria-modal="true" role="dialog">
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

    });


    function viewOrder(id) {
        window.location.href = "{{ route('admin.index') }}?id=" + id;
    }

      // Apply Filters
    $('#applyFilters').click(function() {
        refreshDataTable();
    });

    // Clear Filters
    $('#clearFilters').click(function() {
        $('#user_name_filter').val('');
        $('#email_filter').val('');
        $('#status_filter').val('');
        refreshDataTable();
    });

    function refreshDataTable(){
                 if (window.orderTables && window.orderTables.all) {
                    window.orderTables.all.ajax.reload(null, false);
        }
    }

    

function initDataTable(planId = '') {
 
    const tableId = '#myTable';
    const $table = $(tableId);

    if (!$table.length) {
    
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
                d.user_name = $('#user_name_filter').val();
                d.email = $('#email_filter').val();
                d.status = $('#status_filter').val();
            },
            dataSrc: function(json) {
              
                return json.data;
            },
            error: function(xhr, error, thrown) {
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
            { 
                data: 'name', name: 'name' ,
                render: function(data, type, row) {
                    return `
                        <img src="https://cdn-icons-png.flaticon.com/128/2202/2202112.png" style="width: 35px" alt="">
                        <span>${data}</span>
                    `;
                }
            },
            { data: 'role', name: 'role', orderable: false, searchable: false },
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
    this.api().columns.adjust();
    this.api().responsive?.recalc();

    @if (Auth::user()->hasPermissionTo('Mod'))
        // Don't show the button for users with 'Mod' permission
    @else
        // const button = `
        //     <button class="m-btn fw-semibold border-0 rounded-1 ms-2 text-white"
        //             style="padding: .4rem 1rem"
        //             type="button"
        //             data-bs-toggle="offcanvas"
        //             data-bs-target="#offcanvasAddAdmin"
        //             aria-controls="offcanvasAddAdmin">
        //         + Add New Record
        //     </button>
        // `;
        // $('.dataTables_filter').append(button);
    @endif
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
   
    toastr.error('Error initializing table. Please refresh the page.');
}

}

    $(document).ready(function() {
        try {
           
            window.orderTables = {};
            // Initialize table for all Subscriptions
            window.orderTables.all = initDataTable();

            // Handle tab changes
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                const tabId = $(e.target).attr('id');
               
                
                // Force recalculation of column widths for visible tables
                setTimeout(function() {
                    Object.values(window.orderTables).forEach(function(table) {
                        if ($(table.table().node()).is(':visible')) {
                            table.columns.adjust();
                            table.responsive.recalc();
                            
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
                    
                }
            }, 10);

            // Add global error handler for AJAX requests
            $(document).ajaxError(function(event, xhr, settings, error) {
              
                if (xhr.status === 401) {
                    window.location.href = "{{ route('login') }}";
                } else if (xhr.status === 403) {
                    toastr.error('You do not have permission to perform this action');
                }
            });
        } catch (error) {
            
        }
    });
</script>


<script>
    $('#createUserForm').on('submit', function (e) {
        e.preventDefault();

        const form = this;
        const userId = $('#user_id').val();
        const password = $('#password').val();
        const confirmPassword = $('#confirm_password').val();

        // Disable button and add loading class at the start
        $('#submit_btn').attr('disabled', true).addClass('btn-loading');

     if (password && password !== confirmPassword) {
        toastr.error('Passwords do not match!');
        // Wait 3 seconds before re-enabling the button
        setTimeout(function () {
            $('#submit_btn').removeAttr('disabled').removeClass('btn-loading');
        }, 3000);
        return;
}
        let formData = new FormData(form);
        let url = userId
            ? "{{ url('admin/') }}/" + userId  // Edit URL
            : "{{ route('admin.users.store') }}";   // Create URL

        let method = "POST"; // Both create/update use POST, with _method spoofed for update

        if (userId) {
            formData.append('_method', 'PUT'); // Laravel expects PUT for update
        }

        $.ajax({
            url: url,
            method: method,
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                let action = userId ? 'updated' : 'created';
                toastr.success(`User ${action} successfully!`);

                // Reset and clear form
                $('#createUserForm')[0].reset();
                $('#user_id').val('');

                // Hide the offcanvas
                let offcanvasElement = document.getElementById('offcanvasAddAdmin');
                let offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
                offcanvasInstance.hide();

                // Reload DataTable
                if (window.orderTables && window.orderTables.all) {
                    window.orderTables.all.ajax.reload(null, false);
                }

                // Re-enable button
                 setTimeout(function () {
                    $('#submit_btn').removeAttr('disabled').removeClass('btn-loading');
                }, 3000);
            },
            error: function (xhr) {
                if (xhr.responseJSON?.errors) {
                    let errors = xhr.responseJSON.errors;
                    let errorMessages = Object.values(errors).map(err => err.join(', ')).join('<br>');
                    toastr.error(errorMessages);
                } else {
                    toastr.error('Something went wrong.');
                }

                // Re-enable the button on error
                 setTimeout(function () {
                    $('#submit_btn').removeAttr('disabled').removeClass('btn-loading');
                }, 3000);
            }
        });
    });
</script>



<script>
    $(document).on('click', '.edit-btn', function (e) {
        e.preventDefault();

        let userId = $(this).data('id');

        // Fetch user data via AJAX
        $.ajax({
            url: "{{ url('admin/') }}/" + userId + "/edit",
            method: "GET",
            success: function (data) {
                // Populate the form fields
                $('#user_id').val(data.id);
                $('#full_name').val(data.name);
                $('#email').val(data.email);
                $('#status').val(data.status);
                $('#role').val(data?.roles[0]?.id ?? '');

                // Do not set password fields for editing

                // Open the offcanvas
                let offcanvasElement = document.getElementById('offcanvasAddAdmin');
                let offcanvasInstance = new bootstrap.Offcanvas(offcanvasElement);
                offcanvasInstance.show();
            },
            error: function () {
                toastr.error('Failed to fetch user details.');
            }
        });
    });
    </script>
    <script>
    $(document).on('click', '.delete-btn', function (e) {
    e.preventDefault();
    let userId = $(this).data('id');

    if (confirm("Are you sure you want to delete this user?")) {
        $.ajax({
            url: `/admin/${userId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                toastr.success(response.message);
                if (window.orderTables && window.orderTables.all) {
                    window.orderTables.all.ajax.reload(); // Refresh table
                }
            },
            error: function (xhr) {
                toastr.error('Failed to delete user.');
            }
        });
    }
});
    </script>

    <script>
        $(document).ready(function() {
            $('#permissions').select2({
                placeholder: 'Select permissions',
                width: '100%'
            });
        });
    </script>
@endpush