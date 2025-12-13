<div id="manual-assignment-section" class="mb-4">
    <h5 class="mb-3 mt-4">
        <i class="fas fa-server me-2"></i>Panel Assignment
    </h5>

    {{-- Assignment Mode Toggle --}}
    <div class="mb-4 panel-assignment-toggle">
        <label class="form-label fw-bold">Assignment Mode</label>
        <div class="btn-group w-100" role="group">
            <input type="radio" class="btn-check" name="assignment_mode" id="mode_automatic" value="automatic" checked>
            <label class="btn btn-outline-primary" for="mode_automatic">
                <i class="fas fa-magic me-1"></i>Automatic Assignment
            </label>
            
            <input type="radio" class="btn-check" name="assignment_mode" id="mode_manual" value="manual">
            <label class="btn btn-outline-primary" for="mode_manual">
                <i class="fas fa-hand-pointer me-1"></i>Manual Assignment
            </label>
        </div>
        <small class=" d-block mt-2">
            <strong>Automatic:</strong> System assigns pools to panels automatically based on capacity.<br>
            <strong>Manual:</strong> You specify which domains go to which panels in batches.
        </small>
    </div>

    {{-- Manual Assignment Builder --}}
    <div id="manual-assignment-builder" style="display: none;">
        <div class="d-flex align-items-center justify-content-end flex-wrap gap-2 mb-3">
            <button type="button" class="btn btn-success btn-sm" id="add-batch-btn">
                <i class="fas fa-plus me-1"></i>Add Batch
            </button>
        </div>

        {{-- Assignment Summary --}}
        <div class="assignment-summary mb-3">
            <div class="summary-tile">
                <div class="label">Total Domains</div>
                <div class="value" id="total-domains-count">0</div>
            </div>
            <div class="summary-tile">
                <div class="label">Assigned</div>
                <div class="value" id="assigned-domains-count">0</div>
            </div>
            <div class="summary-tile">
                <div class="label">Remaining</div>
                <div class="value text-warning" id="remaining-domains-count">0</div>
            </div>
            <div class="summary-tile">
                <div class="label">Total Space</div>
                <div class="value"><span id="total-space-needed">0</span> inboxes</div>
            </div>
        </div>

        {{-- Batch List --}}
        <div id="batches-container" class="mb-3">
            <div class="placeholder-card ">
                <i class="fas fa-layer-group me-2"></i>No batches yet. Click "Add Batch" to start.
            </div>
        </div>

        {{-- Validation Messages --}}
        <div id="assignment-validation-messages"></div>
    </div>
</div>

