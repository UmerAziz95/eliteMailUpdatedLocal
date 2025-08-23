@extends('admin.layouts.app')

@section('title', 'Log Viewer - ' . $logInfo['name'])

@push('styles')
<style>
    .log-header {
        background: linear-gradient(145deg, #2c2c54, #1a1a2e);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 1rem;
    }

    .log-content {
        background: #1a1a2e;
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        max-height: 70vh;
        overflow-y: auto;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        line-height: 1.4;
    }

    .log-line {
        padding: 0.25rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        color: #e9ecef;
    }

    .log-line:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }

    .log-line.error {
        background-color: rgba(220, 53, 69, 0.1);
        border-left: 3px solid #dc3545;
    }

    .log-line.warning {
        background-color: rgba(255, 193, 7, 0.1);
        border-left: 3px solid #ffc107;
    }

    .log-line.info {
        background-color: rgba(23, 162, 184, 0.1);
        border-left: 3px solid #17a2b8;
    }

    .log-line.debug {
        background-color: rgba(108, 117, 125, 0.1);
        border-left: 3px solid #6c757d;
    }

    .filter-card {
        background: linear-gradient(145deg, #2c2c54, #1a1a2e);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
    }

    .log-stats {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .stat-item {
        background: rgba(255, 255, 255, 0.05);
        padding: 0.5rem 1rem;
        border-radius: 8px;
        text-align: center;
    }

    .no-logs {
        text-align: center;
        padding: 3rem;
        color: #6c757d;
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="text-white mb-0">Log Viewer</h4>
            <small class="text-white-50">{{ $logInfo['name'] }}</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.logs.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Logs
            </a>
            <a href="{{ route('admin.logs.download', $logInfo['name']) }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-download me-1"></i> Download
            </a>
            <!-- <button type="button" class="btn btn-outline-warning btn-sm" onclick="clearLog('{{ $logInfo['name'] }}')">
                <i class="fas fa-eraser me-1"></i> Clear
            </button> -->
            <button type="button" class="btn btn-outline-light btn-sm" onclick="window.location.reload()">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Log Info -->
    <div class="log-header mb-4">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="log-stats">
                    <div class="stat-item">
                        <div class="text-white-50 small">File Size</div>
                        <div class="text-white fw-bold">{{ $logInfo['size'] }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="text-white-50 small">Total Lines</div>
                        <div class="text-white fw-bold">{{ number_format($logInfo['total_lines']) }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="text-white-50 small">Showing</div>
                        <div class="text-white fw-bold">{{ number_format($logInfo['showing_lines']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-item">
                    <div class="text-white-50 small">Last Modified</div>
                    <div class="text-white fw-bold">{{ $logInfo['modified'] }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card p-3 mb-4">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label text-white-50">Search</label>
                <input type="text" name="search" class="form-control bg-dark text-white border-secondary" 
                       placeholder="Search in logs..." value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <label class="form-label text-white-50">Lines to show</label>
                <select name="lines" class="form-control bg-dark text-white border-secondary">
                    <option value="50" {{ $lines == 50 ? 'selected' : '' }}>Last 50 lines</option>
                    <option value="100" {{ $lines == 100 ? 'selected' : '' }}>Last 100 lines</option>
                    <option value="200" {{ $lines == 200 ? 'selected' : '' }}>Last 200 lines</option>
                    <option value="500" {{ $lines == 500 ? 'selected' : '' }}>Last 500 lines</option>
                    <option value="1000" {{ $lines == 1000 ? 'selected' : '' }}>Last 1000 lines</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
                <a href="{{ route('admin.logs.show', $logInfo['name']) }}" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-1"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Log Content -->
    @if(count($logLines) > 0)
        <div class="log-content">
            @foreach($logLines as $index => $line)
                @if(trim($line))
                    @php
                        $lineClass = '';
                        $line_lower = strtolower($line);
                        if (str_contains($line_lower, 'error') || str_contains($line_lower, 'exception') || str_contains($line_lower, 'fatal')) {
                            $lineClass = 'error';
                        } elseif (str_contains($line_lower, 'warning') || str_contains($line_lower, 'warn')) {
                            $lineClass = 'warning';
                        } elseif (str_contains($line_lower, 'info')) {
                            $lineClass = 'info';
                        } elseif (str_contains($line_lower, 'debug')) {
                            $lineClass = 'debug';
                        }
                    @endphp
                    <div class="log-line {{ $lineClass }}">{{ $line }}</div>
                @endif
            @endforeach
        </div>
    @else
        <div class="no-logs">
            <i class="fas fa-search fa-3x mb-3 opacity-50"></i>
            <h5 class="text-white-50">No Log Entries Found</h5>
            <p class="text-white-50">
                @if($search)
                    No log entries match your search criteria "{{ $search }}".
                @else
                    The log file appears to be empty or contains no readable content.
                @endif
            </p>
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

    // Auto-scroll to bottom on page load
    document.addEventListener('DOMContentLoaded', function() {
        const logContent = document.querySelector('.log-content');
        if (logContent) {
            logContent.scrollTop = logContent.scrollHeight;
        }
    });
</script>
@endpush
