<style>
.badge-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: #dc3545;
    margin-right: 5px;
}
.dropdown-notifications-read {
    position: relative;
    display: inline-block;
    margin-right: 10px;
}
</style>

<header class="d-flex align-items-center justify-content-between py-2 px-4 rounded-3">
    <button type="button" class="bg-transparent border-0 d-flex align-items-center gap-3" data-bs-toggle="modal"
        data-bs-target="#search">
        <i class="fa-solid fa-magnifying-glass fs-5"></i> Search
    </button>

    <div class="d-flex align-items-center gap-3">
        <div class="dropdown">
            <!-- <div class="bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-language fs-5"></i>
            </div> -->
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">English</a></li>
                <li><a class="dropdown-item" href="#">French</a></li>
                <li><a class="dropdown-item" href="#">German</a></li>
            </ul>
        </div>

        <div class="dropdown">
            <div class="bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-moon-stars fs-5"></i>
            </div>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item d-flex align-items-center gap-1" id="light-theme" href="#"><i
                            class="ti ti-brightness-up fs-6"></i> Light</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-1" id="dark-theme" href="#"><i
                            class="ti ti-moon-stars fs-6"></i> Dark</a></li>
            </ul>
        </div>

        <!-- <div class="dropdown">
            <div class="bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-category fs-5"></i>
            </div>
            {{-- <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Action</a></li>
                <li><a class="dropdown-item" href="#">Another action</a></li>
                <li><a class="dropdown-item" href="#">Something else here</a></li> 
            </ul> --}} 
        </div> -->

        <div class="dropdown notification-dropdown">
            <div class="bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="notificationDropdownToggle">
                <i class="ti ti-bell fs-5"></i>
            </div>
            <ul class="dropdown-menu overflow-y-auto py-0" style="min-width: 370px; max-height: 24rem;" id="notificationDropdown">
                <div class="position-sticky top-0 d-flex align-items-center justify-content-between p-3" style="background-color: var(--secondary-color); z-index: 10">
                    <h6 class="mb-0">Notifications</h6>
                    <i class="fa-regular fa-envelope fs-5"></i>
                </div>
                <div id="notificationList">
                    <!-- Notifications will be loaded here dynamically -->
                </div>
                <div class="position-sticky bottom-0 py-2 px-3" style="background-color: var(--secondary-color)">
                    <a href="/customer/dashboard" class="m-btn py-2 px-4 w-100 border-0 rounded-2d-flex align-items-center justify-content-center">View All Notifications</a>
                </div>
            </ul>
        </div>

        <div class="dropdown">
            <div class="bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="{{ Auth::user()->profile_image ? asset('storage/profile_images/' . Auth::user()->profile_image) : 'https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/avatars/1.png' }}"
                    style="border-radius: 50%" height="40" width="40" class="object-fit-cover login-user-profile" alt="">
            </div>
            <ul class="dropdown-menu px-2 py-3" style="min-width: 200px">
                <div class="profile d-flex align-items-center gap-2 px-2">
                    <img src="{{ Auth::user()->profile_image ? asset('storage/profile_images/' . Auth::user()->profile_image) : 'https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/avatars/1.png' }}"
                    style="border-radius: 50%" height="40" width="40" class="object-fit-cover login-user-profile" alt="">
                    <div>
                        <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                        <p class="small mb-0">{{ Auth::user()->email }}</p>
                    </div>
                </div>
                <hr>

                <li><a class="dropdown-item d-flex gap-2 align-items-center mb-2 px-3 rounded-2" style="font-size: 15px" href="/customer/profile"><i class="ti ti-user"></i> My Profile</a></li>
                <li><a class="dropdown-item d-flex gap-2 align-items-center mb-2 px-3 rounded-2" style="font-size: 15px" href="/customer/settings"><i class="ti ti-settings"></i> Settings</a></li>
                <!-- <li><a class="dropdown-item d-flex gap-2 align-items-center mb-2 px-3 rounded-2" style="font-size: 15px" href="#"><i class="ti ti-receipt"></i> Billing</a></li> -->
                <li><a class="dropdown-item d-flex gap-2 align-items-center mb-2 px-3 rounded-2" style="font-size: 15px" href="{{ url('customer/pricing') }}"><i class="ti ti-currency-dollar"></i> Pricing</a></li>
                <!-- <li><a class="dropdown-item d-flex gap-2 align-items-center mb-2 px-3 rounded-2" style="font-size: 15px" href="#"><i class="ti ti-message-2"></i> Faq</a></li> -->

                <div class="logout-btn">
                    <a href="{{route('logout')}}" class="btn btn-danger w-100" style="font-size: 13px"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </ul>
        </div>
    </div>
</header>


