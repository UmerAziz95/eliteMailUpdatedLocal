<aside class="sidebar px-2 py-4 overflow-y-auto d-none d-xl-block" style="scrollbar-width: none">
    <div class="d-flex align-items-center gap-2">
        <img src="https://cdn.prod.website-files.com/680f5aabe088c7bbcd389903/681b21577e8b6e172787ecb7_Project%20Inbox.svg"
            width="140" alt="">
    </div>
    <div class="form-check" id="toggle-btn" style="position: absolute; right: 10px; top: 33px">
        <input class="form-check-input"
            style="height: 17px; width: 17px; border-radius: 50px !important; cursor: pointer" type="checkbox" value=""
            id="checkDefault">
    </div>

    <ul class="nav flex-column list-unstyled">
        <!-- Dashboard -->



        @can('Dashboard')
        <li class="nav-item">
            <a class="nav-link px-3 d-flex align-items-center {{ Route::is('admin.dashboard') ? 'active' : '' }}"
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
        @can( $item->permission)
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

        {{-- <p class="px-3 text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Website settings</p> --}}
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
                <li><a class="nav-link px-3 d-flex align-items-center" style="gap: 13px" href="{{ url('/') }}"><span
                            class="circle"></span> Faq</a></li>
                <li><a class="nav-link px-3 d-flex align-items-center" style="gap: 13px" href="{{ url('/') }}"><span
                            class="circle"></span> Pricing</a></li>
                <li><a class="nav-link px-3 d-flex align-items-center" style="gap: 13px" href="{{ url('/') }}"><span
                            class="circle"></span> Teams</a></li>
                <li><a class="nav-link px-3 d-flex align-items-center" style="gap: 13px" href="{{ url('/') }}"><span
                            class="circle"></span> Projects</a></li>
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


        <!-- Support -->
        <li class="nav-item">
            <a class="nav-link px-3 d-flex align-items-center {{ Route::is('admin.support') ? 'active' : '' }}"
                href="{{ url('admin/support') }}">
                <div class="d-flex align-items-center" style="gap: 13px">
                    <div class="icons"><i class="ti ti-device-mobile-question fs-5"></i></div>
                    <div class="text">Support</div>
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
        width: 210px;
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
        width: 210px;
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