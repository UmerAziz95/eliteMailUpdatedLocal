@extends('admin.layouts.app')

@section('title', 'Pools')

@push('styles')
    <style>
        .status-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-weight: 500;
        }

        .badge-pending {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        .badge-in_progress {
            background-color: rgba(13, 202, 240, 0.2);
            color: #0dcaf0;
            border: 1px solid #0dcaf0;
        }

        .badge-completed {
            background-color: rgba(25, 135, 84, 0.2);
            color: #198754;
            border: 1px solid #198754;
        }

        .badge-cancelled {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        /* .table-responsive {
                                            border-radius: 12px;
                                            overflow: hidden;
                                            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                                        } */


        /* .action-buttons {
                                            display: flex;
                                            gap: 0.25rem;
                                            justify-content: center;
                                        }

                                        .action-buttons .btn {
                                            padding: 0.375rem 0.5rem;
                                            font-size: 0.75rem;
                                        } */

        .badge-type {
            font-size: 0.625rem;
            padding: 0.25rem 0.375rem;
            margin-right: 0.25rem;
        }

        /* DataTable custom styling to match theme */
        .dataTables_wrapper .dataTables_filter {
            float: right;
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            margin-left: 0.5rem;
        }

        .dataTables_wrapper .dataTables_length {
            float: left;
            margin-bottom: 1rem;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            margin: 0 0.5rem;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 1rem;
            color: var(--bs-gray-600);
        }

        .dataTables_wrapper .dataTables_paginate {
            padding-top: 1rem;
            float: right;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.375rem 0.75rem;
            margin: 0 0.125rem;
            border: 1px solid var(--bs-border-color);
            border-radius: 0.375rem;
            color: var(--bs-body-color);
            text-decoration: none;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background-color: var(--bs-primary);
            color: white;
            border-color: var(--bs-primary);
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background-color: var(--bs-primary);
            color: white;
            border-color: var(--bs-primary);
        }
    </style>
@endpush

@section('content')
    <section class="py-3">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">Pools Management</h2>
                <p class="mb-0">Manage and track all your pools</p>
            </div>
            <a href="{{ route('admin.pools.create') }}" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Create New Pool
            </a>
        </div>

        <!-- Bulk Action Bar (hidden until items selected) -->
        <div id="bulkActionBar" class="alert alert-info d-none align-items-center justify-content-between mb-3">
            <div>
                <i class="fa fa-check-circle me-2"></i>
                <span id="selectedCount">0</span> email account(s) selected
            </div>
            <div>
                <button type="button" class="btn btn-success btn-sm me-2" onclick="openBulkUpdateModal('extend')">
                    <i class="fa fa-plus me-1"></i>Extend Days
                </button>
                <button type="button" class="btn btn-warning btn-sm me-2" onclick="openBulkUpdateModal('reduce')">
                    <i class="fa fa-minus me-1"></i>Reduce Days
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearAllSelections()">
                    <i class="fa fa-times me-1"></i>Clear Selection
                </button>
            </div>
        </div>

        <!-- Tab Navigation -->
        <ul class="nav nav-pills mb-3 border-0" id="poolsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white active"
                    id="google-pool-tab" data-bs-toggle="tab" data-bs-target="#google-pool-tab-pane" type="button"
                    role="tab" aria-controls="google-pool-tab-pane" aria-selected="true">
                    <i class="fa-brands fa-google me-1"></i>Google Pool
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white"
                    id="ms365-pool-tab" data-bs-toggle="tab" data-bs-target="#ms365-pool-tab-pane" type="button" role="tab"
                    aria-controls="ms365-pool-tab-pane" aria-selected="false">
                    <i class="fa-brands fa-microsoft me-1"></i>365 Pool
                </button>
            </li>
        </ul>

        <div class="tab-content" id="poolsTabContent">
            <!-- Google Pool Tab -->
            <div class="tab-pane fade show active" id="google-pool-tab-pane" role="tabpanel"
                aria-labelledby="google-pool-tab" tabindex="0">

                <!-- Nested Tabs for Google Pool -->
                <ul class="nav nav-tabs mb-3" id="googleStatusTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="google-warming-tab" data-bs-toggle="tab"
                            data-bs-target="#google-warming-pane" type="button" role="tab">
                            <i class="fa fa-fire me-1 text-warning"></i>Warming
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="google-available-tab" data-bs-toggle="tab"
                            data-bs-target="#google-available-pane" type="button" role="tab">
                            <i class="fa fa-check-circle me-1 text-success"></i>Available
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="google-used-tab" data-bs-toggle="tab"
                            data-bs-target="#google-used-pane" type="button" role="tab">
                            <i class="fa fa-lock me-1 text-danger"></i>Used
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="googleStatusTabContent">
                    <!-- Google Warming -->
                    <div class="tab-pane fade show active" id="google-warming-pane" role="tabpanel">
                        <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                            <div class="table-responsive">
                                <table id="googleWarmingTable" class="table table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" class="select-all-visible" /></th>
                                            <th>Pool ID</th>
                                            <th>Created By</th>
                                            <th>Status</th>
                                            <th>Email Account</th>
                                            <th>Expiry Date</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Google Available -->
                    <div class="tab-pane fade" id="google-available-pane" role="tabpanel">
                        <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                            <div class="table-responsive">
                                <table id="googleAvailableTable" class="table table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" class="select-all-visible" /></th>
                                            <th>Pool ID</th>
                                            <th>Created By</th>
                                            <th>Status</th>
                                            <th>Email Account</th>
                                            <th>Expiry Date</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- Google Used -->
                    <div class="tab-pane fade" id="google-used-pane" role="tabpanel">
                        <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                            <div class="table-responsive">
                                <table id="googleUsedTable" class="table table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" class="select-all-visible" /></th>
                                            <th>Pool ID</th>
                                            <th>Created By</th>
                                            <th>Status</th>
                                            <th>Email Account</th>
                                            <th>Expiry Date</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 365 Pool Tab -->
            <div class="tab-pane fade" id="ms365-pool-tab-pane" role="tabpanel" aria-labelledby="ms365-pool-tab"
                tabindex="0">

                <!-- Nested Tabs for 365 Pool -->
                <ul class="nav nav-tabs mb-3" id="ms365StatusTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="ms365-warming-tab" data-bs-toggle="tab"
                            data-bs-target="#ms365-warming-pane" type="button" role="tab">
                            <i class="fa fa-fire me-1 text-warning"></i>Warming
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ms365-available-tab" data-bs-toggle="tab"
                            data-bs-target="#ms365-available-pane" type="button" role="tab">
                            <i class="fa fa-check-circle me-1 text-success"></i>Available
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="ms365-used-tab" data-bs-toggle="tab" data-bs-target="#ms365-used-pane"
                            type="button" role="tab">
                            <i class="fa fa-lock me-1 text-danger"></i>Used
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="ms365StatusTabContent">
                    <!-- 365 Warming -->
                    <div class="tab-pane fade show active" id="ms365-warming-pane" role="tabpanel">
                        <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                            <div class="table-responsive">
                                <table id="ms365WarmingTable" class="table table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" class="select-all-visible" /></th>
                                            <th>Pool ID</th>
                                            <th>Created By</th>
                                            <th>Status</th>
                                            <th>Email Account</th>
                                            <th>Expiry Date</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- 365 Available -->
                    <div class="tab-pane fade" id="ms365-available-pane" role="tabpanel">
                        <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                            <div class="table-responsive">
                                <table id="ms365AvailableTable" class="table table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" class="select-all-visible" /></th>
                                            <th>Pool ID</th>
                                            <th>Created By</th>
                                            <th>Status</th>
                                            <th>Email Account</th>
                                            <th>Expiry Date</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <!-- 365 Used -->
                    <div class="tab-pane fade" id="ms365-used-pane" role="tabpanel">
                        <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                            <div class="table-responsive">
                                <table id="ms365UsedTable" class="table table-hover w-100">
                                    <thead>
                                        <tr>
                                            <th><input type="checkbox" class="select-all-visible" /></th>
                                            <th>Pool ID</th>
                                            <th>Created By</th>
                                            <th>Status</th>
                                            <th>Email Account</th>
                                            <th>Expiry Date</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this pool? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bulk Update Days Modal -->
    <div class="modal fade" id="bulkUpdateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkUpdateModalTitle">Update Warmup Days</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="bulkUpdateAction" value="">
                    <div class="mb-3">
                        <label for="bulkDaysInput" class="form-label" id="bulkDaysLabel">Number of Days</label>
                        <input type="number" class="form-control" id="bulkDaysInput" min="1" value="30">
                    </div>
                    <p class="text-muted mb-0">
                        <i class="fa fa-info-circle me-1"></i>
                        This will update <span id="bulkUpdateCount">0</span> selected email account(s).
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmBulkUpdate">
                        <i class="fa fa-save me-1"></i>Update
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <script>
        let poolToDelete = null;
        let dataTables = {};
        // Set to track selected items across pagination (key: pool_id-domain_id-prefix_key)
        let selectedItems = new Map();

        $(document).ready(function () {
            // Initialize all 6 DataTables
            const tableConfigs = [
                { id: 'googleWarmingTable', provider: 'Google', status: 'warming', emptyIcon: 'fa-fire', emptyColor: 'text-warning' },
                { id: 'googleAvailableTable', provider: 'Google', status: 'available', emptyIcon: 'fa-check-circle', emptyColor: 'text-success' },
                { id: 'googleUsedTable', provider: 'Google', status: 'used', emptyIcon: 'fa-lock', emptyColor: 'text-danger' },
                { id: 'ms365WarmingTable', provider: 'Microsoft 365', status: 'warming', emptyIcon: 'fa-fire', emptyColor: 'text-warning' },
                { id: 'ms365AvailableTable', provider: 'Microsoft 365', status: 'available', emptyIcon: 'fa-check-circle', emptyColor: 'text-success' },
                { id: 'ms365UsedTable', provider: 'Microsoft 365', status: 'used', emptyIcon: 'fa-lock', emptyColor: 'text-danger' }
            ];

            tableConfigs.forEach(config => {
                dataTables[config.id] = $(`#${config.id}`).DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    dom: '<"top"f>rt<"bottom"lip><"clear">',
                    ajax: {
                        url: "{{ route('admin.pool-domains.index') }}",
                        data: function (d) {
                            d.datatable = true;
                            d.provider_type = config.provider;
                            d.status_filter = config.status;
                        }
                    },
                    columns: getTableColumns(),
                    order: [[1, 'desc']],
                    pageLength: 25,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    drawCallback: function () {
                        // Restore checkbox states after pagination
                        restoreCheckboxStates();
                    },
                    language: {
                        emptyTable: `
                                                        <div class="text-center py-4">
                                                            <i class="fa ${config.emptyIcon} fs-1 ${config.emptyColor} mb-3"></i>
                                                            <h5 class="">No ${config.status} email accounts found</h5>
                                                            <p class="">All ${config.status} email accounts will appear here.</p>
                                                        </div>
                                                    `
                    },
                    drawCallback: function () {
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    }
                });
            });

            // Handle tab change events - adjust columns when tab becomes visible
            $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                // Re-adjust visible tables
                setTimeout(() => {
                    Object.values(dataTables).forEach(table => {
                        if (table && table.columns) {
                            table.columns.adjust();
                        }
                    });
                }, 10);
            });
        });

        function getTableColumns() {
            return [
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function (data, type, row) {
                        const key = `${row.pool_id}-${row.domain_id}-${row.prefix_key}`;
                        const checked = selectedItems.has(key) ? 'checked' : '';
                        return `<input type="checkbox" class="row-checkbox" 
                                    data-pool-id="${row.pool_id}" 
                                    data-domain-id="${row.domain_id}" 
                                    data-prefix-key="${row.prefix_key}" 
                                    ${checked} />`;
                    }
                },
                {
                    data: 'pool_id',
                    name: 'pool_id',
                    render: function (data, type, row) {
                        return `
                                                        <a href="/admin/pools/${data}" class="text-primary text-decoration-none">
                                                            <i class="ti ti-hash fs-6 opacity-50"></i>
                                                            <span>${data}</span>
                                                        </a>
                                                    `;
                    }
                },
                {
                    data: 'created_by',
                    name: 'created_by',
                    render: function (data, type, row) {
                        return `
                                                        <div class="d-flex gap-1 align-items-center">
                                                            <i class="ti ti-user fs-6 opacity-50"></i>
                                                            <span>${data || 'N/A'}</span>
                                                        </div>
                                                    `;
                    }
                },
                {
                    data: 'status',
                    name: 'status',
                    render: function (data, type, row) {
                        let badgeClass = 'badge-secondary';
                        let icon = 'fa-circle';

                        if (data === 'warming') {
                            badgeClass = 'badge-pending';
                            icon = 'fa-fire';
                        } else if (data === 'available') {
                            badgeClass = 'badge-completed';
                            icon = 'fa-check-circle';
                        } else if (data === 'in-progress') {
                            badgeClass = 'badge-in_progress';
                            icon = 'fa-clock';
                        } else if (data === 'used') {
                            badgeClass = 'badge-danger';
                            icon = 'fa-lock';
                        }

                        return `
                                                        <span class="status-badge ${badgeClass}">
                                                            <i class="fa ${icon} me-1"></i>${data || 'Unknown'}
                                                        </span>
                                                    `;
                    }
                },
                {
                    data: null,
                    name: 'email_account',
                    render: function (data, type, row) {
                        const prefix = row.prefix_value || '';
                        const domain = row.domain_name || '';
                        const email = prefix && domain ? `${prefix}@${domain}` : domain;

                        return `
                                                        <div class="d-flex gap-1 align-items-center">
                                                            <i class="ti ti-mail fs-6 opacity-50"></i>
                                                            <span>${email || '-'}</span>
                                                        </div>
                                                    `;
                    }
                },
                {
                    data: 'end_date',
                    name: 'end_date',
                    render: function (data, type, row) {
                        if (!data) return '-';

                        const date = new Date(data).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'short',
                            day: 'numeric'
                        });
                        return `
                                                        <div class="d-flex gap-1 align-items-center opacity-75">
                                                            <i class="ti ti-calendar-month fs-6"></i>
                                                            <span>${date}</span>
                                                        </div>
                                                    `;
                    }
                }
            ];
        }

        function deletePool(poolId) {
            poolToDelete = poolId;
            $('#deleteModal').modal('show');
        }

        document.getElementById('confirmDelete').addEventListener('click', function () {
            if (poolToDelete) {
                fetch(`/admin/pools/${poolToDelete}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload both tables
                            if (warmingPoolsTable) warmingPoolsTable.ajax.reload();
                            if (availablePoolsTable) availablePoolsTable.ajax.reload();
                            $('#deleteModal').modal('hide');

                            // Show success message
                            const alert = document.createElement('div');
                            alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
                            alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                            alert.innerHTML = `
                                                        <i class="fas fa-check-circle me-2"></i>Pool deleted successfully!
                                                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                                    `;
                            document.body.appendChild(alert);

                            setTimeout(() => {
                                if (alert.parentNode) {
                                    alert.remove();
                                }
                            }, 5000);
                        } else {
                            alert('Error deleting pool: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting pool');
                    });
            }
        });

        // Row checkbox change handler
        $(document).on('change', '.row-checkbox', function () {
            const poolId = $(this).data('pool-id');
            const domainId = $(this).data('domain-id');
            const prefixKey = $(this).data('prefix-key');
            const key = `${poolId}-${domainId}-${prefixKey}`;

            if ($(this).is(':checked')) {
                selectedItems.set(key, { pool_id: poolId, domain_id: domainId, prefix_key: prefixKey });
            } else {
                selectedItems.delete(key);
            }
            updateBulkActionBar();
        });

        // Select all visible checkbox handler
        $(document).on('change', '.select-all-visible', function () {
            const isChecked = $(this).is(':checked');
            const table = $(this).closest('table');
            table.find('.row-checkbox').each(function () {
                $(this).prop('checked', isChecked).trigger('change');
            });
        });

        function restoreCheckboxStates() {
            $('.row-checkbox').each(function () {
                const poolId = $(this).data('pool-id');
                const domainId = $(this).data('domain-id');
                const prefixKey = $(this).data('prefix-key');
                const key = `${poolId}-${domainId}-${prefixKey}`;
                $(this).prop('checked', selectedItems.has(key));
            });
        }

        function updateBulkActionBar() {
            const count = selectedItems.size;
            $('#selectedCount').text(count);
            if (count > 0) {
                $('#bulkActionBar').removeClass('d-none').addClass('d-flex');
            } else {
                $('#bulkActionBar').removeClass('d-flex').addClass('d-none');
            }
        }

        function clearAllSelections() {
            selectedItems.clear();
            $('.row-checkbox').prop('checked', false);
            $('.select-all-visible').prop('checked', false);
            updateBulkActionBar();
        }

        function openBulkUpdateModal(action) {
            $('#bulkUpdateAction').val(action);
            $('#bulkUpdateCount').text(selectedItems.size);
            if (action === 'extend') {
                $('#bulkUpdateModalTitle').text('Extend Warmup Days');
                $('#bulkDaysLabel').text('Days to Add');
            } else {
                $('#bulkUpdateModalTitle').text('Reduce Warmup Days');
                $('#bulkDaysLabel').text('Days to Reduce');
            }
            $('#bulkUpdateModal').modal('show');
        }

        $('#confirmBulkUpdate').on('click', function () {
            const action = $('#bulkUpdateAction').val();
            const days = parseInt($('#bulkDaysInput').val()) || 0;

            if (days <= 0) {
                alert('Please enter a valid number of days');
                return;
            }

            const items = Array.from(selectedItems.values());
            const payload = {
                items: items,
                extend_days: action === 'extend' ? days : 0,
                reduce_days: action === 'reduce' ? days : 0
            };

            $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>Updating...');

            $.ajax({
                url: "{{ route('admin.pool-domains.bulk-update-days') }}",
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function (response) {
                    $('#bulkUpdateModal').modal('hide');
                    clearAllSelections();

                    // Reload all tables
                    Object.values(dataTables).forEach(table => {
                        if (table && table.ajax) table.ajax.reload();
                    });

                    // Show success message
                    const alertDiv = $(`<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                                <i class="fas fa-check-circle me-2"></i>${response.message}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>`);
                    $('body').append(alertDiv);
                    setTimeout(() => alertDiv.remove(), 5000);
                },
                error: function (xhr) {
                    const msg = xhr.responseJSON?.message || 'Error updating end dates';
                    alert(msg);
                },
                complete: function () {
                    $('#confirmBulkUpdate').prop('disabled', false).html('<i class="fa fa-save me-1"></i>Update');
                }
            });
        });
    </script>
@endpush