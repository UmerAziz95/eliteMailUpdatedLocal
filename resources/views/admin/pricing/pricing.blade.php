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
            top: 5px;
            right: 5px;
            font-size: 12px;
            padding: 2px 5px;
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
    </style>
@endpush

@section('content')
    <section class="py-3">
        <div class="d-flex flex-column align-items-center justify-content-center">
            <h2 class="text-center fw-bold">Manage Plans</h2>
            <p class="text-center">Create and manage subscription plans</p>
            @if (auth()->user()->role_id != 5)
                <button id="addNew" data-bs-target="#addPlan" data-bs-toggle="modal"
                    class="m-btn rounded-1 border-0 py-2 px-4">
                    <i class="fa-solid fa-plus"></i> Add New Plan
                </button>
            @endif
        </div>

        <div class="row mt-4" id="plans-container">
            @foreach ($plans as $plan)
                <div class="col-md-4" id="plan-{{ $plan->id }}">
                    <div class="pricing-card {{ $plan->name === 'Standard' ? 'popular' : '' }}">
                        <h4 class="fw-bold plan-name">{{ $plan->name }}</h4>
                        <h2 class="fw-bold plan-price">${{ number_format($plan->price, 2) }} <span
                                class="fs-6">/{{ $plan->duration == 'monthly' ? 'mo' : $plan->duration }} per
                                inboxes</span>
                        </h2>
                        <p class="plan-description">{{ $plan->description }}</p>
                        <hr>
                        <div class="mb-3">
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
                        @if (auth()->user()->role_id != 5)
                            <div class="d-flex gap-2">
                                <button data-bs-target="#editPlan{{ $plan->id }}" data-bs-toggle="modal"
                                    class="m-btn rounded-1 border-0 py-2 px-4 w-100">
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
                            <div class="modal-body p-5 position-relative">
                                <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                                    data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                                <div class="text-center mb-4">
                                    <h4>Edit Plan</h4>
                                </div>
                                <form id="editPlanForm{{ $plan->id }}" class="edit-plan-form"
                                    data-id="{{ $plan->id }}">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="name{{ $plan->id }}">Plan Name:</label>
                                            <input type="text" class="form-control mb-3" id="name{{ $plan->id }}"
                                                name="name" value="{{ $plan->name }}" required>

                                            <label for="chargebee_plan_id{{ $plan->id }}">Chargebee Plan ID:</label>
                                            <input type="text" class="form-control mb-3"
                                                id="chargebee_plan_id{{ $plan->id }}" name="chargebee_plan_id"
                                                value="{{ $plan->chargebee_plan_id }}">

                                            <label for="price{{ $plan->id }}">Price ($):</label>
                                            <input type="number" class="form-control mb-3" id="price{{ $plan->id }}"
                                                name="price" step="0.01" value="{{ $plan->price }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="duration{{ $plan->id }}">Duration:</label>
                                            <select class="form-control mb-3" id="duration{{ $plan->id }}"
                                                name="duration" required>
                                                <option value="monthly"
                                                    {{ $plan->duration === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                                <option value="yearly"
                                                    {{ $plan->duration === 'yearly' ? 'selected' : '' }}>Yearly</option>
                                            </select>

                                            <label for="description{{ $plan->id }}">Description:</label>
                                            <textarea class="form-control mb-3" id="description{{ $plan->id }}" name="description" rows="2">{{ $plan->description }}</textarea>
                                        </div>
                                        <div class="col-md-12">
                                            <h5 class="mt-2">Inbox Limits</h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <label for="min_inbox{{ $plan->id }}">Min Inboxes:</label>
                                                    <input type="number" class="form-control mb-3"
                                                        id="min_inbox{{ $plan->id }}" name="min_inbox"
                                                        value="{{ $plan->min_inbox }}" min="1" required>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="max_inbox{{ $plan->id }}">Max Inboxes (0 for
                                                        unlimited):</label>
                                                    <input type="number" class="form-control mb-3"
                                                        id="max_inbox{{ $plan->id }}" name="max_inbox"
                                                        value="{{ $plan->max_inbox }}" min="0" required>
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
                                                        <input type="hidden" name="feature_ids[]"
                                                            value="{{ $feature->id }}">
                                                    </div>
                                                    <div class="col-md-7">
                                                        <input type="text"
                                                            class="form-control form-control-sm feature-value-input"
                                                            name="feature_values[]" value="{{ $feature->pivot->value }}"
                                                            placeholder="Value">
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-7">
                                            <select class="form-control feature-dropdown"
                                                id="featureDropdown{{ $plan->id }}">
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
                                        <button type="submit"
                                            class="m-btn py-2 px-4 rounded-2 w-100 update-plan-btn">Update Plan</button>
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
                    <div class="modal-body p-5 position-relative">
                        <button type="button" class="modal-close-btn border-0 rounded-1 position-absolute"
                            data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
                        <div class="text-center mb-4">
                            <h4>Add New Plan</h4>
                        </div>
                        <form id="addPlanForm">
                            @csrf
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="name">Plan Name:</label>
                                    <input type="text" class="form-control mb-3" id="name" name="name"
                                        required>

                                    <label for="chargebee_plan_id">Chargebee Plan ID:</label>
                                    <input type="text" class="form-control mb-3" id="chargebee_plan_id"
                                        name="chargebee_plan_id">

                                    <label for="price">Price ($):</label>
                                    <input type="number" class="form-control mb-3" id="price" name="price"
                                        step="0.01" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="duration">Duration:</label>
                                    <select class="form-control mb-3" id="duration" name="duration" required>
                                        <option value="monthly">Monthly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>

                                    <label for="description">Description:</label>
                                    <textarea class="form-control mb-3" id="description" name="description" rows="2"></textarea>
                                </div>
                                <div class="col-md=6">
                                    <h5 class="mt-2">Inbox Limits</h5>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="min_inbox">Min Inboxes:</label>
                                            <input type="number" class="form-control mb-3" id="min_inbox"
                                                name="min_inbox" value="1" min="1" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="max_inbox">Max Inboxes (0 for unlimited):</label>
                                            <input type="number" class="form-control mb-3" id="max_inbox"
                                                name="max_inbox" value="0" min="0" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <h5 class="mt-4">Features</h5>
                            <div class="selected-features-container" id="newPlanFeatures">
                                <!-- Selected features will be added dynamically -->
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-7">
                                    <select class="form-control feature-dropdown" id="newPlanFeatureDropdown">
                                        <option  value="">Select an existing feature</option>
                                        <!-- Will be populated via AJAX -->
                                    </select>
                                </div>
                                <div class="col-md-5" style="text-align: right;">
                                    <button type="button" class="btn btn-secondary add-selected-feature"
                                        data-plan-id="new">
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

            // Load features for dropdowns
            function loadFeatures() {
                $.ajax({
                    url: "{{ route('admin.features.list') }}",
                    type: "GET",
                    dataType: "json",
                    success: function(response) {
                        if (response.success) {
                            // Populate all feature dropdowns with filtering for each plan
                            $('.feature-dropdown').each(function() {
                                const dropdown = $(this);
                                let planId = dropdown.attr('id').replace('featureDropdown', '');
                                
                                // Handle the special case for new plan dropdown
                                if (dropdown.attr('id') === 'newPlanFeatureDropdown') {
                                    planId = 'new';
                                }
                                
                                const container = planId === 'new' ? $('#newPlanFeatures') : $(`#selectedFeatures${planId}`);
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
                        }
                    },
                    error: function(xhr) {
                        showErrorToast('Failed to load features');
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
                            showErrorToast('Failed to create plan');
                        }
                    }
                });
            });

            // Submit edit plan form
            $('.edit-plan-form').submit(function(e) {
                e.preventDefault();

                const planId = $(this).data('id');

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
                        } else {
                            showErrorToast(response.message || 'Failed to update plan');
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
                            showErrorToast('Failed to update plan');
                        }
                    }
                });
            });

            // Delete plan
            $(document).on('click', '.delete-plan-btn', function() {
                // Get plan ID from data attribute
                if (!$(this).data('id')) {
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
                        // Perform the confirmed action here
                        const planId = $(this).data('id');

                        $.ajax({
                            url: `/admin/plans/${planId}`,
                            type: "DELETE",
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            dataType: "json",
                            success: function(response) {
                                if (response.success) {
                                    // Remove plan card
                                    $(`#plan-${planId}`).fadeOut(300, function() {
                                        $(this).remove();
                                    });

                                    showSuccessToast('Plan deleted successfully');
                                } else {
                                    showErrorToast(response.message ||
                                        'Failed to delete plan');
                                }
                            },
                            error: function(xhr) {
                                showErrorToast('Failed to delete plan');
                            }
                        });
                    } else {
                        console.log('User cancelled');
                    }
                });


                // sweetalert confirmation



            });

            // Helper function to update plan card after edit
            function updatePlanCard(plan) {
                const card = $(`#plan-${plan.id}`);
                card.find('.plan-name').text(plan.name);
                card.find('.plan-price').html(
                    `$${Number(plan.price).toFixed(2)} <span class="fs-6">/${plan.duration == 'monthly' ? 'mo':plan.duration}</span>`
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
        });
    </script>
@endpush
