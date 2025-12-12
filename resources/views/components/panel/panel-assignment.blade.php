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
    <div class="card mb-3 batch-item" data-batch-index="">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <h6 class="mb-0">Batch <span class="batch-number"></span></h6>
            <button type="button" class="btn btn-sm btn-danger remove-batch-btn" title="Remove batch">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                {{-- Panel Selection --}}
                <div class="col-md-4 mb-3">
                    <label class="form-label">Panel</label>
                    <select class="form-select panel-select" name="manual_assignments[][panel_id]">
                        <option value="">Select Panel...</option>
                    </select>
                    <small class=" panel-capacity-info"></small>
                </div>

                {{-- Domain Range Selection --}}
                <div class="col-md-3 mb-3">
                    <label class="form-label">Start Domain</label>
                    <input type="number" class="form-control domain-start" name="manual_assignments[][domain_start]" min="1" placeholder="1" readonly>
                    <small class="">Auto-calculated</small>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">End Domain</label>
                    <input type="number" class="form-control domain-end" name="manual_assignments[][domain_end]" min="1" placeholder="100">
                </div>

                {{-- Batch Summary --}}
                <div class="col-md-2 mb-3">
                    <label class="form-label">Space Needed</label>
                    <input type="text" class="form-control batch-space-display" readonly placeholder="0">
                    <input type="hidden" class="batch-space-value" name="manual_assignments[][space_needed]">
                </div>
            </div>

            {{-- Panel Detail Snapshot --}}
            <div class="panel-detail-box mt-2" style="display: none;">
                <div class="detail-row">
                    <div class="detail-chip">
                        <span class="label"><i class="fas fa-server"></i> Panel</span>
                        <span class="value panel-name">-</span>
                    </div>
                    <div class="detail-chip">
                        <span class="label"><i class="fas fa-battery-three-quarters"></i> Remaining</span>
                        <span class="value remaining-count">-</span>
                    </div>
                    <div class="detail-chip">
                        <span class="label"><i class="fas fa-database"></i> Capacity</span>
                        <span class="value capacity-count">-</span>
                    </div>
                    <div class="detail-chip">
                        <span class="label"><i class="fas fa-forward"></i> After This Batch</span>
                        <span class="value after-count">-</span>
                    </div>
                </div>
            <div class="progress meta-progress mt-2">
                <div class="progress-bar meta-progress-current" role="progressbar" style="width: 0%"></div>
                <div class="progress-bar meta-progress-future" role="progressbar" style="width: 0%"></div>
            </div>
            <small class=" mt-1 d-block meta-footnote"></small>
        </div>

            {{-- Domain List Preview --}}
            <div class="mt-2">
                <small class="">
                    <strong>Domains:</strong> <span class="domain-count-display">0</span> domains
                    (<span class="domain-range-display">-</span>)
                </small>
            </div>

            {{-- Validation Status --}}
            <div class="batch-validation-status mt-2"></div>
        </div>
    </div>
</template>

@once
@push('styles')
<style>
    #manual-assignment-section .assignment-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }
    #manual-assignment-section .summary-tile {
        background: linear-gradient(135deg, #111827, #0b1020);
        border: 1px solid #1f2937;
        border-radius: 10px;
        padding: 12px;
        box-shadow: 0 12px 30px rgba(0,0,0,0.25);
    }
    #manual-assignment-section .summary-tile .label {
        text-transform: uppercase;
        font-size: 0.75rem;
        color: #9ca3af;
        letter-spacing: 0.04em;
    }
    #manual-assignment-section .summary-tile .value {
        font-size: 1.25rem;
        font-weight: 700;
        color: #f3f4f6;
    }
    #manual-assignment-section .placeholder-card {
        padding: 14px;
        border-radius: 8px;
        border: 1px dashed #4b5563;
        text-align: center;
        background: rgba(255,255,255,0.02);
    }
    #manual-assignment-section .batch-item {
        border: 1px solid #1f2937;
        background: #0f172a;
        color: #e5e7eb;
    }
    #manual-assignment-section .batch-item .card-header {
        background: linear-gradient(135deg, #1f2937, #111827);
        color: #f8fafc;
        border-bottom: 1px solid #111827;
    }
    #manual-assignment-section .alert {
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 10px;
        padding: 14px 16px;
        box-shadow: 0 10px 22px rgba(0,0,0,0.2);
        position: relative;
        overflow: hidden;
    }
    #manual-assignment-section .alert:before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 4px;
        height: 100%;
        opacity: 0.9;
    }
    #manual-assignment-section .alert-danger {
        background: rgba(127, 29, 29, 0.35);
        color: #fecdd3;
    }
    #manual-assignment-section .alert-danger:before {
        background: #f87171;
    }
    #manual-assignment-section .alert-warning {
        background: rgba(120, 53, 15, 0.35);
        color: #fde68a;
    }
    #manual-assignment-section .alert-warning:before {
        background: #f59e0b;
    }
    #manual-assignment-section .alert-success {
        background: rgba(6, 95, 70, 0.35);
        color: #a7f3d0;
    }
    #manual-assignment-section .alert-success:before {
        background: #10b981;
    }
    #manual-assignment-section .alert i {
        color: inherit;
    }
    #manual-assignment-section .panel-capacity-info {
        color: #94a3b8;
        font-size: 0.85rem;
    }
    #manual-assignment-section .panel-detail-box {
        border: 1px solid #1f2937;
        border-radius: 10px;
        padding: 12px;
        background: rgba(30, 41, 59, 0.55);
        margin-top: 10px;
    }
    #manual-assignment-section .panel-detail-box .detail-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 8px;
    }
    #manual-assignment-section .panel-detail-box .detail-chip {
        background: rgba(255,255,255,0.04);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: 8px;
        padding: 10px 12px;
        min-width: 140px;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    #manual-assignment-section .panel-detail-box .label {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #9ca3af;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    #manual-assignment-section .panel-detail-box .value {
        font-weight: 700;
        color: #e5e7eb;
        font-size: 0.95rem;
    }
    #manual-assignment-section .panel-detail-box .label i {
        color: #60a5fa;
        font-size: 0.9rem;
    }
    #manual-assignment-section .panel-detail-box .progress.meta-progress {
        height: 12px;
        background: rgba(255,255,255,0.06);
        border-radius: 999px;
        position: relative;
    }
    #manual-assignment-section .panel-detail-box .progress-bar.meta-progress-current {
        background: linear-gradient(90deg, #2563eb, #4f46e5);
        z-index: 1;
    }
    #manual-assignment-section .panel-detail-box .progress-bar.meta-progress-future {
        background: linear-gradient(90deg, #22c55e, #16a34a);
        z-index: 2;
    }
    #manual-assignment-section .panel-detail-box .progress.meta-progress .progress-bar {
        height: 12px;
    }
