@extends('admin.layouts.app')

@section('title', 'Pool Panels')

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

        .card {
            overflow: hidden;
            position: relative;
        }

        .button-container {
            pointer-events: none;
            transition: opacity 0.4s ease, visibility 0.4s ease;
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            opacity: 0;
            visibility: hidden;
            z-index: 10;
        }

        .card:hover .button-container {
            opacity: 1;
            visibility: visible;
            pointer-events: all;
        }

        .card:focus-within .button-container {
            opacity: 1;
            visibility: visible;
            pointer-events: all;
        }

        /* Offcanvas custom styling */
        .offcanvas-end {
            width: 400px;
        }

        .m-btn {
            background-color: var(--second-primary);
            color: var(--light-color);
            border: 1px solid var(--second-primary);
            transition: all 0.3s ease;
        }

        .m-btn:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: var(--light-color);
        }

        .domain-split-container {
            transition: all 0.2s ease;
            border-radius: 12px;
            overflow: hidden;
        }

        .domain-split-container:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.25);
        }

        .domain-list-content {
            background-color: rgba(87, 80, 191, 0.12);
        }

        .domain-list-content .badge {
            background-color: rgba(255, 255, 255, 0.9);
            color: #1d2239;
        }

        .domain-list-content.collapse {
            display: none;
        }

        .domain-list-content.collapse.show {
            display: block;
        }

        .split-header {
            transition: all 0.2s ease;
        }

        .split-header:hover {
            filter: brightness(1.05);
        }

        .domains-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .domains-grid .domain-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.95);
            color: #1d2239;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .domains-grid .domain-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .domains-grid .domain-badge .badge {
            font-size: 9px;
            margin-left: 4px;
            margin-right: 0;
        }
    </style>
@endpush

