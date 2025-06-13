@extends('contractor.layouts.app')

@section('title', 'Panels')

@push('styles')
    <style>
        input,
        .form-control,
        .form-label {
            font-size: 12px
        }

        small {
            font-size: 11px
        }

        .total {
            color: var(--second-primary);
        }

        .used {
            color: #43C95C;
        }

        .remain {
            color: orange
        }

        .accordion {
            --bs-accordion-bg: transparent !important;
        }

        .accordion-button:focus {
            box-shadow: none !important
        }

        .button.collapsed {
            background-color: var(--slide-bg) !important;
            color: var(--light-color)
        }

        .button {
            background-color: var(--second-primary);
            color: var(--light-color);
            transition: all ease .4s
        }

        .accordion-body {
            color: var(--light-color)
        }        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        /* Loading state styling */
        #loadingState {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4rem 2rem;
        }

        /* Fix offcanvas backdrop issues */
        .offcanvas-backdrop {
            transition: opacity 0.15s linear !important;
        }
        
        .offcanvas-backdrop.fade {
            opacity: 0;
        }
        
        .offcanvas-backdrop.show {
            opacity: 0.5;
        }
        
        /* Ensure body doesn't keep backdrop classes */
        body:not(.offcanvas-open) {
            overflow: visible !important;
            padding-right: 0 !important;
        }
          /* Fix any remaining backdrop elements */
        .modal-backdrop,
        .offcanvas-backdrop.fade:not(.show) {
            display: none !important;
        }

        /* Split content animations */
        .collapse {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .collapse:not(.show) {
            opacity: 0;
            transform: translateY(-10px);
        }
        
        .collapse.show {
            opacity: 1;
            transform: translateY(0);
            animation: splitFadeIn 0.4s ease-out;
        }
        
        .collapse.collapsing {
            opacity: 0.5;
            transform: translateY(-5px);
        }

        /* Split fade-in animation */
        @keyframes splitFadeIn {
            0% {
                opacity: 0;
                transform: translateY(-15px) scale(0.98);
            }
            50% {
                opacity: 0.7;
                transform: translateY(-5px) scale(0.99);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Domain badge animations */
        @keyframes domainFadeIn {
            0% {
                opacity: 0;
                transform: translateY(-10px) scale(0.8);
            }
            50% {
                opacity: 0.7;
                transform: translateY(-2px) scale(0.95);
            }
            100% {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Toast animations */
        @keyframes toastSlideIn {
            0% {
                opacity: 0;
                transform: translateX(100%) scale(0.8);
            }
            100% {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }

        /* Chevron rotation animation */
        .transition-transform {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Enhanced hover effects for domain badges */
        .domain-badge {
            will-change: transform, box-shadow;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }

        .domain-badge:hover {
            transform: translateY(-3px) scale(1.08) !important;
            box-shadow: 0 6px 20px rgba(0,0,0,0.25) !important;
            filter: brightness(1.1);
        }

        /* Split container animations */
        .split-container {
            transition: all 0.3s ease;
        }

        .split-container.expanding {
            animation: splitExpand 0.4s ease-out;
        }

        @keyframes splitExpand {
            0% {
                max-height: 0;
                opacity: 0;
            }
            50% {
                opacity: 0.5;
            }
            100% {
                max-height: 1000px;
                opacity: 1;
            }
        }

        /* Fade in up animation for split containers */
        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Accordion content animation */
        .accordion-collapse {
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        .accordion-collapse.collapsing {
            overflow: hidden;
            opacity: 0.7;
            transform: translateY(-5px);
        }

        .accordion-collapse:not(.show) {
            opacity: 0;
            transform: translateY(-10px);
        }

        .accordion-collapse.show {
            opacity: 1;
            transform: translateY(0);
        }

        /* Split container hover effects */
        .domain-split-container {
            transition: all 0.3s ease;
        }

        .domain-split-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .split-header {
            transition: all 0.3s ease;
        }

        .split-header:hover {
            transform: scale(1.02);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        /* Ensure offcanvas doesn't interfere with page interaction */
        .offcanvas.hiding,
        .offcanvas:not(.show) {
            pointer-events: none;
        }
        
        /* Force cleanup of backdrop opacity */
        .offcanvas-backdrop.fade {
            opacity: 0 !important;
            transition: opacity 0.15s linear;
        }
        
        /* Ensure page remains interactive */
        body:not(.offcanvas-open):not(.modal-open) {
            overflow: visible !important;
            padding-right: 0 !important;
        }
        
        /* Hide any orphaned backdrop elements */
        div[class*="backdrop"]:empty {
            display: none !important;
        }
        
        /* Chevron icon transition */
        .transition-transform {
            transition: transform 0.3s ease;
        }
        
        /* Fade in animation for domain splits */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Toast notification animation */
        @keyframes toastSlideIn {
            from {
                opacity: 0;
                transform: translateX(100%);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
@endpush

@section('content')
    <section class="py-3">

        <!-- Advanced Search Filter UI -->
        <div class="card p-3 mb-4">
            <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#filter_1"
                role="button" aria-expanded="false" aria-controls="filter_1">
                <div>
                    <div class="d-flex gap-2 align-items-center">
                        <h6 class="text-uppercase fs-6 mb-0">Filters</h6>
                        <img src="https://static.vecteezy.com/system/resources/previews/052/011/341/non_2x/3d-white-down-pointing-backhand-index-illustration-png.png"
                            width="30" alt="">
                    </div>
                    <small>Click here to open advance search for a table</small>
                </div>
            </div>
            <div class="row collapse" id="filter_1">
                <form id="filterForm">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Panel Id</label>
                            <input type="text" name="panel_id" class="form-control" placeholder="Enter panel ID">
                        </div>
                        <div class="col-md-4 mb-3" style="display: none !important;">
                            <label class="form-label mb-0">Min Inbox Limit</label>
                            <input type="number" name="min_inbox_limit" class="form-control" placeholder="e.g. 10">
                        </div>
                        <div class="col-md-4 mb-3" style="display: none !important;">
                            <label class="form-label mb-0">Max Inbox Limit</label>
                            <input type="number" name="max_inbox_limit" class="form-control" placeholder="e.g. 100">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Min Remaining</label>
                            <input type="number" name="min_remaining" class="form-control" placeholder="e.g. 5">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Max Remaining</label>
                            <input type="number" name="max_remaining" class="form-control" placeholder="e.g. 50">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Order</label>
                            <select name="order" class="form-select">
                                <option value="desc">Newest First</option>
                                <option value="asc">Oldest First</option>
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="button" id="resetFilters" class="btn btn-outline-secondary btn-sm me-2 px-3">Reset</button>
                            <button type="submit" id="submitBtn" class="btn btn-primary btn-sm border-0 px-3">Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>  
        <!-- Grid Cards (Dynamic) -->
        <div id="panelsContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;">
            <!-- Loading state -->
            <div id="loadingState" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Loading panels...</p>
            </div>
        </div>

        <!-- Load More Button -->
        <div id="loadMoreContainer" class="text-center mt-4" style="display: none;">
            <button id="loadMoreBtn" class="btn btn-lg btn-primary px-4 me-2 border-0 animate-gradient">
                <span id="loadMoreText">Load More</span>
                <span id="loadMoreSpinner" class="spinner-border spinner-border-sm ms-2" role="status" style="display: none;">
                    <span class="visually-hidden">Loading...</span>
                </span>
            </button>
            <div id="paginationInfo" class="mt-2 text-light small">
                Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalPanels">0</span> panels
            </div>
        </div>

    </section> 
    
    


    <!-- Orders Offcanvas -->
    <div class="offcanvas offcanvas-end" style="width: 100%;" tabindex="-1" id="order-view" aria-labelledby="order-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="order-viewLabel">Panel Orders</h5>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <div id="panelOrdersContainer">
                <!-- Dynamic content will be loaded here -->
                <div id="ordersLoadingState" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading orders...</span>
                    </div>
                    <p class="mt-2">Loading panel orders...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let panels = [];
        let currentFilters = {};
        let charts = {}; // Store chart instances
        let currentPage = 1;
        let hasMorePages = false;
        let totalPanels = 0;
        let isLoading = false;

        // Load panels data
        async function loadPanels(filters = {}, page = 1, append = false) {
            try {
                if (isLoading) return; // Prevent concurrent requests
                isLoading = true;
                
                console.log('Loading panels with filters:', filters, 'page:', page, 'append:', append);
                
                if (!append) {
                    showLoading();
                    panels = []; // Reset panels array for new search
                }
                
                // Show loading state for Load More button
                if (append) {
                    showLoadMoreSpinner(true);
                }
                
                const params = new URLSearchParams({
                    ...filters,
                    page: page,
                    per_page: 12
                });
                const url = `/contractor/panels/data?${params}`;
                console.log('Fetching from URL:', url);
                
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                console.log('Response status:', response.status);
                console.log('Response ok:', response.ok);
                
                if (!response.ok) {
                    const errorText = await response.text();
                    console.error('Error response:', errorText);
                    throw new Error(`Failed to fetch panels: ${response.status} ${response.statusText}`);
                }
                  
                const data = await response.json();
                console.log('Received data:', data);
                
                const newPanels = data.data || [];
                console.log('New panels:', newPanels);
                
                if (append) {
                    panels = panels.concat(newPanels);
                } else {
                    panels = newPanels;
                }
                
                // Update pagination state
                const pagination = data.pagination || {};
                currentPage = pagination.current_page || 1;
                hasMorePages = pagination.has_more_pages || false;
                totalPanels = pagination.total || 0;
                
                console.log('Updated state:', { currentPage, hasMorePages, totalPanels, panelsCount: panels.length });
                
                renderPanels(append);
                updatePaginationInfo();
                updateLoadMoreButton();
                
            } catch (error) {
                console.error('Error loading panels:', error);
                if (!append) {
                    showError(`Failed to load panels: ${error.message}`);
                }
            } finally {
                isLoading = false;
                if (append) {
                    showLoadMoreSpinner(false);
                }

                // submit enabled
                document.getElementById('submitBtn').disabled = false;
            }
        }
        // Show loading state        
        function showLoading() {
            const container = document.getElementById('panelsContainer');
            const loadingElement = document.getElementById('loadingState');
            
            if (container && loadingElement) {
                // Keep the grid display but show only loading element
                container.style.display = 'grid';
                container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(260px, 1fr))';
                container.style.gap = '1rem';
                
                // Clear any existing content except loading
                container.innerHTML = '';
                container.appendChild(loadingElement);
                loadingElement.style.display = 'flex';
            }
        }
        // Hide loading state
        function hideLoading() {
            const loadingElement = document.getElementById('loadingState');
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
        }        // Show error message
        function showError(message) {
            hideLoading();
            const container = document.getElementById('panelsContainer');
            if (!container) {
                console.error('panelsContainer element not found');
                return;
            }
            
            // Keep grid layout but show error spanning full width
            container.style.display = 'grid';
            container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(260px, 1fr))';
            container.style.gap = '1rem';
            
            container.innerHTML = `<div class="empty-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                    <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 3rem;"></i>
                    <h5>Error</h5>
                    <p class="mb-3">${message}</p>
                    <button class="btn btn-primary" onclick="loadPanels(currentFilters)">Retry</button>
                </div>
            `;
        }
          // Render panels
        function renderPanels(append = false) {
            if (!append) {
                hideLoading();
            }
            
            const container = document.getElementById('panelsContainer');
            if (!container) {
                console.error('panelsContainer element not found');
                return;
            }
              
            if (panels.length === 0 && !append) {
                // Keep grid layout but show empty state spanning full width
                container.style.display = 'grid';
                container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(260px, 1fr))';
                container.style.gap = '1rem';
                
                container.innerHTML = `<div class="empty-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                        <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5>No Panels Found</h5>
                        <p class="mb-3">No panels match your current filters.</p>
                        <button class="btn btn-outline-primary" onclick="resetFilters()">Clear Filters</button>
                    </div>
                `;
                return;
            }
            
            // Reset container to grid layout for panels
            container.style.display = 'grid';
            container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(260px, 1fr))';
            container.style.gap = '1rem';

            if (append) {
                // Only add new panels for pagination
                const currentPanelsCount = container.children.length;
                const newPanels = panels.slice(currentPanelsCount);
                const newPanelsHtml = newPanels.map(panel => createPanelCard(panel)).join('');
                container.insertAdjacentHTML('beforeend', newPanelsHtml);
                
                // Initialize charts for new panels only
                setTimeout(() => {
                    newPanels.forEach(panel => {
                        initChart(panel);
                    });
                }, 100);
            } else {
                // Replace all content for new search
                const panelsHtml = panels.map(panel => createPanelCard(panel)).join('');
                container.innerHTML = panelsHtml;
                
                // Initialize charts after DOM is updated
                setTimeout(() => {
                    panels.forEach(panel => {
                        initChart(panel);
                    });
                }, 100);            }
        }

        // Update pagination info display
        function updatePaginationInfo() {
            const showingFromEl = document.getElementById('showingFrom');
            const showingToEl = document.getElementById('showingTo');
            const totalPanelsEl = document.getElementById('totalPanels');
            
            if (showingFromEl && showingToEl && totalPanelsEl) {
                const from = panels.length > 0 ? 1 : 0;
                const to = panels.length;
                
                showingFromEl.textContent = from;
                showingToEl.textContent = to;
                totalPanelsEl.textContent = totalPanels;
            }
        }

        // Update Load More button visibility and state
        function updateLoadMoreButton() {
            const loadMoreContainer = document.getElementById('loadMoreContainer');
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            
            if (loadMoreContainer && loadMoreBtn) {
                if (hasMorePages && panels.length > 0) {
                    loadMoreContainer.style.display = 'block';
                    loadMoreBtn.disabled = false;
                } else {
                    loadMoreContainer.style.display = 'none';
                }
            }
        }

        // Show/hide loading spinner on Load More button
        function showLoadMoreSpinner(show) {
            const loadMoreText = document.getElementById('loadMoreText');
            const loadMoreSpinner = document.getElementById('loadMoreSpinner');
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            
            if (loadMoreText && loadMoreSpinner && loadMoreBtn) {
                if (show) {
                    loadMoreText.textContent = 'Loading...';
                    loadMoreSpinner.style.display = 'inline-block';
                    loadMoreBtn.disabled = true;
                } else {
                    loadMoreText.textContent = 'Load More';
                    loadMoreSpinner.style.display = 'none';
                    loadMoreBtn.disabled = false;
                }
            }
        }

        // Load More button click handler
        function loadMorePanels() {
            if (hasMorePages && !isLoading) {
                loadPanels(currentFilters, currentPage + 1, true);
            }
        }
        
        // Create panel card HTML
        function createPanelCard(panel) {
            // console.log('Creating card for panel:', panel);
            const used = panel.limit - panel.remaining_limit;
            const remaining = panel.remaining_limit;
            const totalOrders = panel.recent_orders ? panel.recent_orders.length : 0;
            return `
                <div class="card p-3 d-flex flex-column gap-1">                    
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0">${'PNL-' + panel.id || panel.auto_generated_id}</h6>
                        <div class="d-flex flex-column gap-1">
                            <div class="d-flex gap-3 justify-content-between">
                                <small class="total">Total: ${panel.limit}</small>
                                <small class="remain">Remaining: ${remaining}</small>
                                <small class="used">Used: ${used}</small>
                            </div>
                        </div>
                    </div>
                    <div id="chart-${panel.id}"></div>
                    <h6 class="mb-0">Orders</h6>
                    <div style="background-color: #5750bf89; border: 1px solid var(--second-primary);"
                        class="p-2 rounded-1 d-flex align-items-center justify-content-between gap-2">
                        <small>${panel.total_orders || 0} Orders of Inboxes</small>
                        <button style="font-size: 12px" onclick="viewPanelOrders(${panel.id})" data-bs-toggle="offcanvas" data-bs-target="#order-view"
                            aria-controls="order-view" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary">
                            View
                        </button>                    
                    </div>
                </div>
            `;
        }
        
        // Initialize chart for a panel
        function initChart(panel) {
            // Check if ApexCharts is available
            if (typeof ApexCharts === 'undefined') {
                console.error('ApexCharts is not loaded');
                return;
            }

            const chartElement = document.querySelector(`#chart-${panel.id}`);
            if (!chartElement) {
                console.warn(`Chart element #chart-${panel.id} not found`);
                return;
            }

            const total = panel.limit;
            const used = panel.limit - panel.remaining_limit;
            const remaining = panel.remaining_limit;

            // Avoid division by zero
            if (total === 0) {
                console.warn(`Panel ${panel.id} has zero total limit`);
                return;
            }

            const options = {
                series: [
                    total,
                    Math.round((used / total) * 100),
                    Math.round((remaining / total) * 100)
                ],
                chart: {
                    height: 220,
                    type: 'radialBar',
                },
                plotOptions: {
                    radialBar: {
                        offsetY: 0,
                        startAngle: 0,
                        endAngle: 270,
                        hollow: {
                            size: '40%',
                        },
                        dataLabels: {
                            name: {
                                show: true
                            },
                            value: {
                                show: false
                            }
                        }
                    }
                },
                colors: ['#5750bf', '#2AC747', '#FDC007'],
                labels: [
                    `Total: ${total}`,
                    `Used: ${used}`,
                    `Remaining: ${remaining}`
                ],
                legend: {
                    show: true,
                    position: 'bottom',
                    formatter: function(seriesName, opts) {
                        const rawValue = seriesName.includes('Used') ? used : 
                                       seriesName.includes('Remaining') ? remaining : total;
                        return `${seriesName}: ${rawValue}`;
                    },
                    labels: {
                        useSeriesColors: true
                    },
                    itemMargin: {
                        vertical: 5
                    }                }
            };
            
            try {
                // Clean up existing chart if it exists
                if (charts[panel.id]) {
                    charts[panel.id].destroy();
                }
                
                const chart = new ApexCharts(chartElement, options);                
                chart.render();
                charts[panel.id] = chart;
            } catch (error) {
                console.error(`Error creating chart for panel ${panel.id}:`, error);
            }
        }
        
        // View panel orders
        async function viewPanelOrders(panelId) {
            try {
                // Show loading in offcanvas
                const container = document.getElementById('panelOrdersContainer');
                if (container) {
                    container.innerHTML = `
                        <div id="ordersLoadingState" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading orders...</span>
                            </div>
                            <p class="mt-2">Loading panel orders...</p>
                        </div>
                    `;
                }
                  // Show offcanvas with proper cleanup
                const offcanvasElement = document.getElementById('order-view');
                const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
                
                // Add event listeners for proper cleanup
                offcanvasElement.addEventListener('hidden.bs.offcanvas', function () {
                    // Clean up any remaining backdrop elements
                    const backdrops = document.querySelectorAll('.offcanvas-backdrop, .modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    
                    // Ensure body classes are removed
                    document.body.classList.remove('offcanvas-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }, { once: true });
                
                offcanvas.show();
                
                // Fetch orders
                const response = await fetch(`/contractor/panels/${panelId}/orders`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                if (!response.ok) throw new Error('Failed to fetch orders');
                
                const data = await response.json();
                renderPanelOrders(data.orders, data.panel);
                  } catch (error) {
                console.error('Error loading panel orders:', error);
                const container = document.getElementById('panelOrdersContainer');
                if (container) {
                    container.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle text-danger fs-3 mb-3"></i>
                            <h5>Error Loading Orders</h5>
                            <p>Failed to load panel orders. Please try again.</p>
                            <button class="btn btn-primary" onclick="viewPanelOrders(${panelId})">Retry</button>                        </div>
                    `;
                }
            }
        }
        
        // Render panel orders in offcanvas
        function renderPanelOrders(orders, panel) {
            console.log('orders', orders);
            const container = document.getElementById('panelOrdersContainer');
            
            if (!orders || orders.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-inbox text-muted fs-3 mb-3"></i>
                        <h5>No Orders Found</h5>
                        <p>This panel doesn't have any orders yet.</p>
                    </div>
                `;
                return;
            }

            const ordersHtml = `
                <div class="mb-4">
                    <h6>PNL- ${panel.id}</h6>
                    <p class="text-muted small">${panel.description || 'No description'}</p>
                </div>
                <div class="accordion accordion-flush" id="panelOrdersAccordion">
                    ${orders.map((order, index) => `
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <div class="button p-3 collapsed d-flex align-items-center justify-content-between" type="button"
                                    aria-expanded="false"
                                    aria-controls="order-collapse-${order.order_id}"
                                    onclick="toggleOrderAccordion('order-collapse-${order.order_id}', this, event)">
                                    <small>ORDER ID: #${order.order_id || 0 }</small>
                                    <small class="text-light"><i class="fas fa-envelope me-1"></i><span>Inboxes:</span> <span class="fw-bold">${order.space_assigned || order.inboxes_per_domain || 0}</span>${order.remaining_order_panels && order.remaining_order_panels.length > 0 ? `<span> (${order.remaining_order_panels.length} more split${order.remaining_order_panels.length > 1 ? 's' : ''}</span>` : ''})</small>
                                    <div class="d-flex align-items-center gap-2">
                                        ${order.status === 'unallocated' ? `
                                            <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-success"
                                                onclick="event.stopPropagation(); assignOrderToMe(${order.order_panel_id}, this)">
                                                Assign to Me
                                            </button>
                                        ` : `
                                            <span class="badge ${getStatusBadgeClass(order.status)}" style="font-size: 10px;">
                                                ${order.status || 'Unknown'}
                                            </span>
                                        `}
                                        <i class="fas fa-chevron-down transition-transform" id="accordion-icon-${order.order_id}" style="font-size: 12px; transition: transform 0.3s ease;"></i>
                                    </div>
                                </div>
                            </h2>
                            <div id="order-collapse-${order.order_id}" class="accordion-collapse collapse" data-bs-parent="#panelOrdersAccordion">
                                <div class="accordion-body">
                                
                                    
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th scope="col">#</th>
                                                    <th scope="col">Panel ID</th>
                                                    <th scope="col">Order Panel ID</th>
                                                    <th scope="col">Status</th>
                                                    
                                                    <th scope="col">Inboxes/Domain</th>
                                                    <th scope="col">Total Domains</th>
                                                    <th scope="col">Inboxes</th>
                                                    <th scope="col">Date</th>
                                                    <th scope="col">Action</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                
                                                <tr>
                                                    <th scope="row">${index + 1}</th>
                                                    <td>PNL-${order.panel_id || 'N/A'}</td>
                                                    <td>${order.order_panel_id || 'N/A'}</td>
                                                    
                                                    <td>
                                                        <span class="badge badge-update-text ${getStatusBadgeClass(order.status)}">${order.status || 'Unknown'}</span>
                                                    </td>
                                                    
                                                    <td>${order.inboxes_per_domain || 'N/A'}</td>
                                                    <td>
                                                        <span class="badge bg-success" style="font-size: 10px;">
                                                            ${order.splits ? order.splits.reduce((total, split) => total + (split.domains ? split.domains.length : 0), 0) : 0} domain(s)
                                                        </span>
                                                    </td>
                                                    
                                                    <td>${order.space_assigned || 'N/A'}</td>
                                                    <td>${formatDate(order.created_at)}</td>
                                                    <td>
                                                        <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary"
                                                            onclick="window.location.href='/contractor/orders/${order.order_panel_id}/split/view'">
                                                            View
                                                        </button>
                                                    </td>
                                                </tr>
                                                ${order.remaining_order_panels && order.remaining_order_panels.length > 0 ? 
                                                    order.remaining_order_panels.map((remainingPanel, panelIndex) => `
                                                        <tr>
                                                            <th scope="row">${index + 1}.${panelIndex + 1}</th>
                                                            <td>PNL-${remainingPanel.panel_id || 'N/A'}</td>
                                                            <td>${remainingPanel.order_panel_id || 'N/A'}</td>
                                                            
                                                            <td>
                                                                <span class="badge badge-update-text ${getStatusBadgeClass(remainingPanel.status)}">${remainingPanel.status || 'Unknown'}</span>
                                                            </td>
                                                            
                                                            <td>${remainingPanel.inboxes_per_domain || 'N/A'}</td>
                                                            <td>
                                                                <span class="badge bg-success" style="font-size: 10px;">
                                                                    ${remainingPanel.domains_count || 0} domain(s)
                                                                </span>
                                                            </td>
                                                            <td>${remainingPanel.space_assigned || 'N/A'}</td>
                                                            <td>${formatDate(remainingPanel.created_at || order.created_at)}</td>
                                                            <td>
                                                                <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary"
                                                                    onclick="window.location.href='/contractor/orders/${remainingPanel.order_panel_id}/split/view'">
                                                                    View
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    `).join('') : ''
                                                }
                                            </tbody>
                                        </table>
                                    </div>



                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card p-3 mb-3">
                                                <h6 class="d-flex align-items-center gap-2">
                                                    <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                                        <i class="fa-regular fa-envelope"></i>
                                                    </div>
                                                    Email configurations
                                                </h6>
        
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span>Total Inboxes <br> ${order.splits ? (() => {
                                                        const mainDomainsCount = order.splits.reduce((total, split) => total + (split.domains ? split.domains.length : 0), 0);  
                                                        const inboxesPerDomain = order.reorder_info?.inboxes_per_domain || 0;
                                                        const mainTotalInboxes = mainDomainsCount * inboxesPerDomain;
                                                        
                                                        let splitDetails = `<br><span class="badge bg-white text-dark me-1" style="font-size: 10px; font-weight: bold;">Split 01</span> Domains: ${mainTotalInboxes} (${mainDomainsCount} domains × ${inboxesPerDomain})<br>`;
                                                        
                                                        // Add remaining splits details
                                                        if (order.remaining_order_panels && order.remaining_order_panels.length > 0) {
                                                            order.remaining_order_panels.forEach((panel, index) => {
                                                                const splitDomainsCount = panel.domains_count || 0;
                                                                const splitInboxes = splitDomainsCount * inboxesPerDomain;
                                                                splitDetails += `<br><div class="text-white"><span class="badge bg-white text-dark me-1" style="font-size: 10px; font-weight: bold;">Split ${String(index + 2).padStart(2, '0')}</span> Domains: ${splitInboxes} (${splitDomainsCount} domains × ${inboxesPerDomain})</div>`;
                                                            });
                                                        }
                                                        
                                                        const totalAllInboxes = mainTotalInboxes + (order.remaining_order_panels ? 
                                                            order.remaining_order_panels.reduce((total, panel) => total + ((panel.domains_count || 0) * inboxesPerDomain), 0) : 0);
                                                        const totalAllDomains = mainDomainsCount + (order.remaining_order_panels ? 
                                                            order.remaining_order_panels.reduce((total, panel) => total + (panel.domains_count || 0), 0) : 0);
                                                        
                                                        return `<strong>Total: ${totalAllInboxes} (${totalAllDomains} domains)</strong><br>${splitDetails}`;
                                                    })() : 'N/A'}</span>
                                                    
                                                </div>
                                                 
                                                <hr>
                                                <div class="d-flex flex-column">
                                                    <span class="opacity-50">Prefix Variants</span>
                                                    ${renderPrefixVariants(order.reorder_info)}
                                                </div>
                                                <div class="d-flex flex-column mt-3">
                                                    <span class="opacity-50">Profile Picture URL</span>
                                                    <span>${order.reorder_info?.profile_picture_link || 'N/A'}</span>
                                                </div>
                                                <div class="d-flex flex-column mt-3">
                                                    <span class="opacity-50">Email Persona Password</span>
                                                    <span>${order.reorder_info?.email_persona_password || 'N/A'}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="card p-3 overflow-y-auto" style="max-height: 30rem">
                                                <h6 class="d-flex align-items-center gap-2">
                                                    <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                                        <i class="fa-solid fa-earth-europe"></i>
                                                    </div>
                                                    Domains &amp; Configuration
                                                </h6>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Hosting Platform</span>
                                                    <span>${order.reorder_info?.hosting_platform || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Platform Login</span>
                                                    <span>${order.reorder_info?.platform_login || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Platform Password</span>
                                                    <span>${order.reorder_info?.platform_password || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Domain Forwarding Destination URL</span>
                                                    <span>${order.reorder_info?.forwarding_url || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Sending Platform</span>
                                                    <span>${order.reorder_info?.sending_platform || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Sending platform Sequencer - Login</span>
                                                    <span>${order.reorder_info?.sequencer_login || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Sending platform Sequencer - Password</span>
                                                    <span>${order.reorder_info?.sequencer_password || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column">
                                                    <span class="opacity-50 mb-3">
                                                        <i class="fa-solid fa-globe me-2"></i>All Domains & Splits
                                                    </span>
                                                    
                                                    <!-- Main Order Domains -->
                                                    <div class="domain-split-container mb-3" style="animation: fadeInUp 0.5s ease-out;">
                                                        <div class="split-header d-flex align-items-center justify-content-between p-2 rounded-top" 
                                                             style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); cursor: pointer;"
                                                             onclick="toggleSplit('main-split-${order.order_id}')">
                                                            <div class="d-flex align-items-center">
                                                                <span class="badge bg-white text-dark me-2" style="font-size: 10px; font-weight: bold;">
                                                                    Split 01
                                                                </span>
                                                                <small class="text-white fw-bold">PNL-${order.panel_id } Domains</small>
                                                            </div>
                                                            <div class="d-flex align-items-center">
                                                                <span class="badge bg-white bg-opacity-25 text-white me-2" style="font-size: 9px;">
                                                                    ${order.splits ? order.splits.reduce((total, split) => total + (split.domains ? (Array.isArray(split.domains) ? split.domains.length : (split.domains ? 1 : 0)) : 0), 0) : 0} domains
                                                                </span>
                                                                <i class="fa-solid fa-copy text-white me-2" style="font-size: 10px; cursor: pointer; opacity: 0.8;" 
                                                                   title="Copy all domains from Split 01" 
                                                                   onclick="event.stopPropagation(); copyAllDomainsFromSplit('main-split-${order.order_id}', 'Split 01')"></i>
                                                                <i class="fa-solid fa-chevron-down text-white transition-transform" id="icon-main-split-${order.order_id}"></i>
                                                            </div>
                                                        </div>
                                                        <div class="split-content collapse show" id="main-split-${order.order_id}">
                                                            <div class="p-3" style="background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.2); border-top: none; border-radius: 0 0 8px 8px;">
                                                                <div class="domains-grid">
                                                                    ${renderDomainsWithStyle(order.splits)}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Remaining Order Panels Domains -->
                                                    ${order.remaining_order_panels && order.remaining_order_panels.length > 0 ? 
                                                        order.remaining_order_panels.map((panel, index) => `
                                                            <div class="domain-split-container mb-3" style="animation: fadeInUp 0.5s ease-out ${(index + 1) * 0.1}s both;">
                                                                <div  class="split-header d-flex align-items-center justify-content-between p-2 rounded-top" 
                                                             style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); cursor: pointer;"
                                                                     onclick="toggleSplit('remaining-split-${order.order_id}-${index}')">
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="badge bg-white text-dark me-2" style="font-size: 10px; font-weight: bold;">
                                                                            Split ${String(index + 2).padStart(2, '0')}
                                                                        </span>
                                                                        <small class="text-white fw-bold">PNL-${panel.panel_id} Domains</small>
                                                                    </div>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="badge bg-white bg-opacity-25 text-white me-2" style="font-size: 9px;">
                                                                            ${panel.domains_count || 0} domains
                                                                        </span>
                                                                        <i class="fa-solid fa-copy text-white me-2" style="font-size: 10px; cursor: pointer; opacity: 0.8;" 
                                                                           title="Copy all domains from Split ${String(index + 2).padStart(2, '0')}" 
                                                                           onclick="event.stopPropagation(); copyAllDomainsFromSplit('remaining-split-${order.order_id}-${index}', 'Split ${String(index + 2).padStart(2, '0')}')"></i>
                                                                        <i class="fa-solid fa-chevron-down text-white transition-transform" id="icon-remaining-split-${order.order_id}-${index}"></i>
                                                                    </div>
                                                                </div>
                                                                <div class="split-content collapse" id="remaining-split-${order.order_id}-${index}">
                                                                    <div class="p-3" style="background: rgba(240, 147, 251, 0.1); border: 1px solid rgba(240, 147, 251, 0.2); border-top: none; border-radius: 0 0 8px 8px;">
                                                                        <div class="domains-grid">
                                                                            ${renderDomainsWithStyle(panel.splits)}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        `).join('') : ''
                                                    }
                                                </div>
                                                <div class="d-flex flex-column mt-3">
                                                    <span class="opacity-50">Additional Notes</span>
                                                    <span>${order.reorder_info?.additional_notes || 'N/A'}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>


                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            
            container.innerHTML = ordersHtml;
            
            // Initialize chevron states and animations after rendering
            setTimeout(function() {
                initializeChevronStates();
                
                // Initialize Bootstrap accordion events without animations
                const accordionElements = container.querySelectorAll('.accordion-collapse');
                accordionElements.forEach(accordionEl => {
                    accordionEl.addEventListener('show.bs.collapse', function () {
                        // Remove animations - keep static display
                        this.style.opacity = '1';
                        this.style.transform = 'none';
                        this.style.transition = 'none';
                    });
                    
                    accordionEl.addEventListener('hide.bs.collapse', function () {
                        // Remove animations - keep static display
                        this.style.transition = 'none';
                        this.style.opacity = '1';
                        this.style.transform = 'none';
                    });
                });
                
                // Remove staggered animation from domain split containers
                const splitContainers = container.querySelectorAll('.domain-split-container');
                splitContainers.forEach((container, index) => {
                    container.style.animation = 'none';
                });                }, 100);
        }

        // Function to toggle order accordion with arrow icon rotation
        function toggleOrderAccordion(targetId, buttonElement, event) {
            // Prevent default Bootstrap behavior
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            const target = document.getElementById(targetId);
            if (!target) return;
            
            // Get the order ID from targetId (e.g., "order-collapse-123" -> "123")
            const orderId = targetId.replace('order-collapse-', '');
            const arrowIcon = document.getElementById(`accordion-icon-${orderId}`);
            
            // Check if accordion is currently expanded
            const isCurrentlyExpanded = target.classList.contains('show');
            
            if (isCurrentlyExpanded) {
                // Collapse
                target.classList.remove('show');
                target.classList.add('collapse');
                buttonElement.setAttribute('aria-expanded', 'false');
                buttonElement.classList.add('collapsed');
                
                // Rotate arrow down (collapsed state)
                if (arrowIcon) {
                    arrowIcon.style.transform = 'rotate(0deg)';
                    arrowIcon.classList.remove('fa-chevron-up');
                    arrowIcon.classList.add('fa-chevron-down');
                }
            } else {
                // Expand
                target.classList.add('show');
                target.classList.remove('collapse');
                buttonElement.setAttribute('aria-expanded', 'true');
                buttonElement.classList.remove('collapsed');
                
                // Rotate arrow up (expanded state)
                if (arrowIcon) {
                    arrowIcon.style.transform = 'rotate(180deg)';
                    arrowIcon.classList.remove('fa-chevron-down');
                    arrowIcon.classList.add('fa-chevron-up');
                }
            }
        }

        // Helper function to get status badge class
        function getStatusBadgeClass(status) {
            switch(status) {
                case 'completed': return 'bg-success';
                case 'unallocated': return 'bg-warning text-dark';
                case 'allocated': return 'bg-info';
                case 'rejected': return 'bg-danger';
                case 'in-progress': return 'bg-primary';
                default: return 'bg-secondary';
            }
        }
        
        // Helper function to format date
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            try {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: '2-digit'
                });
            } catch (error) {
                return 'Invalid Date';
            }
        }

        // Helper function to render prefix variants
        function renderPrefixVariants(reorderInfo) {
            if (!reorderInfo) return '<span>N/A</span>';
            
            let variants = [];
            
            // Check if we have the new prefix_variants JSON format
            if (reorderInfo.prefix_variants) {
                try {
                    const prefixVariants = typeof reorderInfo.prefix_variants === 'string' 
                        ? JSON.parse(reorderInfo.prefix_variants) 
                        : reorderInfo.prefix_variants;
                    
                    Object.keys(prefixVariants).forEach((key, index) => {
                        if (prefixVariants[key]) {
                            variants.push(`<span>Variant ${index + 1}: ${prefixVariants[key]}</span>`);
                        }
                    });
                } catch (e) {
                    console.warn('Could not parse prefix variants:', e);
                }
            }
            
            // Fallback to old individual fields if new format is empty
            if (variants.length === 0) {
                if (reorderInfo.prefix_variant_1) {
                    variants.push(`<span>Variant 1: ${reorderInfo.prefix_variant_1}</span>`);
                }
                if (reorderInfo.prefix_variant_2) {
                    variants.push(`<span>Variant 2: ${reorderInfo.prefix_variant_2}</span>`);
                }
            }
            
            return variants.length > 0 ? variants.join('') : '<span>N/A</span>';
        }

        // Helper function to render domains from splits
        function renderDomains(splits) {
            console.log(splits);
            if (!splits || splits.length === 0) {
                return '<span>N/A</span>';
            }
            
            let allDomains = [];
            
            splits.forEach(split => {
                if (split.domains) {
                    // Handle different data types for domains
                    if (Array.isArray(split.domains)) {
                        // Check if it's an array of objects with domain property
                        split.domains.forEach(domainItem => {
                            if (typeof domainItem === 'object' && domainItem.domain) {
                                allDomains.push(domainItem.domain);
                            } else if (typeof domainItem === 'string') {
                                allDomains.push(domainItem);
                            }
                        });
                    } else if (typeof split.domains === 'string') {
                        // If it's a string, split by common separators
                        const domainString = split.domains.trim();
                        if (domainString) {
                            // Split by comma, newline, or semicolon
                            const domains = domainString.split(/[,;\n\r]+/).map(d => d.trim()).filter(d => d);
                            allDomains = allDomains.concat(domains);
                        }
                    } else if (typeof split.domains === 'object' && split.domains !== null) {
                        // If it's an object, try to extract domain values
                        const domainValues = Object.values(split.domains).filter(d => d && typeof d === 'string');
                        allDomains = allDomains.concat(domainValues);
                    }
                }
            });
            
            if (allDomains.length === 0) {
                return '<span>N/A</span>';
            }
            
            // Filter out any remaining non-string values and display domains
            return allDomains
                .filter(domain => domain && typeof domain === 'string')
                .map(domain => `<span class="d-block">${domain}</span>`)
                .join('');
        }
        // Enhanced function to render domains with attractive styling
        function renderDomainsWithStyle(splits) {
            if (!splits || splits.length === 0) {
                return '<div class="text-center py-3"><small class="text-muted">No domains available</small></div>';
            }
            
            let allDomains = [];
            
            splits.forEach(split => {
                if (split.domains) {
                    // Handle different data types for domains
                    if (Array.isArray(split.domains)) {
                        split.domains.forEach(domainItem => {
                            if (typeof domainItem === 'object' && domainItem.domain) {
                                allDomains.push(domainItem.domain);
                            } else if (typeof domainItem === 'string') {
                                allDomains.push(domainItem);
                            }
                        });
                    } else if (typeof split.domains === 'string') {
                        const domainString = split.domains.trim();
                        if (domainString) {
                            const domains = domainString.split(/[,;\n\r]+/).map(d => d.trim()).filter(d => d);
                            allDomains = allDomains.concat(domains);
                        }
                    } else if (typeof split.domains === 'object' && split.domains !== null) {
                        const domainValues = Object.values(split.domains).filter(d => d && typeof d === 'string');
                        allDomains = allDomains.concat(domainValues);
                    }
                }
            });
            
            if (allDomains.length === 0) {
                return '<div class="text-center py-3"><small class="text-muted">No domains available</small></div>';
            }
            
            // Create styled domain badges
            return allDomains
                .filter(domain => domain && typeof domain === 'string')
                .map((domain, index) => `
                    <span class="domain-badge" style="
                        display: inline-block;
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        padding: 4px 8px;
                        margin: 2px 2px;
                        border-radius: 12px;
                        font-size: 11px;
                        font-weight: 500;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        animation: domainFadeIn 0.3s ease-out ${index * 0.05}s both;
                        transition: all 0.3s ease;
                        cursor: pointer;
                    " 
                    onmouseover="this.style.transform='translateY(-2px) scale(1.05)'; this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'"
                    onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'"
                    title="Click to copy: ${domain}"
                    onclick="copyToClipboard('${domain}')">
                        <i class="fa-solid fa-globe me-1" style="font-size: 9px;"></i>${domain}
                    </span>
                `).join('');
        }

        // Function to toggle split sections with enhanced animations
        function toggleSplit(splitId) {
            const content = document.getElementById(splitId);
            const icon = document.getElementById('icon-' + splitId);
            
            if (content && icon) {
                // Check current state and toggle
                const isCurrentlyShown = content.classList.contains('show');
                
                if (isCurrentlyShown) {
                    // Hide the content with animation
                    content.style.opacity = '0';
                    content.style.transform = 'translateY(-10px)';
                    
                    setTimeout(() => {
                        content.classList.remove('show');
                        icon.style.transform = 'rotate(-90deg)';
                    }, 150);
                } else {
                    // Show the content with animation
                    content.classList.add('show');
                    content.style.opacity = '0';
                    content.style.transform = 'translateY(-15px) scale(0.98)';
                    
                    // Trigger the animation
                    requestAnimationFrame(() => {
                        content.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                        content.style.opacity = '1';
                        content.style.transform = 'translateY(0) scale(1)';
                        icon.style.transform = 'rotate(0deg)';
                        
                        // Add expanding class for additional effects
                        const container = content.closest('.split-container');
                        if (container) {
                            container.classList.add('expanding');
                            setTimeout(() => {
                                container.classList.remove('expanding');
                            }, 400);
                        }
                    });
                    
                    // Animate domain badges within the split with staggered delay
                    setTimeout(() => {
                        const domainBadges = content.querySelectorAll('.domain-badge');
                        domainBadges.forEach((badge, index) => {
                            badge.style.animation = `domainFadeIn 0.3s ease-out ${index * 0.05}s both`;
                        });
                    }, 200);
                }
            }
        }
        
        // Function to initialize chevron states and animations on page load
        function initializeChevronStates() {
            // Find all collapsible elements and set initial chevron states
            document.querySelectorAll('[id^="main-split-"], [id^="remaining-split-"]').forEach(function(element) {
                const splitId = element.id;
                const icon = document.getElementById('icon-' + splitId);
                
                if (icon) {
                    // Add transition class for smooth chevron rotation
                    icon.classList.add('transition-transform');
                    
                    // Check if the element has 'show' class or is visible
                    const isVisible = element.classList.contains('show') || 
                                    element.classList.contains('collapse') && element.classList.contains('show');
                    
                    if (isVisible) {
                        icon.style.transform = 'rotate(0deg)';
                        // Set initial animation state for visible content
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    } else {
                        icon.style.transform = 'rotate(-90deg)';
                        // Set initial hidden state
                        element.style.opacity = '0';
                        element.style.transform = 'translateY(-10px)';
                    }
                }
            });
            
            // Also initialize any other collapsible elements with transition-transform class
            document.querySelectorAll('.transition-transform').forEach(function(icon) {
                if (icon.id.startsWith('icon-')) {
                    const splitId = icon.id.replace('icon-', '');
                    const content = document.getElementById(splitId);
                    
                    if (content) {
                        const isVisible = content.classList.contains('show');
                        if (isVisible) {
                            icon.style.transform = 'rotate(0deg)';
                            content.style.opacity = '1';
                            content.style.transform = 'translateY(0)';
                        } else {
                            icon.style.transform = 'rotate(-90deg)';
                            content.style.opacity = '0';
                            content.style.transform = 'translateY(-10px)';
                        }
                    }
                }
            });
            
            // Initialize domain badge animations for visible splits
            document.querySelectorAll('.collapse.show .domain-badge').forEach((badge, index) => {
                badge.style.animation = `domainFadeIn 0.3s ease-out ${index * 0.03}s both`;
            });
        }

        // Function to copy domain to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show a temporary success message
                const toast = document.createElement('div');
                toast.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #28a745;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 8px;
                        font-size: 14px;
                        z-index: 9999;
                        animation: toastSlideIn 0.3s ease-out;
                    ">
                        <i class="fa-solid fa-check me-2"></i>Copied: ${text}
                    </div>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 2000);
            }).catch(() => {
                console.warn('Failed to copy to clipboard');
            });
        }

        // Function to copy all domains from a split to clipboard
        function copyAllDomains(splits, splitName) {
            if (!splits || splits.length === 0) {
                // Show error message
                const toast = document.createElement('div');
                toast.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #dc3545;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 8px;
                        font-size: 14px;
                        z-index: 9999;
                        animation: toastSlideIn 0.3s ease-out;
                    ">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>No domains to copy
                    </div>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 2000);
                return;
            }
            
            let allDomains = [];
            
            splits.forEach(split => {
                if (split.domains) {
                    // Handle different data types for domains
                    if (Array.isArray(split.domains)) {
                        split.domains.forEach(domainItem => {
                            if (typeof domainItem === 'object' && domainItem.domain) {
                                allDomains.push(domainItem.domain);
                            } else if (typeof domainItem === 'string') {
                                allDomains.push(domainItem);
                            }
                        });
                    } else if (typeof split.domains === 'string') {
                        const domainString = split.domains.trim();
                        if (domainString) {
                            const domains = domainString.split(/[,;\n\r]+/).map(d => d.trim()).filter(d => d);
                            allDomains = allDomains.concat(domains);
                        }
                    } else if (typeof split.domains === 'object' && split.domains !== null) {
                        const domainValues = Object.values(split.domains).filter(d => d && typeof d === 'string');
                        allDomains = allDomains.concat(domainValues);
                    }
                }
            });
            
            // Filter out any remaining non-string values
            const validDomains = allDomains.filter(domain => domain && typeof domain === 'string');
            
            if (validDomains.length === 0) {
                // Show error message
                const toast = document.createElement('div');
                toast.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #dc3545;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 8px;
                        font-size: 14px;
                        z-index: 9999;
                        animation: toastSlideIn 0.3s ease-out;
                    ">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>No valid domains found
                    </div>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 2000);
                return;
            }
            
            // Join domains with newlines for easy copying
            const domainsText = validDomains.join('\n');
            
            navigator.clipboard.writeText(domainsText).then(() => {
                // Show success message
                const toast = document.createElement('div');
                toast.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #28a745;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 8px;
                        font-size: 14px;
                        z-index: 9999;
                        animation: toastSlideIn 0.3s ease-out;
                    ">
                        <i class="fa-solid fa-check me-2"></i>Copied ${validDomains.length} domains from ${splitName}
                    </div>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 2000);
            }).catch(() => {
                // Show error message
                const toast = document.createElement('div');
                toast.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #dc3545;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 8px;
                        font-size: 14px;
                        z-index: 9999;
                        animation: toastSlideIn 0.3s ease-out;
                    ">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>Failed to copy domains
                    </div>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 2000);
            });
        }

        // Function to copy all domains from a split container by extracting them from the DOM
        function copyAllDomainsFromSplit(splitId, splitName) {
            const splitContainer = document.getElementById(splitId);
            if (!splitContainer) {
                // Show error message
                const toast = document.createElement('div');
                toast.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #dc3545;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 8px;
                        font-size: 14px;
                        z-index: 9999;
                        animation: toastSlideIn 0.3s ease-out;
                    ">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>Split container not found
                    </div>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 2000);
                return;
            }
            
            // Extract domain names from the domain badges in the split container
            // The domains are directly in .domain-badge elements, not in child spans
            const domainBadges = splitContainer.querySelectorAll('.domain-badge');
            const domains = [];
            
            domainBadges.forEach(badge => {
                // Get text content and remove the globe icon
                const fullText = badge.textContent.trim();
                // Remove the globe icon (which appears as a character) and any extra whitespace
                const domainText = fullText.replace(/^\s*[\u{1F30D}\u{1F310}]?\s*/, '').trim();
                if (domainText && domainText !== '') {
                    domains.push(domainText);
                }
            });
            
            if (domains.length === 0) {
                // Show error message
                const toast = document.createElement('div');
                toast.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #dc3545;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 8px;
                        font-size: 14px;
                        z-index: 9999;
                        animation: toastSlideIn 0.3s ease-out;
                    ">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>No domains found in ${splitName}
                    </div>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 2000);
                return;
            }
            
            // Join domains with newlines for easy copying
            const domainsText = domains.join('\n');
            
            navigator.clipboard.writeText(domainsText).then(() => {
                // Show success message
                const toast = document.createElement('div');
                toast.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #28a745;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 8px;
                        font-size: 14px;
                        z-index: 9999;
                        animation: toastSlideIn 0.3s ease-out;
                    ">
                        <i class="fa-solid fa-check me-2"></i>Copied ${domains.length} domains from ${splitName}
                    </div>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 2000);
            }).catch(() => {
                // Show error message
                const toast = document.createElement('div');
                toast.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 20px;
                        right: 20px;
                        background: #dc3545;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 8px;
                        font-size: 14px;
                        z-index: 9999;
                        animation: toastSlideIn 0.3s ease-out;
                    ">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>Failed to copy domains
                    </div>
                `;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 2000);
            });
        }

        // Handle filter form submission
        document.getElementById('filterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const filters = {};
            
            for (let [key, value] of formData.entries()) {
                if (value.trim() !== '') {
                    filters[key] = value.trim();
                }
            }
            
            currentFilters = filters;
            currentPage = 1;
            hasMorePages = false;
            totalPanels = 0;
            loadPanels(filters);
        });
          // Reset filters
        function resetFilters() {
            document.getElementById('filterForm').reset();
            currentFilters = {};
            currentPage = 1;
            hasMorePages = false;
            totalPanels = 0;
            loadPanels();
        }
        
        // Reset filters button
        document.getElementById('resetFilters').addEventListener('click', resetFilters);

        // Global cleanup function for offcanvas issues
        function cleanupOffcanvasBackdrop() {
            // Remove any remaining backdrop elements
            const backdrops = document.querySelectorAll('.offcanvas-backdrop, .modal-backdrop, .fade');
            backdrops.forEach(backdrop => {
                if (backdrop.classList.contains('offcanvas-backdrop') || backdrop.classList.contains('modal-backdrop')) {
                    backdrop.remove();
                }
            });
            
            // Reset body styles
            document.body.classList.remove('offcanvas-open', 'modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }

        // Add global event listener for offcanvas cleanup
        document.addEventListener('click', function(e) {
            // If clicking outside offcanvas or on close button, ensure cleanup
            if (e.target.matches('[data-bs-dismiss="offcanvas"]') || 
                e.target.closest('[data-bs-dismiss="offcanvas"]')) {
                setTimeout(cleanupOffcanvasBackdrop, 300);
            }
        });

        // Cleanup on page focus (in case of any lingering issues)
        window.addEventListener('focus', cleanupOffcanvasBackdrop);
        // Function to assign order to logged-in contractor
        async function assignOrderToMe(orderPanelId, buttonElement) {
            try {
            // First show confirmation dialog
            const confirmResult = await Swal.fire({
                title: 'Confirm Assignment',
                text: 'Are you sure you want to assign this order to yourself?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Assign',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33'
            });

            // If user cancels, exit the function
            if (!confirmResult.isConfirmed) {
                return;
            }

            // Show loading dialog with SweetAlert
            Swal.fire({
                title: 'Assigning Order',
                text: 'Please wait while we assign the order to you...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                Swal.showLoading();
                }
            });

            const response = await fetch(`/contractor/panels/assign/${orderPanelId}`, {
                method: 'POST',
                headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const data = await response.json();

            if (response.ok) {
                // Close loading dialog and show success message
                await Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Order allocated successfully!',
                confirmButtonText: 'OK'
                });
                
                // Update button to show "Allocated" status
                if (buttonElement) {
                    buttonElement.className = 'badge bg-success ms-1';
                    buttonElement.style.fontSize = '10px';
                    buttonElement.textContent = 'Allocated';
                    buttonElement.disabled = true;
                    buttonElement.onclick = null; // Remove click handler
                    // badge-update-text
                    $('.badge-update-text').removeClass('bg-warning text-dark').addClass('bg-success').text('Allocated');
                }
                
                // Refresh the panel orders to show updated status
                const panelId = getCurrentPanelId();
                if (panelId) {
                setTimeout(() => {
                    viewPanelOrders(panelId);
                }, 1000);
                }
            } else {
                // Show error message
                await Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: data.message || 'Failed to assign order',
                confirmButtonText: 'OK'
                });
            }
            } catch (error) {
            console.error('Error assigning order:', error);
            
            // Show error message
            await Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while assigning the order',
                confirmButtonText: 'OK'
            });
            }
        }

        // Function to show notifications
        function showNotification(type, message) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(notification);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification && notification.parentNode) {
                    notification.remove();
                }
            }, 5000);
        }

        // Function to get current panel ID (you might need to adjust this based on your implementation)
        function getCurrentPanelId() {
            // This could be extracted from the current offcanvas or stored globally
            const offcanvasTitle = document.getElementById('order-viewLabel');
            if (offcanvasTitle && offcanvasTitle.textContent) {
                const match = offcanvasTitle.textContent.match(/PNL-(\d+)/);
                return match ? match[1] : null;
            }
            return null;
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Clean up any existing backdrop issues on page load
            cleanupOffcanvasBackdrop();
            
            // Add Load More button event handler
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', loadMorePanels);
            }
            
            // Wait for ApexCharts to be available
            if (typeof ApexCharts === 'undefined') {
                console.error('ApexCharts not loaded, waiting...');
                setTimeout(() => {
                    loadPanels();
                }, 500);
            } else {
                loadPanels();
            }
            
            // Initialize chevron states on page load
            setTimeout(function() {
                initializeChevronStates();
            }, 200);
        });
    </script>
@endpush
