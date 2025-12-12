<div id="manual-assignment-section" class="mb-4">
    <h5 class="mb-3 mt-4">
        <i class="fas fa-server me-2"></i>Panel Assignment
    </h5>

    {{-- Assignment Mode Toggle --}}
    <div class="mb-4">
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
        <small class="text-muted d-block mt-2">
            <strong>Automatic:</strong> System assigns pools to panels automatically based on capacity.<br>
            <strong>Manual:</strong> You specify which domains go to which panels in batches.
        </small>
    </div>

    {{-- Manual Assignment Builder --}}
    <div id="manual-assignment-builder" style="display: none;">
        {{-- Assignment Summary --}}
        <div class="alert alert-info mb-3">
            <div class="row">
                <div class="col-md-3">
                    <strong>Total Domains:</strong> <span id="total-domains-count">0</span>
                </div>
                <div class="col-md-3">
                    <strong>Assigned:</strong> <span id="assigned-domains-count">0</span>
                </div>
                <div class="col-md-3">
                    <strong>Remaining:</strong> <span id="remaining-domains-count" class="text-danger">0</span>
                </div>
                <div class="col-md-3">
                    <strong>Total Space:</strong> <span id="total-space-needed">0</span> inboxes
                </div>
            </div>
        </div>

        {{-- Batch List --}}
        <div id="batches-container" class="mb-3">
            {{-- Batches will be added here dynamically --}}
        </div>

        {{-- Add Batch Button --}}
        <button type="button" class="btn btn-success mb-3" id="add-batch-btn">
            <i class="fas fa-plus me-1"></i>Add Batch
        </button>

        {{-- Validation Messages --}}
        <div id="assignment-validation-messages"></div>
    </div>
</div>

{{-- Batch Template (Hidden) --}}
<template id="batch-template">
    <div class="card mb-3 batch-item" data-batch-index="">
        <div class="card-header d-flex justify-content-between align-items-center bg-light">
            <h6 class="mb-0">Batch <span class="batch-number"></span></h6>
            <button type="button" class="btn btn-sm btn-danger remove-batch-btn">
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
                    <small class="text-muted panel-capacity-info"></small>
                </div>

                {{-- Domain Range Selection --}}
                <div class="col-md-3 mb-3">
                    <label class="form-label">Start Domain</label>
                    <input type="number" class="form-control domain-start" name="manual_assignments[][domain_start]" min="1" placeholder="1" readonly>
                    <small class="text-muted">Auto-calculated</small>
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

            {{-- Domain List Preview --}}
            <div class="mt-2">
                <small class="text-muted">
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
                        const capacityHtml = `
                            <i class=\"fas fa-info-circle me-1\"></i>
                            Available: ${panel.remaining_limit} / ${panel.limit} inboxes
                        `;
                        batchCard.find('.panel-capacity-info').html(capacityHtml);
                        validateBatch(batchCard);
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
