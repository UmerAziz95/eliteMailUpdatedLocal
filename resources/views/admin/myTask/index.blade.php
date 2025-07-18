@extends('admin.layouts.app')

@section('title', 'My-Task')
@push('styles')
<style>
    .nav-link {
        color: #fff;
        font-size: 13px
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

            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-all-tasks-tab" data-bs-toggle="pill" data-bs-target="#pills-all-tasks"
                    type="button" role="tab" aria-controls="pills-all-tasks" aria-selected="false">All Tasks</button>
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
        </div>
    </section>
@endsection

@push('scripts')
<script>
    let tasks = {
        'my-tasks': [],
        'all-tasks': []
    };
    let pagination = {
        'my-tasks': { currentPage: 1, hasMore: false },
        'all-tasks': { currentPage: 1, hasMore: false }
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
    });

    // Load tasks function
    async function loadTasks(type, append = false) {
        if (isLoading) return;
        
        isLoading = true;
        const containerId = type === 'my-tasks' ? 'my-tasks-container' : 'all-tasks-container';
        const container = document.getElementById(containerId);
        
        try {
            if (!append) {
                container.innerHTML = `
                    <div class="loading-state text-center" style="grid-column: 1 / -1;">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading ${type === 'my-tasks' ? 'my' : 'all'} tasks...</p>
                    </div>
                `;
            }

            const params = new URLSearchParams({
                type: type,
                page: append ? pagination[type].currentPage + 1 : 1,
                per_page: 12
            });

            const response = await fetch(`{{ route('admin.myTask.data') }}?${params}`);
            
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
        const containerId = type === 'my-tasks' ? 'my-tasks-container' : 'all-tasks-container';
        const container = document.getElementById(containerId);
        const tasksList = tasks[type];
        
        if (tasksList.length === 0 && !append) {
            container.innerHTML = `
                <div class="empty-state text-center" style="grid-column: 1 / -1;">
                    <i class="fas fa-tasks fa-3x text-muted mb-3"></i>
                    <h5>No ${type === 'my-tasks' ? 'My' : 'All'} Tasks Found</h5>
                    <p>There are no ${type === 'my-tasks' ? 'tasks assigned to you' : 'tasks'} to display.</p>
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
        
        div.innerHTML = `
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="fw-semibold">#${task.task_id}</span>
                <span class="badge ${statusClass} fw-semibold">${task.status.charAt(0).toUpperCase() + task.status.slice(1).replace('-', ' ')}</span>
            </div>

            <!-- Stats -->
            <div class="row mb-3">
                <div class="col-6">
                    <p class="mb-1 small">Total Inboxes</p>
                    <h4 class="fw-bold mb-0">${task.total_inboxes || 0}</h4>
                </div>
                <div class="col-6 text-end">
                    <p class="mb-1 small">Splits</p>
                    <h4 class="fw-bold mb-0">${task.splits_count || 0}</h4>
                </div>
            </div>

            <!-- Inboxes & Domains -->
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

            <!-- Footer with User -->
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div>
                        <img src="${task.customer_image || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(task.customer_name || 'User') + '&background=007bff&color=fff'}" 
                             alt="User" style="border-radius: 50px" width="40" height="40">
                    </div>
                    <div>
                        <p class="mb-0 fw-semibold">${task.customer_name || 'N/A'}</p>
                        <small>${formatDate(task.started_queue_date)}</small>
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary btn-sm d-flex align-items-center justify-content-center">
                        <i class="fas fa-arrow-right text-white"></i>
                    </button>
                </div>
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
        const containerId = type === 'my-tasks' ? 'my-tasks-container' : 'all-tasks-container';
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
</script>
@endpush
