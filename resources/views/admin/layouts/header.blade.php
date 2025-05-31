<header class="d-flex align-items-center justify-content-between justify-content-xl-end px-4 rounded-3"
    style="z-index: 100; top: -19px">
    <div class="d-xl-none" data-bs-toggle="offcanvas" href="#offcanvasExample" role="button"
        aria-controls="offcanvasExample">
        <i class="fa-solid fa-bars"></i>
    </div>
    {{-- <button type="button" class="bg-transparent border-0 d-flex align-items-center gap-3" data-bs-toggle="modal"
        data-bs-target="#search">
        <i class="fa-solid fa-magnifying-glass fs-5"></i> Search
    </button> --}}

    <div class="d-flex align-items-center gap-3">
        {{-- <div class="dropdown">
            <div class="bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-language fs-5"></i>
            </div>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">English</a></li>
                <li><a class="dropdown-item" href="#">French</a></li>
                <li><a class="dropdown-item" href="#">German</a></li>
            </ul>
        </div> --}}

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

        {{-- <div class="dropdown">
            <div class="bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-category fs-5"></i>
            </div>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Action</a></li>
                <li><a class="dropdown-item" href="#">Another action</a></li>
                <li><a class="dropdown-item" href="#">Something else here</a></li>
            </ul>
        </div> --}}

        <div class="dropdown notification-dropdown">
            <div class="bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                id="notificationDropdownToggle">
                <i class="ti ti-bell fs-5"></i>
            </div>
            <ul class="dropdown-menu overflow-y-auto py-0" style="min-width: 370px; max-height: 24rem;"
                id="notificationDropdown">
                <div class="position-sticky top-0 d-flex align-items-center justify-content-between p-3"
                    style="background-color: var(--secondary-color); z-index: 10">
                    <h6 class="mb-0">Notifications</h6>
                    <i class="fa-regular fa-envelope fs-5"></i>
                </div>
                <div id="notificationList">
                    <!-- Notifications will be loaded here dynamically -->
                </div>
                <div class="position-sticky bottom-0 py-2 px-3" style="background-color: var(--secondary-color)">
                    <a href="/admin/settings"
                        class="m-btn py-2 px-4 w-100 border-0 rounded-2 d-flex align-items-center justify-content-center">View
                        All Notifications</a>
                </div>
            </ul>
        </div>

        <div class="dropdown">

            <ul class="dropdown-menu overflow-y-auto py-0" style="min-width: 370px; max-height: 24rem;">
                <div class="position-sticky top-0 d-flex align-items-center justify-content-between p-3"
                    style="background-color: var(--secondary-color); z-index: 10">
                    <h6 class="mb-0">Notification</h6>
                    <i class="fa-regular fa-envelope fs-5"></i>
                </div>

                <div class="position-sticky bottom-0 py-2 px-3" style="background-color: var(--secondary-color)">
                    <a href="/notification"
                        class="m-btn py-2 px-4 w-100 border-0 rounded-2d-flex align-items-center justify-content-center">View
                        All Notifications</a>
                </div>
            </ul>
        </div>

        <div class="dropdown">
            <div class="bg-transparent border-0 p-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <img src="{{ Auth::user()->profile_image ? asset('storage/profile_images/' . Auth::user()->profile_image) : 'https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/avatars/1.png' }}"
                    style="border-radius: 50%" height="40" width="40" class="object-fit-cover login-user-profile"
                    alt="">
                <div class="d-flex flex-column gap-0">
                    <h6 class="mb-0">Muhammad Hamza Ashfaq</h6>
                    <small>hamzaashfaq123@gmail.com</small>
                </div>
            </div>
            <ul class="dropdown-menu px-2 py-3" style="min-width: 200px">
                <div class="profile d-flex align-items-center gap-2 px-2">
                    <div class="bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        <img src="{{ Auth::user()->profile_image ? asset('storage/profile_images/' . Auth::user()->profile_image) : 'https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/avatars/1.png' }}"
                            style="border-radius: 50%" height="40" width="40"
                            class="object-fit-cover login-user-profile" alt="">
                    </div>
                    <div>
                        <h6 class="mb-0">{{ Auth::user()->name }}</h6>
                        <p class="small mb-0">{{ Auth::user()->role->name }}</p>
                    </div>
                </div>
                <hr>

                <li><a class="dropdown-item d-flex gap-2 align-items-center mb-2 px-3 rounded-2" style="font-size: 15px"
                        href="{{ route('admin.profile') }}"><i class="ti ti-user"></i> My Profile</a></li>
                <li><a class="dropdown-item d-flex gap-2 align-items-center mb-2 px-3 rounded-2" style="font-size: 15px"
                        href="{{ route('admin.settings') }}"><i class="ti ti-settings"></i> Settings</a></li>


                <a class="logout-btn" href="{{ route('logout') }}">
                    <button class="btn btn-danger w-100" style="font-size: 13px"><i class="fas fa-sign-out-alt"></i>
                        Logout</button>
                </a>
            </ul>
        </div>
    </div>
