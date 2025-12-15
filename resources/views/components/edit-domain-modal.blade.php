<div class="modal fade" id="editDomainModal" tabindex="-1" aria-labelledby="editDomainModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editDomainModalLabel">Edit Domain</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editDomainForm">
                <div class="modal-body">
                    <input type="hidden" id="edit_pool_id" name="pool_id">
                    <input type="hidden" id="edit_pool_order_id" name="pool_order_id">
                    <input type="hidden" id="edit_domain_id" name="domain_id">
                    <input type="hidden" id="edit_end_date" name="end_date">
                    <input type="hidden" id="edit_prefix_key" name="prefix_key">
                    
                    <!-- Prefix indicator badge (shown when editing a specific prefix) -->
                    <div id="edit_prefix_indicator" class="prefix-banner prefix-banner-primary d-none mb-3">
                        <span class="prefix-banner__icon">
                            <i class="fa fa-tag"></i>
                        </span>
                        <div class="prefix-banner__title">
                            Editing prefix: <strong id="edit_prefix_display"></strong>
                        </div>
                    </div>

                    <!-- Prefix with Domain Info -->
                    <div id="edit_prefix_domain_info" class="prefix-banner prefix-banner-muted d-none mb-3">
                        <span class="prefix-banner__icon">
                            <i class="fa fa-globe"></i>
                        </span>
                        <div class="prefix-banner__title">
                            Full Identity: <strong id="edit_prefix_domain_display"></strong>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_domain_name" class="form-label">Domain Name</label>
                        <input type="text" class="form-control" id="edit_domain_name" name="domain_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <x-domain-status-select name="edit_status" id="edit_status" :required="true" />
                    </div>

                    <div id="edit_end_date_section" class="border rounded-3 p-3 d-none">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <strong>Current End Date:</strong>
                                <span id="edit_current_end_date" class="ms-1">N/A</span>
                            </div>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" role="switch" id="edit_adjust_mode_toggle">
                                <label class="form-check-label" for="edit_adjust_mode_toggle" id="edit_adjust_mode_label">Increase end date</label>
                            </div>
                        </div>
                        <div class="mb-1">
                            <label for="edit_days" class="form-label">Days</label>
                            <input type="number" min="0" class="form-control" id="edit_days" name="days" placeholder="Number of days">
                            <div class="form-text" id="edit_days_help">Add days to extend the end date.</div>
                        </div>
                        <div class="small mt-2" id="edit_affected_date">No change preview yet.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Domain</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')

<script>
function toggleEndDateSection(status) {
    const section = $('#edit_end_date_section');
    if (status === 'warming') {
        section.removeClass('d-none');
    } else {
        section.addClass('d-none');
        $('#edit_days').val('');
    }
}

function setAdjustMode(isIncrease) {
    $('#edit_adjust_mode_toggle').prop('checked', isIncrease);
    $('#edit_adjust_mode_label').text(isIncrease ? 'Increase end date' : 'Reduce end date');
    $('#edit_days_help').text(isIncrease ? 'Add days to extend the end date.' : 'Subtract days to bring the end date sooner.');
}

function parseDateOnly(dateStr) {
    if (!dateStr) return null;
    const parts = dateStr.split('-').map(Number);
    if (parts.length !== 3 || parts.some(isNaN)) return null;
    return new Date(parts[0], parts[1] - 1, parts[2]);
}

