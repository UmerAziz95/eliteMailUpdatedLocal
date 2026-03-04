@extends('admin.layouts.app')
@section('title', isset($pool) ? 'Edit Pool' : 'Create Pool')

@push('styles')
    <style>
        /* Base form styles */
        input,
        .form-control,
        textarea,
        .form-select {
            background-color: #1e1e1e !important;
        }

        /* Invalid state styling */
        .form-control.is-invalid,
        .form-select.is-invalid {
            border-color: #dc3545 !important;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }

        /* Invalid feedback styling */
        .invalid-feedback {
            display: none;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }

        /* Show invalid feedback when field has is-invalid class */
        .is-invalid+.invalid-feedback,
        .is-invalid~.invalid-feedback {
            display: block !important;
        }

        /* Specific rule for domains-error to show when domains textarea is invalid or when it has content */
        .domains:has(#domains.is-invalid) #domains-error,
        .domains #domains.is-invalid~#domains-error,
        #domains-error:not(:empty) {
            display: block !important;
        }

        /* Fallback for browsers that don't support :has() - using JavaScript control */
        #domains-error.show-error {
            display: block !important;
        }

        /* Enhanced styling for domains error with order limits */
        #domains-error {
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.2);
            border-radius: 4px;
            padding: 8px 12px;
            margin-top: 8px;
        }

        #domains-error strong {
            color: #dc3545;
        }

        #domains-error small {
            color: #6c757d;
            font-weight: 500;
        }

        /* Focus state for invalid fields */
        .form-control.is-invalid:focus,
        .form-select.is-invalid:focus {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }

        /* Password field wrapper */
        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
        }

        /* HTML5 validation tooltip styles */
        input:invalid:not(.is-invalid),
        textarea:invalid:not(.is-invalid),
        select:invalid:not(.is-invalid) {
            box-shadow: none;
            border-color: inherit;
        }

        /* Custom validation message container */
        .validation-message {
            display: none;
            color: #dc3545;
            font-size: 0.875em;
            margin-top: 0.25rem;
        }

        .is-invalid+.validation-message {
            display: block;
        }

        /* Enhanced validation error styling */
        .form-control.is-invalid,
        .form-select.is-invalid {
            animation: shake 0.5s ease-in-out;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25) !important;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        /* Validation error summary styling */
        .validation-error-summary {
            background: rgba(220, 53, 69, 0.1);
            border: 1px solid rgba(220, 53, 69, 0.3);
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: none;
        }

        .validation-error-summary.show {
            display: block;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Domain count badge styling */
        #domain-count-badge {
            position: absolute;
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            transition: all 0.3s ease;
            animation: pulseCount 2s infinite;
            right: 20px;
            margin-top: -2px;
        }

        @keyframes pulseCount {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        #domain-count-text {
            font-weight: 600;
            color: var(--bs-info);
            display: none;
        }

        /* Domain textarea enhancements */
        #domains:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 202, 240, 0.25);
        }

        /* Prefix Variant Section Styling */
        .prefix-variant-section .card {
            background-color: #ffffff04;
            border: 1px solid #404040;
            border-radius: 12px;
            transition: all 0.3s ease;
            box-shadow: rgba(125, 125, 186, 0.109) 0px 50px 100px -20px, rgb(0, 0, 0) 0px 30px 60px -20px, rgba(173, 173, 173, 0) 0px -2px 6px 0px inset;
        }

        /* 
                                                .prefix-variant-section .card:hover {
                                                    border-color: #667eea;
                                                    box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
                                                    transform: translateY(-2px);
                                                } */

        /* .prefix-variant-section h6 {
                                                    color: #667eea;
                                                    font-weight: 600;
                                                    margin-bottom: 1rem;
                                                    padding-bottom: 0.5rem;
                                                    border-bottom: 1px solid #404040;
                                                } */

        /* .prefix-variant-section .form-control {
                                                    background-color: #1e1e1e !important;
                                                    border-color: #555;
                                                    transition: border-color 0.3s ease;
                                                }

                                                .prefix-variant-section .form-control:focus {
                                                    border-color: #667eea;
                                                    box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
                                                }

                                                .prefix-variant-section .note {
                                                    font-size: 0.875em;
                                                    color: #6c757d;
                                                    margin-top: 0.25rem;
                                                    margin-bottom: 0;
                                                } */
    </style>
