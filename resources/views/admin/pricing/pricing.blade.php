@extends('admin.layouts.app')

@section('title', 'Pricing Plans')

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
        background: var(--second-primary);
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

    .features-container .feature-item:hover {
        /* background-color: #e9ecef; */
    }

    .volume-item {
        border: 1px solid #dee2e6 !important;
        /* background-color: #f8f9fa; */
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
</style>
@endpush

@section('content')
<section class="py-3">
    <!-- Master Plan Section -->
    <div class="col-12">
        <div class="card p-3">
            <div class="text-white mb-3">
                <h5 class="mb-0 theme-text">
                    <i class="fa-solid fa-crown me-2"></i>Plan Management
                </h5>
            </div>
            <div class="">
                <div id="masterPlanContainer">
                    <!-- Master plan content will be loaded here -->
                </div>
                @if (!auth()->user()->hasPermissionTo('Mod'))
                @if (auth()->user()->role_id != 5)
                <button id="createMasterPlan" class="btn btn-primary border-0 mt-3 btn-sm">
                    <i class="fa-solid fa-plus"></i> Create/Edit Plan
                </button>
                @endif
                @endif
                <!--   -->
            </div>
        </div>
    </div>

    <!-- <div class="d-flex flex-column align-items-center justify-content-center">
                    <h2 class="text-center fw-bold">Manage Plans</h2>
                    <p class="text-center">Create and manage subscription plans</p>
                    @if (!auth()->user()->hasPermissionTo('Mod'))
                    @if (auth()->user()->role_id != 5)
    <button id="addNew" data-bs-target="#addPlan" data-bs-toggle="modal" class="m-btn rounded-1 border-0 py-2 px-4">
                        <i class="fa-solid fa-plus"></i> Add New Plan
                    </button>
    @endif
                    @endif
                </div> -->

    <div class="row mt-5" id="plans-container">
        @foreach ($plans as $plan)
        <div class="col-sm-6 col-lg-4 mb-5" id="plan-{{ $plan->id }}">
            <div class="pricing-card card {{ $getMostlyUsed && $plan->id === $getMostlyUsed->id ? '' : '' }}">
                <div class="inner-content d-flex flex-column justify-content-between">
                    <div>
                        {{-- <div class="d-flex align-items-center justify-content-center mb-0">
                            <div class="plan-header">
                                <h6 class="fs-6 text-uppercase fw-bold">{{ $plan->name }}</h6>
                            </div>
                        </div> --}}
                        <div class="text-start">
                            <h4 class="fw-semibold text-white plan-name text-capitalize fs-4">
                                {{ $plan->name }}</h4>
                            <div class="mb-3 ">
                                <span>
                                    <span class="number">{{ $plan->min_inbox }}
                                        {{ $plan->max_inbox == 0 ? '+' : '- ' . $plan->max_inbox }}</span>
                                    Inboxes
                                </span>
                            </div>

                            {{-- <small class="plan-description text-capitalize opacity-75"
                                style="line-height: 1px !important">{{ $plan->description }}</small> --}}
                            <h2 class="fw-bold plan-price fs-1 theme-text mb-4 d-flex align-items-center gap-1 number">
                                ${{ number_format($plan->price, 2) }}
                                <span class="fw-light text-white pt-3 opacity-75" style="font-size: 13px">
                                    /{{ $plan->duration == 'monthly' ? 'mo' : $plan->duration }}
                                    per Inboxes
                                </span>
                            </h2>
                            <ul class="list-unstyled features-list text-start">
                                @foreach ($plan->features as $feature)
                                <li style="font-size: 14px" class="mb-2 d-flex align-items-center gap-2">
                                    <div>
                                        <img src="https://cdn.prod.website-files.com/68271f86a7dc3b457904455f/682b27d387eda87e2ecf8ba5_checklist%20(1).png"
                                            width="20" alt="">
                                    </div>
                                    {{ $feature->title }} {{ $feature->pivot->value }}
                                </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- <div class="text-center mt-4">
                            @php
                            $activeSubscription = auth()
                            ->user()
                            ->subscription()
                            ->where('plan_id', $plan->id)
                            ->where('status', 'active')
                            ->first();
                            @endphp
                            @if ($activeSubscription)
                            <button class="btn text-white subscribe-btn w-100" data-plan-id="{{ $plan->id }}"
                                style="background-color: rgb(5, 163, 23)">
                                <i class="fas fa-check me-2"></i>Subscribed Plan
                            </button>
                            @else
                            <button class="btn btn-primary subscribe-btn w-100" data-plan-id="{{ $plan->id }}">
                                Get Started Now
                            </button>
                            @endif
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal for each plan -->
        <div class="modal fade" id="editPlan{{ $plan->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-body p-3 p-md-5 position-relative">
                        <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                            data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                        <div class="text-center mb-4">
                            <h4>Edit Plan</h4>
                        </div>
                        <form id="editPlanForm{{ $plan->id }}" class="edit-plan-form" data-id="{{ $plan->id }}">
                            @csrf
                            <label style="display: none;" for="chargebee_plan_id{{ $plan->id }}">Chargebee Plan
                                ID:</label>
                            <input style="display: none;" type="text" class="form-control mb-3"
                                id="chargebee_plan_id{{ $plan->id }}" name="chargebee_plan_id"
                                value="{{ $plan->chargebee_plan_id }}">
                            <label style="display: none;" for="duration{{ $plan->id }}">Duration:</label>
                            <select style="display: none;" class="form-control mb-3" id="duration{{ $plan->id }}"
                                name="duration" required>
                                <option value="monthly" {{ $plan->duration === 'monthly' ? 'selected' : '' }}>
                                    Monthly
                                </option>
                                <option value="yearly" {{ $plan->duration === 'yearly' ? 'selected' : '' }}>Yearly
                                </option>
                            </select>
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="name{{ $plan->id }}">Plan Name:</label>
                                    <input type="text" class="form-control mb-3" id="name{{ $plan->id }}" name="name"
                                        value="{{ $plan->name }}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="price{{ $plan->id }}">Price Per Inboxes ($):</label>
                                    <input type="number" class="form-control mb-3" id="price{{ $plan->id }}"
                                        name="price" step="0.01" value="{{ $plan->price }}" required>
                                </div>
                                <div class="col-md-12">
                                    <label for="description{{ $plan->id }}">Descriptionx:</label>
                                    <textarea class="form-control mb-3" id="description{{ $plan->id }}"
                                        name="description" rows="2">{{ $plan->description }}</textarea>
                                </div>
                                <div class="col-md-12">
                                    <h5 class="mt-2">Inbox Limits</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="min_inbox{{ $plan->id }}">Min Inboxes:</label>
                                            <input type="number" class="form-control mb-3" id="min_inbox{{ $plan->id }}"
                                                name="min_inbox" value="{{ $plan->min_inbox }}" min="1" step="1"
                                                required>
                                            <small class="text-muted">Must be 1 or greater</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="max_inbox{{ $plan->id }}">Max Inboxes (0 for
                                                unlimited):</label>
                                            <input type="number" class="form-control mb-3" id="max_inbox{{ $plan->id }}"
                                                name="max_inbox" value="{{ $plan->max_inbox ?? 0 }}" min="0" step="1"
                                                required>
                                            <small class="text-muted">Use 0 for unlimited</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mt-4">Features</h5>
                            <div class="selected-features-container" id="selectedFeatures{{ $plan->id }}">
                                @foreach ($plan->features as $feature)
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
                                    <select class="form-control feature-dropdown" id="featureDropdown{{ $plan->id }}">
                                        <option value="">Select an existing feature</option>
                                        <!-- Will be populated via AJAX -->
                                    </select>
                                </div>
                                <div class="col-md-5" style="text-align: right;">
                                    <button type="button" class="btn btn-secondary add-selected-feature"
                                        data-plan-id="{{ $plan->id }}">
                                        <i class="fa-solid fa-plus"></i> Selected Feature
                                    </button>
                                    <button type="button" class="btn btn-primary toggle-new-feature-form"
                                        data-plan-id="{{ $plan->id }}">
                                        <i class="fa-solid fa-plus"></i> New
                                    </button>
                                </div>
                                {{-- <div class="col-md-4">

                                </div> --}}
                            </div>

                            {{-- <div class="mt-3">

                            </div> --}}

                            <div class="new-feature-form mt-3" id="newFeatureForm{{ $plan->id }}"
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
                                            data-plan-id="{{ $plan->id }}">
                                            <i class="fa-solid fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="m-btn py-2 px-4 rounded-2 w-100 update-plan-btn">Update
                                    Plan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Add New Plan Modal -->
    <div class="modal fade" id="addPlan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-body p-3 p-md-5 position-relative">
                    <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                        data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                    <div class="text-center mb-4">
                        <h4>Add New Plan</h4>
                    </div>
                    <form id="addPlanForm">
                        @csrf

                        <label for="chargebee_plan_id" style="display:none;">Chargebee Plan ID:</label>
                        <input style="display:none;" type="text" class="form-control mb-3" id="chargebee_plan_id"
                            name="chargebee_plan_id">
                        <label for="duration" style="display:none;">Duration:</label>
                        <select style="display:none;" class="form-control mb-3" id="duration" name="duration" required>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="name">Plan Name:</label>
                                <input type="text" class="form-control mb-3" id="name" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label for="price">Price Per Inboxes ($):</label>
                                <input type="number" class="form-control mb-3" id="price" name="price" step="0.01"
                                    required>
                            </div>
                            <div class="col-md-12">
                                <label for="description">Description:</label>
                                <textarea class="form-control mb-3" id="description" name="description"
                                    rows="2"></textarea>
                            </div>
                            <div class="col-md=6">
                                <h5 class="mt-2">Inbox Limits</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="min_inbox">Min Inboxes:</label>
                                        <input type="number" class="form-control mb-3" id="min_inbox" name="min_inbox"
                                            value="1" min="1" step="1" required>
                                        <small class="text-muted">Must be 1 or greater</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="max_inbox">Max Inboxes (0 for unlimited):</label>
                                        <input type="number" class="form-control mb-3" id="max_inbox" name="max_inbox"
                                            value="0" min="0" step="1" required>
                                        <small class="text-muted">Use 0 for unlimited</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-4">Features</h5>
                        <div class="selected-features-container" id="newPlanFeatures">
                            <!-- Selected features will be added dynamically -->
                        </div>

                        <div class="row gy-3 mt-3">
                            <div class="col-md-7">
                                <select class="form-control feature-dropdown" id="newPlanFeatureDropdown">
                                    <option value="">Select an existing feature</option>
                                    <!-- Will be populated via AJAX -->
                                </select>
                            </div>
                            <div class="col-md-5" style="text-align: right;">
                                <button type="button" class="btn btn-secondary add-selected-feature" data-plan-id="new">
                                    <i class="fa-solid fa-plus"></i> Selected Feature
                                </button>
                                <button type="button" class="btn btn-primary toggle-new-feature-form"
                                    data-plan-id="new">
                                    <i class="fa-solid fa-plus"></i> New
                                </button>
                            </div>
                        </div>

                        <div class="mt-3">

                        </div>

                        <div class="new-feature-form mt-3" style="display: none;">
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
                                        data-plan-id="new">
                                        <i class="fa-solid fa-plus"></i> Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="m-btn py-2 px-4 rounded-2 w-100">Create Plan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Toast notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 99999">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert"
        aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i> <span id="successMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="Close"></button>
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 99999">
    <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert"
        aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-exclamation-circle me-2"></i> <span id="errorMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                aria-label="Close"></button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
            // Reset form when Add New Plan modal opens
            $('#addPlan').on('show.bs.modal', function() {
                $('#addPlanForm').trigger('reset');
                $('#newPlanFeatures').empty();
                $('.new-feature-form').hide();
                $('.new-feature-title').val('');
                $('.new-feature-value').val('');
            });

            function loadFeatures() {
                $.ajax({
                    url: "{{ route('admin.features.list') }}",
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        console.log(response.features);
                        console.log(response);
                        console.log("features are loading here");

                        if (response.success) {
                            // Populate all feature dropdowns with filtering for each plan
                            $('.feature-dropdown').each(function() {
                                const dropdown = $(this);
                                let planId = dropdown.attr('id').replace('featureDropdown', '');

                                // Handle the special case for new plan dropdown
                                if (dropdown.attr('id') === 'newPlanFeatureDropdown') {
                                    planId = 'new';
                                }

                                const container = planId === 'new' ?
                                    $('#newPlanFeatures') :
                                    $(`#selectedFeatures${planId}`);

                                let options =
                                    '<option value="">Select an existing feature</option>';

                                // Get all currently added feature IDs for this plan
                                const addedFeatureIds = [];
                                container.find('.feature-item').each(function() {
                                    addedFeatureIds.push($(this).data('feature-id')
                                        .toString());
                                });

                                // Filter out features that are already added to this plan
                                $.each(response.features, function(index, feature) {
                                    if (!addedFeatureIds.includes(feature.id
                                            .toString())) {
                                        options +=
                                            `<option value="${feature.id}" data-title="${feature.title}">${feature.title}</option>`;
                                    }
                                });

                                dropdown.html(options);
                            });

                            // Also refresh volume item dropdowns
                            loadAllVolumeItemFeatures();
                        }
                    },
                    error: function(xhr) {
                        //showErrorToast('Failed to load features');
                        console.log("failed to load features ");
                    }
                });
            }


            // Real-time validation for plan form inputs
            $(document).on('input', 'input[name="price"], input[name="min_inbox"], input[name="max_inbox"]',
                function() {
                    const $input = $(this);
                    const value = $input.val();
                    const fieldName = $input.attr('name');

                    // Remove any existing error styling
                    $input.removeClass('is-invalid');
                    $input.siblings('.invalid-feedback').remove();

                    // Skip validation if field is empty (will be caught during form submission)
                    if (value === '') {
                        return;
                    }

                    // Validate based on field type
                    if (fieldName === 'price') {
                        const numValue = parseFloat(value);
                        if (isNaN(numValue) || numValue < 0) {
                            $input.addClass('is-invalid');
                            $input.after(
                                '<div class="invalid-feedback">Price must be a valid number (0 or greater)</div>'
                                );
                        }
                    } else if (fieldName === 'min_inbox') {
                        const numValue = parseInt(value);
                        if (isNaN(numValue) || numValue <= 0) {
                            $input.addClass('is-invalid');
                            $input.after(
                                '<div class="invalid-feedback">Min inboxes must be a whole number greater than 0</div>'
                                );
                        }
                    } else if (fieldName === 'max_inbox') {
                        const numValue = parseInt(value);
                        if (isNaN(numValue) || numValue < 0) {
                            $input.addClass('is-invalid');
                            $input.after(
                                '<div class="invalid-feedback">Max inboxes must be a whole number (0 for unlimited)</div>'
                                );
                        }
                    }

                    // Validate range for min/max inputs
                    validateInboxRange($input);
                });

            // Validate min/max inbox range relationship
            function validateInboxRange($changedInput) {
                const $form = $changedInput.closest('form');
                const minInput = $form.find('input[name="min_inbox"]');
                const maxInput = $form.find('input[name="max_inbox"]');

                const minVal = parseInt(minInput.val());
                const maxVal = parseInt(maxInput.val());

                // Clear any existing range validation errors
                minInput.siblings('.range-error').remove();
                maxInput.siblings('.range-error').remove();

                // Only validate if both fields have valid values
                if (!isNaN(minVal) && !isNaN(maxVal) && maxVal !== 0 && minVal > maxVal) {
                    const errorMsg =
                        '<div class="range-error text-danger small mt-1">Min inboxes cannot be greater than max inboxes</div>';
                    minInput.after(errorMsg);
                    maxInput.after(errorMsg);
                    minInput.addClass('is-invalid');
                    maxInput.addClass('is-invalid');
                }
            }

            // Enhanced form validation with visual feedback
            function validateFormCompleteness($form, formType = 'plan') {
                let errors = [];
                let hasEmptyFields = false;

                if (formType === 'masterplan') {
                    // Validate master plan basic info
                    const planName = $('#masterPlanExternalName').val().trim();
                    const description = $('#masterPlanDescription').val().trim();

                    if (!planName) {
                        errors.push('Plan name is required');
                        $('#masterPlanExternalName').addClass('is-invalid');
                        hasEmptyFields = true;
                    }

                    if (!description) {
                        errors.push('Plan description is required');
                        $('#masterPlanDescription').addClass('is-invalid');
                        hasEmptyFields = true;
                    }

                    // Validate volume tiers
                    const volumeItems = collectVolumeItems();
                    if (volumeItems.length === 0) {
                        errors.push('At least one volume tier is required');
                        hasEmptyFields = true;
                    } else {
                        volumeItems.forEach((item, index) => {
                            if (!item.name || !item.name.trim()) {
                                errors.push(`Tier ${index + 1}: Name is required`);
                                hasEmptyFields = true;
                            }
                            if (item.min_inbox === null || item.min_inbox === undefined || item.min_inbox <=
                                0) {
                                errors.push(
                                    `Tier ${index + 1}: Valid min inboxes is required (must be > 0)`);
                                hasEmptyFields = true;
                            }
                            if (item.max_inbox === null || item.max_inbox === undefined) {
                                errors.push(
                                    `Tier ${index + 1}: Max inboxes is required (use 0 for unlimited)`);
                                hasEmptyFields = true;
                            }
                            if (item.price === null || item.price === undefined || item.price < 0) {
                                errors.push(`Tier ${index + 1}: Valid price is required (must be ≥ 0)`);
                                hasEmptyFields = true;
                            }
                        });
                    }
                } else {
                    // Validate regular plan form
                    const requiredFields = {
                        'name': 'Plan name',
                        'price': 'Price',
                        'min_inbox': 'Min inboxes',
                        'max_inbox': 'Max inboxes'
                    };

                    Object.keys(requiredFields).forEach(fieldName => {
                        const $field = $form.find(
                            `input[name="${fieldName}"], textarea[name="${fieldName}"]`);
                        const value = $field.val();

                        if (!value || value.trim() === '') {
                            errors.push(`${requiredFields[fieldName]} is required`);
                            $field.addClass('is-invalid');
                            hasEmptyFields = true;
                        } else if (fieldName === 'min_inbox' && parseInt(value) <= 0) {
                            errors.push('Min inboxes must be greater than 0');
                            $field.addClass('is-invalid');
                            hasEmptyFields = true;
                        } else if ((fieldName === 'max_inbox' || fieldName === 'price') && parseInt(value) <
                            0) {
                            errors.push(`${requiredFields[fieldName]} cannot be negative`);
                            $field.addClass('is-invalid');
                            hasEmptyFields = true;
                        }
                    });
                }

                if (hasEmptyFields) {
                    const errorMessage = `<strong>Form validation failed:</strong><br>• ${errors.join('<br>• ')}`;
                    showErrorToast(errorMessage);

                    // Scroll to first invalid field
                    const $firstInvalid = $('.is-invalid').first();
                    if ($firstInvalid.length) {
                        $firstInvalid[0].scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });
                        $firstInvalid.focus();
                    }

                    return false;
                }

                return true;
            }

            // Prevent form submission with invalid inputs
            $(document).on('submit', 'form', function(e) {
                const $form = $(this);
                const hasInvalidInputs = $form.find('.is-invalid').length > 0;

                if (hasInvalidInputs) {
                    e.preventDefault();
                    showErrorToast('Please fix all validation errors before submitting the form.');
                    return false;
                }
            });

            // Add visual feedback for field validation
            $(document).on('input blur', 'input[required], textarea[required]', function() {
                const $field = $(this);
                const value = $field.val().trim();

                // Remove previous validation classes
                $field.removeClass('is-invalid is-valid');

                if (value === '') {
                    // Field is empty - show as invalid only on blur or if it was previously filled
                    if (event.type === 'blur' || $field.data('was-filled')) {
                        $field.addClass('is-invalid');
                    }
                } else {
                    // Field has value - mark as valid and remember it was filled
                    $field.addClass('is-valid').data('was-filled', true);

                    // Additional validation for specific field types
                    const fieldName = $field.attr('name');
                    if (fieldName === 'min_inbox' && parseInt(value) <= 0) {
                        $field.removeClass('is-valid').addClass('is-invalid');
                    } else if ((fieldName === 'max_inbox' || fieldName === 'price') && parseFloat(value) <
                        0) {
                        $field.removeClass('is-valid').addClass('is-invalid');
                    }
                }
            });

            // Clear validation styling when user starts typing
            $(document).on('focus', 'input, textarea', function() {
                $(this).siblings('.invalid-feedback, .range-error').remove();
            });

            // Initial load of features
            loadFeatures();

            // Add new feature and attach it to plan
            $(document).on('click', '.add-new-feature-btn', function() {
                const planId = $(this).data('plan-id');
                const formContainer = $(this).closest('.new-feature-form');
                const titleInput = formContainer.find('.new-feature-title');
                const valueInput = formContainer.find('.new-feature-value');

                const title = titleInput.val().trim();
                const value = valueInput.val().trim();

                if (!title) {
                    showErrorToast('Please enter a feature title');
                    return;
                }

                // Create new feature via AJAX
                $.ajax({
                    url: "{{ route('admin.features.store') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        title: title,
                        is_active: true
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            // Add the new feature to the container
                            const featureId = response.feature.id;
                            const container = planId === 'new' ? $('#newPlanFeatures') : $(
                                `#selectedFeatures${planId}`);

                            // Check if feature already exists
                            if (container.find(`[data-feature-id="${featureId}"]`).length > 0) {
                                showErrorToast('This feature is already added');
                                return;
                            }

                            const featureHtml = `
                                <div class="feature-item" data-feature-id="${featureId}">
                                    <button type="button" class="btn btn-sm btn-danger remove-feature-btn">
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                    <div class="row">
                                        <div class="col-md-5">
                                            <strong>${title}</strong>
                                            <input type="hidden" name="feature_ids[]" value="${featureId}">
                                        </div>
                                        <div class="col-md-7">
                                            <input type="text" class="form-control form-control-sm feature-value-input" name="feature_values[]" value="${value}" placeholder="Value">
                                        </div>
                                    </div>
                                </div>
                            `;

                            container.append(featureHtml);

                            // Clear inputs
                            titleInput.val('');
                            valueInput.val('');

                            // Reload features dropdown
                            loadFeatures();

                            // Update plan preview if it's an edit form
                            if (planId !== 'new') {
                                updatePlanPreview(planId);
                            }

                            showSuccessToast('New feature added successfully');
                        } else {
                            showErrorToast(response.message || 'Failed to add feature');
                        }
                    },
                    error: function(xhr) {
                        showErrorToast('Failed to add feature');
                    }
                });
            });

            // Call loadFeatures after adding or removing features
            $(document).on('click', '.add-selected-feature', function() {
                const planId = $(this).data('plan-id');
                const dropdown = planId === 'new' ? $('#newPlanFeatureDropdown') : $(
                    `#featureDropdown${planId}`);
                const selectedFeatureId = dropdown.val();
                const selectedFeatureTitle = dropdown.find('option:selected').data('title');

                if (!selectedFeatureId) {
                    showErrorToast('Please select a feature first');
                    return;
                }

                const container = planId === 'new' ? $('#newPlanFeatures') : $(
                    `#selectedFeatures${planId}`);

                // Check if feature already exists
                if (container.find(`[data-feature-id="${selectedFeatureId}"]`).length > 0) {
                    showErrorToast('This feature is already added');
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
                                <input type="text" class="form-control form-control-sm feature-value-input" name="feature_values[]" placeholder="Value">
                            </div>
                        </div>
                    </div>
                `;

                container.append(featureHtml);
                dropdown.val('');

                // Update plan preview if it's an edit form
                if (planId !== 'new') {
                    updatePlanPreview(planId);
                }

                // Refresh the feature dropdowns immediately after adding
                loadFeatures();
            });

            // Toggle new feature form visibility
            $(document).on('click', '.toggle-new-feature-form', function() {
                const planId = $(this).data('plan-id');
                const formContainer = planId === 'new' ?
                    $(this).closest('form').find('.new-feature-form') :
                    $(`#newFeatureForm${planId}`);

                // Toggle form visibility
                formContainer.slideToggle(300);
            });

            // Remove feature from plan
            $(document).on('click', '.remove-feature-btn', function() {
                const featureItem = $(this).closest('.feature-item');
                const planId = $(this).closest('form').data('id');

                // Store reference before removal for preview update
                const featureId = featureItem.data('feature-id');

                // Remove the feature item
                featureItem.remove();

                // Update plan preview if it's an edit form
                if (planId) {
                    updatePlanPreview(planId);
                }

                // Refresh the feature dropdowns after removing
                loadFeatures();
            });

            // Update feature value in real-time
            $(document).on('input', '.feature-value-input', function() {
                const planId = $(this).closest('form').data('id');
                if (planId) {
                    updatePlanPreview(planId);
                }
            });

            // Function to update plan preview in real-time
            function updatePlanPreview(planId) {
                // Get current plan data
                const form = $(`#editPlanForm${planId}`);
                const planCard = $(`#plan-${planId}`);
                const featuresList = planCard.find('.features-list');

                // Clear current features list in preview
                featuresList.empty();

                // Build new features list from selected features
                $(`#selectedFeatures${planId} .feature-item`).each(function() {
                    const featureTitle = $(this).find('strong').text();
                    const featureValue = $(this).find('.feature-value-input').val();

                    // Add feature to preview - don't show value if null/empty
                    featuresList.append(`
                        <li class="mb-2">
                            <i class="fas fa-check text-success"></i> 
                            ${featureTitle}${featureValue ? ' ' + featureValue : ''}
                        </li>
                    `);
                });

                // If no features, add a placeholder
                if (featuresList.find('li').length === 0) {
                    featuresList.append('<li class="mb-2 text-muted">No features added yet</li>');
                }
            }
            // Submit new plan form
            $('#addPlanForm').submit(function(e) {
                e.preventDefault();

                // Clear any previous validation styling
                $(this).find('.is-invalid').removeClass('is-invalid');

                // Validate form completeness
                if (!validateFormCompleteness($(this), 'plan')) {
                    return;
                }

                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');

                $.ajax({
                    url: "{{ route('admin.plans.store') }}",
                    type: "POST",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            // Refresh features dropdown before closing modal
                            loadFeatures();
                            // Clear the form
                            $('#addPlanForm').trigger('reset');
                            $('#newPlanFeatures').empty();
                            $('.new-feature-form').hide();
                            $('.new-feature-title').val('');
                            $('.new-feature-value').val('');

                            // Close modal
                            $('#addPlan').modal('hide');

                            // Refresh the plans section to show the new plan
                            refreshPlansSection();

                            showSuccessToast('Plan created successfully');
                        } else {
                            showErrorToast(response.message || 'Failed to create plan');
                            submitBtn.prop('disabled', false).html('Create Plan');
                        }
                        console.log(response);
                    },
                    error: function(xhr) {
                        // Handle errors
                        const errors = xhr.responseJSON?.errors;
                        if (errors) {
                            let errorMsg = '';
                            Object.keys(errors).forEach(key => {
                                errorMsg += errors[key][0] + '<br>';
                            });
                            showErrorToast(errorMsg);
                        } else {
                            showErrorToast(xhr.responseJSON?.message ||
                                'Failed to create plan');
                        }
                        submitBtn.prop('disabled', false).html('Create Plan');
                    }
                });
            });

            // Submit edit plan form
            $('.edit-plan-form').submit(function(e) {
                e.preventDefault();

                // Clear any previous validation styling
                $(this).find('.is-invalid').removeClass('is-invalid');

                // Validate form completeness
                if (!validateFormCompleteness($(this), 'plan')) {
                    return;
                }

                const planId = $(this).data('id');
                const submitBtn = $(this).find('button[type="submit"]');
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Updating...');

                $.ajax({
                    url: `/admin/plans/${planId}`,
                    type: "PUT",
                    data: $(this).serialize(),
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            // Close modal
                            $(`#editPlan${planId}`).modal('hide');

                            // Update the plan card
                            updatePlanCard(response.plan);

                            // Refresh the plans section to show any changes
                            refreshPlansSection();

                            showSuccessToast('Plan updated successfully');
                        } else {
                            showErrorToast(response.message || 'Failed to update plan');
                            submitBtn.prop('disabled', false).html('Update Plan');
                        }
                    },
                    error: function(xhr) {
                        const errors = xhr.responseJSON?.errors;
                        if (errors) {
                            let errorMsg = '';
                            Object.keys(errors).forEach(key => {
                                errorMsg += errors[key][0] + '<br>';
                            });
                            showErrorToast(errorMsg);
                        } else {
                            showErrorToast(xhr.responseJSON?.message ||
                                'Failed to update plan');
                        }
                        submitBtn.prop('disabled', false).html('Update Plan');
                    }
                });
            });

            // Delete plan
            $(document).on('click', '.delete-plan-btn', function() {
                const $btn = $(this);

                // Get plan ID from data attribute
                const planId = $btn.data('id');
                if (!planId) {
                    showErrorToast('Plan ID not found');
                    return;
                }

                // Confirm deletion
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, do it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Disable button and show loading text
                        $btn.prop('disabled', true).html('Deleting...');

                        // Perform the AJAX request
                        $.ajax({
                            url: `/admin/plans/${planId}`,
                            type: "DELETE",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            dataType: "json",
                            success: function(response) {
                                if (response.success) {
                                    $(`#plan-${planId}`).fadeOut(300, function() {
                                        $(this).remove();
                                    });

                                    // Refresh the plans section to ensure consistency
                                    refreshPlansSection();

                                    showSuccessToast('Plan deleted successfully');
                                } else {
                                    showErrorToast(response.message ||
                                        'Failed to delete plan');
                                    $btn.prop('disabled', false).html('Delete');
                                }
                            },
                            error: function() {
                                showErrorToast('Failed to delete plan');
                                $btn.prop('disabled', false).html('Delete');
                            }
                        });
                    }
                });
            });

            // Helper function to update plan card after edit
            function updatePlanCard(plan) {
                const card = $(`#plan-${plan.id}`);
                card.find('.plan-name').text(plan.name);
                card.find('.plan-price').html(
                    `$${Number(plan.price).toFixed(2)} <span class="fs-6">/${plan.duration == 'monthly' ? 'mo':plan.duration} per inboxes</span>`
                );
                card.find('.plan-description').text(plan.description);

                // Update inbox range with "+" for max_inbox = 0
                const inboxRange =
                    `${plan.min_inbox}${plan.max_inbox == 0 ? '+' : ' - ' + plan.max_inbox} <strong>Inboxes</strong>`;
                card.find('.mb-3').html(inboxRange);

                // Update features list
                let featuresHtml = '';
                if (plan.features && plan.features.length > 0) {
                    plan.features.forEach(function(feature) {
                        // Don't show value if null/empty
                        const featureValue = feature.pivot.value;
                        featuresHtml += `
                            <li class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                ${feature.title}${featureValue ? ' ' + featureValue : ''}
                            </li>
                        `;
                    });
                }
                card.find('.features-list').html(featuresHtml);

                // Set popular class if needed
                if (plan.name === 'Standard') {
                    card.find('.pricing-card').addClass('popular');
                } else {
                    card.find('.pricing-card').removeClass('popular');
                }
            }

            // Toast helpers
            function showSuccessToast(message) {
                $('#successMessage').html(message);
                const toast = new bootstrap.Toast(document.getElementById('successToast'));
                toast.show();
            }

            function showErrorToast(message) {
                $('#errorMessage').html(message);
                const toast = new bootstrap.Toast(document.getElementById('errorToast'));
                toast.show();
            }

            function showWarningToast(message) {
                // For simplicity, using error toast for warnings
                showErrorToast(message);
            }
            // autofixing volume tier plan range start like [10-20, 0-9, 21-0] don't miss the range same functionality perform like chagebee side handle not skip range
            // Function to update master plan display dynamically
          function updateMasterPlanDisplay(masterPlans) {
            const $container = $('#masterPlanContainer');

            $container.fadeOut(200, function () {
                if (Array.isArray(masterPlans) && masterPlans.length > 0) {
                    let html = '';
                    masterPlans.forEach((plan, index) => {
                        const volumeItemsCount = plan.volume_items?.length || 0;
                        const chargebeeStatus = plan.chargebee_plan_id
                            ? `<span class="text-success"><i class="fa-solid fa-check-circle"></i> ${plan.chargebee_plan_id}</span>`
                            : '<span class="text-warning"><i class="fa-solid fa-exclamation-triangle"></i> Not synced</span>';

                        html += `
                            <div class="card mb-3 p-3 border shadow-sm">
                                <div class="row">
                                    <div class="col-md-3 d-flex flex-column">
                                        <small class="small">Plan Name</small>
                                        <small class="opacity-75">${plan.external_name || 'N/A'}</small>
                                    </div>

                                    <div class="col-md-3 d-flex flex-column">
                                        <small class="small">Chargebee Status</small>
                                        <small class="opacity-75">${chargebeeStatus}</small>
                                    </div>

                                    <div class="col-md-3 d-flex flex-column">
                                        <small class="small">Volume Tiers</small>
                                        <small class="opacity-75">${volumeItemsCount} tiers</small>
                                    </div>

                                    <div class="col-md-3 d-flex flex-column">
                                        <small class="small">Description</small>
                                        <small class="opacity-75">${plan.description || 'N/A'}</small>
                                    </div>
                                </div>
                                <div class="text-end mt-2">
                                    <button class="btn btn-sm btn-primary editMasterPlanBtn" data-id="${plan.id}">Edit Plan</button>
                                </div>
                            </div>
                        `;
                    });

                    $container.html(html);
                } else {
                    $container.html('<p class="text-muted">No master plans found.</p>');
                }

                $container.fadeIn(200);
            });
          }



            // Function to refresh the simple plans section
            function refreshPlansSection() {
                console.log('Refreshing plans section...');

                // Add loading indicator
                const plansContainer = $('#plans-container');
                const originalContent = plansContainer.html();

                // Show loading state
                plansContainer.append(
                    '<div id="refresh-loading" class="text-center my-3"><i class="fas fa-spinner fa-spin"></i> Updating plans...</div>'
                );

                $.get('{{ route('admin.plans.with.features') }}')
                    .done(function(response) {
                        console.log('Received response:', response);

                        // Handle both old format (direct plans array) and new format (object with plans and mostlyUsed)
                        const plans = response.plans || response;
                        const mostlyUsed = response.mostlyUsed || null;

                        console.log('Received plans data:', plans);
                        console.log('Most popular plan:', mostlyUsed);

                        // Remove loading indicator
                        $('#refresh-loading').remove();

                        // Check if plan count changed or if we have new plans
                        const currentPlanCount = $('#plans-container .col-sm-6').length;
                        const newPlanCount = plans.length;

                        console.log(`Current plan count: ${currentPlanCount}, New plan count: ${newPlanCount}`);

                        // Handle plan count changes dynamically
                        if (currentPlanCount !== newPlanCount) {
                            console.log('Plan count changed, rebuilding plans section...');
                            rebuildPlansSection(plans, mostlyUsed);
                            return;
                        }

                        // Update existing plan cards with new data
                        updateExistingPlans(plans, mostlyUsed);
                    })
                    .fail(function(xhr) {
                        console.error('Failed to refresh plans section:', xhr);
                        $('#refresh-loading').remove();
                        showErrorToast('Failed to refresh plans section');
                    });
            }
            // Update existing plan cards with new data
            function updateExistingPlans(plans, mostlyUsed = null) {
                console.log('Updating existing plans:', plans);
                console.log('Most popular plan:', mostlyUsed);

                // Get current plan IDs on the page
                const currentPlanIds = [];
                $('#plans-container .col-sm-6').each(function() {
                    const planId = $(this).attr('id').replace('plan-', '');
                    currentPlanIds.push(parseInt(planId));
                });

                // Get new plan IDs from API
                const newPlanIds = plans.map(plan => plan.id);

                console.log('Current plan IDs:', currentPlanIds);
                console.log('New plan IDs:', newPlanIds);

                // Check if we have new plans or removed plans
                const hasNewPlans = newPlanIds.some(id => !currentPlanIds.includes(id));
                const hasRemovedPlans = currentPlanIds.some(id => !newPlanIds.includes(id));

                if (hasNewPlans || hasRemovedPlans) {
                    console.log('Plan structure changed, rebuilding plans section...');
                    rebuildPlansSection(plans, mostlyUsed);
                    return;
                }

                // Update existing plan cards
                plans.forEach(function(plan) {
                    console.log('Updating plan:', plan);
                    const $planCard = $(`#plan-${plan.id}`);
                    if ($planCard.length > 0) {
                        console.log('Found plan card for plan ID:', plan.id);

                        // Update plan name
                        $planCard.find('.plan-name').text(plan.name);

                        // Update plan price
                        $planCard.find('.plan-price').html(
                            `$${Number(plan.price).toFixed(2)} <span class="fs-6 fw-normal">/${plan.duration == 'monthly' ? 'mo' : plan.duration} per inboxes</span>`
                        );

                        // Update plan description
                        $planCard.find('.plan-description').text(plan.description);

                        // Update inbox range - fix selector to match actual HTML structure
                        const inboxRange = plan.max_inbox == 0 ? `${plan.min_inbox}+` :
                            `${plan.min_inbox} - ${plan.max_inbox}`;
                        $planCard.find('.mb-2').html(`${inboxRange} <strong>Inboxes</strong>`);
                        // Update features
                        let featuresHtml = '';
                        if (plan.features && plan.features.length > 0) {
                            featuresHtml = plan.features.map(feature => {
                                const featureValue = feature.pivot && feature.pivot.value ? ' ' +
                                    feature.pivot.value : '';
                                return `<li class="mb-2"><i class="fas fa-check text-success"></i> ${feature.title}${featureValue}</li>`;
                            }).join('');
                        } else {
                            featuresHtml = '<li class="mb-2 text-muted">No features available</li>';
                        }
                        $planCard.find('.features-list').html(featuresHtml);

                        // Update popular status using dynamic mostlyUsed data
                        const mostlyUsedId = mostlyUsed ? mostlyUsed.id : null;
                        const isPopular = mostlyUsedId === plan.id;

                        console.log(
                            `Plan ${plan.id}: mostlyUsedId=${mostlyUsedId}, isPopular=${isPopular}`);

                        if (isPopular) {
                            $planCard.find('.pricing-card').addClass('popular');
                            console.log(`Added 'popular' class to plan ${plan.id}`);
                        } else {
                            $planCard.find('.pricing-card').removeClass('popular');
                            console.log(`Removed 'popular' class from plan ${plan.id}`);
                        }

                        // Add subtle animation to show the card was updated
                        $planCard.addClass('plan-updated');
                        setTimeout(function() {
                            $planCard.removeClass('plan-updated');
                        }, 2000);
                    } else {
                        console.log('Plan card not found for plan ID:', plan.id);
                    }
                });

                console.log('Plans update completed successfully');
                showSuccessToast('Plans updated successfully!');
            }

            // Rebuild plans section when plan count changes
            function rebuildPlansSection(plans, mostlyUsed = null) {
                console.log('Rebuilding plans section with new data:', plans);
                console.log('Most popular plan data:', mostlyUsed);

                const plansContainer = $('#plans-container');
                const mostlyUsedId = mostlyUsed ? mostlyUsed.id : null;

                // Build new HTML for all plans
                let plansHtml = '';

                plans.forEach(function(plan) {
                    const isPopular = mostlyUsedId === plan.id;
                    const popularClass = isPopular ? 'popular' : '';
                    const inboxRange = plan.max_inbox == 0 ? `${plan.min_inbox}+` :
                        `${plan.min_inbox} - ${plan.max_inbox}`;

                    let featuresHtml = '';
                    if (plan.features && plan.features.length > 0) {
                        featuresHtml = plan.features.map(feature => {
                            const featureValue = feature.pivot && feature.pivot.value ? ' ' +
                                feature.pivot.value : '';
                            return `<li class="mb-2"><i class="fas fa-check text-success"></i> ${feature.title}${featureValue}</li>`;
                        }).join('');
                    } else {
                        featuresHtml = '<li class="mb-2 text-muted">No features available</li>';
                    }

                    plansHtml += `
                <div class="col-sm-6 col-lg-4 mb-5" id="plan-${plan.id}">
                    <div class="pricing-card d-flex flex-column justify-content-between ${popularClass}">
                        <div>
                            <h4 class="fw-bold plan-name text-capitalize fs-5">${plan.name}</h4>
                            <h2 class="fw-bold plan-price fs-3">$${Number(plan.price).toFixed(2)} <span class="fs-6 fw-normal">/${plan.duration == 'monthly' ? 'mo' : plan.duration} per inboxes</span></h2>
                            <p class="plan-description text-capitalize">${plan.description}</p>
                            <hr>
                            <div class="mb-2">
                                ${inboxRange} <strong>Inboxes</strong>
                            </div>
                            <ul class="list-unstyled features-list">
                                ${featuresHtml}
                            </ul>
                        </div>
                    </div>
                </div>`;
                });

                // Fade out, replace content, then fade in
                plansContainer.fadeOut(300, function() {
                    plansContainer.html(plansHtml);
                    plansContainer.fadeIn(300);
                    showSuccessToast('Plans section rebuilt successfully!');
                });
            }

            // Master Plan Functions
            function loadMasterPlan() {
               
                $.get('{{ route('admin.master-plan.show') }}')
                    .done(function(response) {

                        console.log('Master plan data:', response);
                         updateMasterPlanDisplay(response);
                    })
                    .fail(function() {
                        updateMasterPlanDisplay(null);
                    });
            }

            // Load master plan on page load
            loadMasterPlan();

            // // Create/Edit Master Plan
            $('#createMasterPlan').click(function() {
                // Load existing data if available
                // $.get('{{ route('admin.master-plan.show') }}')
                //     .done(function(response) {
                //         if (response && response.id) {
                //             $('#masterPlanExternalName').val(response.name || '');
                //             // Generate internal name from external name instead of using stored value
                //             const generatedInternalName = generateInternalName(response.name || '');
                //             $('#masterPlanInternalName').val(generatedInternalName);
                //             $('#internalNamePreview').text(generatedInternalName ||
                //             'plan_name_preview');
                //             $('#masterPlanDescription').val(response.description || '');
                //         }
                //     })
                //     .always(function() {
                //         $('#masterPlanModal').modal('show');
                //     });

                       $('#masterPlanId').val(''); // Clear ID for new plan
                      $('#masterPlanModal').modal('show');
            });

