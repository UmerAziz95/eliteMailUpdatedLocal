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
        <div class="assignment-layout">
            {{-- Left Column: Batch Form --}}
            <div class="assignment-form-column">
                <div class="form-section-header">
                    <h6 class="section-title">
                        <i class="fas fa-edit me-2"></i>Batch Configuration
                    </h6>
                    <button type="button" class="btn btn-success btn-sm" id="add-batch-btn">
                        <i class="fas fa-plus me-1"></i>Add Batch
                    </button>
                </div>

                {{-- Batch List --}}
                <div id="batches-container" class="batches-form-list">
                    <div class="placeholder-card">
                        <i class="fas fa-layer-group me-2"></i>No batches yet. Click "Add Batch" to start.
                    </div>
                </div>
            </div>

            {{-- Right Column: Assignment Summary --}}
            <div class="assignment-summary-column">
                <div class="summary-section-header">
                    <h6 class="section-title">
                        <i class="fas fa-chart-bar me-2"></i>Assignment Overview
                    </h6>
                </div>

                {{-- Summary Stats --}}
                <div class="summary-stats">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Domains</div>
                            <div class="stat-value" id="total-domains-count">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon assigned">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Assigned</div>
                            <div class="stat-value" id="assigned-domains-count">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon remaining">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Remaining</div>
                            <div class="stat-value text-warning" id="remaining-domains-count">0</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon space">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="stat-info">
                            <div class="stat-label">Total Space</div>
                            <div class="stat-value"><span id="total-space-needed">0</span> <small>inboxes</small></div>
                        </div>
                    </div>
                </div>

                {{-- Validation Messages --}}
                <div id="assignment-validation-messages" class="mt-3"></div>

                {{-- Panel Summary Section --}}
                <div class="panel-summary-section">
                    <h6 class="preview-title">
                        <i class="fas fa-server me-2"></i>Panel Usage
                    </h6>
                    <div id="panels-summary-list">
                        <div class="preview-placeholder">
                            <i class="fas fa-server"></i>
                            <p>No panels assigned yet</p>
                        </div>
                    </div>
                </div>

                {{-- Assignment List Preview --}}
                <div class="assignment-preview-section">
                    <h6 class="preview-title">
                        <i class="fas fa-list-ul me-2"></i>Batch Assignments
                    </h6>
                    <div id="batches-preview-list">
                        <div class="preview-placeholder">
                            <i class="fas fa-inbox"></i>
                            <p>No assignments yet</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Batch Template (Hidden) --}}
