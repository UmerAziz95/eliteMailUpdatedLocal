@extends('contractor.layouts.app')

@section('title', 'Pool Domains Management')

@push('styles')
<style>
    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-weight: 500;
    }

    .badge-pending { background-color: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid #ffc107; }
    .badge-in_progress { background-color: rgba(13, 202, 240, 0.2); color: #0dcaf0; border: 1px solid #0dcaf0; }
    .badge-completed { background-color: rgba(25, 135, 84, 0.2); color: #198754; border: 1px solid #198754; }
    .badge-cancelled { background-color: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid #dc3545; }

    .dataTables_wrapper .dataTables_filter {
        float: right;
        margin-bottom: 1rem;
    }

    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
        margin-left: 0.5rem;
    }

    .dataTables_wrapper .dataTables_length {
        float: left;
        margin-bottom: 1rem;
    }

    .dataTables_wrapper .dataTables_length select {
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem;
        padding: 0.375rem 0.75rem;
        margin: 0 0.5rem;
    }

    .dataTables_wrapper .dataTables_info {
        padding-top: 1rem;
        color: var(--bs-gray-600);
    }

    .dataTables_wrapper .dataTables_paginate {
        padding-top: 1rem;
        float: right;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.375rem 0.75rem;
        margin: 0 0.125rem;
        border: 1px solid var(--bs-border-color);
        border-radius: 0.375rem;
        color: var(--bs-body-color);
        text-decoration: none;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
        background-color: var(--bs-primary);
        color: white;
        border-color: var(--bs-primary);
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background-color: var(--bs-primary);
        color: white;
        border-color: var(--bs-primary);
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Trials Order Management</h2>
            <p class="mb-0">Manage trials orders and domains</p>
        </div>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-pills mb-3 border-0" id="poolDomainsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white" 
                    id="pool-orders-tab" data-bs-toggle="tab" data-bs-target="#pool-orders-tab-pane" type="button" 
                    role="tab" aria-controls="pool-orders-tab-pane" aria-selected="false">
                <i class="fa fa-user-check me-1"></i>My Trial Orders
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white" 
                    id="all-pool-orders-tab" data-bs-toggle="tab" data-bs-target="#all-pool-orders-tab-pane" type="button" 
                    role="tab" aria-controls="all-pool-orders-tab-pane" aria-selected="false">
                <i class="fa fa-list me-1"></i>All Trial Orders
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white active" 
                    id="all-domains-tab" data-bs-toggle="tab" data-bs-target="#all-domains-tab-pane" type="button" 
                    role="tab" aria-controls="all-domains-tab-pane" aria-selected="true">
                <i class="fa fa-globe me-1"></i>Trial All Domains
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white" 
                    id="in-queue-tab" data-bs-toggle="tab" data-bs-target="#in-queue-tab-pane" type="button" 
                    role="tab" aria-controls="in-queue-tab-pane" aria-selected="false">
                <i class="fa fa-clock me-1"></i>In-Queue Orders (Unassigned)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="poolDomainsTabContent">
        <!-- My Pool Orders Tab -->
        <div class="tab-pane fade" id="pool-orders-tab-pane" role="tabpanel" aria-labelledby="pool-orders-tab" tabindex="0">
            <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                <div class="table-responsive">
                    <table id="pool-orders-table" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Assigned At</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- All Pool Orders Tab -->
        <div class="tab-pane fade" id="all-pool-orders-tab-pane" role="tabpanel" aria-labelledby="all-pool-orders-tab" tabindex="0">
            <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                <div class="table-responsive">
                    <table id="all-pool-orders-table" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Assigned At</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pool All Domains Tab -->
        <div class="tab-pane fade show active" id="all-domains-tab-pane" role="tabpanel" aria-labelledby="all-domains-tab" tabindex="0">
            <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                <div class="table-responsive">
                    <table id="pool-domains-table" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Domain ID</th>
                                <th>Pool ID</th>
                                <th>Trial Order ID</th>
                                <th>Domain Name</th>
                                <th>Status</th>
                                <th>Usage</th>
                                <th>Order Status</th>
                                <th>Per Inbox</th>
                                <th>Prefixes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- In-Queue Pool Orders (Unassigned) Tab -->
        <div class="tab-pane fade" id="in-queue-tab-pane" role="tabpanel" aria-labelledby="in-queue-tab" tabindex="0">
            <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                <div class="table-responsive">
                    <table id="in-queue-orders-table" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Edit Domain Modal -->
<div class="modal fade" id="editDomainModal" tabindex="-1" aria-labelledby="editDomainModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDomainModalLabel">Edit Domain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editDomainForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_pool_id" name="pool_id">
                    <input type="hidden" id="edit_pool_order_id" name="pool_order_id">
                    <input type="hidden" id="edit_domain_id" name="domain_id">
                    
                    <div class="mb-3">
                        <label for="edit_domain_name" class="form-label">Domain Name</label>
                        <input type="text" class="form-control" id="edit_domain_name" name="domain_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <select class="form-select" id="edit_status" name="status" required>
                            <option value="warming">Warming</option>
                            <option value="available">Available</option>
                            <option value="subscribed">Subscribed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Domain</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Pool Order Status Modal -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeStatusModalLabel">Change Pool Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="changeStatusForm">
                <div class="modal-body">
                    <input type="hidden" id="change_status_order_id" name="order_id">
                    
                    <div class="mb-3">
                        <label for="new_status" class="form-label">Select New Status</label>
                        <select class="form-select" id="new_status" name="status" required>
                            <!-- <option value="pending">Pending</option> -->
                            <option value="in-progress">In Progress</option>
                            <!-- <option value="completed">Completed</option> -->
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info small">
                        <i class="fa fa-info-circle me-1"></i>
                        Note: Once an order is cancelled, its status cannot be changed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Pool All Domains DataTable
    var poolDomainsTable = $('#pool-domains-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('contractor.pool-domains.index') }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'customer_email', name: 'customer_email' },
            { data: 'domain_id', name: 'domain_id', visible: false },
            { data: 'pool_id', name: 'pool_id' },
            { data: 'pool_order_id', name: 'pool_order_id' },
            { data: 'domain_name', name: 'domain_name' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'usage_badge', name: 'is_used', orderable: false, visible: false },
            { data: 'pool_order_status_badge', name: 'pool_order_status', orderable: false, visible: false },
            { data: 'per_inbox', name: 'per_inbox', visible: false },
            { data: 'prefixes_formatted', name: 'prefixes', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[1, 'asc']],
        pageLength: 25,
        responsive: true,
        dom: 'Bfrtip'
    });

    // Pool Orders DataTable
    var poolOrdersTable = $('#pool-orders-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('contractor.pool-orders.list') }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'order_id', name: 'order_id' },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'customer_email', name: 'customer_email' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'assigned_to_name', name: 'assigned_to', orderable: false },
            { data: 'assigned_at_formatted', name: 'assigned_at' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: 'Bfrtip'
    });

    // All Pool Orders DataTable
    var allPoolOrdersTable = $('#all-pool-orders-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('contractor.pool-orders.all') }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'order_id', name: 'order_id' },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'customer_email', name: 'customer_email' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'assigned_to_name', name: 'assigned_to', orderable: false },
            { data: 'assigned_at_formatted', name: 'assigned_at' },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: 'Bfrtip'
    });

    // In-Queue Pool Orders Not Assigned DataTable
    var inQueueOrdersTable = $('#in-queue-orders-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('contractor.pool-orders.in-queue') }}",
            type: 'GET'
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'order_id', name: 'order_id' },
            { data: 'customer_name', name: 'customer_name' },
            { data: 'customer_email', name: 'customer_email' },
            { data: 'status_badge', name: 'status', orderable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[5, 'desc']],
        pageLength: 25,
        responsive: true,
        dom: 'Bfrtip'
    });

    // Reload DataTable when tab is shown
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        var targetTab = $(e.target).attr('data-bs-target');
        if (targetTab === '#pool-orders-tab-pane') {
            poolOrdersTable.ajax.reload();
        } else if (targetTab === '#all-pool-orders-tab-pane') {
            allPoolOrdersTable.ajax.reload();
        } else if (targetTab === '#all-domains-tab-pane') {
            poolDomainsTable.ajax.reload();
        } else if (targetTab === '#in-queue-tab-pane') {
            inQueueOrdersTable.ajax.reload();
        }
    });
});

