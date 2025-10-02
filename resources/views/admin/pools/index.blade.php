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

    .badge-pending { background-color: rgba(255, 193, 7, 0.2); color: #ffc107; border: 1px solid #ffc107; }
    .badge-in_progress { background-color: rgba(13, 202, 240, 0.2); color: #0dcaf0; border: 1px solid #0dcaf0; }
    .badge-completed { background-color: rgba(25, 135, 84, 0.2); color: #198754; border: 1px solid #198754; }
    .badge-cancelled { background-color: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid #dc3545; }

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
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Pools Management</h2>
            <p class="mb-0">Manage and track all your pools</p>
        </div>
        <a href="{{ route('admin.pools.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create New Pool
        </a>
    </div>
    <div class="card py-3 px-4 mb-4 shadow-sm border-0">
        <!-- DataTable -->
        <div class="table-responsive">
            <table id="poolsTable" class="table table-hover w-100">
                <thead>
                    <tr>
                        <th>Pool ID</th>
                        <th>Customer</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Hosting Platform</th>
                        <th>Sending Platform</th>
                        <th>Total Inboxes</th>
                        <th>Assigned To</th>
                        <th>Type</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

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
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
let poolToDelete = null;

$(document).ready(function() {
    $('#poolsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        // autoWidth: false,
        dom: '<"top"f>rt<"bottom"lip><"clear">',
        ajax: {
            url: "{{ route('admin.pools.index') }}",
            data: function (d) {
                d.datatable = true;
            }
        },
        columns: [
            {
                data: 'id',
                name: 'id',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex gap-1 align-items-center">
                            <i class="ti ti-hash fs-6 opacity-50"></i>
                            <span>${data}</span>
                        </div>
                    `;
                }
            },
            {
                data: 'user.name',
                name: 'user.name',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex gap-1 align-items-center">
                            <i class="ti ti-user fs-6 opacity-50"></i>
                            <span>${data || 'N/A'}</span>
                        </div>
                    `;
                }
            },
            {
                data: null,
                name: 'full_name',
                orderable: false,
                searchable: false,
                visible: false,
                render: function(data, type, row) {
                    const fullName = ((row.first_name || '') + ' ' + (row.last_name || '')).trim();
                    return fullName || '-';
                }
            },
            {
                data: 'status',
                name: 'status',
                render: function(data, type, row) {
                    const statusClass = 'badge-' + data;
                    const statusText = data.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    return `<span class="status-badge ${statusClass}">${statusText}</span>`;
                }
            },
            {
                data: 'hosting_platform',
                name: 'hosting_platform',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex gap-1 align-items-center">
                            <i class="ti ti-server fs-6 opacity-50"></i>
                            <span>${data || '-'}</span>
                        </div>
                    `;
                }
            },
            {
                data: 'sending_platform',
                name: 'sending_platform',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex gap-1 align-items-center">
                            <i class="ti ti-send fs-6 opacity-50"></i>
                            <span>${data || '-'}</span>
                        </div>
                    `;
                }
            },
            {
                data: 'total_inboxes',
                name: 'total_inboxes',
                className: 'text-center',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex gap-1 align-items-center justify-content-center">
                            <i class="ti ti-mail fs-6 opacity-50"></i>
                            <span>${data || '-'}</span>
                        </div>
                    `;
                }
            },
            {
                data: 'assigned_to_name',
                name: 'assignedTo.name',
                render: function(data, type, row) {
                    return `
                        <div class="d-flex gap-1 align-items-center">
                            <i class="ti ti-user-check fs-6 opacity-50"></i>
                            <span>${data || '-'}</span>
                        </div>
                    `;
                }
            },
            {
                data: null,
                name: 'type',
                orderable: false,
                searchable: false,
                // visible none
                visible: false,
                render: function(data, type, row) {
                    let badges = '';
                    if (row.is_internal) {
                        badges += '<span class="badge bg-info badge-type">Internal</span>';
                    }
                    if (row.is_shared) {
                        badges += '<span class="badge bg-warning badge-type">Shared</span>';
                    }
                    return badges || '-';
                }
            },
            {
                data: 'created_at',
                name: 'created_at',
                render: function(data, type, row) {
                    const date = new Date(data).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    return `
                        <div class="d-flex gap-1 align-items-center opacity-50">
                            <i class="ti ti-calendar-month fs-6"></i>
                            <span>${date}</span>
                        </div>
                    `;
                }
            },
            {
                data: null,
                name: 'actions',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    return `
                        <div class="dropdown">
                            <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="/admin/pools/${row.id}">
                                        <i class="fa-solid fa-eye"></i> &nbsp;View
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="/admin/pools/${row.id}/edit">
                                        <i class="fa-solid fa-edit"></i> &nbsp;Edit
                                    </a>
                                </li>
                            </ul>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: `
                <div class="text-center py-4">
                    <i class="ti ti-swimming-pool fs-1 text-muted mb-3"></i>
                    <h5 class="text-muted">No pools found</h5>
                    <p class="text-muted">Create your first pool to get started.</p>
                    <a href="{{ route('admin.pools.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>Create Pool
                    </a>
                </div>
            `
        },
        drawCallback: function() {
            $('[data-bs-toggle="tooltip"]').tooltip();
        }
    });
});

function deletePool(poolId) {
    poolToDelete = poolId;
    $('#deleteModal').modal('show');
}

document.getElementById('confirmDelete').addEventListener('click', function() {
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
                $('#poolsTable').DataTable().ajax.reload();
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
</script>
@endpush