function formatDateOnly(dateObj) {
    if (!(dateObj instanceof Date) || isNaN(dateObj.getTime())) return '';
    const y = dateObj.getFullYear();
    const m = String(dateObj.getMonth() + 1).padStart(2, '0');
    const d = String(dateObj.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function updateAffectedDatePreview() {
    const status = $('#edit_status').val();
    const currentEndDate = $('#edit_end_date').val();
    const days = parseInt($('#edit_days').val(), 10) || 0;
    const isIncrease = $('#edit_adjust_mode_toggle').is(':checked');
    const previewEl = $('#edit_affected_date');

    previewEl.removeClass('text-danger');

    if (status !== 'warming') {
        previewEl.text('No change preview for current status.');
        return;
    }

    if (!currentEndDate) {
        previewEl.text('Current end date missing.');
        return;
    }

    const parsedEndDate = parseDateOnly(currentEndDate);
    if (!parsedEndDate || isNaN(parsedEndDate.getTime())) {
        previewEl.text('Current end date is invalid.');
        return;
    }

    if (days === 0) {
        previewEl.text('No change preview yet.');
        return;
    }

    const newDate = new Date(parsedEndDate);
    newDate.setDate(newDate.getDate() + (isIncrease ? days : -days));

    // Check if new date is less than today when reducing
    if (!isIncrease) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        newDate.setHours(0, 0, 0, 0);
        
        if (newDate < today) {
            const formatted = formatDateOnly(newDate);
            previewEl.addClass('text-danger');
            previewEl.text(`Warning: New end date (${formatted}) would be before today's date!`);
            return;
        }
    }

    const formatted = formatDateOnly(newDate);
    previewEl.text(`New end date would be ${formatted}`);
}

// Show modal and prefill fields for editing a domain
function editDomain(poolId, poolOrderId, domainId, domainName, status, endDateOrPrefixKey, prefixKey, prefixValue) {
    // Handle backward compatibility: if only 6 args and 6th looks like a prefix_key (not a date), treat it as prefix_key
    let endDate = endDateOrPrefixKey;
    let actualPrefixKey = prefixKey;
    
    // Check if endDateOrPrefixKey looks like a prefix_key (e.g., 'prefix_variant_1') rather than a date
    if (endDateOrPrefixKey && endDateOrPrefixKey.indexOf('prefix_') === 0) {
        actualPrefixKey = endDateOrPrefixKey;
        endDate = ''; // No end date provided, will be populated from backend data
    } else if (typeof prefixKey === 'undefined' && endDateOrPrefixKey) {
        // Old behavior: 6th param is endDate, no prefixKey
        endDate = endDateOrPrefixKey;
        actualPrefixKey = '';
    }
    
    $('#edit_pool_id').val(poolId);
    $('#edit_pool_order_id').val(poolOrderId);
    $('#edit_domain_id').val(domainId);
    $('#edit_domain_name').val(domainName);
    $('#edit_status').val(status);
    $('#edit_end_date').val(endDate || '');
    $('#edit_current_end_date').text(endDate || 'N/A');
    $('#edit_prefix_key').val(actualPrefixKey || '');
    
    // Show prefix indicator if editing a specific prefix
    if (actualPrefixKey) {
        $('#edit_prefix_indicator').removeClass('d-none');
        // Format prefix display (e.g., 'prefix_variant_1' -> 'Prefix 1')
        const prefixNumber = actualPrefixKey.replace('prefix_variant_', '');
        $('#edit_prefix_display').text('Variant ' + prefixNumber);

        // Show prefix with domain info if available
        if (prefixValue) {
            $('#edit_prefix_domain_info').removeClass('d-none');
            // Try to construct full identity if not fully provided
            let displayValue = prefixValue;
            if (prefixValue.indexOf('@') === -1 && domainName) {
                 displayValue = prefixValue + '@' + domainName;
            }
            $('#edit_prefix_domain_display').text(displayValue);
        } else {
            $('#edit_prefix_domain_info').addClass('d-none');
        }
    } else {
        $('#edit_prefix_indicator').addClass('d-none');
        $('#edit_prefix_display').text('');
        $('#edit_prefix_domain_info').addClass('d-none');
    }
    
    $('#edit_days').val('');
    setAdjustMode(true); // default to increase
    toggleEndDateSection(status);
    updateAffectedDatePreview();
    $('#editDomainModal').modal('show');
}

// Handle edit domain form submission
$('#editDomainForm').on('submit', function(e) {
    e.preventDefault();

    const status = $('#edit_status').val();
    const isIncrease = $('#edit_adjust_mode_toggle').is(':checked');
    const days = parseInt($('#edit_days').val(), 10) || 0;
    let extendDays = 0;
    let reduceDays = 0;

    if (status === 'warming' && days > 0) {
        const currentEndDate = $('#edit_end_date').val();
        if (isIncrease) {
            extendDays = days;
        } else {
            if (!currentEndDate) {
                toastr.error('Current end date is missing, cannot adjust.');
                return;
            }

            const parsedEndDate = parseDateOnly(currentEndDate);
            if (!parsedEndDate || isNaN(parsedEndDate.getTime())) {
                toastr.error('Current end date is invalid.');
                return;
            }

            const newDate = new Date(parsedEndDate);
            newDate.setDate(newDate.getDate() - days);

            // Validate new date is not before today
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            newDate.setHours(0, 0, 0, 0);
            
            if (newDate < today) {
                toastr.error('Cannot reduce end date to before today\'s date!');
                return;
            }

            reduceDays = days;
            // Persist the recalculated end date back to the hidden field for consistency
            $('#edit_end_date').val(formatDateOnly(newDate));
        }
    }

    const formData = {
        pool_id: $('#edit_pool_id').val(),
        pool_order_id: $('#edit_pool_order_id').val(),
        domain_id: $('#edit_domain_id').val(),
        domain_name: $('#edit_domain_name').val(),
        status,
        end_date: $('#edit_end_date').val(),
        extend_days: extendDays,
        reduce_days: reduceDays,
        prefix_key: $('#edit_prefix_key').val() || '',
        _token: '{{ csrf_token() }}'
    };
    
    $.ajax({
        url: '{{ route("admin.pool-domains.update") }}',
        type: 'POST',
        data: formData,
        beforeSend: function() {
            Swal.fire({
                title: 'Updating domain...',
                html: 'Please wait while we save your changes.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        },
        success: function(response) {
            $('#editDomainModal').modal('hide');
            toastr.success(response.message || 'Domain updated successfully');
            // Reload any DataTables that may show domains across pages
            const selectorSet = new Set([
                '#pool-domains-table',
                '#pool-specific-domains-table',
                '#all-pool-orders-table',
                '#pool-orders-table',
                '#in-queue-orders-table'
            ]);

            // Include any table that opts in via data attribute
            $('table[data-reload-on-domain-update]').each(function() {
                const id = $(this).attr('id');
                if (id) {
                    selectorSet.add(`#${id}`);
                }
            });

            selectorSet.forEach(selector => {
                if ($.fn.DataTable && $.fn.DataTable.isDataTable(selector)) {
                    $(selector).DataTable().ajax.reload(null, false);
                }
            });
            Swal.close();
        },
        error: function(xhr) {
            Swal.close();
            const errorMsg = xhr.responseJSON?.message || 'Error updating domain';
            toastr.error(errorMsg);
        }
    });
});

document.addEventListener('DOMContentLoaded', function() {
    toggleEndDateSection($('#edit_status').val());
    updateAffectedDatePreview();

    // Toggle section on status change
    $('#edit_status').on('change', function() {
        toggleEndDateSection($(this).val());
        updateAffectedDatePreview();
    });

    // Toggle labels when switch changes
    $('#edit_adjust_mode_toggle').on('change', function() {
        setAdjustMode($(this).is(':checked'));
        updateAffectedDatePreview();
    });

    $('#edit_days').on('input', function() {
        updateAffectedDatePreview();
    });
});
</script>
@endpush
