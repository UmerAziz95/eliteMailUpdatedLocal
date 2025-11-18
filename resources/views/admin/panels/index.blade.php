@extends('admin.layouts.app')

@section('title', 'Panels')

@push('styles')
<style>

    /* ::-webkit-scrollbar {
        display: none
    } */

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

    .odd {
        background-color: #5a49cd7b !important;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
    }

    .counters {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 20px;
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
        opacity: 0.6;
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
        transition: all 0.01s ease;
    }

    .domain-split-container:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .split-header {
        transition: all 0.01s ease;
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

    /* Ensure page remains interactive */
    body:not(.offcanvas-open):not(.modal-open) {
        overflow: visible !important;
        padding-right: 0 !important;
    }

    /* Chevron icon transition */
    .transition-transform {
        transition: transform 0.3s ease;
    }

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
/* Change Status Modal Styles */
.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 0.5rem 0.5rem 0 0;
}

.modal-header .btn-close {
    filter: invert(1);
}
/* Panel Reassignment Styles */
.panel-option {
    transition: all 0.2s ease;
    border: 2px solid transparent !important;
}

.panel-option:hover:not(.bg-light) {
    background-color: rgba(13, 110, 253, 0.05) !important;
    border-color: #0d6efd !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.panel-option.border-primary {
    border-color: #0d6efd !important;
    background-color: rgba(13, 110, 253, 0.1) !important;
}

.panel-option.bg-light {
    opacity: 0.7;
}

.panel-option .badge {
    font-size: 0.7em;
}

#reassignPanelModal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

#availablePanelsContainer {
    max-height: 400px;
    overflow-y: auto;
}

.reassign-panel-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
}

    .card {
        overflow: hidden
    }

    .button-container {
        pointer-events: none;
        transition: right 0.4s ease, pointer-events 0.4s ease;

    }

    .card:hover .button-container {
        right: 3% !important;
        pointer-events: all
    }
    /*  */
    /* Tab styles */
    .nav-pills .nav-link {
        background-color: transparent;
        color: var(--light-color);
        border: 1px solid var(--second-primary);
        margin-right: 0.5rem;
        transition: all 0.3s ease;
    }
    
    .nav-pills .nav-link:hover {
        background-color: rgba(90, 73, 205, 0.3);
        border-color: var(--second-primary);
    }
    
    .nav-pills .nav-link.active {
        background-color: var(--second-primary);
        color: var(--light-color);
        border-color: var(--second-primary);
    }
    
</style>
@endpush

