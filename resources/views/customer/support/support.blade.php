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
        background: rgba(0, 0, 0, 0.5);
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
        background: rgba(0, 0, 0, 0.7);
        color: white;
        font-size: 10px;
        padding: 2px 5px;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }

    /* Quill editor custom styles */
    .ql-editor {
        min-height: 120px;
        color: var(--light-color);
    }

    .ql-toolbar.ql-snow {
        border-color: var(--input-border);
        border-top-left-radius: 6px;
        border-top-right-radius: 6px;
    }

    .ql-container.ql-snow {
        border-color: var(--input-border);
        border-bottom-left-radius: 6px;
        border-bottom-right-radius: 6px;
        background: transparent;
    }

    .ql-snow .ql-stroke {
        stroke: var(--light-color);
    }

    .ql-snow .ql-fill {
        fill: var(--light-color);
    }

    .ql-snow .ql-picker {
        color: var(--light-color);
    }

    /* Select2 Styles */
    .select2-container--default .select2-selection--single {
        background-color: var(--primary-color);
        border: 1px solid var(--input-border);
        border-radius: 6px;
        height: auto;
        padding: .4rem;
    }

    .select2-container {
        width: 100% !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--light-color);
        padding-left: 0;
        opacity: .7;
        font-size: 14px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
        right: 8px;
    }

    .select2-dropdown {
        background-color: var(--primary-color);
        border: 1px solid var(--input-border);
        z-index: 9999;
    }

    .select2-search--dropdown .select2-search__field {
        background-color: var(--primary-color);
        color: var(--light-color);
        border: 1px solid var(--input-border);
        border-radius: 6px;
        padding: .4rem;
    }

    .select2-container--default .select2-results__option {
        padding: .5rem;
        font-size: 14px;
        opacity: .7;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--second-primary);
        color: var(--white-color);
        opacity: 1;
    }

    .select2-container--default .select2-selection--single:focus {
        border: 2px solid var(--second-primary) !important;
        box-shadow: rgba(159, 93, 209, 0.814) 0px 0px 4px !important;
    }
</style>
@endpush

