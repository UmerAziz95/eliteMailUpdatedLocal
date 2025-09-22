<!-- put this BEFORE the <aside> (preferred: in <head> or just above the aside) -->
<script>
  (function(){
    try {
      if (localStorage.getItem('sidebarPinned') === 'true') {
        // apply an initial class to root so CSS renders collapsed state immediately
        document.documentElement.classList.add('sidebar-pinned');
      }
    } catch (e) {
      // ignore any storage errors
    }
  })();
</script>

<aside class="sidebar px-2 py-4 overflow-y-auto d-none d-xl-block" style="scrollbar-width: none; position: relative;">
    <div class="d-flex align-items-center justify-content-between mb-4 sidebar-header">
        <!-- Logo -->
        <div class="logo d-flex align-items-center justify-content-center">
            <img src="{{ asset('assets/logo/redo.png') }}" width="40" alt="Small Logo" class="logo-small d-none">
            <img src="{{ asset('assets/logo/redo.png') }}" width="140" alt="Full Logo" class="logo-full">
        </div>

        <!-- Pin Button -->
        <button id="pin-btn"
            class="btn btn-sm d-flex align-items-center justify-content-center btn-light border-0 pin-btn">
            <i class="ti ti-pin text-white fs-5"></i>
        </button>
    </div>

    <ul class="nav flex-column list-unstyled">
        {{-- Dynamic Navigation from Database --}}
        @foreach ($navigations as $item)
        @can($item['permission'])
        <li class="nav-item">
            @if (!empty($item['sub_menu']) && count($item['sub_menu']) > 0)
            {{-- Parent with Submenu --}}
            <a class="nav-link px-3 d-flex align-items-center {{ Route::is($item['route']) ? 'active' : '' }}"
                data-bs-toggle="collapse" href="#submenu-{{ $loop->index }}" role="button" aria-expanded="false"
                aria-controls="submenu-{{ $loop->index }}">
                <div class="d-flex align-items-center" style="gap: 13px; flex: 1;">
                    <div class="icons"><i class="{{ $item['icon'] }}"></i></div>
                    <div class="text">{{ $item['name'] }}</div>
                </div>
                <i class="ti ti-chevron-down rotate-icon ms-auto"></i>
            </a>

            <ul class="collapse list-unstyled ps-4" id="submenu-{{ $loop->index }}">
                @foreach ($item['sub_menu'] as $sub_item)
                @can($sub_item['permission'])
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center {{ Route::is($sub_item['route']) ? 'active' : '' }}"
                        href="{{ route($sub_item['route']) }}">
                        <div class="d-flex align-items-center" style="gap: 10px">
                            <div class="icons"><i class="{{ $sub_item['icon'] }}"></i></div>
                            <div class="text">{{ $sub_item['name'] }}</div>
                        </div>
                    </a>
                </li>
                @endcan
                @endforeach
            </ul>
            @else
            {{-- Normal menu without submenu --}}
            <a class="nav-link px-3 d-flex align-items-center {{ Route::is($item['route']) ? 'active' : '' }}"
                href="{{ route($item['route']) }}">
                <div class="d-flex align-items-center" style="gap: 13px">
                    <div class="icons"><i class="{{ $item['icon'] }}"></i></div>
                    <div class="text">{{ $item['name'] }}</div>
                </div>
            </a>
            @endif
        </li>
        @endcan
        @endforeach
    </ul>
</aside>

