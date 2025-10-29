@extends('admin.layouts.app')
@section('title', 'Pool Pricing Plans')

@push('styles')

<style>
    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }

    .invalid-feedback,
    .range-error {
        display: block !important;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }

    .required-field::before {
        content: "* ";
        color: #dc3545;
        font-weight: bold;
    }

    .form-validation-summary {
        background-color: #f8d7da;
        border: 1px solid #f5c6cb;
        color: #721c24;
        padding: 1rem;
        border-radius: 0.375rem;
        margin-bottom: 1rem;
    }

    .pricing-card {
        background-color: var(--secondary-color);
        /* box-shadow: rgba(167, 124, 252, 0.529) 0px 5px 10px 0px; */
        border-radius: 10px;
        padding: 30px;
        text-align: center;
        transition: 0.3s ease-in-out;
        min-height: 28rem;
    }

    a {
        text-decoration: none
    }
    
    .pricing-card:hover {
        /* box-shadow: 0px 5px 15px rgba(163, 163, 163, 0.15); */
        transform: translateY(-10px);
    }

    .popular {
        position: relative;
        /* background: var(--second-primary); */
        color: white;
    }

    .grey-btn {
        background-color: var(--secondary-color);
        color: #fff;
    }

    .popular::before {
        content: "Most Popular";
        position: absolute;
        top: -10px;
        left: 50%;
        transform: translateX(-50%);
        background: #ffcc00;
        color: #000;
        padding: 5px 10px;
        font-size: 14px;
        font-weight: bold;
        border-radius: 5px;
    }

    .feature-item {
        background-color: rgba(255, 255, 255, 0.1);
        padding: 10px;
        border-radius: 5px;
        margin-bottom: 10px;
        position: relative;
    }

    .remove-feature-btn {
        position: absolute;
        top: 17px;
        right: 14px;
        font-size: 8px;
        padding: 2px 5px;
        z-index: 1;
    }

    select option {
        color: #fff;
        background-color: var(--primary-color);
        border: none !important;
    }

    .new-feature-form {
        background-color: rgba(255, 255, 255, 0.05);
        padding: 15px;
        border-radius: 5px;
        margin-top: 10px;
        border: 1px dashed rgba(255, 255, 255, 0.2);
    }

    .features-container .feature-item {
        /* background-color: #f8f9fa; */
        border: 1px solid #9f9f9f83;
    }

    .selected-features-list {
        max-height: 200px;
        overflow-y: auto;
    }

    .feature-item strong {
        font-size: 13px
    }

    .plan-updated {
        animation: planUpdate 0.5s ease-in-out;
    }

    @keyframes planUpdate {
        0% {
            transform: scale(1);
            background-color: transparent;
        }

        50% {
            transform: scale(1.02);
            background-color: rgba(40, 167, 69, 0.1);
        }

        100% {
            transform: scale(1);
            background-color: transparent;
        }
    }

    #refresh-loading {
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
        }

        to {
            opacity: 1;
        }
    }

    @keyframes planUpdate {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(167, 124, 252, 0.3);
        }

        100% {
            transform: scale(1);
        }
    }

    .plans {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
    }

    .price {
        background-color: #000;
        border: 3px solid #fff;
        width: fit-content;
        height: 150px;
        width: 150px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        bottom: -55px;
    }

    /* .subscribe-btn, .btn {
                box-shadow: rgba(0, 0, 0, 0.4) 0px 2px 4px, rgba(0, 0, 0, 0.3) 0px 7px 13px -3px, rgba(0, 0, 0, 0.2) 0px -3px 0px inset;
            } */

    .success-message {
        animation: slideInRight 0.5s ease-out;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
        }
        to {
            transform: translateX(0);
        }
    }

    .modal-close-btn {
        position: absolute;
        top: 20px;
        right: 25px;
        width: 30px;
        height: 30px;
        background-color: var(--secondary-color);
        color: #fff;
        z-index: 999;
        transition: none;
    }

    .modal-close-btn:hover {
        background-color: var(--primary-color);
        transform: none;
    }

    .plan-actions {
        position: absolute;
        top: -25px;
        right: -20px;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .pricing-card:hover .plan-actions {
        opacity: 1;
    }

    .btn-action {
        width: 30px;
        height: 30px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: 5px;
        border-radius: 50%;
        border: none;
        font-size: 12px;
    }

    .btn-edit {
        background-color: rgba(255, 193, 7, 0.8);
        color: #000;
    }

    .btn-delete {
        background-color: rgba(220, 53, 69, 0.8);
        color: #fff;
    }

    .range-summary {
        font-weight: 600;
        color: var(--primary-color);
    }

    .number {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
    }

    .theme-text {
        color: var(--primary-color) !important;
    }

    .features-list li {
        opacity: 0.9;
    }

    .features-list li:hover {
        opacity: 1;
    }

    .features-container .feature-item:hover {
        /* background-color: #e9ecef; */
    }

    .volume-item {
        border: 1px solid #dee2e6 !important;
        /* background-color: #f8f9fa; */
    }

    @media (max-width: 1400px) {
        .pricing-card {
            padding: 40px 30px;
        }

        li {
            font-size: 12px !important
        }

        small {
            font-size: 10px !important
        }
    }

    @media (min-width: 1400px) {
        .plans {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
    }

    /* Section headers styling */
    .section-header {
        border-bottom: 2px solid var(--primary-color);
        padding-bottom: 10px;
        margin-bottom: 30px;
    }

    .section-header h3 {
        position: relative;
    }

    /* Discounted plans specific styling */
    /* .pricing-card[style*="border: 2px solid #28a745"] {
        position: relative;
        overflow: hidden;
    }

    .pricing-card[style*="border: 2px solid #28a745"]::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #28a745, #20c997);
        z-index: 1;
    } */

    /* Badge styling for discounted plans */
    .badge.bg-success {
        font-size: 11px;
        padding: 6px 10px;
        border-radius: 15px;
        z-index: 2;
    }

    /* Section divider */
    .section-divider {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin: 40px 0;
    }

    /* Bulk selection features */
    .bulk-select-mode .pricing-card {
        border: 2px solid transparent;
        transition: all 0.3s ease;
    }

    .bulk-select-mode .pricing-card.selected {
        border: 2px solid var(--primary-color);
        box-shadow: 0 0 20px rgba(167, 124, 252, 0.3);
    }

    .plan-checkbox {
        background-color: rgba(255, 255, 255, 0.9) !important;
        border: 2px solid var(--primary-color) !important;
    }

    .plan-checkbox:checked {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    .template-btn {
        height: 70px;
        font-size: 12px;
        transition: all 0.3s ease;
    }

    .template-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .template-btn.active {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
        color: white !important;
    }

    .bulk-actions-bar {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: none;
    }

    .bulk-actions-bar.show {
        display: block;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .quick-create-panel {
        background: rgba(255, 255, 255, 0.05);
        border: 1px dashed rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        display: none;
    }

    .quick-create-panel.show {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    /* ChargeBee sync status badges */
    .badge.bg-success {
        background-color: #28a745 !important;
    }

    .badge.bg-secondary {
        background-color: #6c757d !important;
    }

    .chargebee-sync-status {
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 12px;
        font-weight: 500;
    }

    .pricing-card .badge {
        font-size: 10px;
        padding: 4px 8px;
        font-weight: 500;
    }

    /* Pool Static Link specific styles */
    .generatePoolStaticLinkBtn {
        transition: all 0.3s ease;
    }

    .generatePoolStaticLinkBtn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .btn-action.btn-success {
        background-color: rgba(40, 167, 69, 0.8);
        color: #fff;
    }

    .btn-action.btn-success:hover {
        background-color: rgba(40, 167, 69, 1);
    }

    #poolStaticLinkModal .modal-content {
        border: none;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }

    #poolStaticLinkModal .modal-header {
        border-radius: 15px 15px 0 0;
        border-bottom: none;
    }

    #poolStaticLinkModal .input-group {
        border-radius: 8px;
        overflow: hidden;
    }

    #poolStaticLinkModal .form-control {
        font-family: 'Courier New', monospace;
        font-size: 12px;
        background-color: #f8f9fa;
    }

    .static-link-info {
        background: linear-gradient(135deg, rgba(0,123,255,0.1), rgba(40,167,69,0.1));
        border: 1px solid rgba(0,123,255,0.2);
        border-radius: 8px;
        padding: 15px;
        margin: 10px 0;
    }
</style>

@endpush

@section('content')
<section class="py-3">
    <!-- Pool Plans Section -->
    <div class="mt-5">
        <div class="text-center mb-4 section-header">
            <h3 class="text-white fw-bold">
                <i class="fa-solid fa-layer-group me-2 theme-text"></i>
                Pool Pricing Plans
            </h3>
            <p class="text-white">Manage your pool-based pricing plans for flexible inbox management</p>
        </div>
        
        <!-- Bulk Actions Section -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#createPoolPlanModal">
                        <i class="fa-solid fa-plus me-1"></i>Add New Plan
                    </button>
                    <button class="btn btn-info btn-sm" onclick="duplicateSelectedPlans()" id="duplicateBtn" style="display: none;">
                        <i class="fa-solid fa-copy me-1"></i>Duplicate Selected
                    </button>
                    <button class="btn btn-warning btn-sm d-none" onclick="toggleBulkMode()" id="bulkModeBtn">
                        <i class="fa-solid fa-check-square me-1"></i>Bulk Select
                    </button>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <span class="text-white-50 small" id="planCount">Total Plans: {{ count($poolPlans) }}</span>
            </div>
        </div>
        <div class="row" id="pool-plans-container">
        @foreach ($poolPlans as $poolPlan)
        <div class="col-sm-6 col-lg-4 mb-5" id="pool-plan-{{ $poolPlan->id }}">
            <div class="pricing-card card">
                <div class="position-relative">
                    <!-- Bulk Selection Checkbox -->
                    <div class="bulk-select-checkbox" style="display: none;">
                        <input type="checkbox" class="form-check-input plan-checkbox" 
                               data-plan-id="{{ $poolPlan->id }}" 
                               style="position: absolute; top: 10px; left: 10px; z-index: 10; transform: scale(1.2);">
                    </div>
                    
                    <!-- Plan Actions -->
                    <div class="plan-actions">
                        <button class="btn btn-edit btn-action" data-bs-toggle="modal" 
                                data-bs-target="#editPoolPlan{{ $poolPlan->id }}" title="Edit Pool Plan">
                            <i class="fa-solid fa-edit"></i>
                        </button>
                        <button class="btn btn-info btn-action d-none" onclick="duplicatePoolPlan({{ $poolPlan->id }})" title="Duplicate Pool Plan">
                            <i class="fa-solid fa-copy"></i>
                        </button>
                        @if($poolPlan->is_chargebee_synced && $poolPlan->chargebee_plan_id)
                        <button class="btn btn-success btn-action generatePoolStaticLinkBtn" 
                                data-pool-plan-id="{{ $poolPlan->id }}" 
                                data-chargebee-plan-id="{{ $poolPlan->chargebee_plan_id }}"
                                title="Generate Static Link">
                            <i class="fa-solid fa-link"></i>
                        </button>
                        @endif
                        <!-- <button class="btn btn-delete btn-action" onclick="deletePoolPlan({{ $poolPlan->id }})" title="Delete Pool Plan">
                            <i class="fa-solid fa-trash"></i>
                        </button> -->
                    </div>
                </div>

                <div class="inner-content d-flex flex-column justify-content-between">
                    <div>
                        <div class="text-center">
                            <h4 class="fw-semibold text-white plan-name text-capitalize fs-4">
                                {{ $poolPlan->name }}
                                @if($poolPlan->is_chargebee_synced)
                                    <span class="badge bg-success ms-2" title="Synced with ChargeBee">
                                        <i class="fa-solid fa-cloud-arrow-up"></i> CB
                                    </span>
                                @else
                                    <span class="badge bg-secondary ms-2" title="Not synced with ChargeBee">
                                        <i class="fa-solid fa-cloud-slash"></i> Local
                                    </span>
                                @endif
                            </h4>

                            <h2 class="fw-semibold text-white plan-name text-capitalize fs-4">
                                ${{ number_format($poolPlan->price, 2) }}
                                <span class="fw-light text-white pt-3 opacity-75" style="font-size: 13px">
                                    /@if($poolPlan->duration == 'monthly')
                                        mo
                                    @elseif($poolPlan->duration == 'weekly')
                                        week
                                    @elseif($poolPlan->duration == 'daily')
                                        day
                                    @else
                                        {{ $poolPlan->duration }}
                                    @endif
                                    per Inboxes
                                </span>
                            </h2>
                            
                            <!-- Display Pricing Model and Billing Cycle -->
                            <div class="mb-2">
                                <small class="text-white-50">
                                    <i class="fa-solid fa-tag"></i> 
                                    {{ ucfirst(str_replace('_', ' ', $poolPlan->pricing_model ?? 'per_unit')) }}
                                </small>
                                <small class="text-white-50 ms-2">
                                    <i class="fa-solid fa-repeat"></i> 
                                    Billing: {{ $poolPlan->billing_cycle ?? '1' }}{{ ($poolPlan->billing_cycle ?? '1') == 'unlimited' ? '' : ' cycle' . (($poolPlan->billing_cycle ?? '1') > 1 ? 's' : '') }}
                                </small>
                            </div>
                            
                            @if($poolPlan->is_chargebee_synced && $poolPlan->chargebee_plan_id)
                                <p class="text-white-50 small mb-2">
                                    <i class="fa-solid fa-id-card"></i> {{ $poolPlan->chargebee_plan_id }}
                                </p>
                            @endif
                            <ul class="list-unstyled features-list text-start">
                                @foreach ($poolPlan->features as $feature)
                                <li style="font-size: 14px" class="mb-2 d-flex align-items-center gap-2">
                                    <div>
                                        <img src="https://cdn.prod.website-files.com/68271f86a7dc3b457904455f/682b27d387eda87e2ecf8ba5_checklist%20(1).png"
                                            width="20" alt="">
                                    </div>
                                    {{ $feature->title }} {{ $feature->pivot->value }}
                                </li>
                                @endforeach
                            </ul>
                            
                            <!-- Static Link Button -->
                            @if($poolPlan->is_chargebee_synced && $poolPlan->chargebee_plan_id)
                            <div class="mt-3 text-center">
                                <button class="btn btn-outline-light btn-sm generatePoolStaticLinkBtn" 
                                        data-pool-plan-id="{{ $poolPlan->id }}" 
                                        data-chargebee-plan-id="{{ $poolPlan->chargebee_plan_id }}">
                                    <i class="fa-solid fa-link me-1"></i>Generate Static Link
                                </button>
                            </div>
                            @else
                            <div class="mt-3 text-center">
                                <small class="text-white-50">
                                    <i class="fa-solid fa-info-circle me-1"></i>Sync with ChargeBee to generate static links
                                </small>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Edit Modal for each pool plan -->
        <div class="modal fade" id="editPoolPlan{{ $poolPlan->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-body p-3 p-md-5 position-relative">
                        
                        <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                            data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                        <div class="text-center mb-4">
                            <h4>Edit Pool Plan</h4>
                        </div>
                        <form id="editPoolPlanForm{{ $poolPlan->id }}" class="edit-pool-plan-form" data-id="{{ $poolPlan->id }}">
                            @csrf
                            @method('PUT')
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <label for="name{{ $poolPlan->id }}" class="required-field">Pool Plan Name:</label>
                                    <input type="text" class="form-control mb-3" id="name{{ $poolPlan->id }}"
                                        name="name" value="{{ $poolPlan->name }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="duration{{ $poolPlan->id }}" class="required-field">Duration:</label>
                                    <select class="form-control mb-3" id="duration{{ $poolPlan->id }}"
                                        disabled
                                        name="duration" required>
                                        <option value="monthly" {{ $poolPlan->duration == 'monthly' ? 'selected' : '' }}>Monthly</option>
                                        <option value="weekly" {{ $poolPlan->duration == 'weekly' ? 'selected' : '' }}>Weekly</option>
                                        <option value="daily" {{ $poolPlan->duration == 'daily' ? 'selected' : '' }}>Daily</option>
                                        <option value="yearly" {{ $poolPlan->duration == 'yearly' ? 'selected' : '' }}>Yearly</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="pricing_model{{ $poolPlan->id }}" class="required-field">Pricing Model:</label>
                                    <select class="form-control mb-3" id="pricing_model{{ $poolPlan->id }}"
                                        name="pricing_model" required disabled>
                                        <option value="per_unit" {{ ($poolPlan->pricing_model ?? 'per_unit') == 'per_unit' ? 'selected' : '' }}>Per Unit</option>
                                        <option value="flat_fee" {{ ($poolPlan->pricing_model ?? 'per_unit') == 'flat_fee' ? 'selected' : '' }}>Flat Fee</option>
                                    </select>
                                    <small class="text-muted">
                                        <i class="fa-solid fa-lock"></i> Pricing model cannot be changed after creation
                                    </small>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <label for="price{{ $poolPlan->id }}" class="required-field">Price ($):</label>
                                    <input type="number" class="form-control mb-3" id="price{{ $poolPlan->id }}"
                                        name="price" value="{{ $poolPlan->price }}" min="0" step="0.01" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="billing_cycle{{ $poolPlan->id }}" class="required-field">Billing Cycle:</label>
                                    <select class="form-control mb-3" id="billing_cycle{{ $poolPlan->id }}"
                                        name="billing_cycle" required disabled>
                                        <option value="1" {{ (string)($poolPlan->billing_cycle ?? '1') === '1' ? 'selected' : '' }}>1</option>
                                        <option value="2" {{ (string)($poolPlan->billing_cycle ?? '1') === '2' ? 'selected' : '' }}>2</option>
                                        <option value="3" {{ (string)($poolPlan->billing_cycle ?? '1') === '3' ? 'selected' : '' }}>3</option>
                                        <option value="4" {{ (string)($poolPlan->billing_cycle ?? '1') === '4' ? 'selected' : '' }}>4</option>
                                        <option value="5" {{ (string)($poolPlan->billing_cycle ?? '1') === '5' ? 'selected' : '' }}>5</option>
                                        <option value="6" {{ (string)($poolPlan->billing_cycle ?? '1') === '6' ? 'selected' : '' }}>6</option>
                                        <option value="7" {{ (string)($poolPlan->billing_cycle ?? '1') === '7' ? 'selected' : '' }}>7</option>
                                        <option value="8" {{ (string)($poolPlan->billing_cycle ?? '1') === '8' ? 'selected' : '' }}>8</option>
                                        <option value="9" {{ (string)($poolPlan->billing_cycle ?? '1') === '9' ? 'selected' : '' }}>9</option>
                                        <option value="10" {{ (string)($poolPlan->billing_cycle ?? '1') === '10' ? 'selected' : '' }}>10</option>
                                        <option value="unlimited" {{ (string)($poolPlan->billing_cycle ?? '1') === 'unlimited' ? 'selected' : '' }}>Unlimited</option>
                                    </select>
                                    <small class="text-muted">
                                        <i class="fa-solid fa-lock"></i> Billing cycle cannot be changed after creation
                                    </small>
                                </div>
                            </div>



                            <label for="description{{ $poolPlan->id }}" class="required-field">Description:</label>
                            <textarea class="form-control mb-3" id="description{{ $poolPlan->id }}" name="description"
                                rows="3" required>{{ $poolPlan->description }}</textarea>

                            <!-- Features Section -->
                            <h5 class="mt-4">Features</h5>
                            <div class="selected-features-container" id="selectedFeatures{{ $poolPlan->id }}">
                                @foreach ($poolPlan->features as $feature)
                                <div class="feature-item" data-feature-id="{{ $feature->id }}">
                                    <button type="button" class="btn btn-sm btn-danger remove-feature-btn">
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                    <div class="row">
                                        <div class="col-md-5">
                                            <strong>{{ $feature->title }}</strong>
                                            <input type="hidden" name="feature_ids[]" value="{{ $feature->id }}">
                                        </div>
                                        <div class="col-md-7">
                                            <input type="text" class="form-control form-control-sm feature-value-input"
                                                name="feature_values[]" value="{{ $feature->pivot->value }}"
                                                placeholder="Value">
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                            <div class="row mt-3 gy-3">
                                <div class="col-md-7">
                                    <select class="form-control feature-dropdown" id="featureDropdown{{ $poolPlan->id }}" data-plan-id="{{ $poolPlan->id }}">
                                        <option value="">Select an existing feature</option>
                                        <!-- Will be populated via AJAX -->
                                    </select>
                                </div>
                                <div class="col-md-5" style="text-align: right;">
                                    <button type="button" class="btn btn-primary toggle-new-feature-form"
                                        data-plan-id="{{ $poolPlan->id }}">
                                        <i class="fa-solid fa-plus"></i> New Feature
                                    </button>
                                </div>
                            </div>
                                
                            <div class="new-feature-form mt-3" id="newFeatureForm{{ $poolPlan->id }}"
                                style="display: none;">
                                <h6>New Feature</h6>
                                <div class="row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control mb-2 new-feature-title"
                                            placeholder="Feature Title">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control mb-2 new-feature-value"
                                            placeholder="Feature Value">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary add-new-feature-btn"
                                            data-plan-id="{{ $poolPlan->id }}">
                                            <i class="fa-solid fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary">Update Pool Plan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
        </div>
    </div>

    <!-- Add New Pool Plan Button -->
    <div class="text-center mt-5">
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#createPoolPlanModal">
                <i class="fa-solid fa-plus me-2"></i>Create New Pool Plan
            </button>
            <button class="btn btn-secondary btn-lg" onclick="showQuickTemplates()">
                <i class="fa-solid fa-magic me-2"></i>Quick Templates
            </button>
        </div>
    </div>

    <!-- Create Pool Plan Modal -->
    <div class="modal fade" id="createPoolPlanModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body p-3 p-md-5 position-relative">
                    <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                        data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    <div class="text-center mb-4">
                        <h4>Create New Pool Plan</h4>
                        <p class="text-muted small">Create individual plans or use templates for multiple plans</p>
                    </div>
                    
                    <!-- Template Selection -->
                    <div class="mb-4 d-none">
                        <label class="form-label">Quick Start Options:</label>
                        <div class="row">
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 template-btn" onclick="loadTemplate('basic')">
                                    <i class="fa-solid fa-star"></i><br>Basic Plan
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 template-btn" onclick="loadTemplate('premium')">
                                    <i class="fa-solid fa-crown"></i><br>Premium Plan
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-primary btn-sm w-100 template-btn" onclick="loadTemplate('enterprise')">
                                    <i class="fa-solid fa-building"></i><br>Enterprise Plan
                                </button>
                            </div>
                        </div>
                        <hr class="my-3">
                    </div>
                    
                    <form id="createPoolPlanForm">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-12">
                                <label for="name" class="required-field">Pool Plan Name:</label>
                                <input type="text" class="form-control mb-3" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="duration" class="required-field">Duration:</label>
                                <select class="form-control mb-3" id="duration" name="duration" required>
                                    <option value="">Select Duration</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="daily">Daily</option>
                                    <!-- <option value="yearly">Yearly</option> -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="pricing_model" class="required-field">Pricing Model:</label>
                                <select class="form-control mb-3" id="pricing_model" name="pricing_model" required>
                                    <option value="">Select Pricing Model</option>
                                    <option value="per_unit">Per Unit</option>
                                    <option value="flat_fee">Flat Fee</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <label for="price" class="required-field">Price ($):</label>
                                <input type="number" class="form-control mb-3" id="price" name="price" 
                                       min="0" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label for="billing_cycle" class="required-field">Billing Cycle:</label>
                                <select class="form-control mb-3" id="billing_cycle" name="billing_cycle" required>
                                    <option value="">Select Billing Cycle</option>
                                    <option value="1">1</option>
                                    <option value="2">2</option>
                                    <option value="3">3</option>
                                    <option value="4">4</option>
                                    <option value="5">5</option>
                                    <option value="6">6</option>
                                    <option value="7">7</option>
                                    <option value="8">8</option>
                                    <option value="9">9</option>
                                    <option value="10">10</option>
                                    <option value="unlimited">Unlimited</option>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" id="currency_code" name="currency_code" value="USD">

                        <label for="description" class="required-field">Description:</label>
                        <textarea class="form-control mb-3" id="description" name="description" rows="3" required></textarea>

                        <!-- Features Section -->
                        <h5 class="mt-4">Features</h5>
                        <div class="selected-features-container" id="selectedFeaturesCreate">
                            <!-- Selected features will appear here -->
                        </div>

                        <div class="row mt-3 gy-3">
                            <div class="col-md-7">
                                <select class="form-control feature-dropdown" id="featureDropdownCreate" data-plan-id="Create">
                                    <option value="">Select an existing feature</option>
                                    <!-- Will be populated via AJAX -->
                                </select>
                            </div>
                            <div class="col-md-5" style="text-align: right;">
                                <button type="button" class="btn btn-primary toggle-new-feature-form"
                                    data-plan-id="Create">
                                    <i class="fa-solid fa-plus"></i> New Feature
                                </button>
                            </div>
                        </div>

                        <div class="new-feature-form mt-3" id="newFeatureFormCreate" style="display: none;">
                            <h6>New Feature</h6>
                            <div class="row">
                                <div class="col-md-5">
                                    <input type="text" class="form-control mb-2 new-feature-title"
                                        placeholder="Feature Title">
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control mb-2 new-feature-value"
                                        placeholder="Feature Value">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-primary add-new-feature-btn"
                                        data-plan-id="Create">
                                        <i class="fa-solid fa-plus"></i> Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-primary">Create Pool Plan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@push('scripts')
<script>
// Global variables for bulk operations
let bulkMode = false;
let selectedPlans = [];
let planTemplates = {
    basic: {
        name: 'Basic Pool Plan',
        price: 5.00,
        duration: 'monthly',
        pricing_model: 'per_unit',
        billing_cycle: '1',
        currency_code: 'USD',
        description: 'Perfect for small teams and individual users',
        features: []
    },
    premium: {
        name: 'Premium Pool Plan',
        price: 7.00,
        duration: 'weekly',
        pricing_model: 'per_unit',
        billing_cycle: '3',
        currency_code: 'USD',
        description: 'Ideal for growing businesses with advanced features',
        features: []
    },
    enterprise: {
        name: 'Enterprise Pool Plan',
        price: 10.00,
        duration: 'daily',
        pricing_model: 'flat_fee',
        billing_cycle: 'unlimited',
        currency_code: 'USD',
        description: 'Comprehensive solution for large organizations',
        features: []
    }
};

$(document).ready(function() {
    // Load features on page load
    loadFeatures();
    
    // Initialize bulk selection handlers
    initializeBulkSelection();

    // Create Pool Plan Form Submission
    $('#createPoolPlanForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Feature data is already included in the form via hidden inputs and value inputs

        // Show loading
        Swal.fire({
            title: 'Creating Pool Plan...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading()
            }
        });

        $.ajax({
            url: "{{ route('admin.pool-plans.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Pool plan created successfully and synced with ChargeBee!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Something went wrong'
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response?.message || 'Something went wrong'
                });
            }
        });
    });

    // Edit Pool Plan Form Submission
    $('.edit-pool-plan-form').on('submit', function(e) {
        e.preventDefault();
        
        const poolPlanId = $(this).data('id');
        const formData = new FormData(this);
        
        // Feature data is already included in the form via hidden inputs and value inputs

        // Show loading
        Swal.fire({
            title: 'Updating Pool Plan...',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading()
            }
        });

        $.ajax({
            url: `{{ url('admin/pool-plans') }}/${poolPlanId}`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Pool plan updated successfully and synced with ChargeBee!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Something went wrong'
                    });
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response?.message || 'Something went wrong'
                });
            }
        });
    });


});

