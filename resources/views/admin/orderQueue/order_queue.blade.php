@extends('admin.layouts.app')

@section('title', 'Orders-Queue')

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

        .table>:not(caption)>*>* {
            border-bottom-width: 0 !important
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

        .flip-card {
            position: relative;
            width: 15px;
            height: 15px;
            perspective: 1000px;
            font-family: "Space Grotesk";
        }

        .flip-inner {
            position: absolute;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            transition: transform 0.6s ease-in-out;
        }

        .flip-front,
        .flip-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            background: linear-gradient(to bottom, #eee 50%, #ccc 50%);
            border-radius: 2px;
            font-size: 12px;
            font-weight: bold;
            color: #222;
            display: flex;
            align-items: center;
            justify-content: center;
            /* border: 1px solid #aaa; */
        }

        .flip-front {
            z-index: 2;
        }

        .flip-back {
            transform: rotateX(180deg);
        }

        /* Flip timer container styles */
        .flip-timer {
            display: inline-flex;
            align-items: center;
            gap: 2px;
            font-family: "Space Grotesk", "Courier New", monospace;
            font-size: 12px;
            padding: 4px 8px;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(2px);
        }

        .flip-timer.positive {
            background: transparent;
            color: #28a745;
        }

        .flip-timer.positive .flip-front,
        .flip-timer.positive .flip-back {
            color: #155724;
            border-color: rgba(40, 167, 69, 0.2);
        }

        .flip-timer.negative {
            background: transparent;
            color: #dc3545;
        }

        .flip-timer.negative .flip-front,
        .flip-timer.negative .flip-back {
            color: #dc3545;
            background-color: rgba(255, 0, 0, 0.16);
            border-color: rgb(220, 53, 70);
        }

        .flip-timer.completed {
            background: rgba(108, 117, 125, 0.1);
            border-color: rgba(108, 117, 125, 0.3);
            color: #6c757d;
        }

        .flip-timer.completed .flip-front,
        .flip-timer.completed .flip-back {
            background: linear-gradient(to bottom, #e2e6ea 50%, #dae0e5 50%);
            color: #495057;
            border-color: rgba(108, 117, 125, 0.2);
        }

        /* Timer separator styling */
        .timer-separator {
            font-weight: bold;
            margin: 0 2px;
            opacity: 0.7;
            font-size: 11px;
        }

        /* Timer icon styling */
        .timer-icon {
            font-size: 11px;
            margin-right: 4px;
        }

        .card-draft {
            background-color: rgba(0, 225, 255, 0.037);
        }

        .domain-split-container {
            transition: all 0.3s ease;
        }

        .split-header {
            transition: all 0.2s ease;
        }

        .split-header:hover {
            /* background-color: var(--second-primary) !important; */
        }

        .split-content {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
        }

        .split-content.show {
            display: block !important;
        }

        .transition-transform {
            transition: transform 0.3s ease;
        }

        .domain-badge:hover {
            background-color: var(--second-primary) !important;
            transform: scale(1.05);
        }

        /* Collapse animation styles */
        .collapse {
            transition: height 0.35s ease, opacity 0.35s ease;
        }

        .collapse:not(.show) {
            height: 0 !important;
            opacity: 0;
        }

        .collapse.show {
            height: auto !important;
            opacity: 1;
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
                            <button type="button" id="resetFilters"
                                class="btn btn-outline-secondary btn-sm me-2 px-3">Reset</button>
                            <button type="submit" id="submitBtn"
                                class="btn btn-primary btn-sm border-0 px-3">Search</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <ul class="nav nav-pills mb-3 border-0" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link py-1 text-capitalize text-white active" id="in-queue-tab" data-bs-toggle="tab"
                    data-bs-target="#in-queue-tab-pane" type="button" role="tab" aria-controls="in-queue-tab-pane"
                    aria-selected="true">in-queue</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link py-1 text-capitalize text-white" id="in-draft-tab" data-bs-toggle="tab"
                    data-bs-target="#in-draft-tab-pane" type="button" role="tab" aria-controls="in-draft-tab-pane"
                    aria-selected="false">in-draft</button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active" id="in-queue-tab-pane" role="tabpanel" aria-labelledby="in-queue-tab"
                tabindex="0">
                <div id="ordersContainer" class="mb-4"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
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
                        <span id="loadMoreSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
                            style="display: none;">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </button>
                    <div id="paginationInfo" class="mt-2 text-light small">
                        Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span
                            id="totalOrders">0</span> orders
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="in-draft-tab-pane" role="tabpanel" aria-labelledby="in-draft-tab"
                tabindex="0">
                <div id="draftsContainer" class="mb-4"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    <!-- Loading state -->
                    <div id="draftsLoadingState" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 mb-0">Loading draft orders...</p>
                    </div>
                </div>
                <!-- Load More Button for Drafts -->
                <div id="loadMoreDraftsContainer" class="text-center mt-4" style="display: none;">
                    <button id="loadMoreDraftsBtn" class="btn btn-lg btn-primary px-4 me-2 border-0 animate-gradient">
                        <span id="loadMoreDraftsText">Load More</span>
                        <span id="loadMoreDraftsSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
                            style="display: none;">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </button>
                    <div id="paginationDraftsInfo" class="mt-2 text-light small">
                        Showing <span id="showingDraftsFrom">0</span> to <span id="showingDraftsTo">0</span> of <span
                            id="totalDrafts">0</span> orders
                    </div>
                </div>
            </div>
        </div>





    </section>
    <!-- Order Details Offcanvas -->
    <div class="offcanvas offcanvas-end" style="width: 100%;" tabindex="-1" id="order-splits-view"
        aria-labelledby="order-splits-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
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
        let drafts = [];
        let currentFilters = {};
        let currentPage = 1;
        let currentDraftsPage = 1;
        let hasMorePages = false;
        let hasMoreDraftsPages = false;
        let totalOrders = 0;
        let totalDrafts = 0;
        let isLoading = false;
        let isDraftsLoading = false;
        let activeTab = 'in-queue';

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Load initial orders
            loadOrders({}, 1, false, 'in-queue');
            
            // Tab change handlers
            document.getElementById('in-queue-tab').addEventListener('click', function() {
                activeTab = 'in-queue';
                if (orders.length === 0) {
                    loadOrders(currentFilters, 1, false, 'in-queue');
                }
            });
            
            document.getElementById('in-draft-tab').addEventListener('click', function() {
                activeTab = 'in-draft';
                if (drafts.length === 0) {
                    loadOrders(currentFilters, 1, false, 'in-draft');
                }
            });

            // Filter form handler
            document.getElementById('filterForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const filters = Object.fromEntries(formData);
                
                // Remove empty filters
                Object.keys(filters).forEach(key => {
                    if (!filters[key]) delete filters[key];
                });
                
                currentFilters = filters;
                document.getElementById('submitBtn').disabled = true;
                
                if (activeTab === 'in-queue') {
                    loadOrders(filters, 1, false, 'in-queue');
                } else {
                    loadOrders(filters, 1, false, 'in-draft');
                }
            });

            // Reset filters handler
            document.getElementById('resetFilters').addEventListener('click', function() {
                document.getElementById('filterForm').reset();
                currentFilters = {};
                if (activeTab === 'in-queue') {
                    loadOrders({}, 1, false, 'in-queue');
                } else {
                    loadOrders({}, 1, false, 'in-draft');
                }
            });

            // Load more handlers
            document.getElementById('loadMoreBtn').addEventListener('click', function() {
                if (hasMorePages && !isLoading) {
                    loadOrders(currentFilters, currentPage + 1, true, 'in-queue');
                }
            });

            document.getElementById('loadMoreDraftsBtn').addEventListener('click', function() {
                if (hasMoreDraftsPages && !isDraftsLoading) {
                    loadOrders(currentFilters, currentDraftsPage + 1, true, 'in-draft');
                }
            });
        });

        // Load orders data
        async function loadOrders(filters = {}, page = 1, append = false, type = 'in-queue') {
            const isLoadingDrafts = type === 'in-draft';
            
            try {
                if (isLoadingDrafts ? isDraftsLoading : isLoading) return;
                
                if (isLoadingDrafts) {
                    isDraftsLoading = true;
                } else {
                    isLoading = true;
                }
                
                if (!append) {
                    if (isLoadingDrafts) {
                        showDraftsLoading();
                        drafts = [];
                    } else {
                        showLoading();
                        orders = [];
                    }
                }
                
                if (append) {
                    showLoadMoreSpinner(true, isLoadingDrafts);
                }
                
                const params = new URLSearchParams({
                    ...filters,
                    type: type,
                    page: page,
                    per_page: 12
                });
                
                const response = await fetch(`/admin/order_queue/data?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`Failed to fetch orders: ${response.status}`);
                }
                
                const data = await response.json();
                const newOrders = data.data || [];
                
                if (isLoadingDrafts) {
                    if (append) {
                        drafts = drafts.concat(newOrders);
                    } else {
                        drafts = newOrders;
                    }
                    
                    const pagination = data.pagination || {};
                    currentDraftsPage = pagination.current_page || 1;
                    hasMoreDraftsPages = pagination.has_more_pages || false;
                    totalDrafts = pagination.total || 0;
                    
                    renderOrders(append, true);
                    updatePaginationInfo(true);
                    updateLoadMoreButton(true);
                } else {
                    if (append) {
                        orders = orders.concat(newOrders);
                    } else {
                        orders = newOrders;
                    }
                    
                    const pagination = data.pagination || {};
                    currentPage = pagination.current_page || 1;
                    hasMorePages = pagination.has_more_pages || false;
                    totalOrders = pagination.total || 0;
                    
                    renderOrders(append, false);
                    updatePaginationInfo(false);
                    updateLoadMoreButton(false);
                }
                
            } catch (error) {
                console.error('Error loading orders:', error);
                if (!append) {
                    showError(error.message, isLoadingDrafts);
                }
            } finally {
                if (isLoadingDrafts) {
                    isDraftsLoading = false;
                } else {
                    isLoading = false;
                }
                
                if (append) {
                    showLoadMoreSpinner(false, isLoadingDrafts);
                }
                
                document.getElementById('submitBtn').disabled = false;
            }
        }

        // Show loading state
        function showLoading() {
            const container = document.getElementById('ordersContainer');
            const loadingElement = document.getElementById('loadingState');
            
            if (container && loadingElement) {
                container.style.display = 'grid';
                container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
                container.style.gap = '30px';
                container.innerHTML = '';
                container.appendChild(loadingElement);
                loadingElement.style.display = 'flex';
            }
        }

        // Show drafts loading state
        function showDraftsLoading() {
            const container = document.getElementById('draftsContainer');
            const loadingElement = document.getElementById('draftsLoadingState');
            
            if (container && loadingElement) {
                container.style.display = 'grid';
                container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
                container.style.gap = '30px';
                container.innerHTML = '';
                container.appendChild(loadingElement);
                loadingElement.style.display = 'flex';
            }
        }

        // Hide loading state
        function hideLoading(isDrafts = false) {
            const loadingElement = document.getElementById(isDrafts ? 'draftsLoadingState' : 'loadingState');
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
        }

        // Show error message
        function showError(message, isDrafts = false) {
            hideLoading(isDrafts);
            const container = document.getElementById(isDrafts ? 'draftsContainer' : 'ordersContainer');
            if (!container) return;
            
            container.style.display = 'grid';
            container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
            container.style.gap = '30px';
            
            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                    <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 3rem;"></i>
                    <h5>Error</h5>
                    <p class="mb-3">${message}</p>
                    <button class="btn btn-primary" onclick="loadOrders(currentFilters, 1, false, '${isDrafts ? 'in-draft' : 'in-queue'}')">Retry</button>
                </div>
            `;
        }

        // Render orders
        function renderOrders(append = false, isDrafts = false) {
            const container = document.getElementById(isDrafts ? 'draftsContainer' : 'ordersContainer');
            const ordersList = isDrafts ? drafts : orders;
            
            if (!append) {
                hideLoading(isDrafts);
            }
            
            if (!container) return;
            
            if (ordersList.length === 0 && !append) {
                container.innerHTML = `
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-inbox"></i>
                        <h5>No ${isDrafts ? 'Draft ' : ''}Orders Found</h5>
                        <p>There are no ${isDrafts ? 'draft ' : ''}orders to display.</p>
                    </div>
                `;
                return;
            }
            
            if (!append) {
                container.innerHTML = '';
                container.style.display = 'grid';
                container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
                container.style.gap = '30px';
            }
            
            const startIndex = append ? (isDrafts ? drafts.length - (drafts.length - ordersList.length) : orders.length - (orders.length - ordersList.length)) : 0;
            const ordersToRender = append ? ordersList.slice(startIndex) : ordersList;
            
            ordersToRender.forEach((order, index) => {
                const orderCard = createOrderCard(order, isDrafts, startIndex + index);
                container.appendChild(orderCard);
            });
            
            // Start timers for rendered orders
            setTimeout(() => {
                startTimersForOrders(ordersToRender, isDrafts, startIndex);
            }, 100);
        }

        // Create order card
        function createOrderCard(order, isDrafts, index) {
            const statusConfig = getStatusConfig(order.status);
            const borderColor = isDrafts ? 'rgb(0, 221, 255)' : statusConfig.borderColor;
            const statusClass = isDrafts ? 'text-info' : statusConfig.statusClass;
            const statusIcon = isDrafts ? 'fa-solid fa-file-lines' : statusConfig.statusIcon;
            const statusText = isDrafts ? 'Draft' : (order.status || 'Pending');
            const lineColor = isDrafts ? 'rgb(0, 242, 255)' : statusConfig.lineColor;
            
            const cardElement = document.createElement('div');
            cardElement.className = 'card p-3 overflow-hidden';
            cardElement.style.borderBottom = `4px solid ${borderColor}`;
            
            const customerImage = order.customer_image 
                ? order.customer_image 
                : 'https://images.pexels.com/photos/415829/pexels-photo-415829.jpeg';
            
            cardElement.innerHTML = `
                <div style="position: relative; z-index: 9;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <h6 class="mb-0">#${order.order_id}</h6>
                            <span class="${statusClass} small">
                                <i class="${statusIcon}"></i>
                                ${statusText}
                            </span>
                        </div>
                        ${createTimerBadge(order, isDrafts, index)}
                    </div>

                    <div class="d-flex flex-column gap-0">
                        <h6 class="mb-0">
                            Total Inboxes : <span class="text-white number">${order.total_inboxes || 0}</span>
                        </h6>
                        <small>Splits : <span class="text-white number">${order.splits_count || 0}</span></small>
                    </div>

                    <div class="my-4">
                        <div class="content-line d-flex align-items-center justify-content-between">
                            <div class="d-flex flex-column">
                                <small>Inboxes/Domain</small>
                                <small class="small">${order.inboxes_per_domain || 0}</small>
                            </div>
                            <div class="d-flex flex-column align-items-end">
                                <small>Total Domains</small>
                                <small class="small">${order.total_domains || 0}</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mt-1">
                            <span style="height: 11px; width: 11px; border-radius: 50px; border: 3px solid #fff; background-color: var(--second-primary)"></span>
                            <span style="height: 1px; width: 100%; background-color: ${lineColor};"></span>
                            <span style="height: 11px; width: 11px; border-radius: 50px; border: 3px solid #fff; background-color: var(--second-primary)"></span>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-1">
                            <img src="${customerImage}" width="40" height="40" class="object-fit-cover" style="border-radius: 50px" alt="" onerror="this.src='https://images.pexels.com/photos/415829/pexels-photo-415829.jpeg'">
                            <div class="d-flex flex-column gap-0">
                                <h6 class="mb-0">${order.customer_name}</h6>
                                <small>${formatDate(order.created_at)}</small>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-center" 
                             style="height: 30px; width: 30px; border-radius: 50px; background-color: var(--second-primary); cursor: pointer;"
                             onclick="viewOrderSplits(${order.order_id})" 
                             data-bs-toggle="offcanvas" 
                             data-bs-target="#order-splits-view">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                    </div>
                </div>
            `;
            
            return cardElement;
        }

        // Get status configuration
        function getStatusConfig(status) {
            const configs = {
                'pending': {
                    borderColor: 'orange',
                    statusClass: 'text-warning',
                    statusIcon: 'fa-solid fa-spinner',
                    lineColor: 'orange'
                },
                'in-progress': {
                    borderColor: '#007bff',
                    statusClass: 'text-primary',
                    statusIcon: 'fa-solid fa-cog fa-spin',
                    lineColor: '#007bff'
                },
                'completed': {
                    borderColor: '#28a745',
                    statusClass: 'text-success',
                    statusIcon: 'fa-solid fa-check',
                    lineColor: '#28a745'
                },
                'cancelled': {
                    borderColor: '#dc3545',
                    statusClass: 'text-danger',
                    statusIcon: 'fa-solid fa-times',
                    lineColor: '#dc3545'
                },
                'expired': {
                    borderColor: '#6c757d',
                    statusClass: 'text-secondary',
                    statusIcon: 'fa-solid fa-clock',
                    lineColor: '#6c757d'
                }
            };
            
            return configs[status?.toLowerCase()] || configs['pending'];
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: '2-digit',
                day: '2-digit',
                year: 'numeric'
            });
        }

        // Update pagination info
        function updatePaginationInfo(isDrafts = false) {
            if (isDrafts) {
                const showingFrom = document.getElementById('showingDraftsFrom');
                const showingTo = document.getElementById('showingDraftsTo');
                const totalOrdersEl = document.getElementById('totalDrafts');
                
                if (showingFrom && showingTo && totalOrdersEl) {
                    showingFrom.textContent = drafts.length > 0 ? 1 : 0;
                    showingTo.textContent = drafts.length;
                    totalOrdersEl.textContent = totalDrafts;
                }
            } else {
                const showingFrom = document.getElementById('showingFrom');
                const showingTo = document.getElementById('showingTo');
                const totalOrdersEl = document.getElementById('totalOrders');
                
                if (showingFrom && showingTo && totalOrdersEl) {
                    showingFrom.textContent = orders.length > 0 ? 1 : 0;
                    showingTo.textContent = orders.length;
                    totalOrdersEl.textContent = totalOrders;
                }
            }
        }

        // Update load more button
        function updateLoadMoreButton(isDrafts = false) {
            if (isDrafts) {
                const container = document.getElementById('loadMoreDraftsContainer');
                if (container) {
                    container.style.display = hasMoreDraftsPages ? 'block' : 'none';
                }
            } else {
                const container = document.getElementById('loadMoreContainer');
                if (container) {
                    container.style.display = hasMorePages ? 'block' : 'none';
                }
            }
        }

        // Show load more spinner
        function showLoadMoreSpinner(show, isDrafts = false) {
            if (isDrafts) {
                const button = document.getElementById('loadMoreDraftsBtn');
                const text = document.getElementById('loadMoreDraftsText');
                const spinner = document.getElementById('loadMoreDraftsSpinner');
                
                if (button && text && spinner) {
                    button.disabled = show;
                    text.textContent = show ? 'Loading...' : 'Load More';
                    spinner.style.display = show ? 'inline-block' : 'none';
                }
            } else {
                const button = document.getElementById('loadMoreBtn');
                const text = document.getElementById('loadMoreText');
                const spinner = document.getElementById('loadMoreSpinner');
                
                if (button && text && spinner) {
                    button.disabled = show;
                    text.textContent = show ? 'Loading...' : 'Load More';
                    spinner.style.display = show ? 'inline-block' : 'none';
                }
            }
        }

        // View order splits
        async function viewOrderSplits(orderId) {
            try {
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

                const response = await fetch(`/admin/order_queue/${orderId}/splits`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch order splits');
                }

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
                        <i class="fas fa-inbox fs-3 mb-3"></i>
                        <h5>No Splits Found</h5>
                        <p>This order doesn't have any splits yet.</p>
                    </div>
                `;
                return;
            }

            const orderInfo = data.order;
            const reorderInfo = data.reorder_info;
            const splits = data.splits;
            
            // Update offcanvas title with timer
            const offcanvasTitle = document.getElementById('order-splits-viewLabel');
            if (offcanvasTitle && orderInfo) {
                offcanvasTitle.innerHTML = `
                    Order Details #${orderInfo.id} 
                `;
            }

            const splitsHtml = `
                <div class="mb-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6>
                                <span class="badge border ${getOrderStatusBadgeClass(orderInfo.status_manage_by_admin)} me-2">${orderInfo.status_manage_by_admin}</span>
                                ${createTimerBadge(orderInfo, false, 0)}
                            </h6>
                            <p class="small mb-0">Customer: ${orderInfo.customer_name} | Date: ${formatDate(orderInfo.created_at)}</p>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mb-4">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Split ID</th>
                                <th scope="col">Panel Id</th>
                                <th scope="col">Panel Title</th>
                                <th scope="col">Split Status</th>
                                <th scope="col">Inboxes/Domain</th>
                                <th scope="col">Total Domains</th>
                                <th scope="col">Total Inboxes</th>
                                <th scope="col">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${splits.map((split, index) => `
                                <tr>
                                    <th scope="row">${index + 1}</th>
                                    <td>
                                        <span class="badge bg-primary" style="font-size: 10px;">
                                            SPL-${split.id || 'N/A'}
                                        </span>
                                    </td>
                                    <td>${split?.panel_id || 'N/A'}</td>
                                    <td>${split?.panel_title || 'N/A'}</td>
                                    <td>
                                        <span class="text-dark px-2 py-1 rounded-1 badge ${getStatusBadgeClass(split.status)}">${split.status || 'Unknown'}</span>
                                    </td>
                                    <td>${split.inboxes_per_domain || 'N/A'}</td>
                                    <td>
                                        <span class="px-2 py-1 rounded-1 bg-success text-white" style="font-size: 10px;">
                                            ${split.domains_count || 0} domain(s)
                                        </span>
                                    </td>
                                    <td>${split.total_inboxes || 'N/A'}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="/admin/orders/${split.order_panel_id}/split/view" style="font-size: 11px" class="me-2 btn btn-sm btn-outline-primary" title="View Split" target="_blank">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="/admin/orders/split/${split.id}/export-csv-domains" style="font-size: 11px" class="me-2 btn btn-sm btn-success" title="Download CSV with ${split.domains_count || 0} domains" target="_blank">
                                                <i class="fas fa-download"></i> CSV
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>

                <div class="row">
                    <div class="col-md-5">
                        <div class="card p-3 mb-3">
                            <h6 class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 30px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                    <i class="fa-regular fa-envelope"></i>
                                </div>
                                Email configurations
                            </h6>

                            <div class="d-flex align-items-center justify-content-between">
                                <span>${(() => {
                                    const totalInboxes = splits.reduce((total, split) => total + (split.total_inboxes || 0), 0);
                                    const totalDomains = splits.reduce((total, split) => total + (split.domains_count || 0), 0);
                                    const inboxesPerDomain = reorderInfo?.inboxes_per_domain || 0;
                                    
                                    let splitDetails = '';
                                    splits.forEach((split, index) => {
                                        splitDetails += `<br><span class="badge bg-white text-dark me-1" style="font-size: 10px; font-weight: bold;">Split ${String(index + 1).padStart(2, '0')}</span> Inboxes: ${split.total_inboxes || 0} (${split.domains_count || 0} domains × ${inboxesPerDomain})<br>`;
                                    });
                                    
                                    return `<strong>Total Inboxes: ${totalInboxes} (${totalDomains} domains)</strong><br>${splitDetails}`;
                                })()}</span>
                            </div>
                             
                            <hr>
                            <div class="d-flex flex-column">
                                <span class="opacity-50 small">Prefix Variants</span>
                                <small>${renderPrefixVariants(reorderInfo)}</small>
                            </div>
                            <div class="d-flex flex-column mt-3">
                                <span class="opacity-50 small">Profile Picture URL</span>
                                <small>${renderProfileLinksFromObject(reorderInfo?.data_obj?.prefix_variants_details)}</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card p-3 overflow-y-auto" style="max-height: 50rem">
                            <h6 class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 30px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                    <i class="fa-solid fa-earth-europe"></i>
                                </div>
                                Domains &amp; Configuration
                            </h6>

                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50 small">Hosting Platform</span>
                                <small>${reorderInfo?.hosting_platform || 'N/A'}</small>
                            </div>

                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50 small">Platform Login</span>
                                <small>${reorderInfo?.platform_login || 'N/A'}</small>
                            </div>

                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50 small">Platform Password</span>
                                <small>${reorderInfo?.platform_password || 'N/A'}</small>
                            </div>

                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50 small">Domain Forwarding Destination URL</span>
                                <small>${reorderInfo?.forwarding_url || 'N/A'}</small>
                            </div>

                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50 small">Sending Platform</span>
                                <small>${reorderInfo?.sending_platform || 'N/A'}</small>
                            </div>

                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50 small">Cold email platform - Login</span>
                                <small>${reorderInfo?.sequencer_login || 'N/A'}</small>
                            </div>

                            <div class="d-flex flex-column mb-3">
                                <span class="opacity-50 small">Cold email platform - Password</span>
                                <small>${reorderInfo?.sequencer_password || 'N/A'}</small>
                            </div>

                            <div class="d-flex flex-column">
                                <span class="opacity-50 small mb-3">
                                    <i class="fa-solid fa-globe me-2"></i>All Domains & Splits
                                </span>
                                
                                <!-- Order Splits Domains -->
                                ${splits.map((split, index) => `
                                    <div class="domain-split-container mb-3">
                                        <div class="split-header d-flex align-items-center justify-content-between p-2 rounded-top" 
                                             style="background-color: var(--filter-color); cursor: pointer;"
                                             onclick="toggleSplit('split-${orderInfo.id}-${index}')">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-white text-dark me-2" style="font-size: 10px; font-weight: bold;">
                                                    Split ${String(index + 1).padStart(2, '0')}
                                                </span>
                                                <small class="fw-bold">PNL-${split.panel_id} Domains</small>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-white bg-opacity-25 me-2" style="font-size: 9px;">
                                                    ${split.domains_count || 0} domains
                                                </span>
                                                <i class="fa-solid fa-copy me-2" style="font-size: 10px; cursor: pointer; opacity: 0.8;" 
                                                   title="Copy all domains from Split ${String(index + 1).padStart(2, '0')}" 
                                                   onclick="event.stopPropagation(); copyAllDomainsFromSplit('split-${orderInfo.id}-${index}', 'Split ${String(index + 1).padStart(2, '0')}')"></i>
                                                <i class="fa-solid fa-chevron-right transition-transform" id="icon-split-${orderInfo.id}-${index}"></i>
                                            </div>
                                        </div>

                                        <div class="split-content collapse" id="split-${orderInfo.id}-${index}">
                                            <div class="p-3" style="background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.2); border-top: none; border-radius: 0 0 8px 8px;">
                                                <div class="domains-grid">
                                                    ${renderDomainsWithStyle([split])}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>

                            <div class="d-flex flex-column mt-3">
                                <span class="opacity-50">Backup Codes</span>
                                <span>${reorderInfo?.data_obj?.backup_codes || 'N/A'}</span>
                            </div>
                            <div class="d-flex flex-column mt-3">
                                <span class="opacity-50">Additional Notes</span>
                                <span>${reorderInfo?.data_obj?.additional_info || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.innerHTML = splitsHtml;
            
            // Start timer for the order in the offcanvas and initialize other features
            setTimeout(function() {
                // Start timer for the order displayed in the offcanvas
                const timerId = `flip-timer-${orderInfo.order_id}-0`;
                const timerElement = document.getElementById(timerId);
                if (timerElement && orderInfo.status !== 'completed' && orderInfo.status !== 'cancelled') {
                    // Use the same timer starting logic as in the main cards
                    startSingleTimer(orderInfo, false, 0);
                }
                
                // Initialize chevron states and animations after rendering
                initializeChevronStates();
            }, 100);
        }

        // Get main order status badge class  
        function getOrderStatusBadgeClass(status) {
            
            const statusClasses = {
                'completed': 'bg-success text-white',
                'pending': 'bg-warning border-warning bg-transparent text-warning',
                'in-progress': 'bg-primary text-white border-primary text-white',
                'draft': 'bg-secondary text-white border-secondary text-white',
                'rejected': 'bg-danger text-white border-danger text-white',
                'expired': 'bg-dark text-white border-dark text-white',
                'cancelled': 'bg-danger text-white border-danger text-white'
            };
            return statusClasses[status?.toLowerCase()] || 'bg-secondary text-white';
        }

        // Get status badge class
        function getStatusBadgeClass(status) {
            const statusClasses = {
                'completed': 'bg-success',
                'in-progress': 'bg-primary',
                'unallocated': 'bg-warning',
                'allocated': 'bg-info',
                'rejected': 'bg-danger'
            };
            return statusClasses[status?.toLowerCase()] || 'bg-secondary';
        }

        // Timer functions
        function createFlipCard(initial) {
            const card = document.createElement('div');
            card.className = 'flip-card';
            card.innerHTML = `
                <div class="flip-inner">
                    <div class="flip-front">${initial}</div>
                    <div class="flip-back">${initial}</div>
                </div>
            `;
            return card;
        }

        function updateFlipCard(card, newVal) {
            const inner = card.querySelector('.flip-inner');
            const front = card.querySelector('.flip-front');
            const back = card.querySelector('.flip-back');

            if (front.textContent === newVal) return;

            back.textContent = newVal;
            inner.style.transform = 'rotateX(180deg)';

            setTimeout(() => {
                front.textContent = newVal;
                inner.style.transition = 'none';
                inner.style.transform = 'rotateX(0deg)';
                setTimeout(() => {
                    inner.style.transition = 'transform 0.6s ease-in-out';
                }, 20);
            }, 600);
        }

        function startTimer(containerId, durationSeconds) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const digitElements = [];

            const formatTime = (s) => {
                const h = Math.floor(s / 3600).toString().padStart(2, '0');
                const m = Math.floor((s % 3600) / 60).toString().padStart(2, '0');
                const sec = (s % 60).toString().padStart(2, '0');
                return h + m + sec;
            };

            const initial = formatTime(durationSeconds);
            for (let i = 0; i < initial.length; i++) {
                const card = createFlipCard(initial[i]);
                container.appendChild(card);
                digitElements.push(card);

                if (i === 1 || i === 3) {
                    const colon = document.createElement('div');
                    colon.textContent = ':';
                    colon.style.cssText = 'font-size: 20px; line-height: 10px; color: white;';
                    container.appendChild(colon);
                }
            }

            let current = durationSeconds;

            function update() {
                if (current < 0) return clearInterval(timer);

                if (current <= 3600) {
                    container.classList.add('time-danger');
                } else {
                    container.classList.remove('time-danger');
                }

                const timeStr = formatTime(current);
                for (let i = 0; i < 6; i++) {
                    updateFlipCard(digitElements[i], timeStr[i]);
                }
                current++;
            }

            update();
            const timer = setInterval(update, 1000);
        }

        // Calculate timer for order (12-hour countdown)
        function calculateOrderTimer(createdAt, status, completedAt = null, timerStartedAt = null) {
            // Use current real-time date for dynamic timer updates
            const now = new Date();
            
            // Use timer_started_at if available, otherwise fall back to created_at
            const startTime = timerStartedAt ? new Date(timerStartedAt) : new Date(createdAt);
            const twelveHoursLater = new Date(startTime.getTime() + (12 * 60 * 60 * 1000));
            
            // If order is completed, timer is paused - show the time it took to complete
            if (status === 'completed' && completedAt) {
                const completionDate = new Date(completedAt);
                const timeTaken = completionDate - startTime;
                const isOverdue = completionDate > twelveHoursLater;
                
                return {
                    display: formatTimeDuration(timeTaken),
                    isNegative: isOverdue,
                    isCompleted: true,
                    class: 'completed'
                };
            }
            
            // If order is completed but no completion date, just show completed
            if (status === 'completed') {
                return {
                    display: 'Completed',
                    isNegative: false,
                    isCompleted: true,
                    class: 'completed'
                };
            }
            
            // For active orders: 12-hour countdown from timer_started_at (or created_at as fallback)
            // - Counts down from 12:00:00 to 00:00:00
            // - After reaching zero, continues in negative time (overtime)
            const timeDiff = now - twelveHoursLater;
            
            if (timeDiff > 0) {
                // Order is overdue (negative time - overtime)
                return {
                    display: '-' + formatTimeDuration(timeDiff),
                    isNegative: true,
                    isCompleted: false,
                    class: 'negative'
                };
            } else {
                // Order still has time remaining (countdown)
                return {
                    display: formatTimeDuration(-timeDiff),
                    isNegative: false,
                    isCompleted: false,
                    class: 'positive'
                };
            }
        }

        // Format time duration in countdown format (HH:MM:SS)
        function formatTimeDuration(milliseconds) {
            const totalSeconds = Math.floor(Math.abs(milliseconds) / 1000);
            const hours = Math.floor(totalSeconds / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            
            // Format with leading zeros for proper countdown display
            const hoursStr = hours.toString().padStart(2, '0');
            const minutesStr = minutes.toString().padStart(2, '0');
            const secondsStr = seconds.toString().padStart(2, '0');
            
            return `${hoursStr}:${minutesStr}:${secondsStr}`;
        }

        // Create timer badge HTML
        function createTimerBadge(order, isDrafts, index) {
            const timer = calculateOrderTimer(order.created_at, order.status, order.completed_at, order.timer_started_at);
            const iconClass = timer.isCompleted ? 'fas fa-check' : (timer.isNegative ? 'fas fa-exclamation-triangle' : 'fas fa-clock');
            
            // Create tooltip text
            let tooltip = '';
            if (timer.isCompleted) {
                tooltip = order.completed_at 
                    ? `Order completed on ${formatDate(order.completed_at)}` 
                    : 'Order is completed';
            } else if (timer.isNegative) {
                tooltip = `Order is overdue by ${timer.display.substring(1)} (overtime). Created on ${formatDate(order.created_at)}`;
            } else {
                tooltip = `Time remaining: ${timer.display} (12-hour countdown). Order created on ${formatDate(order.created_at)}`;
            }
            
            // Generate unique ID for this timer using order ID and index
            const timerId = `flip-timer-${isDrafts ? 'draft-' : ''}${order.order_id}-${index}`;
            
            // Parse the timer display (format: HH:MM:SS or -HH:MM:SS)
            let timeString = timer.display;
            let isNegative = false;
            
            if (timeString.startsWith('-')) {
                isNegative = true;
                timeString = timeString.substring(1);
            }
            
            const timeParts = timeString.split(':');
            const hours = timeParts[0] || '00';
            const minutes = timeParts[1] || '00';
            const seconds = timeParts[2] || '00';
            
            // Create flip timer with individual digit cards
            return `
                <div id="${timerId}" class="flip-timer ${timer.class}" 
                     data-order-id="${order.order_id}" 
                     data-created-at="${order.created_at}" 
                     data-status="${order.status}" 
                     data-completed-at="${order.completed_at || ''}"
                     data-timer-started-at="${order.timer_started_at || ''}"
                     data-tooltip="${tooltip}"
                     title="${tooltip}"
                     style="gap: 4px; align-items: center;">
                    <i class="${iconClass} timer-icon" style="margin-right: 4px;"></i>
                    ${isNegative ? '<span class="negative-sign" style="color: #dc3545; font-weight: bold;">-</span>' : ''}
                    <div class="flip-card" data-digit="${hours.charAt(0)}">
                        <div class="flip-inner">
                            <div class="flip-front">${hours.charAt(0)}</div>
                            <div class="flip-back">${hours.charAt(0)}</div>
                        </div>
                    </div>
                    <div class="flip-card" data-digit="${hours.charAt(1)}">
                        <div class="flip-inner">
                            <div class="flip-front">${hours.charAt(1)}</div>
                            <div class="flip-back">${hours.charAt(1)}</div>
                        </div>
                    </div>
                    <span class="timer-separator">:</span>
                    <div class="flip-card" data-digit="${minutes.charAt(0)}">
                        <div class="flip-inner">
                            <div class="flip-front">${minutes.charAt(0)}</div>
                            <div class="flip-back">${minutes.charAt(0)}</div>
                        </div>
                    </div>
                    <div class="flip-card" data-digit="${minutes.charAt(1)}">
                        <div class="flip-inner">
                            <div class="flip-front">${minutes.charAt(1)}</div>
                            <div class="flip-back">${minutes.charAt(1)}</div>
                        </div>
                    </div>
                    <span class="timer-separator">:</span>
                    <div class="flip-card" data-digit="${seconds.charAt(0)}">
                        <div class="flip-inner">
                            <div class="flip-front">${seconds.charAt(0)}</div>
                            <div class="flip-back">${seconds.charAt(0)}</div>
                        </div>
                    </div>
                    <div class="flip-card" data-digit="${seconds.charAt(1)}">
                        <div class="flip-inner">
                            <div class="flip-front">${seconds.charAt(1)}</div>
                            <div class="flip-back">${seconds.charAt(1)}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Start timers for all rendered orders
        function startTimersForOrders(ordersList, isDrafts, startIndex) {
            ordersList.forEach((order, index) => {
                if (order.status !== 'completed' && order.status !== 'cancelled') {
                    const timerId = `flip-timer-${isDrafts ? 'draft-' : ''}${order.order_id}-${startIndex + index}`;
                    const timer = calculateOrderTimer(order.created_at, order.status, order.completed_at, order.timer_started_at);
                    
                    if (!timer.isCompleted) {
                        updateTimerDisplay(timerId, timer);
                        
                        // Set up interval to update timer every second
                        setInterval(() => {
                            const updatedTimer = calculateOrderTimer(order.created_at, order.status, order.completed_at, order.timer_started_at);
                            updateTimerDisplay(timerId, updatedTimer);
                        }, 1000);
                    }
                }
            });
        }

        // Start timer for a single order (used in offcanvas)
        function startSingleTimer(order, isDrafts, index) {
            if (order.status === 'completed' || order.status === 'cancelled') return;
            
            const timerId = `flip-timer-${isDrafts ? 'draft-' : ''}${order.order_id}-${index}`;
            const timer = calculateOrderTimer(order.created_at, order.status, order.completed_at, order.timer_started_at);
            
            if (!timer.isCompleted) {
                updateTimerDisplay(timerId, timer);
                
                // Set up interval to update timer every second
                setInterval(() => {
                    const updatedTimer = calculateOrderTimer(order.created_at, order.status, order.completed_at, order.timer_started_at);
                    updateTimerDisplay(timerId, updatedTimer);
                }, 1000);
            }
        }

        // Update timer display
        function updateTimerDisplay(timerId, timer) {
            const timerElement = document.getElementById(timerId);
            if (!timerElement) return;
            
            // Update timer class
            timerElement.className = `flip-timer ${timer.class}`;
            
            let timeString = timer.display;
            if (timeString === 'Completed') return;
            
            let isNegative = false;
            if (timeString.startsWith('-')) {
                isNegative = true;
                timeString = timeString.substring(1);
            }
            
            const timeParts = timeString.split(':');
            const hours = timeParts[0] || '00';
            const minutes = timeParts[1] || '00';
            const seconds = timeParts[2] || '00';
            
            // Update digit cards
            const flipCards = timerElement.querySelectorAll('.flip-card');
            if (flipCards.length >= 6) {
                updateFlipCard(flipCards[0], hours.charAt(0));
                updateFlipCard(flipCards[1], hours.charAt(1));
                updateFlipCard(flipCards[2], minutes.charAt(0));
                updateFlipCard(flipCards[3], minutes.charAt(1));
                updateFlipCard(flipCards[4], seconds.charAt(0));
                updateFlipCard(flipCards[5], seconds.charAt(1));
            }
        }

        // Update individual flip card
        function updateFlipCard(card, newDigit) {
            if (!card) return;
            
            const currentDigit = card.getAttribute('data-digit');
            if (currentDigit === newDigit) return;
            
            const flipInner = card.querySelector('.flip-inner');
            const flipFront = card.querySelector('.flip-front');
            const flipBack = card.querySelector('.flip-back');
            
            if (!flipInner || !flipFront || !flipBack) return;
            
            // Update back face with new digit
            flipBack.textContent = newDigit;
            
            // Trigger flip animation
            flipInner.style.transform = 'rotateX(180deg)';
            
            setTimeout(() => {
                // Update front face and reset position
                flipFront.textContent = newDigit;
                card.setAttribute('data-digit', newDigit);
                flipInner.style.transition = 'none';
                flipInner.style.transform = 'rotateX(0deg)';
                
                // Re-enable transition
                setTimeout(() => {
                    flipInner.style.transition = 'transform 0.6s ease-in-out';
                }, 20);
            }, 300);
        }

        // Helper functions for canvas rendering
        function renderProfileLinksFromObject(prefixVariantsDetails) {
            if (!prefixVariantsDetails || typeof prefixVariantsDetails !== 'object') {
                return `<span>N/A</span>`;
            }

            let html = '';

            Object.entries(prefixVariantsDetails).forEach(([key, variant]) => {
                const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

                html += `<div class="mt-1">`;
                html += `<strong>${formattedKey}:</strong> `;

                if (variant?.profile_link) {
                    html += `<a href="${variant.profile_link}" target="_blank">${variant.profile_link}</a>`;
                } else {
                    html += `<span>N/A</span>`;
                }

                html += `</div>`;
            });

            return html;
        }

        // Enhanced function to render domains with attractive styling
        function renderDomainsWithStyle(splits) {
            if (!splits || splits.length === 0) {
                return '<div class="text-center py-3"><small class="text-white">No domains available</small></div>';
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
                return '<div class="text-center py-3"><small class="text-white">No domains available</small></div>';
            }
            
            // Create styled domain badges
            return allDomains
                .filter(domain => domain && typeof domain === 'string')
                .map((domain, index) => `
                    <span class="domain-badge" style="
                        display: inline-block;
                        background-color: var(--filter-color);
                        color: white;
                        min-width: 8rem;
                        padding: 4px 8px;
                        margin: 2px 2px;
                        border-radius: 12px;
                        font-size: 11px;
                        font-weight: 500;
                        cursor: pointer;
                    "
                    title="Click to copy: ${domain}"
                    onclick="copyToClipboard('${domain}')">
                        <i class="fa-solid fa-globe me-1" style="font-size: 9px;"></i>${domain}
                    </span>
                `).join('');
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
                            variants.push(`<span>Variant ${index + 1}: ${prefixVariants[key]}</span><br>`);
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
                        icon.style.transform = 'rotate(0deg)'; // Point right when closed
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
                        icon.style.transform = 'rotate(90deg)'; // Point down when open
                        
                        // Add expanding class for additional effects
                        const container = content.closest('.split-container');
                        if (container) {
                            container.classList.add('expanded');
                        }
                    });
                }
            }
        }

        // Initialize chevron states for all splits
        function initializeChevronStates() {
            const allIcons = document.querySelectorAll('[id^="icon-split-"]');
            allIcons.forEach(icon => {
                const contentId = icon.id.replace('icon-', '');
                const content = document.getElementById(contentId);
                
                if (content && content.classList.contains('show')) {
                    icon.style.transform = 'rotate(90deg)'; // Point down if open
                } else {
                    icon.style.transform = 'rotate(0deg)'; // Point right if closed
                }
            });
        }

        // Copy all domains from a split to clipboard
        function copyAllDomainsFromSplit(splitId, splitLabel) {
            const content = document.getElementById(splitId);
            if (!content) return;
            
            // Get all domain texts
            const domainTexts = Array.from(content.querySelectorAll('.domain-text'))
                .map(el => el.textContent.trim())
                .filter(text => text !== '');
            
            if (domainTexts.length === 0) {
                return alert('No domains found to copy.');
            }
            
            // Create a temporary textarea element to facilitate copying
            const textarea = document.createElement('textarea');
            textarea.value = domainTexts.join('\n');
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            // Show success message
            alert(`All domains from ${splitLabel} copied to clipboard!`);
        }
    </script>
@endpush
