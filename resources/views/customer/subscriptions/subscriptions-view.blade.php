@extends('customer.layouts.app')

@section('title', 'Orders')

@push('styles')

@endpush

@section('content')

<!-- Include Header -->
<section class="py-3 overflow-hidden">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 30px; width: 30px; border-radius: 50px; background-color: #525252c6;">
                                <i class="fa-solid fa-chevron-left"></i>
                            </div>
                            <a href="re-order.html" class="c-btn text-decoration-none">
                                <i class="fa-solid fa-cart-plus"></i>
                                Re-order
                            </a>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mt-3">
                            <div>
                                <h5 class="mb-3">Order #recBT123sdlkw</h5>
                                <h6><span class="opacity-50 fs-6">Order Date:</span> Feb 12, 2025</h6>
                            </div>
                            <button class="border border-success rounded-2 py-1 px-2 text-success bg-transparent">Order
                                Done & Delivered</button>
                        </div>



                        <ul class="nav nav-tabs order_view d-flex align-items-center justify-content-between" id="myTab"
                            role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-5 active" id="configuration-tab" data-bs-toggle="tab"
                                    data-bs-target="#configuration-tab-pane" type="button" role="tab"
                                    aria-controls="configuration-tab-pane" aria-selected="true">Configuration</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-5" id="email-tab" data-bs-toggle="tab"
                                    data-bs-target="#email-tab-pane" type="button" role="tab"
                                    aria-controls="email-tab-pane" aria-selected="false">Emails</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-5" id="subscription-tab" data-bs-toggle="tab"
                                    data-bs-target="#subscription-tab-pane" type="button" role="tab"
                                    aria-controls="subscription-tab-pane" aria-selected="false">Subscription</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link px-5" id="tickets-tab" data-bs-toggle="tab"
                                    data-bs-target="#tickets-tab-pane" type="button" role="tab"
                                    aria-controls="tickets-tab-pane" aria-selected="false">Tickets</button>
                            </li>
                        </ul>

                        <div class="tab-content mt-3" id="myTabContent">
                            <div class="tab-pane fade show active" id="configuration-tab-pane" role="tabpanel"
                                aria-labelledby="configuration-tab" tabindex="0">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card p-3 mb-3">
                                            <h6 class="d-flex align-items-center gap-2">
                                                <div class="d-flex align-items-center justify-content-center"
                                                    style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                                    <i class="fa-regular fa-envelope"></i>
                                                </div>
                                                Email configurations
                                            </h6>

                                            <div class="d-flex align-items-center justify-content-between ">
                                                <span>Total Inboxes <br> 324</span>
                                                <span>Inboxes per domain <br> 324</span>
                                            </div>
                                            <hr>
                                            <div class="d-flex flex-column">
                                                <span class="opacity-50">Prefix Varients</span>
                                                <span>John Doe</span>
                                            </div>
                                            <div class="d-flex flex-column mt-3">
                                                <span class="opacity-50">Profile Picture URL</span>
                                                <span>https://google.com/file/d/sdj23132kajsd#lkjFKLQW</span>
                                            </div>
                                        </div>

                                        <div class="card p-3">
                                            <h6 class="d-flex align-items-center gap-2">
                                                <div class="d-flex align-items-center justify-content-center"
                                                    style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                                    <i class="fa-solid fa-cart-plus"></i>
                                                </div>
                                                Products: <span class="text-success">$972.00</span>
                                                <span>/Monthly</span>
                                            </h6>

                                            <div class="d-flex align-items-center gap-3 ">
                                                <div>
                                                    <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png"
                                                        width="30" alt="">
                                                </div>
                                                <div>
                                                    <span class="opacity-50">Officially Google Workspace Inboxes</span>
                                                    <br>
                                                    <span>324 x $3.00 <small>/monthly</small> </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="card p-3">
                                            <h6 class="d-flex align-items-center gap-2">
                                                <div class="d-flex align-items-center justify-content-center"
                                                    style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                                    <i class="fa-solid fa-earth-europe"></i>
                                                </div>
                                                Domains
                                            </h6>

                                            <div class="d-flex flex-column mb-3">
                                                <span class="opacity-50">Domain Forwarding Destination URL</span>
                                                <span>zlatin@example.com</span>
                                            </div>

                                            <div class="d-flex flex-column">
                                                <span class="opacity-50">Domains</span>
                                                <span>aitransform360.com</span>
                                                <span>aitransform360.com</span>
                                                <span>aitransform360.com</span>
                                                <span>aitransform360.com</span>
                                                <span>aitransform360.com</span>
                                                <span>aitransform360.com</span>
                                                <span>aitransform360.com</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="email-tab-pane" role="tabpanel" aria-labelledby="email-tab"
                                tabindex="0">
                                <div class="col-12">
                                    <div class="card p-3">
                                        <h6 class="d-flex align-items-center gap-2">
                                            <div class="d-flex align-items-center justify-content-center"
                                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                                <i class="fa-solid fa-earth-europe"></i>
                                            </div>
                                            Emails
                                        </h6>

                                        <div class="table-responsive">
                                            <table id="myTable" class="display w-100">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Email</th>
                                                        <th>Password</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                                                style="border-radius: 50%" height="35" width="35"
                                                                class="object-fit-cover" alt="">
                                                            John Doe
                                                        </td>
                                                        <td><i class="ti ti-mail text-success"></i> Johndoe123@gmail.com
                                                        </td>
                                                        <td>
                                                            kjahsd876213
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="subscription-tab-pane" role="tabpanel"
                                aria-labelledby="subscription-tab" tabindex="0">
                                <div class="card p-3">
                                    <div class="d-flex align-items-center justify-content-between ">
                                        <h6 class="d-flex align-items-center gap-2">
                                            <div class="d-flex align-items-center justify-content-center"
                                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                                <i class="fa-solid fa-cart-plus"></i>
                                            </div>
                                            Subscriptions
                                        </h6>
                                        <button
                                            class="py-1 px-2 text-success rounded-2 border border-success bg-transparent">Acitve</button>
                                    </div>

                                    <span>Next Billing</span>

                                    <div>
                                        <span class="theme-text">Price</span>
                                        <h6>$929 <span class="opacity-50">/monthly</span></h6>
                                    </div>

                                    <div>
                                        <span class="theme-text">Date</span>
                                        <h6>12 May, 2025</h6>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button data-bs-toggle="modal" data-bs-target="#cancel_subscription"
                                            class="py-1 px-2 text-danger rounded-2 border border-danger bg-transparent">Cancel
                                            Subscription</button>
                                    </div>
                                </div>

                                <div class="card p-3 mt-3">
                                    <h6 class="d-flex align-items-center gap-2">
                                        <div class="d-flex align-items-center justify-content-center"
                                            style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                            <i class="fa-solid fa-file-invoice"></i>
                                        </div>
                                        Invoices
                                    </h6>

                                    <div class="table-responsive">
                                        <table id="myTable" class="display w-100">
                                            <thead>
                                                <tr>
                                                    <th>Tags</th>
                                                    <th>Date</th>
                                                    <th>Due Date</th>
                                                    <th>Invoice #</th>
                                                    <th>Paid At</th>
                                                    <th>Subscription</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        Add Tags
                                                    </td>
                                                    <td>
                                                        12 Apr, 2025
                                                    </td>
                                                    <td>
                                                        517623
                                                    </td>
                                                    <td>$123</td>
                                                    <td>12 Apr, 2025</td>
                                                    <td>19283kjahdasd9823</td>
                                                    <td><span
                                                            class="py-1 px-2 text-success border border-success rounded-2 bg-transparent">paid</span>
                                                    </td>
                                                    <td>
                                                        <div class="dropdown">
                                                            <button class="bg-transparent border-0" type="button"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <li><a class="dropdown-item" href="#">Action</a></li>
                                                                <li><a class="dropdown-item" href="#">Another action</a>
                                                                </li>
                                                                <li><a class="dropdown-item" href="#">Something else
                                                                        here</a></li>
                                                            </ul>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="tickets-tab-pane" role="tabpanel"
                                aria-labelledby="tickets-tab" tabindex="0">
                                <div class="card p-3">
                                    <div class="table-responsive">
                                        <table id="myTable" class="display w-100">
                                            <thead>
                                                <tr>
                                                    <th>Tags</th>
                                                    <th>Ticket #</th>
                                                    <th>Created At</th>
                                                    <th>Status</th>
                                                    <th>Type</th>
                                                    <th>Order #</th>
                                                    <th>Subscription #</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>

                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Modal -->
                        <div class="modal fade" id="cancel_subscription" tabindex="-1"
                            aria-labelledby="cancel_subscriptionLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header border-0">
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <h6 class="d-flex flex-column align-items-center justify-content-start gap-2">
                                            <div class="d-flex align-items-center justify-content-center"
                                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                                <i class="fa-solid fa-cart-plus"></i>
                                            </div>
                                            Subscriptions
                                        </h6>

                                        <p class="note">
                                            We are sad to to hear you're cancelling. Would you mind sharing the reason
                                            for the cancelation? We strive to always improve and would appreciate your
                                            feedback.
                                        </p>

                                        <div class="mb-3">
                                            <label for="backup-codes">Reason *</label>
                                            <textarea id="backup-codes" class="form-control" rows="8"></textarea>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="" id="">
                                            <label class="form-check-label" for="">
                                                I would like to have these email accounts removed and the domains
                                                released immediately. I will not be using these inboxes any longer.
                                            </label>
                                        </div>
                                    </div>
                                    <div
                                        class="modal-footer border-0 d-flex align-items-center justify-content-between flex-nowrap">
                                        <button type="button"
                                            class="border boder-white text-white py-1 px-3 w-100 bg-transparent rounded-2"
                                            data-bs-dismiss="modal">No, i am not</button>
                                        <button type="button"
                                            class="border border-danger py-1 px-3 w-100 bg-transparent text-danger rounded-2">Yes,
                                            I'm sure</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

@endsection

@push('scripts')

@endpush