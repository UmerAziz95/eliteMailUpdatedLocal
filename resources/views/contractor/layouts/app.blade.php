<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">   
    <title>@yield('title', 'EliteMailBoxes')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    @stack('styles')
</head>

<body>

    <div class="d-flex w-100 h-100 overflow-hidden">
        <div>
            @include('contractor.layouts.sidebar') <!-- Include Sidennnbar -->
        </div>

        <div class="h-100 w-100 px-4 py-3 d-flex flex-column justify-content-between overflow-y-auto">
            <div>
                @include('contractor.layouts.header') <!-- Include Header -->
                @yield('content') <!-- Main Page Content -->
            </div>
            @include('contractor.layouts.footer') <!-- Include Footer (Optional) -->
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
    <script>
        function updateNotificationCount() {
            fetch('/notifications/unread-count')
                .then(response => response.json())
                .then(data => {
                    const bellIcon = document.querySelector('.ti-bell');
                    const count = data.count;
                    
                    if (count > 0) {
                        if (!bellIcon.querySelector('.notification-dot')) {
                            const dot = document.createElement('span');
                            dot.className = 'badge-dot position-absolute';
                            dot.style.top = '0';
                            dot.style.right = '0';
                            dot.style.transform = 'translate(50%, -50%)';
                            bellIcon.appendChild(dot);
                        }
                    } else {
                        const dot = bellIcon.querySelector('.notification-dot');
                        if (dot) {
                            dot.remove();
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
                        // Remove the unread indicator
                        this.remove();
                        // Update the notification count
                        updateNotificationCount();
                    }
                })
                .catch(error => console.error('Error:', error));
            });
        });
        // Update count every 30 seconds
        // setInterval(updateNotificationCount, 30000);
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
