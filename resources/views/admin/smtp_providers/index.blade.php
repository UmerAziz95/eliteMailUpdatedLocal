@extends('admin.layouts.app')

@section('title', 'SMTP Providers')

@push('styles')
    <style>
        .counters {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .provider-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .provider-card {
            transition: all 0.3s ease;
        }

        .provider-card:hover {
            transform: translateY(-5px);
        }

        .provider-card .card-body {
            padding: 1.25rem;
        }

        .provider-card .card-header {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid var(--border-color);
            background: transparent;
        }

        .provider-card .card-footer {
            padding: 0.75rem 1.25rem;
            border-top: 1px solid var(--border-color);
            background: transparent;
        }

        .provider-card .provider-icon {
            width: 45px;
            height: 45px;
            min-width: 45px;
            min-height: 45px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: rgba(90, 73, 205, 0.2);
        }

        .provider-card .provider-icon i {
            color: var(--second-primary);
        }

        .status-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-weight: 500;
        }

        .badge-archived {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .badge-archived:hover {
            background-color: rgba(255, 193, 7, 0.4);
        }

        .badge-unarchived {
            background-color: rgba(25, 135, 84, 0.2);
            color: #198754;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .badge-unarchived:hover {
            background-color: rgba(25, 135, 84, 0.4);
        }

        .stat-item {
            text-align: center;
            padding: 0.5rem 1rem;
            background-color: rgba(90, 73, 205, 0.1);
            border-radius: 8px;
        }

        .stat-item .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--second-primary);
        }

        .stat-item .stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            color: var(--extra-light);
            opacity: 0.7;
        }

        .provider-card .provider-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--light-color);
            margin-bottom: 0.25rem;
        }

        .provider-card .provider-url {
            font-size: 0.75rem;
            color: var(--second-primary);
            text-decoration: none;
            display: block;
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .provider-card .provider-url:hover {
            text-decoration: underline;
        }

        .action-btn {
            padding: 0.4rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 6px;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--second-primary);
            opacity: 0.5;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding-left: 2.25rem;
            background-color: var(--secondary-color);
            border: 1px solid var(--border-color);
            color: var(--light-color);
            width: 250px;
        }

        .search-box input:focus {
            border-color: var(--second-primary);
            box-shadow: none;
        }

        .search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--extra-light);
            opacity: 0.5;
        }

        /* Toggle switch colors */
        .form-check-input {
            background-color: #ffc107;
            border-color: #ffc107;
        }

        /* Green when checked (Unarchived) */
        .form-check-input:checked {
            background-color: #198754;
            border-color: #198754;
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
            border-color: #198754;
        }
    </style>
@endpush

