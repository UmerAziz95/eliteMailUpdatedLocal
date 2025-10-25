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
</style>
@endpush

@section('content')
<section class="py-3">
    <!-- Pool Awaited Panel Allocation Offcanvas -->
    <div class="offcanvas offcanvas-start" style="min-width: 70%;  background-color: var(--filter-color); backdrop-filter: blur(5px); border: 3px solid var(--second-primary);" tabindex="-1" id="poolAllocationOffcanvas" aria-labelledby="poolAllocationOffcanvasLabel">
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

                <label for="pool_panel_description" class="mt-3">Description:</label>
                <textarea class="form-control mb-3" id="pool_panel_description" name="description" rows="3"></textarea>

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
        $getAvailablePoolPanelSpace = function(int $poolSize) use ($poolPanelCapacity, $maxSplitCapacity) {
            // Get active pool panels with remaining capacity
            $availablePoolPanels = \App\Models\PoolPanel::where('is_active', 1)
                                        ->where('remaining_limit', '>', 0)
                                        ->get();
            
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
        $availablePoolPanelCount = \App\Models\PoolPanel::where('is_active', true)
            ->where('remaining_limit', '>=', $maxSplitCapacity)
            ->count();
        
        $adjustedPoolPanelsNeeded = max(0, $totalPoolPanelsNeeded - $availablePoolPanelCount);
    @endphp

    @if($adjustedPoolPanelsNeeded > 0)
    <div id="poolPanelCapacityAlert" class="alert alert-warning alert-dismissible fade show py-2 rounded-1" role="alert"
        style="background-color: rgba(255, 193, 7, 0.2); color: #fff; border: 2px solid #ffc107;">
        <i class="ti ti-layer-group me-2 alert-icon"></i>
        <strong>Pool Panel Capacity Alert:</strong>
        {{ $adjustedPoolPanelsNeeded }} new pool panel{{ $adjustedPoolPanelsNeeded != 1 ? 's' : '' }} required for {{ count($insufficientSpacePools) }} pending pool{{ count($insufficientSpacePools) != 1 ? 's' : '' }}.
        <a href="javascript:void(0)" onclick="showPoolAllocationDetails()" class="text-light alert-link">View Details</a> to see pending pools.
        <button type="button" class="btn-close" style="padding: 11px" data-bs-dismiss="alert"
            aria-label="Close"></button>
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
        <button type="button" class="btn btn-warning btn-sm border-0 px-3" 
                onclick="showPoolAllocationDetails()"
                title="View pools awaiting panel allocation">
            <i class="fa-solid fa-water me-2"></i>
            View Pending Pools
            @if($adjustedPoolPanelsNeeded > 0)
                <span class="badge bg-danger ms-2">{{ count($insufficientSpacePools) }}</span>
            @endif
        </button>
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
                        <button type="button" id="resetFilters"
                            class="btn btn-outline-secondary btn-sm me-2 px-3">
                            <span id="resetText">Reset</span>
                            <span id="resetSpinner" class="spinner-border spinner-border-sm ms-2" role="status" style="display: none;">
                                <span class="visually-hidden">Loading...</span>
                            </span>
                        </button>
                        <button type="submit" id="submitBtn"
                            class="btn btn-primary btn-sm border-0 px-3">
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
                <button class="nav-link active" id="active-tab" data-bs-toggle="pill" data-bs-target="#active-pool-panels" 
                        type="button" role="tab" aria-controls="active-pool-panels" aria-selected="true"
                        onclick="switchTab('active')">
                    <i class="fa-solid fa-check-circle me-1"></i> Active Pool Panels
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="archived-tab" data-bs-toggle="pill" data-bs-target="#archived-pool-panels" 
                        type="button" role="tab" aria-controls="archived-pool-panels" aria-selected="false"
                        onclick="switchTab('archived')">
                    <i class="fa-solid fa-archive me-1"></i> Archived Pool Panels
                </button>
            </li>
        </ul>
        
        {{-- create pool panel button --}}
        <button type="button" class="btn btn-primary btn-sm border-0 px-3" 
                data-bs-toggle="offcanvas" data-bs-target="#poolPanelFormOffcanvas" 
                onclick="openCreateForm();">
            <i class="fa-solid fa-plus me-2"></i>
            Create New Pool Panel
        </button>
    </div>

    <!-- Grid Cards (Dynamic) -->
    <div id="poolPanelsContainer"
        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;">
        <!-- Loading state -->
        <div id="loadingState"
            style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2 mb-0">Loading pool panels...</p>
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
            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span> of <span id="totalPoolPanels">0</span>
            pool panels
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
let currentPoolPanelId = null;
let isEditMode = false;
let currentPage = 1;
let hasMorePages = true;
let isLoading = false;
let currentFilters = {};
let charts = {}; // Store chart instances

$(document).ready(function() {
    // Set initial filter to show only active pool panels
    currentFilters.status = 1;
    
    // Load initial data
    loadPoolPanels();
    updateCounters();

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
        
        const url = isEditMode 
            ? '{{ route("admin.pool-panels.update", ":id") }}'.replace(':id', currentPoolPanelId)
            : '{{ route("admin.pool-panels.store") }}';
        const method = isEditMode ? 'PUT' : 'POST';
        
        $.ajax({
            url: url,
            method: method,
            data: formData + '&_token={{ csrf_token() }}',
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || (isEditMode ? 'Pool Panel updated successfully!' : 'Pool Panel created successfully!'),
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Close offcanvas
                const offcanvasElement = document.getElementById('poolPanelFormOffcanvas');
                const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                offcanvas.hide();
                
                // Reload data
                resetAndReload();
                
                // Reset form
                resetForm();
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
            currentPage++;
            loadPoolPanels(false);
        }
    });
});

