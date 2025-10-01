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

    /* Enhanced styling for domains error */
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
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
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

    /* Form section styling */
    .form-section {
        background-color: #ffffff04;
        border: 1px solid #404040;
        border-radius: 12px;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
        box-shadow: rgba(125, 125, 186, 0.109) 0px 50px 100px -20px, rgb(0, 0, 0) 0px 30px 60px -20px, rgba(173, 173, 173, 0) 0px -2px 6px 0px inset;
    }

    .form-section h5 {
        color: #667eea;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .note {
        font-size: 0.875rem;
        color: #888;
        margin-top: 0.25rem;
    }

    .required-asterisk {
        color: #dc3545;
    }

    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        border-radius: 8px;
        padding: 0.75rem 2rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
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

    /* Progress bar styling */
    .progress {
        background: linear-gradient(135deg, #f5f5f5, #eaeaea);
        border-radius: 6px;
    }

    .progress-bar {
        background: linear-gradle(45deg, #28a745, #20c997);
        border-radius: 6px;
    }

    /* Tutorial section styling */
    #tutorial_section {
        background-color: rgba(102, 126, 234, 0.1);
        border: 1px solid rgba(102, 126, 234, 0.2);
        border-radius: 8px;
        padding: 1rem;
    }

    .tutorial-link {
        color: #667eea;
        text-decoration: none;
        font-weight: 600;
    }

    .tutorial-link:hover {
        color: #5a6fd8;
        text-decoration: underline;
    }

    /* Prefix variant section styling */
    .prefix-variant-section .card {
        background-color: #ffffff04;
        border: 1px solid #404040;
        border-radius: 12px;
        transition: all 0.3s ease;
        box-shadow: rgba(125, 125, 186, 0.109) 0px 50px 100px -20px, rgb(0, 0, 0) 0px 30px 60px -20px, rgba(173, 173, 173, 0) 0px -2px 6px 0px inset;
    }
</style>
@endpush
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ isset($pool) ? 'Edit Pool #' . $pool->id : 'Create New Pool' }}</h2>
            <p class="text-muted mb-0">{{ isset($pool) ? 'Update pool information' : 'Fill in the details to create a new pool' }}</p>
        </div>
        <a href="{{ route('admin.pools.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Pools
        </a>
    </div>

    <form id="poolForm" method="POST" action="{{ isset($pool) ? route('admin.pools.update', $pool) : route('admin.pools.store') }}" novalidate>
        @csrf
        @if(isset($pool))
            @method('PUT')
        @endif

        <!-- Basic Information Section -->
        <div class="form-section">
            <h5><i class="fas fa-info-circle me-2"></i>Basic Information</h5>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="user_id" class="form-label">Customer <span class="required-asterisk">*</span></label>
                    <select id="user_id" name="user_id" class="form-select" required>
                        <option value="">Select Customer</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (isset($pool) && $pool->user_id == $user->id) ? 'selected' : '' }}>
                                {{ $user->name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="user_id-error"></div>
                </div>

                <div class="col-md-6">
                    <label for="plan_id" class="form-label">Plan</label>
                    <select id="plan_id" name="plan_id" class="form-select">
                        <option value="">Select Plan</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ (isset($pool) && $pool->plan_id == $plan->id) ? 'selected' : '' }}>
                                {{ $plan->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="plan_id-error"></div>
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label">Status <span class="required-asterisk">*</span></label>
                    <select id="status" name="status" class="form-select" required>
                        <option value="pending" {{ (isset($pool) && $pool->status == 'pending') ? 'selected' : '' }}>Pending</option>
                        <option value="in_progress" {{ (isset($pool) && $pool->status == 'in_progress') ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ (isset($pool) && $pool->status == 'completed') ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ (isset($pool) && $pool->status == 'cancelled') ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <div class="invalid-feedback" id="status-error"></div>
                </div>

                <div class="col-md-4">
                    <label for="amount" class="form-label">Amount</label>
                    <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0"
                           value="{{ isset($pool) ? $pool->amount : '' }}" placeholder="0.00">
                    <div class="invalid-feedback" id="amount-error"></div>
                </div>

                <div class="col-md-4">
                    <label for="currency" class="form-label">Currency</label>
                    <select id="currency" name="currency" class="form-select">
                        <option value="USD" {{ (isset($pool) && $pool->currency == 'USD') ? 'selected' : '' }}>USD</option>
                        <option value="EUR" {{ (isset($pool) && $pool->currency == 'EUR') ? 'selected' : '' }}>EUR</option>
                        <option value="GBP" {{ (isset($pool) && $pool->currency == 'GBP') ? 'selected' : '' }}>GBP</option>
                    </select>
                    <div class="invalid-feedback" id="currency-error"></div>
                </div>
            </div>
        </div>

        <!-- Domain & Platform Information -->
        <div class="form-section">
            <h5><i class="fas fa-globe me-2"></i>Domain & Platform Information</h5>
            
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="forwarding_url" class="form-label">Domain Forwarding URL <span class="required-asterisk">*</span></label>
                    <input type="url" id="forwarding_url" name="forwarding_url" class="form-control" required
                           value="{{ isset($pool) ? $pool->forwarding_url : '' }}" 
                           placeholder="https://example.com">
                    <div class="invalid-feedback" id="forwarding_url-error"></div>
                    <p class="note">This is where prospects will land if they visit one of your domains. Please enter a full URL (e.g. https://yourdomain.com).</p>
                </div>

                <div class="col-md-6">
                    <label for="hosting_platform" class="form-label">Hosting Platform <span class="required-asterisk">*</span></label>
                    <select id="hosting_platform" name="hosting_platform" class="form-select" required>
                        <option value="">Select hosting platform...</option>
                        @foreach($hostingPlatforms as $platform)
                            <option value="{{ $platform->value }}" 
                                    data-fields='@json($platform->fields)'
                                    data-requires-tutorial="{{ $platform->requires_tutorial }}"
                                    data-tutorial-link="{{ $platform->tutorial_link }}"
                                    data-import-note="{{ $platform->import_note ?? '' }}"
                                    {{ (isset($pool) && $pool->hosting_platform === $platform->value) ? 'selected' : '' }}>
                                {{ $platform->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="hosting_platform-error"></div>
                    <p class="note">Where your domains are hosted and can be accessed to modify the DNS settings.</p>
                </div>

                <div class="col-md-6">
                    <label for="sending_platform" class="form-label">Sending Platform <span class="required-asterisk">*</span></label>
                    <select id="sending_platform" name="sending_platform" class="form-select" required>
                        <option value="">Select sending platform...</option>
                        @foreach($sendingPlatforms as $platform)
                            <option value="{{ $platform->value }}" 
                                    data-fields='@json($platform->fields)'
                                    {{ (isset($pool) && $pool->sending_platform === $platform->value) ? 'selected' : '' }}>
                                {{ $platform->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="sending_platform-error"></div>
                    <p class="note">Please select the cold email platform you would like us to install the inboxes on. To avoid any delays, ensure it isn't on a free trial and that your chosen paid plan is active.</p>
                </div>

                <!-- Tutorial Section (hidden by default) -->
                <div id="tutorial_section" class="col-md-12" style="display: none;">
                    <div class="mb-0">
                        <p class="mb-0" id="hosting-platform-import-note">
                            <!-- Hosting platform import note will be dynamically inserted here -->
                            <a href="#" class="tutorial-link" target="_blank">Click here to view tutorial</a>
                        </p>
                    </div>
                </div>

                <!-- Dynamic Platform Fields Container -->
                <div class="platform col-md-12" id="platform-fields-container">
                    <!-- Dynamic platform fields will be inserted here -->
                </div>

                <!-- Dynamic Sending Platform Fields Container -->
                <div class="sending-platform-fields col-md-12" id="sending-platform-fields">
                    <!-- Dynamic sending platform fields will be inserted here -->
                </div>

                <div class="col-md-12">
                    <label for="domains" class="form-label">
                        Domains <span class="required-asterisk">*</span>
                        <span class="badge bg-primary ms-2" id="domain-count-badge">0 domains</span>
                    </label>
                    <textarea id="domains" name="domains" class="form-control" rows="6" required
                              placeholder="Enter domains, one per line">{{ isset($pool) && $pool->domains ? implode("\n", $pool->domains) : '' }}</textarea>
                    <div class="invalid-feedback" id="domains-error"></div>
                    <small class="note">
                        Please enter each domain on a new line and ensure you double-check the number of domains you submit
                        <br>
                        <span class="text-info" style="display: none;">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            Total domains: <strong id="domain-count-text">0</strong>
                        </span>
                    </small>
                </div>
            </div>
        </div>

        <!-- Inbox Configuration -->
        <div class="form-section">
            <h5><i class="fas fa-envelope me-2"></i>Inbox Configuration</h5>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="inboxes_per_domain" class="form-label">Inboxes Per Domain / Prefix Variant <span class="required-asterisk">*</span></label>
                    <select name="inboxes_per_domain" id="inboxes_per_domain" class="form-select" required>
                        <option value="1" {{ isset($pool) && $pool->inboxes_per_domain == 1 ? 'selected' : '' }}>1</option>
                        <option value="2" {{ isset($pool) && $pool->inboxes_per_domain == 2 ? 'selected' : '' }}>2</option>
                        <option value="3" {{ isset($pool) && $pool->inboxes_per_domain == 3 ? 'selected' : '' }}>3</option>
                    </select>
                    <div class="invalid-feedback" id="inboxes_per_domain-error"></div>
                    <p class="note">How many email accounts you would like us to create per domain - the maximum is 3.</p>
                </div>

                <div class="col-md-6">
                    <label for="total_inboxes" class="form-label">Total Inboxes</label>
                    <input type="number" id="total_inboxes" name="total_inboxes" class="form-control" readonly required
                           value="{{ isset($pool) ? $pool->total_inboxes : '' }}" placeholder="0">
                    <div class="invalid-feedback" id="total_inboxes-error"></div>
                    <p class="note">Automatically calculated based on domains and inboxes per domain.</p>
                </div>

                <div class="col-md-12">
                    <label for="master_inbox_confirmation" class="form-label">Do you want to enable domain forwarding?</label>
                    <select name="master_inbox_confirmation" id="master_inbox_confirmation" class="form-select">
                        <option value="0" {{ isset($pool) && !$pool->master_inbox_confirmation ? 'selected' : (!isset($pool) ? 'selected' : '') }}>No</option>
                        <option value="1" {{ isset($pool) && $pool->master_inbox_confirmation ? 'selected' : '' }}>Yes</option>
                    </select>
                    <div class="invalid-feedback" id="master_inbox_confirmation-error"></div>
                    <p class="note">Choose "Yes" if you want to forward all email inboxes to a specific email.</p>
                </div>

                <div class="col-md-12 master-inbox-email" style="display: {{ isset($pool) && $pool->master_inbox_confirmation ? 'block' : 'none' }};">
                    <label for="master_inbox_email" class="form-label">Master Domain Email <span class="required-asterisk">*</span></label>
                    <input type="email" name="master_inbox_email" id="master_inbox_email" class="form-control"
                           value="{{ isset($pool) ? $pool->master_inbox_email : '' }}" 
                           placeholder="master@example.com"
                           {{ isset($pool) && $pool->master_inbox_confirmation ? 'required' : '' }}>
                    <div class="invalid-feedback" id="master_inbox_email-error"></div>
                    <p class="note">Enter the main email where all messages should be forwarded.</p>
                </div>

                <div class="col-md-12">
                    <label for="initial_total_inboxes" class="form-label">Initial Total Inboxes</label>
                    <input type="number" id="initial_total_inboxes" name="initial_total_inboxes" class="form-control" min="1"
                           value="{{ isset($pool) ? $pool->initial_total_inboxes : '' }}" placeholder="0">
                    <div class="invalid-feedback" id="initial_total_inboxes-error"></div>
                    <p class="note">Initial number of inboxes to start with.</p>
                </div>
            </div>
        </div>

        <!-- Dynamic Prefix Variants Container -->
        <div id="prefix-variants-container" class="form-section prefix-variants" style="display: none;">
            <h5><i class="fas fa-users me-2"></i>Email Persona - Prefix Variants</h5>
            <div class="row g-3" id="prefix-variants-fields">
                <!-- Dynamic prefix variant fields will be inserted here -->
            </div>
        </div>

        <!-- Personal Information -->
        <div class="form-section">
            <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
            
            <div class="row g-3">
                <div class="col-md-6 first-name" style="display: none;">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control"
                           value="{{ isset($pool) ? $pool->first_name : '' }}" placeholder="John">
                    <div class="invalid-feedback" id="first_name-error"></div>
                    <p class="note">First name that you wish to use on the inbox profile.</p>
                </div>

                <div class="col-md-6 last-name" style="display: none;">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control"
                           value="{{ isset($pool) ? $pool->last_name : '' }}" placeholder="Doe">
                    <div class="invalid-feedback" id="last_name-error"></div>
                    <p class="note">Last name that you wish to use on the inbox profile.</p>
                </div>

                <div class="col-md-6 profile-picture" style="display: none;">
                    <label for="profile_picture_link" class="form-label">Profile Picture Link</label>
                    <input type="url" id="profile_picture_link" name="profile_picture_link" class="form-control"
                           value="{{ isset($pool) ? $pool->profile_picture_link : '' }}" 
                           placeholder="https://example.com/picture.jpg">
                    <div class="invalid-feedback" id="profile_picture_link-error"></div>
                </div>

                <div class="col-md-6 email-password" style="display: none;">
                    <label for="email_persona_password" class="form-label">Email Persona - Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="email_persona_password" name="email_persona_password" class="form-control"
                               value="{{ isset($pool) ? $pool->email_persona_password : '' }}" placeholder="Enter password">
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                    <div class="invalid-feedback" id="email_persona_password-error"></div>
                </div>

                <div class="col-md-6 email-picture-link" style="display: none;">
                    <label for="email_persona_picture_link" class="form-label">Email Persona - Profile Picture Link</label>
                    <input type="url" id="email_persona_picture_link" name="email_persona_picture_link" class="form-control"
                           value="{{ isset($pool) ? $pool->email_persona_picture_link : '' }}" 
                           placeholder="https://example.com/email-picture.jpg">
                    <div class="invalid-feedback" id="email_persona_picture_link-error"></div>
                </div>

                <div class="col-md-6">
                    <label for="persona_password" class="form-label">Persona Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="persona_password" name="persona_password" class="form-control"
                               value="{{ isset($pool) ? $pool->persona_password : '' }}" placeholder="Enter password">
                        <i class="fa-regular fa-eye password-toggle"></i>
                    </div>
                    <div class="invalid-feedback" id="persona_password-error"></div>
                </div>
            </div>
        </div>

        <!-- Additional Assets -->
        <div class="form-section">
            <h5><i class="fas fa-info-circle me-2"></i>Additional Assets</h5>
            
            <div class="row g-3">
                <div class="col-md-12">
                    <label for="additional_info" class="form-label">Additional Information / Context</label>
                    <textarea id="additional_info" name="additional_info" class="form-control" rows="6"
                              placeholder="Any additional information or notes">{{ isset($pool) ? $pool->additional_info : '' }}</textarea>
                    <div class="invalid-feedback" id="additional_info-error"></div>
                </div>

                <div class="col-md-12">
                    <label for="backup_codes" class="form-label">Backup Codes</label>
                    <textarea id="backup_codes" name="backup_codes" class="form-control" rows="3"
                              placeholder="Enter backup codes, one per line">{{ isset($pool) ? $pool->backup_codes : '' }}</textarea>
                    <div class="invalid-feedback" id="backup_codes-error"></div>
                    <p class="note">Store any backup or recovery codes here.</p>
                </div>
            </div>
        </div>

        <!-- Assignment & Options -->
        <div class="form-section">
            <h5><i class="fas fa-users me-2"></i>Assignment & Options</h5>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="assigned_to" class="form-label">Assigned To</label>
                    <select id="assigned_to" name="assigned_to" class="form-select">
                        <option value="">Select Contractor</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (isset($pool) && $pool->assigned_to == $user->id) ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback" id="assigned_to-error"></div>
                </div>

                <div class="col-md-6">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" id="is_internal" name="is_internal" value="1"
                               {{ (isset($pool) && $pool->is_internal) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_internal">
                            Internal Pool
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_shared" name="is_shared" value="1"
                               {{ (isset($pool) && $pool->is_shared) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_shared">
                            Shared Pool
                        </label>
                    </div>
                </div>

                <div class="col-md-12">
                    <label for="shared_note" class="form-label">Shared Note</label>
                    <textarea id="shared_note" name="shared_note" class="form-control" rows="3"
                              placeholder="Add notes for shared pool">{{ isset($pool) ? $pool->shared_note : '' }}</textarea>
                    <div class="invalid-feedback" id="shared_note-error"></div>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="text-end">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-2"></i>
                {{ isset($pool) ? 'Update Pool' : 'Create Pool' }}
            </button>
        </div>
    </form>
</div>
@push('scripts')
<script>
$(document).ready(function() {
    // Initialize functionality
    initializePasswordToggles();
    updatePlatformFields();
    updateSendingPlatformFields();
    toggleMasterInboxEmail();
    updatePrefixVariantFields();
    countDomains();

    // Global variables for tracking
    let isImporting = false;
    let limitExceededShown = false;

    // Function to toggle master inbox email field visibility
    function toggleMasterInboxEmail() {
        if ($('#master_inbox_confirmation').val() == '1') {
            $('.master-inbox-email').show();
            $('#master_inbox_email').attr('required', true);
        } else {
            $('.master-inbox-email').hide();
            $('#master_inbox_email').removeAttr('required');
            $('#master_inbox_email').removeClass('is-invalid');
            $('#master_inbox_email-error').text('');
        }
    }

    // Show/hide master inbox email field based on dropdown selection
    $(document).on('change', '#master_inbox_confirmation', function() {
        toggleMasterInboxEmail();
    });

    // Password toggle functionality
    function initializePasswordToggles() {
        $(document).off('click', '.password-toggle').on('click', '.password-toggle', function() {
            const input = $(this).siblings('input');
            const icon = $(this);
            
            if (input.attr('type') === 'password') {
                input.attr('type', 'text');
                icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                input.attr('type', 'password');
                icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });
    }

    // Handle hosting platform changes
    function updatePlatformFields() {
        const selectedOption = $('#hosting_platform option:selected');
        const fieldsData = selectedOption.data('fields');
        const requiresTutorial = selectedOption.data('requires-tutorial');
        const tutorialLink = selectedOption.data('tutorial-link');
        const importNote = selectedOption.data('import-note');
        const container = $('#platform-fields-container');
        const tutorialSection = $('#tutorial_section');
        
        container.empty();
        
        // Handle tutorial section
        if (requiresTutorial && tutorialLink) {
            $('#hosting-platform-import-note').html(importNote + ' <a href="' + tutorialLink + '" class="tutorial-link" target="_blank">Click here to view tutorial</a>');
            tutorialSection.show();
        } else {
            tutorialSection.hide();
        }
        
        if (fieldsData) {
            const existingValues = @json(isset($pool) ? $pool : null);
            container.append(generateFields(fieldsData, existingValues));
            initializePasswordToggles();
        }
    }

    // Handle sending platform changes
    function updateSendingPlatformFields() {
        const selectedOption = $('#sending_platform option:selected');
        const fieldsData = selectedOption.data('fields');
        const container = $('#sending-platform-fields');
        container.empty();
        
        if (fieldsData) {
            const existingValues = @json(isset($pool) ? $pool : null);
            container.append(generateFields(fieldsData, existingValues));
            initializePasswordToggles();
        }
    }

    // Generate form fields from platform data
    function generateFields(fieldsData, existingValues = null) {
        let html = '<div class="row g-3">';
        
        Object.keys(fieldsData).forEach(fieldName => {
            const field = fieldsData[fieldName];
            const existingValue = existingValues ? (existingValues[fieldName] || '') : '';
            html += generateField(fieldName, field, existingValue, 'col-md-6');
        });
        
        html += '</div>';
        return html;
    }

    // Generate individual field HTML
    function generateField(name, field, existingValue = '', colClass = 'col-md-6') {
        let html = `<div class="${colClass} mb-3">`;
        html += `<label for="${name}" class="form-label">${field.label}`;
        if (field.required) html += ' <span class="required-asterisk">*</span>';
        html += '</label>';

        if (field.type === 'select' && field.options) {
            html += `<select id="${name}" name="${name}" class="form-select"${field.required ? ' required' : ''}>`;
            html += '<option value="">Select an option...</option>';
            Object.keys(field.options).forEach(optionValue => {
                const selected = existingValue === optionValue ? ' selected' : '';
                html += `<option value="${optionValue}"${selected}>${field.options[optionValue]}</option>`;
            });
            html += '</select>';
        } else if (field.type === 'password') {
            html += '<div class="password-wrapper">';
            html += `<input type="password" id="${name}" name="${name}" class="form-control" value="${existingValue}" placeholder="${field.placeholder || ''}"${field.required ? ' required' : ''}>`;
            html += '<i class="fa-regular fa-eye password-toggle"></i>';
            html += '</div>';
        } else if (field.type === 'textarea') {
            html += `<textarea id="${name}" name="${name}" class="form-control" rows="${field.rows || 3}" placeholder="${field.placeholder || ''}"${field.required ? ' required' : ''}>${existingValue}</textarea>`;
        } else {
            const inputType = field.type || 'text';
            html += `<input type="${inputType}" id="${name}" name="${name}" class="form-control" value="${existingValue}" placeholder="${field.placeholder || ''}"${field.required ? ' required' : ''}>`;
        }

        html += `<div class="invalid-feedback" id="${name}-error"></div>`;
        html += '</div>';
        return html;
    }

    // Update prefix variant fields based on inboxes per domain
    function updatePrefixVariantFields() {
        const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;
        const container = $('#prefix-variants-container');
        const fieldsContainer = $('#prefix-variants-fields');
        
        if (inboxesPerDomain > 1) {
            container.show();
            fieldsContainer.empty();
            
            const existingVariants = @json(isset($pool) && $pool->prefix_variants ? $pool->prefix_variants : []);
            const existingDetails = @json(isset($pool) && $pool->prefix_variants_details ? $pool->prefix_variants_details : []);
            
            for (let i = 1; i <= inboxesPerDomain; i++) {
                const variantValue = existingVariants[`variant_${i}`] || '';
                const details = existingDetails[`variant_${i}`] || {};
                
                let html = `<div class="col-md-12 mb-4">
                    <div class="card p-3">
                        <h6 class="mb-3">Prefix Variant ${i}</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="prefix_variants_variant_${i}" class="form-label">Email Persona - Prefix Variant ${i}</label>
                                <input type="text" name="prefix_variants[variant_${i}]" id="prefix_variants_variant_${i}" class="form-control" value="${variantValue}" placeholder="Enter prefix variant">
                                <div class="invalid-feedback" id="prefix_variants_variant_${i}-error"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="prefix_variants_details_variant_${i}_first_name" class="form-label">First Name</label>
                                <input type="text" name="prefix_variants_details[variant_${i}][first_name]" id="prefix_variants_details_variant_${i}_first_name" class="form-control" value="${details.first_name || ''}" placeholder="John">
                                <div class="invalid-feedback" id="prefix_variants_details_variant_${i}_first_name-error"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="prefix_variants_details_variant_${i}_last_name" class="form-label">Last Name</label>
                                <input type="text" name="prefix_variants_details[variant_${i}][last_name]" id="prefix_variants_details_variant_${i}_last_name" class="form-control" value="${details.last_name || ''}" placeholder="Doe">
                                <div class="invalid-feedback" id="prefix_variants_details_variant_${i}_last_name-error"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="prefix_variants_details_variant_${i}_profile_link" class="form-label">Profile Picture Link</label>
                                <input type="url" name="prefix_variants_details[variant_${i}][profile_link]" id="prefix_variants_details_variant_${i}_profile_link" class="form-control" value="${details.profile_link || ''}" placeholder="https://example.com/picture.jpg">
                                <div class="invalid-feedback" id="prefix_variants_details_variant_${i}_profile_link-error"></div>
                            </div>
                        </div>
                    </div>
                </div>`;
                
                fieldsContainer.append(html);
            }
            
            // Show/hide individual name fields based on prefix variants
            if (inboxesPerDomain > 1) {
                $('.first-name, .last-name, .profile-picture, .email-password, .email-picture-link').hide();
            } else {
                $('.first-name, .last-name, .profile-picture, .email-password, .email-picture-link').show();
            }
        } else {
            container.hide();
            $('.first-name, .last-name, .profile-picture, .email-password, .email-picture-link').show();
        }
        
        // Calculate total inboxes
        calculateTotalInboxes();
    }

    // Calculate total inboxes based on domains and inboxes per domain
    function calculateTotalInboxes() {
        const domainsText = $('#domains').val();
        const inboxesPerDomain = parseInt($('#inboxes_per_domain').val()) || 1;
        
        if (!domainsText) {
            $('#total_inboxes').val(0);
            return 0;
        }
        
        const domains = domainsText.split(/[\n,]+/)
            .map(domain => domain.trim())
            .filter(domain => domain.length > 0);
            
        const uniqueDomains = [...new Set(domains)];
        const calculatedInboxes = uniqueDomains.length * inboxesPerDomain;
        
        $('#total_inboxes').val(calculatedInboxes);
        return calculatedInboxes;
    }

    // Domain counting function
    function countDomains() {
        const domainsText = $('#domains').val().trim();
        let domainCount = 0;
        
        if (domainsText) {
            let domains;
            if (domainsText.includes(',')) {
                domains = domainsText.split(',').map(d => d.trim()).filter(d => d.length > 0);
            } else {
                domains = domainsText.split('\n').map(d => d.trim()).filter(d => d.length > 0);
            }
            domainCount = domains.length;
        }
        
        // Update both badge and text
        $('#domain-count-badge').text(`${domainCount} domain${domainCount !== 1 ? 's' : ''}`);
        $('#domain-count-text').text(domainCount);
        
        // Add visual feedback based on count
        const badge = $('#domain-count-badge');
        badge.removeClass('bg-primary bg-success bg-warning bg-danger');
        
        if (domainCount === 0) {
            badge.addClass('bg-danger');
        } else if (domainCount < 10) {
            badge.addClass('bg-warning');
        } else if (domainCount < 50) {
            badge.addClass('bg-primary');
        } else {
            badge.addClass('bg-success');
        }
        
        return domainCount;
    }

    // Event handlers
    $('#hosting_platform').on('change', updatePlatformFields);
    $('#sending_platform').on('change', updateSendingPlatformFields);
    $('#inboxes_per_domain').on('change', updatePrefixVariantFields);

    // Real-time domain counting
    $('#domains').on('input paste keyup', function() {
        setTimeout(() => {
            countDomains();
            calculateTotalInboxes();
        }, 100);
    });

    // Form submission
    document.getElementById('poolForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
        
        const formData = new FormData(this);
        
        // Convert domains textarea to array
        const domainsText = document.getElementById('domains').value;
        if (domainsText.trim()) {
            const domainsArray = domainsText.split('\n').map(d => d.trim()).filter(d => d);
            formData.delete('domains');
            formData.append('domains', JSON.stringify(domainsArray));
        }
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = '{{ route("admin.pools.index") }}';
            } else {
                if (data.errors) {
                    Object.keys(data.errors).forEach(field => {
                        const input = document.getElementById(field);
                        const error = document.getElementById(field + '-error');
                        if (input && error) {
                            input.classList.add('is-invalid');
                            error.textContent = data.errors[field][0];
                        }
                    });
                }
                alert(data.message || 'An error occurred');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while saving the pool');
        });
    });

    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
});
</script>
@endpush