@section('content')
    <section class="py-3">
        <!-- Pool Awaited Panel Allocation Offcanvas -->
        <div class="offcanvas offcanvas-start"
            style="min-width: 70%;  background-color: var(--filter-color); backdrop-filter: blur(5px); border: 3px solid var(--second-primary);"
            tabindex="-1" id="poolAllocationOffcanvas" aria-labelledby="poolAllocationOffcanvasLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="poolAllocationOffcanvasLabel">Pool Awaited Panel Allocation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div class="counters mb-3">
                    <div class="p-3 filter">
                        <div>
                            <div class="d-flex align-items-start justify-content-between">
                                <div class="content-left">
                                    <h6 class="text-heading">Number of Pools</h6>
                                    <div class="d-flex align-items-center my-1">
                                        <h4 class="mb-0 me-2 fs-2" id="pools_counter">0</h4>
                                        <p class="text-success mb-0"></p>
                                    </div>
                                    <small class="mb-0"></small>
                                </div>
                                <div class="avatar">
                                    <i class="fa-solid fa-water fs-2"></i>
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
                                        <h4 class="mb-0 me-2 fs-2" id="pool_inboxes_counter">0</h4>
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
                                    <h6 class="text-heading">Pool Panels Required</h6>
                                    <div class="d-flex align-items-center my-1">
                                        <h4 class="mb-0 me-2 fs-2" id="pool_panels_counter">0</h4>
                                        <p class="text-success mb-0"></p>
                                    </div>
                                    <small class="mb-0"></small>
                                </div>
                                <div class="avatar">
                                    <i class="fa-solid fa-layer-group fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="poolTable" class="w-100 display">
                        <thead style="position: sticky; top: 0;">
                            <tr>
                                <th>Pool ID</th>
                                <th>Date</th>
                                <!-- <th>Plan</th>
                                <th>Domain URL</th> -->
                                <th>Total Inboxes</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="overflow-y-auto" id="poolTableBody">
                            <!-- Dynamic data will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pool Panel Form Offcanvas -->
        <div class="offcanvas offcanvas-end" tabindex="-1" data-bs-backdrop="static" id="poolPanelFormOffcanvas" aria-labelledby="staticBackdropLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="poolPanelFormOffcanvasLabel">Pool Panel</h5>
                <button type="button" class="bg-transparent border-0 fs-5" data-bs-dismiss="offcanvas" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="offcanvas-body">
                <form id="poolPanelForm" class="">
                    <div class="mb-3" id="poolPanelIdContainer" style="display: none;">
                        <label for="pool_panel_id" id="poolPanelIdLabel">Pool Panel ID:</label>
                        <input type="text" class="form-control" id="pool_panel_id" name="pool_panel_id" value="" readonly>
                        <small class="text-muted" id="poolPanelIdHint">This ID will be automatically generated</small>
                    </div>

                    <label for="pool_panel_title">Title: <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="pool_panel_title" name="title" value="" required maxlength="255">

                    <label for="provider_type" class="mt-3">Provider Type: <span class="text-danger">*</span></label>
                    <select class="form-control mb-3" id="provider_type" name="provider_type" required>
                        <option value="" disabled>Select provider</option>
                        <option value="Google" selected>Google</option>
                        <option value="Microsoft 365">Microsoft 365</option>
                    </select>


                    <label for="pool_panel_description" class="mt-3">Description:</label>
                    <textarea class="form-control mb-3" id="pool_panel_description" name="description" rows="3"></textarea>


                    <label for="panel_limit">Limit: <span class="text-danger">*</span></label>
                    <input type="number" class="form-control mb-3" id="panel_limit" name="panel_limit" value="{{ env('PANEL_CAPACITY', 1790) }}"
                        required min="1" readonly>


                    <label for="pool_panel_status">Status:</label>
                    <select class="form-control mb-3" name="is_active" id="pool_panel_status" required>
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>

                    <div class="mt-4">
                        <button type="button" id="submitPoolPanelFormBtn" class="m-btn py-2 px-4 rounded-2 w-100 border-0">
                            Submit
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Pool Panel Pools Offcanvas -->
        <div class="offcanvas offcanvas-end" style="width: 100%;" tabindex="-1" id="poolPanelPoolsOffcanvas"
            aria-labelledby="poolPanelPoolsOffcanvasLabel" data-bs-backdrop="true" data-bs-scroll="false">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="poolPanelPoolsOffcanvasLabel">Pool Panel Pools</h5>
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-dismiss="offcanvas" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="offcanvas-body">
                <div id="poolPanelPoolsContainer">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading pools...</span>
                        </div>
                        <p class="mt-2 mb-0">Loading pools...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pool Panel Capacity Alert -->
        @php
            // Get pool panel capacity alert data
            $pendingPools = \App\Models\Pool::where('status', 'pending')
                ->where('is_splitting', 0) // Only get pools that are not currently being split
                ->whereNotNull('total_inboxes')
                ->where('total_inboxes', '>', 0)
                ->orderBy('created_at', 'asc') // Process older pools first
                ->get();

            $insufficientSpacePools = [];
            $totalPoolPanelsNeeded = 0;
            $poolPanelCapacity = env('PANEL_CAPACITY', 1790);
            $maxSplitCapacity = env('MAX_SPLIT_CAPACITY', 358);

            // Helper function to get available pool panel space
            $getAvailablePoolPanelSpace = function (int $poolSize) use ($poolPanelCapacity, $maxSplitCapacity) {
                // Get active pool panels with remaining capacity
                $availablePoolPanels = \App\Models\PoolPanel::where('is_active', 1)->where('remaining_limit', '>', 0)->get();

                $totalSpace = 0;
                foreach ($availablePoolPanels as $poolPanel) {
                    $totalSpace += min($poolPanel->remaining_limit, $maxSplitCapacity);
                }

                return $totalSpace;
            };

            foreach ($pendingPools as $pool) {
                // Calculate available space for this pool
                $availableSpace = $getAvailablePoolPanelSpace($pool->total_inboxes);

                if ($pool->total_inboxes > $availableSpace) {
                    // Calculate pool panels needed for this pool
                    $poolPanelsNeeded = ceil($pool->total_inboxes / $maxSplitCapacity);
                    $insufficientSpacePools[] = $pool;
                    $totalPoolPanelsNeeded += $poolPanelsNeeded;
                }
            }

            // Adjust total pool panels needed based on available pool panels
            $availablePoolPanelCount = \App\Models\PoolPanel::where('is_active', true)->where('remaining_limit', '>=', $maxSplitCapacity)->count();

            $adjustedPoolPanelsNeeded = max(0, $totalPoolPanelsNeeded - $availablePoolPanelCount);
        @endphp

        @if ($adjustedPoolPanelsNeeded > 0)
            <div id="poolPanelCapacityAlert" class="alert alert-warning alert-dismissible fade show py-2 rounded-1" role="alert"
                style="background-color: rgba(255, 193, 7, 0.2); color: #fff; border: 2px solid #ffc107;">
                <i class="ti ti-layer-group me-2 alert-icon"></i>
                <strong>Pool Panel Capacity Alert:</strong>
                {{ $adjustedPoolPanelsNeeded }} new pool panel{{ $adjustedPoolPanelsNeeded != 1 ? 's' : '' }} required for
                {{ count($insufficientSpacePools) }} pending pool{{ count($insufficientSpacePools) != 1 ? 's' : '' }}.
                <a href="javascript:void(0)" onclick="showPoolAllocationDetails()" class="text-light alert-link">View Details</a> to see pending pools.
                <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="counters mb-3">
            <div class="card p-3 counter_1">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Total Pool Panels</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="total_counter">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            <i class="fa-solid fa-layer-group fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_2">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Active Pool Panels</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="active_counter">0</h4>
                                <p class="text-success mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            <i class="fa-solid fa-layer-group fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_3">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Archived Pool Panels</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="inactive_counter">0</h4>
                                <p class="text-danger mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            <i class="fa-solid fa-layer-group fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card p-3 counter_4">
                <div>
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <h6 class="text-heading">Created Today</h6>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2 fs-2" id="today_counter">0</h4>
                                <p class="text-info mb-0"></p>
                            </div>
                            <small class="mb-0"></small>
                        </div>
                        <div class="avatar">
                            <i class="fa-solid fa-layer-group fs-2"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pool Allocation Button -->
        <div class="mb-3 d-flex justify-content-end">
            <button type="button" class="btn btn-warning btn-sm border-0 px-3" onclick="showPoolAllocationDetails()"
                title="View pools awaiting panel allocation">
                <i class="fa-solid fa-water me-2"></i>
                View Pending Pools
                @if ($adjustedPoolPanelsNeeded > 0)
                    <span class="badge bg-danger ms-2">{{ count($insufficientSpacePools) }}</span>
                @endif
            </button>
        </div>

        <!-- Advanced Search Filter UI -->
        <div class="card p-3 mb-4">
            <div class="d-flex align-items-center justify-content-between" data-bs-toggle="collapse" href="#filter_1" role="button"
                aria-expanded="false" aria-controls="filter_1">
                <div>
                    <div class="d-flex gap-2 align-items-center">
                        <h6 class="text-uppercase fs-6 mb-0">Filters</h6>
                        <img src="https://static.vecteezy.com/system/resources/previews/052/011/341/non_2x/3d-white-down-pointing-backhand-index-illustration-png.png"
                            width="30" alt="">
                    </div>
                    <small>Click here to open advance search for pool panels</small>
                </div>
            </div>
            <div class="row collapse" id="filter_1">
                <form id="filterForm">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Pool Panel ID</label>
                            <input type="text" name="panel_id" class="form-control" placeholder="Enter pool panel ID">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Title</label>
                            <input type="text" name="title" class="form-control" placeholder="Search by title">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label mb-0">Order</label>
                            <select name="order" class="form-select">
                                <option value="desc">Newest First</option>
                                <option value="asc">Oldest First</option>
                            </select>
                        </div>
                        <div class="col-12 text-end">
                            <button type="button" id="resetFilters" class="btn btn-outline-secondary btn-sm me-2 px-3">
                                <span id="resetText">Reset</span>
                                <span id="resetSpinner" class="spinner-border spinner-border-sm ms-2" role="status" style="display: none;">
                                    <span class="visually-hidden">Loading...</span>
                                </span>
                            </button>
                            <button type="submit" id="submitBtn" class="btn btn-primary btn-sm border-0 px-3">
                                <span id="searchText">Search</span>
                                <span id="searchSpinner" class="spinner-border spinner-border-sm ms-2" role="status" style="display: none;">
                                    <span class="visually-hidden">Loading...</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Tabs for Active and Archived Pool Panels --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <ul class="nav nav-pills" id="poolPanelTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="active-tab" data-bs-toggle="pill" data-bs-target="#active-pool-panels" type="button"
                        role="tab" aria-controls="active-pool-panels" aria-selected="true" onclick="switchTab('active')">
                        <i class="fa-solid fa-check-circle me-1"></i> Active Pool Panels
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="archived-tab" data-bs-toggle="pill" data-bs-target="#archived-pool-panels" type="button"
                        role="tab" aria-controls="archived-pool-panels" aria-selected="false" onclick="switchTab('archived')">
                        <i class="fa-solid fa-archive me-1"></i> Archived Pool Panels
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
                {{-- create pool panel button --}}
                <button type="button" class="btn btn-primary btn-sm border-0 px-3" data-bs-toggle="offcanvas"
                    data-bs-target="#poolPanelFormOffcanvas" onclick="openCreateForm();">
                    <i class="fa-solid fa-plus me-2"></i>
                    Create New Pool Panel
                </button>
            </div>
        </div>

        <!-- Loading state -->
        <div id="loadingState"
            style="display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 mb-0">Loading pool panels...</p>
        </div>

        <!-- Google Pool Panels Section -->
        <div id="googlePoolPanelsSection" style="display: none;">
            <div class="d-flex align-items-center mb-3">
                <i class="fab fa-google me-2 text-danger" style="font-size: 1.5rem;"></i>
                <h5 class="mb-0">Google Pool Panels</h5>
                <span class="badge bg-primary ms-2" id="googlePoolPanelsCount">0</span>
            </div>
            <div id="googlePoolPanelsContainer"
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            </div>
        </div>

        <!-- Microsoft 365 Pool Panels Section -->
        <div id="microsoftPoolPanelsSection" style="display: none;">
            <div class="d-flex align-items-center mb-3">
                <i class="fab fa-microsoft me-2 text-info" style="font-size: 1.5rem;"></i>
                <h5 class="mb-0">Microsoft 365 Pool Panels</h5>
                <span class="badge bg-primary ms-2" id="microsoftPoolPanelsCount">0</span>
            </div>
            <div id="microsoftPoolPanelsContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;">
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
                Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalPoolPanels">0</span>
                pool panels
            </div>
        </div>

        <!-- Empty state -->
        <div id="emptyState" style="display: none; text-align: center; padding: 3rem 0; min-height: 300px;">
            <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem;"></i>
            <h5>No Pool Panels Found</h5>
            <p class="mb-3">No pool panels match your current filters.</p>
        </div>
    </section>

    <!-- Pool Panel Reassignment Modal -->
    <div class="modal fade" id="poolReassignModal" tabindex="-1" aria-labelledby="poolReassignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning bg-opacity-10">
                    <h5 class="modal-title" id="poolReassignModalLabel">Reassign Pool Panel Split</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="poolReassignAlert" class="alert alert-danger d-none" role="alert"></div>
                    <div class="mb-3">
                        <strong>Reassignment:</strong> Select a target pool panel that has capacity and does not already contain this pool.
                    </div>
                    <div class="mb-3">
                        <label for="poolReassignReason" class="form-label">Reason for Reassignment (Optional)</label>
                        <textarea class="form-control" id="poolReassignReason" rows="2" placeholder="Provide context for this reassignment"></textarea>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Pool Panel</th>
                                    <th>Remaining</th>
                                    <th>Total Splits</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="poolReassignTableBody">
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="spinner-border text-warning" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" id="confirmPoolReassignBtn" disabled onclick="confirmPoolReassignment()">
                        <i class="fas fa-exchange-alt me-1"></i>Select Panel First
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let currentPoolPanelId = null;
        let isEditMode = false;
        let currentPage = 1;
        let hasMorePages = true;
        let isLoading = false;
        let currentFilters = {};
        let poolPanels = [];
        let charts = {}; // Store chart instances
        const PANEL_CAPACITY_FALLBACK = {{ env('PANEL_CAPACITY', 1790) }};
        const DEFAULT_PROVIDER_TYPE = 'Google';
        let currentPoolReassignData = {};
        let poolReassignModalInstance = null;

        function getCapacityForProvider(providerType) {
            // In the future, you can expand this with a map from the backend if capacities differ
            if (providerType === 'Microsoft 365') {
                return {{ env('MICROSOFT_PANEL_CAPACITY', 1790) }}; // Example for different capacity
            }
            if (providerType === 'Google') {
                return {{ env('GOOGLE_PANEL_CAPACITY', 1790) }}; // Example for different capacity
            }
            return PANEL_CAPACITY_FALLBACK;
        }

        $(document).ready(function() {
            // Set initial filter to show only active pool panels
            currentFilters.status = 1;

            // Load initial data
            loadPoolPanels();
            updateCounters();

            const providerTypeSelect = document.getElementById('provider_type');
            if (providerTypeSelect) {
                providerTypeSelect.addEventListener('change', function() {
                    // When provider changes in create mode, fetch new ID and update limit
                    if (!isEditMode) {
                        fetchNextPoolPanelId({
                            showOffcanvas: false,
                            showLoader: false
                        });
                    }
                });
            }

            // Form submission handler
            $('#submitPoolPanelFormBtn').on('click', function() {
                const form = $('#poolPanelForm');
                const formData = form.serialize();
                const submitBtn = $(this);
                const originalText = submitBtn.text();

                // Show Swal loader
                Swal.fire({
                    title: isEditMode ? 'Updating Pool Panel...' : 'Creating Pool Panel...',
                    html: 'Please wait while we process your request.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Disable submit button and show loading
                submitBtn.prop('disabled', true).text('Processing...');

                // Clear previous errors
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                const url = isEditMode ?
                    '{{ route('admin.pool-panels.update', ':id') }}'.replace(':id', currentPoolPanelId) :
                    '{{ route('admin.pool-panels.store') }}';
                const method = isEditMode ? 'PUT' : 'POST';

                $.ajax({
                    url: url,
                    method: method,
                    data: formData + '&_token={{ csrf_token() }}',
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: response.message || (isEditMode ? 'Pool Panel updated successfully!' :
                                'Pool Panel created successfully!'),
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Close offcanvas and clean up backdrop
                        const offcanvasElement = document.getElementById('poolPanelFormOffcanvas');
                        const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                        
                        // Listen for the hidden event to clean up and reload
                        offcanvasElement.addEventListener('hidden.bs.offcanvas', function() {
                            // Remove any lingering backdrops
                            document.querySelectorAll('.offcanvas-backdrop').forEach(backdrop => {
                                backdrop.remove();
                            });
                            
                            // Remove modal-open class from body if present
                            document.body.classList.remove('modal-open');
                            document.body.style.overflow = '';
                            document.body.style.paddingRight = '';
                            
                            // Reset form
                            resetForm();
                            
                            // Reload data
                            resetAndReload();
                        }, { once: true });
                        
                        offcanvas.hide();
                    },
                    error: function(xhr) {
                        Swal.close(); // Close the loading dialog

                        if (xhr.status === 422) {
                            // Validation errors
                            const errors = xhr.responseJSON.errors;
                            let errorMessages = [];
                            $.each(errors, function(field, messages) {
                                const input = $(`[name="${field}"]`);
                                input.addClass('is-invalid');
                                input.after(`<div class="invalid-feedback">${messages[0]}</div>`);
                                errorMessages.push(messages[0]);
                            });

                            Swal.fire({
                                icon: 'error',
                                title: 'Validation Error',
                                html: errorMessages.join('<br>'),
                                confirmButtonText: 'OK'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: xhr.responseJSON?.message || 'An error occurred while processing your request.',
                                confirmButtonText: 'OK'
                            });
                        }
                    },
                    complete: function() {
                        // Close Swal loading if still open and re-enable submit button
                        if (Swal.isVisible()) {
                            Swal.close();
                        }
                        submitBtn.prop('disabled', false).text(originalText);
                    }
                });
            });

            document.getElementById('poolPanelFormOffcanvas')
            .addEventListener('hidden.bs.offcanvas', function () {
                cleanOffcanvasBackdrop();
            });


            function cleanOffcanvasBackdrop() {
                document.querySelectorAll('.offcanvas-backdrop').forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }


            // Filter form submission
            $('#filterForm').on('submit', function(e) {
                e.preventDefault();

                // Prevent multiple submissions if already loading
                if (isLoading) {
                    return false;
                }

                // Show Swal loader for search
                Swal.fire({
                    title: 'Searching Pool Panels...',
                    html: 'Please wait while we filter the results.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Disable buttons and show loading state
                const searchBtn = $('#submitBtn');
                const resetBtn = $('#resetFilters');

                searchBtn.prop('disabled', true);
                resetBtn.prop('disabled', true);

                // Show spinner and update text
                $('#searchText').text('Searching...');
                $('#searchSpinner').show();

                const formData = new FormData(this);
                currentFilters = Object.fromEntries(formData.entries());
                resetAndReload();
            });

            // Reset filters
            $('#resetFilters').on('click', function() {
                // Prevent multiple clicks if already loading
                if (isLoading) {
                    return false;
                }

                // Show Swal loader for reset
                Swal.fire({
                    title: 'Resetting Filters...',
                    html: 'Please wait while we reset the filters.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Disable both buttons during reset
                const searchBtn = $('#submitBtn');
                const resetBtn = $('#resetFilters');

                searchBtn.prop('disabled', true);
                resetBtn.prop('disabled', true);

                // Show spinner and update text for reset button
                $('#resetText').text('Resetting...');
                $('#resetSpinner').show();

                $('#filterForm')[0].reset();
                currentFilters = {};
                resetAndReload();
            });

            // Load more button
            $('#loadMoreBtn').on('click', function() {
                if (!isLoading && hasMorePages) {
                    loadPoolPanels(false);
                }
            });
        });

        // Load pool panels with pagination
        async function loadPoolPanels(resetData = true) {
            if (isLoading) {
                return;
            }

            const pageToFetch = resetData ? 1 : currentPage + 1;
            isLoading = true;

            if (resetData) {
                currentPage = 1;
                hasMorePages = true;
                $('#googlePoolPanelsContainer, #microsoftPoolPanelsContainer').html('');
                $('#googlePoolPanelsSection, #microsoftPoolPanelsSection, #emptyState').hide();
                $('#loadingState').html(`
            <div style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 mb-0">Loading pool panels...</p>
            </div>
        `);
            } else {
                $('#loadMoreSpinner').show();
                $('#loadMoreText').text('Loading...');
            }

            try {
                const params = new URLSearchParams({
                    page: pageToFetch,
                    per_page: 12,
                    ...currentFilters
                });

                const response = await fetch(`{{ route('admin.pool-panels.data') }}?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to fetch pool panels');
                }

                const payload = await response.json();

                if (payload.success === false) {
                    throw new Error(payload.message || 'Failed to fetch pool panels');
                }

                const fetchedPanels = payload.data || [];
                const pagination = payload.pagination || {};

                currentPage = pagination.current_page ?? pageToFetch;
                hasMorePages = Boolean(pagination.has_more_pages);

                if (resetData) {
                    poolPanels = fetchedPanels;
                    renderPoolPanels();
                } else {
                    poolPanels = poolPanels.concat(fetchedPanels);
                    appendPoolPanels(fetchedPanels);
                }

                updatePaginationInfo(pagination);

                if (hasMorePages) {
                    $('#loadMoreContainer').show();
                } else {
                    $('#loadMoreContainer').hide();
                }
            } catch (error) {
                console.error('Error loading pool panels:', error);
                showErrorState(error.message);
            } finally {
                isLoading = false;
                $('#loadMoreSpinner').hide();
                $('#loadMoreText').text('Load More');
            }
        }

        // Render pool panels
        function renderPoolPanels() {
            const googleContainer = document.getElementById('googlePoolPanelsContainer');
            const microsoftContainer = document.getElementById('microsoftPoolPanelsContainer');
            const googleSection = document.getElementById('googlePoolPanelsSection');
            const microsoftSection = document.getElementById('microsoftPoolPanelsSection');
            const emptyState = document.getElementById('emptyState');
            const googleCountBadge = document.getElementById('googlePoolPanelsCount');
            const microsoftCountBadge = document.getElementById('microsoftPoolPanelsCount');
            const loadingState = document.getElementById('loadingState');

            if (loadingState) loadingState.style.display = 'none';

            if (!poolPanels || poolPanels.length === 0) {
                googleSection.style.display = 'none';
                microsoftSection.style.display = 'none';
                emptyState.style.display = 'block';
                return;
            }

            emptyState.style.display = 'none';

            const googlePanels = poolPanels.filter(p => p.provider_type === 'Google');
            const microsoftPanels = poolPanels.filter(p => p.provider_type === 'Microsoft 365');

            const googleHtml = googlePanels.map(panel => createPoolPanelCard(panel)).join('');
            const microsoftHtml = microsoftPanels.map(panel => createPoolPanelCard(panel)).join('');

            googleContainer.innerHTML = googleHtml;
            microsoftContainer.innerHTML = microsoftHtml;

            googleSection.style.display = googlePanels.length > 0 ? 'block' : 'none';
            microsoftSection.style.display = microsoftPanels.length > 0 ? 'block' : 'none';

            if (googleCountBadge) googleCountBadge.textContent = googlePanels.length;
            if (microsoftCountBadge) microsoftCountBadge.textContent = microsoftPanels.length;

            setTimeout(() => {
                poolPanels.forEach(panel => {
                    initChart(panel);
                });
            }, 100);
        }

        // Append pool panels for load more
        function appendPoolPanels(newPanels) {
            const googleContainer = document.getElementById('googlePoolPanelsContainer');
            const microsoftContainer = document.getElementById('microsoftPoolPanelsContainer');

            if (!newPanels || newPanels.length === 0) {
                return;
            }

            const googlePanels = newPanels.filter(p => p.provider_type === 'Google');
            const microsoftPanels = newPanels.filter(p => p.provider_type === 'Microsoft 365');

            if (googlePanels.length > 0) googleContainer.insertAdjacentHTML('beforeend', googlePanels.map(p => createPoolPanelCard(p)).join(''));
            if (microsoftPanels.length > 0) microsoftContainer.insertAdjacentHTML('beforeend', microsoftPanels.map(p => createPoolPanelCard(p)).join(''));

            setTimeout(() => {
                newPanels.forEach(panel => {
                    initChart(panel);
                });
            }, 100);
        }

        // Create pool panel card HTML
        function createPoolPanelCard(poolPanel) {
            const total = Number(poolPanel.limit) || 1790;
            const remaining = Number(poolPanel.remaining_limit ?? total);
            const used = Number(poolPanel.used ?? (total - remaining));
            const totalPools = poolPanel.total_pools ?? 0;
            const totalSplits = poolPanel.total_splits ?? 0;
            const totalAssigned = poolPanel.total_assigned_space ?? used;

            const PROVIDER_BADGE_MAP = {
                'Google': {
                    className: 'bg-danger text-white',
                    icon: 'fab fa-google'
                },
                'Microsoft 365': {
                    className: 'bg-primary text-white',
                    icon: 'fab fa-microsoft'
                },
            };
            const providerConfig = PROVIDER_BADGE_MAP[poolPanel.provider_type] || {
                className: 'bg-secondary text-white',
                icon: 'fas fa-server'
            };
            const providerBadge = `<span class="badge ${providerConfig.className}"><i class="${providerConfig.icon}"></i></span>`;

            const statusBadge = poolPanel.is_active ?
                '<span class="badge bg-success">Active</span>' :
                '<span class="badge bg-danger">Inactive</span>';

            let actionButtons = `
        <div class="d-flex flex-column gap-2">

            ${poolPanel.show_edit_delete_buttons ? `
                    ${poolPanel.can_edit ? `
                    <button class="btn btn-sm btn-outline-primary px-2 py-1"
                            onclick="openEditForm(${poolPanel.id})"
                            title="Edit Pool Panel">
                        <i class="fas fa-edit"></i>
                    </button>
                ` : ''}
                    ${poolPanel.can_delete ? `
                    <button class="btn btn-sm btn-outline-danger px-2 py-1"
                            onclick="deletePoolPanel(${poolPanel.id})"
                            title="Delete Pool Panel">
                        <i class="fas fa-trash"></i>
                    </button>
                ` : ''}
                ` : ''}
            ${poolPanel.is_active ? `
                    <button class="btn btn-sm btn-outline-secondary px-2 py-1"
                            onclick="archivePoolPanel(${poolPanel.id})"
                            title="Archive Pool Panel">
                        <i class="fas fa-archive"></i>
                    </button>
                ` : `
                    <button class="btn btn-sm btn-outline-success px-2 py-1"
                            onclick="unarchivePoolPanel(${poolPanel.id})"
                            title="Unarchive Pool Panel">
                        <i class="fas fa-undo"></i>
                    </button>
                `}
        </div>
    `;

            const archivedStyle = !poolPanel.is_active ? 'background-color: #334761;' : '';

            return `
        <div class="card p-3 d-flex flex-column gap-2 position-relative" style="${archivedStyle}">
            <div class="d-flex flex-column gap-1">
                <small class="opacity-75">${poolPanel.auto_generated_id || 'PPN-' + poolPanel.id}</small>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <h6 class="mb-0">Title: ${poolPanel.title || 'N/A'}</h6>
                    ${providerBadge}
                    ${!poolPanel.is_active ? '<span class="badge bg-secondary ms-1">Archived</span>' : ''}
                    ${statusBadge}
                </div>
                <p class="small mb-0 text-light">${poolPanel.description ? poolPanel.description : ''}</p>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge bg-primary bg-opacity-25 text-light">
                        <i class="fa-solid fa-water me-1"></i> Pools: ${totalPools}
                    </span>
                    <span class="badge bg-secondary bg-opacity-25 text-light d-none">
                        <i class="fa-solid fa-layer-group me-1"></i> Splits: ${totalSplits}
                    </span>
                    <span class="badge bg-info bg-opacity-25 text-light d-none">
                        <i class="fa-solid fa-chart-simple me-1"></i> Assigned: ${totalAssigned}
                    </span>
                </div>
            </div>

            <div class="d-flex gap-3 justify-content-between">
                <small class="total">Total: ${total}</small>
                <small class="remain">Remaining: ${remaining}</small>
                <small class="used">Used: ${used}</small>
            </div>

            <div id="chart-${poolPanel.id}"></div>

            <div class="d-flex align-items-center justify-content-between gap-2">
                <small class="opacity-75">Created by: ${poolPanel.creator ? poolPanel.creator.name : 'Unknown'}</small>
                <button style="font-size: 12px"
                        onclick="viewPoolPanelPools(${poolPanel.id})"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#poolPanelPoolsOffcanvas"
                        class="btn border-0 btn-sm py-0 px-3 rounded-1 btn-primary">
                    View Pools
                </button>
            </div>

            <div class="button-container p-2 rounded-2" style="background-color: var(--filter-color);">
                ${actionButtons}
            </div>
        </div>
    `;
        }

        // Load pool details into offcanvas
        async function viewPoolPanelPools(poolPanelId) {
            const safePoolPanelId = Number(poolPanelId);
            if (Number.isNaN(safePoolPanelId)) {
                console.error('Invalid pool panel id supplied to viewPoolPanelPools:', poolPanelId);
                return;
            }

            const container = document.getElementById('poolPanelPoolsContainer');
            if (container) {
                container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading pools...</span>
                </div>
                <p class="mt-2 mb-0">Loading pools...</p>
            </div>
        `;
            }

            const offcanvasElement = document.getElementById('poolPanelPoolsOffcanvas');
            const offcanvasInstance = bootstrap.Offcanvas.getOrCreateInstance(offcanvasElement);
            offcanvasInstance.show();

            try {
                const url = '{{ route('admin.pool-panels.pools', '__POOL_PANEL_ID__') }}'.replace('__POOL_PANEL_ID__', safePoolPanelId);
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load pool data');
                }

                const data = await response.json();
                if (data.success === false) {
                    throw new Error(data.message || 'Failed to load pool data');
                }

                renderPoolPanelPools(data.pools || [], data.pool_panel || {});
            } catch (error) {
                console.error('Error loading pool panel pools:', error);
                if (container) {
                    container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-exclamation-triangle text-danger fs-3 mb-3"></i>
                    <h5>Failed to load pools</h5>
                    <p>${error.message || 'Please try again later.'}</p>
                    <button class="btn btn-primary" onclick="viewPoolPanelPools(${safePoolPanelId})">Retry</button>
                </div>
            `;
                }
            }
        }

        // Render pools and splits inside offcanvas
        function renderPoolPanelPools(pools, poolPanel) {
            const container = document.getElementById('poolPanelPoolsContainer');
            if (!container) {
                return;
            }

            if (!Array.isArray(pools) || pools.length === 0) {
                container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-database text-muted fs-3 mb-3"></i>
                <h5>No Pools Assigned</h5>
                <p class="mb-0">This pool panel does not have any pools allocated yet.</p>
            </div>
        `;
                return;
            }

            const headerHtml = `
        <div class="mb-4">
            <h6 class="mb-1">${poolPanel.auto_generated_id || ('PPN-' + (poolPanel.id ?? ''))}</h6>
            <p class="mb-0 text-light small">${poolPanel.title || ''}</p>
            <div class="d-flex flex-wrap gap-2 mt-2">
                <span class="badge bg-primary text-light">Limit: ${poolPanel.limit ?? 0}</span>
                <span class="badge bg-warning text-dark">Remaining: ${poolPanel.remaining_limit ?? 0}</span>
                <span class="badge bg-success text-light">Used: ${(poolPanel.limit ?? 0) - (poolPanel.remaining_limit ?? 0)}</span>
            </div>
        </div>
    `;

            const accordionHtml = pools.map((pool, index) => {
                const collapseId = `pool-collapse-${pool.pool_id}`;
                const iconId = `pool-accordion-icon-${pool.pool_id}`;
                const poolInfo = pool.pool || {};
                const splits = pool.splits || [];
                const totalInboxes = pool.total_inboxes ?? 0;
                const assignedSpace = pool.assigned_space ?? 0;
                const availableSpace = pool.available_space ?? Math.max(totalInboxes - assignedSpace, 0);
                const poolStatus = poolInfo.status || 'unknown';
                const poolStatusClass = getPoolStatusBadgeClass(poolStatus);
                const poolStatusLabel = (poolStatus || 'Unknown').toString().replace(/_/g, ' ');
                const detailBadges = [];

                if (poolInfo.plan_name) {
                    detailBadges.push(`<span class="badge bg-secondary bg-opacity-25 text-light">Plan: ${poolInfo.plan_name}</span>`);
                }

                if (poolInfo.inboxes_per_domain) {
                    // detailBadges.push(`<span class="badge bg-primary bg-opacity-25 text-light">Inboxes/Domain: ${poolInfo.inboxes_per_domain}</span>`);
                }

                if (poolInfo.created_at) {
                    // detailBadges.push(`<span class="badge bg-dark bg-opacity-25 text-light">Created: ${formatDate(poolInfo.created_at)}</span>`);
                }

                const headerPanel = pool.panel || poolInfo.panel || poolPanel || {};

                const splitsRows = splits.length ?
                    splits.map((split, splitIndex) => {
                        return `
                    ${(() => {
                        const splitPanel = split.panel || headerPanel;
                        const panelId = splitPanel?.auto_generated_id || (splitPanel?.id ? `PPN-${splitPanel.id}` : poolPanel.auto_generated_id || (poolPanel.id ? `PPN-${poolPanel.id}` : 'N/A'));
                        const panelTitle = splitPanel?.title || poolPanel.title || 'N/A';

                        return `
                                <tr>
                                    <td>${splitIndex + 1}</td>
                                    <td>${panelId}</td>
                                    <td>${panelTitle}</td>
                                    <td>${split.inboxes_per_domain ?? 0}</td>
                                    <td>${split.total_inboxes ?? 0}</td>
                                    <td>${split.created_at ? formatDate(split.created_at) : 'N/A'}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-warning" onclick="openPoolReassignModal(${pool.pool_id}, ${splitPanel?.id || poolPanel.id}, ${split.id}, '${panelTitle.replace(/'/g, "\\'")}')">
                                            <i class="fas fa-exchange-alt"></i> Reassign
                                        </button>
                                    </td>
                                </tr>
                            `;
                    })()}
                `;
                    }).join('') :
                    `<tr><td colspan="6" class="text-center text-muted">No splits recorded for this pool.</td></tr>`;

                return `
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <div class="button p-3 collapsed d-flex align-items-center justify-content-between" type="button"
                        aria-expanded="false"
                        aria-controls="${collapseId}"
                        onclick="togglePoolAccordion('${collapseId}', this, event)">
                        <div class="d-flex flex-column">
                            <small>POOL ID: #${pool.pool_id ?? 'N/A'}</small>
                            <small class="text-light d-none">Splits: ${pool.total_splits ?? splits.length}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                            <span class="badge bg-white text-dark" style="font-size: 10px; font-weight: bold; display:none;">Panel ID: ${headerPanel?.auto_generated_id || (headerPanel?.id ? `PPN-${headerPanel.id}` : poolPanel.auto_generated_id || (poolPanel.id ? `PPN-${poolPanel.id}` : 'N/A'))}</span>
                            <span class="badge bg-white text-dark" style="font-size: 10px; font-weight: bold; display:none;">Panel: ${headerPanel?.title || poolPanel.title || 'N/A'}</span>
                            <span class="badge ${poolStatusClass}" style="font-size: 10px;">${poolStatusLabel}</span>
                            <span class="badge bg-info bg-opacity-25 text-light" style="font-size: 10px;  display:none;">Total Inboxes: ${totalInboxes}</span>
                            <span class="badge bg-success bg-opacity-25 text-light" style="font-size: 10px; display:none;">Assigned: ${assignedSpace}</span>
                            <span class="badge bg-warning bg-opacity-25 text-dark" style="font-size: 10px; display:none;">Available: ${availableSpace}</span>
                            <i class="fas fa-chevron-down transition-transform" id="${iconId}" style="font-size: 12px;"></i>
                        </div>
                    </div>
                </h2>
                <div id="${collapseId}" class="accordion-collapse collapse" data-bs-parent="#poolPanelPoolsAccordion">
                    <div class="accordion-body">
        ${detailBadges.length ? `<div class="mb-3 d-flex flex-wrap gap-2">${detailBadges.join('')}</div>` : ''}
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Panel ID</th>
                        <th>Panel Name</th>
                        <th>Inboxes/Domain</th>
                        <th>Total Inboxes</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${splitsRows}
                                </tbody>
                            </table>
                        </div>
                        ${renderPoolDetailSections(poolInfo, splits, poolPanel)}
                        ${renderOtherPanelSplits(pool.other_panel_splits || [], poolPanel)}
                    </div>
                </div>
            </div>
        `;
            }).join('');

            container.innerHTML = `
        ${headerHtml}
        <div class="accordion accordion-flush" id="poolPanelPoolsAccordion">
            ${accordionHtml}
        </div>
    `;

            setTimeout(() => {
                initializePoolSplitAccordions();
            }, 0);
        }

        // Toggle pool accordions and rotate icon
        function togglePoolAccordion(targetId, buttonElement, event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }

            const target = document.getElementById(targetId);
            if (!target) {
                return;
            }

            const poolId = targetId.replace('pool-collapse-', '');
            const arrowIcon = document.getElementById(`pool-accordion-icon-${poolId}`);
            const isExpanded = target.classList.contains('show');

            if (isExpanded) {
                target.classList.remove('show');
                target.classList.add('collapse');
                buttonElement.setAttribute('aria-expanded', 'false');
                buttonElement.classList.add('collapsed');
                if (arrowIcon) {
                    arrowIcon.style.transform = 'rotate(0deg)';
                }
            } else {
                // Close other accordions
                document.querySelectorAll('#poolPanelPoolsAccordion .accordion-collapse.show').forEach(openItem => {
                    openItem.classList.remove('show');
                    openItem.classList.add('collapse');
                    const btn = openItem.previousElementSibling?.querySelector('.button');
                    if (btn) {
                        btn.setAttribute('aria-expanded', 'false');
                        btn.classList.add('collapsed');
                    }
                    const icon = openItem.previousElementSibling?.querySelector('.fas.fa-chevron-down');
                    if (icon) {
                        icon.style.transform = 'rotate(0deg)';
                    }
                });

                target.classList.add('show');
                target.classList.remove('collapse');
                buttonElement.setAttribute('aria-expanded', 'true');
                buttonElement.classList.remove('collapsed');
                if (arrowIcon) {
                    arrowIcon.style.transform = 'rotate(180deg)';
                }
            }
        }

        // Initialize chart for a pool panel
        function initChart(poolPanel) {
            // Check if ApexCharts is available
            if (typeof ApexCharts === 'undefined') {
                console.error('ApexCharts is not loaded');
                return;
            }

            const chartElement = document.querySelector(`#chart-${poolPanel.id}`);
            if (!chartElement) {
                console.warn(`Chart element #chart-${poolPanel.id} not found`);
                return;
            }

            const total = poolPanel.limit || 1790;
            const used = poolPanel.used_limit || 0;
            const remaining = poolPanel.remaining_limit || total;

            // Avoid division by zero
            if (total === 0) {
                console.warn(`Pool Panel ${poolPanel.id} has zero total limit`);
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
                    }
                }
            };

            try {
                // Clean up existing chart if it exists
                if (charts[poolPanel.id]) {
                    charts[poolPanel.id].destroy();
                }

                const chart = new ApexCharts(chartElement, options);
                chart.render();
                charts[poolPanel.id] = chart;
            } catch (error) {
                console.error(`Error creating chart for pool panel ${poolPanel.id}:`, error);
            }
        }

        // Update counters
        async function updateCounters() {
            try {
                const response = await fetch(`{{ route('admin.pool-panels.index') }}?counters=1`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) throw new Error('Failed to fetch counters');

                const data = await response.json();

                if (data.counters) {
                    $('#total_counter').text(data.counters.total || 0);
                    $('#active_counter').text(data.counters.active || 0);
                    $('#inactive_counter').text(data.counters.inactive || 0);
                    $('#today_counter').text(data.counters.today || 0);
                }
            } catch (error) {
                console.error('Error updating counters:', error);
            }
        }

        // Update pagination info
        function updatePaginationInfo(pagination) {
            $('#showingFrom').text(pagination?.from ?? 0);
            $('#showingTo').text(pagination?.to ?? 0);
            $('#totalPoolPanels').text(pagination?.total ?? 0);
        }

        // Show error state
        function showErrorState(message = 'Failed to load pool panels. Please try again later.') {
            const emptyStateContainer = document.getElementById('emptyState');
            emptyStateContainer.style.display = 'block';
            emptyStateContainer.innerHTML = `
        <div class="empty-state" style="grid-column: 1 / -1;">
            <i class="ti ti-alert-circle text-danger"></i>
            <h5>Error Loading</h5>
            <p>${message}</p>
            <button class="btn btn-primary btn-sm" onclick="resetAndReload()">Retry</button>
        </div>
    `;

            // Also show Swal error
            Swal.fire({
                icon: 'error',
                title: 'Loading Error',
                text: message,
                confirmButtonText: 'OK'
            });
        }

        // Reset and reload data
        function resetAndReload() {
            currentPage = 1;
            hasMorePages = true;

            // Load data and then re-enable filter buttons
            Promise.all([loadPoolPanels(true), updateCounters()])
                .finally(() => {
                    // Close Swal loader if open
                    if (Swal.isVisible()) {
                        Swal.close();
                    }
                    // Re-enable filter buttons after operation completes
                    resetFilterButtonStates();
                });
        }

        // Format date helper
        function formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function renderPoolDetailSections(poolInfo, splits, poolPanel) {
            const emailCard = buildEmailConfigurationsCard(poolInfo, splits);
            const domainCard = buildDomainsConfigurationCard(poolInfo, splits, poolPanel);

            return `
        <div class="row mt-4">
            <div class="col-md-6">${emailCard}</div>
            <div class="col-md-6">${domainCard}</div>
        </div>
    `;
        }

        function buildEmailConfigurationsCard(poolInfo, splits) {
            const {
                summaryText,
                breakdownHtml
            } = computePoolSplitSummary(poolInfo, splits);

            return `
        <div class="card p-3 mb-3">
            <h6 class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                    <i class="fa-regular fa-envelope"></i>
                </div>
                Email configurations
            </h6>
            <div class="d-flex align-items-center justify-content-between">
                <span>${summaryText}</span>
            </div>
            ${breakdownHtml ? `<div class="mt-3 text-white small">${breakdownHtml}</div>` : ''}
            <hr>
            <div class="d-flex flex-column">
                <span class="opacity-50">Prefix Variants</span>
                ${renderPrefixVariantsFromPool(poolInfo)}
            </div>
            <div class="d-flex flex-column mt-3">
                <span class="opacity-50">Profile Picture URL</span>
                <span>${formatLinkValue(poolInfo?.profile_picture_link)}</span>
            </div>
            <div class="d-flex flex-column mt-3">
                <span class="opacity-50">Email Persona Picture URL</span>
                <span>${formatLinkValue(poolInfo?.email_persona_picture_link)}</span>
            </div>
            <div class="d-flex flex-column mt-3">
                <span class="opacity-50">Email Persona Password</span>
                <span>${formatValue(poolInfo?.email_persona_password)}</span>
            </div>
            <div class="d-flex flex-column mt-3">
                <span class="opacity-50">Persona Password</span>
                <span>${formatValue(poolInfo?.persona_password)}</span>
            </div>
        </div>
    `;
        }

        function computePoolSplitSummary(poolInfo, splits) {
            if (!Array.isArray(splits) || splits.length === 0) {
                const totalInboxes = Number(poolInfo?.total_inboxes) || 0;
                return {
                    summaryText: totalInboxes ? `<strong>Total Inboxes: ${totalInboxes}</strong>` : 'N/A',
                    breakdownHtml: ''
                };
            }

            const inboxesPerDomain = Number(poolInfo?.inboxes_per_domain) || 0;
            let totalDomains = 0;
            let totalInboxes = 0;

            const breakdownHtml = splits.map((split, index) => {
                const domainsCount = Number(split.domains_count ?? (Array.isArray(split.domain_names) ? split.domain_names.length : 0));
                const splitTotalInboxes = Number(split.total_inboxes ?? (domainsCount * inboxesPerDomain));

                totalDomains += domainsCount;
                totalInboxes += splitTotalInboxes;

                return `
            <div class="text-white">
                <span class="badge bg-white text-dark me-1" style="font-size: 10px; font-weight: bold;">Pool Split ${String(index + 1).padStart(2, '0')}</span>
                Inboxes: ${splitTotalInboxes} (${domainsCount} domains${inboxesPerDomain ? ` x ${inboxesPerDomain}` : ''})
            </div>
        `;
            }).join('');

            if (!totalInboxes && Number(poolInfo?.total_inboxes)) {
                totalInboxes = Number(poolInfo.total_inboxes);
            }

            return {
                summaryText: totalInboxes ?
                    `<strong>Total Inboxes: ${totalInboxes}${totalDomains ? ` (${totalDomains} domains)` : ''}</strong>` :
                    'N/A',
                breakdownHtml
            };
        }

        function renderPrefixVariantsFromPool(poolInfo) {
            if (!poolInfo) {
                return '<span>N/A</span>';
            }

            const variants = [];

            if (poolInfo.prefix_variants) {
                try {
                    const prefixVariants = typeof poolInfo.prefix_variants === 'string' ?
                        JSON.parse(poolInfo.prefix_variants) :
                        poolInfo.prefix_variants;

                    if (prefixVariants && typeof prefixVariants === 'object') {
                        Object.values(prefixVariants).forEach((value, index) => {
                            if (value) {
                                variants.push(`<span>Variant ${index + 1}: ${value}</span>`);
                            }
                        });
                    }
                } catch (error) {
                    console.warn('Unable to parse pool prefix variants', error);
                }
            }

            if (variants.length === 0) {
                if (poolInfo.prefix_variant_1) {
                    variants.push(`<span>Variant 1: ${poolInfo.prefix_variant_1}</span>`);
                }
                if (poolInfo.prefix_variant_2) {
                    variants.push(`<span>Variant 2: ${poolInfo.prefix_variant_2}</span>`);
                }
            }

            if (variants.length === 0 && poolInfo.prefix_variants_details) {
                try {
                    const details = typeof poolInfo.prefix_variants_details === 'string' ?
                        JSON.parse(poolInfo.prefix_variants_details) :
                        poolInfo.prefix_variants_details;

                    if (Array.isArray(details)) {
                        details.forEach((detail, index) => {
                            if (detail) {
                                variants.push(`<span>Variant ${index + 1}: ${detail}</span>`);
                            }
                        });
                    }
                } catch (error) {
                    console.warn('Unable to parse pool prefix variant details', error);
                }
            }

            return variants.length > 0 ? variants.join('') : '<span>N/A</span>';
        }

        function buildDomainsConfigurationCard(poolInfo, splits, poolPanel) {
            return `
        <div class="card p-3 overflow-y-auto" style="max-height: 30rem">
            <h6 class="d-flex align-items-center gap-2">
                <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                    <i class="fa-solid fa-earth-europe"></i>
                </div>
                Domains &amp; Configuration
            </h6>

            <div class="d-flex flex-column mb-3">
                <span class="opacity-50">Hosting Platform</span>
                <span>${formatValue(poolInfo?.hosting_platform || poolInfo?.other_platform)}</span>
            </div>

            <div class="d-flex flex-column mb-3">
                <span class="opacity-50">Platform Login</span>
                <span>${formatValue(poolInfo?.platform_login)}</span>
            </div>

            <div class="d-flex flex-column mb-3">
                <span class="opacity-50">Platform Password</span>
                <span>${formatValue(poolInfo?.platform_password)}</span>
            </div>

            <div class="d-flex flex-column mb-3">
                <span class="opacity-50">Domain Forwarding Destination URL</span>
                <span>${formatLinkValue(poolInfo?.forwarding_url)}</span>
            </div>

            <div class="d-flex flex-column mb-3">
                <span class="opacity-50">Sending Platform</span>
                <span>${formatValue(poolInfo?.sending_platform)}</span>
            </div>

            <div class="d-flex flex-column mb-3">
                <span class="opacity-50">Cold email platform - Login</span>
                <span>${formatValue(poolInfo?.sequencer_login)}</span>
            </div>

            <div class="d-flex flex-column mb-3">
                <span class="opacity-50">Cold email platform - Password</span>
                <span>${formatValue(poolInfo?.sequencer_password)}</span>
            </div>

            <div class="d-flex flex-column">
                <span class="opacity-50 mb-3">
                    <i class="fa-solid fa-globe me-2"></i>All Domains & Pool Splits
                </span>
                ${renderPoolDomainsList(poolInfo, splits, poolPanel)}
            </div>

            <div class="d-flex flex-column mt-3">
                <span class="opacity-50">Additional Notes</span>
                <span>${formatValue(poolInfo?.additional_info)}</span>
            </div>

            <div class="d-flex flex-column mt-3">
                <span class="opacity-50">Master Inbox Email</span>
                <span>${formatValue(poolInfo?.master_inbox_email)}</span>
            </div>
        </div>
    `;
        }

        function renderPoolDomainsList(poolInfo, splits, poolPanel) {
            if (Array.isArray(splits) && splits.length > 0) {
                const inboxesPerDomain = Number(poolInfo?.inboxes_per_domain) || Number(poolPanel?.inboxes_per_domain) || 0;

                return splits.map((split, index) => {
                    const domainDetails = Array.isArray(split.domain_details) ? split.domain_details : [];
                    const fallbackDomains = Array.isArray(split.domain_names) ? split.domain_names : [];
                    const normalizedDomains = domainDetails.length ?
                        domainDetails.map(detail => detail.name).filter(Boolean) :
                        fallbackDomains.map(normalizeDomainValue).filter(Boolean);
                    const domainCount = normalizedDomains.length;
                    const totalInboxes = Number(split.total_inboxes ?? (domainCount * inboxesPerDomain));
                    const splitPanel = split.panel || poolPanel || {};
                    const panelId = splitPanel.auto_generated_id || (splitPanel.id ? `PPN-${splitPanel.id}` : poolPanel?.auto_generated_id || (
                        poolPanel?.id ? `PPN-${poolPanel.id}` : 'N/A'));
                    const panelTitle = splitPanel.title || poolPanel?.title || 'N/A';

                    const collapseId = `domains-split-${poolPanel?.id ?? 'panel'}-${split.id ?? index}`;
                    const iconId = `icon-${collapseId}`;
                    const domainsForCopy = normalizedDomains;
                    const encodedDomains = encodeURIComponent(JSON.stringify(domainsForCopy));

                    const domainBadges = domainDetails.length ?
                        domainDetails.map(detail => `
                        <span class="domain-badge">
                            <span>${detail.name || 'N/A'}</span>
                            ${detail.status_badge ?? ''}
                        </span>
                    `).join('') :
                        (normalizedDomains.length ?
                            normalizedDomains.map(domain => `
                            <span class="domain-badge">
                                <span>${domain}</span>
                            </span>
                        `).join('') :
                            '<span class="text-muted">No domains listed for this split.</span>');

                    return `
                <div class="domain-split-container mb-3">
                    <div class="split-header d-flex align-items-center justify-content-between p-2 rounded-top"
                        style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); cursor: pointer;"
                        onclick="toggleSplit('${collapseId}', '${iconId}')">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-white text-dark" style="font-size: 10px; font-weight: bold;">Panel ID: ${panelId}</span>
                            <span class="badge bg-white text-dark" style="font-size: 10px; font-weight: bold;">Panel: ${panelTitle}</span>
                            <small class="text-white">${domainCount} domain${domainCount === 1 ? '' : 's'}</small>
                            <small class="text-white">Inboxes: ${totalInboxes}</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-copy text-white" style="font-size: 11px; cursor: pointer; opacity: 0.85;"
                                title="Copy domain list"
                                onclick="event.stopPropagation(); copyDomainsToClipboardFromEncoded('${encodedDomains}');"></i>
                            <i class="fa-solid fa-chevron-down text-white transition-transform" id="${iconId}" style="font-size: 12px;"></i>
                        </div>
                    </div>
                    <div class="domain-list-content border border-top-0 rounded-bottom p-3 collapse" id="${collapseId}">
                        <div class="domains-grid">
                            ${domainBadges}
                        </div>
                    </div>
                </div>
            `;
                }).join('');
            }

            const fallbackDomains = extractDomainArray(poolInfo?.domains);
            if (fallbackDomains.length === 0) {
                return '<span>N/A</span>';
            }

            const fallbackBadges = fallbackDomains
                .map(domain => `<span class="domain-badge"><span>${domain}</span></span>`)
                .join('');

            return `
        <div class="domain-list-content border rounded p-3">
            <div class="domains-grid">
                ${fallbackBadges}
            </div>
        </div>
    `;
        }

        function renderOtherPanelSplits(otherPanels, currentPanel) {
            if (!Array.isArray(otherPanels) || otherPanels.length === 0) {
                return '';
            }

            return `
        <div class="mt-4">
            <span class="opacity-50 mb-3 d-block">
                <i class="fa-solid fa-layer-group me-2"></i>Other Pool Panel Splits
            </span>
            ${otherPanels.map((panelData, index) => renderOtherPanelCard(panelData, currentPanel, index)).join('')}
        </div>
    `;
        }

        function renderOtherPanelCard(panelData, currentPanel, index) {
            const panelInfo = panelData.panel || {};
            const panelId = panelInfo.auto_generated_id || (panelInfo.id ? `PPN-${panelInfo.id}` : 'N/A');
            const panelTitle = panelInfo.title || 'N/A';
            const panelStatus = panelInfo.is_active ? 'Active' : 'Inactive';
            const statusBadgeClass = panelInfo.is_active ? 'bg-success' : 'bg-secondary';

            const splitsHtml = renderPoolDomainsList(panelInfo, panelData.splits || [], panelInfo);

            return `
        <div class="card bg-transparent border border-secondary-subtle mb-3">
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-white text-dark" style="font-size: 10px; font-weight: bold;">Panel ID: ${panelId}</span>
                    <span class="badge bg-white text-dark" style="font-size: 10px; font-weight: bold;">Panel: ${panelTitle}</span>
                    <span class="badge ${statusBadgeClass}" style="font-size: 10px;">${panelStatus}</span>
                    <span class="badge bg-info bg-opacity-25 text-light" style="font-size: 10px;">Splits: ${panelData.total_splits ?? (panelData.splits ? panelData.splits.length : 0)}</span>
                    <span class="badge bg-warning bg-opacity-25 text-dark" style="font-size: 10px;">Domains: ${panelData.total_domains ?? 0}</span>
                    <span class="badge bg-primary bg-opacity-25 text-light" style="font-size: 10px;">Total Inboxes: ${panelData.total_inboxes ?? 0}</span>
                </div>
                <div class="mt-3">
                    ${splitsHtml}
                </div>
            </div>
        </div>
    `;
        }

        function copyDomainsToClipboardFromEncoded(encodedDomains) {
            try {
                const decoded = decodeURIComponent(encodedDomains);
                const domains = JSON.parse(decoded);
                copyDomainsArrayToClipboard(Array.isArray(domains) ? domains : []);
            } catch (error) {
                console.error('Failed to decode domains for copying:', error);
                copyDomainsArrayToClipboard([]);
            }
        }

        function copyDomainsArrayToClipboard(domains) {
            const text = domains.length ? domains.join('\n') : 'No domains available';
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(() => showCopyNotification(domains.length))
                    .catch(error => {
                        console.error('Clipboard write failed:', error);
                        fallbackCopyToClipboard(text);
                    });
            } else {
                fallbackCopyToClipboard(text);
            }
        }

        function fallbackCopyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                showCopyNotification(text ? text.split('\n').length : 0);
            } catch (error) {
                console.error('Fallback copy failed:', error);
            } finally {
                document.body.removeChild(textarea);
            }
        }

        function showCopyNotification(count) {
            const message = count > 0 ?
                `${count} domain${count === 1 ? '' : 's'} copied to clipboard` :
                'No domains to copy';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Copied!',
                    text: message,
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                console.log(message);
            }
        }

        function toggleSplit(contentId, iconId) {
            const content = document.getElementById(contentId);
            const icon = document.getElementById(iconId);
            if (!content) {
                return;
            }

            const isOpen = content.classList.contains('show');
            if (isOpen) {
                content.classList.remove('show');
                if (icon) {
                    icon.style.transform = 'rotate(0deg)';
                }
            } else {
                content.classList.add('show');
                if (icon) {
                    icon.style.transform = 'rotate(180deg)';
                }
            }
        }

        function initializePoolSplitAccordions() {
            const containers = document.querySelectorAll('#poolPanelPoolsContainer .domain-list-content.collapse');
            containers.forEach((content, index) => {
                const iconId = content.getAttribute('id') ? `icon-${content.getAttribute('id')}` : null;
                const icon = iconId ? document.getElementById(iconId) : null;

                if (index === 0) {
                    content.classList.add('show');
                    if (icon) {
                        icon.style.transform = 'rotate(180deg)';
                    }
                } else if (icon) {
                    icon.style.transform = 'rotate(0deg)';
                }
            });
        }

        function extractDomainArray(domains) {
            if (!domains) {
                return [];
            }

            if (Array.isArray(domains)) {
                return domains.map(normalizeDomainValue).filter(Boolean);
            }

            if (typeof domains === 'string') {
                return domains.split(/[\n,;]+/).map(value => value.trim()).filter(Boolean);
            }

            return [];
        }

        function formatValue(value) {
            if (value === null || value === undefined) {
                return 'N/A';
            }

            const stringValue = value.toString().trim();
            return stringValue.length ? stringValue : 'N/A';
        }

        function formatLinkValue(value) {
            if (!value) {
                return 'N/A';
            }

            const stringValue = value.toString().trim();
            if (!stringValue) {
                return 'N/A';
            }

            const hasProtocol = /^https?:\/\//i.test(stringValue);
            const href = hasProtocol ? stringValue : `https://${stringValue}`;

            return `<a href="${href}" target="_blank" rel="noopener noreferrer" class="text-decoration-underline">${stringValue}</a>`;
        }

        function renderDomainBadges(domainNames) {
            if (!Array.isArray(domainNames) || domainNames.length === 0) {
                return '<span class="badge bg-secondary bg-opacity-25 text-light">No domains</span>';
            }

            const badges = domainNames
                .map(normalizeDomainValue)
                .filter(Boolean)
                .map(name => `<span class="badge bg-primary bg-opacity-25 text-light">${name}</span>`);

            return badges.length ? badges.join(' ') : '<span class="badge bg-secondary bg-opacity-25 text-light">No domains</span>';
        }

        function normalizeDomainValue(domain) {
            if (domain === null || domain === undefined) {
                return '';
            }

            if (typeof domain === 'string' || typeof domain === 'number') {
                return domain.toString().trim();
            }

            if (typeof domain === 'object' && domain !== null) {
                const candidates = [
                    'domain',
                    'domain_name',
                    'name',
                    'domain_url',
                    'url',
                    'value',
                    'label',
                    'text',
                    'id'
                ];

                for (const key of candidates) {
                    if (domain[key]) {
                        return domain[key].toString().trim();
                    }
                }
            }

            return '';
        }

        function getPoolStatusBadgeClass(status) {
            const normalized = (status || '').toString().toLowerCase();
            switch (normalized) {
                case 'available':
                case 'active':
                case 'completed':
                    return 'bg-success';
                case 'pending':
                case 'warming':
                case 'in_progress':
                    return 'bg-warning text-dark';
                case 'cancelled':
                case 'archived':
                case 'inactive':
                    return 'bg-danger';
                default:
                    return 'bg-secondary';
            }
        }

        // Reset filter button states
        function resetFilterButtonStates() {
            const searchBtn = $('#submitBtn');
            const resetBtn = $('#resetFilters');

            // Reset search button
            searchBtn.prop('disabled', false);
            $('#searchText').text('Search');
            $('#searchSpinner').hide();

            // Reset reset button
            resetBtn.prop('disabled', false);
            $('#resetText').text('Reset');
            $('#resetSpinner').hide();
        }

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
            loadPoolPanels(currentFilters, 1, false);
        }
        // No longer needed - ID generation happens in the backend

        function fetchNextPoolPanelId(options = {}) {
            const {
                showOffcanvas = true, showLoader = true
            } = options;
            const providerTypeField = document.getElementById('provider_type');
            const providerTypeValue = providerTypeField && providerTypeField.value ?
                providerTypeField.value :
                DEFAULT_PROVIDER_TYPE;
            const query = new URLSearchParams({
                provider_type: providerTypeValue || DEFAULT_PROVIDER_TYPE,
            });
            const panelLimitInput = document.getElementById('panel_limit'); // Corrected ID

            if (showLoader) {
                // Show SweetAlert loading dialog only when requested
                Swal.fire({
                    title: 'Preparing Form...',
                    text: 'Fetching next Pool Panel ID...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            fetch(`{{ route('admin.pool-panels.next-id') }}?${query.toString()}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Failed to fetch next panel ID.');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('pool_panel_id').value = data.next_id || ''; // Corrected ID
                    const capacityFromResponse = data && typeof data.capacity !== 'undefined' // Corrected ID
                        ?
                        Number(data.capacity) :
                        null;

                    if (panelLimitInput) {
                        const capacityToApply = capacityFromResponse && !Number.isNaN(capacityFromResponse) ?
                            capacityFromResponse :
                            getCapacityForProvider(providerTypeValue);
                        panelLimitInput.value = capacityToApply;
                    }

                    if (showLoader) {
                        Swal.close();
                    }

                    if (showOffcanvas) {
                        // Show the offcanvas (no need to wait for fetchNextPanelId, since it sets the value asynchronously)
                        const offcanvasElement = document.getElementById('poolPanelFormOffcanvas');
                        const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
                        offcanvas.show();
                    }
                })
                .catch((error) => {
                    document.getElementById('pool_panel_id').value = ''; // Corrected ID
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

        function openCreateForm() {
            resetForm();
            isEditMode = false;
            currentPoolPanelId = null;
            fetchNextPoolPanelId();
        }

        function openEditForm(id) {
            // Show Swal loader for loading edit data
            Swal.fire({
                title: 'Loading Pool Panel...',
                text: 'Please wait while we load the pool panel data.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: '{{ route('admin.pool-panels.edit', ':id') }}'.replace(':id', id),
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    const poolPanel = response.poolPanel;

                    // Populate form
                    $('#provider_type').val(poolPanel.provider_type || 'Google');
                    $('#panel_limit').val(poolPanel.limit || getCapacityForProvider(poolPanel.provider_type));

                    // Make provider type and limit readonly for editing
                    $('#provider_type').prop('disabled', true);
                    $('#panel_limit').prop('readonly', true);
                    $('#pool_panel_id').val(poolPanel.auto_generated_id);
                    $('#pool_panel_title').val(poolPanel.title);
                    $('#pool_panel_description').val(poolPanel.description);
                    $('#pool_panel_status').val(poolPanel.is_active ? '1' : '0');

                    // Set edit mode
                    isEditMode = true;
                    currentPoolPanelId = id;
                    $('#poolPanelFormOffcanvasLabel').text('Edit Pool Panel');
                    $('#submitPoolPanelFormBtn').text('Update Pool Panel');
                    $('#poolPanelIdContainer').show();
                    $('#poolPanelIdLabel').text('Current Pool Panel ID:');
                    $('#poolPanelIdHint').text('This is the current ID for this pool panel');

                    // Close Swal loader
                    Swal.close();

                    // Show offcanvas
                    const offcanvasElement = document.getElementById('poolPanelFormOffcanvas');
                    const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
                    offcanvas.show();
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error Loading Data',
                        text: xhr.responseJSON?.message || 'An error occurred while loading the pool panel data.',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }

        function deletePoolPanel(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading during deletion
                    Swal.fire({
                        title: 'Deleting Pool Panel...',
                        text: 'Please wait while we delete the pool panel.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    $.ajax({
                        url: '{{ route('admin.pool-panels.destroy', ':id') }}'.replace(':id', id),
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message || 'Pool Panel deleted successfully',
                                timer: 2000,
                                showConfirmButton: false
                            });
                            resetAndReload();
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Delete Failed',
                                text: xhr.responseJSON?.message || 'An error occurred while deleting the pool panel',
                                confirmButtonText: 'OK'
                            });
                        }
                    });
                }
            });
        }

        // Archive pool panel function
        async function archivePoolPanel(poolPanelId) {
            try {
                // Show SweetAlert confirmation dialog
                const confirmResult = await Swal.fire({
                    title: 'Archive Pool Panel?',
                    text: `Are you sure you want to archive pool panel ${poolPanelId}? Archived pool panels will not be used for new orders.`,
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
                    title: 'Archiving Pool Panel',
                    text: 'Please wait while we archive the pool panel...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send archive request (set is_active to false)
                const response = await $.ajax({
                    url: `/admin/pool-panels/${poolPanelId}/archive`,
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
                        text: response.message || 'Pool Panel archived successfully!',
                        confirmButtonText: 'OK'
                    });

                    // Reload pool panels to reflect changes
                    resetAndReload();

                    // Refresh capacity alert
                    refreshPoolPanelCapacityAlert();
                } else {
                    // Show error message
                    await Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Failed to archive pool panel',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (xhr) {
                console.log('Error response:', xhr.responseJSON);

                let errorMessage = 'Failed to archive pool panel. Please try again.';
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

        // Unarchive pool panel function
        async function unarchivePoolPanel(poolPanelId) {
            try {
                // Show SweetAlert confirmation dialog
                const confirmResult = await Swal.fire({
                    title: 'Unarchive Pool Panel?',
                    text: `Are you sure you want to unarchive pool panel ${poolPanelId}? This will make it available for new orders.`,
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
                    title: 'Unarchiving Pool Panel',
                    text: 'Please wait while we unarchive the pool panel...',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Send unarchive request (set is_active to true)
                const response = await $.ajax({
                    url: `/admin/pool-panels/${poolPanelId}/archive`,
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
                        text: response.message || 'Pool Panel unarchived successfully!',
                        confirmButtonText: 'OK'
                    });

                    // Reload pool panels to reflect changes
                    resetAndReload();

                    // Refresh capacity alert
                    refreshPoolPanelCapacityAlert();
                } else {
                    // Show error message
                    await Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Failed to unarchive pool panel',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (xhr) {
                console.log('Error response:', xhr.responseJSON);

                let errorMessage = 'Failed to unarchive pool panel. Please try again.';
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
            // Update current filters based on tab
            if (tab === 'active') {
                currentFilters.status = 1;
            } else if (tab === 'archived') {
                currentFilters.status = 0;
            }

            // Reset and reload with new filter
            resetAndReload();
        }

        // Show pool allocation details offcanvas
        function showPoolAllocationDetails() {
            // Load pool allocation data
            loadPoolAllocationData();

            // Show offcanvas
            const offcanvas = new bootstrap.Offcanvas(document.getElementById('poolAllocationOffcanvas'));
            offcanvas.show();
        }

        // Load pool allocation data
        async function loadPoolAllocationData() {
            try {
                const response = await $.ajax({
                    url: '{{ route('admin.pool-panels.capacity-alert') }}',
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    }
                });

                if (response.success) {
                    // Update counters
                    $('#pools_counter').text(response.insufficient_pools_count || 0);
                    $('#pool_inboxes_counter').text(response.total_inboxes || 0);
                    $('#pool_panels_counter').text(response.total_pool_panels_needed || 0);

                    // Populate table
                    const tbody = $('#poolTableBody');
                    tbody.empty();

                    if (response.insufficient_pools && response.insufficient_pools.length > 0) {
                        response.insufficient_pools.forEach(pool => {
                            const row = `
                        <tr>
                            <td>${pool.id || 'N/A'}</td>
                            <td>${formatDate(pool.created_at)}</td>
                            <td>${pool.total_inboxes || 0}</td>
                            <td><span class="badge bg-warning">Pending</span></td>
                        </tr>
                    `;
                            tbody.append(row);
                        });
                    } else {
                        tbody.html('<tr><td colspan="6" class="text-center">No pending pools awaiting allocation</td></tr>');
                    }
                }
            } catch (error) {
                console.error('Error loading pool allocation data:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load pool allocation data. Please try again.',
                    confirmButtonText: 'OK'
                });
            }
        }

        // Refresh pool panel capacity alert
        function refreshPoolPanelCapacityAlert() {
            $.ajax({
                url: '{{ route('admin.pool-panels.capacity-alert') }}',
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json'
                },
                success: function(response) {
                    if (response.success) {
                        if (response.show_alert) {
                            // Show/update the alert
                            const alertHtml = `
                        <div id="poolPanelCapacityAlert" class="alert alert-warning alert-dismissible fade show py-2 rounded-1" role="alert"
                            style="background-color: rgba(255, 193, 7, 0.2); color: #fff; border: 2px solid #ffc107;">
                            <i class="ti ti-layer-group me-2 alert-icon"></i>
                            <strong>Pool Panel Capacity Alert:</strong>
                            ${response.total_pool_panels_needed} new pool panel${response.total_pool_panels_needed != 1 ? 's' : ''} required for ${response.insufficient_pools_count} pending pool${response.insufficient_pools_count != 1 ? 's' : ''}.
                            <a href="javascript:void(0)" onclick="showPoolAllocationDetails()" class="text-light alert-link">View Details</a> to see pending pools.
                            <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    `;

                            if ($('#poolPanelCapacityAlert').length) {
                                // Update existing alert
                                $('#poolPanelCapacityAlert').replaceWith(alertHtml);
                            } else {
                                // Insert new alert before counters
                                $('.counters').first().before(alertHtml);
                            }
                        } else {
                            // Hide the alert if no longer needed
                            $('#poolPanelCapacityAlert').remove();
                        }

                        console.log('Pool panel capacity alert refreshed at:', new Date().toLocaleTimeString());
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error refreshing pool panel capacity alert:', error);
                }
            });
        }

        function resetForm() {
            $('#poolPanelForm')[0].reset();
            $('#pool_panel_status').val('1');

            // Reset ID label and hint for create mode
            $('#poolPanelIdLabel').text('Next Pool Panel ID:');
            $('#poolPanelIdHint').text('This ID will be automatically generated');
            $('#poolPanelIdContainer').show();
            // Clear validation errors
            $('#provider_type').prop('disabled', false);
            $('#panel_limit').prop('readonly', true);
            $('#poolPanelFormOffcanvasLabel').text('Pool Panel');
            $('#submitPoolPanelFormBtn').text('Submit');
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
        }

        // ========================
        // Pool Panel Reassignment
        // ========================
        function openPoolReassignModal(poolId, currentPoolPanelId, splitId, panelTitle) {
            currentPoolReassignData = {
                poolId,
                currentPoolPanelId,
                splitId,
                targetPoolPanelId: null,
                panelTitle
            };

            document.getElementById('poolReassignReason').value = '';
            showPoolReassignError('');
            showPoolReassignLoading(true);

            const modalElement = document.getElementById('poolReassignModal');
            poolReassignModalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
            poolReassignModalInstance.show();

            const url = '{{ route('admin.pool-panels.available-for-reassignment', ['poolId' => '__POOL_ID__', 'poolPanelId' => '__POOL_PANEL_ID__']) }}'
                .replace('__POOL_ID__', poolId)
                .replace('__POOL_PANEL_ID__', currentPoolPanelId);

            fetch(url, {
                headers: {
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.error || 'Failed to load available pool panels');
                    }
                    renderAvailablePoolPanels(data.panels || []);
                    document.getElementById('poolReassignModalLabel').innerHTML = `Reassign Pool Split from ${panelTitle || ''}`;
                    showPoolReassignLoading(false);
                })
                .catch(error => {
                    console.error('Failed to load pool panels for reassignment', error);
                    showPoolReassignError(error.message || 'Failed to load available pool panels');
                    showPoolReassignLoading(false);
                });
        }

        function renderAvailablePoolPanels(panels) {
            const tbody = document.getElementById('poolReassignTableBody');
            const button = document.getElementById('confirmPoolReassignBtn');

            if (!Array.isArray(panels) || panels.length === 0) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        No pool panels available for reassignment.
                    </td>
                </tr>`;
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Select Panel First';
                return;
            }

            tbody.innerHTML = panels.map(panel => `
                <tr>
                    <td>
                        <div class="fw-semibold">${panel.panel_title || 'N/A'}</div>
                        <small class="text-muted">PPN-${panel.panel_id}</small>
                    </td>
                    <td>${panel.panel_remaining_limit ?? 0}</td>
                    <td>${panel.total_splits ?? 0}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-warning" onclick="setPoolReassignTarget(${panel.panel_id}, '${panel.panel_title ? panel.panel_title.replace(/'/g, "\\'") : 'N/A'}')">
                            <i class="fas fa-arrow-right"></i> Select
                        </button>
                    </td>
                </tr>
            `).join('');

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Select Panel First';
        }

        function setPoolReassignTarget(panelId, panelTitle) {
            currentPoolReassignData.targetPoolPanelId = panelId;
            currentPoolReassignData.targetPanelTitle = panelTitle;

            const button = document.getElementById('confirmPoolReassignBtn');
            button.disabled = false;
            button.innerHTML = `<i class="fas fa-exchange-alt me-1"></i>Reassign to ${panelTitle}`;
            showPoolReassignError('');
        }

        function confirmPoolReassignment() {
            if (!currentPoolReassignData.targetPoolPanelId) {
                showPoolReassignError('Please select a target pool panel');
                return;
            }

            showPoolReassignLoading(true);
            fetch('{{ route('admin.pool-panels.reassign') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    from_pool_panel_id: currentPoolReassignData.currentPoolPanelId,
                    to_pool_panel_id: currentPoolReassignData.targetPoolPanelId,
                    split_id: currentPoolReassignData.splitId,
                    pool_id: currentPoolReassignData.poolId,
                    reason: document.getElementById('poolReassignReason').value
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        throw new Error(data.message || 'Reassignment failed');
                    }

                    Swal.fire({
                        icon: 'success',
                        title: 'Reassignment Successful!',
                        text: data.message || 'Pool panel split reassigned successfully',
                        confirmButtonColor: '#ffc107',
                    });

                    resetPoolReassignModal();
                    poolReassignModalInstance?.hide();
                    loadPoolPanels();
                })
                .catch(error => {
                    console.error('Pool panel reassignment failed', error);
                    showPoolReassignError(error.message || 'Reassignment failed');
                })
                .finally(() => {
                    showPoolReassignLoading(false);
                });
        }

        function showPoolReassignLoading(show) {
            const tbody = document.getElementById('poolReassignTableBody');
            const button = document.getElementById('confirmPoolReassignBtn');

            if (show) {
                tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="text-center py-4">
                        <div class="spinner-border text-warning" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </td>
                </tr>`;
                button.disabled = true;
            }
        }

        function showPoolReassignError(message) {
            const alertBox = document.getElementById('poolReassignAlert');
            if (!alertBox) return;

            if (!message) {
                alertBox.classList.add('d-none');
                alertBox.innerHTML = '';
                return;
            }

            alertBox.classList.remove('d-none');
            alertBox.innerHTML = message;
        }

        function resetPoolReassignModal() {
            currentPoolReassignData = {};
            document.getElementById('poolReassignReason').value = '';
            const button = document.getElementById('confirmPoolReassignBtn');
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-exchange-alt me-1"></i>Select Panel First';
            }
            showPoolReassignError('');
        }
    </script>
@endpush
