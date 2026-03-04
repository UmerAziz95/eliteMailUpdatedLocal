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

    .btn-loading {
        position: relative;
        pointer-events: none;
    }
    .btn-loading i.fa-search {
        opacity: 0;
    }
    .btn-loading::after {
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

    .fade-in {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    #loadingIndicator {
        background-color: rgba(0, 0, 0, 0.1);
        border-radius: 12px;
        backdrop-filter: blur(10px);
    }

    .auto-refresh-enabled {
        background: linear-gradient(45deg, #28a745, #20c997) !important;
        border-color: #28a745 !important;
        animation: pulse-success 2s infinite;
    }

    @keyframes pulse-success {
        0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
    }

    /* Pagination Styling */
    .errorlogs-pagination {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        align-items: center;
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 14px;
        padding: 0.85rem 1.25rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }

    @media (min-width: 768px) {
        .errorlogs-pagination {
            flex-direction: row;
            justify-content: center;
        }
    }

    .pagination-pill {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 0.4rem 0.9rem;
        border-radius: 999px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.04);
        color: #cfd4ff;
        white-space: nowrap;
    }

    #paginationContainer .pagination {
        margin: 0;
        display: flex;
        gap: 0.45rem;
        list-style: none;
        padding: 0;
    }

    #paginationContainer .pagination .page-item {
        margin: 0;
    }

    #paginationContainer .pagination .page-link {
        background: linear-gradient(145deg, #26263d, #1c1c2f);
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: #d4dcff !important;
        padding: 0.55rem 0.75rem;
        border-radius: 10px;
        transition: all 0.2s ease;
        font-size: 0.9rem;
        text-decoration: none;
        display: grid;
        place-items: center;
        min-width: 44px;
        min-height: 44px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.35);
    }

    #paginationContainer .pagination .page-link:hover:not(.disabled) {
        background: linear-gradient(145deg, #2f3052, #24243c);
        border-color: rgba(255, 255, 255, 0.18);
        transform: translateY(-2px);
        box-shadow: 0 12px 26px rgba(0, 0, 0, 0.45);
    }

    #paginationContainer .pagination .page-item.active .page-link {
        background: linear-gradient(145deg, #1f8bff, #0f6bff);
        border-color: rgba(255, 255, 255, 0.18);
        color: #fff !important;
        box-shadow: 0 14px 30px rgba(15, 107, 255, 0.35);
        font-weight: 700;
    }

    #paginationContainer .pagination .page-item.disabled .page-link {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.04);
        color: rgba(255, 255, 255, 0.35) !important;
        cursor: not-allowed;
        pointer-events: none;
        box-shadow: none;
    }

    #paginationContainer {
        padding: 1rem 0;
    }

    #paginationContainer .d-flex {
        background: transparent;
        padding: 0;
        border-radius: 12px;
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
            <form id="filterForm" class="mb-0">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label text-white-50">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control bg-dark text-white border-secondary" value="{{ request('date_from') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-white-50">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control bg-dark text-white border-secondary" value="{{ request('date_to') }}">
                    </div>
                    <div class="col-md-3" style="display: none;">
                        <label class="form-label text-white-50">Severity</label>
                        <select name="severity" id="severity" class="form-control bg-dark text-white border-secondary">
                            <option value="">All Severities</option>
                            <option value="error" {{ request('severity') == 'error' ? 'selected' : '' }}>Error</option>
                            <option value="warning" {{ request('severity') == 'warning' ? 'selected' : '' }}>Warning</option>
                            <option value="info" {{ request('severity') == 'info' ? 'selected' : '' }}>Info</option>
                            <option value="debug" {{ request('severity') == 'debug' ? 'selected' : '' }}>Debug</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-white-50">Search</label>
                        <input type="text" name="search" id="search" class="form-control bg-dark text-white border-secondary" placeholder="Search..." value="{{ request('search') }}">
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-outline-light btn-sm" id="filterBtn">
                            <i class="fa fa-search"></i> Filter
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFiltersBtn">
                            <i class="fa fa-refresh"></i> Clear Filters
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" id="autoRefreshToggle">
                            <i class="fas fa-sync-alt"></i> <span id="autoRefreshText">Enable Auto Refresh</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Error Logs Grid -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="glass-box">
            <span class="text-white-50">Total: </span>
            <span class="text-white fw-bold" id="totalCount">{{ $errorLogs->total() }} logs</span>
        </div>
        <div class="glass-box">
            <input type="checkbox" id="selectAll" class="form-check-input me-2" onchange="toggleSelectAll()">
            <label for="selectAll" class="text-white-50">Select All</label>
        </div>
    </div>

    <!-- Loading indicator -->
    <div id="loadingIndicator" class="text-center py-4" style="display: none;">
        <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="text-white-50 mt-2">Loading error logs...</p>
    </div>

    <div id="errorLogsContainer">
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
                    </div>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div id="paginationContainer">
            @if($errorLogs->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $errorLogs->appends(request()->query())->links('admin.error-logs.pagination') }}
                </div>
            @endif
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

    // Auto refresh variables
    let autoRefreshInterval = null;
    let isAutoRefreshEnabled = false;
    const REFRESH_INTERVAL = 30000; // 30 seconds

    // Current filters state
    let currentFilters = {
        date_from: '{{ request('date_from') }}',
        date_to: '{{ request('date_to') }}',
        severity: '{{ request('severity') }}',
        search: '{{ request('search') }}',
        page: 1
    };

    $(document).ready(function() {
        // Initialize filter form handlers
        initializeFilters();
        
        // Initialize pagination handlers
        initializePagination();
        
        // Auto apply filters on input change with debounce
        let searchTimeout;
        $('#search').on('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                applyFilters();
            }, 500);
        });

        // Apply filters on date change
        $('#date_from, #date_to, #severity').on('change', function() {
            applyFilters();
        });
    });

    function initializeFilters() {
        // Handle filter form submission
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });

        // Handle clear filters button
        $('#clearFiltersBtn').on('click', function() {
            clearAllFilters();
        });

        // Handle auto refresh toggle
        $('#autoRefreshToggle').on('click', function() {
            toggleAutoRefresh();
        });
    }

    function initializePagination() {
        // Handle pagination clicks
        $(document).on('click', '#paginationContainer .pagination a', function(e) {
            e.preventDefault();
            const url = $(this).attr('href');
            const page = new URL(url).searchParams.get('page') || 1;
            currentFilters.page = page;
            loadErrorLogs(currentFilters);
        });
    }

    function applyFilters() {
        // Get current filter values
        currentFilters = {
            date_from: $('#date_from').val(),
            date_to: $('#date_to').val(),
            severity: $('#severity').val(),
            search: $('#search').val(),
            page: 1 // Reset to first page when applying filters
        };

        // Load error logs with new filters
        loadErrorLogs(currentFilters);

        // Update URL without page reload
        updateUrl(currentFilters);
    }

    function clearAllFilters() {
        // Clear form inputs
        $('#date_from, #date_to, #search').val('');
        $('#severity').val('');
        
        // Reset filters
        currentFilters = {
            date_from: '',
            date_to: '',
            severity: '',
            search: '',
            page: 1
        };

        // Load error logs without filters
        loadErrorLogs(currentFilters);

        // Update URL
        updateUrl(currentFilters);
    }

    function loadErrorLogs(filters = {}) {
        // Show loading indicator
        showLoading();

        // Prepare data for request
        const requestData = {};
        Object.keys(filters).forEach(key => {
            if (filters[key]) {
                requestData[key] = filters[key];
            }
        });

        $.ajax({
            url: '{{ route("admin.error-logs.index") }}',
            type: 'GET',
            data: requestData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                // Hide loading indicator
                hideLoading();

                // Update the error logs container
                $('#errorLogsContainer').html(response.html);

                // Update total count
                $('#totalCount').text(response.total + ' logs');

                // Reset checkboxes
                $('#selectAll').prop('checked', false);
                toggleBulkDelete();

                // Re-initialize pagination handlers
                initializePagination();

                // Show success message if no results
                if (response.total === 0) {
                    showNoResultsMessage();
                }
            },
            error: function(xhr) {
                hideLoading();
                
                let message = 'An error occurred while loading error logs.';
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

    function showLoading() {
        $('#loadingIndicator').show();
        $('#errorLogsContainer').hide();
        $('#filterBtn').addClass('btn-loading').prop('disabled', true);
        $('#filterBtn i').removeClass('fa-search').addClass('fa-spinner fa-spin');
    }

    function hideLoading() {
        $('#loadingIndicator').hide();
        $('#errorLogsContainer').show();
        $('#filterBtn').removeClass('btn-loading').prop('disabled', false);
        $('#filterBtn i').removeClass('fa-spinner fa-spin').addClass('fa-search');
    }

    function showNoResultsMessage() {
        const hasFilters = currentFilters.date_from || currentFilters.date_to || 
                          currentFilters.severity || currentFilters.search;
        
        let message = hasFilters ? 
            'No error logs match your current filters.' : 
            'No error logs found.';

        $('#errorLogsGrid').html(`
            <div class="col-12">
                <div class="empty-state">
                    <i class="fas fa-info-circle"></i>
                    <h5 class="text-white-50">No Error Logs Found</h5>
                    <p class="text-white-50">${message}</p>
                    ${hasFilters ? '<button class="btn btn-outline-light btn-sm" onclick="clearAllFilters()"><i class="fas fa-refresh"></i> Clear Filters</button>' : ''}
                </div>
            </div>
        `);
    }

    function updateUrl(filters) {
        const url = new URL(window.location);
        
        // Clear existing parameters
        url.searchParams.delete('date_from');
        url.searchParams.delete('date_to');
        url.searchParams.delete('severity');
        url.searchParams.delete('search');
        url.searchParams.delete('page');

        // Add new parameters
        Object.keys(filters).forEach(key => {
            if (filters[key] && key !== 'page') {
                url.searchParams.set(key, filters[key]);
            }
        });

        // Update browser history
        window.history.pushState({}, '', url);
    }

    function toggleAutoRefresh() {
        if (isAutoRefreshEnabled) {
            // Disable auto refresh
            clearInterval(autoRefreshInterval);
            isAutoRefreshEnabled = false;
            $('#autoRefreshText').text('Enable Auto Refresh');
            $('#autoRefreshToggle').removeClass('auto-refresh-enabled btn-outline-success').addClass('btn-outline-info');
        } else {
            // Enable auto refresh
            autoRefreshInterval = setInterval(function() {
                loadErrorLogs(currentFilters);
            }, REFRESH_INTERVAL);
            isAutoRefreshEnabled = true;
            $('#autoRefreshText').text('Disable Auto Refresh');
            $('#autoRefreshToggle').removeClass('btn-outline-info').addClass('btn-outline-success auto-refresh-enabled');
        }
    }

    function refreshErrorLogs() {
        loadErrorLogs(currentFilters);
    }

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
                                showNoResultsMessage();
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

                        // Reload the current page with filters
                        loadErrorLogs(currentFilters);
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

                        // Remove card from grid with animation
                        $(`#error-log-card-${errorId}`).fadeOut(300, function() {
                            $(this).remove();
                            
                            // Update total count
                            const currentTotal = parseInt($('#totalCount').text().replace(/\D/g, ''));
                            $('#totalCount').text((currentTotal - 1) + ' logs');
                            
                            // Check if grid is empty
                            if ($('#errorLogsGrid .col-lg-6:visible').length === 0) {
                                showNoResultsMessage();
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

                        // Reload the current page with filters
                        loadErrorLogs(currentFilters);
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
