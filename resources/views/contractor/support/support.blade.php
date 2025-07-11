@extends('contractor.layouts.app')

@section('title', 'Support Tickets')

@push('styles')
<style>
    .avatar-group .avatar:hover {
        z-index: 30;
        transition: all .25s ease;
    }

    .pull-up:hover {
        z-index: 30;
        border-radius: 50%;
        box-shadow: var(--box-shadow);
        transform: translateY(-4px) scale(1.02);
    }

    .avatar {
        --bs-avatar-size: 2.5rem;
        position: relative;
        width: var(--bs-avatar-size);
        height: var(--bs-avatar-size);
        cursor: pointer;
    }

    .avatar-group .avatar {
        margin-inline-start: -0.8rem;
        transition: all .25s ease;
    }

    a {
        color: var(--second-primary);
        text-decoration: none;
        font-size: 14.5px
    }

    .avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 2px solid var(--secondary-color);
    }

    .avatar .avatar-initial {
        position: absolute;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: var(--primary-color);
        color: var(--bs-white);
        font-weight: 500;
        inset: 0;
        text-transform: uppercase;
        border-radius: 50%;
    }

    /* h5 {
                                font-size: 18px;
                            } */

    .form-check {
        padding-left: 0 !important
    }

    .form-check-input {
        background-color: transparent;
        border-radius: 2px !important;
        margin-top: .35rem
    }

    .form-check-input:checked {
        background-color: var(--second-primary);
    }


    .message {
        padding: 8px;
        margin: 5px 0;
        border-radius: 5px;
        width: fit-content;
    }

    .user-message {
        background: #d1e7fd;
        text-align: right;
        color: #000
    }

    .support-message {
        background: #f1f1f1;
        text-align: left;
        color: #000;
    }

    .input-container {
        display: flex;
        gap: 5px;
        margin-top: 10px;
    }

    input[type="text"] {
        flex: 1;
        padding: 8px;
    }

    .ticket-status {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.85rem;
    }

    .ticket-priority {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.85rem;
    }

    .unassigned-badge {
        position: relative;
        top: -2px;
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <div class="row mb-4">
        <div class="counters col-lg-12 mb-3">
            <div class="card p-3 counter_1">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Tickets</h6>
                            <div class="d-flex align-items-center my-1">
                                <h1 class="mb-0 fs-2" id="totalTicketsCount">0</h1>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-user-search"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/8112/8112582.gif" width="50"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-ticket fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Open Tickets</h6>
                            <div class="d-flex align-items-center my-1">
                                <h1 class="mb-0 fs-2" id="openTicketsCount">0</h1>
                                <p class="text-danger mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/8701/8701351.gif" width="50"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-envelope-open-text fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">In-Progress</h6>
                            <div class="d-flex align-items-center my-1">
                                <h1 class="mb-0 fs-2" id="inProgressTicketsCount">0</h1>
                                <p class=" mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/17122/17122416.gif" width="50"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-bars-progress fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_1">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Closed Tickets</h6>
                            <div class="d-flex align-items-center my-1">
                                <h1 class="mb-0 fs-2" id="closedTickets">0</h1>
                                <p class=" mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            {{-- <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-plus"></i>
                            </span> --}}
                            {{-- <img src="https://cdn-icons-gif.flaticon.com/10352/10352695.gif" width="50"
                                style="border-radius: 50px" alt=""> --}}
                            <i class="fa-solid fa-shop-slash fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="p-3 h-100 filter">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-2 text-white">Filters</h5>
                </div>

                <div class="d-flex align-items-start gap-4">
                    <div class="row gy-3">
                        <div class="col-md-3">
                            <label for="ticketNumberFilter" class="form-label">Ticket #</label>
                            <input type="text" id="ticketNumberFilter" class="form-control"
                                placeholder="Search by ticket number">
                        </div>
                        <div class="col-md-3">
                            <label for="subjectFilter" class="form-label">Subject</label>
                            <input type="text" id="subjectFilter" class="form-control" placeholder="Search by subject">
                        </div>
                        <div class="col-md-3">
                            <label for="customerFilter" class="form-label">Customer</label>
                            <input type="text" id="customerFilter" class="form-control"
                                placeholder="Search by customer name">
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
                            <button id="applyFilters" class="btn btn-primary btn-sm me-2 px-4 border-0">Filter</button>
                            <button id="clearFilters" class="btn btn-secondary btn-sm px-4">Clear</button>
                        </div>
                    </div>

                    {{-- <img src="https://cdn-icons-gif.flaticon.com/19009/19009016.gif" width="30%"
                        style="border-radius: 50%" alt=""> --}}
                </div>
            </div>
        </div>
    </div>
    {{-- <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fw-normal mb-0">Total Assigned</p>
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
                <div>
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
                <div>
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
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fw-normal mb-0">Closed</p>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="role-heading">
                            <h1 class="mb-0 text-danger" id="completedTicketsCount">{{ $completedTickets }}</h1>
                        </div>
                        <div class="bg-label-danger rounded-1 px-1">
                            <i class="ti ti-ticket fs-2 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card border-0 shadow-sm">
                <div>
                    <div class="row gy-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="mb-2">Filters</h5>
                            <div>
                                <button id="applyFilters" class="btn btn-primary btn-sm me-2">Filter</button>
                                <button id="clearFilters" class="btn btn-secondary btn-sm">Clear</button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label for="ticketNumberFilter" class="form-label">Ticket #</label>
                            <input type="text" id="ticketNumberFilter" class="form-control"
                                placeholder="Search by ticket number">
                        </div>
                        <div class="col-md-3">
                            <label for="subjectFilter" class="form-label">Subject</label>
                            <input type="text" id="subjectFilter" class="form-control" placeholder="Search by subject">
                        </div>
                        <div class="col-md-3">
                            <label for="categoryFilter" class="form-label">Category</label>
                            <select id="categoryFilter" class="form-select">
                                <option value="">All Categories</option>
                                <option value="order">Order Issue</option>
                                <option value="technical">Technical Issue</option>
                                <option value="billing">Billing Issue</option>
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
                    </div>
                </div>
            </div>
        </div>
    </div> --}}
    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-3">
                <h5 class="card-title mb-0">Support Tickets</h5>
                {{-- @if($unassignedTickets->count() > 0)
                <span class="badge bg-info unassigned-badge">{{ $unassignedTickets->count() }} Unassigned</span>
                @endif --}}
            </div>
        </div>

        <div class="table-responsive">
            <table id="ticketsTable" class=" w-100">
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

