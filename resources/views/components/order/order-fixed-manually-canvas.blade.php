<!-- Order Fixed Manually Canvas -->
<div class="offcanvas offcanvas-end" style="width: 100%;" tabindex="-1" id="orderFixedManuallyCanvas"
    aria-labelledby="orderFixedManuallyLabel" data-bs-backdrop="true" data-bs-scroll="false">
    <div class="offcanvas-header" style="border-bottom: 1px solid var(--input-border);">
        <div class="d-flex align-items-center gap-3">
            <h5 class="offcanvas-title mb-0" id="orderFixedManuallyLabel">
                <i class="fa-solid fa-wrench text-warning me-2"></i>
                Order Fixed Manually - Order #<span id="fixOrderId"></span>
            </h5>
            <span id="fixStatusBadge" class="badge bg-secondary">Ready</span>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="offcanvas-body p-0">
        <div class="container-fluid h-100 d-flex flex-column">
            <!-- Control Panel -->
            <div class="row py-3 px-4" style="background: var(--secondary-color); border-bottom: 1px solid var(--input-border);">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <!-- Action Buttons Group -->
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" id="runFixCommand" class="btn m-btn px-4">
                                <i class="fa-solid fa-envelope-circle-check me-2"></i> Create Mailboxes
                            </button>
                            <button type="button" id="stopFixCommand" class="btn cancel-btn px-3" disabled>
                                <i class="fa-solid fa-stop me-1"></i> Stop
                            </button>
                            <button type="button" id="clearFixLogs" class="btn c-btn px-3">
                                <i class="fa-solid fa-eraser me-1"></i> Clear
                            </button>
                            <div style="width: 1px; height: 28px; background: var(--input-border); margin: 0 8px;"></div>
                            <button type="button" id="deleteMailboxesBtn" class="btn cancel-btn px-3">
                                <i class="fa-solid fa-trash-alt me-1"></i> Delete Mailboxes
                            </button>
                        </div>
                        <!-- Options Group -->
                        <div class="d-flex align-items-center gap-4">
                            <div class="form-check form-switch" style="margin-bottom: 0;">
                                <input class="form-check-input" type="checkbox" id="autoRetryToggle" checked style="cursor: pointer;">
                                <label class="form-check-label" for="autoRetryToggle" style="cursor: pointer; color: var(--light-color);">Auto Retry</label>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span style="color: var(--extra-light); font-size: 13px;">Attempts:</span>
                                <span id="retryCount" class="badge" style="background: var(--second-primary);">0 / 3</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Progress Bar -->
            <div class="row px-3 py-2" style="background: var(--filter-color);">
                <div class="col-12">
                    <div class="progress" style="height: 6px;">
                        <div id="fixProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Terminal Output -->
            <div class="row flex-grow-1 px-0">
                <div class="col-12 h-100">
                    <div id="fixTerminalOutput" class="h-100 p-3" 
                         style="background: #1a1a2e; color: #16f75d; font-family: 'Courier New', monospace; font-size: 13px; overflow-y: auto; min-height: 400px;">
                        <div class="terminal-welcome">
                            <div style="color: #00d4ff;">══════════════════════════════════════════════════════════════════════════</div>
                            <div style="color: #ffd700;">  📧 Order Fixed Manually - Mailbox Creation Command</div>
                            <div style="color: #00d4ff;">══════════════════════════════════════════════════════════════════════════</div>
                            <div class="mt-2" style="color: #aaa;">
                                This will run: <span style="color: #ff79c6;">php artisan mailin:create-mailboxes-for-active-domains {order_id}</span>
                            </div>
                            <div style="color: #aaa;">
                                • Creates mailboxes for active domains on Mailin.ai
                            </div>
                            <div style="color: #aaa;">
                                • Backfills missing Mailin IDs for existing emails
                            </div>
                            <div style="color: #aaa;">
                                • Auto-completes order when all mailboxes are created
                            </div>
                            <div class="mt-3" style="color: #50fa7b;">
                                Click <strong>Run Command</strong> to start...
                            </div>
                            <div style="color: #00d4ff;">══════════════════════════════════════════════════════════════════════════</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Summary Panel -->
            <div class="row py-3 px-3" style="background: var(--secondary-color); border-top: 1px solid var(--input-border);">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <div id="fixSummary" class="d-flex align-items-center gap-4">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-check-circle text-success"></i>
                                <span>Created: <strong id="createdCount">0</strong></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-forward text-info"></i>
                                <span>Skipped: <strong id="skippedCount">0</strong></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-exclamation-circle text-danger"></i>
                                <span>Failed: <strong id="failedCount">0</strong></span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-globe text-primary"></i>
                                <span>Active Domains: <strong id="activeDomainsCount">0</strong></span>
                            </div>
                            <div class="vr mx-2"></div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fa-solid fa-trash-alt text-danger"></i>
                                <span>Deleted: <strong id="deletedCount">0</strong></span>
                            </div>
                        </div>
                        <div id="lastRunTime" class="text-muted small">
                            Last run: Never
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Terminal Styling */
#fixTerminalOutput {
    scrollbar-width: thin;
    scrollbar-color: #4a4a6a #1a1a2e;
}