@section('content')
<section class="py-3">

    <div class="counters mb-4">
        <div class="card p-3 counter_1">
            <div>
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <h6 class="text-heading">Total Tickets</h6>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0" id="pendingTicketsCount">{{ $totalTickets }}</h4>
                            <p class="mb-0"></p>
                        </div>
                        <small class="mb-0"></small>
                    </div>
                    <div class="avatar">
                        {{-- <span class="avatar-initial rounded bg-label-warning">
                            <i class="ti ti-user-search"></i>
                        </span> --}}
                        {{-- <img src="https://cdn-icons-gif.flaticon.com/8112/8112582.gif" width="40"
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
                            <h4 class="mb-0" id="pendingTicketsCount">{{ $pendingTickets }}</h4>
                            <p class="mb-0"></p>
                        </div>
                        <small class="mb-0"></small>
                    </div>
                    <div class="avatar">
                        {{-- <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-user-check"></i>
                        </span> --}}
                        {{-- <img src="https://cdn-icons-gif.flaticon.com/8701/8701351.gif" width="40"
                            style="border-radius: 50px" alt=""> --}}
                        <i class="fa-solid fa-envelope-open-text fs-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-3 counter_1">
            <div>
                <!-- {{-- //card body --}} -->
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <h6 class="text-heading">In-Progress Tickets</h6>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0" id="pendingTicketsCount">{{ $inProgressTickets }}</h4>
                            <p class="mb-0"></p>
                        </div>
                        <small class="mb-0"></small>
                    </div>
                    <div class="avatar">
                        {{-- <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-user-plus"></i>
                        </span> --}}
                        {{-- <img src="https://cdn-icons-gif.flaticon.com/17122/17122416.gif" width="40"
                            style="border-radius: 50px" alt=""> --}}
                        <i class="fa-solid fa-bars-progress fs-2"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-3 counter_2">
            <div>
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <h6 class="text-heading">Closed Tickets</h6>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0" id="completedTicketsCount">{{ $completedTickets }}</h4>
                            <p class="mb-0"></p>
                        </div>
                        <small class="mb-0"></small>
                    </div>
                    <div class="avatar">
                        {{-- <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-user-check"></i>
                        </span> --}}
                        {{-- <img src="https://cdn-icons-gif.flaticon.com/10352/10352695.gif" width="40"
                            style="border-radius: 50px" alt=""> --}}
                        <i class="fa-solid fa-shop-slash fs-2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- <div class="row mb-4">
        <div class="col-xl-3 col-lg-6 col-md-6">
            <div class="card">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <p class="fw-normal mb-0">Total tickets</p>
                    </div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div class="role-heading">
                            <h4 class="mb-0 text-primary" id="totalTicketsCount">{{ $totalTickets }}</h4>
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
                        <p class="fw-normal mb-0">Open tickets</p>
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
                        <p class="fw-normal mb-0">In Progress tickets</p>
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
                        <p class="fw-normal mb-0">Closed tickets</p>
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
    </div> --}}

    <div class="row mb-4" style="display: none;">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="card p-3">
        <div class="table-responsive">
            <table id="ticketsTable" class=" w-100">
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
<div class="modal fade" id="createTicketModal" tabindex="-1" aria-labelledby="createTicketModalLabel"
    aria-hidden="true">
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
                            <option value="order">Order Issue</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="mb-3" id="orderSelection" style="display: none;">
                        <label for="order_id" class="form-label">Select Order</label>
                        <select class="form-select" id="order_id" name="order_id">
                            <option value="">Select Order</option>
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
                        <div id="description-editor"></div>
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
            url: "{{ route('customer.support.tickets') }}",
            data: function(d) {
                // Add filter parameters to the AJAX request
                d.ticket_number = $('#ticketNumberFilter').val();
                d.subject = $('#subjectFilter').val();
                d.category = $('#categoryFilter').val();
                d.priority = $('#priorityFilter').val();
                d.status = $('#statusFilter').val();
                d.start_date = $('#startDate').val();
                d.end_date = $('#endDate').val();
            },
            dataSrc: function(json) {
                // Update counters when data is received
                if (json.counters) {
                    $('#totalTicketsCount').text(json.counters.totalTickets);
                    $('#pendingTicketsCount').text(json.counters.pendingTickets);
                    $('#inProgressTicketsCount').text(json.counters.inProgressTickets);
                    $('#completedTicketsCount').text(json.counters.completedTickets);
                }
                return json.data;
            }
        },
        columns: [
            { data: 'ticket_number', name: 'ticket_number' },
            { 
                data: 'subject', name: 'subject',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex gap-1 align-items-center">
                            <div>
                                <img src="https://cdn-icons-png.flaticon.com/128/17720/17720968.png" width="35px" alt=""/>
                            </div>
                            <span>${data}</span>    
                        </div>
                    `;
                }
            },
            { data: 'category', name: 'category' },
            { data: 'priority', name: 'priority' },
            { data: 'status', name: 'status' },
            { data: 'created_at', name: 'created_at' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
    let createTicketButton = `<button class="m-btn py-2 px-4 rounded-2 border-0" data-bs-toggle="modal" data-bs-target="#createTicketModal">
                <i class="fa-solid fa-plus me-2"></i>Create New Ticket
            </button>`;
    // append the button to the top of the table filter
    $('.dataTables_filter').append(createTicketButton);
            
    // Apply filters when any filter input changes
    $('.form-control, .form-select').on('change', function() {
        table.draw();
    });

    // Clear all filters when the Clear button is clicked
    $('#clearFilters').on('click', function() {
        $('.form-control, .form-select').val('');
        table.draw();
    });
    
    // Initially hide the filter button since we're auto-filtering
    $('#applyFilters').hide();
    
    // Update counters on ticket status changes
    $(document).on('ticketStatusChanged', function() {
        table.draw();
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

    // Initialize Quill editor
    const quill = new Quill('#description-editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link'],
                ['clean']
            ]
        },
        placeholder: 'Type your description here...'
    });

    // Handle ticket submission
    $('#submitTicket').click(function() {
        const submitBtn = $(this);
        submitBtn.prop('disabled', true);
        
        Swal.fire({
            title: 'Creating ticket...',
            text: 'Please wait while we process your request',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const form = $('#createTicketForm')[0];
        const formData = new FormData(form);
        formData.append('description', quill.root.innerHTML);

        $.ajax({
            url: "{{ route('customer.support.tickets.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.close();
                if (response.success) {
                    toastr.success(response.message);
                    $('#createTicketModal').modal('hide');
                    table.ajax.reload(null, false); // This will update both table and counters
                    
                    form.reset();
                    quill.root.innerHTML = '';
                    $('#attachmentPreviews').empty();
                }
            },
            error: function(xhr) {
                Swal.close();
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        toastr.error(errors[key][0]);
                    });
                } else {
                    toastr.error('An error occurred while creating the ticket');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false);
            }
        });
    });

    // Show/hide order selection based on category
    $('#category').on('change',function() {
        if ($(this).val() === 'order') {
            $('#orderSelection').show();
            $('#order_id').prop('required', true);
        } else {
            $('#orderSelection').hide();
            $('#order_id').prop('required', false);
            $('#order_id').val(null).trigger('change'); // Clear the selection
        }
    });
});

function viewTicket(id) {
    window.location.href = `/customer/support/tickets/${id}`;
}
</script>

<script>
    $(document).ready(function() {
        // Special initialization for order_id with AJAX
        $('#order_id').select2({
            placeholder: 'Select an order',
            width: '100%',
            dropdownParent: $('#createTicketModal'),
            minimumResultsForSearch: 0, // This ensures search box appears
            language: {
                searching: function() {
                    return "Searching...";
                }
            },
            allowClear: true,
            ajax: {
                url: "{{ route('customer.support.tickets.orders') }}",
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        page: params.page
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.results
                    };
                },
                cache: true
            },
            minimumInputLength: 0
        });
    });
</script>
@endpush