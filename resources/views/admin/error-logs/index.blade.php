@extends('admin.layouts.app')

@section('title', 'Error Logs')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />
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
    .trace-preview {
        max-height: 100px;
        overflow-y: auto;
        font-family: monospace;
        font-size: 0.85rem;
        background-color: #f8f9fa;
        padding: 0.5rem;
        border-radius: 0.25rem;
    }
    .error-message {
        word-break: break-word;
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
</style>
@endpush

@section('content')
<section class="py-3">
    <div class="row gy-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Error Logs</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#clearOldModal">
                            <i class="fa fa-trash"></i> Clear Old Logs
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="bulkDelete()" id="bulkDeleteBtn" style="display: none;">
                            <i class="fa fa-trash"></i> Delete Selected
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('admin.error-logs.index') }}" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Severity</label>
                                <select name="severity" class="form-select">
                                    <option value="">All Severities</option>
                                    @foreach($severityOptions as $severity)
                                        <option value="{{ $severity }}" {{ request('severity') == $severity ? 'selected' : '' }}>
                                            {{ ucfirst($severity) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Search in message, exception, file..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Filter
                                </button>
                                <a href="{{ route('admin.error-logs.index') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-refresh"></i> Clear Filters
                                </a>
                            </div>
                        </div>
                    </form>

                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Error Logs Table -->
                    <div class="table-responsive">
                        <form id="bulkDeleteForm" method="POST" action="{{ route('admin.error-logs.bulk-delete') }}">
                            @csrf
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                        </th>
                                        <th>ID</th>
                                        <th>Date/Time</th>
                                        <th>Severity</th>
                                        <th>Exception</th>
                                        <th>Message</th>
                                        <th>File</th>
                                        <th>URL</th>
                                        <th>User</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="errorLogsTableBody">
                                    @forelse($errorLogs as $log)
                                        <tr id="error-log-row-{{ $log->id }}">
                                            <td>
                                                <input type="checkbox" name="error_log_ids[]" value="{{ $log->id }}" class="error-log-checkbox" onchange="toggleBulkDelete()">
                                            </td>
                                            <td>{{ $log->id }}</td>
                                            <td>
                                                <small>
                                                    {{ $log->created_at->format('Y-m-d') }}<br>
                                                    {{ $log->created_at->format('H:i:s') }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="error-severity severity-{{ $log->severity }}">
                                                    {{ ucfirst($log->severity) }}
                                                </span>
                                            </td>
                                            <td>
                                                <code>{{ class_basename($log->exception_class) }}</code>
                                            </td>
                                            <td>
                                                <div class="error-message" style="max-width: 300px;">
                                                    {{ Str::limit($log->message, 100) }}
                                                </div>
                                            </td>
                                            <td>
                                                <small>
                                                    {{ basename($log->file) }}:{{ $log->line }}
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    {{ $log->url ? Str::limit($log->url, 40) : '-' }}
                                                </small>
                                            </td>
                                            <td>
                                                {{ $log->user ? $log->user->name : 'Guest' }}
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="{{ route('admin.error-logs.show', $log) }}" class="btn btn-sm btn-outline-primary" title="View Details">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger delete-error-log" 
                                                            data-id="{{ $log->id }}" 
                                                            data-url="{{ route('admin.error-logs.destroy', $log) }}" 
                                                            title="Delete">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center py-4">
                                                <div class="text-muted">
                                                    <i class="fa fa-info-circle fa-3x mb-3"></i>
                                                    <p>No error logs found.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </form>
                    </div>

                    <!-- Pagination -->
                    {{ $errorLogs->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
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

                        // Remove deleted rows from table
                        selectedIds.forEach(id => {
                            $(`#error-log-row-${id}`).fadeOut(300, function() {
                                $(this).remove();
                            });
                        });

                        // Reset checkboxes
                        document.getElementById('selectAll').checked = false;
                        toggleBulkDelete();

                        // Check if table is empty after deletion
                        setTimeout(() => {
                            if ($('#errorLogsTableBody tr:visible').length === 0) {
                                // Show no data message
                                $('#errorLogsTableBody').html(`
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fa fa-info-circle fa-3x mb-3"></i>
                                                <p>No error logs found.</p>
                                            </div>
                                        </td>
                                    </tr>
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

                        // Remove row from table
                        $(`#error-log-row-${errorId}`).fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if table is empty
                            if ($('#errorLogsTableBody tr:visible').length === 0) {
                                // Show no data message
                                $('#errorLogsTableBody').html(`
                                    <tr>
                                        <td colspan="10" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fa fa-info-circle fa-3x mb-3"></i>
                                                <p>No error logs found.</p>
                                            </div>
                                        </td>
                                    </tr>
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
