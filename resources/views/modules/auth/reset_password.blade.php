<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ url('assets/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />

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

        .toast-success {
            background-color: #28a745 !important;
            /* Bootstrap green */
        }

        .toast-error {
            background-color: #dc3545 !important;
            /* Bootstrap red */
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
                            <h5 class="fw-bold">Forgot Password? 🔒</h5>
                            <p>
                                Enter your email and we'll send you instructions to reset your password
                            </p>

                            <form action="{{ route('password.update') }}" method="POST" id="resetPasswordForm">
                                @csrf
                                <input type="hidden" name="token" value="{{ $token }}">
                                <input type="hidden" name="email" value="{{ $email }}">

                                <div class="input-group">
                                    <label for="password">New Password</label>
                                    <input type="password" name="password" id="password"
                                        class="@error('password') is-invalid @enderror" placeholder="*************"
                                        value="{{old('password')}}" required>
                                    <span class="input-group-text" id="togglePassword1">
                                        <i class="fas fa-eye-slash"></i>
                                    </span>
                                    @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>

                                <div class="input-group">
                                    <label for="password_confirmation">Confirm Password</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                        class="" placeholder="*************" value="{{old('password_confirmation')}}"
                                        required>
                                    <span class="input-group-text" id="togglePassword2">
                                        <i class="fas fa-eye-slash"></i>
                                    </span>
                                </div>

                                <button type="submit" class="mt-3 w-100 m-btn py-2 px-4 border-0 rounded-2">Reset
                                    Password</button>
                            </form>

                            <div class="mt-3 text-center">
                                <a href="{{ route('login') }}" class="theme-text text-decoration-none">
                                    <i class="fa-solid fa-chevron-left"></i> Back to login
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000"
    };

    // Password toggle functionality
    document.getElementById('togglePassword1').addEventListener('click', function () {
        togglePasswordVisibility('password', this);
    });

    document.getElementById('togglePassword2').addEventListener('click', function () {
        togglePasswordVisibility('password_confirmation', this);
    });

    function togglePasswordVisibility(inputId, element) {
        const passwordField = document.getElementById(inputId);
        const icon = element.querySelector('i');

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        } else {
            passwordField.type = 'password';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        }
    }

    // Form validation
    document.getElementById('resetPasswordForm').addEventListener('submit', function (e) {
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('password_confirmation').value;

        if (password !== confirmPassword) {
          
            e.preventDefault();
            toastr.error('Passwords do not match!');
            return false;
        }

        if (password.length < 8) {
            e.preventDefault();
            toastr.error('Password must be at least 8 characters long!');
            return false;
        }
    });
    </script>

</body>

</html>