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
            <h5 class="mb-3">Fix Domains {{--Split - Order #{{ $order->id }} --}}</h5>
            <p class="text-white">Update domains for rejected order panels. You can modify the domains but the total count
                must remain the same.</p>

            <!-- Validation Summary -->
            <div class="alert alert-info d-none" id="validation-summary">
                <div class="d-flex align-items-center">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    <span id="validation-summary-text">Ready to validate...</span>
                </div>
            </div>
        </div>

        {{-- Include Domain & Platform Configuration Section --}}
        @include('customer.orders.partials._domain_platform_config')

        <div class="card p-3">
            <form id="fixDomainsForm">
                @csrf
                <input type="hidden" name="order_id" value="{{ $order->id }}">

                @foreach ($rejectedPanels as $panel)
                    <div class="panel-section mb-4 rounded">
                        {{-- <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="mb-0 text-primary">
                                <i class="fa-solid fa-server me-2"></i>
                                Panel #{{ $panel->id }}
                            </h6>
                            <span class="badge bg-primary">{{ $panel->space_assigned }} inboxes</span>
                        </div> --}}

                        @foreach ($panel->orderPanelSplits as $split)
                            <div class="split-section mb-4 border rounded overflow-hidden">
                                <!-- Split Summary Header -->
                                <div class="split-header bg-primary text-white p-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-6">
                                            {{-- <h6 class="mb-1">
                                                <i class="fa-solid fa-layer-group me-2"></i>
                                                Split #{{ $split->id }}
                                            </h6> --}}
                                            <h6 class="text-white">{{ $split->inboxes_per_domain }} inboxes per
                                                domain</h6>
                                        </div>
                                        <div class="col-md-6 text-md-end">
                                            <div class="d-flex justify-content-md-end gap-3">
                                                <div class="text-center">
                                                    <div class="h5 mb-0">{{ count($split->domains) }}</div>
                                                    <small class="opacity-75">Domains</small>
                                                </div>
                                                <div class="text-center">
                                                    <div class="h5 mb-0">
                                                        {{ count($split->domains) * $split->inboxes_per_domain }}</div>
                                                    <small class="opacity-75">Total Inboxes</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Domains Input Section -->
                                <div class="p-3 bg-light"> <label class="form-label fw-bold text-white mb-3">
                                        <i class="fa-solid fa-globe me-2"></i>
                                        Domain Names
                                        <span class="badge bg-secondary ms-2">{{ count($split->domains) }} domains
                                            required</span>
                                    </label>
                                    <div class="py-2 mb-3 px-3 text-white rounded-1" style=" background-color: rgba(0, 213, 255, 0.447); font-size: 0.875rem;">
                                        <i class="fa-solid fa-info-circle me-1"></i>
                                        <strong>Note:</strong> Enter domains separated by new lines or commas. All
                                        domains must be unique. Duplicates are not allowed.
                                    </div>
                                    <div class="domain-textarea-group">
                                        <textarea class="form-control domain-textarea" name="panel_splits[{{ $split->id }}][domains]" rows="6"
                                            placeholder="Enter domains (one per line or comma-separated):&#10;example1.com&#10;example2.com, example3.com&#10;example4.com"
                                            data-split-id="{{ $split->id }}" data-required-count="{{ count($split->domains) }}" required>{{ implode("\n", $split->domains) }}</textarea>
                                        <div class="invalid-feedback" id="domains-error-{{ $split->id }}"></div>
                                        <div class="d-flex justify-content-between mt-1">
                                            <small class="form-text text-white">
                                                <i class="fa-solid fa-info-circle me-1"></i>
                                                Required: {{ count($split->domains) }} domains
                                            </small>
                                            <small class="form-text text-white" id="count-display-{{ $split->id }}">
                                                <span class="domain-count">0</span> / {{ count($split->domains) }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach

                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div class="text-white">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        You can modify domains but must keep the same count
                        <br>
                        <small>
                            <i class="fa-solid fa-keyboard me-1"></i>
                            Shortcuts: Ctrl+S to save, Ctrl+R to reset
                        </small>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('customer.orders') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-times me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-sm btn-primary px-4" id="submitBtn" title="Ctrl+S">
                            <i class="fa-solid fa-save me-1"></i> Update Domains
                        </button>
                    </div>
                </div>
            </form>
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

            .domain-textarea-group {
                transition: all 0.2s ease;
            }

            .domain-textarea-group:hover {
                transform: translateY(-2px);
            }

            .domain-textarea {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                border: 2px solid var(--input-border-color, #e9ecef);
                transition: all 0.3s ease;
                resize: vertical;
                min-height: 150px;
                max-height: 400px;
                overflow-y: auto;
            }

            .domain-textarea:focus {
                border-color: var(--second-primary);
                box-shadow: 0 0 0 0.2rem rgba(87, 80, 191, 0.25);
                transform: scale(1.01);
            }

            .domain-textarea.is-invalid {
                border-color: var(--danger-color, #dc3545);
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
            }

            .domain-textarea.is-invalid::placeholder {
                color: rgba(220, 53, 69, 0.6);
            }

            .domain-textarea.is-duplicate {
                border-color: #fd7e14;
                box-shadow: 0 0 0 0.2rem rgba(253, 126, 20, 0.25);
                background-color: rgba(253, 126, 20, 0.1);
            }

            /* Enhanced feedback for textareas */
            .domain-textarea.is-valid {
                border-color: #28a745;
                box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
            }

            .domain-textarea {
                line-height: 1.5;
                font-size: 14px;
            }

            .domain-textarea:focus {
                outline: none;
            }

            /* Validation feedback styling */
            .invalid-feedback {
                display: block;
                color: #dc3545;
                font-size: 0.875rem;
                margin-top: 0.25rem;
            }

            /* Domain count display styling */
            .text-success {
                color: #28a745 !important;
            }

            .text-warning {
                color: #ffc107 !important;
            }

            .text-danger {
                color: #dc3545 !important;
            }

            .domain-count {
                font-weight: bold;
            }

            .input-group-text {
                font-weight: bold;
                min-width: 45px;
                justify-content: center;
                border: 2px solid var(--second-primary);
                background: var(--second-primary);
                color: var(--white-color, #ffffff);
            }

            /* .btn-primary {
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
        } */

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
                                } else {
                                    // enabled button
                                    updateSubmitButtonState();
                                }
                            });
                        }); // Function to handle form submission with validation
                        function submitFormWithValidation() {
                            if (!validatePlatformConfig()) {
                                showValidationToast(['Platform configuration failed.']);
                                return;
                            }

                            let allValid = true;
                            const allDomains = [];
                            const splitDomainMap = {};
                            const duplicateCheck = new Set();
                            const duplicates = new Set();
                            const errorMessages = [];
                            let emptyCount = 0,
                                invalidFormatCount = 0,
                                countMismatch = 0;

                            $('.domain-textarea').each(function() {
                                const textarea = $(this);
                                const splitId = textarea.data('split-id');
                                const requiredCount = parseInt(textarea.data('required-count')) || 0;
                                const value = textarea.val().trim();
                                const errorEl = $(`#domains-error-${splitId}`);

                                textarea.removeClass('is-invalid');
                                errorEl.text('');

                                if (!value) {
                                    allValid = false;
                                    emptyCount++;
                                    textarea.addClass('is-invalid');
                                    errorEl.text('Required field is empty.');
                                    return;
                                }

                                const domains = value
                                    .split(/[\n,]+/)
                                    .map(d => d.trim().toLowerCase())
                                    .filter(d => d);

                                if (domains.length !== requiredCount) {
                                    allValid = false;
                                    countMismatch++;
                                    textarea.addClass('is-invalid');
                                    errorEl.text(`Expected ${requiredCount}, got ${domains.length}.`);
                                    return;
                                }

                                const invalid = domains.filter(d => !/^[^\s]+\.[^\s]+$/.test(d));
                                if (invalid.length > 0) {
                                    allValid = false;
                                    invalidFormatCount++;
                                    textarea.addClass('is-invalid');
                                    errorEl.text(`Invalid domains: ${invalid.join(', ')}`);
                                    return;
                                }

                                // Check duplicates
                                domains.forEach(domain => {
                                    if (duplicateCheck.has(domain)) {
                                        duplicates.add(domain);
                                    } else {
                                        duplicateCheck.add(domain);
                                    }
                                });

                                splitDomainMap[splitId] = {
                                    domains: domains
                                };
                                allDomains.push(...domains);
                            });

                            if (duplicates.size > 0) {
                                allValid = false;
                                const dupes = Array.from(duplicates);
                                $('.domain-textarea').each(function() {
                                    const textarea = $(this);
                                    const splitId = textarea.data('split-id');
                                    const domains = textarea.val()
                                        .split(/[\n,]+/)
                                        .map(d => d.trim().toLowerCase())
                                        .filter(Boolean);

                                    const overlap = domains.filter(d => dupes.includes(d));
                                    if (overlap.length) {
                                        textarea.addClass('is-invalid');
                                        $(`#domains-error-${splitId}`).text(
                                            `Duplicate(s): ${overlap.join(', ')}`);
                                    }
                                });
                                errorMessages.push(`Duplicate domains found: ${Array.from(duplicates).join(', ')}`);
                            }

                            if (emptyCount > 0) errorMessages.push(`${emptyCount} empty textarea(s).`);
                            if (countMismatch > 0) errorMessages.push(`${countMismatch} domain count mismatch.`);
                            if (invalidFormatCount > 0) errorMessages.push(
                                `${invalidFormatCount} invalid format issue(s).`);

                            if (!allValid) {
                                showValidationToast(errorMessages);
                                return;
                            }

                            // Show loading dialog
                            Swal.fire({
                                title: 'Submitting...',
                                text: 'Please wait while we submit your domains.',
                                icon: 'info',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                didOpen: () => Swal.showLoading()
                            });

                            // Collect platform configuration data
                            const platformData = {};
                            const platformFields = [
                                'forwarding_url', 'hosting_platform', 'sending_platform',
                                'platform_login', 'platform_password', 'sequencer_login', 'sequencer_password',
                                'access_tutorial', 'backup_codes', 'bison_url', 'bison_workspace',
                                'other_platform'
                            ];

                            platformFields.forEach(fieldName => {
                                const field = $(`[name="${fieldName}"]`);
                                if (field.length) {
                                    platformData[fieldName] = field.val() || '';
                                }
                            });

                            // Collect dynamic platform fields
                            $('.platform-field input, .platform-field select, .platform-field textarea').each(
                                function() {
                                    const field = $(this);
                                    const fieldName = field.attr('name');
                                    if (fieldName) {
                                        platformData[fieldName] = field.val() || '';
                                    }
                                });

                            // Submit consolidated JSON
                            $.ajax({
                                url: '{{ route('customer.orders.update-fixed-domains', $order->id) }}',
                                method: 'POST',
                                contentType: 'application/json',
                                data: JSON.stringify({
                                    _token: '{{ csrf_token() }}',
                                    panel_splits: splitDomainMap,
                                    ...platformData
                                }),
                                success: function(response) {
                                    Swal.close();
                                    if (response.success) {
                                        showToast('success', response.message);
                                        setTimeout(() => window.location.href =
                                            '{{ route('customer.orders') }}', 2000);
                                    } else {
                                        showToast('error', response.message ||
                                            'Failed to update domains.');
                                    }
                                },
                                error: function(xhr) {
                                    Swal.close();
                                    let message = 'Error occurred.';
                                    if (xhr.responseJSON?.message) {
                                        message = xhr.responseJSON.message;
                                    } else if (xhr.responseJSON?.errors) {
                                        message = Object.values(xhr.responseJSON.errors).flat().join(
                                            '<br>');
                                    }
                                    showToast('error', message);
                                }
                            });
                        }


                        // Function to prepare form data with proper array formattijng
                        function prepareFormData(form) {
                            const formData = new FormData();

                            // Add CSRF token
                            formData.append('_token', $('input[name="_token"]').val());
                            // Add order_id
                            formData.append('order_id', $('input[name="order_id"]').val());

                            // Add platform configuration data
                            const platformFields = [
                                'forwarding_url', 'hosting_platform', 'sending_platform',
                                'platform_login', 'platform_password', 'sequencer_login', 'sequencer_password',
                                'access_tutorial', 'backup_codes', 'bison_url', 'bison_workspace',
                                'other_platform'
                            ];

                            platformFields.forEach(fieldName => {
                                const field = $(`[name="${fieldName}"]`);
                                if (field.length && field.val()) {
                                    formData.append(fieldName, field.val());
                                }
                            });

                            // Process each textarea and convert to array format
                            $('.domain-textarea').each(function() {
                                const textarea = $(this);
                                const name = textarea.attr('name'); // e.g., "panel_splits[3][domains]"
                                const domainsText = textarea.val().trim();
                                if (domainsText) {
                                    // Split domains by newlines and commas, then filter empty lines
                                    const domains = domainsText.split(/[\n,]+/)
                                        .map(domain => domain.trim())
                                        .filter(domain => domain.length > 0);

                                    // Add each domain as an array element
                                    domains.forEach((domain, index) => {
                                        // Convert "panel_splits[3][domains]" to "panel_splits[3][domains][0]", "panel_splits[3][domains][1]", etc.
                                        const arrayName = name.replace('[domains]',
                                            `[domains][${index}]`);
                                        formData.append(arrayName, domain);
                                    });
                                }
                            });

                            console.log('Form data prepared for submission:');
                            for (let pair of formData.entries()) {
                                console.log(pair[0], pair[1]);
                            }

                            return formData;
                        }

                        // Real-time validation for domain textareas
                        $('.domain-textarea').on('input blur', function() {
                            if (!$(this).length) return; // Safety check

                            // Auto-resize textarea based on content
                            const textarea = this;
                            textarea.style.height = 'auto';
                            textarea.style.height = Math.max(150, textarea.scrollHeight) + 'px';

                            validateAllDomainsRealTime();
                            updateSubmitButtonState();
                        });
                        // Add paste event handler for better UX
                        $('.domain-textarea').on('paste', function() {
                            const textarea = $(this);
                            // Use setTimeout to allow the paste content to be processed first
                            setTimeout(() => {
                                validateAllDomainsRealTime();
                                updateSubmitButtonState();

                                // Auto-resize after paste
                                const textareaElement = textarea[0];
                                textareaElement.style.height = 'auto';
                                textareaElement.style.height = Math.max(150, textareaElement
                                    .scrollHeight) + 'px';
                            }, 100);
                        });

                        // Add keyboard shortcuts
                        $(document).on('keydown', function(e) {
                            // Ctrl+S to save
                            if (e.ctrlKey && e.key === 's') {
                                e.preventDefault();
                                if (!$('#submitBtn').prop('disabled')) {
                                    $('#submitBtn').click();
                                }
                            }

                            // Ctrl+R to reset form (with confirmation)
                            if (e.ctrlKey && e.key === 'r') {
                                e.preventDefault();
                                Swal.fire({
                                    title: 'Reset Form?',
                                    text: 'This will reset all domains to their original values.',
                                    icon: 'question',
                                    showCancelButton: true,
                                    confirmButtonColor: 'var(--second-primary)',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: 'Yes, Reset',
                                    cancelButtonText: 'Cancel'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
                                });
                            }
                        }); // Function for real-time domain validation
                        function validateAllDomainsRealTime() {
                            // Reset all validation states
                            $('.domain-textarea').removeClass('is-invalid is-valid');
                            $('.invalid-feedback').text('');

                            let allDomainValues = [];
                            let hasErrors = false;
                            let emptyTextareas = 0;
                            let invalidFormats = 0;
                            let insufficientDomains = 0;
                            let duplicateNames = [];
                            const domainRegex = /^[^\s]+\.[^\s]+$/;

                            // First pass: validate each textarea individually
                            $('.domain-textarea').each(function() {
                                const textarea = $(this);
                                const domainsText = textarea.val().trim();
                                const requiredCount = parseInt(textarea.data('required-count')) || 0;
                                const splitId = textarea.data('split-id');
                                const errorElement = $(`#domains-error-${splitId}`);
                                // Reset individual validation state
                                textarea.removeClass('is-invalid is-valid');
                                errorElement.text('');
                                if (!domainsText) {
                                    textarea.addClass('is-invalid');
                                    errorElement.text('Please enter domains for this split');
                                    emptyTextareas++;
                                    hasErrors = true;

                                    // Update count display for empty textarea
                                    const countDisplay = $(`#count-display-${splitId} .domain-count`);
                                    countDisplay.text(0);
                                    $(`#count-display-${splitId}`).removeClass('text-success text-warning')
                                        .addClass('text-danger');
                                    return;
                                }

                                // Split domains by newlines and commas, then filter empty lines
                                const domains = domainsText.split(/[\n,]+/)
                                    .map(domain => domain.trim())
                                    .filter(domain => domain.length > 0);

                                // Update domain count display
                                const countDisplay = $(`#count-display-${splitId} .domain-count`);
                                countDisplay.text(domains.length);

                                // Color-code the count based on requirement
                                const countParent = $(`#count-display-${splitId}`);
                                countParent.removeClass('text-success text-warning text-danger');
                                if (domains.length === requiredCount) {
                                    countParent.addClass('text-success');
                                } else if (domains.length > 0) {
                                    countParent.addClass('text-warning');
                                } else {
                                    countParent.addClass('text-danger');
                                }

                                // Check if we have the required number of domains
                                if (domains.length !== requiredCount) {
                                    textarea.addClass('is-invalid');
                                    errorElement.text(
                                        `Required: ${requiredCount} domains, found: ${domains.length}`);
                                    insufficientDomains++;
                                    hasErrors = true;
                                    return;
                                }

                                // Validate each domain format
                                const invalidDomains = domains.filter(domain => !domainRegex.test(domain));
                                if (invalidDomains.length > 0) {
                                    textarea.addClass('is-invalid');
                                    errorElement.text(
                                    `Invalid domain format: ${invalidDomains.join(', ')}`);
                                    invalidFormats++;
                                    hasErrors = true;
                                    return;
                                }
                                // Add valid domains to global list for duplicate checking
                                domains.forEach(domain => {
                                    allDomainValues.push({
                                        domain: domain.toLowerCase(),
                                        textarea: textarea,
                                        splitId: splitId
                                    });
                                });

                                // Mark as valid if no issues so far
                                if (!hasErrors) {
                                    textarea.addClass('is-valid');
                                }
                            });

                            // Second pass: check for duplicates across all textareas
                            const domainCounts = {};
                            allDomainValues.forEach(item => {
                                const domain = item.domain;
                                if (!domainCounts[domain]) {
                                    domainCounts[domain] = [];
                                }
                                domainCounts[domain].push(item);
                            });

                            // Mark duplicates
                            Object.keys(domainCounts).forEach(domain => {
                                if (domainCounts[domain].length > 1) {
                                    duplicateNames.push(domain);
                                    domainCounts[domain].forEach(item => {
                                        item.textarea.removeClass('is-valid').addClass(
                                        'is-invalid');
                                        const errorElement = $(`#domains-error-${item.splitId}`);
                                        const currentError = errorElement.text();
                                        const duplicateError = `Duplicate domain: ${domain}`;
                                        errorElement.text(currentError ?
                                            `${currentError}; ${duplicateError}` :
                                            duplicateError);
                                        hasErrors = true;
                                    });
                                }
                            });
                            // Show validation errors in toaster if any found
                            if (emptyTextareas > 0 || invalidFormats > 0 || insufficientDomains > 0 ||
                                duplicateNames.length > 0) {
                                let errorMessages = [];

                                if (emptyTextareas > 0) {
                                    errorMessages.push(
                                        `${emptyTextareas} empty textarea${emptyTextareas > 1 ? 's' : ''} found`
                                        );
                                }
                                if (insufficientDomains > 0) {
                                    errorMessages.push(
                                        `${insufficientDomains} textarea${insufficientDomains > 1 ? 's' : ''} with incorrect domain count`
                                        );
                                }
                                if (invalidFormats > 0) {
                                    errorMessages.push(
                                        `${invalidFormats} textarea${invalidFormats > 1 ? 's' : ''} with invalid domain format${invalidFormats > 1 ? 's' : ''} detected`
                                        );
                                }
                                if (duplicateNames.length > 0) {
                                    errorMessages.push(
                                        `duplicate domain${duplicateNames.length > 1 ? 's' : ''} found: ${duplicateNames.join(', ')}`
                                        );
                                }

                                // Only show toaster after a short delay to avoid spam during typing
                                clearTimeout(window.validationToastTimeout);
                                window.validationToastTimeout = setTimeout(() => {
                                    showValidationToast(errorMessages);
                                }, 1000); // 1 second delay        } else {
                                // Clear any pending validation toasts if all is valid
                                clearTimeout(window.validationToastTimeout);
                            }

                            // Update validation summary
                            updateValidationSummary(emptyTextareas, invalidFormats, insufficientDomains,
                                duplicateNames.length);
                        }

                        // Function to update validation summary
                        function updateValidationSummary(emptyCount, invalidCount, insufficientCount,
                            duplicateCount) {
                            const summaryElement = $('#validation-summary');
                            const summaryText = $('#validation-summary-text');

                            if (emptyCount === 0 && invalidCount === 0 && insufficientCount === 0 &&
                                duplicateCount === 0) {
                                summaryElement.removeClass('alert-warning alert-danger').addClass(
                                    'alert-success d-block');
                                summaryText.html(
                                    '<i class="fa-solid fa-check-circle me-1"></i> All domains are valid and ready for submission!'
                                    );
                            } else {
                                summaryElement.removeClass('alert-success').addClass('alert-warning d-block');
                                let issues = [];
                                if (emptyCount > 0) issues.push(`${emptyCount} empty`);
                                if (insufficientCount > 0) issues.push(`${insufficientCount} incorrect count`);
                                if (invalidCount > 0) issues.push(`${invalidCount} invalid format`);
                                if (duplicateCount > 0) issues.push(`${duplicateCount} duplicates`);

                                summaryText.html(
                                    `<i class="fa-solid fa-exclamation-triangle me-1"></i> Issues found: ${issues.join(', ')}`
                                    );
                            }
                        } // Function to update submit button state
                        function updateSubmitButtonState() {
                            const hasInvalidTextareas = $('.domain-textarea.is-invalid').length > 0;
                            const hasEmptyTextareas = $('.domain-textarea').filter(function() {
                                return $(this).val().trim() === '';
                            }).length > 0;

                            const submitBtn = $('#submitBtn');
                            if (hasInvalidTextareas || hasEmptyTextareas) {
                                submitBtn.prop('disabled', true).addClass('opacity-50');
                            } else {
                                submitBtn.prop('disabled', false).removeClass('opacity-50');
                            }
                        } // Function to show toast notifications
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
                            const numberedErrors = errorMessages.map((error, index) => `${error}`);
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
                                const htmlMessage = formattedMessage.replace(/\n/g, '<br>');
                                toastr.warning(htmlMessage, '⚠️ Validation Issues');
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
                        } // Initialize textarea styling and validation
                        if ($('.domain-textarea').length) {
                            $('.domain-textarea').each(function() {
                                $(this).trigger('blur');

                                // Initialize domain count displays
                                const textarea = $(this);
                                const splitId = textarea.data('split-id');
                                const domainsText = textarea.val().trim();
                                const domains = domainsText ? domainsText.split(/[\n,]+/).map(d => d.trim())
                                    .filter(d => d.length > 0) : [];
                                const requiredCount = parseInt(textarea.data('required-count')) || 0;

                                const countDisplay = $(`#count-display-${splitId} .domain-count`);
                                countDisplay.text(domains.length);

                                // Set initial color
                                const countParent = $(`#count-display-${splitId}`);
                                countParent.removeClass('text-success text-warning text-danger');
                                if (domains.length === requiredCount) {
                                    countParent.addClass('text-success');
                                } else if (domains.length > 0) {
                                    countParent.addClass('text-warning');
                                } else {
                                    countParent.addClass('text-danger');
                                }
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