<!-- Status Update Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Ticket Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateStatusForm">
                    <input type="hidden" id="ticketId">
                    <div class="mb-3">
                        <label for="newStatus" class="form-label">Status</label>
                        <select class="form-select" id="newStatus" required>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="updateStatusBtn">Update Status</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        $(document).ready(function() {
            var table = $('#ticketsTable').DataTable({
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
                ajax: {
                    url: "{{ route('contractor.support.tickets') }}",
                    dataSrc: function(json) {
                        if (json.counters) {
                            $('#totalTicketsCount').text(json.counters.totalTickets);
                            $('#openTicketsCount').text(json.counters.openTickets);
                            $('#inProgressTicketsCount').text(json.counters.inProgressTickets);
                            $('#closedTicketsCount').text(json.counters.closedTickets);
                        }
                        return json.data;
                    },
                    data: function(d) {
                        d.ticket_number = $('#ticketNumberFilter').val();
                        d.subject = $('#subjectFilter').val();
                        d.category = $('#categoryFilter').val();
                        d.priority = $('#priorityFilter').val();
                        d.status = $('#statusFilter').val();
                        d.start_date = $('#startDate').val();
                        d.end_date = $('#endDate').val();
                    }
                },
                columns: [
                    { data: 'ticket_number', name: 'ticket_number' },
                    { 
                        data: 'user.name', name: 'user.name',
                        render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center gap-1">
                                <img src="https://cdn-icons-png.flaticon.com/128/2202/2202112.png" style="width: 35px" alt="">
                                <span>${data}</span>    
                            </div>
                        `;
                }
                    },
                    { data: 'subject', name: 'subject' },
                    { data: 'category', name: 'category' },
                    { data: 'priority', name: 'priority' },
                    { data: 'status', name: 'status' },
                    { 
                        data: 'created_at', name: 'created_at',
                        render: function(data, type, row) {
                        return `
                            <div class="d-flex align-items-center gap-1">
                                <i class="ti ti-calendar-month fs-5"></i>
                                <span class="text-nowrap">${data}</span>    
                            </div>
                        `;
                }
                    },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[6, 'desc']] // Sort by created_at by default
            });
            
            // Apply filters
            $('#applyFilters').on('click', function() {
                table.draw();
            });

            // Clear filters
            $('#clearFilters').on('click', function() {
                $('#ticketNumberFilter').val('');
                $('#subjectFilter').val('');
                $('#categoryFilter').val('');
                $('#priorityFilter').val('');
                $('#statusFilter').val('');
                $('#startDate').val('');
                $('#endDate').val('');
                table.draw();
            });

            // Handle status filter change
            $('#statusFilter').on('change', function() {
                table.ajax.reload();
            });

            // Handle status update modal
            let selectedTicketId = null;
            
            $(document).on('click', '.updateStatus', function(e) {
                e.preventDefault();
                selectedTicketId = $(this).data('id');
                $('#ticketId').val(selectedTicketId);
                $('#newStatus').val($(this).data('status'));
                $('#updateStatusModal').modal('show');
            });

            $('#updateStatusBtn').click(function() {
                const status = $('#newStatus').val();
                
                $.ajax({
                    url: `/contractor/support/tickets/${selectedTicketId}/status`,
                    method: 'PATCH',
                    data: {
                        status: status,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Ticket status updated successfully');
                            $('#updateStatusModal').modal('hide');
                            table.ajax.reload();
                        }
                    },
                    error: function() {
                        toastr.error('Failed to update ticket status');
                    }
                });
            });
        });

        function sendMessage() {
            const messageInput = document.getElementById("messageInput");
            const chatBox = document.getElementById("chatBox");
            const fileInput = document.getElementById("fileInput");

            if (messageInput.value.trim() !== "") {
                const userMessage = document.createElement("div");
                userMessage.classList.add("message", "user-message");
                userMessage.innerText = messageInput.value;
                chatBox.appendChild(userMessage);
            }

            if (fileInput.files.length > 0) {
                const fileMessage = document.createElement("div");
                fileMessage.classList.add("message", "user-message");
                fileMessage.innerHTML = `File uploaded: <strong>${fileInput.files[0].name}</strong>`;
                chatBox.appendChild(fileMessage);
            }

            messageInput.value = "";
            fileInput.value = "";
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function viewTicket(id) {
            window.location.href = `/contractor/support/tickets/${id}`;
        }
</script>
@endpush