@extends('customer.layouts.app')

@section('title', 'Fix Domains Split')

@section('content')
<section class="py-3 overflow-hidden">
    <div class="d-flex align-items-center justify-content-between">
        <a href="{{ route('customer.orders') }}" class="d-flex align-items-center justify-content-center"
            style="height: 30px; width: 30px; border-radius: 50px; background-color: #525252c6;">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark">Rejected Panels</span>
        </div>
    </div>

    <div class="mt-3">
        <h5 class="mb-3">Fix Domains Split - Order #{{ $order->id }}</h5>
        <p class="text-white">Update domains for rejected order panels. You can modify the domains but the total count must remain the same.</p>
    </div>    <div class="card shadow-sm">
        <div class="card-body">
            <form id="fixDomainsForm">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">
                
                @foreach($rejectedPanels as $panel)
                    <div class="panel-section mb-4 p-4 rounded">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0 text-primary">
                                <i class="fa-solid fa-server me-2"></i> 
                                Panel #{{ $panel->id }}
                            </h6>
                            <span class="badge bg-primary">{{ $panel->space_assigned }} inboxes</span>
                        </div>
                        
                        @foreach($panel->orderPanelSplits as $split)
                            <div class="split-section mb-4 border rounded overflow-hidden">
                                <!-- Split Summary Header -->
                                <div class="split-header bg-primary text-white p-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            <h6 class="mb-1">
                                                <i class="fa-solid fa-layer-group me-2"></i>
                                                Split #{{ $split->id }}
                                            </h6>
                                            <small class="opacity-75">{{ $split->inboxes_per_domain }} inboxes per domain</small>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <div class="d-flex justify-content-md-end gap-3">
                                                <div class="text-center">
                                                    <div class="h5 mb-0">{{ count($split->domains) }}</div>
                                                    <small class="opacity-75">Domains</small>
                                                </div>
                                                <div class="text-center">
                                                    <div class="h5 mb-0">{{ count($split->domains) * $split->inboxes_per_domain }}</div>
                                                    <small class="opacity-75">Total Inboxes</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Domains Input Section -->
                                <div class="p-3 bg-light">                                    <label class="form-label fw-bold text-white mb-3">
                                        <i class="fa-solid fa-globe me-2"></i>
                                        Domain Names
                                        <span class="badge bg-secondary ms-2">{{ count($split->domains) }} domains required</span>
                                    </label>
                                    
                                    <div class="alert alert-info py-2 mb-3" style="font-size: 0.875rem;">
                                        <i class="fa-solid fa-info-circle me-1"></i>
                                        <strong>Note:</strong> All domains must be unique. Duplicates are not allowed.
                                    </div>
                                    
                                    <div class="row">
                                        @foreach($split->domains as $index => $domain)
                                            <div class="col-md-6 mb-3">
                                                <div class="domain-input-group">
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-primary text-white fw-bold">
                                                            {{ $index + 1 }}
                                                        </span>
                                                        <input type="text" 
                                                               class="form-control domain-input" 
                                                               name="panel_splits[{{ $split->id }}][domains][]" 
                                                               value="{{ $domain }}" 
                                                               placeholder="example.domain.com"
                                                               required>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div class="text-white">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        You can modify domains but must keep the same count
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('customer.orders') }}" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary px-4" id="submitBtn">
                            <i class="fa-solid fa-save me-1"></i> Update Domains
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

@push('styles')
<!-- SweetAlert2 CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<!-- Toastr CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">

<style>
.panel-section {
    /* border: 2px solid var(--border-color, #e3f2fd); */
    transition: all 0.3s ease;
}

.panel-section:hover {
    border-color: var(--second-primary);
    box-shadow: 0 4px 12px rgba(87, 80, 191, 0.15);
}