// Load features from database
function loadFeatures() {
    $.ajax({
        url: "{{ route('admin.features.list') }}",
        type: "GET",
        dataType: "json",
        success: function(response) {
            if (response.success) {
                // Populate all feature dropdowns
                $('.feature-dropdown').each(function() {
                    const dropdown = $(this);
                    let poolPlanId = dropdown.attr('id').replace('featureDropdown', '');
                    
                    // Handle the special case for create dropdown
                    if (dropdown.attr('id') === 'featureDropdownCreate') {
                        poolPlanId = 'Create';
                    }
                    
                    let options = '<option value="">Select an existing feature</option>';
                    
                    // Get all currently added feature IDs for this pool plan
                    const addedFeatureIds = [];
                    const container = poolPlanId === 'Create' ? $('#selectedFeaturesCreate') : $(`#selectedFeatures${poolPlanId}`);
                    container.find('.feature-item').each(function() {
                        const featureId = $(this).data('feature-id');
                        if (featureId) {
                            addedFeatureIds.push(featureId.toString());
                        }
                    });
                    
                    // Filter out features that are already added to this pool plan
                    $.each(response.features, function(index, feature) {
                        if (!addedFeatureIds.includes(feature.id.toString())) {
                            options += `<option value="${feature.id}" data-title="${feature.title}">${feature.title}</option>`;
                        }
                    });
                    
                    dropdown.html(options);
                });
            }
        },
        error: function(xhr) {
            console.log("Failed to load features");
        }
    });
}

