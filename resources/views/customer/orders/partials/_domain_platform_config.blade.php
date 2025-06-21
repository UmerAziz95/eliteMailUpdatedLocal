{{-- Domain Forwarding and Platform Configuration Section --}}
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">
                <i class="fa-solid fa-cog me-2"></i>
                Domain & Platform Configuration
            </h5>
            <div class="badge bg-info">
                <i class="fa-solid fa-info-circle me-1"></i>
                Required Setup
            </div>
        </div>        
        @if(isset($rejectedPanels) && $rejectedPanels->count() > 0)
            @foreach($rejectedPanels as $rejectedPanel)
                @if($rejectedPanel->note)
                    <div class="mb-4">
                        <div class="alert border-0 shadow-lg panel-rejection-alert" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); border-left: 5px solid #fff !important; position: relative; overflow: hidden;">
                            <!-- Animated background pattern -->
                            <div class="alert-pattern"></div>
                            
                            <div class="d-flex align-items-start position-relative" style="z-index: 2;">
                                <div class="alert-icon-wrapper me-3">
                                    <i class="fa-solid fa-exclamation-triangle fa-2x text-white" style="filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="alert-heading mb-2 text-white fw-bold" style="text-shadow: 0 1px 3px rgba(0,0,0,0.3);">
                                        <i class="fa-solid fa-server me-2"></i>
                                        Panel #{{ $rejectedPanel->id }} - Rejection Notice
                                    </h5>
                                    <div class="rejection-note-content p-3 rounded" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2);">
                                        <p class="mb-0 text-white fw-medium" style="font-size: 1.1em; line-height: 1.5; text-shadow: 0 1px 2px rgba(0,0,0,0.2);">
                                            {{ $rejectedPanel->note }}
                                        </p>
                                    </div>
                                    <div class="mt-3">
                                        <span class="badge bg-warning text-dark px-3 py-2" style="font-size: 0.9em;">
                                            <i class="fa-solid fa-clock me-1"></i>
                                            Action Required
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        @endif

        {{-- Domain Forwarding Destination URL --}}
        <div class="mb-3">
            <label for="forwarding_url" class="form-label fw-bold text-white">
                <i class="fa-solid fa-external-link-alt me-2"></i>
                Domain forwarding destination URL
                <span class="text-danger">*</span>
            </label>            <input type="text" 
                   id="forwarding_url" 
                   name="forwarding_url" 
                   class="form-control" 
                   placeholder="Enter destination URL or text" 
                   value="{{ optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->forwarding_url : '' }}" 
                   required>
            <div class="invalid-feedback" id="forwarding_url-error"></div>            <small class="form-text text-muted">
                <i class="fa-solid fa-info-circle me-1"></i>
                A link or text where you'd like to drive the traffic from the domains you send us – could be your main website, blog post, etc.
            </small>
        </div>

        {{-- Domain Hosting Platform --}}
        <div class="mb-3">
            <label for="hosting_platform" class="form-label fw-bold text-white">
                <i class="fa-solid fa-server me-2"></i>
                Domain hosting platform
                <span class="text-danger">*</span>
            </label>
            <select id="hosting_platform" name="hosting_platform" class="form-control" required>
                <option value="">Select a hosting platform...</option>
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
            <small class="form-text text-muted">
                <i class="fa-solid fa-info-circle me-1"></i>
                Where your domains are hosted and can be accessed to modify the DNS settings
            </small>
        </div>

        {{-- Tutorial Section --}}
        <!-- <div id="tutorial_section" class="mb-3" style="display: none;">
            <div class="alert alert-warning">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                <strong>IMPORTANT</strong> - Please follow the steps from this document to grant us access to your hosting account:
                <a href="#" class="highlight-link tutorial-link text-white" target="_blank">
                    <i class="fa-solid fa-external-link-alt me-1"></i>
                    Click here to view tutorial
                </a>
            </div>
        </div> -->

        {{-- Dynamic Platform Fields Container --}}
        <div id="platform-fields-container">
            <!-- Dynamic platform fields will be inserted here -->
        </div>

        {{-- Sending Platforms Section --}}
        <div class="mt-4 pt-4 border-top">
            <h6 class="mb-3 text-white">
                <i class="fa-solid fa-paper-plane me-2"></i>
                Sending Platforms / Sequencer
            </h6>

            <div class="mb-3">
                <label for="sending_platform" class="form-label fw-bold text-white">
                    <i class="fa-solid fa-rocket me-2"></i>
                    Sending Platform
                    <span class="text-danger">*</span>
                </label>
                <select id="sending_platform" name="sending_platform" class="form-control" required>
                    <option value="">Select a sending platform...</option>
                    @foreach($sendingPlatforms as $platform)
                        <option value="{{ $platform->value }}" 
                            data-fields='@json($platform->fields)'
                            {{ (optional(optional($order)->reorderInfo)->count() > 0 && $order->reorderInfo->first()->sending_platform === $platform->value) ? ' selected' : '' }}>
                            {{ $platform->name }}
                        </option>
                    @endforeach
                </select>
                <div class="invalid-feedback" id="sending_platform-error"></div>
                <small class="form-text text-muted">
                    <i class="fa-solid fa-info-circle me-1"></i>
                    We upload and configure the email accounts for you - it's a software you use to send emails
                </small>
            </div>

            {{-- Dynamic Sending Platform Fields Container --}}
            <div id="sending-platform-fields">
                <!-- Dynamic sending platform fields will be inserted here -->
            </div>
        </div>
    </div>
