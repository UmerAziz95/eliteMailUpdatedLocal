@extends('customer.layouts.app')

@section('content')
<div class="">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-white mb-0">My Trial Orders</h2>
                <a href="{{ route('customer.dashboard') }}" class="btn btn-outline-primary">
                    <i class="ti ti-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="poolOrdersTable" class="display table table-hover mb-0 w-100">
                            <thead class="">
                                <tr>
                                    <th>Order ID</th>
                                    <th>Pool Plan</th>
                                    <th>Qty</th>
                                    <th>Domains</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Order Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    $('#poolOrdersTable').DataTable({
        responsive: {
            details: {
                display: $.fn.dataTable.Responsive.display.modal({
                    header: function(row) {
                        return 'Pool Order Details';
                    }
                }),
                renderer: $.fn.dataTable.Responsive.renderer.tableAll()
            }
        },
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route('customer.pool-orders.data') }}',
            type: "GET",
            error: function(xhr, error, thrown) {
                console.error('Error:', error);
                if (typeof toastr !== 'undefined') {
                    toastr.error('Error loading pool orders data');
                }
            }
        },
        columns: [
            { 
                data: 'id', 
                name: 'id', 
                render: function(data, type, row) {
                    return '<strong class="text-primary">#'+data+'</strong><br><small class="text-muted">'+row.chargebee_subscription_id+'</small>';
                }
            },
            { data: 'pool_plan', name: 'poolPlan.name' },
            { 
                data: 'quantity', 
                name: 'quantity', 
                className: 'text-center', 
                render: function(data) {
                    return '<span class="badge bg-info">'+data+'</span>';
                }
            },
            { data: 'domains', name: 'domains', className: 'text-center', orderable: false, searchable: false },
            { data: 'amount', name: 'amount' },
            { data: 'status', name: 'status', orderable: false, searchable: false },
            { data: 'order_date', name: 'created_at', orderable: false, searchable: false },
            { data: 'actions', name: 'actions', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
    });
});

/**
 * Cancel pool subscription
 */
function cancelPoolSubscription(orderId) {
    Swal.fire({
        title: 'Cancel Subscription?',
        text: "This will immediately cancel your pool subscription and free up the domains. This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, cancel it!',
        cancelButtonText: 'No, keep it',
        input: 'textarea',
        inputPlaceholder: 'Optional: Tell us why you\'re cancelling...',
        inputAttributes: {
            'aria-label': 'Cancellation reason'
        },
        showLoaderOnConfirm: true,
        preConfirm: (reason) => {
            return $.ajax({
                url: '{{ route('customer.pool-orders.cancel', ':id') }}'.replace(':id', orderId),
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    reason: reason || 'Customer requested cancellation'
                },
                dataType: 'json'
            }).fail(function(xhr) {
                let message = 'Failed to cancel subscription.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.showValidationMessage(message);
            });
        },
        allowOutsideClick: () => !Swal.isLoading()
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                title: 'Cancelled!',
                text: result.value.message || 'Your pool subscription has been cancelled successfully.',
                icon: 'success',
                timer: 3000
            });
            
            // Reload the DataTable
            $('#poolOrdersTable').DataTable().ajax.reload(null, false);
        }
    });
}
</script>
@endpush