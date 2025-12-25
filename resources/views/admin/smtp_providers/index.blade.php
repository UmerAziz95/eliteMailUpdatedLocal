@extends('admin.layouts.app')

@section('title', 'SMTP Providers')

@push('styles')
    <style>
        .provider-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .provider-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(90, 73, 205, 0.1), rgba(90, 73, 205, 0.05));
            border: 1px solid var(--border-color);
        }

        .provider-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .badge-active {
            background-color: #28a745;
        }

        .badge-inactive {
            background-color: #6c757d;
        }

        .counters {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        /* Fix text-muted visibility on dark theme */
        .provider-card .text-muted {
            color: rgba(255, 255, 255, 0.6) !important;
        }
    </style>
@endpush

@section('content')
    <section class="py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1"><i class="fa-solid fa-server me-2"></i>SMTP Providers</h4>
                <p class="text-muted mb-0">Manage your SMTP email providers and view associated pools</p>
            </div>
            <button class="btn btn-primary border-0" data-bs-toggle="modal" data-bs-target="#addProviderModal">
                <i class="fa-solid fa-plus me-2"></i>Add Provider
            </button>
        </div>

        <!-- Summary Stats -->
        <div class="counters mb-4">
            <div class="card p-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <h6 class="text-heading mb-1">Total Providers</h6>
                        <h4 class="mb-0" style="color: var(--second-primary);">{{ $totalProviders }}</h4>
                        <small class="text-success">{{ $activeProviders }} active</small>
                    </div>
                    <div class="avatar">
                        <i class="fa-solid fa-server fs-2" style="color: var(--second-primary);"></i>
                    </div>
                </div>
            </div>

            <div class="card p-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <h6 class="text-heading mb-1">Total Pools</h6>
                        <h4 class="mb-0" style="color: var(--second-primary);">{{ $totalPools }}</h4>
                        <small class="text-muted">SMTP pools</small>
                    </div>
                    <div class="avatar">
                        <i class="fa-solid fa-layer-group fs-2" style="color: var(--second-primary);"></i>
                    </div>
                </div>
            </div>

            <div class="card p-3">
                <div class="d-flex align-items-start justify-content-between">
                    <div>
                        <h6 class="text-heading mb-1">Total Emails</h6>
                        <h4 class="mb-0" style="color: var(--second-primary);">{{ $totalEmails }}</h4>
                        <small class="text-muted">Email accounts</small>
                    </div>
                    <div class="avatar">
                        <i class="fa-solid fa-envelope fs-2" style="color: var(--second-primary);"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Providers Grid -->
        @if($providers->count() > 0)
            <div class="provider-grid">
                @foreach($providers as $provider)
                    <div class="card provider-card"
                        onclick="window.location.href='{{ route('admin.smtp-providers.show', $provider->id) }}'">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1">{{ $provider->name }}</h5>
                                    @if($provider->url)
                                        <a href="{{ $provider->url }}" target="_blank" class="text-muted small"
                                            onclick="event.stopPropagation();">
                                            {{ $provider->url }}
                                        </a>
                                    @endif
                                </div>
                                <span class="badge {{ $provider->is_active ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $provider->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>

                            <div class="d-flex gap-4 mt-3">
                                <div>
                                    <small class="text-muted d-block">Pools</small>
                                    <strong class="fs-5" style="color: var(--second-primary);">{{ $provider->pools_count }}</strong>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Emails</small>
                                    <strong class="fs-5"
                                        style="color: var(--second-primary);">{{ $provider->total_emails }}</strong>
                                </div>
                            </div>

                            <div class="mt-3 text-end">
                                <small class="text-muted">
                                    <i class="fa-solid fa-arrow-right me-1"></i>View Details
                                </small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="card p-5 text-center">
                <i class="fa-solid fa-server fs-1 text-muted mb-3"></i>
                <h5>No SMTP Providers Yet</h5>
                <p class="text-muted mb-3">Create your first SMTP provider to start managing email accounts.</p>
                <button class="btn btn-primary border-0" data-bs-toggle="modal" data-bs-target="#addProviderModal">
                    <i class="fa-solid fa-plus me-2"></i>Add Provider
                </button>
            </div>
        @endif
    </section>

    <!-- Add Provider Modal -->
    <div class="modal fade" id="addProviderModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fa-solid fa-plus me-2"></i>Add SMTP Provider</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addProviderForm">
                        <div class="mb-3">
                            <label class="form-label">Provider Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required
                                placeholder="e.g. Mailgun, SendGrid">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Provider URL</label>
                            <input type="url" class="form-control" name="url" placeholder="https://provider.example.com">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary border-0" onclick="saveProvider()">
                        <i class="fa-solid fa-save me-1"></i>Save Provider
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function saveProvider() {
            const form = document.getElementById('addProviderForm');
            const formData = new FormData(form);

            fetch('{{ route('admin.smtp-providers.store') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    name: formData.get('name'),
                    url: formData.get('url')
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                            timer: 1500
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to create provider'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                });
        }
    </script>
@endpush