@section('content')
<section class="py-3">
    
    {{-- <div class="modal fade" id="panelFormModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body p-3 p-md-5 position-relative">
                    <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                        data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    <div class="text-center mb-4">
                        <h4>Panel</h4>
                    </div>
                    <form id="panelForm" class="row g-3">
                        <!-- form fields here -->
                        <label>Panel title:</label>
                        <input type="text" class="form-control mb-3" id="panel_title" name="panel_title" value="">
                        <label>Panel Description:</label>
                        <input type="text" class="form-control mb-3" id="panel_description" name="panel_description"
                            value="">
                        <label>Limit:</label>
                        <input type="text" class="form-control mb-3" id="panel_limit" name="panel_limit" value="{{env('PANEL_CAPACITY', 1790)}}" readonly>
                        <label>Status:</label>
                        <select class="form-control mb-3" name="panel_status" id="panel_status" required>
                            <option value="1">
                                Active
                            </option>
                            <option value="0">In Active
                            </option>
                        </select>
                        <div class="mt-4">
                            <button type="button" id="submitPanelFormBtn"
                                class="m-btn py-2 px-4 rounded-2 w-100 update-plan-btn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div> --}}


    {{-- <div class="offcanvas offcanvas-end" tabindex="-1" id="panelFormOffcanvas" aria-labelledby="offcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="offcanvasLabel">Panel</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            
        </div>
    </div>     --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" data-bs-backdrop="static" id="panelFormOffcanvas" aria-labelledby="staticBackdropLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="panelFormOffcanvasLabel">Panel</h5>
            <button type="button" class="bg-transparent border-0 fs-5" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="offcanvas-body">
            <div class="d-flex justify-content-end">
                <button type="button" id="openSecondOffcanvasBtn" class="m-btn py-1 px-4 rounded-1 mb-2 border-0">
                    View Orders
                </button>
            </div>
            
            <form id="panelForm" class="">
                <div class="mb-3" id="nextPanelIdContainer">
                    <label for="panel_id">Panel ID:</label>
                    <input type="text" class="form-control" id="panel_id" name="panel_id" value="" readonly>
                </div>
                <label for="panel_title">Panel title: <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="panel_title" name="panel_title" value="" required maxlength="255">

                @php
                    $providerOptions = $providerTypes ?? ['Google', 'Microsoft 365'];
                    $selectedProviderType = $defaultProviderType ?? ($providerOptions[0] ?? null);
                    $capacityMap = $providerCapacities ?? [];
                    $selectedCapacity = $selectedProviderType && isset($capacityMap[$selectedProviderType])
                        ? $capacityMap[$selectedProviderType]
                        : env('PANEL_CAPACITY', 1790);
                @endphp
                <label for="provider_type" class="mt-3">Provider Type: <span class="text-danger">*</span></label>
                <select class="form-control mb-3" id="provider_type" name="provider_type" required>
                    <option value="" disabled {{ $selectedProviderType ? '' : 'selected' }}>Select provider</option>
                    @foreach($providerOptions as $provider)
                        <option value="{{ $provider }}" {{ $selectedProviderType === $provider ? 'selected' : '' }}>
                            {{ $provider }}
                        </option>
                    @endforeach
                </select>

                <label for="panel_description" class="mt-3">Panel Description:</label>
                <input type="text" class="form-control mb-3" id="panel_description" name="panel_description" value="">

                <label for="panel_limit">Limit: <span class="text-danger">*</span></label>
                <input type="number" class="form-control mb-3" id="panel_limit" name="panel_limit" value="{{ $selectedCapacity }}" required min="1" readonly>

                <label for="panel_status">Status:</label>
                <select class="form-control mb-3" name="panel_status" id="panel_status" required>
                    <option value="1">Active</option>
                    <option value="0">In Active</option>
                </select>

                <div class="mt-4">
                    <button type="button" id="submitPanelFormBtn" class="m-btn py-2 px-4 rounded-2 w-100 update-plan-btn border-0">
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
    <div class="offcanvas offcanvas-start" style="min-width: 70%;  background-color: var(--filter-color); backdrop-filter: blur(5px); border: 3px solid var(--second-primary);" tabindex="-1" id="secondOffcanvas" aria-labelledby="secondOffcanvasLabel">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="secondOffcanvasLabel">Order Awaited Panel Allocation</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="counters mb-3">
                <div class="p-3 filter">
                    <div>
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <h6 class="text-heading">Number of Orders</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2 fs-2" id="orders_counter">0</h4>
                                    <p class="text-success mb-0"></p>
                                </div>
                                <small class="mb-0"></small>
                            </div>
                            <div class="avatar">
                                <i class="fa-brands fa-first-order fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 filter">
                    <div>
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <h6 class="text-heading">Number of Inboxes</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2 fs-2" id="inboxes_counter">0</h4>
                                    <p class="text-success mb-0"></p>
                                </div>
                                <small class="mb-0"></small>
                            </div>
                            <div class="avatar">
                                <i class="fa-solid fa-inbox fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 filter">
                    <div>
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <h6 class="text-heading">Panels Required</h6>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2 fs-2" id="panels_counter">0</h4>
                                    <p class="text-success mb-0"></p>
                                </div>
                                <small class="mb-0"></small>
                            </div>
                            <div class="avatar">
                                <i class="fa-solid fa-solar-panel fs-2"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table id="myTable" class="w-100 display">
                    <thead style="position: sticky; top: 0;">
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Plan</th>
                            <th>Domain URL</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="overflow-y-auto" id="orderTrackingTableBody">
                        <!-- Dynamic data will be loaded here -->
                    </tbody>
                    
                </table>
            </div>
        </div>
    </div>
        <!-- Panel Capacity Alert -->
        @php
            // Get panel capacity alert data using the same logic as the AJAX endpoint
            $pendingOrders = \App\Models\OrderTracking::where('status', 'pending')
                ->whereNotNull('total_inboxes')
                ->where('total_inboxes', '>', 0)
                ->get();
            
            $insufficientSpaceOrders = [];
            $totalPanelsNeeded = 0;
            $panelCapacity = env('PANEL_CAPACITY', 1790);
            $maxSplitCapacity = env('MAX_SPLIT_CAPACITY', 358);
            
            // Helper function to get available panel space for specific order
            $getAvailablePanelSpaceForOrder = function(int $orderSize, int $inboxesPerDomain) use ($panelCapacity, $maxSplitCapacity) {
                if ($orderSize >= $panelCapacity) {
                    // For large orders, prioritize full capacity panels
                    $fullCapacityPanels = \App\Models\Panel::where('is_active', 1)
                                                ->where('limit', $panelCapacity)
                                                ->where('remaining_limit', '>=', $inboxesPerDomain)
                                                ->get();
                    
                    $fullCapacitySpace = 0;
                    foreach ($fullCapacityPanels as $panel) {
                        $fullCapacitySpace += min($panel->remaining_limit, $maxSplitCapacity);
                    }
                    
                    return $fullCapacitySpace;
                    
                } else {
                    // For smaller orders, use any panel with remaining space that can accommodate at least one domain
                    $availablePanels = \App\Models\Panel::where('is_active', 1)
                                            ->where('limit', $panelCapacity)
                                            ->where('remaining_limit', '>=', $inboxesPerDomain)
                                            ->get();
                    
                    $totalSpace = 0;
                    foreach ($availablePanels as $panel) {
                        $totalSpace += min($panel->remaining_limit, $maxSplitCapacity);
                    }
                    
                    return $totalSpace;
                }
            };
            
            foreach ($pendingOrders as $order) {
                // Get inboxes per domain from order details or use default
                $inboxesPerDomain = $order->inboxes_per_domain ?? 1;
                
                // Calculate available space for this order based on logic
                $availableSpace = $getAvailablePanelSpaceForOrder(
                    $order->total_inboxes, 
                    $inboxesPerDomain
                );
                
                if ($order->total_inboxes > $availableSpace) {
                    // Calculate panels needed for this order (same logic as Console Command)
                    $panelsNeeded = ceil($order->total_inboxes / $maxSplitCapacity);
                    $insufficientSpaceOrders[] = $order;
                    $totalPanelsNeeded += $panelsNeeded;
                }
            }
            
            // Adjust total panels needed based on available panels (same logic as Console Command)
            $availablePanelCount = \App\Models\Panel::where('is_active', true)
                ->where('limit', $panelCapacity)
                ->where('remaining_limit', '>=', $maxSplitCapacity)
                ->count();
            
            $adjustedPanelsNeeded = max(0, $totalPanelsNeeded - $availablePanelCount);
        @endphp

        @if($adjustedPanelsNeeded > 0)
        <div id="panelCapacityAlert" class="alert alert-danger alert-dismissible fade show py-2 rounded-1" role="alert"
            style="background-color: rgba(220, 53, 69, 0.2); color: #fff; border: 2px solid #dc3545;">
            <i class="ti ti-server me-2 alert-icon"></i>
            <strong>Panel Capacity Alert:</strong>
            {{ $adjustedPanelsNeeded }} new panel{{ $adjustedPanelsNeeded != 1 ? 's' : '' }} required for {{ count($insufficientSpaceOrders) }} pending order{{ count($insufficientSpaceOrders) != 1 ? 's' : '' }}.
            <a href="{{ route('admin.panels.index') }}" class="text-light alert-link">Manage Panels</a> to create additional capacity.
            <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert"
                aria-label="Close"></button>
        </div>
        @endif
        <div class="counters mb-3">
            <div class="card p-3 counter_1">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Panels</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="total_counter">0</h4>
                            </div>
                            <small class="mb-0 d-flex align-items-center gap-2">
                                <span class="text-success">
                                    <span class="fw-semibold" id="active_panels_counter">0</span> active
                                </span>
                                <span class="text-muted">•</span>
                                <span class="text-warning">
                                    <span class="fw-semibold" id="archived_panels_counter">0</span> archived
                                </span>
                            </small>
                        </div>
                        <div class="avatar">
                            <i class="fa-solid fa-solar-panel fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
    
            <div class="card p-3 counter_2">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Available Capacity</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="available_capacity_counter">0</h4>
                                <p class="text-danger mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            <i class="fa-solid fa-solar-panel fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
    
            <div class="card p-3 counter_2">
                <div>
                    <!-- {{-- //card body --}} -->
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Used Capacity</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="used_capacity_counter">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            <i class="fa-solid fa-solar-panel fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
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
                        <button type="button" id="resetFilters"
                            class="btn btn-outline-secondary btn-sm me-2 px-3">Reset</button>
                        <button type="submit" id="submitBtn"
                            class="btn btn-primary btn-sm border-0 px-3">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    
    {{-- Tabs for Active and Archived Panels --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <ul class="nav nav-pills" id="panelTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab" data-bs-toggle="pill" data-bs-target="#active-panels" 
                        type="button" role="tab" aria-controls="active-panels" aria-selected="true"
                        onclick="switchTab('active')">
                    <i class="fa-solid fa-check-circle me-1"></i> Active Panels
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="archived-tab" data-bs-toggle="pill" data-bs-target="#archived-panels" 
                        type="button" role="tab" aria-controls="archived-panels" aria-selected="false"
                        onclick="switchTab('archived')">
                    <i class="fa-solid fa-archive me-1"></i> Archived Panels
                </button>
            </li>
        </ul>
        <div class="d-flex align-items-center">
        {{-- Provider Type Filter Dropdown --}}
        <select class="form-select form-select-sm me-2" id="providerTypeFilter" style="width: auto; min-width: 180px;"
                onchange="filterByProviderType()">
            <option value="all">All Providers</option>
            <option value="Google">Google</option>
            <option value="Microsoft 365">Microsoft 365</option>
        </select>
        
        {{-- create panel button --}}
        <button type="button" class="btn btn-primary btn-sm border-0 px-3" 
                onclick="createNewPanel()">
            <i class="fa-solid fa-plus me-2"></i>
            Create New Panel
        </button>
        </div>
    </div>
    <!-- Grid Cards (Dynamic) -->
    <div id="panelsContainer"
        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;">
        <!-- Loading state -->
        <div id="loadingState"
            style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
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
            <span id="loadMoreSpinner" class="spinner-border spinner-border-sm ms-2" role="status"
                style="display: none;">
                <span class="visually-hidden">Loading...</span>
            </span>
        </button>
        <div id="paginationInfo" class="mt-2 text-light small">
            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalPanels">0</span>
            panels
        </div>
    </div>

</section>




<!-- Orders Offcanvas -->
<div class="offcanvas offcanvas-end" style="width: 100%;" tabindex="-1" id="order-view"
    aria-labelledby="order-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
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

<!-- Panel Reassignment Modal -->
<div class="modal fade" id="reassignPanelModal" tabindex="-1" aria-labelledby="reassignModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reassignModalLabel">Reassign Panel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Panel Reassignment:</strong> Select a target panel within the same order to reassign the split(s) to. 
                        This will move all domains and capacity from the current panel to the selected panel.
                    </div>
                </div>
                <!-- Loading State -->
                <div id="reassignLoader" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading available panels...</p>
                </div>
                
                <!-- Available Panels Container -->
                <div id="availablePanelsContainer"></div>
                
                <!-- Reassignment Reason -->
                <div class="mt-3" style="display: none;" id="reassignReasonContainer">
                    <label for="reassignReason" class="form-label">Reason for reassignment (optional)</label>
                    <textarea class="form-control" id="reassignReason" rows="3" placeholder="Enter reason for reassignment..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmReassignBtn" disabled onclick="confirmReassignment()">
                    <i class="fas fa-exchange-alt me-1"></i>Select Panel First
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Pass PHP values to JavaScript
    const PANEL_CAPACITY_FALLBACK = {{ env('PANEL_CAPACITY', 1790) }};
    const DEFAULT_PROVIDER_TYPE = @json($selectedProviderType ?? 'Google');
    const PROVIDER_OPTIONS = @json($providerOptions ?? ['Google', 'Microsoft 365']);
    const PROVIDER_CAPACITIES = @json($capacityMap ?? []);


    const PROVIDER_BADGE_MAP = {
        'Google': { className: 'bg-danger text-white', icon: 'fab fa-google' },
        'Microsoft 365': { className: 'bg-primary text-white', icon: 'fab fa-microsoft' },
        'Private SMTP': { className: 'bg-success text-white', icon: 'fas fa-lock' },
    };

    function getCapacityForProvider(providerType) {
        if (providerType && Object.prototype.hasOwnProperty.call(PROVIDER_CAPACITIES, providerType)) {
            const capacity = Number(PROVIDER_CAPACITIES[providerType]);
            if (!Number.isNaN(capacity) && capacity > 0) {
                return capacity;
            }
        }
        return PANEL_CAPACITY_FALLBACK;
    }

    function getProviderBadge(providerType) {
        if (!providerType) {
            return '';
        }

        const config = PROVIDER_BADGE_MAP[providerType] || { className: 'bg-secondary text-white', icon: 'fas fa-globe' };
        return `
            <span class="badge ${config.className} ms-2 d-inline-flex align-items-center gap-1">
                <i class="${config.icon}"></i>
                ${providerType}
            </span>
        `;
    }

    document.getElementById("openSecondOffcanvasBtn").addEventListener("click", function () {
        const secondOffcanvasElement = document.getElementById("secondOffcanvas");
        const secondOffcanvas = new bootstrap.Offcanvas(secondOffcanvasElement, {
            backdrop: false, // no backdrop to prevent it from dismissing others
            scroll: true     // allows scrolling while multiple are open
        });
        secondOffcanvas.show();
        // Initialize DataTable when offcanvas is shown
        setTimeout(function() {
            initializeOrderTrackingTable();
        }, 300); // Small delay to ensure offcanvas is fully shown
    });

    // Fetch next panel ID when panelFormOffcanvas is opened
    // document.getElementById('panelFormOffcanvas').addEventListener('shown.bs.offcanvas', function () {
        
    // });

    // Filter panels by provider type
    function filterByProviderType() {
        const selectedProvider = document.getElementById('providerTypeFilter').value;
        
        // Store the current filter
        window.currentProviderFilter = selectedProvider;
        
        // Add provider type filter to current filters
        if (selectedProvider !== 'all') {
            currentFilters.provider_type = selectedProvider;
        } else {
            delete currentFilters.provider_type;
        }
        
        // Reset pagination and reload panels
        currentPage = 1;
        loadPanels(currentFilters, 1, false);
    }

    function fetchNextPanelId(options = {}) {
        const { showOffcanvas = true, showLoader = true } = options;
        const providerTypeField = document.getElementById('provider_type');
        const providerTypeValue = providerTypeField && providerTypeField.value
            ? providerTypeField.value
            : DEFAULT_PROVIDER_TYPE;
        const query = new URLSearchParams({
            provider_type: providerTypeValue || DEFAULT_PROVIDER_TYPE,
        });
        const panelLimitInput = document.getElementById('panel_limit');

        if (showLoader) {
            // Show SweetAlert loading dialog only when requested
            Swal.fire({
                title: 'Fetching Panel ID',
                text: 'Please wait...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        }

        fetch(`/admin/panels/next-id?${query.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to fetch next panel ID.');
                }
                return response.json();
            })
            .then(data => {
                document.getElementById('panel_id').value = data.next_id || '';
                const capacityFromResponse = data && typeof data.capacity !== 'undefined'
                    ? Number(data.capacity)
                    : null;

                if (panelLimitInput) {
                    const capacityToApply = capacityFromResponse && !Number.isNaN(capacityFromResponse)
                        ? capacityFromResponse
                        : getCapacityForProvider(providerTypeValue);
                    panelLimitInput.value = capacityToApply;
                }

                if (showLoader) {
                    Swal.close();
                }

                if (showOffcanvas) {
                    // Show the offcanvas (no need to wait for fetchNextPanelId, since it sets the value asynchronously)
                    const offcanvasElement = document.getElementById('panelFormOffcanvas');
                    const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
                    offcanvas.show();
                }
            })
            .catch((error) => {
                document.getElementById('panel_id').value = '';
                if (panelLimitInput) {
                    panelLimitInput.value = getCapacityForProvider(providerTypeValue);
                }

                if (showLoader) {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to fetch next panel ID.',
                        confirmButtonText: 'OK'
                    });
                } else {
                    console.error(error);
                }
            });
    }

    let orderTrackingTable = null;

    function initializeOrderTrackingTable() {
        // Destroy existing table if it exists
        if (orderTrackingTable) {
            orderTrackingTable.destroy();
        }

        // Initialize DataTable
        orderTrackingTable = $('#myTable').DataTable({
            pageLength: 10,         // Show 10 rows per page
            lengthMenu: [10, 25, 50, 100], // Optional dropdown for page length
            ordering: true,         // Enable column sorting
            searching: false,        // Enable search box
            scrollX: true,          // Enable horizontal scroll if needed
            processing: true,       // Show processing indicator
            ajax: {
                url: '/admin/panels/order-tracking',
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                dataSrc: function(json) {
                    if (json.success) {
                        // Update counters with server-calculated data
                        if (json.counters) {
                            console.log('Updating counters from server:', json.counters);
                            $('#orders_counter').text(json.counters.total_orders || 0);
                            $('#inboxes_counter').text(json.counters.total_inboxes || 0);
                            $('#panels_counter').text(json.counters.panels_required || 0);
                        }
                        return json.data;
                    } else {
                        console.error('Error fetching data:', json.message);
                        return [];
                    }
                }
            },
            columns: [
                { data: 'order_id', title: 'Order ID', render: function(data) { return '#' + data; } },
                { data: 'date', title: 'Date' },
                { data: 'plan', title: 'Plan' },
                { data: 'domain_url', title: 'Domain URL' },
                { data: 'total', title: 'Total' },
                { 
                    data: 'status', 
                    title: 'Status',
                    render: function(data) {
                        let badgeClass = 'bg-label-secondary';
                        switch(data) {
                            case 'completed':
                                badgeClass = 'bg-label-success';
                                break;
                            case 'active':
                                badgeClass = 'bg-label-success';
                                break;
                            case 'pending':
                                badgeClass = 'bg-label-warning';
                                break;
                            case 'failed':
                                badgeClass = 'bg-label-danger';
                                break;
                        }
                        return '<span class="badge ' + badgeClass + ' rounded-1 px-2 py-1">' + data.charAt(0).toUpperCase() + data.slice(1) + '</span>';
                    }
                }
            ]
        });
    }
    // Function to update counters - now using server-side calculation
    async function updateCounters(data = null) {
        try {
            // If we have data with counters, use that first
            if (data && typeof data === 'object' && data.counters) {
                console.log('Using provided counter data:', data.counters);
                $('#orders_counter').text(data.counters.total_orders || 0);
                $('#inboxes_counter').text(data.counters.total_inboxes || 0);
                $('#panels_counter').text(data.counters.panels_required || 0);
                return;
            }
            
            // Otherwise fetch from server
            console.log('Fetching counters from server...');
            const response = await fetch('/admin/panels/counters', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success && result.counters) {
                console.log('Counters from server:', result.counters);
                
                $('#orders_counter').text(result.counters.total_orders || 0);
                $('#inboxes_counter').text(result.counters.total_inboxes || 0);
                $('#panels_counter').text(result.counters.panels_required || 0);
            } else {
                console.error('Failed to fetch counters:', result.message);
                // Fallback to 0 values if server returns error
                $('#orders_counter').text(0);
                $('#inboxes_counter').text(0);
                $('#panels_counter').text(0);
            }
        } catch (error) {
            console.error('Error updating counters:', error);
            // Fallback to 0 values on error
            $('#orders_counter').text(0);
            $('#inboxes_counter').text(0);
            $('#panels_counter').text(0);
        }
    }

    // Function to update panel counters with dynamic data
    async function updatePanelCounters() {
        try {
            console.log('Fetching panel counters...');
            
            // Get current provider filter
            const providerFilter = document.getElementById('providerTypeFilter')?.value || 'all';
            const params = new URLSearchParams();
            if (providerFilter && providerFilter !== 'all') {
                params.append('provider_type', providerFilter);
            }
            
            // Fetch comprehensive panel statistics from server
            const url = `/admin/panels/statistics${params.toString() ? '?' + params.toString() : ''}`;
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });

            if (response.ok) {
                const result = await response.json();
                
                if (result.success && result.statistics) {
                    console.log('Panel statistics from server:', result.statistics);
                    
                    // Update the main panel counters
                    $('#total_counter').text(result.statistics.total_panels || 0);
                    $('#available_capacity_counter').text((result.statistics.available_capacity || 0).toLocaleString());
                    $('#used_capacity_counter').text((result.statistics.used_capacity || 0).toLocaleString());
                    $('#active_panels_counter').text(result.statistics.active_panels || 0);
                    $('#archived_panels_counter').text(
                        (result.statistics.archived_panels ?? result.statistics.closed_panels) || 0
                    );
                    
                    return;
                }
            }
            
            // Fallback: calculate from currently loaded panels data
            console.log('Server endpoint not available, calculating from loaded panels...');
            
            if (panels && panels.length > 0) {
                // Note: This is only based on currently loaded/filtered panels
                let totalVisible = panels.length;
                let availableCapacity = 0;
                let usedCapacity = 0;
                let activeVisible = 0;
                let archivedVisible = 0;
                
                panels.forEach(panel => {
                    availableCapacity += panel.remaining_limit || 0;
                    usedCapacity += (panel.limit || 0) - (panel.remaining_limit || 0);
                    if (panel.is_active) {
                        activeVisible++;
                    } else {
                        archivedVisible++;
                    }
                });
                
                console.log('Calculated from visible panels:', {
                    totalVisible,
                    availableCapacity, 
                    usedCapacity,
                    activeVisible,
                    archivedVisible
                });
                
                // Update counters (note: these are based on filtered/paginated results)
                $('#total_counter').text(totalVisible);
                $('#available_capacity_counter').text(availableCapacity.toLocaleString());
                $('#used_capacity_counter').text(usedCapacity.toLocaleString());
                $('#active_panels_counter').text(activeVisible);
                $('#archived_panels_counter').text(archivedVisible);
            } else {
                console.log('No panels data available for calculation');
                // Keep existing values if no data available
            }
            
        } catch (error) {
            console.error('Error updating panel counters:', error);
            
            // Final fallback: try to calculate from any available panels data
            if (panels && panels.length > 0) {
                let availableCapacity = 0;
                let usedCapacity = 0;
                let activeVisible = 0;
                let archivedVisible = 0;
                
                panels.forEach(panel => {
                    availableCapacity += panel.remaining_limit || 0;
                    usedCapacity += (panel.limit || 0) - (panel.remaining_limit || 0);
                    if (panel.is_active) {
                        activeVisible++;
                    } else {
                        archivedVisible++;
                    }
                });
                
                $('#total_counter').text(panels.length);
                $('#available_capacity_counter').text(availableCapacity.toLocaleString());
                $('#used_capacity_counter').text(usedCapacity.toLocaleString());
                $('#active_panels_counter').text(activeVisible);
                $('#archived_panels_counter').text(archivedVisible);
            }
        }
    }

    let panels = [];
        let currentFilters = {};
        let charts = {}; // Store chart instances
        let currentPage = 1;
        let hasMorePages = false;
        let totalPanels = 0;
        let isLoading = false;
        let currentTab = 'active'; // Track current tab (active or archived)

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
                    per_page: 12,
                    is_active: currentTab === 'active' ? 1 : 0
                });
                
                // Add provider type filter if set
                const providerFilter = document.getElementById('providerTypeFilter')?.value;
                if (providerFilter && providerFilter !== 'all') {
                    params.append('provider_type', providerFilter);
                }
                
                const url = `/admin/panels/data?${params}`;
               
                
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                
                if (!response.ok) {
                    const errorText = await response.text();
                 
                    throw new Error(`Failed to fetch panels: ${response.status} ${response.statusText}`);
                }
                  
                const data = await response.json();
              
                
                const newPanels = data.data || [];
              
                
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
                
                
                renderPanels(append);
                updatePaginationInfo();
                updateLoadMoreButton();
                
                // Update panel counters when panels are loaded (only for non-append requests)
                if (!append) {
                    updatePanelCounters();
                }
                
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
            const providerLabel = getProviderBadge(panel.provider_type);
            
            // Generate edit/delete buttons only if conditions are met
            let actionButtons = '';
            if (panel.show_edit_delete_buttons) {
                actionButtons = `
                    <div class="d-flex flex-column gap-2">
                        ${panel.can_edit ? `
                            <button class="btn btn-sm btn-outline-warning px-2 py-1" 
                                    onclick="editPanel(${panel.id})" 
                                    title="Edit Panel">
                                <i class="fas fa-edit"></i>
                            </button>
                        ` : ''}
                        
                        ${panel.can_delete ? `
                            <button class="btn btn-sm btn-outline-danger px-2 py-1" 
                                    onclick="deletePanel(${panel.id})" 
                                    title="Delete Panel">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                `;
            }
            actionButtons = `
                <div class="d-flex flex-column gap-2">
                    ${panel.show_edit_delete_buttons ? `
                        ${panel.can_edit ? `
                            <button class="btn btn-sm btn-outline-warning px-2 py-1" 
                                    onclick="editPanel(${panel.id})" 
                                    title="Edit Panel">
                                <i class="fas fa-edit"></i>
                            </button>
                        ` : ''}
                        
                        ${panel.can_delete ? `
                            <button class="btn btn-sm btn-outline-danger px-2 py-1" 
                                    onclick="deletePanel(${panel.id})" 
                                    title="Delete Panel">
                                <i class="fas fa-trash"></i>
                            </button>
                        ` : ''}
                    ` : ''}
                    
                    ${panel.is_active ? `
                        <button class="btn btn-sm btn-outline-secondary px-2 py-1" 
                                onclick="archivePanel(${panel.id})" 
                                title="Archive Panel">
                            <i class="fas fa-archive"></i>
                        </button>
                    ` : `
                        <button class="btn btn-sm btn-outline-success px-2 py-1" 
                                onclick="unarchivePanel(${panel.id})" 
                                title="Unarchive Panel">
                            <i class="fas fa-undo"></i>
                        </button>
                    `}
                </div>
            `;
            
            // Add archived styling for inactive panels
            const archivedStyle = !panel.is_active ? 'background-color: #334761;' : '';
            
            return `
                <div class="card p-3 d-flex flex-column gap-1" style="${archivedStyle}">                    
                    <div class="d-flex flex-column gap-2 align-items-start justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <small class="mb-0 opacity-75">${'PNL-' + panel.id || panel.auto_generated_id}</small>
                            ${providerLabel}
                        </div>
                        <h6>Title: ${panel.title || 'N/A'} ${!panel.is_active ? '<span class="badge bg-secondary ms-2">Archived</span>' : ''}</h6>
                    </div>

                    <div class="d-flex gap-3 justify-content-between">
                        <small class="total">Total: ${panel.limit}</small>
                        <small class="remain">Remaining: ${remaining}</small>
                        <small class="used">Used: ${used}</small>
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
                    <div class="button-container p-2 rounded-2" style="background-color: var(--filter-color); position: absolute; top: 50%; right: -50px; transform: translate(0%, -50%)">
                        ${actionButtons}    
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
                
                // Fetch orders for the selected panel
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
            const container = document.getElementById('panelOrdersContainer');
            if (!orders || orders.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-inbox text-muted fs-3 mb-3"></i>
                        <h5>No Orders Found</h5>
                        <p class="mb-3">This panel doesn't have any orders yet.</p>
                    </div>
                `;
                return;
            }

            const ordersHtml = `
                <div class="mb-4">
                    <h6>PNL- ${panel.id}</h6>
                    <h6>Title: ${panel.title}</h6>
                    <p class="text-white small">${panel.description || ''}</p>
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
                                    <small class="text-light"><i class="fas fa-envelope me-1"></i><span>Inboxes:</span> <span class="fw-bold">${order.space_assigned || order.inboxes_per_domain || 0}</span>${order.remaining_order_panels && order.remaining_order_panels.length > 0 ? `<span> (${order.remaining_order_panels.length} more split${order.remaining_order_panels.length > 1 ? 's' : ''}</span>)` : ''}</small>
                                    <div class="d-flex align-items-center gap-2">
                                        ${order.order_status === 'pending' ? `<span class="badge ${getStatusBadgeClass(order.order_status)}" style="font-size: 10px;">${order.order_status || 'Unknown'}</span>` : `
                                            <span class="badge ${getStatusBadgeClass(order.order_status)}" style="font-size: 10px;">
                                                ${order.order_status || 'Unknown'}
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
                                                    <th scope="col">Panel Title</th>
                                                    <th scope="col">Inboxes/Domain</th>
                                                    <th scope="col">Total Domains</th>
                                                    <th scope="col">Inboxes</th>
                                                    <th scope="col">Customized Type</th>
                                                    <th scope="col">Date</th>
                                                    <th scope="col">Action</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                
                                                <tr>
                                                    <th scope="row">${index + 1}</th>

                                                    <td>PNL-${order.panel_id || 'N/A'}</td>

                                                    <td>${panel?.title || 'N/A'}</td>
                                                    
                                                    <td>${order.inboxes_per_domain || 'N/A'}</td>
                                                    <td>
                                                        <span class="badge bg-success" style="font-size: 10px;">
                                                            ${order.splits ? order.splits.reduce((total, split) => total + (split.domains ? split.domains.length : 0), 0) : 0} domain(s)
                                                        </span>
                                                    </td>
                                                    
                                                    
                                                    <td>${order.space_assigned || 'N/A'}</td>
                                                    <td>
                                                        ${order.email_count > 0 ? `
                                                            <span class="badge bg-success" style="font-size: 10px;">
                                                                <i class="fa-solid fa-check me-1"></i>Customized
                                                            </span>
                                                        ` : `
                                                            <span class="badge bg-secondary" style="font-size: 10px;">
                                                                <i class="fa-solid fa-cog me-1"></i>Default
                                                            </span>
                                                        `}
                                                    </td>
                                                    <td>${formatDate(order.created_at)}</td>
                                                    <td>
                                                        <div class="d-flex gap-1">
                                                            <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary"
                                                                onclick="window.location.href='/admin/orders/${order.order_panel_id}/split/view'">
                                                                View
                                                            </button>
                                                            <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-info"
                                                                onclick="window.open('/admin/orders/split/${order.order_panel_id}/export-csv-domains', '_blank')"
                                                                title="Download CSV">
                                                                <i class="fa-solid fa-download"></i>
                                                            </button>
                                                            
                                                            ${order.order_status === 'cancelled' || order.order_status === 'reject' || order.order_status === 'removed' ? '' : `
                                                                <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-success"
                                                                    onclick="openReassignModal(${order.order_id}, ${order.panel_id}, ${order.order_panel_id}, '${panel?.title || 'N/A'}')"
                                                                    title="Reassign to another panel">
                                                                    Reassign
                                                                </button>
                                                            `}
                                                            ${order.customized_note ? `
                                                                <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-warning"
                                                                    onclick="showCustomizedNoteModal('${order.customized_note.replace(/'/g, '&apos;').replace(/"/g, '&quot;')}')"
                                                                    title="View Customized Note">
                                                                    <i class="fa-solid fa-sticky-note"></i>
                                                                </button>
                                                            ` : ''}
                                                        </div>
                                                    </td>
                                                </tr>
                                                ${order.remaining_order_panels && order.remaining_order_panels.length > 0 ? 
                                                    order.remaining_order_panels.map((remainingPanel, panelIndex) => `
                                                   
                                                        <tr>

                                                            <th scope="row">${index + 1}.${panelIndex + 1}</th>

                                                            <td>PNL-${remainingPanel.panel_id || 'N/A'}</td>
                                                            
                                                            <td>${remainingPanel.panel_title || 'N/A'}</td>
                                                            
                                                            <td>${remainingPanel.inboxes_per_domain || 'N/A'}</td>
                                                            <td>
                                                                <span class="badge bg-success" style="font-size: 10px;">
                                                                    ${remainingPanel.domains_count || 0} domain(s)
                                                                </span>
                                                            </td>
                                                            <td>${remainingPanel.space_assigned || 'N/A'}</td>
                                                            <td>
                                                                ${remainingPanel.email_count > 0 ? `
                                                                    <span class="badge bg-success" style="font-size: 10px;">
                                                                        <i class="fa-solid fa-check me-1"></i>Customized
                                                                    </span>
                                                                ` : `
                                                                    <span class="badge bg-secondary" style="font-size: 10px;">
                                                                        <i class="fa-solid fa-cog me-1"></i>Default
                                                                    </span>
                                                                `}
                                                            </td>
                                                            <td>${formatDate(remainingPanel.created_at || order.created_at)}</td>
                                                            <td>
                                                                <div class="d-flex gap-1">
                                                                    <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-primary"
                                                                        onclick="window.location.href='/admin/orders/${remainingPanel.order_panel_id}/split/view'">
                                                                        View
                                                                    </button>
                                                                    <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-info"
                                                                        onclick="window.open('/admin/orders/split/${remainingPanel.order_panel_id}/export-csv-domains', '_blank')"
                                                                        title="Download CSV">
                                                                        <i class="fa-solid fa-download"></i>
                                                                    </button>
                                                                    
                                                                    ${order.order_status === 'cancelled' || order.order_status === 'reject' || order.order_status === 'removed' ? '' : `
                                                                        <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-success"
                                                                            onclick="openReassignModal(${order.order_id}, ${remainingPanel.panel_id}, ${remainingPanel.order_panel_id}, '${remainingPanel.panel_title || 'N/A'}')"
                                                                            title="Reassign to another panel">
                                                                            Reassign
                                                                        </button>
                                                                    `}
                                                                    ${remainingPanel.customized_note ? `
                                                                        <button style="font-size: 12px" class="btn border-0 btn-sm py-0 px-2 rounded-1 btn-warning"
                                                                            onclick="showCustomizedNoteModal('${remainingPanel.customized_note.replace(/'/g, '&apos;').replace(/"/g, '&quot;')}')"
                                                                            title="View Customized Note">
                                                                            <i class="fa-solid fa-sticky-note"></i>
                                                                        </button>
                                                                    ` : ''}
                                                                </div>
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
                                                    <span>${order.splits ? (() => {
                                                        const mainDomainsCount = order.splits.reduce((total, split) => total + (split.domains ? split.domains.length : 0), 0);  
                                                        const inboxesPerDomain = order.reorder_info?.inboxes_per_domain || 0;
                                                        const mainTotalInboxes = mainDomainsCount * inboxesPerDomain;
                                                        
                                                        let splitDetails = `<br><span class="badge bg-white text-dark me-1" style="font-size: 10px; font-weight: bold;">Panel Break 01</span> Inboxes: ${mainTotalInboxes} (${mainDomainsCount} domains × ${inboxesPerDomain})<br>`;
                                                        
                                                        // Add remaining splits details
                                                        if (order.remaining_order_panels && order.remaining_order_panels.length > 0) {
                                                            order.remaining_order_panels.forEach((panel, index) => {
                                                                const splitDomainsCount = panel.domains_count || 0;
                                                                const splitInboxes = splitDomainsCount * inboxesPerDomain;
                                                                splitDetails += `<br><div class="text-white"><span class="badge bg-white text-dark me-1" style="font-size: 10px; font-weight: bold;">Panel Break ${String(index + 2).padStart(2, '0')}</span> Inboxes: ${splitInboxes} (${splitDomainsCount} domains × ${inboxesPerDomain})</div>`;
                                                            });
                                                        }
                                                        
                                                        const totalAllInboxes = mainTotalInboxes + (order.remaining_order_panels ? 
                                                            order.remaining_order_panels.reduce((total, panel) => total + ((panel.domains_count || 0) * inboxesPerDomain), 0) : 0);
                                                        const totalAllDomains = mainDomainsCount + (order.remaining_order_panels ? 
                                                            order.remaining_order_panels.reduce((total, panel) => total + (panel.domains_count || 0), 0) : 0);
                                                        
                                                        return `<strong>Total Inboxes: ${totalAllInboxes} (${totalAllDomains} domains)</strong>`;
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
                                                    <span class="opacity-50">Cold email platform - Login</span>
                                                    <span>${order.reorder_info?.sequencer_login || 'N/A'}</span>
                                                </div>

                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Cold email platform - Password</span>
                                                    <span>${order.reorder_info?.sequencer_password || 'N/A'}</span>
                                                </div>
                                                <div class="d-flex flex-column mb-3">
                                                    <span class="opacity-50">Backup Codes</span>
                                                    <span>${order.reorder_info?.backup_codes || 'N/A'}</span>
                                                </div>
                                                <div class="d-flex flex-column">
                                                    <span class="opacity-50 mb-3">
                                                        <i class="fa-solid fa-globe me-2"></i>All Domains & Panel Breaks
                                                    </span>
                                                    
                                                    <!-- Main Order Domains -->
                                                    <div class="domain-split-container mb-3" style="animation: fadeInUp 0.5s ease-out;">
                                                        <div class="split-header d-flex align-items-center justify-content-between p-2 rounded-top" 
                                                             style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); cursor: pointer;"
                                                             onclick="toggleSplit('main-split-${order.order_id}')">
                                                            <div class="d-flex align-items-center">
                                                                <span class="badge bg-white text-dark me-2" style="font-size: 10px; font-weight: bold; display:none;">
                                                                    Panel Break 01
                                                                </span>
                                                                <small class="text-white fw-bold text-uppercase">PNL-${order.panel_id} | ${order.panel_title || 'N/A'}</small>
                                                            </div>
                                                            <div class="d-flex align-items-center">
                                                                <span class="badge bg-white bg-opacity-25 text-white me-2" style="font-size: 9px;">
                                                                    ${order.splits ? order.splits.reduce((total, split) => total + (split.domains ? (Array.isArray(split.domains) ? split.domains.length : (split.domains ? 1 : 0)) : 0), 0) : 0} domains
                                                                </span>
                                                                <i class="fa-solid fa-copy text-white me-2" style="font-size: 10px; cursor: pointer; opacity: 0.8;" 
                                                                   title="Copy all domains from Panel Break 01" 
                                                                   onclick="event.stopPropagation(); copyAllDomainsFromSplit('main-split-${order.order_id}', 'Panel Break 01')"></i>
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
                                                                        <span class="badge bg-white text-dark me-2" style="font-size: 10px; font-weight: bold; display:none;">
                                                                            Panel Break ${String(index + 2).padStart(2, '0')}
                                                                        </span>
                                                                        <small class="text-white fw-bold text-uppercase">PNL-${panel.panel_id} | ${panel.panel_title || 'N/A'}</small>
                                                                    </div>
                                                                    <div class="d-flex align-items-center">
                                                                        <span class="badge bg-white bg-opacity-25 text-white me-2" style="font-size: 9px;">
                                                                            ${panel.domains_count || 0} domains
                                                                        </span>
                                                                        <i class="fa-solid fa-copy text-white me-2" style="font-size: 10px; cursor: pointer; opacity: 0.8;" 
                                                                           title="Copy all domains from Panel Break ${String(index + 2).padStart(2, '0')}" 
                                                                           onclick="event.stopPropagation(); copyAllDomainsFromSplit('remaining-split-${order.order_id}-${index}', 'Panel Break ${String(index + 2).padStart(2, '0')}')"></i>
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
                                                    <span>${order.reorder_info?.additional_info || 'N/A'}</span>
                                                </div>
                                                <div class="d-flex flex-column mt-3">
                                                    <span class="opacity-50">Master Inbox Email</span>
                                                    <span>${order.reorder_info?.master_inbox_email || 'N/A'}</span>
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


  // timer calculator split 
//     function calculateSplitTime(split) {
//     const order_panel = split.order_panel_data;
//     if (!order_panel || !order_panel.timer_started_at) {
//         return "00:00:00";
//     }

//     const start = parseUTCDateTime(order_panel.timer_started_at);
//     if (!start || isNaN(start.getTime())) {
//         return "00:00:00";
//     }

//   let end;
//   let statusLabel = "";

//   if (order_panel.status === "completed" && order_panel.completed_at) {
//     end = parseUTCDateTime(order_panel.completed_at);
//     if (!end || isNaN(end.getTime())) {
//       return "00:00:00";
//     }
//     statusLabel = "completed in";
//   } else if (order_panel.status === "in-progress") {
//     end = new Date();
//     statusLabel = "in-progress";
//   } else {
//     return "00:00:00";
//   }

//   const diffMs = end - start;
//   if (diffMs <= 0) return `${statusLabel} 00:00:00`;

//   const diffHrs = Math.floor(diffMs / (1000 * 60 * 60));
//   const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
//   const diffSecs = Math.floor((diffMs % (1000 * 60)) / 1000);

//   const pad = (n) => (n < 10 ? "0" + n : n);
//   const formattedTime = `${pad(diffHrs)}:${pad(diffMins)}:${pad(diffSecs)}`;

//   return `${formattedTime}`;
// }



        function parseUTCDateTime(dateStr) {
        const [datePart, timePart] = dateStr.split(" ");
        const [year, month, day] = datePart.split("-").map(Number);
        const [hour, minute, second] = timePart.split(":").map(Number);
        return new Date(Date.UTC(year, month - 1, day, hour, minute, second));
        }

        //timer calculator       


// timer calculator for order
 function handleOrderRelativeTimeCount(order) {
  if (order.status_manage_by_admin !== 'pending') return '';

  // Parse and normalize timestamp
  let rawDate = order.created_at?.replace(' ', 'T') ?? '';
  rawDate = rawDate.replace(/\.\d+/, '');
  if (!rawDate.endsWith('Z')) rawDate += 'Z';

  const createdAt = new Date(rawDate);
  const now = new Date();

  if (isNaN(createdAt.getTime())) return 'Invalid creation time';

  const diffInMs = now.getTime() - createdAt.getTime();

  if (diffInMs < 0) return 'Invalid creation time (in future)';

  const formatHHMMSS = (ms) => {
    const totalSeconds = Math.floor(ms / 1000);
    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    const pad = (n) => (n < 10 ? '0' + n : n);
    return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
  };

  const twelveHoursInMs = 12 * 60 * 60 * 1000;

  if (diffInMs <= twelveHoursInMs) {
    return `Order time: ${formatHHMMSS(diffInMs)}`;
  } else {
    const extraMs = diffInMs - twelveHoursInMs;
    return `Order time exceeded: -${formatHHMMSS(extraMs)}`;
  }
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
                case 'pending': return 'bg-warning text-dark';
                case 'reject': return 'bg-danger';
                case 'in-progress': return 'bg-primary';
                case 'cancelled': return 'bg-danger';
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
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>Panel Break container not found
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
        // Function to assign order to logged-in admin
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

            const response = await fetch(`/admin/panels/assign/${orderPanelId}`, {
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
            
            // Initialize active tab filter (default to active panels)
            currentFilters.is_active = 1;
            
            // Add Load More button event handler
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', loadMorePanels);
            }
            // Wait for ApexCharts to be available
            if (typeof ApexCharts === 'undefined') {
                console.error('ApexCharts not loaded, waiting...');
                setTimeout(() => {
                    loadPanels(currentFilters);
                }, 500);
            } else {
                loadPanels(currentFilters);
            }
            
            // Initialize chevron states on page load
            setTimeout(function() {
                initializeChevronStates();
            }, 200);
            
            // Add event listener for panel reassign modal reset
            const reassignPanelModal = document.getElementById('reassignPanelModal');
            if (reassignPanelModal) {
                reassignPanelModal.addEventListener('hidden.bs.modal', function () {
                    resetReassignModal();
                });
            }
        });
        
</script>

<script>
$('#submitPanelFormBtn').on('click', function(e) {
    e.preventDefault();
    
    // Clear any previous error styling
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    
    const form = $('#panelForm');
    let isValid = true;
    
    // Validate panel title
    const panelTitle = $('#panel_title').val().trim();
    if (!panelTitle) {
        $('#panel_title').addClass('is-invalid');
        $('#panel_title').after('<div class="invalid-feedback">Panel title is required</div>');
        isValid = false;
    } else if (panelTitle.length > 255) {
        $('#panel_title').addClass('is-invalid');
        $('#panel_title').after('<div class="invalid-feedback">Panel title must not exceed 255 characters</div>');
        isValid = false;
    }

    // Validate provider type on create
    const providerTypeEl = $('#provider_type');
    const providerType = providerTypeEl.val();
    const allowedProviders = Array.isArray(PROVIDER_OPTIONS) && PROVIDER_OPTIONS.length
        ? PROVIDER_OPTIONS
        : ['Google', 'Microsoft 365'];
    const isUpdate = form.data('action') === 'update';
    if (!isUpdate) {
        if (!providerType || !allowedProviders.includes(providerType)) {
            providerTypeEl.addClass('is-invalid');
            providerTypeEl.after('<div class="invalid-feedback">Please select a valid provider</div>');
            isValid = false;
        }
    }
    
    // Validate panel limit (only for new panels, not for updates since it's readonly)
    const panelLimit = $('#panel_limit').val();
    
    if (!isUpdate) {
        // Only validate limit for new panels
        if (!panelLimit || parseInt(panelLimit) < 1) {
            $('#panel_limit').addClass('is-invalid');
            $('#panel_limit').after('<div class="invalid-feedback">Panel limit must be at least 1</div>');
            isValid = false;
        }
    } else {
        // For updates, ensure we have a valid limit value
        if (!panelLimit) {
            $('#panel_limit').addClass('is-invalid');
            $('#panel_limit').after('<div class="invalid-feedback">Panel limit is required</div>');
            isValid = false;
        }
    }
    
    // If validation fails, stop here
    if (!isValid) {
        toastr.error('Please correct the validation errors');
        return;
    }
    
    const formData = new FormData(form[0]);
    const panelId = form.data('panel-id');
    
    // Debug logging
    console.log('Form submission details:', {
        isUpdate: isUpdate,
        panelId: panelId,
        action: form.data('action'),
        formData: Object.fromEntries(formData)
    });
    
    // Determine URL and method based on action
    let url, method;
    if (isUpdate) {
        url = `/admin/panels/${panelId}`;
        method = 'POST'; // Use POST with _method override for Laravel
        formData.append('_method', 'PUT'); // Laravel method spoofing
    } else {
        url = "{{ url('admin/panels/create') }}";
        method = 'POST';
    }
    
    console.log('Submitting form:', { isUpdate, url, method });
    
    // Show SweetAlert loading dialog
    Swal.fire({
        title: isUpdate ? 'Updating Panel' : 'Creating Panel',
        text: isUpdate ? 'Please wait while we update the panel...' : 'Please wait while we create the panel...',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: url,
        method: method,
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        beforeSend: function() {
            $('#submitPanelFormBtn').prop('disabled', true).text(isUpdate ? 'Updating...' : 'Creating...');
        },
        success: async function(response) {
            const message = isUpdate ? 'Panel updated successfully!' : 'Panel created successfully!';
            
            // Close loading dialog and show success message
            await Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: message,
                confirmButtonText: 'OK'
            });
            
            // Clear and reset the form
            resetPanelForm();
            $('.form-control').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            
            // Reload panels list with current filters
            loadPanels(currentFilters, 1, false);
            // initializeOrderTrackingTable
            initializeOrderTrackingTable();
            
            // Update panel counters after create/update
            updatePanelCounters();
            
            // Check if response has capacity data
            if (response && response.capacity_data) {
                if (response.capacity_data.adjusted_panels_needed === 0) {
                    closeAllOffcanvas();
                }
            }
            refreshPanelCapacityAlert();
        },
        error: async function(xhr) {
            console.log('Error response:', xhr.responseJSON);
            
            let errorMessage = isUpdate ? 'Failed to update panel. Please try again.' : 'Failed to create panel. Please try again.';
            
            if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                // Handle validation errors
                let errorMessages = [];
                Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                    if (Array.isArray(xhr.responseJSON.errors[key])) {
                        errorMessages = errorMessages.concat(xhr.responseJSON.errors[key]);
                    } else {
                        errorMessages.push(xhr.responseJSON.errors[key]);
                    }
                });
                
                // Join all error messages
                errorMessage = errorMessages.join('\n');
                
            } else if (xhr.responseJSON && xhr.responseJSON.message) {
                // Handle other error messages
                errorMessage = xhr.responseJSON.message;
            }
            
            // Close loading dialog and show error message
            await Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: errorMessage,
                confirmButtonText: 'OK'
            });
        },
        complete: function() {
            $('#submitPanelFormBtn').prop('disabled', false).text(isUpdate ? 'Update Panel' : 'Submit');
        }
    });
});
</script>

<script>
    // Function to close all open offcanvas elements
    function closeAllOffcanvas() {
        // Get all offcanvas elements
        const offcanvasElements = document.querySelectorAll('.offcanvas');
        
        offcanvasElements.forEach(function(offcanvasElement) {
            // Check if the offcanvas is currently shown
            if (offcanvasElement.classList.contains('show')) {
                const offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
                if (offcanvasInstance) {
                    offcanvasInstance.hide();
                }
            }
        });
        
        // Clean up any remaining backdrop elements
        setTimeout(function() {
            const backdrops = document.querySelectorAll('.offcanvas-backdrop, .modal-backdrop');
            backdrops.forEach(backdrop => backdrop.remove());
            
            // Ensure body classes are removed
            document.body.classList.remove('offcanvas-open', 'modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        }, 300);
    }
// Load More button event handler
$('#loadMoreBtn').on('click', function() {
    loadMorePanels();
});

// Filter form submission
$('#filterForm').on('submit', function(e) {
    e.preventDefault();
    
    // Disable submit button to prevent multiple requests
    document.getElementById('submitBtn').disabled = true;
    
    // Get form data
    const formData = new FormData(this);
    const filters = {};
    
    for (let [key, value] of formData.entries()) {
        if (value.trim() !== '') {
            filters[key] = value.trim();
        }
    }
    
    // Update current filters and reset pagination
    currentFilters = filters;
    currentPage = 1;
    
    // Load panels with new filters
    loadPanels(filters, 1, false);
});

// Reset filters
function resetFilters() {
    document.getElementById('filterForm').reset();
    currentFilters = {};
    currentPage = 1;
    loadPanels({}, 1, false);
}

$('#resetFilters').on('click', function() {
    resetFilters();
});

// Edit panel function
async function editPanel(panelId) {
    try {
        // nextPanelIdContainer hide it on edit time
        const nextPanelIdContainer = document.getElementById('nextPanelIdContainer');
        if (nextPanelIdContainer) {
            nextPanelIdContainer.style.display = 'none';
        }
        console.log('Editing panel ID:', panelId);
        console.log('Available panels:', panels);
        
        // First, try to get panel data from the current panels array
        let panel = panels.find(p => p.id == panelId);
        
        // If not found in current array, fetch it from the server
        if (!panel) {
            console.log('Panel not found in local array, fetching from server...');
            const response = await fetch(`/admin/panels/${panelId}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            });
            
            if (!response.ok) {
                throw new Error(`Panel not found: ${response.status} ${response.statusText}`);
            }
            
            const data = await response.json();
            panel = data.panel || data.data || data;
            console.log('Panel fetched from server:', panel);
        }
        
        if (!panel) {
            console.error('Panel data is null or undefined');
            toastr.error('Panel not found');
            return;
        }
        
        // Check if panel can be edited
        if (panel.can_edit === false) {
            toastr.error('Cannot edit panel. Panel has used space and is assigned to orders.');
            return;
        }
        
        // Clear any previous validation errors
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        // Populate the form with existing data
        $('#panel_title').val(panel.title || '');
        $('#panel_description').val(panel.description || '');
        $('#panel_limit').val(panel.limit || '');
        $('#panel_status').val(panel.is_active ? '1' : '0');
        // Provider type is fixed after creation; show if available and disable the field
        if (panel.provider_type) {
            $('#provider_type').val(panel.provider_type);
        }
        $('#provider_type').prop('disabled', true);
        
        // For editing, we want to keep the current limit but make it readonly
        // The limit should not be changed for existing panels
        $('#panel_limit').prop('readonly', true);
        
        // Change form title
        $('#panelFormOffcanvasLabel').text('Edit Panel');
        
        // Store panel ID for update
        $('#panelForm').data('panel-id', panelId);
        $('#panelForm').data('action', 'update');
        
        // Change submit button text
        $('#submitPanelFormBtn').text('Update Panel');
        
        console.log('Form data set for editing:', {
            panelId: panelId,
            action: 'update',
            title: $('#panel_title').val(),
            description: $('#panel_description').val(),
            limit: $('#panel_limit').val(),
            status: $('#panel_status').val()
        });
        
        // Show the offcanvas
        const offcanvasElement = document.getElementById('panelFormOffcanvas');
        const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
        offcanvas.show();
        
    } catch (error) {
        console.error('Error loading panel for editing:', error);
        toastr.error(`Failed to load panel data for editing: ${error.message}`);
    }
}

// Delete panel function
async function deletePanel(panelId) {
    // Get panel data
    const panel = panels.find(p => p.id == panelId);
    if (!panel) {
        toastr.error('Panel not found');
        return;
    }
    
    // Check if panel can be deleted
    if (!panel.can_delete) {
        toastr.error('Cannot delete panel. Panel has used space and is assigned to orders.');
        return;
    }
    
    try {
        // Show SweetAlert confirmation dialog
        const confirmResult = await Swal.fire({
            title: 'Delete Panel?',
            text: `Are you sure you want to delete panel PNL-${panelId}? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            reverseButtons: true
        });

        // If user cancels, exit the function
        if (!confirmResult.isConfirmed) {
            return;
        }
        
        // Show loading dialog with SweetAlert
        Swal.fire({
            title: 'Deleting Panel',
            text: 'Please wait while we delete the panel...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send delete request
        const response = await $.ajax({
            url: `/admin/panels/${panelId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            }
        });

        if (response.success) {
            // Close loading dialog and show success message
            await Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: response.message || 'Panel deleted successfully!',
                confirmButtonText: 'OK'
            });
            
            // Reload panels to reflect changes
            loadPanels(currentFilters, 1, false);
            
            // Update panel counters after delete
            updatePanelCounters();
            
            refreshPanelCapacityAlert();
        } else {
            // Show error message
            await Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: response.message || 'Failed to delete panel',
                confirmButtonText: 'OK'
            });
        }
    } catch (xhr) {
        console.log('Error response:', xhr.responseJSON);
        
        let errorMessage = 'Failed to delete panel. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
        }
        
        // Show error message
        await Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: errorMessage,
            confirmButtonText: 'OK'
        });
    }
}

