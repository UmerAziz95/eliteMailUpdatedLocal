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
        max-height: 600px;
        overflow-y: auto;
    }
    

    
    .pagination-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 1rem;
        margin: 1rem 0;
        padding: 1rem;
        background-color: #f8f9fa;
        border-radius: 8px;
    }
    
    .page-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #dee2e6;
        background: white;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .page-btn:hover:not(:disabled) {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }
    
    .page-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    .page-btn.active {
        background-color: #0d6efd;
        color: white;
        border-color: #0d6efd;
    }
    

    
    .loading-spinner {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 200px;
    }
    
    @media (max-width: 768px) {
        .domain-grid {
            grid-template-columns: 1fr;
            max-height: 400px;
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
                                <!-- Search and Filter Controls -->
                                <div class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <div class="position-relative">
                                                <input type="text" 
                                                       id="domainSearch" 
                                                       class="form-control search-box"
                                                       placeholder="🔍 Search domains by name..."
                                                       autocomplete="off">
                                                <div class="position-absolute top-50 end-0 translate-middle-y pe-3">
                                                    <small id="searchResults">Loading domains...</small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <select id="domainsPerPage" class="form-select">
                                                <option value="20">20 per page</option>
                                                <option value="50" selected>50 per page</option>
                                                <option value="100">100 per page</option>
                                                <option value="200">200 per page</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Loading Spinner -->
                                <div id="loadingSpinner" class="loading-spinner">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Loading domains...</span>
                                    </div>
                                </div>

                                <!-- Available Domains -->
                                <div class="domain-grid" id="domainsContainer" style="display: none;"></div>

                                <!-- Pagination Controls -->
                                <div id="paginationControls" class="pagination-controls" style="display: none;">
                                    <button type="button" class="page-btn" id="prevPage">‹ Previous</button>
                                    <div id="pageNumbers"></div>
                                    <button type="button" class="page-btn" id="nextPage">Next ›</button>
                                    <div class="ms-3">
                                        <small id="pageInfo">Page 1 of 1</small>
                                    </div>
                                </div>
                                <!-- No results message -->
                                <div id="noResults" class="text-center py-5" style="display: none;">
                                    <i class="ti ti-search-off" style="font-size: 3rem;"></i>
                                    <h5 class="mt-2">No domains found</h5>
                                    <p>Try adjusting your search terms</p>
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
    // Configuration
    const maxQuantity = {{ $poolOrder->quantity }};
    let allDomains = [];
    let filteredDomains = [];
    let selectedDomains = new Map();
    let currentPage = 1;
    let domainsPerPage = 50;
    let searchTerm = '';
    
    // DOM Elements
    const searchInput = document.getElementById('domainSearch');
    const domainsContainer = document.getElementById('domainsContainer');
    const noResults = document.getElementById('noResults');
    const searchResults = document.getElementById('searchResults');
    const loadingSpinner = document.getElementById('loadingSpinner');
    const paginationControls = document.getElementById('paginationControls');
    const domainsPerPageSelect = document.getElementById('domainsPerPage');
    const form = document.getElementById('domainSelectionForm');
    
    // Initialize
    loadDomainsFromServer();
    
    // Load domains with AJAX for better performance
    async function loadDomainsFromServer() {
        try {
            loadingSpinner.style.display = 'flex';
            
            const response = await fetch(`{{ route('customer.pool-orders.edit', $poolOrder->id) }}?ajax=1`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load domains');
            }
            
            const data = await response.json();
            allDomains = data.domains || [];
            filteredDomains = [...allDomains];
            
            loadingSpinner.style.display = 'none';
            domainsContainer.style.display = 'grid';
            paginationControls.style.display = 'flex';
            
            // Load existing selections
            loadExistingSelections();
            
            // Initial render
            renderCurrentPage();
            updateSearchResults();
            
        } catch (error) {
            console.error('Error loading domains:', error);
            loadingSpinner.innerHTML = `
                <div class="text-center text-danger">
                    <i class="ti ti-alert-circle" style="font-size: 2rem;"></i>
                    <p class="mt-2">Failed to load domains. Please refresh the page.</p>
                </div>
            `;
        }
    }
    
    // Search functionality with debouncing
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchTerm = this.value.toLowerCase();
            applyFilters();
        }, 300);
    });
    
    // Domains per page change
    domainsPerPageSelect.addEventListener('change', function() {
        domainsPerPage = parseInt(this.value);
        currentPage = 1;
        renderCurrentPage();
    });
    
    // Apply filters
    function applyFilters() {
        if (searchTerm) {
            filteredDomains = allDomains.filter(domain => 
                domain.name.toLowerCase().includes(searchTerm)
            );
        } else {
            filteredDomains = [...allDomains];
        }
        
        currentPage = 1;
        renderCurrentPage();
        updateSearchResults();
    }
    
    // Render current page
    function renderCurrentPage() {
        const startIndex = (currentPage - 1) * domainsPerPage;
        const endIndex = startIndex + domainsPerPage;
        const domainsToShow = filteredDomains.slice(startIndex, endIndex);
        
        if (domainsToShow.length === 0 && filteredDomains.length === 0) {
            domainsContainer.style.display = 'none';
            noResults.style.display = 'block';
            paginationControls.style.display = 'none';
            return;
        }
        
        domainsContainer.style.display = 'grid';
        noResults.style.display = 'none';
        paginationControls.style.display = 'flex';
        
        domainsContainer.innerHTML = domainsToShow.map(domain => 
            createDomainCard(domain)
        ).join('');
        
        // Add event listeners to new checkboxes
        addDomainEventListeners();
        
        // Update pagination
        updatePagination();
    }
    
    // Create domain card HTML
    function createDomainCard(domain) {
        const isSelected = selectedDomains.has(domain.id);
        return `
            <div class="domain-card p-3 ${isSelected ? 'selected' : ''}" data-domain-id="${domain.id}" data-domain-name="${domain.name}">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="mb-1 fw-bold">${domain.name}</h6>
                        <span class="domain-status bg-success text-white">
                            ${domain.status.charAt(0).toUpperCase() + domain.status.slice(1)}
                        </span>
                    </div>
                    <div class="text-end">
                        <small class="d-block">Available</small>
                        <strong class="text-primary">${domain.available_inboxes} inboxes</strong>
                    </div>
                </div>
                
                <div class="row align-items-center mt-3">
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input domain-checkbox" 
                                   type="checkbox" 
                                   id="domain_${domain.id}"
                                   value="${domain.id}"
                                   data-inboxes="${domain.available_inboxes}"
                                   data-name="${domain.name}"
                                   ${isSelected ? 'checked' : ''}>
                            <label class="form-check-label fw-medium" for="domain_${domain.id}">
                                Select this domain (${domain.available_inboxes} inboxes included)
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Add event listeners to domain checkboxes
    function addDomainEventListeners() {
        const checkboxes = domainsContainer.querySelectorAll('.domain-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', handleDomainSelection);
        });
    }
    
    // Handle domain selection
    function handleDomainSelection(event) {
        const checkbox = event.target;
        const domainId = checkbox.value;
        const domainName = checkbox.dataset.name;
        const inboxes = parseInt(checkbox.dataset.inboxes) || 0;
        const domainCard = checkbox.closest('.domain-card');
        
        if (checkbox.checked) {
            // Check limits before adding
            const wouldExceedDomainLimit = selectedDomains.size >= maxQuantity;
            const currentTotalInboxes = Array.from(selectedDomains.values())
                .reduce((sum, domain) => sum + domain.inboxes, 0);
            const wouldExceedInboxLimit = (currentTotalInboxes + inboxes) > maxQuantity;
            
            if (wouldExceedDomainLimit) {
                checkbox.checked = false;
                alert(`You can only select up to ${maxQuantity} domains based on your order quantity.`);
                return;
            }
            
            if (wouldExceedInboxLimit) {
                checkbox.checked = false;
                alert(`Total inboxes (${currentTotalInboxes + inboxes}) cannot exceed your order quantity (${maxQuantity}). Please select domains with fewer inboxes.`);
                return;
            }
            
            // Add to selected domains
            selectedDomains.set(domainId, {
                id: domainId,
                name: domainName,
                inboxes: inboxes
            });
            
            domainCard.classList.add('selected');
        } else {
            // Remove from selected domains
            selectedDomains.delete(domainId);
            domainCard.classList.remove('selected');
        }
        
        updateSummary();
    }
    

    
    // Deselect domain function (global)
    window.deselectDomain = function(domainId) {
        selectedDomains.delete(domainId);
        
        // Update any visible checkboxes
        const checkbox = document.querySelector(`input[value="${domainId}"]`);
        if (checkbox) {
            checkbox.checked = false;
            checkbox.closest('.domain-card').classList.remove('selected');
        }
        
        updateSummary();
    };
    
    // Update pagination
    function updatePagination() {
        const totalPages = Math.ceil(filteredDomains.length / domainsPerPage);
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const pageNumbers = document.getElementById('pageNumbers');
        const pageInfo = document.getElementById('pageInfo');
        
        prevBtn.disabled = currentPage === 1;
        nextBtn.disabled = currentPage === totalPages || totalPages === 0;
        
        // Page numbers
        let pageNumbersHTML = '';
        const maxPageButtons = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPageButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxPageButtons - 1);
        
        if (endPage - startPage < maxPageButtons - 1) {
            startPage = Math.max(1, endPage - maxPageButtons + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            pageNumbersHTML += `
                <button type="button" class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">
                    ${i}
                </button>
            `;
        }
        
        pageNumbers.innerHTML = pageNumbersHTML;
        pageInfo.textContent = `Page ${currentPage} of ${totalPages || 1}`;
        
        // Previous/Next button events
        prevBtn.onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                renderCurrentPage();
            }
        };
        
        nextBtn.onclick = () => {
            if (currentPage < totalPages) {
                currentPage++;
                renderCurrentPage();
            }
        };
    }
    
    // Go to specific page (global function)
    window.goToPage = function(page) {
        currentPage = page;
        renderCurrentPage();
    };
    
    // Update search results
    function updateSearchResults() {
        const total = allDomains.length;
        const filtered = filteredDomains.length;
        
        if (searchTerm) {
            searchResults.textContent = `${filtered} of ${total} domains found`;
        } else {
            searchResults.textContent = `${total} domains available`;
        }
    }
    
    // Update summary function
    function updateSummary() {
        const selectedArray = Array.from(selectedDomains.values());
        const totalInboxes = selectedArray.reduce((sum, domain) => sum + domain.inboxes, 0);
        
        document.getElementById('selectedCount').textContent = selectedArray.length;
        
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
    
        // Update selected domains list in sidebar
        const listContainer = document.getElementById('selectedDomainsList');
        if (selectedArray.length === 0) {
            listContainer.innerHTML = '<small class="opacity-75">No domains selected yet</small>';
        } else {
            let domainListHTML = selectedArray.map((domain, index) => 
                `<div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background-color: rgba(255, 255, 255, 0.1);">
                    <div class="flex-grow-1">
                        <small class="text-truncate d-block fw-medium">${domain.name}</small>
                        <small class="badge bg-white text-primary">${domain.inboxes} inboxes</small>
                    </div>
                    <div class="d-flex align-items-center">
                        <small class="me-2 opacity-75">#${index + 1}</small>
                        <button type="button" class="btn btn-sm btn-outline-light" onclick="deselectDomain('${domain.id}')" title="Remove domain">
                            <i class="ti ti-x"></i>
                        </button>
                    </div>
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
        saveBtn.disabled = selectedArray.length === 0 || totalInboxes > maxQuantity;
        
        // Update button text based on validation
        if (totalInboxes > maxQuantity) {
            saveBtn.innerHTML = '<i class="ti ti-alert-triangle me-2"></i>Inbox Limit Exceeded';
        } else if (selectedArray.length === 0) {
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
                const domainData = allDomains.find(d => d.id === domain.domain_id);
                if (domainData) {
                    selectedDomains.set(domain.domain_id, {
                        id: domain.domain_id,
                        name: domainData.name,
                        inboxes: domainData.available_inboxes
                    });
                }
            });
            updateSummary();
        @endif
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedArray = Array.from(selectedDomains.keys());
        const totalInboxes = Array.from(selectedDomains.values())
            .reduce((sum, domain) => sum + domain.inboxes, 0);
        
        if (selectedArray.length === 0) {
            alert('Please select at least one domain');
            return;
        }
        
        if (selectedArray.length > maxQuantity) {
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
                domains: selectedArray
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
            console.error('Submission error:', error);
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