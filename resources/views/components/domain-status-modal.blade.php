@props(['domainId' => null, 'currentStatus' => 'warming'])

<div class="modal fade" id="domainStatusModal" tabindex="-1" aria-labelledby="domainStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="domainStatusModalLabel">
                    <i class="ti ti-edit me-2"></i>Change Domain Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="domainStatusForm">
                    <input type="hidden" id="domain_id" name="domain_id" value="{{ $domainId }}">
                    <input type="hidden" id="current_status" name="current_status" value="{{ $currentStatus }}">
                    
                    <div class="mb-3">
                        <label for="new_status" class="form-label">Select New Status</label>
                        <select class="form-select" id="new_status" name="new_status" required>
                            <option value="">-- Select Status --</option>
                            @foreach(get_domain_status_options() as $value => $label)
                                <option value="{{ $value }}" data-config="{{ json_encode(get_domain_status_config($value)) }}">
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="status-error"></div>
                    </div>
                    
                    <!-- Status Preview -->
                    <div class="alert alert-info d-none" id="status-preview">
                        <div class="d-flex align-items-center mb-2">
                            <strong class="me-2">Preview:</strong>
                            <span id="preview-badge"></span>
                        </div>
                        <small id="preview-description" class="text-muted"></small>
                    </div>
                    
                    <!-- Transition Warning -->
                    <div class="alert alert-warning d-none" id="transition-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <span id="warning-message"></span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveStatusBtn">
                    <i class="ti ti-check me-1"></i>Update Status
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('domainStatusModal');
    const statusSelect = document.getElementById('new_status');
    const currentStatusInput = document.getElementById('current_status');
    const previewSection = document.getElementById('status-preview');
    const previewBadge = document.getElementById('preview-badge');
    const previewDescription = document.getElementById('preview-description');
    const transitionWarning = document.getElementById('transition-warning');
    const warningMessage = document.getElementById('warning-message');
    const statusError = document.getElementById('status-error');
    const saveBtn = document.getElementById('saveStatusBtn');
    
    // Handle status selection change
    statusSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (!this.value) {
            previewSection.classList.add('d-none');
            transitionWarning.classList.add('d-none');
            return;
        }
        
        try {
            const config = JSON.parse(selectedOption.dataset.config);
            const currentStatus = currentStatusInput.value;
            
            // Show preview
            previewBadge.innerHTML = `<span class="badge bg-${config.badge_class}">
                <i class="${config.icon} me-1"></i>${config.label}
            </span>`;
            previewDescription.textContent = config.description;
            previewSection.classList.remove('d-none');
            
            // Check transition validity
            const transitions = @json(config('domain_statuses.transitions'));
            const allowedTransitions = transitions[currentStatus] || [];
            
            if (currentStatus && !allowedTransitions.includes(this.value)) {
                warningMessage.textContent = `Transitioning from "${currentStatus}" to "${this.value}" may not be a valid state change.`;
                transitionWarning.classList.remove('d-none');
            } else {
                transitionWarning.classList.add('d-none');
            }
        } catch (e) {
            console.error('Error parsing status config:', e);
        }
    });
    
    // Handle modal show event
    modal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        if (button) {
            const domainId = button.dataset.domainId;
            const currentStatus = button.dataset.currentStatus;
            
            document.getElementById('domain_id').value = domainId;
            document.getElementById('current_status').value = currentStatus;
            
            // Reset form
            statusSelect.value = '';
            previewSection.classList.add('d-none');
            transitionWarning.classList.add('d-none');
            statusError.textContent = '';
            statusSelect.classList.remove('is-invalid');
        }
    });
    
    // Handle save button
    saveBtn.addEventListener('click', function() {
        const domainId = document.getElementById('domain_id').value;
        const newStatus = statusSelect.value;
        
        if (!newStatus) {
            statusSelect.classList.add('is-invalid');
            statusError.textContent = 'Please select a status';
            return;
        }
        
        // Disable button and show loading
        saveBtn.disabled = true;
        saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Updating...';
        
        // Make AJAX request to update status
        fetch('{{ route("admin.pool-domains.update-status") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                domain_id: domainId,
                status: newStatus
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                flasher.success(data.message || 'Domain status updated successfully');
                
                // Close modal
                bootstrap.Modal.getInstance(modal).hide();
                
                // Reload the page or update the UI
                if (typeof window.dataTable !== 'undefined') {
                    window.dataTable.ajax.reload(null, false);
                } else {
                    location.reload();
                }
            } else {
                throw new Error(data.message || 'Failed to update status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            flasher.error(error.message || 'An error occurred while updating status');
        })
        .finally(() => {
            // Re-enable button
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<i class="ti ti-check me-1"></i>Update Status';
        });
    });
});
</script>
@endpush
