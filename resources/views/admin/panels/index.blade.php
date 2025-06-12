@extends('admin.layouts.app')

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
            <!-- <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button> -->
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
                const url = `/admin/panels/data?${params}`;
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
                        </button>                    </div>
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
                const response = await fetch(`/admin/panels/${panelId}/orders`, {
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
                                    data-bs-toggle="collapse" data-bs-target="#order-collapse-${order.order_id}" aria-expanded="false"
                                    aria-controls="order-collapse-${order.order_id}">
                                    <small>ID: #${order.order_id || 0 }</small>
                                    <small>Inboxes: ${order.space_assigned || order.inboxes_per_domain || 0}</small>
                                    <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary" href="javascript:;">
                                        View
                                    </button>
                                </div>
                            </h2>
                            <div id="order-collapse-${order.order_id}" class="accordion-collapse collapse" data-bs-parent="#panelOrdersAccordion">
                                <div class="accordion-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th scope="col">#</th>
                                                    <th scope="col">Order ID</th>
                                                    <th scope="col">Status</th>
                                                    <th scope="col">Space Assigned</th>
                                                    <th scope="col">Inboxes/Domain</th>
                                                    <th scope="col">Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <th scope="row">${index + 1}</th>
                                                    <td>${order.order_id || 0}</td>
                                                    <td>
                                                        <span class="badge ${getStatusBadgeClass(order.status)}">${order.status || 'Unknown'}</span>
                                                    </td>
                                                    <td>${order.space_assigned || 'N/A'}</td>
                                                    <td>${order.inboxes_per_domain || 'N/A'}</td>
                                                    <td>${formatDate(order.created_at)}</td>
                                                </tr>
                                                ${order.splits && order.splits.length > 0 ? order.splits.map((split, splitIndex) => ``).join('') : ''}
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
                                                        const domainsCount = order.splits.reduce((total, split) => total + (split.domains ? split.domains.length : 0), 0);  
                                                        const inboxesPerDomain = order.reorder_info?.inboxes_per_domain || 0;
                                                        const totalInboxes = domainsCount * inboxesPerDomain;
                                                        return `(${domainsCount} domains × ${inboxesPerDomain} inboxes) = ${totalInboxes}`;
                                                    })() : 'N/A'}</span>
                                                    <span>Inboxes per domain <br> ${order.reorder_info?.inboxes_per_domain || 'N/A'}</span>
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
                                                    <span class="opacity-50">Domains</span>
                                                    ${renderDomains(order.splits)}
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
            if (!splits || splits.length === 0) {
                return '<span>N/A</span>';
            }
            
            let allDomains = [];
            
            splits.forEach(split => {
                if (split.domains && Array.isArray(split.domains)) {
                    allDomains = allDomains.concat(split.domains);
                }
            });
            
            if (allDomains.length === 0) {
                return '<span>N/A</span>';
            }
            
            return allDomains.map(domain => `<span class="d-block">${domain}</span>`).join('');
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
        window.addEventListener('focus', cleanupOffcanvasBackdrop);        // Initialize page
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
        });
    </script>
@endpush