// Load pool panels with pagination
async function loadPoolPanels(resetData = true) {
    if (isLoading) return;
    
    isLoading = true;
    
    if (resetData) {
        currentPage = 1;
        hasMorePages = true;
        $('#poolPanelsContainer').html(`
            <div id="loadingState" style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 3rem 0; min-height: 300px;">
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
            page: currentPage,
            per_page: 12,
            ...currentFilters
        });

        const response = await fetch(`{{ route("admin.pool-panels.index") }}?${params}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) throw new Error('Failed to fetch pool panels');
        
        const data = await response.json();
        
        if (resetData) {
            renderPoolPanels(data.data);
        } else {
            appendPoolPanels(data.data);
        }
        
        // Update pagination info
        updatePaginationInfo(data);
        
        // Check if there are more pages
        hasMorePages = data.current_page < data.last_page;
        
        // Show/hide load more button
        if (hasMorePages) {
            $('#loadMoreContainer').show();
        } else {
            $('#loadMoreContainer').hide();
        }

    } catch (error) {
        console.error('Error loading pool panels:', error);
        showErrorState();
    } finally {
        isLoading = false;
        $('#loadMoreSpinner').hide();
        $('#loadMoreText').text('Load More');
    }
}

// Render pool panels
function renderPoolPanels(poolPanels) {
    const container = document.getElementById('poolPanelsContainer');
    
    if (!poolPanels || poolPanels.length === 0) {
        container.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="ti ti-database-off"></i>
                <h5>No Pool Panels Found</h5>
                <p>Create your first pool panel to get started.</p>
            </div>
        `;
        return;
    }

    const poolPanelsHtml = poolPanels.map(poolPanel => createPoolPanelCard(poolPanel)).join('');
    container.innerHTML = poolPanelsHtml;
    
    // Initialize charts after rendering
    setTimeout(() => {
        poolPanels.forEach(poolPanel => {
            initChart(poolPanel);
        });
    }, 100);
}

// Append pool panels for load more
function appendPoolPanels(poolPanels) {
    const container = document.getElementById('poolPanelsContainer');
    const loadingState = document.getElementById('loadingState');
    
    if (loadingState) {
        loadingState.remove();
    }
    
    if (poolPanels && poolPanels.length > 0) {
        const poolPanelsHtml = poolPanels.map(poolPanel => createPoolPanelCard(poolPanel)).join('');
        container.insertAdjacentHTML('beforeend', poolPanelsHtml);
        
        // Initialize charts for new panels
        setTimeout(() => {
            poolPanels.forEach(poolPanel => {
                initChart(poolPanel);
            });
        }, 100);
    }
}

// Create pool panel card HTML
function createPoolPanelCard(poolPanel) {
    const total = Number(poolPanel.limit) || 1790;
    const used = Number(poolPanel.used_limit) || 0;
    const remainingValue = Number(poolPanel.remaining_limit);
    const remaining = Number.isFinite(remainingValue) ? remainingValue : total;
    const isFullCapacity = Math.abs(remaining - total) < 0.0001;
    const totalOrders = 0; // Pool panels don't have orders like regular panels
    
    const statusBadge = poolPanel.is_active 
        ? '<span class="badge bg-success">Active</span>' 
        : '<span class="badge bg-danger">Inactive</span>';
    
    const actionButtons = `
        <div class="d-flex flex-column gap-2">
            ${isFullCapacity ? `
                <button class="btn btn-sm btn-outline-primary px-2 py-1" 
                        onclick="openEditForm(${poolPanel.id})" 
                        title="Edit Pool Panel">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-outline-danger px-2 py-1" 
                        onclick="deletePoolPanel(${poolPanel.id})" 
                        title="Delete Pool Panel">
                    <i class="fas fa-trash"></i>
                </button>
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
    
    // Add archived styling for inactive pool panels
    const archivedStyle = !poolPanel.is_active ? 'background-color: #334761;' : '';
    
    return `
        <div class="card p-3 d-flex flex-column gap-1" style="${archivedStyle}">                    
            <div class="d-flex flex-column gap-2 align-items-start justify-content-between">
                <small class="mb-0 opacity-75">${poolPanel.auto_generated_id || 'PPN-' + poolPanel.id}</small>
                <h6>Title: ${poolPanel.title || 'N/A'} ${!poolPanel.is_active ? '<span class="badge bg-secondary ms-2">Archived</span>' : ''}</h6>
                <div>${statusBadge}</div>
            </div>

            <div class="d-flex gap-3 justify-content-between">
                <small class="total">Total: ${total}</small>
                <small class="remain">Remaining: ${remaining}</small>
                <small class="used">Used: ${used}</small>
            </div>

            <div id="chart-${poolPanel.id}"></div>
            
            <div class="mt-2">
                <small class="opacity-75">Created by: ${poolPanel.creator ? poolPanel.creator.name : 'Unknown'}</small>
            </div>
            
            <div class="button-container p-2 rounded-2" style="background-color: var(--filter-color);">
                ${actionButtons}    
            </div>
        </div>
    `;
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
        const response = await fetch(`{{ route("admin.pool-panels.index") }}?counters=1`, {
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
function updatePaginationInfo(data) {
    $('#showingFrom').text(data.from || 0);
    $('#showingTo').text(data.to || 0);
    $('#totalPoolPanels').text(data.total || 0);
}

// Show error state
function showErrorState() {
    const container = document.getElementById('poolPanelsContainer');
    container.innerHTML = `
        <div class="empty-state" style="grid-column: 1 / -1;">
            <i class="ti ti-alert-circle"></i>
            <h5>Error Loading Pool Panels</h5>
            <p>Please try again later.</p>
            <button class="btn btn-primary" onclick="resetAndReload()">Retry</button>
        </div>
    `;
    
    // Also show Swal error
    Swal.fire({
        icon: 'error',
        title: 'Loading Error',
        text: 'Failed to load pool panels. Please check your connection and try again.',
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
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
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

// No longer needed - ID generation happens in the backend

function openCreateForm() {
    resetForm();
    isEditMode = false;
    currentPoolPanelId = null;
    $('#poolPanelFormOffcanvasLabel').text('Create Pool Panel');
    $('#submitPoolPanelFormBtn').text('Create Pool Panel');
    $('#poolPanelIdContainer').show(); // Show ID container for create mode too
    
    // Show Swal loader for fetching next ID
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
    
    // Show loading state for ID field
    $('#pool_panel_id').val('Loading next ID...');
    
    // Fetch next pool panel ID from server
    $.ajax({
        url: '{{ route("admin.pool-panels.index") }}?next_id=1',
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(response) {
            $('#pool_panel_id').val(response.next_id);
            $('#poolPanelIdHint').text('This ID will be automatically assigned to your new pool panel');
            
            // Close Swal loader
            Swal.close();
        },
        error: function(xhr) {
            console.error('Failed to fetch next pool panel ID:', xhr);
            // Show error message
            $('#pool_panel_id').val('Error loading ID');
            $('#poolPanelIdHint').text('ID will be generated when creating the pool panel');
            
            // Show Swal warning (this will replace the loading dialog)
            Swal.fire({
                icon: 'warning',
                title: 'ID Generation Warning',
                text: 'Could not fetch next Pool Panel ID. A new ID will be generated when you create the panel.',
                timer: 3000,
                showConfirmButton: false
            });
        }
    });
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
        url: '{{ route("admin.pool-panels.edit", ":id") }}'.replace(':id', id),
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            const poolPanel = response.poolPanel;
            
            // Populate form
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
                url: '{{ route("admin.pool-panels.destroy", ":id") }}'.replace(':id', id),
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
            url: '{{ route("admin.pool-panels.capacity-alert") }}',
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
        url: '{{ route("admin.pool-panels.capacity-alert") }}',
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
    
    // Clear validation errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
}
</script>
@endpush
