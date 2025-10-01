@extends('admin.layouts.app')

@section('title', 'Pools')

@push('styles')
<style>
    .pool-card {
        background-color: #ffffff04;
        border: 1px solid #404040;
        border-radius: 12px;
        transition: all 0.3s ease;
        box-shadow: rgba(125, 125, 186, 0.109) 0px 50px 100px -20px, rgb(0, 0, 0) 0px 30px 60px -20px, rgba(173, 173, 173, 0) 0px -2px 6px 0px inset;
    }

    .pool-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
    }

    .badge-pending { background-color: rgba(255, 193, 7, 0.2); color: #ffc107; }
    .badge-in_progress { background-color: rgba(13, 202, 240, 0.2); color: #0dcaf0; }
    .badge-completed { background-color: rgba(25, 135, 84, 0.2); color: #198754; }
    .badge-cancelled { background-color: rgba(220, 53, 69, 0.2); color: #dc3545; }

    .filter-section {
        background-color: #1e1e1e;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .search-box {
        background-color: #2a2a2a;
        border: 1px solid #404040;
        color: #fff;
    }

    .search-box:focus {
        background-color: #2a2a2a;
        border-color: #667eea;
        color: #fff;
        box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
    }
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

    <!-- Filters Section -->
    <div class="filter-section">
        <form id="filterForm" method="GET">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control search-box" 
                           placeholder="Search pools..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select search-box">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select name="is_internal" class="form-select search-box">
                        <option value="">All Types</option>
                        <option value="1" {{ request('is_internal') == '1' ? 'selected' : '' }}>Internal</option>
                        <option value="0" {{ request('is_internal') == '0' ? 'selected' : '' }}>External</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Shared</label>
                    <select name="is_shared" class="form-select search-box">
                        <option value="">All</option>
                        <option value="1" {{ request('is_shared') == '1' ? 'selected' : '' }}>Shared</option>
                        <option value="0" {{ request('is_shared') == '0' ? 'selected' : '' }}>Not Shared</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        <i class="fas fa-search me-1"></i>Filter
                    </button>
                    <a href="{{ route('admin.pools.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Clear
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Pools Grid -->
    <div class="row">
        @forelse($pools as $pool)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="pool-card card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Pool #{{ $pool->id }}</h6>
                    <span class="status-badge badge-{{ $pool->status }}">
                        {{ ucfirst(str_replace('_', ' ', $pool->status)) }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Customer:</small>
                        <p class="mb-1">{{ $pool->user->name ?? 'N/A' }}</p>
                    </div>

                    @if($pool->first_name || $pool->last_name)
                    <div class="mb-3">
                        <small class="text-muted">Name:</small>
                        <p class="mb-1">{{ trim($pool->first_name . ' ' . $pool->last_name) }}</p>
                    </div>
                    @endif

                    @if($pool->hosting_platform)
                    <div class="mb-3">
                        <small class="text-muted">Hosting Platform:</small>
                        <p class="mb-1">{{ $pool->hosting_platform }}</p>
                    </div>
                    @endif

                    @if($pool->total_inboxes)
                    <div class="mb-3">
                        <small class="text-muted">Total Inboxes:</small>
                        <p class="mb-1">{{ $pool->total_inboxes }}</p>
                    </div>
                    @endif

                    @if($pool->assigned_to)
                    <div class="mb-3">
                        <small class="text-muted">Assigned To:</small>
                        <p class="mb-1">{{ $pool->assignedTo->name ?? 'N/A' }}</p>
                    </div>
                    @endif

                    <div class="mb-3">
                        <small class="text-muted">Created:</small>
                        <p class="mb-1">{{ $pool->created_at->format('M d, Y') }}</p>
                    </div>

                    @if($pool->is_internal)
                    <span class="badge bg-info mb-2">Internal</span>
                    @endif

                    @if($pool->is_shared)
                    <span class="badge bg-warning mb-2">Shared</span>
                    @endif
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        <a href="{{ route('admin.pools.show', $pool) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>View
                        </a>
                        <a href="{{ route('admin.pools.edit', $pool) }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-edit me-1"></i>Edit
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                onclick="deletePool({{ $pool->id }})">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-swimming-pool fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No pools found</h4>
                <p class="text-muted">Create your first pool to get started.</p>
                <a href="{{ route('admin.pools.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Create Pool
                </a>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($pools->hasPages())
    <div class="d-flex justify-content-center mt-4">
        {{ $pools->appends(request()->query())->links() }}
    </div>
    @endif
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
<script>
let poolToDelete = null;

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
                location.reload();
            } else {
                alert('Error deleting pool: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting pool');
        });
    }
    $('#deleteModal').modal('hide');
});

// Auto-submit form on filter change
document.querySelectorAll('select[name]').forEach(select => {
    select.addEventListener('change', function() {
        document.getElementById('filterForm').submit();
    });
});
</script>
@endpush