@extends('admin.layouts.app')

@section('title', 'Roles & Permissions')

@push('styles')
<style>
    .avatar-group .avatar:hover {
        z-index: 30;
        transition: all .25s ease;
    }

    .pull-up:hover {
        z-index: 30;
        border-radius: 50%;
        box-shadow: var(--box-shadow);
        transform: translateY(-4px) scale(1.02);
    }

    .avatar {
        --bs-avatar-size: 2.5rem;
        position: relative;
        width: var(--bs-avatar-size);
        height: var(--bs-avatar-size);
        cursor: pointer;
    }

    .avatar-group .avatar {
        margin-inline-start: -0.8rem;
        transition: all .25s ease;
    }

    a {
        color: var(--second-primary);
        text-decoration: none;
        font-size: 14.5px
    }

    .avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 2px solid var(--secondary-color);
    }

    .avatar .avatar-initial {
        position: absolute;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--primary-color);
        color: var(--bs-white);
        font-weight: 500;
        inset: 0;
        text-transform: uppercase;
        border-radius: 50%;
    }

    /* h5 {
                    font-size: 18px
                } */

    .form-check {
        padding-left: 0 !important
    }

    .form-check-input {
        background-color: transparent;
        border-radius: 2px !important;
        margin-top: .35rem
    }

    .form-check-input:checked {
        background-color: var(--second-primary);
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <div>
        <h5>Roles List</h5>
        <p>A role provides access to predefined menus and features so that an administrator can have access based on
            their assigned role.</p>
    </div>

    <div class="row g-0">

        <div class="counters">
            @foreach ($roles as $role)
            <div class="">
                <div class="card counter_1">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <p class="fw-normal mb-0 text-dark">Total {{ $role->users->count() }} users</p>
                            <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                                @foreach ($role->users as $user)
                                @php
                                $image = $user->profile_image
                                ? asset('storage/profile_images/' . $user->profile_image)
                                : asset('storage/profile_images/default.jpg');
                                @endphp
                                <li class="avatar pull-up" data-bs-toggle="tooltip" title="{{ $user->name ?? 'User' }}">
                                    <img class="rounded-circle" src="{{ $image }}" alt="Avatar" height="40" width="40">
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="role-heading">
                                <h5 class="mb-1 text-capitalize">{{ $role->name }}</h5>
                                {{-- <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#addRoleModal">Add
                                    Role</a> --}}
                            </div>
                            <a href="javascript:void(0);">
                                <i class="icon-base ti tabler-copy icon-md text-heading"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>


        {{--
        <!-- Manager Role -->
        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fw-normal mb-0">Total 7 users</p>
                        <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                            @foreach (['4.png', '1.png', '2.png'] as $avatar)
                            <li class="avatar pull-up" data-bs-toggle="tooltip" title="User">
                                <img class="rounded-circle"
                                    src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/avatars/{{ $avatar }}"
                                    alt="Avatar">
                            </li>
                            @endforeach
                            <li class="avatar">
                                <span class="avatar-initial pull-up" data-bs-toggle="tooltip" title="4 more">+4</span>
                            </li>
                        </ul>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="role-heading">
                            <h5 class="mb-1">Manager</h5>
                            <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#addRoleModal">Edit Role</a>
                        </div>
                        <a href="javascript:void(0);"><i class="icon-base ti tabler-copy icon-md text-heading"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Role -->
        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fw-normal mb-0">Total 5 users</p>
                        <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                            @foreach (['6.png', '9.png', '12.png'] as $avatar)
                            <li class="avatar pull-up" data-bs-toggle="tooltip" title="User">
                                <img class="rounded-circle"
                                    src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/avatars/{{ $avatar }}"
                                    alt="Avatar">
                            </li>
                            @endforeach
                            <li class="avatar">
                                <span class="avatar-initial pull-up" data-bs-toggle="tooltip" title="2 more">+2</span>
                            </li>
                        </ul>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="role-heading">
                            <h5 class="mb-1">Users</h5>
                            <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#addRoleModal">Edit Role</a>
                        </div>
                        <a href="javascript:void(0);"><i class="icon-base ti tabler-copy icon-md text-heading"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Support Role -->
        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fw-normal mb-0">Total 3 users</p>
                        <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                            @foreach (['3.png', '9.png', '4.png'] as $avatar)
                            <li class="avatar pull-up" data-bs-toggle="tooltip" title="User">
                                <img class="rounded-circle"
                                    src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/avatars/{{ $avatar }}"
                                    alt="Avatar">
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="role-heading">
                            <h5 class="mb-1">Support</h5>
                            <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#addRoleModal">Edit Role</a>
                        </div>
                        <a href="javascript:void(0);"><i class="icon-base ti tabler-copy icon-md text-heading"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Restricted Users -->
        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fw-normal mb-0">Total 3 users</p>
                        <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                            @foreach (['3.png', '9.png', '4.png'] as $avatar)
                            <li class="avatar pull-up" data-bs-toggle="tooltip" title="User">
                                <img class="rounded-circle"
                                    src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/avatars/{{ $avatar }}"
                                    alt="Avatar">
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="role-heading">
                            <h5 class="mb-1">Support</h5>
                            <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#addRoleModal">Edit
                                Role</a>
                        </div>
                        <a href="javascript:void(0);"><i class="icon-base ti tabler-copy icon-md text-heading"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Users -->
        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="card h-100">
                <div class="row h-100">
                    <div class="col-sm-5">
                        <div class="d-flex align-items-end h-100 justify-content-center mt-sm-0 mt-4">
                            <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/add-new-roles.png"
                                class="img-fluid" alt="Image" width="83">
                        </div>
                    </div>
                    <div class="col-sm-7">
                        <div class="card-body text-sm-end text-center ps-sm-0">
                            @if (!auth()->user()->hasPermissionTo('Mod'))
                            <button data-bs-target="#addRoleModal" data-bs-toggle="modal"
                                class="text-nowrap m-btn py-1 px-3 mb-3 rounded-2 border-0">
                                Add New Role
                            </button>
                            @endif

                            <p class="mb-0">

                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        <div class="text-center mb-6">
            @if($errors->any())
            <div class="" style="background-color: #2f3349">
                <h4 class="role-title text-danger">Opps! error</h4>
                <strong class="text-danger">There were some problems with your input.</strong>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li class="text-danger" style="list-style: none">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>

        @include('admin.roles.listing')


    </div>

    <!-- Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-body p-5 position-relative">
                    <button type="button" class="p-0 border-0 bg-transparent position-absolute"
                        style="top: 20px; right: 20px" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa-solid fa-xmark"></i></button>
                    <div class="text-center mb-6">
                        <h4 class="role-title">Manage Role</h4>
                        <p>Set role permissions</p>
                    </div>


                    @include('admin.roles.add_new_form')
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')

<script>
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
            url: "{{ route('admin.role.index') }}",
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
            { data: 'permissions', name: 'permissions' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action' },
          
       
        ],
        columnDefs: [
            { width: '20%', targets: 0 },
            { width: '20%', targets: 1 },
            { width: '20%', targets: 2 },
            { width: '20%', targets: 3},
            { width: '20%', targets: 3},
          
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

    // 🔽 Append your custom button next to the search bar, if user doesn't have Mod permission
    @if (!auth()->user()->hasPermissionTo('Mod'))
        const button = `
            <button id="addNew" data-bs-target="#addRoleModal" data-bs-toggle="modal" class="m-btn rounded-1 border-0 ms-2" style="padding: .4rem 1.4rem">
                <i class="fa-solid fa-plus"></i> Add New Role
            </button>
        `;
        $('.dataTables_filter').append(button);
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
    $('#addRoleModal').on('shown.bs.modal', function () {
    $('#permissions').select2({
        placeholder: "Select permissions",
        width: '100%',
        dropdownParent: $('#addRoleModal')
    });
});

$('body').on('click','#addNew',function(){
            // Clear the role name
            $('#name').val('');

            // Clear the role_id hidden field
            $('#roleId').val('');

            // Clear selected permissions
            $('#permissions').val(null).trigger('change');

            // Optional: clear validation messages
            $('.text-danger').html('');
        });

 function editRole(id) {
        $('#addRoleModal').modal('show');
        $('#addRoleForm')[0].reset();
        $('#permissions').val(null).trigger('change');

        $.ajax({
            url: `/admin/roles/${id}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const role = response.role;
                    $('#roleId').val(role.id);
                    $('#name').val(role.name);

                    // Set selected permissions by ID
                    const permissionIds = role.permissions.map(p => p.id);
                    $('#permissions').val(permissionIds).trigger('change');
                }
            },
            error: function() {
                toastr.error('Failed to load role details.');
            }
        });
    }


</script>
@endpush