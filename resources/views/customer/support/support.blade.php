@extends('customer.layouts.app')

@section('title', 'Support')

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

    .file-upload-label {
        cursor: pointer;
        color: var(--second-primary);
    }
    
    .ticket-attachments {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }
    
    .attachment-preview {
        position: relative;
        width: 100px;
        height: 100px;
        border: 1px solid #ddd;
        border-radius: 4px;
        overflow: hidden;
    }
    
    .attachment-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .remove-attachment {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(0,0,0,0.5);
        border: none;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }
    .attachments-area {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }

    .attachment-preview {
        width: 100px;
        height: 100px;
        position: relative;
        border-radius: 5px;
        overflow: hidden;
        border: 1px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
    }

    .attachment-preview.document {
        font-size: 40px;
        color: #6c757d;
    }

    .attachment-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .remove-attachment {
        position: absolute;
        top: 5px;
        right: 5px;
        background: rgba(0, 0, 0, 0.5);
        color: white;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .internal-note {
        background-color: rgba(255, 193, 7, 0.1);
        border-left: 4px solid #ffc107;
        padding: 10px;
        margin-top: 10px;
    }

    .attachment-name {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,0.7);
        color: white;
        font-size: 10px;
        padding: 2px 5px;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
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
                        <p class="fw-normal mb-0">Completed tickets</p>
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

        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card h-100">
                <div class="card-body d-flex align-items-center justify-content-center">
                    <button class="m-btn py-2 px-4 rounded-2 border-0" data-bs-toggle="modal" data-bs-target="#createTicketModal">
                        <i class="fa-solid fa-plus me-2"></i>Create New Ticket
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card p-3">
        <div class="table-responsive">
            <table id="ticketsTable" class="display w-100">
                <thead>
                    <tr>
                        <th>Ticket #</th>
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

<!-- Create Ticket Modal -->
<div class="modal fade" id="createTicketModal" tabindex="-1" aria-labelledby="createTicketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createTicketModalLabel">Create New Support Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="createTicketForm">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="technical">Technical Issue</option>
                            <option value="billing">Billing Issue</option>
                            <option value="account">Account Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="priority" class="form-label">Priority</label>
                        <select class="form-select" id="priority" name="priority" required>
                            <option value="">Select Priority</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label d-block">Attachments</label>
                        <label for="attachments" class="file-upload-label">
                            <i class="fas fa-paperclip me-2"></i>Add Attachments
                        </label>
                        <input type="file" id="attachments" name="attachments[]" multiple style="display: none;">
                        <div class="ticket-attachments" id="attachmentPreviews"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="submitTicket">Create Ticket</button>
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
    // Initialize DataTable
    const table = $('#ticketsTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('customer.support.tickets') }}",
        columns: [
            { data: 'ticket_number', name: 'ticket_number' },
            { data: 'subject', name: 'subject' },
            { data: 'category', name: 'category' },
            { data: 'priority', name: 'priority' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });

    // Handle file input change
    $('#attachments').on('change', function(e) {
        const files = Array.from(e.target.files);
        const previewContainer = $('#attachmentPreviews');
        previewContainer.empty();

        files.forEach((file, index) => {
            const extension = file.name.split('.').pop().toLowerCase();
            const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(extension);
            
            const preview = document.createElement('div');
            preview.className = `attachment-preview ${isImage ? '' : 'document'}`;
            
            if (isImage) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="attachment">
                        <div class="attachment-name">${file.name}</div>
                        <button type="button" class="remove-attachment" data-index="${index}">×</button>
                    `;
                }
                reader.readAsDataURL(file);
            } else {
                const icon = extension === 'pdf' ? 'fa-file-pdf' :
                            ['doc', 'docx'].includes(extension) ? 'fa-file-word' :
                            ['xls', 'xlsx'].includes(extension) ? 'fa-file-excel' : 'fa-file';
                
                preview.innerHTML = `
                    <i class="fas ${icon}"></i>
                    <div class="attachment-name">${file.name}</div>
                    <button type="button" class="remove-attachment" data-index="${index}">×</button>
                `;
            }
            
            previewContainer.append(preview);
        });
    });

    // Handle attachment removal
    $(document).on('click', '.remove-attachment', function() {
        const index = $(this).data('index');
        const dt = new DataTransfer();
        const input = document.getElementById('attachments');
        const { files } = input;
        
        for (let i = 0; i < files.length; i++) {
            if (i !== index) dt.items.add(files[i]);
        }
        
        input.files = dt.files;
        $(this).closest('.attachment-preview').remove();
    });

    // Handle ticket submission
    $('#submitTicket').click(function() {
        const form = $('#createTicketForm')[0];
        const formData = new FormData(form);

        $.ajax({
            url: "{{ route('customer.support.tickets.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#createTicketModal').modal('hide');
                    table.ajax.reload();
                    form.reset();
                    $('#attachmentPreviews').empty();
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        toastr.error(errors[key][0]);
                    });
                } else {
                    toastr.error('An error occurred while creating the ticket');
                }
            }
        });
    });
});

function viewTicket(id) {
    window.location.href = `/customer/support/tickets/${id}`;
}
</script>
@endpush