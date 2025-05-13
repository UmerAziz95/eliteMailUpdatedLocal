@extends('admin.layouts.app')

@section('title', 'Support Tickets')

@push('styles')
<style>
    .unassigned-badge {
        font-size: 0.8rem;
        padding: 0.3rem 0.5rem;
        margin-left: 1rem;
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <div class="row gy-4 mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fw-normal mb-0">Total tickets</p>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="role-heading">
                            <h1 class="mb-0 text-primary" id="totalTicketsCount">{{ $totalTickets }}</h1>
                        </div>
                        <div class="bg-label-primary rounded-1 px-1">
                            <i class="ti ti-ticket fs-2 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fw-normal mb-0">Open</p>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="role-heading">
                            <h1 class="mb-0 text-warning" id="pendingTicketsCount">{{ $pendingTickets }}</h1>
                        </div>
                        <div class="bg-label-warning rounded-1 px-1">
                            <i class="ti ti-ticket fs-2 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fw-normal mb-0">In Progress</p>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="role-heading">
                            <h1 class="mb-0 text-info" id="inProgressTicketsCount">{{ $inProgressTickets }}</h1>
                        </div>
                        <div class="bg-label-info rounded-1 px-1">
                            <i class="ti ti-ticket fs-2 text-info"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fw-normal mb-0">Closed</p>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="role-heading">
                            <h1 class="mb-0 text-success" id="completedTicketsCount">{{ $completedTickets }}</h1>
                        </div>
                        <div class="bg-label-success rounded-1 px-1">
                            <i class="ti ti-ticket fs-2 text-success"></i>
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
                    <div class="d-flex align-items-center gap-2" data-bs-toggle="collapse" href="#collapseExample" role="button" aria-expanded="false" aria-controls="collapseExample">
                        <h5 class="mb-0">Filters</h5>
                        <img src="https://static.vecteezy.com/system/resources/previews/052/011/341/non_2x/3d-white-down-pointing-backhand-index-illustration-png.png" width="30" alt="">
                    </div>
                    <div class="collapse" id="collapseExample">
                        <div class="row gy-3">
                            <div class="col-md-3">
                                <label for="ticketNumberFilter" class="form-label">Ticket #</label>
                                <input type="text" id="ticketNumberFilter" class="form-control" placeholder="Search by ticket number">
                            </div>
                            <div class="col-md-3">
                                <label for="subjectFilter" class="form-label">Subject</label>
                                <input type="text" id="subjectFilter" class="form-control" placeholder="Search by subject">
                            </div>
                            <div class="col-md-3">
                                <label for="customerFilter" class="form-label">Customer</label>
                                <input type="text" id="customerFilter" class="form-control" placeholder="Search by customer name">
                            </div>
                            <div class="col-md-3">
                                <label for="categoryFilter" class="form-label">Category</label>
                                <select id="categoryFilter" class="form-select">
                                    <option value="">All Categories</option>
                                    <option value="technical">Technical Issue</option>
                                    <option value="billing">Billing Issue</option>
                                    <option value="account">Account Issue</option>
                                    <option value="order">Order Issue</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="priorityFilter" class="form-label">Priority</label>
                                <select id="priorityFilter" class="form-select">
                                    <option value="">All Priorities</option>
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="statusFilter" class="form-label">Status</label>
                                <select id="statusFilter" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="open">Open</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" id="startDate" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" id="endDate" class="form-control">
                            </div>
                            <div class="d-flex align-items-center justify-content-end">
                                <button id="applyFilters" class="btn btn-primary btn-sm me-2">Filter</button>
                                <button id="clearFilters" class="btn btn-secondary btn-sm">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-3">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <h5 class="card-title mb-0">Support Tickets</h5>
            </div>
        </div>

        <div class="table-responsive">
            <table id="ticketsTable" class="display w-100">
                <thead>
                    <tr>
                        <th>Ticket #</th>
                        <th>Customer</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const table = $('#ticketsTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('admin.support.tickets') }}",
                dataSrc: function(json) {
                    if (json.counters) {
                        $('#totalTicketsCount').text(json.counters.totalTickets);
                        $('#pendingTicketsCount').text(json.counters.pendingTickets);
                        $('#inProgressTicketsCount').text(json.counters.inProgressTickets);
                        $('#completedTicketsCount').text(json.counters.completedTickets);
                    }
                    return json.data;
                },
                data: function(d) {
                    d.ticket_number = $('#ticketNumberFilter').val();
                    d.subject = $('#subjectFilter').val();
                    d.customer = $('#customerFilter').val();
                    d.category = $('#categoryFilter').val();
                    d.priority = $('#priorityFilter').val();
                    d.status = $('#statusFilter').val();
                    d.start_date = $('#startDate').val();
                    d.end_date = $('#endDate').val();
                }
            },
            columns: [{
                    data: 'ticket_number',
                    name: 'ticket_number'
                },
                {
                    data: 'user.name',
                    name: 'user.name'
                },
                {
                    data: 'subject',
                    name: 'subject'
                },
                {
                    data: 'category',
                    name: 'category'
                },
                {
                    data: 'priority',
                    name: 'priority'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'created_at',
                    name: 'created_at'
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                [6, 'desc']
            ]
        });

        // Apply filters
        $('#applyFilters').on('click', function() {
            table.draw();
        });

        // Clear filters
        $('#clearFilters').on('click', function() {
            $('#ticketNumberFilter').val('');
            $('#subjectFilter').val('');
            $('#customerFilter').val('');
            $('#categoryFilter').val('');
            $('#priorityFilter').val('');
            $('#statusFilter').val('');
            $('#startDate').val('');
            $('#endDate').val('');
            table.draw();
        });

        // Handle status update
        $(document).on('click', '.updateStatus', function(e) {
            e.preventDefault();
            const ticketId = $(this).data('id');
            const status = $(this).data('status');

            $.ajax({
                url: `/admin/support/tickets/${ticketId}/status`,
                method: 'PATCH',
                data: {
                    status: status,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success('Ticket status updated successfully');
                        table.ajax.reload();
                    }
                },
                error: function() {
                    toastr.error('Failed to update ticket status');
                }
            });
        });
    });

    function viewTicket(id) {
        window.location.href = `/admin/support/tickets/${id}`;
    }
</script>
@endpush