@extends('admin.layouts.app')

@section('title', 'All Pool Domains')

@push('styles')

@endpush

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>All Pool Domains</h4>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered" id="pool-domains-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Domain ID</th>
                            <th>Pool ID</th>
                            <th>Pool Order ID</th>
                            <th>Domain Name</th>
                            <th>Status</th>
                            <th>Usage</th>
                            <th>Order Status</th>
                            <th>Per Inbox</th>
                            <th>Prefixes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#pool-domains-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.pool-domains.index') }}",
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
        dom: 'Bfrtip',
        // buttons: [
        //     'copy', 'csv', 'excel', 'pdf'
        // ],
        // language: {
        //     processing: "Loading pool domains..."
        // }
    });
});

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
        url: '{{ route("admin.pool-domains.update") }}',
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
</script>
@endpush