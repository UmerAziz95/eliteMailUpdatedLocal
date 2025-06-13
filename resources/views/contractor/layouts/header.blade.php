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

<header class="d-flex align-items-center justify-content-between justify-content-xl-end rounded-3">
    <div class="d-xl-none" data-bs-toggle="offcanvas" href="#offcanvasExample" role="button"
        aria-controls="offcanvasExample">
        <i class="fa-solid fa-bars"></i>
    </div>
    {{-- <button type="button" class="bg-transparent border-0 d-flex align-items-center gap-3" data-bs-toggle="modal"
        data-bs-target="#search">
        <i class="fa-solid fa-magnifying-glass fs-5"></i> Search
    </button> --}}

    <div class="d-flex align-items-center gap-3">
        <!-- <div class="dropdown">
            <div class="bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-language fs-5"></i>
            </div>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">English</a></li>
                <li><a class="dropdown-item" href="#">French</a></li>
                <li><a class="dropdown-item" href="#">German</a></li>
            </ul>
        </div> -->

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
                    <a href="/contractor/settings"
                        class="m-btn py-2 px-4 w-100 border-0 rounded-2 d-flex align-items-center justify-content-center">View
                        All Notifications</a>
                </div>
            </ul>
        </div>

@php
    $user = Auth::user();

    if (!empty($user->name)) {
        $initials = collect(explode(' ', $user->name))
            ->filter()
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->implode('');
    } else {
        $initials = strtoupper(substr($user->email, 0, 2));
    }
@endphp

<div class="dropdown">
    <div class="bg-transparent border-0 p-0 d-flex align-items-center gap-2" type="button"
        data-bs-toggle="dropdown" aria-expanded="false">
        @if ($user->profile_image)
            <img src="{{ asset('storage/profile_images/' . $user->profile_image) }}"
                style="border-radius: 50%" height="40" width="40" class="object-fit-cover login-user-profile"
                alt="Profile Image">
        @else
            <div class="d-flex justify-content-center align-items-center text-white fw-bold"
                style="border-radius: 50%; width: 40px; height: 40px; font-size: 14px; background-color: #5750bf;">
                {{ $initials }}
            </div>
        @endif

        <div>
            <h6 class="mb-0">{{ $user->name ?? 'N/A' }}</h6>
            <p class="small mb-0">{{ $user->email ?? 'N/A' }}</p>
        </div>
    </div>

    <ul class="dropdown-menu px-2 py-3" style="min-width: 200px">
        <div class="profile d-flex align-items-center gap-2 px-2">
            @if ($user->profile_image)
                <img src="{{ asset('storage/profile_images/' . $user->profile_image) }}"
                    style="border-radius: 50%" height="40" width="40" class="object-fit-cover login-user-profile"
                    alt="Profile Image">
            @else
                <div class="d-flex justify-content-center align-items-center text-white fw-bold"
                    style="border-radius: 50%; width: 40px; height: 40px; font-size: 14px; background-color: #5750bf;">
                    {{ $initials }}
                </div>
            @endif

            <div>
                <h6 class="mb-0">{{ $user->name ?? 'N/A' }}</h6>
                <p class="small mb-0">{{ $user->email ?? 'N/A' }}</p>
            </div>
        </div>
        <hr>

        <li>
            <a class="dropdown-item d-flex gap-2 align-items-center mb-2 px-3 rounded-2"
               style="font-size: 15px" href="/contractor/profile">
                <i class="ti ti-user"></i> My Profile
            </a>
        </li>
        <li>
            <a class="dropdown-item d-flex gap-2 align-items-center mb-2 px-3 rounded-2"
               style="font-size: 15px" href="/contractor/settings">
                <i class="ti ti-settings"></i> Settings
            </a>
        </li>

        <div class="logout-btn">
            <a href="{{ route('logout') }}" class="btn btn-danger w-100" style="font-size: 13px">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </ul>
</div>

    </div>
</header>


