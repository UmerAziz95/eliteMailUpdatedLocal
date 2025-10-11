<aside class="sidebar px-2 py-4 overflow-y-auto d-none d-xl-flex flex-column justify-content-between"
    style="scrollbar-width: none">
    <div>
        <div class="d-flex align-items-center justify-content-center gap-2" style="margin-bottom: 1.15rem">
            <a href="https://projectinbox.ai"><img src="{{ asset('assets/logo/redo.png') }}" width="140" alt="Light Logo"
                    class="logo-light"></a>
            <img src="{{ asset('assets/logo/black.png') }}" width="140" alt="Dark Logo" class="logo-dark">
        </div>
        <!-- <div class="form-check" id="toggle-btn" style="position: absolute; right: 10px; top: 25px">
            <input class="form-check-input"
                style="height: 17px; width: 17px; border-radius: 50px !important; cursor: pointer" type="checkbox" value=""
                id="checkDefault">
        </div> -->
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
            {{-- <p class="text fw-lighter my-2 text-uppercase text-white" style="font-size: 13px;">Overview</p> --}}
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
            {{-- <p class="text fw-lighter my-2 text-uppercase text-white" style="font-size: 13px;">Product</p> --}}
            <li class="nav-item">
                <a class="nav-link px-3 d-flex align-items-center {{ Route::is('customer.orders') ? 'active' : '' }}"
                    href="{{ route('customer.orders') }}">
                    <div class="d-flex align-items-center" style="gap: 13px">
                        <div class="icons"><i class="ti ti-box fs-5"></i></div>
                        <div class="text">Orders</div>
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
            <!-- pool orders -->
            <!-- pool-orders.index -->
            <li class="nav-item">
                <a class="nav-link px-3 d-flex align-items-center {{ Route::is('customer.pool-orders.index') ? 'active' : '' }}"
                    href="{{ route('customer.pool-orders.index') }}">
                    <div class="d-flex align-items-center" style="gap: 13px">
                        <div class="icons"><i class="ti ti-package fs-5"></i></div>
                        <div class="text">Pool Orders</div>
                    </div>
                </a>
            </li>
            

            <!-- Support -->
            {{-- <p class="text fw-lighter my-2 text-uppercase text-white" style="font-size: 13px;">organization</p> --}}
            <li class="nav-item">
                <a class="nav-link px-3 d-flex align-items-center {{ Route::is('customer.support') ? 'active' : '' }}"
                    href="{{ route('customer.support') }}">
                    <div class="d-flex align-items-center" style="gap: 13px">
                        <div class="icons"><i class="ti ti-device-mobile-question fs-5"></i></div>
                        <div class="text">Support</div>
                    </div>
                </a>
            </li>
        </ul>
    </div>
    
    <div style="background-color: var(--second-primary); border: 2px solid #7669ff;"
        class="py-4 px-3 rounded-3 my-3 mx-xxl-3">
        <div class="d-flex flex-column mb-3">
            <h6 class="mb-1 text-white">Do you want to buy more inboxes from here?</h6>
            <small class="text-white opacity-75">Click here to buy more inboxes</small>
        </div>
        <a style="background-color: var(--second-primary); font-size: 12px;"
            class="mt-1 shadow border-0 rounded-2 py-2 px-4 text-white animate-gradient"
            href="{{ route('customer.pricing') }}">Buy Now</a>
    </div>

    <div>
        <a href="/customer/settings" class="nav-link fs-6 py-2 mb-0 px-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-gear fs-6"></i>
            Settings
        </a>

        <a href="{{ route('logout') }}" class=" py-2 px-3 nav-link mt-0 d-flex align-items-center gap-2 text-danger mb-0">
            <i class="fa-solid fa-right-from-bracket fs-6"></i>
            Logout
        </a>
    </div>
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
        width: 220px;
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
        width: 220px;
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
