@extends('admin.layouts.app')

@section('title', 'Ticket Details')

@push('styles')
<style>
    .ql-blank::placeholder {
       color: #999 !important;
    }
</style>
<style>
    #message .ql-editor.ql-blank::before {
        color: #bebec2;
        opacity: 1;
        /* Make sure it's fully visible */
    }
</style>
<style>
    .chat-container {
        max-height: 600px;
        overflow-y: auto;
        padding: 20px;
    }

    .message-bubble {
        margin-bottom: 20px;
        max-width: 80%;
    }

    .message-bubble.sent {
        margin-left: auto;
    }

    .message-bubble.received {
        margin-right: auto;
    }

    .message-content {
        padding: 15px;
        border-radius: 10px;
        position: relative;
    }

    .sent .message-content {
        background-color: var(--second-primary);
        color: white;
    }

    .received .message-content {
        background-color: #64738c;
        color: #333;
    }

    .message-meta {
        font-size: 12px;
        margin-top: 5px;
        color: #666;
    }

    .sent .message-meta {
        text-align: right;
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
</style>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
@endpush

@section('content')
<section class="py-3">
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('admin.support') }}" class="btn btn-sm btn-secondary me-3">
            <i class="fas fa-arrow-left"></i> Back to Tickets
        </a>
        <h4 class="mb-0">Ticket #{{ $ticket->ticket_number }}</h4>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title">{{ $ticket->subject }}</h5>
                        <div class="d-flex gap-2">
                            <select id="ticketStatus" class="form-select form-select-sm">
                                <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In
                                    Progress</option>
                                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Closed
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="reply-box mb-4">
                        <form id="replyForm">
                            <div class="mb-3">
                                <div class="form-check mb-3" style="display: none;">
                                    <input class="form-check-input" type="checkbox" id="isInternal" name="is_internal">
                                    <label class="form-check-label" for="isInternal">
                                        Internal Note (only visible to staff)
                                    </label>
                                </div>
                                <label for="message" class="form-label">Your Reply</label>
                                <div id="message" style="height: 200px;"></div>
                                <input type="hidden" name="message" id="messageContent">
                            </div>
                            <div class="mb-3">
                                <label for="attachments" class="attachment-icon">
                                    <i class="fas fa-paperclip"></i> Add Attachments
                                </label>
                                <input type="file" id="attachments" name="attachments[]" multiple
                                    style="display: none;">
                                <div class="attachments-area" id="attachmentPreviews"></div>
                            </div>
                            {{-- <button type="submit" class="btn btn-primary">Send Reply</button> --}}
                            @if($ticket->status !== 'closed')

                            <!-- ... your form fields ... -->
                            <button type="submit" class="btn btn-primary">Send Reply</button>

                            @else
                            <div class="alert alert-danger mb-3">
                                This ticket is closed. You cannot send new replies.
                            </div>
                            @endif
                        </form>
                    </div>

                    <div id="chatContainer" class="chat-container">
                        <!-- Ticket replies in reverse chronological order -->
                        @foreach($ticket->replies->sortByDesc('created_at') as $reply)
                        <div class="message-bubble {{ $reply->user_id === auth()->id() ? 'sent' : 'received' }}">
                            <div class="message-content">
                                {!! $reply->message !!}
                                @if($reply->is_internal)
                                <div class="internal-note">
                                    <i class="fas fa-eye-slash"></i> Internal Note
                                </div>
                                @endif
                            </div>
                            <div class="message-meta">
                                {{ $reply->user->name }} - {{ $reply->created_at->format('M d, Y H:i') }}
                            </div>
                            @if($reply->attachments && count($reply->attachments) > 0)
                            <div class="attachments-area">
                                @foreach($reply->attachments as $attachment)
                                @php
                                $extension = strtolower(pathinfo($attachment, PATHINFO_EXTENSION));
                                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
                                $fileName = basename($attachment);
                                @endphp
                                <div class="attachment-preview {{ $isImage ? '' : 'document' }}">
                                    @if($isImage)
                                    <img src="{{ Storage::url($attachment) }}" alt="Attachment">
                                    @else
                                    <i class="fas {{ 
                                            in_array($extension, ['pdf']) ? 'fa-file-pdf' : 
                                            (in_array($extension, ['doc', 'docx']) ? 'fa-file-word' : 
                                            (in_array($extension, ['xls', 'xlsx']) ? 'fa-file-excel' : 'fa-file'))
                                        }}"></i>
                                    @endif
                                    <div class="attachment-name">{{ $fileName }}</div>
                                    <a href="{{ Storage::url($attachment) }}"
                                        class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2"
                                        target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endforeach

                        <!-- Initial ticket message -->
                        <div class="message-bubble received">
                            <div class="message-content">
                                {!! $ticket->description !!}
                            </div>
                            <div class="message-meta">
                                {{ $ticket->user->name }} - {{ $ticket->created_at->format('M d, Y H:i') }}
                            </div>
                            @if($ticket->attachments && count($ticket->attachments) > 0)
                            <div class="attachments-area">
                                @foreach($ticket->attachments as $attachment)
                                @php
                                $extension = strtolower(pathinfo($attachment, PATHINFO_EXTENSION));
                                $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif']);
                                $fileName = basename($attachment);
                                @endphp
                                <div class="attachment-preview {{ $isImage ? '' : 'document' }}">
                                    @if($isImage)
                                    <img src="{{ Storage::url($attachment) }}" alt="Attachment">
                                    @else
                                    <i class="fas {{ 
                                            in_array($extension, ['pdf']) ? 'fa-file-pdf' : 
                                            (in_array($extension, ['doc', 'docx']) ? 'fa-file-word' : 
                                            (in_array($extension, ['xls', 'xlsx']) ? 'fa-file-excel' : 'fa-file'))
                                        }}"></i>
                                    @endif
                                    <div class="attachment-name">{{ $fileName }}</div>
                                    <a href="{{ Storage::url($attachment) }}"
                                        class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2"
                                        target="_blank">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title">Ticket Information</h6>
                    <div class="mb-3">
                        <small class="text-light">Customer</small>
                        <p>{{ $ticket->user->name }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-light">Email</small>
                        <p>{{ $ticket->user->email }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-light">Category</small>
                        <p>{{ ucfirst($ticket->category) }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-light">Priority</small>
                        <p>
                            <span
                                class="badge bg-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'success') }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <small class="text-light">Created</small>
                        <p>{{ $ticket->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-light">Last Updated</small>
                        <p>{{ $ticket->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    const quill = new Quill('#message', {
    theme: 'snow',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            ['link'],
            ['clean']
        ]
    },
    placeholder: 'Type your message here...'
});
</script>
<script>
    $(document).ready(function() {
    // Handle status changes
    $('#ticketStatus').change(function() {
        const status = $(this).val();
        
        $.ajax({
            url: "{{ route('admin.support.tickets.status', $ticket->id) }}",
            method: 'PATCH',
            data: {
                status: status,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    toastr.success('Ticket status updated successfully');
                    window.location.reload(); // Reload to update ticket details   
                }
            },
            error: function() {
                toastr.error('Failed to update ticket status');
                // Reset select to previous value
                $(this).val($(this).find('option[selected]').val());
            }
        });
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

    // Handle reply submission
    $('#replyForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('message', quill.root.innerHTML);
        
        $.ajax({
            url: "{{ route('admin.support.tickets.reply', $ticket->id) }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Clear form
                    quill.root.innerHTML = '';
                    $('#attachments').val('');
                    $('#attachmentPreviews').empty();
                    $('#isInternal').prop('checked', false);
                    
                    // Add new message to chat
                    const newMessage = `
                        <div class="message-bubble sent">
                            <div class="message-content">
                                ${response.reply.message}
                            </div>
                            <div class="message-meta">
                                ${response.reply.user.name} - Just now
                            </div>
                            ${response.reply.attachments && response.reply.attachments.length > 0 ? `
                                <div class="attachments-area">
                                    ${response.reply.attachments.map(attachment => {
                                        const extension = attachment.split('.').pop().toLowerCase();
                                        const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(extension);
                                        const fileName = attachment.split('/').pop();
                                        
                                        return `
                                            <div class="attachment-preview ${isImage ? '' : 'document'}">
                                                ${isImage ? 
                                                    `<img src="/storage/${attachment}" alt="Attachment">` :
                                                    `<i class="fas ${
                                                        extension === 'pdf' ? 'fa-file-pdf' :
                                                        ['doc', 'docx'].includes(extension) ? 'fa-file-word' :
                                                        ['xls', 'xlsx'].includes(extension) ? 'fa-file-excel' : 'fa-file'
                                                    }"></i>`
                                                }
                                                <div class="attachment-name">${fileName}</div>
                                                <a href="/storage/${attachment}" class="btn btn-sm btn-primary position-absolute top-0 end-0 m-2" target="_blank">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                        `;
                                    }).join('')}
                                </div>
                            ` : ''}
                        </div>
                    `;
                    
                    // Prepend new message and show with animation
                    $('#chatContainer').prepend(newMessage);
                    $('.message-bubble:first').slideDown();
                    
                    toastr.success('Reply sent successfully');
                    window.location.reload(); // Reload to update ticket details
                }
            }, 
            error: function(xhr) {
                const message = xhr.responseJSON?.message || 'Failed to send reply';
                toastr.error(message);
            }
        });
    });
});
</script>
@endpush