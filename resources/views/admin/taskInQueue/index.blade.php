@extends('admin.layouts.app')
@section('title', 'Task Queue')
@push('styles')
    <style>
        .glass-box {
            background-color: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0.55rem .5rem;
        }

        .nav-link {
            font-size: 13px;
            color: #fff
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

    
        .status-pending {
            background: linear-gradient(45deg, #ffc107, #ffca28);
            color: #212529;
        }

        .status-in-progress {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }

        .status-completed {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .loading-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }

        .loading-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .loading-spinner {
            display: inline-block;
            width: 3rem;
            height: 3rem;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
@endpush

@section('content')
    <section class="py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-white mb-0">Task Queue Management</h4>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-light btn-sm" onclick="refreshTasks()">
                    <i class="fas fa-sync-alt me-1"></i> Refresh
                </button>
                <!-- <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#filterModal">
                    <i class="fas fa-filter me-1"></i> Filter
                </button> -->
            </div>
        </div>

        <ul class="nav nav-pills mb-3" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-tab-pane"
                    type="button" role="tab" aria-controls="pending-tab-pane" aria-selected="true">
                    Pending Tasks
                    {{-- <span class="badge bg-warning text-dark ms-1" id="pending-count">0</span> --}}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="shifted-pending-tab" data-bs-toggle="tab" data-bs-target="#shifted-pending-tab-pane"
                    type="button" role="tab" aria-controls="shifted-pending-tab-pane" aria-selected="false">
                    Migration Pending Tasks
                    {{-- <span class="badge bg-warning text-dark ms-1" id="shifted-pending-count">0</span> --}}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pool-migration-tab" data-bs-toggle="tab" data-bs-target="#pool-migration-tab-pane"
                    type="button" role="tab" aria-controls="pool-migration-tab-pane" aria-selected="false">
                    <i class="ti ti-swimming me-1"></i>Pool Migration Tasks
                    {{-- <span class="badge bg-info text-white ms-1" id="pool-migration-count">0</span> --}}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pool-panel-reassignment-tab" data-bs-toggle="tab" data-bs-target="#pool-panel-reassignment-tab-pane"
                    type="button" role="tab" aria-controls="pool-panel-reassignment-tab-pane" aria-selected="false">
                    <i class="ti ti-arrows-shuffle me-1"></i>Pool Panel Reassignment
                </button>
            </li>
            
            <li class="nav-item" role="presentation" style="display: none;">
                <button class="nav-link" id="in-progress-tab" data-bs-toggle="tab" data-bs-target="#in-progress-tab-pane"
                    type="button" role="tab" aria-controls="in-progress-tab-pane" aria-selected="false">
                    In Progress 
                    {{-- <span class="badge bg-info ms-1" id="progress-count">0</span> --}}
                </button>
            </li>
            <li class="nav-item" role="presentation" style="display: none;">
                <button class="nav-link" id="completed-tab" data-bs-toggle="tab" data-bs-target="#completed-tab-pane"
                    type="button" role="tab" aria-controls="completed-tab-pane" aria-selected="false">
                    Completed 
                    {{-- <span class="badge bg-success ms-1" id="completed-count">0</span> --}}
                </button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <!-- Pending Tasks Tab -->
            <div class="tab-pane fade show active" id="pending-tab-pane" role="tabpanel" aria-labelledby="pending-tab" tabindex="0">
                <div id="pending-tasks-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    <!-- Loading state -->
                    <div class="loading-state text-center" style="grid-column: 1 / -1;">
                        <div class="loading-spinner"></div>
                        <p class="text-white-50 mt-2">Loading tasks...</p>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button id="load-more-pending" class="btn btn-outline-light btn-sm d-none">
                        <i class="fas fa-plus me-1"></i> Load More
                    </button>
                </div>
            </div>

            <!-- Migration Pending Tasks Tab -->
            <div class="tab-pane fade" id="shifted-pending-tab-pane" role="tabpanel" aria-labelledby="shifted-pending-tab" tabindex="0">
                <div id="shifted-pending-tasks-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="text-center mt-4">
                    <button id="load-more-shifted-pending" class="btn btn-outline-light btn-sm d-none">
                        <i class="fas fa-plus me-1"></i> Load More
                    </button>
                </div>
            </div>

            <!-- Pool Migration Tasks Tab -->
            <div class="tab-pane fade" id="pool-migration-tab-pane" role="tabpanel" aria-labelledby="pool-migration-tab" tabindex="0">
                <div id="pool-migration-tasks-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    <!-- Loading state -->
                    <div class="loading-state text-center" style="grid-column: 1 / -1;">
                        <div class="loading-spinner"></div>
                        <p class="text-white-50 mt-2">Loading pool migration tasks...</p>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button id="load-more-pool-migration" class="btn btn-outline-light btn-sm d-none">
                        <i class="fas fa-plus me-1"></i> Load More
                    </button>
                </div>
            </div>

            <!-- Pool Panel Reassignment Tasks Tab -->
            <div class="tab-pane fade" id="pool-panel-reassignment-tab-pane" role="tabpanel" aria-labelledby="pool-panel-reassignment-tab" tabindex="0">
                <div id="pool-panel-reassignment-tasks-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    <!-- Loading state -->
                    <div class="loading-state text-center" style="grid-column: 1 / -1;">
                        <div class="loading-spinner"></div>
                        <p class="text-white-50 mt-2">Loading pool panel reassignment tasks...</p>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <button id="load-more-pool-panel-reassignment" class="btn btn-outline-light btn-sm d-none">
                        <i class="fas fa-plus me-1"></i> Load More
                    </button>
                </div>
            </div>

            <!-- In Progress Tasks Tab -->
            <div class="tab-pane fade" id="in-progress-tab-pane" role="tabpanel" aria-labelledby="in-progress-tab" tabindex="0">
                <div id="in-progress-tasks-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="text-center mt-4">
                    <button id="load-more-progress" class="btn btn-outline-light btn-sm d-none">
                        <i class="fas fa-plus me-1"></i> Load More
                    </button>
                </div>
            </div>

            <!-- Completed Tasks Tab -->
            <div class="tab-pane fade" id="completed-tab-pane" role="tabpanel" aria-labelledby="completed-tab" tabindex="0">
                <div id="completed-tasks-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px !important;">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="text-center mt-4">
                    <button id="load-more-completed" class="btn btn-outline-light btn-sm d-none">
                        <i class="fas fa-plus me-1"></i> Load More
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Task Details Offcanvas -->
    <div class="offcanvas offcanvas-bottom" style="height: 100vh;" tabindex="-1" id="task-details-view"
        aria-labelledby="task-details-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
        <div class="offcanvas-header border-0 pb-0" style="background-color: transparent">
            <h5 class="offcanvas-title text-white" id="task-details-viewLabel">Order Details</h5>
            <button type="button" class="bg-transparent border-0" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fas fa-times fs-5 text-white"></i>
            </button>
        </div>
        <div class="offcanvas-body pt-2">
            <div id="taskDetailsContainer">
                <!-- Dynamic content will be loaded here -->
                <div id="taskLoadingState" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading task details...</span>
                    </div>
                    <p class="mt-2 text-white">Loading task details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Shifted Task Details Offcanvas -->
    <div class="offcanvas offcanvas-bottom" style="height: 100vh;" tabindex="-1" id="shifted-task-details-view"
        aria-labelledby="shifted-task-details-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
        <div class="offcanvas-header border-0 pb-0" style="background-color: transparent">
            <h5 class="offcanvas-title text-white" id="shifted-task-details-viewLabel">Panel Reassignment Details</h5>
            <button type="button" class="bg-transparent border-0" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fas fa-times fs-5 text-white"></i>
            </button>
        </div>
        <div class="offcanvas-body pt-2">
            <div id="shiftedTaskDetailsContainer">
                <!-- Dynamic content will be loaded here -->
                <div id="shiftedTaskLoadingState" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading panel reassignment details...</span>
                    </div>
                    <p class="mt-2 text-white">Loading panel reassignment details...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Pool Panel Reassignment Task Details Offcanvas -->
    <div class="offcanvas offcanvas-bottom" style="height: 100vh;" tabindex="-1" id="pool-panel-task-details-view"
        aria-labelledby="pool-panel-task-details-viewLabel" data-bs-backdrop="true" data-bs-scroll="false">
        <div class="offcanvas-header border-0 pb-0" style="background-color: transparent">
            <h5 class="offcanvas-title text-white" id="pool-panel-task-details-viewLabel">Pool Panel Reassignment Details</h5>
            <button type="button" class="bg-transparent border-0" data-bs-dismiss="offcanvas" aria-label="Close">
                <i class="fas fa-times fs-5 text-white"></i>
            </button>
        </div>
        <div class="offcanvas-body pt-2">
            <div id="poolPanelTaskDetailsContainer">
                <!-- Dynamic content will be loaded here -->
                <div id="poolPanelTaskLoadingState" class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading pool panel reassignment details...</span>
                    </div>
                    <p class="mt-2 text-white">Loading pool panel reassignment details...</p>
                </div>
            </div>
        </div>
    </div>

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

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1" aria-labelledby="filterModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="filterModalLabel">Filter Tasks</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="filterForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select bg-dark text-white border-secondary" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="in-progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="failed">Failed</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="user_id" class="form-label">Customer ID</label>
                                <input type="number" class="form-control bg-dark text-white border-secondary" id="user_id" name="user_id" placeholder="Enter customer ID">
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="date_from" class="form-label">Queue Date From</label>
                                <input type="date" class="form-control bg-dark text-white border-secondary" id="date_from" name="date_from">
                            </div>
                            <div class="col-md-6 mt-3">
                                <label for="date_to" class="form-label">Queue Date To</label>
                                <input type="date" class="form-control bg-dark text-white border-secondary" id="date_to" name="date_to">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-outline-light" onclick="resetFilters()">Reset</button>
                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    let currentFilters = {};
    let tasks = {
        pending: [],
        'shifted-pending': [],
        'pool-migration': [],
        'pool-panel-reassignment': [],
        'in-progress': [],
        completed: []
    };
    let pagination = {
        pending: { currentPage: 1, hasMore: false },
        'shifted-pending': { currentPage: 1, hasMore: false },
        'pool-migration': { currentPage: 1, hasMore: false },
        'pool-panel-reassignment': { currentPage: 1, hasMore: false },
        'in-progress': { currentPage: 1, hasMore: false },
        completed: { currentPage: 1, hasMore: false }
    };
    let isLoading = false;
    let activeTab = 'pending';

    // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
        loadTasks('pending');
        
        // Tab change handlers
        document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
            tab.addEventListener('shown.bs.tab', function(e) {
                const tabId = e.target.getAttribute('aria-controls');
                if (tabId === 'pending-tab-pane') {
                    activeTab = 'pending';
                    if (tasks.pending.length === 0) loadTasks('pending');
                } else if (tabId === 'shifted-pending-tab-pane') {
                    activeTab = 'shifted-pending';
                    if (tasks['shifted-pending'].length === 0) loadTasks('shifted-pending');
                } else if (tabId === 'pool-migration-tab-pane') {
                    activeTab = 'pool-migration';
                    if (tasks['pool-migration'].length === 0) loadPoolMigrationTasks();
                } else if (tabId === 'pool-panel-reassignment-tab-pane') {
                    activeTab = 'pool-panel-reassignment';
                    if (tasks['pool-panel-reassignment'].length === 0) loadPoolPanelReassignmentTasks();
                } else if (tabId === 'in-progress-tab-pane') {
                    activeTab = 'in-progress';
                    if (tasks['in-progress'].length === 0) loadTasks('in-progress');
                } else if (tabId === 'completed-tab-pane') {
                    activeTab = 'completed';
                    if (tasks.completed.length === 0) loadTasks('completed');
                }
            });
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('filterModal'));
            modal.hide();
            
            // Reload current tab with filters
            tasks[activeTab] = [];
            pagination[activeTab] = { currentPage: 1, hasMore: false };
            loadTasks(activeTab, false, true);
        });

        // Load more handlers
        document.getElementById('load-more-pending').addEventListener('click', function() {
            if (pagination.pending.hasMore && !isLoading) {
                loadTasks('pending', true);
            }
        });

        document.getElementById('load-more-shifted-pending').addEventListener('click', function() {
            if (pagination['shifted-pending'].hasMore && !isLoading) {
                loadTasks('shifted-pending', true);
            }
        });

        document.getElementById('load-more-progress').addEventListener('click', function() {
            if (pagination['in-progress'].hasMore && !isLoading) {
                loadTasks('in-progress', true);
            }
        });

        document.getElementById('load-more-completed').addEventListener('click', function() {
            if (pagination.completed.hasMore && !isLoading) {
                loadTasks('completed', true);
            }
        });

        document.getElementById('load-more-pool-migration').addEventListener('click', function() {
            if (pagination['pool-migration'].hasMore && !isLoading) {
                loadPoolMigrationTasks(true);
            }
        });

        document.getElementById('load-more-pool-panel-reassignment').addEventListener('click', function() {
            if (pagination['pool-panel-reassignment'].hasMore && !isLoading) {
                loadPoolPanelReassignmentTasks(true);
            }
        });
    });

    // Load tasks function
    async function loadTasks(status, append = false, useFilters = false) {
        if (isLoading) return;
        
        isLoading = true;
        const containerMap = {
            'pending': 'pending-tasks-container',
            'shifted-pending': 'shifted-pending-tasks-container',
            'in-progress': 'in-progress-tasks-container',
            'completed': 'completed-tasks-container'
        };
        const loadMoreBtnMap = {
            'pending': 'load-more-pending',
            'shifted-pending': 'load-more-shifted-pending',
            'in-progress': 'load-more-progress',
            'completed': 'load-more-completed'
        };
        
        const container = document.getElementById(containerMap[status]);
        const loadMoreBtn = document.getElementById(loadMoreBtnMap[status]);
        
        try {
            console.log('Loading tasks for status:', status, 'append:', append);
            
            if (!append) {
                container.innerHTML = `
                    <div class="loading-state text-center" style="grid-column: 1 / -1;">
                        <div class="loading-spinner"></div>
                        <p class="text-white-50 mt-2">Loading tasks...</p>
                    </div>
                `;
            }

            const params = new URLSearchParams({
                type: status,
                page: append ? pagination[status].currentPage + 1 : 1,
                per_page: 12,
                ...currentFilters
            });

            console.log('Request URL:', `{{ route('admin.taskInQueue.data') }}?${params}`);
            
            const response = await fetch(`{{ route('admin.taskInQueue.data') }}?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            console.log('Response data:', data);

            if (!data.success) {
                throw new Error(data.message || 'Failed to load tasks');
            }

            const newTasks = data.data || [];
            console.log('New tasks loaded:', newTasks.length);
            
            if (append) {
                tasks[status] = tasks[status].concat(newTasks);
            } else {
                tasks[status] = newTasks;
            }
            
            pagination[status] = {
                currentPage: data.pagination.current_page,
                hasMore: data.pagination.has_more_pages
            };
            
            renderTasks(status, append);
            updateTabCounts();
            updateLoadMoreButton(status);
            
        } catch (error) {
            console.error('Error loading tasks:', error);
            if (!append) {
                showError(error.message, status);
            }
        } finally {
            isLoading = false;
        }
    }

    // Load pool migration tasks function
    async function loadPoolMigrationTasks(append = false) {
        if (isLoading) return;
        
        isLoading = true;
        const container = document.getElementById('pool-migration-tasks-container');
        const status = 'pool-migration';
        
        try {
            if (!append) {
                container.innerHTML = `
                    <div class="loading-state text-center" style="grid-column: 1 / -1;">
                        <div class="loading-spinner"></div>
                        <p class="text-white-50 mt-2">Loading pool migration tasks...</p>
                    </div>
                `;
            }
            
            const page = append ? pagination[status].currentPage + 1 : 1;
            const params = new URLSearchParams({
                page: page,
                per_page: 12
            });
            
            const response = await fetch(`{{ route('admin.taskInQueue.pool-migration-tasks') }}?${params}`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to load pool migration tasks');
            }
            
            if (append) {
                tasks[status] = [...tasks[status], ...data.data];
            } else {
                tasks[status] = data.data;
            }
            
            pagination[status] = {
                currentPage: data.pagination.current_page,
                hasMore: data.pagination.has_more_pages
            };
            
            renderPoolMigrationTasks(append);
            updateLoadMoreButton(status);
            
        } catch (error) {
            console.error('Error loading pool migration tasks:', error);
            if (!append) {
                container.innerHTML = `
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        <h5>Error Loading Pool Migration Tasks</h5>
                        <p>${error.message}</p>
                        <button class="btn btn-outline-light btn-sm" onclick="loadPoolMigrationTasks()">
                            <i class="fas fa-redo me-1"></i> Retry
                        </button>
                    </div>
                `;
            }
        } finally {
            isLoading = false;
        }
    }

    // Load pool panel reassignment tasks function
    async function loadPoolPanelReassignmentTasks(append = false) {
        if (isLoading) return;

        isLoading = true;
        const container = document.getElementById('pool-panel-reassignment-tasks-container');
        const status = 'pool-panel-reassignment';

        try {
            if (!append) {
                container.innerHTML = `
                    <div class="loading-state text-center" style="grid-column: 1 / -1;">
                        <div class="loading-spinner"></div>
                        <p class="text-white-50 mt-2">Loading pool panel reassignment tasks...</p>
                    </div>
                `;
            }

            const page = append ? pagination[status].currentPage + 1 : 1;
            const params = new URLSearchParams({
                page: page,
                per_page: 12,
            });

            const response = await fetch(`{{ route('admin.taskInQueue.pool-panel-reassignment-tasks') }}?${params}`);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to load pool panel reassignment tasks');
            }

            if (append) {
                tasks[status] = [...tasks[status], ...data.data];
            } else {
                tasks[status] = data.data;
            }

            pagination[status] = {
                currentPage: data.pagination.current_page,
                hasMore: data.pagination.has_more_pages,
            };

            renderPoolPanelReassignmentTasks(append);
            updateLoadMoreButton(status);
        } catch (error) {
            console.error('Error loading pool panel reassignment tasks:', error);
            if (!append) {
                container.innerHTML = `
                    <div class="empty-state" style="grid-column: 1 / -1;">
                        <i class="fas fa-exclamation-triangle text-danger"></i>
                        <h5>Error Loading Pool Panel Reassignment Tasks</h5>
                        <p>${error.message}</p>
                        <button class="btn btn-outline-light btn-sm" onclick="loadPoolPanelReassignmentTasks()">
                            <i class="fas fa-redo me-1"></i> Retry
                        </button>
                    </div>
                `;
            }
        } finally {
            isLoading = false;
        }
    }

    // Render pool migration tasks function
    function renderPoolMigrationTasks(append = false) {
        const container = document.getElementById('pool-migration-tasks-container');
        const tasksList = tasks['pool-migration'];
        
        if (tasksList.length === 0 && !append) {
            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="ti ti-swimming" style="font-size: 4rem; opacity: 0.5;"></i>
                    <h5>No Pool Migration Tasks Found</h5>
                    <p>There are no pool order migration tasks to display.</p>
                </div>
            `;
            return;
        }

        if (!append) {
            container.innerHTML = '';
        }

        const tasksToRender = append ? tasksList.slice(tasks['pool-migration'].length - (tasksList.length - tasks['pool-migration'].length)) : tasksList;
        
        tasksToRender.forEach((task, index) => {
            const taskCard = createPoolMigrationCard(task);
            container.appendChild(taskCard);
        });
    }

    // Render pool panel reassignment tasks function
    function renderPoolPanelReassignmentTasks(append = false) {
        const container = document.getElementById('pool-panel-reassignment-tasks-container');
        const tasksList = tasks['pool-panel-reassignment'];

        if (tasksList.length === 0 && !append) {
            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fas fa-exchange-alt" style="font-size: 4rem; opacity: 0.5;"></i>
                    <h5>No Pool Panel Reassignment Tasks Found</h5>
                    <p>There are no pool panel reassignment tasks to display.</p>
                </div>
            `;
            return;
        }

        if (!append) {
            container.innerHTML = '';
        }

        const tasksToRender = append
            ? tasksList.slice(tasks['pool-panel-reassignment'].length - (tasksList.length - tasks['pool-panel-reassignment'].length))
            : tasksList;

        tasksToRender.forEach((task) => {
            const taskCard = createPoolPanelReassignmentCard(task);
            container.appendChild(taskCard);
        });
    }

    // Create pool panel reassignment task card
    function createPoolPanelReassignmentCard(task) {
        const div = document.createElement('div');
        div.className = 'card task-card p-3 rounded-4 border-0 shadow';
        div.dataset.taskId = task.task_id; // Store task ID in data attribute

        const statusClass = getStatusClass(task.status);

        div.innerHTML = `
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="text-white-50 small mb-1">Task #${task.task_id}</span>
                    <span class="badge px-2 py-1 rounded ${statusClass} ms-1">
                        ${task.status.charAt(0).toUpperCase() + task.status.slice(1).replace('-', ' ')}
                    </span>
                </div>
                ${task.status === 'pending' && !task.assigned_to ? `
                    <button class="btn btn-sm border-0 assign-btn" 
                            style="background: linear-gradient(145deg, #3f3f62, #1d2239); box-shadow: 0 0 10px #0077ff;"
                            data-action="assign"
                            title="Assign to Me">
                        <i class="fas fa-user-plus text-white"></i>
                    </button>
                ` : `
                    <button class="btn btn-sm border-0 view-btn"
                            style="background: linear-gradient(145deg, #3f3f62, #1d2239); box-shadow: 0 0 10px #0077ff;"
                            data-action="view"
                            data-bs-toggle="offcanvas" 
                            data-bs-target="#pool-panel-task-details-view"
                            title="View Task Details">
                        <i class="fas fa-eye text-white"></i>
                    </button>
                `}
            </div>

            <!-- Pool Info -->
            <div class="mb-3">
                <div class="glass-box mb-2">
                    <div class="d-flex justify-content-between">
                        <span class="small text-white-50">Pool ID</span>
                        <span class="fw-bold text-white">#${task.pool_id}</span>
                    </div>
                </div>
                ${task.domain_url ? `
                    <div class="glass-box mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="small text-white-50">Domain URL</span>
                            <span class="fw-bold text-white">${task.domain_url}</span>
                        </div>
                    </div>
                ` : ''}
            </div>

            <!-- Movement Info -->
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="glass-box text-center">
                        <small class="text-white-50 d-block mb-1">From Panel</small>
                        <span class="fw-semibold text-white">${task.from_panel ? task.from_panel.title : 'N/A'}</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="glass-box text-center">
                        <small class="text-white-50 d-block mb-1">To Panel</small>
                        <span class="fw-semibold text-white">${task.to_panel ? task.to_panel.title : 'N/A'}</span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="glass-box text-center">
                        <small class="text-white-50 d-block mb-1">Space Transferred</small>
                        <span class="fw-semibold text-white">${task.space_transferred || 0}</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="glass-box text-center">
                        <small class="text-white-50 d-block mb-1">Splits</small>
                        <span class="fw-semibold text-white">${task.splits_count || 0}</span>
                    </div>
                </div>
            </div>

            <!-- Assignment Info -->
            ${task.assigned_to_name ? `
                <div class="glass-box mb-3">
                    <div class="d-flex justify-content-between">
                        <span class="small text-white-50">Assigned To</span>
                        <span class="fw-bold text-white">${task.assigned_to_name}</span>
                    </div>
                </div>
            ` : ''}
        `;

        return div;
    }

    // Add event delegation
    document.addEventListener('DOMContentLoaded', function() {
        // Event delegation for pool panel reassignment cards
        document.addEventListener('click', function(e) {
            const card = e.target.closest('.task-card');
            if (!card) return;

            const taskId = card.dataset.taskId;
            
            if (e.target.closest('.assign-btn')) {
                e.preventDefault();
                assignPoolPanelReassignmentTaskToMe(parseInt(taskId));
            } else if (e.target.closest('.view-btn')) {
                e.preventDefault();
                viewPoolPanelReassignmentTaskDetails(parseInt(taskId));
            }
        });
    });

    // Create pool migration task card
    function createPoolMigrationCard(task) {
        const div = document.createElement('div');
        div.className = 'card task-card p-3 rounded-4 border-0 shadow';
        
        const statusClass = getStatusClass(task.status);
        const taskTypeColor = task.task_type === 'configuration' ? 'success' : 'warning';
        
        div.innerHTML = `
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <span class="text-white-50 small mb-1">${task.task_type_icon} #${task.task_id}</span>
                    <span class="badge px-2 py-1 rounded ${statusClass} ms-1">
                        ${task.status.charAt(0).toUpperCase() + task.status.slice(1).replace('-', ' ')}
                    </span>
                    <span class="badge bg-${taskTypeColor} px-2 py-1 rounded ms-1">
                        ${task.task_type_label}
                    </span>
                </div>
                ${task.status === 'pending' && !task.assigned_to ? `
                    <button class="btn btn-sm border-0 assign-btn" 
                            style="background: linear-gradient(145deg, #3f3f62, #1d2239); box-shadow: 0 0 10px #0077ff;"
                            onclick="assignPoolMigrationTaskToMe(${task.task_id})"
                            title="Assign to Me">
                        <i class="fas fa-user-plus text-white"></i>
                    </button>
                ` : `
                    <button class="btn btn-sm border-0"
                            style="background: linear-gradient(145deg, #3f3f62, #1d2239); box-shadow: 0 0 10px #0077ff;"
                            onclick="viewPoolMigrationTaskDetails(${task.task_id})"
                            data-bs-toggle="offcanvas" 
                            data-bs-target="#task-details-view"
                            title="View Task Details">
                        <i class="fas fa-eye text-white"></i>
                    </button>
                `}
            </div>

            <!-- Order Info -->
            <div class="mb-3">
                <div class="glass-box mb-2">
                    <div class="d-flex justify-content-between">
                        <span class="small text-white-50">Pool Order ID</span>
                        <span class="fw-bold text-white">#${task.pool_order_id}</span>
                    </div>
                </div>
                <div class="glass-box mb-2">
                    <div class="d-flex justify-content-between">
                        <span class="small text-white-50">Plan</span>
                        <span class="fw-bold text-white">${task.plan_name}</span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="glass-box text-center">
                        <small class="text-white-50 d-block mb-1">Domains</small>
                        <span class="fw-semibold text-white">${task.domains_count}</span>
                    </div>
                </div>
                <div class="col-6">
                    <div class="glass-box text-center">
                        <small class="text-white-50 d-block mb-1">Inboxes</small>
                        <span class="fw-semibold text-white">${task.total_inboxes}</span>
                    </div>
                </div>
            </div>

            <!-- Additional Info -->
            ${task.assigned_to ? `
                <div class="glass-box mb-3">
                    <div class="d-flex justify-content-between">
                        <span class="small text-white-50">Assigned To</span>
                        <span class="fw-bold text-white">${task.assigned_to_name}</span>
                    </div>
                </div>
            ` : ''}

            <!-- Customer Info -->
            <div class="d-flex align-items-center mt-auto">
                <img src="${task.customer_image || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(task.customer_name || 'User') + '&background=007bff&color=fff'}" 
                     alt="User" class="rounded-circle border border-info" width="42" height="42">
                <div class="ms-2 flex-grow-1">
                    <div class="fw-semibold text-white">${task.customer_name || 'Unknown User'}</div>
                    <small class="text-white-50">${task.customer_email || 'N/A'}</small>
                </div>
            </div>
        `;
        
        return div;
    }

    // Assign pool migration task to current admin
    async function assignPoolPanelReassignmentTaskToMe(taskId) {
        try {
            const result = await Swal.fire({
                title: 'Assign Pool Panel Reassignment Task?',
                text: 'Do you want to assign this pool panel reassignment task to yourself?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, assign to me!',
                cancelButtonText: 'Cancel',
            });

            if (!result.isConfirmed) return;

            const response = await fetch(`/admin/taskInQueue/pool-panel-reassignment/${taskId}/assign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to assign pool panel reassignment task');
            }

            await Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
            });

            tasks['pool-panel-reassignment'] = [];
            pagination['pool-panel-reassignment'] = { currentPage: 1, hasMore: false };
            loadPoolPanelReassignmentTasks();
        } catch (error) {
            console.error('Error assigning pool panel reassignment task:', error);
            Swal.fire({
                title: 'Error!',
                text: error.message || 'Failed to assign pool panel reassignment task',
                icon: 'error',
            });
        }
    }

    // View pool migration task details
    async function viewPoolMigrationTaskDetails(taskId) {
        try {
            const response = await fetch(`/admin/taskInQueue/pool-migration/${taskId}/details`);
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to load pool migration task details');
            }
            
            const task = data.task;
            const order = data.order;
            const metadata = data.metadata;
            
            // Build task details HTML (reuse existing task details container)
            const taskDetailsContainer = document.getElementById('taskDetailsContainer');
            taskDetailsContainer.innerHTML = `
                <div class="task-details-content">
                    <h4 class="text-white mb-3">${task.task_type_icon} ${task.task_type_label}</h4>
                    
                    <div class="mb-4">
                        <h6 class="text-white-50">Task Information</h6>
                        <div class="glass-box mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-white-50">Task ID</span>
                                <span class="text-white fw-bold">#${task.id}</span>
                            </div>
                        </div>
                        <div class="glass-box mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-white-50">Status</span>
                                <span class="badge ${getStatusClass(task.status)}">${task.status}</span>
                            </div>
                        </div>
                        <div class="glass-box mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-white-50">Assigned To</span>
                                <span class="text-white">${task.assigned_to_name}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-white-50 mb-0">Pool Order Details</h6>
                            ${order.has_domains ? `
                                <a href="/admin/pool-orders/${order.order_id}/download-domains-csv" 
                                   class="btn btn-sm btn-success" 
                                   title="Download Domains with Prefixes CSV">
                                    <i class="fa fa-download me-1"></i>CSV
                                </a>
                            ` : ''}
                        </div>
                        <div class="glass-box mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-white-50">Order ID</span>
                                <span class="text-white fw-bold">#${order.order_id}</span>
                            </div>
                        </div>
                        <div class="glass-box mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-white-50">Plan</span>
                                <span class="text-white">${order.plan_name}</span>
                            </div>
                        </div>
                        <div class="glass-box mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-white-50">Domains / Inboxes</span>
                                <span class="text-white">${order.selected_domains_count} / ${order.total_inboxes}</span>
                            </div>
                        </div>
                        <div class="glass-box mb-2">
                            <div class="d-flex justify-content-between">
                                <span class="text-white-50">Platform</span>
                                <span class="text-white">${order.hosting_platform}</span>
                            </div>
                        </div>
                    </div>
                    
                    ${task.notes ? `
                        <div class="mb-4">
                            <h6 class="text-white-50">Notes</h6>
                            <div class="glass-box">
                                <p class="text-white mb-0">${task.notes}</p>
                            </div>
                        </div>
                    ` : ''}
                    
                    ${task.status === 'pending' || task.status === 'in-progress' ? `
                        <div class="d-grid gap-2">
                            ${task.status === 'in-progress' ? `
                                <button class="btn btn-success" onclick="updatePoolMigrationTaskStatus(${task.id}, 'completed')">
                                    <i class="fas fa-check me-2"></i>Mark as Completed
                                </button>
                            ` : ''}
                        </div>
                    ` : ''}
                </div>
            `;
            
        } catch (error) {
            console.error('Error loading pool migration task details:', error);
            Swal.fire({
                title: 'Error!',
                text: error.message || 'Failed to load task details',
                icon: 'error'
            });
        }
    }

    // Update pool migration task status
    async function updatePoolMigrationTaskStatus(taskId, newStatus) {
        try {
            const notes = newStatus === 'completed' ? await Swal.fire({
                title: 'Completion Notes',
                input: 'textarea',
                inputLabel: 'Add any notes about task completion (optional)',
                inputPlaceholder: 'Enter notes...',
                showCancelButton: true,
                confirmButtonText: 'Complete Task',
                cancelButtonText: 'Cancel'
            }).then(result => result.isConfirmed ? result.value : null) : null;
            
            if (newStatus === 'completed' && notes === null) return;
            
            const response = await fetch(`/admin/taskInQueue/pool-migration/${taskId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    status: newStatus,
                    notes: notes
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                
                // Close offcanvas and reload tasks
                const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('task-details-view'));
                if (offcanvas) offcanvas.hide();
                
                tasks['pool-migration'] = [];
                pagination['pool-migration'] = { currentPage: 1, hasMore: false };
                loadPoolMigrationTasks();
            } else {
                throw new Error(data.message);
            }
        } catch (error) {
            console.error('Error updating pool migration task status:', error);
            Swal.fire({
                title: 'Error!',
                text: error.message || 'Failed to update task status',
                icon: 'error'
            });
        }
    }

    // Render tasks function
    function renderTasks(status, append = false) {
        const containerMap = {
            'pending': 'pending-tasks-container',
            'shifted-pending': 'shifted-pending-tasks-container',
            'in-progress': 'in-progress-tasks-container',
            'completed': 'completed-tasks-container'
        };
        
        const container = document.getElementById(containerMap[status]);
        const tasksList = tasks[status];
        
        if (tasksList.length === 0 && !append) {
            container.innerHTML = `
                <div class="empty-state" style="grid-column: 1 / -1;">
                    <i class="fas fa-tasks"></i>
                    <h5>No ${status.charAt(0).toUpperCase() + status.slice(1)} Tasks Found</h5>
                    <p>There are no ${status.replace('-', ' ')} tasks to display.</p>
                </div>
            `;
            return;
        }

        if (!append) {
            container.innerHTML = '';
        }

        const tasksToRender = append ? tasksList.slice(tasks[status].length - (tasksList.length - tasks[status].length)) : tasksList;
        
        tasksToRender.forEach((task, index) => {
            const taskCard = createTaskCard(task, status);
            container.appendChild(taskCard);
        });
    }
    
    // Create task card function
    function createTaskCard(task, status) {
        const div = document.createElement('div');
        div.className = 'card task-card p-3 rounded-4 border-0 shadow';
        
        const statusClass = getStatusClass(task.status);
        const isShiftedTask = task.type === 'panel_reassignment';
        // Different content for shifted pending tasks
        if (isShiftedTask) {
            div.innerHTML = `
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="text-white-50 small mb-1">#${task.task_id}</span>
                        <span class="badge px-2 py-1 rounded ${statusClass}">
                            ${task.status.charAt(0).toUpperCase() + task.status.slice(1).replace('-', ' ')}
                        </span>
                    </div>
                    ${task.status === 'pending' ? `
                        <button class="btn btn-sm border-0 assign-btn" 
                                style="background: linear-gradient(145deg, #3f3f62, #1d2239); box-shadow: 0 0 10px #0077ff;"
                                onclick="assignShiftedTaskToMe(${task.task_id})"
                                title="Assign to Me">
                            <i class="fas fa-user-plus text-white"></i>
                        </button>
                    ` : `
                        <button class="btn btn-sm border-0"
                                style="background: linear-gradient(145deg, #3f3f62, #1d2239); box-shadow: 0 0 10px #0077ff;"
                                onclick="viewShiftedTaskDetails(${task.task_id})"
                                data-bs-toggle="offcanvas" 
                                data-bs-target="#shifted-task-details-view"
                                title="View Panel Reassignment Details">
                            <i class="fas fa-arrow-right text-white"></i>
                        </button>
                    `}
                </div>

                <!-- Panel Info -->
                <div class="mb-4">
                    <div class="glass-box mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="small text-white-50">Action Type</span>
                            <span class="fw-bold text-white">
                                ${task.action_type === 'removed' ? '<i class="fas fa-minus-circle text-danger me-1"></i>Removal' : 
                                  task.action_type === 'added' ? '<i class="fas fa-plus-circle text-success me-1"></i>Assignment' : 
                                  task.action_type.charAt(0).toUpperCase() + task.action_type.slice(1)}
                            </span>
                        </div>
                    </div>
                    <div class="glass-box mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="small text-white-50">${task.action_type === 'added' ? 'Space Transferred' : 'Space Deleted'}</span>
                            <span class="fw-bold text-white">${task.space_transferred || 0}</span>
                        </div>
                    </div>
                </div>

                <!-- Panel Movement Info -->
                <div class="row g-2 mb-4">
                    <div class="col-6">
                        <div class="glass-box text-center">
                            <small class="text-white-50 d-block mb-1">From Panel</small>
                            <span class="fw-semibold text-white">${task.from_panel ? task.from_panel.title : 'N/A'}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="glass-box text-center">
                            <small class="text-white-50 d-block mb-1">To Panel</small>
                            <span class="fw-semibold text-white">${task.to_panel ? task.to_panel.title : 'N/A'}</span>
                        </div>
                    </div>
                </div>

                <!-- User -->
                <div class="d-flex align-items-center mt-auto">
                    <img src="${task.customer_image || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(task.customer_name || 'User') + '&background=007bff&color=fff'}" 
                         alt="User" class="rounded-circle border border-info" width="42" height="42">
                    <div class="ms-3 flex-grow-1">
                        <p class="mb-0 fw-semibold text-white">${task.customer_name || 'N/A'}</p>
                        <small class="text-white-50">
                            ${task.order_id ? `Order #${task.order_id}` : 'No Order'}
                            ${task.assigned_to_name ? ` • ${task.assigned_to_name}` : ''}
                        </small>
                    </div>
                    ${(task.from_panel || task.to_panel) ? `
                        <button class="btn btn-primary btn-sm d-flex align-items-center justify-content-center ms-2"
                            onclick="viewShiftedTaskDetails(${task.task_id})" 
                            data-bs-toggle="offcanvas" 
                            data-bs-target="#shifted-task-details-view"
                            title="View Panel Reassignment Details">
                            <i class="fas fa-eye text-white"></i>
                        </button>
                    ` : `
                        <small class="text-white-50 ms-2">No details available</small>
                    `}
                </div>
            `;
        } else {
            // Regular task card
            const queueDate = new Date(task.started_queue_date);
            const now = new Date();
            
            div.innerHTML = `
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="text-white-50 small mb-1">#${task.task_id}</div>
                        <span class="badge px-2 py-1 rounded ${statusClass}">
                            ${task.status.charAt(0).toUpperCase() + task.status.slice(1).replace('-', ' ')}
                        </span>
                    </div>
                    ${task.status === 'pending' ? `
                        <button class="btn btn-sm border-0 assign-btn" 
                                style="background: linear-gradient(145deg, #3f3f62, #1d2239); box-shadow: 0 0 10px #0077ff;"
                                onclick="assignTaskToMe(${task.task_id})"
                                title="Assign to Me">
                            <i class="fas fa-user-plus text-white"></i>
                        </button>
                    ` : `
                        <button class="btn btn-sm border-0"
                                style="background: linear-gradient(145deg, #3f3f62, #1d2239); box-shadow: 0 0 10px #0077ff;"
                                onclick="viewTaskDetails(${task.task_id})"
                                data-bs-toggle="offcanvas" 
                                data-bs-target="#task-details-view"
                                title="View Details">
                            <i class="fas fa-arrow-right text-white"></i>
                        </button>
                    `}
                </div>

                <!-- Stats -->
                <div class="mb-4">
                    <div class="glass-box mb-2">
                        <div class="d-flex justify-content-between">
                            <span class="small text-white-50">Total Inboxes</span>
                            <span class="fw-bold text-white">${task.total_inboxes || 0}</span>
                        </div>
                    </div>
                    <div class="glass-box">
                        <div class="d-flex justify-content-between">
                            <span class="small text-white-50">Splits</span>
                            <span class="fw-bold text-white">${task.splits_count || 0}</span>
                        </div>
                    </div>
                </div>

                <!-- Domain Info -->
                <div class="row g-2 mb-4">
                    <div class="col-6">
                        <div class="glass-box text-center">
                            <small class="text-white-50 d-block mb-1">Inboxes / Domain</small>
                            <span class="fw-semibold text-white">${task.inboxes_per_domain || 1}</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="glass-box text-center">
                            <small class="text-white-50 d-block mb-1">Total Domains</small>
                            <span class="fw-semibold text-white">${task.total_domains || 0}</span>
                        </div>
                    </div>
                </div>

                <!-- User -->
                <div class="d-flex align-items-center mt-auto">
                    <img src="${task.customer_image || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(task.customer_name || 'User') + '&background=007bff&color=fff'}" 
                         alt="User" class="rounded-circle border border-info" width="42" height="42">
                    <div class="ms-3 flex-grow-1">
                        <p class="mb-0 fw-semibold text-white">${task.customer_name || 'N/A'}</p>
                        <small class="text-white-50">
                            ${task.order_id ? `Order #${task.order_id}` : 'No Order'}
                            ${task.assigned_to_name ? ` • ${task.assigned_to_name}` : ''}
                        </small>
                    </div>
                    ${task.splits_count > 0 ? `
                        <button class="btn btn-primary btn-sm d-flex align-items-center justify-content-center ms-2"
                            onclick="viewTaskDetails(${task.task_id})" 
                            data-bs-toggle="offcanvas" 
                            data-bs-target="#task-details-view"
                            title="View Task Details">
                            <i class="fas fa-eye text-white"></i>
                        </button>
                    ` : `
                        <small class="text-white-50 ms-2">No details available</small>
                    `}
                </div>
            `;
        }
        
        return div;
    }
    
    // Helper functions
    function getStatusClass(status) {
        const classes = {
            'pending': 'status-pending',
            'in-progress': 'status-in-progress',
            'completed': 'status-completed',
            'failed': 'bg-danger'
        };
        return classes[status] || 'bg-secondary';
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }

    // Update tab counts
    function updateTabCounts() {
        // Update pending tasks count
        fetch(`{{ route('admin.taskInQueue.data') }}?type=pending&per_page=1000`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const countEl = document.getElementById('pending-count');
                    if (countEl) countEl.textContent = data.pagination.total;
                }
            })
            .catch(error => console.error('Error fetching pending count:', error));
            
        // Update in-progress tasks count
        fetch(`{{ route('admin.taskInQueue.data') }}?type=in-progress&per_page=1000`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const countEl = document.getElementById('progress-count');
                    if (countEl) countEl.textContent = data.pagination.total;
                }
            })
            .catch(error => console.error('Error fetching in-progress count:', error));
            
        // Update completed tasks count
        fetch(`{{ route('admin.taskInQueue.data') }}?type=completed&per_page=1000`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const countEl = document.getElementById('completed-count');
                    if (countEl) countEl.textContent = data.pagination.total;
                }
            })
            .catch(error => console.error('Error fetching completed count:', error));
            
        // Update shifted pending tasks count
        fetch(`{{ route('admin.taskInQueue.data') }}?type=shifted-pending&per_page=1000`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const countEl = document.getElementById('shifted-pending-count');
                    if (countEl) countEl.textContent = data.pagination.total;
                }
            })
            .catch(error => console.error('Error fetching shifted-pending count:', error));
            
        // Update pool migration tasks count
        fetch(`{{ route('admin.taskInQueue.pool-migration-tasks') }}?per_page=1000`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const countEl = document.getElementById('pool-migration-count');
                    if (countEl) countEl.textContent = data.pagination.total;
                }
            })
            .catch(error => console.error('Error fetching pool-migration count:', error));
    }

    function updateLoadMoreButton(status) {
        const loadMoreBtnMap = {
            'pending': 'load-more-pending',
            'shifted-pending': 'load-more-shifted-pending',
            'in-progress': 'load-more-progress',
            'completed': 'load-more-completed',
            'pool-migration': 'load-more-pool-migration',
            'pool-panel-reassignment': 'load-more-pool-panel-reassignment'
        };
        
        const btn = document.getElementById(loadMoreBtnMap[status]);
        if (pagination[status].hasMore) {
            btn.classList.remove('d-none');
        } else {
            btn.classList.add('d-none');
        }
    }

    function showError(message, status) {
        const containerMap = {
            'pending': 'pending-tasks-container',
            'shifted-pending': 'shifted-pending-tasks-container',
            'in-progress': 'in-progress-tasks-container',
            'completed': 'completed-tasks-container',
            'pool-migration': 'pool-migration-tasks-container',
            'pool-panel-reassignment': 'pool-panel-reassignment-tasks-container'
        };
        
        const container = document.getElementById(containerMap[status]);
        container.innerHTML = `
            <div class="empty-state" style="grid-column: 1 / -1;">
                <i class="fas fa-exclamation-triangle text-danger"></i>
                <h5>Error Loading Tasks</h5>
                <p>${message}</p>
                <button class="btn btn-outline-light btn-sm" onclick="loadTasks('${status}')">
                    <i class="fas fa-retry me-1"></i> Retry
                </button>
            </div>
        `;
    }

    // Assign task to current admin
    async function assignTaskToMe(taskId) {
        try {
            const result = await Swal.fire({
                title: 'Assign Task to Yourself?',
                text: 'This will assign the domain removal task to you. Are you sure?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, assign to me!',
                cancelButtonText: 'Cancel'
            });

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Assigning Task...',
                text: 'Please wait while we assign the task to you.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            const response = await fetch(`{{ url('admin/taskInQueue') }}/${taskId}/assign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to assign task');
            }

            await Swal.fire({
                title: 'Success!',
                text: 'Task assigned successfully!',
                icon: 'success',
                confirmButtonColor: '#28a745'
            });

            // Refresh tasks
            refreshTasks();

        } catch (error) {
            console.error('Error assigning task:', error);
            await Swal.fire({
                title: 'Error!',
                text: error.message || 'Failed to assign task. Please try again.',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    }

    // Assign shifted task to current admin
    async function assignShiftedTaskToMe(taskId) {
        try {
            const result = await Swal.fire({
                title: 'Assign Panel Reassignment Task to Yourself?',
                text: 'This will assign the panel reassignment task to you. Are you sure?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, assign to me!',
                cancelButtonText: 'Cancel'
            });

            if (!result.isConfirmed) return;

            Swal.fire({
                title: 'Assigning Task...',
                text: 'Please wait while we assign the panel reassignment task to you.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => Swal.showLoading()
            });

            const response = await fetch(`{{ url('admin/taskInQueue/shifted') }}/${taskId}/assign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to assign panel reassignment task');
            }

            await Swal.fire({
                title: 'Success!',
                text: 'Panel reassignment task assigned successfully!',
                icon: 'success',
                confirmButtonColor: '#28a745'
            });

            // Refresh tasks
            refreshTasks();

        } catch (error) {
            console.error('Error assigning shifted task:', error);
            await Swal.fire({
                title: 'Error!',
                text: error.message || 'Failed to assign panel reassignment task. Please try again.',
                icon: 'error',
                confirmButtonColor: '#dc3545'
            });
        }
    }

    // Refresh tasks
    function refreshTasks() {
        tasks[activeTab] = [];
        pagination[activeTab] = { currentPage: 1, hasMore: false };
        loadTasks(activeTab);
    }

    // Reset filters
    function resetFilters() {
        document.getElementById('filterForm').reset();
        currentFilters = {};
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
                        <p class="mt-2 text-white">Loading task details...</p>
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
            
            // Fetch task details using admin route
            const response = await fetch(`{{ url('admin/taskInQueue') }}/${taskId}/details`, {
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
                        <h5 class="text-white">Error Loading Task Details</h5>
                        <p class="text-white-50">Failed to load task details. Please try again.</p>
                        <button class="btn btn-primary" onclick="viewTaskDetails(${taskId})">Retry</button>
                    </div>
                `;
            }
        }
    }

    // View shifted task details function
    async function viewShiftedTaskDetails(taskId) {
        try {
            // Show loading in shifted task offcanvas
            const container = document.getElementById('shiftedTaskDetailsContainer');
            if (container) {
                container.innerHTML = `
                    <div id="shiftedTaskLoadingState" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading panel reassignment details...</span>
                        </div>
                        <p class="mt-2 text-white">Loading panel reassignment details...</p>
                    </div>
                `;
            }
            
            // Show offcanvas with proper cleanup
            const offcanvasElement = document.getElementById('shifted-task-details-view');
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
                const offcanvasTitle = document.getElementById('shifted-task-details-viewLabel');
                if (offcanvasTitle) {
                    offcanvasTitle.innerHTML = 'Panel Reassignment Details';
                }
            }, { once: true });
            
            offcanvas.show();
            
            // Fetch shifted task details using admin route
            const response = await fetch(`{{ url('admin/taskInQueue/shifted') }}/${taskId}/details`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            
            if (!response.ok) throw new Error('Failed to fetch panel reassignment details');
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Failed to load panel reassignment details');
            }
            
            renderShiftedTaskDetails(data);
            
        } catch (error) {
            console.error('Error loading shifted task details:', error);
            const container = document.getElementById('shiftedTaskDetailsContainer');
            if (container) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle text-danger fs-3 mb-3"></i>
                        <h5 class="text-white">Error Loading Panel Reassignment Details</h5>
                        <p class="text-white-50">Failed to load panel reassignment details. Please try again.</p>
                        <button class="btn btn-primary" onclick="viewShiftedTaskDetails(${taskId})">Retry</button>
                    </div>
                `;
            }
        }
    }

    // Render task details in offcanvas
    function renderTaskDetails(data) {
        const container = document.getElementById('taskDetailsContainer');
        
        if (!data.splits || data.splits.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-inbox text-white fs-3 mb-3"></i>
                    <h5 class="text-white">No Order Data Found</h5>
                    <p class="text-white-50">This order doesn't have any data yet.</p>
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
                        <h6 class="text-white">${orderInfo.status_manage_by_admin}</h6>
                        <p class="text-white-50 small mb-0">Customer: ${orderInfo.customer_name} | Date: ${formatDate(orderInfo.created_at)}</p>
                    </div>
                </div>
            </div>
            
            <div class="table-responsive mb-4 card rounded-2 p-2" style="max-height: 20rem; overflow-y: auto">
                <table class="table table-striped table-hover position-sticky top-0 border-0">
                    <thead class="border-0">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Panel Break ID</th>
                            <th scope="col">Panel Id</th>
                            <th scope="col">Panel Title</th>
                            <th scope="col">Inboxes/Domain</th>
                            <th scope="col">Total Domains</th>
                            <th scope="col">Total Inboxes</th>
                            <th scope="col">Customized Type</th>
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
                                    ${split.email_count > 0 ? `
                                        <span class="badge bg-success" style="font-size: 10px;">
                                            <i class="fa-solid fa-check me-1"></i>Customized
                                        </span>
                                    ` : `
                                        <span class="badge bg-secondary" style="font-size: 10px;">
                                            <i class="fa-solid fa-cog me-1"></i>Default
                                        </span>
                                    `}
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="/admin/orders/${split.order_panel_id}/split/view" style="font-size: 10px" class="btn btn-sm btn-outline-primary me-2" title="View Split">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="/admin/orders/split/${split.id}/export-csv-domains" style="font-size: 10px" class="btn btn-sm btn-success" title="Download CSV with ${split.domains_count || 0} domains" target="_blank">
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

            <div class="row">
                <div class="col-md-5">
                    <div class="card p-3 mb-3 text-white">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-regular fa-envelope"></i>
                            </div>
                            Email configurations
                        </h6>

                        <div class="d-flex align-items-center justify-content-between">
                            <span style="font-size: 12px" class="text-white">${(() => {
                                const totalInboxes = splits.reduce((total, split) => total + (split.total_inboxes || 0), 0);
                                const totalDomains = splits.reduce((total, split) => total + (split.domains_count || 0), 0);
                                const inboxesPerDomain = reorderInfo?.inboxes_per_domain || 0;
                                
                                let splitDetails = '';
                                splits.forEach((split, index) => {
                                    splitDetails += `
                                        <br>
                                        <span class="bg-white text-dark me-1 py-1 px-2 rounded-1" style="font-size: 10px; font-weight: bold;">Panel Break ${String(index + 1).padStart(2, '0')}</span> 
                                            Inboxes: ${split.total_inboxes || 0} (${split.domains_count || 0} domains × ${inboxesPerDomain})<br>`;
                                });
                                
                                return `<strong>Total Inboxes: ${totalInboxes} (${totalDomains} domains)</strong><br>${splitDetails}`;
                            })()}</span>
                        </div>
                         
                        <hr>
                        <div class="d-flex flex-column">
                            <span class="opacity-50 small">Prefix Variants</span>
                            <small class="text-white">${renderPrefixVariants(reorderInfo)}</small>
                        </div>
                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50 small">Profile Picture URLS</span>
                         <small class="text-white">${renderProfileLinksFromObject(reorderInfo?.data_obj?.prefix_variants_details)}</small>
                        </div>
                       
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card p-3 overflow-y-auto text-white" style="max-height: 50rem">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-earth-europe"></i>
                            </div>
                            Domains &amp; Configuration
                        </h6>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Hosting Platform</span>
                            <small class="text-white">${reorderInfo?.hosting_platform || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Platform Login</span>
                            <small class="text-white">${reorderInfo?.platform_login || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Platform Password</span>
                            <small class="text-white">${reorderInfo?.platform_password || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Domain Forwarding Destination URL</span>
                            <small class="text-white">${reorderInfo?.forwarding_url || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Sending Platform</span>
                            <small class="text-white">${reorderInfo?.sending_platform || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Cold email platform - Login</span>
                            <small class="text-white">${reorderInfo?.sequencer_login || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Cold email platform - Password</span>
                            <small class="text-white">${reorderInfo?.sequencer_password || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column">
                            <h6 class="d-flex align-items-center gap-1">
                                <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                    <i class="fa-solid fa-globe"></i>
                                </div>
                               <span>All Domains & Panel Breaks</span>
                            </h6>
                            
                            <!-- Task Splits Domains -->
                            ${splits.map((split, index) => `
                                <div class="domain-split-container mb-3">
                                    <div class="split-header d-flex align-items-center justify-content-between p-2 rounded-top" 
                                         style="background: var(--filter-color); cursor: pointer; border: 1px solid var(--second-primary)"
                                         onclick="toggleSplit('split-${orderInfo.id}-${index}')">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-white text-dark me-2" style="font-size: 10px; font-weight: bold;">
                                                Panel Break ${String(index + 1).padStart(2, '0')}
                                            </span>
                                            <small class="text-white fw-bold">PNL-${split.panel_id} Domains</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-white bg-opacity-25 text-white me-2" style="font-size: 9px;">
                                                ${split.domains_count || 0} domains
                                            </span>
                                            ${reorderInfo?.master_inbox_email && reorderInfo.master_inbox_email !== '' ? `
                                                <i class="fa-solid fa-file-lines me-2" style="font-size: 10px; cursor: pointer;" 
                                                   title="Download text file for email forwarding (${String(index + 1).padStart(2, '0')}) - Format: individual@domain.com, master@domain.com" 
                                                   onclick="event.stopPropagation(); downloadTextFile(${split.id}, 'Split ${String(index + 1).padStart(2, '0')}')"></i>
                                                ` : ''}
                                            <i class="fa-solid fa-copy text-white me-2" style="font-size: 10px; cursor: pointer; opacity: 0.8;" 
                                               title="Copy all domains from Panel Break ${String(index + 1).padStart(2, '0')}" 
                                               onclick="event.stopPropagation(); copyAllDomainsFromSplit('split-${orderInfo.id}-${index}', 'Panel Break ${String(index + 1).padStart(2, '0')}')"></i>
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
                            <span class="text-white">${reorderInfo?.backup_codes || 'N/A'}</span>

                            <span class="opacity-50">Additional Notes</span>
                            <span class="text-white">${reorderInfo?.additional_info || 'N/A'}</span>
                            <span class="opacity-50">Master Inbox Email</span>
                            <span class="text-white">${reorderInfo?.master_inbox_email || 'N/A'}</span>
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

    // Render shifted task details in offcanvas
    function renderShiftedTaskDetails(data) {
        const container = document.getElementById('shiftedTaskDetailsContainer');
        
        if (!data.splits || data.splits.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-exchange-alt text-white fs-3 mb-3"></i>
                    <h5 class="text-white">No Order Data Found</h5>
                    <p class="text-white-50">This panel reassignment task doesn't have any order data yet.</p>
                </div>
            `;
            return;
        }
        
        const task = data.task;
        const orderInfo = data.order;
        const reorderInfo = data.reorder_info;
        const splits = data.splits;
        const fromPanel = data.from_panel;
        const toPanel = data.to_panel;

        // Update offcanvas title
        const offcanvasTitle = document.getElementById('shifted-task-details-viewLabel');
        if (offcanvasTitle && orderInfo) {
            offcanvasTitle.innerHTML = `Panel Migration - Order #${orderInfo.id}`;
        }

        const detailsHtml = `
            <!-- Panel Migration Header -->
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white">Panel Migration Task #${task.task_id || task.id}</h6>
                        <p class="text-white-50 small mb-0">
                            Customer: ${orderInfo.customer_name} | 
                            Created: ${formatDate(orderInfo.created_at)} |
                            Action: <span class="badge ${task.action_type === 'added' ? 'bg-success' : 'bg-danger'}">${task.action_type === 'added' ? 'Space Assignment' : 'Space Removal'}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Panel Movement Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card p-3 text-white">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-exchange-alt"></i>
                            </div>
                            Panel Migration Details
                        </h6>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <div class="p-3 rounded-3 border-0" 
                                    style="background: linear-gradient(135deg, rgba(220, 53, 69, 0.12) 0%, rgba(220, 53, 69, 0.08) 100%); border-left: 4px solid #dc3545 !important; border: 1px solid rgba(220, 53, 69, 0.2);">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-minus-circle text-danger me-2"></i>
                                        <span class="fw-bold text-white">From Panel</span>
                                    </div>
                                    <div class="ms-4">
                                        <p class="mb-1 text-white"><strong>Panel ID:</strong> ${fromPanel?.id || 'N/A'}</p>
                                        <p class="mb-0 text-white"><strong>Title:</strong> ${fromPanel?.title || 'N/A'}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 rounded-3 border-0" 
                                    style="background: linear-gradient(135deg, rgba(40, 167, 69, 0.12) 0%, rgba(40, 167, 69, 0.08) 100%); border-left: 4px solid #28a745 !important; border: 1px solid rgba(40, 167, 69, 0.2);">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-plus-circle text-success me-2"></i>
                                        <span class="fw-bold text-white">To Panel</span>
                                    </div>
                                    <div class="ms-4">
                                        <p class="mb-1 text-white"><strong>Panel ID:</strong> ${toPanel?.id || 'N/A'}</p>
                                        <p class="mb-0 text-white"><strong>Title:</strong> ${toPanel?.title || 'N/A'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column">
                            <span class="opacity-50 small">Space ${task.action_type === 'added' ? 'Transferred' : 'Deleted'}</span>
                            <span class="text-white fw-bold fs-5">${task.space_transferred || 0}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3 text-white">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-tasks"></i>
                            </div>
                            Task Status
                        </h6>
                        
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center p-2 rounded-3" style="background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.2);">
                                    <i class="fas fa-calendar-plus text-primary fs-5 mb-1"></i>
                                    <p class="mb-0 text-white-50 small">Created</p>
                                    <p class="mb-0 text-white fw-bold small">${formatDate(task.created_at)}</p>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-2 rounded-3" style="background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.2);">
                                    <i class="fas fa-check-circle ${task.status === 'completed' ? 'text-success' : 'text-warning'} fs-5 mb-1"></i>
                                    <p class="mb-0 text-white-50 small">Status</p>
                                    <p class="mb-0 text-white fw-bold small">${task.status.charAt(0).toUpperCase() + task.status.slice(1).replace('-', ' ')}</p>
                                </div>
                            </div>
                        </div>

                        ${task.assigned_to_name ? `
                            <div class="mt-3 p-2 rounded-3" style="background: rgba(102, 126, 234, 0.1); border: 1px solid rgba(102, 126, 234, 0.2);">
                                <span class="opacity-50 small d-block">Assigned To</span>
                                <span class="text-white fw-bold">${task.assigned_to_name}</span>
                            </div>
                        ` : ''}
                    </div>
                </div>
            </div>

            <!-- Order Splits Table -->
            <div class="table-responsive mb-4 card rounded-2 p-2" style="max-height: 20rem; overflow-y: auto">
                <table class="table table-striped table-hover position-sticky top-0 border-0">
                    <thead class="border-0">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Panel Break Id</th>
                            <th scope="col">Panel Id</th>
                            <th scope="col">Panel Title</th>
                            <th scope="col">Inboxes/Domain</th>
                            <th scope="col">Total Domains</th>
                            <th scope="col">Total Inboxes</th>
                            <th scope="col">Customized Type</th>
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
                                    ${split.email_count > 0 ? `
                                        <span class="badge bg-success" style="font-size: 10px;">
                                            <i class="fa-solid fa-check me-1"></i>Customized
                                        </span>
                                    ` : `
                                        <span class="badge bg-secondary" style="font-size: 10px;">
                                            <i class="fa-solid fa-cog me-1"></i>Default
                                        </span>
                                    `}
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="/admin/orders/${split.order_panel_id}/split/view" style="font-size: 10px" class="btn btn-sm btn-outline-primary me-2" title="View Split">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="/admin/orders/split/${split.id}/export-csv-domains" style="font-size: 10px" class="btn btn-sm btn-success" title="Download CSV with ${split.domains_count || 0} domains" target="_blank">
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

            <div class="row">
                <div class="col-md-5">
                    <div class="card p-3 mb-3 text-white">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-regular fa-envelope"></i>
                            </div>
                            Email configurations
                        </h6>

                        <div class="d-flex align-items-center justify-content-between">
                            <span style="font-size: 12px" class="text-white">${(() => {
                                const totalInboxes = splits.reduce((total, split) => total + (split.total_inboxes || 0), 0);
                                const totalDomains = splits.reduce((total, split) => total + (split.domains_count || 0), 0);
                                const inboxesPerDomain = reorderInfo?.inboxes_per_domain || 0;
                                
                                let splitDetails = '';
                                splits.forEach((split, index) => {
                                    splitDetails += `
                                        <br>
                                        <span class="bg-white text-dark me-1 py-1 px-2 rounded-1" style="font-size: 10px; font-weight: bold;">Panel Break ${String(index + 1).padStart(2, '0')}</span> 
                                            Inboxes: ${split.total_inboxes || 0} (${split.domains_count || 0} domains × ${inboxesPerDomain})<br>`;
                                });
                                
                                return `<strong>Total Inboxes: ${totalInboxes} (${totalDomains} domains)</strong><br>${splitDetails}`;
                            })()}</span>
                        </div>
                         
                        <hr>
                        <div class="d-flex flex-column">
                            <span class="opacity-50 small">Prefix Variants</span>
                            <small class="text-white">${renderPrefixVariants(reorderInfo)}</small>
                        </div>
                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50 small">Profile Picture URLS</span>
                         <small class="text-white">${renderProfileLinksFromObject(reorderInfo?.data_obj?.prefix_variants_details)}</small>
                        </div>
                       
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card p-3 overflow-y-auto text-white" style="max-height: 50rem">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-earth-europe"></i>
                            </div>
                            Domains &amp; Configuration
                        </h6>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Hosting Platform</span>
                            <small class="text-white">${reorderInfo?.hosting_platform || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Platform Login</span>
                            <small class="text-white">${reorderInfo?.platform_login || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Platform Password</span>
                            <small class="text-white">${reorderInfo?.platform_password || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Domain Forwarding Destination URL</span>
                            <small class="text-white">${reorderInfo?.forwarding_url || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Sending Platform</span>
                            <small class="text-white">${reorderInfo?.sending_platform || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Cold email platform - Login</span>
                            <small class="text-white">${reorderInfo?.sequencer_login || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50 small">Cold email platform - Password</span>
                            <small class="text-white">${reorderInfo?.sequencer_password || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column">
                            <h6 class="d-flex align-items-center gap-1">
                                <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                    <i class="fa-solid fa-globe"></i>
                                </div>
                               <span>All Domains & Panel Breaks</span>
                            </h6>
                            
                            <!-- Task Splits Domains -->
                            ${splits.map((split, index) => `
                                <div class="domain-split-container mb-3">
                                    <div class="split-header d-flex align-items-center justify-content-between p-2 rounded-top" 
                                         style="background: var(--filter-color); cursor: pointer; border: 1px solid var(--second-primary)"
                                         onclick="toggleSplit('split-${orderInfo.id}-${index}')">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-white text-dark me-2" style="font-size: 10px; font-weight: bold;">
                                                Panel Break ${String(index + 1).padStart(2, '0')}
                                            </span>
                                            <small class="text-white fw-bold">PNL-${split.panel_id} Domains</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-white bg-opacity-25 text-white me-2" style="font-size: 9px;">
                                                ${split.domains_count || 0} domains
                                            </span>
                                            ${reorderInfo?.master_inbox_email && reorderInfo.master_inbox_email !== '' ? `
                                                <i class="fa-solid fa-file-lines me-2" style="font-size: 10px; cursor: pointer;" 
                                                   title="Download text file for email forwarding (${String(index + 1).padStart(2, '0')}) - Format: individual@domain.com, master@domain.com" 
                                                   onclick="event.stopPropagation(); downloadTextFile(${split.id}, 'Split ${String(index + 1).padStart(2, '0')}')"></i>
                                                ` : ''}
                                            <i class="fa-solid fa-copy text-white me-2" style="font-size: 10px; cursor: pointer; opacity: 0.8;" 
                                               title="Copy all domains from Panel Break ${String(index + 1).padStart(2, '0')}" 
                                               onclick="event.stopPropagation(); copyAllDomainsFromSplit('split-${orderInfo.id}-${index}', 'Panel Break ${String(index + 1).padStart(2, '0')}')"></i>
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
                            <span class="text-white">${reorderInfo?.backup_codes || 'N/A'}</span>

                            <span class="opacity-50">Additional Notes</span>
                            <span class="text-white">${reorderInfo?.additional_info || 'N/A'}</span>
                            <span class="opacity-50">Master Inbox Email</span>
                            <span class="text-white">${reorderInfo?.master_inbox_email || 'N/A'}</span>
                        </div>
                    </div>
                </div>
            </div>

            ${task.status === 'pending' ? `
                <div class="mt-3 text-center">
                    <button class="btn btn-primary" onclick="assignShiftedTaskToMe(${task.task_id || task.id})">
                        <i class="fas fa-user-plus me-2"></i>
                        Assign This Panel Migration Task to Me
                    </button>
                </div>
            ` : ''}
        `;
        
        container.innerHTML = detailsHtml;
        
        // Initialize chevron states and animations after rendering
        setTimeout(function() {
            initializeChevronStates();
        }, 100);
    }

    // Helper functions for task details canvas
    function renderPrefixVariants(reorderInfo) {
        if (!reorderInfo) return 'N/A';
        
        let variants = [];
        if (reorderInfo.prefix_variant_1) variants.push(reorderInfo.prefix_variant_1);
        if (reorderInfo.prefix_variant_2) variants.push(reorderInfo.prefix_variant_2);
        
        return variants.length > 0 ? variants.join(', ') : 'N/A';
    }

    function renderProfileLinksFromObject(prefixVariantsDetails) {
        if (!prefixVariantsDetails || typeof prefixVariantsDetails !== 'object') {
            return 'N/A';
        }
        
        let links = [];
        Object.entries(prefixVariantsDetails).forEach(([key, value]) => {
            if (value && typeof value === 'object' && value.profile_picture_url) {
                links.push(`<a href="${value.profile_picture_url}" target="_blank" class="text-info">${key}: ${value.profile_picture_url}</a>`);
            }
        });
        
        return links.length > 0 ? links.join('<br>') : 'N/A';
    }

    function renderDomainsWithStyle(splits) {
        let allDomains = [];
        
        splits.forEach(split => {
            if (split.domains) {
                if (Array.isArray(split.domains)) {
                    allDomains = allDomains.concat(split.domains);
                } else if (typeof split.domains === 'object' && split.domains !== null) {
                    const domainValues = Object.values(split.domains).filter(d => d && typeof d === 'string');
                    allDomains = allDomains.concat(domainValues);
            }
        }
    });

    // Assign pool panel reassignment task to current admin
    async function assignPoolPanelReassignmentTaskToMe(taskId) {
        try {
            const result = await Swal.fire({
                title: 'Assign Pool Panel Reassignment Task?',
                text: 'Do you want to assign this pool panel reassignment task to yourself?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, assign to me!',
                cancelButtonText: 'Cancel',
            });

            if (!result.isConfirmed) return;

            const response = await fetch(`/admin/taskInQueue/pool-panel-reassignment/${taskId}/assign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            });

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to assign pool panel reassignment task');
            }

            await Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false,
            });

            tasks['pool-panel-reassignment'] = [];
            pagination['pool-panel-reassignment'] = { currentPage: 1, hasMore: false };
            loadPoolPanelReassignmentTasks();
        } catch (error) {
            console.error('Error assigning pool panel reassignment task:', error);
            Swal.fire({
                title: 'Error!',
                text: error.message || 'Failed to assign pool panel reassignment task',
                icon: 'error',
            });
        }
    }

    // View pool panel reassignment task details
    async function viewPoolPanelReassignmentTaskDetails(taskId) {
        try {
            const container = document.getElementById('poolPanelTaskDetailsContainer');
            if (container) {
                container.innerHTML = `
                    <div id="poolPanelTaskLoadingState" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading pool panel reassignment details...</span>
                        </div>
                        <p class="mt-2 text-white">Loading pool panel reassignment details...</p>
                    </div>
                `;
            }

            const offcanvasElement = document.getElementById('pool-panel-task-details-view');
            const offcanvas = new bootstrap.Offcanvas(offcanvasElement);

            offcanvasElement.addEventListener('hidden.bs.offcanvas', function () {
                const backdrops = document.querySelectorAll('.offcanvas-backdrop, .modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());

                document.body.classList.remove('offcanvas-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, { once: true });

            offcanvas.show();

            const response = await fetch(`/admin/taskInQueue/pool-panel-reassignment/${taskId}/details`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            if (!response.ok) throw new Error('Failed to fetch pool panel reassignment task details');

            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Failed to load pool panel reassignment task details');
            }

            renderPoolPanelReassignmentDetails(data);
        } catch (error) {
            console.error('Error loading pool panel reassignment task details:', error);
            const container = document.getElementById('poolPanelTaskDetailsContainer');
            if (container) {
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-exclamation-triangle text-danger fs-3 mb-3"></i>
                        <h5 class="text-white">Error Loading Pool Panel Reassignment Details</h5>
                        <p class="text-white-50">Failed to load pool panel reassignment details. Please try again.</p>
                        <button class="btn btn-primary" onclick="viewPoolPanelReassignmentTaskDetails(${taskId})">Retry</button>
                    </div>
                `;
            }
        }
    }

    // Render pool panel reassignment task details
    function renderPoolPanelReassignmentDetails(data) {
        const container = document.getElementById('poolPanelTaskDetailsContainer');
        const task = data.task;
        const pool = data.pool;
        const fromPanel = data.from_panel;
        const toPanel = data.to_panel;

        const statusBadge = getStatusClass(task.status);

        const poolTitle = pool && pool.id ? `Pool #${pool.id}` : 'Pool Details';
        const offcanvasTitle = document.getElementById('pool-panel-task-details-viewLabel');
        if (offcanvasTitle) {
            offcanvasTitle.innerHTML = poolTitle;
        }

        container.innerHTML = `
            <div class="mb-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="text-white">Pool Panel Reassignment Task #${task.task_id}</h6>
                        <p class="text-white-50 small mb-0">Status: <span class="badge ${statusBadge}">${task.status}</span></p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-5">
                    <div class="card p-3 mb-3 text-white">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-sitemap"></i>
                            </div>
                            Pool Details
                        </h6>
                        <div class="d-flex flex-column mt-2">
                            <span class="opacity-50 small">Pool ID</span>
                            <small class="text-white">${pool?.id || 'N/A'}</small>
                        </div>
                        <div class="d-flex flex-column mt-2">
                            <span class="opacity-50 small">Domain URL</span>
                            <small class="text-white">${pool?.domain_url || 'N/A'}</small>
                        </div>
                        <div class="d-flex flex-column mt-2">
                            <span class="opacity-50 small">Total Inboxes</span>
                            <small class="text-white">${pool?.total_inboxes ?? 'N/A'}</small>
                        </div>
                        <div class="d-flex flex-column mt-2">
                            <span class="opacity-50 small">Status</span>
                            <small class="text-white">${pool?.status || 'N/A'}</small>
                        </div>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card p-3 mb-3 text-white">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center" style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-exchange-alt"></i>
                            </div>
                            Reassignment Details
                        </h6>

                        <div class="row g-2 mt-2">
                            <div class="col-md-6">
                                <div class="glass-box">
                                    <small class="text-white-50 d-block mb-1">From Pool Panel</small>
                                    <span class="fw-semibold text-white">${fromPanel ? (fromPanel.auto_generated_id || fromPanel.title || 'N/A') : 'N/A'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="glass-box">
                                    <small class="text-white-50 d-block mb-1">To Pool Panel</small>
                                    <span class="fw-semibold text-white">${toPanel ? (toPanel.auto_generated_id || toPanel.title || 'N/A') : 'N/A'}</span>
                                </div>
                            </div>
                        </div>

                        <div class="row g-2 mt-3">
                            <div class="col-md-6">
                                <div class="glass-box">
                                    <small class="text-white-50 d-block mb-1">Space Transferred</small>
                                    <span class="fw-semibold text-white">${task.space_transferred || 0}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="glass-box">
                                    <small class="text-white-50 d-block mb-1">Splits</small>
                                    <span class="fw-semibold text-white">${task.splits_count || 0}</span>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50 small">Reason</span>
                            <small class="text-white">${task.reason || 'N/A'}</small>
                        </div>

                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50 small">Notes</span>
                            <small class="text-white">${task.notes || 'N/A'}</small>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
        
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
        navigator.clipboard.writeText(text).then(function() {
            // Show toast notification if available
            if (typeof toastr !== 'undefined') {
                toastr.success(`Copied: ${text}`, 'Clipboard', {
                    timeOut: 2000,
                    closeButton: true
                });
            }
        }).catch(function(err) {
            console.error('Failed to copy text: ', err);
        });
    }
    // Download text file for a split
    function downloadTextFile(splitId, splitLabel) {
        // Create the URL for text file download
        const downloadUrl = `/admin/orders/split/${splitId}/export-txt-domains`;
        
        // Create a temporary anchor element to trigger download
        const link = document.createElement('a');
        link.href = downloadUrl;
        link.target = '_blank';
        link.download = ''; // Let the server decide the filename
        
        // Trigger the download
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        // Show success message
        console.log(`Text file download initiated for ${splitLabel}`);
    }
    // Function to copy all domains from a split
    function copyAllDomainsFromSplit(splitId, splitName) {
        const splitContent = document.getElementById(splitId);
        if (splitContent) {
            const domainElements = splitContent.querySelectorAll('.domain-badge');
            const domains = Array.from(domainElements).map(el => el.textContent.replace(/^.*\s/, '').trim());
            
            if (domains.length > 0) {
                const domainsText = domains.join('\n');
                navigator.clipboard.writeText(domainsText).then(function() {
                    if (typeof toastr !== 'undefined') {
                        toastr.success(`Copied ${domains.length} domains from ${splitName}`, 'Clipboard', {
                            timeOut: 3000,
                            closeButton: true
                        });
                    }
                }).catch(function(err) {
                    console.error('Failed to copy domains: ', err);
                });
            }
        }
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

    // Laravel Echo WebSocket Implementation for Real-time Task Updates
    document.addEventListener('DOMContentLoaded', function() {
        // Check if Echo is available (consistent check using window.Echo)
        if (typeof window.Echo !== 'undefined') {
            console.log('🔌 Laravel Echo initialized successfully for Task Queue', window.Echo);
            console.log('🔍 Echo connector details:', window.Echo.connector);
            
            // Test connection status first
            if (window.Echo.connector && window.Echo.connector.pusher) {
                console.log('📡 Pusher connection state:', window.Echo.connector.pusher.connection.state);
            }
            
            // Listen to the 'domain-removal-tasks' channel for real-time task updates
            const tasksChannel = window.Echo.channel('domain-removal-tasks');
            console.log('🎯 Subscribed to domain-removal-tasks channel:', tasksChannel);
            
            tasksChannel
                .listen('.task.started', (e) => {
                    console.log('🚀 Task Started/Created Event:', e);
                    
                    const task = e.task || e;
                    const startedQueueDate = task.started_queue_date;
                    
                    console.log('Task:', task);
                    console.log('Started Queue Date:', startedQueueDate);
                    
                    // Always process task events, regardless of queue date
                    // This handles scenarios where tasks are created with future dates (+1 month +72 hours)
                    if (startedQueueDate) {
                        const startedDate = new Date(startedQueueDate);
                        const formattedDate = startedDate.toLocaleDateString();
                        
                        console.log('✅ Processing task event - task created with queue date:', formattedDate);
                        
                        // Show notification for task created
                        if (typeof toastr !== 'undefined') {
                            toastr.success(
                                `New Task #${task.task_id || task.id} created and scheduled for ${formattedDate}!`, 
                                'New Task Created', 
                                {
                                    timeOut: 5000,
                                    closeButton: true,
                                    progressBar: true,
                                    onclick: function() {
                                        // Focus on pending tab and refresh
                                        if (activeTab !== 'pending') {
                                            document.getElementById('pending-tab').click();
                                        }
                                        refreshTasks();
                                    }
                                }
                            );
                        }
                        
                        // Refresh all tabs since new task should appear
                        setTimeout(() => {
                            refreshAllTabs();
                        }, 1000);
                    } else {
                        console.log('⚠️ No started_queue_date found in task data');
                    }
                })
                .error((error) => {
                    console.error('❌ Channel subscription error:', error);
                });
            
            // Connection status monitoring using window.Echo
            if (window.Echo.connector && window.Echo.connector.pusher) {
                window.Echo.connector.pusher.connection.bind('connected', () => {
                    console.log('✅ WebSocket connected successfully for Task Queue');
                    
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Real-time task updates connected!', 'WebSocket Connected', {
                            timeOut: 2000,
                            closeButton: true
                        });
                    }
                });
                
                window.Echo.connector.pusher.connection.bind('disconnected', () => {
                    console.log('❌ WebSocket disconnected');
                    
                    // Show reconnection status
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Real-time task updates disconnected. Trying to reconnect...', 'Connection Lost', {
                            timeOut: 3000,
                            closeButton: true
                        });
                    }
                });
                
                window.Echo.connector.pusher.connection.bind('reconnected', () => {
                    console.log('🔄 WebSocket reconnected for Task Queue');
                    
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Real-time task updates reconnected!', 'Connection Restored', {
                            timeOut: 2000,
                            closeButton: true
                        });
                    }
                    
                    // Refresh all tabs when reconnected
                    setTimeout(() => {
                        refreshAllTabs();
                    }, 1000);
                });
                
                // Additional connection state monitoring
                window.Echo.connector.pusher.connection.bind('state_change', (states) => {
                    console.log(`🔄 Task Queue connection state changed from ${states.previous} to ${states.current}`);
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
            
            console.log('✅ Listening to task queue events on channel: domain-removal-tasks');
            console.log('🎯 Only processing events where started_queue_date >= today');
            
        } else {
            console.warn('⚠️ Laravel Echo not available. Real-time task updates disabled.');
            
            // Optional: Show warning that real-time updates are not available
            setTimeout(() => {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Real-time task updates are not available. Data will be updated on page refresh.', 'WebSocket Unavailable', {
                        timeOut: 5000,
                        closeButton: true
                    });
                }
            }, 2000);
        }
    });

    // Helper function to refresh all tabs
    function refreshAllTabs() {
        console.log('🔄 Refreshing all task tabs...');
        
        // Clear all task data
        tasks.pending = [];
        tasks['shifted-pending'] = [];
        tasks['in-progress'] = [];
        tasks.completed = [];
        
        // Reset pagination
        pagination.pending = { currentPage: 1, hasMore: false };
        pagination['shifted-pending'] = { currentPage: 1, hasMore: false };
        pagination['in-progress'] = { currentPage: 1, hasMore: false };
        pagination.completed = { currentPage: 1, hasMore: false };
        
        // Refresh current active tab
        loadTasks(activeTab);
        
        // Update tab counts
        updateTabCounts();
    }

    // Alternative implementation if you need to access Echo outside of DOMContentLoaded
    function initializeTaskWebSocket() {
        if (typeof window.Echo !== 'undefined') {
            console.log('🔌 Initializing Laravel Echo for real-time task updates...', window.Echo);
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
</script>
@endpush
