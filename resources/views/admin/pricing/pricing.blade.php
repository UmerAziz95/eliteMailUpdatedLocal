@extends('admin.layouts.app')

@section('title', 'Pricing Plans')

@push('styles')
<style>
    .pricing-card {
        background-color: var(--secondary-color);
        box-shadow: rgba(167, 124, 252, 0.529) 0px 5px 10px 0px;
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
        box-shadow: 0px 5px 15px rgba(163, 163, 163, 0.15);
        transform: translateY(-10px);
    }

    .popular {
        position: relative;
        background: linear-gradient(270deg, rgba(89, 74, 253, 0.7) 0%, #8d84f5 100%);
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
        border: 1px solid #dee2e6;
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
</style>
@endpush

@section('content')
<section class="py-3">
    <!-- Master Plan Section -->
    <div class="container-fluid mb-4">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fa-solid fa-crown me-2"></i>Master Plan Management
                        </h5>
                    </div>
                    <div class="card-body">
                        <div id="masterPlanContainer">
                            <!-- Master plan content will be loaded here -->
                        </div>
                        @if (!auth()->user()->hasPermissionTo('Mod'))
                        @if (auth()->user()->role_id != 5)
                        <button id="createMasterPlan" class="btn btn-warning">
                            <i class="fa-solid fa-plus"></i> Create/Edit Master Plan
                        </button>
                        @endif
                        @endif
                        <!--   -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex flex-column align-items-center justify-content-center">
        <h2 class="text-center fw-bold">Manage Plans</h2>
        <p class="text-center">Create and manage subscription plans</p>
        @if (!auth()->user()->hasPermissionTo('Mod'))
        @if (auth()->user()->role_id != 5)
        <button id="addNew" data-bs-target="#addPlan" data-bs-toggle="modal" class="m-btn rounded-1 border-0 py-2 px-4">
            <i class="fa-solid fa-plus"></i> Add New Plan
        </button>
        @endif
        @endif
    </div>
    
    <div class="row mt-4" id="plans-container">
        @foreach ($plans as $plan)
        <div class="col-sm-6 col-lg-4  mb-5" id="plan-{{ $plan->id }}">
            <div class="pricing-card d-flex flex-column justify-content-between {{ $getMostlyUsed && $plan->id === $getMostlyUsed->id ? 'popular' : '' }}">
                <div>
                    <h4 class="fw-bold plan-name text-capitalize fs-5">{{ $plan->name }}</h4>
                    <h2 class="fw-bold plan-price fs-3">${{ number_format($plan->price, 2) }} <span class="fs-6 fw-normal">/{{
                        $plan->duration == 'monthly' ? 'mo' : $plan->duration }} per
                            inboxes</span>
                    </h2>
                    <p class="plan-description text-capitalize">{{ $plan->description }}</p>
                    <hr>
                    <div class="mb-2">
                        {{ $plan->min_inbox }} {{ $plan->max_inbox == 0 ? '+' : '- ' . $plan->max_inbox }}
                        <strong>Inboxes</strong>
                    </div>
                    <ul class="list-unstyled features-list">
                        @foreach ($plan->features as $feature)
                        <li class="mb-2">
                            <i class="fas fa-check text-success"></i>
                            {{ $feature->title }} {{ $feature->pivot->value }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @if (!auth()->user()->hasPermissionTo('Mod') && auth()->user()->role_id != 5)
                <div class="d-flex gap-2">
                    <button data-bs-target="#editPlan{{ $plan->id }}" data-bs-toggle="modal"
                        class="{{ $getMostlyUsed && $plan->id === $getMostlyUsed->id ? 'grey-btn' : 'm-btn' }} rounded-1 border-0 py-2 px-4 w-100">
                        <i class="fa-regular fa-pen-to-square"></i> Edit
                    </button>
                    <button type="button" class="btn btn-danger rounded-1 py-2 px-4 w-100 delete-plan-btn"
                        data-id="{{ $plan->id }}">
                        <i class="fa-regular fa-trash-can"></i> Delete
                    </button>
                </div>
                @endif

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
                                <option value="monthly" {{ $plan->duration === 'monthly' ? 'selected' : '' }}>Monthly
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
                                                name="min_inbox" value="{{ $plan->min_inbox }}" min="0" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="max_inbox{{ $plan->id }}">Max Inboxes (0 for
                                                unlimited):</label>
                                            <input type="number" class="form-control mb-3" id="max_inbox{{ $plan->id }}"
                                                name="max_inbox" value="{{ $plan->max_inbox }}" min="0" required>
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
                                            value="0" min="0" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="max_inbox">Max Inboxes (0 for unlimited):</label>
                                        <input type="number" class="form-control mb-3" id="max_inbox" name="max_inbox"
                                            value="0" min="0" required>
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

                    const container = planId === 'new' 
                        ? $('#newPlanFeatures') 
                        : $(`#selectedFeatures${planId}`);

                    let options = '<option value="">Select an existing feature</option>';

                    // Get all currently added feature IDs for this plan
                    const addedFeatureIds = [];
                    container.find('.feature-item').each(function() {
                        addedFeatureIds.push($(this).data('feature-id').toString());
                    });

                    // Filter out features that are already added to this plan
                    $.each(response.features, function(index, feature) {
                        if (!addedFeatureIds.includes(feature.id.toString())) {
                            options += `<option value="${feature.id}" data-title="${feature.title}">${feature.title}</option>`;
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
                        const container = planId === 'new' ? $('#newPlanFeatures') : $(`#selectedFeatures${planId}`);

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
            const dropdown = planId === 'new' ? $('#newPlanFeatureDropdown') : $(`#featureDropdown${planId}`);
            const selectedFeatureId = dropdown.val();
            const selectedFeatureTitle = dropdown.find('option:selected').data('title');

            if (!selectedFeatureId) {
                showErrorToast('Please select a feature first');
                return;
            }

            const container = planId === 'new' ? $('#newPlanFeatures') : $(`#selectedFeatures${planId}`);

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

                        // Refresh the page to show updated plans
                        location.reload();
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
                        showErrorToast(xhr.responseJSON?.message || 'Failed to create plan');
                    }
                    submitBtn.prop('disabled', false).html('Create Plan');
                }
            });
        });

        // Submit edit plan form
        $('.edit-plan-form').submit(function(e) {
            e.preventDefault();
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

                        showSuccessToast('Plan updated successfully');
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
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
                        showErrorToast(xhr.responseJSON?.message || 'Failed to update plan');
                    }
                    submitBtn.prop('disabled', false).html('Update Plan');
                }
            });
        });

     // Delete plan
