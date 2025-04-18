@extends('layouts.app')

@section('title', 'Roles & Permissions')

@push('styles')
    <style>
        .pricing-card {
            /* border: 1px solid #ddd; */
            background-color: var(--secondary-color);
            box-shadow: rgba(167, 124, 252, 0.529) 0px 5px 10px 0px;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: 0.3s ease-in-out;
        }

        a {
            text-decoration: none
        }

        .pricing-card:hover {
            box-shadow: 0px 5px 15px rgba(163, 163, 163, 0.15);
            transform: translateY(-10px);
        }

        .popular {
            position: relative;
            background: linear-gradient(270deg, rgba(89, 74, 253, 0.7) 0%, #8d84f5 100%);
            color: white;
        }

        .grey-btn {
            background-color: var(--secondary-color);
            color: #fff;
        }

        .popular::before {
            content: "Most Popular";
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #ffcc00;
            color: #000;
            padding: 5px 10px;
            font-size: 14px;
            font-weight: bold;
            border-radius: 5px;
        }
    </style>
@endpush

@section('content')
    <section class="py-3">
        <h2 class="text-center fw-bold">Choose Your Plan</h2>
        <p class="text-center ">Select a plan that fits your needs.</p>

        <div class="row mt-4">
            <!-- Basic Plan -->
            <div class="col-md-4">
                <div class="pricing-card">
                    <h4 class="fw-bold">Basic</h4>
                    <h2 class="fw-bold">$9.99 <span class=" fs-6">/month</span></h2>
                    <p class="">Perfect for individuals</p>
                    <hr>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 10 Projects</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Basic Support</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 5GB Storage</li>
                        <li class="mb-2" class=""><i class="fas fa-times text-danger"></i> Custom Domain</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 10 Projects</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Basic Support</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 5GB Storage</li>
                        <li class="mb-2" class=""><i class="fas fa-times text-danger"></i> Custom Domain</li>

                    </ul>
                    <a href="#" class="m-btn py-2 px-4 w-100 rounded-2 d-flex align-items-center justify-content-center">Get Started</a>
                </div>
            </div>

            <!-- Standard Plan (Most Popular) -->
            <div class="col-md-4">
                <div class="pricing-card popular">
                    <h4 class="fw-bold">Standard</h4>
                    <h2 class="fw-bold">$19.99 <span class="fs-6">/month</span></h2>
                    <p>Best for small businesses</p>
                    <hr>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 50 Projects</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Priority Support</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 50GB Storage</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Custom Domain</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 50 Projects</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Priority Support</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 50GB Storage</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Custom Domain</li>
                    </ul>
                    <a href="#" class="grey-btn py-2 px-4 w-100 rounded-2 d-flex align-items-center justify-content-center">Get Started</a>
                </div>
            </div>

            <!-- Premium Plan -->
            <div class="col-md-4">
                <div class="pricing-card">
                    <h4 class="fw-bold">Premium</h4>
                    <h2 class="fw-bold">$49.99 <span class=" fs-6">/month</span></h2>
                    <p class="">For enterprises</p>
                    <hr>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Unlimited Projects</li>

                        <li class="mb-2"><i class="fas fa-check text-success"></i> Unlimited Projects</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 24/7 Support</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 1TB Storage</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Custom Domain</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 24/7 Support</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> 1TB Storage</li>
                        <li class="mb-2"><i class="fas fa-check text-success"></i> Custom Domain</li>
                    </ul>
                    <a href="#" class="m-btn py-2 px-4 w-100 rounded-2 d-flex align-items-center justify-content-center">Get Started</a>
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

        // let table = new DataTable('#myTable', {
        //     responsive: true
        // });

        $(document).ready(function() {
            var table = $('#myTable').DataTable();

            $(".dt-search").append(
                '<button id="addNew" data-bs-target="#addRoleModal" data-bs-toggle="modal" class="m-btn rounded-1 border-0 ms-2" style="padding: .4rem 1.4rem"><i class="fa-solid fa-plus"></i> Add Permission</button>'
            );
        });
    </script>
@endpush