$(document).on('click', '.editMasterPlanBtn', function () {
    const id = $(this).data('id');

    // Load existing data for the selected plan using its ID
  const url = '{{ url('admin/master-plan') }}/' + id;
    $.get(url)
        .done(function (response) { 
            if (response && response[0].id) {
                loadMasterPlanData(response[0]);

                $('#masterPlanId').val(response[0].id || '');
                $('#masterPlanExternalName').val(response[0].external_name	 || '');

                // Generate internal name from external name
                const generatedInternalName = generateInternalName(response[0].external_name	 || '');
                $('#masterPlanInternalName').val(generatedInternalName);
                $('#internalNamePreview').text(generatedInternalName || 'plan_name_preview');

                $('#masterPlanDescription').val(response[0].description || '');
            }
        })
        .always(function () {
            $('#masterPlanModal').modal('show');
        });
});


            // Save Master Plan
            $('#saveMasterPlan').click(function() {
                const button = $(this);
                button.prop('disabled', true).text('Saving...');

                // Clear any previous validation styling
                $('.is-invalid').removeClass('is-invalid');

                // Validate form completeness first
                if (!validateFormCompleteness(null, 'masterplan')) {
                    button.prop('disabled', false).text('Save Master Plan');
                    return;
                }

                // Collect form data
                const formData = {
                    external_name: $('#masterPlanExternalName').val(),
                    internal_name: $('#masterPlanInternalName').val(),
                    description: $('#masterPlanDescription').val(),
                    discountMode : $('#planTypeRole').val(), // ✅ Only read once
                    volume_items: collectVolumeItems(),
                    masterPlanId: $('#masterPlanId').val() || null,
                    _token: '{{ csrf_token() }}'
                };

                // Validate each volume item for valid data and ranges
                for (let i = 0; i < formData.volume_items.length; i++) {
                    const item = formData.volume_items[i];

                    // Check for missing required data
                    if (!item.name || !item.name.trim()) {
                        showErrorToast(`Tier ${i + 1} name is required and cannot be empty.`);
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }

                    // Check for missing min_inbox value or if it's 0
                    if (item.min_inbox === undefined || item.min_inbox === null || item.min_inbox === 0) {
                        showErrorToast(`Tier ${i + 1} min inboxes is required and must be greater than 0.`);
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }

                    // Check for missing max_inbox value (note: 0 is valid for unlimited)
                    if (item.max_inbox === undefined || item.max_inbox === null || item.max_inbox === '') {
                        showErrorToast(`Tier ${i + 1} max inboxes is required. Use 0 for unlimited.`);
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }

                    // Check for missing price value or if it's negative
                    if (item.price === undefined || item.price === null || item.price === '' || item.price <
                        0) {
                        showErrorToast(`Tier ${i + 1} price is required and must be 0 or greater.`);
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }

                    // Check for NaN or invalid values
                    if (isNaN(item.min_inbox) || isNaN(item.max_inbox) || isNaN(item.price)) {
                        showErrorToast(
                            `Invalid numeric values in tier ${i + 1}. Please check min inbox, max inbox, and price fields.`
                        );
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }

                    // Check for negative values
                    if (item.min_inbox < 0 || item.max_inbox < 0 || item.price < 0) {
                        showErrorToast(`Negative values not allowed in tier ${i + 1}.`);
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }

                    // Check range validity: min should not be greater than max (unless max is 0 for unlimited)
                    if (item.max_inbox !== 0 && item.min_inbox > item.max_inbox) {
                        showErrorToast(
                            `Invalid range in tier ${i + 1}: Min inboxes (${item.min_inbox}) cannot be greater than max inboxes (${item.max_inbox}). Set max to 0 for unlimited or adjust the values.`
                        );
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }
                }

                // Enhanced volume tier range validation and gap detection
                const sortedItems = formData.volume_items.sort((a, b) => a.min_inbox - b.min_inbox);

                // Volume tier range validation
                // Ensure at least one tier exists
                if (sortedItems.length === 0) {
                    showErrorToast('At least one volume tier is required.');
                    button.prop('disabled', false).text('Save Master Plan');
                    return;
                }

                // Ensure first tier starts from 1 (ChargeBee requirement)
                const firstTier = sortedItems[0];
                if (firstTier.min_inbox !== 1) {
                    showErrorToast(
                        `First tier must start at 1 inbox (ChargeBee requirement). Current first tier starts at ${firstTier.min_inbox}.`
                    );
                    button.prop('disabled', false).text('Save Master Plan');
                    return;
                }

                for (let i = 0; i < sortedItems.length; i++) {
                    const current = sortedItems[i];
                    const next = sortedItems[i + 1];
                    const tierNumber = i + 1;

                    // Basic validation
                    if (current.min_inbox < 0) {
                        showErrorToast(
                            `Tier ${tierNumber} (${current.name}): Min inboxes cannot be negative, got ${current.min_inbox}.`
                        );
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }

                    if (current.max_inbox < 0) {
                        showErrorToast(
                            `Tier ${tierNumber} (${current.name}): Max inboxes cannot be negative, got ${current.max_inbox}. Use 0 for unlimited.`
                        );
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }

                    if (current.max_inbox !== 0 && current.min_inbox > current.max_inbox) {
                        showErrorToast(
                            `Tier ${tierNumber} (${current.name}): Invalid range [${current.min_inbox}-${current.max_inbox}]. Min cannot be greater than max unless max is 0 (unlimited).`
                        );
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }

                    // Check for duplicate ranges
                    for (let j = i + 1; j < sortedItems.length; j++) {
                        const other = sortedItems[j];
                        if (current.min_inbox === other.min_inbox) {
                            showErrorToast(
                                `Duplicate min inbox value: Tier ${tierNumber} and Tier ${j + 1} both start at ${current.min_inbox}. Each tier must have unique ranges.`
                            );
                            button.prop('disabled', false).text('Save Master Plan');
                            return;
                        }
                    }

                    // Validate unlimited tier placement
                    if (current.max_inbox === 0 && next) {
                        showErrorToast(
                            `Tier ${tierNumber} (${current.name}): Unlimited range (max_inbox = 0) can only be used in the last tier. Found ${sortedItems.length - i - 1} tier(s) after it.`
                        );
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }

                    // Critical ChargeBee requirement: Check for gaps that would cause "Tier information is missing after X"
                    if (next) {
                        const currentEnd = current.max_inbox;
                        const nextStart = next.min_inbox;

                        if (currentEnd === 0) {
                            // Already handled above - unlimited tier not at end
                            continue;
                        }

                        // ChargeBee requires EXACT continuity - next tier must start immediately after current ends
                        const expectedNextStart = currentEnd + 1;
                        if (nextStart !== expectedNextStart) {
                            if (nextStart > expectedNextStart) {
                                showErrorToast(
                                    `Gap detected between tiers. Tier ending at ${currentEnd} has a gap before the next tier starting at ${nextStart}. ChargeBee requires continuous tier ranges. Next tier should start at ${expectedNextStart}.`
                                );
                            } else {
                                showErrorToast(
                                    `Overlapping ranges detected between tiers. Tier ending at ${currentEnd} overlaps with tier starting at ${nextStart}.`
                                );
                            }
                            button.prop('disabled', false).text('Save Master Plan');
                            return;
                        }
                    }
                }

                // Final ChargeBee compatibility check
                console.log(
                    '✅ ChargeBee compatibility validated - continuous ranges with no gaps detected');

                console.log(
                    '✅ All volume tier ranges validated successfully - no gaps or overlaps detected');

                $.ajax({
                        url: '{{ route('admin.master-plan.store') }}',
                        method: 'POST',
                        data: formData,
                        dataType: 'json'
                    })
                    .done(function(response) {
                        if (response.success) {
                            $('#masterPlanModal').modal('hide');

                            // Show different success messages based on Chargebee sync status
                            const successMessage = response.message ||
                                'Master plan saved successfully!';
                            showSuccessToast(successMessage);

                            // Update the master plan display with new data
                            updateMasterPlanDisplay(response.data);

                            // Refresh the simple plans section to reflect any changes
                            setTimeout(function() {
                                console.log('Master plan saved, refreshing plans section...');
                                refreshPlansSection();
                            }, 500);

                            // Clear the form for next time
                            $('#masterPlanForm')[0].reset();
                            $('#volumeItemsContainer').empty();
                            volumeItemIndex = 0;
                        } else {
                            showErrorToast(response.message || 'Failed to save master plan');
                        }
                    })
                    .fail(function(xhr) {
                        let errorMessage = 'Failed to save master plan';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                            // Handle validation errors
                            const errors = Object.values(xhr.responseJSON.errors).flat();
                            errorMessage = errors.join('<br>');
                        } else if (xhr.responseText) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                errorMessage = response.message || errorMessage;
                            } catch (e) {
                                // Use default error message
                            }
                        }
                        showErrorToast(errorMessage);
                    })
                    .always(function() {
                        button.prop('disabled', false).text('Save Master Plan');
                    });
            });

            // Volume item management
            var volumeItemIndex = 0;

            // Add volume item
            $('#addVolumeItem').on('click', function() {
                addVolumeItem();
            });

            // Function to generate internal name from external name
            function generateInternalName(externalName) {
                return externalName
                    .toLowerCase()
                    .replace(/[^a-z0-9\s]/g, '') // Remove special characters except spaces
                    .replace(/\s+/g, '_') // Replace spaces with underscores
                    .replace(/_+/g, '_') // Replace multiple underscores with single
                    .replace(/^_|_$/g, ''); // Remove leading/trailing underscores
            }

            // Auto-generate internal name when external name changes
            $(document).on('input', '#masterPlanExternalName', function() {
                const externalName = $(this).val();
                const internalName = generateInternalName(externalName);
                $('#masterPlanInternalName').val(internalName);

                // Update preview
                $('#internalNamePreview').text(internalName || 'plan_name_preview');
            });

            function addVolumeItem(data = null) {
                const item = data ? {
                    id: data.id || null, // Include ID for existing items
                    name: data.name || '',
                    description: data.description || '',
                    min_inbox: data.min_inbox || '',
                    max_inbox: data.max_inbox || '',
                    price: data.price || '',
                    duration: data.duration || 'monthly',
                    features: data.features || [],
                    feature_values: data.feature_values || [],
                    tier_discount_type: data.tier_discount_type || null,
                    tier_discount_value: data.tier_discount_value || null,
                } : {
                    id: null, // New items don't have ID
                    name: '',
                    description: '',
                    min_inbox: '',
                    max_inbox: '',
                    price: '',
                    duration: 'monthly',
                    features: [],
                    feature_values: [],
                    tier_discount_type: null,
                    tier_discount_value: null,
                };
                

                const itemHtml = `
                <div class="volume-item border rounded p-3 mb-3" data-index="${volumeItemIndex}" data-item-id="${item.id || ''}">
                    <div class="d-flex justify-content-between align-items-center ">
                        <h6 class="mb-0">Tier ${volumeItemIndex + 1}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-volume-item">
                            <i class="fa-solid fa-trash"></i> Remove
                        </button>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="">
                                <label class="form-label">Tier Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control volume-name" name="volume_items[${volumeItemIndex}][name]" value="${item.name}" required>
                                ${item.id ? `<input type="hidden" class="volume-id" name="volume_items[${volumeItemIndex}][id]" value="${item.id}">` : ''}
                            </div>
                        </div>
                        <div class="col-md-6 mb-3" style="display:none;">
                            <div class="">
                                <label class="form-label">Duration <span class="text-danger">*</span></label>
                                <select class="form-control volume-duration" name="volume_items[${volumeItemIndex}][duration]" required>
                                    <option value="monthly" ${item.duration === 'monthly' ? 'selected' : ''}>Monthly</option>
                                    <option value="yearly" ${item.duration === 'yearly' ? 'selected' : ''}>Yearly</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <div class="">
                                <label class="form-label">Min Inboxes <span class="text-danger">*</span></label>
                                <input type="number" class="form-control volume-min-inbox" name="volume_items[${volumeItemIndex}][min_inbox]" value="${item.min_inbox}" min="1" step="1" required>
                                <small class="text-muted">Must be 1 or greater</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="">
                                <label class="form-label">Max Inboxes <span class="text-danger">*</span></label>
                                <input type="number" class="form-control volume-max-inbox" name="volume_items[${volumeItemIndex}][max_inbox]" value="${item.max_inbox || '0'}" min="0" step="1">
                                <small class="opacity-75">Set to 0 for unlimited</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2">
                            <div class="">
                                <label class="form-label">Price per Inbox <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control volume-price tier_volume_price" data-itemindex="${volumeItemIndex}" id="tier_volume_price_${volumeItemIndex}" name="volume_items[${volumeItemIndex}][price]" value="${item.price}" step="0.01" min="0" required>
                                </div>
                                <small class="text-muted">Must be 0 or greater</small>
                            </div>
                        </div>
                        <div class="col-md-3 mb-2 calculated_price_after_discount " style="display:none;">
                            <div class="">
                                <label class="form-label">Final Price after applied discount<span class="text-danger"></span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price_after_discount_${volumeItemIndex}" >
                                </div>
                                <small class="text-muted">Must be 0 or greater</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descriptionk</label>
                        <textarea class="form-control volume-description" name="volume_items[${volumeItemIndex}][description]" rows="2">${item.description}</textarea>
                    </div>
                    

                <!-- tier discount fields -->
                 <div class="tier-discount-container-${volumeItemIndex}">
                   ${createTierDiscountFields(volumeItemIndex, item)}
                </div>

                    <!-- Features Section -->
                    <div class="">
                        <label class="form-label">Features</label>
                        <div class="features-container" id="featuresContainer${volumeItemIndex}">
                            <div class="row">
                                <div class="col-md-7 mb-3">
                                    <select class="form-control feature-select volume-feature-dropdown" id="featureSelect${volumeItemIndex}" data-index="${volumeItemIndex}">
                                        <option value="">Select a feature to add</option>
                                    </select>
                                </div>
                                <div class="col-md-5 mb-3" style="text-align: right;">
                                    <button type="button" class="btn btn-sm btn-primary border-0 toggle-new-feature-form-volume" data-index="${volumeItemIndex}">
                                        <i class="fa-solid fa-plus"></i> New
                                    </button>
                                </div>
                            </div>
                            
                            <!-- New Feature Form for Volume Items -->
                            <div class="new-feature-form mt-3" id="newFeatureFormVolume${volumeItemIndex}" style="display: none;">
                                <h6>New Feature</h6>
                                <div class="row">
                                    <div class="col-md-5">
                                        <input type="text" class="form-control mb-2 new-feature-title-volume" placeholder="Feature Title">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="text" class="form-control mb-2 new-feature-value-volume" placeholder="Feature Value">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-primary add-new-feature-btn-volume btn-sm border-0" data-index="${volumeItemIndex}">
                                            <i class="fa-solid fa-plus"></i> Add
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="selected-features-list mt-2" id="selectedFeaturesList${volumeItemIndex}">
                                <!-- Selected features will appear here -->
                            </div>
                        </div>
                    </div>
                </div>
                `;

                $('#volumeItemsContainer').append(itemHtml);
                volumeItemIndex++;
                updateTierNumbers();

                // Load available features for this volume item
                loadFeaturesForVolumeItem(volumeItemIndex - 1, item.features, item.feature_values);
            }