$(document).on('click', '.delete-plan-btn', function () {
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
                success: function (response) {
                    if (response.success) {
                        $(`#plan-${planId}`).fadeOut(300, function () {
                            $(this).remove();
                        });
                        showSuccessToast('Plan deleted successfully');
                        location.reload();
                    } else {
                        showErrorToast(response.message || 'Failed to delete plan');
                        $btn.prop('disabled', false).html('Delete');
                    }
                },
                error: function () {
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

        // Master Plan Functions
        function loadMasterPlan() {
            $.get('{{ route('admin.master-plan.show') }}')
                .done(function(response) {
                    if (response && response.id) {
                        const masterPlan = response;
                        const volumeItemsCount = masterPlan.volume_items ? masterPlan.volume_items.length : 
                                               (masterPlan.volumeItems ? masterPlan.volumeItems.length : 0);
                        
                        $('#masterPlanContainer').html(`
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Plan Name:</strong> ${masterPlan.external_name || 'N/A'}
                                </div>
                                <div class="col-md-4">
                                    <strong>Chargebee ID:</strong> ${masterPlan.chargebee_plan_id || 'Not synced'}
                                </div>
                                <div class="col-md-4">
                                    <strong>Volume Items:</strong> ${volumeItemsCount} tiers
                                </div>
                                <div class="col-12 mt-2">
                                    <strong>Description:</strong> ${masterPlan.description || 'N/A'}
                                </div>
                            </div>
                        `);
                        $('#createMasterPlan').text('Edit Master Plan');
                    } else {
                        $('#masterPlanContainer').html('<p class="text-muted">No master plan created yet.</p>');
                        $('#createMasterPlan').text('Create Master Plan');
                    }
                })
                .fail(function() {
                    $('#masterPlanContainer').html('<p class="text-muted">No master plan created yet.</p>');
                    $('#createMasterPlan').text('Create Master Plan');
                });
        }

        // Load master plan on page load
        loadMasterPlan();

        // Create/Edit Master Plan
        $('#createMasterPlan').click(function() {
            // Load existing data if available
            $.get('{{ route('admin.master-plan.show') }}')
                .done(function(response) {
                    if (response && response.id) {
                        $('#masterPlanExternalName').val(response.name || '');
                        $('#masterPlanInternalName').val(response.chargebee_plan_id || response.name || '');
                        $('#masterPlanDescription').val(response.description || '');
                    }
                })
                .always(function() {
                    $('#masterPlanModal').modal('show');
                });
        });

        // Save Master Plan
        $('#saveMasterPlan').click(function() {
            const button = $(this);
            button.prop('disabled', true).text('Saving...');

            // Collect form data
            const formData = {
                external_name: $('#masterPlanExternalName').val(),
                internal_name: $('#masterPlanInternalName').val(),
                description: $('#masterPlanDescription').val(),
                volume_items: collectVolumeItems(),
                _token: '{{ csrf_token() }}'
            };

            // Validate volume items
            if (formData.volume_items.length === 0) {
                showErrorToast('At least one volume tier is required.');
                button.prop('disabled', false).text('Save Master Plan');
                return;
            }

            // Validate each volume item for valid data
            for (let i = 0; i < formData.volume_items.length; i++) {
                const item = formData.volume_items[i];
                
                // Check for NaN or invalid values
                if (isNaN(item.min_inbox) || isNaN(item.max_inbox) || isNaN(item.price)) {
                    showErrorToast(`Invalid numeric values in tier ${i + 1}. Please check min inbox, max inbox, and price fields.`);
                    button.prop('disabled', false).text('Save Master Plan');
                    return;
                }
                
                // Check for negative values
                if (item.min_inbox < 0 || item.max_inbox < 0 || item.price < 0) {
                    showErrorToast(`Negative values not allowed in tier ${i + 1}.`);
                    button.prop('disabled', false).text('Save Master Plan');
                    return;
                }
                
                // Check for empty required fields
                if (!item.name || !item.name.trim()) {
                    showErrorToast(`Tier ${i + 1} name is required.`);
                    button.prop('disabled', false).text('Save Master Plan');
                    return;
                }
            }

            // Validate and fix range sequences
            const sortedItems = formData.volume_items.sort((a, b) => a.min_inbox - b.min_inbox);
            
            // Validate range continuity and fix gaps
            for (let i = 0; i < sortedItems.length; i++) {
                const current = sortedItems[i];
                const next = sortedItems[i + 1];
                
                // Validate current tier
                if (current.min_inbox < 0 || (current.max_inbox < current.min_inbox && current.max_inbox !== 0)) {
                    showErrorToast(`Invalid range in tier ${i + 1}: min_inbox cannot be greater than max_inbox`);
                    button.prop('disabled', false).text('Save Master Plan');
                    return;
                }
                
                // Check for gaps or overlaps with next tier
                if (next) {
                    if (current.max_inbox === 0) {
                        showErrorToast(`Tier ${i + 1} has unlimited range (max_inbox = 0) but is not the last tier. Only the last tier can be unlimited.`);
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }
                    
                    // Auto-fix: ensure next tier starts where current ends + 1
                    const expectedNextMin = current.max_inbox + 1;
                    if (next.min_inbox !== expectedNextMin) {
                        showErrorToast(`Gap or overlap detected: Tier ${i + 2} should start at ${expectedNextMin} (current tier ends at ${current.max_inbox})`);
                        button.prop('disabled', false).text('Save Master Plan');
                        return;
                    }
                }
            }

            $.ajax({
                url: '{{ route('admin.master-plan.store') }}',
                method: 'POST',
                data: formData,
                dataType: 'json'
            })
                .done(function(response) {
                    if (response.success) {
                        $('#masterPlanModal').modal('hide');
                        showSuccessToast('Master plan saved successfully!');
                        // Optionally reload the page or update UI
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showErrorToast(response.message);
                    }
                })
                .fail(function(xhr) {
                    let message = 'An error occurred while saving the master plan.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                        // Handle validation errors
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        message = errors.join('<br>');
                    }
                    showErrorToast(message);
                })
                .always(function() {
                    // Re-enable button
                    button.prop('disabled', false).text('Save Master Plan');
                });
        });

        // Volume item management
        let volumeItemIndex = 0;

        // Add volume item
        $('#addVolumeItem').on('click', function() {
            addVolumeItem();
        });

        function addVolumeItem(data = null) {
            const item = data ? {
                name: data.name || '',
                description: data.description || '',
                min_inbox: data.min_inbox || '',
                max_inbox: data.max_inbox || '',
                price: data.price || '',
                duration: data.duration || 'monthly',
                features: data.features || [],
                feature_values: data.feature_values || []
            } : {
                name: '',
                description: '',
                min_inbox: '',
                max_inbox: '',
                price: '',
                duration: 'monthly',
                features: [],
                feature_values: []
            };

            const itemHtml = `
                <div class="volume-item border rounded p-3 mb-3" data-index="${volumeItemIndex}">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0">Tier ${volumeItemIndex + 1}</h6>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-volume-item">
                            <i class="fa-solid fa-trash"></i> Remove
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tier Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control volume-name" name="volume_items[${volumeItemIndex}][name]" value="${item.name}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Duration <span class="text-danger">*</span></label>
                                <select class="form-control volume-duration" name="volume_items[${volumeItemIndex}][duration]" required>
                                    <option value="monthly" ${item.duration === 'monthly' ? 'selected' : ''}>Monthly</option>
                                    <option value="yearly" ${item.duration === 'yearly' ? 'selected' : ''}>Yearly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Min Inboxes <span class="text-danger">*</span></label>
                                <input type="number" class="form-control volume-min-inbox" name="volume_items[${volumeItemIndex}][min_inbox]" value="${item.min_inbox}" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Max Inboxes <span class="text-danger">*</span></label>
                                <input type="number" class="form-control volume-max-inbox" name="volume_items[${volumeItemIndex}][max_inbox]" value="${item.max_inbox}" min="0">
                                <small class="text-muted">Set to 0 for unlimited</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Price per Inbox <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control volume-price" name="volume_items[${volumeItemIndex}][price]" value="${item.price}" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control volume-description" name="volume_items[${volumeItemIndex}][description]" rows="2">${item.description}</textarea>
                    </div>
                    
                    <!-- Features Section -->
                    <div class="mb-3">
                        <label class="form-label">Features</label>
                        <div class="features-container" id="featuresContainer${volumeItemIndex}">
                            <div class="row">
                                <div class="col-md-7">
                                    <select class="form-control feature-select volume-feature-dropdown" id="featureSelect${volumeItemIndex}" data-index="${volumeItemIndex}">
                                        <option value="">Select a feature to add</option>
                                    </select>
                                </div>
                                <div class="col-md-5" style="text-align: right;">
                                    <button type="button" class="btn btn-sm btn-primary toggle-new-feature-form-volume" data-index="${volumeItemIndex}">
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
                                        <button type="button" class="btn btn-primary add-new-feature-btn-volume" data-index="${volumeItemIndex}">
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

        // Add input validation to prevent NaN values
        $(document).on('input', '.volume-min-inbox, .volume-max-inbox', function() {
            let value = $(this).val();
            if (value === '' || isNaN(value) || value < 0) {
                $(this).val('');
            } else {
                $(this).val(parseInt(value) || '');
            }
        });

        $(document).on('input', '.volume-price', function() {
            let value = $(this).val();
            if (value === '' || isNaN(value) || value < 0) {
                $(this).val('');
            } else {
                // Ensure it's a valid decimal with max 2 decimal places
                let numValue = parseFloat(value);
                if (!isNaN(numValue)) {
                    $(this).val(numValue.toFixed(2));
                }
            }
        });

        // Remove volume item
        $(document).on('click', '.remove-volume-item', function() {
            $(this).closest('.volume-item').remove();
            updateTierNumbers();
        });

        // Update tier numbers
        function updateTierNumbers() {
            $('#volumeItemsContainer .volume-item').each(function(index) {
                $(this).find('h6').text(`Tier ${index + 1}`);
            });
        }

        // Auto-fix range sequences
        function autoFixRanges() {
            const items = [];
            $('#volumeItemsContainer .volume-item').each(function() {
                const $item = $(this);
                items.push({
                    element: $item,
                    min_inbox: parseInt($item.find('.volume-min-inbox').val()) || 0,
                    max_inbox: parseInt($item.find('.volume-max-inbox').val()) || 0
                });
            });

            // Sort by min_inbox
            items.sort((a, b) => a.min_inbox - b.min_inbox);

            // Auto-fix ranges to be continuous
            let nextMin = items[0]?.min_inbox || 1;
            items.forEach((item, index) => {
                const isLast = index === items.length - 1;
                
                // Set min_inbox for current tier
                item.element.find('.volume-min-inbox').val(nextMin);
                
                if (isLast) {
                    // Last tier can be unlimited (0) or have a specific max
                    const currentMax = item.max_inbox;
                    if (currentMax === 0) {
                        item.element.find('.volume-max-inbox').val(0);
                    } else {
                        // Keep the specified max for last tier
                        item.element.find('.volume-max-inbox').val(currentMax);
                    }
                } else {
                    // For non-last tiers, calculate max based on next tier's intended start
                    const nextItem = items[index + 1];
                    let maxForCurrent;
                    
                    if (nextItem.min_inbox > nextMin) {
                        maxForCurrent = nextItem.min_inbox - 1;
                    } else {
                        // Default range of 10 if not specified
                        maxForCurrent = nextMin + 9;
                    }
                    
                    item.element.find('.volume-max-inbox').val(maxForCurrent);
                    nextMin = maxForCurrent + 1;
                }
            });
            
            updateTierNumbers();
            showSuccessToast('Ranges have been auto-fixed to be continuous');
        }

        // Add auto-fix button functionality
        $(document).on('click', '#autoFixRanges', function() {
            if ($('#volumeItemsContainer .volume-item').length > 0) {
                autoFixRanges();
            } else {
                showErrorToast('No volume tiers to fix');
            }
        });

        // Collect volume items data
        function collectVolumeItems() {
            const items = [];
            $('#volumeItemsContainer .volume-item').each(function() {
                const $item = $(this);
                
                // Get values with proper validation
                const minInbox = parseInt($item.find('.volume-min-inbox').val()) || 0;
                const maxInboxVal = $item.find('.volume-max-inbox').val();
                const maxInbox = maxInboxVal === '' || maxInboxVal === null ? 0 : (parseInt(maxInboxVal) || 0);
                const price = parseFloat($item.find('.volume-price').val()) || 0;
                
                // Collect selected features with values for this volume item
                const features = [];
                const featureValues = [];
                $item.find('.selected-features-list .feature-item').each(function() {
                    const featureId = $(this).data('feature-id');
                    const featureValue = $(this).find('.feature-value-input').val() || '';
                    if (featureId) {
                        features.push(featureId);
                        featureValues.push(featureValue);
                    }
                });
                
                items.push({
                    name: $item.find('.volume-name').val() || '',
                    description: $item.find('.volume-description').val() || '',
                    min_inbox: minInbox,
                    max_inbox: maxInbox,
                    price: price,
                    duration: $item.find('.volume-duration').val() || 'monthly',
                    features: features,
                    feature_values: featureValues
                });
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
                                $select.append(`<option value="${feature.id}" data-title="${feature.title}">${feature.title}</option>`);
                            }
                        });
                        
                        // Display already selected features with their values (only for initial load)
                        if (selectedFeatures.length > 0) {
                            selectedFeatures.forEach(function(featureId, index) {
                                const feature = response.features.find(f => f.id == featureId);
                                if (feature) {
                                    const featureValue = selectedValues[index] || '';
                                    addFeatureToList(itemIndex, feature.id, feature.title, featureValue);
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
        function loadMasterPlanData() {
            $.get('{{ route('admin.master-plan.data') }}')
                .done(function(response) {
                    if (response.success && response.data) {
                        const plan = response.data;
                        
                        // Fill basic information with safe fallbacks
                        $('#masterPlanExternalName').val(plan.external_name || '');
                        $('#masterPlanInternalName').val(plan.chargebee_plan_id || plan.internal_name || '');
                        $('#masterPlanDescription').val(plan.description || '');
                        
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
                })
                .fail(function() {
                    // Error loading data, add one default tier
                    addVolumeItem();
                });
        }

        // Clear form when modal is hidden
        $('#masterPlanModal').on('hidden.bs.modal', function() {
            $('#masterPlanForm')[0].reset();
            $('#volumeItemsContainer').empty();
            volumeItemIndex = 0;
        });

        // Load data when modal is shown
        $('#masterPlanModal').on('show.bs.modal', function() {
            loadMasterPlanData();
        });
    });
</script>

<!-- Master Plan Modal -->
<div class="modal fade" id="masterPlanModal" tabindex="-1" aria-labelledby="masterPlanModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="masterPlanModalLabel">
                    <i class="fa-solid fa-crown me-2"></i>Master Plan Management
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="masterPlanForm">
                    <!-- Basic Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fa-solid fa-info-circle me-2"></i>Basic Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="masterPlanExternalName" class="form-label">External Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="masterPlanExternalName" required>
                                        <small class="text-muted">This will be shown to customers</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="masterPlanInternalName" class="form-label">Internal Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="masterPlanInternalName" required>
                                        <small class="text-muted">Used for internal references and Chargebee</small>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="masterPlanDescription" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="masterPlanDescription" rows="3" required></textarea>
                                <small class="text-muted">Describe the master plan features and benefits</small>
                            </div>
                        </div>
                    </div>

                    <!-- Volume Items -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fa-solid fa-layer-group me-2"></i>Volume Pricing Tiers</h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-info me-2" id="autoFixRanges">
                                    <i class="fa-solid fa-magic"></i> Auto-Fix Ranges
                                </button>
                                <button type="button" class="btn btn-sm btn-success" id="addVolumeItem">
                                    <i class="fa-solid fa-plus"></i> Add Tier
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="volumeItemsContainer">
                                <!-- Volume items will be added here -->
                            </div>
                            <div class="alert alert-info mt-3">
                                <i class="fa-solid fa-lightbulb me-2"></i>
                                <strong>Tip:</strong> Volume pricing allows different rates based on inbox quantity. Set max_inbox to 0 for unlimited.
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-warning mt-3">
                        <i class="fa-solid fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> This plan will be created on Chargebee with volume pricing type. Only one master plan is allowed in the system.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveMasterPlan">
                    <i class="fa-solid fa-save me-2"></i>Save Master Plan
                </button>
            </div>
        </div>
    </div>
</div>
@endpush