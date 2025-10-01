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
                    <label for="forwarding_url" class="form-label">Domain Forwarding URL</label>
                    <input type="url" id="forwarding_url" name="forwarding_url" class="form-control"
                           value="{{ isset($pool) ? $pool->forwarding_url : '' }}" 
                           placeholder="https://example.com">
                    <div class="invalid-feedback" id="forwarding_url-error"></div>
                    <p class="note">This is where prospects will land if they visit one of your domains.</p>
                </div>

                <div class="col-md-6">
                    <label for="hosting_platform" class="form-label">Hosting Platform</label>
                    <input type="text" id="hosting_platform" name="hosting_platform" class="form-control"
                           value="{{ isset($pool) ? $pool->hosting_platform : '' }}" 
                           placeholder="e.g., GoDaddy, Namecheap">
                    <div class="invalid-feedback" id="hosting_platform-error"></div>
                    <p class="note">Where your domains are hosted and DNS can be managed.</p>
                </div>

                <div class="col-md-6">
                    <label for="sending_platform" class="form-label">Sending Platform</label>
                    <input type="text" id="sending_platform" name="sending_platform" class="form-control"
                           value="{{ isset($pool) ? $pool->sending_platform : '' }}" 
                           placeholder="e.g., SendGrid, Mailgun">
                    <div class="invalid-feedback" id="sending_platform-error"></div>
                    <p class="note">Cold email platform for sending emails.</p>
                </div>

                <div class="col-md-12">
                    <label for="domains" class="form-label">Domains</label>
                    <textarea id="domains" name="domains" class="form-control" rows="4"
                              placeholder="Enter domains, one per line">{{ isset($pool) && $pool->domains ? implode("\n", $pool->domains) : '' }}</textarea>
                    <div class="invalid-feedback" id="domains-error"></div>
                    <p class="note">List of domains to be used, one per line.</p>
                </div>
            </div>
        </div>

        <!-- Inbox Configuration -->
        <div class="form-section">
            <h5><i class="fas fa-envelope me-2"></i>Inbox Configuration</h5>
            
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="total_inboxes" class="form-label">Total Inboxes</label>
                    <input type="number" id="total_inboxes" name="total_inboxes" class="form-control" min="1"
                           value="{{ isset($pool) ? $pool->total_inboxes : '' }}" placeholder="0">
                    <div class="invalid-feedback" id="total_inboxes-error"></div>
                </div>

                <div class="col-md-4">
                    <label for="inboxes_per_domain" class="form-label">Inboxes Per Domain</label>
                    <input type="number" id="inboxes_per_domain" name="inboxes_per_domain" class="form-control" min="1"
                           value="{{ isset($pool) ? $pool->inboxes_per_domain : '' }}" placeholder="0">
                    <div class="invalid-feedback" id="inboxes_per_domain-error"></div>
                </div>

                <div class="col-md-4">
                    <label for="initial_total_inboxes" class="form-label">Initial Total Inboxes</label>
                    <input type="number" id="initial_total_inboxes" name="initial_total_inboxes" class="form-control" min="1"
                           value="{{ isset($pool) ? $pool->initial_total_inboxes : '' }}" placeholder="0">
                    <div class="invalid-feedback" id="initial_total_inboxes-error"></div>
                </div>

                <div class="col-md-6">
                    <label for="master_inbox_email" class="form-label">Master Inbox Email</label>
                    <input type="email" id="master_inbox_email" name="master_inbox_email" class="form-control"
                           value="{{ isset($pool) ? $pool->master_inbox_email : '' }}" 
                           placeholder="master@example.com">
                    <div class="invalid-feedback" id="master_inbox_email-error"></div>
                </div>

                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="master_inbox_confirmation" 
                               name="master_inbox_confirmation" value="1"
                               {{ (isset($pool) && $pool->master_inbox_confirmation) ? 'checked' : '' }}>
                        <label class="form-check-label" for="master_inbox_confirmation">
                            Master Inbox Confirmed
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="form-section">
            <h5><i class="fas fa-user me-2"></i>Personal Information</h5>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First Name</label>
                    <input type="text" id="first_name" name="first_name" class="form-control"
                           value="{{ isset($pool) ? $pool->first_name : '' }}" placeholder="John">
                    <div class="invalid-feedback" id="first_name-error"></div>
                    <p class="note">First name for the email persona.</p>
                </div>

                <div class="col-md-6">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input type="text" id="last_name" name="last_name" class="form-control"
                           value="{{ isset($pool) ? $pool->last_name : '' }}" placeholder="Doe">
                    <div class="invalid-feedback" id="last_name-error"></div>
                    <p class="note">Last name for the email persona.</p>
                </div>

                <div class="col-md-6">
                    <label for="persona_password" class="form-label">Persona Password</label>
                    <input type="text" id="persona_password" name="persona_password" class="form-control"
                           value="{{ isset($pool) ? $pool->persona_password : '' }}" placeholder="Enter password">
                    <div class="invalid-feedback" id="persona_password-error"></div>
                </div>

                <div class="col-md-6">
                    <label for="email_persona_password" class="form-label">Email Persona Password</label>
                    <input type="text" id="email_persona_password" name="email_persona_password" class="form-control"
                           value="{{ isset($pool) ? $pool->email_persona_password : '' }}" placeholder="Enter password">
                    <div class="invalid-feedback" id="email_persona_password-error"></div>
                </div>

                <div class="col-md-6">
                    <label for="profile_picture_link" class="form-label">Profile Picture Link</label>
                    <input type="url" id="profile_picture_link" name="profile_picture_link" class="form-control"
                           value="{{ isset($pool) ? $pool->profile_picture_link : '' }}" 
                           placeholder="https://example.com/picture.jpg">
                    <div class="invalid-feedback" id="profile_picture_link-error"></div>
                </div>

                <div class="col-md-6">
                    <label for="email_persona_picture_link" class="form-label">Email Persona Picture Link</label>
                    <input type="url" id="email_persona_picture_link" name="email_persona_picture_link" class="form-control"
                           value="{{ isset($pool) ? $pool->email_persona_picture_link : '' }}" 
                           placeholder="https://example.com/email-picture.jpg">
                    <div class="invalid-feedback" id="email_persona_picture_link-error"></div>
                </div>
            </div>
        </div>

        <!-- Platform Credentials -->
        <div class="form-section">
            <h5><i class="fas fa-key me-2"></i>Platform Credentials</h5>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="platform_login" class="form-label">Platform Login</label>
                    <input type="text" id="platform_login" name="platform_login" class="form-control"
                           value="{{ isset($pool) ? $pool->platform_login : '' }}" placeholder="username or email">
                    <div class="invalid-feedback" id="platform_login-error"></div>
                </div>

                <div class="col-md-6">
                    <label for="platform_password" class="form-label">Platform Password</label>
                    <input type="password" id="platform_password" name="platform_password" class="form-control"
                           value="{{ isset($pool) ? $pool->platform_password : '' }}" placeholder="Enter password">
                    <div class="invalid-feedback" id="platform_password-error"></div>
                </div>

                <div class="col-md-6">
                    <label for="sequencer_login" class="form-label">Sequencer Login</label>
                    <input type="text" id="sequencer_login" name="sequencer_login" class="form-control"
                           value="{{ isset($pool) ? $pool->sequencer_login : '' }}" placeholder="username or email">
                    <div class="invalid-feedback" id="sequencer_login-error"></div>
                </div>

                <div class="col-md-6">
                    <label for="sequencer_password" class="form-label">Sequencer Password</label>
                    <input type="password" id="sequencer_password" name="sequencer_password" class="form-control"
                           value="{{ isset($pool) ? $pool->sequencer_password : '' }}" placeholder="Enter password">
                    <div class="invalid-feedback" id="sequencer_password-error"></div>
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

                <div class="col-md-12">
                    <label for="additional_info" class="form-label">Additional Information</label>
                    <textarea id="additional_info" name="additional_info" class="form-control" rows="3"
                              placeholder="Any additional information or notes">{{ isset($pool) ? $pool->additional_info : '' }}</textarea>
                    <div class="invalid-feedback" id="additional_info-error"></div>
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
</script>
@endpush