// Auto-add feature when selected from dropdown
$(document).on('change', '.feature-dropdown', function() {
    const dropdown = $(this);
    const poolPlanId = dropdown.data('plan-id');
    const selectedFeatureId = dropdown.val();
    const selectedFeatureTitle = dropdown.find('option:selected').data('title');
    
    if (!selectedFeatureId) {
        return; // No feature selected or cleared selection
    }
    
    // Check if feature already exists
    const container = poolPlanId === 'Create' ? $('#selectedFeaturesCreate') : $(`#selectedFeatures${poolPlanId}`);
    const existingFeature = container.find(`[data-feature-id="${selectedFeatureId}"]`);
    if (existingFeature.length > 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Duplicate Feature',
            text: 'This feature is already added to this plan'
        });
        dropdown.val(''); // Clear selection
        return;
    }
    
    // Add feature to the container
    const featureHtml = `
        <div class="feature-item" data-feature-id="${selectedFeatureId}">
            <button type="button" class="btn btn-sm btn-danger remove-feature-btn">
                <i class="fa-solid fa-times"></i>
            </button>
            <div class="row">
                <div class="col-md-5">
                    <strong>${selectedFeatureTitle}</strong>
                    <input type="hidden" name="feature_ids[]" value="${selectedFeatureId}">
                </div>
                <div class="col-md-7">
                    <input type="text" class="form-control form-control-sm feature-value-input"
                        name="feature_values[]" value="" placeholder="Value">
                </div>
            </div>
        </div>
    `;
    
    container.append(featureHtml);
    
    // Clear dropdown selection
    dropdown.val('');
    
    // Reload features to update dropdown options
    loadFeatures();
});