{{-- Batch Template (Hidden) --}}
<template id="batch-template">
    <div class="batch-item" data-batch-index="">
        <div class="batch-header">
            <div class="batch-title">
                <span class="batch-icon">
                    <i class="fas fa-layer-group"></i>
                </span>
                <span class="batch-label">Batch <span class="batch-number"></span></span>
            </div>
            <button type="button" class="btn-remove-batch remove-batch-btn" title="Remove batch">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="batch-body">
            <div class="row g-3">
                {{-- Panel Selection --}}
                <div class="col-lg-5 col-md-6">
                    <label class="batch-label-text">
                        <i class="fas fa-server me-1"></i>Panel
                    </label>
                    <select class="form-select panel-select" name="manual_assignments[][panel_id]">
                        <option value="">Select Panel...</option>
                    </select>
                    <small class="panel-capacity-info"></small>
                </div>

                {{-- Domain Range Selection --}}
                <div class="col-lg-2 col-md-3 col-6">
                    <label class="batch-label-text">
                        <i class="fas fa-play me-1"></i>Start Domain
                    </label>
                    <input type="number" class="form-control domain-start" name="manual_assignments[][domain_start]" min="1" placeholder="1" readonly>
                    <small class="text-muted">Auto-calculated</small>
                </div>

                <div class="col-lg-2 col-md-3 col-6">
                    <label class="batch-label-text">
                        <i class="fas fa-stop me-1"></i>End Domain
                    </label>
                    <input type="number" class="form-control domain-end" name="manual_assignments[][domain_end]" min="1" placeholder="100">
                </div>

                {{-- Batch Summary --}}
                <div class="col-lg-3 col-md-12">
                    <label class="batch-label-text">
                        <i class="fas fa-chart-pie me-1"></i>Space Needed
                    </label>
                    <div class="space-needed-badge">
                        <span class="batch-space-display">0</span>
                        <span class="space-unit">inboxes</span>
                    </div>
                    <input type="hidden" class="batch-space-value" name="manual_assignments[][space_needed]">
                </div>
            </div>

            {{-- Panel Detail Snapshot --}}
            <div class="panel-detail-box" style="display: none;">
                <div class="detail-grid">
                    <div class="detail-chip">
                        <div class="chip-icon">
                            <i class="fas fa-server"></i>
                        </div>
                        <div class="chip-content">
                            <span class="chip-label">Panel</span>
                            <span class="chip-value panel-name">-</span>
                        </div>
                    </div>
                    <div class="detail-chip">
                        <div class="chip-icon">
                            <i class="fas fa-battery-three-quarters"></i>
                        </div>
                        <div class="chip-content">
                            <span class="chip-label">Available</span>
                            <span class="chip-value remaining-count">-</span>
                        </div>
                    </div>
                    <div class="detail-chip">
                        <div class="chip-icon">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="chip-content">
                            <span class="chip-label">Total Capacity</span>
                            <span class="chip-value capacity-count">-</span>
                        </div>
                    </div>
                    <div class="detail-chip highlight">
                        <div class="chip-icon">
                            <i class="fas fa-forward"></i>
                        </div>
                        <div class="chip-content">
                            <span class="chip-label">After Assignment</span>
                            <span class="chip-value after-count">-</span>
                        </div>
                    </div>
                </div>
                <div class="progress-container">
                    <div class="progress meta-progress">
                        <div class="progress-bar meta-progress-current" role="progressbar" style="width: 0%"></div>
                        <div class="progress-bar meta-progress-future" role="progressbar" style="width: 0%"></div>
                    </div>
                    <small class="meta-footnote"></small>
                </div>
            </div>

            {{-- Domain List Preview --}}
            <div class="domain-preview">
                <i class="fas fa-globe me-1"></i>
                <strong class="domain-count-display">0</strong> domains
                <span class="domain-range-display">-</span>
            </div>

            {{-- Validation Status --}}
            <div class="batch-validation-status"></div>
        </div>
    </div>
</template>

