@extends('admin.layouts.app')

@section('title', 'Pools')

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">
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


</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Pools Management</h2>
            <p class="text-muted mb-0">Manage and track all your pools</p>
        </div>
        <a href="{{ route('admin.pools.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create New Pool
        </a>
    </div>

    <!-- DataTable -->
    <div class="dataTables_wrapper">
        <table id="poolsTable" class="table table-dark table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
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
        </table>
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
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>

<script>
let poolToDelete = null;

$(document).ready(function() {
    $('#poolsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
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
                width: '60px',
                render: function(data, type, row) {
                    return '#' + data;
                }
            },
            {
                data: 'user.name',
                name: 'user.name',
                defaultContent: 'N/A'
            },
            {
                data: null,
                name: 'full_name',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    if (row.first_name || row.last_name) {
                        return (row.first_name || '') + ' ' + (row.last_name || '');
                    }
                    return '-';
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
                defaultContent: '-'
            },
            {
                data: 'sending_platform',
                name: 'sending_platform',
                defaultContent: '-'
            },
            {
                data: 'total_inboxes',
                name: 'total_inboxes',
                defaultContent: '-',
                className: 'text-center'
            },
            {
                data: 'assigned_to_name',
                name: 'assignedTo.name',
                defaultContent: '-'
            },
            {
                data: null,
                name: 'type',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    let badges = '';
                    if (row.is_internal) {
                        badges += '<span class="badge bg-info badge-type me-1">Internal</span>';
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
                    return new Date(data).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
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
                        <div class="action-buttons">
                            <a href="/admin/pools/${row.id}" class="btn btn-outline-primary btn-sm" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="/admin/pools/${row.id}/edit" class="btn btn-outline-secondary btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deletePool(${row.id})" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>' +
             '<"row"<"col-md-12"Bt>>' +
             '<"row"<"col-md-12"tr>>' +
             '<"row"<"col-md-5"i><"col-md-7"p>>',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="fas fa-file-excel me-1"></i>Export Excel',
                className: 'btn btn-success btn-sm me-2'
            },
            {
                extend: 'pdf',
                text: '<i class="fas fa-file-pdf me-1"></i>Export PDF',
                className: 'btn btn-danger btn-sm me-2'
            },
            {
                text: '<i class="fas fa-sync me-1"></i>Refresh',
                className: 'btn btn-info btn-sm',
                action: function (e, dt, node, config) {
                    dt.ajax.reload();
                }
            }
        ],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: `
                <div class="text-center py-4">
                    <i class="fas fa-swimming-pool fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No pools found</h5>
                    <p class="text-muted">Create your first pool to get started.</p>
                    <a href="{{ route('admin.pools.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Pool
                    </a>
                </div>
            `
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