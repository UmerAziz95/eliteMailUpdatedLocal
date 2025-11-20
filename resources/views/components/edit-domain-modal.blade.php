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
                    
                    <div class="mb-3">
                        <label for="edit_domain_name" class="form-label">Domain Name</label>
                        <input type="text" class="form-control" id="edit_domain_name" name="domain_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status</label>
                        <x-domain-status-select name="edit_status" id="edit_status" :required="true" />
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
// Show modal and prefill fields for editing a domain
function editDomain(poolId, poolOrderId, domainId, domainName, status) {
    $('#edit_pool_id').val(poolId);
    $('#edit_pool_order_id').val(poolOrderId);
    $('#edit_domain_id').val(domainId);
    $('#edit_domain_name').val(domainName);
    $('#edit_status').val(status);
    $('#editDomainModal').modal('show');
}

// Handle edit domain form submission
$('#editDomainForm').on('submit', function(e) {
    e.preventDefault();
    
    const formData = {
        pool_id: $('#edit_pool_id').val(),
        pool_order_id: $('#edit_pool_order_id').val(),
        domain_id: $('#edit_domain_id').val(),
        domain_name: $('#edit_domain_name').val(),
        status: $('#edit_status').val(),
        _token: '{{ csrf_token() }}'
    };
    
    $.ajax({
        url: '{{ route("admin.pool-domains.update") }}',
        type: 'POST',
        data: formData,
        success: function(response) {
            $('#editDomainModal').modal('hide');
            toastr.success(response.message || 'Domain updated successfully');
            $('#pool-domains-table').DataTable().ajax.reload();
        },
        error: function(xhr) {
            const errorMsg = xhr.responseJSON?.message || 'Error updating domain';
            toastr.error(errorMsg);
        }
    });
});
</script>
@endpush