function createTierDiscountFields(volumeItemIndex, item) {
    const selectedVal = $('#planTypeRole').val();
    if (selectedVal === "Discounted") {
        return `
            <div class="row mt-3 mb-3 discount-fields" id="discountFields${volumeItemIndex}">
                <div class="col-md-4">
                    <label  class="form-label">Discount Type</label>
                    <select value="${item.tier_discount_type}" class="form-select tier_discount_type" 
                        id="tier_discount_type_${volumeItemIndex}" 
                        data-itemindex="${volumeItemIndex}" 
                        name="volume_items[${volumeItemIndex}][discount_type]">
                        <option value="percentage">Percentage</option>
                        <option value="fixed">Fixed</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount Value</label>
                    <input type="number" class="form-control tier_discount_value" 
                        id="tier_discount_value_${volumeItemIndex}" 
                        data-itemindex="${volumeItemIndex}" 
                        name="volume_items[${volumeItemIndex}][discount_value]" 
                        value="${item.tier_discount_value || 0}"
                        placeholder="Discount" step="0.01" />
                </div>
            </div>
        `;
    } else {
        return '';
    }
} 



            // Add input validation to prevent invalid values and validate range logic (ChargeBee compatible)
            $(document).on('input', '.volume-min-inbox, .volume-max-inbox', function() {
                let value = $(this).val();

                // Allow empty values during editing
                if (value === '') {
                    return;
                }

                // Convert to integer and validate
                let intValue = parseInt(value);

                // Check for invalid numbers or negative values (min values should be 1 or higher)
                if (isNaN(intValue) || intValue < 0) {
                    $(this).val('');
                    return;
                }

                // Special validation for min_inbox - must be at least 1
                if ($(this).hasClass('volume-min-inbox') && intValue === 0) {
                    $(this).val('');
                    showErrorToast(
                        'Min inboxes must be 1 or greater. Use Auto-Fix to create proper ranges.');
                    return;
                }

                // Set the cleaned value
                $(this).val(intValue);

                // Validate range after input
                validateVolumeItemRange($(this).closest('.volume-item'));
            });

            // Add similar validation for regular plan inputs
            $(document).on('input', 'input[name="min_inbox"]', function() {
                let value = $(this).val();

                if (value === '') {
                    return;
                }

                let intValue = parseInt(value);

                if (isNaN(intValue) || intValue <= 0) {
                    $(this).val('');
                    showErrorToast('Min inboxes must be 1 or greater.');
                    return;
                }

                $(this).val(intValue);
            });

            $(document).on('input', 'input[name="max_inbox"]', function() {
                let value = $(this).val();

                if (value === '') {
                    return;
                }

                let intValue = parseInt(value);

                if (isNaN(intValue) || intValue < 0) {
                    $(this).val('');
                    showErrorToast('Max inboxes must be 0 or greater (0 = unlimited).');
                    return;
                }

                $(this).val(intValue);
            });

            // Validate individual volume item range with enhanced feedback (ChargeBee compatible)
            function validateVolumeItemRange($item) {
                const minInbox = parseInt($item.find('.volume-min-inbox').val()) || 0;
                const maxInbox = parseInt($item.find('.volume-max-inbox').val()) || 0;
                const $minField = $item.find('.volume-min-inbox');
                const $maxField = $item.find('.volume-max-inbox');
                const tierName = $item.find('.volume-name').val() || 'Unnamed tier';

                // Remove existing validation styles
                $minField.removeClass('is-invalid');
                $maxField.removeClass('is-invalid');
                $item.find('.range-error').remove();

                let isValid = true;
                let errorMessages = [];

                // Check for negative values (ranges should start from 1 or higher)
                if (minInbox < 0) {
                    $minField.addClass('is-invalid');
                    errorMessages.push(`Min inboxes cannot be negative (${minInbox}). Use 1 or higher.`);
                    isValid = false;
                }

                // Check for min values less than 1 (enforce 1-based ranges)
                if (minInbox === 0) {
                    $minField.addClass('is-invalid');
                    errorMessages.push(`Min inboxes should start from 1, not 0. Use Auto-Fix for proper ranges.`);
                    isValid = false;
                }

                if (maxInbox < 0) {
                    $maxField.addClass('is-invalid');
                    errorMessages.push(`Max inboxes cannot be negative (${maxInbox}). Use 0 for unlimited.`);
                    isValid = false;
                }

                // Check if min > max (when max is not 0 for unlimited)
                if (maxInbox !== 0 && minInbox > maxInbox) {
                    $minField.addClass('is-invalid');
                    $maxField.addClass('is-invalid');
                    errorMessages.push(
                        `Min inboxes (${minInbox}) cannot be greater than max inboxes (${maxInbox})`);
                    isValid = false;
                }

                // Display errors if any
                if (errorMessages.length > 0) {
                    const errorHtml = `<div class="range-error text-danger small mt-1">
                    <i class="fa-solid fa-exclamation-triangle"></i> 
                    ${errorMessages.join('<br>')}
                    <div class="mt-1">
                        <small>Tip: Min should start from 1 or higher. Set max to 0 for unlimited or use Auto-Fix button.</small>
                    </div>
                </div>`;
                    $item.append(errorHtml);
                }

                return isValid;
            }

            $(document).on('input', '.volume-price', function() {
                let value = $(this).val();
                if (value === '' || isNaN(value) || value < 0) {
                    $(this).val('');
                } else {
                    // Ensure it's a valid decimal with max 2 decimal places
                    let numValue = parseFloat(value);
                    if (!isNaN(numValue)) {
                        // $(this).val(numValue.toFixed(2));
                    }
                }
            });

            // Remove volume item
            $(document).on('click', '.remove-volume-item', function() {
                $(this).closest('.volume-item').remove();
                updateTierNumbers();
            });

            // Function to ensure unlimited tier exists
            function ensureUnlimitedTierExists() {
                let hasUnlimitedTier = false;
                let highestMax = 0;

                $('#volumeItemsContainer .volume-item').each(function() {
                    const maxInbox = parseInt($(this).find('.volume-max-inbox').val()) || 0;
                    if (maxInbox === 0) {
                        hasUnlimitedTier = true;
                    } else if (maxInbox > highestMax) {
                        highestMax = maxInbox;
                    }
                });

                if (!hasUnlimitedTier) {
                    const nextMin = highestMax > 0 ? highestMax + 1 : 1;

                    // Auto-create unlimited tier
                    addVolumeItem({
                        name: 'Unlimited Tier',
                        description: 'Unlimited volume tier for higher quantities',
                        min_inbox: nextMin,
                        max_inbox: 0, // Unlimited
                        price: 0,
                        duration: 'monthly',
                        features: [],
                        feature_values: []
                    });

                    showSuccessToast(`Created unlimited tier starting from ${nextMin}`);
                    return true;
                }

                return false;
            }

            // Enhanced tier ordering function
            function orderTiersByRange() {
                const container = $('#volumeItemsContainer');
                const items = [];

                // Collect all tier elements with their range data
                container.find('.volume-item').each(function() {
                    const $item = $(this);
                    const minInbox = parseInt($item.find('.volume-min-inbox').val()) || 0;
                    const maxInbox = parseInt($item.find('.volume-max-inbox').val()) || 0;

                    items.push({
                        element: $item.clone(true), // Clone with events
                        min: minInbox,
                        max: maxInbox
                    });
                });

                // Sort items: unlimited tiers (max = 0) go last, others by min value
                items.sort((a, b) => {
                    // Unlimited tiers go last
                    if (a.max === 0 && b.max !== 0) return 1;
                    if (b.max === 0 && a.max !== 0) return -1;

                    // Sort by min value
                    return a.min - b.min;
                });

                // Clear container and re-add in correct order
                container.empty();
                items.forEach(item => {
                    container.append(item.element);
                });

                // Update tier numbers
                updateTierNumbers();
            }

            // Update tier numbers
            function updateTierNumbers() {
                $('#volumeItemsContainer .volume-item').each(function(index) {
                    $(this).find('h6').text(`Tier ${index + 1}`);
                    $(this).data('index', index);
                });
            }

            // Auto-fix range sequences with improved logic like Chargebee
            function autoFixRanges() {
                const items = [];
                let hasErrors = false;

                // Collect all volume items with their current data
                $('#volumeItemsContainer .volume-item').each(function() {
                    const $item = $(this);
                    const minInbox = parseInt($item.find('.volume-min-inbox').val()) || 0;
                    const maxInbox = parseInt($item.find('.volume-max-inbox').val()) || 0;
                    const name = $item.find('.volume-name').val() || '';

                    // Basic validation - check for obviously invalid data
                    if (minInbox < 1) {
                        hasErrors = true;
                        showErrorToast(`Invalid min value (${minInbox}) in tier "${name}". Must be >= 1.`);
                        return;
                    }

                    if (maxInbox < 0) {
                        hasErrors = true;
                        showErrorToast(
                            `Invalid max value (${maxInbox}) in tier "${name}". Must be >= 0 (0 = unlimited).`
                        );
                        return;
                    }

                    items.push({
                        element: $item,
                        name: name || `Tier ${items.length + 1}`,
                        min_inbox: minInbox,
                        max_inbox: maxInbox,
                        originalMin: minInbox,
                        originalMax: maxInbox,
                        index: items.length
                    });
                });

                if (hasErrors) {
                    return; // Don't proceed if there are validation errors
                }

                if (items.length === 0) {
                    showErrorToast('No volume tiers to fix');
                    return;
                }

                if (items.length === 1) {
                    // Single tier - ensure it starts from a reasonable min (1 or higher)
                    const singleItem = items[0];
                    const currentMin = singleItem.min_inbox;
                    const startMin = currentMin > 0 ? currentMin : 1; // Start from 1 if min is 0

                    singleItem.element.find('.volume-min-inbox').val(startMin);

                    // For single tier, if max is 0 keep it unlimited, otherwise ensure max >= min
                    if (singleItem.max_inbox !== 0 && singleItem.max_inbox < startMin) {
                        singleItem.element.find('.volume-max-inbox').val(0); // Make unlimited
                        showSuccessToast('Single tier range fixed - set to unlimited');
                    } else {
                        showSuccessToast('Single tier range validated');
                    }
                    return;
                }

                // Multiple tiers - sort by original min values first to preserve intent
                // Then sort unlimited tiers (max_inbox = 0) to the end
                items.sort((a, b) => {
                    // First priority: unlimited tiers go last
                    if (a.max_inbox === 0 && b.max_inbox !== 0) return 1;
                    if (b.max_inbox === 0 && a.max_inbox !== 0) return -1;

                    // Second priority: sort by min_inbox
                    if (a.min_inbox !== b.min_inbox) {
                        return a.min_inbox - b.min_inbox;
                    }

                    // Third priority: sort by max_inbox (unlimited last among same min)
                    return a.max_inbox - b.max_inbox;
                });

                // Chargebee-style auto-fix: create continuous non-overlapping ranges
                // Start from 1 for user-friendly 1-based ranges (updated per user request)
                let currentStart = 1; // Start ranges from 1 instead of 0 for better user experience
                let fixedRanges = [];

                items.forEach((item, index) => {
                    const isLastTier = index === items.length - 1;
                    const tierName = item.name || `Tier ${index + 1}`;

                    // Calculate range size preference
                    let preferredRangeSize = 10; // Default range size
                    if (item.originalMax > item.originalMin && item.originalMax !== 0) {
                        preferredRangeSize = item.originalMax - item.originalMin + 1;
                    }

                    // Ensure minimum range size of 1
                    preferredRangeSize = Math.max(preferredRangeSize, 1);

                    let tierMin = currentStart;
                    let tierMax = 0;

                    if (isLastTier && item.originalMax === 0) {
                        // Last tier and originally unlimited: keep unlimited
                        tierMax = 0;
                    } else if (isLastTier) {
                        // Last tier but was not originally unlimited: can be unlimited or have a specific end
                        tierMax = item.originalMax === 0 ? 0 : Math.max(tierMin + preferredRangeSize - 1,
                            tierMin);
                    } else {
                        // Non-last tier: must have a definite end to ensure no gaps
                        tierMax = tierMin + preferredRangeSize - 1;
                    }

                    // Store the fixed range
                    fixedRanges.push({
                        element: item.element,
                        name: tierName,
                        min: tierMin,
                        max: tierMax,
                        originalMin: item.originalMin,
                        originalMax: item.originalMax
                    });

                    // Next tier starts where this one ends + 1 (ChargeBee requirement for continuity)
                    if (tierMax !== 0) {
                        currentStart = tierMax + 1;
                    }
                });

                // Apply the fixed ranges to the form and reorder DOM elements
                let changesMessage = 'Ranges auto-fixed and ordered by ranges:\n';
                const container = $('#volumeItemsContainer');

                fixedRanges.forEach((range, index) => {
                    const beforeMin = range.originalMin;
                    const beforeMax = range.originalMax === 0 ? '∞' : range.originalMax;
                    const afterMin = range.min;
                    const afterMax = range.max === 0 ? '∞' : range.max;

                    range.element.find('.volume-min-inbox').val(range.min);
                    range.element.find('.volume-max-inbox').val(range.max);

                    // Reorder elements in DOM according to new order
                    container.append(range.element);

                    // Track changes for user feedback
                    if (beforeMin !== afterMin || range.originalMax !== range.max) {
                        changesMessage +=
                            `• ${range.name}: [${beforeMin}-${beforeMax}] → [${afterMin}-${afterMax}]\n`;
                    }
                });

                // Validate all ranges after auto-fix
                let validationPassed = true;
                $('#volumeItemsContainer .volume-item').each(function() {
                    if (!validateVolumeItemRange($(this))) {
                        validationPassed = false;
                    }
                });

                // Update tier numbers
                updateTierNumbers();

                // Show success message with details
                if (validationPassed) {
                    console.log(changesMessage);
                    showSuccessToast(
                        'Volume tier ranges auto-fixed and ordered successfully! Check console for details.');
                } else {
                    showWarningToast('Ranges were adjusted but some validation issues remain. Please review.');
                }
            }

            // Add auto-fix button functionality with enhanced validation for ChargeBee
            $(document).on('click', '#autoFixRanges', function() {
                const $button = $(this);
                const originalText = $button.text();

                // Ensure at least one tier exists
                if ($('#volumeItemsContainer .volume-item').length === 0) {
                    // Auto-create default unlimited tier without confirmation
                    addVolumeItem({
                        name: 'Unlimited Tier',
                        description: 'Unlimited volume tier',
                        min_inbox: 1,
                        max_inbox: 0, // Unlimited
                        price: 0,
                        duration: 'monthly',
                        features: [],
                        feature_values: []
                    });
                    showSuccessToast('Added default unlimited tier (1-∞)');
                    return;
                }

                // First, ensure unlimited tier exists and order tiers by range
                ensureUnlimitedTierExists();
                orderTiersByRange();

                // Check if unlimited tier exists
                let hasUnlimitedTier = false;
                $('#volumeItemsContainer .volume-item').each(function() {
                    const maxInbox = parseInt($(this).find('.volume-max-inbox').val()) || 0;
                    if (maxInbox === 0) {
                        hasUnlimitedTier = true;
                        return false; // break
                    }
                });

                // If no unlimited tier exists, create one
                if (!hasUnlimitedTier) {
                    // Find the highest max_inbox value to determine start of unlimited tier
                    let highestMax = 0;
                    $('#volumeItemsContainer .volume-item').each(function() {
                        const maxInbox = parseInt($(this).find('.volume-max-inbox').val()) || 0;
                        if (maxInbox > highestMax) {
                            highestMax = maxInbox;
                        }
                    });

                    addVolumeItem({
                        name: 'Unlimited Tier',
                        description: 'Unlimited volume tier',
                        min_inbox: highestMax + 1,
                        max_inbox: 0, // Unlimited
                        price: 0,
                        duration: 'monthly',
                        features: [],
                        feature_values: []
                    });
                    showSuccessToast(`Added unlimited tier starting from ${highestMax + 1}`);
                }

                // Collect current issues for user confirmation
                const issues = [];
                const items = [];
                $('#volumeItemsContainer .volume-item').each(function(index) {
                    const $item = $(this);
                    const minInbox = parseInt($item.find('.volume-min-inbox').val()) || 0;
                    const maxInbox = parseInt($item.find('.volume-max-inbox').val()) || 0;
                    const name = $item.find('.volume-name').val() || `Tier ${index + 1}`;

                    items.push({
                        element: $item,
                        name,
                        min_inbox: minInbox,
                        max_inbox: maxInbox
                    });

                    // Check for basic issues (using 1-based ranges)
                    if (minInbox < 1) issues.push(
                        `${name}: Invalid min value (${minInbox}) - must be 1 or higher`);
                    if (maxInbox < 0) issues.push(
                        `${name}: Invalid max value (${maxInbox}) - use 0 for unlimited`);
                    if (maxInbox !== 0 && minInbox > maxInbox) issues.push(
                        `${name}: Min > Max (${minInbox} > ${maxInbox})`);
                });

                // Check for ChargeBee-specific gaps/overlaps - sort by min_inbox, unlimited last
                const sortedItems = items.sort((a, b) => {
                    // Unlimited tiers go last
                    if (a.max_inbox === 0 && b.max_inbox !== 0) return 1;
                    if (b.max_inbox === 0 && a.max_inbox !== 0) return -1;
                    // Sort by min_inbox
                    return a.min_inbox - b.min_inbox;
                });

                // Best practice: first tier should start from 1
                if (sortedItems.length > 0 && sortedItems[0].min_inbox !== 1) {
                    issues.push(
                        `First tier should start from 1, currently starts from ${sortedItems[0].min_inbox}`
                    );
                }

                for (let i = 0; i < sortedItems.length - 1; i++) {
                    const current = sortedItems[i];
                    const next = sortedItems[i + 1];

                    // Skip gap checking if current tier is unlimited (should be last anyway)
                    if (current.max_inbox === 0) continue;

                    if (next.min_inbox !== current.max_inbox + 1) {
                        if (next.min_inbox > current.max_inbox + 1) {
                            issues.push(
                                `ChargeBee API Error: Missing tier info after ${current.max_inbox} (${current.name} → ${next.name})`
                            );
                        } else {
                            issues.push(
                                `Overlap: ${current.name} ends at ${current.max_inbox}, ${next.name} starts at ${next.min_inbox}`
                            );
                        }
                    }
                }

                if (issues.length === 0) {
                    showSuccessToast(
                        'All ranges are ChargeBee-compatible and ordered properly! No fixes needed.');
                    return;
                }

                // Auto-fix without confirmation for better UX
                $button.prop('disabled', true).text('Fixing & Ordering...');

                setTimeout(() => {
                    autoFixRanges();
                    $button.prop('disabled', false).text(originalText);
                }, 300);
            });

            // Collect volume items data
          // Collect volume items data   
