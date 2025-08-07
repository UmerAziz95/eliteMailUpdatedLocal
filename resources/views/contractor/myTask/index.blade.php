@extends('contractor.layouts.app')

@section('title', 'My-Task')
@push('styles')
<style>
    .nav-link {
        color: #fff;
        font-size: 13px
    }
    
    /* Fix offcanvas backdrop issues */
    .offcanvas-backdrop {
        z-index: 1040;
    }

    .offcanvas-backdrop.fade {
        opacity: 0;
    }

    .offcanvas-backdrop.show {
        opacity: 0.5;
    }

    /* Ensure body doesn't get scrollable when offcanvas is open */
    body:not(.offcanvas-open) {
        overflow: auto !important;
        padding-right: 0 !important;
    }

    /* Remove backdrop when it shouldn't be there */
    .offcanvas-backdrop.fade:not(.show) {
        display: none !important;
    }

    /* Domain badge hover effects */
    .domain-badge:hover {
        background-color: rgba(102, 126, 234, 0.8) !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2) !important;
    }

    /* Transition for chevron icons */
    .transition-transform {
        transition: transform 0.3s ease-in-out;
    }

    /* Split container expanding effect */
    .split-container.expanding {
        transform: scale(1.01);
        transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Domain fade in animation */
    @keyframes domainFadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px) scale(0.9);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Custom SweetAlert styles */
    .swal-wide {
        width: 600px !important;
    }

    /* Hover effect for clickable check icon */
    .fa-check-to-slot:hover {
        color: #28a745 !important;
        transform: scale(1.1) !important;
    }
    
</style>
@endpush