// Remove feature button handler
$(document).on('click', '.remove-feature-btn', function() {
    $(this).closest('.feature-item').remove();
    // Reload features to update dropdown options
    loadFeatures();
});



// Toggle new feature form visibility
$(document).on('click', '.toggle-new-feature-form', function() {
    const poolPlanId = $(this).data('plan-id');
    const formContainer = $(`#newFeatureForm${poolPlanId}`);
    
    // Toggle form visibility
    formContainer.slideToggle(300);
});

// Add new feature (create and add to pool plan)
$(document).on('click', '.add-new-feature-btn', function() {
    const poolPlanId = $(this).data('plan-id');
    const formContainer = $(`#newFeatureForm${poolPlanId}`);
    const titleInput = formContainer.find('.new-feature-title');
    const valueInput = formContainer.find('.new-feature-value');
    
    const title = titleInput.val().trim();
    const value = valueInput.val().trim();
    
    if (!title) {
        Swal.fire({
            icon: 'warning',
            title: 'Missing Information',
            text: 'Please enter a feature title'
        });
        return;
    }
    
    // Create new feature via AJAX
    $.ajax({
        url: "{{ route('admin.features.store') }}",
        type: "POST",
        data: {
            _token: "{{ csrf_token() }}",
            title: title,
            value: '', // Set empty value for new features
            is_active: true // Set feature as active by default
        },
        dataType: "json",
        beforeSend: function() {
            // Show loading
            Swal.fire({
                title: 'Creating Feature...',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading()
                }
            });
        },
        success: function(response) {
            if (response.success) {
                // Add the new feature to the container
                const container = poolPlanId === 'Create' ? $('#selectedFeaturesCreate') : $(`#selectedFeatures${poolPlanId}`);
                const featureHtml = `
                    <div class="feature-item" data-feature-id="${response.feature.id}">
                        <button type="button" class="btn btn-sm btn-danger remove-feature-btn">
                            <i class="fa-solid fa-times"></i>
                        </button>
                        <div class="row">
                            <div class="col-md-5">
                                <strong>${response.feature.title}</strong>
                                <input type="hidden" name="feature_ids[]" value="${response.feature.id}">
                            </div>
                            <div class="col-md-7">
                                <input type="text" class="form-control form-control-sm feature-value-input"
                                    name="feature_values[]" value="${value}" placeholder="Value">
                            </div>
                        </div>
                    </div>
                `;
                container.append(featureHtml);
                
                // Clear inputs
                titleInput.val('');
                valueInput.val('');
                
                // Hide form
                formContainer.slideUp(300);
                
                // Reload features dropdown
                loadFeatures();
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'New feature created and added successfully!',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message || 'Unknown error occurred'
                });
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: response?.message || 'Something went wrong'
            });
        }
    });
});