function collectVolumeItems() {
    const items = [];
    const discountMode = $('#planTypeRole').val(); // ✅ Only read once

    $('#volumeItemsContainer .volume-item').each(function(index) {
        const $item = $(this);

        const itemId = $item.data('item-id') || $item.find('.volume-id').val() || null;
        const nameVal = $item.find('.volume-name').val();
        const minInboxVal = $item.find('.volume-min-inbox').val();
        const maxInboxVal = $item.find('.volume-max-inbox').val();
        const originalPriceVal = $item.find('.volume-price').val();

        const rawDiscountValue = $item.find('.tier_discount_value').val();
        const tier_discount_value = rawDiscountValue === '' || rawDiscountValue == null ? null : parseFloat(rawDiscountValue);
        const tier_discount_type = $item.find('.tier_discount_type').val() || null;

        const name = nameVal ? nameVal.trim() : '';
        const minInbox = minInboxVal === '' ? null : (parseInt(minInboxVal) || 0);
        const maxInbox = maxInboxVal === '' ? null : (parseInt(maxInboxVal) || 0);
        const originalPrice = originalPriceVal === '' ? null : (parseFloat(originalPriceVal) || 0);

        let finalPrice = originalPrice;
        const actual_price_before_discount = originalPrice;

        const priceAfterDiscountField = $(`#price_after_discount_${index}`);

        // ✅ Only apply discount if planTypeRole is "Discounted"
        if (
            discountMode === 'Discounted' &&
            tier_discount_type &&
            tier_discount_value !== null &&
            !isNaN(originalPrice)
        ) {
            if (tier_discount_type === 'percentage') {
                const discountAmount = (tier_discount_value / 100) * originalPrice;
                finalPrice = Math.max(originalPrice - discountAmount, 0);
            } else if (tier_discount_type === 'fixed') {
                finalPrice = Math.max(originalPrice - tier_discount_value, 0);
            }

            if (priceAfterDiscountField.length) {
                priceAfterDiscountField.show();
                priceAfterDiscountField.val(finalPrice.toFixed(2));
            }
        } else {
            // Not Discounted → clear price after discount field
            if (priceAfterDiscountField.length) {
                priceAfterDiscountField.hide();
                priceAfterDiscountField.val('');
            }
        }

        // Collect features and values
        const features = [];
        const featureValues = [];
        $item.find('.selected-features-list .feature-item').each(function () {
            const featureId = $(this).data('feature-id');
            const featureValue = $(this).find('.feature-value-input').val() || '';
            if (featureId) {
                features.push(featureId);
                featureValues.push(featureValue);
            }
        });

        const itemData = {
            name: name,
            description: $item.find('.volume-description').val() || '',
            min_inbox: minInbox,
            max_inbox: maxInbox,
            price: finalPrice,
            actual_price_before_discount: actual_price_before_discount,
            duration: $item.find('.volume-duration').val() || 'monthly',
            features: features,
            feature_values: featureValues,
            tier_discount_value: tier_discount_value,
            tier_discount_type: tier_discount_type
        };

        if (itemId) {
            itemData.id = itemId;
        }

        items.push(itemData);
    });

    return items;
}




            // Load features for a specific volume item
            function loadFeaturesForVolumeItem(itemIndex, selectedFeatures = [], selectedValues = []) {
                $.get('{{ route('admin.features.list') }}')
                    .done(function(response) {
                        if (response.success && response.features) {
                            const $select = $(`#featureSelect${itemIndex}`);
                            const $featuresList = $(`#selectedFeaturesList${itemIndex}`);

                            // Get all currently added feature IDs for this volume item
                            const addedFeatureIds = [];
                            $featuresList.find('.feature-item').each(function() {
                                addedFeatureIds.push($(this).data('feature-id').toString());
                            });

                            // Clear and populate feature dropdown with filtering
                            $select.empty().append('<option value="">Select a feature to add</option>');
                            response.features.forEach(function(feature) {
                                // Only add feature to dropdown if it's not already selected
                                if (!addedFeatureIds.includes(feature.id.toString())) {
                                    $select.append(
                                        `<option value="${feature.id}" data-title="${feature.title}">${feature.title}</option>`
                                    );
                                }
                            });

                            // Display already selected features with their values (only for initial load)
                            if (selectedFeatures.length > 0) {
                                selectedFeatures.forEach(function(featureId, index) {
                                    const feature = response.features.find(f => f.id == featureId);
                                    if (feature) {
                                        const featureValue = selectedValues[index] || '';
                                        addFeatureToList(itemIndex, feature.id, feature.title,
                                            featureValue);
                                    }
                                });
                            }
                        }
                    })
                    .fail(function() {
                        showErrorToast('Failed to load features');
                    });
            }

            // Load features for all volume items (similar to loadFeatures for plan modals)
            function loadAllVolumeItemFeatures() {
                $('#volumeItemsContainer .volume-item').each(function() {
                    const index = $(this).data('index');
                    if (index !== undefined) {
                        loadFeaturesForVolumeItem(index, [], []);
                    }
                });
            }

            // Add feature to the selected list
            function addFeatureToList(itemIndex, featureId, featureTitle, featureValue = '') {
                const $featuresList = $(`#selectedFeaturesList${itemIndex}`);

                // Check if feature is already added
                if ($featuresList.find(`[data-feature-id="${featureId}"]`).length > 0) {
                    showWarningToast('Feature already added to this tier');
                    return;
                }

                const featureHtml = `
                <div class="feature-item" data-feature-id="${featureId}">
                    <button type="button" class="btn btn-sm btn-danger remove-feature-btn" data-index="${itemIndex}" data-feature-id="${featureId}">
                        <i class="fa-solid fa-times"></i>
                    </button>


                    <div class="row">
                        <div class="col-md-5">
                            <strong>${featureTitle}</strong>
                            <input type="hidden" name="volume_items[${itemIndex}][feature_ids][]" value="${featureId}">
                        </div>
                        <div class="col-md-7">
                            <input type="text" class="form-control form-control-sm feature-value-input" name="volume_items[${itemIndex}][feature_values][]" value="${featureValue}" placeholder="Value">
                        </div>
                    </div>
                </div>
            `;

                $featuresList.append(featureHtml);
            }

            // Automatic feature selection on dropdown change for volume items
            $(document).on('change', '.volume-feature-dropdown', function() {
                const itemIndex = $(this).data('index');
                const selectedOption = $(this).find('option:selected');
                const selectedFeatureId = selectedOption.val();
                const selectedFeatureTitle = selectedOption.data('title');

                if (!selectedFeatureId) {
                    return; // No feature selected
                }

                // Add feature to the list automatically
                addFeatureToList(itemIndex, selectedFeatureId, selectedFeatureTitle);

                // Reset dropdown selection
                $(this).val('');

                // Refresh the dropdown to remove the selected feature from options
                loadFeaturesForVolumeItem(itemIndex, [], []);
            });

            $(document).on('click', '.remove-feature-btn', function() {
                const itemIndex = $(this).data('index');
                $(this).closest('.feature-item').remove();

                // Refresh the dropdown to add the removed feature back to options
                if (itemIndex !== undefined) {
                    loadFeaturesForVolumeItem(itemIndex, [], []);
                }
            });

            // Update feature value in real-time for volume items
            $(document).on('input', '.selected-features-list .feature-value-input', function() {
                // Real-time value updates - no additional action needed as data is collected during form submission
            });

            // Toggle new feature form visibility for volume items
            $(document).on('click', '.toggle-new-feature-form-volume', function() {
                const itemIndex = $(this).data('index');
                const formContainer = $(`#newFeatureFormVolume${itemIndex}`);

                // Toggle form visibility
                formContainer.slideToggle(300);
            });

            // Add new feature for volume items
            $(document).on('click', '.add-new-feature-btn-volume', function() {
                const itemIndex = $(this).data('index');
                const formContainer = $(`#newFeatureFormVolume${itemIndex}`);
                const titleInput = formContainer.find('.new-feature-title-volume');
                const valueInput = formContainer.find('.new-feature-value-volume');

                const title = titleInput.val().trim();
                const value = valueInput.val().trim();

                if (!title) {
                    showErrorToast('Please enter a feature title');
                    return;
                }

                // Create new feature via AJAX
                $.ajax({
                    url: "{{ route('admin.features.store') }}",
                    type: "POST",
                    data: {
                        _token: "{{ csrf_token() }}",
                        title: title,
                        is_active: true
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            const featureId = response.feature.id;

                            // Add the new feature to the volume item's feature list with value
                            addFeatureToList(itemIndex, featureId, title, value);

                            // Clear inputs
                            titleInput.val('');
                            valueInput.val('');

                            // Hide the form
                            formContainer.slideUp(300);

                            // Reload features for all volume items
                            loadAllVolumeItemFeatures();

                            showSuccessToast('New feature added successfully');
                        } else {
                            showErrorToast(response.message || 'Failed to add feature');
                        }
                    },
                    error: function(xhr) {
                        showErrorToast('Failed to add feature');
                    }
                });
            });

            // Load existing master plan data if editing
  function loadMasterPlanData(plan) {
         
    if (plan) {
        console.log('Loading existing master plan data:', plan);
        // Fill basic information with safe fallbacks
        $('#masterPlanExternalName').val(plan.external_name || '');

        // Generate internal name from external name instead of using stored value
        const generatedInternalName = generateInternalName(plan.external_name || '');
        $('#masterPlanInternalName').val(generatedInternalName);
        $('#internalNamePreview').text(generatedInternalName || 'plan_name_preview');
        $('#masterPlanDescription').val(plan.description || '');
        $('#planTypeRole').val(plan.is_discounted ? 'Discounted' : 'Without Discount');

        // Clear and add volume items
        $('#volumeItemsContainer').empty();
        volumeItemIndex = 0;

        if (plan.volume_items && plan.volume_items.length > 0) {
            plan.volume_items.forEach(function(item) {
                addVolumeItem(item);
            });
        } else {
            // Add one default tier if no volume items exist
            addVolumeItem();
        }
    } else {
        // No existing master plan, add one default tier
        addVolumeItem();
    }
}


            // Clear form when modal is hidden
            $('#masterPlanModal').on('hidden.bs.modal', function() {
                $('#masterPlanForm')[0].reset();
                $('#volumeItemsContainer').empty();
                $('#internalNamePreview').text('plan_name_preview');
                volumeItemIndex = 0;
            });

            // Load data when modal is shown
            // $('#masterPlanModal').on('show.bs.modal', function() {
            //     loadMasterPlanData();
            // });
        });



   $(document).ready(function () {
    // Toggle discount fields based on plan type
    $('#planTypeRole').on('change', function () {
        $('#volumeItemsContainer').empty(); // Clears all children inside the container
    });
});
</script>
<script>
    $(document).ready(function () {
    function recalculateVolumePrice(index) {
        console.log(`📦 Triggered field with index: ${index}`);

        const discountType = $(`#tier_discount_type_${index}`).val();
        const discountValueRaw = $(`#tier_discount_value_${index}`).val();
        const basePriceRaw = $(`#tier_volume_price_${index}`).val();

        console.log(`🔍 Index: ${index}`);
        console.log(`➡️  Discount Type: ${discountType}`);
        console.log(`➡️  Discount Value (raw): ${discountValueRaw}`);
        console.log(`➡️  Base Price (raw): ${basePriceRaw}`);

        const discountValue = parseFloat(discountValueRaw);
        const basePrice = parseFloat(basePriceRaw);

        console.log(`✅ Parsed Discount Value: ${discountValue}`);
        console.log(`✅ Parsed Base Price: ${basePrice}`);

        if (isNaN(discountValue) || isNaN(basePrice)) {
            console.warn("❌ One or more values are NaN — exiting");
            $(`#price_after_discount_${index}`).val('');
            return;
        }

        let updatedPrice = basePrice;

        if (discountType === 'percentage') {
            updatedPrice = basePrice * ((100 - discountValue) / 100);
        } else if (discountType === 'fixed') {
            updatedPrice = basePrice - discountValue;
        }

        updatedPrice = Math.max(updatedPrice, 0); // Prevent negative values
        updatedPrice = parseFloat(updatedPrice.toFixed(2));

        console.log(`✅ Updated Price: ${updatedPrice}`);

        // Show the calculated price in the separate field (not modifying base price)
        $(`#price_after_discount_${index}`).val(updatedPrice);
    }

    $(document).on('change input', '.tier_discount_type, .tier_discount_value, .tier_volume_price', function () {
        const index = $(this).data('itemindex');
        console.log(`📦 Triggered field with index: ${index}`);

        if (typeof index !== 'undefined') {
            recalculateVolumePrice(index);
        } else {
            console.warn('⚠️ No data-itemindex found on this field.');
        }
    });
});
</script>



