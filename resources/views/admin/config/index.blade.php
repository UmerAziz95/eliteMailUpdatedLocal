@extends('admin.layouts.app')

@section('title', 'security')

@push('styles')
<style>
    .bg-label-secondary {
        background-color: #ffffff28;
        color: var(--extra-light);
        font-weight: 100;
    }

    .nav-tabs .nav-link {
        color: var(--extra-light);
        border: none
    }

    .nav-tabs .nav-link:hover {
        color: var(--white-color);
        border: none
    }

    .nav-tabs .nav-link.active {
        background-color: var(--second-primary);
        color: #fff;
        border: none;
        border-radius: 6px
    }

    .timeline .timeline-item {
        position: relative;
        border: 0;
        border-inline-start: 1px solid var(--extra-light);
        padding-inline-start: 1.4rem;
    }

    .timeline .timeline-item .timeline-point {
        position: absolute;
        z-index: 10;
        display: block;
        background-color: var(--second-primary);
        block-size: .75rem;
        box-shadow: 0 0 0 10px var(--secondary-color);
        inline-size: .75rem;
        inset-block-start: 0;
        inset-inline-start: -0.38rem;
        outline: 3px solid #506295;
        border-radius: 50%;
        opacity: 1
    }

   
    .bg-lighter {
        background-color: #ffffff1d;
        padding: .3rem;
        border-radius: 4px;
        color: var(--extra-light)
    }

    .timeline:not(.timeline-center) {
        padding-inline-start: .5rem;
    }

    #logoThumbnail {
        transition: all 0.3s ease;
    }

    #logoThumbnail:hover {
        border-color: #007bff !important;
        box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
    }
</style>
@endpush