#fixTerminalOutput::-webkit-scrollbar {
    width: 8px;
}

#fixTerminalOutput::-webkit-scrollbar-track {
    background: #1a1a2e;
}

#fixTerminalOutput::-webkit-scrollbar-thumb {
    background-color: #4a4a6a;
    border-radius: 4px;
}

.log-line {
    padding: 2px 0;
    font-family: 'Courier New', monospace;
    white-space: pre-wrap;
    word-break: break-word;
}

.log-info {
    color: #00d4ff;
}

.log-success {
    color: #50fa7b;
}

.log-warning {
    color: #ffb86c;
}

.log-error {
    color: #ff5555;
}

.log-highlight {
    color: #ff79c6;
}

.log-dim {
    color: #6272a4;
}

.log-separator {
    color: #44475a;
    border-bottom: 1px dashed #44475a;
    margin: 10px 0;
}

/* Status Badge Animations */
#fixStatusBadge.running {
    animation: pulse-badge 1.5s infinite;
}

@keyframes pulse-badge {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

/* Progress Bar Colors */
.progress-bar.bg-success {
    background-color: #50fa7b !important;
}

.progress-bar.bg-danger {
    background-color: #ff5555 !important;
}

.progress-bar.bg-warning {
    background-color: #ffb86c !important;
}
</style>

<script>
(function() {
    // State variables
    let currentOrderId = null;
    let isRunning = false;
    let eventSource = null;
    let retryCount = 0;
    const maxRetries = 3;
    let stats = {
        created: 0,
        skipped: 0,
        failed: 0,
        activeDomains: 0,
        deleted: 0
    };
    let currentMode = 'create'; // 'create' or 'delete'

    // DOM Elements
    const canvas = document.getElementById('orderFixedManuallyCanvas');
    const terminalOutput = document.getElementById('fixTerminalOutput');
    const runBtn = document.getElementById('runFixCommand');
    const stopBtn = document.getElementById('stopFixCommand');
    const clearBtn = document.getElementById('clearFixLogs');
    const deleteBtn = document.getElementById('deleteMailboxesBtn');
    const statusBadge = document.getElementById('fixStatusBadge');
    const progressBar = document.getElementById('fixProgressBar');
    const autoRetryToggle = document.getElementById('autoRetryToggle');
    const retryCountEl = document.getElementById('retryCount');

    // Initialize canvas open handler
    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('.order-fixed-manually');
        if (trigger) {
            e.preventDefault();
            currentOrderId = trigger.getAttribute('data-order-id');
            document.getElementById('fixOrderId').textContent = currentOrderId;
            resetState();
            
            const bsCanvas = new bootstrap.Offcanvas(canvas);
            bsCanvas.show();
        }
    });

    // Run Command Button
    runBtn.addEventListener('click', function() {
        if (!currentOrderId || isRunning) return;
        retryCount = 0;
        runCommand();
    });

    // Stop Command Button
    stopBtn.addEventListener('click', function() {
        stopCommand();
    });

    // Clear Logs Button
    clearBtn.addEventListener('click', function() {
        clearTerminal();
    });

    // Delete Mailboxes Button
    deleteBtn.addEventListener('click', function() {
        if (!currentOrderId || isRunning) return;
        
        // Show confirmation dialog
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '⚠️ Delete Mailboxes',
                html: `<p>Are you sure you want to delete <strong>ALL mailboxes</strong> for Order #${currentOrderId}?</p>
                       <p class="text-danger"><strong>This will:</strong></p>
                       <ul class="text-start">
                           <li>Delete all mailboxes from Mailin.ai</li>
                           <li>Remove all email records from database</li>
                       </ul>
                       <p class="text-danger"><strong>This action cannot be undone!</strong></p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Delete All!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    runDeleteCommand();
                }
            });
        } else {
            // Fallback to native confirm
            if (confirm(`Are you sure you want to delete ALL mailboxes for Order #${currentOrderId}? This action cannot be undone!`)) {
                runDeleteCommand();
            }
        }
    });

    // Run the create mailboxes command
    function runCommand() {
        if (!currentOrderId) return;
        
        currentMode = 'create';
        isRunning = true;
        updateUI('running');
        appendLog('info', `Starting mailbox creation for Order #${currentOrderId}...`);
        appendLog('separator');
        executeCommand(`{{ url('/admin/orders') }}/${currentOrderId}/run-fix-mailboxes`);
    }

    // Run the delete mailboxes command
    function runDeleteCommand() {
        if (!currentOrderId) return;
        
        currentMode = 'delete';
        isRunning = true;
        stats.deleted = 0;
        document.getElementById('deletedCount').textContent = '0';
        updateUI('running');
        appendLog('error', `⚠️ DELETING mailboxes for Order #${currentOrderId}...`);
        appendLog('separator');
        executeCommand(`{{ url('/admin/orders') }}/${currentOrderId}/run-delete-mailboxes`);
    }

    // Execute command with SSE streaming  
    function executeCommand(url) {

        // Use fetch with streaming response
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'text/event-stream',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            let buffer = '';
            
            function processStream() {
                reader.read().then(({done, value}) => {
                    if (done) {
                        processComplete();
                        return;
                    }
                    
                    buffer += decoder.decode(value, {stream: true});
                    const lines = buffer.split('\n');
                    buffer = lines.pop(); // Keep incomplete line in buffer
                    
                    lines.forEach(line => {
                        if (line.startsWith('data:')) {
                            try {
                                const data = JSON.parse(line.substring(5).trim());
                                handleLogData(data);
                            } catch (e) {
                                // Plain text log
                                const text = line.substring(5).trim();
                                if (text) {
                                    appendLogRaw(text);
                                }
                            }
                        }
                    });
                    
                    processStream();
                }).catch(error => {
                    console.error('Stream error:', error);
                    handleError(error);
                });
            }
            
            processStream();
        })
        .catch(error => {
            console.error('Fetch error:', error);
            handleError(error);
        });
    }

    // Handle log data from stream
    function handleLogData(data) {
        if (data.type === 'log') {
            appendLog(data.level || 'info', data.message);
        } else if (data.type === 'progress') {
            updateProgress(data.percent);
        } else if (data.type === 'stats') {
            updateStats(data);
        } else if (data.type === 'complete') {
            handleComplete(data);
        } else if (data.type === 'error') {
            appendLog('error', data.message);
        }
    }

    // Append raw log line
    function appendLogRaw(text) {
        const logClass = getLogClass(text);
        const line = document.createElement('div');
        line.className = `log-line ${logClass}`;
        line.textContent = text;
        terminalOutput.appendChild(line);
        scrollToBottom();
    }

    // Determine log class based on content
    function getLogClass(text) {
        if (text.includes('✓') || text.includes('success') || text.includes('Created') || text.includes('COMPLETED')) {
            return 'log-success';
        } else if (text.includes('✗') || text.includes('Error') || text.includes('Failed') || text.includes('error')) {
            return 'log-error';
        } else if (text.includes('⚠') || text.includes('Warning') || text.includes('Skipping') || text.includes('missing')) {
            return 'log-warning';
        } else if (text.includes('Processing') || text.includes('Checking') || text.includes('...')) {
            return 'log-info';
        }
        return '';
    }

    // Append formatted log
    function appendLog(level, message) {
        const line = document.createElement('div');
        line.className = `log-line log-${level}`;
        
        const timestamp = new Date().toLocaleTimeString();
        
        if (level === 'separator') {
            line.className = 'log-separator';
            line.innerHTML = '─'.repeat(70);
        } else {
            const prefix = getLogPrefix(level);
            line.innerHTML = `<span class="log-dim">[${timestamp}]</span> ${prefix} ${escapeHtml(message)}`;
        }
        
        terminalOutput.appendChild(line);
        scrollToBottom();
    }

    // Get log prefix based on level
    function getLogPrefix(level) {
        switch(level) {
            case 'success': return '<span class="log-success">✓</span>';
            case 'error': return '<span class="log-error">✗</span>';
            case 'warning': return '<span class="log-warning">⚠</span>';
            case 'info': return '<span class="log-info">→</span>';
            default: return '';
        }
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Scroll terminal to bottom
    function scrollToBottom() {
        terminalOutput.scrollTop = terminalOutput.scrollHeight;
    }

    // Update progress bar
    function updateProgress(percent) {
        progressBar.style.width = percent + '%';
        progressBar.setAttribute('aria-valuenow', percent);
    }

    // Update statistics
    function updateStats(data) {
        if (data.created !== undefined) {
            stats.created = data.created;
            document.getElementById('createdCount').textContent = stats.created;
        }
        if (data.skipped !== undefined) {
            stats.skipped = data.skipped;
            document.getElementById('skippedCount').textContent = stats.skipped;
        }
        if (data.failed !== undefined) {
            stats.failed = data.failed;
            document.getElementById('failedCount').textContent = stats.failed;
        }
        if (data.activeDomains !== undefined) {
            stats.activeDomains = data.activeDomains;
            document.getElementById('activeDomainsCount').textContent = stats.activeDomains;
        }
        if (data.deleted !== undefined) {
            stats.deleted = data.deleted;
            document.getElementById('deletedCount').textContent = stats.deleted;
        }
    }

    // Handle command completion
    function handleComplete(data) {
        isRunning = false;
        document.getElementById('lastRunTime').textContent = 'Last run: ' + new Date().toLocaleString();
        
        if (data.needsRetry && autoRetryToggle.checked && retryCount < maxRetries) {
            retryCount++;
            retryCountEl.textContent = `${retryCount} / ${maxRetries}`;
            updateUI('retrying');
            appendLog('separator');
            appendLog('warning', `Some mailboxes not created. Auto-retrying in 5 seconds... (Attempt ${retryCount}/${maxRetries})`);
            
            setTimeout(() => {
                if (!isRunning) { // Check if not manually stopped
                    runCommand();
                }
            }, 5000);
        } else if (data.success) {
            updateUI('success');
            appendLog('separator');
            appendLog('success', 'Command completed successfully!');
            updateProgress(100);
            progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
            progressBar.classList.add('bg-success');
        } else {
            updateUI('failed');
            appendLog('separator');
            appendLog('error', 'Command completed with errors. Check logs above.');
            progressBar.classList.remove('progress-bar-animated', 'progress-bar-striped');
            progressBar.classList.add('bg-danger');
        }
    }

    // Process stream complete
    function processComplete() {
        if (isRunning) {
            isRunning = false;
            updateUI('completed');
            appendLog('separator');
            appendLog('info', 'Command execution finished.');
            document.getElementById('lastRunTime').textContent = 'Last run: ' + new Date().toLocaleString();
        }
    }

    // Handle errors
    function handleError(error) {
        isRunning = false;
        updateUI('error');
        appendLog('error', `Error: ${error.message}`);
        updateProgress(0);
    }

    // Stop command execution
    function stopCommand() {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }
        isRunning = false;
        updateUI('stopped');
        appendLog('separator');
        appendLog('warning', 'Command stopped by user.');
    }

    // Update UI state
    function updateUI(state) {
        runBtn.disabled = isRunning;
        stopBtn.disabled = !isRunning;
        deleteBtn.disabled = isRunning;
        
        // Remove all classes first
        statusBadge.classList.remove('bg-secondary', 'bg-success', 'bg-danger', 'bg-warning', 'bg-info', 'bg-primary', 'running');
        progressBar.classList.remove('bg-success', 'bg-danger', 'bg-warning');
        
        switch(state) {
            case 'running':
                statusBadge.textContent = 'Running...';
                statusBadge.classList.add('bg-primary', 'running');
                progressBar.classList.add('progress-bar-striped', 'progress-bar-animated');
                updateProgress(10);
                break;
            case 'retrying':
                statusBadge.textContent = 'Retrying...';
                statusBadge.classList.add('bg-warning', 'running');
                break;
            case 'success':
                statusBadge.textContent = 'Completed';
                statusBadge.classList.add('bg-success');
                break;
            case 'failed':
            case 'error':
                statusBadge.textContent = 'Failed';
                statusBadge.classList.add('bg-danger');
                break;
            case 'stopped':
                statusBadge.textContent = 'Stopped';
                statusBadge.classList.add('bg-warning');
                break;
            case 'completed':
                statusBadge.textContent = 'Done';
                statusBadge.classList.add('bg-info');
                break;
            default:
                statusBadge.textContent = 'Ready';
                statusBadge.classList.add('bg-secondary');
        }
    }

    // Reset state
    function resetState() {
        retryCount = 0;
        currentMode = 'create';
        stats = { created: 0, skipped: 0, failed: 0, activeDomains: 0, deleted: 0 };
        retryCountEl.textContent = '0 / 3';
        document.getElementById('createdCount').textContent = '0';
        document.getElementById('skippedCount').textContent = '0';
        document.getElementById('deletedCount').textContent = '0';
        document.getElementById('failedCount').textContent = '0';
        document.getElementById('activeDomainsCount').textContent = '0';
        updateProgress(0);
        progressBar.classList.remove('bg-success', 'bg-danger', 'bg-warning');
        progressBar.classList.add('progress-bar-striped', 'progress-bar-animated');
        updateUI('ready');
    }

    // Clear terminal
    function clearTerminal() {
        terminalOutput.innerHTML = `
            <div class="terminal-welcome">
                <div style="color: #00d4ff;">══════════════════════════════════════════════════════════════════════════</div>
                <div style="color: #ffd700;">  📧 Order Fixed Manually - Mailbox Creation Command</div>
                <div style="color: #00d4ff;">══════════════════════════════════════════════════════════════════════════</div>
                <div class="mt-2" style="color: #aaa;">Terminal cleared. Click <strong style="color: #50fa7b;">Run Command</strong> to start...</div>
                <div style="color: #00d4ff;">══════════════════════════════════════════════════════════════════════════</div>
            </div>
        `;
        resetState();
    }

    // Canvas close handler - cleanup
    canvas.addEventListener('hidden.bs.offcanvas', function() {
        if (eventSource) {
            eventSource.close();
            eventSource = null;
        }
        isRunning = false;
    });
})();
</script>