</style>
@endpush
@push('scripts')
<script>
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
                
                $currentSelect.empty().append('<option value=\"\">Select Panel...</option>');
                
                availablePanels.forEach(panel => {
                    const panelIdStr = String(panel.id);
                    const isUsedInOtherBatch = selectedPanelIds.includes(panelIdStr) && panelIdStr !== currentValue;
                    
                    if (!isUsedInOtherBatch) {
                        const optionText = `${panel.auto_generated_id} - ${panel.title} (${panel.remaining_limit}/${panel.limit} available)`;
                        $currentSelect.append(`<option value=\"${panel.id}\">${optionText}</option>`);
                    }
                });
                
                if (currentValue && currentValue !== '') {
                    $currentSelect.val(currentValue);
                }
            });
        }

        function loadPanelCapacity(panelId, batchCard) {
            $.ajax({
                url: `/admin/pools/panels/${panelId}/capacity`,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        const panel = response.panel;
                        batchCard.data('panel-capacity', panel);
                        const capacityHtml = `
                            <i class=\"fas fa-info-circle me-1\"></i>
                            Available: ${panel.remaining_limit} / ${panel.limit} inboxes
                        `;
                        batchCard.find('.panel-capacity-info').html(capacityHtml);
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
                batchCard.find('.domain-range-display').text(`${start} - ${end}`);
                batchCard.find('.batch-space-display').val(spaceNeeded);
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
                errors.push(`End domain cannot exceed total domains (${totalDomains})`);
            }
            
            if (panelId && spaceNeeded > 0) {
                const panel = availablePanels.find(p => p.id == panelId);
                if (panel && spaceNeeded > panel.remaining_limit) {
                    errors.push(`Space needed (${spaceNeeded}) exceeds panel capacity (${panel.remaining_limit})`);
                }
            }
            
            const $statusDiv = batchCard.find('.batch-validation-status');
            if (errors.length > 0) {
                $statusDiv.html(`
                    <div class=\"alert alert-danger alert-sm mb-0\">
                        <i class=\"fas fa-exclamation-triangle me-1\"></i>
                        ${errors.join('<br>')}
                    </div>
                `);
            } else if (panelId && start > 0 && end > 0) {
                $statusDiv.html(`
                    <div class=\"alert alert-success alert-sm mb-0\">
                        <i class=\"fas fa-check-circle me-1\"></i>
                        Valid assignment
                    </div>
                `);
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
                $('#assignment-validation-messages').html(`
                    <div class=\"alert alert-warning\">
                        <i class=\"fas fa-exclamation-triangle me-1\"></i>
                        You have ${remaining} unassigned domains. All domains must be assigned.
                    </div>
                `);
            } else if (totalDomains > 0 && remaining === 0) {
                $('#assignment-validation-messages').html(`
                    <div class=\"alert alert-success\">
                        <i class=\"fas fa-check-circle me-1\"></i>
                        All domains are assigned!
                    </div>
                `);
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
                cleanedFormData.push({ name: `manual_assignments[${index}][panel_id]`, value: batch.panel_id });
                cleanedFormData.push({ name: `manual_assignments[${index}][domain_start]`, value: batch.domain_start });
                cleanedFormData.push({ name: `manual_assignments[${index}][domain_end]`, value: batch.domain_end });
                cleanedFormData.push({ name: `manual_assignments[${index}][space_needed]`, value: batch.space_needed });
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

            metaBox.find('.panel-name').text(panel.auto_generated_id || panel.title || `Panel ${panel.id}`);
            metaBox.find('.remaining-count').text(`${panel.remaining_limit} free`);
            metaBox.find('.capacity-count').text(`${panel.limit} total`);
            metaBox.find('.after-count').text(`${remainingAfter} free after`);
            metaBox.find('.meta-footnote').text(`Using ${used} of ${panel.limit} now • This batch needs ${spaceNeeded}`);

            const currentBar = metaBox.find('.meta-progress-current');
            const futureBar = metaBox.find('.meta-progress-future');
            currentBar.css('width', `${percentUsed}%`);
            futureBar.css({
                width: `${futureSegment}%`,
                marginLeft: `${percentUsed}%`
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
