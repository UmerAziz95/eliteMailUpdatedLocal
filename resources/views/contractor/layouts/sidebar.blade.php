<aside class="sidebar px-2 py-4 overflow-y-auto d-none d-xl-flex flex-column justify-content-between" style="scrollbar-width: none">
    <div>
        <div class="d-flex align-items-center gap-2 justify-content-center">
            <img src="{{ asset('assets/logo/redo.png') }}" width="140" alt="Light Logo" class="logo-light">
            <img src="{{ asset('assets/logo/black.png') }}" width="140" alt="Dark Logo" class="logo-dark">
        </div>
        <!-- <div class="form-check" id="toggle-btn" style="position: absolute; right: 10px; top: 25px">
            <input class="form-check-input"
                style="height: 17px; width: 17px; border-radius: 50px !important; cursor: pointer" type="checkbox" value=""
                id="checkDefault">
        </div> -->
        <ul class="nav flex-column list-unstyled mt-4">
            <!-- Dashboard -->
            {{-- <p class="text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Overview</p> --}}
            <li class="nav-item">
                <a class="nav-link px-3 d-flex align-items-center {{ Route::is('contractor.dashboard') ? 'active' : '' }}"
                    href="{{ route('contractor.dashboard') }}">
                    <div class="d-flex align-items-center" style="gap: 13px">
                        <div class="icons"><i class="ti ti-home fs-5"></i></div>
                        <div class="text">Dashboard</div>
                    </div>
                </a>
            </li>
    
    
            <!-- Orders -->
            {{-- <p class="text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Product</p> --}}
            <li class="nav-item">
                <a class="nav-link px-3 d-flex align-items-center {{ Route::is('contractor.orders') ? 'active' : '' }}"
                    href="{{ route('contractor.orders') }}">
                    <div class="d-flex align-items-center" style="gap: 13px">
                        <div class="icons"><i class="ti ti-box fs-5"></i></div>
                        <div class="text">My Orders</div>
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
    
    
            <li class="nav-item">
                <a href="{{ route('contractor.panels.index') }}"
                    class="nav-link px-3 d-flex align-items-center {{ Route::is('contractor.panels.index') ? 'active' : '' }}">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-layout-dashboard fs-5"></i>
                        <span class="text">Order In Queue</span>
                    </div>
                </a>
            </li>   
    
            <!-- Support -->
            {{-- <p class="text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Product</p> --}}
    
            <li class="nav-item">
                <a class="nav-link px-3 d-flex align-items-center {{ Route::is('contractor.support') ? 'active' : '' }}"
                    href="{{ route('contractor.support') }}">
                    <div class="d-flex align-items-center" style="gap: 13px">
                        <div class="icons"><i class="ti ti-device-mobile-question fs-5"></i></div>
                        <div class="text">Support</div>
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
    </div>

    {{-- <div>
        <a href="/contractor/settings" class="nav-link fs-6 mb-0 px-3 py-2 d-flex align-items-center gap-2">
            <i class="fa-solid fa-gear fs-6"></i>
            Settings
        </a>

        <a href="{{ route('logout') }}" class="px-3 py-2 mt-0 nav-link d-flex align-items-center gap-2 text-danger mb-0">
            <i class="fa-solid fa-right-from-bracket fs-6"></i>
            Logout
        </a>
    </div> --}}
</aside>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".toggle-btn").forEach(function(toggle) {
            let icon = toggle.querySelector(".rotate-icon");
            let collapseTarget = document.querySelector(toggle.getAttribute("href"));

            collapseTarget.addEventListener("show.bs.collapse", () => icon.classList.add("active"));
            collapseTarget.addEventListener("hide.bs.collapse", () => icon.classList.remove("active"));
        });

        // const textElements = document.querySelectorAll('.text');
        // const toggleBtn = document.querySelector('#toggle-btn');
        // const sidebar = document.querySelector('aside');

        // toggleBtn.addEventListener('click', function() {
        //     sidebar.classList.toggle('collapsed');
        //     textElements.forEach(function(item) {
        //         item.style.opacity = sidebar.classList.contains('collapsed') ? '0' : '1';
        //     });
        // });

    });
</script>

<style>
    aside {
        width: 270px;
        transition: width 1s ease;
    }

    aside.collapsed #toggle-btn {
        opacity: 0;
        transition: opacity .4s ease
    }

    aside.collapsed:hover #toggle-btn {
        opacity: 1
    }

    aside.collapsed {
        width: 70px;
    }

    aside.collapsed:hover {
        width: 270px;
    }

    aside.collapsed .nav-link.active {
        width: 55px;
        transition: width .6s ease
    }

    aside.collapsed:hover .nav-link.active {
        width: 100%;
    }

    aside.collapsed .text {
        transition: opacity .6s ease, transform .4s ease;
        transform: translateX(-10px);
        white-space: nowrap
    }

    aside.collapsed:hover .text {
        opacity: 1 !important;
        transform: translateX(0px)
    }
</style>
