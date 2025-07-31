@extends('contractor.layouts.app')

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

        .flip-timer.paused {
            background: rgba(255, 193, 7, 0.1);
            border-color: rgba(255, 193, 7, 0.3);
            color: #856404;
        }

        .flip-timer.paused .flip-front,
        .flip-timer.paused .flip-back {
            background: linear-gradient(to bottom, #fff3cd 50%, #ffeaa7 50%);
            color: #856404;
            border-color: rgba(255, 193, 7, 0.2);
        }

        .flip-timer.cancelled {
            background: rgba(108, 117, 125, 0.1);
            border-color: rgba(108, 117, 125, 0.3);
            color: #6c757d;
        }

        .flip-timer.cancelled .flip-front,
        .flip-timer.cancelled .flip-back {
            background: linear-gradient(to bottom, #e2e6ea 50%, #dae0e5 50%);
            color: #495057;
            border-color: rgba(108, 117, 125, 0.2);
        }

        .flip-timer.reject {
            background: rgba(220, 53, 69, 0.1);
            border-color: rgba(220, 53, 69, 0.3);
            color: #721c24;
        }

        .flip-timer.reject .flip-front,
        .flip-timer.reject .flip-back {
            background: linear-gradient(to bottom, #f8d7da 50%, #f5c6cb 50%);
            color: #721c24;
            border-color: rgba(220, 53, 69, 0.2);
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
                        <div class="col-md-4 mb-3">
                            <label class="form-label mb-0">Order ID</label>
                            <input type="text" name="order_id" class="form-control" placeholder="Enter order ID">
                        </div>
                        <!-- <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="unallocated">Unallocated</option>
                                <option value="allocated">Allocated</option>
                                <option value="in-progress">In Progress</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div> -->
                        <div class="col-md-4 mb-3">
                            <label class="form-label mb-0">Min Inboxes</label>
                            <input type="number" name="min_inboxes" class="form-control" placeholder="e.g. 10">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label mb-0">Max Inboxes</label>
                            <input type="number" name="max_inboxes" class="form-control" placeholder="e.g. 100">
                        </div>
                        <!-- <div class="col-md-3 mb-3">
                            <div class="form-check" style="padding-top: 1.5rem;">
                                <input class="form-check-input" type="checkbox" name="assigned_to_me" id="assignedToMeFilter" value="1">
                                <label class="form-check-label" for="assignedToMeFilter">
                                    <i class="fas fa-user me-1"></i>
                                    Assigned to Me Only
                                </label>
                            </div>
                        </div> -->
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
            <li class="nav-item" role="presentation" style="display:none;">
                <button class="nav-link py-1 text-capitalize text-white" id="in-draft-tab" data-bs-toggle="tab"
                    data-bs-target="#in-draft-tab-pane" type="button" role="tab" aria-controls="in-draft-tab-pane"
                    aria-selected="false">in-draft</button>
            </li>
            @php
                $is_rejected = \App\Models\Order::where('status_manage_by_admin', 'reject')->count();
            @endphp
            <li class="nav-item" role="presentation" style="display: {{ $is_rejected ? 'block' : 'none' }};" id="reject-orders-tab-li">
                <button class="nav-link py-1 text-capitalize text-white" id="reject-orders-tab" data-bs-toggle="tab"
                    data-bs-target="#reject-orders-tab-pane" type="button" role="tab" aria-controls="reject-orders-tab-pane"
                    aria-selected="false">
                    reject orders
                </button>
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
            <div class="tab-pane fade" id="reject-orders-tab-pane" role="tabpanel" aria-labelledby="reject-orders-tab"
                tabindex="0">
                <div id="rejectOrdersContainer" class="mb-4"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    <!-- Loading state -->
                    <div id="rejectOrdersLoadingState" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                        <div class="spinner-border text-danger" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 mb-0">Loading rejected orders...</p>
                    </div>
                </div>
                <!-- Load More Button for Rejected Orders -->
                <div id="loadMoreRejectOrdersContainer" class="text-center mt-4" style="display: none;">
                    <button id="loadMoreRejectOrdersBtn" class="btn btn-lg btn-danger px-4 me-2 border-0">
                        <span id="loadMoreRejectOrdersText">Load More</span>
                        <span id="loadMoreRejectOrdersSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
                            style="display: none;">
                            <span class="visually-hidden">Loading...</span>
                        </span>
                    </button>
                    <div id="paginationRejectOrdersInfo" class="mt-2 text-light small">
                        Showing <span id="showingRejectOrdersFrom">0</span> to <span id="showingRejectOrdersTo">0</span> of <span
                            id="totalRejectOrders">0</span> orders
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

    <!-- Reject Order Modal -->
    <div class="modal fade" id="rejectOrderModal" tabindex="-1" aria-labelledby="rejectOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="rejectOrderModalLabel">
                        <i class="fas fa-times me-2"></i>Reject Order
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>
                            This action will mark the order as rejected and cannot be undone.
                        </div>
                    </div>
                    
                    <form id="rejectOrderForm">
                        <div class="mb-3">
                            <label for="rejectionReason" class="form-label fw-bold">
                                Rejection Reason <span class="text-danger">*</span>
                            </label>
                            <textarea 
                                class="form-control" 
                                id="rejectionReason" 
                                name="rejection_reason"
                                rows="4" 
                                placeholder="Please provide a detailed reason for rejecting this order..."
                                required
                                minlength="5"
                            ></textarea>
                            <div class="form-text">Minimum 5 characters required</div>
                            <div class="invalid-feedback" id="rejectionReasonError"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmRejectBtn">
                        <i class="fas fa-check me-1"></i>Reject Order
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection



@push('scripts')
    <script>
        let orders = [];
        let drafts = [];
        let rejectedOrders = [];
        let currentFilters = {};
        let currentPage = 1;
        let currentDraftsPage = 1;
        let currentRejectOrdersPage = 1;
        let hasMorePages = false;
        let hasMoreDraftsPages = false;
        let hasMoreRejectOrdersPages = false;
        let totalOrders = 0;
        let totalDrafts = 0;
        let totalRejectOrders = 0;
        let isLoading = false;
        let isDraftsLoading = false;
        let isRejectOrdersLoading = false;
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

            document.getElementById('reject-orders-tab').addEventListener('click', function() {
                activeTab = 'reject-orders';
                if (rejectedOrders.length === 0) {
                    loadOrders(currentFilters, 1, false, 'reject-orders');
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
                } else if (activeTab === 'in-draft') {
                    loadOrders(filters, 1, false, 'in-draft');
                } else {
                    loadOrders(filters, 1, false, 'reject-orders');
                }
            });

            // Reset filters handler
            document.getElementById('resetFilters').addEventListener('click', function() {
                document.getElementById('filterForm').reset();
                currentFilters = {};
                if (activeTab === 'in-queue') {
                    loadOrders({}, 1, false, 'in-queue');
                } else if (activeTab === 'in-draft') {
                    loadOrders({}, 1, false, 'in-draft');
                } else {
                    loadOrders({}, 1, false, 'reject-orders');
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

            document.getElementById('loadMoreRejectOrdersBtn').addEventListener('click', function() {
                if (hasMoreRejectOrdersPages && !isRejectOrdersLoading) {
                    loadOrders(currentFilters, currentRejectOrdersPage + 1, true, 'reject-orders');
                }
            });
        });

        // Load orders data
        async function loadOrders(filters = {}, page = 1, append = false, type = 'in-queue') {
            const isLoadingDrafts = type === 'in-draft';
            const isLoadingRejectOrders = type === 'reject-orders';
            
            try {
                if (isLoadingDrafts ? isDraftsLoading : (isLoadingRejectOrders ? isRejectOrdersLoading : isLoading)) return;
                
                if (isLoadingDrafts) {
                    isDraftsLoading = true;
                } else if (isLoadingRejectOrders) {
                    isRejectOrdersLoading = true;
                } else {
                    isLoading = true;
                }
                
                if (!append) {
                    if (isLoadingDrafts) {
                        showDraftsLoading();
                        drafts = [];
                    } else if (isLoadingRejectOrders) {
                        showRejectOrdersLoading();
                        rejectedOrders = [];
                    } else {
                        showLoading();
                        orders = [];
                    }
                }
                
                if (append) {
                    showLoadMoreSpinner(true, isLoadingDrafts || isLoadingRejectOrders ? (isLoadingDrafts ? 'drafts' : 'reject-orders') : 'orders');
                }
                
                const params = new URLSearchParams({
                    ...filters,
                    type: type,
                    page: page,
                    per_page: 12
                });
                
                const response = await fetch(`/contractor/order_queue/data?${params}`, {
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
                } else if (isLoadingRejectOrders) {
                    if (append) {
                        rejectedOrders = rejectedOrders.concat(newOrders);
                    } else {
                        rejectedOrders = newOrders;
                    }
                    
                    const pagination = data.pagination || {};
                    currentRejectOrdersPage = pagination.current_page || 1;
                    hasMoreRejectOrdersPages = pagination.has_more_pages || false;
                    totalRejectOrders = pagination.total || 0;
                    
                    renderOrders(append, false, true);
                    updatePaginationInfo(false, true);
                    updateLoadMoreButton(false, true);
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
                    showError(error.message, isLoadingDrafts, isLoadingRejectOrders);
                }
            } finally {
                if (isLoadingDrafts) {
                    isDraftsLoading = false;
                } else if (isLoadingRejectOrders) {
                    isRejectOrdersLoading = false;
                } else {
                    isLoading = false;
                }
                
                if (append) {
                    showLoadMoreSpinner(false, isLoadingDrafts || isLoadingRejectOrders ? (isLoadingDrafts ? 'drafts' : 'reject-orders') : 'orders');
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

        // Show reject orders loading state
        function showRejectOrdersLoading() {
            const container = document.getElementById('rejectOrdersContainer');
            const loadingElement = document.getElementById('rejectOrdersLoadingState');
            
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
        function hideLoading(isDrafts = false, isRejectOrders = false) {
            let loadingElement;
            if (isRejectOrders) {
                loadingElement = document.getElementById('rejectOrdersLoadingState');
            } else if (isDrafts) {
                loadingElement = document.getElementById('draftsLoadingState');
            } else {
                loadingElement = document.getElementById('loadingState');
            }
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
        }

        // Show error message
        function showError(message, isDrafts = false, isRejectOrders = false) {
            hideLoading(isDrafts, isRejectOrders);
            let container;
            let containerType;
            if (isRejectOrders) {
                container = document.getElementById('rejectOrdersContainer');
                containerType = 'reject-orders';
            } else if (isDrafts) {
                container = document.getElementById('draftsContainer');
                containerType = 'in-draft';
            } else {
                container = document.getElementById('ordersContainer');
                containerType = 'in-queue';
            }
            if (!container) return;
            
            container.style.display = 'grid';
            container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
            container.style.gap = '30px';
            
            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                    <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 3rem;"></i>
                    <h5>Error</h5>
                    <p class="mb-3">${message}</p>
                    <button class="btn btn-primary" onclick="loadOrders(currentFilters, 1, false, '${containerType}')">Retry</button>
                </div>
            `;
        }

        // Render orders
        function renderOrders(append = false, isDrafts = false, isRejectOrders = false) {
            let container, ordersList, containerLabel;
            if (isRejectOrders) {
                container = document.getElementById('rejectOrdersContainer');
                ordersList = rejectedOrders;
                containerLabel = 'Rejected ';
            } else if (isDrafts) {
                container = document.getElementById('draftsContainer');
                ordersList = drafts;
                containerLabel = 'Draft ';
            } else {
                container = document.getElementById('ordersContainer');
                ordersList = orders;
                containerLabel = '';
            }
            
            if (!append) {
                hideLoading(isDrafts, isRejectOrders);
            }
            
            if (!container) return;
            
            if (ordersList.length === 0 && !append) {
                container.innerHTML = `
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-inbox"></i>
                        <h5>No ${containerLabel}Orders Found</h5>
                        <p>There are no ${containerLabel.toLowerCase()}orders to display.</p>
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
            
            let startIndex;
            if (isRejectOrders) {
                startIndex = append ? (rejectedOrders.length - (rejectedOrders.length - ordersList.length)) : 0;
            } else if (isDrafts) {
                startIndex = append ? (drafts.length - (drafts.length - ordersList.length)) : 0;
            } else {
                startIndex = append ? (orders.length - (orders.length - ordersList.length)) : 0;
            }
            const ordersToRender = append ? ordersList.slice(startIndex) : ordersList;
            
            ordersToRender.forEach((order, index) => {
                const orderCard = createOrderCard(order, isDrafts, startIndex + index, isRejectOrders);
                container.appendChild(orderCard);
            });
            
            // Start timers for rendered orders
            setTimeout(() => {
                startTimersForOrders(ordersToRender, isDrafts, startIndex);
            }, 100);
        }

        // Create order card
        function createOrderCard(order, isDrafts, index, isRejectOrders = false) {
            const statusConfig = getStatusConfig(order.status);
            let borderColor, statusClass, statusIcon, statusText, lineColor;
            
            if (isRejectOrders) {
                borderColor = '#dc3545';
                statusClass = 'text-danger';
                statusIcon = 'fa-solid fa-ban';
                statusText = 'Rejected';
                lineColor = '#dc3545';
            } else if (isDrafts) {
                borderColor = 'rgb(0, 221, 255)';
                statusClass = 'text-info';
                statusIcon = 'fa-solid fa-file-lines';
                statusText = 'Draft';
                lineColor = 'rgb(0, 242, 255)';
            } else {
                borderColor = statusConfig.borderColor;
                statusClass = statusConfig.statusClass;
                statusIcon = statusConfig.statusIcon;
                statusText = order.status || 'Pending';
                lineColor = statusConfig.lineColor;
            }
            
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
                                ${statusText.charAt(0).toUpperCase() + statusText.slice(1)}
                            </span>
                        </div>
                        ${createTimerBadge(order, isDrafts, index)}
                    </div>

                    <div class="d-flex flex-column gap-0">
                        <h6 class="mb-0">
                            Total Inboxes : <span class="text-white number">${order.total_inboxes || 0}</span>
                        </h6>
                        <small>
                            Splits : <span class="text-white number">${order.splits_count || 0}</span>
                            ${order.rejected_by && order.status === 'reject' ? ` | Rejected by: <span class="text-white number">${order.rejected_by}</span>` : ''}
                        </small>
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
                        ${order.status === 'reject' ? `
                            <div class="d-flex align-items-center justify-content-center" 
                                style="height: 30px; width: 30px; border-radius: 50px; background-color: var(--second-primary); cursor: pointer;"
                                onclick="rejectReasonAlert(${order.rejected_by ? `'${order.rejected_by}'` : 'null'}, '${order.reason || ''}')">
                                <i class="fa-solid fa-chevron-right"></i>
                            </div>
                        ` : `
                        <div class="d-flex align-items-center justify-content-center" 
                             style="height: 30px; width: 30px; border-radius: 50px; background-color: var(--second-primary); cursor: pointer;"
                             onclick="viewOrderSplits(${order.order_id})" 
                             data-bs-toggle="offcanvas" 
                             data-bs-target="#order-splits-view">
                            <i class="fa-solid fa-chevron-right"></i>
                        </div>
                        `}
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
                'rejected': {
                    borderColor: '#dc3545',
                    statusClass: 'text-danger',
                    statusIcon: 'fa-solid fa-times',
                    lineColor: '#dc3545'
                },
                'reject': {
                    borderColor: '#dc3545',
                    statusClass: 'text-danger',
                    statusIcon: 'fa-solid fa-ban',
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
        function updatePaginationInfo(isDrafts = false, isRejectOrders = false) {
            if (isRejectOrders) {
                const showingFrom = document.getElementById('showingRejectOrdersFrom');
                const showingTo = document.getElementById('showingRejectOrdersTo');
                const totalOrdersEl = document.getElementById('totalRejectOrders');
                
                if (showingFrom && showingTo && totalOrdersEl) {
                    showingFrom.textContent = rejectedOrders.length > 0 ? 1 : 0;
                    showingTo.textContent = rejectedOrders.length;
                    totalOrdersEl.textContent = totalRejectOrders;
                }
            } else if (isDrafts) {
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
        function updateLoadMoreButton(isDrafts = false, isRejectOrders = false) {
            if (isRejectOrders) {
                const container = document.getElementById('loadMoreRejectOrdersContainer');
                if (container) {
                    container.style.display = hasMoreRejectOrdersPages ? 'block' : 'none';
                }
            } else if (isDrafts) {
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
        function showLoadMoreSpinner(show, type = 'orders') {
            let button, text, spinner;
            
            if (type === 'reject-orders') {
                button = document.getElementById('loadMoreRejectOrdersBtn');
                text = document.getElementById('loadMoreRejectOrdersText');
                spinner = document.getElementById('loadMoreRejectOrdersSpinner');
            } else if (type === 'drafts') {
                button = document.getElementById('loadMoreDraftsBtn');
                text = document.getElementById('loadMoreDraftsText');
                spinner = document.getElementById('loadMoreDraftsSpinner');
            } else {
                button = document.getElementById('loadMoreBtn');
                text = document.getElementById('loadMoreText');
                spinner = document.getElementById('loadMoreSpinner');
            }
            
            if (button && text && spinner) {
                button.disabled = show;
                text.textContent = show ? 'Loading...' : 'Load More';
                spinner.style.display = show ? 'inline-block' : 'none';
            }
        }
        // rejectReasonAlert swal alert for rejected orders
        function rejectReasonAlert(rejectedBy, reason) {
            const title = rejectedBy ? `Rejected by: ${rejectedBy}` : 'Order Rejected';
            Swal.fire({
                title: title,
                text: reason || 'No reason provided',
                icon: 'warning',
                confirmButtonText: 'Close',
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
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

                const response = await fetch(`/contractor/order_queue/${orderId}/splits`, {
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
                                <span class="badge border ${getOrderStatusBadgeClass(orderInfo.status_manage_by_admin)} me-2">${orderInfo.status_manage_by_admin.charAt(0).toUpperCase() + orderInfo.status_manage_by_admin.slice(1)}</span>
                                ${createTimerBadge(orderInfo, false, 0)}
                            </h6>
                            <p class="small mb-0">Customer: ${orderInfo.customer_name} | Date: ${formatDate(orderInfo.created_at)}</p>
                        </div>
                        
                        <div class="d-flex gap-2">
                            ${(() => {
                                const unallocatedSplits = splits.filter(split => split.status === 'unallocated');
                                let buttonsHtml = '';
                                
                                if (unallocatedSplits.length > 0) {
                                    buttonsHtml += `
                                        <button class="btn btn-success btn-sm px-3 py-2" 
                                                onclick="assignOrderToMe(${orderInfo.id})"
                                                id="assignOrderBtn"
                                                style="font-size: 11px;">
                                            <i class="fas fa-user-plus me-1" style="font-size: 10px;"></i>
                                            Assign Order to Me
                                            <span class="badge bg-white text-success ms-1 rounded-pill" style="font-size: 9px;">${unallocatedSplits.length}</span>
                                        </button>
                                    `;
                                } else {
                                    buttonsHtml += `
                                        <span class="btn btn-primary rounded-1 px-3 py-2" style="font-size: 11px;">
                                            <i class="fas fa-check me-1" style="font-size: 10px;"></i>
                                            All Splits Assigned
                                        </span>
                                    `;
                                }
                                
                                // Add reject button if order is not already rejected or completed
                                if (orderInfo.status_manage_by_admin !== 'reject' && orderInfo.status_manage_by_admin !== 'completed') {
                                    buttonsHtml += `
                                        <button class="btn btn-danger btn-sm px-3 py-2" 
                                                onclick="rejectOrder(${orderInfo.id})"
                                                id="rejectOrderBtn"
                                                style="font-size: 11px;">
                                            <i class="fas fa-times me-1" style="font-size: 10px;"></i>
                                            Reject Order
                                        </button>
                                    `;
                                }
                                
                                return buttonsHtml;
                            })()}
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
                                            <a href="/contractor/orders/${split.order_panel_id}/split/view" style="font-size: 11px" class="me-2 btn btn-sm btn-outline-primary" title="View Split" target="_blank">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="/contractor/orders/split/${split.id}/export-csv-domains" style="font-size: 11px" class="me-2 btn btn-sm btn-success" title="Download CSV with ${split.domains_count || 0} domains" target="_blank">
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
                                                <small class="fw-bold text-uppercase">PNL-${split.panel_id} | ${split.panel_title || 'N/A'}</small>
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
                if (timerElement && orderInfo.status !== 'completed' && orderInfo.status !== 'cancelled' && orderInfo.status !== 'reject') {
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

        // Calculate timer for order (12-hour countdown) with pause functionality
        function calculateOrderTimer(createdAt, status, completedAt = null, timerStartedAt = null, timerPausedAt = null, totalPausedSeconds = 0) {
            const now = new Date();

            const startTime = timerStartedAt ? new Date(timerStartedAt) : new Date(createdAt);
            const twelveHours = 12 * 60 * 60 * 1000;

            // ⏸ If paused OR cancelled OR rejected, treat as paused
            if ((timerPausedAt && status !== 'completed') || status === 'cancelled' || status === 'reject') {
                const pausedTime = timerPausedAt ? new Date(timerPausedAt) : now;

                const timeElapsedBeforePause = pausedTime - startTime;
                const effectiveTimeAtPause = Math.max(0, timeElapsedBeforePause - (totalPausedSeconds * 1000));
                const timeDiffAtPause = effectiveTimeAtPause - twelveHours;

                const label = (status === 'cancelled' || status === 'reject') ? '' : '';
                const timerClass = (status === 'cancelled' || status === 'reject') ? status : 'paused';

                if (timeDiffAtPause > 0) {
                    // Was overdue
                    return {
                        display: '-' + formatTimeDuration(timeDiffAtPause) + label,
                        isNegative: true,
                        isCompleted: false,
                        isPaused: true,
                        class: `${timerClass} negative`
                    };
                } else {
                    // Still had time left
                    return {
                        display: formatTimeDuration(-timeDiffAtPause) + label,
                        isNegative: false,
                        isCompleted: false,
                        isPaused: true,
                        class: `${timerClass} positive`
                    };
                }
            }

            // ✅ Completed (with timestamp)
            if (status === 'completed' && completedAt) {
                const completionDate = new Date(completedAt);
                const totalElapsedTime = completionDate - startTime;
                const effectiveWorkingTime = Math.max(0, totalElapsedTime - (totalPausedSeconds * 1000));
                const isOverdue = effectiveWorkingTime > twelveHours;

                return {
                    display: formatTimeDuration(effectiveWorkingTime),
                    isNegative: isOverdue,
                    isCompleted: true,
                    class: 'completed'
                };
            }

            // ✅ Completed (no timestamp)
            if (status === 'completed') {
                return {
                    display: 'Completed',
                    isNegative: false,
                    isCompleted: true,
                    class: 'completed'
                };
            }

            // ⏱ Active countdown or overtime
            const totalElapsedTime = now - startTime;
            const effectiveElapsedTime = Math.max(0, totalElapsedTime - (totalPausedSeconds * 1000));
            const effectiveDeadline = new Date(startTime.getTime() + twelveHours + (totalPausedSeconds * 1000));
            const timeDiff = now - effectiveDeadline;

            if (timeDiff > 0) {
                // Overtime
                return {
                    display: '-' + formatTimeDuration(timeDiff),
                    isNegative: true,
                    isCompleted: false,
                    class: 'negative'
                };
            } else {
                // Still in time
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
            const timer = calculateOrderTimer(
                order.created_at, 
                order.status, 
                order.completed_at, 
                order.timer_started_at, 
                order.timer_paused_at, 
                order.total_paused_seconds
            );

            // Determine the icon class based on status and timer
            let iconClass = '';
            if (order.status === 'cancelled') {
                iconClass = 'fas fa-exclamation-triangle'; // warning icon
            } else if (order.status === 'reject') {
                iconClass = 'fas fa-ban'; // ban icon for rejected
            } else if (timer.isCompleted) {
                iconClass = 'fas fa-check';
            } else if (timer.isPaused) {
                iconClass = 'fas fa-pause';
            } else {
                iconClass = timer.isNegative ? 'fas fa-exclamation-triangle' : 'fas fa-clock';
            }

            // Create tooltip text
            let tooltip = '';
            if (order.status === 'cancelled') {
                tooltip = `Order was cancelled on ${formatDate(order.completed_at || order.timer_paused_at || order.created_at)}`;
            } else if (order.status === 'reject') {
                tooltip = `Order was rejected on ${formatDate(order.completed_at || order.timer_paused_at || order.created_at)}`;
            } else if (timer.isCompleted) {
                tooltip = order.completed_at 
                    ? `Order completed on ${formatDate(order.completed_at)}` 
                    : 'Order is completed';
            } else if (timer.isPaused) {
                tooltip = `Timer is paused at ${timer.display.replace(' (Paused)', '')}. Paused on ${formatDate(order.timer_paused_at)}`;
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
                     data-timer-paused-at="${order.timer_paused_at || ''}"
                     data-total-paused-seconds="${order.total_paused_seconds || 0}"
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
                if (order.status !== 'completed' && order.status !== 'cancelled' && order.status !== 'reject') {
                    const timerId = `flip-timer-${isDrafts ? 'draft-' : ''}${order.order_id}-${startIndex + index}`;
                    const timer = calculateOrderTimer(
                        order.created_at, 
                        order.status, 
                        order.completed_at, 
                        order.timer_started_at, 
                        order.timer_paused_at, 
                        order.total_paused_seconds
                    );
                    
                    if (!timer.isCompleted) {
                        updateTimerDisplay(timerId, timer);
                        
                        // Set up interval to update timer every second
                        setInterval(() => {
                            const updatedTimer = calculateOrderTimer(
                                order.created_at, 
                                order.status, 
                                order.completed_at, 
                                order.timer_started_at, 
                                order.timer_paused_at, 
                                order.total_paused_seconds
                            );
                            updateTimerDisplay(timerId, updatedTimer);
                        }, 1000);
                    }
                }
            });
        }

        // Start timer for a single order (used in offcanvas)
        function startSingleTimer(order, isDrafts, index) {
            if (order.status === 'completed' || order.status === 'cancelled' || order.status === 'reject') return;
            
            const timerId = `flip-timer-${isDrafts ? 'draft-' : ''}${order.order_id}-${index}`;
            const timer = calculateOrderTimer(
                order.created_at, 
                order.status, 
                order.completed_at, 
                order.timer_started_at, 
                order.timer_paused_at, 
                order.total_paused_seconds
            );
            
            if (!timer.isCompleted) {
                updateTimerDisplay(timerId, timer);
                
                // Set up interval to update timer every second
                setInterval(() => {
                    const updatedTimer = calculateOrderTimer(
                        order.created_at, 
                        order.status, 
                        order.completed_at, 
                        order.timer_started_at, 
                        order.timer_paused_at, 
                        order.total_paused_seconds
                    );
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
        
        // Function to assign entire order to logged-in contractor
        async function assignOrderToMe(orderId) {
            try {
                // Show SweetAlert2 confirmation dialog
                const result = await Swal.fire({
                    title: 'Assign Order to Yourself?',
                    text: 'This will assign this order to you. Are you sure?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, assign to me!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                });

                // If user cancels, return early
                if (!result.isConfirmed) {
                    return;
                }

                // Show SweetAlert2 loading dialog
                Swal.fire({
                    title: 'Assigning Order...',
                    text: 'Please wait while we assign the order to you.',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Show loading state on the button as backup
                const button = document.getElementById('assignOrderBtn');
                if (button) {
                    const originalHtml = button.innerHTML;
                    button.disabled = true;
                    button.innerHTML = `
                        <div class="spinner-border spinner-border-sm me-1" role="status" style="width: 12px; height: 12px;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        Assigning Order...
                    `;
                }

                // Make API request to assign the order (using contractor route if exists, fallback to contractor route)
                const response = await fetch(`/contractor/order_queue/${orderId}/assign-to-me`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to assign order');
                }

                const data = await response.json();
                
                // Close loading dialog and show success
                await Swal.fire({
                    title: 'Success!',
                    text: data.message || 'Order assigned successfully!',
                    icon: 'success',
                    confirmButtonColor: '#28a745',
                    timer: 3000,
                    timerProgressBar: true
                });
                
                // Update the button to show assigned state
                if (button) {
                    button.outerHTML = `
                        <span class="badge bg-info px-3 py-2" style="font-size: 11px;">
                            <i class="fas fa-check me-1" style="font-size: 10px;"></i>
                            Order Assigned to You
                        </span>
                    `;
                }
                
                // Refresh the order list to reflect changes
                setTimeout(() => {
                    if (activeTab === 'in-queue') {
                        loadOrders(currentFilters, 1, false, 'in-queue');
                    } else {
                        loadOrders(currentFilters, 1, false, 'in-draft');
                    }
                }, 1000);
                
            } catch (error) {
                console.error('Error assigning order:', error);
                
                // Close loading dialog and show error
                await Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Failed to assign order. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
                
                // Restore button state
                const button = document.getElementById('assignOrderBtn');
                if (button) {
                    button.disabled = false;
                    // Restore original button content
                    button.innerHTML = `
                        <i class="fas fa-user-plus me-1" style="font-size: 10px;"></i>
                        Assign Order to Me
                        <span class="badge bg-white text-success ms-1 rounded-pill" style="font-size: 9px;">1</span>
                    `;
                }
            }
        }

        // Global variable to store current order ID for rejection
        let currentOrderIdForRejection = null;
        // Function to reject an order
        function rejectOrder(orderId) {
            // Store the order ID globally
            currentOrderIdForRejection = orderId;
            
            // Clear previous form data
            const rejectionReasonTextarea = document.getElementById('rejectionReason');
            const rejectionReasonError = document.getElementById('rejectionReasonError');
            const rejectOrderForm = document.getElementById('rejectOrderForm');
            
            rejectionReasonTextarea.value = '';
            rejectionReasonTextarea.classList.remove('is-invalid');
            rejectionReasonError.textContent = '';
            rejectOrderForm.classList.remove('was-validated');
            
            // Show the rejection reason modal
            const rejectModal = new bootstrap.Modal(document.getElementById('rejectOrderModal'));
            rejectModal.show();
        }

        // Handle confirm reject button click in the first modal
        document.getElementById('confirmRejectBtn').addEventListener('click', async function() {
            const rejectionReason = document.getElementById('rejectionReason').value.trim();
            const rejectionReasonTextarea = document.getElementById('rejectionReason');
            const rejectionReasonError = document.getElementById('rejectionReasonError');
            const rejectOrderForm = document.getElementById('rejectOrderForm');
            const confirmRejectBtn = this;
            
            // Reset previous validation state
            rejectionReasonTextarea.classList.remove('is-invalid');
            rejectionReasonError.textContent = '';
            rejectOrderForm.classList.remove('was-validated');
            
            // Validate rejection reason
            if (!rejectionReason) {
                rejectionReasonTextarea.classList.add('is-invalid');
                rejectionReasonError.textContent = 'Please provide a reason for rejection';
                rejectOrderForm.classList.add('was-validated');
                return;
            }
            
            if (rejectionReason.length < 5) {
                rejectionReasonTextarea.classList.add('is-invalid');
                rejectionReasonError.textContent = 'Reason must be at least 5 characters long';
                rejectOrderForm.classList.add('was-validated');
                return;
            }
            
            try {
                // Hide the modal
                const rejectModal = bootstrap.Modal.getInstance(document.getElementById('rejectOrderModal'));
                rejectModal.hide();
                
                // Show loading state on the confirm reject button
                confirmRejectBtn.disabled = true;
                confirmRejectBtn.innerHTML = `
                    <div class="spinner-border spinner-border-sm me-1" role="status" style="width: 16px; height: 16px;">
                    </div>
                    Rejecting...
                `;
                
                // Show loading state on the reject order button in offcanvas
                const button = document.getElementById('rejectOrderBtn');
                if (button) {
                    button.disabled = true;
                    button.innerHTML = `
                        <div class="spinner-border spinner-border-sm me-1" role="status" style="width: 16px; height: 16px;">
                        </div>
                        Rejecting...
                    `;
                }

                // Show SweetAlert2 loading dialog
                Swal.fire({
                    title: 'Rejecting Order...',
                    text: 'Please wait while we reject the order.',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Make API request to reject the order with reason
                const response = await fetch(`/contractor/order_queue/${currentOrderIdForRejection}/reject`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        rejection_reason: rejectionReason
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to reject order');
                }

                const data = await response.json();
                
                // Close loading dialog and show success
                await Swal.fire({
                    title: 'Success!',
                    text: data.message || 'Order rejected successfully!',
                    icon: 'success',
                    confirmButtonColor: '#28a745',
                    timer: 3000,
                    timerProgressBar: true
                });
                // Close the offcanvas if it's open
                const offcanvasElement = document.getElementById('order-splits-view');
                if (offcanvasElement) {
                    const offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
                    if (offcanvasInstance) {
                        offcanvasInstance.hide();
                    }
                }
                
                // Update the button to show rejected state
                if (button) {
                    button.outerHTML = `
                        <div class="alert alert-danger text-center mb-0" role="alert">
                            <i class="fas fa-ban me-1"></i>Order Rejected
                        </div>
                    `;
                }
                
                // Refresh all tabs data immediately
                setTimeout(() => {
                    
                    // Refresh in-queue tab
                    loadOrders(currentFilters, 1, false, 'in-queue');
                    
                    // Refresh in-draft tab
                    loadOrders(currentFilters, 1, false, 'in-draft');
                    // reject-orders-tab-li
                    const rejectOrdersTabLi = document.getElementById('reject-orders-tab-li');
                    if (rejectOrdersTabLi) {
                        rejectOrdersTabLi.style.display = 'block'; // Show the tab if there are rejected orders
                    }
                }, 500); // Reduced timeout for faster refresh
                
            } catch (error) {
                console.error('Error rejecting order:', error);
                
                // Close loading dialog and show error
                await Swal.fire({
                    title: 'Error!',
                    text: error.message || 'Failed to reject order. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
                
                // Restore button states
                if (button) {
                    button.disabled = false;
                    button.innerHTML = `
                        <i class="fas fa-ban me-1" style="font-size: 10px;"></i>
                        Reject Order
                    `;
                }
                
                // Restore confirm reject button state
                confirmRejectBtn.disabled = false;
                confirmRejectBtn.innerHTML = `
                    <i class="fas fa-ban me-1"></i>Reject Order
                `;
            } finally {
                // Clear the current order ID
                currentOrderIdForRejection = null;
            }
        });

        // Handle modal hide events to reset button states if needed
        document.getElementById('rejectOrderModal').addEventListener('hidden.bs.modal', function() {
            // Reset button state if modal is closed without completing the action
            if (currentOrderIdForRejection) {
                const button = document.getElementById('rejectOrderBtn');
                if (button && button.disabled) {
                    button.disabled = false;
                    button.innerHTML = `
                        <i class="fas fa-times me-1" style="font-size: 10px;"></i>
                        Reject Order
                    `;
                }
                // Clear the current order ID if modal is closed
                currentOrderIdForRejection = null;
            }
        });
    </script>

     
    <script>
        // Laravel Echo WebSocket Implementation for Real-time Order Updates
        document.addEventListener('DOMContentLoaded', function() {
            // Check if Echo is available (consistent check using window.Echo)
            if (typeof window.Echo !== 'undefined') {
                console.log('🔌 Laravel Echo initialized successfully', window.Echo);
                console.log('🔍 Echo connector details:', window.Echo.connector);
                
                // Test connection status first
                if (window.Echo.connector && window.Echo.connector.pusher) {
                    console.log('📡 Pusher connection state:', window.Echo.connector.pusher.connection.state);
                }
                
                // Listen to the 'orders' channel for real-time order updates
                const ordersChannel = window.Echo.channel('orders');
                console.log('🎯 Subscribed to orders channel:', ordersChannel);
                
                ordersChannel
                    .listen('.order.created', (e) => {
                        console.log('🆕 New Order Created:', e);
                        
                        // // Clear existing UI containers first to ensure fresh display
                        // const ordersContainer = document.getElementById('ordersContainer');
                        // const draftsContainer = document.getElementById('draftsContainer');
                        // const rejectOrdersContainer = document.getElementById('rejectOrdersContainer');
                        
                        // if (ordersContainer) ordersContainer.innerHTML = '';
                        // if (draftsContainer) draftsContainer.innerHTML = '';
                        // if (rejectOrdersContainer) rejectOrdersContainer.innerHTML = '';
                        
                        // // Reset all data arrays
                        // orders = [];
                        // drafts = [];
                        // rejectedOrders = [];
                        
                        // // Reset pagination states before refreshing
                        // currentPage = 1;
                        // hasMorePages = true;
                        // totalOrders = 0;
                        
                        // draftsCurrentPage = 1;
                        // draftsHasMorePages = true;
                        // totalDraftsOrders = 0;
                        
                        // rejectOrdersCurrentPage = 1;
                        // rejectOrdersHasMorePages = true;
                        // totalRejectOrders = 0;
                        
                        // Add small delay to ensure UI clears before loading new data
                        setTimeout(() => {
                            // Refresh all tabs to show the new order
                            loadOrders({}, 1, false, 'in-queue');
                            // loadOrders({}, 1, false, 'in-draft');
                            // loadOrders({}, 1, false, 'reject-orders');
                            console.log('✅ New order loaded successfully...........');
                        }, 25000);
                    })
                    .listen('.order.updated', (e) => {
                        console.log('🔄 Order Updated:', e);
                        
                        const order = e.order || e;
                        const changes = e.changes || {};
                        
                        // // Clear existing UI containers first to ensure fresh display
                        // const ordersContainer = document.getElementById('ordersContainer');
                        // const draftsContainer = document.getElementById('draftsContainer');
                        // const rejectOrdersContainer = document.getElementById('rejectOrdersContainer');
                        
                        // if (ordersContainer) ordersContainer.innerHTML = '';
                        // if (draftsContainer) draftsContainer.innerHTML = '';
                        // if (rejectOrdersContainer) rejectOrdersContainer.innerHTML = '';
                        
                        // // Reset all data arrays
                        // orders = [];
                        // drafts = [];
                        // rejectedOrders = [];
                        
                        // // Reset pagination states before refreshing
                        // currentPage = 1;
                        // hasMorePages = true;
                        // totalOrders = 0;
                        
                        // draftsCurrentPage = 1;
                        // draftsHasMorePages = true;
                        // totalDraftsOrders = 0;
                        
                        // rejectOrdersCurrentPage = 1;
                        // rejectOrdersHasMorePages = true;
                        // totalRejectOrders = 0;
                        
                        // Add delay to ensure UI clears before loading new data
                        setTimeout(() => {
                            // Refresh all tabs to reflect changes
                            loadOrders({}, 1, false, 'in-queue');
                            loadOrders({}, 1, false, 'in-draft');
                            // loadOrders({}, 1, false, 'reject-orders');
                            console.log('✅ Order updated successfully 232323...........');
                        }, 25000);
                    })
                    .listen('.order.status.updated', (e) => {
                        console.log('📊 Order Status Updated:', e);
                        
                        const order = e.order || e;
                        const previousStatus = e.previous_status;
                        const newStatus = e.status || order.status;
                        
                        // // Clear existing UI containers first to ensure fresh display
                        // const ordersContainer = document.getElementById('ordersContainer');
                        // const draftsContainer = document.getElementById('draftsContainer');
                        // const rejectOrdersContainer = document.getElementById('rejectOrdersContainer');
                        
                        // if (ordersContainer) ordersContainer.innerHTML = '';
                        // if (draftsContainer) draftsContainer.innerHTML = '';
                        // if (rejectOrdersContainer) rejectOrdersContainer.innerHTML = '';
                        
                        // // Reset all data arrays
                        // orders = [];
                        // drafts = [];
                        // rejectedOrders = [];
                        
                        // // Reset pagination states before refreshing
                        // currentPage = 1;
                        // hasMorePages = true;
                        // totalOrders = 0;
                        
                        // draftsCurrentPage = 1;
                        // draftsHasMorePages = true;
                        // totalDraftsOrders = 0;
                        
                        // rejectOrdersCurrentPage = 1;
                        // rejectOrdersHasMorePages = true;
                        // totalRejectOrders = 0;
                        
                        // Add small delay to ensure UI clears before loading new data
                        setTimeout(() => {
                            // Refresh all tabs to reflect status changes
                            loadOrders({}, 1, false, 'in-queue');
                            loadOrders({}, 1, false, 'in-draft');
                            // loadOrders({}, 1, false, 'reject-orders');
                            console.log('✅ Order status updated successfully...........');
                        }, 25000);
                    })
                    .error((error) => {
                        console.error('❌ Channel subscription error:', error);
                    });
                
                // Connection status monitoring using window.Echo
                if (window.Echo.connector && window.Echo.connector.pusher) {
                    window.Echo.connector.pusher.connection.bind('connected', () => {
                        console.log('✅ WebSocket connected successfully');
                        
                        if (typeof toastr !== 'undefined') {
                            // toastr.success('Real-time updates connected!', 'WebSocket Connected', {
                            //     timeOut: 2000,
                            //     closeButton: true
                            // });
                        }
                    });
                    
                    window.Echo.connector.pusher.connection.bind('disconnected', () => {
                        console.log('❌ WebSocket disconnected');
                        
                        // Show reconnection status
                        if (typeof toastr !== 'undefined') {
                            // toastr.warning('Real-time updates disconnected. Trying to reconnect...', 'Connection Lost', {
                            //     timeOut: 3000,
                            //     closeButton: true
                            // });
                        }
                    });
                    
                    window.Echo.connector.pusher.connection.bind('reconnected', () => {
                        console.log('🔄 WebSocket reconnected');
                        
                        if (typeof toastr !== 'undefined') {
                            // toastr.success('Real-time updates reconnected!', 'Connection Restored', {
                            //     timeOut: 2000,
                            //     closeButton: true
                            // });
                        }
                        
                        // Refresh data when reconnected
                        setTimeout(() => {
                            // Clear existing UI containers first
                            const ordersContainer = document.getElementById('ordersContainer');
                            const draftsContainer = document.getElementById('draftsContainer');
                            const rejectOrdersContainer = document.getElementById('rejectOrdersContainer');
                            
                            if (ordersContainer) ordersContainer.innerHTML = '';
                            if (draftsContainer) draftsContainer.innerHTML = '';
                            if (rejectOrdersContainer) rejectOrdersContainer.innerHTML = '';
                            
                            // Reset all data arrays
                            orders = [];
                            drafts = [];
                            rejectedOrders = [];
                            
                            // Reset pagination states
                            currentPage = 1;
                            hasMorePages = true;
                            totalOrders = 0;
                            
                            draftsCurrentPage = 1;
                            draftsHasMorePages = true;
                            totalDraftsOrders = 0;
                            
                            rejectOrdersCurrentPage = 1;
                            rejectOrdersHasMorePages = true;
                            totalRejectOrders = 0;
                            // Add small delay to ensure UI clears
                            setTimeout(() => {
                                // Refresh all tabs
                                loadOrders({}, 1, false, 'in-queue');
                                // loadOrders({}, 1, false, 'in-draft');
                                // loadOrders({}, 1, false, 'reject-orders');
                                console.log('✅ Orders refreshed after reconnection...........');
                            }, 100);
                        }, 25000);
                    });
                    
                    // Additional connection state monitoring
                    window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                        console.log(`🔄 Connection state changed from ${states.previous} to ${states.current}`);
                    });
                    
                    window.Echo.connector.pusher.connection.bind('error', (error) => {
                        console.error('❌ WebSocket connection error:', error);
                        
                        if (typeof toastr !== 'undefined') {
                            toastr.error('WebSocket connection error occurred', 'Connection Error', {
                                timeOut: 5000,
                                closeButton: true
                            });
                        }
                    });
                }
                
                console.log('✅ Listening to order events on channel: orders');
                
            } else {
                console.warn('⚠️ Laravel Echo not available. Real-time updates disabled.');
                
                // Optional: Show warning that real-time updates are not available
                setTimeout(() => {
                    if (typeof toastr !== 'undefined') {
                        // toastr.warning('Real-time updates are not available. Data will be updated on page refresh.', 'WebSocket Unavailable', {
                        //     timeOut: 5000,
                        //     closeButton: true
                        // });
                    }
                }, 2000);
            }
        });

        // Alternative implementation if you need to access Echo outside of DOMContentLoaded
        function initializeOrderWebSocket() {
            if (typeof window.Echo !== 'undefined') {
                console.log('🔌 Initializing Laravel Echo for real-time order updates...', window.Echo);
                
                // Your WebSocket logic here using window.Echo
                return window.Echo;
            } else {
                console.warn('⚠️ Laravel Echo not initialized yet');
                return null;
            }
        }

        // Function to safely check and use Echo
        function withEcho(callback) {
            if (typeof window.Echo !== 'undefined') {
                return callback(window.Echo);
            } else {
                console.warn('⚠️ Laravel Echo not available');
                return null;
            }
        }

        // Example usage:
        // withEcho((echo) => {
        //     echo.channel('orders').listen('.order.created', (e) => {
        //         console.log('Order created:', e);
        //     });
        // });

        // Export for potential module usage
        if (typeof module !== 'undefined' && module.exports) {
            module.exports = { initializeOrderWebSocket, withEcho };
        }

    </script>
@endpush