// View domains for a pool order
function viewPoolOrderDomains(orderId) {
    // Switch to the "Pool All Domains" tab and filter by order ID
    var allDomainsTab = new bootstrap.Tab(document.getElementById('all-domains-tab'));
    allDomainsTab.show();
    
    // Apply search filter after tab is shown
    setTimeout(function() {
        $('#pool-domains-table').DataTable().search(orderId).draw();
    }, 100);
}

// Cancel pool order
function cancelPoolOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this pool order?')) {
        return;
    }
    
    $.ajax({
        url: '{{ route("contractor.pool-orders.cancel") }}',
        type: 'POST',
        data: {
            order_id: orderId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            toastr.success(response.message || 'Pool order cancelled successfully');
            $('#pool-orders-table').DataTable().ajax.reload();
            $('#all-pool-orders-table').DataTable().ajax.reload();
            $('#in-queue-orders-table').DataTable().ajax.reload();
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Error cancelling pool order';
            toastr.error(errorMsg);
        }
    });
}

// Edit domain function
function editDomain(poolId, poolOrderId, domainId, domainName, status) {
    $('#edit_pool_id').val(poolId);
    $('#edit_pool_order_id').val(poolOrderId);
    $('#edit_domain_id').val(domainId);
    $('#edit_domain_name').val(domainName);
    $('#edit_status').val(status);
    $('#editDomainModal').modal('show');
}

