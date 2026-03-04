@extends('admin.layouts.app')

@section('title', 'Pool Panel Details')

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

    .card {
        background-color: var(--slide-bg);
        border: 1px solid var(--border-color);
    }

    .card-header {
        background-color: var(--second-primary);
        color: var(--light-color);
    }

    .info-card {
        background-color: var(--info-bg, #e3f2fd);
        border-left: 4px solid var(--info-color, #2196f3);
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .stats-card {
        background: linear-gradient(135deg, var(--second-primary, #6f42c1) 0%, var(--primary-color, #5a49cd) 100%);
        color: white;
        padding: 1.5rem;
        border-radius: 10px;
        margin-bottom: 1rem;
    }

    .progress {
        height: 10px;
        border-radius: 5px;
    }

    .badge {
        font-size: 0.875em;
    }

    .detail-row {
        border-bottom: 1px solid var(--border-color, #dee2e6);
        padding: 0.75rem 0;
    }

    .detail-row:last-child {
        border-bottom: none;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0">Pool Panel Details</h4>
                <div>
                    <a href="{{ route('admin.pool-panels.edit', $poolPanel->id) }}" class="btn btn-primary me-2">
                        <i class="ti ti-edit"></i> Edit
                    </a>
                    <button class="btn btn-danger me-2" onclick="deletePoolPanel({{ $poolPanel->id }})">
                        <i class="ti ti-trash"></i> Delete
                    </button>
                    <a href="{{ route('admin.pool-panels.index') }}" class="btn btn-secondary">
                        <i class="ti ti-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="stats-card">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <h5 class="mb-1">{{ $poolPanel->title }}</h5>
                                <small class="opacity-75">{{ $poolPanel->auto_generated_id }}</small>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h6 class="mb-0">{{ $poolPanel->used_limit }}</h6>
                                    <small>Used</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h6 class="mb-0">{{ $poolPanel->remaining_limit }}</h6>
                                    <small>Remaining</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <h6 class="mb-0">{{ $poolPanel->limit }}</h6>
                                    <small>Total Limit</small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small>Usage Progress</small>
                                <small>{{ $poolPanel->usage_percentage }}%</small>
                            </div>
                            <div class="progress">
                                <div class="progress-bar 
                                    @if($poolPanel->usage_percentage > 80) bg-danger
                                    @elseif($poolPanel->usage_percentage > 60) bg-warning
                                    @else bg-success
                                    @endif" 
                                    style="width: {{ $poolPanel->usage_percentage }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pool Panel Information -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Pool Panel Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="detail-row">
                                <div class="row">
                                    <div class="col-md-4"><strong>Auto Generated ID:</strong></div>
                                    <div class="col-md-8">
                                        <code>{{ $poolPanel->auto_generated_id }}</code>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="row">
                                    <div class="col-md-4"><strong>Title:</strong></div>
                                    <div class="col-md-8">{{ $poolPanel->title }}</div>
                                </div>
                            </div>

                            <div class="detail-row">
                                <div class="row">
                                    <div class="col-md-4"><strong>Associated Panel:</strong></div>
                                    <div class="col-md-8">
                                        @if($poolPanel->panel)
                                            {{ $poolPanel->panel->title }}
                                            <small class="text-muted">({{ $poolPanel->panel->auto_generated_id }})</small>
                                        @else
                                            <span class="text-muted">No panel associated</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            @if($poolPanel->description)
                            <div class="detail-row">
                                <div class="row">
                                    <div class="col-md-4"><strong>Description:</strong></div>
                                    <div class="col-md-8">{{ $poolPanel->description }}</div>
                                </div>
                            </div>
                            @endif

                            <div class="detail-row">
                                <div class="row">
                                    <div class="col-md-4"><strong>Status:</strong></div>
                                    <div class="col-md-8">
                                        <span class="badge {{ $poolPanel->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $poolPanel->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        @if($poolPanel->is_active)
                                            <button class="btn btn-sm btn-outline-secondary ms-2" onclick="toggleStatus({{ $poolPanel->id }})">
                                                Deactivate
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-success ms-2" onclick="toggleStatus({{ $poolPanel->id }})">
                                                Activate
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Usage Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Usage Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="row">
                                    <div class="col-4">
                                        <div class="p-3 rounded" style="background-color: rgba(40, 167, 69, 0.1);">
                                            <h6 class="mb-0 text-success">{{ $poolPanel->used_limit }}</h6>
                                            <small class="text-muted">Used</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-3 rounded" style="background-color: rgba(255, 193, 7, 0.1);">
                                            <h6 class="mb-0 text-warning">{{ $poolPanel->remaining_limit }}</h6>
                                            <small class="text-muted">Remaining</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="p-3 rounded" style="background-color: rgba(13, 110, 253, 0.1);">
                                            <h6 class="mb-0 text-primary">{{ $poolPanel->limit }}</h6>
                                            <small class="text-muted">Total</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-2">
                                <small class="d-flex justify-content-between">
                                    <span>Remaining Percentage:</span>
                                    <span>{{ $poolPanel->remaining_percentage }}%</span>
                                </small>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: {{ $poolPanel->remaining_percentage }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">System Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="detail-row">
                                <small class="text-muted">Created By:</small><br>
                                <strong>{{ $poolPanel->creator ? $poolPanel->creator->name : 'System' }}</strong>
                            </div>

                            @if($poolPanel->updater)
                            <div class="detail-row">
                                <small class="text-muted">Last Updated By:</small><br>
                                <strong>{{ $poolPanel->updater->name }}</strong>
                            </div>
                            @endif

                            <div class="detail-row">
                                <small class="text-muted">Created At:</small><br>
                                <strong>{{ $poolPanel->created_at->format('M d, Y H:i:s') }}</strong>
                            </div>

                            @if($poolPanel->updated_at != $poolPanel->created_at)
                            <div class="detail-row">
                                <small class="text-muted">Last Updated:</small><br>
                                <strong>{{ $poolPanel->updated_at->format('M d, Y H:i:s') }}</strong>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleStatus(id) {
    Swal.fire({
        title: 'Toggle Status',
        text: 'Are you sure you want to change the status of this pool panel?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, toggle it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `{{ route('admin.pool-panels.toggle-status', ':id') }}`.replace(':id', id),
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    toastr.success(response.message);
                    location.reload();
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred');
                }
            });
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
                url: `{{ route('admin.pool-panels.destroy', ':id') }}`.replace(':id', id),
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    Swal.fire(
                        'Deleted!',
                        response.message,
                        'success'
                    ).then(() => {
                        window.location.href = '{{ route("admin.pool-panels.index") }}';
                    });
                },
                error: function(xhr) {
                    Swal.fire(
                        'Error!',
                        xhr.responseJSON?.message || 'An error occurred while deleting the pool panel.',
                        'error'
                    );
                }
            });
        }
    });
}
</script>
@endpush