// Archive panel function
async function archivePanel(panelId) {
    try {
        // Show SweetAlert confirmation dialog
        const confirmResult = await Swal.fire({
            title: 'Archive Panel?',
            text: `Are you sure you want to archive panel PNL-${panelId}? Archived panels will not be used for new orders.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Archive',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#3085d6',
            reverseButtons: true
        });

        // If user cancels, exit the function
        if (!confirmResult.isConfirmed) {
            return;
        }
        
        // Show loading dialog with SweetAlert
        Swal.fire({
            title: 'Archiving Panel',
            text: 'Please wait while we archive the panel...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send archive request (set is_active to false)
        const response = await $.ajax({
            url: `/admin/panels/${panelId}/archive`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: {
                is_active: false
            }
        });

        if (response.success) {
            // Close loading dialog and show success message
            await Swal.fire({
                icon: 'success',
                title: 'Archived!',
                text: response.message || 'Panel archived successfully!',
                confirmButtonText: 'OK'
            });
            
            // Reload panels to reflect changes
            loadPanels(currentFilters, 1, false);
            
            // Update panel counters after archive
            updatePanelCounters();
            
            refreshPanelCapacityAlert();
        } else {
            // Show error message
            await Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: response.message || 'Failed to archive panel',
                confirmButtonText: 'OK'
            });
        }
    } catch (xhr) {
        console.log('Error response:', xhr.responseJSON);
        
        let errorMessage = 'Failed to archive panel. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
        }
        
        // Show error message
        await Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: errorMessage,
            confirmButtonText: 'OK'
        });
    }
}

// Unarchive panel function
async function unarchivePanel(panelId) {
    try {
        // Show SweetAlert confirmation dialog
        const confirmResult = await Swal.fire({
            title: 'Unarchive Panel?',
            text: `Are you sure you want to unarchive panel PNL-${panelId}? This will make it available for new orders.`,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Yes, Unarchive',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#3085d6',
            reverseButtons: true
        });

        // If user cancels, exit the function
        if (!confirmResult.isConfirmed) {
            return;
        }
        
        // Show loading dialog with SweetAlert
        Swal.fire({
            title: 'Unarchiving Panel',
            text: 'Please wait while we unarchive the panel...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Send unarchive request (set is_active to true)
        const response = await $.ajax({
            url: `/admin/panels/${panelId}/archive`,
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            data: {
                is_active: true
            }
        });

        if (response.success) {
            // Close loading dialog and show success message
            await Swal.fire({
                icon: 'success',
                title: 'Unarchived!',
                text: response.message || 'Panel unarchived successfully!',
                confirmButtonText: 'OK'
            });
            
            // Reload panels to reflect changes
            loadPanels(currentFilters, 1, false);
            
            // Update panel counters after unarchive
            updatePanelCounters();
            
            refreshPanelCapacityAlert();
        } else {
            // Show error message
            await Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: response.message || 'Failed to unarchive panel',
                confirmButtonText: 'OK'
            });
        }
    } catch (xhr) {
        console.log('Error response:', xhr.responseJSON);
        
        let errorMessage = 'Failed to unarchive panel. Please try again.';
        if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
        }
        
        // Show error message
        await Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: errorMessage,
            confirmButtonText: 'OK'
        });
    }
}

// Tab switching function
function switchTab(tab) {
    // Clear existing panels and show loading state immediately
    panels = [];
    showLoading();
    
    // Update current tab
    currentTab = tab;
    
    // Update current filters based on tab
    if (tab === 'active') {
        currentFilters.is_active = 1;
        delete currentFilters.is_archived; // Remove any archived filter
    } else if (tab === 'archived') {
        currentFilters.is_active = 0;
        delete currentFilters.is_archived; // Remove any conflicting filters
    }
    
    // Reset pagination
    currentPage = 1;
    hasMorePages = false;
    totalPanels = 0;
    
    // Reload panels with new filter
    loadPanels(currentFilters, 1, false);
}

// Function to create new panel
function createNewPanel() {
    // Reset form for new panel creation
    resetPanelForm();
    // Call fetchNextPanelId and show the offcanvas after it completes
    fetchNextPanelId();
}

// Reset form for new panel creation
function resetPanelForm() {
    // nextPanelIdContainer show it on reset
    const nextPanelIdContainer = document.getElementById('nextPanelIdContainer');
    if (nextPanelIdContainer) {
        nextPanelIdContainer.style.display = 'block';
    }
    $('#panelForm')[0].reset();
    $('#panelForm').removeData('panel-id');
    $('#panelForm').removeData('action');
    $('#panelFormOffcanvasLabel').text('Panel');
    $('#submitPanelFormBtn').text('Submit');
    $('#panel_limit').val(getCapacityForProvider(DEFAULT_PROVIDER_TYPE)); // Reset to default
    // Reset provider type for new panel creation
    $('#provider_type').prop('disabled', false);
    $('#provider_type').val(DEFAULT_PROVIDER_TYPE);
    
    // Clear any validation errors
    $('.form-control').removeClass('is-invalid');
    $('.invalid-feedback').remove();
    
    // Ensure panel_limit is readonly for new panels
    $('#panel_limit').prop('readonly', true);
}

// Initialize on document ready
$(document).ready(function() {
    // Load initial panels
    // loadPanels();

    const providerTypeSelect = document.getElementById('provider_type');
    if (providerTypeSelect) {
        providerTypeSelect.addEventListener('change', function() {
            const limitInput = document.getElementById('panel_limit');
            if (limitInput) {
                limitInput.value = getCapacityForProvider(this.value);
            }
            fetchNextPanelId({ showOffcanvas: false, showLoader: false });
        });
    }
    
    // Reset form when offcanvas is hidden
    $('#panelFormOffcanvas').on('hidden.bs.offcanvas', function () {
        // Only reset if form is not being submitted
        setTimeout(function() {
            resetPanelForm();
        }, 100);
    });
    
    // Auto-refresh panel capacity alert and counters every 60 seconds
    setInterval(function() {
        refreshPanelCapacityAlert();
        
        // Refresh panel counters
        updatePanelCounters();
        
        // Also refresh counters if the order tracking table exists
        if (orderTrackingTable) {
            orderTrackingTable.ajax.reload(null, false);
        } else {
            // If no table, update counters directly
            updateCounters();
        }
    }, 60000); // 60 seconds
});

/**
 * Refresh the panel capacity alert without refreshing the entire page
 */
function refreshPanelCapacityAlert() {
    $.ajax({
        url: '{{ route("admin.panels.capacity-alert") }}',
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                // Update the alert section
                const alertContainer = $('#panelCapacityAlert').parent();
                
                if (response.show_alert) {
                    // Show/update the alert
                    const alertHtml = `
                        <div id="panelCapacityAlert" class="alert alert-danger alert-dismissible fade show py-2 rounded-1" role="alert"
                            style="background-color: rgba(220, 53, 69, 0.2); color: #fff; border: 2px solid #dc3545;">
                            <i class="ti ti-server me-2 alert-icon"></i>
                            <strong>Panel Capacity Alert:</strong>
                            ${response.total_panels_needed} new panel${response.total_panels_needed != 1 ? 's' : ''} required for ${response.insufficient_orders_count} pending order${response.insufficient_orders_count != 1 ? 's' : ''}.
                            <a href="{{ route('admin.panels.index') }}" class="text-light alert-link">Manage Panels</a> to create additional capacity.
                            <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    `;
                    
                    if ($('#panelCapacityAlert').length) {
                        // Update existing alert
                        $('#panelCapacityAlert').replaceWith(alertHtml);
                    } else {
                        // Insert new alert after the order tracking table
                        $('#orderTrackingTableBody').closest('.card').after(alertHtml);
                    }
                } else {
                    // Hide the alert if no longer needed
                    $('#panelCapacityAlert').remove();
                }
                
                console.log('Panel capacity alert refreshed at:', new Date().toLocaleTimeString());
            }
        },
        error: function(xhr, status, error) {
            console.error('Error refreshing panel capacity alert:', error);
        }
    });
}

// Panel Reassignment Functions (from orders.blade.php)
let currentReassignData = {};

/**
 * Open the panel reassignment modal
 */
function openReassignModal(orderId, currentPanelId, orderPanelId, panelTitle) {
    currentReassignData = {
        orderId: orderId,
        currentPanelId: currentPanelId,
        orderPanelId: orderPanelId,
        panelTitle: panelTitle
    };

    // Update modal title
    document.getElementById('reassignModalLabel').innerHTML = `Reassign Panel: ${'PNL-'+currentPanelId +" "+ panelTitle}`;
    
    // Load available panels using orderPanelId
    loadAvailablePanels(orderId, orderPanelId);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('reassignPanelModal'));
    modal.show();
}

/**
 * Load available panels for reassignment
 */
async function loadAvailablePanels(orderId, orderPanelId) {
    try {
        showReassignLoading(true);
        
        const response = await fetch(`/admin/orders/${orderId}/order-panels/${orderPanelId}/available-for-reassignment`);
        const data = await response.json();
        
        if (data.success) {
            renderAvailablePanels(data.panels);
        } else {
            showReassignError(data.error || 'Failed to load available panels');
        }
    } catch (error) {
        console.error('Error loading available panels:', error);
        showReassignError('Failed to load available panels');
    } finally {
        showReassignLoading(false);
    }
}

/**
 * Render available panels in the modal
 */
function renderAvailablePanels(panels) {
    const container = document.getElementById('availablePanelsContainer');
    
    if (!panels || panels.length === 0) {
        container.innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-info-circle text-muted mb-3" style="font-size: 2rem;"></i>
                <p class="text-muted mb-0">No panels available for reassignment</p>
            </div>
        `;
        return;
    }

    // Add search input
    const searchHtml = `
        <div class="mb-3">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>
                <input type="text" class="form-control border-start-0" id="panelSearchInput" 
                       placeholder="Search panels by ID or title..." onkeyup="filterPanels()">
            </div>
        </div>
    `;
    
    const panelsHtml = panels.map(panel => `
        <div class="panel-option mb-2 border rounded-3 shadow-sm position-relative overflow-hidden panel-card" 
             data-panel-id="${panel.panel_id}"
             data-panel-title="${panel.panel_title.toLowerCase()}"
             data-space-needed="${panel.space_needed || 0}"
             data-panel-limit="${panel.panel_limit}"
             data-panel-remaining="${panel.panel_remaining_limit}"
             ${panel.is_reassignable ? `onclick="selectTargetPanel(${panel.panel_id}, '${panel.panel_title}', ${panel.space_needed || 0}, ${panel.panel_remaining_limit})"` : ''} 
             style="${panel.is_reassignable ? 'cursor: pointer; transition: all 0.2s ease;' : 'cursor: not-allowed; opacity: 0.6;'}">
            
            ${panel.is_reassignable ? '' : '<div class="position-absolute top-0 start-0 w-100 h-100 bg-light bg-opacity-75 d-flex align-items-center justify-content-center" style="z-index: 2;"><span class="badge bg-warning text-dark">Insufficient Space</span></div>'}
            
            <div class="p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center">
                        <div class="panel-icon me-2">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-gradient" 
                                 style="width: 35px; height: 35px;">
                                <i class="fas fa-server text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h6 class="mb-0 fw-bold">
                                <span class="badge bg-info bg-gradient me-2 px-2 py-1 small">PNL-${panel.panel_id}</span>
                                <span class="panel-title-text">${panel.panel_title}</span>
                            </h6>
                        </div>
                    </div>
                    
                    ${panel.is_reassignable ? 
                        `<button type="button" class="btn btn-outline-primary btn-sm px-3 select-btn" 
                             onclick="selectTargetPanel(${panel.panel_id}, '${panel.panel_title}', ${panel.space_needed || 0}, ${panel.panel_remaining_limit})">
                            <i class="fas fa-arrow-right me-1"></i>Select
                        </button>` : ''
                    }
                </div>
                
                <div class="row g-2 mt-1">
                    <div class="col-3">
                        <div class="text-center p-2 rounded bg-light">
                            <div class="fw-bold text-success panel-space-needed" style="font-size: 0.9rem;">${panel.space_needed || 0}</div>
                            <small class="text-muted">Need</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center p-2 rounded bg-light">
                            <div class="fw-bold text-primary" style="font-size: 0.9rem;">${panel.total_orders || 0}</div>
                            <small class="text-muted">Orders</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center p-2 rounded bg-light">
                            <div class="fw-bold text-warning" style="font-size: 0.9rem;">${panel.panel_limit}</div>
                            <small class="text-muted">Limit</small>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="text-center p-2 rounded bg-light">
                            <div class="fw-bold text-danger panel-remaining" style="font-size: 0.9rem;">${panel.panel_remaining_limit}</div>
                            <small class="text-muted">Free</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    container.innerHTML = searchHtml + '<div id="panelsList">' + panelsHtml + '</div>';
    
    // Add CSS for hover effects
    const style = document.createElement('style');
    style.textContent = `
        .panel-card{
            border: 1px solid #dee2e6;
        }
        .panel-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        }
        .panel-card.selected {
            border-color: #0d6efd !important;
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.1) 0%, rgba(102, 126, 234, 0.05) 100%) !important;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25) !important;
        }
        .badge-sm {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
    `;
    document.head.appendChild(style);
}

/**
 * Filter panels based on search input
 */
function filterPanels() {
    const searchTerm = document.getElementById('panelSearchInput').value.toLowerCase();
    const panelCards = document.querySelectorAll('.panel-card');
    let visibleCount = 0;
    
    panelCards.forEach(card => {
        const panelId = card.getAttribute('data-panel-id');
        const panelTitle = card.getAttribute('data-panel-title');
        
        const isVisible = panelId.includes(searchTerm) || panelTitle.includes(searchTerm);
        
        if (isVisible) {
            card.style.display = 'block';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    const panelsList = document.getElementById('panelsList');
    let noResultsDiv = document.getElementById('noSearchResults');
    
    if (visibleCount === 0 && searchTerm.length > 0) {
        if (!noResultsDiv) {
            noResultsDiv = document.createElement('div');
            noResultsDiv.id = 'noSearchResults';
            noResultsDiv.className = 'text-center py-4';
            noResultsDiv.innerHTML = `
                <i class="fas fa-search text-muted mb-2" style="font-size: 1.5rem;"></i>
                <p class="text-muted mb-0">No panels found matching "${searchTerm}"</p>
            `;
            panelsList.appendChild(noResultsDiv);
        }
    } else if (noResultsDiv) {
        noResultsDiv.remove();
    }
}

/**
 * Select target panel for reassignment
 */
function selectTargetPanel(targetPanelId, targetPanelTitle, spaceNeeded = 0, remainingSpace = 0) {
    // Remove previous selection
    document.querySelectorAll('.panel-card').forEach(option => {
        option.classList.remove('selected');
    });

    // Highlight selected panel
    const selectedPanel = document.querySelector(`[data-panel-id="${targetPanelId}"]`);
    if (selectedPanel) {
        selectedPanel.classList.add('selected');
    }

    // Update space values dynamically
    updatePanelSpaceValues(targetPanelId, spaceNeeded);

    // Store selection
    currentReassignData.targetPanelId = targetPanelId;
    currentReassignData.targetPanelTitle = targetPanelTitle;
    currentReassignData.spaceNeeded = spaceNeeded;
    currentReassignData.remainingSpace = remainingSpace;

    // Enable reassign button
    const reassignBtn = document.getElementById('confirmReassignBtn');
    reassignBtn.disabled = false;
    reassignBtn.innerHTML = `<i class="fas fa-exchange-alt me-1"></i>Reassign to ${targetPanelTitle}`;
}

/**
 * Update panel space values after selection
 */
function updatePanelSpaceValues(selectedPanelId, spaceToMove) {
    // Get current space needed from the selected order panel (from currentReassignData)
    const currentSpaceNeeded = spaceToMove;
    
    // First, reset all panels to their original values
    document.querySelectorAll('.panel-card').forEach(panelOption => {
        const originalSpaceNeeded = parseInt(panelOption.getAttribute('data-space-needed')) || 0;
        const originalRemaining = parseInt(panelOption.getAttribute('data-panel-remaining')) || 0;
        
        const spaceNeededElement = panelOption.querySelector('.panel-space-needed');
        const remainingElement = panelOption.querySelector('.panel-remaining');
        
        // Reset to original values and styles
        if (spaceNeededElement) {
            spaceNeededElement.textContent = originalSpaceNeeded;
            spaceNeededElement.style.color = '';
            spaceNeededElement.style.fontWeight = '';
        }
        if (remainingElement) {
            remainingElement.textContent = originalRemaining;
            remainingElement.style.color = '';
            remainingElement.style.fontWeight = '';
        }
    });
    
    // Then update only the selected panel to show new values after reassignment
    document.querySelectorAll('.panel-card').forEach(panelOption => {
        const panelId = panelOption.getAttribute('data-panel-id');
        const originalSpaceNeeded = parseInt(panelOption.getAttribute('data-space-needed')) || 0;
        const originalRemaining = parseInt(panelOption.getAttribute('data-panel-remaining')) || 0;
        
        const spaceNeededElement = panelOption.querySelector('.panel-space-needed');
        const remainingElement = panelOption.querySelector('.panel-remaining');
        // not need to add on need 
        if (panelId == selectedPanelId) {
            // This panel will receive the space
            const newSpaceNeeded = originalSpaceNeeded + currentSpaceNeeded;
            const newRemaining = originalRemaining - currentSpaceNeeded;
            
            // if (spaceNeededElement) {
            //     spaceNeededElement.textContent = newSpaceNeeded;
            //     spaceNeededElement.style.color = '#198754'; // Green for increase
            //     spaceNeededElement.style.fontWeight = 'bold';
            // }
            if (remainingElement) {
                remainingElement.textContent = newRemaining;
                remainingElement.style.color = newRemaining < 0 ? '#dc3545' : '#dc3545'; // Red
                remainingElement.style.fontWeight = 'bold';
            }
        }
    });
}

/**
 * Confirm panel reassignment
 */

async function confirmReassignment() {
    if (!currentReassignData.targetPanelId) {
        showReassignError('Please select a target panel');
        return;
    }

    try {
        // Show SweetAlert2 confirmation dialog
        const result = await Swal.fire({
            title: 'Confirm Panel Reassignment?',
            html: `
                <div class="text-start">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <div class="card-body text-center text-white">
                                    <i class="fas fa-exchange-alt fs-2 mb-2"></i>
                                    <h4 class="card-title mb-1 fw-bold">PNL-${currentReassignData.currentPanelId}</h4>
                                    <p class="mb-1 fw-semibold">${currentReassignData.panelTitle}</p>
                                    <small class="text-white-50">From Panel</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);">
                                <div class="card-body text-center text-white">
                                    <i class="fas fa-arrow-right fs-2 mb-2"></i>
                                    <h4 class="card-title mb-1 fw-bold">PNL-${currentReassignData.targetPanelId}</h4>
                                    <p class="mb-1 fw-semibold">${currentReassignData.targetPanelTitle}</p>
                                    <small class="text-white-50">To Panel</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                                <div class="card-body text-center text-white">
                                    <i class="fas fa-inbox fs-2 mb-2"></i>
                                    <h4 class="card-title mb-1 fw-bold">${currentReassignData.spaceNeeded || 0}</h4>
                                    <small class="text-white-50">Spaces to Transfer</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3 mb-0" style="font-size: 14px;">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> After this action is completed, the selected spaces will be transferred from the source panel to the destination panel.
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-exchange-alt me-1"></i>Confirm Reassignment',
            cancelButtonText: '<i class="fas fa-times me-1"></i>Cancel',
            reverseButtons: true,
            customClass: {
                popup: 'swal-wide'
            }
        });

        // If user cancels, return early
        if (!result.isConfirmed) {
            return;
        }

        // Show SweetAlert2 loading dialog
        Swal.fire({
            title: 'Reassigning Panel...',
            html: `
                <div class="text-center">
                    <div class="spinner-border text-warning mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p>Please wait while we reassign the panel...</p>
                    <small class="text-muted">This may take a few moments</small>
                </div>
            `,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            customClass: {
                popup: 'swal-loading'
            }
        });

        const formData = {
            from_order_panel_id: currentReassignData.orderPanelId,
            to_panel_id: currentReassignData.targetPanelId,
            reason: document.getElementById('reassignReason').value || null
        };

        const response = await fetch('/admin/orders/panels/reassign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(formData)
        });

        const data = await response.json();

        if (data.success) {
            // Close loading dialog and show success
            await Swal.fire({
                title: 'Reassignment Successful!',
                html: `
                    <div class="text-center">
                        <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                        <p class="mb-2">${data.message}</p>
                        <small class="text-muted">Panel has been successfully reassigned</small>
                    </div>
                `,
                icon: 'success',
                confirmButtonColor: '#28a745',
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: true,
                confirmButtonText: 'Great!'
            });
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('reassignPanelModal')).hide();
            
            // Refresh the panel orders view
            const currentPanelId = currentReassignData.currentPanelId;
            if (currentPanelId) {
                setTimeout(() => {
                    viewPanelOrders(currentPanelId);
                }, 1000);
            }
            
            // Refresh panels list
            loadPanels(currentFilters, 1, false);
            
            // Reset form
            resetReassignModal();
        } else {
            // Close loading dialog and show error
            await Swal.fire({
                title: 'Reassignment Failed!',
                html: `
                    <div class="text-center">
                        <i class="fas fa-exclamation-triangle text-danger mb-3" style="font-size: 3rem;"></i>
                        <p class="mb-2">${data.message || 'Reassignment failed'}</p>
                        <small class="text-muted">Please try again or contact support</small>
                    </div>
                `,
                icon: 'error',
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Try Again'
            });
            
            showReassignError(data.message || 'Reassignment failed');
        }
    } catch (error) {
        console.error('Error during reassignment:', error);
        
        // Close loading dialog and show error
        await Swal.fire({
            title: 'Error!',
            html: `
                <div class="text-center">
                    <i class="fas fa-times-circle text-danger mb-3" style="font-size: 3rem;"></i>
                    <p class="mb-2">An error occurred during reassignment</p>
                    <small class="text-muted">Please check your connection and try again</small>
                </div>
            `,
            icon: 'error',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'OK'
        });
        
        showReassignError('An error occurred during reassignment');
    }
}