<!-- Search Popup -->
<div class="modal fade" id="search" tabindex="-1" aria-labelledby="searchLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-start border-0">
                <div class="input-group mb-3">
                    <span class="input-group-text pe-0 bg-transparent border-0" id="basic-addon1"><i
                            class="fa-solid fa-magnifying-glass fs-5"></i></span>
                    <input type="search" class="form-control bg-transparent border-0" placeholder="Search"
                        aria-label="Search" aria-describedby="basic-addon1">
                </div>
                <div type="button" data-bs-dismiss="modal" aria-label="Close">
                    <i class="fa-solid fa-xmark fs-5"></i>
                </div>
            </div>
            <div class="modal-body">
                <div class="row px-4">
                    <div class="col-6">
                        <small class="opacity-50 text-uppercase">POPULAR SEARCHES</small>
                        <a class="nav-link d-flex align-items-center mb-3 mt-2 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>
                    </div>

                    <div class="col-6">
                        <small class="opacity-50 text-uppercase">Apps & Pages</small>
                        <a class="nav-link d-flex align-items-center mb-3 mt-2 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>
                    </div>

                    <div class="col-6">
                        <small class="opacity-50 text-uppercase">User Interface</small>
                        <a class="nav-link d-flex align-items-center mb-3 mt-2 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>
                    </div>

                    <div class="col-6">
                        <small class="opacity-50 text-uppercase">Forms & Charts</small>
                        <a class="nav-link d-flex align-items-center mb-3 mt-2 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>

                        <a class="nav-link d-flex align-items-center mb-3 {{ request()->is('/') ? 'active' : '' }}"
                            href="{{ url('/') }}">
                            <div class="d-flex align-items-center gap-2">
                                <div class="icons"><i class="fa-solid fa-house"></i></div>
                                <div>Dashboard</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>



<script>
    document.addEventListener("DOMContentLoaded", () => {
        // Existing theme code
        const lightThemeBtn = document.getElementById("light-theme");
        const darkThemeBtn = document.getElementById("dark-theme");
        const currentTheme = localStorage.getItem("theme");

        // Set default theme as dark if no preference is stored
        if (!currentTheme || currentTheme === "dark") {
            document.body.classList.add("dark-theme");
        } else {
            document.body.classList.remove("light-theme");
        }

        // Light theme click
        lightThemeBtn.addEventListener("click", () => {
            document.body.classList.add("light-theme");
            localStorage.setItem("theme", "light");
        });

        // Dark theme click
        darkThemeBtn.addEventListener("click", () => {
            document.body.classList.remove("light-theme");
            localStorage.setItem("theme", "dark");
        });

        // Add notification dropdown event listener
        const notificationDropdownEl = document.querySelector('.notification-dropdown');
        notificationDropdownEl.addEventListener('show.bs.dropdown', function () {
            loadNotifications();
        });
    });

    function loadNotifications() {
        fetch('/notifications/list')
            .then(response => response.json())
            .then(data => {
                const notificationList = document.getElementById('notificationList');
                notificationList.innerHTML = data.notifications.map(notification => `
                    <hr class="my-0">
                    <li class="dropdown-item py-2">
                        <div class="d-flex">
                            <div class="flex-shrink-0 me-3">
                                <div class="avatar">
                                    ${notification.user_profile_photo 
                                        ? `<img src="${notification.user_profile_photo}" style="border-radius: 50%" height="40" width="40" class="object-fit-cover" alt="">`
                                        : '<i class="ti ti-user-circle fs-2"></i>'
                                    }
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="small mb-2">${notification.title}</h6>
                                <small class="mb-1 d-block opacity-75">${notification.message}</small>
                                <small class="opacity-50">${notification.created_at}</small>
                                <small class="opacity-50">
                                    ${!notification.is_read 
                                        ? `<a href="javascript:void(0)" class="dropdown-notifications-read" data-id="${notification.id}">
                                            <span class="badge bg-danger">Unread</span>
                                           </a>`
                                        : ''
                                    }
                                </small>
                            </div>
                            <div class="flex-shrink-0 dropdown-notifications-actions">
                                <a href="javascript:void(0)" class="dropdown-notifications-archive"><span class="icon-base ti tabler-x"></span></a>
                            </div>
                        </div>
                    </li>
                `).join('');

                // Reattach event listeners for mark as read buttons
                document.querySelectorAll('.dropdown-notifications-read').forEach(button => {
                    button.addEventListener('click', handleNotificationRead);
                });
            })
            .catch(error => console.error('Error loading notifications:', error));
    }

    function handleNotificationRead() {
        const notificationId = this.dataset.id;
        fetch(`/notifications/${notificationId}/mark-as-read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.message) {
                // Update notification count
                updateNotificationCount();
                // Remove the unread badge
                this.remove();
                // Close the dropdown
                const dropdownMenu = document.getElementById('notificationDropdown');
                const bsDropdown = bootstrap.Dropdown.getInstance(dropdownMenu);
                if (bsDropdown) {
                    bsDropdown.hide();
                }
            }
        })
        .catch(error => console.error('Error:', error));
    }
</script>
