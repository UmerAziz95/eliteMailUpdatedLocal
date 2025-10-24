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
            <button type="button" class="btn btn-outline-light btn-sm" id="refresh-log-btn" onclick="window.location.reload()">
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
                        <div class="text-white fw-bold" id="log-file-size">{{ $logInfo['size'] ?? '--' }}</div>
                    </div>
                    <div class="stat-item">
                        <div class="text-white-50 small">Total Lines</div>
                        <div class="text-white fw-bold" id="log-total-lines">
                            {{ is_numeric($logInfo['total_lines']) ? number_format($logInfo['total_lines']) : ($logInfo['total_lines'] ?? '--') }}
                        </div>
                    </div>
                    <div class="stat-item">
                        <div class="text-white-50 small">Showing</div>
                        <div class="text-white fw-bold" id="log-showing-lines">{{ number_format($logInfo['showing_lines']) }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-item">
                    <div class="text-white-50 small">Last Modified</div>
                    <div class="text-white fw-bold" id="log-last-modified">{{ $logInfo['modified'] }}</div>
                </div>
                <div class="text-white-50 small mt-2 {{ !empty($logInfo['is_large_file']) ? '' : 'd-none' }}" id="large-file-notice">
                    Large file detected. Showing the last <span id="large-file-showing-count">{{ number_format($logInfo['showing_lines']) }}</span> lines for performance.
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card p-3 mb-4">
        <form method="GET" class="row g-3 align-items-end" id="log-filter-form" data-default-lines="{{ $lines }}">
            <div class="col-md-4">
                <label class="form-label text-white-50">Search</label>
                <input type="text" name="search" id="search-input" class="form-control bg-dark text-white border-secondary" 
                       placeholder="Search in logs..." value="{{ $search }}">
            </div>
            <div class="col-md-3">
                <label class="form-label text-white-50">Lines to show</label>
                <select name="lines" id="lines-select" class="form-control bg-dark text-white border-secondary">
                    @foreach($lineOptions as $option)
                        <option value="{{ $option }}" {{ $lines == $option ? 'selected' : '' }}>
                            Last {{ number_format($option) }} lines
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-1"></i> Filter
                </button>
                <a href="{{ route('admin.logs.show', $logInfo['name']) }}" class="btn btn-outline-secondary" id="clear-filters-btn">
                    <i class="fas fa-times me-1"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Log Content -->
    <div class="log-content" id="log-lines-container">
        <div class="no-logs" id="log-loading-state">
            <i class="fas fa-sync fa-spin fa-2x mb-3 opacity-50"></i>
            <h5 class="text-white-50">Loading Logs</h5>
            <p class="text-white-50 mb-0">Fetching the latest entries...</p>
        </div>
    </div>
    <noscript>
        <div class="no-logs mt-3">
            <i class="fas fa-exclamation-triangle fa-2x mb-3 opacity-50"></i>
            <h5 class="text-white-50">JavaScript Required</h5>
            <p class="text-white-50 mb-0">Enable JavaScript in your browser to view the log entries.</p>
        </div>
    </noscript>
</section>
@endsection

@push('scripts')
<script>
    (function () {
        let loaderVisible = false;
        let form;
        let searchInput;
        let linesSelect;
        let filterButton;
        let logContainer;
        let fileSizeEl;
        let totalLinesEl;
        let showingLinesEl;
        let lastModifiedEl;
        let largeFileNotice;
        let largeFileCount;

        function initElements() {
            form = document.getElementById('log-filter-form');
            if (!form) {
                return false;
            }

            searchInput = form.querySelector('input[name="search"]');
            linesSelect = form.querySelector('select[name="lines"]');
            logContainer = document.getElementById('log-lines-container');
            fileSizeEl = document.getElementById('log-file-size');
            totalLinesEl = document.getElementById('log-total-lines');
            showingLinesEl = document.getElementById('log-showing-lines');
            lastModifiedEl = document.getElementById('log-last-modified');
            largeFileNotice = document.getElementById('large-file-notice');
            largeFileCount = document.getElementById('large-file-showing-count');
            filterButton = form.querySelector('button[type="submit"]');

            return true;
        }

        function setFilterButtonState(isLoading) {
            if (!filterButton) {
                return;
            }
            if (isLoading) {
                filterButton.classList.add('btn-loading');
                filterButton.disabled = true;
            } else {
                filterButton.classList.remove('btn-loading');
                filterButton.disabled = false;
            }
        }

        function showLoader() {
            if (typeof Swal !== 'undefined') {
                loaderVisible = true;
                Swal.fire({
                    title: 'Loading log',
                    html: 'Please wait...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            } else if (logContainer) {
                logContainer.innerHTML = '<div class="no-logs"><span class="text-white-50">Loading...</span></div>';
            }
        }

        function hideLoader() {
            if (loaderVisible && typeof Swal !== 'undefined') {
                Swal.close();
            }
            loaderVisible = false;
        }

        function formatNumber(value) {
            if (value === null || typeof value === 'undefined') {
                return '--';
            }
            if (typeof value === 'number') {
                return value.toLocaleString();
            }
            const numeric = Number(value);
            if (!Number.isNaN(numeric)) {
                return numeric.toLocaleString();
            }
            return value;
        }

        function classifyLine(line) {
            const lower = line.toLowerCase();
            if (lower.includes('error') || lower.includes('exception') || lower.includes('fatal')) {
                return 'error';
            }
            if (lower.includes('warning') || lower.includes('warn')) {
                return 'warning';
            }
            if (lower.includes('info')) {
                return 'info';
            }
            if (lower.includes('debug')) {
                return 'debug';
            }
            return '';
        }

        function renderLines(lines) {
            if (!logContainer) {
                return;
            }

            logContainer.innerHTML = '';

            if (!lines || lines.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'no-logs';
                const searchText = searchInput && searchInput.value
                    ? `No log entries match your search criteria "${searchInput.value}".`
                    : 'The log file appears to be empty or contains no readable content.';
                empty.innerHTML = `
                    <i class="fas fa-search fa-3x mb-3 opacity-50"></i>
                    <h5 class="text-white-50">No Log Entries Found</h5>
                    <p class="text-white-50">${searchText}</p>
                `;
                logContainer.appendChild(empty);
                return;
            }

            lines.forEach((line) => {
                if (!line || line.trim() === '') {
                    return;
                }

                const lineClass = classifyLine(line);
                const div = document.createElement('div');
                div.className = `log-line${lineClass ? ` ${lineClass}` : ''}`;
                div.textContent = line;
                logContainer.appendChild(div);
            });

            logContainer.scrollTop = logContainer.scrollHeight;
        }

        function updateStats(logInfo) {
            if (!logInfo) {
                return;
            }

            if (fileSizeEl) {
                fileSizeEl.textContent = logInfo.size || '--';
            }
            if (totalLinesEl) {
                totalLinesEl.textContent = formatNumber(logInfo.total_lines);
            }
            if (showingLinesEl) {
                showingLinesEl.textContent = formatNumber(logInfo.showing_lines);
            }
            if (lastModifiedEl) {
                lastModifiedEl.textContent = logInfo.modified || '--';
            }
            if (largeFileNotice && largeFileCount) {
                if (logInfo.is_large_file) {
                    largeFileNotice.classList.remove('d-none');
                    largeFileCount.textContent = formatNumber(logInfo.showing_lines);
                } else {
                    largeFileNotice.classList.add('d-none');
                }
            }
        }

        function buildRequestUrl() {
            const url = new URL(window.location.href);
            if (linesSelect) {
                url.searchParams.set('lines', linesSelect.value);
            }
            if (searchInput && searchInput.value) {
                url.searchParams.set('search', searchInput.value);
            } else {
                url.searchParams.delete('search');
            }
            return url;
        }

        function fetchLogs(pushState = true) {
            if (!form) {
                return;
            }

            const requestUrl = buildRequestUrl();
            setFilterButtonState(true);

            if (pushState) {
                window.history.replaceState({}, '', requestUrl.toString());
            }

            showLoader();

            fetch(requestUrl.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error('Unable to load logs');
                    }
                    return response.json();
                })
                .then((data) => {
                    hideLoader();
                    if (!data.success) {
                        throw new Error(data.message || 'Unable to load logs');
                    }
                    renderLines(data.log_lines || []);
                    updateStats(data.log_info || {});
                })
                .catch((error) => {
                    hideLoader();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error loading logs',
                            text: error.message || 'Unexpected error occurred'
                        });
                    } else {
                        alert(error.message || 'Unexpected error occurred');
                    }
                })
                .finally(() => {
                    setFilterButtonState(false);
                });
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (!initElements()) {
                return;
            }

            const refreshBtn = document.getElementById('refresh-log-btn');
            const clearBtn = document.getElementById('clear-filters-btn');

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                fetchLogs();
            });

            if (linesSelect) {
                linesSelect.addEventListener('change', function () {
                    fetchLogs();
                });
            }

            if (refreshBtn) {
                refreshBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    fetchLogs(false);
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    if (searchInput) {
                        searchInput.value = '';
                    }
                    const defaultLines = form.getAttribute('data-default-lines');
                    if (defaultLines && linesSelect && linesSelect.querySelector(`option[value="${defaultLines}"]`)) {
                        linesSelect.value = defaultLines;
                    }
                    fetchLogs();
                });
            }

            fetchLogs(false);
        });

        window.clearLog = function (filename) {
            if (typeof Swal === 'undefined') {
                return;
            }

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
                if (!result.isConfirmed) {
                    return;
                }

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

                fetch(`/admin/logs/${filename}/clear`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then((response) => response.json())
                    .then((data) => {
                        if (!data.success) {
                            throw new Error(data.message || 'Unknown error');
                        }

                        Swal.fire({
                            icon: 'success',
                            title: 'Cleared!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });

                        setTimeout(() => {
                            fetchLogs();
                        }, 2000);
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An error occurred while clearing the log file: ' + error.message
                        });
                    });
            });
        };
    })();
</script>
@endpush
