@extends('contractor.layouts.app')

@section('title', 'Shared Orders')

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
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    /* Enhanced stat boxes hover effects */
    .order-card .col-6>div {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .order-card .col-6>div:hover {
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

    /* Shared order styling */
    .shared-order-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: linear-gradient(135deg, #ff6b6b, #feca57);
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.7rem;
        font-weight: bold;
        z-index: 10;
    }
</style>

@endpush

@section('content')
<section class="py-3">
    <!-- Page Header with Shared Orders indicator -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="fa-solid fa-share-nodes text-warning me-2"></i>
                Shared Orders
            </h4>
            <p class="text-white mb-0">Orders shared with you by other contractors</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('contractor.orders') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i>
                Back to My Orders
            </a>
        </div>
    </div>

    <!-- Advanced Search Filter UI -->
    <div class="card p-3 mb-4">
        <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#filter_1"
            role="button" aria-expanded="false" aria-controls="filter_1">
            <div>
                <h6 class="mb-0">
                    <i class="fa-solid fa-filter me-2"></i>
                    Advanced Search & Filter
                </h6>
                <small>Click here to open advance search for shared orders</small>
            </div>
            <div>
                <i class="fa-solid fa-chevron-down"></i>
            </div>
        </div>

        <div class="row collapse" id="filter_1">
            <form id="filterForm">
                <div class="row g-2 mt-2">
                    <div class="col-md-2">
                        <label for="orderIdFilter" class="form-label">Order ID</label>
                        <input type="text" class="form-control" id="orderIdFilter" name="order_id" placeholder="Enter Order ID">
                    </div>
                    <div class="col-md-2">
                        <label for="statusFilter" class="form-label">Status</label>
                        <select class="form-select" id="statusFilter" name="status">
                            <option value="">All Status</option>
                            <option value="completed">Completed</option>
                            <option value="in-progress">In Progress</option>
                            <option value="pending">Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="minInboxesFilter" class="form-label">Min Inboxes</label>
                        <input type="number" class="form-control" id="minInboxesFilter" name="min_inboxes" placeholder="Min">
                    </div>
                    <div class="col-md-2">
                        <label for="maxInboxesFilter" class="form-label">Max Inboxes</label>
                        <input type="number" class="form-control" id="maxInboxesFilter" name="max_inboxes" placeholder="Max">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fa-solid fa-search me-1"></i>
                            Search
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="resetFilters">
                            <i class="fa-solid fa-refresh me-1"></i>
                            Reset
                        </button>
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
            <p class="mt-2 mb-0">Loading shared orders...</p>
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
            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalOrders">0</span>
            shared orders
        </div>
    </div>

</section>

<!-- Order Details Offcanvas -->
<div class="offcanvas offcanvas-end" style="width: 100%;" tabindex="-1" id="order-splits-view"
    aria-labelledby="order-splits-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
    <div class="offcanvas-header border-0 pb-0" style="background-color: transparent">
        <h5 class="offcanvas-title" id="order-splits-viewLabel">Shared Order Details</h5>
        <button type="button" class="bg-transparent border-0" data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="fas fa-times fs-5"></i>
        </button>
    </div>
    <div class="offcanvas-body pt-2">
        <div id="orderSplitsContainer">
            <!-- Dynamic content will be loaded here -->
            <div id="splitsLoadingState" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-light">Loading order details...</p>
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
                    <label class="form-label">Order ID: <span id="modalOrderId" class="fw-bold"></span></label>
                    <br>
                    <label class="form-label">Current Status: <span id="modalCurrentStatus" class="badge"></span></label>
                </div>
                
                <div class="mb-3">
                    <label for="newStatus" class="form-label">New Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="newStatus" required>
                        <option value="">Select new status</option>
                        <option value="in-progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="pending">Pending</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="statusReason" class="form-label">Reason for Change</label>
                    <textarea class="form-control" id="statusReason" rows="3" placeholder="Optional: Explain why you're changing the status..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusChange" onclick="updateOrderStatus()">
                    Update Status
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Avatar generation functions
    function getInitials(name) {
        if (!name) return 'N/A';
        const words = name.trim().split(' ');
        if (words.length === 1) {
            return words[0].charAt(0).toUpperCase();
        }
        return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
    }

    function getAvatarColor(name) {
        if (!name) return '#6c757d';
        const colors = [
            '#e74c3c', '#3498db', '#2ecc71', '#f39c12', '#9b59b6',
            '#1abc9c', '#e67e22', '#34495e', '#e91e63', '#00bcd4'
        ];
        const hash = name.split('').reduce((a, b) => {
            a = ((a << 5) - a) + b.charCodeAt(0);
            return a & a;
        }, 0);
        return colors[Math.abs(hash) % colors.length];
    }

    function createAvatar(name, image = null, size = 60) {
        if (image && image !== '' && !image.includes('pexels')) {
            return `<img src="${image}" width="${size}" height="${size}" style="border-radius: 50px; object-fit: cover;" alt="${name}" onerror="this.outerHTML = createAvatar('${name}', null, ${size})">`;
        }
        
        const initials = getInitials(name);
        const backgroundColor = getAvatarColor(name);
        
        return `<div style="width: ${size}px; height: ${size}px; border-radius: 50%; background-color: ${backgroundColor}; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: ${Math.floor(size * 0.35)}px;">${initials}</div>`;
    }

    let orders = [];
    let currentFilters = {};
    let currentPage = 1;
    let hasMorePages = false;
    let totalOrders = 0;
    let isLoading = false;

    // Load orders data (modified for shared orders)
    async function loadOrders(filters = {}, page = 1, append = false) {
        try {
            if (isLoading) return; // Prevent concurrent requests
            
            isLoading = true;
            
            if (!append) {
                showLoading();
                orders = [];
                currentPage = 1;
            } else {
                showLoadMoreSpinner(true);
            }

            // Update current filters
            currentFilters = filters;

            const queryParams = new URLSearchParams({
                page: page,
                per_page: 12,
                ...filters
            });

            const response = await fetch(`{{ route('contractor.shared-orders.data') }}?${queryParams}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            
            if (data.success) {
                if (append) {
                    orders = orders.concat(data.data);
                } else {
                    orders = data.data;
                }
                
                // Update pagination info
                if (data.pagination) {
                    currentPage = data.pagination.current_page;
                    hasMorePages = data.pagination.has_more_pages;
                    totalOrders = data.pagination.total;
                }
                
                renderOrders(append);
                updatePaginationInfo();
                updateLoadMoreButton();
            } else {
                showError(data.message || 'Failed to load shared orders');
            }
            
        } catch (error) {
            console.error('Error loading orders:', error);
            showError('An error occurred while loading shared orders. Please try again.');
        } finally {
            isLoading = false;
            hideLoading();
            showLoadMoreSpinner(false);
        }
    }

    // Show loading state        
    function showLoading() {
        const container = document.getElementById('ordersContainer');
        const loadingElement = document.getElementById('loadingState');
        
        if (container && loadingElement) {
            container.style.display = 'grid';
            container.style.gridTemplateColumns = '1fr';
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
            return;
        }
        
        // Keep grid layout but show error spanning full width
        container.style.display = 'grid';
        container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
        container.style.gap = '1rem';
        
        container.innerHTML = `
            <div style="grid-column: 1 / -1;" class="empty-state">
                <i class="fas fa-exclamation-triangle text-warning"></i>
                <h5>Error Loading Shared Orders</h5>
                <p>${message}</p>
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
            return;
        }
          
        if (orders.length === 0 && !append) {
            container.innerHTML = `
                <div style="grid-column: 1 / -1;" class="empty-state">
                    <i class="fas fa-share-nodes text-warning fs-1 mb-3"></i>
                    <h5>No Shared Orders Found</h5>
                    <p>You don't have any shared orders at the moment.</p>
                    <div class="mt-3">
                        <button class="btn btn-outline-primary" onclick="resetFilters()">Clear Filters</button>
                    </div>
                </div>
            `;
            return;
        }
        
        // Reset container to grid layout for orders
        container.style.display = 'grid';
        container.style.gridTemplateColumns = 'repeat(auto-fill, minmax(320px, 1fr))';
        container.style.gap = '1rem';

        if (append) {
            const newOrdersStartIndex = orders.length - currentFilters.per_page || 12;
            const newOrders = orders.slice(Math.max(0, newOrdersStartIndex));
            const newOrdersHtml = newOrders.map(order => createOrderCard(order)).join('');
            container.insertAdjacentHTML('beforeend', newOrdersHtml);
        } else {
            const ordersHtml = orders.map(order => createOrderCard(order)).join('');
            container.innerHTML = ordersHtml;
        }
    }

    // Create order card HTML (modified for shared orders)
    function createOrderCard(order) {
        // Calculate splits table content
        const splitsTableContent = order.splits && order.splits.length > 0 
        ? order.splits.map((split, index) => `
            <tr>
                <td style="font-size: 10px; padding: 5px !important;">${split.id}</td>
                <td style="font-size: 10px; padding: 5px !important;">
                    <span class="badge bg-${split.status === 'completed' ? 'success' : split.status === 'in-progress' ? 'primary' : 'warning'}" style="font-size: 9px;">
                        ${split.status}
                    </span>
                </td>
                <td style="font-size: 10px; padding: 5px !important;">${split.inboxes_per_domain}</td>
                <td style="font-size: 10px; padding: 5px !important;">${split.domains_count}</td>
                <td style="font-size: 10px; padding: 5px !important;">
                    <i class="fa-regular fa-eye" style="cursor: pointer;" onclick="event.stopPropagation(); window.open('/contractor/orders/${split.order_panel_id}/split/view', '_blank')" title="View Split"></i>
                    <i class="fa-solid fa-download" style="cursor: pointer; color: #28a745;" onclick="event.stopPropagation(); window.open('/contractor/orders/split/${split.id}/export-csv-domains', '_blank')" title="Download CSV"></i>
                    ${split.customized_note ? `
                        <i class="fa-solid fa-sticky-note" style="cursor: pointer; color: #ffc107;" onclick="event.stopPropagation(); showCustomizedNoteModal('${split.customized_note.replace(/'/g, '&apos;').replace(/"/g, '&quot;')}')" title="View Customized Note"></i>
                    ` : ''}
                </td>
            </tr>
        `).join('')
        : `<tr><td colspan="6" style="font-size: 10px; padding: 10px; text-align: center;">No splits available</td></tr>`;

        return `
        <div class="anim_card rounded-2 position-relative">
            <!-- Shared Order Badge -->
            
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
                    <h6>Shared Order #${order.order_id}</h6>
                    <div>
                        ${order.status_manage_by_admin}
                        ${createTimerBadge(order)}
                    </div>
                </div>

                <div class="mt-3 d-flex gap-3 align-items-center">
                    <div>
                        ${createAvatar(order.customer_name, order.customer_image, 60)}
                    </div>

                    <div class="d-flex flex-column gap-1">
                    <span class="fw-bold">${order.customer_name}</span>
                    <small>
                        Total Inboxes: ${order.total_inboxes} | ${order.splits_count} Split${order.splits_count === 1 ? '' : 's'}
                    </small>
                    ${order.helpers_names && order.helpers_names.length > 0 ? `
                        <small class="text-warning">
                            <i class="fa-solid fa-users me-1"></i>
                            Shared with: ${order.helpers_names.join(', ')}
                        </small>
                    ` : ''}
                    </div>
                </div>

                <small class="ms-2">${formatDate(order.created_at)}</small>

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
            case 'in-progress': return 'bg-primary';
            case 'pending': return 'bg-warning';
            case 'expired': return 'bg-danger';
            default: return 'bg-secondary';
        }
    }
    
    // Helper function to format date
    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        try {
            return new Date(dateString).toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric'
            });
        } catch (error) {
            return 'N/A';
        }
    }

    // Calculate timer for order
    function calculateOrderTimer(createdAt, status, completedAt = null, timerStartedAt = null, timerPausedAt = null, totalPausedSeconds = 0) {
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
                return {
                    display: '-' + formatTimeDuration(timeDiffAtPause) + label,
                    isNegative: true,
                    isPaused: true,
                    class: timerClass
                };
            } else {
                return {
                    display: formatTimeDuration(-timeDiffAtPause) + label,
                    isNegative: false,
                    isPaused: true,
                    class: timerClass
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
            iconClass = 'fas fa-exclamation-triangle';
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
                data-tooltip="${tooltip}"
                title="${tooltip}">
                <i class="${iconClass} timer-icon"></i>
                ${timer.display}
            </span>
        `;
    }

    // View order splits (same as original)
    async function viewOrderSplits(orderId) {
        try {
            // Show loading in offcanvas
            const container = document.getElementById('orderSplitsContainer');
            if (container) {
                container.innerHTML = `
                    <div id="splitsLoadingState" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-light">Loading order details...</p>
                    </div>
                `;
            }
              
            // Show offcanvas with proper cleanup
            const offcanvasElement = document.getElementById('order-splits-view');
            const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
            
            // Add event listeners for proper cleanup
            offcanvasElement.addEventListener('hidden.bs.offcanvas', function () {
                // Clean up any backdrop issues
                cleanupOffcanvasBackdrop();
            }, { once: true });
            
            offcanvas.show();
            
            // Fetch order splits
            const response = await fetch(`/contractor/orders/${orderId}/splits`, {
                method: 'GET',
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
                        <i class="fas fa-exclamation-triangle text-warning fs-3 mb-3"></i>
                        <h5>Error Loading Order Details</h5>
                        <p>Unable to load order details. Please try again.</p>
                        <button class="btn btn-primary" onclick="viewOrderSplits(${orderId})">Retry</button>
                    </div>
                `;
            }
        }
    }

    // Include all other functions from the original file...
    // (renderOrderSplits, toggle shared functionality, etc. - same as original)

    // Shared toggle functionality (same as contractor orders)
    $(document).on('click', '.toggle-shared', function(e) {
        e.preventDefault();
        const orderId = $(this).data('order-id');
        
        Swal.fire({
            title: 'Toggle Shared Status',
            text: "Are you sure you want to change the shared status of this order?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, toggle it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/contractor/orders/${orderId}/toggle-shared`,
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            
                            // Refresh the orders list to show updated status
                            loadOrders(currentFilters, 1, false);
                            
                            // If we're in the offcanvas, refresh that view too
                            const offcanvas = document.getElementById('order-splits-view');
                            if (offcanvas && offcanvas.classList.contains('show')) {
                                // Reload the offcanvas content
                                viewOrderSplits(orderId);
                            }
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while updating shared status.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage
                        });
                    }
                });
            }
        });
    });

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
        // Only remove offcanvas-related backdrop elements, not modal backdrops
        const offcanvasBackdrops = document.querySelectorAll('.offcanvas-backdrop');
        offcanvasBackdrops.forEach(backdrop => {
            backdrop.remove();
        });
        
        // Only remove fade elements that are specifically offcanvas-related
        const fadeElements = document.querySelectorAll('.fade:not(.modal):not(.modal *)');
        fadeElements.forEach(element => {
            // Only remove if it's not a modal or inside a modal
            if (!element.closest('.modal') && !element.classList.contains('modal')) {
                element.remove();
            }
        });
        
        // Reset body styles only if no modals are currently open
        const activeModals = document.querySelectorAll('.modal.show');
        if (activeModals.length === 0) {
            document.body.classList.remove('offcanvas-open');
            // Only remove modal-open class if no modals are actually showing
            const showingModals = document.querySelectorAll('.modal.show');
            if (showingModals.length === 0) {
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }
        }
    }

    // Add global event listener for offcanvas cleanup
    document.addEventListener('click', function(e) {
        // If clicking outside offcanvas or on close button, ensure cleanup
        if (e.target.matches('[data-bs-dismiss="offcanvas"]') || 
            e.target.closest('[data-bs-dismiss="offcanvas"]')) {
            setTimeout(cleanupOffcanvasBackdrop, 100);
        }
    });

    // Cleanup on page focus (in case of any lingering issues)
    window.addEventListener('focus', cleanupOffcanvasBackdrop);

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
                              timer.isNegative ? 'fas fa-exclamation-triangle' : 'fas fa-clock';
            
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
            badge.title = tooltip;
            
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
        
        // Load shared orders immediately
        loadOrders();
        
        // Update timers every second for real-time countdown
        setInterval(updateAllTimers, 1000); // Update every 1 second
    });

    // Render order splits function
    function renderOrderSplits(data) {
        const container = document.getElementById('orderSplitsContainer');
        
        if (!data.splits || data.splits.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-inbox text-white fs-3 mb-3"></i>
                    <h5>No Panel Break Found</h5>
                    <p>This order doesn't have any panel breaks yet.</p>
                </div>
            `;
            return;
        }
        
        const orderInfo = data.order;
        const reorderInfo = data.reorder_info;
        const splits = data.splits;

        // Update offcanvas title
        const offcanvasTitle = document.getElementById('order-splits-viewLabel');
        if (offcanvasTitle && orderInfo) {
            offcanvasTitle.innerHTML = `Shared Order Details #${orderInfo.id}`;
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
                                            style="font-size: 13px;display:none;">
                                        <i class="fas fa-user-plus me-1" style="font-size: 12px;"></i>
                                        Assign Order to Me
                                        <span class="badge bg-white text-success ms-1 rounded-pill" style="font-size: 9px;">${unallocatedSplits?.length}</span>
                                    </button>
                                `;
                            } else {
                                return `
                                    <span class="bg-success rounded-1 px-3 py-2 text-white" style="font-size: 13px;display:none;">
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

                        <!-- Shared Status Toggle Button -->
                        <button class="btn btn-sm ${orderInfo.is_shared ? 'btn-outline-warning' : 'btn-outline-success'} toggle-shared px-3 py-2" 
                                data-order-id="${orderInfo.id}" 
                                title="${orderInfo.is_shared ? 'Unshare Order' : 'Share Order'}"
                                style="font-size: 13px; display:none;">
                            <i class="fa-solid ${orderInfo.is_shared ? 'fa-share-from-square' : 'fa-share-nodes'} me-1" style="font-size: 12px;"></i>
                            ${orderInfo.is_shared ? 'Unshare' : 'Share'}
                        </button>
                    </div>
                </div>
                ${orderInfo.is_shared ? `
                    <div class="alert alert-warning py-2 mb-3" style="font-size: 12px;">
                        <i class="fa-solid fa-users me-2"></i>
                        This order is currently shared with other contractors
                        ${orderInfo.helpers_names && orderInfo.helpers_names.length > 0 ? ` (${orderInfo.helpers_names.join(', ')})` : ''}
                    </div>
                ` : ''}
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
                            <th scope="col">Customized Type</th>
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
                                <td>
                                    ${split.email_count > 0 ? `
                                        <span class="badge bg-success" style="font-size: 10px;">
                                            <i class="fa-solid fa-check me-1"></i>Custom
                                        </span>
                                    ` : `
                                        <span class="badge bg-secondary" style="font-size: 10px;">
                                            <i class="fa-solid fa-cog me-1"></i>Default
                                        </span>
                                    `}
                                </td>
                                <td>${calculateSplitTime(split) || 'N/A'}</td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="/contractor/orders/${split.order_panel_id}/split/view" style="font-size: 10px" class="btn btn-sm btn-outline-primary me-2" title="View Split">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="/contractor/orders/split/${split.id}/export-csv-domains" style="font-size: 10px" class="btn btn-sm btn-success" title="Download CSV with ${split.domains_count || 0} domains" target="_blank">
                                            <i class="fas fa-download"></i> CSV
                                        </a>
                                        ${split.customized_note ? `
                                            <button type="button" class="btn btn-sm btn-warning" style="font-size: 10px;" onclick="showCustomizedNoteModal('${split.customized_note.replace(/'/g, '&apos;').replace(/"/g, '&quot;')}')" title="View Customized Note">
                                                <i class="fa-solid fa-sticky-note"></i> Note
                                            </button>
                                        ` : ''}
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
        
        container.innerHTML = splitsHtml;
    }

    // Function to show customized note modal
    function showCustomizedNoteModal(note) {
        const noteContent = document.getElementById('customizedNoteContent');
        if (noteContent) {
            noteContent.innerHTML = note || 'No note available';
            const modal = new bootstrap.Modal(document.getElementById('customizedNoteModal'));
            modal.show();
        }
    }

    // Assign order to me function
    async function assignOrderToMe(orderId) {
        try {
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

            if (!result.isConfirmed) {
                return;
            }

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
            
            await Swal.fire({
                title: 'Success!',
                text: data.message || 'Order assigned successfully!',
                icon: 'success',
                confirmButtonColor: '#28a745',
                timer: 3000,
                timerProgressBar: true
            });
            
            // Refresh views
            loadOrders(currentFilters, 1, false);
            
        } catch (error) {
            console.error('Error assigning order:', error);
            await Swal.fire({
                title: 'Error!',
                text: error.message || 'Failed to assign order. Please try again.',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    }

    // Open change status modal
    function openChangeStatusModal(orderId, currentStatus) {
        try {
            // Ensure DOM is ready and elements exist
            const modalOrderIdElement = document.getElementById('modalOrderId');
            const modalCurrentStatusElement = document.getElementById('modalCurrentStatus');
            const newStatusElement = document.getElementById('newStatus');
            const statusReasonElement = document.getElementById('statusReason');
            const changeStatusModalElement = document.getElementById('changeStatusModal');
            
            if (!modalOrderIdElement || !modalCurrentStatusElement || !newStatusElement || !statusReasonElement || !changeStatusModalElement) {
                console.error('Modal elements not found:', {
                    modalOrderId: !!modalOrderIdElement,
                    modalCurrentStatus: !!modalCurrentStatusElement,
                    newStatus: !!newStatusElement,
                    statusReason: !!statusReasonElement,
                    changeStatusModal: !!changeStatusModalElement
                });
                return;
            }
            
            // Set the order ID and current status in the modal
            modalOrderIdElement.textContent = '#' + orderId;
            
            // Set current status with appropriate styling
            modalCurrentStatusElement.textContent = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);
            modalCurrentStatusElement.className = 'badge ' + getStatusBadgeClass(currentStatus);
            
            // Reset form
            newStatusElement.value = '';
            statusReasonElement.value = '';
            
            // Store order ID for later use
            changeStatusModalElement.setAttribute('data-order-id', orderId);
            
            // Show the modal
            const modal = new bootstrap.Modal(changeStatusModalElement);
            modal.show();
        } catch (error) {
            console.error('Error opening change status modal:', error);
        }
    }

    // Update order status
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

        if (newStatus !== 'completed' && !reason) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please provide a reason for changing this status',
                icon: 'warning',
                confirmButtonColor: '#f39c12'
            });
            return;
        }

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

        if (!result.isConfirmed) {
            return;
        }
        
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
                await Swal.fire({
                    title: 'Success!',
                    text: result.message || 'Status updated successfully!',
                    icon: 'success',
                    confirmButtonColor: '#28a745',
                    timer: 3000,
                    timerProgressBar: true
                });
                
                const modalInstance = bootstrap.Modal.getInstance(modal);
                modalInstance.hide();
                
                loadOrders(currentFilters, 1, false);
                
            } else {
                throw new Error(result.message || 'Failed to update status');
            }
            
        } catch (error) {
            console.error('Error updating status:', error);
            
            await Swal.fire({
                title: 'Error!',
                text: error.message || 'Failed to update status. Please try again.',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    }

    // Split time calculator functions
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
            end = new Date();
        } else {
            return "00:00:00";
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
</script>

<!-- Include the rest of the necessary JavaScript functions -->
<!-- This would include renderOrderSplits, assignOrderToMe, updateOrderStatus, etc. from the original file -->

@endpush

<!-- Customized Note Modal (same as original) -->
<div class="modal fade" id="customizedNoteModal" tabindex="-1" aria-labelledby="customizedNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="background: #1d2239;">
            <div class="modal-header border-0">
                <h5 class="modal-title text-light" id="customizedNoteModalLabel">
                    <i class="fas fa-sticky-note text-warning me-2"></i>
                    Customized Note
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body border-0">
                <div class="bg-dark rounded p-3 border-start border-warning border-4">
                    <div id="customizedNoteContent" class="text-light" style="white-space: pre-wrap; font-family: 'Courier New', monospace; font-size: 14px; line-height: 1.6;">
                        <!-- Note content will be inserted here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>
                    Close
                </button>
            </div>
        </div>
    </div>
</div>