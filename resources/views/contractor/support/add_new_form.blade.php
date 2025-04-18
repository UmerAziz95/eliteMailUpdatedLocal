<!-- Add role form -->
<form id="addRoleForm" class="row g-3" onsubmit="return false" novalidate="novalidate">
    <div class="col-12 mb-3">
        <label class="form-label" for="modalRoleName">Role Name</label>
        <input type="text" id="modalRoleName" name="modalRoleName" class="form-control" placeholder="Enter a role name"
            tabindex="-1">
        <div class="fv-plugins-message-container fv-plugins-message-container--enabled invalid-feedback">
        </div>
    </div>
    <div class="col-12">
        <h5 class="mb-4">Role Permissions</h5>
        <!-- Permission table -->
        <div class="table-responsive">
            <table class="table table-flush-spacing">
                <tbody>
                    <tr>
                        <td class="text-nowrap fw-medium">
                            Administrator Access
                            <i class="fa-solid fa-circle-question" data-bs-toggle="tooltip" data-bs-placement="top"
                                aria-label="Allows a full access to the system"
                                data-bs-original-title="Allows a full access to the system"></i>
                        </td>
                        <td>
                            <div class="d-flex justify-content-end gap-2 gap-lg-5">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                    <label class="form-check-label" for="selectAll"> Select All </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap fw-medium text-heading">User Management</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2 gap-lg-5">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="userManagementRead">
                                    <label class="form-check-label" for="userManagementRead"> Read
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="userManagementWrite">
                                    <label class="form-check-label" for="userManagementWrite"> Write
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="userManagementCreate">
                                    <label class="form-check-label" for="userManagementCreate"> Create
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap fw-medium text-heading">Content Management</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2 gap-lg-5">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="contentManagementRead">
                                    <label class="form-check-label" for="contentManagementRead"> Read
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="contentManagementWrite">
                                    <label class="form-check-label" for="contentManagementWrite"> Write
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="contentManagementCreate">
                                    <label class="form-check-label" for="contentManagementCreate">
                                        Create </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap fw-medium text-heading">Disputes Management</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2 gap-lg-5">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="dispManagementRead">
                                    <label class="form-check-label" for="dispManagementRead"> Read
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="dispManagementWrite">
                                    <label class="form-check-label" for="dispManagementWrite"> Write
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="dispManagementCreate">
                                    <label class="form-check-label" for="dispManagementCreate"> Create
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap fw-medium text-heading">Database Management</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2 gap-lg-5">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="dbManagementRead">
                                    <label class="form-check-label" for="dbManagementRead"> Read
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="dbManagementWrite">
                                    <label class="form-check-label" for="dbManagementWrite"> Write
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="dbManagementCreate">
                                    <label class="form-check-label" for="dbManagementCreate"> Create
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap fw-medium text-heading">Financial Management</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2 gap-lg-5">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="finManagementRead">
                                    <label class="form-check-label" for="finManagementRead"> Read
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="finManagementWrite">
                                    <label class="form-check-label" for="finManagementWrite"> Write
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="finManagementCreate">
                                    <label class="form-check-label" for="finManagementCreate"> Create
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap fw-medium text-heading">Reporting</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2 gap-lg-5">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="reportingRead">
                                    <label class="form-check-label" for="reportingRead"> Read </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="reportingWrite">
                                    <label class="form-check-label" for="reportingWrite"> Write
                                    </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="reportingCreate">
                                    <label class="form-check-label" for="reportingCreate"> Create
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap fw-medium text-heading">API Control</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2 gap-lg-5">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="apiRead">
                                    <label class="form-check-label" for="apiRead"> Read </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="apiWrite">
                                    <label class="form-check-label" for="apiWrite"> Write </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="apiCreate">
                                    <label class="form-check-label" for="apiCreate"> Create </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap fw-medium text-heading">Repository Management</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2 gap-lg-5">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="repoRead">
                                    <label class="form-check-label" for="repoRead"> Read </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="repoWrite">
                                    <label class="form-check-label" for="repoWrite"> Write </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="repoCreate">
                                    <label class="form-check-label" for="repoCreate"> Create </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap fw-medium text-heading">Payroll</td>
                        <td>
                            <div class="d-flex justify-content-end gap-2 gap-lg-5">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="payrollRead">
                                    <label class="form-check-label" for="payrollRead"> Read </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="payrollWrite">
                                    <label class="form-check-label" for="payrollWrite"> Write </label>
                                </div>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="payrollCreate">
                                    <label class="form-check-label" for="payrollCreate"> Create
                                    </label>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
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
