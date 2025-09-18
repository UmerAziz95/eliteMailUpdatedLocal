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

        {{-- <div class="dropdown">
            <div class="bg-transparent border-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="ti ti-moon-stars fs-5"></i>
            </div>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item d-flex align-items-center gap-1" id="light-theme" href="#"><i
                            class="ti ti-brightness-up fs-6"></i> Light</a></li>
                <li><a class="dropdown-item d-flex align-items-center gap-1" id="dark-theme" href="#"><i
                            class="ti ti-moon-stars fs-6"></i> Dark</a></li>
            </ul>
        </div> --}}

<div class="dropdown notification-dropdown">
  <!-- Toggle button (manual trigger) -->
  <div class="bg-transparent border-0 p-0" type="button" id="notificationDropdownToggle">
    <i class="ti ti-bell fs-5"></i>
  </div>

  <!-- Dropdown menu -->
  <ul class="dropdown-menu overflow-y-auto py-0" style="min-width: 370px; max-height: 24rem;"
      id="notificationDropdown">
    
    <!-- Header with mark buttons -->
    <div class="position-sticky top-0 d-flex align-items-center justify-content-between p-3"
         style="background-color: var(--secondary-color); z-index: 10">
      <h6 class="mb-0">Notifications</h6>
      <div class="d-flex align-items-center gap-2">
        <i class="p-2 fa-regular fa-envelope fs-5 markReadToAllNotification" 
           data-bs-toggle="tooltip" 
           title="Mark all as read"></i>

        <i class="p-2 fa-solid fa-envelope-open-text fs-5 markUnReadToAllNotification" 
           data-bs-toggle="tooltip" 
           title="Mark all as unread"></i>
      </div>
    </div>

    <!-- Notification list -->
    <div id="notificationList">
      <!-- Notifications will be injected here -->
    </div>

    <!-- Footer -->
    <div class="position-sticky bottom-0 py-2 px-3" style="background-color: var(--secondary-color)">
      <a href="/contractor/settings"
         class="m-btn py-2 px-4 w-100 border-0 rounded-2 d-flex align-items-center justify-content-center">
        View All Notifications
      </a>
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
                style="border-radius: 50%; width: 40px; height: 40px; font-size: 14px; background-color: var(--second-primary);">
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
                    style="border-radius: 50%; width: 40px; height: 40px; font-size: 14px; background-color: var(--second-primary);">
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

                <!-- Shared Orders -->
                <li class="nav-item">
                    <a class="nav-link px-3 d-flex align-items-center {{ Route::is('contractor.shared-orders') ? 'active' : '' }}"
                        href="{{ route('contractor.shared-orders') }}">
                        <div class="d-flex align-items-center" style="gap: 13px">
                            <div class="icons"><i class="fa-solid fa-share-nodes fs-6 text-warning"></i></div>
                            <div class="text">Shared Orders</div>
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


               
            </ul>
        </aside>
    </div>
</div>



<script>
    document.addEventListener("DOMContentLoaded", () => {
        // const lightThemeBtn = document.getElementById("light-theme");
        // const darkThemeBtn = document.getElementById("dark-theme");

        // // Light theme click
        // lightThemeBtn.addEventListener("click", () => {
        //     document.documentElement.classList.add("light-theme");
        //     document.documentElement.classList.remove("dark-theme");
        //     localStorage.setItem("theme", "light");
        // });

        // // Dark theme click
        // darkThemeBtn.addEventListener("click", () => {
        //     document.documentElement.classList.add("dark-theme");
        //     document.documentElement.classList.remove("light-theme");
        //     localStorage.setItem("theme", "dark");
        // });

    });

  document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('notificationDropdownToggle');
    const dropdownInstance = new bootstrap.Dropdown(toggleBtn);
    let isDropdownOpen = false;

    toggleBtn.addEventListener('click', async function () {
        if (!isDropdownOpen) {
            await loadNotifications(); // Load notifications + button logic first
            dropdownInstance.show();   // Then show dropdown
            isDropdownOpen = true;
        } else {
            dropdownInstance.hide();   // Toggle off
            isDropdownOpen = false;
        }
    });

    // Reset state when hidden externally (e.g., ESC or click outside)
    document.getElementById('notificationDropdown').addEventListener('hidden.bs.dropdown', function () {
        isDropdownOpen = false;
    });

    document.getElementById('notificationDropdown').addEventListener('shown.bs.dropdown', function () {
        isDropdownOpen = true;
    });
});

function loadNotifications() {
    return fetch('/notifications/list')
        .then(response => response.json())
        .then(data => {
            handleToggleNotificationReadUnreadBtn(data?.notifications);
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
                            <a href="javascript:void(0)" class="dropdown-notifications-archive">
                                <span class="icon-base ti tabler-x"></span>
                            </a>
                        </div>
                    </div>
                </li>
            `).join('');

            document.querySelectorAll('.dropdown-notifications-read').forEach(button => {
                button.addEventListener('click', handleNotificationRead);
            });
        })
        .catch(error => console.error('Error loading notifications:', error));
}

function handleToggleNotificationReadUnreadBtn(notifications) {
    const unreadCount = notifications.filter(n => !n.is_read).length;
    const readCount = notifications.length - unreadCount;

    const markReadBtn = document.querySelector('.markReadToAllNotification');
    const markUnreadBtn = document.querySelector('.markUnReadToAllNotification');

    if (readCount > 0 && unreadCount > 0) {
        markReadBtn.style.display = 'block';
        markUnreadBtn.style.display = 'block';
    } else if (unreadCount === 0) {
        markReadBtn.style.display = 'none';
        markUnreadBtn.style.display = 'block';
    } else if (readCount === 0) {
        markReadBtn.style.display = 'block';
        markUnreadBtn.style.display = 'none';
    }
}

function handleNotificationRead(e) {
    const id = e.currentTarget.dataset.id;

    fetch(`/notifications/mark-read/${id}`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(() => loadNotifications());
}
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const markReadButton = document.querySelector('.markReadToAllNotification');
    if (markReadButton) {
        markReadButton.addEventListener('click', function () {
            fetch('/contractor/notifications/mark-all-as-read', {
                method: 'GET',
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success(data.message)
                     console.log(data)
                   // updateNotificationCount();
                      window.location.reload();
                
                    document.querySelectorAll('.dropdown-notifications-read').forEach(el => el.remove());
                    // Close the dropdown
                    const dropdownMenu = document.getElementById('notificationDropdown');
                    const bsDropdown = bootstrap.Dropdown.getInstance(dropdownMenu);
                    if (bsDropdown) {
                        bsDropdown.hide();
                    }
                }
            })
            .catch(error => {
                console.error('Error marking all notifications as read:', error);
            });
        });
    }
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
    const markUnReadButton = document.querySelector('.markUnReadToAllNotification');
    if (markUnReadButton) {
        markUnReadButton.addEventListener('click', function () {
            fetch('/contractor/notifications/mark-all-as-unread', {
                method: 'GET',
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                   toastr.success(data.message)
                    
                    // updateNotificationCount();
                     window.location.reload();
                                // Close the dropdown
                    const dropdownMenu = document.getElementById('notificationDropdown');
                    const bsDropdown = bootstrap.Dropdown.getInstance(dropdownMenu);
                    if (bsDropdown) {
                        bsDropdown.hide();
                    }
                }
            })
            .catch(error => {
                console.error('Error marking all notifications as read:', error);
            });
        });
    }
});
</script>