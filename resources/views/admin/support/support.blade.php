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

    <div class="row mb-4">
        <div class="counters col-lg-6">
            <div class="card p-2 counter_1">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Tickets</h6>
                            <div class="d-flex align-items-center my-1">
                                <h1 class="mb-0" id="totalTicketsCount">{{ $totalTickets }}</h1>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-user-search"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/8112/8112582.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-2 counter_2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Open Tickets</h6>
                            <div class="d-flex align-items-center my-1">
                                <h1 class="mb-0" id="pendingTicketsCount">{{ $pendingTickets }}</h1>
                                <p class="text-danger mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/8701/8701351.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-2 counter_2">
                <div class="card-body">
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">In-Progress</h6>
                            <div class="d-flex align-items-center my-1">
                                <h1 class="mb-0 " id="inProgressTicketsCount">{{ $inProgressTickets }}</h1>
                                <p class=" mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/17122/17122416.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-2 counter_1">
                <div class="card-body">
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Closed Tickets</h6>
                            <div class="d-flex align-items-center my-1">
                                <h1 class="mb-0" id="completedTicketsCount">{{ $completedTickets }}</h1>
                                <p class=" mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            <img src="https://cdn-icons-gif.flaticon.com/10352/10352695.gif" width="50"
                                style="border-radius: 50px" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card p-4 h-100 filter">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-2 text-white">Filters</h5>
                </div>

                <div class="d-flex align-items-start gap-4">
                    <div class="row gy-3">
                        <div class="col-md-4">
                            {{-- <label for="ticketNumberFilter" class="form-label">Ticket #</label> --}}
                            <input type="text" id="ticketNumberFilter" class="form-control"
                                placeholder="Search by ticket number">
                        </div>
                        <div class="col-md-4">
                            {{-- <label for="subjectFilter" class="form-label">Subject</label> --}}
                            <input type="text" id="subjectFilter" class="form-control" placeholder="Search by subject">
                        </div>
                        <div class="col-md-4">
                            {{-- <label for="customerFilter" class="form-label">Customer</label> --}}
                            <input type="text" id="customerFilter" class="form-control"
                                placeholder="Search by customer name">
                        </div>
                        <div class="col-md-4">
                            {{-- <label for="categoryFilter" class="form-label">Category</label> --}}
                            <select id="categoryFilter" class="form-select">
                                <option value="">All Categories</option>
                                <option value="technical">Technical Issue</option>
                                <option value="billing">Billing Issue</option>
                                <option value="account">Account Issue</option>
                                <option value="order">Order Issue</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            {{-- <label for="priorityFilter" class="form-label">Priority</label> --}}
                            <select id="priorityFilter" class="form-select">
                                <option value="">All Priorities</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            {{-- <label for="statusFilter" class="form-label">Status</label> --}}
                            <select id="statusFilter" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="open">Open</option>
                                <option value="in_progress">In Progress</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            {{-- <label for="startDate" class="form-label">Start Date</label> --}}
                            <input type="date" id="startDate" class="form-control">
                        </div>
                        <div class="col-md-4">
                            {{-- <label for="endDate" class="form-label">End Date</label> --}}
                            <input type="date" id="endDate" class="form-control">
                        </div>
                        <div class="d-flex align-items-center justify-content-end">
                            <button id="applyFilters" class="btn btn-primary btn-sm me-2">Filter</button>
                            <button id="clearFilters" class="btn btn-secondary btn-sm">Clear</button>
                        </div>
                    </div>

                    <img src="https://cdn-icons-gif.flaticon.com/19009/19009016.gif" width="30%"
                        style="border-radius: 50%" alt="">
                </div>
            </div>
        </div>
    </div>

    <div class="card p-3">
        <div>
            <table id="ticketsTable">
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
            responsive: {
                details: {
                    display: $.fn.dataTable.Responsive.display.modal({
                        header: function(row) {
                            return 'Ticket Details';
                        }
                    }),
                    renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                }
            },
            processing: true,
            serverSide: true,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
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