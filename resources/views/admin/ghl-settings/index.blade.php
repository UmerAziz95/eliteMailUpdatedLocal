@extends('admin.layouts.app')

@section('title', 'GHL Settings')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="mb-0">
                        <i class="ti ti-api me-2"></i>
                        GoHighLevel API Settings
                    </h5>
                    <div class="card-action-element">
                        <button type="button" id="testCredentialsBtn" class="btn btn-info btn-sm" disabled>
                            <i class="ti ti-plug me-1"></i>
                            Test Connection
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <form id="ghlSettingsForm">
                        @csrf
                        
                        <!-- Status Section -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="mb-1">Integration Status</h6>
                                                <p class="mb-0 text-white small">Enable or disable GHL integration</p>
                                            </div>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" role="switch" 
                                                       id="enabled" name="enabled" value="1"
                                                       {{ $settings->enabled ? 'checked' : '' }}>
                                                <label class="form-check-label" for="enabled">
                                                    <span id="statusText">{{ $settings->enabled ? 'Enabled' : 'Disabled' }}</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- API Configuration -->
                        <div class="row">
                            <div class="col-md-12 mb-3" style="display: none;">
                                <label for="base_url" class="form-label">
                                    <i class="ti ti-world me-1"></i>
                                    Base URL <span class="text-danger">*</span>
                                </label>
                                <input type="url" class="form-control" id="base_url" name="base_url" 
                                       value="{{ $settings->base_url }}" 
                                       placeholder="https://rest.gohighlevel.com/v1" required readonly>
                                <div class="invalid-feedback" id="base_url-error"></div>
                                <small class="form-text text-muted">GHL API base URL endpoint</small>
                            </div>

                            <div class="col-md-6 mb-3" style="display: none;">
                                <label for="api_version" class="form-label">
                                    <i class="ti ti-versions me-1"></i>
                                    API Version <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="api_version" name="api_version" required>
                                    <option value="2021-07-28" {{ $settings->api_version == '2021-07-28' ? 'selected' : '' }}>2021-07-28</option>
                                    <!-- <option value="2021-04-15" {{ $settings->api_version == '2021-04-15' ? 'selected' : '' }}>2021-04-15</option>
                                    <option value="2020-09-22" {{ $settings->api_version == '2020-09-22' ? 'selected' : '' }}>2020-09-22</option> -->
                                </select>
                                <div class="invalid-feedback" id="api_version-error"></div>
                                <small class="form-text text-muted">GHL API version to use</small>
                            </div>
                        </div>

                        <div class="row" >
                            <div class="col-md-6 mb-3" style="display: none;">
                                <label for="auth_type" class="form-label">
                                    <i class="ti ti-shield-lock me-1"></i>
                                    Authentication Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="auth_type" name="auth_type" required>
                                    <option value="bearer" {{ $settings->auth_type == 'bearer' ? 'selected' : '' }}>Bearer Token</option>
                                    <!-- <option value="api_key" {{ $settings->auth_type == 'api_key' ? 'selected' : '' }}>API Key</option> -->
                                </select>
                                <div class="invalid-feedback" id="auth_type-error"></div>
                                <small class="form-text text-muted">Authentication method for API requests</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label for="location_id" class="form-label">
                                    <i class="ti ti-map-pin me-1"></i>
                                    Location ID
                                </label>
                                <input type="text" class="form-control" id="location_id" name="location_id" 
                                       value="{{ $settings->location_id }}" 
                                       placeholder="Enter GHL Location ID">
                                <div class="invalid-feedback" id="location_id-error"></div>
                                <small class="form-text text-muted">GHL Location ID (optional)</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <label for="api_token" class="form-label">
                                    <i class="ti ti-key me-1"></i>
                                    API Token <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="api_token" name="api_token" 
                                           value="{{ $settings->api_token }}" 
                                           placeholder="Enter your GHL API token" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleApiToken">
                                        <i class="ti ti-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="api_token-error"></div>
                                <small class="form-text text-muted">Your GHL API authentication token</small>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <hr class="my-4">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary" id="saveBtn">
                                        <i class="ti ti-device-floppy me-1"></i>
                                        Save Settings
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="resetBtn">
                                        <i class="ti ti-refresh me-1"></i>
                                        Reset
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Connection Status Card -->
            <div class="card mt-4" id="connectionStatusCard" style="display: none;">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="ti ti-activity me-2"></i>
                        Connection Test Results
                    </h6>
                </div>
                <div class="card-body" id="connectionResults">
                    <!-- Results will be populated via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // CSRF Token Setup
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Toggle API token visibility
    $('#toggleApiToken').on('click', function() {
        const input = $('#api_token');
        const icon = $('#toggleIcon');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('ti-eye').addClass('ti-eye-off');
        } else {
            input.attr('type', 'password');
            icon.removeClass('ti-eye-off').addClass('ti-eye');
        }
    });

    // Update status text when enabled switch changes
    $('#enabled').on('change', function() {
        const isEnabled = $(this).is(':checked');
        $('#statusText').text(isEnabled ? 'Enabled' : 'Disabled');
        updateTestButtonState();
    });

    // Monitor form changes to enable/disable test button
    function updateTestButtonState() {
        const hasApiToken = $('#api_token').val().trim().length > 0;
        const hasBaseUrl = $('#base_url').val().trim().length > 0;
        const isEnabled = $('#enabled').is(':checked');
        
        $('#testCredentialsBtn').prop('disabled', !(hasApiToken && hasBaseUrl && isEnabled));
    }

    // Monitor required fields
    $('#api_token, #base_url').on('input', updateTestButtonState);
    
    // Initial test button state
    updateTestButtonState();

    // Form validation
    function validateForm() {
        let isValid = true;
        const requiredFields = ['base_url', 'api_token', 'auth_type', 'api_version'];
        
        // Clear previous errors
        $('.form-control, .form-select').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        requiredFields.forEach(function(field) {
            const input = $(`#${field}`);
            const value = input.val().trim();
            
            if (!value) {
                input.addClass('is-invalid');
                $(`#${field}-error`).text('This field is required');
                isValid = false;
            }
        });
        
        // Validate URL format
        const baseUrl = $('#base_url').val().trim();
        if (baseUrl && !isValidUrl(baseUrl)) {
            $('#base_url').addClass('is-invalid');
            $('#base_url-error').text('Please enter a valid URL');
            isValid = false;
        }
        
        return isValid;
    }

    // URL validation helper
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }

    // Form submission
    $('#ghlSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm()) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Error',
                text: 'Please fix the validation errors before submitting.',
                customClass: {
                    popup: 'swal-dark'
                }
            });
            return;
        }
        
        // Show loading
        Swal.fire({
            title: 'Saving Settings...',
            text: 'Please wait while we save your GHL settings.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            customClass: {
                popup: 'swal-dark'
            },
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        const formData = {
            enabled: $('#enabled').is(':checked') ? 1 : 0,
            base_url: $('#base_url').val().trim(),
            api_token: $('#api_token').val().trim(),
            location_id: $('#location_id').val().trim(),
            auth_type: $('#auth_type').val(),
            api_version: $('#api_version').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        $.ajax({
            url: '{{ route("admin.ghl-settings.update") }}',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message || 'Settings saved successfully!',
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'swal-dark'
                        }
                    });
                    updateTestButtonState();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Failed!',
                        text: response.message || 'Failed to save settings',
                        customClass: {
                            popup: 'swal-dark'
                        }
                    });
                }
            },
            error: function(xhr) {
                console.error('Save error:', xhr);
                
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(field) {
                        $(`#${field}`).addClass('is-invalid');
                        $(`#${field}-error`).text(errors[field][0]);
                    });
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Validation Error',
                        text: 'Please fix the validation errors.',
                        customClass: {
                            popup: 'swal-dark'
                        }
                    });
                } else {
                    const message = xhr.responseJSON?.message || 'An error occurred while saving settings';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: message,
                        customClass: {
                            popup: 'swal-dark'
                        }
                    });
                }
            }
        });
        
    });

    // Test credentials functionality
    $('#testCredentialsBtn').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        
        // Save form first if there are unsaved changes
        if (!validateForm()) {
            Swal.fire({
                icon: 'warning',
                title: 'Validation Required',
                text: 'Please save valid settings before testing connection.',
                customClass: {
                    popup: 'swal-dark'
                }
            });
            return;
        }
        
        
        // Show loading
        Swal.fire({
            title: 'Testing Connection...',
            text: 'Please wait while we test the GHL API connection.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            customClass: {
                popup: 'swal-dark'
            },
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        $.ajax({
            url: '{{ route("admin.ghl-settings.test-connection") }}',
            method: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                showConnectionResults(response);
                
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Connection Successful!',
                        text: 'GHL API connection test passed successfully.',
                        timer: 3000,
                        showConfirmButton: true,
                        customClass: {
                            popup: 'swal-dark'
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Failed!',
                        text: response.message || 'Unable to connect to GHL API.',
                        customClass: {
                            popup: 'swal-dark'
                        }
                    });
                }
            },
            error: function(xhr) {
                console.error('Test error:', xhr);
                const message = xhr.responseJSON?.message || 'Connection test failed';
                
                showConnectionResults({
                    success: false,
                    message: message
                });
                
                Swal.fire({
                    icon: 'error',
                    title: 'Test Failed!',
                    text: message,
                    customClass: {
                        popup: 'swal-dark'
                    }
                });
            },
            complete: function() {
                updateTestButtonState();
            }
        });
        
    });

    // Show connection test results
    function showConnectionResults(response) {
        const card = $('#connectionStatusCard');
        const results = $('#connectionResults');
        
        let html = '<div class="d-flex align-items-center mb-2">';
        
        if (response.success) {
            html += '<div class="badge bg-success me-2"><i class="ti ti-check"></i></div>';
            html += '<h6 class="mb-0 text-success">Connection Successful</h6>';
        } else {
            html += '<div class="badge bg-danger me-2"><i class="ti ti-x"></i></div>';
            html += '<h6 class="mb-0 text-danger">Connection Failed</h6>';
        }
        
        html += '</div>';
        html += `<p class="mb-0">${response.message}</p>`;
        html += `<small class="text-muted">Tested at: ${new Date().toLocaleString()}</small>`;
        
        results.html(html);
        card.show();
    }

    // Reset form
    $('#resetBtn').on('click', function() {
        Swal.fire({
            title: 'Reset Settings?',
            text: 'This will reload the original settings and discard all unsaved changes.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#6c757d',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Reset Settings',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'swal-dark'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading while reloading
                Swal.fire({
                    title: 'Resetting...',
                    text: 'Reloading original settings.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    timer: 1000,
                    customClass: {
                        popup: 'swal-dark'
                    },
                    didOpen: () => {
                        Swal.showLoading();
                    }
                }).then(() => {
                    location.reload();
                });
            }
        });
    });

    // Auto-save on blur for better UX (optional)
    let autoSaveTimeout;
    $('#ghlSettingsForm input, #ghlSettingsForm select').on('blur', function() {
        clearTimeout(autoSaveTimeout);
        autoSaveTimeout = setTimeout(function() {
            if (validateForm()) {
                // Auto-save could be implemented here if desired
            }
        }, 1000);
    });
});
</script>

<style>
.card-action-element {
    flex-shrink: 0;
}

.form-check-input:checked {
    background-color: #28a745;
    border-color: #28a745;
}

.badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    border-radius: 50%;
}

#connectionStatusCard {
    border: 1px solid #dee2e6;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.input-group .btn {
    border-left: 0;
}

.text-danger {
    color: #dc3545 !important;
}

.form-text {
    font-size: 0.875em;
    color: #6c757d;
}

.ti-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Custom SweetAlert2 Dark Theme */
.swal-dark {
    background-color: #2d3748 !important;
    color: #e2e8f0 !important;
}

.swal-dark .swal2-title {
    color: #e2e8f0 !important;
}

.swal-dark .swal2-content {
    color: #cbd5e0 !important;
}

.swal-dark .swal2-input {
    background-color: #4a5568 !important;
    border: 1px solid #718096 !important;
    color: #e2e8f0 !important;
}

.swal-dark .swal2-input:focus {
    border-color: #3182ce !important;
    box-shadow: 0 0 0 0.2rem rgba(49, 130, 206, 0.25) !important;
}

/* Loading spinner customization */
.swal2-loader {
    border-color: #3182ce transparent #3182ce transparent !important;
}
</style>
@endpush