@section('content')
    <section class="py-3">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="mb-1">SMTP Providers</h2>
                <p class="mb-0">Manage your SMTP email providers and view associated pools</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <div class="search-box">
                    <i class="fa fa-search"></i>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search providers..."
                        onkeyup="filterProviders()">
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProviderModal">
                    <i class="fas fa-plus me-2"></i>Add Provider
                </button>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="counters mb-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex flex-column justify-content-between">
                            <h6 class="mb-1 fw-semibold">Total Providers</h6>
                            <h4 class="mb-0">{{ $totalProviders }}</h4>
                            <small class="d-flex align-items-center gap-2">
                                <span class="text-success"><span class="fw-semibold" id="total_unarchived">{{ $providers->where('is_active', true)->count() }}</span> unarchived</span>
                                <span class="text-muted">•</span>
                                <span class="text-warning"><span class="fw-semibold" id="total_archived">{{ $providers->where('is_active', false)->count() }}</span> archived</span>
                            </small>
                        </div>
                        <div class="icon rounded p-2">
                            <i class="fa-solid fa-server fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex flex-column justify-content-between">
                            <h6 class="mb-1 fw-semibold">Total Pools</h6>
                            <h4 class="mb-0">{{ $totalPools }}</h4>
                            <small class="opacity-50">SMTP pools</small>
                        </div>
                        <div class="icon rounded p-2">
                            <i class="fa-solid fa-layer-group fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex flex-column justify-content-between">
                            <h6 class="mb-1 fw-semibold">Total Emails</h6>
                            <h4 class="mb-0">{{ $totalEmails }}</h4>
                            <small class="opacity-50">Email accounts</small>
                        </div>
                        <div class="icon rounded p-2">
                            <i class="fa-solid fa-envelope fs-5"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
            <ul class="nav nav-pills mb-3 border-0" id="providersTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white active"
                        id="unarchived-tab" data-bs-toggle="tab" data-bs-target="#unarchived-pane" type="button"
                        role="tab" aria-controls="unarchived-pane" aria-selected="true">
                        <i class="fa fa-check-circle me-1 text-success"></i>Unarchived
                        <span class="badge bg-success ms-1">{{ $providers->where('is_active', true)->count() }}</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button style="font-size: 13px" class="nav-link rounded-1 py-1 text-capitalize text-white"
                        id="archived-tab" data-bs-toggle="tab" data-bs-target="#archived-pane" type="button" role="tab"
                        aria-controls="archived-pane" aria-selected="false">
                        <i class="fa fa-archive me-1 text-warning"></i>Archived
                        <span class="badge bg-warning ms-1">{{ $providers->where('is_active', false)->count() }}</span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="providersTabContent">
                <!-- Unarchived Tab -->
                <div class="tab-pane fade show active" id="unarchived-pane" role="tabpanel" aria-labelledby="unarchived-tab" tabindex="0">
                    <div class="provider-grid" id="unarchivedGrid">
                        @php $unarchivedProviders = $providers->where('is_active', true); @endphp
                        @if($unarchivedProviders->count() > 0)
                            @foreach($unarchivedProviders as $provider)
                                <div class="card provider-card" data-name="{{ strtolower($provider->name) }}" data-url="{{ strtolower($provider->url ?? '') }}">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="provider-icon">
                                                <i class="fa-solid fa-server fs-5"></i>
                                            </div>
                                            <div>
                                                <h6 class="provider-name mb-0">{{ $provider->name }}</h6>
                                                @if($provider->url)
                                                    <a href="{{ $provider->url }}" target="_blank" class="provider-url">{{ $provider->url }}</a>
                                                @else
                                                    <span class="provider-url opacity-50">No URL</span>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="status-badge badge-unarchived" onclick="toggleArchiveStatus({{ $provider->id }}, '{{ addslashes($provider->name) }}', true)" title="Click to archive">Unarchived</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex gap-3">
                                            <div class="stat-item flex-fill">
                                                <div class="stat-value">{{ $provider->pools_count }}</div>
                                                <div class="stat-label">Pools</div>
                                            </div>
                                            <div class="stat-item flex-fill">
                                                <div class="stat-value">{{ $provider->total_emails }}</div>
                                                <div class="stat-label">Emails</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex gap-2">
                                        <a href="{{ route('admin.smtp-providers.show', $provider->id) }}" class="btn btn-sm btn-outline-primary action-btn flex-fill">
                                            <i class="fa fa-eye me-1"></i>View
                                        </a>
                                        <button class="btn btn-sm btn-outline-warning action-btn flex-fill" 
                                                onclick="editProvider({{ $provider->id }}, '{{ addslashes($provider->name) }}', '{{ addslashes($provider->url ?? '') }}', {{ $provider->is_active ? 'true' : 'false' }})">
                                            <i class="fa fa-edit me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger action-btn flex-fill" 
                                                onclick="deleteProvider({{ $provider->id }}, {{ $provider->pools_count }})"
                                                {{ $provider->pools_count > 0 ? 'disabled' : '' }}>
                                            <i class="fa fa-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="card" style="grid-column: 1 / -1;">
                                <div class="empty-state">
                                    <i class="fa-solid fa-check-circle text-success"></i>
                                    <h5>No Unarchived Providers</h5>
                                    <p class="mb-3">All providers are currently archived.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Archived Tab -->
                <div class="tab-pane fade" id="archived-pane" role="tabpanel" aria-labelledby="archived-tab" tabindex="0">
                    <div class="provider-grid" id="archivedGrid">
                        @php $archivedProviders = $providers->where('is_active', false); @endphp
                        @if($archivedProviders->count() > 0)
                            @foreach($archivedProviders as $provider)
                                <div class="card provider-card" data-name="{{ strtolower($provider->name) }}" data-url="{{ strtolower($provider->url ?? '') }}">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="provider-icon">
                                                <i class="fa-solid fa-server fs-5"></i>
                                            </div>
                                            <div>
                                                <h6 class="provider-name mb-0">{{ $provider->name }}</h6>
                                                @if($provider->url)
                                                    <a href="{{ $provider->url }}" target="_blank" class="provider-url">{{ $provider->url }}</a>
                                                @else
                                                    <span class="provider-url opacity-50">No URL</span>
                                                @endif
                                            </div>
                                        </div>
                                        <span class="status-badge badge-archived" onclick="toggleArchiveStatus({{ $provider->id }}, '{{ addslashes($provider->name) }}', false)" title="Click to unarchive">Archived</span>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex gap-3">
                                            <div class="stat-item flex-fill">
                                                <div class="stat-value">{{ $provider->pools_count }}</div>
                                                <div class="stat-label">Pools</div>
                                            </div>
                                            <div class="stat-item flex-fill">
                                                <div class="stat-value">{{ $provider->total_emails }}</div>
                                                <div class="stat-label">Emails</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex gap-2">
                                        <a href="{{ route('admin.smtp-providers.show', $provider->id) }}" class="btn btn-sm btn-outline-primary action-btn flex-fill">
                                            <i class="fa fa-eye me-1"></i>View
                                        </a>
                                        <button class="btn btn-sm btn-outline-warning action-btn flex-fill" 
                                                onclick="editProvider({{ $provider->id }}, '{{ addslashes($provider->name) }}', '{{ addslashes($provider->url ?? '') }}', {{ $provider->is_active ? 'true' : 'false' }})">
                                            <i class="fa fa-edit me-1"></i>Edit
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger action-btn flex-fill" 
                                                onclick="deleteProvider({{ $provider->id }}, {{ $provider->pools_count }})"
                                                {{ $provider->pools_count > 0 ? 'disabled' : '' }}>
                                            <i class="fa fa-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="card" style="grid-column: 1 / -1;">
                                <div class="empty-state">
                                    <i class="fa-solid fa-archive text-warning"></i>
                                    <h5>No Archived Providers</h5>
                                    <p class="mb-3">No providers have been archived yet.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <!-- Add Provider Modal -->
        <div class="modal fade" id="addProviderModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add SMTP Provider</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addProviderForm">
                            <div class="mb-3">
                                <label class="form-label">Provider Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" required placeholder="e.g. Mailgun, SendGrid">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Provider URL</label>
                                <input type="url" class="form-control" name="url" placeholder="https://provider.example.com">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="saveProvider()">
                            <i class="fas fa-save me-1"></i>Save Provider
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Provider Modal -->
        <div class="modal fade" id="editProviderModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit SMTP Provider</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editProviderForm">
                            <input type="hidden" id="edit_provider_id" name="id">
                            <div class="mb-3">
                                <label class="form-label">Provider Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="edit_provider_name" name="name" required placeholder="e.g. Mailgun, SendGrid">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Provider URL</label>
                                <input type="url" class="form-control" id="edit_provider_url" name="url" placeholder="https://provider.example.com">
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="edit_provider_archived" name="is_active" onchange="updateToggleLabel(this)">
                                    <label class="form-check-label" for="edit_provider_archived" id="edit_toggle_label">Archived</label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="updateProvider()">
                            <i class="fas fa-save me-1"></i>Update Provider
                        </button>
                    </div>
                </div>
            </div>
        </div>
