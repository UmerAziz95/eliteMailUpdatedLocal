@extends('admin.layouts.app')

@section('title', 'System Logs')

@push('styles')
<style>
    .log-card {
        background: linear-gradient(145deg, #2c2c54, #1a1a2e);
        border: 1px solid rgba(255, 255, 255, 0.1);
        transition: all 0.3s ease;
    }

    .log-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        border-color: rgba(255, 255, 255, 0.2);
    }

    .file-icon {
        background: linear-gradient(45deg, #17a2b8, #138496);
        border-radius: 8px;
        padding: 0.5rem;
        margin-right: 1rem;
    }

    .file-size {
        background: rgba(255, 255, 255, 0.1);
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
    }

    .btn-action {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        border-radius: 4px;
    }

    .empty-state {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="text-white mb-0">System Logs</h4>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-light btn-sm" onclick="window.location.reload()">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Logs Grid -->
    @if(count($logs) > 0)
        <div class="row g-3">
            @foreach($logs as $log)
                <div class="col-lg-6 col-xl-4">
                    <div class="card log-card h-100">
                        <div class="card-body">
                            <!-- Header -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="file-icon">
                                    <i class="fas fa-file-alt text-white"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="text-white mb-1 text-truncate" title="{{ $log['name'] }}">{{ Str::limit($log['name'], 30) }}</h6>
                                    <small class="text-white-50">{{ $log['modified_diff'] }}</small>
                                </div>
                            </div>

                            <!-- File Info -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-white-50 small">Size:</span>
                                    <span class="file-size text-white">{{ $log['size'] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-white-50 small">Modified:</span>
                                    <span class="text-white small">{{ $log['modified'] }}</span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-flex gap-2">
                                <a href="{{ route('admin.logs.show', basename($log['name'])) }}" 
                                   class="btn btn-primary btn-action flex-fill">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <a href="{{ route('admin.logs.download', basename($log['name'])) }}" 
                                   class="btn btn-outline-success btn-action">
                                    <i class="fas fa-download"></i>
                                </a>
                                <!-- <button type="button" class="btn btn-outline-warning btn-action" 
                                        onclick="clearLog('{{ basename($log['name']) }}')">
                                    <i class="fas fa-eraser"></i>
                                </button> -->
                                <!-- <button type="button" class="btn btn-outline-danger btn-action" 
                                        onclick="deleteLog('{{ basename($log['name']) }}')">
                                    <i class="fas fa-trash"></i>
                                </button> -->
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <i class="fas fa-file-alt"></i>
            <h5 class="text-white-50">No Log Files Found</h5>
            <p class="text-white-50">No log files are currently available in the storage/logs directory.</p>
        </div>
    @endif
</section>
@endsection

@push('scripts')
<script>
    function clearLog(filename) {
        Swal.fire({
            title: 'Clear Log File',
            text: `Are you sure you want to clear the contents of ${filename}? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, clear it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Clearing...',
                    text: 'Please wait while we clear the log file.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Make AJAX request
                fetch(`/admin/logs/${filename}/clear`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cleared!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Reload page after a delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while clearing the log file: ' + error.message,
                    });
                });
            }
        });
    }

    function deleteLog(filename) {
        Swal.fire({
            title: 'Delete Log File',
            text: `Are you sure you want to permanently delete ${filename}? This action cannot be undone.`,
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
                    text: 'Please wait while we delete the log file.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Make AJAX request
                fetch(`/admin/logs/${filename}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                        
                        // Reload page after a delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        throw new Error(data.message || 'Unknown error');
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while deleting the log file: ' + error.message,
                    });
                });
            }
        });
    }

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
