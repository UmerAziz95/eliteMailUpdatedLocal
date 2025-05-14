@extends('customer.layouts.app')

@section('title', 'New Order')

@push('styles')
<style>
    input,
    .form-control,
    textarea,
    .form-select {
        background-color: #1e1e1e !important;
    }
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
    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }
    .is-invalid {
        border-color: #dc3545 !important;
    }
</style>
@endpush

@section('content')
<form id="newOrderForm">
    @csrf
    <input type="hidden" name="user_id" value="{{ auth()->id() }}">
    <input type="hidden" name="plan_id" value="{{ $plan->id ?? '' }}">

    <section class="py-3 overflow-hidden">
        <div class="card p-3">
            <h5 class="mb-4">Domains & hosting platform</h5>

            <div class="mb-3">
                <label for="forwarding">Domain forwarding destination URL *</label>
                <input type="text" id="forwarding" name="forwarding_url" class="form-control" required />
                <p class="note mb-0">(A link where you'd like to drive the traffic from the domains you
                    send us – could be your main website, blog post, etc.)</p>
            </div>

            <div class="mb-3">
                <label for="hosting">Domain hosting platform *</label>
                <select id="hosting" name="hosting_platform" class="form-control" required>
                    @foreach($hostingPlatforms as $platform)
                        <option value="{{ $platform->value }}">{{ $platform->name }}</option>
                    @endforeach
                </select>
                <p class="note mb-0">(where your domains are hosted and can be accessed to modify the
                    DNS settings)</p>
            </div>

            <div class="mb-3" id="tutorial-section" style="display: none;">
                <label for="tutorial">Domain Hosting Platform – Tutorial</label>
                <select id="tutorial" class="form-control">
                    <option selected>Yes – I reviewed the tutorial and am submitting the access information in requested format.</option>
                </select>
                <p class="note mb-0">
                    IMPORTANT – please follow the steps from this document to grant us access to your hosting account:
                    <a href="#" class="highlight-link tutorial-link">Tutorial Link</a><br>
                    For Domain Hosting Login please enter your username, NOT email.
                </p>
            </div>

            <div class="mb-3">
                <label for="backup-codes">Domain Hosting Platform – Namecheap – Backup Codes *</label>
                <textarea id="backup-codes" name="backup_codes" class="form-control" rows="8" required></textarea>
                <div class="invalid-feedback" id="backup-codes-error"></div>
                <small class="text-muted">Enter backup codes separated by commas or new lines</small>
            </div>

            <div class="row">
                <div class="col-6">
                    <label for="platform_login">Domain Hosting Platform – Login *</label>
                    <input type="text" id="platform_login" name="platform_login" class="form-control" required />
                </div>

                <div class="col-6">
                    <label for="platform_password">Domain Hosting Platform – Password *</label>
                    <div class="password-wrapper">
                        <input type="password" id="platform_password" name="platform_password" class="form-control" required>
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="domains">Domains *</label>
                <textarea id="domains" name="domains" class="form-control" rows="8" required></textarea>
                <div class="invalid-feedback" id="domains-error"></div>
                <small class="text-muted">Enter domains separated by commas or new lines</small>
            </div>

            <div class="row g-3 mt-4">
                <h5 class="mb-2">Sending Platforms/ Sequencer</h5>

                <div class="col-md-12">
                    <label>Sending Platform</label>
                    <select name="sending_platform" class="form-control" required>
                        <option value="Instantly">Instantly</option>
                    </select>
                    <p class="note">(We upload and configure the email accounts for you - its a software
                        you use to send emails)</p>
                </div>

                <div class="col-md-6">
                    <label>Sequencer Login</label>
                    <input type="email" name="sequencer_login" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label>Sequencer Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="sequencer_password" name="sequencer_password" class="form-control" required>
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                </div>

                <h5 class="mb-2 mt-5">Email Account Information</h5>

                <div class="col-md-6">
                    <label>Total Inboxes</label>
                    <input type="number" name="total_inboxes" id="total_inboxes" class="form-control" readonly required>
                    <p class="note">(Automatically calculated based on domains and inboxes per domain)</p>
                </div>

                <div class="col-md-6">
                    <label>Inboxes per Domain</label>
                    <input type="number" name="inboxes_per_domain" id="inboxes_per_domain" class="form-control" required min="1" value="1">
                    <p class="note">(How many email accounts per domain - the maximum is 3)</p>
                </div>

                <div class="col-md-6">
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-control" required>
                    <p class="note">(First name that you wish to use on the inbox profile)</p>
                </div>

                <div class="col-md-6">
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-control" required>
                    <p class="note">(Last name that you wish to use on the inbox profile)</p>
                </div>

                <div class="col-md-6">
                    <label>Prefix Variant 1</label>
                    <input type="text" name="prefix_variant_1" class="form-control" required>
                </div>

                <div class="col-md-6">
                    <label>Prefix Variant 2</label>
                    <input type="text" name="prefix_variant_2" class="form-control" required>
                </div>

                <!-- <div class="col-md-6">
                    <label>Persona Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="persona_password" name="persona_password" class="form-control" required>
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                </div> -->

                <div class="col-md-6" style="display: none;">
                    <label>Profile Picture Link</label>
                    <input type="url" name="profile_picture_link" class="form-control">
                </div>

                <div class="col-md-6">
                    <label>Email Persona - Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="email_persona_password" name="email_persona_password" class="form-control" required>
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                </div>

                <div class="col-md-6">
                    <label>Email Persona - Profile Picture Link</label>
                    <input type="url" name="email_persona_picture_link" class="form-control">
                </div>

                <div class="col-md-6">
                    <label>Centralized master inbox email</label>
                    <input type="email" name="master_inbox_email" class="form-control">
                    <p class="note">(This is optional - if you want to forward all email inboxes to a
                        specific email, enter above)</p>
                </div>

                <div id="additional-assets-section" style="display: none;">
                    <h5 class="mb-2 mt-4">Additional Assets</h5>

                    <div class="mb-3">
                        <label for="additional_info">Additional Information / Context *</label>
                        <textarea id="additional_info" name="additional_info" class="form-control" rows="8"></textarea>
                    </div>
                </div>

                <div class="col-md-6">
                    <label>Coupon Code</label>
                    <input type="text" name="coupon_code" class="form-control" value="">
                </div>

                <!-- Price display section -->
                <div>
                    @if(isset($plan))
                        <h6><span class="theme-text">Original Price:</span> ${{ number_format($plan->price, 2) }} <small>/{{ $plan->duration }}</small></h6>
                        <h6><span class="theme-text">Total:</span> ${{ number_format($plan->price, 2) }} <small>/{{ $plan->duration }}</small></h6>
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
    // Handle platform selection and tutorial visibility
    function updateSections() {
        const selectedPlatform = $('#hosting').find(':selected');
        const platformValue = selectedPlatform.val();
        const platformData = @json($hostingPlatforms);
        
        const platform = platformData.find(p => p.value === platformValue);
        
        // Handle tutorial section
        if (platform && platform.requires_tutorial) {
            $('#tutorial-section').show();
            $('.tutorial-link').attr('href', platform.tutorial_link);
        } else {
            $('#tutorial-section').hide();
        }

        // Handle additional assets section
        if (platformValue === 'other') {
            $('#additional-assets-section').show();
            $('#additional_info').prop('required', true);
        } else {
            $('#additional-assets-section').hide();
            $('#additional_info').prop('required', false);
        }
    }

    // Initial check
    updateSections();

    // Handle changes
    $('#hosting').on('change', updateSections);

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

    // Form submit handler
    $('#newOrderForm').on('submit', function(e) {
        e.preventDefault();
        
        // Validate both fields before submission
        const backupCodesValid = validateField(document.getElementById('backup-codes'), 'backup codes');
        const domainsValid = validateField(document.getElementById('domains'), 'domains');
        
        if (!backupCodesValid || !domainsValid) {
            return;
        }

        // Submit form with automatically updated plan_id
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
                    Object.keys(xhr.responseJSON.errors).forEach(key => {
                        toastr.error(xhr.responseJSON.errors[key][0]);
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred. Please try again later.');
                }
            }
        });
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

    function calculateTotalInboxes() {
        const domainsText = $('#domains').val();
        const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 0;
        
        // Split domains by commas or newlines and filter out empty entries
        const domains = domainsText.split(/[\n,]+/)
            .map(domain => domain.trim())
            .filter(domain => domain.length > 0);
            
        // Remove duplicates
        const uniqueDomains = [...new Set(domains)];
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
        let priceHtml, totalHtml;
        let planToShow = suitablePlan || currentPlan;
        
        if (!suitablePlan && totalInboxes > 0) {
            // No suitable plan exists
            priceHtml = `<span class="theme-text">Original Price:</span> <br><small class="text-danger">Please contact support for a custom solution</small>`;
            totalHtml = `<span class="theme-text">Total:</span> <br><small class="text-danger">Configuration exceeds available limits</small>`;
        } else {
            // Show price from suitable plan if available, otherwise current plan
            priceHtml = `<span class="theme-text">Original Price:</span> $${parseFloat(planToShow.price).toFixed(2)} <small>/${planToShow.duration}</small>`;
            totalHtml = `<span class="theme-text">Total:</span> $${parseFloat(planToShow.price).toFixed(2)} <small>/${planToShow.duration}</small>`;
        }
        
        // Update displayed price
        $('.theme-text:contains("Original Price:")').parent().html(priceHtml);
        $('.theme-text:contains("Total:")').parent().html(totalHtml);
        
        // Update form's plan_id if there's a suitable plan
        if (suitablePlan) {
            $('input[name="plan_id"]').val(suitablePlan.id);
        }
    }

    // Calculate total inboxes whenever domains or inboxes per domain changes
    $('#domains, #inboxes_per_domain').on('input change', calculateTotalInboxes);

    // Initial calculation
    calculateTotalInboxes();
});
</script>
@endpush