@endsection

@push('scripts')
    <script>
        let allProviders = [];

        $(document).ready(function() {
            // Initial load is from server, but we'll use AJAX for updates
        });

        function loadProviders() {
            $.ajax({
                url: "{{ route('admin.smtp-providers.all') }}",
                type: 'GET',
                success: function(response) {
                    if (response.success && response.providers) {
                        allProviders = response.providers;
                        updateStats(allProviders);
                        renderProviders(allProviders);
                    }
                },
                error: function(xhr) {
                    console.error('Error loading providers:', xhr);
                }
            });
        }

        function updateStats(providers) {
            let totalPools = 0;
            let totalEmails = 0;
            let unarchivedCount = 0;
            let archivedCount = 0;

            providers.forEach(p => {
                if (p.is_active) {
                    unarchivedCount++;
                } else {
                    archivedCount++;
                }
                if (p.pools) {
                    totalPools += p.pools.length;
                    p.pools.forEach(pool => {
                        // First try smtp_accounts_data (for pools created directly as SMTP)
                        if (pool.smtp_accounts_data && pool.smtp_accounts_data.accounts) {
                            totalEmails += pool.smtp_accounts_data.accounts.length;
                        } 
                        // Fallback: Extract from domains + prefix_variants (for migrated pools)
                        else if (pool.domains && Array.isArray(pool.domains) && pool.prefix_variants && typeof pool.prefix_variants === 'object') {
                            const prefixVariants = pool.prefix_variants;
                            const prefixVariantsDetails = pool.prefix_variants_details || {};
                            
                            pool.domains.forEach(domain => {
                                const domainName = domain.name || domain.domain_name;
                                if (!domainName) return;
                                
                                // Check if domain has prefix_statuses (new format)
                                if (domain.prefix_statuses && typeof domain.prefix_statuses === 'object') {
                                    Object.keys(domain.prefix_statuses).forEach(prefixKey => {
                                        const prefixNumber = parseInt(prefixKey.replace(/\D/g, '')) || 1;
                                        const prefixValue = prefixVariants[prefixKey] || prefixVariants[`prefix_variant_${prefixNumber}`];
                                        if (prefixValue) {
                                            totalEmails++;
                                        }
                                    });
                                } else {
                                    // Fallback: Use all prefix variants for this domain (old format)
                                    Object.keys(prefixVariants).forEach(prefixKey => {
                                        const prefixValue = prefixVariants[prefixKey];
                                        if (prefixValue) {
                                            totalEmails++;
                                        }
                                    });
                                }
                            });
                        }
                    });
                }
            });

            // Update counters in the page
            $('.counters .card:eq(0) h4').text(providers.length);
            $('#total_unarchived').text(unarchivedCount);
            $('#total_archived').text(archivedCount);
            $('.counters .card:eq(1) h4').text(totalPools);
            $('.counters .card:eq(2) h4').text(totalEmails);

            // Update tab counters
            $('#unarchived-tab .badge').text(unarchivedCount);
            $('#archived-tab .badge').text(archivedCount);
        }

        function renderProviders(providers) {
            const unarchivedProviders = providers.filter(p => p.is_active);
            const archivedProviders = providers.filter(p => !p.is_active);

            // Render Unarchived tab
            renderProviderGrid($('#unarchivedGrid'), unarchivedProviders, 'unarchived');
            
            // Render Archived tab
            renderProviderGrid($('#archivedGrid'), archivedProviders, 'archived');
        }

        function renderProviderGrid(container, providers, type) {
            if (providers.length === 0) {
                const icon = type === 'unarchived' ? 'fa-check-circle text-success' : 'fa-archive text-warning';
                const title = type === 'unarchived' ? 'No Unarchived Providers' : 'No Archived Providers';
                const message = type === 'unarchived' ? 'All providers are currently archived.' : 'No providers have been archived yet.';
                
                container.html(`
                    <div class="card" style="grid-column: 1 / -1;">
                        <div class="empty-state">
                            <i class="fa-solid ${icon}"></i>
                            <h5>${title}</h5>
                            <p class="mb-3">${message}</p>
                        </div>
                    </div>
                `);
                return;
            }

            let html = '';
            providers.forEach(provider => {
                const poolsCount = provider.pools ? provider.pools.length : 0;
                let emailsCount = 0;
                if (provider.pools) {
                    provider.pools.forEach(pool => {
                        // First try smtp_accounts_data (for pools created directly as SMTP)
                        if (pool.smtp_accounts_data && pool.smtp_accounts_data.accounts) {
                            emailsCount += pool.smtp_accounts_data.accounts.length;
                        } 
                        // Fallback: Extract from domains + prefix_variants (for migrated pools)
                        else if (pool.domains && Array.isArray(pool.domains) && pool.prefix_variants && typeof pool.prefix_variants === 'object') {
                            const prefixVariants = pool.prefix_variants;
                            const prefixVariantsDetails = pool.prefix_variants_details || {};
                            
                            pool.domains.forEach(domain => {
                                const domainName = domain.name || domain.domain_name;
                                if (!domainName) return;
                                
                                // Check if domain has prefix_statuses (new format)
                                if (domain.prefix_statuses && typeof domain.prefix_statuses === 'object') {
                                    Object.keys(domain.prefix_statuses).forEach(prefixKey => {
                                        const prefixNumber = parseInt(prefixKey.replace(/\D/g, '')) || 1;
                                        const prefixValue = prefixVariants[prefixKey] || prefixVariants[`prefix_variant_${prefixNumber}`];
                                        if (prefixValue) {
                                            emailsCount++;
                                        }
                                    });
                                } else {
                                    // Fallback: Use all prefix variants for this domain (old format)
                                    Object.keys(prefixVariants).forEach(prefixKey => {
                                        const prefixValue = prefixVariants[prefixKey];
                                        if (prefixValue) {
                                            emailsCount++;
                                        }
                                    });
                                }
                            });
                        }
                    });
                }

                const urlDisplay = provider.url 
                    ? `<a href="${escapeHtml(provider.url)}" target="_blank" class="provider-url">${escapeHtml(provider.url)}</a>`
                    : '<span class="provider-url opacity-50">No URL</span>';

                const badgeClass = provider.is_active ? 'badge-unarchived' : 'badge-archived';
                const badgeText = provider.is_active ? 'Unarchived' : 'Archived';

                html += `
                    <div class="card provider-card" data-name="${provider.name.toLowerCase()}" data-url="${(provider.url || '').toLowerCase()}">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="provider-icon">
                                    <i class="fa-solid fa-server fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="provider-name mb-0">${escapeHtml(provider.name)}</h6>
                                    ${urlDisplay}
                                </div>
                            </div>
                            <span class="status-badge ${badgeClass}" onclick="toggleArchiveStatus(${provider.id}, '${escapeJs(provider.name)}', ${provider.is_active})" title="Click to ${provider.is_active ? 'archive' : 'unarchive'}">${badgeText}</span>
                        </div>
                        <div class="card-body">
                            <div class="d-flex gap-3">
                                <div class="stat-item flex-fill">
                                    <div class="stat-value">${poolsCount}</div>
                                    <div class="stat-label">Pools</div>
                                </div>
                                <div class="stat-item flex-fill">
                                    <div class="stat-value">${emailsCount}</div>
                                    <div class="stat-label">Emails</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <a href="{{ url('admin/smtp-providers') }}/${provider.id}/show" class="btn btn-sm btn-outline-primary action-btn flex-fill">
                                <i class="fa fa-eye me-1"></i>View
                            </a>
                            <button class="btn btn-sm btn-outline-warning action-btn flex-fill" 
                                    onclick="editProvider(${provider.id}, '${escapeJs(provider.name)}', '${escapeJs(provider.url || '')}', ${provider.is_active})">
                                <i class="fa fa-edit me-1"></i>Edit
                            </button>
                            <button class="btn btn-sm btn-outline-danger action-btn flex-fill" 
                                    onclick="deleteProvider(${provider.id}, ${poolsCount})"
                                    ${poolsCount > 0 ? 'disabled' : ''}>
                                <i class="fa fa-trash me-1"></i>Delete
                            </button>
                        </div>
                    </div>
                `;
            });

            container.html(html);
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function escapeJs(text) {
            if (!text) return '';
            return text.replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
        }

        function filterProviders() {
            const search = $('#searchInput').val().toLowerCase();
            $('.provider-card').each(function() {
                const name = $(this).data('name') || '';
                const url = $(this).data('url') || '';
                if (name.includes(search) || url.includes(search)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        }

        function saveProvider() {
            const form = document.getElementById('addProviderForm');
            const formData = new FormData(form);

            $.ajax({
                url: '{{ route('admin.smtp-providers.store') }}',
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                data: JSON.stringify({
                    name: formData.get('name'),
                    url: formData.get('url')
                }),
                success: function(data) {
                    if (data.success) {
                        $('#addProviderModal').modal('hide');
                        form.reset();
                        loadProviders();
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to create provider'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                }
            });
        }

        function updateToggleLabel(checkbox) {
            const label = document.getElementById('edit_toggle_label');
            if (checkbox.checked) {
                label.textContent = 'Unarchived';
                label.classList.remove('text-warning');
                label.classList.add('text-success');
            } else {
                label.textContent = 'Archived';
                label.classList.remove('text-success');
                label.classList.add('text-warning');
            }
        }

        function toggleArchiveStatus(id, providerName, currentlyUnarchived) {
            const newStatus = !currentlyUnarchived;
            const actionText = currentlyUnarchived ? 'archive' : 'unarchive';
            const titleText = currentlyUnarchived ? 'Archive Provider?' : 'Unarchive Provider?';
            const confirmColor = currentlyUnarchived ? '#ffc107' : '#198754';

            Swal.fire({
                title: titleText,
                text: `Are you sure you want to ${actionText} "${providerName}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: confirmColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${actionText} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('admin/smtp-providers') }}/${id}`,
                        type: 'PUT',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                        },
                        data: JSON.stringify({
                            is_active: newStatus
                        }),
                        success: function(data) {
                            if (data.success) {
                                loadProviders();
                                Swal.fire({
                                    icon: 'success',
                                    title: newStatus ? 'Unarchived!' : 'Archived!',
                                    text: `Provider has been ${newStatus ? 'unarchived' : 'archived'}.`,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || `Failed to ${actionText} provider`
                                });
                            }
                        },
                        error: function(xhr) {
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

        function editProvider(id, name, url, isActive) {
            $('#edit_provider_id').val(id);
            $('#edit_provider_name').val(name);
            $('#edit_provider_url').val(url);
            $('#edit_provider_archived').prop('checked', isActive);
            // Update label based on current state
            updateToggleLabel(document.getElementById('edit_provider_archived'));
            $('#editProviderModal').modal('show');
        }

        function updateProvider() {
            const id = $('#edit_provider_id').val();
            const name = $('#edit_provider_name').val();
            const url = $('#edit_provider_url').val();
            const isActive = $('#edit_provider_archived').is(':checked');

            $.ajax({
                url: `{{ url('admin/smtp-providers') }}/${id}`,
                type: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                data: JSON.stringify({
                    name: name,
                    url: url,
                    is_active: isActive
                }),
                success: function(data) {
                    if (data.success) {
                        $('#editProviderModal').modal('hide');
                        loadProviders();
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: data.message,
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to update provider'
                        });
                    }
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred. Please try again.'
                    });
                }
            });
        }

        function deleteProvider(id, poolsCount) {
            if (poolsCount > 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Cannot Delete',
                    text: `This provider has ${poolsCount} pool(s) associated with it. Remove the pools first.`
                });
                return;
            }

            Swal.fire({
                title: 'Delete Provider?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('admin/smtp-providers') }}/${id}`,
                        type: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        success: function(data) {
                            if (data.success) {
                                loadProviders();
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: data.message,
                                    timer: 1500,
                                    showConfirmButton: false
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: data.message || 'Failed to delete provider'
                                });
                            }
                        },
                        error: function(xhr) {
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
    </script>
@endpush