@section('content')
<div class="row py-4">

    <div class="col-12">
        <ul class="nav nav-tabs border-0" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="chargebee-tab" data-bs-toggle="tab"
                    data-bs-target="#chargebee_configuration_tab-pane" type="button" role="tab" aria-controls="chargebee_configuration_tab-pane"
                    aria-selected="false"><i class="fa-solid fa-unlock"></i> Chargebee Configuration</button>
            </li>
            
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notify-tab" data-bs-toggle="tab" data-bs-target="#backup-tab-pane"
                    type="button" role="tab" aria-controls="backup-tab-pane" aria-selected="false"><i
                        class="fa-regular fa-file"></i> System Backup</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="configuration-pane" data-bs-toggle="tab" data-bs-target="#system_configuration-pane"
                    type="button" role="tab" aria-controls="system_configuration-tab-pane" aria-selected="false"><i
                        class="fa-regular fa-file"></i> System Configuration</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="panel-config-tab" data-bs-toggle="tab" data-bs-target="#panel_configuration-pane"
                    type="button" role="tab" aria-controls="panel_configuration-tab-pane" aria-selected="false"><i
                        class="fa-solid fa-sliders"></i> Panel Configurations</button>
            </li>
        </ul>

        <div class="tab-content mt-4" id="myTabContent">
            <div class="tab-pane fade show active" id="account-tab-pane" role="tabpanel" aria-labelledby="account-tab"
                tabindex="0">
            </div>

            <!-- Chargebee Configuration -->
            <div class="tab-pane fade active show" id="chargebee_configuration_tab-pane" role="tabpanel"
                aria-labelledby="chargebe_configuration-tab" tabindex="0">
                <div class="card mb-4 p-3">
                    <h5 class="card-header">Chargebee Configuration</h5>
                    <div class="card-body">
                        <form id="chargebeeConfigForm">
                            @csrf
                            @php
                                $chargebeeArray = [];
                                if (isset($chargebeeConfigs)) {
                                    foreach ($chargebeeConfigs as $config) {
                                        $chargebeeArray[$config->key] = $config->value;
                                    }
                                }
                            @endphp

                            <div class="row gx-3">
                                <div class="col-md-6 mb-3">
                                    <label for="chargebeePublishableKey" class="form-label">Publishable API Key</label>
                                    <input type="text" id="chargebeePublishableKey" name="CHARGEBEE_PUBLISHABLE_API_KEY" 
                                           class="form-control" 
                                           value="{{ $chargebeeArray['CHARGEBEE_PUBLISHABLE_API_KEY'] ?? '' }}"
                                           placeholder="Enter Publishable API Key">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="chargebeeSite" class="form-label">Chargebee Site</label>
                                    <input type="text" id="chargebeeSite" name="CHARGEBEE_SITE" 
                                           class="form-control" 
                                           value="{{ $chargebeeArray['CHARGEBEE_SITE'] ?? '' }}"
                                           placeholder="Enter Site Name">
                                </div>
                            </div>

                            <div class="row gx-3">
                                <div class="col-md-12 mb-3">
                                    <label for="chargebeeApiKey" class="form-label">Secret API Key</label>
                                    <input type="text" id="chargebeeApiKey" name="CHARGEBEE_API_KEY" 
                                           class="form-control" 
                                           value="{{ $chargebeeArray['CHARGEBEE_API_KEY'] ?? '' }}"
                                           placeholder="Enter Secret API Key">
                                </div>
                            </div>

                            <div class="mt-4">

                                <button type="submit" id="chargebeeConfigSubmit" class="btn btn-primary">Save Configuration</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- System Backup -->
            <div class="tab-pane fade" id="backup-tab-pane" role="tabpanel" aria-labelledby="backup-tab" tabindex="0">
                <div class="card mb-4 p-3">
                    <h5 class="card-header">System Backups</h5>
                    <div class="card-body">
                        <!-- Filters -->
                        <div class="row g-3 align-items-end mb-3">
                            <div class="col-md-3">
                                <label class="form-label" for="backupStart">Start Date</label>
                                <input type="date" class="form-control" id="backupStart" value="{{ now()->subDays(30)->toDateString() }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="backupEnd">End Date</label>
                                <input type="date" class="form-control" id="backupEnd" value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="backupSizeMin">Min Size (MB)</label>
                                <input type="number" min="0" step="1" class="form-control" id="backupSizeMin" placeholder="e.g. 10">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label" for="backupSizeMax">Max Size (MB)</label>
                                <input type="number" min="0" step="1" class="form-control" id="backupSizeMax" placeholder="e.g. 500">
                            </div>
                            <div class="col-md-2 d-flex gap-2">
                                <button type="button" id="applyBackupFilters" class="btn btn-primary w-100">Apply</button>
                                <button type="button" id="resetBackupFilters" class="btn btn-secondary w-100">Reset</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="backupsTable" class="table table-bordered table-hover align-middle mb-0" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Backup File</th>
                                        <th>File Size</th>
                                        <th>Created At</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="tab-pane fade" id="system_configuration-pane" role="tabpanel"
                aria-labelledby="system_configuration-tab" tabindex="0">
                <div class="card mb-4 p-3">
                    <h5 class="card-header">System Configuration</h5>
                    <div class="card-body">
                        <form id="systemConfigForm">
                            @csrf
                            @php
                                $systemArray = [];
                                if (isset($systemConfigs)) {
                                    foreach ($systemConfigs as $config) {
                                        $systemArray[$config->key] = $config->value;
                                    }
                                }
                            @endphp
                            
                            <div class="row gx-4">
                                <div class="col-md-6 mb-3">
                                    <label for="systemName" class="form-label">System Name</label>
                                    <input type="text" class="form-control" id="systemName" name="SYSTEM_NAME" 
                                           value="{{ $systemArray['SYSTEM_NAME'] ?? '' }}"
                                           placeholder="My Application">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="adminEmail" class="form-label">Admin Email</label>
                                    <input type="email" class="form-control" id="adminEmail" name="ADMIN_EMAIL" 
                                           value="{{ $systemArray['ADMIN_EMAIL'] ?? '' }}"
                                           placeholder="admin@example.com">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="supportEmail" class="form-label">Support Email</label>
                                    <input type="email" class="form-control" id="supportEmail" name="SUPPORT_EMAIL" 
                                           value="{{ $systemArray['SUPPORT_EMAIL'] ?? '' }}"
                                           placeholder="support@example.com">
                                </div>

                                <div class="col-md-12 mb-3">
                                    <label for="footerText" class="form-label">Footer Text</label>
                                    <textarea class="form-control" id="footerText" name="FOOTER_TEXT" rows="3"
                                              placeholder="© 2025 My Application. All rights reserved.">{{ $systemArray['FOOTER_TEXT'] ?? '' }}</textarea>
                                </div>
                                
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">System Logo</label>
                                    <div class="d-flex align-items-center gap-3">
                                        <!-- Logo Preview Thumbnail -->
                                        <div id="logoThumbnail" class="border rounded d-flex align-items-center justify-content-center" 
                                             style="width: 80px; height: 80px; background-color: #f8f9fa; cursor: pointer; overflow: hidden;">
                                            @if(!empty($systemArray['SYSTEM_LOGO']))
                                                <img src="{{ asset($systemArray['SYSTEM_LOGO']) }}" id="logoThumbImg" alt="Logo" 
                                                     style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                            @else
                                                <i class="fa fa-image fa-2x text-muted" id="logoPlaceholderIcon"></i>
                                                <img src="" id="logoThumbImg" alt="Logo" style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;">
                                            @endif
                                        </div>
                                        
                                        <!-- Upload Button and Info -->
                                        <div class="flex-grow-1">
                                            <input type="file" class="d-none" id="logo" name="logo" accept="image/*">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="document.getElementById('logo').click()">
                                                <i class="fa fa-upload me-1"></i> Upload Logo
                                            </button>
                                            <!-- Remove Logo Button -->
                                            <button type="button" class="btn btn-sm btn-outline-danger ms-2" id="removeLogo" style="display: {{ !empty($systemArray['SYSTEM_LOGO']) ? 'inline-block' : 'none' }};">
                                                <i class="fa fa-trash me-1"></i> Remove
                                            </button>
                                            <div class="mt-2">
                                                <small class="text-muted d-block">Accepted: JPG, PNG, GIF, SVG</small>
                                                <small class="text-muted">Max size: 2MB</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Hidden input to track logo removal -->
                            <input type="hidden" id="removeLogo_input" name="remove_logo" value="0">

                            <div class="mt-3">
                                <button type="submit" id="systemConfigSubmit" class="btn btn-primary">Save Configuration</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>


            <div class="tab-pane fade" id="panel_configuration-pane" role="tabpanel"
                 aria-labelledby="panel_configuration-tab" tabindex="0">
                <div class="card mb-4 p-3">
                    <h5 class="card-header">Panel Configurations</h5>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead class="">
                                    <tr>
                                        <th>Configuration Key</th>
                                        <th>Label / Description</th>
                                        <th>Value</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $panelConfigs = collect($configurations ?? []);
                                    @endphp
                                    
                                    @forelse ($panelConfigs as $config)
                                        @php
                                            $type = strtolower($config->type ?? 'string');
                                            $rawValue = $config->value ?? '';
                                            $displayValue = ($rawValue === null || $rawValue === '') ? '--' : $rawValue;
                                            $badgeClass = null;

                                            if ($type === 'boolean') {
                                                $isTrue = filter_var($rawValue, FILTER_VALIDATE_BOOLEAN);
                                                $displayValue = $isTrue ? 'true' : 'false';
                                                $badgeClass = $isTrue ? 'bg-label-success' : 'bg-label-danger';
                                            } elseif ($config->key === 'PROVIDER_TYPE') {
                                                $badgeClass = 'bg-label-success';
                                            }

                                            $keySlug = \Illuminate\Support\Str::slug($config->key, '-');
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $config->key }}</strong></td>
                                            <td id="desc-{{ $keySlug }}" class="text-muted">
                                                {{ $config->description ?? '--' }}
                                            </td>
                                            <td id="value-{{ $keySlug }}">
                                                @if ($badgeClass)
                                                    <span class="badge {{ $badgeClass }}">{{ $displayValue }}</span>
                                                @else
                                                    {{ $displayValue }}
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="editConfig('{{ $config->key }}')">
                                                    <i class="fa fa-edit me-1"></i>Edit
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No panel configurations found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>


