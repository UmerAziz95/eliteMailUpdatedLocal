@extends('admin.layouts.app')

@section('title', 'Shared Order Requests')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
<style>
    .avatar {
        position: relative;
        block-size: 2.5rem;
        cursor: pointer;
        inline-size: 2.5rem;
    }

    .avatar .avatar-initial {
        position: absolute;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--second-primary);
        font-size: 1.5rem;
        font-weight: 500;
        inset: 0;
        text-transform: uppercase;
    }

    .nav-tabs .nav-link {
        color: var(--extra-light);
        border: none
    }

    .nav-tabs .nav-link:hover {
        color: var(--white-color);
        border: none
    }

    .nav-tabs .nav-link.active {
        background-color: var(--second-primary);
        color: #fff;
        border: none;
        border-radius: 6px
    }

    .dt-loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 1rem;
        border-radius: 4px;
    }

    .loading {
        position: relative;
        pointer-events: none;
        opacity: 0.6;
    }

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
    }

    .shared-order-card {
        border-left: 4px solid var(--warning);
        transition: all 0.3s ease;
    }

    .shared-order-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .helper-badge {
        background: linear-gradient(45deg, #ffc107, #ffeb3b);
        color: #000;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 12px;
        margin: 2px;
        display: inline-block;
    }

    .shared-order-icon {
        color: var(--warning);
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }

    /* SweetAlert2 z-index fix for offcanvas */
    .swal-over-canvas {
        z-index: 1060 !important;
    }
    
    .swal2-container.swal-over-canvas {
        z-index: 1060 !important;
    }
</style>
@endpush

@section('content')
<section class="py-3">
    <!-- Statistics Cards -->
    <div class="row gy-4 mb-4">
        <div class="col-sm-6 col-xl-4">
            <div class="card p-2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text text-light">Total Requests</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2 text-warning" id="totalRequestsCount">0</h4>
                            </div>
                        </div>
                        <div class="avatar bg-label-warning">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="fa-solid fa-share-nodes fs-4"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card p-2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text text-light">Pending</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2 text-warning" id="pendingRequestsCount">0</h4>
                            </div>
                        </div>
                        <div class="avatar bg-label-warning">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-clock fs-4"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-4">
            <div class="card p-2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text text-light">Confirmed</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2 text-success" id="confirmedRequestsCount">0</h4>
                            </div>
                        </div>
                        <div class="avatar bg-label-success">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="fa-solid fa-check-circle fs-4"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3 d-none">
            <div class="card p-2">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text text-light">Completed Shared</span>
                            <div class="d-flex align-items-end mt-2">
                                <h4 class="mb-0 me-2 text-success">{{ $completedSharedOrders }}</h4>
                            </div>
                        </div>
                        <div class="avatar bg-label-success">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-check fs-4"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card mb-4 d-none">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h5 class="mb-0">
                <i class="fa-solid fa-share-nodes text-warning me-2 shared-order-icon"></i>
                Shared Order Management
            </h5>
            <button class="btn btn-sm btn-outline-primary" onclick="refreshSharedOrders()">
                <i class="fa-solid fa-refresh me-1"></i> Refresh
            </button>
        </div>
        <div class="card-body">
            

            <div class="row g-3 d-none">
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Filter by Status</label>
                    <select id="statusFilter" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in-progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="reject">Rejected</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="planFilter" class="form-label">Filter by Plan</label>
                    <select id="planFilter" class="form-select form-select-sm">
                        <option value="">All Plans</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="searchInput" class="form-label">Search Orders</label>
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search by Order ID, Customer...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" onclick="applyFilters()">
                            <i class="fa-solid fa-filter me-1"></i> Apply
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="clearFilters()">
                            <i class="fa-solid fa-eraser me-1"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-3" id="sharedOrderTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests-tab-pane" type="button" role="tab" aria-controls="requests-tab-pane" aria-selected="true">
                <i class="fa-solid fa-clock text-warning me-2"></i>
                Requests
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="confirmed-tab" data-bs-toggle="tab" data-bs-target="#confirmed-tab-pane" type="button" role="tab" aria-controls="confirmed-tab-pane" aria-selected="false">
                <i class="fa-solid fa-check-circle text-success me-2"></i>
                Confirmed
            </button>
        </li>
    </ul>
    <!-- Tab Content -->
    <div class="tab-content" id="sharedOrderTabContent">
        <!-- Requests Tab -->
        <div class="tab-pane fade show active" id="requests-tab-pane" role="tabpanel" aria-labelledby="requests-tab" tabindex="0">
            <div id="sharedOrdersContainer">
                <!-- Shared orders will be loaded here via JavaScript -->
                <div class="text-center p-4">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-white">Loading shared order requests...</p>
                </div>
            </div>
        </div>

        <!-- Confirmed Tab -->
        <div class="tab-pane fade" id="confirmed-tab-pane" role="tabpanel" aria-labelledby="confirmed-tab" tabindex="0">
            <div id="confirmedOrdersContainer">
                <!-- Confirmed orders will be loaded here via JavaScript -->
                <div class="text-center p-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-white">Loading confirmed orders...</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Shared Order Detail Modal -->
<div class="modal fade" id="sharedOrderDetailModal" tabindex="-1" aria-labelledby="sharedOrderDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sharedOrderDetailModalLabel">
                    <i class="fa-solid fa-share-nodes text-warning me-2"></i>
                    Shared Order Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="sharedOrderDetailContent">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Assign Contractors Modal -->
<div class="modal fade" id="assignContractorsModal" tabindex="-1" aria-labelledby="assignContractorsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="assignContractorsModalLabel">
                    <i class="fa-solid fa-users text-primary me-2"></i>
                    Add Helpers to Shared Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="assignContractorsForm">
                    <input type="hidden" id="assignOrderId" name="order_id">
                    <div class="mb-3">
                        <label for="contractors" class="form-label">Select Contractors (Helpers)</label>
                        <select id="contractors" name="contractors[]" class="form-select" multiple required>
                            <!-- Contractors will be loaded dynamically -->
                        </select>
                        <div class="form-text">Hold Ctrl/Cmd to select multiple contractors</div>
                        <div id="currentAssignments" class="mt-2" style="display: none;">
                            <small class="text-info">
                                <i class="fa-solid fa-info-circle me-1"></i>
                                <span id="currentAssignmentsText"></span>
                            </small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="assignContractors()">
                    <i class="fa-solid fa-check me-1"></i> Assign Helpers
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    // Global variable to store orders data
    let sharedOrdersData = [];
    let currentTab = 'requests'; // Track current tab

    $(document).ready(function() {
        // Initialize Select2 for contractors dropdown
        $('#contractors').select2({
            theme: 'bootstrap-5',
            width: '100%',
            placeholder: 'Select contractors...',
            allowClear: true,
            closeOnSelect: false
        });
        
        // Handle tab switching
        $('#sharedOrderTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            const tabId = e.target.getAttribute('data-bs-target');
            const newTab = tabId === '#requests-tab-pane' ? 'requests' : 'confirmed';
            
            // Only reload if tab actually changed
            if (currentTab !== newTab) {
                currentTab = newTab;
                loadSharedOrders();
            }
        });
        
        // Load data and statistics together
        loadAllData();
        loadContractors();
    });

    function loadAllData() {
        // Load both tabs data to get accurate counts
        let pendingCount = 0;
        let confirmedCount = 0;
        let requestsData = null;
        let confirmedData = null;
        let requestsCompleted = false;
        let confirmedCompleted = false;

        // Load requests data
        $.ajax({
            url: '{{ route("admin.orders.shared.data") }}',
            method: 'GET',
            data: { tab: 'requests' },
            success: function(response) {
                console.log('Requests tab response:', response);
                pendingCount = response.data ? response.data.length : 0;
                requestsData = response;
                requestsCompleted = true;
                
                // Render if this is the current tab
                if (currentTab === 'requests') {
                    renderSharedOrders(response);
                }
                
                // Update statistics when both calls complete
                if (confirmedCompleted) {
                    updateStatistics({
                        total: pendingCount + confirmedCount,
                        pending: pendingCount,
                        confirmed: confirmedCount
                    });
                }
            },
            error: function(xhr) {
                console.error('Error loading requests data:', xhr);
                requestsCompleted = true;
                if (currentTab === 'requests') {
                    $('#sharedOrdersContainer').html('<div class="alert alert-danger">Error loading requests data. Please try again.</div>');
                }
                if (confirmedCompleted) {
                    updateStatistics({ total: confirmedCount, pending: 0, confirmed: confirmedCount });
                }
            }
        });

        // Load confirmed data
        $.ajax({
            url: '{{ route("admin.orders.shared.data") }}',
            method: 'GET',
            data: { tab: 'confirmed' },
            success: function(response) {
                console.log('Confirmed tab response:', response);
                confirmedCount = response.data ? response.data.length : 0;
                confirmedData = response;
                confirmedCompleted = true;
                
                // Render if this is the current tab
                if (currentTab === 'confirmed') {
                    renderSharedOrders(response);
                }
                
                // Update statistics when both calls complete
                if (requestsCompleted) {
                    updateStatistics({
                        total: pendingCount + confirmedCount,
                        pending: pendingCount,
                        confirmed: confirmedCount
                    });
                }
            },
            error: function(xhr) {
                console.error('Error loading confirmed data:', xhr);
                confirmedCompleted = true;
                if (currentTab === 'confirmed') {
                    $('#confirmedOrdersContainer').html('<div class="alert alert-danger">Error loading confirmed data. Please try again.</div>');
                }
                if (requestsCompleted) {
                    updateStatistics({ total: pendingCount, pending: pendingCount, confirmed: 0 });
                }
            }
        });
    }

    function updateStatistics(stats) {
        $('#totalRequestsCount').text(stats.total || 0);
        $('#pendingRequestsCount').text(stats.pending || 0);
        $('#confirmedRequestsCount').text(stats.confirmed || 0);
    }

    function loadSharedOrders() {
        const container = currentTab === 'requests' ? $('#sharedOrdersContainer') : $('#confirmedOrdersContainer');
        const spinnerColor = currentTab === 'requests' ? 'text-warning' : 'text-success';
        
        // Show loading
        container.html(`
            <div class="text-center p-4">
                <div class="spinner-border ${spinnerColor}" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-white">Loading ${currentTab === 'requests' ? 'shared order requests' : 'confirmed orders'}...</p>
            </div>
        `);

        // Get filter values
        const filters = {
            status: $('#statusFilter').val() || '',
            plan_id: $('#planFilter').val() || '',
            search: $('#searchInput').val() || '',
            tab: currentTab // Send current tab to backend
        };

        $.ajax({
            url: '{{ route("admin.orders.shared.data") }}',
            method: 'GET',
            data: filters,
            success: function(response) {
                console.log(`Loading ${currentTab} tab data:`, response);
                
                // Debug information
                if (response.debug) {
                    console.log('Debug info:', response.debug);
                }
                
                if (response.success !== false) {
                    renderSharedOrders(response);
                } else {
                    container.html('<div class="alert alert-danger">Error loading shared orders.</div>');
                }
            },
            error: function(xhr) {
                console.error(`Error loading ${currentTab} orders:`, xhr);
                if (xhr.status === 401 || xhr.status === 403) {
                    container.html('<div class="alert alert-warning">Please login to view shared orders. <a href="/login">Click here to login</a></div>');
                } else {
                    container.html(`<div class="alert alert-danger">Error loading ${currentTab} orders. Please try again.</div>`);
                }
            }
        });
    }

    function renderSharedOrders(data) {
        const container = currentTab === 'requests' ? $('#sharedOrdersContainer') : $('#confirmedOrdersContainer');
        const orders = data.data || data;
        
        console.log(`Rendering ${currentTab} orders:`, orders);
        
        // Store orders data globally for later use
        sharedOrdersData = orders || [];
        
        container.empty();
        
        if (orders && orders.length > 0) {
            console.log(`Found ${orders.length} orders for ${currentTab} tab`);
            orders.forEach(order => {
                const helpersCount = order.helpers_ids ? order.helpers_ids.length : 0;
                const helpersHtml = order.helpers_names && order.helpers_names.length > 0 
                    ? order.helpers_names.map(name => `<span class="helper-badge">${name}</span>`).join('')
                    : `<span class="badge bg-secondary">No helpers assigned</span>`;
                
                const orderHtml = `
                    <div class="card mb-3 shared-order-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fa-solid fa-share-nodes text-warning me-2 shared-order-icon"></i>
                                        <h6 class="card-title mb-0">Order #${order.id}</h6>
                                        <span class="badge bg-${getStatusColor(order.status_manage_by_admin)} ms-2">
                                            ${order.status_manage_by_admin || 'pending'}
                                        </span>
                                    </div>
                                    
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa-solid fa-user text-info me-2"></i>
                                                <div>
                                                    <strong>${order.user ? order.user.name : 'N/A'}</strong>
                                                    <br><small class="text-white">${order.user ? order.user.email : 'N/A'}</small>
                                                </div>
                                            </div>

                                            ${order.assigned_to ? `
                                                <div class="d-flex align-items-center mb-2">
                                                    <i class="fa-solid fa-user-tie text-info me-2"></i>
                                                    <div>
                                                        <strong>${order.assigned_to.name}</strong>
                                                        <br><small class="text-white">Assigned To</small>
                                                    </div>
                                                </div>
                                            ` : ''}
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa-solid fa-calendar text-success me-2"></i>
                                                <div>
                                                    <strong>${new Date(order.created_at).toLocaleDateString()}</strong>
                                                    <br><small class="text-white">Created</small>
                                                </div>
                                            </div>
                                            ${helpersCount == 0 ? '' : `
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fa-solid fa-users text-warning me-2"></i>
                                                <div>
                                                    <strong>${helpersCount} Helper(s)</strong>
                                                    <br><small class="text-white">Assigned</small>
                                                </div>
                                            </div>`}
                                        </div>
                                    </div>
                                    
                                    ${order.shared_note ? `
                                        <div class="alert alert-info py-2 mt-2 mb-2" style="font-size: 12px;">
                                            <i class="fa-solid fa-sticky-note me-2"></i>
                                            <strong>Shared Note:</strong> ${order.shared_note}
                                        </div>
                                    ` : ''}
                                    
                                    <div class="mt-2">
                                        <strong>Helpers:</strong>
                                        <div class="mt-1">${helpersHtml}</div>
                                    </div>
                                </div>
                                
                                <div class="text-end">
                                    <div class="btn-group-vertical" role="group">
                                        <button class="btn btn-sm btn-outline-primary mb-1" onclick="viewSharedOrderDetails(${order.id})" title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        
                                        ${helpersCount === 0 ? `
                                            <button class="btn btn-sm btn-outline-success mb-1" onclick="openAssignContractorsModal(${order.id})" title="Add Helpers">
                                                <i class="fa-solid fa-users"></i>
                                            </button>
                                        ` : `
                                            <button class="btn btn-sm btn-outline-warning mb-1" onclick="openAssignContractorsModal(${order.id})" title="Manage Helpers">
                                                <i class="fa-solid fa-user-edit"></i>
                                            </button>
                                        `}
                                        
                                        <button class="btn btn-sm btn-outline-warning" onclick="toggleSharedStatus(${order.id})" title="Unshare Order">
                                            <i class="fa-solid fa-share-from-square"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                container.append(orderHtml);
            });
        } else {
            console.log(`No orders found for ${currentTab} tab`);
            const emptyMessage = currentTab === 'requests' ? 'No Shared Order Requests Found' : 'No Confirmed Orders Found';
            const emptyDescription = currentTab === 'requests' ? 'There are currently no shared order requests to display.' : 'There are currently no confirmed orders to display.';
            const emptyIcon = currentTab === 'requests' ? 'fa-clock' : 'fa-check-circle';
            const emptyColor = currentTab === 'requests' ? 'text-warning' : 'text-success';
            
            container.html(`
                <div class="text-center p-4">
                    <i class="fa-solid ${emptyIcon} ${emptyColor}" style="font-size: 4rem;"></i>
                    <h5 class="mt-3 text-white">${emptyMessage}</h5>
                    <p class="text-white">${emptyDescription}</p>
                    <button class="btn btn-outline-primary btn-sm" onclick="loadAllData()">
                        <i class="fa-solid fa-refresh me-1"></i> Refresh Data
                    </button>
                </div>
            `);
        }
    }

    function getStatusColor(status) {
        const statusColors = {
            'pending': 'warning',
            'in-progress': 'info',
            'completed': 'success',
            'cancelled': 'danger',
            'reject': 'danger',
            'draft': 'secondary'
        };
        return statusColors[status] || 'secondary';
    }

    function refreshSharedOrders() {
        console.log('Refreshing all shared orders data');
        loadAllData();
    }

    function applyFilters() {
        loadSharedOrders();
    }

    function clearFilters() {
        $('#statusFilter').val('');
        $('#planFilter').val('');
        $('#searchInput').val('');
        loadSharedOrders();
    }

    function viewSharedOrderDetails(orderId) {
        // Redirect to order view page (same as main admin orders)
        window.location.href = `/admin/orders/${orderId}/view`;
    }

    function loadContractors() {
        $.ajax({
            url: '{{ route("admin.orders.contractors") }}',
            method: 'GET',
            success: function(response) {
                const contractorSelect = $('#contractors');
                contractorSelect.empty();
                
                if (response.success && response.data && response.data.length > 0) {
                    response.data.forEach(contractor => {
                        contractorSelect.append(
                            `<option value="${contractor.id}">${contractor.name} (${contractor.email})</option>`
                        );
                    });
                    
                    // Refresh Select2 after adding options
                    contractorSelect.trigger('change');
                    
                    // Trigger custom event to indicate contractors are loaded
                    $(document).trigger('contractorsLoaded');
                } else {
                    contractorSelect.append('<option disabled>No contractors available</option>');
                    contractorSelect.trigger('change');
                }
            },
            error: function(xhr) {
                console.error('Error loading contractors:', xhr);
                $('#contractors').html('<option disabled>Error loading contractors</option>').trigger('change');
            }
        });
    }

    function openAssignContractorsModal(orderId) {
        $('#assignOrderId').val(orderId);
        
        // Find the current order to get existing helper IDs
        const currentOrder = sharedOrdersData.find(order => order.id == orderId);
        const existingHelperIds = currentOrder ? (currentOrder.helpers_ids || []) : [];
        const helpersCount = existingHelperIds.length;
        const helpersNames = currentOrder ? (currentOrder.helpers_names || []) : [];
        
        // Convert helper IDs to strings to match option values
        const existingHelperIdsStr = existingHelperIds.map(id => String(id));
        
        console.log('Opening modal for order:', orderId);
        console.log('Existing helper IDs:', existingHelperIdsStr);
        console.log('Current order data:', currentOrder);
        
        // Update modal title based on whether helpers already exist
        const modalTitle = helpersCount > 0 
            ? `<i class="fa-solid fa-user-edit text-warning me-2"></i>Manage Helpers - Order #${orderId}`
            : `<i class="fa-solid fa-users text-primary me-2"></i>Add Helpers - Order #${orderId}`;
        $('#assignContractorsModalLabel').html(modalTitle);
        
        // Show current assignments if any
        if (helpersCount > 0 && helpersNames.length > 0) {
            $('#currentAssignmentsText').text(`Currently assigned: ${helpersNames.join(', ')}`);
            $('#currentAssignments').show();
        } else {
            $('#currentAssignments').hide();
        }
        
        // Clear previous selections first
        $('#contractors').val([]).trigger('change');
        
        // Function to set the selections
        function setContractorSelections() {
            if (existingHelperIdsStr.length > 0) {
                console.log('Setting contractor selections:', existingHelperIdsStr);
                $('#contractors').val(existingHelperIdsStr).trigger('change');
                
                // Double-check if selection worked
                const selectedValues = $('#contractors').val();
                console.log('Selected values after setting:', selectedValues);
                
                // If still not selected, try again with a different approach
                if (!selectedValues || selectedValues.length === 0) {
                    setTimeout(() => {
                        $('#contractors').val(existingHelperIdsStr).trigger('change');
                        console.log('Second attempt result:', $('#contractors').val());
                    }, 50);
                }
            }
        }
        
        // Check if contractors are already loaded
        if ($('#contractors option').length > 1) { // More than just the default option
            setContractorSelections();
        } else {
            // Wait for contractors to be loaded
            $(document).one('contractorsLoaded', function() {
                setTimeout(setContractorSelections, 150);
            });
            
            // Also reload contractors to ensure they're available
            loadContractors();
        }
        
        $('#assignContractorsModal').modal('show');
    }

    // Reset modal when closed
    $('#assignContractorsModal').on('hidden.bs.modal', function() {
        $('#contractors').val([]).trigger('change');
        $('#assignContractorsModalLabel').html('<i class="fa-solid fa-users text-primary me-2"></i>Add Helpers to Shared Order');
        $('#currentAssignments').hide();
        $('#assignOrderId').val('');
    });

    function getCurrentOrderById(orderId) {
        return sharedOrdersData.find(order => order.id == orderId) || null;
    }

    function assignContractors() {
        const orderId = $('#assignOrderId').val();
        const contractors = $('#contractors').val();
        
        if (!contractors || contractors.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning!',
                text: 'Please select at least one contractor.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        Swal.fire({
            title: 'Assign Contractors',
            text: `Are you sure you want to assign ${contractors.length} contractor(s) to this order?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, assign them!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Make AJAX call to assign contractors
                $.ajax({
                    url: `/admin/orders/${orderId}/assign-contractors`,
                    method: 'POST',
                    data: {
                        contractor_ids: contractors,
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while we assign the contractors...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#assignContractorsModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message || 'Contractors assigned successfully',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                loadAllData(); // Refresh data and statistics
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'Error assigning contractors',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while assigning contractors.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage,
                            confirmButtonColor: '#3085d6'
                        });
                    }
                });
            }
        });
    }

    function toggleSharedStatus(orderId) {
        Swal.fire({
            title: 'Unshare Order',
            text: 'Are you sure you want to unshare this order?',
            input: 'textarea',   
            inputLabel: 'Add a note',
            inputPlaceholder: 'Type your note here...',
            inputAttributes: {
                'aria-label': 'Type your note here'
            },
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, unshare it!',
            inputValidator: (value) => {
                if (!value || !value.trim()) {
                    return 'You must provide a note before proceeding!';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/orders/${orderId}/toggle-shared`,
                    method: 'POST',
                    data: {
                        note: result.value,
                        _token: '{{ csrf_token() }}'
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Processing...',
                            text: 'Please wait while we unshare the order...',
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: response.message || 'Order unshared successfully',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                loadAllData(); // Refresh data and statistics
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: response.message || 'An unknown error occurred.',
                                confirmButtonColor: '#3085d6'
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while unsharing the order.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage,
                            confirmButtonColor: '#3085d6'
                        });
                    }
                });
            }
        });
    }
</script>
@endpush