<template id="batch-template">
    <div class="batch-item" data-batch-index="">
        <div class="batch-header">
            <div class="batch-title">
                <span class="batch-number-badge"></span>
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
        --accent: var(--second-primary, #5a49cd);
        --accent-2: var(--primary-color, #4f46e5);
        --bg-1: #0f172a;
        --bg-2: #0d1324;
        background: radial-gradient(circle at 20% 20%, color-mix(in srgb, var(--accent) 18%, transparent), transparent 35%),
            radial-gradient(circle at 80% 0%, color-mix(in srgb, var(--accent-2) 14%, transparent), transparent 30%),
            linear-gradient(145deg, var(--bg-1) 0%, var(--bg-2) 100%);
        border-radius: 16px;
        padding: 24px;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.45);
    }

    /* ===== Two Column Layout ===== */
    .assignment-layout {
        display: grid;
        grid-template-columns: 1fr 420px;
        gap: 24px;
        margin-top: 20px;
    }

    @media (max-width: 1200px) {
        .assignment-layout {
            grid-template-columns: 1fr;
        }
    }

    /* ===== Form Column ===== */
    .assignment-form-column {
        min-height: 400px;
    }

    .form-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 2px solid rgba(99, 102, 241, 0.2);
    }

    .form-section-header .section-title {
        color: #f1f5f9;
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
    }

    .batches-form-list {
        max-height: 70vh;
        overflow-y: auto;
        padding-right: 8px;
    }

    .batches-form-list::-webkit-scrollbar {
        width: 8px;
    }

    .batches-form-list::-webkit-scrollbar-track {
        background: rgba(30, 41, 59, 0.4);
        border-radius: 4px;
    }

    .batches-form-list::-webkit-scrollbar-thumb {
        background: rgba(99, 102, 241, 0.4);
        border-radius: 4px;
    }

    .batches-form-list::-webkit-scrollbar-thumb:hover {
        background: rgba(99, 102, 241, 0.6);
    }

    /* ===== Summary Column ===== */
    .assignment-summary-column {
        background: linear-gradient(145deg, #1e293b, #0f172a);
        border: 1px solid rgba(99, 102, 241, 0.25);
        border-radius: 16px;
        padding: 20px;
        position: sticky;
        top: 20px;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }

    .summary-section-header {
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid rgba(99, 102, 241, 0.2);
    }

    .summary-section-header .section-title {
        color: #f1f5f9;
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0;
    }

    /* ===== Summary Stats Cards ===== */
    .summary-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    .stat-card {
        background: rgba(15, 23, 42, 0.6);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 12px;
        padding: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        border-color: rgba(99, 102, 241, 0.4);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2);
    }

    .stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .stat-icon.total {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: #fff;
    }

    .stat-icon.assigned {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff;
    }

    .stat-icon.remaining {
        background: linear-gradient(135deg, #f59e0b, #d97706);
        color: #fff;
    }

    .stat-icon.space {
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: #fff;
    }

    .stat-info {
        flex: 1;
    }

    .stat-label {
        color: #94a3b8;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .stat-value {
        color: #f1f5f9;
        font-size: 1.4rem;
        font-weight: 800;
        line-height: 1;
    }

    .stat-value small {
        font-size: 0.6rem;
        color: #94a3b8;
        font-weight: 600;
    }

    /* ===== Assignment Preview List ===== */
    .assignment-preview-section {
        margin-top: 24px;
    }

    .preview-title {
        color: #cbd5e1;
        font-size: 0.9rem;
        font-weight: 700;
        margin-bottom: 12px;
    }

    #batches-preview-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .preview-placeholder {
        text-align: center;
        padding: 40px 20px;
        color: #64748b;
    }

    .preview-placeholder i {
        font-size: 2.5rem;
        margin-bottom: 12px;
        opacity: 0.5;
    }

    .preview-placeholder p {
        margin: 0;
        font-size: 0.9rem;
    }

    .preview-item {
        background: rgba(15, 23, 42, 0.4);
        border: 1px solid rgba(99, 102, 241, 0.15);
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 10px;
        transition: all 0.3s ease;
    }

    .preview-item:hover {
        border-color: rgba(99, 102, 241, 0.3);
        background: rgba(15, 23, 42, 0.6);
    }

    .preview-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .preview-header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .preview-batch-name {
        color: #f1f5f9;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .preview-batch-badge {
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        color: #fff;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 700;
    }

    .preview-item-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        font-size: 0.75rem;
    }

    .preview-detail {
        color: #94a3b8;
    }

    .preview-detail strong {
        color: #cbd5e1;
        font-weight: 600;
    }

    .preview-panel-info {
        background: rgba(59, 130, 246, 0.1);
        border-left: 3px solid #3b82f6;
        padding: 8px 10px;
        margin-top: 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        color: #93c5fd;
    }

    .preview-panel-info i {
        margin-right: 6px;
    }

    .btn-remove-preview {
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        color: #fca5a5;
        border-radius: 6px;
        padding: 4px 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
    }

    .btn-remove-preview:hover {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border-color: #ef4444;
        color: #fff;
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }

    #manual-assignment-section h5 {
        color: #f8fafc;
        font-weight: 700;
        font-size: 1.35rem;
        margin-bottom: 1.5rem;
    }

    /* ===== Assignment Mode Toggle ===== */
    .panel-assignment-toggle {
        --accent: var(--second-primary, #5a49cd);
        --accent-2: var(--primary-color, #4f46e5);
        position: relative;
        padding: 14px;
        border-radius: 14px;
        background: linear-gradient(135deg, color-mix(in srgb, var(--accent) 12%, transparent), color-mix(in srgb, var(--accent-2) 12%, transparent));
        border: 1px solid rgba(255, 255, 255, 0.06);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
    }

    .panel-assignment-toggle::after {
        content: '';
        position: absolute;
        inset: 6px;
        border-radius: 10px;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0));
        pointer-events: none;
    }

    .panel-assignment-toggle .form-label {
        color: #f8fafc;
        font-size: 0.95rem;
        margin-bottom: 12px;
        letter-spacing: 0.01em;
    }

    .panel-assignment-toggle .btn-group {
        background: rgba(255, 255, 255, 0.04);
        border-radius: 12px;
        padding: 4px;
        gap: 6px;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.08);
    }

    .panel-assignment-toggle .btn-outline-primary {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.04));
        border: 1px solid rgba(255, 255, 255, 0.08);
        color: var(--light-color, #e2e8f0);
        font-weight: 700;
        padding: 12px 18px;
        border-radius: 10px !important;
        transition: all 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        position: relative;
        overflow: hidden;
        isolation: isolate;
    }

    .panel-assignment-toggle .btn-outline-primary::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 20% 20%, color-mix(in srgb, var(--accent) 22%, transparent), transparent 50%),
            radial-gradient(circle at 80% 0%, color-mix(in srgb, var(--accent-2) 18%, transparent), transparent 40%);
        opacity: 0;
        transition: opacity 0.2s ease;
        z-index: -1;
    }

    .panel-assignment-toggle .btn-outline-primary:hover::before {
        opacity: 1;
    }

    .panel-assignment-toggle .btn-outline-primary:hover {
        border-color: color-mix(in srgb, var(--accent-2) 70%, transparent);
        color: var(--light-color, #e2e8f0);
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.35), 0 0 0 1px color-mix(in srgb, var(--accent) 35%, transparent);
    }

    .panel-assignment-toggle .btn-check:checked + .btn-outline-primary {
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        border-color: color-mix(in srgb, var(--accent-2) 70%, transparent);
        color: #fff;
        box-shadow: 0 12px 30px color-mix(in srgb, var(--accent-2) 55%, transparent), 0 0 0 1px rgba(255, 255, 255, 0.08) inset;
    }

    .panel-assignment-toggle .btn-check:checked + .btn-outline-primary::before {
        opacity: 1;
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
        border-radius: 12px;
        margin-bottom: 16px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    #manual-assignment-section .batch-item:hover {
        border-color: rgba(99, 102, 241, 0.45);
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.25);
    }

    /* ===== Batch Header ===== */
    #manual-assignment-section .batch-header {
        background: linear-gradient(135deg, #334155 0%, #1e293b 100%);
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(99, 102, 241, 0.2);
    }

    #manual-assignment-section .batch-title {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    #manual-assignment-section .batch-number-badge {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.9rem;
        font-weight: 800;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }

    #manual-assignment-section .batch-label {
        color: #f1f5f9;
        font-weight: 700;
        font-size: 0.95rem;
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
        padding: 16px;
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

    /* ===== Panel Summary Section ===== */
    .panel-summary-section {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid rgba(99, 102, 241, 0.15);
    }

    #panels-summary-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .panel-summary-card {
        background: linear-gradient(135deg, rgba(15, 23, 42, 0.6), rgba(30, 41, 59, 0.4));
        border: 1px solid rgba(99, 102, 241, 0.25);
        border-radius: 12px;
        padding: 16px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
    }

    .panel-summary-card:hover {
        border-color: rgba(99, 102, 241, 0.4);
        transform: translateX(4px);
        box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2);
    }

    .panel-summary-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(99, 102, 241, 0.15);
    }

    .panel-summary-icon {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #3b82f6, #6366f1);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.95rem;
    }

    .panel-summary-title {
        flex: 1;
    }

    .panel-summary-name {
        color: #f1f5f9;
        font-weight: 700;
        font-size: 0.9rem;
        display: block;
        margin-bottom: 2px;
    }

    .panel-summary-id {
        color: #94a3b8;
        font-size: 0.7rem;
    }

    .panel-summary-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
    }

    .panel-stat-item {
        background: rgba(15, 23, 42, 0.5);
        border: 1px solid rgba(148, 163, 184, 0.1);
        border-radius: 8px;
        padding: 10px;
        text-align: center;
    }

    .panel-stat-item.highlight {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(99, 102, 241, 0.15));
        border-color: rgba(99, 102, 241, 0.3);
    }

    .panel-stat-label {
        color: #94a3b8;
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 4px;
        display: block;
    }

    .panel-stat-value {
        color: #f1f5f9;
        font-weight: 700;
        font-size: 0.95rem;
    }

    .panel-stat-value.success {
        color: #10b981;
    }

    .panel-stat-value.warning {
        color: #f59e0b;
    }

    .panel-stat-value.danger {
        color: #ef4444;
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
            
            // Remove batch from left side form
            $('#batches-container').on('click', '.remove-batch-btn', function() {
                $(this).closest('.batch-item').remove();
                
                // If no batches left, show placeholder
                if ($('.batch-item').length === 0) {
                    $('#batches-container').html('<div class="placeholder-card"><i class="fas fa-layer-group me-2"></i>No batches yet. Click "Add Batch" to start.</div>');
                }
                
                updateBatchNumbers();
                recalculateBatchRanges();
                updateAssignmentSummary();
                updatePanelDropdowns();
                updatePreviewList();
                updatePanelSummary();
            });

            // Remove batch from right side preview
            $('#batches-preview-list').on('click', '.btn-remove-preview', function() {
                const $previewItem = $(this).closest('.preview-item');
                const batchIndex = $previewItem.attr('data-batch-index');
                
                // Find and remove the corresponding batch from the form
                const $batchToRemove = $('.batch-item[data-batch-index="' + batchIndex + '"]');
                if ($batchToRemove.length) {
                    $batchToRemove.remove();
                    
                    // If no batches left, show placeholder
                    if ($('.batch-item').length === 0) {
                        $('#batches-container').html('<div class="placeholder-card"><i class="fas fa-layer-group me-2"></i>No batches yet. Click "Add Batch" to start.</div>');
                    }
                    
                    updateBatchNumbers();
                    recalculateBatchRanges();
                    updateAssignmentSummary();
                    updatePanelDropdowns();
                    updatePreviewList();
                    updatePanelSummary();
                }
            });

            $('#batches-container').on('change', '.panel-select', function() {
                const panelId = $(this).val();
                const batchCard = $(this).closest('.batch-item');
                
                if (panelId) {
                    loadPanelCapacity(panelId, batchCard);
                }
                
                updatePanelDropdowns();
                updatePanelSummary();
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
                updatePanelSummary();
            });

            $('#batches-container').on('input', '.domain-end', function() {
                const batchCard = $(this).closest('.batch-item');
                updateBatchCalculations(batchCard);
                updatePanelSummary();
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
            
            updatePanelSummary();
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
                    }
                },
                error: function(xhr) {
                    console.error('Failed to load panel capacity:', xhr);
                }
            });
        }

        function addBatch() {
            // Validate before adding new batch
            if (!canAddNewBatch()) {
                const $batches = $('.batch-item');
                if ($batches.length === 0) {
                    alert('Please add domains first before creating batches.');
                } else {
                    const hasValidationErrors = $batches.find('.alert-danger').length > 0;
                    if (hasValidationErrors) {
                        alert('Please fix all validation errors in existing batches before adding a new one.');
                    } else {
                        alert('Please fill in the end domain and select a panel for the last batch before adding a new one.');
                    }
                }
                return;
            }
            
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
            updateAllBatchCalculations();
            updatePanelDropdowns();
            updateAssignmentSummary();
            updatePreviewList();
        }

        function updateBatchNumbers() {
            $('.batch-item').each(function(index) {
                $(this).find('.batch-number').text(index + 1);
                $(this).find('.batch-number-badge').text(index + 1);
            });
            updatePreviewList();
        }

        function canAddNewBatch() {
            const $batches = $('.batch-item');
            
            // If no batches exist, can add
            if ($batches.length === 0) {
                return true;
            }
            
            // Check if any batch has validation errors
            let hasErrors = false;
            $batches.each(function() {
                const $batch = $(this);
                const $validationStatus = $batch.find('.batch-validation-status');
                const hasErrorAlert = $validationStatus.find('.alert-danger').length > 0;
                
                if (hasErrorAlert) {
                    hasErrors = true;
                    return false; // break the loop
                }
                
                // Also check if required fields are missing
                const panelId = $batch.find('.panel-select').val();
                const endDomain = parseInt($batch.find('.domain-end').val()) || 0;
                
                if (!panelId || endDomain === 0) {
                    hasErrors = true;
                    return false; // break the loop
                }
            });
            
            if (hasErrors) {
                return false;
            }
            
            // Check if last batch has valid end domain
            const $lastBatch = $batches.last();
            const lastEnd = parseInt($lastBatch.find('.domain-end').val()) || 0;
            
            // Must have a valid end domain value
            return lastEnd > 0;
        }

        function updateAddBatchButton() {
            const $addBtn = $('#add-batch-btn');
            const remaining = totalDomains - getAssignedDomainsCount();
            
            if (totalDomains === 0) {
                // No domains to assign
                $addBtn.prop('disabled', true).addClass('disabled');
            } else if (remaining === 0) {
                // All domains assigned
                $addBtn.prop('disabled', true).addClass('disabled');
            } else if (!canAddNewBatch()) {
                // Last batch doesn't have valid end domain
                $addBtn.prop('disabled', true).addClass('disabled');
            } else {
                // Can add new batch
                $addBtn.prop('disabled', false).removeClass('disabled');
            }
        }

        function getAssignedDomainsCount() {
            let assignedDomains = 0;
            $('.batch-item').each(function() {
                const start = parseInt($(this).find('.domain-start').val()) || 0;
                const end = parseInt($(this).find('.domain-end').val()) || 0;
                
                if (start > 0 && end >= start) {
                    assignedDomains += (end - start + 1);
                }
            });
            return assignedDomains;
        }

        function recalculateBatchRanges() {
            let nextStart = 1;
            
            $('.batch-item').each(function() {
                const $batch = $(this);
                const currentStart = parseInt($batch.find('.domain-start').val()) || 0;
                const currentEnd = parseInt($batch.find('.domain-end').val()) || 0;
                
                // Update start to the next available position
                $batch.find('.domain-start').val(nextStart);
                
                if (currentEnd > 0 && currentStart > 0) {
                    // Calculate the range size from the old batch
                    const rangeSize = currentEnd - currentStart;
                    // Set new end based on the new start
                    const newEnd = nextStart + rangeSize;
                    $batch.find('.domain-end').val(newEnd);
                    nextStart = newEnd + 1;
                } else if (currentEnd > 0) {
                    // If only end was set, keep it relative to new start
                    nextStart = currentEnd + 1;
                }
                
                // Update calculations for this batch
                updateBatchCalculations($batch);
            });
        }

        function updatePreviewList() {
            const $previewList = $('#batches-preview-list');
            const $batches = $('.batch-item');
            
            if ($batches.length === 0) {
                $previewList.html(`
                    <div class="preview-placeholder">
                        <i class="fas fa-inbox"></i>
                        <p>No assignments yet</p>
                    </div>
                `);
                return;
            }
            
            $previewList.empty();
            
            $batches.each(function(index) {
                const $batch = $(this);
                const batchNum = index + 1;
                const panelId = $batch.find('.panel-select').val();
                const panelText = $batch.find('.panel-select option:selected').text() || 'Not selected';
                const domainStart = parseInt($batch.find('.domain-start').val()) || 0;
                const domainEnd = parseInt($batch.find('.domain-end').val()) || 0;
                const domainCount = (domainEnd >= domainStart && domainStart > 0) ? (domainEnd - domainStart + 1) : 0;
                const spaceNeeded = parseInt($batch.find('.batch-space-value').val()) || 0;
                
                const previewHtml = `
                    <div class="preview-item" data-batch-index="${$batch.attr('data-batch-index')}">
                        <div class="preview-item-header">
                            <span class="preview-batch-name">
                                <i class="fas fa-layer-group me-1"></i>Batch ${batchNum}
                            </span>
                            <div class="preview-header-actions">
                                <span class="preview-batch-badge">${domainCount} domains</span>
                                <button type="button" class="btn-remove-preview" title="Remove batch">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="preview-item-details">
                            <div class="preview-detail">
                                <i class="fas fa-play me-1"></i><strong>${domainStart}</strong>
                            </div>
                            <div class="preview-detail">
                                <i class="fas fa-stop me-1"></i><strong>${domainEnd}</strong>
                            </div>
                            <div class="preview-detail" style="grid-column: 1 / -1;">
                                <i class="fas fa-database me-1"></i>Space: <strong>${spaceNeeded} inboxes</strong>
                            </div>
                        </div>
                        ${panelId ? `
                            <div class="preview-panel-info">
                                <i class="fas fa-server"></i>${panelText.substring(0, 40)}${panelText.length > 40 ? '...' : ''}
                            </div>
                        ` : ''}
                    </div>
                `;
                
                $previewList.append(previewHtml);
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
            updatePreviewList();
            updatePanelSummary();
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
            
            // Update add batch button state after validation
            updateAddBatchButton();
        }

        function updateAssignmentSummary() {
            const assignedDomains = getAssignedDomainsCount();
            const remaining = totalDomains - assignedDomains;
            const totalSpace = totalDomains * inboxesPerDomain;
            
            $('#assigned-domains-count').text(assignedDomains);
            $('#remaining-domains-count').text(remaining).toggleClass('text-danger', remaining !== 0);
            $('#total-space-needed').text(totalSpace);
            
            updateAddBatchButton();
            
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

        function updatePanelSummary() {
            const $panelsList = $('#panels-summary-list');
            const panelUsage = {};
            
            // Collect usage data for each panel
            $('.batch-item').each(function() {
                const $batch = $(this);
                const panelId = $batch.find('.panel-select').val();
                const spaceNeeded = parseInt($batch.find('.batch-space-value').val()) || 0;
                
                if (panelId && spaceNeeded > 0) {
                    if (!panelUsage[panelId]) {
                        panelUsage[panelId] = {
                            totalAssigned: 0,
                            batches: 0
                        };
                    }
                    panelUsage[panelId].totalAssigned += spaceNeeded;
                    panelUsage[panelId].batches++;
                }
            });
            
            const panelIds = Object.keys(panelUsage);
            
            if (panelIds.length === 0) {
                $panelsList.html(`
                    <div class="preview-placeholder">
                        <i class="fas fa-server"></i>
                        <p>No panels assigned yet</p>
                    </div>
                `);
                return;
            }
            
            $panelsList.empty();
            
            panelIds.forEach(panelId => {
                const panel = availablePanels.find(p => String(p.id) === String(panelId));
                if (!panel) return;
                
                const usage = panelUsage[panelId];
                const available = panel.remaining_limit;
                const total = panel.limit;
                const afterAssignment = Math.max(available - usage.totalAssigned, 0);
                const percentUsed = total > 0 ? Math.round(((total - available) / total) * 100) : 0;
                const percentAfter = total > 0 ? Math.round(((total - afterAssignment) / total) * 100) : 0;
                
                let afterClass = 'success';
                if (percentAfter >= 90) afterClass = 'danger';
                else if (percentAfter >= 70) afterClass = 'warning';
                
                const cardHtml = `
                    <div class="panel-summary-card">
                        <div class="panel-summary-header">
                            <div class="panel-summary-icon">
                                <i class="fas fa-server"></i>
                            </div>
                            <div class="panel-summary-title">
                                <span class="panel-summary-name">${panel.title || 'Panel'}</span>
                                <span class="panel-summary-id">${panel.auto_generated_id || 'ID: ' + panel.id}</span>
                            </div>
                        </div>
                        <div class="panel-summary-stats">
                            <div class="panel-stat-item">
                                <span class="panel-stat-label">Available</span>
                                <div class="panel-stat-value">${available}</div>
                            </div>
                            <div class="panel-stat-item">
                                <span class="panel-stat-label">Total</span>
                                <div class="panel-stat-value">${total}</div>
                            </div>
                            <div class="panel-stat-item highlight">
                                <span class="panel-stat-label">After Assignment</span>
                                <div class="panel-stat-value ${afterClass}">${afterAssignment}</div>
                            </div>
                            <div class="panel-stat-item">
                                <span class="panel-stat-label">Usage After</span>
                                <div class="panel-stat-value ${afterClass}">${percentAfter}%</div>
                            </div>
                        </div>
                    </div>
                `;
                
                $panelsList.append(cardHtml);
            });
        }

        function validateManualAssignments() {
            if (!isManualMode()) {
                return { valid: true };
            }

            const $batches = $('.batch-item');
            const errors = [];

            // Check if there are any batches
            if ($batches.length === 0) {
                errors.push('Please add at least one batch assignment.');
                return { valid: false, errors: errors };
            }

            // Check each batch for validation errors
            $batches.each(function(index) {
                const $batch = $(this);
                const batchNum = index + 1;
                const panelId = $batch.find('.panel-select').val();
                const startDomain = parseInt($batch.find('.domain-start').val()) || 0;
                const endDomain = parseInt($batch.find('.domain-end').val()) || 0;
                const hasErrorAlert = $batch.find('.batch-validation-status .alert-danger').length > 0;

                if (!panelId) {
                    errors.push('Batch ' + batchNum + ': Please select a panel.');
                }

                if (endDomain === 0) {
                    errors.push('Batch ' + batchNum + ': Please specify the end domain.');
                }

                if (hasErrorAlert) {
                    const errorText = $batch.find('.batch-validation-status .alert-danger').text().trim();
                    errors.push('Batch ' + batchNum + ': ' + errorText.replace(/\s+/g, ' '));
                }
            });

            // Check if all domains are assigned
            const assignedDomains = getAssignedDomainsCount();
            const remaining = totalDomains - assignedDomains;
            
            if (remaining > 0) {
                errors.push('You have ' + remaining + ' unassigned domains. All domains must be assigned.');
            }

            if (remaining < 0) {
                errors.push('You have assigned more domains than available. Please check your batch ranges.');
            }

            return {
                valid: errors.length === 0,
                errors: errors
            };
        }

        return {
            init,
            getProviderType: () => providerType,
            appendAssignments,
            collectValidBatches,
            isManualMode,
            validateManualAssignments
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