<script>
 document.addEventListener("DOMContentLoaded", function () {
    // mark JS ready so transitions after initial paint are smooth
    document.documentElement.classList.add('sidebar-js-ready');

    const docEl = document.documentElement;
    const sidebar = document.querySelector("aside.sidebar");
    const pinBtn = document.querySelector("#pin-btn");
    const logoFull = document.querySelector(".logo-full");
    const logoSmall = document.querySelector(".logo-small");
    const textElements = document.querySelectorAll(".text");

    let isPinned = localStorage.getItem("sidebarPinned") === "true";
    let tooltipInstances = [];

    // Apply current state on load
    applySidebarState();

    // Pin button toggle
    pinBtn.addEventListener("click", function () {
        isPinned = !isPinned;
        localStorage.setItem("sidebarPinned", isPinned);
        applySidebarState();
    });

    function applySidebarState() {
        pinBtn.classList.toggle("active", isPinned);

        const pinIcon = pinBtn.querySelector("i");
        if (isPinned) {
            docEl.classList.add("sidebar-pinned");
            sidebar.classList.add("collapsed", "sidebar-pinned");

            if (logoFull) logoFull.classList.add("d-none");
            if (logoSmall) logoSmall.classList.add("d-none");

            // painted icon
            if (pinIcon) {
                pinIcon.classList.remove("ti-pin");
                pinIcon.classList.add("ti-pin-filled");
            }

            // ✅ Enable tooltips only when collapsed
            enableTooltips();

        } else {
            docEl.classList.remove("sidebar-pinned");
            sidebar.classList.remove("collapsed", "sidebar-pinned");

            if (logoFull) logoFull.classList.remove("d-none");
            if (logoSmall) logoSmall.classList.add("d-none");

            // simple/unpainted icon
            if (pinIcon) {
                pinIcon.classList.remove("ti-pin-filled");
                pinIcon.classList.add("ti-pin");
            }

            // ✅ Disable tooltips when expanded
            disableTooltips();
        }

        updateTextVisibility();
    }

    function updateTextVisibility() {
        textElements.forEach(function (item) {
            item.style.opacity = isPinned ? "0" : "1";
        });
    }

    function enableTooltips() {
        disableTooltips(); // clear any existing before reinit
        const navLinks = document.querySelectorAll(".nav-link");

        navLinks.forEach(link => {
            const textEl = link.querySelector(".text");
            if (textEl) {
                link.setAttribute("title", textEl.innerText.trim());
            }
        });

        if (typeof bootstrap !== "undefined") {
            tooltipInstances = [].slice.call(document.querySelectorAll('[title]'))
                .map(el => new bootstrap.Tooltip(el, {
                    placement: "left",
                    customClass: "yellow-tooltip"
                }));
        }
    }

    function disableTooltips() {
        tooltipInstances.forEach(instance => instance.dispose());
        tooltipInstances = [];
        document.querySelectorAll(".nav-link[title]").forEach(el => {
            el.removeAttribute("title");
        });
    }
});

</script>

<style>
    /* base */
    aside.sidebar {
        width: 240px;
        transition: width 0.4s ease;
    }

    /* collapsed (applies after JS runs) */
    aside.sidebar.collapsed {
        width: 70px;
    }

    /* ensure the initial paint uses collapsed state if document had .sidebar-pinned set early */
    html.sidebar-pinned aside.sidebar {
        width: 70px;
    }

    /* avoid transition on initial paint — enable transitions only after we mark js-ready */
    html.sidebar-pinned:not(.sidebar-js-ready) aside.sidebar,
    html:not(.sidebar-js-ready) aside.sidebar {
        transition: none;
    }

    /* hide text when collapsed (root-level selector prevents flicker) */
    html.sidebar-pinned .text,
    aside.sidebar.collapsed .text {
        opacity: 0;
        transform: translateX(-10px);
        transition: opacity 0.3s ease, transform 0.3s ease;
        white-space: nowrap;
    }

    /* normal text state */
    .text {
        opacity: 1;
        transition: opacity 0.3s ease;
    }

    /* hide logos when collapsed via root class (applies immediately) */
    html.sidebar-pinned .logo-full,
    html.sidebar-pinned .logo-small {
        display: none !important;
    }

    /* Pin button default */
    .pin-btn {
        background-color: var(--filter-color);
        height: 35px;
        width: 35px;
        border-radius: 50px;
        transition: all 0.2s ease;
    }

    /* When collapsed, move & shrink pin button near top-right */
    html.sidebar-pinned .pin-btn,
    aside.sidebar.collapsed .pin-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        height: 25px;
        width: 25px;
        border-radius: 50%;
        padding: 0;
    }

    /* Pin button active look */
    #pin-btn.active {
        background-color: #333 !important;
    }

    /* small niceties */
    .logo img {
        transition: opacity 0.2s ease, width 0.2s ease;
    }

    /* Custom yellow tooltip */
.yellow-tooltip .tooltip-inner {
    background-color: orange !important;
    color: black !important;
    font-weight: 500;
    font-size: 13px;
    padding: 6px 10px;
    border-radius: 5px;
}

/* Arrow color */
.yellow-tooltip.bs-tooltip-auto[data-popper-placement^=right] .tooltip-arrow::before,
.yellow-tooltip.bs-tooltip-end .tooltip-arrow::before {
    border-right-color: orange !important;
}

</style>
