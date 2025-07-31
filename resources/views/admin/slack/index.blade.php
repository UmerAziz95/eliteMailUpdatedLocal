@extends('admin.layouts.app')

@section('title', 'Slack Settings')

@push('styles')
<style>
    .bg-label-secondary {
        background-color: #ffffff28;
        color: var(--extra-light);
        font-weight: 100;
    }

    .nav-tabs .nav-link {
        color: var(--extra-light);
        border: none
    }

    .nav-tabs .nav-link:hover {
        color: var(--white-color);
        border: none
    }

    .nav-tabs .nav-link.active {
        background-color: var(--second-primary);
        color: #fff;
        border: none;
        border-radius: 6px
    }

    .webhook-form {
        background-color: #ffffff1d;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .webhook-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .webhook-title {
        color: var(--white-color);
        font-weight: 600;
        margin: 0;
        font-size: 1.1rem;
    }

    .status-toggle {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        color: var(--extra-light);
        font-weight: 500;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control {
        background-color: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: var(--white-color);
        border-radius: 6px;
        padding: 0.75rem 1rem;
    }

    .form-control:focus {
        background-color: rgba(255, 255, 255, 0.15);
        border-color: var(--second-primary);
        color: var(--white-color);
        box-shadow: 0 0 0 0.2rem rgba(var(--second-primary-rgb), 0.25);
    }

    .form-control::placeholder {
        color: rgba(255, 255, 255, 0.5);
    }

    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .form-control.is-valid {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
    }

    .btn-test {
        background-color: #17a2b8;
        border-color: #17a2b8;
        color: white;
    }

    .btn-test:hover {
        background-color: #138496;
        border-color: #138496;
        color: white;
    }

    .btn-save {
        background-color: var(--second-primary);
        border-color: var(--second-primary);
        color: white;
    }

    .btn-save:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        color: white;
    }

    .form-switch {
        padding-left: 2.5em;
    }

    .form-switch .form-check-input {
        width: 2em;
        margin-left: -2.5em;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%28255, 255, 255, 0.25%29'/%3e%3c/svg%3e");
        background-color: rgba(255, 255, 255, 0.25);
        border-color: rgba(255, 255, 255, 0.25);
    }

    .form-switch .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%28255, 255, 255, 1.0%29'/%3e%3c/svg%3e");
        box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
    }

    .form-switch .form-check-input:focus {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='-4 -4 8 8'%3e%3ccircle r='3' fill='rgba%28255, 255, 255, 0.25%29'/%3e%3c/svg%3e");
        border-color: rgba(255, 255, 255, 0.5);
        box-shadow: 0 0 0 0.25rem rgba(40, 167, 69, 0.25);
    }

    .status-toggle .form-label {
        color: #6c757d;
    }

    .status-toggle .form-check-input:checked + .status-label {
        color: #28a745;
        font-weight: 600;
    }

    .status-indicator {
        font-size: 0.875rem;
        font-weight: 500;
        margin-left: 0.5rem;
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        background-color: rgba(255, 255, 255, 0.1);
    }

    .status-indicator.enabled {
        color: #28a745;
        background-color: rgba(40, 167, 69, 0.1);
        border: 1px solid rgba(40, 167, 69, 0.2);
    }

    .status-indicator.disabled {
        color: #6c757d;
        background-color: rgba(108, 117, 125, 0.1);
        border: 1px solid rgba(108, 117, 125, 0.2);
    }

    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }

    .validation-error {
        color: #dc3545;
        font-size: 0.875rem;
        margin-top: 0.25rem;
    }
</style>

@endpush

