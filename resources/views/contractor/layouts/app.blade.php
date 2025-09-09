<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ProjectInbox')</title>
    <link rel="icon" href="{{ asset('assets/favicon/favicon.png') }}" type="image/x-icon">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap"
        rel="stylesheet">

        <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&family=Space+Grotesk:wght@300..700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    {{-- <link rel="stylesheet" href="https://unpkg.com/flipdown@0.3.2/dist/flipdown.css"> --}}
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.css" rel="stylesheet">
    @vite(['resources/js/app.js'])
    @stack('styles')

    <script>
        (function () {
            const theme = localStorage.getItem("theme");
            if (theme === "light") {
            document.documentElement.classList.add("light-theme");
            } else {
            document.documentElement.classList.add("dark-theme");
            }
        })();
    </script>
    <!-- Include tracking scripts -->
    @if(View::exists('promoter.tracking-scripts'))
        @include('promoter.tracking-scripts')
    @endif
</head>

<body>

    <div class="d-flex w-100 h-100 overflow-hidden">
        <div>
            @include('contractor.layouts.sidebar')
            <!-- Include Sidennnbar -->
        </div>

        <div class="h-100 w-100 px-4 py-3 d-flex flex-column justify-content-between overflow-y-auto">
            <div>
                @include('contractor.layouts.header')
                <!-- Include Header -->
                @yield('content')
                <!-- Main Page Content -->
            </div>
            @include('contractor.layouts.footer')
            <!-- Include Footer (Optional) -->
        </div>
    </div>
    <!-- jQuery Latest        -->
    <!-- <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script> -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Toastr JS alert js-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.js"></script>
    <!-- sweeetalert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@3.41.0/dist/apexcharts.min.js"></script>
    {{-- <script src="https://unpkg.com/flipdown@0.3.2/dist/flipdown.min.js"></script> --}}
    <script>
        function updateNotificationCount() {
            fetch('/notifications/unread-count')
                .then(response => response.json())
                .then(data => {
                    const bellIcon = document.querySelector('.ti-bell');
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
                    } else {
                        if (existingDot) {
                            existingDot.remove();
                        }
                    }
                });
        }

        // Handle marking notifications as read
        document.querySelectorAll('.dropdown-notifications-read').forEach(button => {
            button.addEventListener('click', function() {
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
                        // Immediately update the notification count
                        updateNotificationCount();
                        
                        // Remove the unread indicator from this notification
                        this.closest('.notification-item').classList.remove('unread');
                        this.remove();
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });

        // Update count every 30 seconds
        setInterval(updateNotificationCount, 10000);
        
        // Initial update
        document.addEventListener('DOMContentLoaded', updateNotificationCount);
    </script>
    @stack('scripts')
    <script>
        // Global AJAX request handler
        $(document).ajaxStart(function() {
            // Find the submit button in the form being submitted
            const $form = $('form:has(input:focus, select:focus, textarea:focus)');
            const $btn = $form.find('button[type="submit"]');
            if ($btn.length) {
                $btn.addClass('btn-loading');
                $btn.prop('disabled', true);
            }
        });

        $(document).ajaxComplete(function() {
            // Re-enable all submit buttons
            $('button[type="submit"]').removeClass('btn-loading').prop('disabled', false);
        });

        // Handle form submissions
        $(document).on('submit', 'form', function() {
            const $btn = $(this).find('button[type="submit"]');
            $btn.addClass('btn-loading');
            $btn.prop('disabled', true);
        });

    </script>
</body>

</html>