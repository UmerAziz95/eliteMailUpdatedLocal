@extends('admin.layouts.app')
@section('title', 'Log Viewer - ' . $logInfo['name'])
<!--  -->
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
        padding: 0;
    }

    .log-lines {
        max-height: 70vh;
        overflow-y: auto;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        line-height: 1.4;
        border-radius: inherit;
        scroll-behavior: smooth;
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

    .log-render-status {
        position: sticky;
        top: 0;
        z-index: 1;
        background: rgba(26, 26, 46, 0.95);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        backdrop-filter: blur(4px);
        color: #adb5bd;
        font-size: 0.8rem;
        padding: 0.35rem 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .log-render-status .status-text {
        flex: 1 1 auto;
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

    .log-quick-search {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 8px;
        padding: 0.75rem 1rem;
    }

    .log-quick-search .input-group-text,
    .log-quick-search .form-control,
    .log-quick-search .btn {
        border-color: rgba(255, 255, 255, 0.12);
    }

    .log-quick-search .input-group-text {
        background: rgba(26, 26, 46, 0.8);
        color: #f8f9fa;
    }

    .log-quick-search .form-control {
        background: rgba(17, 17, 32, 0.9);
        color: #f8f9fa;
    }

    .log-quick-search .form-control::placeholder {
        color: rgba(248, 249, 250, 0.5);
    }

    .log-quick-search .btn {
        background: rgba(26, 26, 46, 0.7);
        color: #f8f9fa;
    }

    .log-line-match mark.log-highlight {
        background: rgba(255, 193, 7, 0.3);
        color: #fff;
        padding: 0 0.15rem;
        border-radius: 3px;
    }

    mark.log-highlight-active {
        background: #ffc107;
        color: #212529;
    }

    .log-line-active-match {
        box-shadow: inset 0 0 0 1px rgba(255, 193, 7, 0.6);
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

    <!-- Client-Side Quick Search -->
    <div class="log-quick-search mb-3">
        <label class="form-label text-white-50 mb-2" for="log-client-search-input">
            Quick find in current results (highlights without reloading)
        </label>
        <div class="input-group input-group-sm">
            <span class="input-group-text">
                <i class="fas fa-highlighter"></i>
            </span>
            <input type="text" id="log-client-search-input" class="form-control border-secondary"
                   placeholder="Type to highlight within the loaded log lines..." autocomplete="off">
            <button class="btn btn-outline-secondary" type="button" id="log-client-search-prev" title="Previous match" disabled>
                <i class="fas fa-chevron-up"></i>
            </button>
            <button class="btn btn-outline-secondary" type="button" id="log-client-search-next" title="Next match" disabled>
                <i class="fas fa-chevron-down"></i>
            </button>
            <button class="btn btn-outline-secondary" type="button" id="log-client-search-clear" title="Clear quick search" disabled>
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="text-white-50 small mt-2" id="log-client-search-status">
            Client-side search highlights within loaded lines. Use Enter to jump between matches.
        </div>
    </div>

    <!-- Log Content -->
    <div class="log-content">
        <div class="log-lines" id="log-lines-container">
            <div class="no-logs" id="log-loading-state">
                <i class="fas fa-sync fa-spin fa-2x mb-3 opacity-50"></i>
                <h5 class="text-white-50">Loading Logs</h5>
                <p class="text-white-50 mb-0">Fetching the latest entries...</p>
            </div>
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
        let clientSearchInput;
        let clientSearchStatus;
        let clientSearchNextBtn;
        let clientSearchPrevBtn;
        let clientSearchClearBtn;
        let fileSizeEl;
        let totalLinesEl;
        let showingLinesEl;
        let lastModifiedEl;
        let largeFileNotice;
        let largeFileCount;
        let clientSearchTerm = '';
        let clientSearchTermLower = '';
        let clientSearchIndex = -1;
        let clientSearchProcessing = false;
        let clientSearchProcessingToken = 0;
        const LARGE_RENDER_THRESHOLD = 20000;
        const MAX_RENDER_LINES = 500000;
        const CHUNK_MIN_SIZE = 750;
        const CHUNK_MAX_SIZE = 4000;
        const CLIENT_SEARCH_HELP_TEXT = 'Client-side search highlights within loaded lines. Use Enter to jump between matches.';
        const CLIENT_SEARCH_CHUNK_THRESHOLD = 6000;
        const CLIENT_SEARCH_CHUNK_SIZE = 600;

        function initElements() {
            form = document.getElementById('log-filter-form');
            if (!form) {
                return false;
            }

            searchInput = form.querySelector('input[name="search"]');
            linesSelect = form.querySelector('select[name="lines"]');
            logContainer = document.getElementById('log-lines-container');
            clientSearchInput = document.getElementById('log-client-search-input');
            clientSearchStatus = document.getElementById('log-client-search-status');
            clientSearchNextBtn = document.getElementById('log-client-search-next');
            clientSearchPrevBtn = document.getElementById('log-client-search-prev');
            clientSearchClearBtn = document.getElementById('log-client-search-clear');
            fileSizeEl = document.getElementById('log-file-size');
            totalLinesEl = document.getElementById('log-total-lines');
            showingLinesEl = document.getElementById('log-showing-lines');
            lastModifiedEl = document.getElementById('log-last-modified');
            largeFileNotice = document.getElementById('large-file-notice');
            largeFileCount = document.getElementById('large-file-showing-count');
            filterButton = form.querySelector('button[type="submit"]');

            setClientSearchStatusDefault();

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

        function setClientSearchStatus(message) {
            if (clientSearchStatus) {
                clientSearchStatus.textContent = message;
            }
        }

        function setClientSearchControlsState(totalMatches, options = {}) {
            const { forceDisable = false } = options;
            const hasTerm = clientSearchTerm.length > 0;
            const hasMatches = hasTerm && totalMatches > 0;
            const disableNav = forceDisable || !hasMatches;

            if (clientSearchNextBtn) {
                clientSearchNextBtn.disabled = disableNav;
            }
            if (clientSearchPrevBtn) {
                clientSearchPrevBtn.disabled = disableNav;
            }
            if (clientSearchClearBtn) {
                clientSearchClearBtn.disabled = forceDisable || !hasTerm;
            }
        }

        function setClientSearchStatusDefault() {
            setClientSearchStatus(CLIENT_SEARCH_HELP_TEXT);
            setClientSearchControlsState(0);
        }

        function getClientSearchMarks() {
            if (!logContainer) {
                return [];
            }
            return Array.from(logContainer.querySelectorAll('mark.log-highlight'));
        }

        function clearClientSearchFocus(marks = null) {
            const activeMarks = marks || getClientSearchMarks();
            activeMarks.forEach((mark) => {
                mark.classList.remove('log-highlight-active');
            });

            if (logContainer) {
                logContainer
                    .querySelectorAll('.log-line-active-match')
                    .forEach((line) => line.classList.remove('log-line-active-match'));
            }

            clientSearchIndex = -1;
        }

        function applyHighlight(element) {
            const stored = typeof element.dataset.rawLine === 'string'
                ? element.dataset.rawLine
                : element.textContent || '';
            element.dataset.rawLine = stored;

            if (!clientSearchTerm) {
                element.textContent = stored;
                element.classList.remove('log-line-match', 'log-line-active-match');
                return 0;
            }

            const lowerSource = stored.toLowerCase();
            const termLength = clientSearchTerm.length;
            const lowerTerm = clientSearchTermLower;

            if (!lowerTerm || !lowerSource.includes(lowerTerm)) {
                element.textContent = stored;
                element.classList.remove('log-line-match', 'log-line-active-match');
                return 0;
            }

            let index = lowerSource.indexOf(lowerTerm);
            let lastIndex = 0;
            let matchCount = 0;
            const fragment = document.createDocumentFragment();

            while (index !== -1) {
                if (index > lastIndex) {
                    fragment.append(stored.slice(lastIndex, index));
                }

                const mark = document.createElement('mark');
                mark.className = 'log-highlight';
                mark.textContent = stored.slice(index, index + termLength);
                fragment.append(mark);

                lastIndex = index + termLength;
                matchCount += 1;
                index = lowerSource.indexOf(lowerTerm, lastIndex);
            }

            if (lastIndex < stored.length) {
                fragment.append(stored.slice(lastIndex));
            }

            element.innerHTML = '';
            element.appendChild(fragment);
            element.classList.add('log-line-match');
            element.classList.remove('log-line-active-match');

            return matchCount;
        }

        function restoreLineContent(element) {
            if (!element) {
                return 0;
            }

            const stored = typeof element.dataset.rawLine === 'string'
                ? element.dataset.rawLine
                : element.textContent || '';

            element.dataset.rawLine = stored;
            element.textContent = stored;
            element.classList.remove('log-line-match', 'log-line-active-match');

            return 0;
        }

        function processClientSearchLines(options = {}) {
            const {
                logLines = null,
                totalLines = 0,
                processLine = () => 0,
                onComplete = null,
                processingText = '',
                token = 0,
            } = options;

            if (!logLines || totalLines === 0) {
                clientSearchProcessing = false;
                if (typeof onComplete === 'function') {
                    onComplete({ totalMatches: 0, totalLines: 0 });
                }
                return;
            }

            const useChunks = totalLines > CLIENT_SEARCH_CHUNK_THRESHOLD;
            const batchSize = useChunks ? CLIENT_SEARCH_CHUNK_SIZE : totalLines;
            let processed = 0;
            let totalMatches = 0;

            clientSearchProcessing = true;

            if (useChunks && processingText) {
                setClientSearchStatus(`${processingText} (0 / ${formatNumber(totalLines)})`);
            }

            function processBatch() {
                if (token !== clientSearchProcessingToken) {
                    return;
                }

                const end = Math.min(processed + batchSize, totalLines);

                for (let index = processed; index < end; index++) {
                    const line = logLines[index];
                    if (!line) {
                        continue;
                    }
                    totalMatches += processLine(line) || 0;
                }

                processed = end;

                if (useChunks && processingText) {
                    setClientSearchStatus(`${processingText} (${formatNumber(processed)} / ${formatNumber(totalLines)})`);
                }

                if (processed < totalLines) {
                    requestAnimationFrame(processBatch);
                    return;
                }

                clientSearchProcessing = false;

                if (typeof onComplete === 'function') {
                    onComplete({ totalMatches, totalLines });
                }
            }

            requestAnimationFrame(processBatch);
        }

        function createLineElement(line) {
            if (!line || line.trim() === '') {
                return null;
            }

            const lineClass = classifyLine(line);
            const div = document.createElement('div');
            div.className = `log-line${lineClass ? ` ${lineClass}` : ''}`;
            div.dataset.rawLine = line;
            if (clientSearchTerm) {
                applyHighlight(div);
            } else {
                div.textContent = line;
            }

            return div;
        }

        function focusClientMatch(index, marks = null, options = {}) {
            const { scroll = true } = options;
            const matchElements = marks || getClientSearchMarks();

            if (!matchElements.length) {
                clientSearchIndex = -1;
                updateClientSearchStatus(0);
                return;
            }

            const normalizedIndex = ((index % matchElements.length) + matchElements.length) % matchElements.length;
            clientSearchIndex = normalizedIndex;

            matchElements.forEach((mark, idx) => {
                const isActive = idx === normalizedIndex;
                mark.classList.toggle('log-highlight-active', isActive);
                const line = mark.closest('.log-line');
                if (line) {
                    line.classList.toggle('log-line-active-match', isActive);
                }
            });

            if (scroll && logContainer) {
                const targetMark = matchElements[normalizedIndex];
                const lineEl = targetMark.closest('.log-line');
                if (lineEl) {
                    const targetOffset = Math.max(
                        lineEl.offsetTop - (logContainer.clientHeight / 2),
                        0,
                    );
                    if (typeof logContainer.scrollTo === 'function') {
                        logContainer.scrollTo({
                            top: targetOffset,
                            behavior: 'smooth',
                        });
                    } else {
                        logContainer.scrollTop = targetOffset;
                    }
                } else {
                    targetMark.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }

            updateClientSearchStatus(matchElements.length);
        }

        function focusNextClientMatch() {
            if (!clientSearchTerm || clientSearchProcessing) {
                return;
            }

            const marks = getClientSearchMarks();
            if (!marks.length) {
                updateClientSearchStatus(0);
                return;
            }

            const nextIndex = clientSearchIndex + 1;
            focusClientMatch(nextIndex, marks);
        }

        function focusPreviousClientMatch() {
            if (!clientSearchTerm || clientSearchProcessing) {
                return;
            }

            const marks = getClientSearchMarks();
            if (!marks.length) {
                updateClientSearchStatus(0);
                return;
            }

            const previousIndex = clientSearchIndex === -1 ? marks.length - 1 : clientSearchIndex - 1;
            focusClientMatch(previousIndex, marks);
        }

        function updateClientSearchStatus(totalMatches) {
            if (!clientSearchStatus) {
                return;
            }

            if (!clientSearchTerm) {
                setClientSearchStatusDefault();
                return;
            }

            if (!totalMatches) {
                setClientSearchStatus(`No matches for "${clientSearchTerm}".`);
                setClientSearchControlsState(0);
                return;
            }

            const current = clientSearchIndex >= 0 ? clientSearchIndex + 1 : 1;
            setClientSearchStatus(`Match ${current} of ${totalMatches} for "${clientSearchTerm}".`);
            setClientSearchControlsState(totalMatches);
        }

        function applyClientSearch(term, options = {}) {
            const { focusFirst = true } = options;
            clientSearchTerm = term ? term.trim() : '';
            clientSearchTermLower = clientSearchTerm.toLowerCase();

            if (!logContainer) {
                return;
            }

            clientSearchProcessingToken += 1;
            const processingToken = clientSearchProcessingToken;

            clearClientSearchFocus();

            const logLines = logContainer.querySelectorAll('.log-line');
            const totalLines = logLines.length;

            if (!clientSearchTerm) {
                if (!totalLines) {
                    setClientSearchStatusDefault();
                    return;
                }

                setClientSearchStatus(
                    totalLines > CLIENT_SEARCH_CHUNK_THRESHOLD
                        ? 'Clearing highlights...'
                        : CLIENT_SEARCH_HELP_TEXT,
                );
                setClientSearchControlsState(0, { forceDisable: true });

                processClientSearchLines({
                    logLines,
                    totalLines,
                    processLine: restoreLineContent,
                    onComplete: () => {
                        setClientSearchStatusDefault();
                    },
                    processingText: 'Clearing highlights...',
                    token: processingToken,
                });

                return;
            }

            if (!totalLines) {
                setClientSearchStatus(`No log entries loaded to search for "${clientSearchTerm}".`);
                setClientSearchControlsState(0);
                return;
            }

            setClientSearchStatus(
                totalLines > CLIENT_SEARCH_CHUNK_THRESHOLD
                    ? `Highlighting matches for "${clientSearchTerm}"...`
                    : `Searching for "${clientSearchTerm}"...`,
            );
            setClientSearchControlsState(0, { forceDisable: true });

            processClientSearchLines({
                logLines,
                totalLines,
                processLine: (line) => {
                    const matches = applyHighlight(line);
                    line.classList.toggle('log-line-match', matches > 0);
                    return matches;
                },
                onComplete: ({ totalMatches }) => {
                    if (!clientSearchTerm) {
                        setClientSearchStatusDefault();
                        return;
                    }

                    if (!totalMatches) {
                        updateClientSearchStatus(0);
                        return;
                    }

                    const marks = getClientSearchMarks();
                    if (!marks.length) {
                        updateClientSearchStatus(0);
                        return;
                    }

                    const existingIndex = clientSearchIndex >= 0 ? clientSearchIndex : 0;
                    const targetIndex = focusFirst
                        ? 0
                        : Math.min(existingIndex, marks.length - 1);

                    focusClientMatch(targetIndex, marks, { scroll: focusFirst });
                },
                processingText: `Highlighting matches for "${clientSearchTerm}"...`,
                token: processingToken,
            });
        }

        function refreshClientSearchStatus(options = {}) {
            const {
                preserveIndex = true,
                ensureFocus = false,
            } = options;

            if (clientSearchProcessing) {
                return;
            }

            if (!clientSearchTerm) {
                setClientSearchStatusDefault();
                return;
            }

            const marks = getClientSearchMarks();
            if (!marks.length) {
                updateClientSearchStatus(0);
                return;
            }

            if (preserveIndex && clientSearchIndex >= 0) {
                const clampedIndex = Math.min(clientSearchIndex, marks.length - 1);
                focusClientMatch(clampedIndex, marks, { scroll: false });
                return;
            }

            const fallbackIndex = ensureFocus || clientSearchIndex < 0
                ? 0
                : Math.min(clientSearchIndex, marks.length - 1);
            focusClientMatch(fallbackIndex, marks, { scroll: ensureFocus });
        }

        function handleClientSearchKeydown(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                if (clientSearchProcessing) {
                    return;
                }
                if (event.shiftKey) {
                    focusPreviousClientMatch();
                } else {
                    focusNextClientMatch();
                }
            } else if (event.key === 'Escape') {
                event.preventDefault();
                if (clientSearchInput) {
                    clientSearchInput.value = '';
                }
                applyClientSearch('');
            }
        }

        function renderLines(lines, meta = {}) {
            if (!logContainer) {
                return;
            }

            clientSearchProcessingToken += 1;
            clientSearchProcessing = false;

            logContainer.innerHTML = '';

            const hasLines = Array.isArray(lines) && lines.length > 0;

            if (!hasLines) {
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

            const totalFromServer = Number.isFinite(meta.totalFromServer)
                ? meta.totalFromServer
                : lines.length;

            let linesToRender = lines;
            let truncated = false;
            if (linesToRender.length > MAX_RENDER_LINES) {
                linesToRender = linesToRender.slice(-MAX_RENDER_LINES);
                truncated = true;
            }

            const totalLines = linesToRender.length;
            const needsChunkRendering = totalLines > LARGE_RENDER_THRESHOLD;
            const needsStatusBar = truncated || needsChunkRendering;

            let statusTextEl = null;
            if (needsStatusBar) {
                const statusBar = document.createElement('div');
                statusBar.className = 'log-render-status';

                const icon = document.createElement('i');
                icon.className = 'fas fa-info-circle opacity-75';

                statusTextEl = document.createElement('span');
                statusTextEl.className = 'status-text';

                statusBar.appendChild(icon);
                statusBar.appendChild(statusTextEl);
                logContainer.appendChild(statusBar);

                if (truncated && needsChunkRendering) {
                    statusTextEl.textContent = `Showing latest ${formatNumber(totalLines)} lines out of ${formatNumber(totalFromServer)}. Rendering...`;
                } else if (truncated) {
                    statusTextEl.textContent = `Showing latest ${formatNumber(totalLines)} lines out of ${formatNumber(totalFromServer)}.`;
                } else if (needsChunkRendering) {
                    statusTextEl.textContent = `Rendering ${formatNumber(totalLines)} lines...`;
                }
            }

            if (needsChunkRendering) {
                renderLinesInChunks(linesToRender, {
                    statusTextEl,
                    truncated,
                    totalFromServer,
                    totalLines,
                });
                return;
            }

            const fragment = document.createDocumentFragment();

            linesToRender.forEach((line) => {
                const element = createLineElement(line);
                if (element) {
                    fragment.appendChild(element);
                }
            });

            logContainer.appendChild(fragment);
            logContainer.scrollTop = logContainer.scrollHeight;

            refreshClientSearchStatus({
                preserveIndex: clientSearchIndex >= 0,
                ensureFocus: clientSearchIndex === -1,
            });

            if (statusTextEl) {
                const message = truncated
                    ? `Showing latest ${formatNumber(totalLines)} lines out of ${formatNumber(totalFromServer)}.`
                    : `Rendered ${formatNumber(totalLines)} lines.`;
                statusTextEl.textContent = message;
            }
        }

        function renderLinesInChunks(lines, options = {}) {
            const {
                statusTextEl = null,
                truncated = false,
                totalFromServer = lines.length,
                totalLines = lines.length,
            } = options;

            let index = 0;
            const chunkSize = Math.max(
                CHUNK_MIN_SIZE,
                Math.min(CHUNK_MAX_SIZE, Math.floor(totalLines / 40) || CHUNK_MIN_SIZE)
            );

            function updateStatus(renderedCount, isComplete = false) {
                if (!statusTextEl) {
                    return;
                }

                if (isComplete) {
                    statusTextEl.textContent = truncated
                        ? `Showing latest ${formatNumber(totalLines)} lines out of ${formatNumber(totalFromServer)}.`
                        : `Rendered ${formatNumber(totalLines)} lines.`;
                    return;
                }

                const baseText = truncated
                    ? `Showing latest ${formatNumber(totalLines)} lines out of ${formatNumber(totalFromServer)}.`
                    : `Rendering ${formatNumber(totalLines)} lines...`;

                statusTextEl.textContent = `${baseText} (${formatNumber(renderedCount)} / ${formatNumber(totalLines)})`;
            }

            function renderChunk() {
                if (index >= totalLines) {
                    updateStatus(totalLines, true);
                    refreshClientSearchStatus({
                        preserveIndex: clientSearchIndex >= 0,
                        ensureFocus: clientSearchIndex === -1,
                    });
                    return;
                }

                const fragment = document.createDocumentFragment();
                const end = Math.min(index + chunkSize, totalLines);

                for (; index < end; index++) {
                    const line = lines[index];
                    const element = createLineElement(line);
                    if (element) {
                        fragment.appendChild(element);
                    }
                }

                logContainer.appendChild(fragment);
                logContainer.scrollTop = logContainer.scrollHeight;

                updateStatus(Math.min(index, totalLines), false);

                requestAnimationFrame(renderChunk);
            }

            updateStatus(0, false);
            requestAnimationFrame(renderChunk);
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
                    const logInfo = data.log_info || {};
                    const logLines = Array.isArray(data.log_lines) ? data.log_lines : [];
                    renderLines(logLines, {
                        totalFromServer: Number(logInfo.showing_lines),
                    });
                    updateStats(logInfo);
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

            if (clientSearchInput) {
                clientSearchInput.addEventListener('input', function (event) {
                    applyClientSearch(event.target.value);
                });
                clientSearchInput.addEventListener('keydown', handleClientSearchKeydown);
            }

            if (clientSearchNextBtn) {
                clientSearchNextBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    focusNextClientMatch();
                });
            }

            if (clientSearchPrevBtn) {
                clientSearchPrevBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    focusPreviousClientMatch();
                });
            }

            if (clientSearchClearBtn) {
                clientSearchClearBtn.addEventListener('click', function (event) {
                    event.preventDefault();
                    if (clientSearchInput) {
                        clientSearchInput.value = '';
                        try {
                            clientSearchInput.focus({ preventScroll: true });
                        } catch (error) {
                            clientSearchInput.focus();
                        }
                    }
                    applyClientSearch('');
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
