@extends('customer.layouts.app')

@section('title', 'Orders')

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
</style>
@endpush

@section('content')
<!-- Update form tag to disable browser validation -->
<form id="reorderForm" novalidate>
    @csrf
    <input type="hidden" name="user_id" value="{{ auth()->id() }}">
    <input type="hidden" name="plan_id" value="{{ $plan->id ?? '' }}">

    <!-- Include Header -->
    <section class="py-3 overflow-hidden">
        @if(isset($order) && $order->reorderInfo && $order->reorderInfo->count() > 0)
        <div class="card mb-3 p-3">
            <h5>Credit Card</h5>
            <div id="card-details">Loading card details...</div>
            <div class="mt-3" id="card-button-container">
                <!-- Button will be dynamically added here -->
            </div>
        </div>
        @endif

        <div class="card p-3">
            <h5 class="mb-4">Domains & hosting platform</h5>

            <div class="mb-3">
                <label for="forwarding">Domain forwarding destination URL *</label>
                <input type="text" id="forwarding" name="forwarding_url" class="form-control" required
                    value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->forwarding_url : '' }}" />
                <div class="invalid-feedback" id="forwarding_url-error"></div>
                    send us – could be your main website, blog post, etc.)</p>
            </div>

            <div class="mb-3">
                <label for="hosting">Domain hosting platform *</label>
                <select id="hosting" name="hosting_platform" class="form-control" required>
                    @foreach($hostingPlatforms as $platform)
                        <option value="{{ $platform->value }}" 
                            data-fields='@json($platform->fields)'
                            data-requires-tutorial="{{ $platform->requires_tutorial }}"
                            data-tutorial-link="{{ $platform->tutorial_link }}"
                            {{ (optional(optional($order)->reorderInfo)->count() > 0 && $order->reorderInfo->first()->hosting_platform === $platform->value) ? ' selected' : '' }}>
                            {{ $platform->name }}
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback" id="hosting_platform-error"></div>
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
                <label for="other_platform">Please specify your other hosting platform *</label>
                <input type="text" id="other_platform" name="other_platform" class="form-control" 
                    value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->other_platform : '' }}">
                <div class="invalid-feedback" id="other-platform-error"></div>
            </div> -->

            <div id="platform-fields-container">
                <!-- Dynamic platform fields will be inserted here -->
            </div>

            <div class="mb-3">
                <label for="domains">Domains *</label>
                <textarea id="domains" name="domains" class="form-control" rows="8" required>{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->domains : '' }}</textarea>
                <div class="invalid-feedback" id="domains-error"></div>
                <small class="note">You can paste in with a comma or new line  ensure you double-check the number of domains you submit</small>
            </div>

            <div class="row g-3 mt-4">
                <h5 class="mb-2">Sending Platforms/ Sequencer</h5>

                <div class="col-md-12">
                    <label>Sending Platform</label>
                    <select id="sending_platform" name="sending_platform" class="form-control" required>
                        @foreach($sendingPlatforms as $platform)
                            <option value="{{ $platform->value }}" 
                                data-fields='@json($platform->fields)'
                                {{ (optional(optional($order)->reorderInfo)->count() > 0 && $order->reorderInfo->first()->sending_platform === $platform->value) ? ' selected' : '' }}>
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
                        <option value="1" {{ (optional(optional($order)->reorderInfo)->count() > 0 && $order->reorderInfo->first()->inboxes_per_domain == 1) ? 'selected' : '' }}>1</option>
                        <option value="2" {{ (optional(optional($order)->reorderInfo)->count() > 0 && $order->reorderInfo->first()->inboxes_per_domain == 2) ? 'selected' : '' }}>2</option>
                        <option value="3" {{ (optional(optional($order)->reorderInfo)->count() > 0 && $order->reorderInfo->first()->inboxes_per_domain == 3) ? 'selected' : '' }}>3</option>
                    </select>
                    <p class="note">(How many email accounts per domain)</p>
                </div>
                <div class="col-md-6">
                    <label>Total Inboxes</label>
                    <input type="number" name="total_inboxes" id="total_inboxes" class="form-control" readonly required 
                        value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->total_inboxes : '' }}">
                    <p class="note">(Automatically calculated based on domains and inboxes per domain)</p>
                </div>

                <div class="col-md-6">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" required 
                        value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->first_name : '' }}">
                    <div class="invalid-feedback" id="first_name-error"></div>
                    <p class="note">(First name that you wish to use on the inbox profile)</p>
                </div>

                <div class="col-md-6">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" required 
                        value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->last_name : '' }}">
                    <div class="invalid-feedback" id="last_name-error"></div>
                    <p class="note">(Last name that you wish to use on the inbox profile)</p>
                </div>

                <!-- Hidden original prefix variant fields -->
                <div class="col-md-6" style="display: none;">
                    <label>Email Persona - Prefix Variant 1</label>
                    <input type="text" name="prefix_variant_1" class="form-control" 
                        value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->prefix_variant_1 : '' }}">
                    <div class="invalid-feedback" id="prefix_variant_1-error"></div>
                </div>

                <div class="col-md-6" style="display: none;">
                    <label>Email Persona - Prefix Variant 2</label>
                    <input type="text" name="prefix_variant_2" class="form-control" 
                        value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->prefix_variant_2 : '' }}">
                    <div class="invalid-feedback" id="prefix_variant_2-error"></div>
                </div>

                <!-- Dynamic prefix variants container -->
                <div id="prefix-variants-container" class="row g-3 mt-4">
                    <!-- Dynamic prefix variant fields will be inserted here -->
                </div>

                <!-- <div class="col-md-6">
                    <label>Persona Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="persona_password" name="persona_password" class="form-control" required value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->persona_password : '' }}">
                        <div class="invalid-feedback" id="persona_password-error"></div>
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                </div> -->

                <div class="col-md-6" style="display: none;">
                    <label>Profile Picture Link</label>
                    <input type="url" name="profile_picture_link" class="form-control"
                        value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->profile_picture_link : '' }}">
                    <div class="invalid-feedback" id="profile_picture_link-error"></div>
                </div>

                <div class="col-md-6">
                    <label>Email Persona - Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="email_persona_password" name="email_persona_password" class="form-control" required value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->email_persona_password : '' }}">
                        <div class="invalid-feedback" id="email_persona_password-error"></div>
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                </div>

                <div class="col-md-6">
                    <label>Email Persona - Profile Picture Link</label>
                    <input type="url" name="email_persona_picture_link" class="form-control"
                        value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->email_persona_picture_link : '' }}">
                    <div class="invalid-feedback" id="email_persona_picture_link-error"></div>
                </div>

                <div class="col-md-6">
                    <label>Centralized master inbox email</label>
                    <input type="email" name="master_inbox_email" class="form-control" 
                        value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->master_inbox_email : '' }}">
                    <div class="invalid-feedback" id="master_inbox_email-error"></div>
                    <p class="note">(This is optional - if you want to forward all email inboxes to a
                        specific email, enter above)</p>
                </div>

                <div id="additional-assets-section">
                    <h5 class="mb-2 mt-4">Additional Assets</h5>

                    <div class="mb-3">
                        <label for="additional_info">Additional Information / Context *</label>
                        <textarea id="additional_info" name="additional_info" class="form-control" rows="8">{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->additional_info : '' }}</textarea>
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
                            $totalInboxes = optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->total_inboxes : 0;
                            $originalPrice = $plan->price * $totalInboxes;
                        @endphp
                        <div class="d-flex align-items-center gap-3 ">
                            <div>
                                <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png"
                                    width="30" alt="">
                            </div>
                            <div>
                                <span class="opacity-50">Officially Google Workspace Inboxes</span>
                                <br>
                                <span>({{ $totalInboxes }} x ${{ number_format($plan->price, 2) }} <small>/{{ $plan->duration }}) </span>
                            </div>
                        </div>
                        <h6><span class="theme-text">Original Price:</span> ${{ number_format($originalPrice, 2) }} </small></h6>
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
    function generateField(name, field, existingValue = '') {
        const fieldId = `${name}`;
        let html = `<div class="mb-3">
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

    function updatePlatformFields() {
        const selectedOption = $('#hosting option:selected');
        const fieldsData = selectedOption.data('fields');
        const requiresTutorial = selectedOption.data('requires-tutorial');
        const tutorialLink = selectedOption.data('tutorial-link');
        const platformValue = selectedOption.val();
        
        const container = $('#platform-fields-container');
        container.empty();
        
        if (fieldsData) {
            // Get existing values from the order if available
            const existingValues = @json(optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first() : null);
            
            Object.entries(fieldsData).forEach(([name, field]) => {
                const existingValue = existingValues && existingValues[name] ? existingValues[name] : '';
                container.append(generateField(name, field, existingValue));
            });
            
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
    
    // Load card details when page loads
    if($('#card-details').length) {
        loadCardDetails();
    }

    // Handle platform changes
    $('#hosting').on('change', updatePlatformFields);

    // Handle sending platform changes
    function updateSendingPlatformFields() {
        const selectedOption = $('#sending_platform option:selected');
        const fieldsData = selectedOption.data('fields');
        const container = $('#sending-platform-fields');
        container.empty();
        
        if (fieldsData) {
            const existingValues = @json(optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first() : null);
            
            Object.entries(fieldsData).forEach(([name, field]) => {
                const existingValue = existingValues && existingValues[name] ? existingValues[name] : '';
                container.append(generateField(name, field, existingValue));
            });
            
            // Reinitialize password toggles for new fields
            initializePasswordToggles();
        }
    }

    // Initial sending platform setup
    updateSendingPlatformFields();

    // Handle sending platform changes
    $('#sending_platform').on('change', updateSendingPlatformFields);

    // Form validation functions
    function validateInput(value, fieldName) {
        const items = value.split(/[\n,]+/).map(item => item.trim()).filter(item => item.length > 0);
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

        // Validate dynamic prefix variants
        const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;
        let firstErrorField = null;
        
        for (let i = 1; i <= inboxesPerDomain; i++) {
            const prefixField = $(`input[name="prefix_variants[prefix_variant_${i}]"]`);
            const value = prefixField.val()?.trim();
            
            if (i === 1 && !value) {
                // First prefix variant is required
                isValid = false;
                prefixField.addClass('is-invalid');
                prefixField.siblings('.invalid-feedback').text('This field is required');
                
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
                    
                    if (!firstErrorField) {
                        firstErrorField = prefixField;
                    }
                }
            }
        }

        // Focus on first error field if validation failed
        if (!isValid && firstErrorField) {
            firstErrorField.focus();
        }

        return isValid;
    }

    // Update form submit handler
    $('#reorderForm').on('submit', function(e) {
        e.preventDefault(); // Always prevent default form submission
        
        // // Perform HTML5 validation manually
        // if (!this.checkValidity()) {
        //     // Trigger the browser's native validation UI
        //     this.reportValidity();
        //     return false;
        // }
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

        // Specific validation for Namecheap platform
        if (selectedPlatform === 'namecheap') {
            const accessTutorial = $('#platform-field-access_tutorial').val();
            if (accessTutorial === 'no') {
                toastr.error('Please review the Namecheap access tutorial before proceeding.');
                return false;
            }
        }
        
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
            return false;
        }

        // Submit form via AJAX if all validations pass
        $.ajax({
            url: '{{ route("customer.orders.reorder.store") }}',
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
                            // Store the first error field
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

                    // Focus and scroll to the first error field
                    if (firstErrorField) {
                        firstErrorField.focus();
                        $('html, body').animate({
                            scrollTop: firstErrorField.offset().top - 100
                        }, 500);
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

    // Rest of your existing code (calculateTotalInboxes, etc.)
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

function loadCardDetails() {
    $.ajax({
        url: '{{ route("customer.plans.card-details") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            order_id: '{{ $order->id ?? '' }}'
        },
        success: function(response) {
            if (response.success) {
                const card = response.card;
                if (response.payment_sources && response.payment_sources.length > 0) {
                    let cardHtml = '';
                    response.payment_sources.forEach(source => {
                        if (source.type === 'card' && source.status === 'valid' && source.card) {
                            cardHtml += `
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="opacity-50">
                                        <strong>Card</strong> **** **** **** ${source.card.last4} – Expires ${source.card.expiry_month}/${source.card.expiry_year}
                                    </span>
                                    <button type="button" class="cancel-btn py-2 px-2 rounded-2 border-0" onclick="deletePaymentMethod('${source.id}')">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            `;
                        }
                    });
                    
                    if (cardHtml) {
                        $('#card-details').html(cardHtml);
                        // Show Change Card button when cards are available
                        $('#card-button-container').html(`
                            <button type="button" class="c-btn" onclick="updatePaymentMethod()">
                                <i class="fa-solid fa-credit-card"></i> Change Card
                            </button>
                        `);
                    } else {
                        $('#card-details').html('<span class="opacity-50">No valid card details available</span>');
                        // Show Add Card button when no valid cards
                        $('#card-button-container').html(`
                            <button type="button" class="c-btn btn-success" onclick="updatePaymentMethod()">
                                <i class="fa-solid fa-plus"></i> Add Card
                            </button>
                        `);
                    }
                } else {
                    $('#card-details').html('<span class="opacity-50">No card details available</span>');
                    // Show Add Card button when no cards
                    $('#card-button-container').html(`
                        <button type="button" class="c-btn btn-success" onclick="updatePaymentMethod()">
                            <i class="fa-solid fa-plus"></i> Add Card
                        </button>
                    `);
                }
            } else {
                $('#card-details').html(
                    '<span class="opacity-50">No card details available</span>'
                );
                // Show Add Card button when no cards
                $('#card-button-container').html(`
                    <button type="button" class="c-btn btn-success" onclick="updatePaymentMethod()">
                        <i class="fa-solid fa-plus"></i> Add Card
                    </button>
                `);
            }
        },
        error: function(xhr) {
            $('#card-details').html(
                '<span class="opacity-50">Failed to load card details</span>'
            );
        }
    });
}
function deletePaymentMethod(paymentSourceId) {
    // Use SweetAlert for confirmation
    Swal.fire({
        title: 'Are you sure?',
        text: "You are about to delete this payment method.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading state
            Swal.fire({
                title: 'Deleting...',
                text: 'Please wait while we delete your payment method.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: '{{ route("customer.plans.delete-payment-method") }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    payment_source_id: paymentSourceId,
                    order_id: '{{ $order->id ?? '' }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Your payment method has been deleted successfully.',
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        });
                        // Reload card details to update the UI
                        loadCardDetails();
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message || 'Failed to delete payment method',
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                },
                error: function(xhr) {
                    // Handle specific error for primary/only payment method
                    if (xhr.status === 400 && xhr.responseJSON && xhr.responseJSON.message) {
                        Swal.fire({
                            title: 'Warning!',
                            text: xhr.responseJSON.message,
                            icon: 'warning',
                            confirmButtonColor: '#3085d6'
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: xhr.responseJSON?.message || 'Failed to delete payment method',
                            icon: 'error',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                }
            });
        }
    });
}

function updatePaymentMethod() {
    // Show loading state
    Swal.fire({
        title: 'Loading...',
        text: 'Please wait while we prepare the payment form.',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: '{{ route("customer.plans.update-payment-method") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            order_id: '{{ $order->id ?? '' }}'
        },
        success: function(response) {
            if (response.success) {
                // Close the loading dialog
                Swal.close();
                
                // Open the payment page in a popup window
                const popupWidth = 500;
                const popupHeight = 700;
                const left = (window.innerWidth - popupWidth) / 2;
                const top = (window.innerHeight - popupHeight) / 2;
                
                const popup = window.open(
                    response.hosted_page_url,
                    'payment_method_update',
                    `width=${popupWidth},height=${popupHeight},top=${top},left=${left},resizable=yes,scrollbars=yes`
                );
                
                // Check when popup is closed
                const checkPopup = setInterval(function() {
                    if (popup.closed) {
                        clearInterval(checkPopup);
                        // Reload card details without refreshing page
                        loadCardDetails();
                        Swal.fire({
                            title: 'Success!',
                            text: 'Your payment method has been updated successfully.',
                            icon: 'success',
                            confirmButtonColor: '#3085d6'
                        });
                    }
                }, 500);
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: response.message || 'Failed to initiate payment method update',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            }
        },
        error: function(xhr) {
            Swal.fire({
                title: 'Error!',
                text: xhr.responseJSON?.message || 'Failed to initiate payment method update',
                icon: 'error',
                confirmButtonColor: '#3085d6'
            });
        }
    });
}

    // Dynamic prefix variant fields functionality
    function generatePrefixVariantFields(count) {
        const container = $('#prefix-variants-container');
        container.empty();
        
        // Get existing prefix variant values from old fields or database
        const existingPrefixVariants = @json(optional(optional($order)->reorderInfo)->first()->prefix_variants ?? []);
        
        for (let i = 1; i <= count; i++) {
            const existingValue = existingPrefixVariants[`prefix_variant_${i}`] || 
                                (i === 1 ? '{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->prefix_variant_1 : '' }}' : '') ||
                                (i === 2 ? '{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->prefix_variant_2 : '' }}' : '');
            
            const fieldHtml = `
                <div class="col-md-6">
                    <label>Email Persona - Prefix Variant ${i}</label>
                    <input type="text" name="prefix_variants[prefix_variant_${i}]" class="form-control" 
                           value="${existingValue}" ${i === 1 ? 'required' : ''}>
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

// subscribe plan function
function subscribePlan(planId) {
    $.ajax({
        url: `/customer/plans/${planId}/subscribe`,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            'order_id': '{{ $order->id ?? 0 }}'
        },
        success: function(response) {
            if (response.success) {
                // Redirect to Chargebee hosted page
                window.location.href = response.hosted_page_url;
            } else {
                // Show error message
                toastr.error(response.message || 'Failed to initiate subscription');
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to initiate subscription');
        }
    });
}
</script>
@endpush