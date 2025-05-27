<div class="mt-5">
    <h5>Total roles</h5>
    {{-- <p>Find all of your company’s administrator accounts and their associate roles.</p> --}}
</div>

<div class="card py-3 px-4">
    <div class="table-responsive">
        <table id="myTable">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Role</th>
                    <th>Permissions</th>
                    <th>Created at</th>
                    @if (!auth()->user()->hasPermissionTo('Mod')) {
                    <th>Action</th>
                    }
                    @endif

                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>


<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAddAdmin" aria-labelledby="offcanvasAddAdminLabel"
    aria-modal="true" role="dialog">
    <div class="offcanvas-header" style="border-bottom: 1px solid var(--input-border)">
        <h5 id="offcanvasAddAdminLabel" class="offcanvas-title">Add User</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
        @include('modules.admins.add_new_form')
    </div>
</div>