<aside class="sidebar px-2 py-4 overflow-y-auto d-none d-xl-block" style="scrollbar-width: none">
    <div class="d-flex align-items-center justify-content-center gap-2 mb-4">
        <img src="{{ asset('assets/logo/redo.png') }}" width="140" alt="Light Logo" class="logo-light">
        <img src="{{ asset('assets/logo/black.png') }}" width="140" alt="Dark Logo" class="logo-dark">
    </div>
    <!-- <div class="form-check" id="toggle-btn" style="position: absolute; right: 10px; top: 23px">
        <input class="form-check-input"
            style="height: 17px; width: 17px; border-radius: 50px !important; cursor: pointer" type="checkbox"
            value="" id="checkDefault">
    </div> -->

    <ul class="nav flex-column list-unstyled">
        <!-- Dashboard -->

        {{-- <p class="text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Overview</p> --}}
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


        <li class="nav-item">
            <a class="nav-link px-3 d-flex align-items-center {{ Route::is('admin.orderQueue.order_queue') ? 'active' : '' }}"
                href="{{ route('admin.orderQueue.order_queue') }}">
                <div class="d-flex align-items-center" style="gap: 13px">
                    <div class="icons"><i class="fa-brands fa-first-order"></i></div>
                    <div class="text">Order In Queue</div>
                </div>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link px-3 d-flex align-items-center {{ Route::is('admin.taskInQueue.index') ? 'active' : '' }}"
                href="{{ route('admin.taskInQueue.index') }}">
                <div class="d-flex align-items-center" style="gap: 13px">
                    <div class="icons"><i class="fa-brands fa-first-order"></i></div>
                    <div class="text">Task In Queue</div>
                </div>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link px-3 d-flex align-items-center {{ Route::is('admin.myTask.index') ? 'active' : '' }}"
                href="{{ route('admin.myTask.index') }}">
                <div class="d-flex align-items-center" style="gap: 13px">
                    <div class="icons"><i class="fa-brands fa-first-order"></i></div>
                    <div class="text">My Task</div>
                </div>
            </a>
        </li>
        <div>
        @php
            $allowedItems = ['My Orders', 'Orders'];
        @endphp
        @foreach ($navigations as $item)
                @if (in_array($item->name, $allowedItems))
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
                @endif
            @endforeach

        

        <!-- <li class="nav-item">
            <a class="nav-link px-3 d-flex align-items-center {{ Route::is('admin.checkout.index') ? 'active' : '' }}"
                href="{{ route('admin.checkout.index') }}">
                <div class="d-flex align-items-center" style="gap: 13px">
                    <div class="icons"><i class="fa-brands fa-first-order"></i></div>
                    <div class="text">Check Out</div>
                </div>
            </a>
        </li> -->
        
    

        {{-- <button class="btn text-white  w-full" type="button" aria-expanded="false" aria-controls="collapseExample">
            <li class="nav-item">
                <div class="d-flex align-items-center" style="gap: 13px">
                    <div class="icons"><i class="ti ti-box fs-5"></i></div>
                    <div class="text">Orders</div>
                </div>
        </button> --}}
        {{-- <p class="text fw-lighter my-2 text-uppercase" style="font-size: 13px;">Product</p> --}}

        @php
            $allowedItems = ['Plans', 'Subscriptions', 'Invoices', 'Panels'];
        @endphp

        

            {{-- MAIN NAVIGATION --}}
            @foreach ($navigations as $item)
                @if (in_array($item->name, $allowedItems))
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
                @endif
            @endforeach

            {{-- USERS SECTION --}}
            {{-- <p class=" text fw-lighter mt-4 text-uppercase" style="font-size: 13px;">Users</p> --}}

            @php
                $excludedItems = ['Dashboard', 'Plans', 'Orders', 'Subscriptions', 'Invoices', 'Panels', 'My Orders'];
            @endphp

            @foreach ($navigations as $item)
                @if (in_array($item->name, $excludedItems))
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
            @endforeach




            {{-- <p class=" text fw-lighter mt-4 text-uppercase" style="font-size: 13px;">misc</p> --}}
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
