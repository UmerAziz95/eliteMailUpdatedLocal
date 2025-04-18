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

        <div class="row gy-4">
            <!-- Administrator Role -->
            <div class="col-xl-4 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <p class="fw-normal mb-0">Total 4 users</p>
                            <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                                @foreach (['5.png', '12.png', '6.png', '3.png'] as $avatar)
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
                                <h5 class="mb-1">Administrator</h5>
                                <a href="javascript:;" data-bs-toggle="modal" data-bs-target="#addRoleModal">Edit Role</a>
                            </div>
                            <a href="javascript:void(0);"><i class="icon-base ti tabler-copy icon-md text-heading"></i></a>
                        </div>
                    </div>
                </div>
            </div>

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
                            <a href="javascript:void(0);"><i
                                    class="icon-base ti tabler-copy icon-md text-heading"></i></a>
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
                                <button data-bs-target="#addRoleModal" data-bs-toggle="modal"
                                    class="text-nowrap m-btn py-1 px-3 mb-3 rounded-2 border-0">Add
                                    New Role</button>
                                <p class="mb-0">
                                    Add new role, <br>
                                    if it doesn't exist.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('modules.roles.listing')


        <!-- Modal -->
        <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-body p-5 position-relative">
                        <button type="button" class="p-0 border-0 bg-transparent position-absolute"
                            style="top: 20px; right: 20px" data-bs-dismiss="modal" aria-label="Close"><i
                                class="fa-solid fa-xmark"></i></button>
                        <div class="text-center mb-6">
                            <h4 class="role-title">Edit Role</h4>
                            <p>Set role permissions</p>
                        </div>

                        @include('modules.roles.add_new_form')
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        $(document).ready(function() {
            var table = $('#myTable').DataTable();

            $(".dt-search").append(
                '<button id="addNew" data-bs-target="#addRoleModal" data-bs-toggle="modal" class="m-btn rounded-1 border-0 ms-2" style="padding: .4rem 1.4rem"><i class="fa-solid fa-plus"></i> Add New User</button>'
            );
        });
    </script>
@endpush