.split-section {
    border: 1px solid var(--border-color, #dee2e6);
    transition: all 0.3s ease;
}

.split-section:hover {
    border-color: var(--second-primary);
    box-shadow: 0 2px 8px rgba(87, 80, 191, 0.1);
}

.split-header {
    background: var(--second-primary) !important;
}

.domain-input-group {
    transition: all 0.2s ease;
}

.domain-input-group:hover {
    transform: translateY(-2px);
}

.domain-input {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    border: 2px solid var(--input-border-color, #e9ecef);
    transition: all 0.3s ease;
}

.domain-input:focus {
    border-color: var(--second-primary);
    box-shadow: 0 0 0 0.2rem rgba(87, 80, 191, 0.25);
    transform: scale(1.02);
}

.domain-input.is-invalid {
    border-color: var(--danger-color, #dc3545);
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.domain-input.is-invalid::placeholder {
    color: rgba(220, 53, 69, 0.6);
}

.domain-input.is-duplicate {
    border-color: #fd7e14;
    box-shadow: 0 0 0 0.2rem rgba(253, 126, 20, 0.25);
    background-color: rgba(253, 126, 20, 0.1);
}

.input-group-text {
    font-weight: bold;
    min-width: 45px;
    justify-content: center;
    border: 2px solid var(--second-primary);
    background: var(--second-primary);
    color: var(--white-color, #ffffff);
}

.btn-primary {
    background: var(--second-primary);
    border-color: var(--second-primary);
    color: var(--white-color, #ffffff);
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: var(--primary-color, var(--second-primary));
    border-color: var(--primary-color, var(--second-primary));
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(87, 80, 191, 0.3);
}

.btn-outline-secondary {
    border: 2px solid var(--second-primary);
    color: var(--second-primary);
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
    background: var(--second-primary);
    border-color: var(--second-primary);
    color: var(--white-color, #ffffff);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(87, 80, 191, 0.2);
}

.bg-primary {
    background-color: var(--second-primary) !important;
}

.text-primary {
    color: var(--second-primary) !important;
}

.badge.bg-primary {
    background-color: var(--second-primary) !important;
    color: var(--white-color, #ffffff) !important;
}

.badge.bg-secondary {
    background-color: var(--gray-color, #6c757d) !important;
    color: var(--white-color, #ffffff) !important;
}

#submitBtn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.opacity-50 {
    opacity: 0.5 !important;
}

.badge {
    font-size: 0.75rem;
    padding: 0.5rem 0.75rem;
}

.card {
    border: none;
    /* background: var(--card-bg, #ffffff); */
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.alert {
    border: none;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.bg-light {
    background-color: rgba(87, 80, 191, 0.2) !important;
}

/* Enhanced Toastr styling */
.toast-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
}

.toast-error {
    background-color: #dc3545 !important;
}

.toast-success {
    background-color: #28a745 !important;
}

.toast {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
    border-radius: 8px !important;
}

.toast-message {
    line-height: 1.4 !important;
    font-size: 14px !important;
}

.toast-title {
    font-weight: bold !important;
    margin-bottom: 5px !important;
}
</style>
@endpush

@push('scripts')
<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- Toastr JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<script>
// Debug: Check for any existing event listeners that might cause conflicts
console.log('Loading Fix Domains script...');

// Check if jQuery is loaded
if (typeof jQuery === 'undefined') {
    console.error('jQuery is not loaded. Please ensure jQuery is included before this script.');
    
    // Fallback: Try to run with vanilla JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Fallback: Using vanilla JavaScript');
        const form = document.getElementById('fixDomainsForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (!form || !submitBtn) {
            console.error('Required form elements not found in fallback mode');
            return;
        }
        
        console.log('Fallback mode: Elements found, but limited functionality available');
    });
} else {
    // Use jQuery with noConflict to avoid conflicts
    (function($) {
        $(document).ready(function() {
            console.log('Fix Domains script loaded successfully');
            
            // Check if required elements exist
            if (!$('#fixDomainsForm').length || !$('#submitBtn').length) {
                console.error('Required form elements not found');
                console.log('Form element count:', $('#fixDomainsForm').length);
                console.log('Submit button count:', $('#submitBtn').length);
                return;
            }
            
            console.log('All required elements found, initializing form handlers');
              // Form validation and submission
            $('#fixDomainsForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = $('#submitBtn');
        
        // Show confirmation dialog
        Swal.fire({
            title: 'Update Domains?',
            text: 'Are you sure you want to update the domain configuration?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: 'var(--second-primary)',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Update',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                submitFormWithValidation(form, submitBtn);
            }else{
                // enabled button
                updateSubmitButtonState();
            }
        });
    });    // Function to handle form submission with validation
    function submitFormWithValidation(form, submitBtn) {
        // Validate all domains are filled and are valid domain names
        let allValid = true;
        let errorMessages = [];
        let domainValues = [];
        let emptyFields = 0;
        let invalidFormats = 0;
        let duplicateNames = [];

        $('.domain-input').each(function() {
            const domain = $(this).val().trim();
            const domainRegex = /^[^\s]+\.[^\s]+$/;
            
            if (!domain) {
                allValid = false;
                $(this).addClass('is-invalid');
                emptyFields++;
            } else if (!domainRegex.test(domain)) {
                allValid = false;
                $(this).addClass('is-invalid');
                invalidFormats++;
            } else {
                $(this).removeClass('is-invalid');
                domainValues.push(domain.toLowerCase());
            }
        });

        // Check for duplicate domains
        const duplicateDomains = domainValues.filter((domain, index) => domainValues.indexOf(domain) !== index);
        if (duplicateDomains.length > 0) {
            allValid = false;
            duplicateNames = [...new Set(duplicateDomains)]; // Remove duplicates from duplicate list
            // Mark duplicate domain inputs as invalid
            $('.domain-input').each(function() {
                const domain = $(this).val().trim().toLowerCase();
                if (duplicateDomains.includes(domain)) {
                    $(this).addClass('is-invalid');
                }
            });
        }

        // Build numbered error messages
        if (emptyFields > 0) {
            errorMessages.push(`${emptyFields} empty domain field${emptyFields > 1 ? 's' : ''} found. Please fill all required fields.`);
        }
        if (invalidFormats > 0) {
            errorMessages.push(`${invalidFormats} invalid domain format${invalidFormats > 1 ? 's' : ''} detected. Please use valid domain names (e.g., example.com).`);
        }
        if (duplicateNames.length > 0) {
            errorMessages.push(`${duplicateNames.length} duplicate domain${duplicateNames.length > 1 ? 's' : ''} found: ${duplicateNames.join(', ')}. Each domain must be unique.`);
        }
        
        if (!allValid) {
            showValidationToast(errorMessages);
            console.log('Form submission failed:', errorMessages);
            updateSubmitButtonState();
            return;
        }
        
        // Show loading state
        Swal.fire({
            title: 'Updating Domains...',
            text: 'Please wait while we update your domain configuration.',
            icon: 'info',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Submit the form
        $.ajax({
            url: '{{ route("customer.orders.update-fixed-domains", $order->id) }}',
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                Swal.close();
                if (response.success) {
                    showToast('success', response.message);
                    setTimeout(function() {
                        window.location.href = '{{ route("customer.orders") }}';
                    }, 2000);
                } else {
                    showToast('error', response.message || 'Failed to update domains.');
                }
            },
            error: function(xhr) {
                Swal.close();
                let errorMessage = 'Failed to update domains.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                }
                showToast('error', errorMessage);
            }
        });
    }// Real-time validation for domain fields
    $('.domain-input').on('input blur', function() {
        if (!$(this).length) return; // Safety check
        
        validateAllDomainsRealTime();
        updateSubmitButtonState();
    });    // Function for real-time domain validation
    function validateAllDomainsRealTime() {
        // Reset all validation states
        $('.domain-input').removeClass('is-invalid');
        $('.input-group-text').removeClass('border-danger').addClass('border-primary');
        
        let domainCounts = {};
        let invalidInputs = [];
        let emptyFields = 0;
        let invalidFormats = 0;
        let duplicateCount = 0;
        let duplicateNames = [];
        const domainRegex = /^[^\s]+\.[^\s]+$/;
        
        // First pass: validate format and count domains
        $('.domain-input').each(function() {
            const currentInput = $(this);
            const domain = currentInput.val().trim();
            
            if (domain) {
                // Check domain format
                if (!domainRegex.test(domain)) {
                    currentInput.addClass('is-invalid');
                    invalidInputs.push(currentInput);
                    invalidFormats++;
                } else {
                    // Count domain occurrences (case-insensitive)
                    const lowerDomain = domain.toLowerCase();
                    domainCounts[lowerDomain] = (domainCounts[lowerDomain] || []);
                    domainCounts[lowerDomain].push(currentInput);
                }
            } else {
                // Empty domain
                currentInput.addClass('is-invalid');
                invalidInputs.push(currentInput);
                emptyFields++;
            }
        });
        
        // Second pass: mark duplicates
        Object.keys(domainCounts).forEach(domain => {
            if (domainCounts[domain].length > 1) {
                duplicateCount += domainCounts[domain].length;
                duplicateNames.push(domain);
                domainCounts[domain].forEach(input => {
                    input.addClass('is-invalid');
                    invalidInputs.push(input);
                });
            }
        });
        
        // Update input group styling for all invalid inputs
        invalidInputs.forEach(input => {
            const inputGroup = input.closest('.input-group');
            if (inputGroup.length) {
                inputGroup.find('.input-group-text').addClass('border-danger').removeClass('border-primary');
            }
        });
        
        // Show validation errors in toaster if any found
        if (emptyFields > 0 || invalidFormats > 0 || duplicateCount > 0) {
            let errorMessages = [];
            
            if (emptyFields > 0) {
                errorMessages.push(`${emptyFields} empty domain field${emptyFields > 1 ? 's' : ''} found`);
            }
            if (invalidFormats > 0) {
                errorMessages.push(`${invalidFormats} invalid domain format${invalidFormats > 1 ? 's' : ''} detected`);
            }
            if (duplicateCount > 0) {
                errorMessages.push(`${duplicateNames.length} duplicate domain${duplicateNames.length > 1 ? 's' : ''} found: ${duplicateNames.join(', ')}`);
            }
            
            // Only show toaster after a short delay to avoid spam during typing
            clearTimeout(window.validationToastTimeout);
            window.validationToastTimeout = setTimeout(() => {
                showValidationToast(errorMessages);
            }, 1000); // 1 second delay
        } else {
            // Clear any pending validation toasts if all is valid
            clearTimeout(window.validationToastTimeout);
        }
    }

    // Function to update submit button state
    function updateSubmitButtonState() {
        const hasInvalidInputs = $('.domain-input.is-invalid').length > 0;
        const hasEmptyInputs = $('.domain-input').filter(function() {
            return $(this).val().trim() === '';
        }).length > 0;
        
        const submitBtn = $('#submitBtn');
        if (hasInvalidInputs || hasEmptyInputs) {
            submitBtn.prop('disabled', true).addClass('opacity-50');
        } else {
            submitBtn.prop('disabled', false).removeClass('opacity-50');
        }
    }      // Function to show toast notifications
    function showToast(type, message) {
        // Remove HTML tags for toast display
        const cleanMessage = message.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
        
        if (typeof toastr !== 'undefined') {
            // Use toastr if available
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": false,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };
            
            if (type === 'success') {
                toastr.success(cleanMessage, 'Success');
            } else {
                toastr.error(cleanMessage, 'Error');
            }
        } else {
            // Fallback to SweetAlert if toastr is not available
            Swal.fire({
                title: type === 'success' ? 'Success!' : 'Error!',
                text: cleanMessage,
                icon: type === 'success' ? 'success' : 'error',
                confirmButtonColor: 'var(--second-primary)',
                timer: type === 'success' ? 3000 : null,
                timerProgressBar: true
            });
        }
    }

    // Function to show validation errors in toaster
    function showValidationToast(errorMessages) {
        if (!errorMessages || errorMessages.length === 0) return;
        
        // Format numbered error messages
        const numberedErrors = errorMessages.map((error, index) => `${index + 1}. ${error}`);
        const formattedMessage = numberedErrors.join('\n');
        
        if (typeof toastr !== 'undefined') {
            // Clear any existing validation toasts first
            toastr.clear();
            
            toastr.options = {
                "closeButton": true,
                "debug": false,
                "newestOnTop": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "preventDuplicates": true,
                "onclick": null,
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "6000", // Longer timeout for validation errors
                "extendedTimeOut": "2000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut",
                "escapeHtml": false
            };
            
            // Convert line breaks to HTML for better formatting
            const htmlMessage = formattedMessage.replace(/\n/g, '<br>');            toastr.warning(htmlMessage, '⚠️ Validation Issues');
        } else {
            // Fallback to SweetAlert
            Swal.fire({
                title: '⚠️ Validation Issues',
                html: formattedMessage.replace(/\n/g, '<br>'),
                icon: 'warning',
                confirmButtonColor: 'var(--second-primary)',
                timer: 6000,
                timerProgressBar: true,
                width: '450px'
            });
        }
    }
    
        // Initialize input styling and validation
        if ($('.domain-input').length) {
            $('.domain-input').each(function() {
                $(this).trigger('blur');
            });
            // Run initial validation
            validateAllDomainsRealTime();
            updateSubmitButtonState();
        }
        
        }); // End of $(document).ready()
    })(jQuery); // End of noConflict wrapper
} // End of jQuery check
</script>
@endpush
@endsection
