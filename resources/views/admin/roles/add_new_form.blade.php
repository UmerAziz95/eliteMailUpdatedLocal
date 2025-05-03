<!-- Add role form -->






<form action="{{ route('admin.role.store') }}" method="post" id="addRoleForm" class="row g-3">
    @csrf
    <div class="col-12 mb-3">
  

        <label class="form-label" for="modalRoleName">Role Name</label>
        
        <input type="text" id="name" name="name" class="form-control" placeholder="Enter a role name"
            tabindex="-1">
            
        <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
        </div>
    </div>
    <div class="col-12">
        <h5 class="mb-4">Role Permissions</h5>
        <!-- Permission table -->
        <div class="table-responsive">
          
    <div class="mb-3">
        <label for="permissions" class="form-label">Assign Permissions</label>
        <select name="permissions[]" id="permissions" class="form-control select2" multiple
            style="background-color: #000; color: #fff; border: 1px solid #444;">
            @foreach($permissions as $permission)
                <option value="{{ $permission->id }}"
                    style="background-color: #000; color: #fff;">
                    {{ $permission->name }}
                </option>
            @endforeach
        </select>
    </div>
        </div>
        <!-- Permission table -->
    </div>
    <div class="col-12 text-center">
        <button type="submit" class="m-btn py-2 px-3 border-0 rounded-2">Submit</button>
        <button type="reset" class="cancel-btn py-2 px-3 border-0 rounded-2 ms-3" data-bs-dismiss="modal"
            aria-label="Close">Cancel</button>
    </div>
    <input type="hidden">
</form>