<div class="offcanvas offcanvas-start" style="width: 250px;" tabindex="-1" id="offcanvasExample"
    aria-labelledby="offcanvasExampleLabel">
    <div class="offcanvas-header d-flex align-items-center px-4 pt-5">
        <div class="d-flex align-items-center gap-2">
            <img src="{{ asset('assets/logo/redo.png') }}" width="140" alt="Light Logo" class="logo-light">
            <img src="{{ asset('assets/logo/black.png') }}" width="140" alt="Dark Logo" class="logo-dark">
        </div>
        <div data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="fa-solid fa-xmark fs-5"></i>
        </div>
    </div>
    <div class="offcanvas-body p-0 px-2 overflow-hidden">
        <aside class="sidebar-mobile overflow-y-auto" style="scrollbar-width: none">
            <ul class="nav flex-column list-unstyled">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('customer.dashboard') ? 'active' : '' }}"
                        href="{{ route('contractor.dashboard') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-home fs-5"></i></div>
                            <div class="text">Dashboard</div>
                        </div>
                    </a>
                </li>


                <!-- Orders -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('contractor.orders') ? 'active' : '' }}"
                        href="{{ route('contractor.orders') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-box fs-5"></i></div>
                            <div class="text">Orders</div>
                        </div>
                    </a>
                </li>

                <!-- Pricing -->
                <!-- <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('contractor.pricing') ? 'active' : '' }}"
                    href="{{ route('contractor.pricing') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-devices-dollar fs-5"></i></div>
                            <div class="text">Pricing</div>
                        </div>
                    </a>
                </li> -->

                <!-- Payments -->
                <!-- <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('contractor.payments') ? 'active' : '' }}"
                    href="{{ route('contractor.payments') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-wallet fs-5"></i></div>
                            <div class="text">Payments</div>
                        </div>
                    </a>
                </li> -->

                <!-- Support -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('contractor.support') ? 'active' : '' }}"
                        href="{{ route('contractor.support') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-device-mobile-question fs-5"></i></div>
                            <div class="text">Support</div>
                        </div>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('contractor.panels.index') ? 'active' : '' }}"
                        href="{{ route('contractor.panels.index') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-device-mobile-question fs-5"></i></div>
                            <div class="text">Panel</div>
                        </div>
                    </a>
                </li>


                {{-- <p class="px-3 text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Users</p>
                <!-- Admins -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('admins') ? 'active' : '' }}"
                        href="{{ url('admins') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-user fs-5"></i></div>
                            <div class="text">Admins</div>
                        </div>
                    </a>
                </li>

                <!-- Users -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('customers') ? 'active' : '' }}"
                        href="{{ url('customers') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-headphones fs-5"></i></div>
                            <div class="text">Customers</div>
                        </div>
                    </a>
                </li>

                <!-- Contractors -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('contractor') ? 'active' : '' }}"
                        href="{{ url('contractor') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-contract fs-5"></i></div>
                            <div class="text">Contractors</div>
                        </div>
                    </a>
                </li>

                <p class="px-3 text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Roles and Permissions
                </p>
                <!-- Roles -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('roles') ? 'active' : '' }}"
                        href="{{ url('roles') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-circles fs-5"></i></div>
                            <div class="text">Roles</div>
                        </div>
                    </a>
                </li>

                <!-- Permissions -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('permissions') ? 'active' : '' }}"
                        href="{{ url('permissions') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-pointer-pause fs-5"></i></div>
                            <div class="text">Permissions</div>
                        </div>
                    </a>
                </li>

                <p class="px-3 text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Website settings</p>
                <!-- Pages -->
                <li class="nav-item">
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
                </li>

                <!-- Front Pages -->
                <li class="nav-item">
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
                </li>

                <p class="px-3 text fw-lighter my-2 text-uppercase" style="font-size: 13px;">payments</p>
                <!-- Pricing -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('pricing') ? 'active' : '' }}"
                        href="{{ url('pricing') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-devices-dollar fs-5"></i></div>
                            <div class="text">Pricing</div>
                        </div>
                    </a>
                </li>

                <!-- Payments -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('payments') ? 'active' : '' }}"
                        href="{{ url('payments') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-wallet fs-5"></i></div>
                            <div class="text">Payments</div>
                        </div>
                    </a>
                </li>

                <!-- Orders -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('orders') ? 'active' : '' }}"
                        href="{{ url('orders') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-details"></i></div>
                            <div class="text">Orders</div>
                        </div>
                    </a>
                </li>

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
                        href="{{ url('support') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-device-mobile-question fs-5"></i></div>
                            <div class="text">Support</div>
                        </div>
                    </a>
                </li> --}}
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
                    // Update the notification count
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

    // Add event listener to load notifications when dropdown is opened
    document.addEventListener('DOMContentLoaded', function() {
        // Add event listener to notification dropdown
        const notificationDropdownEl = document.querySelector('.notification-dropdown');
        notificationDropdownEl.addEventListener('show.bs.dropdown', function() {
            loadNotifications();
        });
    });
</script>