/**
 * Show/hide loading state in reassign modal
 */
function showReassignLoading(show) {
    const loader = document.getElementById('reassignLoader');
    const container = document.getElementById('availablePanelsContainer');
    
    if (show) {
        loader.style.display = 'block';
        container.style.display = 'none';
    } else {
        loader.style.display = 'none';
        container.style.display = 'block';
    }
}

/**
 * Show error in reassign modal
 */
function showReassignError(message) {
    const container = document.getElementById('availablePanelsContainer');
    container.innerHTML = `
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            ${message}
        </div>
    `;
}

/**
 * Reset reassign modal
 */
function resetReassignModal() {
    currentReassignData = {};
    document.getElementById('availablePanelsContainer').innerHTML = '';
    document.getElementById('reassignReason').value = '';
    document.getElementById('confirmReassignBtn').disabled = true;
    document.getElementById('confirmReassignBtn').innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Select Panel First';
    
    // Reset any modified space values and styles
    document.querySelectorAll('.panel-space-needed, .panel-remaining').forEach(element => {
        element.style.color = '';
        element.style.fontWeight = '';
    });
    
    // Remove any search results
    const noResultsDiv = document.getElementById('noSearchResults');
    if (noResultsDiv) {
        noResultsDiv.remove();
    }
}