// Handle form submission
$('#editDomainForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        pool_id: $('#edit_pool_id').val(),
        pool_order_id: $('#edit_pool_order_id').val(),
        domain_id: $('#edit_domain_id').val(),
        domain_name: $('#edit_domain_name').val(),
        status: $('#edit_status').val(),
        _token: '{{ csrf_token() }}'
    };
    
    $.ajax({
        url: '{{ route("contractor.pool-domains.update") }}',
        type: 'POST',
        data: formData,
        success: function(response) {
            $('#editDomainModal').modal('hide');
            toastr.success(response.message || 'Domain updated successfully');
            $('#pool-domains-table').DataTable().ajax.reload();
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Error updating domain';
            toastr.error(errorMsg);
        }
    });
});

// Assign pool order to current contractor user
function assignToMe(orderId) {
    Swal.fire({
        title: 'Assign Order to You?',
        text: 'This pool order will be assigned to you.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, assign it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loader
            Swal.fire({
                title: 'Assigning Order...',
                html: 'Please wait while we assign this order to you.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route("contractor.pool-orders.assign-to-me") }}',
                type: 'POST',
                data: {
                    order_id: orderId,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Pool order assigned to you successfully',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Reload all relevant tables
                    $('#in-queue-orders-table').DataTable().ajax.reload();
                    $('#pool-orders-table').DataTable().ajax.reload();
                    $('#all-pool-orders-table').DataTable().ajax.reload();
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Error assigning pool order';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: errorMsg
                    });
                }
            });
        }
    });
}

// Change pool order status
function changePoolOrderStatus(orderId, currentStatus) {
    $('#change_status_order_id').val(orderId);
    $('#new_status').val(currentStatus);
    $('#changeStatusModal').modal('show');
}

// Handle status change form submission
$('#changeStatusForm').on('submit', function(e) {
    e.preventDefault();
    
    const orderId = $('#change_status_order_id').val();
    const newStatus = $('#new_status').val();
    
    console.log('Changing status for order:', orderId, 'to:', newStatus);
    
    // Show loader
    Swal.fire({
        title: 'Updating Status...',
        html: 'Please wait while we update the order status.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: '{{ route("contractor.pool-orders.change-status") }}',
        type: 'POST',
        data: {
            order_id: orderId,
            status: newStatus,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            console.log('Success response:', response);
            $('#changeStatusModal').modal('hide');
            
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: response.message || 'Pool order status updated successfully',
                timer: 2000,
                showConfirmButton: false
            });
            
            // Reload all relevant tables
            $('#pool-orders-table').DataTable().ajax.reload();
            $('#all-pool-orders-table').DataTable().ajax.reload();
            $('#in-queue-orders-table').DataTable().ajax.reload();
        },
        error: function(xhr) {
            console.error('Error response:', xhr);
            console.error('Status:', xhr.status);
            console.error('Response:', xhr.responseJSON);
            
            $('#changeStatusModal').modal('hide');
            
            let errorMsg = 'Error updating pool order status';
            
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.responseText) {
                errorMsg = xhr.responseText;
            }
            
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: errorMsg,
                confirmButtonText: 'OK'
            });
        }
    });
});
</script>
@endpush