// Delete pool plan
function deletePoolPlan(poolPlanId) {
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
            // Show loading
            Swal.fire({
                title: 'Deleting...',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading()
                }
            });
            
            $.ajax({
                url: `{{ url('admin/pool-plans') }}/${poolPlanId}`,
                method: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Pool plan has been deleted.',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        $(`#pool-plan-${poolPlanId}`).fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: response.message || 'Something went wrong'
                        });
                    }
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response?.message || 'Something went wrong'
                    });
                }
            });
        }
    });
}

// Show success toast message
function showSuccessToast(message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
    
    Toast.fire({
        icon: 'success',
        title: message
    });
}

// Show error toast message  
function showErrorToast(message) {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
    
    Toast.fire({
        icon: 'error',
        title: message
    });
}

// Initialize bulk selection functionality
function initializeBulkSelection() {
    // Handle individual checkbox changes
    $(document).on('change', '.plan-checkbox', function() {
        const planId = $(this).data('plan-id');
        const card = $(`#pool-plan-${planId} .pricing-card`);
        
        if ($(this).is(':checked')) {
            selectedPlans.push(planId);
            card.addClass('selected');
        } else {
            selectedPlans = selectedPlans.filter(id => id !== planId);
            card.removeClass('selected');
        }
        
        updateBulkActions();
    });
}