@section('content')
<div class="row py-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white">
                <i class="ti ti-brand-slack me-2"></i>
                Slack Settings
            </h2>
            <button type="button" class="btn btn-outline-light btn-sm" id="refreshSettings">
                <i class="ti ti-refresh me-1"></i> Refresh
            </button>
        </div>

        <div class="">
            <div class="">
                <div class="alert alert-info" role="alert">
                    <h5 class="alert-heading mb-1">
                        <i class="ti ti-info-circle me-2"></i>
                        How to Setup Slack Webhooks
                    </h5>
                    <p class="mb-2 mx-2 text-muted">To receive notifications in your Slack channel, you need to create a webhook URL:</p>
                    <ol class="mb-0">
                        <li>Go to your Slack workspace</li>
                        <li>Navigate to Apps > Incoming Webhooks</li>
                        <li>Create a new webhook for your desired channel</li>
                        <li>Copy the webhook URL and paste it in the forms below</li>
                        <li>Configure which events should trigger notifications</li>
                    </ol>
                </div>

                <!-- Webhook Forms Container -->
                <div id="webhookFormsContainer">
                    @foreach($types as $typeKey => $typeLabel)
                        <div class="webhook-form" id="form-{{ $typeKey }}" data-type="{{ $typeKey }}">
                            <div class="webhook-header">
                                <h5 class="webhook-title">
                                    <i class="ti ti-webhook me-2"></i>
                                    {{ $typeLabel }}
                                </h5>
                                <div class="status-toggle">
                                    <label for="status-{{ $typeKey }}" class="form-label mb-0">Enable</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-switch" type="checkbox" role="switch" 
                                               id="status-{{ $typeKey }}" 
                                               data-type="{{ $typeKey }}"
                                               {{ isset($settings[$typeKey]) && $settings[$typeKey] && $settings[$typeKey]->status ? 'checked' : '' }}>
                                    </div>
                                    <span class="status-indicator {{ isset($settings[$typeKey]) && $settings[$typeKey] && $settings[$typeKey]->status ? 'enabled' : 'disabled' }}" 
                                          id="status-text-{{ $typeKey }}">
                                        {{ isset($settings[$typeKey]) && $settings[$typeKey] && $settings[$typeKey]->status ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>

                            <form class="webhook-settings-form" data-type="{{ $typeKey }}">
                                @csrf
                                <input type="hidden" name="type" value="{{ $typeKey }}">
                                
                                <div class="form-group">
                                    <label for="url-{{ $typeKey }}" class="form-label">
                                        <i class="ti ti-link me-1"></i>
                                        Webhook URL *
                                    </label>
                                    <input type="url" 
                                           class="form-control webhook-url" 
                                           id="url-{{ $typeKey }}" 
                                           name="url" 
                                           placeholder="https://hooks.slack.com/services/..." 
                                           value="{{ isset($settings[$typeKey]) && $settings[$typeKey] ? $settings[$typeKey]->url : '' }}" 
                                           required>
                                    <small class="form-text text-muted">
                                        Enter your Slack webhook URL for {{ strtolower($typeLabel) }} notifications
                                    </small>
                                    <div class="validation-error" id="url-error-{{ $typeKey }}" style="display: none;"></div>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-test btn-sm test-webhook" data-type="{{ $typeKey }}">
                                        <i class="ti ti-send me-1"></i> Test Webhook
                                    </button>
                                    <button type="submit" class="btn btn-save btn-sm">
                                        <i class="ti ti-device-floppy me-1"></i> Save Settings
                                    </button>
                                    @if(isset($settings[$typeKey]) && $settings[$typeKey])
                                        <button type="button" class="btn btn-danger btn-sm delete-webhook" data-id="{{ $settings[$typeKey]->id }}" data-type="{{ $typeKey }}">
                                            <i class="ti ti-trash me-1"></i> Delete
                                        </button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    @endforeach
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

    // Utility Functions
    const showSwalLoading = (title = 'Processing...', text = 'Please wait') => {
        Swal.fire({
            title: title,
            text: text,
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            },
            customClass: {
                popup: 'swal-dark'
            }
        });
    };

    const closeSwalLoading = () => {
        Swal.close();
    };

    const clearValidationErrors = (type) => {
        const form = $(`.webhook-settings-form[data-type="${type}"]`);
        form.find('.form-control').removeClass('is-invalid is-valid');
        form.find('.validation-error').hide().text('');
    };

    const showValidationError = (type, field, message) => {
        const input = $(`#${field}-${type}`);
        const errorElement = $(`#${field}-error-${type}`);
        
        input.addClass('is-invalid');
        errorElement.text(message).show();
    };

    const validateUrl = (url) => {
        const pattern = /^https:\/\/hooks\.slack\.com\/services\/.+/;
        return pattern.test(url);
    };

    const updateStatusDisplay = (type, enabled) => {
        const statusText = $(`#status-text-${type}`);
        const checkbox = $(`#status-${type}`);
        
        checkbox.prop('checked', enabled);
        
        if (enabled) {
            statusText.text('Enabled').removeClass('disabled').addClass('enabled');
        } else {
            statusText.text('Disabled').removeClass('enabled').addClass('disabled');
        }
    };

    const updateDeleteButton = (type, settingId) => {
        const form = $(`.webhook-settings-form[data-type="${type}"]`);
        const buttonContainer = form.find('.d-flex');
        
        // Remove existing delete button
        buttonContainer.find('.delete-webhook').remove();
        
        if (settingId) {
            const deleteButton = `
                <button type="button" class="btn btn-danger btn-sm delete-webhook" data-id="${settingId}" data-type="${type}">
                    <i class="ti ti-trash me-1"></i> Delete
                </button>
            `;
            buttonContainer.append(deleteButton);
        }
    };

    // Handle status toggle change
    $('.status-switch').on('change', function() {
        const type = $(this).data('type');
        const isChecked = $(this).is(':checked');
        
        updateStatusDisplay(type, isChecked);
        
        // Auto-save status change if URL exists
        const url = $(`#url-${type}`).val();
        if (url && validateUrl(url)) {
            autoSaveSettings(type);
        }
    });

    // Auto-save function
    const autoSaveSettings = (type) => {
        const form = $(`.webhook-settings-form[data-type="${type}"]`);
        const formData = new FormData(form[0]);
        const statusCheckbox = $(`#status-${type}`);
        
        formData.append('status', statusCheckbox.is(':checked') ? 'true' : 'false');
        
        $.ajax({
            url: '{{ route("admin.slack.settings.save") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success && response.data) {
                    updateDeleteButton(type, response.data.id);
                }
            },
            error: function(xhr) {
                console.error('Auto-save failed:', xhr.responseJSON);
            }
        });
    };

    // Handle form submission
    $('.webhook-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const type = form.data('type');
        const formData = new FormData(this);
        const statusCheckbox = $(`#status-${type}`);
        const url = $(`#url-${type}`).val();
        
        // Clear previous validation errors
        clearValidationErrors(type);
        
        // Client-side validation
        if (!url) {
            showValidationError(type, 'url', 'Webhook URL is required');
            Swal.fire({
                icon: 'error',
                title: 'Validation Error!',
                text: 'Please enter a webhook URL',
                customClass: {
                    popup: 'swal-dark'
                }
            });
            return;
        }
        
        if (!validateUrl(url)) {
            showValidationError(type, 'url', 'Please enter a valid Slack webhook URL');
            Swal.fire({
                icon: 'error',
                title: 'Validation Error!',
                text: 'Please enter a valid Slack webhook URL starting with https://hooks.slack.com/services/',
                customClass: {
                    popup: 'swal-dark'
                }
            });
            return;
        }
        
        // Add status as boolean value
        formData.append('status', statusCheckbox.is(':checked') ? 'true' : 'false');
        
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Show SweetAlert loading
        showSwalLoading('Saving Settings...', 'Please wait while we save your webhook configuration');
        submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i> Saving...');
        
        $.ajax({
            url: '{{ route("admin.slack.settings.save") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Mark input as valid
                    $(`#url-${type}`).addClass('is-valid');
                    
                    closeSwalLoading();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'swal-dark'
                        }
                    });
                    
                    // Update the status display
                    const isEnabled = statusCheckbox.is(':checked');
                    updateStatusDisplay(type, isEnabled);
                    
                    // Update delete button if new setting
                    if (response.data && response.data.id) {
                        updateDeleteButton(type, response.data.id);
                    }
                } else {
                    closeSwalLoading();
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: response.message || 'Something went wrong!',
                        customClass: {
                            popup: 'swal-dark'
                        }
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'Something went wrong!';
                
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = xhr.responseJSON.errors;
                    errorMessage = Object.values(errors).flat().join(', ');
                    
                    // Show field-specific errors
                    Object.keys(errors).forEach(field => {
                        if (field === 'url') {
                            showValidationError(type, 'url', errors[field][0]);
                        }
                    });
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                closeSwalLoading();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    customClass: {
                        popup: 'swal-dark'
                    }
                });
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle test webhook
    $('.test-webhook').on('click', function() {
        const type = $(this).data('type');
        const url = $(`#url-${type}`).val();
        
        // Clear previous validation errors
        clearValidationErrors(type);
        
        if (!url) {
            showValidationError(type, 'url', 'Please enter a webhook URL first');
            Swal.fire({
                icon: 'warning',
                title: 'Missing URL!',
                text: 'Please enter a webhook URL first',
                customClass: {
                    popup: 'swal-dark'
                }
            });
            return;
        }
        
        if (!validateUrl(url)) {
            showValidationError(type, 'url', 'Please enter a valid Slack webhook URL');
            Swal.fire({
                icon: 'error',
                title: 'Invalid URL!',
                text: 'Please enter a valid Slack webhook URL starting with https://hooks.slack.com/services/',
                customClass: {
                    popup: 'swal-dark'
                }
            });
            return;
        }
        
        const btn = $(this);
        const originalText = btn.html();
        
        // Show SweetAlert loading
        showSwalLoading('Testing Webhook...', 'Sending test message to your Slack channel');
        btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i> Testing...');
        
        $.ajax({
            url: '{{ route("admin.slack.settings.test") }}',
            method: 'POST',
            data: {
                type: type,
                url: url
            },
            success: function(response) {
                if (response.success) {
                    $(`#url-${type}`).addClass('is-valid');
                    
                    closeSwalLoading();
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Test Successful!',
                        text: response.message,
                        timer: 3000,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'swal-dark'
                        }
                    });
                } else {
                    closeSwalLoading();
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Test Failed!',
                        text: response.message,
                        customClass: {
                            popup: 'swal-dark'
                        }
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'Error occurred while testing webhook';
                
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                
                closeSwalLoading();
                
                Swal.fire({
                    icon: 'error',
                    title: 'Test Failed!',
                    text: errorMessage,
                    customClass: {
                        popup: 'swal-dark'
                    }
                });
            },
            complete: function() {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Handle delete webhook
    $(document).on('click', '.delete-webhook', function() {
        const id = $(this).data('id');
        const type = $(this).data('type');
        const btn = $(this);
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the webhook configuration!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ti ti-trash me-1"></i> Yes, delete it!',
            cancelButtonText: '<i class="ti ti-x me-1"></i> Cancel',
            customClass: {
                popup: 'swal-dark'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                showSwalLoading('Deleting Webhook...', 'Please wait while we delete the webhook configuration');
                
                $.ajax({
                    url: `{{ url('admin/slack/settings') }}/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            // Reset form
                            $(`#url-${type}`).val('').removeClass('is-valid is-invalid');
                            updateStatusDisplay(type, false);
                            btn.remove();
                            
                            // Clear validation errors
                            clearValidationErrors(type);
                            
                            closeSwalLoading();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: {
                                    popup: 'swal-dark'
                                }
                            });
                        }
                    },
                    error: function(xhr) {
                        closeSwalLoading();
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'Something went wrong while deleting.',
                            customClass: {
                                popup: 'swal-dark'
                            }
                        });
                    }
                });
            }
        });
    });

    // Handle refresh settings
    $('#refreshSettings').on('click', function() {
        const btn = $(this);
        const originalText = btn.html();
        
        btn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i> Refreshing...');
        
        showSwalLoading('Refreshing Settings...', 'Please wait while we reload the page');
        
        // Simulate refresh by reloading the page
        setTimeout(() => {
            window.location.reload();
        }, 500);
    });

    // Real-time URL validation
    $('.webhook-url').on('input', function() {
        const type = $(this).closest('.webhook-settings-form').data('type');
        const url = $(this).val();
        
        clearValidationErrors(type);
        
        if (url && !validateUrl(url)) {
            showValidationError(type, 'url', 'Please enter a valid Slack webhook URL');
            $(this).addClass('is-invalid');
        } else if (url && validateUrl(url)) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        } else {
            $(this).removeClass('is-invalid is-valid');
        }
    });

    // Initialize tooltips if Bootstrap tooltips are available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
</script>
@endpush