@section('content')
    <section class="py-3">
        <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-mytask-tab" data-bs-toggle="pill" data-bs-target="#pills-mytask"
                    type="button" role="tab" aria-controls="pills-mytask" aria-selected="true">My Task</button>
            </li>

            <li class="nav-item" role="presentation" style="display: none;">
                <button class="nav-link" id="pills-all-tasks-tab" data-bs-toggle="pill" data-bs-target="#pills-all-tasks"
                    type="button" role="tab" aria-controls="pills-all-tasks" aria-selected="false">All Tasks</button>
            </li>

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-shifted-tasks-tab" data-bs-toggle="pill" data-bs-target="#pills-shifted-tasks"
                    type="button" role="tab" aria-controls="pills-shifted-tasks" aria-selected="false">Shifted Tasks</button>
            </li>
        </ul>
        <div class="tab-content" id="pills-tabContent">
            <div class="tab-pane fade show active" id="pills-mytask" role="tabpanel" aria-labelledby="pills-mytask-tab"
                tabindex="0">
                <div id="my-tasks-container"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    <!-- Loading state -->
                    <div class="loading-state text-center" style="grid-column: 1 / -1;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading my tasks...</p>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="pills-all-tasks" role="tabpanel" aria-labelledby="pills-all-tasks-tab"
                tabindex="0">
                <div id="all-tasks-container"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>

            <div class="tab-pane fade" id="pills-shifted-tasks" role="tabpanel" aria-labelledby="pills-shifted-tasks-tab"
                tabindex="0">
                <div id="shifted-tasks-container"
                    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>
    </section>

    <!-- Task Details Offcanvas -->
    <div class="offcanvas offcanvas-bottom" style="height: 100vh;" tabindex="-1" id="task-details-view"
        aria-labelledby="task-details-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
        <div class="offcanvas-header border-0 pb-0" style="background-color: transparent">
            <h5 class="offcanvas-title" id="task-details-viewLabel">Order Details</h5>
            <button type="button" class="bg-transparent border-0" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fas fa-times fs-5"></i>
            </button>
        </div>
        <div class="offcanvas-body pt-2">
            <div id="taskDetailsContainer">
                <!-- Dynamic content will be loaded here -->
                <div id="taskLoadingState" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading task details...</span>
                    </div>
                    <p class="mt-2">Loading task details...</p>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let tasks = {
        'my-tasks': [],
        'all-tasks': [],
        'shifted-tasks': []
    };
    let pagination = {
        'my-tasks': { currentPage: 1, hasMore: false },
        'all-tasks': { currentPage: 1, hasMore: false },
        'shifted-tasks': { currentPage: 1, hasMore: false }
    };
    let isLoading = false;
    let activeTab = 'my-tasks';

    // Initialize the page
    document.addEventListener('DOMContentLoaded', function() {
        loadTasks('my-tasks');
        
        // Tab change handlers
        document.getElementById('pills-mytask-tab').addEventListener('click', function() {
            activeTab = 'my-tasks';
            if (tasks['my-tasks'].length === 0) {
                loadTasks('my-tasks');
            }
        });
        
        document.getElementById('pills-all-tasks-tab').addEventListener('click', function() {
            activeTab = 'all-tasks';
            if (tasks['all-tasks'].length === 0) {
                loadTasks('all-tasks');
            }
        });

        document.getElementById('pills-shifted-tasks-tab').addEventListener('click', function() {
            activeTab = 'shifted-tasks';
            if (tasks['shifted-tasks'].length === 0) {
                loadTasks('shifted-tasks');
            }
        });
    });

    // Load tasks function
    async function loadTasks(type, append = false) {
        if (isLoading) return;
        
        isLoading = true;
        const containerMap = {
            'my-tasks': 'my-tasks-container',
            'all-tasks': 'all-tasks-container',
            'shifted-tasks': 'shifted-tasks-container'
        };
        const containerId = containerMap[type];
        const container = document.getElementById(containerId);
        
        try {
            if (!append) {
                const loadingText = type === 'my-tasks' ? 'my' : 
                                 type === 'all-tasks' ? 'all' : 'shifted';
                container.innerHTML = `
                    <div class="loading-state text-center" style="grid-column: 1 / -1;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading ${loadingText} tasks...</p>
                    </div>
                `;
            }

            const params = new URLSearchParams({
                type: type === 'shifted-tasks' ? 'shifted-tasks' : type,
                page: append ? pagination[type].currentPage + 1 : 1,
                per_page: 12
            });

            const response = await fetch(`{{ route('contractor.myTask.data') }}?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to load tasks');
            }

            const newTasks = data.data || [];
            
            if (append) {
                tasks[type] = tasks[type].concat(newTasks);
            } else {
                tasks[type] = newTasks;
            }
            
            pagination[type] = {
                currentPage: data.pagination.current_page,
                hasMore: data.pagination.has_more_pages
            };
            
            renderTasks(type, append);
            
        } catch (error) {
            console.error('Error loading tasks:', error);
            if (!append) {
                showError(error.message, type);
            }
        } finally {
            isLoading = false;
        }
    }

    // Render tasks function
    function renderTasks(type, append = false) {
        const containerMap = {
            'my-tasks': 'my-tasks-container',
            'all-tasks': 'all-tasks-container',
            'shifted-tasks': 'shifted-tasks-container'
        };
        const containerId = containerMap[type];
        const container = document.getElementById(containerId);
        const tasksList = tasks[type];
        
        if (tasksList.length === 0 && !append) {
            const emptyText = type === 'my-tasks' ? 'My' : 
                            type === 'all-tasks' ? 'All' : 'Shifted';
            const emptyDescription = type === 'my-tasks' ? 'tasks assigned to you' : 
                                   type === 'all-tasks' ? 'tasks' : 'shifted panel tasks';
            container.innerHTML = `
                <div class="empty-state text-center" style="grid-column: 1 / -1;">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <h5>No ${emptyText} Tasks Found</h5>
                    <p>There are no ${emptyDescription} to display.</p>
                </div>
            `;
            return;
        }

        if (!append) {
            container.innerHTML = '';
        }

        const tasksToRender = append ? tasksList.slice(tasks[type].length - (tasksList.length - tasks[type].length)) : tasksList;
        
        tasksToRender.forEach((task, index) => {
            const taskCard = createTaskCard(task);
            container.appendChild(taskCard);
        });
    }

    // Create task card function
    function createTaskCard(task) {
        const div = document.createElement('div');
        div.className = 'card p-3';
        
        const statusClass = getStatusClass(task.status);
        
        // Determine if this is a shifted task (panel reassignment)
        const isShiftedTask = task.type === 'panel_reassignment';
        
        div.innerHTML = `
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-semibold">#${task.task_id} 
                    ${isShiftedTask ? 
                        `<small class="badge bg-info text-dark ms-1">${task.action_type || 'reassignment'}</small>` : 
                        `<i class="fa fa-solid fa-check-to-slot ${task.status === 'completed' ? 'text-success' : 'text-primary'}" 
                            style="cursor: ${task.status === 'completed' ? 'default' : 'pointer'}; transition: all 0.2s ease;" 
                            onmouseover="this.style.color='#28a745'; this.style.transform='scale(1.1)'" 
                            onmouseout="this.style.color=''; this.style.transform='scale(1)'" 
                            onclick="confirmTaskCompletion(${task.task_id})"
                            title="${task.status === 'completed' ? 'Task completed' : 'Mark as completed'}"></i>`
                    }
                </span>

                <span class="badge ${statusClass} fw-semibold">
                    <i class="${(() => {
                        switch(task.status) {
                            case 'pending': return 'fa fa-clock fa-pulse';
                            case 'in-progress': return 'fa fa-solid fa-gear fa-spin';
                            case 'completed': return 'fa fa-check';
                            case 'failed': return 'fa fa-times fa-shake';
                            default: return 'fa fa-question fa-fade';
                        }
                    })()}"></i>
                    ${task.status.charAt(0).toUpperCase() + task.status.slice(1).replace('-', ' ')}
                </span>
            </div>

            <!-- Stats -->
            <div class="row mb-3">
                <div class="col-6">
                    <p class="mb-1 small">${isShiftedTask ? (task.action_type === 'removed' ? 'Removed Spaces' : 'Space Transferred') : 'Total Inboxes'}</p>
                    <h4 class="fw-bold mb-0">${isShiftedTask ? (task.space_transferred || 0) : (task.total_inboxes || 0)}</h4>
                </div>
                <div class="col-6 text-end">
                    <p class="mb-1 small">Splits</p>
                    <h4 class="fw-bold mb-0">${task.splits_count || 0}</h4>
                </div>
            </div>

            <!-- Panel Information for Shifted Tasks or Inboxes & Domains for Regular Tasks -->
            ${isShiftedTask ? `
                <div class="row mb-3">
                    <div class="col">
                        <div style="background-color: rgba(0, 0, 0, 0.398); border: 1px solid #464646;"
                            class="rounded py-2 px-3">
                            ${task.action_type === 'removed' ? `
                                <!-- Show only From Panel when action is removed -->
                                <div class="text-center"><small class="mb-1 small">Panel</small><h6 class="fw-semibold mb-0">${task.from_panel ? task.from_panel.title : 'N/A'}</h6><small class="mb-1 small">ID: ${task.from_panel ? task.from_panel.id : 'N/A'}</small></div>
                            ` : `
                                <!-- Show To Panel for other actions -->
                                <div class="text-center">
                                    <small class="mb-1 small">To Panel</small>
                                    <h6 class="fw-semibold mb-0">${task.to_panel ? task.to_panel.title : 'N/A'}</h6>
                                    <small class="mb-1 small">ID: ${task.to_panel ? task.to_panel.id : 'N/A'}</small>
                                </div>
                            `}
                        </div>
                    </div>
                </div>
            ` : `
                <!-- Inboxes & Domains for Regular Domain Removal Tasks -->
                <div class="row mb-3">
                    <div class="col">
                        <div style="background-color: rgba(0, 0, 0, 0.398); border: 1px solid #464646;"
                            class="rounded py-2 px-3 d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-1 small">Inboxes/Domain</p>
                                <h6 class="fw-semibold mb-0">${task.inboxes_per_domain || 1}</h6>
                            </div>
                            <div class="text-end">
                                <p class="mb-1 small">Total Domains</p>
                                <h6 class="fw-semibold mb-0">${task.total_domains || 0}</h6>
                            </div>
                        </div>
                    </div>
                </div>
            `}

            <!-- Footer with User -->
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div>
                        <img src="${task.customer_image || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(task.customer_name || 'User') + '&background=007bff&color=fff'}" 
                             alt="User" style="border-radius: 50px" width="40" height="40">
                    </div>
                    <div>
                        <p class="mb-0 fw-semibold">${task.customer_name || 'N/A'}</p>
                        ${isShiftedTask && task.assigned_to_name ? `<small class="mb-1 small">Assigned: ${task.assigned_to_name}</small>` : ''}
                    </div>
                </div>
                ${!isShiftedTask ? `
                <div>
                    ${task.splits_count > 0 ? `
                        <button class="btn btn-primary btn-sm d-flex align-items-center justify-content-center"
                            onclick="viewTaskDetails(${task.task_id})" 
                            data-bs-toggle="offcanvas" 
                            data-bs-target="#task-details-view">
                            <i class="fas fa-arrow-right text-white"></i>
                        </button>
                    ` : `
                        <p class="mb-0 fw-semibold">No splits available</span>
                    `}
                    
                </div>
                ` : `
                <div class="d-flex gap-2">
                    ${task.status === 'in-progress' ? `
                        <button class="btn btn-success btn-sm d-flex align-items-center justify-content-center"
                            onclick="completeShiftedTask(${task.task_id})"
                            title="Mark as Completed">
                            <i class="fas fa-check text-white"></i>
                        </button>
                    ` : ''}
                    ${task.status === 'completed' ? `
                        <span class="badge bg-success">
                            <i class="fas fa-check me-1"></i>Completed
                        </span>
                    ` : ''}
                </div>
                `}
            </div>
        `;
        return div;
    }

    // Helper functions
    function getStatusClass(status) {
        const classes = {
            'pending': 'bg-warning text-dark',
            'in-progress': 'bg-info text-dark',
            'completed': 'bg-success text-white',
            'failed': 'bg-danger text-white'
        };
        return classes[status] || 'bg-secondary text-white';
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }

    function showError(message, type) {
        const containerMap = {
            'my-tasks': 'my-tasks-container',
            'all-tasks': 'all-tasks-container',
            'shifted-tasks': 'shifted-tasks-container'
        };
        const containerId = containerMap[type];
        const container = document.getElementById(containerId);
        container.innerHTML = `
            <div class="empty-state text-center" style="grid-column: 1 / -1;">
                <i class="fas fa-exclamation-triangle text-danger fa-3x mb-3"></i>
                <h5>Error Loading Tasks</h5>
                <p>${message}</p>
                <button class="btn btn-primary btn-sm" onclick="loadTasks('${type}')">
                    <i class="fas fa-retry me-1"></i> Retry
                </button>
            </div>
        `;
    }

    // View task details function
    async function viewTaskDetails(taskId) {
        try {
            // Show loading in offcanvas
            const container = document.getElementById('taskDetailsContainer');
            if (container) {
                container.innerHTML = `
                    <div id="taskLoadingState" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading task details...</span>
                        </div>
                        <p class="mt-2">Loading task details...</p>
                    </div>
                `;
            }
            
            // Show offcanvas with proper cleanup
            const offcanvasElement = document.getElementById('task-details-view');
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
                const offcanvasTitle = document.getElementById('task-details-viewLabel');
                if (offcanvasTitle) {
                    offcanvasTitle.innerHTML = 'Order Details';
                }
            }, { once: true });
            
            offcanvas.show();
            
            // Fetch task details using contractor route
            const response = await fetch(`{{ url('contractor/myTask') }}/${taskId}/details`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            if (!response.ok) throw new Error('Failed to fetch task details');
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to load task details');
            }
            
            renderTaskDetails(data);
            
        } catch (error) {
            console.error('Error loading task details:', error);
            const container = document.getElementById('taskDetailsContainer');
            if (container) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle text-danger fs-3 mb-3"></i>
                        <h5>Error Loading Task Details</h5>
                        <p>Failed to load task details. Please try again.</p>
                        <button class="btn btn-primary" onclick="viewTaskDetails(${taskId})">Retry</button>
                    </div>
                `;
            }
        }
    }

    // Render task details in offcanvas (simplified version without timer and status change)
    function renderTaskDetails(data) {
        const container = document.getElementById('taskDetailsContainer');
        
        if (!data.splits || data.splits.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-inbox text-white fs-3 mb-3"></i>
                    <h5>No Order Data Found</h5>
                    <p>This order doesn't have any data yet.</p>
                </div>
            `;
            return;
        }
        
        const orderInfo = data.order;
        const reorderInfo = data.reorder_info;
        const splits = data.splits;

        // Update offcanvas title
        const offcanvasTitle = document.getElementById('task-details-viewLabel');
        if (offcanvasTitle && orderInfo) {
            offcanvasTitle.innerHTML = `Order Details #${orderInfo.id}`;
        }

        const detailsHtml = `
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6>${orderInfo.status_manage_by_admin}</h6>
                        <p class="text-white small mb-0">Customer: ${orderInfo.customer_name} | Date: ${formatDate(orderInfo.created_at)}</p>
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
                                <td>${split.inboxes_per_domain || 'N/A'}</td>
                                <td>
                                    <span class="py-1 px-2 rounded-1 border border-success success" style="font-size: 10px;">
                                        ${split.domains_count || 0} domain(s)
                                    </span>
                                </td>
                                <td>${split.total_inboxes || 'N/A'}</td>
                                <td>
                                    <div class="d-flex gap-1">
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
                            
                            <!-- Task Splits Domains -->
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
        
        container.innerHTML = detailsHtml;
        
        // Initialize chevron states and animations after rendering
        setTimeout(function() {
            initializeChevronStates();
        }, 100);
    }

    // Helper functions for task details canvas

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

    // Helper function to render profile links from object
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
                });
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

    // Task completion confirmation function
    async function confirmTaskCompletion(taskId) {
        try {
            // First get the completion summary
            const summaryResponse = await fetch(`{{ url('contractor/myTask') }}/${taskId}/completion-summary`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!summaryResponse.ok) {
                throw new Error('Failed to get task completion summary');
            }

            const summaryData = await summaryResponse.json();

            if (!summaryData.success) {
                throw new Error(summaryData.message || 'Failed to get task completion summary');
            }

            const summary = summaryData.data;
            
            // Check if task is already completed
            if (summary.task_status === 'completed') {
                Swal.fire({
                    title: 'Task Already Completed',
                    text: 'This task has already been marked as completed.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            // Create detailed confirmation message
            let confirmationText = `<div class="text-start">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <div class="card-body text-center text-white">
                                <i class="fas fa-tasks fs-2 mb-2"></i>
                                <h4 class="card-title mb-1 fw-bold">#${summary.task_id}</h4>
                                <small class="text-white-50">Task ID</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #36d1dc 0%, #5b86e5 100%);">
                            <div class="card-body text-center text-white">
                                <i class="fas fa-layer-group fs-2 mb-2"></i>
                                <h4 class="card-title mb-1 fw-bold">${summary.splits_count}</h4>
                                <small class="text-white-50">Splits to Process</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                            <div class="card-body text-center text-white">
                                <i class="fas fa-unlock fs-2 mb-2"></i>
                                <h4 class="card-title mb-1 fw-bold">${summary.total_spaces_to_release}</h4>
                                <small class="text-white-50">Spaces to Release</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            if (summary.panels_affected && summary.panels_affected.length > 0) {
                confirmationText += `
                <div class="mb-4 text-center">
                    <h5 class="text-light text-center mb-3 d-flex align-items-center">
                        <i class="fas fa-server me-2 text-primary"></i>
                        <span>Panels Affected</span>
                    </h5>
                    <div class="row g-2" style="max-height: 250px; overflow-y: auto; overflow-x: hidden;">`;
                
                summary.panels_affected.forEach(panel => {
                    confirmationText += `
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm h-100" style="background: white; border-left: 4px solid #007bff !important;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="card-title mb-0 text-dark fw-bold">${panel.title}</h6>
                                        <span class="badge bg-primary rounded-pill px-2 py-1" style="font-size: 10px;">ID: PNL-${panel.id}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="text-center">
                                            <div class="p-2 rounded-3" style="background: rgba(108, 117, 125, 0.1);">
                                                <div class="text-muted small mb-1">Current</div>
                                                <div class="fw-bold text-secondary">${panel.current_available}</div>
                                            </div>
                                        </div>
                                        <div class="text-center mx-2">
                                            <i class="fas fa-arrow-right text-muted"></i>
                                        </div>
                                        <div class="text-center">
                                            <div class="p-2 rounded-3" style="background: rgba(25, 135, 84, 0.1);">
                                                <div class="text-muted small mb-1">After</div>
                                                <div class="fw-bold text-success">${panel.new_available}</div>
                                            </div>
                                        </div>
                                        <div class="text-center ms-2">
                                            <span class="badge bg-success rounded-pill px-2 py-1" style="font-size: 11px;">
                                                +${panel.spaces_to_release}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });
                
                confirmationText += `
                    </div>
                </div>`;
            }

            confirmationText += `
                <div class="alert alert-warning mt-3 mb-0" style="font-size: 14px;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> After this action completed, all splits with assigned spaces will be removed and the spaces will be released back to their respective panels.
                </div>
            </div>`;

            // Show SweetAlert confirmation
            const result = await Swal.fire({
                title: 'Mark Task as Completed?',
                html: confirmationText,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check me-2"></i>Yes, Complete Task',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Cancel',
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                width: '600px',
                customClass: {
                    popup: 'swal-wide'
                }
            });

            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Completing Task...',
                    text: 'Please wait while we process the task completion and release panel spaces.',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Complete the task
                await completeTask(taskId);
            }

        } catch (error) {
            console.error('Error in task completion confirmation:', error);
            Swal.fire({
                title: 'Error',
                text: error.message || 'Failed to load task completion details',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    }

    // Function to complete the task
    async function completeTask(taskId) {
        try {
            const response = await fetch(`{{ url('contractor/myTask') }}/${taskId}/complete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (!response.ok) {
                throw new Error('Failed to complete task');
            }

            const data = await response.json();

            if (data.success) {
                // Show success message
                await Swal.fire({
                    title: 'Task Completed Successfully!',
                    html: `
                        <div class="text-start">
                            <p><strong>Task #${data.data.task_id} has been completed successfully.</strong></p>
                            <p><strong>Released spaces:</strong> ${data.data.released_spaces}</p>
                            <p><strong>Processed splits:</strong> ${data.data.processed_splits}</p>
                            <p><strong>Affected panels:</strong> ${data.data.affected_panels.length}</p>
                            <div class="alert alert-success mt-3 mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                All splits have been processed and spaces have been released back to the panels.
                            </div>
                        </div>
                    `,
                    icon: 'success',
                    confirmButtonText: 'OK'
                });

                // Reload the current tab's tasks to reflect the changes
                loadTasks(activeTab);
            } else {
                throw new Error(data.message || 'Failed to complete task');
            }

        } catch (error) {
            console.error('Error completing task:', error);
            Swal.fire({
                title: 'Error',
                text: error.message || 'Failed to complete task',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    }

    // Complete shifted task function
    async function completeShiftedTask(taskId) {
        try {
            // Show confirmation dialog
            const result = await Swal.fire({
                title: 'Complete Shifted Task?',
                html: `
                    <div class="text-start">
                        <p>Are you sure you want to mark this panel reassignment task as completed?</p>
                        <div class="mb-3">
                            <label for="completion_notes" class="form-label">Completion Notes (Optional)</label>
                            <textarea id="completion_notes" class="form-control" rows="3" 
                                placeholder="Add any completion notes or comments..."></textarea>
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="fas fa-check me-2"></i>Yes, Complete Task',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Cancel',
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                width: '500px',
                preConfirm: () => {
                    const notes = document.getElementById('completion_notes').value;
                    return { notes: notes };
                }
            });

            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Completing Task...',
                    text: 'Please wait while we update the task status.',
                    icon: 'info',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Update task status to completed
                const response = await fetch(`{{ url('contractor/taskInQueue/shifted') }}/${taskId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        status: 'completed',
                        completion_notes: result.value.notes
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Show success message
                    await Swal.fire({
                        title: 'Task Completed!',
                        text: 'The panel reassignment task has been marked as completed successfully.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    });

                    // Reload the shifted tasks to reflect the changes
                    loadTasks('shifted-tasks');
                } else {
                    throw new Error(data.message || 'Failed to complete task');
                }
            }

        } catch (error) {
            console.error('Error completing shifted task:', error);
            Swal.fire({
                title: 'Error',
                text: error.message || 'Failed to complete the task',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    }
</script>
@endpush
