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

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- jQuery Latest -->
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
    <!-- <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script> -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Toastr JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
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
</head>

<body>

    <div class="d-flex w-100 h-100 overflow-hidden">
        <div>
            @include('admin.layouts.sidebar')
            <!-- Include Sidennnbar -->
        </div>

        <div
            class="h-100 w-100 p-2 px-md-4 py-md-3 d-flex flex-column justify-content-between overflow-y-auto overflow-x-hidden">
            <div class="h-100 d-flex flex-column justify-content-between">
                <div>
                    @include('admin.layouts.header')
                    <!-- Include Header -->
                    @yield('content')
                </div>
                <!-- Main Page Content -->
                @include('admin.layouts.footer')
                <!-- Include Footer (Optional) -->
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    @stack('scripts')

    <script>
        // Global AJAX request handlers
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