</div>

{{-- Additional Styles --}}
@push('styles')
<style>
.panel-rejection-alert {
    animation: pulseGlow 2s ease-in-out infinite alternate;
    border-radius: 12px !important;
}

.panel-rejection-alert::before {
    content: '';
    position: absolute;
    top: -2px;
    left: -2px;
    right: -2px;
    bottom: -2px;
    background: linear-gradient(45deg, #ff6b6b, #feca57, #48dbfb, #ff9ff3);
    border-radius: 14px;
    z-index: -1;
    animation: borderAnimation 3s linear infinite;
}

.alert-pattern {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: 
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
    animation: patternMove 8s ease-in-out infinite;
}

.alert-icon-wrapper {
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    padding: 12px;
    backdrop-filter: blur(10px);
    animation: iconBounce 2s ease-in-out infinite;
}

.rejection-note-content {
    transform: translateY(0);
    transition: transform 0.3s ease;
}

.panel-rejection-alert:hover .rejection-note-content {
    transform: translateY(-2px);
}

@keyframes pulseGlow {
    0% {
        box-shadow: 
            0 4px 20px rgba(220, 53, 69, 0.4),
            0 0 0 0 rgba(220, 53, 69, 0.4);
    }
    100% {
        box-shadow: 
            0 8px 30px rgba(220, 53, 69, 0.6),
            0 0 0 10px rgba(220, 53, 69, 0);
    }
}

@keyframes borderAnimation {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes patternMove {
    0%, 100% { transform: translateX(0) translateY(0); }
    25% { transform: translateX(-10px) translateY(-5px); }
    50% { transform: translateX(10px) translateY(-10px); }
    75% { transform: translateX(-5px) translateY(5px); }
}

@keyframes iconBounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}

.platform-field {
    transition: all 0.3s ease;
}

.platform-field:hover {
    transform: translateY(-1px);
}

.highlight-link {
    text-decoration: underline !important;
    font-weight: bold;
}

.highlight-link:hover {
    opacity: 0.8;
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
    z-index: 10;
}

.password-toggle:hover {
    color: var(--second-primary);
}
</style>
@endpush

{{-- JavaScript for Dynamic Fields --}}
@push('scripts')
<script>
// Dynamic field generation function
function generatePlatformField(name, field, existingValue = '') {
    const fieldId = `${name}_field`;
    const fieldClass = field.required ? 'required' : '';
    
    let html = `<div class="mb-3 platform-field" id="${fieldId}">
        <label for="${name}" class="form-label fw-bold text-white">
            <i class="fa-solid fa-key me-2"></i>
            ${field.label}
            ${field.required ? '<span class="text-danger">*</span>' : ''}
        </label>`;
    
    if (field.type === 'select' && field.options) {
        html += `<select id="${name}" name="${name}" class="form-control ${fieldClass}">`;
        Object.entries(field.options).forEach(([value, label]) => {
            const selected = existingValue === value ? 'selected' : '';
            html += `<option value="${value}" ${selected}>${label}</option>`;
        });
        html += `</select>`;
    } else if (field.type === 'textarea') {
        html += `<textarea id="${name}" name="${name}" class="form-control ${fieldClass}" rows="4" placeholder="${field.label}">${existingValue}</textarea>`;
    } else if (field.type === 'password') {
        html += `<div class="password-wrapper">
            <input type="password" id="${name}" name="${name}" class="form-control ${fieldClass}" value="${existingValue}" placeholder="Enter ${field.label.toLowerCase()}">
            <i class="fa-regular fa-eye password-toggle" data-target="${name}"></i>
        </div>`;
    } else {
        html += `<input type="${field.type}" id="${name}" name="${name}" class="form-control ${fieldClass}" value="${existingValue}" placeholder="Enter ${field.label.toLowerCase()}">`;
    }
    
    if (field.note) {
        html += `<small class="form-text text-muted"><i class="fa-solid fa-info-circle me-1"></i>${field.note}</small>`;
    }
    
    html += `<div class="invalid-feedback" id="${name}-error"></div></div>`;
    return html;
}

// Update hosting platform fields
function updatePlatformFields() {
    const selectedOption = $('#hosting_platform option:selected');
    const fieldsData = selectedOption.data('fields');
    const requiresTutorial = selectedOption.data('requires-tutorial');
    const tutorialLink = selectedOption.data('tutorial-link');
    const container = $('#platform-fields-container');
    
    container.empty();
    
    if (fieldsData) {
        // Get existing values
        const existingValues = @json(optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first() : null);
        
        Object.entries(fieldsData).forEach(([name, field]) => {
            const existingValue = existingValues && existingValues[name] ? existingValues[name] : '';
            container.append(generatePlatformField(name, field, existingValue));
        });
        
        // Initialize password toggles
        initializePasswordToggles();
    }
    
    // Handle tutorial section
    if (requiresTutorial && tutorialLink) {
        $('#tutorial_section').show();
        $('.tutorial-link').attr('href', tutorialLink);
    } else {
        $('#tutorial_section').hide();
    }
}

// Update sending platform fields
function updateSendingPlatformFields() {
    const selectedOption = $('#sending_platform option:selected');
    const fieldsData = selectedOption.data('fields');
    const container = $('#sending-platform-fields');
    
    container.empty();
    
    if (fieldsData) {
        // Get existing values
        const existingValues = @json(optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first() : null);
        
        Object.entries(fieldsData).forEach(([name, field]) => {
            const existingValue = existingValues && existingValues[name] ? existingValues[name] : '';
            container.append(generatePlatformField(name, field, existingValue));
        });
        
        // Initialize password toggles
        initializePasswordToggles();
    }
}

// Initialize password toggle functionality
function initializePasswordToggles() {
    $('.password-toggle').off('click').on('click', function() {
        const targetId = $(this).data('target');
        const input = $(`#${targetId}`);
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            $(this).removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            $(this).removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
}

// Platform configuration validation
function validatePlatformConfig() {
    let isValid = true;
    const requiredFields = [
        'forwarding_url',
        'hosting_platform', 
        'sending_platform'
    ];
    
    // Validate basic required fields
    requiredFields.forEach(fieldName => {
        const field = $(`#${fieldName}`);
        const value = field.val()?.trim();
        
        field.removeClass('is-invalid');
        $(`#${fieldName}-error`).text('');
        
        if (!value) {
            field.addClass('is-invalid');
            $(`#${fieldName}-error`).text(`${field.prev('label').text().replace('*', '').trim()} is required`);
            isValid = false;
        }
    });
      // Validate URL format
    // URL validation removed - forwarding_url is now treated as simple text
    
    // Validate dynamic platform fields
    $('.platform-field .required').each(function() {
        const field = $(this);
        const value = field.val()?.trim();
        
        field.removeClass('is-invalid');
        if (!value) {
            field.addClass('is-invalid');
            const fieldName = field.attr('name');
            $(`#${fieldName}-error`).text('This field is required');
            isValid = false;
        }
    });
      return isValid;
}

// Initialize platform configuration
$(document).ready(function() {
    // Initialize fields on page load
    updatePlatformFields();
    updateSendingPlatformFields();
    
    // Handle platform changes
    $('#hosting_platform').on('change', updatePlatformFields);
    $('#sending_platform').on('change', updateSendingPlatformFields);
    
    // Real-time validation
    $('#forwarding_url, #hosting_platform, #sending_platform').on('blur change', function() {
        const field = $(this);
        const value = field.val()?.trim();
        const fieldName = field.attr('name');
        
        field.removeClass('is-invalid');
        $(`#${fieldName}-error`).text('');
          if (!value) {
            field.addClass('is-invalid');
            $(`#${fieldName}-error`).text('This field is required');
        }
        // URL validation removed for forwarding_url - now treated as simple text
    });
});
</script>
@endpush
