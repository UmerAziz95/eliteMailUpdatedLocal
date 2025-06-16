@extends('contractor.layouts.app')

@section('title', 'Orders')

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

        .empty-state {
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

        /* Domain badge styling */
        .domain-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: 1px solid rgba(102, 126, 234, 0.3);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            margin: 0.125rem;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .domain-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        /* Order card styling */
        .order-card {
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.1);
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
                    <small>Click here to open advance search for orders</small>
                </div>
            </div>
            <div class="row collapse" id="filter_1">
                <form id="filterForm">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Order ID</label>
                            <input type="text" name="order_id" class="form-control" placeholder="Enter order ID">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="unallocated">Unallocated</option>
                                <option value="allocated">Allocated</option>
                                <option value="in-progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Min Inboxes</label>
                            <input type="number" name="min_inboxes" class="form-control" placeholder="e.g. 10">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Max Inboxes</label>
                            <input type="number" name="max_inboxes" class="form-control" placeholder="e.g. 100">
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
        <div id="ordersContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;">
            <!-- Loading state -->
            <div id="loadingState" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Loading orders...</p>
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
                Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalOrders">0</span> orders
            </div>
        </div>

    </section> 
    
    <!-- Order Details Offcanvas -->
    <div class="offcanvas offcanvas-end" style="width: 100%;" tabindex="-1" id="order-splits-view" aria-labelledby="order-splits-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="order-splits-viewLabel">Order Details</h5>
            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <div id="orderSplitsContainer">
                <!-- Dynamic content will be loaded here -->
                <div id="splitsLoadingState" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading order details...</span>
                    </div>
                    <p class="mt-2">Loading order details...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let orders = [];
        let currentFilters = {};
        let currentPage = 1;
        let hasMorePages = false;
        let totalOrders = 0;
        let isLoading = false;

        // Load orders data
        async function loadOrders(filters = {}, page = 1, append = false) {
            try {
                if (isLoading) return; // Prevent concurrent requests
                isLoading = true;
                
                console.log('Loading orders with filters:', filters, 'page:', page, 'append:', append);
                
                if (!append) {
                    showLoading();
                    orders = []; // Reset orders array for new search
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
                    throw new Error(`Failed to fetch orders: ${response.status} ${response.statusText}`);
                }
                  
                const data = await response.json();
                console.log('Received data:', data);
                
                const newOrders = data.data || [];
                console.log('New orders:', newOrders);
                
                if (append) {
                    orders = orders.concat(newOrders);
                } else {
                    orders = newOrders;
                }
                
                // Update pagination state
                const pagination = data.pagination || {};
                currentPage = pagination.current_page || 1;
                hasMorePages = pagination.has_more_pages || false;
                totalOrders = pagination.total || 0;
                
                console.log('Updated state:', { currentPage, hasMorePages, totalOrders, ordersCount: orders.length });
                
                renderOrders(append);
                updatePaginationInfo();
                updateLoadMoreButton();
                
            } catch (error) {
                console.error('Error loading orders:', error);
                if (!append) {
                    showError(`Failed to load orders: ${error.message}`);
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
            const container = document.getElementById('ordersContainer');
            const loadingElement = document.getElementById('loadingState');
            
            if (container && loadingElement) {
                // Keep the grid display but show only loading element
                container.style.display = 'grid';
                container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
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
        }        
        
        // Show error message
        function showError(message) {
            hideLoading();
            const container = document.getElementById('ordersContainer');
            if (!container) {
                console.error('ordersContainer element not found');
                return;
            }
            
            // Keep grid layout but show error spanning full width
            container.style.display = 'grid';
            container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
            container.style.gap = '1rem';
            
            container.innerHTML = `<div class="empty-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                    <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 3rem;"></i>
                    <h5>Error</h5>
                    <p class="mb-3">${message}</p>
                    <button class="btn btn-primary" onclick="loadOrders(currentFilters)">Retry</button>
                </div>
            `;
        }
        
        // Render orders
        function renderOrders(append = false) {
            if (!append) {
                hideLoading();
            }
            
            const container = document.getElementById('ordersContainer');
            if (!container) {
                console.error('ordersContainer element not found');
                return;
            }
              
            if (orders.length === 0 && !append) {
                // Keep grid layout but show empty state spanning full width
                container.style.display = 'grid';
                container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
                container.style.gap = '1rem';
                
                container.innerHTML = `<div class="empty-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                        <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem;"></i>
                        <h5>No Orders Found</h5>
                        <p class="mb-3">No orders match your current filters.</p>
                        <button class="btn btn-outline-primary" onclick="resetFilters()">Clear Filters</button>
                    </div>
                `;
                return;
            }
            
            // Reset container to grid layout for orders
            container.style.display = 'grid';
            container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
            container.style.gap = '1rem';

            if (append) {
                // Only add new orders for pagination
                const currentOrdersCount = container.children.length;
                const newOrders = orders.slice(currentOrdersCount);
                const newOrdersHtml = newOrders.map(order => createOrderCard(order)).join('');
                container.insertAdjacentHTML('beforeend', newOrdersHtml);
            } else {
                // Replace all content for new search
                const ordersHtml = orders.map(order => createOrderCard(order)).join('');
                container.innerHTML = ordersHtml;
            }
        }

        // Create order card HTML
        function createOrderCard(order) {
            return `
                <div class="card p-3 d-flex flex-column gap-3 order-card">                    
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="mb-0">Order #${order.order_id}</h6>
                        <span class="badge ${getStatusBadgeClass(order.status)}">${order.status}</span>
                    </div>
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted d-block">Customer</small>
                            <small class="fw-bold">${order.customer_name}</small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Total Inboxes</small>
                            <small class="fw-bold">${order.total_inboxes}</small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Inboxes/Domain</small>
                            <small class="fw-bold">${order.inboxes_per_domain}</small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Total Domains</small>
                            <small class="fw-bold">${order.total_domains}</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center justify-content-between">
                        <small class="text-muted">${formatDate(order.created_at)}</small>
                        <button class="btn btn-sm btn-primary" onclick="viewOrderSplits(${order.order_id})" data-bs-toggle="offcanvas" data-bs-target="#order-splits-view">
                            View Order (${order.splits_count} splits)
                        </button>
                    </div>
                </div>
            `;
        }

        // Update pagination info display
        function updatePaginationInfo() {
            const showingFromEl = document.getElementById('showingFrom');
            const showingToEl = document.getElementById('showingTo');
            const totalOrdersEl = document.getElementById('totalOrders');
            
            if (showingFromEl && showingToEl && totalOrdersEl) {
                const from = orders.length > 0 ? 1 : 0;
                const to = orders.length;
                
                showingFromEl.textContent = from;
                showingToEl.textContent = to;
                totalOrdersEl.textContent = totalOrders;
            }
        }

        // Update Load More button visibility and state
        function updateLoadMoreButton() {
            const loadMoreContainer = document.getElementById('loadMoreContainer');
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            
            if (loadMoreContainer && loadMoreBtn) {
                if (hasMorePages && orders.length > 0) {
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
        function loadMoreOrders() {
            if (hasMorePages && !isLoading) {
                loadOrders(currentFilters, currentPage + 1, true);
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
        // View order splits
        async function viewOrderSplits(orderId) {
            try {
                // Show loading in offcanvas
                const container = document.getElementById('orderSplitsContainer');
                if (container) {
                    container.innerHTML = `
                        <div id="splitsLoadingState" class="text-center py-5">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading order details...</span>
                            </div>
                            <p class="mt-2">Loading order details...</p>
                        </div>
                    `;
                }
                  
                // Show offcanvas with proper cleanup
                const offcanvasElement = document.getElementById('order-splits-view');
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
                
                // Fetch order splits
                const response = await fetch(`/contractor/orders/${orderId}/splits`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) throw new Error('Failed to fetch order splits');
                
                const data = await response.json();
                renderOrderSplits(data);
                  
            } catch (error) {
                console.error('Error loading order splits:', error);
                const container = document.getElementById('orderSplitsContainer');
                if (container) {
                    container.innerHTML = `
                        <div class="text-center py-5">
                            <i class="fas fa-exclamation-triangle text-danger fs-3 mb-3"></i>
                            <h5>Error Loading Order Details</h5>
                            <p>Failed to load order details. Please try again.</p>
                            <button class="btn btn-primary" onclick="viewOrderSplits(${orderId})">Retry</button>
                        </div>
                    `;
                }
            }
        }
        
        // Render order splits in offcanvas
        function renderOrderSplits(data) {
            const container = document.getElementById('orderSplitsContainer');
            
            if (!data.splits || data.splits.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-inbox text-muted fs-3 mb-3"></i>
                        <h5>No Splits Found</h5>
                        <p>This order doesn't have any splits yet.</p>
                    </div>
                `;
                return;
            }

            const orderInfo = data.order;
            const reorderInfo = data.reorder_info;
            const splits = data.splits;

            const splitsHtml = `
                <div class="mb-4">
                    <h6>Order #${orderInfo.id}</h6>
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Customer</small>
                            <small class="fw-bold">${orderInfo.customer_name}</small>
                        </div>
                        ${reorderInfo ? `
                        <div class="col-md-3">
                            <small class="text-muted d-block">Total Inboxes</small>
                            <small class="fw-bold">${reorderInfo.total_inboxes || 'N/A'}</small>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Inboxes per Domain</small>
                            <small class="fw-bold">${reorderInfo.inboxes_per_domain || 'N/A'}</small>
                        </div>
                        ` : ''}
                        <div class="col-md-3">
                            <small class="text-muted d-block">Order Date</small>
                            <small class="fw-bold">${formatDate(orderInfo.created_at)}</small>
                        </div>
                    </div>
                </div>

                <h6 class="mb-3">Order Splits (${splits.length})</h6>
                <div class="row g-3">
                    ${splits.map((split, index) => `
                        <div class="col-md-6">
                            <div class="card p-3 h-100">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0">Split ${index + 1}</h6>
                                    <span class="badge ${getStatusBadgeClass(split.status)}">${split.status}</span>
                                </div>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Panel</small>
                                        <small class="fw-bold">PNL-${split.panel_id}</small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Total Inboxes</small>
                                        <small class="fw-bold">${split.total_inboxes}</small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Inboxes/Domain</small>
                                        <small class="fw-bold">${split.inboxes_per_domain}</small>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Domains Count</small>
                                        <small class="fw-bold">${split.domains_count}</small>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-2">Domains:</small>
                                    <div class="domains-container" style="max-height: 200px; overflow-y: auto;">
                                        ${split.domains && split.domains.length > 0 ? 
                                            split.domains.map(domain => `
                                                <span class="domain-badge" onclick="copyToClipboard('${domain}')" title="Click to copy">
                                                    <i class="fas fa-globe me-1"></i>${domain}
                                                </span>
                                            `).join('')
                                            : '<small class="text-muted">No domains</small>'
                                        }
                                    </div>
                                </div>
                                ${split.domains && split.domains.length > 0 ? `
                                <button class="btn btn-sm btn-outline-primary" onclick="copyAllDomains(${JSON.stringify(split.domains).replace(/"/g, '&quot;')}, 'Split ${index + 1}')">
                                    <i class="fas fa-copy me-1"></i>Copy All Domains (${split.domains.length})
                                </button>
                                ` : ''}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
            
            container.innerHTML = splitsHtml;
        }
        
        // Function to copy domain to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show a temporary success message
                showToast('Domain copied to clipboard!', 'success');
            }).catch(() => {
                console.warn('Failed to copy to clipboard');
                showToast('Failed to copy domain', 'error');
            });
        }

        // Function to copy all domains from a split to clipboard
        function copyAllDomains(domains, splitName) {
            if (!domains || domains.length === 0) {
                showToast('No domains to copy', 'error');
                return;
            }
            
            // Join domains with newlines for easy copying
            const domainsText = domains.join('\n');
            
            navigator.clipboard.writeText(domainsText).then(() => {
                showToast(`All domains from ${splitName} copied to clipboard!`, 'success');
            }).catch(() => {
                showToast('Failed to copy domains', 'error');
            });
        }

        // Function to show toast notifications
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            // Auto remove after 3 seconds
            setTimeout(() => {
                if (toast && toast.parentNode) {
                    toast.remove();
                }
            }, 3000);
        }
        // Function to copy domain to clipboard
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                // Show a temporary success message
                showToast('Domain copied to clipboard!', 'success');
            }).catch(() => {
                console.warn('Failed to copy to clipboard');
                showToast('Failed to copy domain', 'error');
            });
        }

        // Function to copy all domains from a split to clipboard
        function copyAllDomains(domains, splitName) {
            if (!domains || domains.length === 0) {
                showToast('No domains to copy', 'error');
                return;
            }
            
            // Join domains with newlines for easy copying
            const domainsText = domains.join('\n');
            
            navigator.clipboard.writeText(domainsText).then(() => {
                showToast(`All domains from ${splitName} copied to clipboard!`, 'success');
            }).catch(() => {
                showToast('Failed to copy domains', 'error');
            });
        }

        // Function to show toast notifications
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;

            document.body.appendChild(toast);

            // Auto remove after 3 seconds
            setTimeout(() => {
                if (toast && toast.parentNode) {
                    toast.remove();
                }
            }, 3000);
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
            totalOrders = 0;
            loadOrders(filters);
        });
          
        // Reset filters
        function resetFilters() {
            document.getElementById('filterForm').reset();
            currentFilters = {};
            currentPage = 1;
            hasMorePages = false;
            totalOrders = 0;
            loadOrders();
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

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Clean up any existing backdrop issues on page load
            cleanupOffcanvasBackdrop();
            
            // Add Load More button event handler
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', loadMoreOrders);
            }
            
            // Load orders immediately
            loadOrders();
        });
    </script>
@endpush
