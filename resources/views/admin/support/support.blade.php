@extends('admin.layouts.app')

@section('title', 'Support Tickets')

@push('styles')
<style>
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
                        <p class="fw-normal mb-0">Pending tickets</p>
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

    <!-- @include('modules.support.listing') -->


    <!-- Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body p-5 position-relative">
                    <button type="button" class="p-0 border-0 bg-transparent position-absolute"
                        style="top: 20px; right: 20px" data-bs-dismiss="modal" aria-label="Close"><i
                            class="fa-solid fa-xmark"></i></button>

                    <div class="chat-container overflow-y-auto" style="max-height: 70vh;">
                        <h3>Support Chat</h3>
                        <div class="chat-box" id="chatBox">
                            <div>
                                <div class="message user-message">Hi, I'm having an issue with my account.</div>
                            </div>
                            <div class="d-flex align-items-center justify-content-end">
                                <div class="message support-message">Hello! Can you please describe the issue?</div>
                            </div>
                        </div>
                        <div class="input-container position-sticky bottom-0"
                            style="background-color: var(--secondary-color)">
                            <input type="text" id="messageInput" placeholder="Type a message...">

                            <!-- Hidden File Input -->
                            <input type="file" id="fileInput" style="display: none;">

                            <!-- File Upload Icon -->
                            <label for="fileInput" class="file-icon">
                                <i class="fa-solid fa-paperclip"></i>
                            </label>

                            <button onclick="sendMessage()" class="m-btn py-2 px-4 rounded-2 border-0">Send</button>
                        </div>

                        <style>
                            .file-icon {
                                cursor: pointer;
                                font-size: 18px;
                                margin: 0 10px;
                            }
                        </style>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
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
            var table = $('#myTable').DataTable();

            $(".dt-search").append(
                '<button id="addNew" data-bs-target="#addRoleModal" data-bs-toggle="modal" class="m-btn rounded-1 border-0 ms-2" style="padding: .4rem 1.4rem"><i class="fa-solid fa-plus"></i> Support Department</button>'
            );
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

$(document).ready(function() {
    const table = $('#ticketsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.support.tickets') }}",
            data: function(d) {
                d.status = $('#statusFilter').val();
                return d;
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
        order: [[6, 'desc']]
    });

    // Handle status filter change
    $('#statusFilter').on('change', function() {
        table.ajax.reload();
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