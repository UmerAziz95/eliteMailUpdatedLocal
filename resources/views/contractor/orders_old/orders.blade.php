@extends('contractor.layouts.app')

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
@endpush

@section('content')
    <section class="py-3">
        <div class="row gy-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card p-2">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <h6 class="text-heading">Total Orders</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">21,459</h4>
                                    <p class="text-success mb-0">(+29%)</p>
                                </div>
                                <small class="mb-0">Total Users</small>
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
            <div class="col-sm-6 col-xl-3">
                <div class="card p-2">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <h6 class="text-heading">Pending Orders</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">4,567</h4>
                                    <p class="text-success mb-0">(+18%)</p>
                                </div>
                                <small class="mb-0">Last week analytics </small>
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
            <div class="col-sm-6 col-xl-3">
                <div class="card p-2">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <h6 class="text-heading">Complete Orders</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">19,860</h4>
                                    <p class="text-danger mb-0">(-14%)</p>
                                </div>
                                <small class="mb-0">Last week analytics</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="ti ti-brand-booking"></i>
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
                                <h6 class="text-heading">Active Orders</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">237</h4>
                                    <p class="text-success mb-0">(+42%)</p>
                                </div>
                                <small class="mb-0">Last week analytics</small>
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
                    <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic-tab-pane"
                        type="button" role="tab" aria-controls="basic-tab-pane" aria-selected="true">Basic</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="standard-tab" data-bs-toggle="tab" data-bs-target="#standard-tab-pane"
                        type="button" role="tab" aria-controls="standard-tab-pane"
                        aria-selected="false">Standard</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="premium-tab" data-bs-toggle="tab" data-bs-target="#premium-tab-pane"
                        type="button" role="tab" aria-controls="premium-tab-pane"
                        aria-selected="false">Premium</button>
                </li>
            </ul>
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="basic-tab-pane" role="tabpanel" aria-labelledby="basic-tab"
                    tabindex="0">
                    <div class="row gy-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-2">Filters</h5>
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
                                    <th class="text-start">User ID</th>
                                    <th>Name</th>
                                    <th>Email Address</th>
                                    <th>Purchase Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($i = 0; $i < 20; $i++)
                                    <tr>
                                        <td class="text-start">001</td>
                                        <td>
                                            <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                                style="border-radius: 50%" height="35" width="35"
                                                class="object-fit-cover" alt="">
                                            John Doe
                                        </td>
                                        <td><i class="ti ti-mail text-success"></i> Johndoe123@gmail.com</td>
                                        <td>4/4/2025</td>
                                        <td><span class="active_status">Active</span></td>
                                        <td>
                                            <button class="bg-transparent p-0 border-0 mx-2"><i
                                                    class="fa-regular fa-eye"></i></button>
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="standard-tab-pane" role="tabpanel" aria-labelledby="standard-tab"
                    tabindex="0">

                </div>

                <div class="tab-pane fade" id="premium-tab-pane" role="tabpanel" aria-labelledby="premium-tab"
                    tabindex="0">

                </div>
            </div>
        </div>


        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdmin"
            aria-labelledby="offcanvasAddAdminLabel" aria-modal="true" role="dialog">
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
        $(document).ready(function() {
            var table = $('#myTable').DataTable();

            $(".dt-search").append(
                '<button class="m-btn fw-semibold border-0 rounded-1 ms-2 text-white" style="padding: .4rem 1rem" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAddAdmin" aria-controls="offcanvasAddAdmin"> + Add New Record </button>'
            );
        });
    </script>
@endpush
