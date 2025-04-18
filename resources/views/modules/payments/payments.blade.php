@extends('layouts.app')

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

        .form-check {
            padding-left: 0 !important
        }

        .form-check-input {
            background-color: transparent;
            border-radius: 2px !important;
            margin-top: .35rem
        }

        tr {
            border-bottom: 1px solid #ffffff63 !important
        }

        .form-check-input:checked {
            background-color: var(--second-primary);
        }

        .purple {
            background-color: rgba(129, 81, 192, 0.219);
            color: var(--second-primary);
            font-size: 14px
        }

        .orange {
            background-color: rgba(255, 189, 66, 0.236);
            color: orange;
            font-size: 14px
        }

        .green {
            background-color: rgba(147, 255, 100, 0.222);
            color: rgb(6, 203, 6);
            font-size: 14px
        }

        .sup {
            background-color: rgba(108, 255, 255, 0.208);
            color: rgb(8, 236, 236);
            font-size: 14px
        }

        .red {
            background-color: rgba(255, 86, 86, 0.188);
            color: red;
            font-size: 14px
        }

        .modal {
            scale: .5;
            transform: translateY(-450px);
            transition: all ease .6s
        }

        .modal.show {
            scale: 1;
            transform: translateY(0);
        }
    </style>
@endpush

@section('content')
    <section class="py-3">
        <div class="card py-3 px-4">
            <div class="table-responsive">
                <table id="paymentTable" class="display w-100">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Payer Name</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                            <th>Payment Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($i = 0; $i < 20; $i++)
                            <tr>
                                <td>#INV-{{ 1000 + $i }}</td>
                                <td>John Doe</td>
                                <td><i class="fa-solid fa-sack-dollar text-warning"></i> $ {{ rand(50, 500) }}.00</td>
                                <td>
                                    @php
                                        $status = ['Paid', 'Pending', 'Failed', 'Refunded'];
                                        $randomStatus = $status[array_rand($status)];
                                    @endphp
                                    <span
                                        class="{{ $randomStatus == 'Paid'
                                            ? 'green py-1 px-2 rounded-1'
                                            : ($randomStatus == 'Pending'
                                                ? 'orange py-1 px-2 rounded-1'
                                                : ($randomStatus == 'Failed'
                                                    ? 'red py-1 px-2 rounded-1'
                                                    : 'sup py-1 px-2 rounded-1')) }}">
                                        {{ $randomStatus }}
                                    </span>
                                </td>
                                <td>{{ now()->subDays(rand(1, 30))->format('d M Y') }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <button class="bg-transparent p-0 border-0" data-bs-target="#paymentModal"
                                            data-bs-toggle="modal"><i class="fa-solid fa-eye"></i></button>
                                        <div class="dropdown">
                                            <button class="p-0 bg-transparent border-0" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#">View Details</a></li>
                                                <li><a class="dropdown-item" href="#">Download Invoice</a></li>
                                                <li><a class="dropdown-item text-danger" href="#">Refund</a></li>
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


        <!-- Modal -->
        <div class="modal fade" style="scrollbar-width: none" id="addRoleModal" tabindex="-1"
            aria-labelledby="addRoleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body p-5 position-relative">
                        <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                            data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                        <div class="text-center mb-4">
                            <h4>Add New Permission</h4>
                            <p>Permissions you may use and assign to your users.</p>
                        </div>

                        <div>
                            <div class="mb-3">
                                <label for="exampleFormControlInput1" class="form-label">Permission Name</label>
                                <input type="email" class="form-control" id="exampleFormControlInput1"
                                    placeholder="Permission Name">
                            </div>

                            <div class="form-check ps-0">
                                <input class="form-check-input ms-0 me-1" type="checkbox" value=""
                                    id="flexCheckDefault">
                                <label class="form-check-label" for="flexCheckDefault">
                                    Default checkbox
                                </label>
                            </div>

                            <div class="mt-4 text-center">
                                <button class="py-2 px-3 rounded-1 border-0 m-btn">Create Permission</button>
                                <button class="btn btn-secondary opacity-50">Discard</button>
                            </div>
                        </div>
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
            var table = $('#paymentTable').DataTable();
            // $(".dt-search").append(
            //     '<button id="addNew" data-bs-target="#addRoleModal" data-bs-toggle="modal" class="m-btn rounded-1 border-0 ms-2" style="padding: .4rem 1.4rem"><i class="fa-solid fa-plus"></i> Add Permission</button>'
            // );
        });
    </script>
@endpush
