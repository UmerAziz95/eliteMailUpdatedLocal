@extends('admin.layouts.app')

@section('title', 'Disclaimer Settings')

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

    .disclaimer-form {
        background-color: #ffffff1d;
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .disclaimer-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .disclaimer-title {
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

    .char-counter {
        font-size: 0.75rem;
        color: rgba(255, 255, 255, 0.6);
        float: right;
    }

    .char-counter.warning {
        color: #ffc107;
    }

    .char-counter.danger {
        color: #dc3545;
    }
</style>
@endpush

@section('content')
<div class="row py-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="text-white">
                <i class="ti ti-file-text me-2"></i>
                Disclaimer Settings
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
                        About Disclaimers
                    </h5>
                    <p class="mb-2 mx-2 text-muted">Manage disclaimers that appear on different pages of your application:</p>
                    <ul class="mb-0">
                        <li>Create custom disclaimers for specific pages (Order, Checkout, Dashboard, etc.)</li>
                        <li>Enable or disable disclaimers without deleting them</li>
                        <li>Rich text content support with up to 5000 characters</li>
                        <li>Active disclaimers are automatically displayed on their respective pages</li>
                    </ul>
                </div>

                <!-- Disclaimer Forms Container -->
                <div id="disclaimerFormsContainer">
                    @foreach($types as $typeKey => $typeLabel)
                        <div class="disclaimer-form" id="form-{{ $typeKey }}" data-type="{{ $typeKey }}">
                            <div class="disclaimer-header">
                                <h5 class="disclaimer-title">
                                    <i class="ti ti-file-description me-2"></i>
                                    {{ $typeLabel }}
                                </h5>
                                <div class="status-toggle">
                                    <label for="status-{{ $typeKey }}" class="form-label mb-0">Enable</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input status-switch" type="checkbox" role="switch" 
                                               id="status-{{ $typeKey }}" 
                                               data-type="{{ $typeKey }}"
                                               {{ isset($disclaimers[$typeKey]) && $disclaimers[$typeKey] && $disclaimers[$typeKey]->status ? 'checked' : '' }}>
                                    </div>
                                    <span class="status-indicator {{ isset($disclaimers[$typeKey]) && $disclaimers[$typeKey] && $disclaimers[$typeKey]->status ? 'enabled' : 'disabled' }}" 
                                          id="status-text-{{ $typeKey }}">
                                        {{ isset($disclaimers[$typeKey]) && $disclaimers[$typeKey] && $disclaimers[$typeKey]->status ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
                            </div>

                            <form class="disclaimer-settings-form" data-type="{{ $typeKey }}">
                                @csrf
                                <input type="hidden" name="type" value="{{ $typeKey }}">
                                
                                <div class="form-group">
                                    <label for="content-{{ $typeKey }}" class="form-label">
                                        <i class="ti ti-text me-1"></i>
                                        Disclaimer Content *
                                        <span class="char-counter" id="char-counter-{{ $typeKey }}">0 / 5000</span>
                                    </label>
                                    <textarea 
                                        class="form-control disclaimer-content" 
                                        id="content-{{ $typeKey }}" 
                                        name="content" 
                                        rows="6" 
                                        maxlength="5000"
                                        placeholder="Enter your disclaimer text here..."
                                        required>{{ isset($disclaimers[$typeKey]) && $disclaimers[$typeKey] ? $disclaimers[$typeKey]->content : '' }}</textarea>
                                    <small class="form-text text-muted">
                                        Enter the disclaimer text for {{ strtolower($typeLabel) }}. Supports line breaks and basic formatting.
                                    </small>
                                    <div class="validation-error" id="content-error-{{ $typeKey }}" style="display: none;"></div>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-save btn-sm">
                                        <i class="ti ti-device-floppy me-1"></i> Save Disclaimer
                                    </button>
                                    @if(isset($disclaimers[$typeKey]) && $disclaimers[$typeKey])
                                        <button type="button" class="btn btn-danger btn-sm delete-disclaimer" data-id="{{ $disclaimers[$typeKey]->id }}" data-type="{{ $typeKey }}">
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
        const form = $(`.disclaimer-settings-form[data-type="${type}"]`);
        form.find('.form-control').removeClass('is-invalid is-valid');
        form.find('.validation-error').hide().text('');
    };

    const showValidationError = (type, field, message) => {
        const input = $(`#${field}-${type}`);
        const errorElement = $(`#${field}-error-${type}`);
        
        input.addClass('is-invalid');
        errorElement.text(message).show();
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

    const updateDeleteButton = (type, disclaimerId) => {
        const form = $(`.disclaimer-settings-form[data-type="${type}"]`);
        const buttonContainer = form.find('.d-flex');
        
        // Remove existing delete button
        buttonContainer.find('.delete-disclaimer').remove();
        
        if (disclaimerId) {
            const deleteButton = `
                <button type="button" class="btn btn-danger btn-sm delete-disclaimer" data-id="${disclaimerId}" data-type="${type}">
                    <i class="ti ti-trash me-1"></i> Delete
                </button>
            `;
            buttonContainer.append(deleteButton);
        }
    };

    const updateCharCounter = (type) => {
        const textarea = $(`#content-${type}`);
        const counter = $(`#char-counter-${type}`);
        const length = textarea.val().length;
        const maxLength = 5000;
        
        counter.text(`${length} / ${maxLength}`);
        
        // Update counter color based on usage
        counter.removeClass('warning danger');
        if (length > maxLength * 0.9) {
            counter.addClass('danger');
        } else if (length > maxLength * 0.7) {
            counter.addClass('warning');
        }
    };

    // Initialize character counters
    $('.disclaimer-content').each(function() {
        const type = $(this).closest('.disclaimer-settings-form').data('type');
        updateCharCounter(type);
    });

    // Update character counter on input
    $('.disclaimer-content').on('input', function() {
        const type = $(this).closest('.disclaimer-settings-form').data('type');
        updateCharCounter(type);
    });

    // Handle status toggle change
    $('.status-switch').on('change', function() {
        const type = $(this).data('type');
        const isChecked = $(this).is(':checked');
        
        updateStatusDisplay(type, isChecked);
        
        // Auto-save status change if content exists
        const content = $(`#content-${type}`).val();
        if (content && content.trim().length > 0) {
            autoSaveSettings(type);
        }
    });

    // Auto-save function
    const autoSaveSettings = (type) => {
        const form = $(`.disclaimer-settings-form[data-type="${type}"]`);
        const formData = new FormData(form[0]);
        const statusCheckbox = $(`#status-${type}`);
        
        formData.append('status', statusCheckbox.is(':checked') ? 'true' : 'false');
        
        $.ajax({
            url: '{{ route("admin.disclaimers.save") }}',
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
    $('.disclaimer-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const type = form.data('type');
        const formData = new FormData(this);
        const statusCheckbox = $(`#status-${type}`);
        const content = $(`#content-${type}`).val();
        
        // Clear previous validation errors
        clearValidationErrors(type);
        
        // Client-side validation
        if (!content || content.trim().length === 0) {
            showValidationError(type, 'content', 'Disclaimer content is required');
            Swal.fire({
                icon: 'error',
                title: 'Validation Error!',
                text: 'Please enter disclaimer content',
                customClass: {
                    popup: 'swal-dark'
                }
            });
            return;
        }
        
        if (content.length > 5000) {
            showValidationError(type, 'content', 'Content cannot exceed 5000 characters');
            Swal.fire({
                icon: 'error',
                title: 'Validation Error!',
                text: 'Disclaimer content cannot exceed 5000 characters',
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
        showSwalLoading('Saving Disclaimer...', 'Please wait while we save your disclaimer');
        submitBtn.prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i> Saving...');
        
        $.ajax({
            url: '{{ route("admin.disclaimers.save") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Mark input as valid
                    $(`#content-${type}`).addClass('is-valid');
                    
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
                    
                    // Update delete button if new disclaimer
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
                        if (field === 'content') {
                            showValidationError(type, 'content', errors[field][0]);
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

    // Handle delete disclaimer
    $(document).on('click', '.delete-disclaimer', function() {
        const id = $(this).data('id');
        const type = $(this).data('type');
        const btn = $(this);
        
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete the disclaimer!",
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
                showSwalLoading('Deleting Disclaimer...', 'Please wait while we delete the disclaimer');
                
                $.ajax({
                    url: `{{ url('admin/disclaimers') }}/${id}`,
                    method: 'DELETE',
                    success: function(response) {
                        if (response.success) {
                            // Reset form
                            $(`#content-${type}`).val('').removeClass('is-valid is-invalid');
                            updateStatusDisplay(type, false);
                            updateCharCounter(type);
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

    // Real-time content validation
    $('.disclaimer-content').on('input', function() {
        const type = $(this).closest('.disclaimer-settings-form').data('type');
        const content = $(this).val();
        
        clearValidationErrors(type);
        
        if (content && content.length > 5000) {
            showValidationError(type, 'content', 'Content cannot exceed 5000 characters');
            $(this).addClass('is-invalid');
        } else if (content && content.trim().length > 0) {
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
