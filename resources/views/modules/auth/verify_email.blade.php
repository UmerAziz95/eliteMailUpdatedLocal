<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Verify Email</title>
    <link rel="stylesheet" href="{{ url('assets/style.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toastr.min.css') }}" /> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toastr.min.css') }}" />
    <style>
        .login {
            background-color: var(--primary-color);
        }

        .login-right {
            background-color: var(--secondary-color);
        }

        .input-group {
            position: relative;
            margin-bottom: 15px;
        }

        .input-group input {
            width: 100%;
            padding: 7px 10px;
            padding-right: 40px;
            box-sizing: border-box;
            border: 1px solid var(--input-border);
            font-size: 14px
        }

        .input-group input:focus {
            border: 1px solid var(--secondary-color) !important
        }

        .input-group .input-group-text {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-16%);
            cursor: pointer;
        }

        .form-check-input {
            background-color: transparent;
            border-radius: 4px !important
        }
    </style>
</head>
<script>
    var baseurl = "{{ url('/') }}";
</script>

<body>

    <div class="preloader">
        <div class="circle circle5 c51"></div>
    </div>
    <input type="hidden" name="url" id="url" value="{{ URL::to('/') }}">
    <input type="hidden" name="table_name" id="table_name" value="@yield('table')">

    <div>
        <div class="content">
            <section class="login vh-100 w-100">
                <div class="row h-100 g-0 justify-content-between">
                    <div
                        class="d-none col-md-7 col-lg-8 d-md-flex flex-column align-items-center justify-content-center overflow-hidden">
                        <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/auth-forgot-password-illustration-dark.png"
                            style="margin-bottom: -3rem" width="400" alt="">
                        <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/bg-shape-image-dark.png"
                            style="margin-bottom: -8rem" width="100%" alt="">
                    </div>

                    <div
                        class="col-md-5 col-lg-4 login-right d-flex align-items-center justify-content-center text-start p-5">
                        <div class="text-white position-relative w-100" style="z-index: 9">
                            <h5 class="fw-bold">Enter Four Digits Code</h5>

                            {{-- Success Message --}}
                            @if (session('success'))
                            <div class="alert alert-success">
                                {{ session('success') }}
                            </div>
                            @endif

                            {{-- Error Messages --}}
                            @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif

                            {{-- Verification Code Form --}}
                            <form action="{{ route('verify.email.code') }}" method="POST">
                                @csrf
                                <input type="hidden" name="email" value="{{ old('email', request()->email) }}">
                                <input type="hidden" name="encrypted" value="{{ $encrypted }}">

                                <div class="mb-4">
                                    <label for="code" class="form-label text-white">Enter the 4-digit code sent to your
                                        email</label>
                                    <div class="d-flex gap-2 justify-content-between">
                                        @for ($i = 1; $i <= 4; $i++) <input type="text" name="code[]" maxlength="1"
                                            class="form-control text-center" required
                                            oninput="moveToNext(this, {{ $i }})">
                                            @endfor
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <button type="submit"
                                        class="d-flex align-items-center justify-content-center text-decoration-none w-100 m-btn py-2 px-4 border-0 rounded-2">
                                        Verify Code
                                    </button>
                                </div>
                            </form>

                            <div class="mt-3 text-center d-flex flex-column gap-2">
                                <a href="{{ route('login') }}" class="theme-text text-decoration-none">
                                    <i class="fa-solid fa-chevron-left"></i> Back to login
                                </a>

                                {{-- Use named route if available, fallback to url with absolute path --}}
                                <a href="#" class="theme-text text-decoration-none resendVerification">
                                    <i class="fa-solid fa-rotate-right"></i> Resend verification email
                                </a>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </div>
    </div>




</body>

</html>

<script>
    function moveToNext(current, index) {
        if (current.value.length === 1) {
            const next = current.parentElement.querySelectorAll('input')[index];
            if (next) {
                next.focus();
            }
        }
       }
        document.addEventListener('DOMContentLoaded', function () { 
            const resendBtn = document.querySelector('.resendVerification');
            if (resendBtn) {
                resendBtn.addEventListener('click', function (e) {
                    e.preventDefault();

                    // Pass the encrypted URL from Blade as a string
                    const url = @json(url('resend-verfication-code/' . $encrypted))
                
                    window.location.href=url
                });
            }
        });


</script>