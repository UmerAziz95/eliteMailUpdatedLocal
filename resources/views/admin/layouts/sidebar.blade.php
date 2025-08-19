<aside class="sidebar px-2 py-4 overflow-y-auto d-none d-xl-block" style="scrollbar-width: none">
    <div class="d-flex align-items-center justify-content-center gap-2 mb-4">
        <img src="{{ asset('assets/logo/redo.png') }}" width="140" alt="Light Logo" class="logo-light">
        <img src="{{ asset('assets/logo/black.png') }}" width="140" alt="Dark Logo" class="logo-dark">
    </div>

    <ul class="nav flex-column list-unstyled">
        {{-- Dynamic Navigation from Database --}}
        @foreach ($navigations as $item)
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