</header>




<div class="offcanvas offcanvas-start" style="width: 250px;" tabindex="-1" id="offcanvasExample"
    aria-labelledby="offcanvasExampleLabel">
    <div class="offcanvas-header px-4 pt-5">
        <div class="d-flex align-items-center gap-2">
            <img src="https://cdn-icons-png.flaticon.com/128/4439/4439182.png" width="40" alt="">
            <h4 class="text fs-5">Mailboxes</h4>
        </div>
        <div data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="fa-solid fa-xmark fs-5"></i>
        </div>
    </div>
    <div class="offcanvas-body">
        <aside class="sidebar-mobile px-2 overflow-y-auto" style="scrollbar-width: none">

            <ul class="nav flex-column list-unstyled">
                <!-- Dashboard -->

                @can('Dashboard')
                    <li class="nav-item">
                        <a class="nav-link px-3 d-flex align-items-center {{ request()->is('dashboard') ? 'active' : '' }}"
                            href="{{ route('admin.dashboard') }}">
                            <div class="d-flex align-items-center" style="gap: 13px">
                                <div class="icons"><i class="ti ti-home fs-5"></i></div>
                                <div class="text">Dashboard</div>
                            </div>
                        </a>
                    </li>
                @endcan


                <p class="px-3 text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Users</p>
                <!-- Admins -->
                @foreach ($navigations as $item)
                    @if ($item->name == 'Dashboard')
                        @continue
                    @endif
                    @can($item->permission)
                        <li class="nav-item">
                            <a class="nav-link px-3 d-flex align-items-center {{ Route::is($item->route) ? 'active' : '' }}"
                                href="{{ route($item->route) }}">
                                <div class="d-flex align-items-center" style="gap: 13px">
                                    <div class="icons"><i class="{{ $item->icon }}"></i></div>
                                    <div class="text">{{ $item->name }}</div>
                                </div>
                            </a>
                        </li>
                    @endcan

                    <!-- Users -->
                    {{-- <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('admin/customer') ? 'active' : '' }}"
                        href="{{ url('admin/customer') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-headphones fs-5"></i></div>
                            <div class="text">Customers</div>
                        </div>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('admin/subscriptions') ? 'active' : '' }}"
                        href="{{ url('admin/subscriptions') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-currency-dollar fs-5"></i></div>
                            <div class="text">Subscriptions</div>
                        </div>
                    </a>
                </li>

                <!-- Contractors -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('admin/contractor') ? 'active' : '' }}"
                        href="{{ url('admin/contractor') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-contract fs-5"></i></div>
                            <div class="text">Contractors</div>
                        </div>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('admin/invoices') ? 'active' : '' }}"
                        href="{{ url('admin/invoices') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-file-invoice fs-5"></i></div>
                            <div class="text">Invoices</div>
                        </div>
                    </a>
                </li> --}}
                @endforeach

                {{-- <p class="px-3 text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Roles and Permissions
                </p>
                <!-- Roles -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('/admin/role') ? 'active' : '' }}"
                        href="{{ url('/admin/role') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-circles fs-5"></i></div>
                            <div class="text">Roles</div>
                        </div>
                    </a>
                </li> --}}

                <!-- Permissions -->
                {{-- <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('permissions') ? 'active' : '' }}"
                        href="{{ url('permissions') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-pointer-pause fs-5"></i></div>
                            <div class="text">Permissions</div>
                        </div>
                    </a>
                </li> --}}

                {{-- <p class="px-3 text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Website settings</p>
                --}}
                <!-- Pages -->
                {{-- <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center justify-content-between toggle-btn"
                        data-bs-toggle="collapse" href="#pages" role="button" aria-expanded="false">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-clipboard fs-5"></i></div>
                            <div class="text">Pages</div>
                        </div>
                        <i class="fa-solid fa-chevron-right rotate-icon"></i>
                    </a>
                    <ul class="collapse list-unstyled" id="pages">
                        <li><a class="nav-link px-3 d-flex align-items-center" style="gap: 13px"
                                href="{{ url('/') }}"><span class="circle"></span> Faq</a></li>
                        <li><a class="nav-link px-3 d-flex align-items-center" style="gap: 13px"
                                href="{{ url('/') }}"><span class="circle"></span> Pricing</a></li>
                        <li><a class="nav-link px-3 d-flex align-items-center" style="gap: 13px"
                                href="{{ url('/') }}"><span class="circle"></span> Teams</a></li>
                        <li><a class="nav-link px-3 d-flex align-items-center" style="gap: 13px"
                                href="{{ url('/') }}"><span class="circle"></span> Projects</a></li>
                    </ul>
                </li> --}}

                <!-- Front Pages -->
                {{-- <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center justify-content-between toggle-btn"
                        data-bs-toggle="collapse" href="#front_pages" role="button" aria-expanded="false">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-brand-pagekit fs-5"></i></div>
                            <div class="text">Front Pages</div>
                        </div>
                        <i class="fa-solid fa-chevron-right rotate-icon"></i>
                    </a>
                    <ul class="collapse list-unstyled" id="front_pages">
                        <li><a class="nav-link px-3 d-flex align-items-center gap-1" href="{{ url('/') }}"><span
                                    class="circle"></span> Home</a></li>
                        <li><a class="nav-link px-3 d-flex align-items-center gap-1" href="{{ url('/') }}"><span
                                    class="circle"></span> Why Us</a></li>
                        <li><a class="nav-link px-3 d-flex align-items-center gap-1" href="{{ url('/') }}"><span
                                    class="circle"></span> Pricing</a></li>
                        <li><a class="nav-link px-3 d-flex align-items-center gap-1" href="{{ url('/') }}"><span
                                    class="circle"></span> Contact</a></li>
                        <li><a class="nav-link px-3 d-flex align-items-center gap-1" href="{{ url('/') }}"><span
                                    class="circle"></span> Testimonials</a></li>
                    </ul>
                </li> --}}

                {{-- <p class="px-3 text fw-lighter my-2 text-uppercase" style="font-size: 13px;">payments</p>
                <!-- Pricing -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('pricing') ? 'active' : '' }}"
                        href="{{ url('admin/pricing') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-devices-dollar fs-5"></i></div>
                            <div class="text">Plans</div>
                        </div>
                    </a>
                </li> --}}

                <!-- Payments -->
                {{-- <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('payments') ? 'active' : '' }}"
                        href="{{ url('payments') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-wallet fs-5"></i></div>
                            <div class="text">Payments</div>
                        </div>
                    </a>
                </li> --}}

                {{-- <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('admin/orders') ? 'active' : '' }}"
                        href="{{ url('admin/orders') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-box fs-5"></i></div>
                            <div class="text">Orders</div>
                        </div>
                    </a>
                </li> --}}

                <p class="px-3 text fw-lighter my-2 text-uppercase" style="font-size: 13px;">misc</p>
                <!-- Support -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('contact_us') ? 'active' : '' }}"
                        href="{{ url('contact_us') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-address-book fs-5"></i></div>
                            <div class="text">Contact Us</div>
                        </div>
                    </a>
                </li>

                <!-- Support -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('support') ? 'active' : '' }}"
                        href="{{ url('admin/support') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-device-mobile-question fs-5"></i></div>
                            <div class="text">Support</div>
                        </div>
                    </a>
                </li>
            </ul>
        </aside>
    </div>
</div>



<script>
    document.addEventListener("DOMContentLoaded", () => {
        const lightThemeBtn = document.getElementById("light-theme");
        const darkThemeBtn = document.getElementById("dark-theme");

        // Light theme click
        lightThemeBtn.addEventListener("click", () => {
            document.documentElement.classList.add("light-theme");
            document.documentElement.classList.remove("dark-theme");
            localStorage.setItem("theme", "light");
        });

        // Dark theme click
        darkThemeBtn.addEventListener("click", () => {
            document.documentElement.classList.add("dark-theme");
            document.documentElement.classList.remove("light-theme");
            localStorage.setItem("theme", "dark");
        });

        // Add notification dropdown event listener
        const notificationDropdownEl = document.querySelector('.notification-dropdown');
        notificationDropdownEl.addEventListener('show.bs.dropdown', function() {
            loadNotifications();
        });
    });

    function loadNotifications() {
        fetch('/notifications/list/all')
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