// Toggle bulk selection mode
function toggleBulkMode() {
    bulkMode = !bulkMode;
    const bulkBtn = $('#bulkModeBtn');
    const duplicateBtn = $('#duplicateBtn');
    const checkboxes = $('.bulk-select-checkbox');
    const container = $('#pool-plans-container');
    
    if (bulkMode) {
        // Enable bulk mode
        bulkBtn.html('<i class="fa-solid fa-times me-1"></i>Exit Bulk Mode').removeClass('btn-warning').addClass('btn-danger');
        checkboxes.show();
        container.addClass('bulk-select-mode');
    } else {
        // Disable bulk mode
        bulkBtn.html('<i class="fa-solid fa-check-square me-1"></i>Bulk Select').removeClass('btn-danger').addClass('btn-warning');
        checkboxes.hide();
        container.removeClass('bulk-select-mode');
        duplicateBtn.hide();
        
        // Clear selections
        $('.plan-checkbox').prop('checked', false);
        $('.pricing-card').removeClass('selected');
        selectedPlans = [];
    }
}

// Update bulk actions visibility
function updateBulkActions() {
    const duplicateBtn = $('#duplicateBtn');
    
    if (selectedPlans.length > 0) {
        duplicateBtn.show().html(`<i class="fa-solid fa-copy me-1"></i>Duplicate (${selectedPlans.length})`);
    } else {
        duplicateBtn.hide();
    }
}

