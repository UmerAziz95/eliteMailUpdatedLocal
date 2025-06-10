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
</style>
@endpush

@push('scripts')
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
        const originalText = submitBtn.html();
          // Validate all domains are filled and are valid domain names
        let allValid = true;
        let errorMessages = [];$('.domain-input').each(function() {
            const email = $(this).val().trim();
            const emailRegex = /^[^\s]+\.[^\s]+$/;
            
            if (!email) {
                allValid = false;
                $(this).addClass('is-invalid');
                errorMessages.push('All domain fields must be filled.');            } else if (!emailRegex.test(email)) {
                allValid = false;
                $(this).addClass('is-invalid');
                errorMessages.push('Please enter valid domain names.');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!allValid) {
            const uniqueErrors = [...new Set(errorMessages)];
            showAlert('error', uniqueErrors.join('<br>'));
            return;
        }
        
        // Disable submit button and show loading
        submitBtn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...');
        
        // Submit the form
        $.ajax({
            url: '{{ route("customer.orders.update-fixed-domains", $order->id) }}',
            method: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    showAlert('success', response.message);
                    setTimeout(function() {
                        window.location.href = '{{ route("customer.orders") }}';
                    }, 2000);
                } else {
                    showAlert('error', response.message || 'Failed to update domains.');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to update domains.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errors = Object.values(xhr.responseJSON.errors).flat();
                    errorMessage = errors.join('<br>');
                }
                showAlert('error', errorMessage);
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });
      // Real-time validation for domain fields
    $('.domain-input').on('input blur', function() {
        if (!$(this).length) return; // Safety check
          const email = $(this).val().trim();
        const emailRegex = /^[^\s]+\.[^\s]+$/;
        
        if (email && !emailRegex.test(email)) {
            $(this).addClass('is-invalid');
        } else if (email) {
            $(this).removeClass('is-invalid');
        }
        
        // Update the input group styling
        const inputGroup = $(this).closest('.input-group');
        if (inputGroup.length) {
            if ($(this).hasClass('is-invalid')) {
                inputGroup.find('.input-group-text').addClass('border-danger').removeClass('border-primary');
            } else {
                inputGroup.find('.input-group-text').addClass('border-primary').removeClass('border-danger');
            }
        }
    });
      // Function to show alerts
    function showAlert(type, message) {
        if (!$('#fixDomainsForm').length) return; // Safety check
        
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const iconClass = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';
        
        const alertHtml = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fa-solid ${iconClass} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Remove existing alerts
        $('.alert').remove();
        
        // Add new alert at the top of the form
        $('#fixDomainsForm').prepend(alertHtml);
        
        // Scroll to top to show alert
        if ($('#fixDomainsForm').offset()) {
            $('html, body').animate({
                scrollTop: $('#fixDomainsForm').offset().top - 20
            }, 300);
        }
        
        // Auto-dismiss success alerts
        if (type === 'success') {
            setTimeout(function() {
                $('.alert-success').fadeOut();
            }, 3000);
        }
    }        // Initialize input styling - with safety check
        if ($('.domain-input').length) {
            $('.domain-input').each(function() {
                $(this).trigger('blur');
            });
        }
        
        }); // End of $(document).ready()
    })(jQuery); // End of noConflict wrapper
} // End of jQuery check
</script>
@endpush
@endsection