@once
@push('styles')
<style>
    /* ===== Assignment Section ===== */
    #manual-assignment-section {
        background: linear-gradient(145deg, #0f172a 0%, #1e293b 100%);
        border-radius: 16px;
        padding: 24px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    }

    #manual-assignment-section h5 {
        color: #f8fafc;
        font-weight: 700;
        font-size: 1.35rem;
        margin-bottom: 1.5rem;
    }

    /* ===== Assignment Mode Toggle ===== */
    .panel-assignment-toggle {
        background: rgba(15, 23, 42, 0.6);
        padding: 20px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.06);
    }

    .panel-assignment-toggle .form-label {
        color: #cbd5e1;
        font-size: 0.95rem;
        margin-bottom: 12px;
    }

    .panel-assignment-toggle .btn-group {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
    }

    .panel-assignment-toggle .btn-outline-primary {
        background: linear-gradient(135deg, #1e293b, #334155);
        border: 1px solid rgba(99, 102, 241, 0.3);
        color: #cbd5e1;
        font-weight: 600;
        padding: 12px 20px;
        transition: all 0.3s ease;
    }

    .panel-assignment-toggle .btn-outline-primary:hover {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border-color: #6366f1;
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
    }

    .panel-assignment-toggle .btn-check:checked + .btn-outline-primary {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border-color: #6366f1;
        color: #fff;
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
    }

    /* ===== Summary Cards ===== */
    #manual-assignment-section .assignment-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    #manual-assignment-section .summary-tile {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    #manual-assignment-section .summary-tile:before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: linear-gradient(180deg, #3b82f6, #6366f1);
        opacity: 0.8;
    }

    #manual-assignment-section .summary-tile:hover {
        transform: translateY(-4px);
        box-shadow: 0 15px 40px rgba(59, 130, 246, 0.3);
        border-color: rgba(99, 102, 241, 0.4);
    }

    #manual-assignment-section .summary-tile .label {
        text-transform: uppercase;
        font-size: 0.75rem;
        color: #94a3b8;
        letter-spacing: 0.08em;
        font-weight: 600;
        margin-bottom: 8px;
    }

    #manual-assignment-section .summary-tile .value {
        font-size: 1.75rem;
        font-weight: 800;
        color: #f1f5f9;
        background: linear-gradient(135deg, #60a5fa, #a78bfa);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* ===== Placeholder ===== */
    #manual-assignment-section .placeholder-card {
        padding: 40px 20px;
        border-radius: 12px;
        border: 2px dashed rgba(99, 102, 241, 0.3);
        text-align: center;
        background: rgba(30, 41, 59, 0.3);
        color: #94a3b8;
        font-size: 1rem;
        transition: all 0.3s ease;
    }

    #manual-assignment-section .placeholder-card:hover {
        border-color: rgba(99, 102, 241, 0.5);
        background: rgba(30, 41, 59, 0.5);
    }

    /* ===== Batch Items ===== */
    #manual-assignment-section .batch-item {
        background: linear-gradient(145deg, #1e293b, #0f172a);
        border: 1px solid rgba(99, 102, 241, 0.25);
        border-radius: 16px;
        margin-bottom: 20px;
        box-shadow: 0 10px 35px rgba(0, 0, 0, 0.35);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    #manual-assignment-section .batch-item:hover {
        border-color: rgba(99, 102, 241, 0.45);
        box-shadow: 0 15px 45px rgba(59, 130, 246, 0.25);
        transform: translateY(-2px);
    }

    /* ===== Batch Header ===== */
    #manual-assignment-section .batch-header {
        background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
        padding: 18px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(99, 102, 241, 0.2);
    }

    #manual-assignment-section .batch-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    #manual-assignment-section .batch-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 1.1rem;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);
    }

    #manual-assignment-section .batch-label {
        color: #f1f5f9;
        font-weight: 700;
        font-size: 1.1rem;
    }

    #manual-assignment-section .btn-remove-batch {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #fca5a5;
        border-radius: 8px;
        padding: 8px 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 1rem;
    }

    #manual-assignment-section .btn-remove-batch:hover {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border-color: #ef4444;
        color: #fff;
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
    }

    /* ===== Batch Body ===== */
    #manual-assignment-section .batch-body {
        padding: 24px;
    }

    #manual-assignment-section .batch-label-text {
        color: #cbd5e1;
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 8px;
        display: block;
    }

    #manual-assignment-section .form-select,
    #manual-assignment-section .form-control {
        background: rgba(15, 23, 42, 0.7);
        border: 1px solid rgba(99, 102, 241, 0.25);
        color: #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
        transition: all 0.3s ease;
    }

    #manual-assignment-section .form-select:focus,
    #manual-assignment-section .form-control:focus {
        background: rgba(15, 23, 42, 0.9);
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        color: #f1f5f9;
    }

    #manual-assignment-section .form-select option {
        background: #1e293b;
        color: #e2e8f0;
    }

    #manual-assignment-section .panel-select {
        appearance: none;
        background-color: rgba(15, 23, 42, 0.7);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M4.646 6.646a.5.5 0 0 1 .708 0L8 9.293l2.646-2.647a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 0 1 0-.708z' fill='%2394a3b8'/%3E%3C/svg%3E");
        background-repeat: no-repeat !important;
        background-position: right 0.85rem center;
        background-size: 0.95rem;
        padding-right: 2.4rem;
    }

    #manual-assignment-section .panel-select:focus {
        background-color: rgba(15, 23, 42, 0.9);
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M4.646 6.646a.5.5 0 0 1 .708 0L8 9.293l2.646-2.647a.5.5 0 0 1 .708.708l-3 3a.5.5 0 0 1-.708 0l-3-3a.5.5 0 0 1 0-.708z' fill='%2394a3b8'/%3E%3C/svg%3E");
        background-repeat: no-repeat !important;
        background-position: right 0.85rem center;
        background-size: 0.95rem;
    }

    #manual-assignment-section .panel-capacity-info {
        color: #94a3b8;
        font-size: 0.8rem;
        display: block;
        margin-top: 4px;
    }

    /* ===== Space Needed Badge ===== */
    #manual-assignment-section .space-needed-badge {
        background: linear-gradient(135deg, #059669, #10b981);
        border-radius: 10px;
        padding: 10px 16px;
        display: flex;
        align-items: baseline;
        gap: 6px;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        margin-top: 8px;
    }

    #manual-assignment-section .space-needed-badge .batch-space-display {
        font-size: 1.5rem;
        font-weight: 800;
        color: #fff;
    }

    #manual-assignment-section .space-needed-badge .space-unit {
        font-size: 0.85rem;
        color: #d1fae5;
        font-weight: 600;
    }

    /* ===== Panel Detail Box ===== */
    #manual-assignment-section .panel-detail-box {
        background: rgba(30, 41, 59, 0.6);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 14px;
        padding: 20px;
        margin-top: 20px;
    }

    #manual-assignment-section .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
        margin-bottom: 16px;
    }

    #manual-assignment-section .detail-chip {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(148, 163, 184, 0.15);
        border-radius: 10px;
        padding: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s ease;
    }

    #manual-assignment-section .detail-chip:hover {
        background: rgba(15, 23, 42, 0.9);
        border-color: rgba(99, 102, 241, 0.3);
        transform: translateY(-2px);
    }

    #manual-assignment-section .detail-chip.highlight {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(99, 102, 241, 0.15));
        border-color: rgba(99, 102, 241, 0.4);
    }

    #manual-assignment-section .chip-icon {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    #manual-assignment-section .chip-content {
        display: flex;
        flex-direction: column;
        gap: 2px;
        flex: 1;
    }

    #manual-assignment-section .chip-label {
        color: #94a3b8;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
    }

    #manual-assignment-section .chip-value {
        color: #f1f5f9;
        font-weight: 700;
        font-size: 0.95rem;
    }

    /* ===== Progress Bar ===== */
    #manual-assignment-section .progress-container {
        margin-top: 12px;
    }

    #manual-assignment-section .progress.meta-progress {
        height: 14px;
        background: rgba(15, 23, 42, 0.8);
        border-radius: 999px;
        position: relative;
        overflow: hidden;
        box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    #manual-assignment-section .progress-bar.meta-progress-current {
        background: linear-gradient(90deg, #2563eb, #4f46e5);
        box-shadow: 0 0 15px rgba(37, 99, 235, 0.5);
    }

    #manual-assignment-section .progress-bar.meta-progress-future {
        background: linear-gradient(90deg, #10b981, #059669);
        box-shadow: 0 0 15px rgba(16, 185, 129, 0.5);
    }

    #manual-assignment-section .meta-footnote {
        color: #94a3b8;
        font-size: 0.8rem;
        display: block;
        margin-top: 8px;
    }

    /* ===== Domain Preview ===== */
    #manual-assignment-section .domain-preview {
        background: rgba(30, 41, 59, 0.4);
        border: 1px solid rgba(148, 163, 184, 0.15);
        border-radius: 8px;
        padding: 12px 16px;
        margin-top: 16px;
        color: #cbd5e1;
        font-size: 0.9rem;
    }

    #manual-assignment-section .domain-preview strong {
        color: #60a5fa;
        font-weight: 700;
    }

    #manual-assignment-section .domain-range-display {
        color: #94a3b8;
        margin-left: 4px;
    }

    /* ===== Alerts ===== */
    #manual-assignment-section .alert {
        border: none;
        border-radius: 12px;
        padding: 16px 20px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
        position: relative;
        overflow: hidden;
        margin-top: 12px;
    }

    #manual-assignment-section .alert:before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 5px;
        height: 100%;
    }

    #manual-assignment-section .alert-danger {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.15));
        color: #fecaca;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }

    #manual-assignment-section .alert-danger:before {
        background: linear-gradient(180deg, #ef4444, #dc2626);
    }

    #manual-assignment-section .alert-warning {
        background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(217, 119, 6, 0.15));
        color: #fde68a;
        border: 1px solid rgba(245, 158, 11, 0.3);
    }

    #manual-assignment-section .alert-warning:before {
        background: linear-gradient(180deg, #f59e0b, #d97706);
    }

    #manual-assignment-section .alert-success {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(5, 150, 105, 0.15));
        color: #a7f3d0;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    #manual-assignment-section .alert-success:before {
        background: linear-gradient(180deg, #10b981, #059669);
    }

    #manual-assignment-section .alert i {
        color: inherit;
        margin-right: 8px;
    }

    /* ===== Add Batch Button ===== */
    #add-batch-btn {
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
        color: #fff;
        padding: 10px 24px;
        border-radius: 10px;
        font-weight: 600;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.3);
        transition: all 0.3s ease;
    }

    #add-batch-btn:hover:not(:disabled) {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
    }

    #add-batch-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* ===== Responsive ===== */
    @media (max-width: 768px) {
        #manual-assignment-section .assignment-summary {
            grid-template-columns: repeat(2, 1fr);
        }

        #manual-assignment-section .detail-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        #manual-assignment-section .batch-header {
            padding: 14px 18px;
        }

        #manual-assignment-section .batch-body {
            padding: 18px;
        }
    }

    @media (max-width: 576px) {
        #manual-assignment-section .assignment-summary {
            grid-template-columns: 1fr;
        }

        #manual-assignment-section .detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush
