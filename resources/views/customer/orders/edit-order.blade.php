@extends('customer.layouts.app')

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
<form id="editOrderForm" novalidate>
    @csrf
    <input type="hidden" name="user_id" value="{{ auth()->id() }}">
    <input type="hidden" name="plan_id" value="{{ $plan->id ?? '' }}">
    <!-- order_id -->
    <input type="hidden" name="order_id" value="{{ isset($order) ? $order->id : '' }}">
    <input type="hidden" name="edit_id" value="{{ isset($order) && $order->reorderInfo ? $order->reorderInfo->first()->id : '' }}">

    <section class="py-3 overflow-hidden">
        <div class="card p-3">
            <h5 class="mb-4">Domains & hosting platform</h5>

            <div class="mb-3">
                <label for="forwarding">Domain forwarding destination URL *</label>
                <input type="text" id="forwarding" name="forwarding_url" class="form-control" value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->forwarding_url : '' }}" required />
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
                            data-tutorial-link="{{ $platform->tutorial_link }}"
                            {{ (optional(optional($order)->reorderInfo)->count() > 0 && $order->reorderInfo->first()->hosting_platform === $platform->value) ? ' selected' : '' }}>
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
                <textarea id="domains" name="domains" class="form-control" rows="8" required>{{ isset($order) && $order->reorderInfo ? $order->reorderInfo->first()->domains : '' }}</textarea>
                <div class="invalid-feedback" id="domains-error"></div>
                <small class="note">Please enter each domain on a new line and ensure you double-check the number of domains you submit</small>
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
                        <option value="1" {{ isset($order) && optional($order->reorderInfo)->first()->inboxes_per_domain == 1 ? 'selected' : '' }}>1</option>
                        <option value="2" {{ isset($order) && optional($order->reorderInfo)->first()->inboxes_per_domain == 2 ? 'selected' : '' }}>2</option>
                        <option value="3" {{ isset($order) && optional($order->reorderInfo)->first()->inboxes_per_domain == 3 ? 'selected' : '' }}>3</option>
                    </select>
                    <p class="note">(How many email accounts per domain - the maximum is 3)</p>
                </div>
                <div class="col-md-6">
                    <label>Total Inboxes</label>
                    <input type="number" name="total_inboxes" id="total_inboxes" class="form-control" readonly required 
                        value="{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->total_inboxes : '' }}">
                    <p class="note">(Automatically calculated based on domains and inboxes per domain)</p>
                </div>

                <div class="col-md-6">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" value="{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->first_name : '' }}" required>
                    <div class="invalid-feedback" id="first_name-error"></div>
                    <p class="note">(First name that you wish to use on the inbox profile)</p>
                </div>

                <div class="col-md-6">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" value="{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->last_name : '' }}" required>
                    <div class="invalid-feedback" id="last_name-error"></div>
                    <p class="note">(Last name that you wish to use on the inbox profile)</p>
                </div>

                <div class="col-md-6">
                    <label>Email Persona - Prefix Variant 1</label>
                    <input type="text" name="prefix_variant_1" class="form-control" value="{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->prefix_variant_1 : '' }}" required>
                    <div class="invalid-feedback" id="prefix_variant_1-error"></div>
                </div>

                <div class="col-md-6">
                    <label>Email Persona - Prefix Variant 2</label>
                    <input type="text" name="prefix_variant_2" class="form-control" value="{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->prefix_variant_2 : '' }}" required>
                    <div class="invalid-feedback" id="prefix_variant_2-error"></div>
                </div>

                <!-- <div class="col-md-6">
                    <label>Persona Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="persona_password" name="persona_password" class="form-control" value="{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->persona_password : '' }}" required>
                        <div class="invalid-feedback" id="persona_password-error"></div>
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                </div> -->

                <div class="col-md-6" style="display: none;">
                    <label>Profile Picture Link</label>
                    <input type="url" name="profile_picture_link" class="form-control" value="{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->profile_picture_link : '' }}">
                    <div class="invalid-feedback" id="profile_picture_link-error"></div>
                </div>

                <div class="col-md-6">
                    <label>Email Persona - Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="email_persona_password" name="email_persona_password" class="form-control" value="{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->email_persona_password : '' }}" required>
                        <div class="invalid-feedback" id="email_persona_password-error"></div>
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                </div>

                <div class="col-md-6">
                    <label>Email Persona - Profile Picture Link</label>
                    <input type="url" name="email_persona_picture_link" class="form-control" value="{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->email_persona_picture_link : '' }}">
                    <div class="invalid-feedback" id="email_persona_picture_link-error"></div>
                </div>

                <div class="col-md-6">
                    <label>Centralized master inbox email</label>
                    <input type="email" name="master_inbox_email" class="form-control" value="{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->master_inbox_email : '' }}">
                    <div class="invalid-feedback" id="master_inbox_email-error"></div>
                    <p class="note">(This is optional - if you want to forward all email inboxes to a
                        specific email, enter above)</p>
                </div>

                <div id="additional-assets-section">
                    <h5 class="mb-2 mt-4">Additional Assets</h5>

                    <div class="mb-3">
                        <label for="additional_info">Additional Information / Context *</label>
                        <textarea id="additional_info" name="additional_info" class="form-control" rows="8">{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->additional_info : '' }}</textarea>
                    </div>
                </div>

                <div class="col-md-6" style="display: none;">
                    <label>Coupon Code</label>
                    <input type="text" name="coupon_code" class="form-control" value="{{ isset($order) && optional($order->reorderInfo)->first() ? $order->reorderInfo->first()->coupon_code : '' }}">
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

    // Calculate total inboxes and check plan limits
    function calculateTotalInboxes() {
        const domainsText = $('#domains').val();
        const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 0;
        const submitButton = $('button[type="submit"]');
        
        // Split domains by newlines and filter out empty entries
        const domains = domainsText.split(/[\n,]+/)
            .map(domain => domain.trim())
            .filter(domain => domain.length > 0);
            
        const uniqueDomains = [...new Set(domains)];
        const totalInboxes = uniqueDomains.length * inboxesPerDomain;
        
        $('#total_inboxes').val(totalInboxes);
        
        // Get current plan details
        const currentPlan = @json($plan);
        const orderInfo = @json(optional($order)->reorderInfo->first());
        console.log(orderInfo);
        const TOTAL_INBOXES = orderInfo ? orderInfo.total_inboxes : 0;
        let priceHtml = '';
        
        if (!totalInboxes) {
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
        } 
        else if (currentPlan && totalInboxes > TOTAL_INBOXES) {
            priceHtml = `
                <div class="d-flex align-items-center gap-3 mb-4">
                    <div>
                        <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png" width="30" alt="">
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
            
            // Disable submit button and show upgrade confirmation
            submitButton.prop('disabled', true);
            submitButton.hide();
            
            Swal.fire({
                title: 'Plan Limit Exceeded',
                html: `The number of inboxes (${totalInboxes}) exceeds your current plan limit (${TOTAL_INBOXES}).<br>Would you like to upgrade your plan?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Upgrade Plan',
                cancelButtonText: 'Cancel',
                allowOutsideClick: false,
                allowEscapeKey:false,
                allowEnterKey:false,
                backdrop: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "{{ route('customer.pricing') }}";
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    // Calculate how many domains we can keep within the plan limit
                    const maxDomainsAllowed = Math.floor(TOTAL_INBOXES / inboxesPerDomain);
                    const trimmedDomains = domains.slice(0, maxDomainsAllowed);
                    
                    // Update domains field with trimmed list
                    $('#domains').val(trimmedDomains.join('\n'));
                    
                    // Recalculate totals
                    calculateTotalInboxes();
                    
                    // Show notification to user
                    toastr.info(`Domains list has been trimmed to fit within your current plan limit of ${TOTAL_INBOXES} inboxes.`);
                }
            });
        } else {
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
            
            // Enable submit button
            submitButton.prop('disabled', false);
            submitButton.show();
        }
        
        $('.price-display-section').html(priceHtml);
    }

    // Domain validation
    $('#domains').on('input', function() {
        const domainsField = $(this);
        const domains = domainsField.val().trim().split(/[\n,]+/).map(d => d.trim()).filter(d => d.length > 0);
        
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
            
            // Updated domain format validation to handle multi-level domains
            const domainRegex = /^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/;
            const domainRegexSimple = /^[a-zA-Z0-9][a-zA-Z0-9-_.]+\.[a-zA-Z]{2,}$/;
            const invalidDomains = domains.filter(d => !domainRegex.test(d) && !domainRegexSimple.test(d));
            
            if (invalidDomains.length > 0) {
                domainsField.addClass('is-invalid');
                $('#domains-error').text(`Invalid domain format: ${invalidDomains.join(', ')}`);
                return;
            }
        }
        
        // Update total inboxes calculation
        calculateTotalInboxes();
    });

    // Calculate total inboxes whenever domains or inboxes per domain changes
    $('#domains, #inboxes_per_domain').on('input change', calculateTotalInboxes);

    // Initial calculation
    calculateTotalInboxes();
    
    // Initial URL validation
    $('#forwarding').trigger('blur');

    // Form validation and submission
    $('#editOrderForm').on('submit', function(e) {
        e.preventDefault();
        
        // Reset all validations
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        let isValid = true;
        let firstErrorField = null;
        
        // Validate required fields
        $(this).find(':input[required]').each(function() {
            const field = $(this);
            const value = field.val()?.trim();
            
            if (!value) {
                isValid = false;
                field.addClass('is-invalid');
                field.siblings('.invalid-feedback').text('This field is required');
                
                if (!firstErrorField) {
                    firstErrorField = field;
                }
            }
        });
        
        // Validate email fields
        $(this).find('input[type="email"]').each(function() {
            const field = $(this);
            const value = field.val()?.trim();
            
            if (value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    isValid = false;
                    field.addClass('is-invalid');
                    field.siblings('.invalid-feedback').text('Please enter a valid email address');
                    
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
            
            if (value) {
                try {
                    new URL(value);
                } catch (_) {
                    isValid = false;
                    field.addClass('is-invalid');
                    field.siblings('.invalid-feedback').text('Please enter a valid URL (include http:// or https://)');
                    
                    if (!firstErrorField) {
                        firstErrorField = field;
                    }
                }
            }
        });
        
        // Validate domains
        const domainsField = $('#domains');
        const domains = domainsField.val().trim().split(/[\n,]+/).map(d => d.trim()).filter(d => d.length > 0);
        
        if (domains.length === 0) {
            isValid = false;
            domainsField.addClass('is-invalid');
            $('#domains-error').text('Please enter at least one domain');
            
            if (!firstErrorField) {
                firstErrorField = domainsField;
            }
        } else {
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
                $('#domains-error').text(`Duplicate domains are not allowed: ${duplicates.join(', ')}`);
                
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
                    $('#domains-error').text(`Invalid domain format: ${invalidDomains.join(', ')}`);
                    
                    if (!firstErrorField) {
                        firstErrorField = domainsField;
                    }
                }
            }
        }
        
        if (!isValid) {
            // Focus and scroll to the first error field
            if (firstErrorField) {
                // Smooth scroll to the error field
                firstErrorField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Set focus after scroll animation completes
                setTimeout(() => {
                    firstErrorField.focus();
                }, 500);
            }
            return false;
        }

        // If validation passes, submit via AJAX
        $.ajax({
            url: '{{ route("customer.orders.reorder.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    toastr.success('Order updated successfully');
                    // subscribePlan(response.plan_id);
                    window.location.href = "{{ route('customer.orders') }}";
                } else {
                    toastr.error(response.message || 'An error occurred. Please try again later.');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON.errors) {
                    // Handle validation errors from server
                    let firstErrorField = null;
                    Object.keys(xhr.responseJSON.errors).forEach(key => {
                        const field = $(`[name="${key}"]`);
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
                                field.after(`<div class="invalid-feedback">${xhr.responseJSON.errors[key][0]}</div>`);
                            } else {
                                feedbackEl.text(xhr.responseJSON.errors[key][0]);
                            }
                        }
                        toastr.error(xhr.responseJSON.errors[key][0]);
                    });
                    
                    // Focus and scroll to the first error field
                    if (firstErrorField) {
                        firstErrorField[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => {
                            firstErrorField.focus();
                        }, 500);
                    }
                } else {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred. Please try again later.');
                }
            }
        });
    });
    
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endpush