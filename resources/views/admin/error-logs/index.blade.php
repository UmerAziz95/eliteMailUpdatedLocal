@extends('admin.layouts.app')

@section('title', 'Error Logs')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />
<style>
    .glass-box {
        background-color: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 0.55rem .5rem;
    }

    .error-card {
        background: linear-gradient(145deg, #2c2c54, #1a1a2e);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
        min-height: 280px;
    }

    .error-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        border-color: rgba(255, 255, 255, 0.2);
    }

    .error-severity {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-weight: 600;
    }
    
    .severity-error { 
        background: linear-gradient(45deg, #dc3545, #c82333);
        color: white;
    }
    .severity-warning { 
        background: linear-gradient(45deg, #ffc107, #e0a800);
        color: #212529;
    }
    .severity-info { 
        background: linear-gradient(45deg, #17a2b8, #138496);
        color: white;
    }
    .severity-debug { 
        background: linear-gradient(45deg, #6c757d, #5a6268);
        color: white;
    }

    .error-message {
        word-break: break-word;
        background-color: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        padding: 0.5rem;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        border-left: 3px solid #dc3545;
    }

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

    .error-id-badge {
        background: linear-gradient(45deg, #007bff, #0056b3);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
        grid-column: 1 / -1;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .nav-link {
        font-size: 13px;
        color: #fff
    }

    .filter-card {
        background: linear-gradient(145deg, #2c2c54, #1a1a2e);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-white mb-0">Error Logs Management</h4>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-light btn-sm" onclick="refreshErrorLogs()">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#clearOldModal">
                <i class="fa fa-trash me-1"></i> Clear Old Logs
            </button>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="bulkDelete()" id="bulkDeleteBtn" style="display: none;">
                <i class="fa fa-trash me-1"></i> Delete Selected
            </button>
        </div>
    </div>
    
    <!-- Filters Card -->
    <div class="card filter-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.error-logs.index') }}" class="mb-0">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-white-50">Date From</label>
                        <input type="date" name="date_from" class="form-control bg-dark text-white border-secondary" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-white-50">Date To</label>
                        <input type="date" name="date_to" class="form-control bg-dark text-white border-secondary" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-white-50">Search</label>
                        <input type="text" name="search" class="form-control bg-dark text-white border-secondary" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-outline-light btn-sm">
                            <i class="fa fa-search"></i> Filter
                        </button>
                        <a href="{{ route('admin.error-logs.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-refresh"></i> Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Error Logs Grid -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="glass-box">
            <span class="text-white-50">Total: </span>
            <span class="text-white fw-bold">{{ $errorLogs->total() }} logs</span>
        </div>
        <div class="glass-box">
            <input type="checkbox" id="selectAll" class="form-check-input me-2" onchange="toggleSelectAll()">
            <label for="selectAll" class="text-white-50">Select All</label>
        </div>
    </div>

    <div class="row g-3" id="errorLogsGrid">
        @forelse($errorLogs as $errorLog)
            <div class="col-lg-6 col-xl-4" id="error-log-card-{{ $errorLog->id }}">
                <div class="card error-card h-100">
                    <div class="card-body d-flex flex-column">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <input type="checkbox" class="error-log-checkbox form-check-input" value="{{ $errorLog->id }}" name="error_log_ids[]" onchange="toggleBulkDelete()">
                                <span class="error-id-badge">#{{ $errorLog->id }}</span>
                            </div>
                            <span class="error-severity severity-{{ $errorLog->severity }}">
                                {{ ucfirst($errorLog->severity) }}
                            </span>
                        </div>

                        <!-- Exception Type -->
                        <div class="glass-box mb-3">
                            <div class="d-flex align-items-center text-white-50 mb-1">
                                <i class="fas fa-bug me-2"></i>
                                <span class="small">Exception</span>
                            </div>
                            <code class="text-warning">{{ class_basename($errorLog->exception_class) }}</code>
                        </div>

                        <!-- Error Message -->
                        <div class="flex-grow-1 mb-3">
                            <div class="d-flex align-items-center text-white-50 mb-2">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <span class="small">Message</span>
                            </div>
                            <div class="error-message">
                                {{ Str::limit($errorLog->message, 120) }}
                            </div>
                        </div>

                        <!-- File Location -->
                        <div class="glass-box mb-3">
                            <div class="d-flex align-items-center text-white-50 mb-1">
                                <i class="fas fa-file-code me-2"></i>
                                <span class="small">Location</span>
                            </div>
                            <div class="text-white small">
                                <strong>{{ basename($errorLog->file) }}</strong>:{{ $errorLog->line }}
                            </div>
                        </div>

                        <!-- Footer Info -->
                        <div class="mt-auto">
                            <div class="row g-2 align-items-center">
                                <div class="col">
                                    <div class="glass-box">
                                        <div class="d-flex align-items-center text-white-50 mb-1">
                                            <i class="fas fa-user me-2"></i>
                                            <span class="small">User</span>
                                        </div>
                                        <div class="text-white small">
                                            @if($errorLog->user)
                                                {{ $errorLog->user->name }}
                                            @else
                                                Guest
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="glass-box">
                                        <div class="d-flex align-items-center text-white-50 mb-1">
                                            <i class="fas fa-clock me-2"></i>
                                            <span class="small">Time</span>
                                        </div>
                                        <div class="text-white small">
                                            {{ $errorLog->created_at->format('M j, H:i') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-flex gap-2 mt-3">
                                <a href="{{ route('admin.error-logs.show', $errorLog) }}" class="btn btn-outline-light btn-sm flex-fill">
                                    <i class="fas fa-eye me-1"></i> View Details
                                </a>
                                <button type="button" class="btn btn-outline-danger btn-sm delete-error-log" 
                                        data-id="{{ $errorLog->id }}" 
                                        data-url="{{ route('admin.error-logs.destroy', $errorLog) }}" 
                                        title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-info-circle"></i>
                    <h5 class="text-white-50">No Error Logs Found</h5>
                    <p class="text-white-50">No error logs match your current filters.</p>
                    <!-- @if(request()->anyFilled(['severity', 'date_from', 'date_to', 'search']))
                        <a href="{{ route('admin.error-logs.index') }}" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-refresh"></i> Clear Filters
                        </a>
                    @endif -->
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($errorLogs->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $errorLogs->appends(request()->query())->links() }}
        </div>
    @endif
</section>

<!-- Clear Old Logs Modal -->
<div class="modal fade" id="clearOldModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Old Error Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="clearOldForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Delete logs older than (days):</label>
                        <input type="number" name="days" id="clearDays" class="form-control" value="30" min="1" required>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fa fa-warning"></i>
                        This action cannot be undone. Please make sure you want to permanently delete old error logs.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="clearOldBtn">Delete Old Logs</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // CSRF token setup for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const checkboxes = document.querySelectorAll('.error-log-checkbox');
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAll.checked;
        });
        
        toggleBulkDelete();
    }
    
    function toggleBulkDelete() {
        const checkboxes = document.querySelectorAll('.error-log-checkbox:checked');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        
        if (checkboxes.length > 0) {
            bulkDeleteBtn.style.display = 'inline-block';
        } else {
            bulkDeleteBtn.style.display = 'none';
        }
    }

    function refreshErrorLogs() {
        window.location.reload();
    }

    function deleteErrorLog(errorId) {
        const deleteUrl = '{{ route("admin.error-logs.destroy", ":id") }}'.replace(':id', errorId);
        
        Swal.fire({
            title: 'Confirm Delete',
            text: 'Are you sure you want to delete this error log?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the error log.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Make AJAX request
                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Remove card from grid
                        $(`#error-log-card-${errorId}`).fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if grid is empty
                            if ($('#errorLogsGrid .col-lg-6:visible').length === 0) {
                                // Show no data message
                                $('#errorLogsGrid').html(`
                                    <div class="col-12">
                                        <div class="empty-state">
                                            <i class="fas fa-info-circle"></i>
                                            <h5 class="text-white-50">No Error Logs Found</h5>
                                            <p class="text-white-50">All error logs have been deleted.</p>
                                        </div>
                                    </div>
                                `);
                            }
                        });

                        // Reset bulk delete if needed
                        toggleBulkDelete();
                    },
                    error: function(xhr) {
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
                            title: 'Error!',
                            text: message,
                        });
                    }
                });
            }
        });
    }

    function bulkDelete() {
        const checkboxes = document.querySelectorAll('.error-log-checkbox:checked');
        
        if (checkboxes.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Selection',
                text: 'Please select at least one error log to delete.',
            });
            return;
        }

        // Get selected IDs
        const selectedIds = Array.from(checkboxes).map(cb => cb.value);
        
        Swal.fire({
            title: 'Confirm Bulk Delete',
            text: `Are you sure you want to delete ${checkboxes.length} selected error log(s)?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the selected error logs.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Make AJAX request
                $.ajax({
                    url: '{{ route("admin.error-logs.bulk-delete") }}',
                    type: 'POST',
                    data: {
                        error_log_ids: selectedIds
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Remove deleted cards from grid
                        selectedIds.forEach(id => {
                            $(`#error-log-card-${id}`).fadeOut(300, function() {
                                $(this).remove();
                            });
                        });

                        // Reset checkboxes
                        document.getElementById('selectAll').checked = false;
                        toggleBulkDelete();

                        // Check if grid is empty after deletion
                        setTimeout(() => {
                            if ($('#errorLogsGrid .col-lg-6:visible').length === 0) {
                                // Show no data message
                                $('#errorLogsGrid').html(`
                                    <div class="col-12">
                                        <div class="empty-state">
                                            <i class="fas fa-info-circle"></i>
                                            <h5 class="text-white-50">No Error Logs Found</h5>
                                            <p class="text-white-50">All error logs have been deleted.</p>
                                        </div>
                                    </div>
                                `);
                            }
                        }, 500);
                    },
                    error: function(xhr) {
                        let message = 'An error occurred while deleting error logs.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            message = 'Validation error: ' + Object.values(xhr.responseJSON.errors).flat().join(', ');
                        } else if (xhr.status === 403) {
                            message = 'You do not have permission to delete these error logs.';
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message,
                        });
                    }
                });
            }
        });
    }

    // Handle individual delete buttons
    $(document).on('click', '.delete-error-log', function() {
        const button = $(this);
        const errorId = button.data('id');
        const deleteUrl = button.data('url');

        // Prevent multiple clicks
        if (button.hasClass('btn-loading')) {
            return;
        }

        Swal.fire({
            title: 'Confirm Delete',
            text: 'Are you sure you want to delete this error log?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Add loading state to button
                button.addClass('btn-loading').prop('disabled', true);
                const originalText = button.html();
                button.html('<i class="fa fa-spinner fa-spin"></i>');

                // Make AJAX request
                $.ajax({
                    url: deleteUrl,
                    type: 'DELETE',
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Remove card from grid
                        $(`#error-log-card-${errorId}`).fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if grid is empty
                            if ($('#errorLogsGrid .col-lg-6:visible').length === 0) {
                                // Show no data message
                                $('#errorLogsGrid').html(`
                                    <div class="col-12">
                                        <div class="empty-state">
                                            <i class="fas fa-info-circle"></i>
                                            <h5 class="text-white-50">No Error Logs Found</h5>
                                            <p class="text-white-50">All error logs have been deleted.</p>
                                        </div>
                                    </div>
                                `);
                            }
                        });

                        // Reset bulk delete if needed
                        toggleBulkDelete();
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
                            title: 'Error!',
                            text: message,
                        });
                    }
                });
            }
        });
    });

    // Handle clear old logs form
    $('#clearOldForm').on('submit', function(e) {
        e.preventDefault();
        
        const days = $('#clearDays').val();
        
        Swal.fire({
            title: 'Confirm Clear Old Logs',
            text: `Are you sure you want to delete all error logs older than ${days} days?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete them!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Hide modal
                $('#clearOldModal').modal('hide');
                
                // Show loading
                Swal.fire({
                    title: 'Clearing Old Logs...',
                    text: 'Please wait while we delete old error logs.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Make AJAX request
                $.ajax({
                    url: '{{ route("admin.error-logs.clear-old") }}',
                    type: 'POST',
                    data: {
                        days: days
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message,
                            timer: 3000,
                            showConfirmButton: false
                        });

                        // Reload page after a delay
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    },
                    error: function(xhr) {
                        let message = 'An error occurred while clearing old logs.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: message,
                        });
                    }
                });
            }
        });
    });

    // Show success/error messages from session
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
</script>
@endpush
