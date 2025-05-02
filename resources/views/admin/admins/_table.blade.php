<div class="table-responsive">
    <table id="myTable" class="display">
        <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Role</th>
                <th>Email</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            {{-- @for ($i = 0; $i < 20; $i++) <tr>
                <td>

                    1
                </td>
                <td>

                    John Doe
                </td>
                <td><i class="ti ti-contract me-2 text-primary"></i>Contractor</td>
                <td><span class="pending_status">Pending</span></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <button class="bg-transparent p-0 border-0"><i
                                class="fa-regular fa-trash-can text-danger"></i></button>
                        <button class="bg-transparent p-0 border-0 mx-2"><i class="fa-regular fa-eye"></i></button>
                        <div class="dropdown">
                            <button class="p-0 bg-transparent border-0" type="button" data-bs-toggle="dropdown"
                                aria-expanded="false">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Action</a></li>
                                <li><a class="dropdown-item" href="#">Another action</a></li>
                                <li><a class="dropdown-item" href="#">Something else here</a></li>
                            </ul>
                        </div>
                    </div>
                </td>
                </tr>
                @endfor --}}
        </tbody>
    </table>
</div>