@extends('customer.layouts.app')

@section('title', 'Ticket Details')

@push('styles')
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
        background-color: #f0f0f0;
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
    }

    .attachment-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .reply-box {
        background: var(--dark);
        padding: 20px;
        border-radius: 10px;
        margin-top: 20px;
    }

    .attachment-icon {
        cursor: pointer;
        color: var(--second-primary);
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="card-title">Ticket #{{ $ticket->ticket_number }}</h5>
                        <span class="badge bg-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'closed' ? 'success' : 'primary') }}">
                            {{ ucfirst($ticket->status) }}
                        </span>
                    </div>

                    <h6 class="mb-3">{{ $ticket->subject }}</h6>

                    <div class="chat-container" id="chatContainer">
                        <!-- Original Ticket Message -->
                        <div class="message-bubble sent">
                            <div class="message-content">
                                <p>{{ $ticket->description }}</p>
                                @if($ticket->attachments)
                                    <div class="attachments-area">
                                        @foreach($ticket->attachments as $attachment)
                                            <div class="attachment-preview">
                                                <img src="{{ Storage::url($attachment) }}" alt="Attachment">
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="message-meta">
                                {{ $ticket->user->name }} - {{ $ticket->created_at->format('M d, Y H:i') }}
                            </div>
                        </div>

                        <!-- Replies -->
                        @foreach($ticket->replies as $reply)
                            <div class="message-bubble {{ $reply->user_id === auth()->id() ? 'sent' : 'received' }}">
                                <div class="message-content">
                                    <p>{{ $reply->message }}</p>
                                    @if($reply->attachments)
                                        <div class="attachments-area">
                                            @foreach($reply->attachments as $attachment)
                                                <div class="attachment-preview">
                                                    <img src="{{ Storage::url($attachment) }}" alt="Attachment">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="message-meta">
                                    {{ $reply->user->name }} - {{ $reply->created_at->format('M d, Y H:i') }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($ticket->status !== 'closed')
                        <div class="reply-box">
                            <form id="replyForm">
                                <div class="mb-3">
                                    <label for="message" class="form-label">Your Reply</label>
                                    <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="attachments" class="attachment-icon">
                                        <i class="fas fa-paperclip"></i> Add Attachments
                                    </label>
                                    <input type="file" id="attachments" name="attachments[]" multiple style="display: none;">
                                    <div class="attachments-area" id="attachmentPreviews"></div>
                                </div>
                                <button type="submit" class="btn btn-primary">Send Reply</button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-info mt-4">
                            This ticket is closed. Please create a new ticket if you need further assistance.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">Ticket Information</h6>
                    <div class="mb-3">
                        <small class="text-muted">Category</small>
                        <p>{{ ucfirst($ticket->category) }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Priority</small>
                        <p>
                            <span class="badge bg-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'success') }}">
                                {{ ucfirst($ticket->priority) }}
                            </span>
                        </p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Created</small>
                        <p>{{ $ticket->created_at->format('M d, Y H:i') }}</p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Last Updated</small>
                        <p>{{ $ticket->updated_at->format('M d, Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const chatContainer = document.getElementById('chatContainer');
    chatContainer.scrollTop = chatContainer.scrollHeight;

    // Handle file input change
    $('#attachments').on('change', function(e) {
        const files = Array.from(e.target.files);
        const previewContainer = $('#attachmentPreviews');
        previewContainer.empty();

        files.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = `
                    <div class="attachment-preview">
                        <img src="${e.target.result}" alt="attachment">
                        <button type="button" class="remove-attachment" data-index="${index}">×</button>
                    </div>`;
                previewContainer.append(preview);
            }
            reader.readAsDataURL(file);
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

        $.ajax({
            url: "{{ route('customer.support.tickets.reply', $ticket->id) }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    toastr.success('Reply sent successfully');
                    location.reload();
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(key => {
                        toastr.error(errors[key][0]);
                    });
                } else {
                    toastr.error('An error occurred while sending your reply');
                }
            }
        });
    });
});
</script>
@endpush