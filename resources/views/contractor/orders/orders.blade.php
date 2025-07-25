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

    /* span {
        font-size: 13px !important
    } */

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
    /* .domain-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: 1px solid rgba(102, 126, 234, 0.3);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.75rem;
        margin: 0.125rem;
        display: inline-block;
        transition: all 0.3s ease;
    } */

    .domain-badge:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    /* Order card styling */
    .order-card {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .order-card:hover {
        /* transform: translateY(-2px); */
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* Enhanced stat boxes hover effects */
    .order-card .col-6>div {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .order-card .col-6>div:hover {
        /* transform: translateY(-1px); */
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border-color: rgba(255, 255, 255, 0.3) !important;
    }

    /* Icon animations */
    .order-card i {
        transition: all 0.3s ease;
    }

    .order-card:hover i {
        transform: scale(1.1);
    }

    /* Status badge enhancement */
    .order-card .badge {
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Button enhancement */
    .order-card button {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .order-card button:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
    }

    /* Split content animations */
    /* .collapse {
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
    } */

    /* Split fade-in animation */
    /* @keyframes splitFadeIn {
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
    } */

    /* Domain badge animations */
    /* @keyframes domainFadeIn {
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
    } */

    /* Toast animations */
    /* @keyframes toastSlideIn {
        0% {
            opacity: 0;
            transform: translateX(100%) scale(0.8);
        }

        100% {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
    } */

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
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25) !important;
        filter: brightness(1.1);
    }

    /* Split container animations */
    .split-container {
        transition: all 0.3s ease;
    }

    .split-container.expanding {
        animation: splitExpand 0.4s ease-out;
    }

    /* @keyframes splitExpand {
        0% {
            transform: scale(0.98);
        }

        50% {
            transform: scale(1.02);
        }

        100% {
            transform: scale(1);
        }
    } */

    /* Timer badge styling */
    .timer-badge {
        font-family: 'Courier New', monospace;
        font-weight: bold;
        font-size: 11px;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        transition: all 0.3s ease;
        border: 1px solid transparent;
        letter-spacing: 0.5px;
        min-width: 70px;
        justify-content: center;
        margin-left: 8px;
    }

    /* Timer states */
    .timer-badge.positive {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
        border-color: rgba(40, 167, 69, 0.3);
        box-shadow: 0 2px 4px rgba(40, 167, 69, 0.2);
    }

    .timer-badge.negative {
        background: linear-gradient(135deg, #dc3545, #fd7e14);
        color: white;
        border-color: rgba(220, 53, 69, 0.3);
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        animation: pulse-red 2s infinite;
    }

    .timer-badge.completed {
        background: linear-gradient(135deg, #6c757d, #495057);
        color: white;
        border-color: rgba(108, 117, 125, 0.3);
        box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);
    }

    .timer-badge.paused {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
        color: #212529;
        border-color: rgba(255, 193, 7, 0.3);
        box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
        animation: pulse-yellow 3s infinite;
    }

    .timer-badge.paused.negative {
        background: linear-gradient(135deg, #dc3545, #ffc107);
        color: white;
        border-color: rgba(220, 53, 69, 0.3);
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        animation: pulse-red-yellow 3s infinite;
    }

    .timer-badge.cancelled {
        background: linear-gradient(135deg, #858585, #8f8f8f);
        color: white;
        border-color: rgba(143, 143, 143, 0.3);
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
    }

    /* Pulse animation for overdue timers */
    @keyframes pulse-red {
        0% {
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }

        50% {
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.4);
            transform: scale(1.02);
        }

        100% {
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }
    }

    /* Pulse animation for paused timers */
    @keyframes pulse-yellow {
        0% {
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
        }

        50% {
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.4);
            transform: scale(1.01);
        }

        100% {
            box-shadow: 0 2px 4px rgba(255, 193, 7, 0.2);
        }
    }

    /* Pulse animation for paused overdue timers */
    @keyframes pulse-red-yellow {
        0% {
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }

        25% {
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.4);
        }

        50% {
            box-shadow: 0 4px 8px rgba(220, 53, 69, 0.4);
            transform: scale(1.01);
        }

        75% {
            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.4);
        }

        100% {
            box-shadow: 0 2px 4px rgba(220, 53, 69, 0.2);
        }
    }

    /* Timer icon styling */
    .timer-icon {
        font-size: 10px;
        margin-right: 2px;
    }

    /* Hover effects */
    .timer-badge:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
</style>
<style>
    .anim_card {
        background-color: var(--secondary-color);
        color: var(--light-color);
        border: 1px solid #99999962;
        border-radius: 8px;
        position: relative;
        opacity: 1;
    }

    .anim_card .order_detail {
        width: 100%;
        height: 14rem;
        overflow: hidden;
        border: 1px solid #86868654
    }

    .anim_card .order_detail .card_content {
        width: 100%;
        transition: .5s;
    }

    .card_content {
        transform: translateX(30%);
    }

    .anim_card:hover .order_detail .card_content {
        opacity: .9;
        transform: translateX(0%);
    }

    .anim_card .flip_details {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--second-primary);
        border-radius: 10px;
        transition: transform 0.5s ease, box-shadow 0.5s ease;
        transform-origin: left;
        transform: perspective(2000px) rotateY(0deg);
        z-index: 2;
    }

    /* .anim_card .flip_details::after {
    content: "";
    position: absolute;
    top: 0;
    right: -5px;
    width: 0px;
    height: 100%;
    background: rgba(255, 255, 255, 0.602);
    border-radius: 0 5px 5px 0;
    transition: width 0.3s ease;
    } */

    .anim_card:hover .flip_details {
        transform: perspective(2000px) rotateY(-90deg);
        box-shadow: rgba(255, 255, 255, 0.4) 0px 2px 4px,
            rgba(255, 255, 255, 0.3) 0px 7px 13px -3px,
            rgba(255, 255, 255, 0.2) 0px -3px 0px inset;
        pointer-events: none
    }

    .anim_card:hover .flip_details::after {
        width: 102px;
        background-color: #9a9a9a81;
        pointer-events: none;
    }

    .anim_card .flip_details .center {
        padding: 20px;
        background-color: var(--secondary-color);
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
    }