@push('scripts')
<script>
    /* Panel Assignment v2.0 - UTF-8 Fix Applied */
    window.manualPanelAssignment = (function($) {
        let batchIndex = 0;
        let totalDomains = 0;
        let inboxesPerDomain = 1;
        let providerType = 'Google';
        let availablePanels = [];

        function init() {
            bindEvents();
            inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;
            updateDomainCount();

            if (isManualMode()) {
                $('#manual-assignment-builder').show();
                loadAvailablePanels();
            }
        }

        function bindEvents() {
            $('input[name="assignment_mode"]').on('change', function() {
                if ($(this).val() === 'manual') {
                    $('#manual-assignment-builder').slideDown();
                    loadAvailablePanels();
                } else {
                    $('#manual-assignment-builder').slideUp();
                }
            });

            $('#add-batch-btn').on('click', addBatch);
            $('#batches-container').on('click', '.remove-batch-btn', function() {
                $(this).closest('.batch-item').remove();
                updateBatchNumbers();
                updateAssignmentSummary();
                updatePanelDropdowns();
            });

            $('#batches-container').on('change', '.panel-select', function() {
                const panelId = $(this).val();
                const batchCard = $(this).closest('.batch-item');
                
                if (panelId) {
                    loadPanelCapacity(panelId, batchCard);
                }
                
                updatePanelMeta(batchCard);
                updatePanelDropdowns();
            });

            $('#batches-container').on('change', '.domain-end', function() {
                const batchCard = $(this).closest('.batch-item');
                const $endInput = $(this);
                const start = parseInt(batchCard.find('.domain-start').val()) || 0;
                let end = parseInt($endInput.val()) || 0;
                
                if (end > 0) {
                    if (end < start) {
                        $endInput.val(start);
                        end = start;
                    }
                    
                    if (end > totalDomains) {
                        $endInput.val(totalDomains);
                        end = totalDomains;
                    }
                    
                    let totalAssigned = 0;
                    $('.batch-item').each(function() {
                        const $batch = $(this);
                        const bStart = parseInt($batch.find('.domain-start').val()) || 0;
                        let bEnd = parseInt($batch.find('.domain-end').val()) || 0;
                        
                        if ($batch.is(batchCard)) {
                            bEnd = end;
                        }
                        
                        if (bStart > 0 && bEnd > 0 && bEnd >= bStart) {
                            totalAssigned += (bEnd - bStart + 1);
                        }
                    });
                    
                    if (totalAssigned > totalDomains) {
                        const excessDomains = totalAssigned - totalDomains;
                        const adjustedEnd = end - excessDomains;
                        if (adjustedEnd >= start) {
                            $endInput.val(adjustedEnd);
                            end = adjustedEnd;
                        }
                    }
                }
                
                updateBatchCalculations(batchCard);
                updateSubsequentBatches(batchCard);
            });

            $('#batches-container').on('input', '.domain-end', function() {
                const batchCard = $(this).closest('.batch-item');
                updateBatchCalculations(batchCard);
            });

            $('#inboxes_per_domain').on('change', function() {
                inboxesPerDomain = parseInt($(this).val()) || 1;
                updateAllBatchCalculations();
            });

            $('#domains').on('input', debounce(function() {
                updateDomainCount();
            }, 500));

            $('select[name="hosting_platform"]').on('change', function() {
                const selectedPlatform = $('option:selected', this).data('provider');
                if (selectedPlatform) {
                    providerType = selectedPlatform;
                    if (isManualMode()) {
                        loadAvailablePanels();
                    }
                }
            });
        }

        function loadAvailablePanels() {
            return $.ajax({
                url: '{{ route("admin.pools.getAvailablePanels") }}',
                method: 'GET',
                data: {},
                success: function(response) {
                    if (response.success) {
                        availablePanels = response.panels || [];
                        if (response.provider_type) {
                            providerType = response.provider_type;
                        }
                        updatePanelDropdowns();
                    }
                },
                error: function(xhr) {
                    console.error('Failed to load available panels:', xhr);
                    alert('Failed to load available panels. Please try again.');
                }
            });
        }

        function updatePanelDropdowns() {
            const $selects = $('.panel-select');
            const selectedPanelIds = [];

            $selects.each(function() {
                const panelId = $(this).val();
                if (panelId && panelId !== '') {
                    selectedPanelIds.push(String(panelId));
                }
            });
            
            $selects.each(function() {
                const $currentSelect = $(this);
                const currentValue = String($currentSelect.val() || '');
                
                // Clear and add placeholder
                $currentSelect.empty();
                const $placeholder = $('<option></option>').attr('value', '').text('Select Panel...');
                $currentSelect.append($placeholder);
                
                availablePanels.forEach(panel => {
                    const panelIdStr = String(panel.id);
                    const isUsedInOtherBatch = selectedPanelIds.includes(panelIdStr) && panelIdStr !== currentValue;
                    
                    if (!isUsedInOtherBatch) {
                        const optionText = panel.auto_generated_id + ' - ' + panel.title + ' (' + panel.remaining_limit + '/' + panel.limit + ' available)';
                        const $option = $('<option></option>')
                            .attr('value', panel.id)
                            .text(optionText);
                        $currentSelect.append($option);
                    }
                });
                
                if (currentValue && currentValue !== '') {
                    $currentSelect.val(currentValue);
                }
            });
        }

        function loadPanelCapacity(panelId, batchCard) {
            $.ajax({
                url: '/admin/pools/panels/' + panelId + '/capacity',
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const panel = response.panel;
                        batchCard.data('panel-capacity', panel);
                        const $capacityInfo = $('<span></span>')
                            .append($('<i class="fas fa-info-circle me-1"></i>'))
                            .append('Available: ' + panel.remaining_limit + ' / ' + panel.limit + ' inboxes');
                        batchCard.find('.panel-capacity-info').html($capacityInfo);
                        validateBatch(batchCard);
                        updatePanelMeta(batchCard);
                    }
                },
                error: function(xhr) {
                    console.error('Failed to load panel capacity:', xhr);
                }
            });
        }

        function addBatch() {
            const template = document.getElementById('batch-template');
            const clone = template.content.cloneNode(true);
            
            batchIndex++;
            const $batchItem = $(clone).find('.batch-item');
            $batchItem.attr('data-batch-index', batchIndex);
            $batchItem.find('.batch-number').text(batchIndex);
            
            let nextDomainStart = 1;
            const $batches = $('.batch-item');
            if ($batches.length > 0) {
                const $lastBatch = $batches.last();
                const lastEnd = parseInt($lastBatch.find('.domain-end').val()) || 0;
                if (lastEnd > 0) {
                    nextDomainStart = lastEnd + 1;
                }
            }
            
            $batchItem.find('.domain-start').val(nextDomainStart);
            
            $('#batches-container').append($batchItem);
            
            $('#batches-container .placeholder-card').remove();
            updatePanelDropdowns();
            updateAssignmentSummary();
        }

        function updateBatchNumbers() {
            $('.batch-item').each(function(index) {
                $(this).find('.batch-number').text(index + 1);
            });
        }

        function updateSubsequentBatches(changedBatchCard) {
            const changedEnd = parseInt(changedBatchCard.find('.domain-end').val()) || 0;
            
            if (changedEnd > 0) {
                let nextStart = changedEnd + 1;
                let foundChanged = false;
                
                $('.batch-item').each(function() {
                    const $currentBatch = $(this);
                    
                    if (!foundChanged) {
                        if ($currentBatch.is(changedBatchCard)) {
                            foundChanged = true;
                        }
                        return;
                    }
                    
                    $currentBatch.find('.domain-start').val(nextStart);
                    
                    const currentEnd = parseInt($currentBatch.find('.domain-end').val()) || 0;
                    if (currentEnd > 0) {
                        updateBatchCalculations($currentBatch);
                        nextStart = currentEnd + 1;
                    }
                });
            }
        }

        function updateBatchCalculations(batchCard) {
            const start = parseInt(batchCard.find('.domain-start').val()) || 0;
            const end = parseInt(batchCard.find('.domain-end').val()) || 0;
            
            if (start > 0 && end > 0 && end >= start) {
                const domainCount = end - start + 1;
                const spaceNeeded = domainCount * inboxesPerDomain;
                
                batchCard.find('.domain-count-display').text(domainCount);
                batchCard.find('.domain-range-display').text(start + ' - ' + end);
                batchCard.find('.batch-space-display').text(spaceNeeded);
                batchCard.find('.batch-space-value').val(spaceNeeded);
                
                validateBatch(batchCard);
            }
            
            updateAssignmentSummary();
            updatePanelMeta(batchCard);
        }

        function updateAllBatchCalculations() {
            $('.batch-item').each(function() {
                updateBatchCalculations($(this));
            });
        }

        function validateBatch(batchCard) {
            const start = parseInt(batchCard.find('.domain-start').val()) || 0;
            const end = parseInt(batchCard.find('.domain-end').val()) || 0;
            const spaceNeeded = parseInt(batchCard.find('.batch-space-value').val()) || 0;
            const panelId = batchCard.find('.panel-select').val();
            
            const errors = [];
            
            if (start < 1 || end < 1) {
                errors.push('Domain range must start from 1');
            }
            if (end < start) {
                errors.push('End domain must be >= start domain');
            }
            if (end > totalDomains) {
                errors.push('End domain cannot exceed total domains (' + totalDomains + ')');
            }
            
            if (panelId && spaceNeeded > 0) {
                const panel = availablePanels.find(p => p.id == panelId);
                if (panel && spaceNeeded > panel.remaining_limit) {
                    errors.push('Space needed (' + spaceNeeded + ') exceeds panel capacity (' + panel.remaining_limit + ')');
                }
            }
            
            const $statusDiv = batchCard.find('.batch-validation-status');
            if (errors.length > 0) {
                const $alert = $('<div class="alert alert-danger alert-sm mb-0"></div>')
                    .append($('<i class="fas fa-exclamation-triangle me-1"></i>'))
                    .append(errors.join('<br>'));
                $statusDiv.html($alert);
            } else if (panelId && start > 0 && end > 0) {
                const $alert = $('<div class="alert alert-success alert-sm mb-0"></div>')
                    .append($('<i class="fas fa-check-circle me-1"></i>'))
                    .append('Valid assignment');
                $statusDiv.html($alert);
            } else {
                $statusDiv.empty();
            }
        }

        function updateAssignmentSummary() {
            let assignedDomains = 0;
            
            $('.batch-item').each(function() {
                const start = parseInt($(this).find('.domain-start').val()) || 0;
                const end = parseInt($(this).find('.domain-end').val()) || 0;
                
                if (start > 0 && end >= start) {
                    assignedDomains += (end - start + 1);
                }
            });
            
            const remaining = totalDomains - assignedDomains;
            const totalSpace = totalDomains * inboxesPerDomain;
            
            $('#assigned-domains-count').text(assignedDomains);
            $('#remaining-domains-count').text(remaining).toggleClass('text-danger', remaining !== 0);
            $('#total-space-needed').text(totalSpace);
            
            const $addBatchBtn = $('#add-batch-btn');
            if (totalDomains > 0 && remaining === 0) {
                $addBatchBtn.prop('disabled', true).addClass('disabled');
            } else {
                $addBatchBtn.prop('disabled', false).removeClass('disabled');
            }
            
            if (remaining !== 0 && totalDomains > 0) {
                const $alert = $('<div class="alert alert-warning"></div>')
                    .append($('<i class="fas fa-exclamation-triangle me-1"></i>'))
                    .append('You have ' + remaining + ' unassigned domains. All domains must be assigned.');
                $('#assignment-validation-messages').html($alert);
            } else if (totalDomains > 0 && remaining === 0) {
                const $alert = $('<div class="alert alert-success"></div>')
                    .append($('<i class="fas fa-check-circle me-1"></i>'))
                    .append('All domains are assigned!');
                $('#assignment-validation-messages').html($alert);
            } else {
                $('#assignment-validation-messages').empty();
            }
        }

        function updateDomainCount() {
            const domainsText = $('#domains').val().trim();
            if (!domainsText) {
                totalDomains = 0;
            } else {
                const domains = domainsText.split(/[\n,]+/).map(d => d.trim()).filter(d => d);
                totalDomains = domains.length;
            }
            
            $('#total-domains-count').text(totalDomains);
            updateAssignmentSummary();
        }

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function isManualMode() {
            return $('input[name=\"assignment_mode\"]:checked').val() === 'manual';
        }

        function collectValidBatches() {
            const validBatches = [];
            $('.batch-item').each(function() {
                const panelId = $(this).find('.panel-select').val();
                const domainStart = $(this).find('.domain-start').val();
                const domainEnd = $(this).find('.domain-end').val();
                const spaceNeeded = $(this).find('.batch-space-value').val();
                
                if (panelId && domainStart && domainEnd && spaceNeeded) {
                    validBatches.push({
                        panel_id: panelId,
                        domain_start: domainStart,
                        domain_end: domainEnd,
                        space_needed: spaceNeeded
                    });
                }
            });
            return validBatches;
        }

        function appendAssignments(formData) {
            const cleanedFormData = formData.filter(item => !item.name.startsWith('manual_assignments'));
            
            if (!isManualMode()) {
                return cleanedFormData;
            }

            const validBatches = collectValidBatches();
            validBatches.forEach((batch, index) => {
                cleanedFormData.push({ name: 'manual_assignments[' + index + '][panel_id]', value: batch.panel_id });
                cleanedFormData.push({ name: 'manual_assignments[' + index + '][domain_start]', value: batch.domain_start });
                cleanedFormData.push({ name: 'manual_assignments[' + index + '][domain_end]', value: batch.domain_end });
                cleanedFormData.push({ name: 'manual_assignments[' + index + '][space_needed]', value: batch.space_needed });
            });

            return cleanedFormData;
        }

        function updatePanelMeta(batchCard) {
            const panelId = batchCard.find('.panel-select').val();
            const metaBox = batchCard.find('.panel-detail-box');

            if (!panelId) {
                metaBox.hide();
                return;
            }

            let panel = batchCard.data('panel-capacity');
            if (!panel) {
                panel = availablePanels.find(p => String(p.id) === String(panelId));
            }
            if (!panel) {
                metaBox.hide();
                return;
            }

            const spaceNeeded = parseInt(batchCard.find('.batch-space-value').val()) || 0;
            const remainingAfter = Math.max(panel.remaining_limit - spaceNeeded, 0);
            const used = Math.max(panel.limit - panel.remaining_limit, 0);
            const percentUsed = panel.limit ? Math.min((used / panel.limit) * 100, 100) : 0;
            const percentAfter = panel.limit ? Math.min(((used + spaceNeeded) / panel.limit) * 100, 100) : 0;
            const futureSegment = Math.max(percentAfter - percentUsed, 0);

            metaBox.find('.panel-name').text(panel.auto_generated_id || panel.title || ('Panel ' + panel.id));
            metaBox.find('.remaining-count').text(panel.remaining_limit + ' free');
            metaBox.find('.capacity-count').text(panel.limit + ' total');
            metaBox.find('.after-count').text(remainingAfter + ' free after');
            metaBox.find('.meta-footnote').text('Using ' + used + ' of ' + panel.limit + ' now • This batch needs ' + spaceNeeded);

            const currentBar = metaBox.find('.meta-progress-current');
            const futureBar = metaBox.find('.meta-progress-future');
            currentBar.css('width', percentUsed + '%');
            futureBar.css({
                width: futureSegment + '%',
                marginLeft: percentUsed + '%'
            });

            metaBox.show();
        }

        return {
            init,
            getProviderType: () => providerType,
            appendAssignments,
            collectValidBatches,
            isManualMode
        };
    })(jQuery);

    $(document).ready(function() {
        if (window.manualPanelAssignment && typeof window.manualPanelAssignment.init === 'function') {
            window.manualPanelAssignment.init();
        }
    });
</script>
@endpush
@endonce
<!-- this panel assigment section is attractive also panel v -->