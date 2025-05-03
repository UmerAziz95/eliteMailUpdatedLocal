<form id="createUserForm">
    @csrf
    <div class="mb-3">
        <label for="full_name" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="full_name" name="full_name" required>
    </div>
    <input type="hidden" class="form-control" id="user_id" name="user_id">

    <div class="mb-3">
        <label for="email" class="form-label">Email address</label>
        <input type="email" class="form-control" id="email" name="email" required>
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" class="form-control" id="password" name="password">
    </div>

    <div class="mb-3">
        <label for="confirm_password" class="form-label">Confirm Password</label>
        <input type="password" class="form-control" id="confirm_password" name="password_confirmation">
    </div>

    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status" required>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="role" class="form-label">Select Role</label>
        <select name="role_id" id="role" class="form-control">
            @foreach($roles as $role)
            <option value="{{ $role->id }}">{{ $role->name }}</option>
            @endforeach
        </select>
    </div>

    {{-- <!-- Permissions Multi-Select -->
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
    </div> --}}

    <button type="submit" class="btn btn-primary">Submit</button>
</form>