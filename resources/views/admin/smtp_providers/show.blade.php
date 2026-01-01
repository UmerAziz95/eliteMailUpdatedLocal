@extends('admin.layouts.app')

@section('title', $smtpProvider->name . ' - SMTP Provider')

@push('styles')
    <style>
        .pool-card {
            transition: all 0.3s ease;
        }

        .pool-card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .email-table {
            font-size: 0.85rem;
            background-color: transparent !important;
        }

        .email-table th {
            color: rgba(255, 255, 255, 0.7) !important;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.75rem;
            border-bottom: 1px solid var(--border-color) !important;
            background-color: var(--secondary-color) !important;
        }

        .email-table td {
            color: rgba(255, 255, 255, 0.9) !important;
            border-bottom: 1px solid var(--border-color) !important;
            background-color: transparent !important;
        }

        .email-table tbody tr {
            background-color: transparent !important;
        }

        .email-table tbody tr:hover {
            background-color: rgba(74, 58, 255, 0.1) !important;
        }

        .accordion-item {
            background-color: transparent !important;
            border: none !important;
        }

        .accordion-button {
            background-color: var(--secondary-color) !important;
            color: var(--light-color) !important;
        }

        .accordion-button:not(.collapsed) {
            background-color: var(--second-primary) !important;
            color: #fff !important;
        }

        .accordion-button::after {
            filter: invert(1);
        }

        .accordion-body {
            background-color: var(--primary-bg, #0f0f23) !important;
            color: var(--light-color) !important;
        }

        .accordion-collapse {
            background-color: var(--primary-bg, #0f0f23) !important;
        }

        /* Fix pool info row styling */
        .pool-info-row {
            color: rgba(255, 255, 255, 0.9);
        }

        .pool-info-row . {
            color: rgba(255, 255, 255, 0.6) !important;
        }

        /* Table wrapper */
        .table-responsive {
            background-color: transparent !important;
        }
    </style>
@endpush

@section('content')
    <section class="py-3">
        <!-- Breadcrumb & Header -->
        <div class="mb-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-2">
                    <li class="breadcrumb-item">
                        <a href="{{ route('admin.smtp-providers.page') }}" class="text-decoration-none">
                            <i class="fa-solid fa-server me-1"></i>SMTP Providers
                        </a>
                    </li>
                    <li class="breadcrumb-item active">{{ $smtpProvider->name }}</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="mb-1">
                        {{ $smtpProvider->name }}
                        <span class="badge {{ $smtpProvider->is_active ? 'bg-success' : 'bg-secondary' }} ms-2">
                            {{ $smtpProvider->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </h4>
                    @if($smtpProvider->url)
                        <a href="{{ $smtpProvider->url }}" target="_blank" class="">
                            <i class="fa-solid fa-external-link me-1"></i>{{ $smtpProvider->url }}
                        </a>
                    @endif
                </div>
                <a href="{{ route('admin.smtp-providers.page') }}" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-1"></i>Back
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card p-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fa-solid fa-layer-group fs-2" style="color: var(--second-primary);"></i>
                        </div>
                        <div>
                            <small class=" d-block">Total Pools</small>
                            <h4 class="mb-0" style="color: var(--second-primary);">{{ $smtpProvider->pools->count() }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fa-solid fa-envelope fs-2" style="color: var(--second-primary);"></i>
                        </div>
                        <div>
                            <small class=" d-block">Total Email Accounts</small>
                            <h4 class="mb-0" style="color: var(--second-primary);">{{ $totalEmails }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fa-solid fa-globe fs-2" style="color: var(--second-primary);"></i>
                        </div>
                        <div>
                            <small class=" d-block">Unique Domains</small>
                            <h4 class="mb-0" style="color: var(--second-primary);">{{ count($uniqueDomains ?? []) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pools Accordion -->
        @if($smtpProvider->pools->count() > 0)
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fa-solid fa-layer-group me-2"></i>Pools</h5>
                </div>
                <div class="card-body p-0">
                    <div class="accordion" id="poolsAccordion">
                        @foreach($smtpProvider->pools as $index => $pool)
                            @php
                                // Get accounts from controller (supports both smtp_accounts_data and domains+prefix_variants)
                                $accounts = $poolAccountsMap[$pool->id] ?? [];
                                $poolDomains = array_unique(array_column($accounts, 'domain'));
                            @endphp
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#pool-{{ $pool->id }}">
                                        <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                            <div>
                                                <strong>Pool #{{ $pool->id }}</strong>
                                                @if($pool->user)
                                                    <span class=" ms-2">- {{ $pool->user->name }}</span>
                                                @endif
                                            </div>
                                            <div class="d-flex gap-3">
                                                <span class="badge bg-primary">{{ count($accounts) }} emails</span>
                                                <span class="badge bg-info">{{ count($poolDomains) }} domains</span>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                <div id="pool-{{ $pool->id }}" class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}"
                                    data-bs-parent="#poolsAccordion">
                                    <div class="accordion-body">
                                        <!-- Pool Info -->
                                        <div class="row mb-3 pool-info-row">
                                            <div class="col-md-2">
                                                <small class="">Customer</small>
                                                <div>{{ $pool->user->name ?? 'N/A' }}</div>
                                            </div>
                                            <div class="col-md-2">
                                                <small class="">Status</small>
                                                <div>
                                                    <span
                                                        class="badge bg-{{ $pool->status === 'completed' ? 'success' : ($pool->status === 'pending' ? 'warning' : 'secondary') }}">
                                                        {{ ucfirst($pool->status) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <small class="">Provider Type</small>
                                                <div>
                                                    @php
                                                        $providerType = $pool->provider_type ?? 'SMTP';
                                                        $badgeClass = $providerType === 'Google' ? 'bg-danger' : ($providerType === 'Microsoft 365' ? 'bg-primary' : 'bg-warning text-dark');
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }}">
                                                        {{ $providerType }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <small class="">Created</small>
                                                <div>{{ $pool->created_at->format('M d, Y') }}</div>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <button class="btn btn-sm btn-outline-warning"
                                                        onclick="openChangeProviderModal({{ $pool->id }}, '{{ addslashes($pool->user->name ?? 'Pool #' . $pool->id) }}')"
                                                        title="Change SMTP Provider (sub-provider)">
                                                        <i class="fa-solid fa-exchange-alt me-1"></i>Change SMTP Provider
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-info"
                                                        onclick="openChangeProviderTypeModal({{ $pool->id }}, '{{ $pool->provider_type ?? 'SMTP' }}')"
                                                        title="Change Provider Type (SMTP ↔ Google/365)">
                                                        <i class="fa-solid fa-sync-alt me-1"></i>Change Provider
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Email Accounts Table -->
                                        @if(count($accounts) > 0)
                                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                <table class="table table-sm email-table mb-0">
                                                    <thead style="position: sticky; top: 0; background: var(--secondary-color);">
                                                        <tr>
                                                            <th>#</th>
                                                            <th>First Name</th>
                                                            <th>Last Name</th>
                                                            <th>Email</th>
                                                            <th>Domain</th>
                                                            <th>Password</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($accounts as $i => $account)
                                                            <tr>
                                                                <td>{{ $i + 1 }}</td>
                                                                <td>{{ $account['first_name'] ?? '-' }}</td>
                                                                <td>{{ $account['last_name'] ?? '-' }}</td>
                                                                <td>
                                                                    <span style="color: #ff6b9d;">{{ $account['email'] ?? '-' }}</span>
                                                                </td>
                                                                <td>
                                                                    <span class="badge" style="background-color: var(--second-primary);">
                                                                        {{ $account['domain'] ?? '-' }}
                                                                    </span>
                                                                </td>
                                                                <td style="color: rgba(255,255,255,0.6);">{{ $account['password'] ?? '-' }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-3 ">
                                                <i class="fa-solid fa-inbox me-1"></i>No email accounts in this pool
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="card p-5 text-center">
                <i class="fa-solid fa-layer-group fs-1  mb-3"></i>
                <h5>No Pools</h5>
                <p class="">This provider doesn't have any associated pools yet.</p>
            </div>
        @endif
    </section>

    <!-- Change Provider Modal -->
    <div class="modal fade" id="changeProviderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-exchange-alt me-2"></i>Change SMTP Provider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="change_pool_id">
                    <p class="mb-3">Change SMTP provider for: <strong id="change_pool_name"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Current Provider</label>
                        <input type="text" class="form-control" value="{{ $smtpProvider->name }}" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Provider <span class="text-danger">*</span></label>
                        <select class="form-select" id="new_provider_id" required>
                            <option value="">Select a provider...</option>
                            @foreach($allProviders as $provider)
                                @if($provider->id !== $smtpProvider->id)
                                    <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="changeProvider()">
                        <i class="fa-solid fa-exchange-alt me-1"></i>Change Provider
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Provider Type Modal -->
    <div class="modal fade" id="changeProviderTypeModal" tabindex="-1" aria-labelledby="changeProviderTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changeProviderTypeModalLabel">
                        <i class="fas fa-exchange-alt me-2"></i>
                        Change Provider Type
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pool ID</label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary" id="providerModalPoolId">#</span>
                            <span>Current:</span>
                            <span class="badge" id="providerModalCurrentType">None</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="newProviderType" class="form-label">Select Provider Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="newProviderType" required>
                            <option value="">-- Select Provider Type --</option>
                            <option value="Google">Google</option>
                            <option value="Microsoft 365">Microsoft 365</option>
                            <option value="SMTP">SMTP</option>
                        </select>
                    </div>
                    <!-- SMTP Provider Selection (shown only when SMTP is selected) -->
                    <div class="mb-3" id="smtpProviderSelection" style="display: none;">
                        <label for="smtpProviderId" class="form-label">Select SMTP Provider <span class="text-danger">*</span></label>
                        <select class="form-select" id="smtpProviderId" style="width: 100%;">
                            <option value="">-- Select SMTP Provider --</option>
                            @foreach($allProviders ?? [] as $provider)
                                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="smtpProviderId-error"></div>
                        <p class="note mb-0 mt-1">(Required when migrating to SMTP - pool will be removed from panels)</p>
                    </div>
                    <div class="mb-3 d-none">
                        <label for="providerChangeReason" class="form-label">Reason for Change (Optional)</label>
                        <textarea class="form-control" id="providerChangeReason" rows="3" placeholder="Enter reason for provider type change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmProviderTypeChange">
                        <i class="fas fa-save me-1"></i>
                        Update Provider Type
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openChangeProviderModal(poolId, poolName) {
            $('#change_pool_id').val(poolId);
            $('#change_pool_name').text(poolName);
            $('#new_provider_id').val('');
            $('#changeProviderModal').modal('show');
        }

        function changeProvider() {
            const poolId = $('#change_pool_id').val();
            const newProviderId = $('#new_provider_id').val();

            if (!newProviderId) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Select Provider',
                    text: 'Please select a new SMTP provider.'
                });
                return;
            }

            Swal.fire({
                title: 'Change Provider?',
                text: 'Are you sure you want to change the SMTP provider for this pool?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ffc107',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, change it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('admin/pools') }}/${poolId}/change-provider`,
                        type: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        data: JSON.stringify({
                            smtp_provider_id: newProviderId
                        }),
                        success: function (data) {
                            if (data.success) {
                                $('#changeProviderModal').modal('hide');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Provider Changed!',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Failed to change provider'
                                });
                            }
                        },
                        error: function (xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'An error occurred. Please try again.'
                            });
                        }
                    });
                }
            });
        }

        /**
         * Open Change Provider Type Modal
         */
        function openChangeProviderTypeModal(poolId, currentProviderType) {
            console.log('openChangeProviderTypeModal called', { poolId, currentProviderType });
            const modalEl = document.getElementById('changeProviderTypeModal');
            if (!modalEl) {
                console.error('Modal element not found: changeProviderTypeModal');
                Swal.fire({
                    title: 'Error',
                    text: 'Modal not found. Please refresh the page.',
                    icon: 'error'
                });
                return;
            }

            // Update modal content
            const poolLabel = document.getElementById('providerModalPoolId');
            if (poolLabel) {
                poolLabel.textContent = '#' + poolId;
            }

            const currentBadge = document.getElementById('providerModalCurrentType');
            if (currentBadge) {
                if (currentProviderType) {
                    currentBadge.textContent = currentProviderType;
                    if (currentProviderType === 'Google') {
                        currentBadge.className = 'badge bg-danger';
                    } else if (currentProviderType === 'Microsoft 365') {
                        currentBadge.className = 'badge bg-primary';
                    } else if (currentProviderType === 'SMTP') {
                        currentBadge.className = 'badge bg-warning text-dark';
                    } else {
                        currentBadge.className = 'badge bg-secondary';
                    }
                } else {
                    currentBadge.textContent = 'SMTP';
                    currentBadge.className = 'badge bg-warning text-dark';
                }
            }

            // Reset form
            const providerSelect = document.getElementById('newProviderType');
            const reasonField = document.getElementById('providerChangeReason');
            const smtpProviderSelect = document.getElementById('smtpProviderId');
            const smtpProviderContainer = document.getElementById('smtpProviderSelection');

            if (providerSelect) providerSelect.value = '';
            if (reasonField) reasonField.value = '';
            if (smtpProviderSelect) smtpProviderSelect.value = '';
            if (smtpProviderContainer) smtpProviderContainer.style.display = 'none';

            // Store pool ID in modal for later use
            modalEl.setAttribute('data-pool-id', poolId);
            modalEl.setAttribute('data-current-provider', currentProviderType || 'SMTP');

            // Show modal
            try {
                const modal = new bootstrap.Modal(modalEl);
                modal.show();
                console.log('Modal shown successfully');
            } catch (error) {
                console.error('Error showing modal:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'Failed to open modal: ' + error.message,
                    icon: 'error'
                });
            }
        }

        /**
         * Update Provider Type for Pool
         */
        async function updatePoolProviderType() {
            const modal = document.getElementById('changeProviderTypeModal');
            if (!modal) return;

            const poolId = modal.getAttribute('data-pool-id');
            const providerSelect = document.getElementById('newProviderType');
            const reasonField = document.getElementById('providerChangeReason');
            const smtpProviderSelect = document.getElementById('smtpProviderId');

            if (!providerSelect || !poolId) return;

            const newProviderType = providerSelect.value;
            const reason = reasonField ? reasonField.value : '';
            const smtpProviderId = smtpProviderSelect ? smtpProviderSelect.value : null;

            if (!newProviderType) {
                Swal.fire({
                    title: 'Provider Type Required',
                    text: 'Please select a provider type.',
                    icon: 'warning',
                    confirmButtonColor: '#ffc107'
                });
                return;
            }

            // Validate SMTP provider if SMTP is selected
            if (newProviderType === 'SMTP' && !smtpProviderId) {
                Swal.fire({
                    title: 'SMTP Provider Required',
                    text: 'Please select an SMTP provider when migrating to SMTP.',
                    icon: 'warning',
                    confirmButtonColor: '#ffc107'
                });
                if (smtpProviderSelect) {
                    smtpProviderSelect.classList.add('is-invalid');
                }
                return;
            }

            // Show loading
            Swal.fire({
                title: 'Updating Provider Type...',
                text: 'Please wait while we update the provider type.',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const response = await fetch(`/admin/pool/${poolId}/change-provider-type`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        provider_type: newProviderType,
                        reason: reason,
                        smtp_provider_id: smtpProviderId
                    })
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Failed to update provider type');
                }

                if (result.success) {
                    await Swal.fire({
                        title: 'Success!',
                        text: result.message || 'Provider type updated successfully!',
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true
                    });

                    // Close modal
                    const modalInstance = bootstrap.Modal.getInstance(modal);
                    if (modalInstance) {
                        modalInstance.hide();
                    }

                    // Reload the page to show updated data
                    location.reload();
                } else {
                    throw new Error(result.message || 'Failed to update provider type');
                }
            } catch (error) {
                console.error('Error updating provider type:', error);
                Swal.fire({
                    title: 'Error',
                    text: error.message || 'An error occurred while updating the provider type',
                    icon: 'error',
                    confirmButtonColor: '#dc3545'
                });
            }
        }

        // Initialize event listeners when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const confirmBtn = document.getElementById('confirmProviderTypeChange');
            if (confirmBtn) {
                confirmBtn.addEventListener('click', updatePoolProviderType);
            }

            // Handle provider type change to show/hide SMTP provider selection
            const providerSelect = document.getElementById('newProviderType');
            const smtpContainer = document.getElementById('smtpProviderSelection');
            const smtpSelect = document.getElementById('smtpProviderId');
            
            if (providerSelect && smtpContainer && smtpSelect) {
                // Use event delegation on the modal
                const modal = document.getElementById('changeProviderTypeModal');
                if (modal) {
                    modal.addEventListener('change', function(e) {
                        if (e.target && e.target.id === 'newProviderType') {
                            if (e.target.value === 'SMTP') {
                                smtpContainer.style.display = 'block';
                                smtpSelect.required = true;
                            } else {
                                smtpContainer.style.display = 'none';
                                smtpSelect.required = false;
                                smtpSelect.value = '';
                                smtpSelect.classList.remove('is-invalid');
                            }
                        }
                    });
                }
            }
        });
    </script>
@endpush