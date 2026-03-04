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

        .status-badge.badge-danger,
        .badge-danger {
            background-color: rgba(220, 53, 69, 0.2) !important;
            color: rgba(var(--bs-danger-rgb)) !important;
            border: 1px solid rgba(var(--bs-danger-rgb)) !important;
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
        .dataTables_wrapper > .top {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: nowrap;
            gap: 1rem;
        }

        .dataTables_wrapper .dataTables_filter {
            float: right;
            margin-bottom: 1rem;
        }

        .dataTables_wrapper > .top .dataTables_filter {
            float: none;
            margin-bottom: 0;
            margin-right: 0.5rem;
            display: flex;
            align-items: center;
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
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .dataTables_wrapper > .top .dataTables_length {
            float: none;
            margin-bottom: 0;
            margin-left: 0.5rem;
            display: flex !important;
            align-items: center;
        }

        .dataTables_wrapper .dataTables_length label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0;
            font-weight: normal;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            margin: 0;
            background-color: var(--bs-body-bg);
            color: var(--bs-body-color);
            display: inline-block !important;
            visibility: visible !important;
            opacity: 1 !important;
            min-width: 80px;
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
        <div id="bulkActionBar" class="card d-none align-items-center justify-content-between mb-3 px-4 py-3 flex-row"
            style="border: 1px solid var(--second-primary); background: linear-gradient(135deg, rgba(74, 58, 255, 0.15), rgba(74, 58, 255, 0.05));">
            <div class="d-flex align-items-center">
                <i class="fa fa-check-circle me-2" style="color: var(--second-primary);"></i>
                <span id="selectedCount">0</span> email account(s) selected
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm" style="background-color: var(--second-primary); color: #fff;"
                    onclick="openBulkUpdateModal('extend')">
                    <i class="fa fa-plus me-1"></i>Extend Days
                </button>
                <button type="button" class="btn btn-sm"
                    style="background-color: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid #ffc107;"
                    onclick="openBulkUpdateModal('reduce')">
                    <i class="fa fa-minus me-1"></i>Reduce Days
                </button>
                <button type="button" class="btn btn-sm"
                    style="background-color: transparent; color: var(--light-color); border: 1px solid var(--input-border);"
                    onclick="clearAllSelections()">
                    <i class="fa fa-times me-1"></i>Clear
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
        <li class="nav-item" role="presentation">
                    <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white"
                        id="smtp-pool-tab" data-bs-toggle="tab" data-bs-target="#smtp-pool-tab-pane" type="button" role="tab"
                        aria-controls="smtp-pool-tab-pane" aria-selected="false">
                        <i class="fa fa-envelope me-1"></i>SMTP Pool
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
                                <span class="badge bg-warning text-dark ms-2" id="google-warming-badge">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="google-available-tab" data-bs-toggle="tab"
                                data-bs-target="#google-available-pane" type="button" role="tab">
                                <i class="fa fa-check-circle me-1 text-success"></i>Available
                                <span class="badge bg-success ms-2" id="google-available-badge">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="google-used-tab" data-bs-toggle="tab"
                                data-bs-target="#google-used-pane" type="button" role="tab">
                                <i class="fa fa-lock me-1 text-danger"></i>Used
                                <span class="badge bg-danger ms-2" id="google-used-badge">0</span>
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
                                                <th>Days Remaining</th>
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
                                                <th>Days Remaining</th>
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
                                <span class="badge bg-warning text-dark ms-2" id="ms365-warming-badge">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ms365-available-tab" data-bs-toggle="tab"
                                data-bs-target="#ms365-available-pane" type="button" role="tab">
                                <i class="fa fa-check-circle me-1 text-success"></i>Available
                                <span class="badge bg-success ms-2" id="ms365-available-badge">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="ms365-used-tab" data-bs-toggle="tab" data-bs-target="#ms365-used-pane"
                                type="button" role="tab">
                                <i class="fa fa-lock me-1 text-danger"></i>Used
                                <span class="badge bg-danger ms-2" id="ms365-used-badge">0</span>
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
                                                <th>Days Remaining</th>
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
                                                <th>Days Remaining</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SMTP Pool Tab -->
                <div class="tab-pane fade" id="smtp-pool-tab-pane" role="tabpanel" aria-labelledby="smtp-pool-tab"
                    tabindex="0">

                    <!-- Nested Tabs for SMTP Pool -->
                    <ul class="nav nav-tabs mb-3" id="smtpStatusTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="smtp-warming-tab" data-bs-toggle="tab"
                                data-bs-target="#smtp-warming-pane" type="button" role="tab">
                                <i class="fa fa-fire me-1 text-warning"></i>Warming
                                <span class="badge bg-warning text-dark ms-2" id="smtp-warming-badge">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="smtp-available-tab" data-bs-toggle="tab"
                                data-bs-target="#smtp-available-pane" type="button" role="tab">
                                <i class="fa fa-check-circle me-1 text-success"></i>Available
                                <span class="badge bg-success ms-2" id="smtp-available-badge">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="smtp-used-tab" data-bs-toggle="tab"
                                data-bs-target="#smtp-used-pane" type="button" role="tab">
                                <i class="fa fa-lock me-1 text-danger"></i>Used
                                <span class="badge bg-danger ms-2" id="smtp-used-badge">0</span>
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="smtpStatusTabContent">
                        <!-- SMTP Warming -->
                        <div class="tab-pane fade show active" id="smtp-warming-pane" role="tabpanel">
                            <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                                <div class="table-responsive">
                                    <table id="smtpWarmingTable" class="table table-hover w-100">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" class="select-all-visible" /></th>
                                                <th>Pool ID</th>
                                                <th>Created By</th>
                                                <th>Status</th>
                                                <th>Email Account</th>
                                                <th>Provider URL</th>
                                                <th>Expiry Date</th>
                                                <th>Days Remaining</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- SMTP Available -->
                        <div class="tab-pane fade" id="smtp-available-pane" role="tabpanel">
                            <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                                <div class="table-responsive">
                                    <table id="smtpAvailableTable" class="table table-hover w-100">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" class="select-all-visible" /></th>
                                                <th>Pool ID</th>
                                                <th>Created By</th>
                                                <th>Status</th>
                                                <th>Email Account</th>
                                                <th>Provider URL</th>
                                                <th>Expiry Date</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <!-- SMTP Used -->
                        <div class="tab-pane fade" id="smtp-used-pane" role="tabpanel">
                            <div class="card py-3 px-4 mb-4 shadow-sm border-0">
                                <div class="table-responsive">
                                    <table id="smtpUsedTable" class="table table-hover w-100">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" class="select-all-visible" /></th>
                                                <th>Pool ID</th>
                                                <th>Created By</th>
                                                <th>Status</th>
                                                <th>Email Account</th>
                                                <th>Provider URL</th>
                                                <th>Expiry Date</th>
                                                <th>Days Remaining</th>
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

        <!-- Bulk Update Days Offcanvas -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="bulkUpdateOffcanvas" style="width: 70%;">
            <div class="offcanvas-header border-bottom" style="border-color: var(--input-border) !important;">
                <h5 class="offcanvas-title" id="bulkUpdateOffcanvasTitle">
                    <i class="fa fa-calendar-plus me-2" style="color: var(--second-primary);"></i>
                    Update Warmup Days
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <input type="hidden" id="bulkUpdateAction" value="">

                <!-- Days Input Section -->
                <div class="card mb-4"
                    style="border: 1px solid var(--second-primary); background: linear-gradient(135deg, rgba(74, 58, 255, 0.1), rgba(74, 58, 255, 0.02));">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <label for="bulkDaysInput" class="form-label mb-1" id="bulkDaysLabel">Number of Days</label>
                                <div class="input-group">
                                    <input type="number" class="form-control form-control-lg" id="bulkDaysInput" min="1"
                                        value="30"
                                        style="background-color: var(--primary-color); border-color: var(--input-border);">
                                    <span class="input-group-text"
                                        style="background-color: var(--secondary-color); border-color: var(--input-border);">days</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center h-100 pt-4">
                                    <i class="fa fa-info-circle me-2" style="color: var(--second-primary);"></i>
                                    <span>Updating <strong id="bulkUpdateCount">0</strong> email account(s)</span>
                                </div>
                            </div>
                            <div class="col-md-4 text-end pt-4">
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="offcanvas">
                                    <i class="fa fa-times me-1"></i>Cancel
                                </button>
                                <button type="button" class="btn btn-primary" id="confirmBulkUpdate">
                                    <i class="fa fa-save me-1"></i>Apply Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Selected Items Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fa fa-list me-2"></i>Selected Email Accounts</h6>
                        <small class="opacity-75">Preview of changes</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="bulkPreviewTable">
                                <thead>
                                    <tr style="background-color: var(--primary-color);">
                                        <th class="ps-3">Pool ID</th>
                                        <th>Email Account</th>
                                        <th>Current Expiry</th>
                                        <th>New Expiry</th>
                                        <th>Change</th>
                                    </tr>
                                </thead>
                                <tbody id="bulkPreviewTableBody">
                                    <!-- Populated dynamically -->
                                </tbody>
                            </table>
                        </div>
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
            // Initialize all 9 DataTables (Google, MS365, SMTP)
            const tableConfigs = [
                { id: 'googleWarmingTable', provider: 'Google', status: 'warming', emptyIcon: 'fa-fire', emptyColor: 'text-warning' },
                { id: 'googleAvailableTable', provider: 'Google', status: 'available', emptyIcon: 'fa-check-circle', emptyColor: 'text-success' },
                { id: 'googleUsedTable', provider: 'Google', status: 'used', emptyIcon: 'fa-lock', emptyColor: 'text-danger' },
                { id: 'ms365WarmingTable', provider: 'Microsoft 365', status: 'warming', emptyIcon: 'fa-fire', emptyColor: 'text-warning' },
                { id: 'ms365AvailableTable', provider: 'Microsoft 365', status: 'available', emptyIcon: 'fa-check-circle', emptyColor: 'text-success' },
                { id: 'ms365UsedTable', provider: 'Microsoft 365', status: 'used', emptyIcon: 'fa-lock', emptyColor: 'text-danger' },
                { id: 'smtpWarmingTable', provider: 'SMTP', status: 'warming', emptyIcon: 'fa-fire', emptyColor: 'text-warning' },
                { id: 'smtpAvailableTable', provider: 'SMTP', status: 'available', emptyIcon: 'fa-check-circle', emptyColor: 'text-success' },
                { id: 'smtpUsedTable', provider: 'SMTP', status: 'used', emptyIcon: 'fa-lock', emptyColor: 'text-danger' }
            ];

            tableConfigs.forEach(config => {
                dataTables[config.id] = $(`#${config.id}`).DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    dom: '<"top"lf>rt<"bottom"ip><"clear">',
                    ajax: {
                        url: "{{ route('admin.pool-domains.index') }}",
                        data: function (d) {
                            d.datatable = true;
                            d.provider_type = config.provider;
                            d.status_filter = config.status;
                        }
                    },
                    columns: getTableColumns(config.provider, config.status),
                    order: [[1, 'desc']],
                    pageLength: 25,
                    lengthMenu: [[25, 50, 100, 250, 500, 1000, -1], [25, 50, 100, 250, 500, 1000, "All"]],
                    drawCallback: function () {
                        // Restore checkbox states after pagination
                        restoreCheckboxStates();
                        // Initialize tooltips
                        $('[data-bs-toggle="tooltip"]').tooltip();
                    },
                    language: {
                        emptyTable: `
                                                                <div class="text-center py-4">
                                                                    <i class="fa ${config.emptyIcon} fs-1 ${config.emptyColor} mb-3"></i>
                                                                    <h5 class="">No ${config.status} email accounts found</h5>
                                                                    <p class="">All ${config.status} email accounts will appear here.</p>
                                                                </div>
                                                            `
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

            // Load email counts for all providers
            loadEmailCounts('Google');
            loadEmailCounts('Microsoft 365');
            loadEmailCounts('SMTP');
        });

        /**
         * Load email counts for a specific provider type
         */
        function loadEmailCounts(providerType) {
            $.ajax({
                url: "{{ route('admin.pool-domains.email-counts') }}",
                type: 'GET',
                data: {
                    provider_type: providerType
                },
                success: function(response) {
                    if (response.success && response.counts) {
                        const counts = response.counts;
                        
                        // Update badge counts on tabs
                        if (providerType === 'Google') {
                            $('#google-warming-badge').text(counts.warming || 0);
                            $('#google-available-badge').text(counts.available || 0);
                            $('#google-used-badge').text(counts.used || 0);
                        } else if (providerType === 'Microsoft 365') {
                            $('#ms365-warming-badge').text(counts.warming || 0);
                            $('#ms365-available-badge').text(counts.available || 0);
                            $('#ms365-used-badge').text(counts.used || 0);
                        } else if (providerType === 'SMTP') {
                            $('#smtp-warming-badge').text(counts.warming || 0);
                            $('#smtp-available-badge').text(counts.available || 0);
                            $('#smtp-used-badge').text(counts.used || 0);
                        }
                    }
                },
                error: function(xhr) {
                    console.error('Error loading email counts for ' + providerType + ':', xhr);
                }
            });
        }

        function getTableColumns(providerType, statusFilter) {
            const columns = [
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
                    data: 'email_account',
                    name: 'email_account',
                    searchable: true,
                    render: function (data, type, row) {
                        // Use email_account from backend if available, otherwise construct from prefix and domain
                        const email = data || (row.prefix_value && row.domain_name ? `${row.prefix_value}@${row.domain_name}` : row.domain_name || '-');

                        return `
                                                                <div class="d-flex gap-1 align-items-center">
                                                                    <i class="ti ti-mail fs-6 opacity-50"></i>
                                                                    <span>${email || '-'}</span>
                                                                </div>
                                                            `;
                    }
                }
            ];

            // Add SMTP Provider URL column ONLY for SMTP provider type
            if (providerType === 'SMTP' || providerType === 'Private SMTP') {
                columns.push({
                    data: 'smtp_provider_url',
                    name: 'smtp_provider_url',
                    orderable: false,
                    searchable: false,
                    defaultContent: '-',
                    render: function (data, type, row) {
                        // Backend returns raw URL string, frontend renders HTML
                        const url = data || row.smtp_provider_url || null;
                        
                        // Check if URL exists and is not empty
                        if (url && typeof url === 'string' && url.trim() !== '') {
                            const cleanUrl = url.trim();
                            return `
                                <a href="${escapeHtml(cleanUrl)}" target="_blank" class="text-info text-decoration-none">
                                    <i class="fa fa-external-link me-1"></i>${escapeHtml(cleanUrl)}
                                </a>
                            `;
                        }
                        return '<span class="text-muted">-</span>';
                    }
                });
            }

            columns.push({
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
            });

            // Add Days Remaining column for warming and used status
            if (statusFilter === 'warming' || statusFilter === 'used') {
                columns.push({
                    data: 'days_remaining',
                    name: 'days_remaining',
                    orderable: false,
                    render: function (data, type, row) {
                        if (data === null || data === undefined) return '-';
                        
                        const days = parseInt(data);
                        if (isNaN(days)) return '-';
                        
                        if (days > 0) {
                            return `<span class="badge bg-warning text-dark">${days} days left</span>`;
                        } else if (days === 0) {
                            return `<span class="badge bg-success">Ends today</span>`;
                        } else {
                            return `<span class="badge bg-danger">Ended ${Math.abs(days)} days ago</span>`;
                        }
                    }
                });
            }

            return columns;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
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
            const $checkbox = $(this);
            const poolId = $checkbox.data('pool-id');
            const domainId = $checkbox.data('domain-id');
            const prefixKey = $checkbox.data('prefix-key');
            const key = `${poolId}-${domainId}-${prefixKey}`;

            if ($checkbox.is(':checked')) {
                // Get the row data from DataTable
                const $row = $checkbox.closest('tr');
                const $table = $checkbox.closest('table');
                const tableId = $table.attr('id');

                let rowData = {};
                if (dataTables[tableId]) {
                    rowData = dataTables[tableId].row($row).data() || {};
                }

                const prefixValue = rowData.prefix_value || '';
                const domainName = rowData.domain_name || '';
                const email = prefixValue && domainName ? `${prefixValue}@${domainName}` : domainName;

                selectedItems.set(key, { 
                    pool_id: poolId, 
                    domain_id: domainId, 
                    prefix_key: prefixKey,
                    email: email,
                    end_date: rowData.end_date || null,
                    prefix_value: prefixValue,
                    domain_name: domainName
                });
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
                $('#bulkUpdateOffcanvasTitle').html('<i class="fa fa-calendar-plus me-2" style="color: var(--second-primary);"></i>Extend Warmup Days');
                $('#bulkDaysLabel').text('Days to Add');
            } else {
                $('#bulkUpdateOffcanvasTitle').html('<i class="fa fa-calendar-minus me-2" style="color: #ffc107;"></i>Reduce Warmup Days');
                $('#bulkDaysLabel').text('Days to Reduce');
            }

            // Populate preview table
            updatePreviewTable();

            // Show offcanvas
            const offcanvas = new bootstrap.Offcanvas(document.getElementById('bulkUpdateOffcanvas'));
            offcanvas.show();
        }

        // Update preview table when days input changes
        $('#bulkDaysInput').on('input', function() {
            updatePreviewTable();
        });

        function updatePreviewTable() {
            const action = $('#bulkUpdateAction').val();
            const days = parseInt($('#bulkDaysInput').val()) || 0;
            const $tbody = $('#bulkPreviewTableBody');
            $tbody.empty();

            if (selectedItems.size === 0) {
                $tbody.append('<tr><td colspan="5" class="text-center py-4 opacity-75">No items selected</td></tr>');
                return;
            }

            selectedItems.forEach((item, key) => {
                const poolId = item.pool_id;
                const email = item.email || '-';
                const currentEndDate = item.end_date;

                // Calculate new date
                let newEndDate = null;
                let diffDays = 0;

                if (currentEndDate) {
                    const currentDate = new Date(currentEndDate);
                    diffDays = action === 'extend' ? days : -days;
                    currentDate.setDate(currentDate.getDate() + diffDays);
                    newEndDate = currentDate.toISOString().split('T')[0];
                } else {
                    // If no current date, use today + days
                    const today = new Date();
                    diffDays = action === 'extend' ? days : -days;
                    today.setDate(today.getDate() + diffDays);
                    newEndDate = today.toISOString().split('T')[0];
                }

                // Format dates for display
                const formatDate = (dateStr) => {
                    if (!dateStr) return '<span class="opacity-50">Not set</span>';
                    const date = new Date(dateStr);
                    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                };

                // Difference badge
                const diffBadge = action === 'extend' 
                    ? `<span class="badge" style="background-color: rgba(25, 135, 84, 0.2); color: #198754; border: 1px solid #198754;">+${days} days</span>`
                    : `<span class="badge" style="background-color: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid #dc3545;">-${days} days</span>`;

                const row = `
                    <tr>
                        <td class="ps-3">
                            <a href="/admin/pools/${poolId}" class="text-primary text-decoration-none">
                                <i class="ti ti-hash fs-6 opacity-50"></i>${poolId}
                            </a>
                        </td>
                        <td>
                            <i class="ti ti-mail fs-6 opacity-50 me-1"></i>${email}
                        </td>
                        <td>
                            <span class="opacity-75">${formatDate(currentEndDate)}</span>
                        </td>
                        <td>
                            <strong style="color: ${action === 'extend' ? '#198754' : '#ffc107'};">${formatDate(newEndDate)}</strong>
                        </td>
                        <td>${diffBadge}</td>
                    </tr>
                `;
                $tbody.append(row);
            });
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

            $(this).prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i>Applying...');

            $.ajax({
                url: "{{ route('admin.pool-domains.bulk-update-days') }}",
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                contentType: 'application/json',
                data: JSON.stringify(payload),
                success: function (response) {
                    // Hide offcanvas
                    const offcanvasEl = document.getElementById('bulkUpdateOffcanvas');
                    const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasEl);
                    if (offcanvas) offcanvas.hide();

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
                    $('#confirmBulkUpdate').prop('disabled', false).html('<i class="fa fa-save me-1"></i>Apply Changes');
                }
            });
        });
    </script>
@endpush