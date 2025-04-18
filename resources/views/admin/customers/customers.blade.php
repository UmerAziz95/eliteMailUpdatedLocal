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
            <div class="col-sm-6 col-xl-3">
                <div class="card p-2">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <h6 class="text-heading">Session</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">21,459</h4>
                                    <p class="text-success mb-0">(+29%)</p>
                                </div>
                                <small class="mb-0">Total Users</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-users"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card p-2">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <h6 class="text-heading">Paid Users</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">4,567</h4>
                                    <p class="text-success mb-0">(+18%)</p>
                                </div>
                                <small class="mb-0">Last week analytics </small>
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
            <div class="col-sm-6 col-xl-3">
                <div class="card p-2">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <h6 class="text-heading">Active Users</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">19,860</h4>
                                    <p class="text-danger mb-0">(-14%)</p>
                                </div>
                                <small class="mb-0">Last week analytics</small>
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
            <div class="col-sm-6 col-xl-3">
                <div class="card p-2">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <h6 class="text-heading">Pending Users</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">237</h4>
                                    <p class="text-success mb-0">(+42%)</p>
                                </div>
                                <small class="mb-0">Last week analytics</small>
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
            <div class="table-responsive">
                <table id="myTable" class="display">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Plan</th>
                            <th>Billing</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 20; $i++)
                            <tr>
                                <td>
                                    <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                        style="border-radius: 50%" height="35" width="35" class="object-fit-cover"
                                        alt="">
                                    John Doe
                                </td>
                                <td><i class="ti ti-device-desktop-minus text-success me-2"></i>Admin</td>
                                <td>Premium</td>
                                <td>Monthly</td>
                                <td><span class="active_status">Active</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="bg-transparent p-0 border-0"><i
                                                class="fa-regular fa-trash-can text-danger"></i></button>
                                        <button class="bg-transparent p-0 border-0 mx-2"><i
                                                class="fa-regular fa-eye"></i></button>
                                        <div class="dropdown">
                                            <button class="p-0 bg-transparent border-0" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#">Action</a></li>
                                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                        style="border-radius: 50%" height="35" width="35"
                                        class="object-fit-cover" alt="">
                                    John Doe
                                </td>
                                <td><i class="ti ti-user-cog me-2 text-warning"></i>Customer</td>
                                <td>Basic</td>
                                <td>Annual</td>
                                <td><span class="inactive_status">Inactive</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="bg-transparent p-0 border-0"><i
                                                class="fa-regular fa-trash-can text-danger"></i></button>
                                        <button class="bg-transparent p-0 border-0 mx-2"><i
                                                class="fa-regular fa-eye"></i></button>
                                        <div class="dropdown">
                                            <button class="p-0 bg-transparent border-0" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#">Action</a></li>
                                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                        style="border-radius: 50%" height="35" width="35"
                                        class="object-fit-cover" alt="">
                                    John Doe
                                </td>
                                <td><i class="ti ti-contract me-2 text-primary"></i>Contractor</td>
                                <td>Basic</td>
                                <td>Annual</td>
                                <td><span class="pending_status">Pending</span></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="bg-transparent p-0 border-0"><i
                                                class="fa-regular fa-trash-can text-danger"></i></button>
                                        <button class="bg-transparent p-0 border-0 mx-2"><i
                                                class="fa-regular fa-eye"></i></button>
                                        <div class="dropdown">
                                            <button class="p-0 bg-transparent border-0" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#">Action</a></li>
                                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
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
        $(document).ready(function() {
            var table = $('#myTable').DataTable();

            $(".dt-search").append(
                '<button class="m-btn fw-semibold border-0 rounded-1 ms-2 text-white" style="padding: .4rem 1rem" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddAdmin" aria-controls="offcanvasAddAdmin"> + Add New Record </button>'
            );
        });
    </script>
@endpush
