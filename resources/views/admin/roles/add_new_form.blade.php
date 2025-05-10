<form action="{{ route('admin.role.store') }}" method="post" id="addRoleForm" class="row g-3">
    @csrf
    <input type="hidden" name="role_id" id="roleId">

    <div class="col-12 mb-3">
        <label class="form-label" for="modalRoleName">Role Name</label>
        <input type="text" id="name" name="name" class="form-control" placeholder="Enter a role name">
        <div class="invalid-feedback"></div>
    </div>

    <div class="col-12">
        <h5 class="mb-4">Role Permissions</h5>
        <div class="table-responsive">
            <div class="mb-3">
                <label for="permissions" class="form-label">Assign Permissions</label>
                <select name="permissions[]" id="permissions" class="form-control select2" multiple
                    style="background-color: #2f3349; color: #fff; border: 1px solid #444;">
                    @foreach($permissions as $permission)
                    <option value="{{ $permission->id }}">{{ $permission->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="col-12 text-center">
        <button type="submit" class="m-btn py-2 px-3 border-0 rounded-2">Submit</button>
        <button type="reset" class="cancel-btn py-2 px-3 border-0 rounded-2 ms-3" data-bs-dismiss="modal">Cancel</button>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('#permissions').select2({
            dropdownAutoWidth: true,
            width: '100%',
            dropdownParent: $('#permissions').parent() // helps if inside a modal
        });

        // When dropdown opens, style options and dropdown container
        $('#permissions').on('select2:open', function() {
            $('.select2-results__option').each(function() {
                $(this).attr('style', 'background-color: #2f3349 !important; color: #fff !important;');
            });

            $('.select2-results').parent().attr('style', 'background-color: #2f3349 !important; color: #fff !important; border: 1px solid #444 !important;');
        });

        // Style the selected area
        $('.select2-selection').attr('style', 'background-color: #2f3349 !important; color: #fff !important; border: 1px solid #444 !important;');
    });
</script>