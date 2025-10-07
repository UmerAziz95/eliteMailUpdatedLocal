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
                                                <div class="col-auto">
                                                    <div class="form-check">
                                                        <input class="form-check-input domain-checkbox" 
                                                               type="checkbox" 
                                                               id="domain_{{ $domain['id'] }}"
                                                               value="{{ $domain['id'] }}">
                                                        <label class="form-check-label fw-medium" for="domain_{{ $domain['id'] }}">
                                                            Select
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="col">
                                                    <div class="d-flex align-items-center justify-content-end">
                                                        <label class="form-label mb-0 me-2 small">Per Inbox:</label>
                                                        <input type="number" 
                                                               class="form-control per-inbox-input" 
                                                               min="1" 
                                                               max="{{ $domain['available_inboxes'] }}"
                                                               value="1"
                                                               disabled
                                                               data-domain-id="{{ $domain['id'] }}">
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
    const perInboxInputs = document.querySelectorAll('.per-inbox-input');
    const searchInput = document.getElementById('domainSearch');
    const domainsContainer = document.getElementById('domainsContainer');
    const noResults = document.getElementById('noResults');
    const searchResults = document.getElementById('searchResults');
    const form = document.getElementById('domainSelectionForm');
    
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
            const domainId = this.value;
            const domainCard = this.closest('.domain-card');
            const perInboxInput = domainCard.querySelector('.per-inbox-input');
            
            if (this.checked) {
                domainCard.classList.add('selected');
                perInboxInput.disabled = false;
                perInboxInput.focus();
            } else {
                domainCard.classList.remove('selected');
                perInboxInput.disabled = true;
                perInboxInput.value = 1;
            }
            
            updateSummary();
        });
    });
    
    // Per inbox input handling
    perInboxInputs.forEach(input => {
        input.addEventListener('input', updateSummary);
    });
    
    // Update summary function
    function updateSummary() {
        const selectedDomains = [];
        let totalInboxes = 0;
        
        domainCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const domainCard = checkbox.closest('.domain-card');
                const perInboxInput = domainCard.querySelector('.per-inbox-input');
                const domainName = domainCard.dataset.domainName;
                const perInbox = parseInt(perInboxInput.value) || 0;
                
                selectedDomains.push({
                    id: checkbox.value,
                    name: domainName,
                    per_inbox: perInbox
                });
                
                totalInboxes += perInbox;
            }
        });
        
        document.getElementById('selectedCount').textContent = selectedDomains.length;
        document.getElementById('totalInboxes').textContent = totalInboxes;
        
        // Update selected domains list
        const listContainer = document.getElementById('selectedDomainsList');
        if (selectedDomains.length === 0) {
            listContainer.innerHTML = '<small class="opacity-75">No domains selected yet</small>';
        } else {
            listContainer.innerHTML = selectedDomains.map(domain => 
                `<div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-truncate">${domain.name}</small>
                    <small class="badge bg-white text-primary">${domain.per_inbox}</small>
                </div>`
            ).join('');
        }
        
        // Enable/disable save button
        document.getElementById('saveBtn').disabled = selectedDomains.length === 0;
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
                    
                    const domainCard = checkbox.closest('.domain-card');
                    const perInboxInput = domainCard.querySelector('.per-inbox-input');
                    perInboxInput.value = domain.per_inbox;
                }
            });
            updateSummary();
        @endif
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedDomains = [];
        domainCheckboxes.forEach(checkbox => {
            if (checkbox.checked) {
                const domainCard = checkbox.closest('.domain-card');
                const perInboxInput = domainCard.querySelector('.per-inbox-input');
                
                selectedDomains.push({
                    domain_id: parseInt(checkbox.value),
                    per_inbox: parseInt(perInboxInput.value)
                });
            }
        });
        
        if (selectedDomains.length === 0) {
            alert('Please select at least one domain');
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