<!-- Master Plan Modal -->
<div class="modal fade" id="masterPlanModal" tabindex="-1" aria-labelledby="masterPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header border-0" style="background-color: var(--second-primary)">
                <h5 class="modal-title" id="masterPlanModalLabel">
                    <i class="fa-solid fa-crown me-2"></i>Plan Management
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="masterPlanForm">
                    <!-- Basic Information -->
                    <div class="card mb-4 p-3">
                        <div>
                            <h6 class="mb-0 theme-text">
                                <i class="fa-solid fa-info-circle me-2"></i>Basic Information
                            </h6>
                        </div>
                        <div>
                            <div class="row">
                                <div class="col-md-12 mb-2">
                                    <div>
                                        <label for="masterPlanExternalName" class="form-label">Plan Name <span
                                                class="text-danger">*</span></label>
                                        <input type="hidden" id="masterPlanId" value="">
                                        <input type="text" class="form-control" id="masterPlanExternalName" required>
                                        <small class="opacity-50" style="display: none;">This will be shown to
                                            customers</small>
                                        <small class="text-muted d-block mt-1" style="display: none !important;">
                                            Internal name: <span id="internalNamePreview"
                                                class="text-primary">plan_name_preview</span>
                                        </small>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-2" style="display: none;">
                                    <input type="hidden" class="form-control" id="masterPlanInternalName">
                                </div>

                                <div class="col-12">
                                    <label for="masterPlanDescription" class="form-label">Description <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" id="masterPlanDescription" rows="3"
                                        required></textarea>
                                    <small class="opacity-50">Describe the master plan features and benefits</small>
                                </div>

                                <!-- Type Dropdown -->
                                <div class="col-12 mt-3">
                                    <label for="planType" class="form-label">Type <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select" id="planTypeRole" required>
                                        <option value="">-- Select Type --</option>
                                        <option value="Discounted">Discounted</option>
                                        <option value="Without Discount">Without Discount</option>

                                    </select>
                                </div>

                                <!-- Other Type Input -->
                                <div class="col-12 mt-3" id="otherTypeWrapper" style="display: none;">
                                    <label for="otherType" class="form-label">Other Type</label>
                                    <input type="text" class="form-control" id="otherType"
                                        placeholder="Enter other type">
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Tier Creation Instructions -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="theme-text mb-0"><i
                                            class="fa-solid fa-layer-group me-2"></i>Understanding Volume
                                        Tiers</h6>
                                    <small class="mb-3">Volume pricing allows you to offer different rates based on
                                        the
                                        number of inboxes. Each tier covers a specific range of inbox
                                        quantities.</small>

                                    <h6 class="mt-3"><i
                                            class="fa-solid fa-chart-line me-2 text-success"></i>Step-by-Step Guide
                                    </h6>
                                    <ol class="mb-3">
                                        <li style="font-size: 12px" class="opacity-75"><strong>Click "Add
                                                Tier"</strong> to create a new pricing tier</li>
                                        <li style="font-size: 12px" class="opacity-75"><strong>Set Min Inbox:</strong>
                                            The starting number of inboxes for this tier
                                            (e.g., 1, 11, 51)</li>
                                        <li style="font-size: 12px" class="opacity-75"><strong>Set Max Inbox:</strong>
                                            The ending number of inboxes (e.g., 10, 50,
                                            100) or 0 for unlimited</li>
                                        <li style="font-size: 12px" class="opacity-75"><strong>Set Price:</strong> The
                                            monthly price per inbox for this tier</li>
                                        <li style="font-size: 12px" class="opacity-75"><strong>Add Features:</strong>
                                            Select specific features available in this
                                            tier</li>
                                        <li style="font-size: 12px" class="opacity-75"><strong>Use Auto-Fix:</strong>
                                            Automatically order tiers and fix any range
                                            issues</li>
                                    </ol>

                                    <h6><i class="fa-solid fa-lightbulb me-2 text-warning"></i>Best Practices</h6>
                                    <ul class="mb-0">
                                        <li style="font-size: 12px" class="opacity-75"><strong>Start from 1:</strong>
                                            First tier should start from 1 inbox</li>
                                        <li style="font-size: 12px" class="opacity-75"><strong>No Gaps:</strong>
                                            Ensure continuous coverage (e.g., 1-10, 11-50,
                                            51-∞)</li>
                                        <li style="font-size: 12px" class="opacity-75"><strong>Unlimited
                                                Tier:</strong> Always include one tier with max_inbox = 0
                                            for unlimited</li>
                                        <li style="font-size: 12px" class="opacity-75"><strong>Price Scaling:</strong>
                                            Generally, price per inbox decreases as
                                            volume increases</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <div style="background-color: var(--second-primary)" class="text-white p-3 rounded">
                                        <h6 class="">
                                            <i class="fa-solid fa-calculator"></i> Example Tiers
                                        </h6>
                                        <div class="small">
                                            <div style="border-bottom: 1px solid #ffffff3d" class="pb-2 mb-2">
                                                <strong>Tier 1: Starter</strong><br>
                                                <small class="">Range:</small> <small class="opacity-50">1-10
                                                    inboxes</small><br>
                                                <small class="">Price:</small> <small
                                                    class="opacity-50">$5.00/inbox/month</small>
                                            </div>
                                            <div style="border-bottom: 1px solid #ffffff3d" class="pb-2 mb-2">
                                                <strong>Tier 2: Business</strong><br>
                                                <small class="">Range:</small> <small class="opacity-50">11-50
                                                    inboxes</small><br>
                                                <small class="">Price:</small> <small
                                                    class="opacity-50">$4.00/inbox/month</small>
                                            </div>
                                            <div style="border-bottom: 1px solid #ffffff3d" class="pb-2 mb-2">
                                                <strong>Tier 3: Enterprise</strong><br>
                                                <small class="">Range:</small> <small class="opacity-50">51-100
                                                    inboxes</small><br>
                                                <small class="">Price:</small> <small
                                                    class="opacity-50">$3.00/inbox/month</small>
                                            </div>
                                            <div>
                                                <strong>Tier 4: Unlimited</strong><br>
                                                <small class="">Range:</small> <small class="opacity-50">101-∞
                                                    inboxes</small><br>
                                                <small class="">Price:</small> <small
                                                    class="opacity-50">$2.50/inbox/month</small>
                                            </div>
                                        </div>
                                    </div>

                                    <div style="background-color: var(--second-primary)"
                                        class="text-white p-3 rounded mt-3">
                                        <h6><i class="fa-solid fa-magic me-2"></i>Auto-Fix Features</h6>
                                        <ul class="small mb-0 ps-3">
                                            <li style="font-size: 12px">Creates unlimited tier if missing</li>
                                            <li style="font-size: 12px">Orders tiers by range automatically</li>
                                            <li style="font-size: 12px">Fixes gaps and overlaps</li>
                                            <li style="font-size: 12px">Ensures ChargeBee compatibility</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-label-warning p-3 rounded-2 mt-3 mb-0">
                                <div class="row align-items-center">
                                    <div class="col-md-8 d-flex align-items-start gap-1">
                                        <div>
                                            <i class="fa-solid fa-info-circle"></i>
                                        </div>
                                        <small>
                                            <strong>Range Rules:</strong>
                                            Ranges must be continuous with no gaps. For example: 1-10, 11-50, 51-∞.
                                            The last tier should always be unlimited (max = 0) to handle any quantity.
                                        </small>
                                    </div>
                                    <!-- <div class="col-md-4 text-end">
                                                            <small class="text-muted">
                                                                <i class="fa-solid fa-clock me-1"></i>Auto-saves every change
                                                            </small>
                                                        </div> -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Volume Items -->
                    <div class="card p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="theme-text"><i class="fa-solid fa-layer-group me-2"></i>Volume Pricing Tiers
                            </h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-info me-2 text-white" id="autoFixRanges">
                                    <i class="fa-solid fa-magic"></i> Auto-Fix Ranges
                                </button>
                                <button type="button" class="btn btn-sm btn-primary border-0" id="addVolumeItem">
                                    <i class="fa-solid fa-plus"></i> Add Tier
                                </button>
                            </div>
                        </div>
                        <div class="">
                            <div id="volumeItemsContainer">
                                <!-- Volume items will be added here -->
                            </div>
                            <div style="background-color: #8d84f57a" class="mt-3 p-3 rounded-2">
                                <i class="fa-solid fa-lightbulb me-2"></i>
                                <strong>Tip:</strong> Volume pricing allows different rates based on inbox quantity. Set
                                max_inbox to 0 for unlimited.
                            </div>
                        </div>
                    </div>



                    <div class="bg-label-warning mt-3 p-3 rounded-2">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> This plan will be created on Chargebee with volume pricing type. Only one
                        master plan is allowed in the system.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm border-0" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm border-0" id="saveMasterPlan">
                    <i class="fa-solid fa-save me-2"></i>Save Plan
                </button>
            </div>
        </div>
    </div>
</div>
@endpush