// Duplicate single pool plan
function duplicatePoolPlan(planId) {
    Swal.fire({
        title: 'Duplicate Pool Plan',
        text: 'This will create a copy of the selected plan. Continue?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, duplicate it!'
    }).then((result) => {
        if (result.isConfirmed) {
            performDuplication([planId]);
        }
    });
}

// Duplicate selected plans
function duplicateSelectedPlans() {
    if (selectedPlans.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Plans Selected',
            text: 'Please select at least one plan to duplicate.'
        });
        return;
    }
    
    Swal.fire({
        title: 'Duplicate Selected Plans',
        text: `This will create copies of ${selectedPlans.length} selected plan(s). Continue?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, duplicate them!'
    }).then((result) => {
        if (result.isConfirmed) {
            performDuplication(selectedPlans);
        }
    });
}

// Perform the duplication
function performDuplication(planIds) {
    Swal.fire({
        title: 'Duplicating Plans...',
        text: 'Please wait while we create copies of your plans.',
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading()
        }
    });
    
    // Make AJAX call to duplicate plans
    $.ajax({
        url: "{{ route('admin.pool-plans.duplicate') }}",
        method: 'POST',
        data: {
            _token: "{{ csrf_token() }}",
            plan_ids: planIds
        },
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: `Successfully duplicated ${planIds.length} plan(s)!`,
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.message || 'Failed to duplicate plans'
                });
            }
        },
        error: function(xhr) {
            const response = xhr.responseJSON;
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: response?.message || 'Something went wrong during duplication'
            });
        }
    });
}

// Load template data into create form
function loadTemplate(templateType) {
    const template = planTemplates[templateType];
    if (!template) return;
    
    // Clear active template buttons
    $('.template-btn').removeClass('active');
    
    // Mark current template as active
    $(`.template-btn[onclick="loadTemplate('${templateType}')"]`).addClass('active');
    
    // Fill form with template data
    $('#name').val(template.name);
    $('#price').val(template.price);
    $('#duration').val(template.duration);
    $('#pricing_model').val(template.pricing_model);
    $('#billing_cycle').val(template.billing_cycle);
    $('#currency_code').val(template.currency_code);
    $('#description').val(template.description);
    
    // Clear existing features in create form
    $('#selectedFeaturesCreate').empty();
    
    // Show success feedback
    showSuccessToast(`${template.name} template loaded successfully!`);
}

// Show quick templates modal
function showQuickTemplates() {
    Swal.fire({
        title: 'Quick Plan Templates',
        html: `
            <div class="text-start">
                <p class="text-muted">Choose a template to quickly create multiple plans:</p>
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">Create Multiple Plans at Once</h6>
                                <p class="card-text small">Select templates and we'll create multiple plans for you:</p>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="basic" id="templateBasic">
                                    <label class="form-check-label" for="templateBasic">Basic Plan ($5.00/month)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="premium" id="templatePremium">
                                    <label class="form-check-label" for="templatePremium">Premium Plan ($7.00/month)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="enterprise" id="templateEnterprise">
                                    <label class="form-check-label" for="templateEnterprise">Enterprise Plan ($10.00/month)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Create Selected Plans',
        cancelButtonText: 'Cancel',
        width: '500px'
    }).then((result) => {
        if (result.isConfirmed) {
            createMultiplePlansFromTemplates();
        }
    });
}