<!-- Modal -->
<div class="modal fade" style="scrollbar-width: none" id="addRoleModal" tabindex="-1"
    aria-labelledby="addRoleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                    data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                <div class="text-center mb-6">
                    <h4 class="address-title mb-2">Edit Address</h4>
                    <p class="address-subtitle">Edit your current address</p>
                </div>
                <form id="addNewAddressForm" class="row g-6">
                    <div class="col-12">
                        <div class="row">
                            <div class="col-md mb-md-0 mb-4">
                                <div class="form-check custom-option custom-option-icon checked">
                                    <label class="form-check-label custom-option-content" for="customRadioHome">
                                        <span class="custom-option-body">
                                            <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path opacity="0.2"
                                                    d="M16.625 23.625V16.625H11.375V23.625H4.37501V12.6328C4.37437 12.5113 4.39937 12.391 4.44837 12.2798C4.49737 12.1686 4.56928 12.069 4.65939 11.9875L13.4094 4.03592C13.5689 3.88911 13.7778 3.80762 13.9945 3.80762C14.2113 3.80762 14.4202 3.88911 14.5797 4.03592L23.3406 11.9875C23.4287 12.0706 23.4992 12.1706 23.548 12.2814C23.5969 12.3922 23.6231 12.5117 23.625 12.6328V23.625H16.625Z">
                                                </path>
                                                <path
                                                    d="M23.625 23.625V12.6328C23.623 12.5117 23.5969 12.3922 23.548 12.2814C23.4992 12.1706 23.4287 12.0706 23.3406 11.9875L14.5797 4.03592C14.4202 3.88911 14.2113 3.80762 13.9945 3.80762C13.7777 3.80762 13.5689 3.88911 13.4094 4.03592L4.65937 11.9875C4.56926 12.069 4.49736 12.1686 4.44836 12.2798C4.39936 12.391 4.37436 12.5113 4.375 12.6328V23.625M1.75 23.625H26.25M16.625 23.625V17.5C16.625 17.2679 16.5328 17.0454 16.3687 16.8813C16.2046 16.7172 15.9821 16.625 15.75 16.625H12.25C12.0179 16.625 11.7954 16.7172 11.6313 16.8813C11.4672 17.0454 11.375 17.2679 11.375 17.5V23.625"
                                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                </path>
                                            </svg>
                                            <span class="custom-option-title">Home</span>
                                            <small> Delivery time (9am – 9pm) </small>
                                        </span>
                                        <input name="customRadioIcon" class="form-check-input rounded-1" type="radio"
                                            value="" id="customRadioHome" checked="">
                                    </label>
                                </div>
                            </div>
                            <div class="col-md mb-md-0 mb-4">
                                <div class="form-check custom-option custom-option-icon">
                                    <label class="form-check-label custom-option-content" for="customRadioOffice">
                                        <span class="custom-option-body">
                                            <svg width="28" height="28" viewBox="0 0 28 28" fill="none"
                                                xmlns="http://www.w3.org/2000/svg">
                                                <path opacity="0.2"
                                                    d="M15.75 23.625V4.375C15.75 4.14294 15.6578 3.92038 15.4937 3.75628C15.3296 3.59219 15.1071 3.5 14.875 3.5H4.375C4.14294 3.5 3.92038 3.59219 3.75628 3.75628C3.59219 3.92038 3.5 4.14294 3.5 4.375V23.625">
                                                </path>
                                                <path
                                                    d="M1.75 23.625H26.25M15.75 23.625V4.375C15.75 4.14294 15.6578 3.92038 15.4937 3.75628C15.3296 3.59219 15.1071 3.5 14.875 3.5H4.375C4.14294 3.5 3.92038 3.59219 3.75628 3.75628C3.59219 3.92038 3.5 4.14294 3.5 4.375V23.625M24.5 23.625V11.375C24.5 11.1429 24.4078 10.9204 24.2437 10.7563C24.0796 10.5922 23.8571 10.5 23.625 10.5H15.75M7 7.875H10.5M8.75 14.875H12.25M7 19.25H10.5M19.25 19.25H21M19.25 14.875H21"
                                                    stroke-opacity="0.9" stroke-width="2" stroke-linecap="round"
                                                    stroke-linejoin="round"></path>
                                            </svg>
                                            <span class="custom-option-title"> Office </span>
                                            <small> Delivery time (9am – 5pm) </small>
                                        </span>
                                        <input name="customRadioIcon" class="form-check-input rounded-1" type="radio"
                                            value="" id="customRadioOffice">
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="modalAddressFirstName">First Name</label>
                        <input type="text" id="modalAddressFirstName" name="modalAddressFirstName" class="form-control"
                            placeholder="John">
                        <div
                            class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="modalAddressLastName">Last Name</label>
                        <input type="text" id="modalAddressLastName" name="modalAddressLastName" class="form-control"
                            placeholder="Doe">
                        <div
                            class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="modalAddressCountry">Country</label>
                        <div class="position-relative">
                            <div class="position-relative"><select id="modalAddressCountry" name="modalAddressCountry"
                                    class="select2 form-select select2-hidden-accessible" data-allow-clear="true"
                                    tabindex="-1" aria-hidden="true" data-select2-id="modalAddressCountry">
                                    <option value="" data-select2-id="82">Select</option>
                                    <option value="Australia">Australia</option>
                                    <option value="Bangladesh">Bangladesh</option>
                                    <option value="Belarus">Belarus</option>
                                    <option value="Brazil">Brazil</option>
                                    <option value="Canada">Canada</option>
                                    <option value="China">China</option>
                                    <option value="France">France</option>
                                    <option value="Germany">Germany</option>
                                    <option value="India">India</option>
                                    <option value="Indonesia">Indonesia</option>
                                    <option value="Israel">Israel</option>
                                    <option value="Italy">Italy</option>
                                    <option value="Japan">Japan</option>
                                    <option value="Korea">Korea, Republic of</option>
                                    <option value="Mexico">Mexico</option>
                                    <option value="Philippines">Philippines</option>
                                    <option value="Russia">Russian Federation</option>
                                    <option value="South Africa">South Africa</option>
                                    <option value="Thailand">Thailand</option>
                                    <option value="Turkey">Turkey</option>
                                    <option value="Ukraine">Ukraine</option>
                                    <option value="United Arab Emirates">United Arab Emirates</option>
                                    <option value="United Kingdom">United Kingdom</option>
                                    <option value="United States">United States</option>
                                </select><span class="select2 select2-container select2-container--default" dir="ltr"
                                    data-select2-id="81" style="width: auto;"><span class="selection"><span
                                            class="select2-selection select2-selection--single" role="combobox"
                                            aria-haspopup="true" aria-expanded="false" tabindex="0"
                                            aria-disabled="false"
                                            aria-labelledby="select2-modalAddressCountry-container"><span
                                                class="select2-selection__rendered"
                                                id="select2-modalAddressCountry-container" role="textbox"
                                                aria-readonly="true"><span class="select2-selection__placeholder">Select
                                                    value</span></span><span class="select2-selection__arrow"
                                                role="presentation"><b
                                                    role="presentation"></b></span></span></span><span
                                        class="dropdown-wrapper" aria-hidden="true"></span></span></div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="modalAddressAddress1">Address Line 1</label>
                        <input type="text" id="modalAddressAddress1" name="modalAddressAddress1" class="form-control"
                            placeholder="12, Business Park">
                    </div>

                    <div class="col-12">
                        <label class="form-label" for="modalAddressAddress2">Address Line 2</label>
                        <input type="text" id="modalAddressAddress2" name="modalAddressAddress2" class="form-control"
                            placeholder="Mall Road">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="modalAddressLandmark">Landmark</label>
                        <input type="text" id="modalAddressLandmark" name="modalAddressLandmark" class="form-control"
                            placeholder="Nr. Hard Rock Cafe">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="modalAddressCity">City</label>
                        <input type="text" id="modalAddressCity" name="modalAddressCity" class="form-control"
                            placeholder="Los Angeles">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="modalAddressLandmark">State</label>
                        <input type="text" id="modalAddressState" name="modalAddressState" class="form-control"
                            placeholder="California">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="modalAddressZipCode">Zip Code</label>
                        <input type="text" id="modalAddressZipCode" name="modalAddressZipCode" class="form-control"
                            placeholder="99950">
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input type="checkbox" class="form-check-input rounded-1" id="billingAddress">
                            <label for="billingAddress" class="form-switch-label">Use as a billing address?</label>
                        </div>
                    </div>

                    <div class="col-12 text-center">
                        <button type="submit" class="m-btn py-2 px-4 border-0 rounded-2">Submit</button>
                        <button type="reset" class="cancel-btn py-2 px-4 border-0 rounded-2" data-bs-dismiss="modal"
                            aria-label="Close">Cancel</button>
                    </div>

                    <input type="hidden">
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Configuration Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="editConfigOffcanvas" aria-labelledby="editConfigOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="editConfigOffcanvasLabel">Edit Configuration</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="editConfigForm">
            <div class="mb-3">
                <label for="configKey" class="form-label">Configuration Key</label>
                <input type="text" class="form-control" id="configKey" readonly>
            </div>
            <div class="mb-3">
                <label for="configDescription" class="form-label">Label / Description</label>
                <textarea class="form-control" id="configDescription" rows="3" placeholder="Enter description"></textarea>
                <small class="text-muted">This helps identify what this configuration is used for.</small>
            </div>
            <div class="mb-3" id="numberInput">
                <label for="configValue" class="form-label">Value</label>
                <input type="number" class="form-control" id="configValue" placeholder="Enter value">
            </div>
            <div class="mb-3" id="selectInput" style="display: none;">
                <label for="configSelectValue" class="form-label">Value</label>
                <select class="form-select" id="configSelectValue">
                    @foreach($providerTypes ?? [] as $provider)
                        <option value="{{ $provider }}">{{ $provider }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3" id="booleanInput" style="display: none;">
                <label class="form-label">Value</label>
                <div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="configBoolValue" id="boolTrue" value="true">
                        <label class="form-check-label" for="boolTrue">True</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="configBoolValue" id="boolFalse" value="false">
                        <label class="form-check-label" for="boolFalse">False</label>
                    </div>
                </div>
            </div>
            <div class="d-grid gap-2">
                <button type="button" class="btn btn-primary" id="saveConfigBtn">
                    <i class="fa fa-save me-2"></i>Save Changes
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">
                    <i class="fa fa-times me-2"></i>Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" style="scrollbar-width: none" id="edit" tabindex="-1" aria-labelledby="editLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                    data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                <div class="text-center mb-6">
                    <h4 class="mb-2">Edit User Information</h4>
                    <p>Updating user details will receive a privacy audit.</p>
                </div>
                <form id="editUserForm" class="row gy-3">
                    <div class="col-12 col-md-12">
                        <label class="form-label" for="modalEditUserFirstName">Full Name</label>
                        <input type="text" id="modalEditUserFirstName" name="modalEditUserFirstName"
                            class="form-control" placeholder="John" value="{{ Auth::user()->name }}">
                    </div>
                    <div class="col-12 col-md-12">
                        <label class="form-label" for="modalEditUserEmail">Email</label>
                        <input type="text" id="modalEditUserEmail" name="modalEditUserEmail" class="form-control"
                            placeholder="example@domain.com" value="{{ Auth::user()->email }}" readonly>
                    </div>



                    <div class="col-12 col-md-12">
                        <label class="form-label" for="modalEditUserPhone">Phone Number</label>
                        <div class="input-group">
                            <input type="text" id="modalEditUserPhone" name="modalEditUserPhone"
                                class="form-control phone-number-mask" placeholder="" value="{{ Auth::user()->phone }}">
                        </div>
                    </div>

                    <div class="col-12 text-center">
                        <button type="submit" class="m-btn py-2 px-4 rounded-2 border-0 ">Submit</button>
                        <button type="reset" class="cancel-btn py-2 px-4 rounded-2 border-0" data-bs-dismiss="modal"
                            aria-label="Close">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="cropperModal" tabindex="-1" role="dialog" aria-labelledby="cropperModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Profile Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="cropper-container">
                    <img id="cropperImage" src="" alt="Image to crop" style="max-width: 100%;">
                </div>
                <div class="cropper-controls">
                    <button type="button" class="rotate-left"><i class="ti ti-rotate-clockwise-2"></i> Rotate
                        Left</button>
                    <button type="button" class="rotate-right"><i class="ti ti-rotate"></i> Rotate Right</button>
                    <button type="button" class="flip-horizontal"><i class="ti ti-flip-horizontal"></i> Flip H</button>
                    <button type="button" class="flip-vertical"><i class="ti ti-flip-vertical"></i> Flip V</button>
                    <div class="zoom-controls">
                        <button type="button" class="zoom-in"><i class="ti ti-zoom-in"></i></button>
                        <input type="range" class="zoom-slider" min="0" max="100" value="0">
                        <button type="button" class="zoom-out"><i class="ti ti-zoom-out"></i></button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="cancel-btn py-2 px-4 rounded-2 border-0"
                    data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="m-btn py-2 px-4 rounded-2 border-0" id="cropButton">Crop & Upload</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Panel Configuration Edit Functions
    let currentConfigKey = '';
    let currentConfigType = '';

    function editConfig(key, value = null, type = null, description = null) {
        // Show loading state
        Swal.fire({
            title: 'Loading...',
            text: 'Fetching configuration data',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Fetch fresh data from the server
        fetch('{{ route("admin.panel.configurations.get") }}', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            // Close loading
            Swal.close();
            
            if (data.success) {
                // Find the configuration by key
                const config = data.data.find(c => c.key === key);
                
                if (config) {
                    currentConfigKey = config.key;
                    currentConfigType = config.type;
                    
                    // Hide all input types first
                    document.getElementById('numberInput').style.display = 'none';
                    document.getElementById('selectInput').style.display = 'none';
                    document.getElementById('booleanInput').style.display = 'none';
                    
                    // Reset all input values
                    document.getElementById('configValue').value = '';
                    document.getElementById('configSelectValue').selectedIndex = 0;
                    document.querySelectorAll('input[name="configBoolValue"]').forEach(radio => radio.checked = false);
                    
                    // Set form values with fresh data
                    document.getElementById('configKey').value = config.key;
                    document.getElementById('configDescription').value = config.description || '';
                    
                    // Show and set the appropriate input based on type
                    if (config.type === 'boolean') {
                        document.getElementById('booleanInput').style.display = 'block';
                        
                        if (config.value === 'true' || config.value === true) {
                            document.getElementById('boolTrue').checked = true;
                        } else {
                            document.getElementById('boolFalse').checked = true;
                        }
                    } else if (config.type === 'select' || config.type === 'string') {
                        // Check if key is PROVIDER_TYPE for select dropdown
                        if (config.key === 'PROVIDER_TYPE') {
                            document.getElementById('selectInput').style.display = 'block';
                            setTimeout(() => {
                                document.getElementById('configSelectValue').value = config.value;
                            }, 10);
                        } else {
                            document.getElementById('numberInput').style.display = 'block';
                            document.getElementById('configValue').value = config.value;
                            document.getElementById('configValue').type = 'text';
                        }
                    } else {
                        document.getElementById('numberInput').style.display = 'block';
                        document.getElementById('configValue').value = config.value;
                        document.getElementById('configValue').type = config.type === 'number' ? 'number' : 'text';
                    }
                    
                    // Open offcanvas
                    const offcanvas = new bootstrap.Offcanvas(document.getElementById('editConfigOffcanvas'));
                    offcanvas.show();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Configuration not found',
                        confirmButtonText: 'OK'
                    });
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to fetch configuration',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while fetching the configuration',
                confirmButtonText: 'OK'
            });
        });
    }

    document.getElementById('saveConfigBtn').addEventListener('click', function() {
        let newValue;
        let newDescription = document.getElementById('configDescription').value;
        
        if (currentConfigType === 'boolean') {
            newValue = document.querySelector('input[name="configBoolValue"]:checked').value;
        } else if (currentConfigType === 'select') {
            newValue = document.getElementById('configSelectValue').value;
        } else {
            newValue = document.getElementById('configValue').value;
        }

        // Validate input
        if (!newValue && currentConfigType !== 'boolean') {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please enter a value',
                confirmButtonText: 'OK'
            });
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'Updating Configuration',
            text: 'Please wait...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Make AJAX call to save to the backend
        fetch('{{ route("admin.panel.configurations.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                key: currentConfigKey,
                value: newValue,
                type: currentConfigType,
                description: newDescription
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the table display
                const keySlug = currentConfigKey.toLowerCase().replace(/_/g, '-');
                const valueCell = document.getElementById(`value-${keySlug}`);
                const descCell = document.getElementById(`desc-${keySlug}`);
                
                // Update description
                if (descCell) {
                    descCell.textContent = newDescription;
                }
                
                // Update value
                if (currentConfigType === 'boolean') {
                    const badgeClass = newValue === 'true' ? 'bg-label-success' : 'bg-label-danger';
                    valueCell.innerHTML = `<span class="badge ${badgeClass}">${newValue}</span>`;
                } else if (currentConfigType === 'select') {
                    valueCell.innerHTML = `<span class="badge bg-label-info">${newValue}</span>`;
                } else {
                    valueCell.textContent = newValue;
                }
                
                // Close offcanvas
                const offcanvasElement = document.getElementById('editConfigOffcanvas');
                const offcanvas = bootstrap.Offcanvas.getInstance(offcanvasElement);
                if (offcanvas) {
                    offcanvas.hide();
                }
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Configuration updated successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to update configuration',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while updating the configuration',
                confirmButtonText: 'OK'
            });
        });
    });

    // Reset form when offcanvas is closed
    document.getElementById('editConfigOffcanvas').addEventListener('hidden.bs.offcanvas', function () {
        // Clear all inputs
        document.getElementById('configKey').value = '';
        document.getElementById('configDescription').value = '';
        document.getElementById('configValue').value = '';
        document.getElementById('configSelectValue').selectedIndex = 0;
        document.querySelectorAll('input[name="configBoolValue"]').forEach(radio => radio.checked = false);
        
        // Hide all input sections
        document.getElementById('numberInput').style.display = 'none';
        document.getElementById('selectInput').style.display = 'none';
        document.getElementById('booleanInput').style.display = 'none';
        
        // Reset tracking variables
        currentConfigKey = '';
        currentConfigType = '';
    });

    // Chargebee Configuration Form
    document.getElementById('chargebeeConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {};
        formData.forEach((value, key) => {
            if (key !== '_token') {
                data[key] = value;
            }
        });

        // Show loading
        Swal.fire({
            title: 'Saving...',
            text: 'Updating Chargebee configuration',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('{{ route("admin.chargebee.configurations.update") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            // enabled submit button
            document.getElementById('chargebeeConfigSubmit').disabled = false;
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message || 'Chargebee configuration updated successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to update Chargebee configuration',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('chargebeeConfigSubmit').disabled = false;
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while saving',
                confirmButtonText: 'OK'
            });
        });
    });

    // Logo Thumbnail Click - Show Larger Preview
    document.getElementById('logoThumbnail').addEventListener('click', function() {
        const logoThumbImg = document.getElementById('logoThumbImg');
        if (logoThumbImg.src && logoThumbImg.style.display !== 'none') {
            Swal.fire({
                imageUrl: logoThumbImg.src,
                imageAlt: 'System Logo',
                showConfirmButton: false,
                showCloseButton: true,
                width: 'auto',
                padding: '2rem',
                background: '#fff'
            });
        }
    });

    // Logo Preview Handler
    document.getElementById('logo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml'];
            if (!validTypes.includes(file.type)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid File Type',
                    text: 'Please select a valid image file (JPG, PNG, GIF, SVG)',
                    confirmButtonText: 'OK'
                });
                e.target.value = '';
                return;
            }

            // Validate file size (2MB)
            if (file.size > 2048000) {
                Swal.fire({
                    icon: 'error',
                    title: 'File Too Large',
                    text: 'Image size should not exceed 2MB',
                    confirmButtonText: 'OK'
                });
                e.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(event) {
                const logoThumbImg = document.getElementById('logoThumbImg');
                const logoPlaceholderIcon = document.getElementById('logoPlaceholderIcon');
                const removeLogoBtn = document.getElementById('removeLogo');
                const removeLogoInput = document.getElementById('removeLogo_input');
                
                // Reset remove flag since user is uploading a new logo
                if (removeLogoInput) {
                    removeLogoInput.value = '0';
                }
                
                // Update thumbnail
                logoThumbImg.src = event.target.result;
                logoThumbImg.style.display = 'block';
                
                // Hide placeholder icon
                if (logoPlaceholderIcon) {
                    logoPlaceholderIcon.style.display = 'none';
                }
                
                // Show remove button
                if (removeLogoBtn) {
                    removeLogoBtn.style.display = 'inline-block';
                }
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Remove Logo Handler
    document.getElementById('removeLogo').addEventListener('click', function() {
        Swal.fire({
            title: 'Remove Logo?',
            text: 'Are you sure you want to remove the system logo?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, remove it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                const logoInput = document.getElementById('logo');
                const logoThumbImg = document.getElementById('logoThumbImg');
                const logoPlaceholderIcon = document.getElementById('logoPlaceholderIcon');
                const removeLogoBtn = document.getElementById('removeLogo');
                const removeLogoInput = document.getElementById('removeLogo_input');
                
                // Set hidden input to indicate logo should be removed
                if (removeLogoInput) {
                    removeLogoInput.value = '1';
                }
                
                // Clear file input
                logoInput.value = '';
                
                // Reset thumbnail to placeholder
                logoThumbImg.src = '';
                logoThumbImg.style.display = 'none';
                
                // Show placeholder icon
                if (logoPlaceholderIcon) {
                    logoPlaceholderIcon.style.display = 'inline-block';
                }
                
                // Hide remove button
                removeLogoBtn.style.display = 'none';
                
                Swal.fire({
                    icon: 'success',
                    title: 'Removed!',
                    text: 'Logo has been removed. Save to apply changes.',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    });

    // System Configuration Form
    document.getElementById('systemConfigForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);

        // Show loading
        Swal.fire({
            title: 'Saving...',
            text: 'Updating System configuration',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('{{ route("admin.system.configurations.update") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // enabled submit button
            document.getElementById('systemConfigSubmit').disabled = false;
            if (data.success) {
                // Reset the remove_logo flag after successful save
                const removeLogoInput = document.getElementById('removeLogo_input');
                if (removeLogoInput) {
                    removeLogoInput.value = '0';
                }
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message || 'System configuration updated successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message || 'Failed to update System configuration',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('systemConfigSubmit').disabled = false;
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while saving',
                confirmButtonText: 'OK'
            });
        });
    });

    // Delete Backup Handler
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-backup-btn') || e.target.closest('.delete-backup-btn')) {
            const button = e.target.classList.contains('delete-backup-btn') ? e.target : e.target.closest('.delete-backup-btn');
            const filename = button.getAttribute('data-file');
            
            Swal.fire({
                title: 'Delete Backup?',
                text: 'Are you sure you want to delete this backup file? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Deleting...',
                        text: 'Please wait while we delete the backup file',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Delete backup
                    fetch('{{ route("admin.backup.delete") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            file: filename
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Prefer DataTables reload if available
                            if (window.backupsTable) {
                                window.backupsTable.ajax.reload(null, false);
                            } else {
                                // Fallback: remove the row directly
                                const row = button.closest('tr');
                                if (row) { row.remove(); }
                            }

                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: data.message || 'Backup file has been deleted successfully.',
                                timer: 1200,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to delete backup file',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while deleting the backup',
                            confirmButtonText: 'OK'
                        });
                    });
                }
            });
        }
    });
    // Initialize backups DataTable and filter actions
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined' && document.getElementById('backupsTable')) {
            const $table = $('#backupsTable');
            const listUrl = '{{ route("admin.backup.list") }}';

            window.backupsTable = $table.DataTable({
                responsive: true,
                processing: true,
                serverSide: false,
                searching: false,
                ajax: {
                    url: listUrl,
                    data: function(d) {
                        d.start_date = document.getElementById('backupStart')?.value || '';
                        d.end_date = document.getElementById('backupEnd')?.value || '';
                        d.size_min = document.getElementById('backupSizeMin')?.value || '';
                        d.size_max = document.getElementById('backupSizeMax')?.value || '';
                    },
                    dataSrc: function(json) { return json.data || []; }
                },
                columns: [
                    { data: 'name', render: function(data){
                        return `<i class="fa fa-file-archive me-2 text-primary"></i>${data}`;
                    }},
                    { data: 'size_human' },
                    { data: 'date' },
                    { data: null, orderable: false, searchable: false, className: 'text-center', render: function(data, type, row){
                        const downloadUrl = '{{ route("admin.backup.download") }}' + '?file=' + encodeURIComponent(row.name);
                        return `<a href="${downloadUrl}" class="btn btn-sm btn-success me-1" title="Download Backup"><i class="fa fa-download"></i></a>
                                <button type="button" class="btn btn-sm btn-danger delete-backup-btn" data-file="${row.name}" title="Delete Backup"><i class="fa fa-trash"></i></button>`;
                    }}
                ],
                order: [[2, 'desc']]
            });

            const apply = document.getElementById('applyBackupFilters');
            const reset = document.getElementById('resetBackupFilters');
            if (apply) apply.addEventListener('click', () => window.backupsTable.ajax.reload());
            if (reset) reset.addEventListener('click', () => {
                document.getElementById('backupStart').value = '{{ now()->subDays(30)->toDateString() }}';
                document.getElementById('backupEnd').value = '{{ now()->toDateString() }}';
                document.getElementById('backupSizeMin').value = '';
                document.getElementById('backupSizeMax').value = '';
                window.backupsTable.ajax.reload();
            });
        }
    });
</script>
@endpush
