@extends('admin.layouts.app')
@section('title', 'Pool Details')

@section('content')
<section class="py-3 overflow-hidden">
    @php
    $defaultImage = 'https://cdn-icons-png.flaticon.com/128/300/300221.png';
    $defaultProfilePic =
    'https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600';
    @endphp


    <div class="d-flex align-items-center justify-content-between mt-3">
        <div>
            <div style="cursor: pointer; display: inline-flex; align-items: center; margin-bottom: 10px;">
                <a href="{{ route('admin.pools.index') }}"
                    style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #2f3349; border-radius: 50%; text-decoration: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </a>
            </div>
            <h5 class="mb-3">Pool #{{ $pool->id ?? 'N/A' }}</h5>
            <h6><span class="opacity-50 fs-6">Created Date:</span>
                {{ $pool->created_at ? $pool->created_at->format('M d, Y') : 'N/A' }}</h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div
                class="border border-{{ $pool->status == 'cancelled' ? 'warning' : ($pool->status == 'completed' ? 'success' : 'primary') }} rounded-2 py-1 px-2 text-{{ $pool->status == 'cancelled' ? 'warning' : ($pool->status == 'completed' ? 'success' : 'primary') }} bg-transparent">
                {{ ucfirst(str_replace('_', ' ', $pool->status ?? '')) }}
            </div>
            @if($pool->assigned_to)
            <button class="btn btn-sm btn-outline-primary ms-2" data-bs-toggle="modal" data-bs-target="#reassignHelperModal">
                <i class="fa fa-user-edit"></i> Reassign Helper
            </button>
            @endif
        </div>
    </div>

    
    <!-- Reassign Helper Modal -->
    <div class="modal fade" id="reassignHelperModal" tabindex="-1" aria-labelledby="reassignHelperModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="reassignHelperForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="reassignHelperModalLabel">Reassign Helper</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body"> 
                        <div class="mb-3">
                            <label for="helperSelect" class="form-label">Select Helper</label>
                           <select class="form-select" id="helperSelect" name="helper_id" required>
                        <option value="">-- Select Helper --</option>
                        @php
                            $roleNames = ['Teams Leader', 'contractor']; // add any roles you want
                            $helpers = App\Models\User::whereHas('role', function($q) use ($roleNames) {
                                    $q->whereIn('name', $roleNames);
                                })
                                ->orWhereHas('roles', function($q) use ($roleNames) { // spatie roles
                                    $q->whereIn('name', $roleNames);
                                })
                                ->get();
                        @endphp

                        @foreach($helpers as $helper)
                            <option value="{{ $helper->id }}" 
                                @if($pool->assigned_to == $helper->id) selected @endif>
                                {{ $helper->name }} ({{ $helper->email }})
                            </option>
                        @endforeach
                    </select>

                        </div>
                            <input type="text" name="reassignment_note" id="reassignment_note" class="form-control" placeholder="Enter reason for reassignment..." required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Reassign</button>
                    </div>
                </div>
            </form>
        </div>
    </div>




    <ul class="nav nav-tabs order_view d-flex align-items-center justify-content-between" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link fs-6 px-5 active" id="pool-details-tab" data-bs-toggle="tab"
                data-bs-target="#pool-details-tab-pane" type="button" role="tab" aria-controls="pool-details-tab-pane"
                aria-selected="true">Pool Details</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fs-6 px-5" id="domains-tab" data-bs-toggle="tab" data-bs-target="#domains-tab-pane"
                type="button" role="tab" aria-controls="domains-tab-pane" aria-selected="false">Domains</button>
        </li>
        @if($pool->helpers && count($pool->helpers) > 0)
        <li class="nav-item" role="presentation">
            <button class="nav-link fs-6 px-5" id="helpers-tab" data-bs-toggle="tab" data-bs-target="#helpers-tab-pane"
                type="button" role="tab" aria-controls="helpers-tab-pane" aria-selected="false">Helpers</button>
        </li>
        @endif
    </ul>

    <div class="tab-content mt-3" id="myTabContent">
        <div class="tab-pane fade show active" id="pool-details-tab-pane" role="tabpanel"
            aria-labelledby="pool-details-tab" tabindex="0">
            <div class="row">
                <div class="col-md-6">
                    <div class="card p-3 mb-3">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-swimming-pool"></i>
                            </div>
                            Pool configurations
                        </h6>

                        @if ($pool)
                        <div class="d-flex align-items-center justify-content-between">
                            @php
                            $poolDomains = $pool->domains ?? [];
                            $totalDomains = count($poolDomains);
                            @endphp
                            <span>Total Domains <br> {{ $totalDomains }}</span>
                            <span>Pool Type <br>
                                {{ $pool->is_internal ? 'Internal' : 'External' }}</span>
                        </div>
                        <hr>
                        <div class="d-flex flex-column">
                            <span class="opacity-50">Pool Name</span>
                            <span>{{ $pool->name ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50">Description</span>
                            <span>{{ $pool->description ?? 'N/A' }}</span>
                        </div>
                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50">Status</span>
                            <span>{{ ucfirst(str_replace('_', ' ', $pool->status)) }}</span>
                        </div>
                        @if($pool->assigned_to)
                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50">Assigned To</span>
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $pool->assignedTo->profile_image ?? $defaultProfilePic }}" 
                                     width="30" height="30" class="rounded-circle" alt="Profile">
                                <span>{{ $pool->assignedTo->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        @endif
                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50">Created By</span>
                            <div class="d-flex align-items-center gap-2">
                                <img src="{{ $pool->user->profile_image ?? $defaultProfilePic }}" 
                                     width="30" height="30" class="rounded-circle" alt="Profile">
                                <span>{{ $pool->user->name ?? 'N/A' }}</span>
                            </div>
                        </div>
                        @else
                        <div class="text-muted">No pool configuration data available</div>
                        @endif
                    </div>

                    <div class="price-display-section card p-3">
                        @if($pool)
                        <div class="d-flex align-items-center gap-3">
                            <div>
                                <img src="{{ $defaultImage }}" width="30" alt="Pool Icon">
                            </div>
                            <div>
                                <span class="opacity-50">Pool Domain Collection</span>
                                <br>
                                <span>({{ count($pool->domains ?? []) }} domains)</span>
                            </div>
                        </div>
                        <h6 class="my-3 small"><span class="theme-text">Pool Type:</span> {{ $pool->is_internal ? 'Internal' : 'External' }}</h6>
                        <h6 class="small"><span class="theme-text">Status:</span> {{ ucfirst(str_replace('_', ' ', $pool->status)) }}</h6>
                        @if($pool->is_shared)
                        <h6 class="small"><span class="theme-text">Shared:</span> Yes</h6>
                        @endif
                        @else
                        <h6><span class="theme-text">Pool Type:</span> <small>No pool data available</small>
                        </h6>
                        <h6><span class="theme-text">Status:</span> <small>No pool data available</small></h6>
                        @endif
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3 overflow-y-auto" style="max-height: 30rem">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-earth-europe"></i>
                            </div>
                            Domains & Configuration
                        </h6>

                        @if ($pool && count($pool->domains ?? []) > 0)
                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Pool Settings</span>
                            <span>Shared: {{ $pool->is_shared ? 'Yes' : 'No' }}</span>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Pool Type</span>
                            <span>{{ $pool->is_internal ? 'Internal' : 'External' }}</span>
                        </div>

                        @if($pool->notes)
                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Notes</span>
                            <span>{{ $pool->notes }}</span>
                        </div>
                        @endif

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Created Date</span>
                            <span>{{ $pool->created_at->format('M d, Y H:i A') }}</span>
                        </div>

                        @if($pool->updated_at != $pool->created_at)
                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Last Updated</span>
                            <span>{{ $pool->updated_at->format('M d, Y H:i A') }}</span>
                        </div>
                        @endif

                        <div class="d-flex flex-column">
                            <span class="opacity-50">Domains</span>
                            @php
                            $poolDomains = $pool->domains ?? [];
                            @endphp

                            @foreach (array_slice($poolDomains, 0, 10) as $domain)
                            <span class="d-block">{{ $domain['name'] ?? 'N/A' }}</span>
                            @endforeach
                            @if(count($poolDomains) > 10)
                            <small class="text-muted mt-2">... and {{ count($poolDomains) - 10 }} more domains</small>
                            @endif
                        </div>
                        @else
                        <div class="text-muted">No configuration information available</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Domains Tab -->
        <div class="tab-pane fade" id="domains-tab-pane" role="tabpanel" aria-labelledby="domains-tab" tabindex="0">
            <div class="col-12">
                <div class="card p-3">
                    <h6 class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center"
                            style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                            <i class="fa-solid fa-earth-europe"></i>
                        </div>
                        Pool Domains
                    </h6>

                    @php
                    $poolDomains = $pool->domains ?? [];
                    @endphp

                    @if(count($poolDomains) > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Domain ID</th>
                                    <th>Domain Name</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($poolDomains as $index => $domain)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td><span class="badge bg-secondary">{{ $domain['id'] ?? 'N/A' }}</span></td>
                                    <td><strong>{{ $domain['name'] ?? 'N/A' }}</strong></td>
                                    <td>
                                        <!-- is_used -->
                                        @if($domain['is_used'])
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center text-muted py-4">
                        <i class="fa-solid fa-inbox fa-3x mb-3"></i>
                        <p>No domains assigned to this pool</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Helpers Tab -->
        @if($pool->helpers && count($pool->helpers) > 0)
        <div class="tab-pane fade" id="helpers-tab-pane" role="tabpanel" aria-labelledby="helpers-tab" tabindex="0">
            <div class="col-12">
                <div class="card p-3">
                    <h6 class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center"
                            style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        Pool Helpers
                    </h6>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Helper</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pool->helpers as $helper)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <img src="{{ $helper->profile_image ?? $defaultProfilePic }}" 
                                                 width="40" height="40" class="rounded-circle" alt="Helper">
                                            <div>
                                                <strong>{{ $helper->name }}</strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $helper->email }}</td>
                                    <td>{{ $helper->phone ?? 'N/A' }}</td>
                                    <td><span class="badge bg-info">Helper</span></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>

@push('scripts')
<script>
$(function() {
    $('#reassignHelperForm').on('submit', function(e) {
        e.preventDefault();
        var helperId = $('#helperSelect').val();
        if (!helperId) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Please select a helper',
                confirmButtonColor: '#3085d6'
            });
            return;
        }
        
        performReassignment(helperId);
    });

    function performReassignment(helperId) {
        $.ajax({
            url: '/admin/pools/{{ $pool->id }}/reassign-helper',
            method: 'POST',
            data: {
                 helper_id: helperId,
                 reassignment_note: $('#reassignment_note').val(),
                _token: '{{ csrf_token() }}'
            },
            beforeSend: function() {
                Swal.fire({
                    title: 'Reassigning Helper...',
                    text: 'Please wait while we process your request',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Helper reassigned successfully',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    $('#reassignHelperModal').modal('hide');
                    location.reload();
                });
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'Failed to reassign helper';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: msg,
                    confirmButtonColor: '#3085d6'
                });
            }
        });
    }
});
</script>
@endpush
@endsection