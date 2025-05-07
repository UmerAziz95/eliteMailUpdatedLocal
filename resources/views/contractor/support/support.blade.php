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
                                font-size: 18px
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
            <div class="col-xl-3 col-lg-6 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <p class="fw-normal mb-0">Total Assigned</p>
                        </div>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="role-heading">
                                <h1 class="mb-0 text-primary">{{ $totalTickets }}</h1>
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
                            <p class="fw-normal mb-0">Pending</p>
                        </div>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="role-heading">
                                <h1 class="mb-0 text-warning">{{ $pendingTickets }}</h1>
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
                                <h1 class="mb-0 text-info">{{ $inProgressTickets }}</h1>
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
                            <p class="fw-normal mb-0">Completed</p>
                        </div>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="role-heading">
                                <h1 class="mb-0 text-success">{{ $completedTickets }}</h1>
                            </div>
                            <div class="bg-label-success rounded-1 px-1">
                                <i class="ti ti-ticket fs-2 text-success"></i>
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
                    @if($unassignedTickets->count() > 0)
                        <span class="badge bg-info unassigned-badge">{{ $unassignedTickets->count() }} Unassigned</span>
                    @endif
                </div>
                <div>
                    <select id="statusFilter" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="open">Open</option>
                        <option value="in_progress">In Progress</option>
                        <option value="closed">Closed</option>
                    </select>
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
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('contractor.support.tickets') }}",
                    data: function(d) {
                        d.status = $('#statusFilter').val();
                    }
                },
                columns: [
                    { data: 'ticket_number', name: 'ticket_number' },
                    { data: 'user.name', name: 'user.name' },
                    { data: 'subject', name: 'subject' },
                    { data: 'category', name: 'category' },
                    { data: 'priority', name: 'priority' },
                    { data: 'status', name: 'status' },
                    { data: 'created_at', name: 'created_at' },
                    { data: 'action', name: 'action', orderable: false, searchable: false }
                ],
                order: [[6, 'desc']] // Sort by created_at by default
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
