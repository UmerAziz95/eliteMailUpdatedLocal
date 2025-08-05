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
        'in-progress': [],
        completed: []
    };
    let pagination = {
        pending: { currentPage: 1, hasMore: false },
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
    });

    // Load tasks function
    async function loadTasks(status, append = false, useFilters = false) {
        if (isLoading) return;
        
        isLoading = true;
        const container = document.getElementById(`${status === 'in-progress' ? 'in-progress' : status}-tasks-container`);
        const loadMoreBtn = document.getElementById(`load-more-${status === 'in-progress' ? 'progress' : status}`);
        
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

    // Render tasks function
    function renderTasks(status, append = false) {
        const container = document.getElementById(`${status === 'in-progress' ? 'in-progress' : status}-tasks-container`);
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
                            style="background: linear-gradient(145deg, #3f3f62, #1d2239); box-shadow: 0 0 10px #0077ff;">
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
                <div class="ms-3">
                    <p class="mb-0 fw-semibold text-white">${task.customer_name || 'N/A'}</p>
                    <small class="text-white-50">
                        ${task.order_id ? `Order #${task.order_id}` : 'No Order'}
                        ${task.assigned_to_name ? ` • ${task.assigned_to_name}` : ''}
                    </small>
                </div>
            </div>
        `;
        
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
        // For demonstration, let's fetch actual counts
        fetch(`{{ route('admin.taskInQueue.data') }}?type=pending&per_page=1000`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('pending-count').textContent = data.pagination.total;
                }
            });
            
        fetch(`{{ route('admin.taskInQueue.data') }}?type=in-progress&per_page=1000`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('progress-count').textContent = data.pagination.total;
                }
            });
            
        fetch(`{{ route('admin.taskInQueue.data') }}?type=completed&per_page=1000`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('completed-count').textContent = data.pagination.total;
                }
            });
    }

    function updateLoadMoreButton(status) {
        const btn = document.getElementById(`load-more-${status === 'in-progress' ? 'progress' : status}`);
        if (pagination[status].hasMore) {
            btn.classList.remove('d-none');
        } else {
            btn.classList.add('d-none');
        }
    }

    function showError(message, status) {
        const container = document.getElementById(`${status === 'in-progress' ? 'in-progress' : status}-tasks-container`);
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
        tasks['in-progress'] = [];
        tasks.completed = [];
        
        // Reset pagination
        pagination.pending = { currentPage: 1, hasMore: false };
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
