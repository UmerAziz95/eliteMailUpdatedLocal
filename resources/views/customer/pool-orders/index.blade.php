@extends('customer.layouts.app')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-white mb-0">My Pool Orders</h2>
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
</script>
@endpush