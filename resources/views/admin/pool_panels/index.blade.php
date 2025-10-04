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
                    <label for="pool_panel_id">Pool Panel ID:</label>
                    <input type="text" class="form-control" id="pool_panel_id" name="pool_panel_id" value="" readonly>
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
                        <h6 class="text-heading">Inactive Pool Panels</h6>
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
                            class="btn btn-outline-secondary btn-sm me-2 px-3">Reset</button>
                        <button type="submit" id="submitBtn"
                            class="btn btn-primary btn-sm border-0 px-3">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- create pool panel button --}}
    <div class="col-12 text-end mb-4">
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
    // Load initial data
    loadPoolPanels();
    updateCounters();

    // Form submission handler
    $('#submitPoolPanelFormBtn').on('click', function() {
        const form = $('#poolPanelForm');
        const formData = form.serialize();
        const submitBtn = $(this);
        const originalText = submitBtn.text();
        
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
                toastr.success(response.message || (isEditMode ? 'Pool Panel updated successfully!' : 'Pool Panel created successfully!'));
                
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
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    $.each(errors, function(field, messages) {
                        const input = $(`[name="${field}"]`);
                        input.addClass('is-invalid');
                        input.after(`<div class="invalid-feedback">${messages[0]}</div>`);
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred while processing your request.');
                }
            },
            complete: function() {
                // Re-enable submit button
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        currentFilters = Object.fromEntries(formData.entries());
        resetAndReload();
    });

    // Reset filters
    $('#resetFilters').on('click', function() {
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
    const total = poolPanel.limit || 1790;
    const used = poolPanel.used_limit || 0;
    const remaining = poolPanel.remaining_limit || total;
    const totalOrders = 0; // Pool panels don't have orders like regular panels
    
    const statusBadge = poolPanel.is_active 
        ? '<span class="badge bg-success">Active</span>' 
        : '<span class="badge bg-danger">Inactive</span>';
    
    const actionButtons = `
        <div class="d-flex flex-column gap-2">
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
        </div>
    `;
    
    return `
        <div class="card p-3 d-flex flex-column gap-1">                    
            <div class="d-flex flex-column gap-2 align-items-start justify-content-between">
                <small class="mb-0 opacity-75">${poolPanel.auto_generated_id || 'PPN-' + poolPanel.id}</small>
                <h6>Title: ${poolPanel.title || 'N/A'}</h6>
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
            
            <div class="button-container p-2 rounded-2" style="background-color: var(--filter-color); position: absolute; top: 50%; right: -50px; transform: translate(0%, -50%)">
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
}

// Reset and reload data
function resetAndReload() {
    currentPage = 1;
    hasMorePages = true;
    loadPoolPanels(true);
    updateCounters();
}

// Format date helper
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

// No longer needed - ID generation happens in the backend

function openCreateForm() {
    resetForm();
    isEditMode = false;
    currentPoolPanelId = null;
    $('#poolPanelFormOffcanvasLabel').text('Create Pool Panel');
    $('#submitPoolPanelFormBtn').text('Create Pool Panel');
    $('#poolPanelIdContainer').hide();
    
    // Generate a preview ID for display (optional)
    const previewId = 'PP_' + Math.random().toString(36).substr(2, 8).toUpperCase() + '_' + Date.now();
    $('#pool_panel_id').val(previewId);
}

function openEditForm(id) {
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
            
            // Show offcanvas
            const offcanvasElement = document.getElementById('poolPanelFormOffcanvas');
            const offcanvas = new bootstrap.Offcanvas(offcanvasElement);
            offcanvas.show();
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'An error occurred while loading the pool panel data.');
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
            $.ajax({
                url: '{{ route("admin.pool-panels.destroy", ":id") }}'.replace(':id', id),
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message || 'Pool Panel deleted successfully');
                    resetAndReload();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred while deleting the pool panel');
                }
            });
        }
    });
}

function resetForm() {
    $('#poolPanelForm')[0].reset();
    $('#pool_panel_status').val('1');
    
    // Clear validation errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();
}
</script>
@endpush