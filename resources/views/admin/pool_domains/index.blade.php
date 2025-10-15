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
                        </tr>
                    </thead>
                </table>
            </div>
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
            { data: 'prefixes_formatted', name: 'prefixes', orderable: false, searchable: false }
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
</script>
@endpush
