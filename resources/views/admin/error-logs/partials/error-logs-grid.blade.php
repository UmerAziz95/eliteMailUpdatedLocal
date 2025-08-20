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
            {{ $errorLogs->appends(request()->query())->links() }}
        </div>
    @endif
</div>
