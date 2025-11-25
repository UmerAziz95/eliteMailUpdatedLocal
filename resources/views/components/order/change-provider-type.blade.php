<div class="modal fade" id="changeProviderTypeModal" tabindex="-1" aria-labelledby="changeProviderTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeProviderTypeModalLabel">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Change Provider Type
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Order ID</label>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary" id="providerModalOrderId">#</span>
                        <span>Current:</span>
                        <span class="badge" id="providerModalCurrentType">None</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="newProviderType" class="form-label">Select Provider Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="newProviderType" required>
                        <option value="">-- Select Provider Type --</option>
                        <option value="Google">Google</option>
                        <option value="Microsoft 365">Microsoft 365</option>
                    </select>
                </div>
                <div class="mb-3 d-none">
                    <label for="providerChangeReason" class="form-label">Reason for Change (Optional)</label>
                    <textarea class="form-control" id="providerChangeReason" rows="3"
                        placeholder="Enter reason for provider type change..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmProviderTypeChange">
                    <i class="fas fa-save me-1"></i>
                    Update Provider Type
                </button>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            (() => {
                const modalId = 'changeProviderTypeModal';
                const orderIdAttr = 'data-order-id';
                const currentProviderAttr = 'data-current-provider';

                const getModalEl = () => document.getElementById(modalId);
                const getField = (id) => document.getElementById(id);

                window.openChangeProviderTypeModal = function (orderId, currentProviderType) {
                    const modalEl = getModalEl();
                    if (!modalEl) return;

                    const orderLabel = getField('providerModalOrderId');
                    if (orderLabel) {
                        orderLabel.textContent = '#' + orderId;
                    }

                    const currentBadge = getField('providerModalCurrentType');
                    if (currentBadge) {
                        if (currentProviderType) {
                            currentBadge.textContent = currentProviderType;
                            currentBadge.className = 'badge ' + (currentProviderType === 'Google' ? 'bg-primary' : 'bg-info');
                        } else {
                            currentBadge.textContent = 'Not Set';
                            currentBadge.className = 'badge bg-secondary';
                        }
                    }

                    const providerSelect = getField('newProviderType');
                    if (providerSelect) {
                        providerSelect.value = '';
                    }
                    const reasonField = getField('providerChangeReason');
                    if (reasonField) {
                        reasonField.value = '';
                    }

                    modalEl.setAttribute(orderIdAttr, orderId);
                    modalEl.setAttribute(currentProviderAttr, currentProviderType || '');

                    const modal = new bootstrap.Modal(modalEl);
                    modal.show();
                };

                window.updateProviderType = async function () {
                    const modal = getModalEl();
                    if (!modal) return;

                    const orderId = modal.getAttribute(orderIdAttr);
                    const providerSelect = getField('newProviderType');
                    const reasonField = getField('providerChangeReason');

                    if (!providerSelect) return;
                    const newProviderType = providerSelect.value;
                    const reason = reasonField ? reasonField.value : '';

                    if (!newProviderType) {
                        Swal.fire({
                            title: 'Provider Type Required',
                            text: 'Please select a provider type.',
                            icon: 'warning',
                            confirmButtonColor: '#ffc107'
                        });
                        return;
                    }

                    Swal.fire({
                        title: 'Updating Provider Type...',
                        text: 'Please wait while we update the provider type.',
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    try {
                        const response = await fetch(`/admin/orders/${orderId}/change-provider-type`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                provider_type: newProviderType,
                                reason: reason
                            })
                        });

                        const result = await response.json();

                        if (!response.ok) {
                            throw new Error(result.message || 'Failed to update provider type');
                        }

                        if (result.success) {
                            const successMessage = result.message || 'Provider type updated successfully!';

                            await Swal.fire({
                                title: 'Success!',
                                text: successMessage,
                                icon: 'success',
                                confirmButtonColor: '#28a745',
                                timer: 3000,
                                timerProgressBar: true
                            });

                            const modalInstance = bootstrap.Modal.getInstance(modal);
                            if (modalInstance) {
                                modalInstance.hide();
                            }

                            if (typeof viewOrderSplits === 'function') {
                                viewOrderSplits(orderId);
                            }

                            if (typeof loadOrders === 'function') {
                                loadOrders(typeof currentFilters !== 'undefined' ? currentFilters : {}, 1, false);
                            }
                        } else {
                            throw new Error(result.message || 'Failed to update provider type');
                        }
                    } catch (error) {
                        console.error('Error updating provider type:', error);
                        Swal.fire({
                            title: 'Error',
                            text: error.message || 'An error occurred while updating the provider type',
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    }
                };

                document.addEventListener('DOMContentLoaded', () => {
                    const confirmBtn = getField('confirmProviderTypeChange');
                    if (confirmBtn) {
                        confirmBtn.addEventListener('click', () => window.updateProviderType());
                    }
                });
            })();
        </script>
    @endpush
@endonce
