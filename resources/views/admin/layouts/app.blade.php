<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'EliteMailBoxes')</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- jQuery Latest -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    @stack('styles')
</head>

<body>

    <div class="d-flex w-100 h-100 overflow-hidden">
        <div>
            @include('admin.layouts.sidebar') <!-- Include Sidennnbar -->
        </div>

        <div class="h-100 w-100 px-4 py-3 d-flex flex-column justify-content-between overflow-y-auto">
            <div>
                @include('admin.layouts.header') <!-- Include Header -->
                @yield('content') <!-- Main Page Content -->
            </div>
            @include('admin.layouts.footer') <!-- Include Footer (Optional) -->
        </div>
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

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
