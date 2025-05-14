@extends('customer.layouts.app')

@section('title', 'Invoices')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />
@endpush

@section('content')
<section class="py-3">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2">Total Invoices</h6>
                            <h3 class="card-title mb-0" id="totalInvoices">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2">Paid Invoices</h6>
                            <h3 class="card-title mb-0 text-success" id="paidInvoices">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2">Pending Invoices</h6>
                            <h3 class="card-title mb-0 text-warning" id="pendingInvoices">0</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2">Failed Invoices</h6>
                            <h3 class="card-title mb-0 text-danger" id="failedInvoices">0</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
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
        
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">My Invoices</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="invoicesTable" class="display w-100">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Order ID #</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th>Price</th>
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
</section>
@endsection

@push('scripts')
<!-- <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script> -->
<script>
    $(document).ready(function() {
        var table = $('#invoicesTable').DataTable({
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
                error: function(xhr, error, thrown) {
                    console.error('Error:', error);
                    toastr.error('Error loading invoice data');
                },
                data: function(d) {
                    d.status = $('#statusFilter').val();
                    d.startDate = $('#startDate').val();
                    d.endDate = $('#endDate').val();
                    d.priceRange = $('#priceRange').val();
                    d.orderId = $('#orderIdFilter').val();
                    d.orderStatus = $('#orderStatusFilter').val();
                    return d;
                }
            },
            columns: [
                { data: 'chargebee_invoice_id', name: 'chargebee_invoice_id' },
                { data: 'order_id', name: 'order_id' },
                { data: 'created_at', name: 'created_at' },
                { data: 'created_at', name: 'created_at' },
                { data: 'amount', name: 'amount' },
                { data: 'paid_at', name: 'paid_at' },
                { data: 'chargebee_subscription_id', name: 'chargebee_subscription_id' },
                { data: 'status', name: 'status' },
                { data: 'status_manage_by_admin', name: 'status_manage_by_admin' },
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

        // Apply filters when clicking the Filter button
        $('#applyFilters').on('click', function() {
            table.draw();
        });

        // Clear filters when clicking the Clear button
        $('#clearFilters').on('click', function() {
            $('#statusFilter').val('');
            $('#startDate').val('');
            $('#endDate').val('');
            $('#priceRange').val('');
            $('#orderIdFilter').val('');
            $('#orderStatusFilter').val('');
            table.draw();
        });
    });

    function downloadInvoice(invoiceId) {
        window.location.href = `/customer/invoices/${invoiceId}/download`;
    }

    function viewInvoice(invoiceId) {
        window.location.href = `/customer/invoices/${invoiceId}`;
    }
</script>
@endpush