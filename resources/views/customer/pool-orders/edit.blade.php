@extends('customer.layouts.app')

@section('title', 'Edit Pool Order - Domain Selection')

@push('styles')
<style>
    /* inbox prefix styling - default (unselected) */
    .inbox-prefix {
        display: block;
        background: rgba(255, 255, 255, 0.05);
        color: rgba(226, 232, 240, 0.8);
        font-size: 0.85rem;
        margin-bottom: 0.4rem;
        padding: 0.4rem 0.7rem;
        border-radius: 8px;
        word-break: break-word;
        border-left: 3px solid rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .inbox-prefix:hover {
        background: rgba(255, 255, 255, 0.08);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        border-color: rgba(255, 255, 255, 0.25);
    }
    
    /* inbox prefix styling when domain is selected - green theme */
    .domain-card.selected .inbox-prefix {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(6, 78, 59, 0.08));
        color: #a7f3d0;
        border-left: 3px solid #10b981;
        border: 1px solid rgba(16, 185, 129, 0.25);
        box-shadow: 0 1px 3px rgba(16, 185, 129, 0.1);
    }
    
    .domain-card.selected .inbox-prefix:hover {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.18), rgba(6, 78, 59, 0.12));
        box-shadow: 0 2px 6px rgba(16, 185, 129, 0.25);
        border-color: rgba(16, 185, 129, 0.4);
    }
    
    .inbox-prefix-muted {
        color: rgba(226, 232, 240, 0.65);
        font-weight: 500;
        margin-bottom: 0.6rem;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
    }
    
    /* Enhanced domain card styling */
    .domain-card {
        background: linear-gradient(145deg, #2d3748, #1a202c) !important;
        border: 2px solid rgba(99, 102, 241, 0.3) !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
    }
    
    .domain-card:hover {
        border-color: rgba(99, 102, 241, 0.6) !important;
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.25) !important;
    }
    
    .domain-card.selected {
        border-color: #10b981 !important;
        background: linear-gradient(145deg, #065f46, #064e3b) !important;
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3) !important;
    }
    
    /* Summary section inbox prefixes */
    .summary-inbox-prefix {
        display: block;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(67, 56, 202, 0.1));
        color: #c7d2fe;
        font-size: 0.72rem;
        margin-bottom: 0.3rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        border-left: 2px solid #6366f1;
        word-break: break-word;
    }
    
    /* Enhanced form buttons and controls */
    #saveBtn {
        background: linear-gradient(135deg, #10b981, #059669) !important;
        border: none !important;
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3) !important;
    }
    
    #saveBtn:hover {
        background: linear-gradient(135deg, #059669, #047857) !important;
        box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4) !important;
    }
    
    #saveBtn:disabled {
        background: linear-gradient(135deg, #6b7280, #4b5563) !important;
        box-shadow: none !important;
    }
    
    /* Enhanced search and filter controls */
    .form-control:focus {
        border-color: #6366f1 !important;
        box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.25) !important;
    }
    
    /* Enhanced pagination */
    .page-link {
        background-color: rgba(255, 255, 255, 0.1) !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
        color: #e2e8f0 !important;
        transition: all 0.2s ease !important;
    }
    
    .page-link:hover {
        background-color: rgba(99, 102, 241, 0.2) !important;
        border-color: #6366f1 !important;
        color: #ffffff !important;
    }
    
    .page-item.active .page-link {
        background-color: #6366f1 !important;
        border-color: #6366f1 !important;
    }
    
    /* Enhanced sidebar summary card */
    .card.summary-card {
        background: linear-gradient(145deg, #2d3748, #1a202c) !important;
        border: 1px solid rgba(99, 102, 241, 0.3) !important;
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.15) !important;
    }
    
    .card.summary-card:hover {
        border-color: rgba(99, 102, 241, 0.5) !important;
        box-shadow: 0 12px 35px rgba(99, 102, 241, 0.2) !important;
    }
    
    /* Enhanced summary section styling */
    .summary-section {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(67, 56, 202, 0.05));
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(99, 102, 241, 0.2);
    }
    
    .summary-title {
        color: #6366f1 !important;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.75rem;
        border-bottom: 2px solid rgba(99, 102, 241, 0.2);
        padding-bottom: 0.5rem;
    }
    
    .summary-item {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(99, 102, 241, 0.15);
        border-radius: 8px;
        padding: 0.75rem;
        margin-bottom: 0.5rem;
    }
    
    .summary-item:hover {
        background: rgba(99, 102, 241, 0.05);
        border-color: rgba(99, 102, 241, 0.3);
    }
    
    .domain-name {
        color: #e2e8f0 !important;
        font-weight: 500;
    }
    
    .inbox-count-badge {
        background: linear-gradient(135deg, #6366f1, #4f46e5) !important;
        color: white !important;
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-weight: 600;
    }
    
    /* Enhanced delete button styling */
    .delete-domain-btn {
        background: linear-gradient(135deg, #ef4444, #dc2626) !important;
        border: 1px solid rgba(239, 68, 68, 0.3) !important;
        color: white !important;
        padding: 0.25rem 0.5rem !important;
        border-radius: 6px !important;
        font-size: 0.75rem !important;
        box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2) !important;
    }
    
    .delete-domain-btn:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c) !important;
        border-color: rgba(239, 68, 68, 0.6) !important;
        box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3) !important;
        color: white !important;
    }
    
    .delete-domain-btn i {
        font-size: 0.8rem;
    }
    .domain-card {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        cursor: pointer;
    }
    
    .domain-card:hover {
        border-color: #0d6efd;
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
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
        border: 1px solid var(--second-primary);
        color: white;
        border-radius: 15px;
        padding: 1.5rem;
        position: sticky;
        top: 20px;
        background-color: #4a3aff36;
    }
    
    .btn-save {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        border-radius: 10px;
        padding: 12px 30px;
        font-weight: 600;
        color: white;
    }
    
    .btn-save:hover {
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
        /* background: linear-gradient(135deg, #ffc107 0%, #ffca2c 100%) !important; */
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
        gap: 0.5rem;
        margin: 2rem 0;
        padding: 1rem;
        background-color: #2c3e50;
        border-radius: 15px;
        border: 2px solid #34495e;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        flex-wrap: wrap;
    }
    
    .page-btn {
        padding: 0.6rem 1rem;
        border: 2px solid #34495e;
        background-color: #34495e;
        color: #ecf0f1;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 500;
        min-width: 40px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        font-size: 0.9rem;
    }
    
    .page-btn:hover:not(:disabled) {
        background-color: #1abc9c;
        color: #ffffff;
        border-color: #16a085;
        box-shadow: 0 4px 8px rgba(26, 188, 156, 0.3);
    }
    
    .page-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        background-color: #2c3e50;
        color: #7f8c8d;
        border-color: #2c3e50;
    }
    
    .page-btn.active {
        background-color: #e74c3c;
        color: #ffffff;
        border-color: #c0392b;
        box-shadow: 0 4px 8px rgba(231, 76, 60, 0.3);
    }
    
    @media (max-width: 576px) {
        .pagination-controls {
            padding: 0.8rem;
            gap: 0.3rem;
            margin: 1rem 0;
        }
        
        .page-btn {
            padding: 0.5rem 0.8rem;
            min-width: 35px;
            font-size: 0.8rem;
        }
        
        #pageInfo {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            width: 100%;
            text-align: center;
            color: #bdc3c7;
        }
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
<div class="">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-lg">
                <div class="card-body p-4">
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
                                            <select id="domainsPerPage" class="form-select" style="padding: 12px 20px; border-radius: 12px; border: 2px solid #e9ecef; font-size: 1rem; transition: all 0.3s ease;">
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
                                <div class="card summary-card">
                                    <div class="card-body">
                                        <h5 class="summary-title mb-3">
                                            <i class="ti ti-list-check me-2"></i>Selection Summary
                                        </h5>
                                    
                                        <!-- Pool Order Info -->
                                        <div class="summary-section mb-3">
                                            <div class="row g-2">
                                                <div class="col-6">
                                                    <div class="summary-item">
                                                        <small class="opacity-75 d-block">Pool Plan</small>
                                                        <small class="domain-name">{{ $poolOrder->poolPlan->name ?? 'N/A' }}</small>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <div class="summary-item">
                                                        <small class="opacity-75 d-block">Total Inboxes</small>
                                                        <small class="domain-name">{{ $poolOrder->quantity ?? 1 }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    
                                    <hr class="border-white-50">
                                    
                                    <!-- Selection Limit Notice -->
                                    <!-- <div class="alert alert-info mb-3" style="background-color: rgba(13, 202, 240, 0.1); border: 1px solid rgba(13, 202, 240, 0.3); color: #0dcaf0;">
                                        <i class="ti ti-info-circle me-2"></i>
                                        <small><strong>Limit:</strong> Up to <strong>{{ $poolOrder->quantity }}</strong> domains and <strong>{{ $poolOrder->quantity }}</strong> total inboxes.</small>
                                    </div> -->
                                    
                                        <div class="summary-section mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="domain-name">Selected Domains:</span>
                                                <span class="inbox-count-badge" id="selectedCount">0</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="domain-name">Total Inboxes:</span>
                                                <span class="inbox-count-badge" id="totalInboxes">0</span>
                                            </div>
                                        </div>

                                    <hr class="border-white-50">

                                        <!-- Selected Domains List -->
                                        <div class="summary-section">
                                            <div class="summary-title">Selected Domains</div>
                                            <div id="selectedDomainsList">
                                                <small class="opacity-75">No domains selected yet</small>
                                            </div>
                                        </div>

                                        <!-- Save Button -->
                                        <button type="submit" class="btn btn-save w-100 mt-3" id="saveBtn" disabled>
                                            <i class="ti ti-device-floppy me-2"></i>Save Configuration
                                        </button>
                                    </div>
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
    let selectedDomains = new Map();
    let currentPage = 1;
    let domainsPerPage = 50;
    let searchTerm = '';
    let currentPageDomains = [];
    let paginationData = {
        currentPage: 1,
        totalPages: 1,
        total: 0,
        from: 0,
        to: 0
    };
    
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
    loadDomainsFromServer(currentPage, searchTerm);
    
    // Load existing selections first
    loadExistingSelections();
    
    // Load domains with server-side pagination
    async function loadDomainsFromServer(page = 1, search = '') {
        try {
            loadingSpinner.style.display = 'flex';
            domainsContainer.style.display = 'none';
            paginationControls.style.display = 'none';
            
            const params = new URLSearchParams({
                ajax: '1',
                page: page,
                per_page: domainsPerPage,
                search: search
            });
            
            const response = await fetch(`{{ route('customer.pool-orders.edit', $poolOrder->id) }}?${params}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to load domains');
            }
            
            const data = await response.json();
            
            // Update pagination data
            paginationData = {
                currentPage: data.current_page || 1,
                totalPages: data.last_page || 1,
                total: data.total || 0,
                from: data.from || 0,
                to: data.to || 0
            };
            
            // Store current page domains
            currentPageDomains = data.data || [];
            
            loadingSpinner.style.display = 'none';
            
            if (currentPageDomains.length === 0 && search) {
                domainsContainer.style.display = 'none';
                noResults.style.display = 'block';
                paginationControls.style.display = 'none';
            } else {
                domainsContainer.style.display = 'grid';
                noResults.style.display = 'none';
                paginationControls.style.display = 'flex';
                
                // Render current page domains
                renderDomainsOnPage();
                updatePagination();
            }
            
            // Update search results
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
            currentPage = 1; // Reset to first page on search
            loadDomainsFromServer(currentPage, searchTerm);
        }, 500);
    });
    
    // Domains per page change
    domainsPerPageSelect.addEventListener('change', function() {
        domainsPerPage = parseInt(this.value);
        currentPage = 1; // Reset to first page
        loadDomainsFromServer(currentPage, searchTerm);
    });
    
    // Render domains on current page
    function renderDomainsOnPage() {
        domainsContainer.innerHTML = currentPageDomains.map(domain => 
            createDomainCard(domain)
        ).join('');
        
        // Add event listeners to new checkboxes
        addDomainEventListeners();
        
        // Apply existing selections to current page
        applyExistingSelections();
    }
    
    // Create domain card HTML
    function createDomainCard(domain) {
        const isSelected = selectedDomains.has(domain.id);
        // Create inbox prefixes display if domain has multiple inboxes and prefix variants
        let inboxPrefixesHtml = '';
        if (domain.available_inboxes > 1 && domain.prefix_variants) {
            const prefixVariants = typeof domain.prefix_variants === 'string' 
                ? JSON.parse(domain.prefix_variants) 
                : domain.prefix_variants;
            
                if (prefixVariants && Object.keys(prefixVariants).length > 0) {
                    // show up to 3 prefixes as block lines for better layout
                    const prefixList = Object.values(prefixVariants)
                        .slice(0, Math.min(domain.available_inboxes, 3))
                        .map(prefix => `<div class="inbox-prefix">${prefix}@${domain.name}</div>`)
                        .join('');

                    inboxPrefixesHtml = `
                    <div class="mt-2">
                        <div class="inbox-prefix inbox-prefix-muted" style="font-size:0.78rem;">Available Inboxes:</div>
                        <div>${prefixList}</div>
                    </div>
                `;
                }
        }
        
        return `
            <div class="domain-card p-3 ${isSelected ? 'selected' : ''}" data-domain-id="${domain.id}" data-domain-name="${domain.name}">
                <!-- Checkbox at the top -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check d-flex align-items-center">
                        <input class="form-check-input domain-checkbox me-3" 
                               type="checkbox" 
                               id="domain_${domain.id}"
                               value="${domain.id}"
                               data-inboxes="${domain.available_inboxes}"
                               data-name="${domain.name}"
                               style="width: 20px; height: 20px; transform: scale(1.2);"
                               ${isSelected ? 'checked' : ''}>
                        <label class="form-check-label fw-medium mb-0" for="domain_${domain.id}">
                            Select this domain
                        </label>
                    </div>
                    <div class="text-end">
                        <strong class="text-primary">${domain.available_inboxes} inboxes</strong>
                    </div>
                </div>
                
                <!-- Domain info -->
                <div class="mb-2">
                    <h6 class="mb-1 fw-bold">${domain.name}</h6>
                </div>
                
                ${inboxPrefixesHtml}
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
                Swal.fire({
                    icon: 'error',
                    title: 'Domain Limit Exceeded',
                    text: `You can only select up to ${maxQuantity} domains based on your order quantity.`,
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }
            
            if (wouldExceedInboxLimit) {
                checkbox.checked = false;
                Swal.fire({
                    icon: 'error',
                    title: 'Inbox Limit Exceeded',
                    text: `Total inboxes (${currentTotalInboxes + inboxes}) cannot exceed your order quantity (${maxQuantity}). Please select domains with fewer inboxes.`,
                    confirmButtonColor: '#0d6efd'
                });
                return;
            }
            
            // Find the full domain data for prefix variants
            const fullDomainData = currentPageDomains.find(d => d.id == domainId);
            
            // Add to selected domains
            selectedDomains.set(domainId, {
                id: domainId,
                name: domainName,
                inboxes: inboxes,
                prefixVariants: fullDomainData ? fullDomainData.prefix_variants : null
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
        const { currentPage: current, totalPages, from, to, total } = paginationData;
        
        const prevBtn = document.getElementById('prevPage');
        const nextBtn = document.getElementById('nextPage');
        const pageNumbers = document.getElementById('pageNumbers');
        const pageInfo = document.getElementById('pageInfo');
        
        // Update current page reference
        currentPage = current;
        
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
        pageInfo.textContent = `Page ${currentPage} of ${totalPages || 1} (${from}-${to} of ${total})`;
        
        // Previous/Next button events
        prevBtn.onclick = () => {
            if (currentPage > 1) {
                loadDomainsFromServer(currentPage - 1, searchTerm);
            }
        };
        
        nextBtn.onclick = () => {
            if (currentPage < totalPages) {
                loadDomainsFromServer(currentPage + 1, searchTerm);
            }
        };
    }
    
    // Go to specific page (global function)
    window.goToPage = function(page) {
        if (page !== currentPage) {
            loadDomainsFromServer(page, searchTerm);
        }
    };
    
    // Update search results
    function updateSearchResults() {
        const { total } = paginationData;
        
        if (searchTerm) {
            searchResults.textContent = `${total} domains found`;
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
            let domainListHTML = selectedArray.map((domain, index) => {
                // Create prefix variants display for summary - use domain's stored prefix data
                let prefixVariantsHtml = '';
                if (domain.prefixVariants && domain.inboxes > 1) {
                    const prefixVariants = typeof domain.prefixVariants === 'string' 
                        ? JSON.parse(domain.prefixVariants) 
                        : domain.prefixVariants;
                    
                    if (prefixVariants && Object.keys(prefixVariants).length > 0) {
                        const prefixList = Object.values(prefixVariants)
                            .slice(0, Math.min(domain.inboxes, 3))
                            .map(prefix => `<div class="summary-inbox-prefix">${prefix}@${domain.name}</div>`)
                            .join('');

                        prefixVariantsHtml = `<div class="mt-1">${prefixList}</div>`;
                    }
                }
                
                return `<div class="summary-item d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1">
                        <small class="domain-name text-truncate d-block fw-medium">${domain.name}</small>
                        <small class="inbox-count-badge">${domain.inboxes} inboxes</small>
                        ${prefixVariantsHtml}
                    </div>
                    <div class="d-flex align-items-center">
                        <small class="me-2 opacity-75">#${index + 1}</small>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-domain-btn" onclick="deselectDomain('${domain.id}')" title="Remove domain">
                            <i class="ti ti-trash"></i>
                        </button>
                    </div>
                </div>`;
            }).join('');
            
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
            // Store the existing domains for reference
            window.existingSelections = existingDomains;
            
            // Load all existing domain selections into selectedDomains Map
            if (existingDomains && existingDomains.length > 0) {
                existingDomains.forEach(domain => {
                    // Get domain data from the JSON domains field
                    const poolDomains = @json($poolOrder->pool && $poolOrder->pool->domains ? $poolOrder->pool->domains : []);
                    const domainInfo = poolDomains.find(d => d.domain_id === domain.domain_id);
                    
                    if (domainInfo) {
                        selectedDomains.set(domain.domain_id, {
                            id: domain.domain_id,
                            name: domainInfo.name || `Domain ${domain.domain_id}`,
                            inboxes: domainInfo.available_inboxes || 1,
                            prefixVariants: domainInfo.prefix_variants || null
                        });
                    }
                });
                
                // Update summary to show existing selections
                updateSummary();
            }
        @endif
    }
    
    // Apply existing selections to current page domains
    function applyExistingSelections() {
        // Update checkboxes and styling for domains that are already in selectedDomains Map
        currentPageDomains.forEach(domain => {
            if (selectedDomains.has(domain.id)) {
                const checkbox = document.querySelector(`input[value="${domain.id}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.closest('.domain-card').classList.add('selected');
                }
                
                // Update domain data with current page information (for prefix variants, etc.)
                const existingDomain = selectedDomains.get(domain.id);
                selectedDomains.set(domain.id, {
                    ...existingDomain,
                    name: domain.name,
                    inboxes: domain.available_inboxes,
                    prefixVariants: domain.prefix_variants
                });
            }
        });
        
        // Update summary to reflect any changes
        updateSummary();
    }
    
    // Form submission
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const selectedArray = Array.from(selectedDomains.keys());
        const totalInboxes = Array.from(selectedDomains.values())
            .reduce((sum, domain) => sum + domain.inboxes, 0);
        
        if (selectedArray.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Domains Selected',
                text: 'Please select at least one domain to continue.',
                confirmButtonColor: '#0d6efd'
            });
            return;
        }
        
        if (selectedArray.length > maxQuantity) {
            Swal.fire({
                icon: 'error',
                title: 'Domain Limit Exceeded',
                text: `You can only select up to ${maxQuantity} domains based on your order quantity.`,
                confirmButtonColor: '#0d6efd'
            });
            return;
        }
        
        if (totalInboxes > maxQuantity) {
            Swal.fire({
                icon: 'error',
                title: 'Inbox Limit Exceeded',
                text: `Total inboxes (${totalInboxes}) cannot exceed your order quantity (${maxQuantity}). Please adjust your domain selection.`,
                confirmButtonColor: '#0d6efd'
            });
            return;
        }
        
        // Show loading alert
        Swal.fire({
            title: 'Saving Configuration',
            text: 'Please wait while we save your domain selection...',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
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
                // Show success message with SweetAlert
                Swal.fire({
                    icon: 'success',
                    title: 'Configuration Saved!',
                    text: data.message || 'Domain selection saved successfully!',
                    confirmButtonColor: '#28a745',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: true,
                    confirmButtonText: 'Continue'
                }).then((result) => {
                    // Redirect to pool order details page
                    // window.location.href = '{{ route('customer.pool-orders.show', $poolOrder->id) }}';
                });
            } else {
                throw new Error(data.message || 'Failed to save');
            }
        })
        .catch(error => {
            console.error('Submission error:', error);
            
            // Close loading alert and show error
            Swal.fire({
                icon: 'error',
                title: 'Save Failed',
                text: error.message || 'An error occurred while saving your configuration. Please try again.',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Try Again'
            });
        })
        .finally(() => {
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        });
    });
});
</script>
@endpush