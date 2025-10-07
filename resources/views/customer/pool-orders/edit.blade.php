@extends('customer.layouts.app')

@section('title', 'Edit Pool Order - Domain Selection')

@push('styles')
<style>
    .domain-card {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .domain-card:hover {
        border-color: #0d6efd;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
        transform: translateY(-2px);
    }
    
    .domain-card.selected {
        border-color: #0d6efd;
        background-color: #f8f9ff;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
    }
    
    .domain-status {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
    }
    
    .search-box {
        border-radius: 12px;
        border: 2px solid #e9ecef;
        padding: 12px 20px;
        font-size: 1rem;
        transition: all 0.3s ease;
    }
    
    .search-box:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .per-inbox-input {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 8px 12px;
        width: 80px;
        text-align: center;
    }
    
    .per-inbox-input:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    .summary-card {
        border: 2px solid #e9ecef;
        color: white;
        border-radius: 15px;
        padding: 1.5rem;
        position: sticky;
        top: 20px;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        border-radius: 10px;
        padding: 12px 30px;
        font-weight: 600;
        color: white;
        transition: all 0.3s ease;
    }
    
    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 15px rgba(40, 167, 69, 0.3);
        color: white;
    }
    
    .inbox-count-warning {
        background-color: #fff3cd !important;
        color: #856404 !important;
    }
    
    .inbox-count-error {
        background-color: #f8d7da !important;
        color: #721c24 !important;
    }
    
    .summary-warning {
        border-color: #ffc107 !important;
        background: linear-gradient(135deg, #ffc107 0%, #ffca2c 100%) !important;
    }
    
    .summary-error {
        border-color: #dc3545 !important;
        background: linear-gradient(135deg, #dc3545 0%, #e74c3c 100%) !important;
    }
    
    .domain-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1rem;
    }
    
    @media (max-width: 768px) {
        .domain-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0"><i class="ti ti-edit me-2"></i>Edit Pool Order</h4>
                            <small>Select domains and configure inboxes per domain</small>
                        </div>
                        <a href="{{ route('customer.pool-orders.show', $poolOrder->id) }}" class="btn btn-light btn-sm">
                            <i class="ti ti-arrow-left me-1"></i>Back
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Pool Order Info -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="border border-2 p-3 rounded">
                                <h6 class=" mb-1">Pool Plan</h6>
                                <strong>{{ $poolOrder->poolPlan->name ?? 'N/A' }}</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-2 p-3 rounded">
                                <h6 class=" mb-1">Capacity</h6>
                                <strong>{{ $poolOrder->poolPlan->capacity ?? 'N/A' }} inboxes</strong>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="border border-2 p-3 rounded">
                                <h6 class=" mb-1">Quantity</h6>
                                <strong>{{ $poolOrder->quantity ?? 1 }}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Quantity Limit Notice -->
                    <div class="alert alert-info mb-4">
                        <i class="ti ti-info-circle me-2"></i>
                        <strong>Selection Limit:</strong> You can select up to <strong>{{ $poolOrder->quantity }}</strong> domains and <strong>{{ $poolOrder->quantity }}</strong> total inboxes based on your order quantity. Each domain comes with its pre-configured inbox count.
                    </div>

                    <form id="domainSelectionForm">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-lg-8">
                                <!-- Search Box -->
                                <div class="mb-4">
                                    <div class="position-relative">
                                        <input type="text" 
                                               id="domainSearch" 
                                               class="form-control search-box"
                                               placeholder="🔍 Search domains by name..."
                                               autocomplete="off">
                                        <div class="position-absolute top-50 end-0 translate-middle-y pe-3">
                                            <small class="" id="searchResults">{{ count($availableDomains) }} domains available</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Available Domains -->
                                <div class="domain-grid" id="domainsContainer">
                                    @foreach($availableDomains as $domain)
                                        <div class="domain-card p-3" data-domain-id="{{ $domain['id'] }}" data-domain-name="{{ $domain['name'] }}">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="mb-1 fw-bold">{{ $domain['name'] }}</h6>
                                                    <span class="domain-status bg-success text-white">
                                                        {{ ucfirst($domain['status']) }}
                                                    </span>
                                                </div>
                                                <div class="text-end">
                                                    <small class=" d-block">Available</small>
                                                    <strong class="text-primary">{{ $domain['available_inboxes'] }} inboxes</strong>
                                                </div>
                                            </div>
                                            
                                            <div class="row align-items-center mt-3">
                                                <div class="col-12">
                                                    <div class="form-check">
                                                        <input class="form-check-input domain-checkbox" 
                                                               type="checkbox" 
                                                               id="domain_{{ $domain['id'] }}"
                                                               value="{{ $domain['id'] }}"
                                                               data-inboxes="{{ $domain['available_inboxes'] }}">
                                                        <label class="form-check-label fw-medium" for="domain_{{ $domain['id'] }}">
                                                            Select this domain ({{ $domain['available_inboxes'] }} inboxes included)
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- No results message -->
                                <div id="noResults" class="text-center py-5" style="display: none;">
                                    <i class="ti ti-search-off " style="font-size: 3rem;"></i>
                                    <h5 class=" mt-2">No domains found</h5>
                                    <p class="">Try adjusting your search terms</p>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <!-- Selection Summary -->
                                <div class="summary-card">
                                    <h5 class="mb-3">
                                        <i class="ti ti-list-check me-2"></i>Selection Summary
                                    </h5>
                                    
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span>Selected Domains:</span>
                                            <span class="badge bg-white text-primary px-3 py-2" id="selectedCount">0</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>Total Inboxes:</span>
                                            <span class="badge bg-white text-success px-3 py-2" id="totalInboxes">0</span>
                                        </div>
                                    </div>

                                    <hr class="border-white-50">

                                    <!-- Selected Domains List -->
                                    <div id="selectedDomainsList" class="mb-3">
                                        <small class="opacity-75">No domains selected yet</small>
                                    </div>

                                    <!-- Save Button -->
                                    <button type="submit" class="btn btn-save w-100" id="saveBtn" disabled>
                                        <i class="ti ti-device-floppy me-2"></i>Save Configuration
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const domainCheckboxes = document.querySelectorAll('.domain-checkbox');
    const searchInput = document.getElementById('domainSearch');
    const domainsContainer = document.getElementById('domainsContainer');
    const noResults = document.getElementById('noResults');
    const searchResults = document.getElementById('searchResults');
    const form = document.getElementById('domainSelectionForm');
    const maxQuantity = {{ $poolOrder->quantity }};
    
    // Load existing selections
    loadExistingSelections();
    
    // Search functionality
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const domainCards = domainsContainer.querySelectorAll('.domain-card');
        let visibleCount = 0;
        
        domainCards.forEach(card => {
            const domainName = card.dataset.domainName.toLowerCase();
            if (domainName.includes(searchTerm)) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        if (visibleCount === 0) {
            domainsContainer.style.display = 'none';
            noResults.style.display = 'block';
        } else {
            domainsContainer.style.display = 'grid';
            noResults.style.display = 'none';
        }
        
        searchResults.textContent = `${visibleCount} domain${visibleCount !== 1 ? 's' : ''} found`;
    });
    
    // Domain selection handling
    domainCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const selectedCount = document.querySelectorAll('.domain-checkbox:checked').length;
            
            // Calculate total inboxes if this domain is selected
            let totalInboxes = 0;
            document.querySelectorAll('.domain-checkbox:checked').forEach(cb => {
                totalInboxes += parseInt(cb.dataset.inboxes) || 0;
            });
            
            if (this.checked) {
                // Check domain count limit
                if (selectedCount > maxQuantity) {
                    this.checked = false;
                    alert(`You can only select up to ${maxQuantity} domains based on your order quantity.`);
                    return;
                }
                
                // Check total inbox limit
                if (totalInboxes > maxQuantity) {
                    this.checked = false;
                    alert(`Total inboxes (${totalInboxes}) cannot exceed your order quantity (${maxQuantity}). Please select domains with fewer inboxes.`);
                    return;
                }
            }
            
            const domainCard = this.closest('.domain-card');
            
            if (this.checked) {
                domainCard.classList.add('selected');
            } else {
                domainCard.classList.remove('selected');
            }
            
            updateSummary();
        });
    });
    
    // Update summary function
    function updateSummary() {
        const selectedDomains = [];
        let totalInboxes = 0;
        
        domainCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const domainCard = checkbox.closest('.domain-card');
                const domainName = domainCard.dataset.domainName;
                const inboxes = parseInt(checkbox.dataset.inboxes) || 0;
                
                selectedDomains.push({
                    id: checkbox.value,
                    name: domainName,
                    per_inbox: inboxes
                });
                
                totalInboxes += inboxes;
            }
        });
        
        document.getElementById('selectedCount').textContent = selectedDomains.length;
        
        // Update inbox count with visual indicators
        const totalInboxesElement = document.getElementById('totalInboxes');
        totalInboxesElement.textContent = totalInboxes;
        
        // Reset classes
        totalInboxesElement.className = 'badge px-3 py-2';
        const summaryCard = document.querySelector('.summary-card');
        summaryCard.className = 'summary-card';
        
        // Add visual indicators based on inbox count
        if (totalInboxes > maxQuantity) {
            totalInboxesElement.classList.add('inbox-count-error');
            summaryCard.classList.add('summary-error');
        } else if (totalInboxes === maxQuantity) {
            totalInboxesElement.classList.add('bg-white', 'text-success');
        } else if (totalInboxes > maxQuantity * 0.8) {
            totalInboxesElement.classList.add('inbox-count-warning');
            summaryCard.classList.add('summary-warning');
        } else {
            totalInboxesElement.classList.add('bg-white', 'text-success');
        }
        
        // Update selected domains list
        const listContainer = document.getElementById('selectedDomainsList');
        if (selectedDomains.length === 0) {
            listContainer.innerHTML = '<small class="opacity-75">No domains selected yet</small>';
        } else {
            let domainListHTML = selectedDomains.map(domain => 
                `<div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-truncate">${domain.name}</small>
                    <small class="badge bg-white text-primary">${domain.per_inbox}</small>
                </div>`
            ).join('');
            
            // Add warning message if over limit
            if (totalInboxes > maxQuantity) {
                domainListHTML += `<div class="mt-2 p-2 rounded" style="background-color: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.3);">
                    <small class="text-danger"><i class="ti ti-alert-triangle me-1"></i>Exceeds inbox limit by ${totalInboxes - maxQuantity}</small>
                </div>`;
            }
            
            listContainer.innerHTML = domainListHTML;
        }
        
        // Enable/disable save button
        const saveBtn = document.getElementById('saveBtn');
        saveBtn.disabled = selectedDomains.length === 0 || totalInboxes > maxQuantity;
        
        // Update button text based on validation
        if (totalInboxes > maxQuantity) {
            saveBtn.innerHTML = '<i class="ti ti-alert-triangle me-2"></i>Inbox Limit Exceeded';
        } else if (selectedDomains.length === 0) {
            saveBtn.innerHTML = '<i class="ti ti-device-floppy me-2"></i>Save Configuration';
        } else {
            saveBtn.innerHTML = '<i class="ti ti-device-floppy me-2"></i>Save Configuration';
        }
    }
    
    // Load existing selections
    function loadExistingSelections() {
        @if($poolOrder->domains)
            const existingDomains = @json($poolOrder->domains);
            existingDomains.forEach(domain => {
                const checkbox = document.querySelector(`input[value="${domain.domain_id}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change'));
                }
            });
            updateSummary();
        @endif
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedDomains = [];
        let totalInboxes = 0;
        
        domainCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                selectedDomains.push(parseInt(checkbox.value));
                totalInboxes += parseInt(checkbox.dataset.inboxes) || 0;
            }
        });
        
        if (selectedDomains.length === 0) {
            alert('Please select at least one domain');
            return;
        }
        
        if (selectedDomains.length > maxQuantity) {
            alert(`You can only select up to ${maxQuantity} domains based on your order quantity.`);
            return;
        }
        
        if (totalInboxes > maxQuantity) {
            alert(`Total inboxes (${totalInboxes}) cannot exceed your order quantity (${maxQuantity}). Please adjust your domain selection.`);
            return;
        }
        
        const saveBtn = document.getElementById('saveBtn');
        const originalText = saveBtn.innerHTML;
        saveBtn.innerHTML = '<i class="spinner-border spinner-border-sm me-2"></i>Saving...';
        saveBtn.disabled = true;
        
        fetch(`{{ route('customer.pool-orders.update', $poolOrder->id) }}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                domains: selectedDomains
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="ti ti-check-circle me-2"></i>${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.card-body').insertBefore(alert, document.querySelector('.row'));
                
                // Scroll to top
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                // Redirect after 2 seconds
                setTimeout(() => {
                    window.location.href = '{{ route('customer.pool-orders.show', $poolOrder->id) }}';
                }, 2000);
            } else {
                throw new Error(data.message || 'Failed to save');
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        })
        .finally(() => {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        });
    });
});
</script>
@endpush