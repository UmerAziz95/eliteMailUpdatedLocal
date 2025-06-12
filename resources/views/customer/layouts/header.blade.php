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
        <div class="">
            <a href="{{ url('customer/pricing') }}"
                class="btn btn-primary animate-gradient btn-sm me-2 d-flex align-items-center gap-1"><i
                    class="ti ti-building-store"></i>
                Buy More Inboxes</a>
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
                <div class="position-sticky bottom-0 py-2 px-3 w-100" style="background-color: var(--secondary-color)">
                    <a href="/customer/settings"
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
    <div class="bg-transparent border-0 p-0 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        @if ($user->profile_image)
            <img src="{{ asset('storage/profile_images/' . $user->profile_image) }}"
                style="border-radius: 50%;" height="40" width="40"
                class="object-fit-cover login-user-profile" alt="User Image">
        @else
            <div class="d-flex justify-content-center align-items-center text-white fw-bold"
                style="border-radius: 50%; width: 40px; height: 40px; font-size: 14px; background-color: #5750bf;">
                {{ $initials }}
            </div>
        @endif

        <div class="d-flex flex-column gap-0">
            <h6 class="mb-0">{{ $user->name ?? 'N/A' }}</h6>
            <small>{{ $user->email ?? 'N/A' }}</small>
        </div>
    </div>

    <ul class="dropdown-menu px-2 py-3" style="min-width: 200px">
        <div class="profile d-flex align-items-center gap-2 px-2">
            @if ($user->profile_image)
                <img src="{{ asset('storage/profile_images/' . $user->profile_image) }}"
                    style="border-radius: 50%;" height="40" width="40"
                    class="object-fit-cover login-user-profile" alt="User Image">
            @else
                <div class="d-flex justify-content-center align-items-center text-white fw-bold"
                    style="border-radius: 50%; width: 40px; height: 40px; font-size: 14px; background-color: #5750bf;">
                    {{ $initials }}
                </div>
            @endif

            <div>
                <h6 class="mb-0">{{ $user->name ?? 'N/A' }}</h6>
                <p class="small mb-0">{{ $user->role->name ?? '' }}</p>
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
            <img src="{{ asset('assets/logo/redo.png') }}" width="140" alt="Light Logo" class="logo-light">
            <img src="{{ asset('assets/logo/black.png') }}" width="140" alt="Dark Logo" class="logo-dark">
        </div>
        <div data-bs-dismiss="offcanvas" aria-label="Close">
            <i class="fa-solid fa-xmark fs-5"></i>
        </div>
    </div>
    <div class="offcanvas-body overflow-hidden">
        <aside class="sidebar-mobile px-2 overflow-y-auto" style="scrollbar-width: none">
            <ul class="nav flex-column list-unstyled">
                {{--
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('dashboard') ? 'active' : '' }}"
                        href="{{ route('customer.dashboard') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-home fs-5"></i></div>
                            <div class="text">Dashboard</div>
                        </div>
                    </a>
                </li> --}}
        
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('customer.dashboard') ? 'active' : '' }}"
                        href="{{ route('customer.dashboard') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-home fs-5"></i></div>
                            <div class="text">Dashboard</div>
                        </div>
                    </a>
                </li>
        
        
                <!-- Orders -->
                <!-- <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ request()->is('pricing') ? 'active' : '' }}"
                        href="{{ url('customer/pricing') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-devices-dollar fs-5"></i></div>
                            <div class="text">Plan</div>
                        </div>
                    </a>
                </li> -->
        
                <!-- pricing -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('customer.orders') ? 'active' : '' }}"
                        href="{{ route('customer.orders') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-box fs-5"></i></div>
                            <div class="text">Orders</div>
                        </div>
                    </a>
                </li>
        
                <!-- Support -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('customer.support') ? 'active' : '' }}"
                        href="{{ route('customer.support') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-device-mobile-question fs-5"></i></div>
                            <div class="text">Support</div>
                        </div>
                    </a>
                </li>
                <!-- Subscriptions -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('customer.subscriptions.view') ? 'active' : '' }}"
                        href="{{ route('customer.subscriptions.view') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-currency-dollar fs-5"></i></i></div>
                            <div class="text">Subscriptions</div>
                        </div>
                    </a>
                </li>
                <!-- invoices -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('customer.invoices.index') ? 'active' : '' }}"
                        href="{{ route('customer.invoices.index') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="ti ti-file-invoice fs-5"></i></div>
                            <div class="text">Invoices</div>
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
        notificationDropdownEl.addEventListener('show.bs.dropdown', function () {
            loadNotifications();
        });

        // Initial notification count update
        updateNotificationCount();
        
        // Update notifications every 30 seconds
        setInterval(updateNotificationCount, 30000);
    });

    function updateNotificationCount() {
        fetch('/notifications/unread-count', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (response.status === 401) {
                // Unauthorized - ignore silently
                return;
            }
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (!data || data.error) return;
            
            const bellIcon = document.querySelector('.ti-bell');
            if (!bellIcon) return;
            
            const count = data.count;
            const existingDot = bellIcon.querySelector('.notification-dot');
            
            if (count > 0) {
                if (!existingDot) {
                    const dot = document.createElement('span');
                    dot.className = 'notification-dot badge-dot position-absolute';
                    dot.style.top = '0';
                    dot.style.right = '0';
                    dot.style.transform = 'translate(50%, -50%)';
                    bellIcon.appendChild(dot);
                }
            } else if (existingDot) {
                existingDot.remove();
            }
        })
        .catch(error => {
            // Silently fail to avoid disrupting the user experience
            console.error('Error fetching notification count:', error);
        });
    }

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