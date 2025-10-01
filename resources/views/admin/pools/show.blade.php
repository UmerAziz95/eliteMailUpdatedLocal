@extends('admin.layouts.app')

@section('title', 'Pool Details')

@push('styles')
<style>
    .pool-detail-card {
        background-color: #ffffff04;
        border: 1px solid #404040;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        box-shadow: rgba(125, 125, 186, 0.109) 0px 50px 100px -20px, rgb(0, 0, 0) 0px 30px 60px -20px, rgba(173, 173, 173, 0) 0px -2px 6px 0px inset;
    }

    .pool-detail-card h5 {
        color: #667eea;
        margin-bottom: 1rem;
        font-weight: 600;
        border-bottom: 2px solid #667eea;
        padding-bottom: 0.5rem;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #333;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 600;
        color: #aaa;
        min-width: 150px;
    }

    .detail-value {
        color: #fff;
        word-break: break-word;
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
        border-radius: 6px;
        font-weight: 600;
    }

    .badge-pending { background-color: rgba(255, 193, 7, 0.2); color: #ffc107; }
    .badge-in_progress { background-color: rgba(13, 202, 240, 0.2); color: #0dcaf0; }
    .badge-completed { background-color: rgba(25, 135, 84, 0.2); color: #198754; }
    .badge-cancelled { background-color: rgba(220, 53, 69, 0.2); color: #dc3545; }

    .domain-list {
        background-color: #2a2a2a;
        border-radius: 6px;
        padding: 1rem;
        max-height: 200px;
        overflow-y: auto;
    }

    .domain-item {
        padding: 0.25rem 0.5rem;
        margin: 0.25rem 0;
        background-color: #3a3a3a;
        border-radius: 4px;
        border-left: 3px solid #667eea;
    }

    .action-buttons {
        background-color: #1a1a1a;
        border-radius: 8px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .masked-field {
        font-family: monospace;
        color: #888;
    }

    .show-password {
        cursor: pointer;
        color: #667eea;
        font-size: 0.875rem;
        margin-left: 0.5rem;
    }

    .show-password:hover {
        text-decoration: underline;
    }

    .empty-value {
        color: #666;
        font-style: italic;
    }
</style>
@endpush

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Pool #{{ $pool->id }}</h2>
            <p class="text-muted mb-0">
                Created on {{ $pool->created_at->format('M d, Y \a\t H:i') }}
                @if($pool->completed_at)
                    • Completed on {{ $pool->completed_at->format('M d, Y \a\t H:i') }}
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.pools.edit', $pool) }}" class="btn btn-outline-primary">
                <i class="fas fa-edit me-2"></i>Edit Pool
            </a>
            <a href="{{ route('admin.pools.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Pools
            </a>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <div class="d-flex gap-2 align-items-center">
            <span class="status-badge badge-{{ $pool->status }}">
                {{ ucfirst(str_replace('_', ' ', $pool->status)) }}
            </span>
            
            @if($pool->is_internal)
                <span class="badge bg-info">Internal</span>
            @endif
            
            @if($pool->is_shared)
                <span class="badge bg-warning">Shared</span>
            @endif

            <div class="ms-auto">
                @if($pool->status == 'pending')
                    <button class="btn btn-sm btn-success" onclick="updateStatus('in_progress')">
                        <i class="fas fa-play me-1"></i>Start Pool
                    </button>
                @elseif($pool->status == 'in_progress')
                    <button class="btn btn-sm btn-primary" onclick="updateStatus('completed')">
                        <i class="fas fa-check me-1"></i>Complete Pool
                    </button>
                @endif
                
                @if($pool->status != 'cancelled')
                    <button class="btn btn-sm btn-danger" onclick="updateStatus('cancelled')">
                        <i class="fas fa-times me-1"></i>Cancel Pool
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Basic Information -->
        <div class="col-md-6">
            <div class="pool-detail-card">
                <h5><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
                
                <div class="detail-row">
                    <span class="detail-label">Customer:</span>
                    <span class="detail-value">{{ $pool->user->name ?? 'N/A' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Plan:</span>
                    <span class="detail-value">{{ $pool->plan->name ?? 'No plan assigned' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value">
                        @if($pool->amount)
                            {{ $pool->currency }} {{ number_format($pool->amount, 2) }}
                        @else
                            <span class="empty-value">Not specified</span>
                        @endif
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge badge-{{ $pool->status }}">
                            {{ ucfirst(str_replace('_', ' ', $pool->status)) }}
                        </span>
                    </span>
                </div>
                
                @if($pool->assigned_to)
                <div class="detail-row">
                    <span class="detail-label">Assigned To:</span>
                    <span class="detail-value">{{ $pool->assignedTo->name ?? 'N/A' }}</span>
                </div>
                @endif
                
                @if($pool->reason)
                <div class="detail-row">
                    <span class="detail-label">Reason:</span>
                    <span class="detail-value">{{ $pool->reason }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Domain & Platform Information -->
        <div class="col-md-6">
            <div class="pool-detail-card">
                <h5><i class="fas fa-globe me-2"></i>Domain & Platform</h5>
                
                <div class="detail-row">
                    <span class="detail-label">Forwarding URL:</span>
                    <span class="detail-value">
                        @if($pool->forwarding_url)
                            <a href="{{ $pool->forwarding_url }}" target="_blank" class="text-primary">
                                {{ $pool->forwarding_url }}
                            </a>
                        @else
                            <span class="empty-value">Not specified</span>
                        @endif
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Hosting Platform:</span>
                    <span class="detail-value">{{ $pool->hosting_platform ?? 'Not specified' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Sending Platform:</span>
                    <span class="detail-value">{{ $pool->sending_platform ?? 'Not specified' }}</span>
                </div>
                
                @if($pool->other_platform)
                <div class="detail-row">
                    <span class="detail-label">Other Platform:</span>
                    <span class="detail-value">{{ $pool->other_platform }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- Domains -->
        @if($pool->domains && count($pool->domains) > 0)
        <div class="col-md-12">
            <div class="pool-detail-card">
                <h5><i class="fas fa-list me-2"></i>Domains ({{ count($pool->domains) }})</h5>
                <div class="domain-list">
                    @foreach($pool->domains as $domain)
                        <div class="domain-item">{{ $domain }}</div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        <!-- Inbox Configuration -->
        <div class="col-md-6">
            <div class="pool-detail-card">
                <h5><i class="fas fa-envelope me-2"></i>Inbox Configuration</h5>
                
                <div class="detail-row">
                    <span class="detail-label">Total Inboxes:</span>
                    <span class="detail-value">{{ $pool->total_inboxes ?? 'Not specified' }}</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Inboxes Per Domain:</span>
                    <span class="detail-value">{{ $pool->inboxes_per_domain ?? 'Not specified' }}</span>
                </div>
                
                @if($pool->initial_total_inboxes)
                <div class="detail-row">
                    <span class="detail-label">Initial Total:</span>
                    <span class="detail-value">{{ $pool->initial_total_inboxes }}</span>
                </div>
                @endif
                
                <div class="detail-row">
                    <span class="detail-label">Master Inbox:</span>
                    <span class="detail-value">
                        @if($pool->master_inbox_email)
                            {{ $pool->master_inbox_email }}
                            @if($pool->master_inbox_confirmation)
                                <span class="badge bg-success ms-2">Confirmed</span>
                            @endif
                        @else
                            <span class="empty-value">Not specified</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="col-md-6">
            <div class="pool-detail-card">
                <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
                
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">
                        @if($pool->first_name || $pool->last_name)
                            {{ trim($pool->first_name . ' ' . $pool->last_name) }}
                        @else
                            <span class="empty-value">Not specified</span>
                        @endif
                    </span>
                </div>
                
                @if($pool->profile_picture_link)
                <div class="detail-row">
                    <span class="detail-label">Profile Picture:</span>
                    <span class="detail-value">
                        <a href="{{ $pool->profile_picture_link }}" target="_blank" class="text-primary">
                            View Image
                        </a>
                    </span>
                </div>
                @endif
                
                @if($pool->email_persona_picture_link)
                <div class="detail-row">
                    <span class="detail-label">Email Picture:</span>
                    <span class="detail-value">
                        <a href="{{ $pool->email_persona_picture_link }}" target="_blank" class="text-primary">
                            View Image
                        </a>
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Platform Credentials -->
        <div class="col-md-12">
            <div class="pool-detail-card">
                <h5><i class="fas fa-key me-2"></i>Platform Credentials</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="detail-row">
                            <span class="detail-label">Platform Login:</span>
                            <span class="detail-value">{{ $pool->platform_login ?? 'Not specified' }}</span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Platform Password:</span>
                            <span class="detail-value">
                                @if($pool->platform_password)
                                    <span class="masked-field" id="platform-password">••••••••</span>
                                    <span class="show-password" onclick="togglePassword('platform-password', '{{ $pool->platform_password }}')">Show</span>
                                @else
                                    <span class="empty-value">Not specified</span>
                                @endif
                            </span>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="detail-row">
                            <span class="detail-label">Sequencer Login:</span>
                            <span class="detail-value">{{ $pool->sequencer_login ?? 'Not specified' }}</span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Sequencer Password:</span>
                            <span class="detail-value">
                                @if($pool->sequencer_password)
                                    <span class="masked-field" id="sequencer-password">••••••••</span>
                                    <span class="show-password" onclick="togglePassword('sequencer-password', '{{ $pool->sequencer_password }}')">Show</span>
                                @else
                                    <span class="empty-value">Not specified</span>
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
                
                @if($pool->backup_codes)
                <div class="detail-row">
                    <span class="detail-label">Backup Codes:</span>
                    <span class="detail-value">
                        <pre class="bg-dark p-2 rounded">{{ $pool->backup_codes }}</pre>
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Additional Information -->
        @if($pool->shared_note || $pool->additional_info)
        <div class="col-md-12">
            <div class="pool-detail-card">
                <h5><i class="fas fa-sticky-note me-2"></i>Additional Information</h5>
                
                @if($pool->shared_note)
                <div class="detail-row">
                    <span class="detail-label">Shared Note:</span>
                    <span class="detail-value">{{ $pool->shared_note }}</span>
                </div>
                @endif
                
                @if($pool->additional_info)
                <div class="detail-row">
                    <span class="detail-label">Additional Info:</span>
                    <span class="detail-value">{{ $pool->additional_info }}</span>
                </div>
                @endif
                
                @if($pool->reassignment_note)
                <div class="detail-row">
                    <span class="detail-label">Reassignment Note:</span>
                    <span class="detail-value">{{ $pool->reassignment_note }}</span>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Timestamps -->
        <div class="col-md-12">
            <div class="pool-detail-card">
                <h5><i class="fas fa-clock me-2"></i>Timeline</h5>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="detail-row">
                            <span class="detail-label">Created:</span>
                            <span class="detail-value">{{ $pool->created_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="detail-row">
                            <span class="detail-label">Updated:</span>
                            <span class="detail-value">{{ $pool->updated_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                    
                    @if($pool->completed_at)
                    <div class="col-md-4">
                        <div class="detail-row">
                            <span class="detail-label">Completed:</span>
                            <span class="detail-value">{{ $pool->completed_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                    @endif
                </div>
                
                @if($pool->timer_started_at)
                <div class="row">
                    <div class="col-md-4">
                        <div class="detail-row">
                            <span class="detail-label">Timer Started:</span>
                            <span class="detail-value">{{ $pool->timer_started_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                    
                    @if($pool->timer_paused_at)
                    <div class="col-md-4">
                        <div class="detail-row">
                            <span class="detail-label">Timer Paused:</span>
                            <span class="detail-value">{{ $pool->timer_paused_at->format('M d, Y H:i') }}</span>
                        </div>
                    </div>
                    @endif
                    
                    @if($pool->total_paused_seconds > 0)
                    <div class="col-md-4">
                        <div class="detail-row">
                            <span class="detail-label">Total Paused:</span>
                            <span class="detail-value">{{ gmdate('H:i:s', $pool->total_paused_seconds) }}</span>
                        </div>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Pool Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to update the pool status to <span id="newStatusText"></span>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStatusUpdate">Update Status</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let newStatus = null;

function updateStatus(status) {
    newStatus = status;
    document.getElementById('newStatusText').textContent = status.replace('_', ' ').toUpperCase();
    $('#statusModal').modal('show');
}

document.getElementById('confirmStatusUpdate').addEventListener('click', function() {
    if (newStatus) {
        fetch(`/admin/pools/{{ $pool->id }}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                status: newStatus,
                user_id: {{ $pool->user_id }},
                plan_id: {{ $pool->plan_id ?? 'null' }}
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating status');
        });
    }
    $('#statusModal').modal('hide');
});

function togglePassword(elementId, password) {
    const element = document.getElementById(elementId);
    const showButton = element.nextElementSibling;
    
    if (element.textContent === '••••••••') {
        element.textContent = password;
        showButton.textContent = 'Hide';
    } else {
        element.textContent = '••••••••';
        showButton.textContent = 'Show';
    }
}
</script>
@endpush