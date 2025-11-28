<div class="modal fade" id="reassignPanelModal" tabindex="-1" aria-labelledby="reassignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reassignModalLabel">Reassign Panel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Panel Reassignment:</strong> Select a target panel within the same order to reassign the split(s) to.
                        This will move all domains and capacity from the current panel to the selected panel.
                    </div>
                </div>
                <div id="reassignLoader" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading available panels...</p>
                </div>
                <div id="availablePanelsContainer"></div>
                <div class="mt-3" style="display: none;" id="reassignReasonContainer">
                    <label for="reassignReason" class="form-label">Reason for reassignment (optional)</label>
                    <textarea class="form-control" id="reassignReason" rows="3" placeholder="Enter reason for reassignment..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmReassignBtn" disabled onclick="confirmReassignment()">
                    <i class="fas fa-exchange-alt me-1"></i>Select Panel First
                </button>
            </div>
        </div>
    </div>
</div>

@once
    @push('styles')
        <style>
            /* Panel Reassignment Styles */
            .panel-option {
                transition: all 0.2s ease;
                border: 2px solid transparent !important;
            }

            .panel-option:hover:not(.bg-light) {
                background-color: rgba(13, 110, 253, 0.05) !important;
                border-color: #0d6efd !important;
                transform: translateY(-1px);
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .panel-option.border-primary {
                border-color: #0d6efd !important;
                background-color: rgba(13, 110, 253, 0.1) !important;
            }

            .panel-option.bg-light {
                opacity: 0.7;
            }

            .panel-option .badge {
                font-size: 0.7em;
            }

            #reassignPanelModal .modal-body {
                max-height: 70vh;
                overflow-y: auto;
            }

            #availablePanelsContainer {
                max-height: 400px;
                overflow-y: auto;
            }

            .reassign-panel-info {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-radius: 8px;
                padding: 1rem;
                margin-bottom: 1rem;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            let currentReassignData = {};

            function openReassignModal(orderId, currentPanelId, orderPanelId, panelTitle) {
                currentReassignData = {
                    orderId: orderId,
                    currentPanelId: currentPanelId,
                    orderPanelId: orderPanelId,
                    panelTitle: panelTitle
                };

                const modalTitle = document.getElementById('reassignModalLabel');
                if (modalTitle) {
                    modalTitle.innerHTML = `Reassign Panel: ${'PNL-' + currentPanelId + ' ' + panelTitle}`;
                }

                loadAvailablePanels(orderId, orderPanelId);

                const modal = new bootstrap.Modal(document.getElementById('reassignPanelModal'));
                modal.show();
            }

            async function loadAvailablePanels(orderId, orderPanelId) {
                try {
                    showReassignLoading(true);

                    const response = await fetch(`/admin/orders/${orderId}/order-panels/${orderPanelId}/available-for-reassignment`);
                    const data = await response.json();

                    if (data.success) {
                        renderAvailablePanels(data.panels);
                    } else {
                        showReassignError(data.error || 'Failed to load available panels');
                    }
                } catch (error) {
                    console.error('Error loading available panels:', error);
                    showReassignError('Failed to load available panels');
                } finally {
                    showReassignLoading(false);
                }
            }

            function renderAvailablePanels(panels) {
                const container = document.getElementById('availablePanelsContainer');

                if (!panels || panels.length === 0) {
                    container.innerHTML = `
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle text-muted mb-3" style="font-size: 2rem;"></i>
                            <p class="mb-0">No panels available for reassignment</p>
                        </div>
                    `;
                    return;
                }

                const searchHtml = `
                    <div class="mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="panelSearchInput"
                                   placeholder="Search panels by ID or title..." onkeyup="filterPanels()">
                        </div>
                    </div>
                `;

                const panelsHtml = panels.map(panel => `
                    <div class="panel-option mb-2 border rounded-3 shadow-sm position-relative overflow-hidden panel-card"
                         data-panel-id="${panel.panel_id}"
                         data-panel-sr-no="${panel.panel_sr_no || panel.panel_id}"
                         data-panel-title="${(panel.panel_title || '').toLowerCase()}"
                         data-space-needed="${panel.space_needed || 0}"
                         data-panel-limit="${panel.panel_limit}"
                         data-panel-remaining="${panel.panel_remaining_limit}"
                         ${panel.is_reassignable ? `onclick="selectTargetPanel(${panel.panel_id}, '${panel.panel_title}', ${panel.space_needed || 0}, ${panel.panel_remaining_limit})"` : ''}
                         style="${panel.is_reassignable ? 'cursor: pointer; transition: all 0.2s ease;' : 'cursor: not-allowed; opacity: 0.6;'}">

                        ${panel.is_reassignable ? '' : '<div class="position-absolute top-0 start-0 w-100 h-100 bg-light bg-opacity-75 d-flex align-items-center justify-content-center" style="z-index: 2;"><span class="badge bg-warning text-dark">Insufficient Space</span></div>'}

                        <div class="p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <div class="panel-icon me-2">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-gradient"
                                             style="width: 35px; height: 35px;">
                                            <i class="fas fa-server text-white"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fw-bold">
                                            <span class="badge bg-info bg-gradient me-2 px-2 py-1 small">PNL-${panel.panel_sr_no || panel.panel_id}</span>
                                            <span class="panel-title-text">${panel.panel_title}</span>
                                        </h6>
                                    </div>
                                </div>

                                ${panel.is_reassignable ?
                                    `<button type="button" class="btn btn-outline-primary btn-sm px-3 select-btn"
                                         onclick="selectTargetPanel(${panel.panel_id}, '${panel.panel_title}', ${panel.space_needed || 0}, ${panel.panel_remaining_limit})">
                                        <i class="fas fa-arrow-right me-1"></i>Select
                                    </button>` : ''
                                }
                            </div>

                            <div class="row g-2 mt-1">
                                <div class="col-3">
                                    <div class="text-center p-2 rounded bg-light">
                                        <div class="fw-bold text-success panel-space-needed" style="font-size: 0.9rem;">${panel.space_needed || 0}</div>
                                        <small class="text-muted">Need</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-center p-2 rounded bg-light">
                                        <div class="fw-bold text-primary" style="font-size: 0.9rem;">${panel.total_orders || 0}</div>
                                        <small class="text-muted">Orders</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-center p-2 rounded bg-light">
                                        <div class="fw-bold text-warning" style="font-size: 0.9rem;">${panel.panel_limit}</div>
                                        <small class="text-muted">Limit</small>
                                    </div>
                                </div>
                                <div class="col-3">
                                    <div class="text-center p-2 rounded bg-light">
                                        <div class="fw-bold text-danger panel-remaining" style="font-size: 0.9rem;">${panel.panel_remaining_limit}</div>
                                        <small class="text-muted">Free</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');

                container.innerHTML = searchHtml + '<div id="panelsList">' + panelsHtml + '</div>';

                const style = document.createElement('style');
                style.textContent = `
                    .panel-card{
                        border: 1px solid #dee2e6;
                    }
                    .panel-card:hover {
                        transform: translateY(-2px);
                        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
                    }
                    .panel-card.selected {
                        border-color: #0d6efd !important;
                        background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(102, 126, 234, 0.05) 100%) !important;
                        box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25) !important;
                    }
                    .badge-sm {
                        font-size: 0.7rem;
                        padding: 0.25rem 0.5rem;
                    }
                `;
                document.head.appendChild(style);
            }

            function filterPanels() {
                const searchInput = document.getElementById('panelSearchInput');
                const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
                const panelCards = document.querySelectorAll('.panel-card');
                let visibleCount = 0;

                panelCards.forEach(card => {
                    const panelId = (card.getAttribute('data-panel-id') || '').toLowerCase();
                    const panelTitle = card.getAttribute('data-panel-title') || '';
                    const isVisible = panelId.includes(searchTerm) || panelTitle.includes(searchTerm);

                    if (isVisible) {
                        card.style.display = 'block';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                const panelsList = document.getElementById('panelsList');
                let noResultsDiv = document.getElementById('noSearchResults');

                if (visibleCount === 0 && searchTerm.length > 0) {
                    if (!noResultsDiv) {
                        noResultsDiv = document.createElement('div');
                        noResultsDiv.id = 'noSearchResults';
                        noResultsDiv.className = 'text-center py-4';
                        noResultsDiv.innerHTML = `
                            <i class="fas fa-search text-muted mb-2" style="font-size: 1.5rem;"></i>
                            <p class="text-muted mb-0">No panels found matching "${searchTerm}"</p>
                        `;
                        panelsList.appendChild(noResultsDiv);
                    }
                } else if (noResultsDiv) {
                    noResultsDiv.remove();
                }
            }

            function selectTargetPanel(targetPanelId, targetPanelTitle, spaceNeeded = 0, remainingSpace = 0) {
                document.querySelectorAll('.panel-card').forEach(option => {
                    option.classList.remove('selected');
                });

                const selectedPanel = document.querySelector(`[data-panel-id="${targetPanelId}"]`);
                if (selectedPanel) {
                    selectedPanel.classList.add('selected');
                }

                updatePanelSpaceValues(targetPanelId, spaceNeeded);

                currentReassignData.targetPanelId = targetPanelId;
                currentReassignData.targetPanelTitle = targetPanelTitle;
                currentReassignData.spaceNeeded = spaceNeeded;
                currentReassignData.remainingSpace = remainingSpace;

                const reassignBtn = document.getElementById('confirmReassignBtn');
                if (reassignBtn) {
                    reassignBtn.disabled = false;
                    reassignBtn.innerHTML = `<i class="fas fa-exchange-alt me-1"></i>Reassign to ${targetPanelTitle}`;
                }
            }

            function updatePanelSpaceValues(selectedPanelId, spaceToMove) {
                const currentSpaceNeeded = spaceToMove;

                document.querySelectorAll('.panel-card').forEach(panelOption => {
                    const originalSpaceNeeded = parseInt(panelOption.getAttribute('data-space-needed')) || 0;
                    const originalRemaining = parseInt(panelOption.getAttribute('data-panel-remaining')) || 0;

                    const spaceNeededElement = panelOption.querySelector('.panel-space-needed');
                    const remainingElement = panelOption.querySelector('.panel-remaining');

                    if (spaceNeededElement) {
                        spaceNeededElement.textContent = originalSpaceNeeded;
                        spaceNeededElement.style.color = '';
                        spaceNeededElement.style.fontWeight = '';
                    }
                    if (remainingElement) {
                        remainingElement.textContent = originalRemaining;
                        remainingElement.style.color = '';
                        remainingElement.style.fontWeight = '';
                    }
                });

                document.querySelectorAll('.panel-card').forEach(panelOption => {
                    const panelId = panelOption.getAttribute('data-panel-id');
                    const originalSpaceNeeded = parseInt(panelOption.getAttribute('data-space-needed')) || 0;
                    const originalRemaining = parseInt(panelOption.getAttribute('data-panel-remaining')) || 0;

                    const spaceNeededElement = panelOption.querySelector('.panel-space-needed');
                    const remainingElement = panelOption.querySelector('.panel-remaining');
                    if (panelId == selectedPanelId) {
                        const newSpaceNeeded = originalSpaceNeeded + currentSpaceNeeded;
                        const newRemaining = originalRemaining - currentSpaceNeeded;

                        if (remainingElement) {
                            remainingElement.textContent = newRemaining;
                            remainingElement.style.color = '#dc3545';
                            remainingElement.style.fontWeight = 'bold';
                        }
                    }
                });
            }

            async function confirmReassignment() {
                if (!currentReassignData.targetPanelId) {
                    showReassignError('Please select a target panel');
                    return;
                }

                try {
                    const result = await Swal.fire({
                        title: 'Confirm Panel Reassignment?',
                        html: `
                            <div class="text-start">
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                            <div class="card-body text-center text-white">
                                                <i class="fas fa-exchange-alt fs-2 mb-2"></i>
                                                <h4 class="card-title mb-1 fw-bold">PNL-${currentReassignData.currentPanelId}</h4>
                                                <p class="mb-1 fw-semibold">${currentReassignData.panelTitle}</p>
                                                <small class="text-white-50">From Panel</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);">
                                            <div class="card-body text-center text-white">
                                                <i class="fas fa-arrow-right fs-2 mb-2"></i>
                                                <h4 class="card-title mb-1 fw-bold">PNL-${currentReassignData.targetPanelId}</h4>
                                                <p class="mb-1 fw-semibold">${currentReassignData.targetPanelTitle}</p>
                                                <small class="text-white-50">To Panel</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                                            <div class="card-body text-center text-white">
                                                <i class="fas fa-inbox fs-2 mb-2"></i>
                                                <h4 class="card-title mb-1 fw-bold">${currentReassignData.spaceNeeded || 0}</h4>
                                                <small class="text-white-50">Spaces to Transfer</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="alert alert-warning mt-3 mb-0" style="font-size: 14px;">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Note:</strong> After this action is completed, the selected spaces will be transferred from the source panel to the destination panel.
                                </div>
                            </div>
                        `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#ffc107',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: '<i class="fas fa-exchange-alt me-1"></i>Confirm Reassignment',
                        cancelButtonText: '<i class="fas fa-times me-1"></i>Cancel',
                        reverseButtons: true,
                        customClass: {
                            popup: 'swal-wide'
                        }
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    Swal.fire({
                        title: 'Reassigning Panel...',
                        html: `
                            <div class="text-center">
                                <div class="spinner-border text-warning mb-3" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p>Please wait while we reassign the panel...</p>
                                <small class="text-muted">This may take a few moments</small>
                            </div>
                        `,
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'swal-loading'
                        }
                    });

                    const formData = {
                        from_order_panel_id: currentReassignData.orderPanelId,
                        to_panel_id: currentReassignData.targetPanelId,
                        reason: document.getElementById('reassignReason').value || null
                    };

                    const response = await fetch('/admin/orders/panels/reassign', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(formData)
                    });

                    const data = await response.json();

                    if (data.success) {
                        // Capture IDs before we potentially reset shared state
                        const targetPanelId = currentReassignData.currentPanelId;
                        const targetOrderId = currentReassignData.orderId;

                        await Swal.fire({
                            title: 'Reassignment Successful!',
                            html: `
                                <div class="text-center">
                                    <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                                    <p class="mb-2">${data.message}</p>
                                    <small class="text-muted">Panel has been successfully reassigned</small>
                                </div>
                            `,
                            icon: 'success',
                            confirmButtonColor: '#28a745',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: true,
                            confirmButtonText: 'Great!'
                        });

                        const modalInstance = bootstrap.Modal.getInstance(document.getElementById('reassignPanelModal'));
                        if (modalInstance) {
                            modalInstance.hide();
                        }

                        if (typeof viewPanelOrders === 'function' && targetPanelId) {
                            setTimeout(() => {
                                viewPanelOrders(targetPanelId);
                            }, 1000);
                        }

                        if (typeof viewOrderSplits === 'function' && targetOrderId) {
                            setTimeout(() => {
                                viewOrderSplits(targetOrderId);
                            }, 1000);
                        }

                        if (typeof loadPanels === 'function') {
                            loadPanels(typeof currentFilters !== 'undefined' ? currentFilters : {}, 1, false);
                        }
                        if (typeof loadOrders === 'function') {
                            loadOrders(typeof currentFilters !== 'undefined' ? currentFilters : {}, 1, false);
                        }
                        resetReassignModal();
                    } else {
                        await Swal.fire({
                            title: 'Reassignment Failed!',
                            html: `
                                <div class="text-center">
                                    <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 3rem;"></i>
                                    <p class="mb-2">${data.message || 'Reassignment failed'}</p>
                                    <small class="text-muted">Please try again or contact support</small>
                                </div>
                            `,
                            icon: 'error',
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: 'Try Again'
                        });

                        showReassignError(data.message || 'Reassignment failed');
                    }
                } catch (error) {
                    console.error('Error during reassignment:', error);

                    await Swal.fire({
                        title: 'Error!',
                        html: `
                            <div class="text-center">
                                <i class="fas fa-times-circle text-danger mb-3" style="font-size: 3rem;"></i>
                                <p class="mb-2">An error occurred during reassignment</p>
                                <small class="text-muted">Please check your connection and try again</small>
                            </div>
                        `,
                        icon: 'error',
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });

                    showReassignError('An error occurred during reassignment');
                }
            }

            function showReassignLoading(show) {
                const loader = document.getElementById('reassignLoader');
                const container = document.getElementById('availablePanelsContainer');

                if (loader && container) {
                    if (show) {
                        loader.style.display = 'block';
                        container.style.display = 'none';
                    } else {
                        loader.style.display = 'none';
                        container.style.display = 'block';
                    }
                }
            }

            function showReassignError(message) {
                const container = document.getElementById('availablePanelsContainer');
                if (container) {
                    container.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${message}
                        </div>
                    `;
                }
            }

            function resetReassignModal() {
                currentReassignData = {};
                const availablePanelsContainer = document.getElementById('availablePanelsContainer');
                const reassignReason = document.getElementById('reassignReason');
                const confirmReassignBtn = document.getElementById('confirmReassignBtn');

                if (availablePanelsContainer) {
                    availablePanelsContainer.innerHTML = '';
                }

                if (reassignReason) {
                    reassignReason.value = '';
                }

                if (confirmReassignBtn) {
                    confirmReassignBtn.disabled = true;
                    confirmReassignBtn.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Select Panel First';
                }

                document.querySelectorAll('.panel-space-needed, .panel-remaining').forEach(element => {
                    element.style.color = '';
                    element.style.fontWeight = '';
                });

                const noResultsDiv = document.getElementById('noSearchResults');
                if (noResultsDiv) {
                    noResultsDiv.remove();
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const reassignPanelModal = document.getElementById('reassignPanelModal');
                if (reassignPanelModal) {
                    reassignPanelModal.addEventListener('hidden.bs.modal', resetReassignModal);
                }
            });
        </script>
    @endpush
@endonce
