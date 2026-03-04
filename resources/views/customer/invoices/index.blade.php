@extends('customer.layouts.app')

@section('title', 'Invoices')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />
@endpush

@section('content')
<section class="py-3" data-page="invoices">
    @include('customer.chatbot.chat')
    <div class="counters mb-4">
        <div class="card p-2 counter_1">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <h6 class="text-heading">Total Invoices</h6>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2 fs-4" id="totalInvoices">0</h4>
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

        <div class="card p-2 counter_2">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <h6 class="text-heading">Active Invoices</h6>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2 fs-4" id="paidInvoices">0</h4>
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

        <div class="card p-2 counter_1">
            <div class="card-body">
                <!-- {{-- //card body --}} -->
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <h6 class="text-heading">Inactive Invoices</h6>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2 fs-4" id="pendingInvoices">0</h4>
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

        <div class="card p-2 counter_2">
            <div class="card-body">
                <!-- {{-- //card body --}} -->
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <h6 class="text-heading">Failed Invoices</h6>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2 fs-4" id="failedInvoices">0</h4>
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

    <div class="row mb-4" style="display: none;">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-2">Filters</h5>
                            <div>
                                <button id="applyFilters" class="btn btn-primary btn-sm me-2">Filter</button>
                                <button id="clearFilters" class="btn btn-secondary btn-sm">Clear</button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="statusFilter" class="form-label">Invoice Status</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="paid">Paid</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="orderIdFilter" class="form-label">Order ID</label>
                            <input type="text" id="orderIdFilter" class="form-control" placeholder="Enter Order ID">
                        </div>
                        <div class="col-md-3">
                            <label for="orderStatusFilter" class="form-label">Order Status</label>
                            <select id="orderStatusFilter" class="form-select">
                                <option value="">All Order Statuses</option>
                                @foreach($statuses as $key => $status)
                                <option value="{{ $key }}">{{ ucfirst(str_replace('_', ' ', $key)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" id="startDate" class="form-control" placeholder="Start Date">
                        </div>
                        <div class="col-md-3">
                            <label for="endDate" class="form-label">End Date</label>
                            <input type="date" id="endDate" class="form-control" placeholder="End Date">
                        </div>
                        <div class="col-md-3">
                            <label for="priceRange" class="form-label">Price Range</label>
                            <select id="priceRange" class="form-select">
                                <option value="">All Prices</option>
                                <option value="0-100">$0 - $100</option>
                                <option value="101-500">$101 - $500</option>
                                <option value="501-1000">$501 - $1000</option>
                                <option value="1001+">$1000+</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <ul class="nav nav-pills mb-3" role="tablist">
                @if(in_array(auth()->user()->customer_access, ['full', 'normal']))
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="pills-normal-tab" data-bs-toggle="pill"
                        data-bs-target="#pills-normal" type="button" role="tab"
                        aria-controls="pills-normal" aria-selected="true">
                        <i class="fa-regular fa-file-lines me-1"></i>
                        Normal Invoices
                    </button>
                </li>
                @endif
                @if(auth()->user()->customer_access === 'trial')
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ in_array(auth()->user()->customer_access, ['full', 'normal']) ? '' : 'active' }}" id="pills-trial-tab" data-bs-toggle="pill"
                        data-bs-target="#pills-trial" type="button" role="tab"
                        aria-controls="pills-trial" aria-selected="{{ in_array(auth()->user()->customer_access, ['full', 'normal']) ? 'false' : 'true' }}" tabindex="{{ in_array(auth()->user()->customer_access, ['full', 'normal']) ? '-1' : '0' }}">
                        <i class="fa-solid fa-flask me-1"></i>
                        Trial Invoices
                    </button>
                </li>
                @endif
            </ul>
            <div class="tab-content" id="pills-tabContent">
                @if(in_array(auth()->user()->customer_access, ['full', 'normal']))
                <div class="tab-pane fade show active" id="pills-normal" role="tabpanel" aria-labelledby="pills-normal-tab" tabindex="0">
                    <div class="table-responsive">
                        <table id="normalInvoicesTable" class="w-100">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Order ID #</th>
                                    <th>Date</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                @endif
                @if(auth()->user()->customer_access === 'trial')
                <div class="tab-pane fade {{ in_array(auth()->user()->customer_access, ['full', 'normal']) ? '' : 'show active' }}" id="pills-trial" role="tabpanel" aria-labelledby="pills-trial-tab" tabindex="0">
                    <div class="table-responsive">
                        <table id="trialInvoicesTable" class="w-100">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Order ID #</th>
                                    <th>Date</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<!-- <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script> -->
<script>
    $(document).ready(function() {
        // Custom CSS for consistent styling
        $('head').append(`
            <style>
                #normalInvoicesTable_wrapper .dataTables_length,
                #normalInvoicesTable_wrapper .dataTables_filter,
                #normalInvoicesTable_wrapper .dataTables_info,
                #normalInvoicesTable_wrapper .dataTables_paginate,
                #trialInvoicesTable_wrapper .dataTables_length,
                #trialInvoicesTable_wrapper .dataTables_filter,
                #trialInvoicesTable_wrapper .dataTables_info,
                #trialInvoicesTable_wrapper .dataTables_paginate {
                    padding: 0.5rem 0;
                }
            </style>
        `);

        // Normal Invoices DataTable
        @if(in_array(auth()->user()->customer_access, ['full', 'normal']))
        var normalTable = $('#normalInvoicesTable').DataTable({
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function(row) {
                            return 'Invoice Details';
                        }
                    }),
                    renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                }
            },
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('customer.invoices.index') }}",
                type: "GET",
                data: function(d) {
                    d.invoice_type = 'normal';
                    d.status = $('#statusFilter').val();
                    d.startDate = $('#startDate').val();
                    d.endDate = $('#endDate').val();
                    d.priceRange = $('#priceRange').val();
                    d.orderId = $('#orderIdFilter').val();
                    d.orderStatus = $('#orderStatusFilter').val();
                    return d;
                },
                error: function(xhr, error, thrown) {
                    console.error('Error:', error);
                    toastr.error('Error loading invoice data');
                }
            },
            columns: [
                { data: 'chargebee_invoice_id', name: 'chargebee_invoice_id' },
                { data: 'order_id', name: 'order_id' },
                { 
                    data: 'created_at', name: 'created_at',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex gap-1 align-items-center opacity-50">
                                <i class="ti ti-calendar-month"></i>
                                <span>${data}</span>    
                            </div>
                        `;
                    }
                },
                { 
                    data: 'amount', name: 'amount',
                    render: function(data, type, row) {
                        return `<span class="text-warning">${data}</span>`;
                    } 
                },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[1, 'desc']],
            drawCallback: function(settings) {
                if(settings.json && settings.json.counters) {
                    $('#totalInvoices').text(settings.json.counters.total);
                    $('#paidInvoices').text(settings.json.counters.paid);
                    $('#pendingInvoices').text(settings.json.counters.pending);
                    $('#failedInvoices').text(settings.json.counters.failed);
                }
            }
        });
        @endif

        // Trial Invoices DataTable
        @if(auth()->user()->customer_access === 'trial')
        var trialTable = $('#trialInvoicesTable').DataTable({
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function(row) {
                            return 'Trial Invoice Details';
                        }
                    }),
                    renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                }
            },
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('customer.invoices.index') }}",
                type: "GET",
                data: function(d) {
                    d.invoice_type = 'trial';
                    d.status = $('#statusFilter').val();
                    d.startDate = $('#startDate').val();
                    d.endDate = $('#endDate').val();
                    d.priceRange = $('#priceRange').val();
                    d.orderId = $('#orderIdFilter').val();
                    d.orderStatus = $('#orderStatusFilter').val();
                    return d;
                },
                error: function(xhr, error, thrown) {
                    console.error('Error:', error);
                    toastr.error('Error loading trial invoice data');
                }
            },
            columns: [
                { data: 'id', name: 'id', title: 'Invoice #' },
                { data: 'order_id', name: 'order_id' },
                { 
                    data: 'created_at', name: 'created_at',
                    render: function(data, type, row) {
                        return `
                            <div class="d-flex gap-1 align-items-center opacity-50">
                                <i class="ti ti-calendar-month"></i>
                                <span>${data}</span>    
                            </div>
                        `;
                    }
                },
                { 
                    data: 'amount', name: 'amount',
                    render: function(data, type, row) {
                        return `<span class="text-warning">${data}</span>`;
                    } 
                },
                { data: 'status', name: 'status' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            order: [[1, 'desc']],
            drawCallback: function(settings) {
                if(settings.json && settings.json.counters) {
                    $('#totalInvoices').text(settings.json.counters.total);
                    $('#paidInvoices').text(settings.json.counters.paid);
                    $('#pendingInvoices').text(settings.json.counters.pending);
                    $('#failedInvoices').text(settings.json.counters.failed);
                }
            }
        });
        @endif

        // Apply filters when clicking the Filter button
        $('#applyFilters').on('click', function() {
            @if(in_array(auth()->user()->customer_access, ['full', 'normal']))
            normalTable.draw();
            @endif
            @if(auth()->user()->customer_access === 'trial')
            trialTable.draw();
            @endif
        });

        // Clear filters when clicking the Clear button
        $('#clearFilters').on('click', function() {
            $('#statusFilter').val('');
            $('#startDate').val('');
            $('#endDate').val('');
            $('#priceRange').val('');
            $('#orderIdFilter').val('');
            $('#orderStatusFilter').val('');
            @if(in_array(auth()->user()->customer_access, ['full', 'normal']))
            normalTable.draw();
            @endif
            @if(auth()->user()->customer_access === 'trial')
            trialTable.draw();
            @endif
        });
    });

    function downloadInvoice(invoiceId, isTrial = false) {
        if (isTrial) {
            window.location.href = `/customer/pool-invoices/${invoiceId}/download`;
        } else {
            window.location.href = `/customer/invoices/${invoiceId}/download`;
        }
    }

    function viewInvoice(invoiceId, isTrial = false) {
        if (isTrial) {
            window.location.href = `/customer/pool-invoices/${invoiceId}`;
        } else {
            window.location.href = `/customer/invoices/${invoiceId}`;
        }
    }
</script>
@endpush