<form id="addNewUserForm">
    <div class="mb-3">
        <label for="add-user-fullname" class="form-label">Full Name</label>
        <input type="text" id="add-user-fullname" name="userFullname" class="form-control" placeholder="John Doe">
    </div>
    <div class="mb-3">
        <label for="add-user-email" class="form-label">Email</label>
        <input type="email" id="add-user-email" name="userEmail" class="form-control"
            placeholder="john.doe@example.com">
    </div>
    <div class="mb-3">
        <label for="add-user-contact" class="form-label">Contact</label>
        <input type="text" id="add-user-contact" name="userContact" class="form-control"
            placeholder="+1 (609) 988-44-11">
    </div>
    <div class="mb-3">
        <label for="add-user-company" class="form-label">Company</label>
        <input type="text" id="add-user-company" name="companyName" class="form-control" placeholder="Web Developer">
    </div>
    <div class="mb-3">
        <label for="country" class="form-label">Country</label>
        <select id="country" name="country" class="form-select">
            <option value="">Select</option>
            <option value="Australia">Australia</option>
            <option value="Bangladesh">Bangladesh</option>
            <option value="Belarus">Belarus</option>
            <option value="Brazil">Brazil</option>
            <option value="Canada">Canada</option>
            <option value="China">China</option>
            <option value="France">France</option>
            <option value="Germany">Germany</option>
            <option value="India">India</option>
            <option value="Indonesia">Indonesia</option>
            <option value="Israel">Israel</option>
            <option value="Italy">Italy</option>
            <option value="Japan">Japan</option>
            <option value="Korea">Korea, Republic of</option>
            <option value="Mexico">Mexico</option>
            <option value="Philippines">Philippines</option>
            <option value="Russia">Russian Federation</option>
            <option value="South Africa">South Africa</option>
            <option value="Thailand">Thailand</option>
            <option value="Turkey">Turkey</option>
            <option value="Ukraine">Ukraine</option>
            <option value="United Arab Emirates">United Arab Emirates</option>
            <option value="United Kingdom">United Kingdom</option>
            <option value="United States">United States</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="user-role" class="form-label">User Role</label>
        <select id="user-role" name="userRole" class="form-select">
            <option value="subscriber">Subscriber</option>
            <option value="editor">Editor</option>
            <option value="maintainer">Maintainer</option>
            <option value="author">Author</option>
            <option value="admin">Admin</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="user-plan" class="form-label">Select Plan</label>
        <select id="user-plan" name="userPlan" class="form-select">
            <option value="basic">Basic</option>
            <option value="enterprise">Enterprise</option>
            <option value="company">Company</option>
            <option value="team">Team</option>
        </select>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="m-btn py-2 px-4 rounded-2 border-0">Submit</button>
        <button type="reset" class="cancel-btn py-2 px-4 rounded-2 border-0">Cancel</button>
    </div>
</form>
