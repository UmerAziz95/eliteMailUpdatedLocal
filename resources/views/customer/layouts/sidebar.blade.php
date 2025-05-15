<aside class="sidebar px-2 py-4 overflow-y-auto" style="scrollbar-width: none">
    <div class="d-flex align-items-center gap-2">
        <img src="https://cdn-icons-png.flaticon.com/128/4439/4439182.png" width="40" alt="">
        <h4 class="text fs-5 mb-0">Mailboxes</h4>
    </div>
    <div class="form-check" id="toggle-btn" style="position: absolute; right: 10px; top: 25px">
        <input class="form-check-input"
            style="height: 17px; width: 17px; border-radius: 50px !important; cursor: pointer" type="checkbox" value=""
            id="checkDefault">
    </div>
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
                    <div class="icons"><i class="ti ti-details"></i></div>
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
                    <div class="icons"><i class="ti ti-device-mobile-question fs-5"></i></div>
                    <div class="text">Subscriptions</div>
                </div>
            </a>
        </li>
        <!-- invoices -->
        <li class="nav-item">
            <a class="nav-link px-3 d-flex align-items-center {{ Route::is('customer.invoices.index') ? 'active' : '' }}"
                href="{{ route('customer.invoices.index') }}">
                <div class="d-flex align-items-center" style="gap: 13px">
                    <div class="icons"><i class="ti ti-device-mobile-question fs-5"></i></div>
                    <div class="text">Invoices</div>
                </div>
            </a>
        </li>

    </ul>
</aside>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".toggle-btn").forEach(function(toggle) {
            let icon = toggle.querySelector(".rotate-icon");
            let collapseTarget = document.querySelector(toggle.getAttribute("href"));

            collapseTarget.addEventListener("show.bs.collapse", () => icon.classList.add("active"));
            collapseTarget.addEventListener("hide.bs.collapse", () => icon.classList.remove("active"));
        });

        const textElements = document.querySelectorAll('.text');
        const toggleBtn = document.querySelector('#toggle-btn');
        const sidebar = document.querySelector('aside');

        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            textElements.forEach(function(item) {
                item.style.opacity = sidebar.classList.contains('collapsed') ? '0' : '1';
            });
        });

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