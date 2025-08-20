@extends('admin.layouts.app')

@section('title', 'Error Log Details')

@push('styles')
<style>
    .error-severity {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-weight: 600;
    }
    .severity-error { background-color: #f8d7da; color: #721c24; }
    .severity-warning { background-color: #fff3cd; color: #856404; }
    .severity-info { background-color: #d1ecf1; color: #0c5460; }
    .severity-debug { background-color: #e2e3e5; color: #383d41; }
    
    .code-block {
        /* background-color: #f8f9fa; */
        border: 1px solid #e9ecef;
        border-radius: 0.375rem;
        padding: 1rem;
        font-family: 'Courier New', monospace;
        font-size: 0.875rem;
        overflow-x: auto;
        white-space: pre-wrap;
        word-break: break-all;
    }
    
    .info-card {
        border-left: 4px solid #007bff;
        /* background-color: #f8f9fa; */
    }
    
    .error-card {
        border-left: 4px solid #dc3545;
        /* background-color: #fff5f5; */
    }
    
    .btn-loading {
        position: relative;
        pointer-events: none;
    }
    
    .action-buttons .btn {
        transition: all 0.3s ease;
    }
    
    .action-buttons .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .code-block {
        max-height: 400px;
        overflow-y: auto;
        transition: max-height 0.3s ease;
    }
    
    .code-block.collapsed {
        max-height: 150px;
        overflow-y: auto;
    }
    
    .table-borderless td {
        padding: 0.5rem 0.75rem;
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4>Error Log Details #{{ $errorLog->id }}</h4>
                <div>
                    <a href="{{ route('admin.error-logs.index') }}" class="btn btn-outline-secondary">
                        <i class="fa fa-arrow-left"></i> Back to Error Logs
                    </a>
                    <button type="button" class="btn btn-outline-danger delete-error-log-detail" 
                            data-id="{{ $errorLog->id }}" 
                            data-url="{{ route('admin.error-logs.destroy', $errorLog) }}">
                        <i class="fa fa-trash"></i> Delete
                    </button>
                </div>
            </div>

            <!-- Basic Information -->
            <div class="card mb-4 error-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa fa-exclamation-triangle text-danger"></i>
                        Exception Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="30%"><strong>Exception Class:</strong></td>
                                    <td><code>{{ $errorLog->exception_class }}</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Severity:</strong></td>
                                    <td>
                                        <span class="error-severity severity-{{ $errorLog->severity }}">
                                            {{ ucfirst($errorLog->severity) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Date/Time:</strong></td>
                                    <td>{{ $errorLog->created_at->format('Y-m-d H:i:s') }}</td>
                                </tr>
                                <tr>
                                    <td><strong>File:</strong></td>
                                    <td><code>{{ $errorLog->file }}</code></td>
                                </tr>
                                <tr>
                                    <td><strong>Line:</strong></td>
                                    <td><code>{{ $errorLog->line }}</code></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td width="30%"><strong>URL:</strong></td>
                                    <td>{{ $errorLog->url ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>HTTP Method:</strong></td>
                                    <td>{{ $errorLog->method ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>User:</strong></td>
                                    <td>{{ $errorLog->user ? $errorLog->user->name : 'Guest' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>IP Address:</strong></td>
                                    <td>{{ $errorLog->ip_address ?: 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>User Agent:</strong></td>
                                    <td style="word-break: break-all;">{{ Str::limit($errorLog->user_agent, 50) ?: 'N/A' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div>
                        <h6><strong>Error Message:</strong></h6>
                        <div class="code-block">{{ $errorLog->message }}</div>
                    </div>
                </div>
            </div>

            <!-- Stack Trace -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fa fa-code text-primary"></i>
                        Stack Trace
                    </h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleStackTrace()" id="stackTraceToggle">
                        <i class="fa fa-compress"></i> Collapse
                    </button>
                </div>
                <div class="card-body">
                    <div class="code-block" id="stackTraceBlock">{{ $errorLog->trace }}</div>
                </div>
            </div>

            <!-- Request Data -->
            @if($errorLog->request_data && count($errorLog->request_data) > 0)
            <div class="card mb-4 info-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa fa-database text-info"></i>
                        Request Data
                    </h5>
                </div>
                <div class="card-body">
                    <div class="code-block">{{ json_encode($errorLog->request_data, JSON_PRETTY_PRINT) }}</div>
                </div>
            </div>
            @endif

            <!-- User Agent Details -->
            @if($errorLog->user_agent)
            <div class="card mb-4 info-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa fa-globe text-info"></i>
                        User Agent Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="code-block">{{ $errorLog->user_agent }}</div>
                </div>
            </div>
            @endif

            <!-- Actions -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fa fa-cogs text-secondary"></i>
                        Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap action-buttons">
                        <button type="button" class="btn btn-outline-primary" onclick="copyToClipboard()">
                            <i class="fa fa-copy"></i> Copy Error Details
                        </button>
                        
                        <!-- @if($errorLog->url)
                        <a href="{{ $errorLog->url }}" target="_blank" class="btn btn-outline-info">
                            <i class="fa fa-external-link"></i> Visit URL
                        </a>
                        @endif -->
                        
                        <!-- <button type="button" class="btn btn-outline-success" onclick="markAsResolved()">
                            <i class="fa fa-check"></i> Mark as Resolved
                        </button>
                        
                        <button type="button" class="btn btn-outline-warning" onclick="reportSimilar()">
                            <i class="fa fa-search"></i> Find Similar Errors
                        </button> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // CSRF token setup for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Handle delete button click
    $(document).on('click', '.delete-error-log-detail', function() {
        const button = $(this);
        const errorId = button.data('id');
        const deleteUrl = button.data('url');

        // Prevent multiple clicks
        if (button.hasClass('btn-loading')) {
            return;
        }

        Swal.fire({
            title: 'Confirm Delete',
            text: 'Are you sure you want to delete this error log? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting Error Log...',
                    text: 'Please wait while we delete this error log.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Add loading state to button
                button.addClass('btn-loading').prop('disabled', true);
                const originalText = button.html();
                button.html('<i class="fa fa-spinner fa-spin"></i> Deleting...');

                // Make AJAX request
                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted Successfully!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Redirect to index page after a short delay
                        setTimeout(() => {
                            window.location.href = '{{ route("admin.error-logs.index") }}';
                        }, 1500);
                    },
                    error: function(xhr) {
                        // Remove loading state
                        button.removeClass('btn-loading').prop('disabled', false);
                        button.html(originalText);
                        
                        let message = 'An error occurred while deleting the error log.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.status === 404) {
                            message = 'Error log not found. It may have already been deleted.';
                        } else if (xhr.status === 403) {
                            message = 'You do not have permission to delete this error log.';
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Deletion Failed!',
                            text: message,
                        });
                    }
                });
            }
        });
    });

    function copyToClipboard() {
        const errorDetails = `
Error Log #{{ $errorLog->id }}
Exception: {{ $errorLog->exception_class }}
Message: {{ $errorLog->message }}
File: {{ $errorLog->file }}:{{ $errorLog->line }}
Date: {{ $errorLog->created_at->format('Y-m-d H:i:s') }}
URL: {{ $errorLog->url ?: 'N/A' }}
User: {{ $errorLog->user ? $errorLog->user->name : 'Guest' }}

Stack Trace:
{{ $errorLog->trace }}
        `.trim();
        
        // Show loading
        Swal.fire({
            title: 'Copying...',
            text: 'Preparing error details for clipboard.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            timer: 500,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Use modern clipboard API
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(errorDetails).then(function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Error details have been copied to your clipboard.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
                fallbackCopyTextToClipboard(errorDetails);
            });
        } else {
            // Fallback for older browsers or non-HTTPS
            fallbackCopyTextToClipboard(errorDetails);
        }
    }

    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            const successful = document.execCommand('copy');
            document.body.removeChild(textArea);
            
            if (successful) {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: 'Error details have been copied to your clipboard.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                throw new Error('Copy command failed');
            }
        } catch (err) {
            document.body.removeChild(textArea);
            Swal.fire({
                icon: 'error',
                title: 'Copy Failed',
                text: 'Unable to copy to clipboard. Please select and copy the text manually.',
                showConfirmButton: true
            });
        }
    }
    
    function markAsResolved() {
        Swal.fire({
            title: 'Mark as Resolved',
            text: 'This will help track which errors have been addressed. Are you sure this error has been resolved?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, mark as resolved',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show success message (placeholder for future feature)
                Swal.fire({
                    icon: 'info',
                    title: 'Feature Coming Soon',
                    text: 'Resolution tracking system will be implemented in a future update.',
                    timer: 3000,
                    showConfirmButton: false
                });
            }
        });
    }

    function reportSimilar() {
        Swal.fire({
            title: 'Finding Similar Errors...',
            text: 'Searching for errors with similar characteristics.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Simulate search and redirect
        setTimeout(() => {
            const searchQuery = '{{ addslashes($errorLog->exception_class) }}';
            const searchUrl = '{{ route("admin.error-logs.index") }}' + '?search=' + encodeURIComponent(searchQuery);
            
            Swal.fire({
                icon: 'success',
                title: 'Search Complete',
                text: 'Redirecting to similar errors...',
                timer: 1500,
                showConfirmButton: false
            });

            setTimeout(() => {
                window.location.href = searchUrl;
            }, 1000);
        }, 1500);
    }

    function toggleStackTrace() {
        const stackTraceBlock = $('#stackTraceBlock');
        const toggleButton = $('#stackTraceToggle');
        
        if (stackTraceBlock.hasClass('collapsed')) {
            // Expand
            stackTraceBlock.removeClass('collapsed').css('max-height', 'none');
            toggleButton.html('<i class="fa fa-compress"></i> Collapse');
        } else {
            // Collapse
            stackTraceBlock.addClass('collapsed').css('max-height', '150px');
            toggleButton.html('<i class="fa fa-expand"></i> Expand');
        }
    }

    // Show session messages with SweetAlert2
    @if(session('success'))
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '{{ session("success") }}',
            timer: 3000,
            showConfirmButton: false
        });
    @endif

    @if(session('error'))
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '{{ session("error") }}',
        });
    @endif

    // Add loading styles for buttons
    $('<style>')
        .prop('type', 'text/css')
        .html(`
            .btn-loading {
                position: relative;
                pointer-events: none;
            }
            .btn-loading:after {
                content: '';
                position: absolute;
                width: 16px;
                height: 16px;
                margin: auto;
                border: 2px solid transparent;
                border-top-color: #ffffff;
                border-radius: 50%;
                animation: button-loading-spinner 1s ease infinite;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
            @keyframes button-loading-spinner {
                from { transform: translate(-50%, -50%) rotate(0turn); }
                to { transform: translate(-50%, -50%) rotate(1turn); }
            }
        `)
        .appendTo('head');
</script>
@endpush
