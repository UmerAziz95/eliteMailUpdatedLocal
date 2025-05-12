<form id="addNewUserForm">
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

 <!-- Password Field with Eye Toggle -->
 <div class="mb-3 position-relative">
    <label for="password" class="form-label">Password</label>
    <div class="input-group">
        <input type="password" class="form-control" id="password" name="password">
        <span class="input-group-text toggle-password " toggle="#password" style="cursor: pointer; color:#2f3349">
            <i class="fas fa-eye-slash"></i>
        </span>
    </div>
</div>

<!-- Confirm Password Field with Eye Toggle -->
<div class="mb-3 position-relative">
    <label for="confirm_password" class="form-label">Confirm Password</label>
    <div class="input-group">
        <input type="password" class="form-control" id="confirm_password" name="password_confirmation">
        <span class="input-group-text toggle-password" toggle="#confirm_password" style="cursor: pointer; color:#2f3349">
            <i class="fas fa-eye-slash"></i>
        </span>
    </div>
</div>

    <div class="mb-3">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status" required>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
    </div>

    @if (Auth::user()->hasPermissionTo('Mod')) 

    @else   
     <button type="submit" id="submit_btn" class="btn btn-primary">Submit</button>
    @endif
</form>


<script>
    document.querySelectorAll('.toggle-password').forEach(function(toggle) {
        toggle.addEventListener('click', function () {
            const input = document.querySelector(this.getAttribute('toggle'));
            const icon = this.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });
    });
</script>
