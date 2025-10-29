@extends('admin.layouts.app')

@section('title', 'Invoices')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />
<style>
    /* Ensure consistent styling for both DataTables */
    #normalInvoicesTable_wrapper,
    #trialInvoicesTable_wrapper {
        width: 100%;
    }

    #normalInvoicesTable,
    #trialInvoicesTable {
        width: 100% !important;
        margin: 0 !important;
    }

    .dataTables_wrapper {
        padding: 0 !important;
    }

    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter,
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        padding: 0.5rem 0;
    }

    table.dataTable thead th,
    table.dataTable tbody td {
        padding: 0.75rem !important;
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <div class="row gy-3 mb-4">
        <div class="counters col-lg-6">
            <div class="card p-3 counter_1">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Invoices</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="totalInvoices">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-user-search"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/15579/15579005.gif" width="40"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-file-invoice fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Active Users</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="paidInvoices">0</h4>
                                <p class="text-danger mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/15575/15575685.gif" width="40"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-brands fa-creative-commons-sa fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Inactive Invoices</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="pendingInvoices">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/18998/18998293.gif" width="40"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-regular fa-circle-xmark fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_1">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Failed Invoices</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="failedInvoices">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/17905/17905386.gif" width="40"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-triangle-exclamation fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="p-3 h-100 filter">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-2 text-white">Filters</h5>
                </div>

                <div class="d-flex align-items-start gap-4">
                    <div class="row gy-3">

                        <div class="col-md-6 col-lg-4">
                            <label for="statusFilter" class="form-label mb-0">Invoice Status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="paid">Paid</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="orderIdFilter" class="form-label mb-0">Order ID</label>
                            <input type="text" id="orderIdFilter" class="form-control" placeholder="Enter Order ID">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="orderStatusFilter" class="form-label mb-0">Order Status</label>
                            <select id="orderStatusFilter" class="form-select">
                                <option value="" class="text-light">All Order Statuses</option>
                                @foreach($statuses as $key => $status)
                                <option value="{{ $key }}" class="text-{{$status}}">{{ ucfirst(str_replace('_', ' ',
                                    $key)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="startDate" class="form-label mb-0">Start Date</label>
                            <input type="date" id="startDate" class="form-control" placeholder="Start Date">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="endDate" class="form-label mb-0">End Date</label>
                            <input type="date" id="endDate" class="form-control" placeholder="End Date">
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <label for="priceRange" class="form-label mb-0">Price Range</label>
                            <select id="priceRange" class="form-select">
                                <option value="">All Prices</option>
                                <option value="0-100">$0 - $100</option>
                                <option value="101-500">$101 - $500</option>
                                <option value="501-1000">$501 - $1000</option>
                                <option value="1001+">$1000+</option>
                            </select>
                        </div>

                        <div class="d-flex align-items-center justify-content-end">
                            <button id="applyFilters" class="btn btn-primary btn-sm me-2 px-4 border-0">Filter</button>
                            <button id="clearFilters" class="btn btn-secondary btn-sm px-4">Clear</button>
                        </div>
                    </div>

                    {{-- <img src="https://cdn-icons-gif.flaticon.com/19009/19009016.gif" width="30%"
                        style="border-radius: 50%" class="d-none d-sm-block" alt=""> --}}
                </div>

            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-pills mb-3 border-0" id="invoicesTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white active" 
                    id="normal-tab" data-bs-toggle="tab" data-bs-target="#normal-tab-pane" type="button" 
                    role="tab" aria-controls="normal-tab-pane" aria-selected="true">
                <i class="fa fa-file-invoice me-1"></i>Normal Invoices
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white" 
                    id="trial-tab" data-bs-toggle="tab" data-bs-target="#trial-tab-pane" type="button" 
                    role="tab" aria-controls="trial-tab-pane" aria-selected="false">
                <i class="fa fa-flask me-1"></i>Trial Invoices
            </button>
        </li>
    </ul>

    <div class="tab-content" id="invoicesTabContent">
        <!-- Normal Invoices Tab -->
        <div class="tab-pane fade show active" id="normal-tab-pane" role="tabpanel" aria-labelledby="normal-tab" tabindex="0">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="normalInvoicesTable" class="table table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Order ID #</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                    <th>Price</th>
                                    <th>Customer Name</th>
                                    <th>Paid At</th>
                                    <th>Subscription ID</th>
                                    <th>Status</th>
                                    <th>Order Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Trial Invoices Tab -->
        <div class="tab-pane fade" id="trial-tab-pane" role="tabpanel" aria-labelledby="trial-tab" tabindex="0">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="trialInvoicesTable" class="table table-hover w-100">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Order ID #</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                    <th>Price</th>
                                    <th>Customer Name</th>
                                    <th>Paid At</th>
                                    <th>Subscription ID</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script>
    let normalInvoicesTable = null;
    let trialInvoicesTable = null;

    $(document).ready(function() {
        // Initialize Normal Invoices DataTable
        normalInvoicesTable = $('#normalInvoicesTable').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            ajax: {
                url: "{{ route('admin.invoices.index') }}",
                type: "GET",
                error: function(xhr, error, thrown) {
                    console.error('Error:', error);
                    toastr.error('Error loading invoice data');
                },
                data: function(d) {
                    d.invoice_type = 'normal';
                    d.status = $('#statusFilter').val();
                    d.startDate = $('#startDate').val();
                    d.endDate = $('#endDate').val();
                    d.priceRange = $('#priceRange').val();
                    d.orderId = $('#orderIdFilter').val();
                    d.orderStatus = $('#orderStatusFilter').val();
                    return d;
                }
            },
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'order_id',
                    name: 'order_id'
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center gap-1">
                                <i class="ti ti-calendar-month fs-6"></i>
                                <span class="text-nowrap">${data}</span>
                            </div>
                        `;
                    }
                },

                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center gap-1">
                                <i class="ti ti-calendar-month fs-6"></i>
                                <span class="text-nowrap">${data}</span>
                            </div>
                        `;
                    }
                },

                {
                    data: 'amount',
                    name: 'amount',
                    render: function(data, type, row) {
                        return `
                            <span class="text-warning">${data}</span>
                        `;
                    }
                },
                {
                    data: 'customer_name',
                    name: 'customer_name',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center gap-1">
                                <img src="https://cdn-icons-png.flaticon.com/128/2202/2202112.png" style="width: 25px" alt="">
                                <span>${data}</span>    
                            </div>
                        `;
                    }
                },
                {
                    data: 'paid_at',
                    name: 'paid_at',
                    render: function(data, type, row) {
                        return `
                            <span style="color: #00FF77">${data}</span>
                        `;
                    }
                },
                {
                    data: 'chargebee_subscription_id',
                    name: 'chargebee_subscription_id'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'status_manage_by_admin',
                    name: 'status_manage_by_admin'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [1, 'desc']
            ],
            drawCallback: function(settings) {
                if (settings.json && settings.json.counters) {
                    $('#totalInvoices').text(settings.json.counters.total);
                    $('#paidInvoices').text(settings.json.counters.paid);
                    $('#pendingInvoices').text(settings.json.counters.pending);
                    $('#failedInvoices').text(settings.json.counters.failed);
                }
            }
        });

        // Initialize Trial Invoices DataTable
        trialInvoicesTable = $('#trialInvoicesTable').DataTable({
            responsive: true,
            processing: true,
            serverSide: true,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            ajax: {
                url: "{{ route('admin.invoices.index') }}",
                type: "GET",
                error: function(xhr, error, thrown) {
                    console.error('Error:', error);
                    toastr.error('Error loading trial invoice data');
                },
                data: function(d) {
                    d.invoice_type = 'trial';
                    d.status = $('#statusFilter').val();
                    d.startDate = $('#startDate').val();
                    d.endDate = $('#endDate').val();
                    d.priceRange = $('#priceRange').val();
                    d.orderId = $('#orderIdFilter').val();
                    return d;
                }
            },
            columns: [{
                    data: 'id',
                    name: 'id'
                },
                {
                    data: 'pool_order_id',
                    name: 'pool_order_id'
                },
                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center gap-1">
                                <i class="ti ti-calendar-month fs-6"></i>
                                <span class="text-nowrap">${data}</span>
                            </div>
                        `;
                    }
                },

                {
                    data: 'created_at',
                    name: 'created_at',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center gap-1">
                                <i class="ti ti-calendar-month fs-6"></i>
                                <span class="text-nowrap">${data}</span>
                            </div>
                        `;
                    }
                },

                {
                    data: 'amount',
                    name: 'amount',
                    render: function(data, type, row) {
                        return `
                            <span class="text-warning">${data}</span>
                        `;
                    }
                },
                {
                    data: 'customer_name',
                    name: 'customer_name',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center gap-1">
                                <img src="https://cdn-icons-png.flaticon.com/128/2202/2202112.png" style="width: 25px" alt="">
                                <span>${data}</span>    
                            </div>
                        `;
                    }
                },
                {
                    data: 'paid_at',
                    name: 'paid_at',
                    render: function(data, type, row) {
                        return `
                            <span style="color: #00FF77">${data}</span>
                        `;
                    }
                },
                {
                    data: 'chargebee_invoice_id',
                    name: 'chargebee_invoice_id'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [1, 'desc']
            ],
            drawCallback: function(settings) {
                if (settings.json && settings.json.counters) {
                    $('#totalInvoices').text(settings.json.counters.total);
                    $('#paidInvoices').text(settings.json.counters.paid);
                    $('#pendingInvoices').text(settings.json.counters.pending);
                    $('#failedInvoices').text(settings.json.counters.failed);
                }
            }
        });

        // Apply filters when clicking the Filter button
        $('#applyFilters').on('click', function() {
            if (normalInvoicesTable) {
                normalInvoicesTable.draw();
            }
            if (trialInvoicesTable) {
                trialInvoicesTable.draw();
            }
        });

        // Clear invoice filters when clicking the Clear button 
        $('#clearFilters').on('click', function() {
            $('#statusFilter').val('');
            $('#startDate').val('');
            $('#endDate').val('');
            $('#priceRange').val('');
            $('#orderIdFilter').val('');
            $('#orderStatusFilter').val('');
            if (normalInvoicesTable) {
                normalInvoicesTable.draw();
            }
            if (trialInvoicesTable) {
                trialInvoicesTable.draw();
            }
        });
    });

    function downloadInvoice(invoiceId) {
        

        window.location.href = `/admin/invoices/${invoiceId}/download`;
    }

    function viewInvoice(invoiceId) {
        window.location.href = `/admin/invoices/${invoiceId}`;
    }
</script>
@endpush