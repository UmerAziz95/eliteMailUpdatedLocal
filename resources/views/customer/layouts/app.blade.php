<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EliteMailBoxes')</title>

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css" />
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    @stack('styles')
</head>

<body>

    <div class="d-flex w-100 h-100 overflow-hidden">
        <div>
            @include('customer.layouts.sidebar') <!-- Include Sidebar -->
        </div>

        <div class="h-100 w-100 px-4 py-3 d-flex flex-column justify-content-between overflow-y-auto">
            <div>
                @include('customer.layouts.header') <!-- Include Header -->
                @yield('content') <!-- Main Page Content -->
            </div>
            @include('customer.layouts.footer') <!-- Include Footer (Optional) -->
        </div>
    </div>

    <!-- Core JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <!-- Other plugins -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Swiper/11.0.5/swiper-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    @stack('scripts')

    <script>
        // Setup CSRF token for all Ajax requests
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

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