// Function to show customized note modal
function showCustomizedNoteModal(note) {
    // Decode HTML entities
    const decodedNote = note.replace(/&apos;/g, "'").replace(/&quot;/g, '"');
    
    // Set the note content
    document.getElementById('customizedNoteContent').textContent = decodedNote;
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('customizedNoteModal'));
    modal.show();
}

</script>
@endpush

<!-- Customized Note Modal -->
<div class="modal fade" id="customizedNoteModal" tabindex="-1" aria-labelledby="customizedNoteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="background: #1d2239;">
            <div class="modal-body p-0">
                <div class="position-relative overflow-hidden rounded-4 border-0 shadow-sm" 
                    style="background: linear-gradient(135deg, #1d2239 0%, #252c4a 100%);">
                    <!-- Close Button -->
                    <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 mt-3 me-3" 
                            style="z-index: 10;" data-bs-dismiss="modal" aria-label="Close"></button>
                    
                    <!-- Decorative Background Pattern -->
                    <div class="position-absolute top-0 start-0 w-100 h-100 opacity-10">
                       <div class="position-absolute" style="top: -20px; right: -20px; width: 80px; height: 80px; background: linear-gradient(45deg, #667eea, #764ba2); border-radius: 50%; opacity: 0.3;"></div>
                       <div class="position-absolute" style="bottom: -10px; left: -10px; width: 60px; height: 60px; background: linear-gradient(45deg, #667eea, #4facfe); border-radius: 50%; opacity: 0.2;"></div>
                    </div>
                    
                    <!-- Content Container -->
                    <div class="position-relative p-4">
                       <!-- Header with Icon -->
                       <div class="d-flex align-items-center mb-3">
                          <div class="me-3 d-flex align-items-center justify-content-center" 
                              style="width: 45px; height: 45px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);">
                             <i class="fa-solid fa-sticky-note text-white fs-5"></i>
                          </div>
                          <div>
                             <h6 class="mb-0 fw-bold text-white">Customized Note</h6>
                             <small class="text-light opacity-75">Additional information provided</small>
                          </div>
                       </div>
                       
                       <!-- Note Content -->
                       <div class="p-4 rounded-3 border-0 position-relative overflow-hidden" 
                           style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.12) 0%, rgba(118, 75, 162, 0.08) 100%); border-left: 4px solid #667eea !important; border: 1px solid rgba(102, 126, 234, 0.2);">
                          <!-- Quote Icon -->
                          <div class="position-absolute top-0 start-0 mt-2 ms-3">
                             <i class="fas fa-quote-left text-primary opacity-25 fs-4"></i>
                          </div>
                          
                          <!-- Note Text -->
                          <div class="ms-4">
                             <p class="mb-0 text-white fw-medium" id="customizedNoteContent" 
                                style="line-height: 1.7; font-size: 15px; text-indent: 1rem;">
                                <!-- Note content will be populated by JavaScript -->
                             </p>
                          </div>
                          
                          <!-- Bottom Quote Icon -->
                          <div class="position-absolute bottom-0 end-0 mb-2 me-3">
                             <i class="fas fa-quote-right text-primary opacity-25 fs-4"></i>
                          </div>
                       </div>
                       
                       <!-- Bottom Accent Line -->
                       <div class="mt-3 mx-auto rounded-pill" 
                           style="width: 60px; height: 3px; background: linear-gradient(90deg, #667eea, #764ba2);"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- when panel save then  -->
