<div class="mt-5">
    <h5>Total users with their roles</h5>
    <p>Find all of your company’s administrator accounts and their associate roles.</p>
</div>

<div class="card py-3 px-4">
    <div class="table-responsive">
        <table id="myTable" class="display">
            <thead>
                <tr>
                    <th>Ticket Id</th>
                    <th>User</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < 20; $i++)
                    <tr>
                        <td>
                            #7912
                        </td>
                        <td><i class="ti ti-device-desktop-minus text-success me-2"></i>Admin</td>
                        <td>Billing Issue</td>
                        <td>Medium</td>
                        <td><span class="active_status">Active</span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <button class="bg-transparent p-0 border-0"><i
                                        class="fa-regular fa-trash-can text-danger"></i></button>
                                <button class="bg-transparent p-0 border-0 mx-2"><i
                                        class="fa-regular fa-eye"></i></button>
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

                    <tr>
                        <td>
                            <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                style="border-radius: 50%" height="35" width="35" class="object-fit-cover"
                                alt="">
                            John Doe
                        </td>
                        <td><i class="ti ti-user-cog me-2 text-warning"></i>Customer</td>
                        <td>Basic</td>
                        <td>Annual</td>
                        <td><span class="inactive_status">Inactive</span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <button class="bg-transparent p-0 border-0"><i
                                        class="fa-regular fa-trash-can text-danger"></i></button>
                                <button class="bg-transparent p-0 border-0 mx-2"><i
                                        class="fa-regular fa-eye"></i></button>
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

                    <tr>
                        <td>
                            <img src="https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600"
                                style="border-radius: 50%" height="35" width="35" class="object-fit-cover"
                                alt="">
                            John Doe
                        </td>
                        <td><i class="ti ti-contract me-2 text-primary"></i>Contractor</td>
                        <td>Basic</td>
                        <td>Annual</td>
                        <td><span class="pending_status">Pending</span></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <button class="bg-transparent p-0 border-0"><i
                                        class="fa-regular fa-trash-can text-danger"></i></button>
                                <button class="bg-transparent p-0 border-0 mx-2"><i
                                        class="fa-regular fa-eye"></i></button>
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
                @endfor
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
        

    </div>
</div>
