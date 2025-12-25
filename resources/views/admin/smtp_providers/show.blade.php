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
                            @php
                                $uniqueDomains = [];
                                foreach ($smtpProvider->pools as $pool) {
                                    if ($pool->smtp_accounts_data && isset($pool->smtp_accounts_data['accounts'])) {
                                        foreach ($pool->smtp_accounts_data['accounts'] as $account) {
                                            if (isset($account['domain'])) {
                                                $uniqueDomains[$account['domain']] = true;
                                            }
                                        }
                                    }
                                }
                            @endphp
                            <h4 class="mb-0" style="color: var(--second-primary);">{{ count($uniqueDomains) }}</h4>
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
                                $accounts = $pool->smtp_accounts_data['accounts'] ?? [];
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
                                            <div class="col-md-3">
                                                <small class="">Customer</small>
                                                <div>{{ $pool->user->name ?? 'N/A' }}</div>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="">Status</small>
                                                <div>
                                                    <span
                                                        class="badge bg-{{ $pool->status === 'completed' ? 'success' : ($pool->status === 'pending' ? 'warning' : 'secondary') }}">
                                                        {{ ucfirst($pool->status) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <small class="">Created</small>
                                                <div>{{ $pool->created_at->format('M d, Y') }}</div>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <a href="{{ route('admin.pools.edit', $pool->id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fa-solid fa-edit me-1"></i>Edit Pool
                                                </a>
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
@endsection