@endpush
@section('content')


    @if(isset($pool) && $pool->reason)
        <div class="mb-4">
            <div class="alert border-0 shadow-lg panel-rejection-alert mt-5"
                style="background-color: rgba(255, 0, 0, 0.32); border-left: 5px solid red !important; position: relative; overflow: hidden;">
                <!-- Animated background pattern -->
                <div class="alert-pattern"></div>

                <div class="d-flex align-items-start position-relative" style="z-index: 2;">
                    <div class="alert-icon-wrapper me-3">
                        <i class="fa-solid fa-exclamation-triangle fa-2x text-white"
                            style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-2 text-white fw-bold">
                            Rejection Reason
                        </h6>
                        <div class="rejection-note-content">
                            <p class="mb-0 text-white fw-medium small">
                                {{ $pool->reason }}
                            </p>
                        </div>
                        <div class="mt-3">
                            <span class="badge bg-warning text-dark px-2 rounded-1 py-1">
                                <i class="fa-solid fa-clock me-1"></i>
                                Action Required
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if(isset($pool) && $pool->id)
        <form id="editOrderForm" action="{{ route('admin.pools.update', $pool->id) }}" method="POST" novalidate
            onsubmit="return false;">
            @csrf
            @method('PUT')
    @else
            <form id="editOrderForm" action="{{ route('admin.pools.store') }}" method="POST" novalidate
                onsubmit="return false;">
                @csrf
        @endif
            @csrf
            <input type="hidden" name="user_id" value="{{ auth()->id() }}">
            <input type="hidden" name="plan_id" value="{{ isset($pool) ? $pool->plan_id : '' }}">
            <!-- order_id -->
            <input type="hidden" name="pool_id" value="{{ isset($pool) ? $pool->id : '' }}">
            <input type="hidden" name="edit_id" value="{{ isset($pool) ? $pool->id : '' }}">
            <!-- Hidden fields for current and max inboxes -->
            <input type="hidden" name="current_inboxes" id="current_inboxes" value="0">
            <input type="hidden" name="max_inboxes" id="max_inboxes" value="0">
            <!-- Draft flag for incomplete orders -->
            <input type="hidden" name="is_draft" id="is_draft" value="0">

            <section class="py-3 overflow-hidden" data-page="edit-order">
                <div class="card p-3">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="mb-0">Domains & hosting platform</h5>
                        <!-- <button type="button" class="m-btn py-1 px-3 rounded-2 border-0 import-btn" id="orderImportBtn">
                                                                <i class="fa-solid fa-file-import"></i>
                                                                Import Order
                                                            </button> -->
                    </div>

                    {{-- <div class="domain-forwarding mb-3">
                        <label for="forwarding">Domain forwarding destination URL *</label>
                        <input type="text" id="forwarding" name="forwarding_url" class="form-control"
                            value="{{ isset($pool) ? $pool->forwarding_url : '' }}" required />
                        <div class="invalid-feedback" id="forwarding-error"></div>
                        <p class="note mb-0">(This is usually your VSL, lead capture form, or main website. It’s where
                            prospects will land if they go to one of your domains that you provide us. Please enter a full
                            URL (e.g. https://yourdomain.com).)</p>
                    </div> --}}

                    <div class="domain-hosting mb-3">
                        <label for="hosting">Domain hosting platform *</label>
                        <select id="hosting" name="hosting_platform" class="form-control" required>
                            @foreach($hostingPlatforms as $platform)
                                                <option value="{{ $platform->value }}" data-fields='@json($platform->fields)'
                                                    data-requires-tutorial="{{ $platform->requires_tutorial }}"
                                                    data-tutorial-link="{{ $platform->tutorial_link }}"
                                                    data-import-note="{{ $platform->import_note ?? '' }}" {{ (isset($pool) && $pool->hosting_platform ===
                                $platform->value) ? ' selected' : '' }}>
                                                    {{ $platform->name }}
                                                </option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback" id="hosting-error"></div>
                        <p class="note mb-0">(Where your domains are hosted and can be accessed to modify the
                            DNS settings)</p>
                    </div>

                    <div id="tutorial_section" class="mb-3" style="display: none;">
                        <div class="">
                            <p class="mb-0" id="hosting-platform-import-note">
                                <!-- Hosting platform import note will be dynamically inserted here -->
                                <a href="#" class="highlight-link tutorial-link" target="_blank">Click here to view
                                    tutorial</a>
                            </p>
                        </div>
                    </div>

                    <!-- <div id="other-platform-section" class="mb-3" style="display: none;">
                                                            <label for="other_platform">Please specify your hosting other platform *</label>
                                                            <input type="text" id="other_platform" name="other_platform" class="form-control">
                                                            <div class="invalid-feedback" id="other-platform-error"></div>
                                                        </div> -->

                    <div class="platform" id="platform-fields-container">
                        <!-- Dynamic platform fields will be inserted here -->
                    </div>



                    <div class="row g-3 mt-4">
                        <h5 class="mb-2">Cold Email Platform</h5>

                        <div class="sending-platform col-md-12">
                            <label>Sending Platform</label>
                            <select id="sending_platform" name="sending_platform" class="form-control" required>
                                @foreach($sendingPlatforms as $platform)
                                                        <option value="{{ $platform->value }}" data-fields='@json($platform->fields)' {{
                                    (isset($pool) && $pool->sending_platform === $platform->value) ? ' selected' : '' }}>
                                                            {{ $platform->name }}
                                                        </option>
                                @endforeach
                            </select>
                            <p class="note">(Please select the cold email platform you would like us to install the inboxes
                                on. To avoid any delays, ensure it isn’t on a free trial and that your chosen paid plan is
                                active.)</p>
                        </div>

                        <div class="sending-platform-fields" id="sending-platform-fields">
                            <!-- Dynamic sending platform fields will be inserted here -->
                        </div>

                        <!-- SMTP Pool Mode Section -->
                        <div class="col-md-12 mt-4 smtp-mode-section">
                            <div class="card"
                                style="border: 1px solid var(--second-primary); background: linear-gradient(135deg, rgba(74, 58, 255, 0.1), rgba(74, 58, 255, 0.02));">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h6 class="mb-1"><i class="fa fa-server me-2"
                                                    style="color: var(--second-primary);"></i>SMTP Pool Mode</h6>
                                            <small class="opacity-75">Enable this to create a pool using SMTP email accounts
                                                from a CSV file</small>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="smtp_mode_toggle"
                                                name="smtp_mode" value="1"
                                                style="width: 3rem; height: 1.5rem; cursor: pointer;" {{ isset($pool) && ($pool->provider_type ?? '') === 'SMTP' ? 'checked' : '' }}>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SMTP Fields (Hidden by default, shown when SMTP mode is enabled) -->
                        <div id="smtp-fields-container" class="col-md-12 mt-4" style="display: none;">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fa fa-envelope me-2"></i>SMTP Email Accounts</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="smtp_provider_id">SMTP Provider *</label>
                                            <select id="smtp_provider_id" name="smtp_provider_id"
                                                class="form-control select2-smtp-provider" style="width: 100%;">
                                                <option value="">Select or Create SMTP Provider</option>
                                                @if(isset($pool) && $pool->smtpProvider)
                                                    <option value="{{ $pool->smtpProvider->id }}" selected>
                                                        {{ $pool->smtpProvider->name }}
                                                    </option>
                                                @endif
                                            </select>
                                            <input type="hidden" id="smtp_provider_url" name="smtp_provider_url"
                                                value="{{ isset($pool) ? ($pool->smtp_provider_url ?? '') : '' }}">
                                            <div class="invalid-feedback" id="smtp_provider_id-error"></div>
                                            <p class="note mb-0">(Select an existing provider or type to create new)</p>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="smtp_csv_file">Upload CSV File *</label>
                                            <input type="file" id="smtp_csv_file" class="form-control" accept=".csv">
                                            <div class="invalid-feedback" id="smtp_csv_file-error"></div>
                                            <p class="note mb-0">(CSV with columns: First Name, Last Name, Email Address,
                                                Password, Org Unit Path)</p>
                                        </div>
                                    </div>

                                    <!-- CSV Preview Section -->
                                    <div id="smtp-csv-preview" class="mt-4" style="display: none;">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0"><i class="fa fa-table me-2"></i>CSV Preview</h6>
                                            <div>
                                                <span class="badge bg-primary" id="smtp-csv-count">0 accounts</span>
                                                <span class="badge bg-info" id="smtp-csv-domains">0 domains</span>
                                            </div>
                                        </div>
                                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                            <table class="table table-sm table-hover mb-0" id="smtp-csv-table">
                                                <thead
                                                    style="position: sticky; top: 0; background: var(--secondary-color);">
                                                    <tr>
                                                        <th>First Name</th>
                                                        <th>Last Name</th>
                                                        <th>Email Address</th>
                                                        <th>Domain</th>
                                                        <th>Password</th>
                                                        <th>Org Unit Path</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="smtp-csv-tbody">
                                                    <!-- CSV data rows will be inserted here -->
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Extracted Summary -->
                                        <div class="row g-3 mt-3">
                                            <div class="col-md-3">
                                                <div class="card bg-transparent border">
                                                    <div class="card-body p-2 text-center">
                                                        <small class="opacity-75 d-block">Total Inboxes</small>
                                                        <strong id="smtp-total-inboxes" class="fs-5"
                                                            style="color: var(--second-primary);">0</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-transparent border">
                                                    <div class="card-body p-2 text-center">
                                                        <small class="opacity-75 d-block">Unique Domains</small>
                                                        <strong id="smtp-unique-domains" class="fs-5"
                                                            style="color: var(--second-primary);">0</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-transparent border">
                                                    <div class="card-body p-2 text-center">
                                                        <small class="opacity-75 d-block">Max Inboxes/Domain</small>
                                                        <strong id="smtp-max-per-domain" class="fs-5"
                                                            style="color: var(--second-primary);">0</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="card bg-transparent border">
                                                    <div class="card-body p-2 text-center">
                                                        <small class="opacity-75 d-block">Prefix Variants</small>
                                                        <strong id="smtp-prefix-count" class="fs-5"
                                                            style="color: var(--second-primary);">0</strong>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Hidden field to store parsed SMTP accounts data -->
                                    <input type="hidden" id="smtp_accounts_data" name="smtp_accounts_data"
                                        value="{{ isset($pool) && $pool->smtp_accounts_data ? json_encode($pool->smtp_accounts_data) : '' }}">
                                    <!-- Hidden fields to store raw CSV file -->
                                    <input type="hidden" id="smtp_csv_content" name="smtp_csv_file"
                                        value="{{ isset($pool) ? ($pool->smtp_csv_file ?? '') : '' }}">
                                    <input type="hidden" id="smtp_csv_filename_input" name="smtp_csv_filename"
                                        value="{{ isset($pool) ? ($pool->smtp_csv_filename ?? '') : '' }}">
                                </div>
                            </div>
                        </div>

                        <!-- Standard Pool Fields (hidden when SMTP mode is enabled) -->
                        <div id="standard-pool-fields">
                            <h5 class="mb-2 mt-5">Email Account Information</h5>

                            <div class="row">
                                <div class="inboxes-per-domain col-md-6">
                                    <label>Inboxes per Domain / Prefix Variant</label>
                                    <select name="inboxes_per_domain" id="inboxes_per_domain" class="form-control" required
                                        {{ isset($pool) && $pool->inboxes_per_domain ? 'disabled' : '' }}>
                                        <option value="1" {{ isset($pool) && $pool->inboxes_per_domain == 1 ? 'selected' : '' }}>1</option>
                                        <option value="2" {{ isset($pool) && $pool->inboxes_per_domain == 2 ? 'selected' : '' }}>2</option>
                                        <option value="3" {{ isset($pool) && $pool->inboxes_per_domain == 3 ? 'selected' : '' }}>3</option>
                                    </select>
                                    @if(isset($pool) && $pool->inboxes_per_domain)
                                        <input type="hidden" name="inboxes_per_domain" value="{{ $pool->inboxes_per_domain }}">
                                    @endif
                                    <p class="note">(How many email accounts you would like us to create per domain - the
                                        maximum is
                                        3){{ isset($pool) && $pool->inboxes_per_domain ? ' - This field cannot be changed once set.' : '' }}
                                    </p>
                                </div>
                                <div class="col-md-6 total-inbox">
                                    <label>Total Inboxes</label>
                                    <input type="number" name="total_inboxes" id="total_inboxes" class="form-control"
                                        readonly required value="{{ isset($pool) ? $pool->total_inboxes : '' }}">
                                    <p class="note">(Automatically calculated based on domains and inboxes per domain)</p>
                                </div>
                            </div>
                            <div class="domains mb-3">
                                <label for="domains">
                                    Domains *
                                    <span class="badge bg-primary ms-2" id="domain-count-badge">0 domains</span>
                                </label>
                                <div id="domains-container">
                                    <!-- Editable domains textarea -->
                                    <textarea id="domains" name="domains" class="form-control"
                                        rows="6">{{ isset($pool) && $pool->domains ? (is_array($pool->domains) ? implode("\n", array_filter(array_map(function ($d) {
        return is_array($d) && !($d['is_used'] ?? false) ? $d['name'] : (is_string($d) ? $d : null); }, $pool->domains))) : $pool->domains) : '' }}</textarea>

                                    <!-- Read-only domains display -->
                                    <div id="readonly-domains" class="mt-2" style="display: none;">
                                        <label class="form-label">Used Domains (Read-only)</label>
                                        <div id="readonly-domains-list" class="rounded p-1 text-muted">
                                            <!-- Used domains will be populated here -->
                                        </div>
                                    </div>
                                </div>
                                <div class="invalid-feedback" id="domains-error"></div>
                                <small class="note">
                                    Please enter each domain on a new line and ensure you double-check the number of domains
                                    you submit
                                    <br>
                                    <span class="text-info" style="display: none;">
                                        <i class="fa-solid fa-info-circle me-1"></i>
                                        Total domains: <strong id="domain-count-text">0</strong>
                                    </span>
                                    <br>
                                    <span class="text-warning" id="used-domains-note" style="display: none;">
                                        <i class="fa-solid fa-lock me-1"></i>
                                        Some domains are locked and cannot be edited as they are currently in use.
                                    </span>
                                </small>
                            </div>

                            <!-- Pool Status Management -->
                            <div class="col-md-6 pool-status mb-3 d-none">
                                <label for="status_manage_by_admin">Pool Status *</label>
                                <select id="status_manage_by_admin" name="status_manage_by_admin" class="form-control"
                                    required>
                                    <option value="warming" {{ (isset($pool) && $pool->status_manage_by_admin === 'warming') ? 'selected' : '' }}>Warming</option>
                                    <option value="available" {{ (isset($pool) && $pool->status_manage_by_admin === 'available') ? 'selected' : '' }}>Available</option>
                                </select>
                                <div class="invalid-feedback" id="status_manage_by_admin-error"></div>
                                <p class="note mb-0">(Warming: Pool is being prepared. Available: Pool is ready for use.)
                                </p>
                            </div>



                            <!-- Remaining Inboxes Progress Bar -->
                            <div class="col-md-12 remaining">
                                <div class="mb-3">
                                    <label>Remaining Inboxes</label>

                                    <div class="progress position-relative" style="height: 25px;
                                                                        background: linear-gradient(135deg, #f5f5f5, #eaeaea);
                                                                        border-radius: 6px;">

                                        <div class="progress-bar" role="progressbar" id="remaining-inboxes-bar"
                                            style="width: 0%; background: linear-gradient(45deg, #28a745, #20c997); border-radius: 6px;">
                                        </div>

                                        <!-- Centered Text -->
                                        <span id="remaining-inboxes-text" class="position-absolute w-100 text-center"
                                            style="color: #333; font-weight: 600; line-height: 25px;">
                                            0 / 0 inboxes used
                                        </span>
                                    </div>

                                    <p class="note" id="remaining-inboxes-note">(Shows your current order usage)</p>
                                </div>
                            </div>


                            <div class="col-md-6 first-name" style="display:none;">
                                <label>First Name</label>
                                <input type="text" name="first_name" class="form-control"
                                    value="{{ isset($pool) ? $pool->first_name : '' }}">
                                <div class="invalid-feedback" id="first_name-error"></div>
                                <p class="note">(First name that you wish to use on the inbox profile)</p>
                            </div>

                            <div class="col-md-6 last-name" style="display:none;">
                                <label>Last Name</label>
                                <input type="text" name="last_name" class="form-control"
                                    value="{{ isset($pool) ? $pool->last_name : '' }}">
                                <div class="invalid-feedback" id="last_name-error"></div>
                                <p class="note">(Last name that you wish to use on the inbox profile)</p>
                            </div>

                            <!-- Hidden original prefix variant fields -->
                            <div class="col-md-6" style="display: none;">
                                <label>Email Persona - Prefix Variant 1</label>
                                <input type="text" name="prefix_variant_1" class="form-control"
                                    value="{{ isset($pool) ? $pool->prefix_variant_1 : '' }}">
                                <div class="invalid-feedback" id="prefix_variant_1-error"></div>
                            </div>

                            <div class="col-md-6" style="display: none;">
                                <label>Email Persona - Prefix Variant 2</label>
                                <input type="text" name="prefix_variant_2" class="form-control"
                                    value="{{ isset($pool) ? $pool->prefix_variant_2 : '' }}">
                                <div class="invalid-feedback" id="prefix_variant_2-error"></div>
                            </div>

                            <!-- Dynamic prefix variants container -->
                            <div id="prefix-variants-container" class="row g-3 mt-4 prefix-variants">
                                <h5 class="mb-2 col-12">Email Persona - Prefix Variants</h5>
                                <!-- Dynamic prefix variant fields will be inserted here -->
                            </div>

                            <!-- <div class="col-md-6">
                                                                <label>Persona Password</label>
                                                                <div class="password-wrapper">
                                                                    <input type="password" id="persona_password" name="persona_password" class="form-control" value="{{ isset($pool) ? $pool->persona_password : '' }}" required>
                                                                    <div class="invalid-feedback" id="persona_password-error"></div>
                                                                    <i class="fa-regular fa-eye password-toggle"></i>
                                                                </div>
                                                            </div> -->

                            <div class="col-md-6 profile-picture" style="display: none;">
                                <label>Profile Picture Link</label>
                                <input type="url" name="profile_picture_link" class="form-control"
                                    value="{{ isset($pool) ? $pool->profile_picture_link : '' }}">
                                <div class="invalid-feedback" id="profile_picture_link-error"></div>
                            </div>

                            <div class="col-md-6 email-password" style="display:none;">
                                <label>Email Persona - Password</label>
                                <div class="password-wrapper">
                                    <input type="password" id="email_persona_password" name="email_persona_password"
                                        class="form-control"
                                        value="{{ isset($pool) ? $pool->email_persona_password : '' }}">
                                    <div class="invalid-feedback" id="email_persona_password-error"></div>
                                    <i class="fa-regular fa-eye password-toggle"></i>
                                </div>
                            </div>

                            <div class="col-md-6 email-picture-link" style="display:none;">
                                <label>Email Persona - Profile Picture Link</label>
                                <input type="url" name="email_persona_picture_link" class="form-control"
                                    value="{{ isset($pool) ? $pool->email_persona_picture_link : '' }}">
                                <div class="invalid-feedback" id="email_persona_picture_link-error"></div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 master-inbox">
                                    <label for="master_inbox_confirmation">Do you want to enable domain forwarding?</label>
                                    <select name="master_inbox_confirmation" id="master_inbox_confirmation"
                                        class="form-control">
                                        <option value="0" {{ isset($pool) && !$pool->master_inbox_confirmation ? 'selected' : (!isset($pool) ? 'selected' : '') }}>No</option>
                                        <option value="1" {{ isset($pool) && $pool->master_inbox_confirmation ? 'selected' : '' }}>Yes</option>
                                    </select>
                                    <p class="note">(Choose "Yes" if you want to forward all email inboxes to a specific
                                        email)</p>
                                </div>

                                <div class="col-md-6 master-inbox-email"
                                    style="display: {{ isset($pool) && $pool->master_inbox_confirmation ? 'block' : 'none' }};">
                                    <label>Master Domain Email *</label>
                                    <input type="email" name="master_inbox_email" id="master_inbox_email"
                                        class="form-control" value="{{ isset($pool) ? $pool->master_inbox_email : '' }}" {{ isset($pool) && $pool->master_inbox_confirmation ? 'required' : '' }}>
                                    <div class="invalid-feedback" id="master_inbox_email-error"></div>
                                    <p class="note">(Enter the main email where all messages should be forwarded)</p>
                                </div>
                            </div>

                        </div>
                        <!-- End of Standard Pool Fields -->
                    </div>
                    <!-- End of Cold Email Platform Row -->

                    <!-- Common Form Sections (visible in both standard and SMTP modes) -->
                    <div class="row">
                        <!-- Purchase Date -->
                        <div id="purchase-date-section" class="col-md-6 mb-3">
                            <label for="purchase_date">Purchase Date</label>
                            <input type="date" id="purchase_date" name="purchase_date" class="form-control"
                                value="{{ isset($pool) && $pool->purchase_date ? \Carbon\Carbon::parse($pool->purchase_date)->format('Y-m-d') : '' }}"
                                required>
                            <div class="invalid-feedback" id="purchase_date-error"></div>
                            <small>Optional: Date when the pool was purchased</small>
                        </div>

                        <!-- Expiry Date (Auto-calculated) -->
                        <div class="col-md-6 mb-3" id="expiry_date_container"
                            style="display: {{ isset($pool) && $pool->purchase_date ? 'block' : 'none' }};">
                            <label for="expiry_date">Expiry Date (12 months from purchase)</label>
                            <input type="text" id="expiry_date" class="form-control" readonly
                                style="background-color: #2a2a2a; cursor: not-allowed;"
                                value="{{ isset($pool) && $pool->purchase_date ? \Carbon\Carbon::parse($pool->purchase_date)->addMonths(12)->format('F j, Y') : '' }}">
                            <small>Automatically calculated: Purchase Date + 12 months</small>
                        </div>
                    </div>

                    <div id="additional-assets-section">
                        <h5 class="mb-2 mt-4">Additional Assets</h5>

                        <div class="mb-3">
                            <label for="additional_info">Additional Information / Context </label>
                            <textarea id="additional_info" name="additional_info" class="form-control"
                                rows="8">{{ isset($pool) ? $pool->additional_info : '' }}</textarea>
                        </div>
                    </div>

                    {{-- Manual Panel Assignment Section (create only, hidden for SMTP mode) --}}
                    @if(!isset($pool) || !$pool?->id)
                        <div id="panel-assignment-section">
                            <x-panel.panel-assignment />
                        </div>
                    @endif

                    <div class="col-md-6" style="display: none;">
                        <label>Coupon Code</label>
                        <input type="text" name="coupon_code" class="form-control"
                            value="{{ isset($pool) ? $pool->coupon_code : '' }}">
                    </div>

                    <!-- Price display section -->
                    <div class="price-display-section" style="display: none;">
                        @if(isset($pool))
                                        @php
                                            $totalInboxes = 0;
                                            if (isset($pool)) {
                                                $totalInboxes = $pool->total_inboxes;
                                            }
                                            $originalPrice = $pool->price * $totalInboxes;
                                        @endphp
                                        <h6><span class="theme-text">Original Price:</span> ${{ number_format($originalPrice, 2) }} ({{
                            $totalInboxes }} x ${{ number_format($pool->price, 2) }}
                                            <small>/{{ $pool->duration }})</small>
                                        </h6>
                                        <h6><span class="theme-text">Discount:</span> 0%</h6>
                                        <h6><span class="theme-text">Total:</span> ${{ number_format($originalPrice, 2) }} <small>/{{
                            $pool->duration }}</small></h6>
                        @else
                            <h6><span class="theme-text">Original Price:</span> <small>Price will be calculated based on
                                    selected plan</small></h6>
                            <h6><span class="theme-text">Total:</span> <small>Total will be calculated based on selected
                                    plan</small></h6>
                        @endif
                    </div>

                    <div id="submit-section" class="d-flex gap-2">
                        <button type="submit" class="m-btn py-1 px-3 rounded-2 border-0 purchase-btn">

                            @if(isset($pool) && $pool->status === 'rejected')
                                Fix Order
                            @else
                                <i class="fa-solid fa-cart-shopping"></i>
                                Submit
                            @endif
                        </button>
                    </div>
                </div>
                </section>
                </form>

                <!-- Order Import Modal -->
                <div class="modal fade" id="orderImportModal" tabindex="-1" aria-labelledby="orderImportModalLabel"
                    aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content order-import-modal">
                            <div class="modal-header">
                                <h5 class="modal-title" id="orderImportModalLabel">
                                    <div class="d-flex align-items-center">
                                        <div class="modal-icon-wrapper me-3">
                                            <i class="fa-solid fa-file-import"></i>
                                        </div>
                                        <div>
                                            <span class="modal-title-main">Import Order Data</span>
                                            <small class="modal-subtitle d-block">Select an existing order to populate form
                                                data</small>
                                        </div>
                                    </div>
                                </h5>
                                <button type="button" class="btn-close-custom" data-bs-dismiss="modal" aria-label="Close">
                                    <i class="fa-solid fa-times"></i>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="import-description">
                                    <div class="d-flex align-items-start">
                                        <div class="info-icon me-3">
                                            <i class="fa-solid fa-info-circle"></i>
                                        </div>
                                        <div>
                                            <p class="mb-2"><strong>How it works:</strong></p>
                                            <ul class="import-steps">
                                                <li>Browse your existing orders in the table below</li>
                                                <li>Click "Import" on any order to copy its data</li>
                                                <li>All form fields will be automatically populated</li>
                                                <li>Review and modify the imported data as needed</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-container">
                                    <div class="table-responsive">
                                        <table id="ordersImportTable" class="table import-table w-100" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th><i class="fa-solid fa-hashtag me-1"></i>Order ID</th>
                                                    <th><i class="fa-solid fa-file-lines me-1"></i>Plan</th>
                                                    <th><i class="fa-solid fa-envelope me-1"></i>Total Inboxes</th>
                                                    <th><i class="fa-solid fa-signal me-1"></i>Status</th>
                                                    <th><i class="fa-solid fa-calendar me-1"></i>Created Date</th>
                                                    <th><i class="fa-solid fa-cogs me-1"></i>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <!-- <div class="modal-footer"> 
                                                                <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">
                                                                    <i class="fa-solid fa-times me-2"></i>
                                                                    Cancel
                                                                </button>
                                                            </div> -->
                        </div>
                    </div>
                </div>

                <style>
                    /* Modal Animation and Styling */
                    .order-import-modal {
                        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
                        border: 2px solid #444;
                        border-radius: 15px;
                        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8);
                        overflow: hidden;
                        animation: modalSlideIn 0.4s ease-out;
                    }

                    @keyframes modalSlideIn {
                        from {
                            opacity: 0;
                            transform: translateY(-50px) scale(0.9);
                        }

                        to {
                            opacity: 1;
                            transform: translateY(0) scale(1);
                        }
                    }

                    /* Modal Header */
                    .order-import-modal .modal-header {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        border-bottom: none;
                        padding: 20px 30px;
                        position: relative;
                        overflow: hidden;
                    }

                    .order-import-modal .modal-header::before {
                        content: '';
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
                        pointer-events: none;
                    }

                    .modal-icon-wrapper {
                        background: rgba(255, 255, 255, 0.2);
                        width: 50px;
                        height: 50px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        animation: iconPulse 2s infinite;
                    }

                    @keyframes iconPulse {

                        0%,
                        100% {
                            transform: scale(1);
                        }

                        50% {
                            transform: scale(1.1);
                        }
                    }

                    .modal-icon-wrapper i {
                        font-size: 20px;
                        color: white;
                    }

                    .modal-title-main {
                        color: white;
                        font-size: 1.4rem;
                        font-weight: 600;
                        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
                    }

                    .modal-subtitle {
                        color: rgba(255, 255, 255, 0.8);
                        font-size: 0.85rem;
                        margin-top: 2px;
                    }

                    .btn-close-custom {
                        background: rgba(255, 255, 255, 0.1);
                        border: 1px solid rgba(255, 255, 255, 0.2);
                        border-radius: 50%;
                        width: 40px;
                        height: 40px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        transition: all 0.3s ease;
                        color: white;
                    }

                    .btn-close-custom:hover {
                        background: rgba(255, 255, 255, 0.2);
                        transform: rotate(90deg);
                    }

                    /* Modal Body */
                    .order-import-modal .modal-body {
                        padding: 30px;
                        background: #1e1e1e;
                    }

                    .import-description {
                        background: linear-gradient(135deg, #2d4a7c 0%, #3d5a8c 100%);
                        border-radius: 12px;
                        padding: 20px;
                        margin-bottom: 25px;
                        border: 1px solid #4a6fa5;
                        position: relative;
                        overflow: hidden;
                    }

                    .import-description::before {
                        content: '';
                        position: absolute;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: linear-gradient(45deg, rgba(255, 255, 255, 0.05) 0%, transparent 100%);
                        pointer-events: none;
                    }

                    .info-icon {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        width: 40px;
                        height: 40px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        flex-shrink: 0;
                    }

                    .info-icon i {
                        color: white;
                        font-size: 16px;
                    }

                    .import-description p {
                        color: #e0e6ed;
                        margin-bottom: 0.5rem;
                    }

                    .import-steps {
                        color: #b8c5d1;
                        padding-left: 1.2rem;
                        margin: 0;
                    }

                    .import-steps li {
                        margin-bottom: 0.4rem;
                        position: relative;
                    }

                    .import-steps li::marker {
                        color: #667eea;
                    }

                    /* Table Container */
                    .table-container {
                        background: #252525;
                        border-radius: 12px;
                        /* padding: 20px;
                                                    border: 1px solid #404040; */
                        box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.3);
                    }

                    /* Enhanced responsive table wrapper for horizontal scrolling */
                    .table-responsive {
                        border-radius: 8px;
                        overflow-x: auto;
                        overflow-y: visible;
                        -webkit-overflow-scrolling: touch;
                        border: 1px solid #404040;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
                        min-height: 200px;
                        background: #252525;
                    }

                    /* Custom scrollbar for horizontal scrolling */
                    .table-responsive::-webkit-scrollbar {
                        height: 12px;
                    }

                    .table-responsive::-webkit-scrollbar-track {
                        background: #1a1a1a;
                        border-radius: 6px;
                    }

                    .table-responsive::-webkit-scrollbar-thumb {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        border-radius: 6px;
                        border: 2px solid #1a1a1a;
                    }

                    .table-responsive::-webkit-scrollbar-thumb:hover {
                        background: linear-gradient(135deg, #5a6fd8 0%, #6b4a8a 100%);
                    }

                    /* Firefox scrollbar styling */
                    .table-responsive {
                        scrollbar-width: thin;
                        scrollbar-color: #667eea #1a1a1a;
                    }

                    /* Table content styling for better horizontal scrolling */
                    .import-table {
                        min-width: 800px;
                        /* Ensure minimum width to trigger horizontal scrolling */
                        white-space: nowrap;
                        /* Prevent text wrapping in cells */
                    }

                    .import-table th,
                    .import-table td {
                        min-width: 120px;
                        /* Set minimum column width */
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    /* Specific column width adjustments */
                    .import-table th:first-child,
                    .import-table td:first-child {
                        min-width: 80px;
                        /* Order ID column */
                    }

                    .import-table th:nth-child(2),
                    .import-table td:nth-child(2) {
                        min-width: 150px;
                        /* Plan column */
                    }

                    .import-table th:nth-child(6),
                    .import-table td:nth-child(6) {
                        min-width: 120px;
                        /* Action column */
                    }

                    /* Scroll indicators for better UX */
                    .scroll-indicators {
                        position: relative;
                        height: 0;
                        pointer-events: none;
                    }

                    .scroll-indicator-left,
                    .scroll-indicator-right {
                        position: absolute;
                        top: 50%;
                        transform: translateY(-50%);
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        width: 30px;
                        height: 30px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        opacity: 0;
                        transition: opacity 0.3s ease;
                        pointer-events: auto;
                        z-index: 10;
                        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
                    }

                    .scroll-indicator-left {
                        left: 10px;
                    }

                    .scroll-indicator-right {
                        right: 10px;
                    }

                    .scroll-indicator-left.visible,
                    .scroll-indicator-right.visible {
                        opacity: 0.8;
                    }

                    .scroll-indicator-left:hover,
                    .scroll-indicator-right:hover {
                        opacity: 1;
                        background: linear-gradient(135deg, #5a6fd8 0%, #6b4a8a 100%);
                    }

                    /* Table Styling */
                    .import-table {
                        margin: 0;
                    }

                    .import-table thead th {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        color: white;
                        border: none;
                        padding: 15px 12px;
                        font-weight: 600;
                        text-transform: uppercase;
                        font-size: 0.85rem;
                        letter-spacing: 0.5px;
                        position: relative;
                    }

                    .import-table thead th:first-child {
                        border-top-left-radius: 8px;
                    }

                    .import-table thead th:last-child {
                        border-top-right-radius: 8px;
                    }

                    .import-table tbody tr {
                        background: #2a2a2a;
                        border-bottom: 1px solid #3a3a3a;
                        transition: all 0.3s ease;
                    }

                    .import-table tbody tr:hover {
                        background: linear-gradient(135deg, #3a3a3a 0%, #4a4a4a 100%);
                        transform: translateY(-1px);
                        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
                    }

                    .import-table tbody td {
                        padding: 15px 12px;
                        color: #e0e0e0;
                        border: none;
                        vertical-align: middle;
                    }

                    /* Import Button */
                    .import-order-btn {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                        border: none;
                        color: white;
                        padding: 8px 16px;
                        border-radius: 6px;
                        font-size: 0.85rem;
                        font-weight: 500;
                        transition: all 0.3s ease;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }

                    .import-order-btn:hover {
                        background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
                        transform: translateY(-2px);
                        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                        color: white;
                    }

                    .import-order-btn:active {
                        transform: translateY(0);
                    }

                    /* Modal Footer */
                    .order-import-modal .modal-footer {
                        background: #252525;
                        border-top: 1px solid #404040;
                        padding: 20px 30px;
                    }

                    .btn-cancel {
                        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
                        border: none;
                        color: white;
                        padding: 10px 20px;
                        border-radius: 6px;
                        font-weight: 500;
                        transition: all 0.3s ease;
                    }

                    .btn-cancel:hover {
                        background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
                        transform: translateY(-1px);
                        box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
                        color: white;
                    }

                    /* Status Badges Enhancement */
                    .import-table .badge {
                        padding: 6px 12px;
                        border-radius: 20px;
                        font-size: 0.75rem;
                        font-weight: 500;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }

                    /* Loading Animation for AJAX tables */
                    .table-loading {
                        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
                        color: white !important;
                        border-radius: 8px !important;
                        border: none !important;
                        padding: 20px !important;
                        font-weight: 500 !important;
                        text-align: center;
                    }

                    /* Responsive Design */
                    @media (max-width: 768px) {
                        .order-import-modal .modal-header {
                            padding: 15px 20px;
                        }

                        .modal-title-main {
                            font-size: 1.2rem;
                        }

                        .order-import-modal .modal-body {
                            padding: 20px;
                        }

                        .import-description {
                            padding: 15px;
                        }

                        .table-container {
                            /* padding: 15px; */
                        }

                        /* Enhanced mobile horizontal scrolling */
                        .table-responsive {
                            margin: 0 -15px;
                            /* Extend to modal edges on mobile */
                            border-radius: 0;
                            border-left: none;
                            border-right: none;
                        }

                        .import-table {
                            min-width: 900px;
                            /* Increased minimum width for mobile */
                        }

                        /* Make scrollbar more prominent on mobile */
                        .table-responsive::-webkit-scrollbar {
                            height: 16px;
                        }

                        .table-responsive::-webkit-scrollbar-thumb {
                            border: 3px solid #1a1a1a;
                        }

                        /* Mobile-specific column adjustments */
                        .import-table th,
                        .import-table td {
                            min-width: 140px;
                            padding: 12px 8px;
                            font-size: 0.9rem;
                        }

                        /* Add scroll hint for mobile users */
                        .table-container::after {
                            content: "← Swipe to see more →";
                            display: block;
                            text-align: center;
                            color: #667eea;
                            font-size: 0.8rem;
                            margin-top: 10px;
                            font-style: italic;
                        }
                    }

                    @media (max-width: 480px) {
                        .import-table {
                            min-width: 1000px;
                            /* Even wider on very small screens */
                        }

                        .import-table th,
                        .import-table td {
                            min-width: 160px;
                            padding: 10px 6px;
                            font-size: 0.85rem;
                        }

                        .order-import-modal .modal-body {
                            padding: 15px;
                        }
                    }

                    /* Animation for table rows */
                    .import-table tbody tr {
                        animation: tableRowSlideIn 0.5s ease-out forwards;
                        opacity: 0;
                    }

                    @keyframes tableRowSlideIn {
                        from {
                            opacity: 0;
                            transform: translateX(-20px);
                        }

                        to {
                            opacity: 1;
                            transform: translateX(0);
                        }
                    }

                    /* Stagger animation for multiple rows */
                    .import-table tbody tr:nth-child(1) {
                        animation-delay: 0.1s;
                    }

                    .import-table tbody tr:nth-child(2) {
                        animation-delay: 0.2s;
                    }

                    .import-table tbody tr:nth-child(3) {
                        animation-delay: 0.3s;
                    }

                    .import-table tbody tr:nth-child(4) {
                        animation-delay: 0.4s;
                    }

                    .import-table tbody tr:nth-child(5) {
                        animation-delay: 0.5s;
                    }

                    /* Select2 Dark Theme Styles for SMTP Provider */
                    .select2-container--default .select2-selection--single {
                        background-color: var(--secondary-color, #1a1a2e) !important;
                        border: 1px solid var(--border-color, #3d3d5c) !important;
                        border-radius: 4px !important;
                        height: 38px !important;
                        color: #fff !important;
                    }

                    .select2-container--default .select2-selection--single .select2-selection__rendered {
                        color: #fff !important;
                        line-height: 36px !important;
                        padding-left: 12px !important;
                        padding-right: 40px !important;
                        font-size: 0.9rem !important;
                    }

                    .select2-container--default .select2-selection--single .select2-selection__placeholder {
                        color: rgba(255, 255, 255, 0.6) !important;
                    }

                    .select2-container--default .select2-selection--single .select2-selection__arrow {
                        height: 36px !important;
                        right: 8px !important;
                    }

                    .select2-container--default .select2-selection--single .select2-selection__arrow b {
                        border-color: #fff transparent transparent transparent !important;
                    }

                    .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
                        border-color: transparent transparent #fff transparent !important;
                    }

                    /* Clear button (x) styling */
                    .select2-container--default .select2-selection--single .select2-selection__clear {
                        color: rgba(255, 255, 255, 0.6) !important;
                        font-size: 18px !important;
                        font-weight: normal !important;
                        margin-right: 5px !important;
                        position: absolute !important;
                        right: 25px !important;
                        top: 50% !important;
                        transform: translateY(-50%) !important;
                        cursor: pointer !important;
                        line-height: 1 !important;
                        padding: 0 5px !important;
                    }

                    .select2-container--default .select2-selection--single .select2-selection__clear:hover {
                        color: #ff6b6b !important;
                    }

                    .select2-dropdown {
                        background-color: var(--secondary-color, #1a1a2e) !important;
                        border: 1px solid var(--border-color, #3d3d5c) !important;
                        border-radius: 4px !important;
                    }

                    .select2-container--default .select2-search--dropdown .select2-search__field {
                        background-color: var(--primary-color, #0f0f23) !important;
                        border: 1px solid var(--border-color, #3d3d5c) !important;
                        color: #fff !important;
                        border-radius: 4px !important;
                        padding: 8px 12px !important;
                    }

                    .select2-container--default .select2-search--dropdown .select2-search__field::placeholder {
                        color: rgba(255, 255, 255, 0.5) !important;
                    }

                    .select2-container--default .select2-results__option {
                        padding: 10px 12px !important;
                        color: #fff !important;
                        background-color: var(--secondary-color, #1a1a2e) !important;
                    }

                    .select2-container--default .select2-results__option--highlighted[aria-selected] {
                        background-color: var(--second-primary, #4a3aff) !important;
                        color: #fff !important;
                    }

                    .select2-container--default .select2-results__option[aria-selected="true"] {
                        background-color: rgba(74, 58, 255, 0.3) !important;
                    }

                    .select2-container--default .select2-results__option--disabled {
                        color: rgba(255, 255, 255, 0.4) !important;
                    }

                    .select2-results__message {
                        color: rgba(255, 255, 255, 0.6) !important;
                    }

                    /* Create new option styling */
                    .select2-container--default .select2-results__option .text-success {
                        color: #28a745 !important;
                    }

                    /* CSV Preview Table Dark Theme Styling */
                    #smtp-csv-table {
                        color: #fff !important;
                    }

                    #smtp-csv-table thead th {
                        color: rgba(255, 255, 255, 0.85) !important;
                        font-weight: 600 !important;
                        text-transform: uppercase !important;
                        font-size: 0.75rem !important;
                        letter-spacing: 0.5px !important;
                        border-bottom: 1px solid var(--border-color, #3d3d5c) !important;
                    }

                    #smtp-csv-table tbody tr {
                        border-bottom: 1px solid var(--border-color, #3d3d5c) !important;
                        background-color: transparent !important;
                    }

                    #smtp-csv-table tbody tr:hover {
                        background-color: rgba(74, 58, 255, 0.1) !important;
                    }

                    #smtp-csv-table tbody td {
                        color: rgba(255, 255, 255, 0.9) !important;
                        vertical-align: middle !important;
                        padding: 0.5rem !important;
                    }

                    /* Code elements in CSV preview - use proper white text on dark */
                    #smtp-csv-table code {
                        color: #7dd3fc !important;
                        /* Light cyan for code in dark theme */
                        background-color: rgba(0, 0, 0, 0.3) !important;
                        padding: 2px 6px !important;
                        border-radius: 3px !important;
                        font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace !important;
                        font-size: 0.85rem !important;
                    }

                    /* Badge styling in table */
                    #smtp-csv-table .badge {
                        font-weight: 500 !important;
                    }

                    /* Custom cell styling for CSV preview to match theme */
                    .smtp-email-cell {
                        color: #ff6b9d !important;
                        /* Pink/magenta for emails - matches theme accent */
                        font-family: inherit !important;
                    }

                    .smtp-domain-badge {
                        background-color: var(--second-primary, #4a3aff) !important;
                        color: #fff !important;
                        font-weight: 500 !important;
                    }

                    .smtp-password-cell {
                        color: rgba(255, 255, 255, 0.6) !important;
                        /* Muted white for passwords */
                        font-family: inherit !important;
                    }

                    .smtp-data-cell {
                        color: rgba(255, 255, 255, 0.9) !important;
                        font-family: inherit !important;
                    }
                </style>

@endsection
    @push('scripts')
        <script>
                    // Function to toggle master inbox e         mail         field visibility
                    function toggleMasterInboxEmail() {
                        const masterInboxDropdown = $('.master-inbox');
                        if ($('#master_inbox_confirmation').val() == '1') {
                            $('.master-inbox-email').show();
                            $('#master_inbox_email').attr('required', true);
                            // When email field is shown, use col-md-6 for both
                            masterInboxDropdown.removeClass('col-md-12').addClass('col-md-6');
                        } else {
                            $('.master-inbox-email').hide();
                            // Don't clear the email field when hiding - keep the value
                            $('#master_inbox_email').removeAttr('required');
                            $('#master_inbox_email').removeClass('is-invalid'); // Remove validation error when hiding
                            $('#master_inbox_email-error').text(''); // Clear error message
                            // When email field is hidden, expand dropdown to full width
                            masterInboxDropdown.removeClass('col-md-6').addClass('col-md-12');
                        }
                    }

                    // Show/hide master inbox email field based on dropdown selection
                    $(document).on('change', '#master_inbox_confirmation', function() {
                         toggleMasterInboxEmail();
                        });

                        // Initialize field visibility on page load
                        $(document).ready(function() {
                            toggleMasterInboxEmail();

                            // Initialize expiry date calculation on page load
                            calculateExpiryDate();

                            // Initialize Select2 for SMTP Provider dropdown
                            if ($('.select2-smtp-provider').length) {
                                $('.select2-smtp-provider').select2({
                                    placeholder: 'Select or Create SMTP Provider',
                                    allowClear: true,
                                    tags: true,
                                    minimumInputLength: 0,
                                    ajax: {
                                        url: '{{ route("admin.smtp-providers.index") }}',
                                        dataType: 'json',
                                        delay: 250,
                                        data: function(params) {
                                            return {
                                                search: params.term || ''
                                            };
                                        },
                                        processResults: function(data) {
                                            return {
                                                results: data.results || []
                                            };
                                        },
                                        cache: true
                                    },
                                    createTag: function(params) {
                                        var term = $.trim(params.term);
                                        if (term === '') {
                                            return null;
                                        }
                                        return {
                                            id: 'new:' + term,
                                            text: term,
                                            newTag: true
                                        };
                                    },
                                    templateResult: function(data) {
                                        // Handle loading state
                                        if (data.loading) {
                                            return $('<span><i class="fa fa-spinner fa-spin me-2"></i>Searching...</span>');
                                        }
                                        // Handle new tag creation
                                        if (data.newTag) {
                                            return $('<span><i class="fa fa-plus-circle me-2" style="color: #28a745;"></i>Create: <strong>' + data.text + '</strong></span>');
                                        }
                                        // Handle normal options
                                        var $result = $('<span>' + (data.text || '') + '</span>');
                                        if (data.url) {
                                            $result = $('<span>' + data.text + ' <small style="opacity: 0.7;">(' + data.url + ')</small></span>');
                                        }
                                        return $result;
                                    },
                                    templateSelection: function(data) {
                                        return data.text || data.id;
                                    }
                                }).on('select2:select', function(e) {
                                    var data = e.params.data;
                                    // Check if it's a new provider being created
                                    if (data.id && data.id.toString().startsWith('new:')) {
                                        var providerName = data.id.replace('new:', '');
                                        // Create new provider via AJAX
                                        $.ajax({
                                            url: '{{ route("admin.smtp-providers.store") }}',
                                            type: 'POST',
                                            data: {
                                                _token: $('meta[name="csrf-token"]').attr('content'),
                                                name: providerName,
                                                url: ''
                                            },
                                            success: function(response) {
                                                if (response.success) {
                                                    // Update the select with the real ID
                                                    var $select = $('.select2-smtp-provider');
                                                    $select.empty();
                                                    var newOption = new Option(response.provider.text, response.provider.id, true, true);
                                                    $select.append(newOption).trigger('change');

                                                    // Show success notification
                                                    if (typeof toastr !== 'undefined') {
                                                        toastr.success('SMTP Provider "' + providerName + '" created successfully!');
                                                    }
                                                } else {
                                                    if (typeof toastr !== 'undefined') {
                                                        toastr.error(response.message || 'Failed to create provider');
                                                    }
                                                }
                                            },
                                            error: function(xhr) {
                                                var message = 'Failed to create provider';
                                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                                    message = xhr.responseJSON.message;
                                                }
                                                if (typeof toastr !== 'undefined') {
                                                    toastr.error(message);
                                                }
                                            }
                                        });
                                    }
                                });
                            }
                        });

                        // Function to calculate and display expiry date (12 months from purchase date)
                        function calculateExpiryDate() {
                            const purchaseDateInput = $('#purchase_date');
                            const expiryDateInput = $('#expiry_date');
                            const expiryDateContainer = $('#expiry_date_container');

                            if (purchaseDateInput.val()) {
                                const purchaseDate = new Date(purchaseDateInput.val());

                                // Add 12 months to purchase date
                                const expiryDate = new Date(purchaseDate);
                                expiryDate.setMonth(expiryDate.getMonth() + 12);

                                // Format the expiry date
                                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                                const formattedExpiryDate = expiryDate.toLocaleDateString('en-US', options);

                                // Display expiry date
                                expiryDateInput.val(formattedExpiryDate);
                                expiryDateContainer.show();
                            } else {
                                // Hide expiry date if no purchase date is selected
                                expiryDateContainer.hide();
                                expiryDateInput.val('');
                            }
                        }

                        // Listen for changes to purchase date
                        $(document).on('change', '#purchase_date', function() {
                            calculateExpiryDate();
                        });

                        // Initialize domain arrays early for global functions
                        let usedDomains = []; // Array to store used domains that cannot be edited
                        let editableDomains = []; // Array to store editable domains

                        // Initialize used domains from pool data
                        @if(isset($pool) && $pool->domains)
                            @php
                                $existingDomains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
                            @endphp
                            @if(is_array($existingDomains))
                                @foreach($existingDomains as $domain)
                                    @if(isset($domain['is_used']) && $domain['is_used'] && isset($domain['name']))
                                        usedDomains.push({
                                            id: '{{ $domain['id'] ?? '' }}',
                                            name: '{{ $domain['name'] }}',
                                            is_used: true,
                                            prefix_statuses: @json($domain['prefix_statuses'] ?? null)
                                        });
                                    @elseif(isset($domain['name']))
                                        editableDomains.push('{{ $domain['name'] }}');
                                    @endif
                                @endforeach
                            @endif
                        @endif

                        // Global function for calculating total inboxes and updating price - accessible from import functionality
                    function calculateTotalInboxes() {
                        const domainsText = $('#domains').val();
                        const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;

                        let editableDomainsCount = 0;
                        if (domainsText) {
                            // Split domains by newlines and filter out empty entries
                            const domains = domainsText.split(/[\n,]+/)
                                .map(domain => domain.trim())
                                .filter(domain => domain.length > 0);

                            const uniqueDomains = [...new Set(domains)];
                            editableDomainsCount = uniqueDomains.length;
                        }

                        // Total domains include both editable and used domains (safely check if usedDomains exists)
                        const usedDomainsCount = (typeof usedDomains !== 'undefined') ? usedDomains.length : 0;
                        const totalDomainsCount = editableDomainsCount + usedDomainsCount;
                        const calculatedInboxes = totalDomainsCount * inboxesPerDomain;

                        // Always use calculated inboxes and apply current order limit validation
                        $('#total_inboxes').val(calculatedInboxes);
                        updateRemainingInboxesBar(calculatedInboxes);
                        updatePriceDisplay(calculatedInboxes);
                        return calculatedInboxes;
                    }
                    // Global function for updating price display based on total inboxes
                    function updatePriceDisplay(totalInboxes) {
                        const currentPlan = null; // Removed plan dependency
                        const poolInfo = @json($pool ?? null);

                        // Calculate TOTAL_INBOXES based on poolInfo.total_inboxes
                        let TOTAL_INBOXES = 0;
                        if (poolInfo && poolInfo.total_inboxes !== undefined) {
                            const rawTotalInboxes = poolInfo.total_inboxes;
                            const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;

                            // Calculate maximum usable inboxes based on inboxes_per_domain
                            // For example: 500 total inboxes with 3 inboxes per domain = 166 domains max = 498 usable inboxes
                            const maxDomainsAllowed = Math.floor(rawTotalInboxes / inboxesPerDomain);
                            TOTAL_INBOXES = maxDomainsAllowed * inboxesPerDomain;
                        }

                        const submitButton = $('button[type="submit"]');
                        let priceHtml = '';

                        if (!totalInboxes || totalInboxes === 0) {
                            priceHtml = `
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div>
                                        <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png" width="30" alt="">
                                    </div>
                                    <div>
                                        <span class="opacity-50">Officially Google Workspace Inboxes</span>
                                        <br>
                                        <span>Please add domains and inboxes to calculate price</span>
                                    </div>
                                </div>
                                <h6><span class="theme-text">Original Price:</span> <small>Please add domains and inboxes to calculate price</small></h6>
                                <h6><span class="theme-text">Discount:</span> 0%</h6>
                                <h6><span class="theme-text">Total:</span> <small>Please add domains and inboxes to calculate price</small></h6>
                            `;
                        } else if (currentPlan && TOTAL_INBOXES > 0 && totalInboxes > TOTAL_INBOXES) {
                            // Order limit exceeded (only show if order has a limit, i.e., total_inboxes > 0)
                            priceHtml = `
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Order Limit Exceeded</strong> — You currently have ${totalInboxes} inboxes, but this order supports only ${TOTAL_INBOXES} inboxes.
                                    <br><small>Please reduce the number of domains.</small>
                                </div>
                                <h6><span class="theme-text">Original Price:</span> <small>Exceeds order limit</small></h6>
                                <h6><span class="theme-text">Discount:</span> 0%</h6>
                                <h6><span class="theme-text">Total:</span> <small>Please reduce domains</small></h6>
                            `;
                            if (submitButton.length) {
                                submitButton.prop('disabled', true);
                                submitButton.hide();
                            }
                        } else if (currentPlan) {
                            const originalPrice = parseFloat(currentPlan.price * totalInboxes).toFixed(2);
                            priceHtml = `
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div>
                                        <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png" width="30" alt="">
                                    </div>
                                    <div>
                                        <span class="opacity-50">Officially Google Workspace Inboxes</span>
                                        <br>
                                        <span>${totalInboxes} x $${parseFloat(currentPlan.price).toFixed(2)} <small>/${currentPlan.duration}</small></span>
                                    </div>
                                </div>
                                <h6><span class="theme-text">Original Price:</span> $${originalPrice}</h6>
                                <h6><span class="theme-text">Discount:</span> 0%</h6>
                                <h6><span class="theme-text">Total:</span> $${originalPrice} <small>/${currentPlan.duration}</small></h6>
                            `;
                            if (submitButton.length) {
                                submitButton.prop('disabled', false);
                                submitButton.show();
                            }
                        }

                        // Update the price display section
                        $('.price-display-section').html(priceHtml);
                    }

                    // Global function for updating remaining inboxes progress bar
                    function updateRemainingInboxesBar(currentInboxes = null, totalLimit = null) {
                        // Get current inboxes if not provided
                        if (currentInboxes === null) {
                            const domainsText = $('#domains').val() || '';
                            const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;

                            let editableDomainsCount = 0;
                            if (domainsText) {
                                const domains = domainsText.split(/[\n,]+/)
                                    .map(domain => domain.trim())
                                    .filter(domain => domain.length > 0);
                                const uniqueDomains = [...new Set(domains)];
                                editableDomainsCount = uniqueDomains.length;
                            }

                            // Include used domains in total count (safely check if usedDomains exists)
                            const usedDomainsCount = (typeof usedDomains !== 'undefined') ? usedDomains.length : 0;
                            const totalDomainsCount = editableDomainsCount + usedDomainsCount;
                            currentInboxes = totalDomainsCount * inboxesPerDomain;
                        }

                        // For progress bar display, maxInboxes should be the current total (used + editable domains)
                        // This shows "current inboxes / total current inboxes" rather than "current inboxes / pool limit"
                        const poolInfo = @json(isset($pool) ? $pool : null);
                        const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;

                        // Calculate current total domains (used + editable)
                        const domainsText = $('#domains').val() || '';
                        let editableDomainsCount = 0;
                        if (domainsText) {
                            const domains = domainsText.split(/[\n,]+/)
                                .map(domain => domain.trim())
                                .filter(domain => domain.length > 0);
                            const uniqueDomains = [...new Set(domains)];
                            editableDomainsCount = uniqueDomains.length;
                        }
                        const usedDomainsCount = (typeof usedDomains !== 'undefined') ? usedDomains.length : 0;
                        const totalCurrentDomains = editableDomainsCount + usedDomainsCount;
                        const maxInboxes = totalCurrentDomains * inboxesPerDomain;

                        // Update hidden form fields for server submission
                        $('#current_inboxes').val(currentInboxes);
                        $('#max_inboxes').val(maxInboxes);

                        // Calculate percentage used (only if order has limits)
                        const percentageUsed = maxInboxes > 0 ? (currentInboxes / maxInboxes) * 100 : 0;

                        // Update progress bar elements
                        const progressBar = $('#remaining-inboxes-bar');
                        const progressText = $('#remaining-inboxes-text');
                        const progressNote = $('#remaining-inboxes-note');

                        if (progressBar.length === 0) {
                            return; // Progress bar not found, exit gracefully
                        }

                        // Set width and aria values
                        progressBar.css('width', Math.min(percentageUsed, 100) + '%');
                        progressBar.attr('aria-valuenow', Math.min(percentageUsed, 100));
                        progressBar.attr('aria-valuemax', 100);

                        // Check against pool limits for validation
                        let poolLimit = 0;
                        let exceedsLimit = false;

                        if (poolInfo && poolInfo.total_inboxes !== undefined && poolInfo.total_inboxes > 0) {
                            const rawTotalInboxes = poolInfo.total_inboxes;

                            // For your case: rawTotalInboxes should be 3, inboxesPerDomain should be 1
                            // So poolLimit should be 3 (3 domains × 1 inbox = 3 inboxes)
                            poolLimit = rawTotalInboxes; // Direct assignment - pool limit is the total inboxes allowed

                            // Check if current usage exceeds pool limit
                            exceedsLimit = currentInboxes > poolLimit;

                            // Show domain validation error if limit exceeded (but don't overwrite other validation errors)
                            if (exceedsLimit) {
                                const currentError = $('#domains-error').text();
                                // Only show limit error if there's no other validation error (like duplicates)
                                if (!currentError || (!currentError.includes('Duplicate') && !currentError.includes('Invalid'))) {
                                    const domainsField = $('#domains');
                                    // const usedDomainsText = usedDomainsCount > 0 ? ` (${editableDomainsCount} editable + ${usedDomainsCount} used)` : '';
                                    const usedDomainsText = '';
                                    domainsField.addClass('is-invalid');
                                    $('#domains-error').html(`
                                        <strong>Order Limit Exceeded</strong> — You currently have ${currentInboxes} inboxes, but this order supports only ${poolLimit} usable inboxes.
                                        <br><small>Pool Limit: ${rawTotalInboxes} total inboxes with ${inboxesPerDomain} inbox${inboxesPerDomain > 1 ? 'es' : ''} per domain</small>
                                    `);
                                }
                            } else {
                                // Only clear limit-related errors, keep other validation errors (like duplicates)
                                const currentError = $('#domains-error').text();
                                if (currentError && currentError.includes('Order Limit Exceeded')) {
                                    $('#domains').removeClass('is-invalid');
                                    $('#domains-error').text('');
                                }
                            }
                        }

                        // Update text display - show current usage vs current total
                        if (maxInboxes > 0) {
                            // Show current usage: "current inboxes / total current inboxes"
                            // const domainBreakdown = usedDomainsCount > 0 ? ` (${editableDomainsCount} editable + ${usedDomainsCount} used)` : '';
                            const domainBreakdown = '';

                            if (exceedsLimit && poolLimit > 0) {
                                // Show limit exceeded in progress text
                                progressText.text(`${currentInboxes} / ${poolLimit} inboxes used (LIMIT EXCEEDED)${domainBreakdown}`);
                                progressBar.css('width', '100%');
                                progressBar.css('background', 'linear-gradient(45deg, #dc3545, #c82333)');
                                progressNote.html(`(Pool limit: ${poolLimit} inboxes, Current: ${currentInboxes} inboxes)`);

                                // Hide submit button when order limit is exceeded
                                const submitButton = $('button[type="submit"]');
                                if (submitButton.length) {
                                    submitButton.prop('disabled', true);
                                    submitButton.hide();
                                }
                            } else {
                                // Normal display - show current vs current (not vs pool limit)
                                progressText.text(`${currentInboxes} / ${maxInboxes} inboxes used${domainBreakdown}`);
                                progressBar.css('width', '100%');
                                progressBar.css('background', 'linear-gradient(45deg, #28a745, #20c997)');

                                // Show submit button when within order limits
                                const submitButton = $('button[type="submit"]');
                                if (submitButton.length) {
                                    submitButton.prop('disabled', false);
                                    submitButton.show();
                                }

                                // Show breakdown information with pool limit info if available
                                if (poolLimit > 0) {
                                    const poolLimitText = poolLimit !== maxInboxes ? ` (Pool limit: ${poolLimit})` : '';
                                    if (usedDomainsCount > 0) {
                                        progressNote.html(`(Total: ${totalCurrentDomains} domains - ${editableDomainsCount} editable, ${usedDomainsCount} used${poolLimitText})`);
                                    } else {
                                        progressNote.html(`(Total: ${totalCurrentDomains} domains, ${inboxesPerDomain} inbox${inboxesPerDomain > 1 ? 'es' : ''} per domain${poolLimitText})`);
                                    }
                                } else {
                                    if (usedDomainsCount > 0) {
                                        progressNote.html(`(Total: ${totalCurrentDomains} domains - ${editableDomainsCount} editable, ${usedDomainsCount} used)`);
                                    } else {
                                        progressNote.html(`(Total: ${totalCurrentDomains} domains, ${inboxesPerDomain} inbox${inboxesPerDomain > 1 ? 'es' : ''} per domain)`);
                                    }
                                }
                            }
                        } else {
                            // No domains yet
                            progressText.text('0 / 0 inboxes used');
                            progressBar.css('width', '0%');
                            progressNote.html('(Add domains to see usage)');
                            progressBar.css('background', 'linear-gradient(45deg, #6c757d, #5a6268)');
                        }
                    }

                    $(document).ready(function() {
                        // Initialize domain counts and calculations on page load
                        if (typeof calculateTotalInboxes === 'function') {
                            calculateTotalInboxes();
                        }
                        if (typeof updateRemainingInboxesBar === 'function') {
                            updateRemainingInboxesBar();
                        }

                        // Show pool limit information
                        const poolInfo = @json(isset($pool) ? $pool : null);
                        if (poolInfo && poolInfo.total_inboxes) {
                            if (poolInfo.total_inboxes === 0) {
                                toastr.info('This pool has unlimited inboxes. No inbox limits apply.', 'Unlimited Pool', {
                                    timeOut: 4000,
                                    closeButton: true,
                                    progressBar: true
                                });
                            } else {
                                toastr.info(`This order allows up to ${poolInfo.total_inboxes} inboxes.`, 'Order Limit', {
                                    timeOut: 4000,
                                    closeButton: true,
                                    progressBar: true
                                });
                            }
                        }

                        // Initialize Order Import Modal
                        $('#orderImportBtn').on('click', function() {
                            initializeOrdersImportTable();
                        });

                        // Add event listeners for automatic progress bar updates
                        $(document).on('input change', '#domains, #inboxes_per_domain', function() {
                            if (typeof calculateTotalInboxes === 'function') {
                                calculateTotalInboxes();
                            }
                        });

                        // Initial progress bar update on page load
                        setTimeout(() => {
                            if (typeof updateRemainingInboxesBar === 'function') {
                                updateRemainingInboxesBar();
                            }
                        }, 1000);

                        function initializeOrdersImportTable() {
                            // Show loading indicator
                            Swal.fire({
                                title: 'Loading Orders...',
                                text: 'Please wait while we fetch your orders.',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false,
                                showConfirmButton: false,
                                backdrop: true,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // Clear existing table content
                            $('#ordersImportTable tbody').empty();

                            // AJAX call to fetch orders data
                            $.ajax({
                                url: "{{ route('admin.pools.import.data') }}",
                                type: "GET",
                                data: {
                                    for_import: true,
                                    exclude_current: "{{ isset($pool) ? $pool->id : '' }}"
                                },
                                success: function(response) {
                                    // Close loading indicator
                                    Swal.close();

                                    // Show modal
                                    $('#orderImportModal').modal('show');

                                    // Populate table with data
                                    populateOrdersTable(response.data || []);
                                },
                                error: function(xhr, status, error) {
                                    // Close loading indicator
                                    Swal.close();

                                    console.error('Error loading orders:', error);
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Failed to load orders data. Please try again.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            });
                        }

                        function populateOrdersTable(orders) {
                            const tbody = $('#ordersImportTable tbody');
                            tbody.empty();

                            if (!orders || orders.length === 0) {
                                tbody.append(`
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fa-solid fa-inbox me-2"></i>
                                            No orders available for import
                                        </td>
                                    </tr>
                                `);
                                return;
                            }

                            orders.forEach(function(order) {
                                const row = `
                                    <tr class="table-row-hover">
                                        <td class="text-center">${order.id || 'N/A'}</td>
                                        <td title="${getPlanName(order)}">${getPlanName(order)}</td>
                                        <td class="text-center">${order.total_inboxes || '0'}</td>
                                        <td class="text-center">${order.status_badge || 'N/A'}</td>
                                        <td class="text-center" title="${order.created_at_formatted || 'N/A'}">${order.created_at_formatted || 'N/A'}</td>
                                        <td class="text-center">${getActionButton(order)}</td>
                                    </tr>
                                `;
                                tbody.append(row);
                            });

                            // Add horizontal scroll indicators for better UX
                            setTimeout(() => {
                                addScrollIndicators();
                            }, 100);
                        }

                        // Function to add scroll indicators for better horizontal scrolling UX
                        function addScrollIndicators() {
                            const tableContainer = $('.table-responsive');
                            if (tableContainer.length) {
                                const scrollWidth = tableContainer[0].scrollWidth;
                                const clientWidth = tableContainer[0].clientWidth;

                                // Only show indicators if table is wider than container
                                if (scrollWidth > clientWidth) {
                                    // Add scroll indicators
                                    if (!$('.scroll-indicator-left').length) {
                                        tableContainer.before(`
                                            <div class="scroll-indicators">
                                                <div class="scroll-indicator-left">
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                </div>
                                                <div class="scroll-indicator-right">
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </div>
                                            </div>
                                        `);
                                    }

                                    // Handle scroll events to update indicators
                                    tableContainer.on('scroll', function() {
                                        const scrollLeft = $(this).scrollLeft();
                                        const maxScroll = scrollWidth - clientWidth;

                                        $('.scroll-indicator-left').toggleClass('visible', scrollLeft > 0);
                                        $('.scroll-indicator-right').toggleClass('visible', scrollLeft < maxScroll - 5);
                                    });

                                    // Initial state
                                    $('.scroll-indicator-left').removeClass('visible');
                                    $('.scroll-indicator-right').addClass('visible');
                                }
                            }
                        }

                        function getPlanName(order) {
                            if (order.plan && order.plan.name) {
                                return order.plan.name;
                            }
                            return 'N/A';
                        }

                        function getActionButton(order) {
                            return `
                                <button type="button" 
                                        class="import-order-btn" 
                                        data-order-id="${order.id}"
                                        title="Import this order data">
                                    <i class="fa-solid fa-download me-1"></i>
                                    Import
                                </button>
                            `;
                        }    
                        // Handle order import
                        $(document).on('click', '.import-order-btn', function() {
                            const poolId = $(this).data('pool-id');

                            Swal.fire({
                                title: 'Import Order Data?',
                                text: 'This will replace all current form data with the selected order\'s information. Are you sure?',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, Import Data',
                                cancelButtonText: 'Cancel'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    importPoolData(poolId);
                                }
                            });
                        });

                        function importPoolData(poolId) {
                            // Show loading
                            Swal.fire({
                                title: 'Importing Order Data...',
                                text: 'Please wait while we import the order data.',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false,
                                showConfirmButton: false,
                                backdrop: true,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            $.ajax({
                                url: "{{ route('admin.pools.import-data', ':id') }}".replace(':id', poolId),
                                method: 'GET',
                                success: function(response) {
                                    if (response.success && response.data) {
                                        populateFormWithOrderData(response.data);
                                        $('#orderImportModal').modal('hide');

                                        Swal.fire({
                                            title: 'Success!',
                                            text: 'Order data has been imported successfully.',
                                            icon: 'success',
                                            timer: 2000,
                                            showConfirmButton: false
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: response.message || 'Failed to import order data.',
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                },
                                error: function(xhr) {
                                    Swal.close();
                                    let errorMessage = 'An error occurred while importing order data.';
                                    if (xhr.responseJSON && xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    }

                                    Swal.fire({
                                        title: 'Error!',
                                        text: errorMessage,
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                }
                            });
                        }

                        function populateFormWithOrderData(orderData) {
                            // Set import flag to prevent toastr notifications during import
                            isImporting = true;

                            // Clear any existing validation errors
                            $('.is-invalid').removeClass('is-invalid');
                            $('.invalid-feedback').text('');

                            // Specifically clear domains error
                            $('#domains').removeClass('is-invalid');
                            $('#domains-error').text('');

                            const poolData = orderData;
                            if (!poolData) {
                                toastr.warning('No detailed information available for this order.');
                                return;
                            }
                            // Populate basic fields
                            if (poolInfo.forwarding_url) $('#forwarding').val(poolInfo.forwarding_url);
                            if (poolInfo.hosting_platform) $('#hosting').val(poolInfo.hosting_platform).trigger('change');
                            if (poolInfo.domains) {
                                // Handle both old format (array of strings) and new format (array of objects with id/name)
                                let domainsText = '';
                                if (Array.isArray(poolInfo.domains)) {
                                    if (poolInfo.domains.length > 0 && typeof poolInfo.domains[0] === 'object' && poolInfo.domains[0].name) {
                                        // New format: array of objects with id/name
                                        domainsText = poolInfo.domains.map(d => d.name).join('\n');
                                        // Update existing domain IDs to avoid duplicates
                                        poolInfo.domains.forEach(domain => {
                                            if (domain.id && domain.name) {
                                                existingDomainIds.set(domain.name, domain.id);
                                                // Extract sequence number from imported ID (format: poolId_sequence)
                                                const importIdParts = domain.id.toString().split('_');
                                                if (importIdParts.length === 2 && !isNaN(importIdParts[1])) {
                                                    domainSequenceCounter = Math.max(domainSequenceCounter, parseInt(importIdParts[1]) + 1);
                                                }
                                            }
                                        });
                                    } else {
                                        // Old format: array of strings
                                        domainsText = poolInfo.domains.join('\n');
                                    }
                                } else if (typeof poolInfo.domains === 'string') {
                                    domainsText = poolInfo.domains;
                                }

                                $('#domains').val(domainsText);
                                // Trigger domain counting after populating domains
                                setTimeout(() => {
                                    if (typeof countDomains === 'function') {
                                        countDomains();
                                    }
                                }, 100);
                            }
                            if (poolData.sending_platform) $('#sending_platform').val(poolData.sending_platform).trigger('change');
                            if (poolData.inboxes_per_domain) $('#inboxes_per_domain').val(poolData.inboxes_per_domain).trigger('change');

                            // Don't import total_inboxes - always use current order's limit

                            // Populate email account information
                            if (poolData.first_name) $('input[name="first_name"]').val(poolData.first_name);
                            if (poolData.last_name) $('input[name="last_name"]').val(poolData.last_name);
                            if (poolData.email_persona_password) $('input[name="email_persona_password"]').val(poolData.email_persona_password);
                            if (poolData.email_persona_picture_link) $('input[name="email_persona_picture_link"]').val(poolData.email_persona_picture_link);
                            // Handle master inbox email and confirmation
                            if (poolData.master_inbox_confirmation !== undefined) {
                                $('#master_inbox_confirmation').val(poolData.master_inbox_confirmation ? '1' : '0');
                            }
                            if (poolData.master_inbox_email) {
                                $('input[name="master_inbox_email"]').val(poolData.master_inbox_email);
                            }
                            // Trigger master inbox email field visibility toggle after setting values
                            if (typeof toggleMasterInboxEmail === 'function') {
                                toggleMasterInboxEmail();
                            }
                            if (poolData.additional_info) $('textarea[name="additional_info"]').val(poolData.additional_info);

                            // Populate prefix variants if available
                            if (poolData.prefix_variants) {
                                try {
                                    const prefixVariants = typeof poolData.prefix_variants === 'string' 
                                        ? JSON.parse(poolData.prefix_variants) 
                                        : poolData.prefix_variants;

                                    Object.keys(prefixVariants).forEach(key => {
                                        const input = $(`input[name="prefix_variants[${key}]"]`);
                                        if (input.length && prefixVariants[key]) {
                                            input.val(prefixVariants[key]);
                                        }
                                    });
                                } catch (e) {
                                    console.warn('Could not parse prefix variants:', e);
                                }
                            }

                            // Populate prefix variants details if available
                            if (poolData.prefix_variants_details) {
                                try {
                                    const prefixVariantsDetails = typeof poolData.prefix_variants_details === 'string' 
                                        ? JSON.parse(poolData.prefix_variants_details) 
                                        : poolData.prefix_variants_details;

                                    Object.keys(prefixVariantsDetails).forEach(variantKey => {
                                        const details = prefixVariantsDetails[variantKey];
                                        if (details) {
                                            // Populate first name
                                            if (details.first_name) {
                                                $(`input[name="prefix_variants_details[${variantKey}][first_name]"]`).val(details.first_name);
                                            }
                                            // Populate last name
                                            if (details.last_name) {
                                                $(`input[name="prefix_variants_details[${variantKey}][last_name]"]`).val(details.last_name);
                                            }
                                            // Populate profile link
                                            if (details.profile_link) {
                                                $(`input[name="prefix_variants_details[${variantKey}][profile_link]"]`).val(details.profile_link);
                                            }
                                        }
                                    });
                                } catch (e) {
                                    console.warn('Could not parse prefix variants details:', e);
                                }
                            }

                            // Populate dynamic platform fields
                            setTimeout(() => {
                                populateDynamicFields(poolData);
                            }, 500);

                            // Recalculate totals and check domain limits with a slight delay to ensure all fields are populated
                            setTimeout(() => {
                                if (typeof calculateTotalInboxes === 'function') {
                                    calculateTotalInboxes();
                                }

                                // Update domain count badge after import
                                if (typeof countDomains === 'function') {
                                    countDomains();
                                }

                                // Check domain cutting if needed
                                checkDomainCutting();

                                // Force update progress bar and price one more time to ensure it's correct
                                setTimeout(() => {
                                    if (typeof updateRemainingInboxesBar === 'function') {
                                        updateRemainingInboxesBar();
                                    }
                                    if (typeof calculateTotalInboxes === 'function') {
                                        calculateTotalInboxes();
                                    }
                                    // Final domain count update
                                    if (typeof countDomains === 'function') {
                                        // alert('Domains have been imported successfully. Please check the domain count.');
                                        $('#domains').trigger('input'); // Trigger input to recalculate domains
                                        countDomains();
                                    }
                                    $('#domains').trigger('input'); // Trigger input to recalculate domains
                                }, 200);
                            }, 600);

                            // Clear import flag after a delay to ensure all operations are complete
                            setTimeout(() => {
                                isImporting = false;

                                // Check if imported data exceeds current order limits and show appropriate notification
                                const finalDomains = $('#domains').val();
                                const finalInboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;

                                if (finalDomains) {
                                    const domainsArray = finalDomains.split(/[\n,]+/).filter(d => d.trim().length > 0);
                                    const totalInboxes = domainsArray.length * finalInboxesPerDomain;

                                    // Use the current order's total_inboxes value for validation (not imported)
                                    const poolInfo = @json(optional($pool));
                                    let currentOrderLimit = 0;
                                    let isConfigurationError = false;

                                    if (poolInfo && poolInfo.total_inboxes) {
                                        // Check for configuration error first (order has limit but can't fit any domains)
                                        if (poolInfo.total_inboxes > 0 && poolInfo.total_inboxes < finalInboxesPerDomain) {
                                            currentOrderLimit = 0;
                                            isConfigurationError = true;
                                        } else {
                                            const maxDomainsAllowed = Math.floor(poolInfo.total_inboxes / finalInboxesPerDomain);
                                            currentOrderLimit = maxDomainsAllowed * finalInboxesPerDomain;
                                        }
                                    }

                                    // Prioritize configuration error over order limit exceeded
                                    if (isConfigurationError) {
                                        // Configuration error: order has limit but can't fit any domains
                                        $('#domains').addClass('is-invalid');
                                        $('#domains-error').text('Can’t create inboxes with current settings. Please reduce the inboxes per domain, lower the domain count, or contact support to increase your order.');
                                        toastr.warning('Can’t create inboxes with current settings. Please reduce inboxes per domain or contact support.', 'Import Complete - Configuration Issue', {
                                            timeOut: 8000,
                                            closeButton: true,
                                            progressBar: true
                                        });
                                    } else if (currentOrderLimit > 0 && totalInboxes > currentOrderLimit) {
                                        // Show domains-error div for exceeding current order limit
                                        $('#domains').addClass('is-invalid');
                                        $('#domains-error').html(`
                                            <strong>Order Limit Exceeded</strong> — You currently have ${totalInboxes} inboxes, but this order supports only ${currentOrderLimit} usable inboxes.
                                            <br><small>Usable Limit: ${currentOrderLimit} inboxes</small>
                                        `);
                                        toastr.warning(`Order Limit Exceeded — You currently have ${totalInboxes} inboxes, but this order supports only ${currentOrderLimit} usable inboxes.`, 'Import Complete - Order Limit Exceeded', {
                                            timeOut: 8000,
                                            closeButton: true,
                                            progressBar: true
                                        });
                                    } else {
                                        // Clear domain errors if within valid range
                                        $('#domains').removeClass('is-invalid');
                                        $('#domains-error').text('');
                                        toastr.success('Order data imported successfully!');
                                    }
                                } else {
                                    // Clear domain errors if no domains
                                    $('#domains').removeClass('is-invalid');
                                    $('#domains-error').text('');
                                    toastr.success('Order data imported successfully!');
                                }
                            }, 1000);

                        }

                        function populateDynamicFields(poolData) {
                            // Populate hosting platform fields
                            const hostingFields = [
                                'backup_codes', 'bison_url', 'bison_workspace', 
                                'platform_login', 'platform_password'
                            ];

                            hostingFields.forEach(field => {
                                if (poolData[field]) {
                                    const input = $(`input[name="${field}"], textarea[name="${field}"]`);
                                    if (input.length) {
                                        input.val(poolData[field]);
                                    }
                                }
                            });

                            // Populate sending platform fields
                            const sendingFields = [
                                'sequencer_login', 'sequencer_password'
                            ];

                            sendingFields.forEach(field => {
                                if (poolData[field]) {
                                    const input = $(`input[name="${field}"], textarea[name="${field}"]`);
                                    if (input.length) {
                                        input.val(poolData[field]);
                                    }
                                }
                            });
                        }

                        function checkDomainCutting() {
                            // Don't auto-trim during import
                            if (isImporting) {
                                return;
                            }

                            const domainsText = $('#domains').val();
                            const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;

                            if (!domainsText) return;

                            const domains = domainsText.split(/[\n,]+/)
                                .map(domain => domain.trim())
                                .filter(domain => domain.length > 0);

                            const totalInboxes = domains.length * inboxesPerDomain;

                            // Always use current order's total inboxes limit (not imported)
                            const poolInfo = @json(optional($pool));
                            let TOTAL_INBOXES = 0;

                            if (poolInfo && poolInfo.total_inboxes !== undefined) {
                                const rawTotalInboxes = poolInfo.total_inboxes;

                                // Calculate maximum usable inboxes based on inboxes_per_domain
                                // For example: 500 total inboxes with 3 inboxes per domain = 166 domains max = 498 usable inboxes
                                const maxDomainsAllowed = Math.floor(rawTotalInboxes / inboxesPerDomain);
                                TOTAL_INBOXES = maxDomainsAllowed * inboxesPerDomain;
                            }

                            // Only enforce limits if order has a limit (total_inboxes > 0)
                            if (TOTAL_INBOXES > 0 && totalInboxes > TOTAL_INBOXES) {
                                // Automatically trim domains to fit within order limit
                                const maxDomainsAllowed = Math.floor(TOTAL_INBOXES / inboxesPerDomain);
                                const trimmedDomains = domains.slice(0, maxDomainsAllowed);
                                const removedCount = domains.length - maxDomainsAllowed;

                                $('#domains').val(trimmedDomains.join('\n'));

                                // Clear the error message and update validation
                                $('#domains').removeClass('is-invalid');
                                $('#domains-error').text('');

                                // Recalculate totals after trimming
                                if (typeof calculateTotalInboxes === 'function') {
                                    calculateTotalInboxes();
                                }

                                // Force update the price display after domain trimming
                                setTimeout(() => {
                                    if (typeof calculateTotalInboxes === 'function') {
                                        calculateTotalInboxes();
                                    }

                                    // Re-run validation to update error messages properly after trimming
                                    if (typeof validateDomainsFormat === 'function') {
                                        validateDomainsFormat(true, false);
                                    }

                                    // Also update domain count and other UI elements
                                    if (typeof countDomains === 'function') {
                                        countDomains();
                                    }
                                    if (typeof updateRemainingInboxesBar === 'function') {
                                        updateRemainingInboxesBar();
                                    }
                                }, 100);

                                // Show notification about the automatic trimming
                                const poolInfo = @json(optional($pool));
                                const rawTotal = poolInfo && poolInfo.total_inboxes ? poolInfo.total_inboxes : TOTAL_INBOXES;

                                Swal.fire({
                                    title: 'Domains Automatically Trimmed',
                                    html: `<strong>${removedCount}</strong> domains were automatically removed because your order limit is <strong>${TOTAL_INBOXES}</strong> usable inboxes${rawTotal > TOTAL_INBOXES ? ` (${rawTotal} total with ${inboxesPerDomain} inboxes per domain)` : ''}.<br>`,
                                    icon: 'info',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#3085d6'
                                });
                            }
                        }

                    });  // Close the first $(document).ready() block
                    // Second document ready block for existing functionality
                    $(document).ready(function() {
                        // Flag to prevent multiple popups for the same limit exceeded situation
                        let limitExceededShown = false;
                        // Flag to prevent toastr notifications during import
                        let isImporting = false;
                        // Master inbox email functionality - no confirmation needed

                        // ========================================
                        // DOMAIN VALIDATION SYSTEM
                        // ========================================
                        // New unified validation system using one comprehensive function:
                        // - validateDomainsFormat(checkLimits, showPopups) - Main validation function
                        // - validateDomainLimits() - Helper for limit checking  
                        // - validateAndTrimDomains() - Legacy wrapper for backward compatibility
                        // ========================================

                        /**
                         * Comprehensive domain validation function that handles all domain validations:
                         * - Empty domain validation
                         * - Duplicate domain detection  
                         * - Domain format validation
                         * - Configuration error checking
                         * - Order limit validation and auto-trimming
                         * - Error display and clearing
                         * 
                         * @param {boolean} checkLimits - Whether to check order limits (default: true)
                         * @param {boolean} showPopups - Whether to show popups for limits (default: true)
                         * @returns {boolean} - True if validation passes, false otherwise
                         */
                        function validateDomainsFormat(checkLimits = true, showPopups = true) {
                            const domainsField = $('#domains');
                            const domainsText = domainsField.val();
                            const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;

                            // Handle empty domains input
                            if (!domainsText.trim()) {
                                // Only clear errors if no high-priority errors exist
                                if (!$('#domains-error').text().includes('Duplicate') && 
                                    !$('#domains-error').text().includes('Invalid') && 
                                    !$('#domains-error').text().includes('Cannot create domains')) {
                                    domainsField.removeClass('is-invalid');
                                    $('#domains-error').text('').removeClass('show-error');
                                }
                                calculateTotalInboxes();
                                return true;
                            }

                            // Parse and clean domains
                            let domains = domainsText.split(/[\n,]+/)
                                .map(domain => domain.trim())
                                .filter(domain => domain.length > 0);

                            if (domains.length === 0) {
                                calculateTotalInboxes();
                                return true;
                            }

                            // Step 1: Check for duplicate domains
                            const seen = new Set();
                            const duplicates = domains.filter(domain => {
                                if (seen.has(domain)) {
                                    return true;
                                }
                                seen.add(domain);
                                return false;
                            });

                            if (duplicates.length > 0) {
                                domainsField.addClass('is-invalid');
                                $('#domains-error')
                                    .text(`Duplicate domains are not allowed: ${duplicates.join(', ')}`)
                                    .addClass('show-error');
                                calculateTotalInboxes();
                                return false;
                            }

                            // Step 2: Validate domain format
                            const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/;
                            const domainRegexSimple = /^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/;
                            const invalidDomains = domains.filter(d => !domainRegex.test(d) && !domainRegexSimple.test(d));

                            if (invalidDomains.length > 0) {
                                domainsField.addClass('is-invalid');
                                $('#domains-error')
                                    .text(`Invalid domain format: ${invalidDomains.join(', ')} Valid formats: example.com, example.co.uk`)
                                    .addClass('show-error');
                                calculateTotalInboxes();
                                return false;
                            }

                            // Step 3: Check limits if requested
                            if (checkLimits) {
                                return validateDomainLimits(domains, domainsField, inboxesPerDomain, showPopups);
                            }

                            // All basic validations passed - clear errors
                            if (!$('#domains-error').text().includes('Cannot create domains')) {
                                domainsField.removeClass('is-invalid');
                                $('#domains-error').text('').removeClass('show-error');
                            }

                            calculateTotalInboxes();
                            return true;
                        }
                        /**
                         * Helper function to validate domain limits and handle configuration errors
                         * @param {Array} domains - Array of domain strings
                         * @param {jQuery} domainsField - jQuery object for domains textarea
                         * @param {number} inboxesPerDomain - Number of inboxes per domain
                         * @param {boolean} showPopups - Whether to show popups
                         * @returns {boolean} - True if within limits, false otherwise
                         */
                        function validateDomainLimits(domains, domainsField, inboxesPerDomain, showPopups) {
                            const totalInboxes = domains.length * inboxesPerDomain;
                            const poolInfo = @json(optional($pool));

                            // Calculate limits based on pool configuration
                            let TOTAL_INBOXES = 0;
                            let isConfigurationError = false;

                            if (poolInfo && poolInfo.total_inboxes !== undefined) {
                                const rawTotalInboxes = poolInfo.total_inboxes;

                                // Check for configuration error (order limit can't fit any domains)
                                if (rawTotalInboxes > 0 && rawTotalInboxes < inboxesPerDomain) {
                                    TOTAL_INBOXES = 0;
                                    isConfigurationError = true;
                                } else {
                                    // Calculate maximum usable inboxes
                                    const maxDomainsAllowed = Math.floor(rawTotalInboxes / inboxesPerDomain);
                                    TOTAL_INBOXES = maxDomainsAllowed * inboxesPerDomain;
                                }
                            }

                            // Handle configuration error
                            if (isConfigurationError) {
                                domainsField.addClass('is-invalid');
                                $('#domains-error')
                                    .text('Can\'t create inboxes with current settings. Please reduce the inboxes per domain, lower the domain count, or contact support to increase your order.')
                                    .addClass('show-error');

                                if (isImporting) {
                                    calculateTotalInboxes();
                                    return false;
                                }

                                // Show configuration error popup
                                if (showPopups && domains.length > 0 && !limitExceededShown) {
                                    limitExceededShown = true;
                                    Swal.fire({
                                        title: 'Configuration Issue',
                                        html: `<strong>Can't create inboxes with current settings.</strong><br><br>
                                               Your order limit is <strong>${poolInfo.total_inboxes}</strong> inboxes, but you have selected <strong>${inboxesPerDomain}</strong> inboxes per domain.<br><br>
                                               <small>Please reduce the inboxes per domain, lower the domain count, or contact support to increase your order.</small>`,
                                        icon: 'warning',
                                        confirmButtonText: 'Clear All Domains',
                                        confirmButtonColor: '#dc3545',
                                        showCancelButton: true,
                                        cancelButtonText: 'Keep Domains',
                                        cancelButtonColor: '#6c757d'
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            domainsField.val('');
                                            domainsField.removeClass('is-invalid');
                                            $('#domains-error').text('').removeClass('show-error');

                                            calculateTotalInboxes();
                                            if (typeof countDomains === 'function') countDomains();
                                            if (typeof updateRemainingInboxesBar === 'function') updateRemainingInboxesBar();

                                            toastr.success('All domains have been cleared due to configuration constraints.', 'Domains Cleared');
                                        }
                                        limitExceededShown = false;
                                    });
                                }
                                calculateTotalInboxes();
                                return false;
                            }

                            // Handle order limit exceeded
                            if (TOTAL_INBOXES > 0 && totalInboxes > TOTAL_INBOXES) {
                                const rawTotal = poolInfo && poolInfo.total_inboxes ? poolInfo.total_inboxes : TOTAL_INBOXES;

                                domainsField.addClass('is-invalid');
                                $('#domains-error').html(`
                                    <strong>Order Limit Exceeded</strong> — You currently have ${totalInboxes} inboxes, but this order supports only ${TOTAL_INBOXES} usable inboxes.
                                    <br><small>Usable Limit: ${TOTAL_INBOXES} inboxes</small>
                                `).addClass('show-error');

                                if (isImporting) {
                                    calculateTotalInboxes();
                                    return false;
                                }

                                // Show limit exceeded popup with auto-trim option
                                if (showPopups && !limitExceededShown) {
                                    limitExceededShown = true;
                                    const maxDomainsAllowed = Math.floor(TOTAL_INBOXES / inboxesPerDomain);
                                    const excessDomains = domains.length - maxDomainsAllowed;

                                    Swal.fire({
                                        title: 'Order Limit Exceeded',
                                        html: `<strong>Warning:</strong> You have entered ${domains.length} domains (${totalInboxes} inboxes), but this order supports only <strong>${TOTAL_INBOXES}</strong> usable inboxes${rawTotal > TOTAL_INBOXES ? ` (${rawTotal} total with ${inboxesPerDomain} inboxes per domain)` : ''}.<br><br>
                                               You need to remove <strong>${excessDomains}</strong> domains.<br><br>
                                               <small>Maximum domains allowed: ${maxDomainsAllowed}</small>`,
                                        icon: 'warning',
                                        confirmButtonText: 'I Understand',
                                        confirmButtonColor: '#f0ad4e',
                                        showCancelButton: true,
                                        cancelButtonText: 'Remove Excess Domains',
                                        cancelButtonColor: '#dc3545'
                                    }).then((result) => {
                                        if (!result.isConfirmed && result.dismiss === Swal.DismissReason.cancel) {
                                            // Auto-trim excess domains
                                            const trimmedDomains = domains.slice(0, maxDomainsAllowed);
                                            domainsField.val(trimmedDomains.join('\n'));

                                            // Clear errors and revalidate
                                            domainsField.removeClass('is-invalid');
                                            $('#domains-error').text('').removeClass('show-error');

                                            calculateTotalInboxes();

                                            // Re-run validation after trimming
                                            setTimeout(() => {
                                                validateDomainsFormat(true, false);
                                                if (typeof countDomains === 'function') countDomains();
                                                if (typeof updateRemainingInboxesBar === 'function') updateRemainingInboxesBar();
                                            }, 100);

                                            toastr.success(`${excessDomains} domains were removed to fit your order limit. You now have ${maxDomainsAllowed} domains (${TOTAL_INBOXES} usable inboxes).`, 'Domains Trimmed');
                                        }
                                        limitExceededShown = false;
                                    });
                                }
                                calculateTotalInboxes();
                                return false;
                            }

                            // Within limits - clear flags and errors
                            limitExceededShown = false;
                            domainsField.removeClass('is-invalid');
                            $('#domains-error').text('').removeClass('show-error');

                            calculateTotalInboxes();
                            return true;
                        }

                        // Legacy function for backward compatibility - redirects to new function
                        function validateAndTrimDomains() {
                            // Redirect to the new comprehensive validation function with full checks
                            return validateDomainsFormat(true, true);

                            if (!domainsText.trim()) {
                                // Only clear errors if no high-priority errors exist
                                if (!$('#domains-error').text().includes('Duplicate') && !$('#domains-error').text().includes('Invalid') && !$('#domains-error').text().includes('Cannot create domains')) {
                                    domainsField.removeClass('is-invalid');
                                    $('#domains-error').text('').removeClass('show-error');
                                }             
                                calculateTotalInboxes();
                                return;
                            }

                            let domains = domainsText.split(/[\n,]+/)
                                .map(domain => domain.trim())
                                .filter(domain => domain.length > 0);

                            if (domains.length > 0) {
                                // Check for duplicates
                                const seen = new Set();
                                const duplicates = domains.filter(domain => {
                                    if (seen.has(domain)) {
                                        return true;
                                    }
                                    seen.add(domain);
                                    return false;
                                });

                                if (duplicates.length > 0) {
                                    domainsField.addClass('is-invalid');
                                    $('#domains-error').text(`Duplicate domains are not allowed: ${duplicates.join(', ')}`).addClass('show-error');
                                    calculateTotalInboxes();
                                    return;
                                }

                                // Updated domain format validation to handle multi-level domains
                                const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/;
                                const domainRegexSimple = /^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/;
                                const invalidDomains = domains.filter(d => !domainRegex.test(d) && !domainRegex.test(d));

                                if (invalidDomains.length > 0) {
                                    domainsField.addClass('is-invalid');
                                    $('#domains-error').text(`Invalid domain format: ${invalidDomains.join(', ')}`).addClass('show-error');
                                    calculateTotalInboxes();
                                    return;
                                }

                                // Auto-trim domains if they exceed order limit
                                const totalInboxes = domains.length * inboxesPerDomain;

                                // Get current order's total inboxes limit from reorder_info
                                const poolInfo = @json(optional($pool));

                                // Calculate TOTAL_INBOXES based on poolInfo.total_inboxes
                                let TOTAL_INBOXES = 0;
                                let isConfigurationError = false;

                                if (poolInfo && poolInfo.total_inboxes !== undefined) {
                                    const rawTotalInboxes = poolInfo.total_inboxes;

                                    // Check for configuration error first (order has limit but can't fit any domains)
                                    if (rawTotalInboxes > 0 && rawTotalInboxes < inboxesPerDomain) {
                                        TOTAL_INBOXES = 0;
                                        isConfigurationError = true;
                                    } else {
                                        // Calculate maximum usable inboxes based on inboxes_per_domain
                                        // For example: 500 total inboxes with 3 inboxes per domain = 166 domains max = 498 usable inboxes
                                        const maxDomainsAllowed = Math.floor(rawTotalInboxes / inboxesPerDomain);
                                        TOTAL_INBOXES = maxDomainsAllowed * inboxesPerDomain;
                                    }
                                }

                                // Handle configuration error vs regular order limit exceeded
                                if (isConfigurationError) {
                                    // Configuration error: order has limit but can't fit any domains with current inboxes per domain
                                    $('#domains').addClass('is-invalid');
                                    $('#domains-error').text('Can’t create inboxes with current settings. Please reduce the inboxes per domain, lower the domain count, or contact support to increase your order.');

                                    // During import, just return early
                                    if (isImporting) {
                                        return;
                                    }

                                    // For configuration errors, show a specific dialog when user has domains that need to be cleared
                                    if (domains.length > 0 && !limitExceededShown) {
                                        limitExceededShown = true;
                                        Swal.fire({
                                            title: 'Configuration Issue',
                                            html: `<strong>Can’t create inboxes with current settings.</strong><br><br>
                                                   Your order limit is <strong>${poolInfo.total_inboxes}</strong> inboxes, but you have selected <strong>${inboxesPerDomain}</strong> inboxes per domain.<br><br>
                                                   <small>Please reduce the inboxes per domain, lower the domain count, or contact support to increase your order.</small>`,
                                            icon: 'warning',
                                            confirmButtonText: 'Clear All Domains',
                                            confirmButtonColor: '#dc3545',
                                            showCancelButton: true,
                                            cancelButtonText: 'Keep Domains',
                                            cancelButtonColor: '#6c757d'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                // Clear all domains
                                                domainsField.val('');

                                                // Clear the error message and update validation
                                                $('#domains').removeClass('is-invalid');
                                                $('#domains-error').text('');

                                                // Recalculate totals after clearing
                                                if (typeof calculateTotalInboxes === 'function') {
                                                    calculateTotalInboxes();
                                                }

                                                // Update other UI elements
                                                if (typeof countDomains === 'function') {
                                                    countDomains();
                                                }
                                                if (typeof updateRemainingInboxesBar === 'function') {
                                                    updateRemainingInboxesBar();
                                                }

                                                toastr.success('All domains have been cleared due to configuration constraints.', 'Domains Cleared');
                                            }
                                            // Reset flag after popup is closed
                                            limitExceededShown = false;
                                        });
                                    }
                                } else if (TOTAL_INBOXES > 0 && totalInboxes > TOTAL_INBOXES && !isConfigurationError) {
                                    // Get original total for display
                                    const rawTotal = poolInfo && poolInfo.total_inboxes ? poolInfo.total_inboxes : TOTAL_INBOXES;

                                    // Always show order limit in domains-error div when limit exceeded
                                    $('#domains').addClass('is-invalid');
                                    $('#domains-error').html(`
                                        <strong>Order Limit Exceeded</strong> — You currently have ${totalInboxes} inboxes, but this order supports only ${TOTAL_INBOXES} usable inboxes.
                                        <br><small>Usable Limit: ${TOTAL_INBOXES} inboxes</small>
                                    `);

                                    // During import, just return early
                                    if (isImporting) {
                                        return;
                                    }
                                    // Show popup warning but don't auto-trim (only show once per session)
                                    if (!limitExceededShown) {
                                        limitExceededShown = true;
                                        const maxDomainsAllowed = Math.floor(TOTAL_INBOXES / inboxesPerDomain);
                                        const excessDomains = domains.length - maxDomainsAllowed;

                                        // Show warning popup without auto-trimming
                                        Swal.fire({
                                            title: 'Configuration Issue',
                                            html: `<strong>Warning:</strong> You have entered ${domains.length} domains (${totalInboxes} inboxes), but this order supports only <strong>${TOTAL_INBOXES}</strong> usable inboxes${rawTotal > TOTAL_INBOXES ? ` (${rawTotal} total with ${inboxesPerDomain} inboxes per domain)` : ''}.<br><br>
                                                   You need to remove <strong>${excessDomains}</strong> domains.<br><br>
                                                   <small>Maximum domains allowed: ${maxDomainsAllowed}</small>`,
                                            icon: 'warning',
                                            confirmButtonText: 'I Understand',
                                            confirmButtonColor: '#f0ad4e',
                                            showCancelButton: true,
                                            cancelButtonText: 'Remove Excess Domains',
                                            cancelButtonColor: '#dc3545'
                                        }).then((result) => {
                                            if (!result.isConfirmed && result.dismiss === Swal.DismissReason.cancel) {
                                                // User chose to remove excess domains
                                                const trimmedDomains = domains.slice(0, maxDomainsAllowed);
                                                domainsField.val(trimmedDomains.join('\n'));

                                                // Clear the error message and update validation
                                                $('#domains').removeClass('is-invalid');
                                                $('#domains-error').text('');

                                                // Recalculate and revalidate
                                                calculateTotalInboxes();

                                                // Re-run validation to update error messages properly
                                                setTimeout(() => {
                                                    validateDomainsFormat(true, false);

                                                    // Also update domain count and other UI elements
                                                    if (typeof countDomains === 'function') {
                                                        countDomains();
                                                    }
                                                    if (typeof updateRemainingInboxesBar === 'function') {
                                                        updateRemainingInboxesBar();
                                                    }
                                                }, 100);

                                                toastr.success(`${excessDomains} domains were removed to fit your order limit. You now have ${maxDomainsAllowed} domains (${TOTAL_INBOXES} usable inboxes).`, 'Domains Trimmed');
                                            }
                                            // Reset flag after popup is closed
                                            limitExceededShown = false;
                                        });
                                    }
                                } else {
                                    // Reset flag when within order limits or configuration error (already handled above)
                                    limitExceededShown = false;

                                    // Clear domain errors if within valid range and no other errors
                                    // Configuration errors are already handled in the main logic above
                                    if (!isConfigurationError && !$('#domains-error').text().includes('Duplicate') && !$('#domains-error').text().includes('Invalid') && !$('#domains-error').text().includes('Cannot create domains')) {
                                        $('#domains').removeClass('is-invalid');
                                        $('#domains-error').text('').removeClass('show-error');
                                    }
                                }
                            }

                            // Update total inboxes calculation
                            calculateTotalInboxes();
                        }

                        function generateField(name, field, existingValue = '', colClass = 'mb-3') {
                            const fieldId = `${name}`;
                            let html = `<div class="${colClass}">
                                <label for="${fieldId}">${field.label}${field.required ? ' *' : ''}</label>`;

                            if (field.type === 'select' && field.options) {
                                html += `<select id="${fieldId}" name="${name}" class="form-control"${field.required ? ' required' : ''}>`;
                                Object.entries(field.options).forEach(([value, label]) => {
                                    const selected = value === existingValue ? ' selected' : '';
                                    html += `<option value="${value}"${selected}>${label}</option>`;
                                });
                                html += '</select>';
                            } else if (field.type === 'textarea') {
                                html += `<textarea id="${fieldId}" name="${name}" class="form-control"${field.required ? ' required' : ''} rows="8">${existingValue}</textarea>`;
                            } else if (field.type === 'password') {
                                html += `
                                <div class="password-wrapper">
                                    <input type="password" id="${fieldId}" name="${name}" class="form-control"${field.required ? ' required' : ''} value="${existingValue}">
                                    <i class="fa-regular fa-eye password-toggle"></i>
                                </div>`;
                            } else {
                                html += `<input type="${field.type}" id="${fieldId}" name="${name}" class="form-control"${field.required ? ' required' : ''} value="${existingValue}">`;
                            }

                            if (field.note) {
                                html += `<p class="note mb-0">${field.note}</p>`;
                            }

                            html += `<div class="invalid-feedback" id="${fieldId}-error"></div></div>`;
                            return html;
                        }

                        function generatePairedFields(fieldsData, existingValues) {
                            let html = '';
                            const processedFields = new Set();

                            Object.entries(fieldsData).forEach(([name, field]) => {
                                if (processedFields.has(name)) return;

                                // Check for login/password pairs
                                const isLoginField = name.includes('login') || name.includes('Login');
                                const passwordFieldKey = name.replace(/login/gi, 'password').replace(/Login/gi, 'Password');
                                const hasPasswordPair = fieldsData[passwordFieldKey];

                                if (isLoginField && hasPasswordPair) {
                                    // Generate paired login/password fields
                                    const loginValue = existingValues && existingValues[name] ? existingValues[name] : '';
                                    const passwordValue = existingValues && existingValues[passwordFieldKey] ? existingValues[passwordFieldKey] : '';

                                    html += '<div class="row gx-3 mb-3">';
                                    html += generateField(name, field, loginValue, 'col-md-6');
                                    html += generateField(passwordFieldKey, fieldsData[passwordFieldKey], passwordValue, 'col-md-6');
                                    html += '</div>';

                                    processedFields.add(name);
                                    processedFields.add(passwordFieldKey);
                                } else if (!processedFields.has(name)) {
                                    // Generate single field
                                    const existingValue = existingValues && existingValues[name] ? existingValues[name] : '';
                                    html += generateField(name, field, existingValue);
                                    processedFields.add(name);
                                }
                            });

                            return html;
                        }

                        // function updatePlatformFields() {
                        //     const selectedOption = $('#hosting option:selected');
                        //     const fieldsData = selectedOption.data('fields');
                        //     const requiresTutorial = selectedOption.data('requires-tutorial');
                        //     const tutorialLink = selectedOption.data('tutorial-link');
                        //     const platformValue = selectedOption.val();

                        //     const container = $('#platform-fields-container');
                        //     container.empty();

                        //     if (fieldsData) {
                        //         Object.entries(fieldsData).forEach(([name, field]) => {
                        //             container.append(generateField(name, field));
                        //         });

                        //         // Reinitialize password toggles for new fields
                        //         initializePasswordToggles();
                        //     }

                        //     // Handle tutorial section visibility
                        //     if (requiresTutorial && tutorialLink) {
                        //         $('#tutorial_section').show();
                        //         $('.tutorial-link').attr('href', tutorialLink);
                        //     } else {
                        //         $('#tutorial_section').hide();
                        //     }
                        // }
                        function updatePlatformFields() {
                            const selectedOption = $('#hosting option:selected');
                            const fieldsData = selectedOption.data('fields');
                            const requiresTutorial = selectedOption.data('requires-tutorial');
                            const tutorialLink = selectedOption.data('tutorial-link');
                            const importNote = selectedOption.data('import-note');
                            const platformValue = selectedOption.val();

                            const container = $('#platform-fields-container');
                            container.empty();

                            if (fieldsData) {
                                // Get existing values from the pool if available
                                const existingValues = @json(isset($pool) ? $pool : null);

                                // Use the new paired field generation
                                container.append(generatePairedFields(fieldsData, existingValues));

                                // Reinitialize password toggles for new fields
                                initializePasswordToggles();
                            }

                            // Handle other platform section
                            if (platformValue === 'other') {
                                $('#other-platform-section').show();
                                $('#other_platform').prop('required', true);
                            } else {
                                $('#other-platform-section').hide();
                                $('#other_platform').prop('required', false);
                                $('#other_platform').removeClass('is-invalid');
                                $('#other-platform-error').text('');
                            }
                            // Handle tutorial section visibility
                            if (requiresTutorial && tutorialLink) {
                                $('#tutorial_section').show();
                                $('.tutorial-link').attr('href', tutorialLink);

                                // Update import note dynamically
                                const importNoteElement = $('#hosting-platform-import-note');
                                console.log('Import Note from Seeder:', importNote);
                                if (importNote && importNote.trim() !== '') {
                                    // Show the import note from seeder with tutorial link if it's not just '#'
                                    if (tutorialLink && tutorialLink !== '#') {
                                        importNoteElement.html(importNote + ' <a href="' + tutorialLink + '" class="highlight-link tutorial-link" target="_blank">Click here to view tutorial</a>');
                                    } else {
                                        // Show just the import note without tutorial link if tutorialLink is '#'
                                        importNoteElement.html(importNote);
                                    }
                                } else {
                                    // Fallback to default message if no import note is set
                                    importNoteElement.html('<strong>IMPORTANT</strong> - please follow the steps from this document to grant us access to your hosting account: <a href="' + tutorialLink + '" class="highlight-link tutorial-link" target="_blank">Click here to view tutorial</a>');
                                }
                            } else if (importNote && importNote.trim() !== '') {
                                // Show tutorial section with import note even when no tutorial link is provided
                                $('#tutorial_section').show();
                                const importNoteElement = $('#hosting-platform-import-note');
                                console.log('Import Note from Seeder (no tutorial):', importNote);
                                // Show just the import note without tutorial link
                                importNoteElement.html(importNote);
                            } else {
                                $('#tutorial_section').hide();
                            }
                        }

                        function initializePasswordToggles() {
                            $('.password-toggle').off('click').on('click', function() {
                                const input = $(this).closest('.password-wrapper').find('input');
                                if (input.attr('type') === 'password') {
                                    input.attr('type', 'text');
                                    $(this).removeClass('fa-eye').addClass('fa-eye-slash');
                                } else {
                                    input.attr('type', 'password');
                                    $(this).removeClass('fa-eye-slash').addClass('fa-eye');
                                }
                            });
                        }

                        // Initial setup
                        updatePlatformFields();
                        initializePasswordToggles();

                        // Handle platform changes
                        $('#hosting').on('change', updatePlatformFields);

                            // Handle sending platform changes
                            function updateSendingPlatformFields() {
                            const selectedOption = $('#sending_platform option:selected');
                            const fieldsData = selectedOption.data('fields');
                            const container = $('#sending-platform-fields');
                            container.empty();

                            if (fieldsData) {
                                const existingValues = @json(isset($pool) ? $pool : null);

                                // Use the new paired field generation
                                container.append(generatePairedFields(fieldsData, existingValues));

                                // Reinitialize password toggles for new fields
                                initializePasswordToggles();
                            }
                        }
                        // Initial sending platform setup
                        updateSendingPlatformFields();

                        // Handle sending platform changes
                        $('#sending_platform').on('change', updateSendingPlatformFields);
                        // Update remaining inboxes progress bar (legacy function for existing code)
                        function updateRemainingInboxes() {
                            // Use the global function instead
                            if (typeof updateRemainingInboxesBar === 'function') {
                                updateRemainingInboxesBar();
                            }
                        }

                        // Calculate total inboxes and update pricing - enhanced for auto-domain trimming
                        function calculateTotalInboxes() {
                            const domainsText = $('#domains').val();
                            const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 0;

                            let editableDomainsCount = 0;
                            if (domainsText) {
                                // Split domains by newlines and filter out empty entries
                                const domains = domainsText.split(/[\n,]+/)
                                    .map(domain => domain.trim())
                                    .filter(domain => domain.length > 0);

                                const uniqueDomains = [...new Set(domains)];
                                editableDomainsCount = uniqueDomains.length;
                            }

                            // Include used domains in total calculation (safely check if usedDomains exists)
                            const usedDomainsCount = (typeof usedDomains !== 'undefined') ? usedDomains.length : 0;
                            const totalDomainsCount = editableDomainsCount + usedDomainsCount;
                            const totalInboxes = totalDomainsCount * inboxesPerDomain;

                            $('#total_inboxes').val(totalInboxes);

                            // Update remaining inboxes progress bar using global function
                            if (typeof updateRemainingInboxesBar === 'function') {
                                updateRemainingInboxesBar(totalInboxes);
                            }

                            // Update price display using global function
                            if (typeof updatePriceDisplay === 'function') {
                                updatePriceDisplay(totalInboxes);
                            }

                            return totalInboxes;
                        }
                        // Domain validation with auto-trimming - using centralized function (less aggressive for input)
                        $('#domains').on('input', function() {
                            // Only validate format, don't check limits or show popups on input
                            validateDomainsFormat(false, false);
                        });

                        // Add event listener for inboxes per domain changes with domain validation
                        $('#inboxes_per_domain').on('input change', function() {
                            // Reset the limit exceeded flag when user changes inboxes per domain
                            limitExceededShown = false;
                            validateDomainsFormat(true, true);
                        });

                        // Add paste event handler for domains field to handle auto-trimming
                        $('#domains').on('paste', function() {
                            // Use setTimeout to allow the paste content to be processed first
                            setTimeout(() => {
                                validateDomainsFormat(true, true);
                            }, 100);
                        });

                        // Add change event handler for domains field to handle auto-trimming when content changes
                        $('#domains').on('change', function() {
                            validateDomainsFormat(true, true);
                        });

                        // Add focusout event handler for domains field to show popup when user leaves the field
                        $('#domains').on('focusout', function() {
                            validateDomainsFormat(true, true);
                        });

                        // Initial validation and calculation
                        validateDomainsFormat(true, false);

                        // Debug function to test order limit display (can be called from browser console)
                        window.testOrderLimitDisplay = function(totalInboxes) {
                            const poolInfo = @json(optional($pool));
                            if (poolInfo) {
                                console.log('Order Info:', poolInfo);
                                console.log('Testing with totalInboxes:', totalInboxes);

                                if (poolInfo.total_inboxes > 0 && totalInboxes > poolInfo.total_inboxes) {
                                    $('#domains').addClass('is-invalid');
                                    $('#domains-error').html(`
                                        <strong>Order Limit Exceeded</strong> — You currently have ${totalInboxes} inboxes, but this order supports only ${poolInfo.total_inboxes} inboxes.
                                        <br><small>Order Limit: ${poolInfo.total_inboxes} inboxes</small>
                                    `);
                                    console.log('Order limit exceeded error displayed');
                                } else {
                                    $('#domains').removeClass('is-invalid');
                                    $('#domains-error').text('');
                                    console.log('Within valid range - errors cleared');
                                }
                            } else {
                                console.log('No order info available');
                            }
                        };

                        // Initial remaining inboxes progress bar update
                        if (typeof updateRemainingInboxesBar === 'function') {
                            updateRemainingInboxesBar();
                        }

                        // Initial URL validation
                        $('#forwarding').trigger('blur');

                        // Form submission state management
                        let isFormSubmitting = false;
                        let formValidationInProgress = false;

                        // Form validation and submission
                        $('#editOrderForm').on('submit', function(e) {
                            e.preventDefault();
                            e.stopPropagation();

                            // Prevent multiple simultaneous submissions
                            if (isFormSubmitting || formValidationInProgress) {
                                console.log('Form submission blocked: already in progress');
                                return false;
                            }

                            // Set validation flag
                            formValidationInProgress = true;

                            // Disable submit button immediately
                            const submitButton = $(this).find('button[type="submit"]');
                            const originalButtonText = submitButton.text();
                            submitButton.prop('disabled', true).text('Validating...');

                            // Clear master inbox email if confirmation is set to "No" (0)
                            if ($('#master_inbox_confirmation').val() == '0') {
                                $('#master_inbox_email').val('');
                            }

                            // Reset all validations
                            $('.is-invalid').removeClass('is-invalid');
                            $('.invalid-feedback').text('');
                            $('#domains-error').text('').removeClass('show-error');

                            let isValid = true;
                            let firstErrorField = null;
                            let validationErrors = [];

                            // Check if SMTP mode is enabled for skipping standard pool field validation
                            const isSmtpModeEnabled = $('#smtp_mode_toggle').prop('checked');

                            // Validate required fields
                            $(this).find(':input[required]').each(function() {
                                const field = $(this);
                                const value = field.val()?.trim();
                                const fieldName = field.attr('name') || field.attr('id') || 'Unknown field';

                                // Skip validation for fields inside hidden sections when SMTP mode is enabled
                                if (isSmtpModeEnabled) {
                                    // Skip fields inside standard-pool-fields (which is hidden in SMTP mode)
                                    if (field.closest('#standard-pool-fields').length > 0) {
                                        return; // Skip this field
                                    }
                                    // Skip fields inside prefix-variant-section
                                    if (field.closest('.prefix-variant-section').length > 0) {
                                        return; // Skip this field
                                    }
                                    // Skip master inbox email field
                                    if (fieldName.includes('master_inbox_email')) {
                                        return; // Skip this field
                                    }
                                }

                                if (!value) {
                                    isValid = false;
                                    field.addClass('is-invalid');
                                    const friendlyMessage = formatValidationError(fieldName, 'This field is required');
                                    field.siblings('.invalid-feedback').text(friendlyMessage);
                                    validationErrors.push(friendlyMessage);

                                    if (!firstErrorField) {
                                        firstErrorField = field;
                                    }
                                }
                            });

                            // Validate email fields
                            $(this).find('input[type="email"]').each(function() {
                                const field = $(this);
                                const value = field.val()?.trim();
                                const fieldName = field.attr('name') || field.attr('id') || 'Email field';

                                if (value) {
                                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                                    if (!emailRegex.test(value)) {
                                        isValid = false;
                                        field.addClass('is-invalid');
                                        const friendlyMessage = formatValidationError(fieldName, 'Please enter a valid email address');
                                        field.siblings('.invalid-feedback').text(friendlyMessage);
                                        validationErrors.push(friendlyMessage);

                                        if (!firstErrorField) {
                                            firstErrorField = field;
                                        }
                                    }
                                }
                            });

                            // Validate URL fields
                            $(this).find('input[type="url"]').each(function() {
                                const field = $(this);
                                const value = field.val()?.trim();
                                const fieldName = field.attr('name') || field.attr('id') || 'URL field';

                                if (value) {
                                    try {
                                        new URL(value);
                                    } catch (_) {
                                        isValid = false;
                                        field.addClass('is-invalid');
                                        const friendlyMessage = formatValidationError(fieldName, 'Please enter a valid URL');
                                        field.siblings('.invalid-feedback').text('Please enter a valid URL (include http:// or https://)');
                                        validationErrors.push(friendlyMessage);

                                        if (!firstErrorField) {
                                            firstErrorField = field;
                                        }
                                    }
                                }
                            });
                            // domain hosting platform access tutorial validation 
                            const accessTutorial = $('#access_tutorial');
                            const selectedValue = accessTutorial.val()?.trim();

                            if (selectedValue === 'no') {
                                isValid = false;
                                accessTutorial.addClass('is-invalid');
                                accessTutorial.siblings('.invalid-feedback').text(
                                    'Please review the Domain Hosting Platform - Access Tutorial and select "Yes".'
                                );
                                validationErrors.push('Access Tutorial: Please review and select "Yes"');

                                if (!firstErrorField) {
                                    firstErrorField = accessTutorial;
                                }
                            }
                            // Validate dynamic prefix variant fields (skip for SMTP mode)
                            if (!isSmtpModeEnabled) {
                                const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;
                                for (let i = 1; i <= inboxesPerDomain; i++) {
                                    const prefixField = $(`input[name="prefix_variants[prefix_variant_${i}]"]`);
                                    const value = prefixField.val()?.trim();

                                    if (i === 1 && !value) {
                                        // First prefix variant is required
                                        isValid = false;
                                        prefixField.addClass('is-invalid');
                                        prefixField.siblings('.invalid-feedback').text('This field is required');
                                        validationErrors.push(`Prefix Variant ${i}: This field is required`);

                                        if (!firstErrorField) {
                                            firstErrorField = prefixField;
                                        }
                                    } else if (value) {
                                        // Validate prefix variant format (alphanumeric and basic characters only)
                                        const prefixRegex = /^[a-zA-Z0-9._-]+$/;
                                        if (!prefixRegex.test(value)) {
                                            isValid = false;
                                            prefixField.addClass('is-invalid');
                                            prefixField.siblings('.invalid-feedback').text('Only letters, numbers, dots, hyphens and underscores are allowed');
                                            validationErrors.push(`Prefix Variant ${i}: Invalid format`);

                                            if (!firstErrorField) {
                                                firstErrorField = prefixField;
                                            }
                                        }
                                    }
                                }
                            }


                            // Validate domains (skip for SMTP mode)
                            if (!isSmtpModeEnabled) {
                                const domainsField = $('#domains');
                                const domains = domainsField.val().trim().split(/[\n,]+/).map(d => d.trim()).filter(d => d.length > 0);

                                // Check if we have used domains
                                const hasUsedDomains = (typeof usedDomains !== 'undefined' && usedDomains.length > 0);

                                if (domains.length === 0 && !hasUsedDomains) {
                                    isValid = false;
                                    domainsField.addClass('is-invalid');
                                    $('#domains-error').text('Please enter at least one domain').addClass('show-error');
                                    validationErrors.push('Domains: Please enter at least one domain');

                                    if (!firstErrorField) {
                                        firstErrorField = domainsField;
                                    }
                                } else if (domains.length > 0) {
                                    // Check for duplicates
                                    const seen = new Set();
                                    const duplicates = domains.filter(domain => {
                                        if (seen.has(domain)) {
                                            return true;
                                        }
                                        seen.add(domain);
                                        return false;
                                    });

                                    if (duplicates.length > 0) {
                                        isValid = false;
                                        domainsField.addClass('is-invalid');
                                        $('#domains-error').text(`Duplicate domains are not allowed: ${duplicates.join(', ')}`).addClass('show-error');
                                        validationErrors.push(`Domains: Duplicate domains found - ${duplicates.join(', ')}`);

                                        if (!firstErrorField) {
                                            firstErrorField = domainsField;
                                        }
                                    } else {
                                        // Validate domain format
                                        const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/;
                                        const domainRegexSimple = /^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/;
                                        const invalidDomains = domains.filter(d => !domainRegex.test(d) && !domainRegexSimple.test(d));

                                        if (invalidDomains.length > 0) {
                                            isValid = false;
                                            domainsField.addClass('is-invalid');
                                            $('#domains-error').text(`Invalid domain format: ${invalidDomains.join(', ')}`).addClass('show-error');
                                            validationErrors.push(`Domains: Invalid format - ${invalidDomains.join(', ')}`);

                                            if (!firstErrorField) {
                                                firstErrorField = domainsField;
                                            }
                                        } else {
                                            // Check for configuration errors (order limit too small for inboxes per domain)
                                            const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;
                                            const totalInboxes = domains.length * inboxesPerDomain;
                                            const poolInfo = @json(optional($pool));

                                            if (poolInfo && poolInfo.total_inboxes !== undefined) {
                                                const rawTotalInboxes = poolInfo.total_inboxes;

                                                // Configuration error: order has limit but can't fit any domains with current inboxes per domain
                                                if (rawTotalInboxes > 0 && rawTotalInboxes < inboxesPerDomain) {
                                                    isValid = false;
                                                    domainsField.addClass('is-invalid');
                                                    $('#domains-error').text('Can\'t create inboxes with current settings. Please reduce the inboxes per domain, lower the domain count, or contact support to increase your order.');

                                                    if (!firstErrorField) {
                                                        firstErrorField = domainsField;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if (!isValid) {
                                // Reset form submission state
                                formValidationInProgress = false;
                                isFormSubmitting = false;

                                // Re-enable submit button
                                submitButton.prop('disabled', false).text(originalButtonText);

                                // Log validation errors for debugging
                                console.error('Form validation failed:', validationErrors);

                                // Focus and scroll to the first error field
                                if (firstErrorField) {
                                    // Smooth scroll to the error field
                                    firstErrorField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    // Set focus after scroll animation completes
                                    setTimeout(() => {
                                        firstErrorField.focus();
                                    }, 500);
                                }

                                // Show validation error alert
                                Swal.fire({
                                    title: 'Validation Error!',
                                    html: `<p>Please fix the following errors before submitting:</p><ul>${validationErrors.map(err => `<li>${err}</li>`).join('')}</ul>`,
                                    icon: 'error',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#dc3545'
                                });

                                // Absolutely prevent any form submission
                                return false;
                            }

                            // Reset validation flag as we passed validation
                            formValidationInProgress = false;

                            // Check if total inboxes are not fully completed
                            const currentTotalInboxes = parseInt($('#total_inboxes').val()) || 0;
                            const poolInfo = @json(optional($pool));
                            let originalTotalInboxes = 0;

                            if (poolInfo && poolInfo.total_inboxes !== undefined) {
                                originalTotalInboxes = parseInt(poolInfo.total_inboxes) || 0;
                            }

                            // If current inboxes are less than original, show confirmation dialog
                            if (originalTotalInboxes > 0 && currentTotalInboxes < originalTotalInboxes) {
                                Swal.fire({
                                    title: 'Incomplete Order',
                                    html: `
                                        <p>You haven't finished adding all of your domains.</p>
                                        <p><strong>Planned Total:</strong> ${originalTotalInboxes} inboxes</p>
                                        <p><strong>Currently Used:</strong> ${currentTotalInboxes} inboxes</p>
                                        <p>Are you sure you want to continue?</p>
                                    `,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    showDenyButton: true,
                                    confirmButtonText: 'Save as Draft',
                                    denyButtonText: 'Continue Anyway',
                                    cancelButtonText: 'Cancel',
                                    confirmButtonColor: '#6c757d',
                                    denyButtonColor: '#0d6efd',
                                    cancelButtonColor: '#dc3545'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Save as draft
                                        $('#is_draft').val('1');
                                        submitForm();
                                    } else if (result.isDenied) {
                                        // Continue anyway (keep original total)
                                        $('#total_inboxes').val(originalTotalInboxes);
                                        $('#is_draft').val('0');
                                        submitForm();
                                    } else {
                                        // If cancelled, reset form state and re-enable button
                                        isFormSubmitting = false;
                                        formValidationInProgress = false;
                                        submitButton.prop('disabled', false).text(originalButtonText);
                                    }
                                });
                                return false;
                            }

                            // If validation passes and no confirmation needed, submit directly
                            submitForm();
                        });

                        // Function to handle the actual form submission
                        // Function to manage domain IDs with pool prefix for uniqueness
                        @if(isset($pool) && $pool->id)
                            const poolId = {{ $pool->id }}; // Use existing pool ID
                        @else
                            const poolId = Date.now(); // Generate timestamp-based ID for new pools (will be updated by PoolObserver after creation)
                        @endif
                        let domainSequenceCounter = 1;
                        let existingDomainIds = new Map(); // Map to store domain name -> ID mapping
                        let existingDomainsById = new Map(); // Map to store ID -> domain data mapping
                        let existingDomainsByPosition = []; // Array to store original domain order with IDs

                        // Initialize existing domain IDs if editing
                        @if(isset($pool) && $pool->domains)
                            @php
                                $existingDomains = is_string($pool->domains) ? json_decode($pool->domains, true) : $pool->domains;
                            @endphp
                            @if(is_array($existingDomains))
                                @foreach($existingDomains as $index => $domain)
                                    @if(isset($domain['id']) && isset($domain['name']))
                                        const domainData{{ $index }} = {
                                            id: '{{ $domain['id'] }}',
                                            name: '{{ $domain['name'] }}',
                                            is_used: {{ isset($domain['is_used']) && $domain['is_used'] ? 'true' : 'false' }},
                                            prefix_statuses: @json($domain['prefix_statuses'] ?? null)
                                        };
                                        existingDomainIds.set('{{ $domain['name'] }}', domainData{{ $index }});
                                        existingDomainsById.set('{{ $domain['id'] }}', domainData{{ $index }});
                                        existingDomainsByPosition[{{ $index }}] = domainData{{ $index }};
                                        // Extract sequence number from existing ID (format: poolId_sequence)
                                        (function() {
                                            const parts = '{{ $domain['id'] }}'.split('_');
                                            if (parts.length === 2 && !isNaN(parts[1])) {
                                                domainSequenceCounter = Math.max(domainSequenceCounter, parseInt(parts[1]) + 1);
                                            }
                                        })();
                                    @endif
                                @endforeach
                            @endif
                        @endif

                        function processDomainIds(domainArray) {
                            const processedDomains = [];

                            // First, add all used domains (they cannot be edited)
                            usedDomains.forEach(domain => {
                                const existingData = existingDomainsById.get(domain.id);
                                processedDomains.push({
                                    id: domain.id,
                                    name: domain.name,
                                    is_used: true,
                                    prefix_statuses: existingData?.prefix_statuses || null
                                });
                            });

                            // Then process editable domains from textarea
                            for (let i = 0; i < domainArray.length; i++) {
                                const domainName = domainArray[i];
                                if (domainName.trim()) {
                                    let domainData;

                                    // Priority 1: Check if this domain name already exists
                                    if (existingDomainIds.has(domainName)) {
                                        const existing = existingDomainIds.get(domainName);
                                        domainData = {
                                            id: existing.id,
                                            name: domainName,
                                            is_used: existing.is_used || false,
                                            prefix_statuses: existing.prefix_statuses || null
                                        };
                                    }
                                    // Priority 2: Check if we have an existing domain at this position (name might have changed)
                                    else if (existingDomainsByPosition[i] && !existingDomainsByPosition[i].is_used) {
                                        // Domain name changed but position is same, preserve the original ID
                                        const existingAtPosition = existingDomainsByPosition[i];
                                        domainData = {
                                            id: existingAtPosition.id, // PRESERVE original ID
                                            name: domainName.trim(), // Use new name
                                            is_used: existingAtPosition.is_used || false,
                                            original_id: existingAtPosition.id, // Keep track of original ID for backend
                                            prefix_statuses: existingAtPosition.prefix_statuses || null
                                        };

                                        console.log('Domain renamed:', {
                                            position: i,
                                            oldName: existingAtPosition.name,
                                            newName: domainName.trim(),
                                            preservedId: existingAtPosition.id
                                        });
                                    }
                                    // Priority 3: Completely new domain (prefix_statuses will be generated by backend)
                                    else {
                                        // Assign new unique ID with pool prefix: poolId_sequence
                                        const newId = poolId + '_' + domainSequenceCounter++;
                                        domainData = {
                                            id: newId,
                                            name: domainName.trim(),
                                            is_used: false,
                                            prefix_statuses: null // Backend will generate prefix_statuses for new domains
                                        };
                                        existingDomainIds.set(domainName.trim(), { id: newId, is_used: false, prefix_statuses: null });
                                    }

                                    // Only add if not already in used domains
                                    if (!usedDomains.some(used => used.name === domainName.trim())) {
                                        processedDomains.push(domainData);
                                    }
                                }
                            }

                            return processedDomains;
                        }
                        function submitForm() {
                            // Final validation check before submission
                            const hasValidationErrors = $('.is-invalid').length > 0;
                            const hasErrorMessages = $('#domains-error').hasClass('show-error') || $('#domains-error').text().trim() !== '';

                            if (hasValidationErrors || hasErrorMessages) {
                                console.error('Attempted to submit form with validation errors present');
                                console.log('Invalid fields:', $('.is-invalid').length);
                                console.log('Error messages:', hasErrorMessages);

                                // Reset form state
                                isFormSubmitting = false;
                                formValidationInProgress = false;

                                // Re-enable submit button
                                const submitButton = $('#editOrderForm').find('button[type="submit"]');
                                submitButton.prop('disabled', false).text('{{ isset($pool) ? "Update Pool" : "Create Pool" }}');

                                Swal.fire({
                                    title: 'Validation Error!',
                                    text: 'Please fix all validation errors before submitting the form.',
                                    icon: 'error',
                                    confirmButtonText: 'OK',
                                    confirmButtonColor: '#dc3545'
                                });
                                return false;
                            }

                            // Check manual panel assignment validation if enabled
                            if (window.manualPanelAssignment && typeof window.manualPanelAssignment.validateManualAssignments === 'function') {
                                const validationResult = window.manualPanelAssignment.validateManualAssignments();

                                if (!validationResult.valid) {
                                    console.error('Manual panel assignment validation failed');
                                    console.log('Errors:', validationResult.errors);

                                    // Reset form state
                                    isFormSubmitting = false;
                                    formValidationInProgress = false;

                                    // Re-enable submit button
                                    const submitButton = $('#editOrderForm').find('button[type="submit"]');
                                    submitButton.prop('disabled', false).text('{{ isset($pool) ? "Update Pool" : "Create Pool" }}');

                                    // Build error message HTML
                                    let errorHtml = '<div style="text-align: left;">';
                                    errorHtml += '<ul style="margin: 10px 0; padding-left: 20px;">';
                                    validationResult.errors.forEach(error => {
                                        errorHtml += '<li>' + error + '</li>';
                                    });
                                    errorHtml += '</ul></div>';

                                    Swal.fire({
                                        title: 'Manual Assignment Errors!',
                                        html: errorHtml,
                                        icon: 'error',
                                        confirmButtonText: 'OK',
                                        confirmButtonColor: '#dc3545',
                                        width: '600px'
                                    });
                                    return false;
                                }
                            }

                            // Set form submitting flag
                            isFormSubmitting = true;

                            // Show loading indicator
                            const isEdit = {{ isset($pool) && $pool->id ? 'true' : 'false' }};
                            Swal.fire({
                                title: isEdit ? 'Updating Pool...' : 'Creating Pool...',
                                text: isEdit ? 'Please wait while we process your pool update.' : 'Please wait while we create your pool.',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                allowEnterKey: false,
                                showConfirmButton: false,
                                backdrop: true,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // If validation passes, submit via AJAX
                            const form = $('#editOrderForm');

                            // Process domains to JSON format with unique IDs
                            const domainsText = $('#domains').val().trim();
                            let domainsArray = [];
                            if (domainsText) {
                                const domainLines = domainsText.split(/[\n,]+/).map(d => d.trim()).filter(d => d.length > 0);
                                domainsArray = processDomainIds(domainLines);
                            }

                            // Get form data and replace domains with processed JSON
                            let formData = form.serializeArray();
                            formData = formData.filter(item => item.name !== 'domains');
                            formData.push({
                                name: 'domains',
                                value: JSON.stringify(domainsArray)
                            });

                            // Provider type and manual panel assignments (managed by panel assignment component)
                            const providerTypeValue = window.manualPanelAssignment?.getProviderType?.() ?? 'Google';
                            if (providerTypeValue) {
                                formData.push({
                                    name: 'provider_type',
                                    value: providerTypeValue
                                });
                            }

                            if (window.manualPanelAssignment?.appendAssignments) {
                                formData = window.manualPanelAssignment.appendAssignments(formData);
                            }

                            $.ajax({
                                url: form.attr('action'),
                                method: form.attr('method') || 'POST',
                                data: formData,
                                success: function(response) {
                                    Swal.close();
                                    if (response.success) {
                                        // Check if status is draft and show appropriate message
                                        const messageText = response.status == 'draft' ? 
                                            'Your pool has been saved as draft because some domain information is incomplete' : 
                                            (isEdit ? 'Pool updated successfully' : 'Pool created successfully');
                                        Swal.fire({
                                            title: 'Success!',
                                            text: messageText,
                                            icon: 'success',
                                            timer: 5000,
                                            showConfirmButton: false
                                        }).then(() => {
                                            // Send a separate request to run panel capacity check
                                            $.ajax({
                                                url: '{{ route("admin.pools.capacity-check") }}',
                                                method: 'POST',
                                                data: {
                                                    order_id: response.order_id || '',
                                                    user_id: response.user_id || '',
                                                    _token: $('meta[name="csrf-token"]').attr('content')
                                                },
                                                success: function(capacityResponse) {
                                                    console.log('Panel capacity check completed:', capacityResponse);
                                                },
                                                error: function(xhr) {
                                                    console.log('Panel capacity check failed:', xhr.responseJSON);
                                                    // Don't show error to user as it's a background process
                                                }
                                            });

                                            window.location.href = "{{ route('admin.pools.index') }}";
                                        });
                                    } else {
                                        // Reset form state on response error
                                        isFormSubmitting = false;
                                        formValidationInProgress = false;

                                        // Re-enable submit button
                                        const submitButton = $('#editOrderForm').find('button[type="submit"]');
                                        submitButton.prop('disabled', false).text('{{ isset($pool) ? "Update Pool" : "Create Pool" }}');

                                        Swal.fire({
                                            title: 'Error!',
                                            text: response.message || 'An error occurred. Please try again later.',
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                },
                                error: function(xhr) {
                                    Swal.close();

                                    // Reset form state on any error
                                    isFormSubmitting = false;
                                    formValidationInProgress = false;

                                    // Re-enable submit button
                                    const submitButton = $('#editOrderForm').find('button[type="submit"]');
                                    submitButton.prop('disabled', false).text('{{ isset($pool) ? "Update Pool" : "Create Pool" }}');

                                    if (xhr.status === 422 && xhr.responseJSON.errors) {
                                        // Handle validation errors from server
                                        let firstErrorField = null;
                                        const errorMessages = [];

                                        console.log('Raw validation errors:', xhr.responseJSON.errors);

                                        Object.keys(xhr.responseJSON.errors).forEach(key => {
                                            const originalMessage = xhr.responseJSON.errors[key][0];
                                            const friendlyMessage = formatValidationError(key, originalMessage);

                                            console.log(`Field: ${key}, Original: ${originalMessage}, Friendly: ${friendlyMessage}`);

                                            errorMessages.push(friendlyMessage);

                                            // Try to find the field with exact name match
                                            let field = $(`[name="${key}"]`);

                                            // If not found, try to find by field name with dots converted to brackets
                                            if (!field.length && key.includes('.')) {
                                                const bracketNotation = key.replace(/\.(\d+)\./g, '[$1].').replace(/\.(\w+)/g, '[$1]');
                                                field = $(`[name="${bracketNotation}"]`);
                                            }

                                            if (field.length) {
                                                field.addClass('is-invalid');
                                                if (!firstErrorField) {
                                                    firstErrorField = field;
                                                }

                                                // Find or create feedback element
                                                let feedbackEl = field.siblings('.invalid-feedback');
                                                if (!feedbackEl.length) {
                                                    feedbackEl = field.closest('.form-group, .mb-3').find('.invalid-feedback');
                                                }
                                                if (!feedbackEl.length) {
                                                    field.after(`<div class="invalid-feedback">${friendlyMessage}</div>`);
                                                } else {
                                                    feedbackEl.text(friendlyMessage);
                                                }
                                            }
                                        });

                                        // Focus and scroll to the first error field
                                        if (firstErrorField) {
                                            firstErrorField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                                            setTimeout(() => {
                                                firstErrorField.focus();
                                            }, 1500);
                                        }

                                        // Build error list HTML
                                        let errorListHtml = '<div style="text-align: left; max-height: 400px; overflow-y: auto;">';
                                        errorListHtml += '<p style="margin-bottom: 10px;">Please fix the following errors:</p>';
                                        errorListHtml += '<ul style="margin: 0; padding-left: 20px;">';
                                        errorMessages.forEach(msg => {
                                            errorListHtml += '<li style="margin-bottom: 5px;">' + msg + '</li>';
                                        });
                                        errorListHtml += '</ul></div>';

                                        Swal.fire({
                                            title: 'Validation Error!',
                                            html: errorListHtml,
                                            icon: 'error',
                                            confirmButtonText: 'OK',
                                            confirmButtonColor: '#dc3545',
                                            width: '600px'
                                        });
                                    } else {
                                        Swal.fire({
                                            title: 'Error!',
                                            text: xhr.responseJSON?.message || 'An error occurred. Please try again later.',
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
                                    }
                                }
                            });
                        }

                        // Function to format validation error messages to be user-friendly
                        function formatValidationError(fieldName, errorMessage) {
                            // Map of field names to friendly labels
                            const fieldLabels = {
                                'backup_codes': 'Backup Codes',
                                'platform_login': 'Platform Login',
                                'platform_password': 'Platform Password',
                                'bison_url': 'Bison URL',
                                'bison_workspace': 'Bison Workspace',
                                'sequencer_login': 'Sequencer Login',
                                'sequencer_password': 'Sequencer Password',
                                'domains': 'Domains',
                                'purchase_date': 'Purchase Date',
                                'hosting_platform': 'Hosting Platform',
                                'forwarding_email': 'Forwarding Email',
                                'status_manage_by_admin': 'Status Manage by Admin',
                                'first_name': 'First Name',
                                'last_name': 'Last Name',
                                'profile_picture_link': 'Profile Picture Link',
                                'email_persona_password': 'Email Persona Password',
                                'email_persona_picture_link': 'Email Persona Picture Link',
                                'email': 'Email',
                                'password': 'Password',
                                'name': 'Name',
                                'phone': 'Phone',
                                'address': 'Address',
                                'city': 'City',
                                'state': 'State',
                                'zip': 'ZIP Code',
                                'country': 'Country',
                                'smtp_provider_id': 'SMTP Provider',
                                'smtp_provider_url': 'SMTP Provider URL'
                            };

                            let friendlyName = fieldName;

                            // Handle prefix variant details fields (e.g., prefix_variants_details[prefix_variant_1][first_name])
                            if (fieldName.includes('prefix_variants_details')) {
                                const matches = fieldName.match(/prefix_variants_details\[prefix_variant_(\d+)\]\[(\w+)\]/);
                                if (matches) {
                                    const variantNum = matches[1];
                                    const fieldType = matches[2];
                                    const fieldTypeLabel = fieldLabels[fieldType] || fieldType.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                    friendlyName = `Prefix Variant ${variantNum} - ${fieldTypeLabel}`;
                                }
                            } 
                            // Handle simple prefix variants fields (e.g., prefix_variants[prefix_variant_1])
                            else if (fieldName.includes('prefix_variants[prefix_variant_')) {
                                const matches = fieldName.match(/prefix_variants\[prefix_variant_(\d+)\]/);
                                if (matches) {
                                    friendlyName = `Prefix Variant ${matches[1]}`;
                                }
                            } 
                            // Handle already friendly names
                            else if (fieldName.startsWith('Prefix Variant') || fieldName.startsWith('Batch ')) {
                                friendlyName = fieldName;
                            } 
                            // Handle array notation fields (e.g., field_name[0], field_name[key])
                            else if (fieldName.includes('[') && fieldName.includes(']')) {
                                // Extract base field and index/key
                                const baseMatch = fieldName.match(/^([^\[]+)/);
                                const indexMatch = fieldName.match(/\[([^\]]+)\]/g);

                                if (baseMatch) {
                                    let baseName = baseMatch[1];
                                    baseName = fieldLabels[baseName] || baseName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());

                                    if (indexMatch && indexMatch.length > 0) {
                                        // Extract all indices/keys
                                        const indices = indexMatch.map(m => m.replace(/[\[\]]/g, ''));
                                        const indexStr = indices.map(idx => {
                                            // Check if it's a number
                                            if (!isNaN(idx)) {
                                                return parseInt(idx) + 1; // Make 1-indexed for display
                                            }
                                            // Otherwise clean up the key name
                                            return idx.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                                        }).join(' - ');

                                        friendlyName = `${baseName} (${indexStr})`;
                                    } else {
                                        friendlyName = baseName;
                                    }
                                }
                            } 
                            // Use predefined label or convert snake_case to Title Case
                            else {
                                friendlyName = fieldLabels[fieldName] || fieldName.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                            }

                            // Format the error message
                            if (errorMessage.toLowerCase().includes('required')) {
                                return `${friendlyName} is required`;
                            } else if (errorMessage.toLowerCase().includes('at least one domain')) {
                                return 'Please enter at least one domain';
                            } else if (errorMessage.toLowerCase().includes('invalid')) {
                                return `${friendlyName} is invalid`;
                            } else if (errorMessage.toLowerCase().includes('must be')) {
                                return `${friendlyName} ${errorMessage.toLowerCase().replace(/^the .+ field /, '')}`;
                            } else {
                                return `${friendlyName}: ${errorMessage}`;
                            }
                        }

                        // Dynamic prefix variant functionality
                       function generatePrefixVariantFields(count) {
                        const container = $('#prefix-variants-container');
                        container.empty();

                        // Add header
                        container.append('<h5 class="mb-2 col-12">Email Persona - Prefix Variants</h5>');

                        const existingPrefixVariants = @json(isset($pool) ? ($pool->prefix_variants ?? []) : []);
                        const existingPrefixVariantsDetails = @json(isset($pool) ? ($pool->prefix_variants_details ?? []) : []);

                        for (let i = 1; i <= count; i++) {
                            const existingValue = existingPrefixVariants[`prefix_variant_${i}`] || 
                                (i === 1 ? '{{ isset($pool) && $pool->prefix_variant_1 ? $pool->prefix_variant_1 : '' }}' : '') ||
                                (i === 2 ? '{{ isset($pool) && $pool->prefix_variant_2 ? $pool->prefix_variant_2 : '' }}' : '');

                            // Get existing values for the detailed fields
                            const detailsKey = `prefix_variant_${i}`;
                            const existingDetails = existingPrefixVariantsDetails[detailsKey] || {};

                            // Determine example prefix and note based on iteration
                            let examplePrefix = '';
                            let noteHtml = '';

                            if (i === 1) {
                                examplePrefix = 'john';
                                noteHtml = `
                                    <p class="note">
                                        Enter the email prefix for variant ${i} (the part before @). 
                                        For example, in "<strong>${examplePrefix}@example.com</strong>", 
                                        "<strong>${examplePrefix}</strong>" is the prefix. 
                                        You currently have chosen <strong>${count}</strong> inboxes/prefix variants per domain.
                                    </p>
                                `;
                            } else if (i === 2) {
                                examplePrefix = 'john.smith';
                                noteHtml = `<p class="note">e.g <strong>${examplePrefix}</strong></p>`;
                            } else if (i === 3) {
                                examplePrefix = 'j.smith';
                                noteHtml = `<p class="note">e.g <strong>${examplePrefix}</strong></p>`;
                            }

                            const fieldHtml = `
                                <div class="col-12 prefix-variant-section" data-variant="${i}">
                                    <div class="card p-3 mb-3">
                                        <h6 class="mb-3 text-white">Particulars Variant ${String(i).padStart(2, '0')}</h6>

                                        <div class="row g-3">

                                            <div class="col-md-4">
                                                <label>First Name*</label>
                                                <input type="text" name="prefix_variants_details[prefix_variant_${i}][first_name]" 
                                                    class="form-control" value="${existingDetails.first_name || ''}" required>
                                                <div class="invalid-feedback" id="prefix_variant_${i}_first_name-error"></div>
                                                <p class="note">First name for this email persona</p>
                                            </div>

                                            <div class="col-md-4">
                                                <label>Last Name*</label>
                                                <input type="text" name="prefix_variants_details[prefix_variant_${i}][last_name]" 
                                                    class="form-control" value="${existingDetails.last_name || ''}" required>
                                                <div class="invalid-feedback" id="prefix_variant_${i}_last_name-error"></div>
                                                <p class="note">Last name for this email persona</p>
                                            </div>

                                            <div class="col-md-4">
                                                <label>Profile Picture Link</label>
                                                <input type="url" name="prefix_variants_details[prefix_variant_${i}][profile_link]" 
                                                    class="form-control" value="${existingDetails.profile_link || ''}" >
                                                <div class="invalid-feedback" id="prefix_variant_${i}_profile_link-error"></div>
                                                <p class="note">Profile picture URL for this persona</p>
                                            </div>

                                            <div class="col-md-6">
                                                <label>Email Prefix ${i} *</label>
                                                <input type="text" name="prefix_variants[prefix_variant_${i}]" class="form-control" 
                                                    value="${existingValue}" required>
                                                <div class="invalid-feedback" id="prefix_variant_${i}-error"></div>
                                                ${noteHtml}
                                            </div>
                                            <div class="col-md-6">
                                                <label>Prefix ${i} Password*</label>
                                                <input type="text" name="prefix_variants_details[prefix_variant_${i}][password]" 
                                                    class="form-control" value="${existingDetails.password || ''}" required>
                                                <div class="invalid-feedback" id="prefix_variant_${i}_password-error"></div>
                                                <p class="note">Password for this email persona</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;

                            container.append(fieldHtml);
                        }

                        //highlight the prefix note on focus input
                        container.find('input').on('focus', function () {
                            $(this).siblings('.note').css('color', 'orange');
                        }).on('blur', function () {
                            $(this).siblings('.note').css('color', ''); // Reset to default
                        });

                        // Validate prefix variants for duplicates
                        container.find('input[name*="prefix_variants[prefix_variant_"]').on('input', function () {
                            const values = [];
                            const seen = new Set();
                            let hasDuplicate = false;

                            container.find('input[name*="prefix_variants[prefix_variant_"]').each(function () {
                                const val = $(this).val().trim();
                                values.push(val);

                                if (val !== '' && seen.has(val)) {
                                    hasDuplicate = true;
                                    $(this).addClass('is-invalid');
                                    $(this).siblings('.invalid-feedback').text('Same Prefixes are not allowed.');
                                } else {
                                    seen.add(val);
                                    $(this).removeClass('is-invalid');
                                    $(this).siblings('.invalid-feedback').text('');
                                }
                            });
                        });
                    }


                        // 🔁 After generating all fields, bind change event

                        // Handle inboxes per domain change event
                        $('#inboxes_per_domain').on('change', function() {
                            const inboxesPerDomain = parseInt($(this).val()) || 1;
                            generatePrefixVariantFields(inboxesPerDomain);

                            // Recalculate total inboxes when inboxes per domain changes
                            calculateTotalInboxes();
                        });

                        // Initialize prefix variant fields on page load
                        const initialInboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;
                        generatePrefixVariantFields(initialInboxesPerDomain);

                        // Initialize remaining inboxes progress bar on page load
                        updateRemainingInboxesBar();
                        // Function to display used domains in read-only section
                        function displayUsedDomains() {
                            const readonlyContainer = $('#readonly-domains');
                            const readonlyList = $('#readonly-domains-list');
                            const usedNote = $('#used-domains-note');

                            // Safely check if usedDomains exists and has items
                            if (typeof usedDomains !== 'undefined' && usedDomains.length > 0) {
                                readonlyContainer.show();
                                usedNote.show();
                                const domainsList = usedDomains.map(domain => 
                                    `<span class="badge bg-warning text-dark me-2 mb-1 d-inline-flex align-items-center">
                                        <i class="fa-solid fa-lock me-1"></i>
                                        ${domain.name}
                                    </span>`
                                ).join('');

                                readonlyList.html(domainsList);
                            } else {
                                readonlyContainer.hide();
                                usedNote.hide();
                            }
                        }

                        // Initialize used domains display
                        displayUsedDomains();

                        // Domain counting functionality
                        function countDomains() {
                            const domainsText = $('#domains').val().trim();
                            let editableDomainCount = 0;

                            if (domainsText) {
                                // Handle both comma-separated and newline-separated domains
                                let domains;
                                if (domainsText.includes(',')) {
                                    // Comma-separated format
                                    domains = domainsText.split(',').map(d => d.trim()).filter(d => d.length > 0);
                                } else {
                                    // Newline-separated format (default)
                                    domains = domainsText.split('\n').map(d => d.trim()).filter(d => d.length > 0);
                                }
                                editableDomainCount = domains.length;
                            }

                            // Total count includes both editable and used domains (safely check if usedDomains exists)
                            const usedDomainsCount = (typeof usedDomains !== 'undefined') ? usedDomains.length : 0;
                            const totalDomainCount = editableDomainCount + usedDomainsCount;

                            // Update badge with total count
                            let badgeText = `${totalDomainCount} domain${totalDomainCount !== 1 ? 's' : ''}`;
                            if (usedDomainsCount > 0) {
                                badgeText += ` (${usedDomainsCount} locked)`;
                            }
                            $('#domain-count-badge').text(badgeText);
                            $('#domain-count-text').text(totalDomainCount);

                            // Add visual feedback based on count
                            const badge = $('#domain-count-badge');
                            badge.removeClass('bg-primary bg-success bg-warning bg-danger');

                            if (totalDomainCount === 0) {
                                badge.addClass('bg-danger');
                            } else if (totalDomainCount < 10) {
                                badge.addClass('bg-warning');
                            } else if (totalDomainCount < 50) {
                                badge.addClass('bg-primary');
                            } else {
                                badge.addClass('bg-success');
                            }

                            return totalDomainCount;
                        }

                        // Real-time domain counting
                        $('#domains').on('input paste keyup', function() {
                            // Small delay to handle paste operations
                            setTimeout(countDomains, 100);
                        });

                        // Initial domain count on page load - with delay to ensure DOM is fully loaded
                        setTimeout(() => {
                            countDomains();

                            // Additional check after a longer delay for pre-populated data
                            setTimeout(() => {
                                if ($('#domains').val().trim()) {
                                    countDomains();
                                }
                            }, 500);
                        }, 100);

                        // Master inbox email field - no confirmation alerts needed
                        // Users can freely enter and clear the email field without any popups

                        // Initialize tooltips
                        $('[data-bs-toggle="tooltip"]').tooltip();

                        // ==========================================
                        // SMTP Pool Mode Handling
                        // ==========================================

                        // Toggle SMTP mode
                        $('#smtp_mode_toggle').on('change', function() {
                            const isSmtpMode = $(this).is(':checked');
                            toggleSmtpMode(isSmtpMode);
                        });

                        function toggleSmtpMode(isSmtpMode) {
                            if (isSmtpMode) {
                                // Show SMTP fields
                                $('#smtp-fields-container').slideDown(300);
                                // Hide standard pool fields
                                $('#standard-pool-fields').slideUp(300);
                                // Hide panel assignment section (SMTP doesn't need panel assignment)
                                $('#panel-assignment-section').slideUp(300);

                                // Ensure common sections remain visible
                                $('#submit-section').show();
                                $('#purchase-date-section').closest('.row').show();
                                $('#additional-assets-section').show();

                                // Make SMTP fields required
                                $('#smtp_provider_id').attr('required', true);
                                // Remove required from standard fields
                                $('#domains').attr('required', false);
                                $('#inboxes_per_domain').attr('required', false);

                            } else {
                                // Hide SMTP fields
                                $('#smtp-fields-container').slideUp(300);
                                // Show standard pool fields
                                $('#standard-pool-fields').slideDown(300);
                                // Show panel assignment section
                                $('#panel-assignment-section').slideDown(300);

                                // Ensure common sections remain visible
                                $('#submit-section').show();
                                $('#purchase-date-section').closest('.row').show();
                                $('#additional-assets-section').show();

                                // Remove required from SMTP fields
                                $('#smtp_provider_id').attr('required', false);
                                // Make standard fields required again
                                $('#domains').attr('required', true);
                                $('#inboxes_per_domain').attr('required', true);

                                // Clear SMTP data
                                $('#smtp_accounts_data').val('');
                            }
                        }

                        // Initialize SMTP mode on page load
                        if ($('#smtp_mode_toggle').is(':checked')) {
                            toggleSmtpMode(true);

                            // Load existing SMTP accounts data if editing a pool
                            @if(isset($pool) && $pool->smtp_accounts_data)
                                try {
                                    const existingSmtpData = @json($pool->smtp_accounts_data);
                                    if (existingSmtpData && existingSmtpData.accounts && existingSmtpData.accounts.length > 0) {
                                        // Display existing data in preview
                                        loadExistingSmtpPreview(existingSmtpData);
                                    }
                                } catch (e) {
                                    console.error('Error loading existing SMTP data:', e);
                                }
                            @endif
                        }

                        // Function to load existing SMTP accounts data in preview
                        function loadExistingSmtpPreview(smtpData) {
                            const accounts = smtpData.accounts || [];
                            if (accounts.length === 0) return;

                            // Build domains object from accounts
                            const domains = {};
                            const prefixes = new Set();

                            accounts.forEach(account => {
                                const domain = account.domain || '';
                                const prefix = account.prefix || '';

                                if (domain) {
                                    if (!domains[domain]) {
                                        domains[domain] = { count: 0, prefixes: new Set() };
                                    }
                                    domains[domain].count++;
                                    if (prefix) {
                                        domains[domain].prefixes.add(prefix);
                                        prefixes.add(prefix);
                                    }
                                }
                            });

                            // Update preview using existing function
                            updateSmtpCsvPreview(accounts, domains, prefixes);

                            // Store data in hidden field (it should already be there from the form, but ensure it's set)
                            $('#smtp_accounts_data').val(JSON.stringify(smtpData));

                            // Update domain fields
                            if (Object.keys(domains).length > 0) {
                                $('#domains').val(Object.keys(domains).join('\n'));
                                $('#total_inboxes').val(accounts.length);
                                $('#inboxes_per_domain').val(Math.max(...Object.values(domains).map(d => d.count)));
                            }
                        }

                        // CSV File Upload Handler
                        $('#smtp_csv_file').on('change', function(e) {
                            const file = e.target.files[0];
                            if (!file) return;

                            if (!file.name.toLowerCase().endsWith('.csv')) {
                                alert('Please upload a CSV file');
                                $(this).val('');
                                return;
                            }

                            const reader = new FileReader();
                            reader.onload = function(event) {
                                const csvContent = event.target.result;

                                // Store raw CSV content and filename in hidden fields (use unique IDs)
                                $('#smtp_csv_content').val(csvContent);
                                $('#smtp_csv_filename_input').val(file.name);

                                parseSmtpCsv(csvContent);
                            };
                            reader.readAsText(file);
                        });

                        function parseSmtpCsv(csvContent) {
                            const lines = csvContent.split('\n').filter(line => line.trim());
                            if (lines.length < 2) {
                                alert('CSV file is empty or has no data rows');
                                return;
                            }

                            // Parse header row
                            const headers = parseCsvLine(lines[0]);
                            const headerMap = {};
                            headers.forEach((header, index) => {
                                const normalizedHeader = header.toLowerCase().trim();

                                // Basic fields
                                if (normalizedHeader === 'email' || normalizedHeader === 'email address') {
                                    headerMap['email'] = index;
                                } else if (normalizedHeader === 'first name' || (normalizedHeader.includes('first') && normalizedHeader.includes('name'))) {
                                    headerMap['first_name'] = index;
                                } else if (normalizedHeader === 'last name' || (normalizedHeader.includes('last') && normalizedHeader.includes('name'))) {
                                    headerMap['last_name'] = index;
                                } else if (normalizedHeader === 'password') {
                                    // Exact match for "password" column
                                    headerMap['password'] = index;
                                } else if (normalizedHeader.includes('org') || normalizedHeader.includes('unit') || (normalizedHeader.includes('path') && !normalizedHeader.includes('imap') && !normalizedHeader.includes('smtp'))) {
                                    headerMap['org_unit_path'] = index;
                                }
                                // IMAP fields
                                else if (normalizedHeader === 'imap username' || normalizedHeader === 'imap user') {
                                    headerMap['imap_username'] = index;
                                } else if (normalizedHeader === 'imap password' || normalizedHeader.includes('imap') && normalizedHeader.includes('password')) {
                                    headerMap['imap_password'] = index;
                                } else if (normalizedHeader === 'imap host') {
                                    headerMap['imap_host'] = index;
                                } else if (normalizedHeader === 'imap port') {
                                    headerMap['imap_port'] = index;
                                }
                                // SMTP fields
                                else if (normalizedHeader === 'smtp username' || normalizedHeader === 'smtp user') {
                                    headerMap['smtp_username'] = index;
                                } else if (normalizedHeader === 'smtp password' || normalizedHeader.includes('smtp') && normalizedHeader.includes('password')) {
                                    headerMap['smtp_password'] = index;
                                } else if (normalizedHeader === 'smtp host') {
                                    headerMap['smtp_host'] = index;
                                } else if (normalizedHeader === 'smtp port') {
                                    headerMap['smtp_port'] = index;
                                }
                                // Warmup/Limit fields
                                else if (normalizedHeader === 'daily limit') {
                                    headerMap['daily_limit'] = index;
                                } else if (normalizedHeader === 'warmup enabled') {
                                    headerMap['warmup_enabled'] = index;
                                } else if (normalizedHeader === 'warmup limit') {
                                    headerMap['warmup_limit'] = index;
                                } else if (normalizedHeader === 'warmup increment') {
                                    headerMap['warmup_increment'] = index;
                                }
                            });

                            // Validate required headers
                            if (headerMap['email'] === undefined) {
                                alert('CSV must have an Email column');
                                return;
                            }

                            // Parse data rows
                            const accounts = [];
                            const domains = {};
                            const prefixes = new Set();

                            for (let i = 1; i < lines.length; i++) {
                                const values = parseCsvLine(lines[i]);
                                if (values.length === 0) continue;

                                const email = values[headerMap['email']] || '';
                                if (!email || !email.includes('@')) continue;

                                const [prefix, domain] = email.split('@');
                                if (!domain) continue;

                                // Get password values with proper fallbacks
                                const basePassword = headerMap['password'] !== undefined ? (values[headerMap['password']] || '') : '';
                                const imapPwd = headerMap['imap_password'] !== undefined ? (values[headerMap['imap_password']] || '') : '';
                                const smtpPwd = headerMap['smtp_password'] !== undefined ? (values[headerMap['smtp_password']] || '') : '';

                                // Use the first available password
                                const primaryPassword = basePassword || imapPwd || smtpPwd;

                                const account = {
                                    // Basic fields
                                    first_name: headerMap['first_name'] !== undefined ? (values[headerMap['first_name']] || '') : '',
                                    last_name: headerMap['last_name'] !== undefined ? (values[headerMap['last_name']] || '') : '',
                                    email: email,
                                    prefix: prefix,
                                    domain: domain,
                                    password: primaryPassword,
                                    org_unit_path: headerMap['org_unit_path'] !== undefined ? (values[headerMap['org_unit_path']] || '/') : '/',
                                    // IMAP fields
                                    imap_username: headerMap['imap_username'] !== undefined ? (values[headerMap['imap_username']] || email) : email,
                                    imap_password: imapPwd || primaryPassword,
                                    imap_host: headerMap['imap_host'] !== undefined ? (values[headerMap['imap_host']] || '') : '',
                                    imap_port: headerMap['imap_port'] !== undefined ? (values[headerMap['imap_port']] || '') : '',
                                    // SMTP fields
                                    smtp_username: headerMap['smtp_username'] !== undefined ? (values[headerMap['smtp_username']] || email) : email,
                                    smtp_password: smtpPwd || primaryPassword,
                                    smtp_host: headerMap['smtp_host'] !== undefined ? (values[headerMap['smtp_host']] || '') : '',
                                    smtp_port: headerMap['smtp_port'] !== undefined ? (values[headerMap['smtp_port']] || '') : '',
                                    // Warmup fields
                                    daily_limit: headerMap['daily_limit'] !== undefined ? (values[headerMap['daily_limit']] || '') : '',
                                    warmup_enabled: headerMap['warmup_enabled'] !== undefined ? (values[headerMap['warmup_enabled']] || '') : '',
                                    warmup_limit: headerMap['warmup_limit'] !== undefined ? (values[headerMap['warmup_limit']] || '') : '',
                                    warmup_increment: headerMap['warmup_increment'] !== undefined ? (values[headerMap['warmup_increment']] || '') : ''
                                };

                                accounts.push(account);

                                // Track domains and their inbox counts
                                if (!domains[domain]) {
                                    domains[domain] = { count: 0, prefixes: new Set() };
                                }
                                domains[domain].count++;
                                domains[domain].prefixes.add(prefix);
                                prefixes.add(prefix);
                            }

                            if (accounts.length === 0) {
                                alert('No valid email accounts found in CSV');
                                return;
                            }

                            // Update preview
                            updateSmtpCsvPreview(accounts, domains, prefixes);

                            // Store data in hidden field
                            $('#smtp_accounts_data').val(JSON.stringify({
                                accounts: accounts,
                                domains: Object.keys(domains).map(d => ({
                                    name: d,
                                    inbox_count: domains[d].count,
                                    prefixes: Array.from(domains[d].prefixes)
                                })),
                                total_inboxes: accounts.length,
                                unique_domains: Object.keys(domains).length,
                                max_per_domain: Math.max(...Object.values(domains).map(d => d.count)),
                                prefix_count: prefixes.size
                            }));

                            // Also update the hidden form fields for domains and total_inboxes
                            const domainsArray = Object.keys(domains).map((domainName, index) => ({
                                id: 'smtp_' + (index + 1),
                                name: domainName,
                                is_used: false,
                                prefix_statuses: {}
                            }));

                            // Populate domains textarea with domain list (for backend processing)
                            $('#domains').val(Object.keys(domains).join('\n'));
                            $('#total_inboxes').val(accounts.length);
                            $('#inboxes_per_domain').val(Math.max(...Object.values(domains).map(d => d.count)));

                            // Trigger domain count update
                            countDomains();
                        }

                        function parseCsvLine(line) {
                            const result = [];
                            let current = '';
                            let inQuotes = false;

                            for (let i = 0; i < line.length; i++) {
                                const char = line[i];

                                if (char === '"') {
                                    inQuotes = !inQuotes;
                                } else if (char === ',' && !inQuotes) {
                                    result.push(current.trim());
                                    current = '';
                                } else {
                                    current += char;
                                }
                            }
                            result.push(current.trim());

                            return result;
                        }

                        function updateSmtpCsvPreview(accounts, domains, prefixes) {
                            // Show preview section
                            $('#smtp-csv-preview').slideDown(300);

                            // Update badges
                            $('#smtp-csv-count').text(accounts.length + ' accounts');
                            $('#smtp-csv-domains').text(Object.keys(domains).length + ' domains');

                            // Determine which extended columns have data
                            const hasImapData = accounts.some(a => a.imap_host || a.imap_port);
                            const hasSmtpData = accounts.some(a => a.smtp_host || a.smtp_port);
                            const hasImapPassword = accounts.some(a => a.imap_password && a.imap_password !== a.password);
                            const hasSmtpPassword = accounts.some(a => a.smtp_password && a.smtp_password !== a.password);
                            const hasWarmupData = accounts.some(a => a.daily_limit || a.warmup_enabled || a.warmup_limit || a.warmup_increment);

                            // Build table header dynamically
                            const thead = $('#smtp-csv-table thead tr');
                            thead.empty();

                            // Base columns
                            thead.append('<th>First Name</th>');
                            thead.append('<th>Last Name</th>');
                            thead.append('<th>Email Address</th>');
                            thead.append('<th>Domain</th>');
                            thead.append('<th>Password</th>');

                            // IMAP columns (if data exists)
                            if (hasImapData) {
                                thead.append('<th>IMAP Host</th>');
                                thead.append('<th>IMAP Port</th>');
                            }
                            if (hasImapPassword) {
                                thead.append('<th>IMAP Password</th>');
                            }

                            // SMTP columns (if data exists)
                            if (hasSmtpData) {
                                thead.append('<th>SMTP Host</th>');
                                thead.append('<th>SMTP Port</th>');
                            }
                            if (hasSmtpPassword) {
                                thead.append('<th>SMTP Password</th>');
                            }

                            // Warmup columns (if data exists)
                            if (hasWarmupData) {
                                thead.append('<th>Daily Limit</th>');
                                thead.append('<th>Warmup</th>');
                            }

                            // Build table rows
                            const tbody = $('#smtp-csv-tbody');
                            tbody.empty();

                            accounts.forEach(account => {
                                let row = '<tr>';

                                // Base columns - using theme-compatible styling
                                row += '<td class="text-white">' + safeDisplayVal(account.first_name) + '</td>';
                                row += '<td class="text-white">' + safeDisplayVal(account.last_name) + '</td>';
                                row += '<td><span class="smtp-email-cell">' + safeDisplayVal(account.email) + '</span></td>';
                                row += '<td><span class="badge smtp-domain-badge">' + safeDisplayVal(account.domain) + '</span></td>';
                                row += '<td><span class="smtp-password-cell">' + safeDisplayVal(account.password) + '</span></td>';

                                // IMAP columns
                                if (hasImapData) {
                                    row += '<td><span class="smtp-data-cell">' + safeDisplayVal(account.imap_host) + '</span></td>';
                                    row += '<td class="text-white">' + safeDisplayVal(account.imap_port) + '</td>';
                                }
                                if (hasImapPassword) {
                                    row += '<td><span class="smtp-password-cell">' + safeDisplayVal(account.imap_password) + '</span></td>';
                                }

                                // SMTP columns
                                if (hasSmtpData) {
                                    row += '<td><span class="smtp-data-cell">' + safeDisplayVal(account.smtp_host) + '</span></td>';
                                    row += '<td class="text-white">' + safeDisplayVal(account.smtp_port) + '</td>';
                                }
                                if (hasSmtpPassword) {
                                    row += '<td><span class="smtp-password-cell">' + safeDisplayVal(account.smtp_password) + '</span></td>';
                                }

                                // Warmup columns
                                if (hasWarmupData) {
                                    row += '<td>' + safeDisplayVal(account.daily_limit) + '</td>';

                                    let warmupStatus = '-';
                                    if (account.warmup_enabled === true || account.warmup_enabled === "true" || account.warmup_enabled === "1") {
                                        warmupStatus = '<span class="badge bg-success">Yes</span>';
                                    } else if (account.warmup_enabled === false || account.warmup_enabled === "false" || account.warmup_enabled === "0") {
                                        warmupStatus = '<span class="badge bg-secondary">No</span>';
                                    }
                                    row += '<td>' + warmupStatus + '</td>';
                                }

                                row += '</tr>';
                                tbody.append(row);
                            });

                            // Update summary cards
                            $("#smtp-total-inboxes").text(accounts.length);
                            $("#smtp-unique-domains").text(Object.keys(domains).length);
                            $("#smtp-max-per-domain").text(Math.max(...Object.values(domains).map(d => d.count)));
                            $("#smtp-prefix-count").text(prefixes.size);
                        }

                        function safeDisplayVal(val) {
                            if (val === null || val === undefined || val === '') return '-';
                            return escapeHtml(String(val));
                        }

                        function escapeHtml(text) {
                            if (!text) return '';
                            return text.replace(/&/g, '&amp;')
                                       .replace(/</g, '&lt;')
                                       .replace(/>/g, '&gt;')
                                       .replace(/"/g, '&quot;')
                                       .replace(/'/g, '&#039;');
                        }

                    });
                    </script>


    @endpush