// Create multiple plans from selected templates
function createMultiplePlansFromTemplates() {
    const selectedTemplates = [];
    
    // Get selected templates
    $('input[type="checkbox"][id^="template"]:checked').each(function() {
        selectedTemplates.push($(this).val());
    });
    
    if (selectedTemplates.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'No Templates Selected',
            text: 'Please select at least one template.'
        });
        return;
    }
    
    // Show loading
    Swal.fire({
        title: 'Creating Multiple Plans...',
        text: `Creating ${selectedTemplates.length} plan(s) from templates...`,
        allowOutsideClick: false,
        showConfirmButton: false,
        willOpen: () => {
            Swal.showLoading()
        }
    });
    
    // Create plans sequentially
    let createdCount = 0;
    let errors = [];
    
    function createNextPlan(index) {
        if (index >= selectedTemplates.length) {
            // All done
            if (errors.length === 0) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: `Successfully created ${createdCount} plan(s)!`,
                    showConfirmButton: false,
                    timer: 2000
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'Partially Successful',
                    text: `Created ${createdCount} plan(s). ${errors.length} failed.`,
                    showConfirmButton: true
                }).then(() => {
                    location.reload();
                });
            }
            return;
        }
        
        const templateType = selectedTemplates[index];
        const template = planTemplates[templateType];
        
        const formData = new FormData();
        formData.append('_token', "{{ csrf_token() }}");
        formData.append('name', template.name);
        formData.append('price', template.price);
        formData.append('duration', template.duration);
        formData.append('currency_code', template.currency_code);
        formData.append('description', template.description);
        
        $.ajax({
            url: "{{ route('admin.pool-plans.store') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    createdCount++;
                } else {
                    errors.push(`${template.name}: ${response.message}`);
                }
                createNextPlan(index + 1);
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                errors.push(`${template.name}: ${response?.message || 'Unknown error'}`);
                createNextPlan(index + 1);
            }
        });
    }
    
    // Start creating plans
    createNextPlan(0);
}

// Handle Generate Pool Static Link button click
$(document).on('click', '.generatePoolStaticLinkBtn', function() {
    const $btn = $(this);
    const poolPlanId = $btn.data('pool-plan-id');
    const chargebeePlanId = $btn.data('chargebee-plan-id');
    
    if (!chargebeePlanId) {
        showErrorToast('ChargeBee Plan ID is required to generate static link. Please sync the plan with ChargeBee first.');
        return;
    }
    
    // Disable button while processing
    $btn.prop('disabled', true);
    const originalText = $btn.html();
    $btn.html('<i class="fa-solid fa-spinner fa-spin"></i> Generating...');
    
    $.ajax({
        url: '{{ route("admin.pool-plans.generate-static-link") }}',
        method: 'POST',
        data: {
            pool_plan_id: poolPlanId,
            chargebee_plan_id: chargebeePlanId,
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                showPoolStaticLinkModal(response.link, poolPlanId, chargebeePlanId);
            } else {
                showErrorToast(response.message || 'Failed to generate static link');
            }
        },
        error: function(xhr) {
            let errorMessage = 'Failed to generate static link';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            showErrorToast(errorMessage);
        },
        complete: function() {
            $btn.prop('disabled', false);
            $btn.html(originalText);
        }
    });
});

// Function to show the generated pool static link in a modal
function showPoolStaticLinkModal(link, poolPlanId, chargebeePlanId) {
    const modalHtml = `
        <div class="modal fade" id="poolStaticLinkModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header text-white">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-link me-2"></i>Generated Pool Static Link
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <p><strong>Pool Plan ID:</strong> <span class="badge bg-info">${poolPlanId}</span></p>
                                <p><strong>ChargeBee Plan ID:</strong> <span class="badge bg-success">${chargebeePlanId}</span></p>
                            </div>
                        </div>
                        
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="poolStaticLinkInput" value="${link}" readonly>
                            <button class="btn btn-outline-secondary copy-pool-static-link-btn" type="button">
                                <i class="fa-solid fa-copy"></i> Copy Link
                            </button>
                        </div>

                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fa-solid fa-clock me-1"></i>Generated on: ${new Date().toLocaleString()}
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fa-solid fa-times me-1"></i>Close
                        </button>
                        <button type="button" class="btn btn-primary copy-pool-static-link-btn">
                            <i class="fa-solid fa-copy me-1"></i>Copy Link
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#poolStaticLinkModal').remove();
    $('body').append(modalHtml);
    $('#poolStaticLinkModal').modal('show');
}

// Handle copy pool static link button click using event delegation
$(document).on('click', '.copy-pool-static-link-btn', function() {
    const linkInput = document.getElementById('poolStaticLinkInput');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        showSuccessToast('Pool static link copied to clipboard!');
        
        // Update button text temporarily
        const $btn = $(this);
        const originalText = $btn.html();
        $btn.html('<i class="fa-solid fa-check me-1"></i>Copied!');
        setTimeout(() => {
            $btn.html(originalText);
        }, 2000);
    } catch (err) {
        showErrorToast('Failed to copy link. Please copy manually.');
    }
});
</script>
@endpush

@endsection