</style>

<style>
    .flip-card {
        position: relative;
        width: 30px;
        height: 30px;
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
        border-radius: 4px;
        font-size: 24px;
        font-weight: bold;
        color: #222;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #aaa;
    }

    .flip-front {
        z-index: 2;
    }

    .flip-back {
        transform: rotateX(180deg);
    }

    /* Change Status Modal Styles */
    .modal-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.5rem 0.5rem 0 0;
    }

    .modal-header .btn-close {
        filter: invert(1);
    }

    .modal-body {
        /* background-color: #f8f9fa; */
    }

    .form-select, .form-control {
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .form-select:focus, .form-control:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    /* Status badge styles */
    .badge.bg-primary { background-color: #0d6efd !important; }
    .badge.bg-success { background-color: #198754 !important; }
    .badge.bg-warning { background-color: #ffc107 !important; color: #000 !important; }
    .badge.bg-danger { background-color: #dc3545 !important; }
    .badge.bg-secondary { background-color: #6c757d !important; }

    /* Notification styles */
    .alert {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
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


    <!-- Grid Cards (Dynamic) -->
    <div id="ordersContainer"
        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1rem;">
        <!-- Loading state -->
        <div id="loadingState"
            style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 mb-0">Loading orders...</p>
        </div>
    </div>

    {{-- <div id="flip-timer" style="display: flex; gap: 10px; background: #111; padding: 20px;"></div> --}}


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
            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalOrders">0</span>
            orders
        </div>
    </div>


</section>

<!-- Order Details Offcanvas -->
<div class="offcanvas offcanvas-end" style="width: 100%;" tabindex="-1" id="order-splits-view"
    aria-labelledby="order-splits-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
    <div class="offcanvas-header border-0 pb-0" style="background-color: transparent">
        <h5 class="offcanvas-title" id="order-splits-viewLabel">Order Details</h5>
        <button type="button" class="bg-transparent border-0" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="fas fa-times fs-5"></i>
        </button>
    </div>
    <div class="offcanvas-body pt-2">
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

<!-- Change Status Modal -->
<div class="modal fade" id="changeStatusModal" tabindex="-1" aria-labelledby="changeStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changeStatusModalLabel">Change Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Order ID: <span id="modalOrderId" class="fw-bold text-primary"></span></label>
                </div>
                <div class="mb-3">
                    <label class="form-label">Current Status: <span id="modalCurrentStatus" class="badge"></span></label>
                </div>
                <div class="mb-3">
                    <label for="newStatus" class="form-label">Select New Status</label>
                    <select class="form-select" id="newStatus" required>
                        <option value="">-- Select Status --</option>
                        <!-- <option value="pending">Pending</option> -->
                        <option value="completed">Completed</option>
                        <!-- <option value="cancelled">Cancelled</option> -->
                        <option value="reject">Rejected</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="statusReason" class="form-label">Reason for Status Change (Optional)</label>
                    <textarea class="form-control" id="statusReason" rows="3" placeholder="Enter reason for status change..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusChange" onclick="updateOrderStatus()">
                    <i class="fas fa-save me-1"></i>
                    Update Status
                </button>
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
                const url = `/contractor/assigned/order/data?${params}`;
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
            
            container.innerHTML = 
                `<div class="empty-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
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
                
                container.innerHTML = 
                    `<div class="empty-state" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                        <i class="fas fa-inbox text-white mb-3" style="font-size: 3rem;"></i>
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
            // Calculate splits table content
            const splitsTableContent = order.splits && order.splits.length > 0 
            ? order.splits.map((split, index) => `
                <tr>
                <td style="font-size: 10px; padding: 5px !important;">${index + 1}</td>
                <td style="font-size: 10px; padding: 5px !important;"><span class="badge ${getStatusBadgeClass(split.status)}" style="font-size: 9px;">${split.status || 'Unknown'}</span></td>
                <td style="font-size: 10px; padding: 5px !important;">${split.inboxes_per_domain || 'N/A'}</td>
                <td style="font-size: 10px; padding: 5px !important;">${split.domains_count || 0}</td>
                <td style="padding: 5px !important;">
                    <div class="d-flex gap-1">
                        <i class="fa-regular fa-eye" style="cursor: pointer;" onclick="event.stopPropagation(); window.open('/contractor/orders/${split.order_panel_id}/split/view', '_blank')" title="View Split"></i>
                        <i class="fa-solid fa-download" style="cursor: pointer; color: #28a745;" onclick="event.stopPropagation(); window.open('/contractor/orders/split/${split.id}/export-csv-domains', '_blank')" title="Download CSV"></i>
                    </div>
                </td>
                </tr>
            `).join('')
            : `<tr><td colspan="5" style="font-size: 10px; padding: 10px; text-align: center;">No splits available</td></tr>`;

            return `
            <div class="anim_card rounded-2">
                <div class="order_detail p-3">
                <div class="card_content">
                    <div class="text-end">
                    <button class="btn btn-primary px-2 py-1 rounded-1" 
                        onclick="viewOrderSplits(${order.order_id})" 
                        data-bs-toggle="offcanvas" 
                        data-bs-target="#order-splits-view"
                        style="font-size: 11px">
                        View More Detail
                    </button>
                    </div>

                    <table class="mt-2 border-0 w-100" style="height: 10.5rem; overflow-y: auto; display: block; scrollbar-width: none;">
                    <thead>
                        <tr>
                        <th style="font-size: 11px; padding: 5px !important; min-width: 2rem !important;" class="text-capitalize">ID #</th>
                        <th style="font-size: 11px; padding: 5px !important;" class="text-capitalize">Split Status</th>
                        <th style="font-size: 11px; padding: 5px !important;" class="text-capitalize">Inboxes/Domain</th>
                        <th style="font-size: 11px; padding: 5px !important;" class="text-capitalize">Total Domains</th>
                        <th style="font-size: 11px; padding: 5px !important; min-width: 2rem !important;" class="text-capitalize">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${splitsTableContent}
                    </tbody>
                    </table>
                </div>
                </div>
                
                <div class="flip_details overflow-hidden">
                <div class="center w-100 h-100">
                    <div class="rounded-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6>Order #${order.order_id}</h6>
                        <div>
                            ${order.status_manage_by_admin}
                            ${createTimerBadge(order)}
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-3 align-items-center">
                        <div>
                            ${order.customer_image ? 
                                `<img src="${order.customer_image}" width="60" height="60" style="border-radius: 50px; object-fit: cover;" alt="${order.customer_name}">` :
                                `<div class="d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; border-radius: 50px; background-color: #f8f9fa; border: 2px solid #dee2e6;">
                                    <i class="fas fa-user text-muted" style="font-size: 24px;"></i>
                                </div>`
                            }
                        </div>

                        <div class="d-flex flex-column gap-1">
                        <span class="fw-bold">${order.customer_name}</span>
                        <small>
                            Total Inboxes: ${order.total_inboxes} | ${order.splits_count} Split${order.splits_count === 1 ? '' : 's'}
                        </small>
                        </div>
                    </div>

                    <small class="ms-2">${formatDate(order.created_at)}</small>

                    <!-- Order Splits Table in flip_details -->
                    

                    <div class="d-flex align-items-center justify-content-between mt-3">
                        <div class="d-flex flex-column align-items-center gap-0">
                        <small class="fw-bold" style="font-size: 13px">Inbox/Domain</small>
                        <small style="font-size: 12px">${order.inboxes_per_domain}</small>
                        </div>
                        <div class="d-flex flex-column align-items-center gap-0">
                        <small class="fw-bold" style="font-size: 13px">Total Domains</small>
                        <small style="font-size: 12px">${order.total_domains}</small>
                        </div>
                    </div>
                    </div>
                </div>
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
        // Calculate timer for order
function calculateOrderTimer(createdAt, status, completedAt = null, timerStartedAt = null, timerPausedAt = null, totalPausedSeconds = 0) {
    console.log(createdAt, status, completedAt, timerStartedAt, timerPausedAt, totalPausedSeconds);
    const now = new Date();

    const startTime = timerStartedAt ? new Date(timerStartedAt) : new Date(createdAt);
    const twelveHours = 12 * 60 * 60 * 1000;

    // ⏸ If paused OR cancelled, treat as paused
    if ((timerPausedAt && status !== 'completed') || status === 'cancelled') {
        const pausedTime = timerPausedAt ? new Date(timerPausedAt) : now;

        const timeElapsedBeforePause = pausedTime - startTime;
        const effectiveTimeAtPause = Math.max(0, timeElapsedBeforePause - (totalPausedSeconds * 1000));
        const timeDiffAtPause = effectiveTimeAtPause - twelveHours;

        const label = status === 'cancelled' ? '' : '';
        const timerClass = status === 'cancelled' ? 'cancelled' : 'paused';

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
        function createTimerBadge(order) {
            console.log(order);
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

            return `
                <span class="timer-badge ${timer.class}" 
                    data-order-id="${order.order_id}" 
                    data-created-at="${order.created_at}" 
                    data-status="${order.status}" 
                    data-completed-at="${order.completed_at || ''}"
                    data-timer-started-at="${order.timer_started_at || ''}"
                    data-timer-paused-at="${order.timer_paused_at || ''}"
                    data-total-paused-seconds="${order.total_paused_seconds || 0}"
                    data-tooltip="${tooltip}">
                    <i class="${iconClass} timer-icon"></i>
                    ${timer.display}
                </span>
            `;
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
                    
                    // Reset offcanvas title
                    const offcanvasTitle = document.getElementById('order-splits-viewLabel');
                    if (offcanvasTitle) {
                        offcanvasTitle.innerHTML = 'Order Details';
                    }
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
                        <i class="fas fa-inbox text-white fs-3 mb-3"></i>
                        <h5>No Splits Found</h5>
                        <p>This order doesn't have any splits yet.</p>
                    </div>
                `;
                return;
            }
            
            const orderInfo = data.order;
            const reorderInfo = data.reorder_info;
            const splits = data.splits;
            // console.log('Order Info:', orderInfo);
            // console.log('reOrder data_obj Info:',reorderInfo.data_obj);
            // console.log('reOrder  Info:',reorderInfo);

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
                                ${orderInfo.status_manage_by_admin}
                                ${createTimerBadge({
                                    order_id: orderInfo.id,
                                    created_at: orderInfo.created_at,
                                    status: orderInfo.status,
                                    completed_at: orderInfo.completed_at,
                                    timer_started_at: orderInfo.timer_started_at,
                                    timer_paused_at: orderInfo.timer_paused_at,
                                    total_paused_seconds: orderInfo.total_paused_seconds
                                })}
                            </h6>
                            <p class="text-white small mb-0">Customer: ${orderInfo.customer_name} | Date: ${formatDate(orderInfo.created_at)}</p>
                        </div>
                        <div class="d-flex gap-2">
                            ${(() => {
                                const unallocatedSplits = splits.filter(split => split.status === 'unallocated');
                                if (unallocatedSplits.length > 0) {
                                   
                                    return `
                                        <button class="btn btn-success btn-sm px-3 py-2" 
                                                onclick="assignOrderToMe(${orderInfo?.id})"
                                                id="assignOrderBtn"
                                                style="font-size: 13px;">
                                            <i class="fas fa-user-plus me-1" style="font-size: 12px;"></i>
                                            Assign Order to Me
                                            <span class="badge bg-white text-success ms-1 rounded-pill" style="font-size: 9px;">${unallocatedSplits?.length}</span>
                                        </button>
                                    `;
                                } else {
                                    return `
                                        <span class="bg-success rounded-1 px-3 py-2 text-white" style="font-size: 13px;">
                                            <i class="fas fa-check me-1" style="font-size: 12px;"></i>
                                            All Splits Assigned
                                        </span>
                                    `;
                                }
                            })()}
                            
                            ${orderInfo.status !== 'reject' && orderInfo.status !== 'completed' ? `
                                <button class="btn btn-warning btn-sm px-3 py-2" 
                                        onclick="openChangeStatusModal(${orderInfo?.id}, '${orderInfo?.status}')"
                                        style="font-size: 13px;">
                                    <i class="fas fa-edit me-1" style="font-size: 12px;"></i>
                                    Change Status
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
                <div class="table-responsive mb-4 card rounded-2 p-2" style="max-height: 20rem; overflow-y: auto">
                    <table class="table table-striped table-hover position-sticky top-0 border-0">
                        <thead class="border-0">
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Split ID</th>
                                <th scope="col">Panel Id</th>
                                <th scope="col">Panel Title</th>
                                <th scope="col">Split Status</th>
                                <th scope="col">Inboxes/Domain</th>
                                <th scope="col">Total Domains</th>
                                <th scope="col">Total Inboxes</th>
                                <th scope="col">Split timer</th>
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
                                        <span class="py-1 px-2 rounded-1 text-white text-capitalize ${getStatusBadgeClass(split.status)}">${split.status || 'Unknown'}</span>
                                    </td>
                                    
                                    <td>${split.inboxes_per_domain || 'N/A'}</td>
                                    <td>
                                        <span class="py-1 px-2 rounded-1 border border-success success" style="font-size: 10px;">
                                            ${split.domains_count || 0} domain(s)
                                        </span>
                                    </td>
                                    <td>${split.total_inboxes || 'N/A'}</td>
                                    <td>${calculateSplitTime(split)|| 'N/A'}</td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="/contractor/orders/${split.order_panel_id}/split/view" style="font-size: 10px" class="btn btn-sm btn-outline-primary me-2" title="View Split">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="/contractor/orders/split/${split.id}/export-csv-domains" style="font-size: 10px" class="btn btn-sm btn-success" title="Download CSV with ${split.domains_count || 0} domains" target="_blank">
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
                                <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                    <i class="fa-regular fa-envelope"></i>
                                </div>
                                Email configurations
                            </h6>

                            <div class="d-flex align-items-center justify-content-between">
                                <span style="font-size: 12px">${(() => {
                                    const totalInboxes = splits.reduce((total, split) => total + (split.total_inboxes || 0), 0);
                                    const totalDomains = splits.reduce((total, split) => total + (split.domains_count || 0), 0);
                                    const inboxesPerDomain = reorderInfo?.inboxes_per_domain || 0;
                                    
                                    let splitDetails = '';
                                    splits.forEach((split, index) => {
                                        splitDetails += `
                                            <br>
                                            <span class="bg-white text-dark me-1 py-1 px-2 rounded-1" style="font-size: 10px; font-weight: bold;">Split ${String(index + 1).padStart(2, '0')}</span> 
                                                Inboxes: ${split.total_inboxes || 0} (${split.domains_count || 0} domains × ${inboxesPerDomain})<br>`;
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
                                <span class="opacity-50 small">Profile Picture URLS</span>
                             <small>${renderProfileLinksFromObject(reorderInfo?.data_obj?.prefix_variants_details)}</small>
                            </div>
                           
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card p-3 overflow-y-auto" style="max-height: 50rem">
                            <h6 class="d-flex align-items-center gap-2">
                                <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
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
                                <h6 class="d-flex align-items-center gap-1">
                                    <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                        <i class="fa-solid fa-globe"></i>
                                    </div>
                                   <span>All Domains & Splits</span>
                                </h6>
                                
                                <!-- Order Splits Domains -->
                                ${splits.map((split, index) => `
                                    <div class="domain-split-container mb-3">
                                        <div class="split-header d-flex align-items-center justify-content-between p-2 rounded-top" 
                                             style="background: var(--filter-color); cursor: pointer; border: 1px solid var(--second-primary)"
                                             onclick="toggleSplit('split-${orderInfo.id}-${index}')">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-white text-dark me-2" style="font-size: 10px; font-weight: bold;">
                                                    Split ${String(index + 1).padStart(2, '0')}
                                                </span>
                                                <small class="text-white fw-bold">PNL-${split.panel_id} Domains</small>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-white bg-opacity-25 text-white me-2" style="font-size: 9px;">
                                                    ${split.domains_count || 0} domains
                                                </span>
                                                <i class="fa-solid fa-copy text-white me-2" style="font-size: 10px; cursor: pointer; opacity: 0.8;" 
                                                   title="Copy all domains from Split ${String(index + 1).padStart(2, '0')}" 
                                                   onclick="event.stopPropagation(); copyAllDomainsFromSplit('split-${orderInfo.id}-${index}', 'Split ${String(index + 1).padStart(2, '0')}')"></i>
                                                <i class="fa-solid fa-chevron-right text-white transition-transform" id="icon-split-${orderInfo.id}-${index}"></i>
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


                                <span class="opacity-50">Back up codes</span>
                                <span>${reorderInfo?.data_obj?.backup_codes || 'N/A'}</span>

                                <span class="opacity-50">Additional Notes</span>
                                <span>${reorderInfo?.data_obj?.additional_info || 'N/A'}</span>

    
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            container.innerHTML = splitsHtml;
            
            // Initialize chevron states and animations after rendering
            setTimeout(function() {
                initializeChevronStates();
            }, 100);
        }

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



      // Split timer calculator
function calculateSplitTime(split) {
  const order_panel = split.order_panel;

  if (!order_panel || !order_panel.timer_started_at) {
    return "00:00:00";
  }

  const start = parseUTCDateTime(order_panel.timer_started_at);
  let end;

  if (order_panel.status === "completed" && order_panel.completed_at) {
    end = parseUTCDateTime(order_panel.completed_at);
  } else if (order_panel.status === "in-progress") {
    end = new Date(); // current time
  } else {
    return "00:00:00"; // unknown status
  }

  const diffMs = end - start;
  if (diffMs <= 0) return "00:00:00";

  const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
  const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
  const diffSecs = Math.floor((diffMs % (1000 * 60)) / 1000);

  const pad = (n) => (n < 10 ? "0" + n : n);
  return `${pad(diffHrs)}:${pad(diffMins)}:${pad(diffSecs)}`;
}

function parseUTCDateTime(dateStr) {
  const [datePart, timePart] = dateStr.split(" ");
  const [year, month, day] = datePart.split("-").map(Number);
  const [hour, minute, second] = timePart.split(":").map(Number);
  return new Date(Date.UTC(year, month - 1, day, hour, minute, second));
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
                        min-width: 7rem;
                        padding: 4px 8px;
                        margin: 2px 2px;
                        border-radius: 12px;
                        font-size: 10px;
                        font-weight: 200;
                        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        transition: all 0.3s ease;
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
    if (!reorderInfo) return '<div>N/A</div>';

    let variants = [];

    // Check if we have the new prefix_variants JSON format
    if (reorderInfo.prefix_variants) {
        try {
            const prefixVariants = typeof reorderInfo.prefix_variants === 'string' 
                ? JSON.parse(reorderInfo.prefix_variants) 
                : reorderInfo.prefix_variants;

            Object.keys(prefixVariants).forEach((key, index) => {
                if (prefixVariants[key]) {
                    variants.push(`<div>Variant ${index + 1}: ${prefixVariants[key]}</div>`);
                }
            });
        } catch (e) {
            console.warn('Could not parse prefix variants:', e);
        }
    }

    // Fallback to old individual fields if new format is empty
    if (variants.length === 0) {
        if (reorderInfo.prefix_variant_1) {
            variants.push(`<div>Variant 1: ${reorderInfo.prefix_variant_1}</div>`);
        }
        if (reorderInfo.prefix_variant_2) {
            variants.push(`<div>Variant 2: ${reorderInfo.prefix_variant_2}</div>`);
        }
    }

    return variants.length > 0 ? variants.join('') : '<div>N/A</div>';
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
                            container.classList.add('expanding');
                            setTimeout(() => {
                                container.classList.remove('expanding');
                            }, 400);
                        }
                    });
                    
                    // Animate domain badges within the split with staggered delay
                    // setTimeout(() => {
                    //     const domainBadges = content.querySelectorAll('.domain-badge');
                    //     domainBadges.forEach((badge, index) => {
                    //         badge.style.animation = `domainFadeIn 0.3s ease-out ${index * 0.001}s both`;
                    //     });
                    // }, 200);
                }
            }
        }
        
        // Function to initialize chevron states and animations on page load
        function initializeChevronStates() {
            // Find all collapsible elements and set initial chevron states
            document.querySelectorAll('[id^="split-"]').forEach(function(element) {
                const splitId = element.id;
                const icon = document.getElementById('icon-' + splitId);
                
                if (icon) {
                    // Add transition class for smooth chevron rotation
                    icon.classList.add('transition-transform');
                    
                    // Check if the element has 'show' class or is visible
                    const isVisible = element.classList.contains('show');
                    
                    if (isVisible) {
                        icon.style.transform = 'rotate(90deg)'; // Point down when open
                        // Set initial animation state for visible content
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    } else {
                        icon.style.transform = 'rotate(0deg)'; // Point right when closed
                        // Set initial hidden state
                        element.style.opacity = '0';
                        element.style.transform = 'translateY(-10px)';
                    }
                }
            });
            
            // Initialize domain badge animations for visible splits only
            // document.querySelectorAll('.collapse.show .domain-badge').forEach((badge, index) => {
            //     badge.style.animation = `domainFadeIn 0.3s ease-out ${index * 0.001}s both`;
            // });
        }

        // Function to copy all domains from a split container by extracting them from the DOM
        function copyAllDomainsFromSplit(splitId, splitName) {
            const splitContainer = document.getElementById(splitId);
            if (!splitContainer) {
                showToast('Split container not found', 'error');
                return;
            }
            
            // Extract domain names from the domain badges in the split container
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
                showToast(`No domains found in ${splitName}`, 'error');
                return;
            }
            
            // Join domains with newlines for easy copying
            const domainsText = domains.join('\n');
            
            navigator.clipboard.writeText(domainsText).then(() => {
                showToast(`Copied ${domains.length} domains from ${splitName}`, 'success');
            }).catch(() => {
                showToast('Failed to copy domains', 'error');
            });
        }

        // Function to assign entire order to logged-in contractor
        async function assignOrderToMe(orderId) {
            try {
            // Show SweetAlert2 confirmation dialog
            const result = await Swal.fire({
                title: 'Assign Order to Yourself?',
                text: 'This will assign all unallocated splits of this order to you. Are you sure?',
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

            // Make API request to assign all unallocated splits of the order
            const response = await fetch(`/contractor/orders/${orderId}/assign-to-me`, {
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
            
            // Update all status badges in the table to show allocated
            const statusBadges = document.querySelectorAll('#orderSplitsContainer .table tbody tr td:nth-child(2) .badge');
            statusBadges.forEach(badge => {
                if (badge.textContent.trim().toLowerCase() === 'unallocated') {
                badge.className = 'badge bg-info';
                badge.textContent = 'allocated';
                }
            });
            
            // Refresh the order list to reflect changes
            setTimeout(() => {
                loadOrders(currentFilters, 1, false);
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
                // Restore original button content - we need to recreate it
                const unallocatedCount = document.querySelectorAll('#orderSplitsContainer .table tbody tr td:nth-child(2) .badge').length;
                button.innerHTML = `
                <i class="fas fa-user-plus me-1" style="font-size: 10px;"></i>
                Assign Order to Me
                <span class="badge bg-white text-success ms-1 rounded-pill" style="font-size: 9px;">${unallocatedCount}</span>
                `;
            }
            }
        }
        // Update all timer badges on the page
        function updateAllTimers() {
            const timerBadges = document.querySelectorAll('.timer-badge');
            timerBadges.forEach(badge => {
                const orderId = badge.dataset.orderId;
                const createdAt = badge.dataset.createdAt;
                const status = badge.dataset.status;
                const completedAt = badge.dataset.completedAt;
                const timerStartedAt = badge.dataset.timerStartedAt;
                const timerPausedAt = badge.dataset.timerPausedAt;
                const totalPausedSeconds = parseInt(badge.dataset.totalPausedSeconds) || 0;
                
                // Skip updating completed orders (timer is paused)
                if (status === 'completed') {
                    return;
                }
                
                // Skip updating paused orders (they don't change until resumed)
                if (timerPausedAt && timerPausedAt !== '') {
                    return;
                }
                
                const timer = calculateOrderTimer(createdAt, status, completedAt, timerStartedAt, timerPausedAt, totalPausedSeconds);
                const iconClass = timer.isCompleted ? 'fas fa-check' : 
                                  timer.isPaused ? 'fas fa-pause' : 
                                  (timer.isNegative ? 'fas fa-exclamation-triangle' : 'fas fa-clock');
                
                // Check if the timer display has changed to avoid unnecessary DOM updates
                const currentDisplay = badge.textContent.trim();
                if (currentDisplay === timer.display) {
                    return;
                }
                
                // Create tooltip text
                let tooltip = '';
                if (timer.isCompleted) {
                    tooltip = completedAt 
                        ? `Order completed on ${formatDate(completedAt)}` 
                        : 'Order is completed';
                } else if (timer.isPaused) {
                    tooltip = `Timer is paused at ${timer.display.replace(' (Paused)', '')}. Paused on ${formatDate(timerPausedAt)}`;
                } else if (timer.isNegative) {
                    tooltip = `Order is overdue by ${timer.display.substring(1)} (overtime). Created on ${formatDate(createdAt)}`;
                } else {
                    tooltip = `Time remaining: ${timer.display} (12-hour countdown). Order created on ${formatDate(createdAt)}`;
                }
                
                // Update badge class and tooltip
                badge.className = `timer-badge ${timer.class}`;
                badge.setAttribute('data-tooltip', tooltip);
                
                // Update badge content
                badge.innerHTML = `
                    <i class="${iconClass} timer-icon"></i>
                    ${timer.display}
                `;
            });
        }

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
            
            // Update timers every second for real-time countdown
            setInterval(updateAllTimers, 1000); // Update every 1 second
        });
</script>


<script>
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
    
      // Set up new value before flip
      back.textContent = newVal;
    
      // Flip animation
      inner.style.transform = 'rotateX(180deg)';
    
      setTimeout(() => {
        // Reset values after flip
        front.textContent = newVal;
        inner.style.transition = 'none';
        inner.style.transform = 'rotateX(0deg)';
        setTimeout(() => {
          inner.style.transition = 'transform 0.6s ease-in-out';
        }, 20);
      }, 600);
    }
    
    function startTimer(durationSeconds) {
      const container = document.getElementById('flip-timer');
      const digitElements = [];
    
      const formatTime = (s) => {
        const h = Math.floor(s / 3600).toString().padStart(2, '0');
        const m = Math.floor((s % 3600) / 60).toString().padStart(2, '0');
        const sec = (s % 60).toString().padStart(2, '0');
        return h + m + sec;
      };
    
      // Create 6 flip cards (HHMMSS)
      const initial = formatTime(durationSeconds);
      for (let i = 0; i < initial.length; i++) {
        const card = createFlipCard(initial[i]);
        container.appendChild(card);
        digitElements.push(card);
    
        // Add colons
        if (i === 1 || i === 3) {
          const colon = document.createElement('div');
          colon.textContent = ':';
          colon.style.cssText = 'font-size: 24px; line-height: 20px; color: white;';
          container.appendChild(colon);
        }
      }
    
      let current = durationSeconds;
      function update() {
        if (current < 0) return clearInterval(timer);
        const timeStr = formatTime(current);
        for (let i = 0; i < 6; i++) {
          updateFlipCard(digitElements[i], timeStr[i]);
        }
        current--;
      }
    
      update();
      const timer = setInterval(update, 1000);
    }
    
    // Example: 24 hours
    startTimer(24 * 60 * 60);

    // Change Status Modal Functions
    function openChangeStatusModal(orderId, currentStatus) {
        // Set the order ID and current status in the modal
        document.getElementById('modalOrderId').textContent = '#' + orderId;
        
        // Set current status with appropriate styling
        const statusBadge = document.getElementById('modalCurrentStatus');
        statusBadge.textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
        statusBadge.className = 'badge ' + getStatusBadgeClass(currentStatus);
        
        // Reset form
        document.getElementById('newStatus').value = '';
        document.getElementById('statusReason').value = '';
        
        // Store order ID for later use
        document.getElementById('changeStatusModal').setAttribute('data-order-id', orderId);
        
        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('changeStatusModal'));
        modal.show();
    }

    // Helper function to get status badge class
    function getStatusBadgeClass(status) {
        switch(status) {
            case 'completed': return 'bg-success';
            case 'pending': return 'bg-warning text-dark';
            case 'cancelled': return 'bg-danger';
            case 'rejected': return 'bg-danger';
            case 'in-progress': return 'bg-primary';
            case 'unallocated': return 'bg-warning text-dark';
            case 'allocated': return 'bg-info';
            default: return 'bg-secondary';
        }
    }

    async function updateOrderStatus() {
        const modal = document.getElementById('changeStatusModal');
        const orderId = modal.getAttribute('data-order-id');
        const newStatus = document.getElementById('newStatus').value;
        const reason = document.getElementById('statusReason').value;
        
        if (!newStatus) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please select a new status',
                icon: 'warning',
                confirmButtonColor: '#f39c12'
            });
            return;
        }
        // Validate reason if status is not completed
        if (newStatus !== 'completed' && !reason) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please provide a reason for rejecting this order',
                icon: 'warning',
                confirmButtonColor: '#f39c12'
            });
            return;
        }

        // Show SweetAlert2 confirmation dialog
        const result = await Swal.fire({
            title: 'Update Order Status?',
            text: `Are you sure you want to change the status to "${newStatus}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, update status!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        });

        // If user cancels, return early
        if (!result.isConfirmed) {
            return;
        }
        
        // Show SweetAlert2 loading dialog
        Swal.fire({
            title: 'Updating Status...',
            text: 'Please wait while we update the order status.',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        try {
            const response = await fetch(`/contractor/orders/${orderId}/change-status`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    status: newStatus,
                    reason: reason
                })
            });
            
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || 'Failed to update status');
            }
            
            const result = await response.json();
            
            if (result.success) {
                // Close loading dialog and show success
                await Swal.fire({
                    title: 'Success!',
                    text: result.message || 'Status updated successfully!',
                    icon: 'success',
                    confirmButtonColor: '#28a745',
                    timer: 3000,
                    timerProgressBar: true
                });
                
                // Hide modal
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
                
                // Refresh the order details if currently viewing this order
                const currentOrderId = document.querySelector('[data-order-id="' + orderId + '"]');
                if (currentOrderId) {
                    viewOrderSplits(orderId);
                    // if order status is not completed, then close the canvas
                    if (newStatus !== 'completed') {
                        const offcanvas = document.querySelector('.offcanvas.show');
                        if (offcanvas) {
                            const offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvas);
                            offcanvasInstance.hide();
                        }
                    }
                }
                
                // Refresh the orders list to reflect changes
                setTimeout(() => {
                    loadOrders(currentFilters, 1, false);
                }, 1000);
                
            } else {
                throw new Error(result.message || 'Failed to update status');
            }
            
        } catch (error) {
            console.error('Error updating status:', error);
            
            // Close loading dialog and show error
            await Swal.fire({
                title: 'Error!',
                text: error.message || 'Failed to update status. Please try again.',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    }

    // Show notification function
    function showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add to body
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification && notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }
</script>
@endpush