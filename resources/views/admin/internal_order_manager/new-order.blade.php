@extends('admin.layouts.app')

@section('title', 'New Order')

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
    .is-invalid + .invalid-feedback,
    .is-invalid ~ .invalid-feedback {
        display: block !important;
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

    .is-invalid + .validation-message {
        display: block;
    }
</style>
@endpush

@section('content')
<!-- new order form -->    
<form id="newOrderForm" novalidate>
    @csrf
    <input type="hidden" name="user_id" value="{{ auth()->id() }}">
    <input type="hidden" name="plan_id" value="{{ $plan->id ?? '' }}">

    <section class="py-3 overflow-hidden">
        <div class="card p-3">
            <h5 class="mb-4">Domains & hosting platform</h5>

            <div class="mb-3">
                <label for="forwarding">Domain forwarding destination URL *</label>
                <input type="text" id="forwarding" name="forwarding_url" class="form-control" required />
                <div class="invalid-feedback" id="forwarding-error"></div>
                <p class="note mb-0">(A link where you'd like to drive the traffic from the domains you
                    send us – could be your main website, blog post, etc.)</p>
            </div>

            <div class="mb-3">
                <label for="hosting">Domain hosting platform *</label>
                <select id="hosting" name="hosting_platform" class="form-control" required>
                    @foreach($hostingPlatforms as $platform)
                        <option value="{{ $platform->value }}" 
                            data-fields='@json($platform->fields)'
                            data-requires-tutorial="{{ $platform->requires_tutorial }}"
                            data-tutorial-link="{{ $platform->tutorial_link }}">
                            {{ $platform->name }}
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback" id="hosting-error"></div>
                <p class="note mb-0">(where your domains are hosted and can be accessed to modify the
                    DNS settings)</p>
            </div>

            <div id="tutorial_section" class="mb-3" style="display: none;">
                <div class="">
                    <p class="mb-0">
                        <strong>IMPORTANT</strong> - please follow the steps from this document to grant us access to your hosting account:
                        <a href="#" class="highlight-link tutorial-link" target="_blank">Click here to view tutorial</a>
                    </p>
                </div>
            </div>

            <!-- <div id="other-platform-section" class="mb-3" style="display: none;">
                <label for="other_platform">Please specify your hosting other platform *</label>
                <input type="text" id="other_platform" name="other_platform" class="form-control">
                <div class="invalid-feedback" id="other-platform-error"></div>
            </div> -->

            <div id="platform-fields-container">
                <!-- Dynamic platform fields will be inserted here -->
            </div>

            <div class="mb-3">
                <label for="domains">Domains *</label>
                <textarea id="domains" name="domains" class="form-control" rows="8" required></textarea>
                <div class="invalid-feedback" id="domains-error"></div>
                <small class="note">You can paste in with a comma or new line  ensure you double-check the number of domains you submit</small>
            </div>

            <div class="row g-3 mt-4">
                <h5 class="mb-2">Cold Email Platform</h5>
                <div class="col-md-12">
                    <label>Sending Platform</label>
                    <select id="sending_platform" name="sending_platform" class="form-control" required>
                        @foreach($sendingPlatforms as $platform)
                            <option value="{{ $platform->value }}" 
                                data-fields='@json($platform->fields)'>
                                {{ $platform->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="note">(We upload and configure the email accounts for you - its a software
                        you use to send emails)</p>
                </div>

                <div id="sending-platform-fields">
                    <!-- Dynamic sending platform fields will be inserted here -->
                </div>

                <h5 class="mb-2 mt-5">Email Account Information</h5>

                

                <div class="col-md-6">
                    <label>Inboxes per Domain</label>
                    <select name="inboxes_per_domain" id="inboxes_per_domain" class="form-control" required>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                    <p class="note">(How many email accounts per domain - the maximum is 3)</p>
                </div>
                <div class="col-md-6">
                    <label>Total Inboxes</label>
                    <input type="number" name="total_inboxes" id="total_inboxes" class="form-control" readonly required>
                    <p class="note">(Automatically calculated based on domains and inboxes per domain)</p>
                </div>

                <div class="col-md-6">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" required>
                    <div class="invalid-feedback" id="first_name-error"></div>
                    <p class="note">(First name that you wish to use on the inbox profile)</p>
                </div>

                <div class="col-md-6">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" required>
                    <div class="invalid-feedback" id="last_name-error"></div>
                    <p class="note">(Last name that you wish to use on the inbox profile)</p>
                </div>

                <!-- Hidden original prefix variant fields -->
                <div class="col-md-6" style="display: none;">
                    <label>Email Persona - Prefix Variant 1</label>
                    <input type="text" name="prefix_variant_1" class="form-control">
                    <div class="invalid-feedback" id="prefix_variant_1-error"></div>
                </div>

                <div class="col-md-6" style="display: none;">
                    <label>Email Persona - Prefix Variant 2</label>
                    <input type="text" name="prefix_variant_2" class="form-control">
                    <div class="invalid-feedback" id="prefix_variant_2-error"></div>
                </div>

                <!-- Dynamic prefix variants container -->
                <div id="prefix-variants-container" class="row g-3 mt-4">
                    <!-- Dynamic prefix variant fields will be inserted here -->
                </div>

                <!-- <div class="col-md-6">
                    <label>Persona Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="persona_password" name="persona_password" class="form-control" required>
                        <div class="invalid-feedback" id="persona_password-error"></div>
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                </div> -->

                <div class="col-md-6" style="display: none;">
                    <label>Profile Picture Link</label>
                    <input type="url" name="profile_picture_link" class="form-control">
                    <div class="invalid-feedback" id="profile_picture_link-error"></div>
                </div>

                <div class="col-md-6">
                    <label>Email Persona - Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="email_persona_password" name="email_persona_password" class="form-control" required>
                        <div class="invalid-feedback" id="email_persona_password-error"></div>
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                </div>

                <div class="col-md-6">
                    <label>Email Persona - Profile Picture Link</label>
                    <input type="url" name="email_persona_picture_link" class="form-control">
                    <div class="invalid-feedback" id="email_persona_picture_link-error"></div>
                </div>

                <div class="col-md-6">
                    <label>Centralized master inbox email</label>
                    <input type="email" name="master_inbox_email" class="form-control">
                    <div class="invalid-feedback" id="master_inbox_email-error"></div>
                    <p class="note">(This is optional - if you want to forward all email inboxes to a
                        specific email, enter above)</p>
                </div>

                <div id="additional-assets-section">
                    <h5 class="mb-2 mt-4">Additional Assets</h5>

                    <div class="mb-3">
                        <label for="additional_info">Additional Information / Context *</label>
                        <textarea id="additional_info" name="additional_info" class="form-control" rows="8"></textarea>
                    </div>
                </div>

                <div class="col-md-6" style="display: none;">
                    <label>Coupon Code</label>
                    <input type="text" name="coupon_code" class="form-control" value="">
                </div>

                <!-- Price display section --> 
                <div class="price-display-section">
                    @if(isset($plan))
                        @php
                            $totalInboxes = 0;
                            if (isset($order) && optional($order->reorderInfo)->count() > 0) {
                                $totalInboxes = $order->reorderInfo->first()->total_inboxes;
                            }
                            $originalPrice = $plan->price * $totalInboxes;
                        @endphp
                        <h6><span class="theme-text">Original Price:</span> ${{ number_format($originalPrice, 2) }} ({{ $totalInboxes }} x ${{ number_format($plan->price, 2) }} <small>/{{ $plan->duration }})</small></h6>
                        <h6><span class="theme-text">Discount:</span> 0%</h6>
                        <h6><span class="theme-text">Total:</span> ${{ number_format($originalPrice, 2) }} <small>/{{ $plan->duration }}</small></h6>
                    @else
                        <h6><span class="theme-text">Original Price:</span> <small>Price will be calculated based on selected plan</small></h6>
                        <h6><span class="theme-text">Total:</span> <small>Total will be calculated based on selected plan</small></h6>
                    @endif
                </div>

                <div>
                    <button type="submit" class="m-btn py-1 px-3 rounded-2 border-0">
                        <i class="fa-solid fa-cart-shopping"></i>
                        Purchase Accounts
                    </button>
                </div>
            </div>
        </div>
    </section>
</form>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    function generateField(name, field) {
        const fieldId = `${name}`;
        let html = `<div class="mb-3">
            <label for="${fieldId}">${field.label}${field.required ? ' *' : ''}</label>`;
            
        if (field.type === 'select' && field.options) {
            html += `<select id="${fieldId}" name="${name}" class="form-control"${field.required ? ' required' : ''}>`;
            Object.entries(field.options).forEach(([value, label]) => {
                html += `<option value="${value}">${label}</option>`;
            });
            html += '</select>';
        } else if (field.type === 'textarea') {
            html += `<textarea id="${fieldId}" name="${name}" class="form-control"${field.required ? ' required' : ''} rows="8"></textarea>`;
        } else if (field.type === 'password') {
            html += `
            <div class="password-wrapper">
                <input type="password" id="${fieldId}" name="${name}" class="form-control"${field.required ? ' required' : ''}>
                <i class="fa-regular fa-eye password-toggle"></i>
            </div>`;
        } else {
            html += `<input type="${field.type}" id="${fieldId}" name="${name}" class="form-control"${field.required ? ' required' : ''}>`;
        }
        
        if (field.note) {
            html += `<p class="note mb-0">${field.note}</p>`;
        }
        
        html += `<div class="invalid-feedback" id="${fieldId}-error"></div></div>`;
        return html;
    }

    function updatePlatformFields() {
        const selectedOption = $('#hosting option:selected');
        const fieldsData = selectedOption.data('fields');
        const requiresTutorial = selectedOption.data('requires-tutorial');
        const tutorialLink = selectedOption.data('tutorial-link');
        
        const container = $('#platform-fields-container');
        container.empty();
        
        if (fieldsData) {
            Object.entries(fieldsData).forEach(([name, field]) => {
                container.append(generateField(name, field));
            });
            
            // Reinitialize password toggles for new field
            initializePasswordToggles();
        }
        
        // Handle tutorial section visibility
        if (requiresTutorial && tutorialLink) {
            $('#tutorial_section').show();
            $('.tutorial-link').attr('href', tutorialLink);
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

    // Handle platform selection and tutorial visibility
    function updateSections() {
        const selectedPlatform = $('#hosting').find(':selected');
        const platformValue = selectedPlatform.val();
        const platformData = @json($hostingPlatforms);
        
        const platform = platformData.find(p => p.value === platformValue);
        
        // Handle tutorial section
        if (platform && platform.requires_tutorial) {
            $('#tutorial_section').show();
            $('.tutorial-link').attr('href', platform.tutorial_link);
        } else {
            $('#tutorial_section').hide();
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
    }

    // Initial check
    updateSections();

    // Handle changes
    $('#hosting').on('change', updateSections);

    // Validate other platform field
    $('#other_platform').on('input', function() {
        if ($('#hosting').val() === 'other') {
            if (!$(this).val().trim()) {
                $(this).addClass('is-invalid');
                $('#other-platform-error').text('Please specify the hosting platform');
            } else {
                $(this).removeClass('is-invalid');
                $('#other-platform-error').text('');
            }
        }
    });

    // subscribe plan
    function subscribePlan(planId) {
        $.ajax({
            url: `/customer/plans/${planId}/subscribe`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to Chargebee hosted page
                    window.location.href = response.hosted_page_url;
                } else {
                    // Show error message
                    alert(response.message || 'Failed to initiate subscription');
                }
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Failed to initiate subscription');
            }
        });
    }

    function validateInput(value, fieldName) {
        // Split by commas or newlines
        const items = value.split(/[\n,]+/).map(item => item.trim()).filter(item => item.length > 0);
        
        // Check for duplicates
        const duplicates = items.filter((item, index) => items.indexOf(item) !== index);
        
        if (duplicates.length > 0) {
            return {
                isValid: false,
                error: `The following ${fieldName} are repeated: ${duplicates.join(', ')}`
            };
        }
        
        return {
            isValid: true,
            items: items
        };
    }

    function validateField(field, fieldName) {
        if (!field) return true; // Skip validation if field doesn't exist
        const result = validateInput(field.value, fieldName);
        const errorDiv = $(`#${field.id}-error`);
        
        if (!result.isValid) {
            $(field).addClass('is-invalid');
            errorDiv.text(result.error);
            return false;
        } else {
            $(field).removeClass('is-invalid');
            errorDiv.text('');
            return true;
        }
    }

    // Validate on input
    $('#backup-codes, #domains').on('input', function() {
        validateField(this, this.id === 'backup-codes' ? 'backup codes' : 'domains');
    });

    function validateForm() {
        let isValid = true;
        const requiredFields = $('form :input[required]');
        
        // Reset all validations
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        requiredFields.each(function() {
            const field = $(this);
            const value = field.val();
            
            // Skip undefined/null values - let HTML5 handle required validation
            if (!value) {
                return;
            }

            const trimmedValue = value.trim();
            
            // Only validate non-empty fields
            if (trimmedValue) {
                // Special validation for email fields
                if (field.attr('type') === 'email') {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(trimmedValue)) {
                        isValid = false;
                        field.addClass('is-invalid');
                        field.siblings('.invalid-feedback').text('Please enter a valid email address');
                    }
                }
                
                // Special validation for URL fields
                if (field.attr('type') === 'url') {
                    try {
                        new URL(trimmedValue);
                    } catch (_) {
                        isValid = false;
                        field.addClass('is-invalid');
                        field.siblings('.invalid-feedback').text('Please enter a valid URL');
                    }
                }
            }
        });

        return isValid;
    }

    // Update form submit handler
    $('#newOrderForm').on('submit', function(e) {
        e.preventDefault(); // Always prevent default form submission
        // Perform HTML5 validation manually
        // Check form validity but use custom validation UI instead of browser's native one
        const form = this;
        let isValid = true;
        let firstInvalidField = null;
        
        // Check all required fields and show custom validation messages
        $(form).find(':input[required]').each(function() {
            const field = $(this);
            if (!this.validity.valid) {
                isValid = false;
                field.addClass('is-invalid');
                
                // Store first invalid field for focusing later
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
                
                // Find the corresponding feedback element
                const feedbackEl = field.siblings('.invalid-feedback');
                if (feedbackEl.length) {
                    // Use field-specific validation messages
                    if (this.validity.valueMissing) {
                        feedbackEl.text('This field is required');
                    } else if (this.validity.typeMismatch) {
                        feedbackEl.text(`Please enter a valid ${this.type}`);
                    } else if (this.validity.patternMismatch) {
                        feedbackEl.text('Please match the requested format');
                    } else {
                        feedbackEl.text('Please enter a valid value');
                    }
                }
            } else {
                field.removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            // Focus on the first invalid field and scroll it into view
            if (firstInvalidField) {
                firstInvalidField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalidField.focus();
            }
            return false;
        }
        
        // Only continue if our custom validation passes
        if (!validateForm()) {
            toastr.error('Please fill in all fields correctly');
            return false;
        }
        
        // Get selected platform
        const selectedPlatform = $('#hosting').val();
        
        // Handle "other" platform validation
        if (selectedPlatform === 'other') {
            const otherPlatform = $('#other_platform');
            const platformValue = otherPlatform.val();
            
            if (!platformValue || platformValue.trim() === '') {
                otherPlatform.addClass('is-invalid');
                $('#other-platform-error').text('Please specify the hosting platform');
                return false;
            }
        }
        
        // Additional domain validation
        const domainsField = $('#domains');
        const domains = domainsField.val().trim().split(/[\n,]+/).map(d => d.trim()).filter(d => d);
        
        if (domains.length === 0) {
            domainsField.addClass('is-invalid');
            $('#domains-error').text('Please enter at least one domain');
            return false;
        }
        
        // Validate domain format
        const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/;
        const invalidDomains = domains.filter(d => !domainRegex.test(d));
        if (invalidDomains.length > 0) {
            domainsField.addClass('is-invalid');
            $('#domains-error').text(`Invalid domain format: ${invalidDomains.join(', ')}`);
            return false;
        }
        
        // Check for duplicate domains
        if (new Set(domains).size !== domains.length) {
            domainsField.addClass('is-invalid');
            $('#domains-error').text('Duplicate domains are not allowed');
            return false;
        }

        // Submit form via AJAX if all validations pass
        $.ajax({
            url: '{{ route("admin.orders.reorder.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success('Order submitted successfully');
                    subscribePlan(response.plan_id);
                } else {
                    console.log(response.message);
                    toastr.error(response.message || 'An error occurred. Please try again later.');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    // Handle validation errors
                    let firstErrorField = null;
                    
                    Object.keys(xhr.responseJSON.errors).forEach(key => {
                        toastr.error(xhr.responseJSON.errors[key][0]);
                        const field = $(`[name="${key}"]`);
                        if (field.length) {
                            field.addClass('is-invalid');
                            // Track the first error field
                            if (!firstErrorField) {
                                firstErrorField = field;
                            }
                            
                            // Find the closest invalid-feedback element
                            let feedbackEl = field.siblings('.invalid-feedback');
                            if (!feedbackEl.length) {
                                // If no sibling feedback found, look for feedback after the field
                                feedbackEl = field.closest('.form-group, .mb-3').find('.invalid-feedback');
                            }
                            if (!feedbackEl.length) {
                                // If still no feedback element found, create one
                                field.after(`<div class="invalid-feedback">${xhr.responseJSON.errors[key][0]}</div>`);
                            } else {
                                feedbackEl.text(xhr.responseJSON.errors[key][0]);
                            }
                            // Ensure the feedback is displayed
                            feedbackEl.show();
                        }
                    });
                    
                    // Focus on the first error field and scroll it into view
                    if (firstErrorField) {
                        firstErrorField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => firstErrorField.focus(), 500);
                    }
                } else {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred. Please try again later.');
                }
            }
        });
    });

    // Remove real-time validation for empty fields
    $('form :input[required]').on('input', function() {
        const field = $(this);
        const value = field.val().trim();
        
        if (value) {
            // Only validate non-empty fields
            if (field.attr('type') === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    field.addClass('is-invalid');
                    field.siblings('.invalid-feedback').text('Please enter a valid email address');
                } else {
                    field.removeClass('is-invalid');
                    field.siblings('.invalid-feedback').text('');
                }
            } else if (field.attr('type') === 'url') {
                try {
                    new URL(value);
                    field.removeClass('is-invalid');
                    field.siblings('.invalid-feedback').text('');
                } catch (_) {
                    field.addClass('is-invalid');
                    field.siblings('.invalid-feedback').text('Please enter a valid URL');
                }
            }
        }
    });

    // Toggle password visibility
    $('.password-toggle').on('click', function() {
        const input = $(this).closest('.password-wrapper').find('input');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            $(this).removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            $(this).removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });

    // function calculateTotalInboxes() {
    //     const domainsText = $('#domains').val();
    //     const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 0;
    //     const submitButton = $('button[type="submit"]');
        
    //     // Split domains by commas or newlines and filter out empty entries
    //     const domains = domainsText.split(/[\n,]+/)
    //         .map(domain => domain.trim())
    //         .filter(domain => domain.length > 0);
            
    //     // Remove duplicates
    //     const uniqueDomains = [...new Set(domains)];
    //     const totalInboxes = uniqueDomains.length * inboxesPerDomain;
        
    //     $('#total_inboxes').val(totalInboxes);
        
    //     // Get current plan details
    //     const currentPlan = @json($plan);
        
    //     // Update price display
    //     if (currentPlan) {
    //         const originalPrice = parseFloat(currentPlan.price * totalInboxes).toFixed(2);
    //         let priceHtml = `
    //             <div class="d-flex align-items-center gap-3 mb-4">
    //                 <div>
    //                     <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png"
    //                         width="30" alt="">
    //                 </div>
    //                 <div>
    //                     <span class="opacity-50">Officially Google Workspace Inboxes</span>
    //                     <br>
    //                     <span>${totalInboxes} x $${parseFloat(currentPlan.price).toFixed(2)} <small>/${currentPlan.duration}</small></span>
    //                 </div>
    //             </div>
    //             <h6><span class="theme-text">Original Price:</span> $${originalPrice}</h6>
    //             <h6><span class="theme-text">Discount:</span> 0%</h6>
    //             <h6><span class="theme-text">Total:</span> $${originalPrice} <small>/${currentPlan.duration}</small></h6>
    //         `;
            
    //         // Check if total inboxes exceeds current plan limit
    //         if (currentPlan && totalInboxes > currentPlan.max_inbox && currentPlan.max_inbox !== 0) {
    //             priceHtml = `
    //                 <div class="d-flex align-items-center gap-3 mb-4">
    //                     <div>
    //                         <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png"
    //                             width="30" alt="">
    //                     </div>
    //                     <div>
    //                         <span class="opacity-50">Officially Google Workspace Inboxes</span>
    //                         <br>
    //                         <span>Configuration exceeds available limits</span>
    //                     </div>
    //                 </div>
    //                 <h6><span class="theme-text">Original Price:</span> <small class="text-danger">Please contact support for a custom solution</small></h6>
    //                 <h6><span class="theme-text">Discount:</span> 0%</h6>
    //                 <h6><span class="theme-text">Total:</span> <small class="text-danger">Configuration exceeds available limits</small></h6>
    //             `;
                
    //             // Disable submit button 
    //             submitButton.prop('disabled', true);
    //             submitButton.hide();
                
    //             // Show upgrade confirmation
    //             Swal.fire({
    //                 title: 'Plan Limit Exceeded',
    //                 html: `The number of inboxes (${totalInboxes}) exceeds your current plan limit (${currentPlan.max_inbox}).<br>Would you like to upgrade your plan?`,
    //                 icon: 'warning',
    //                 showCancelButton: true,
    //                 confirmButtonText: 'Upgrade Plan',
    //                 cancelButtonText: 'Cancel'
    //             }).then((result) => {
    //                 if (result.isConfirmed) {
    //                     window.location.href = "{{ route('customer.pricing') }}";
    //                 }
    //             });
    //         } else {
    //             // Enable submit button
    //             submitButton.prop('disabled', false);
    //             submitButton.show();
    //         }

    //         $('.price-display-section').html(priceHtml);
    //     } else {
    //         $('.price-display-section').html(`
    //             <div class="d-flex align-items-center gap-3 mb-4">
    //                 <div>
    //                     <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png"
    //                         width="30" alt="">
    //                 </div>
    //                 <div>
    //                     <span class="opacity-50">Officially Google Workspace Inboxes</span>
    //                     <br>
    //                     <span>Please add domains and inboxes to calculate price</span>
    //                 </div>
    //             </div>
    //             <h6><span class="theme-text">Original Price:</span> <small>Please add domains and inboxes to calculate price</small></h6>
    //             <h6><span class="theme-text">Discount:</span> 0%</h6>
    //             <h6><span class="theme-text">Total:</span> <small>Please add domains and inboxes to calculate price</small></h6>
    //         `);
    //     }
    // }
    function calculateTotalInboxes() {
        const domainsText = $('#domains').val();
        const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 0;
        
        // Split domains by commas or newlines and filter out empty entries
        const domains = domainsText.split(/[\n,]+/)
            .map(domain => domain.trim())
            .filter(domain => domain.length > 0);
        
        const uniqueDomains = [...new Set(domains)]; // Remove duplicates
        const totalInboxes = uniqueDomains.length * inboxesPerDomain;
        
        $('#total_inboxes').val(totalInboxes);
        
        // Get all plans and current plan details
        const plans = @json(App\Models\Plan::where('is_active', true)->orderBy('price')->get());
        const currentPlan = @json($plan);
        
        // Find suitable plan for the total inboxes
        const suitablePlan = plans.find(p => 
            (p.min_inbox <= totalInboxes && (p.max_inbox >= totalInboxes || p.max_inbox === 0))
        );
        
        // Update price display based on suitable plan availability
        let priceHtml = '';
        let planToShow = suitablePlan || currentPlan;
        
        if (!suitablePlan && totalInboxes > 0) {
            // No suitable plan exists
            priceHtml = `
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div>
                        <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png"
                            width="30" alt="">
                    </div>
                    <div>
                        <span class="opacity-50">Officially Google Workspace Inboxes</span>
                        <br>
                        <span>Configuration exceeds available limits</span>
                    </div>
                </div>
                <h6><span class="theme-text">Original Price:</span> <small class="text-danger">Please contact support for a custom solution</small></h6>
                <h6><span class="theme-text">Discount:</span> 0%</h6>
                <h6><span class="theme-text">Total:</span> <small class="text-danger">Configuration exceeds available limits</small></h6>
            `;
        } else if (planToShow && totalInboxes > 0) {
            // Show price from suitable plan if available, otherwise current plan
            const originalPrice = parseFloat(planToShow.price * totalInboxes).toFixed(2);
            priceHtml = `
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div>
                        <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png"
                            width="30" alt="">
                    </div>
                    <div>
                        <span class="opacity-50">Officially Google Workspace Inboxes</span>
                        <br>
                        <span>${totalInboxes} x $${parseFloat(planToShow.price).toFixed(2)} <small>/${planToShow.duration}</small></span>
                    </div>
                </div>
                <h6><span class="theme-text">Original Price:</span> $${originalPrice}</h6>
                <h6><span class="theme-text">Discount:</span> 0%</h6>
                <h6><span class="theme-text">Total:</span> $${originalPrice} <small>/${planToShow.duration}</small></h6>
            `;
        } else {
            priceHtml = `
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div>
                        <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png"
                            width="30" alt="">
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
        }
        
        // Update displayed price
        $('.price-display-section').html(priceHtml);
        
        // Update form's plan_id if there's a suitable plan
        if (suitablePlan) {
            $('input[name="plan_id"]').val(suitablePlan.id);
        }
    }

    // Calculate total inboxes whenever domains or inboxes per domain changes
    $('#domains, #inboxes_per_domain').on('input change', calculateTotalInboxes);

    // Initial calculation
    calculateTotalInboxes();

    // Handle sending platform changes
    function updateSendingPlatformFields() {
        const selectedOption = $('#sending_platform option:selected');
        const fieldsData = selectedOption.data('fields');
        const container = $('#sending-platform-fields');
        container.empty();
        
        if (fieldsData) {
            Object.entries(fieldsData).forEach(([name, field]) => {
                container.append(generateField(name, field));
            });
            
            // Reinitialize password toggles for new fields
            initializePasswordToggles();
        }
    }

    // Initial sending platform setup
    updateSendingPlatformFields();

    // Handle sending platform changes
    $('#sending_platform').on('change', updateSendingPlatformFields);

    // Add real-time domain validation
    $('#domains').on('input', function() {
        const domainsField = $(this);
        const domains = domainsField.val().trim().split(/[\n,;]+/).map(d => d.trim()).filter(d => d);
        
        // Reset validation state
        domainsField.removeClass('is-invalid');
        $('#domains-error').text('');
        
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
                $('#domains-error').text(`Duplicate domains are not allowed: ${duplicates.join(', ')}`);
                return;
            }
            
            // Validate domain format
            const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/;
            const invalidDomains = domains.filter(d => !domainRegex.test(d));
            if (invalidDomains.length > 0) {
                domainsField.addClass('is-invalid');
                $('#domains-error').text(`Invalid domain format: ${invalidDomains.join(', ')}`);
                return;
            }
        }
        
        // Update total inboxes calculation
        calculateTotalInboxes();
    });

    function validateField(field) {
        const $field = $(field);
        const value = $field.val();
        const fieldType = $field.attr('type');
        const fieldId = $field.attr('id');
        const fieldName = $field.attr('name');
        const $feedback = $field.siblings('.invalid-feedback');
        let isValid = true;
        let errorMessage = '';

        // Clear previous validation state
        $field.removeClass('is-invalid');
        $feedback.text('');

        // Required field validation
        if ($field.prop('required') && (!value || value.trim() === '')) {
            isValid = false;
            errorMessage = 'This field is required';
        } 
        // Email validation
        else if (fieldType === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
        }
        // URL validation
        else if (fieldType === 'url' && value) {
            try {
                new URL(value);
            } catch (_) {
                isValid = false;
                errorMessage = 'Please enter a valid URL';
            }
        }
        // Domain validation
        else if (fieldId === 'domains' && value) {
            const domains = value.split(/[\n,]+/).map(d => d.trim()).filter(d => d);
            const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/;
            const invalidDomains = domains.filter(d => !domainRegex.test(d));
            const duplicates = domains.filter((d, i) => domains.indexOf(d) !== i);
            
            if (invalidDomains.length > 0) {
                isValid = false;
                errorMessage = `Invalid domain format: ${invalidDomains.join(', ')}`;
            } else if (duplicates.length > 0) {
                isValid = false;
                errorMessage = `Duplicate domains are not allowed: ${duplicates.join(', ')}`;
            }
        }
        // Password validation
        else if (fieldType === 'password' && value) {
            if (value.length < 6) {
                isValid = false;
                errorMessage = 'Password must be at least 6 characters long';
            }
        }

        // Update validation UI
        if (!isValid) {
            $field.addClass('is-invalid');
            $feedback.text(errorMessage);
            
            // Scroll field into view if it's not visible
            if (!isElementInViewport($field[0])) {
                $field[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            
            // Focus the field
            $field.focus();
        }

        return isValid;
    }

    // Helper function to check if element is in viewport
    function isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }

    // Real-time validation on input
    $('form :input').on('input blur', function() {
        if ($(this).val()) { // Only validate if field has a value
            validateField(this);
        }
    });

    // Dynamic prefix variant fields functionality
    function generatePrefixVariantFields(count) {
        const container = $('#prefix-variants-container');
        container.empty();
        
        for (let i = 1; i <= count; i++) {
            const fieldHtml = `
                <div class="col-md-6">
                    <label>Email Persona - Prefix Variant ${i}</label>
                    <input type="text" name="prefix_variants[prefix_variant_${i}]" class="form-control" 
                           ${i === 1 ? 'required' : ''}>
                    <div class="invalid-feedback" id="prefix_variant_${i}-error"></div>
                    <p class="note">(Prefix variant ${i} for email persona)</p>
                </div>
            `;
            container.append(fieldHtml);
        }
    }

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

});
</script>
@endpush