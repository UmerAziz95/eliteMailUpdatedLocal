<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>@yield('title')</title>
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
                        <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/auth-login-illustration-dark.png"
                            style="margin-bottom: -3rem" width="400" alt="">
                        <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/bg-shape-image-dark.png"
                            style="margin-bottom: -8rem" width="100%" alt="">
                    </div>

                    <div
                        class="col-md-5 col-lg-4 login-right d-flex align-items-center justify-content-center text-start p-5">
                        <div class="text-white position-relative w-100" style="z-index: 9">
                            <h5 class="fw-bold">Welcome to Elite Mailboxes 👋</h5>
                            <p>
                                Please sign-in to your account and start the adventure
                            </p>
                            @if (session('success'))
                                <div class="alert alert-success">
                                    {{ session('success') }}
                                </div>
                            @endif
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form action="{{ route('doLogin') }}" method="POST">
                                @csrf
                                <div class="input-group">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" id="email" placeholder="Email">
                                    {{-- error --}}
                                    {{-- @error('email')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror --}}
                                </div>
                                <div class="input-group">
                                    <label for="password">Password</label>
                                    <input type="password" name="password" id="password" placeholder="Password">
                                    <span id="togglePassword"
                                        class="input-group-text bg-transparent text-white border-0">
                                        <i class="fas fa-eye-slash"></i>
                                    </span>
                                    {{-- error --}}
                                    {{-- @error('password')
                                        <div class="text-danger">{{ $message }}</div>
                                    @enderror --}}
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value=""
                                            id="flexCheckDefault">
                                        <label class="form-check-label" for="flexCheckDefault">
                                            I agree to privacy policy & terms
                                        </label>
                                    </div>

                                    <div>
                                        <a href="{{ route('password.request') }}"
                                            class="theme-text text-decoration-none">Forget
                                            Password?</a>
                                    </div>
                                </div>
                                <div class="w-100 mt-3">
                                    {{-- <a href="{{ route('login') }}" class="w-100 d-flex align-items-center justify-content-center m-btn py-2 px-4 border-0 rounded-2 text-decoration-none">
                                        Sign In
                                    </a> --}}
                                    <button type="submit"
                                        class="w-100 d-flex align-items-center justify-content-center m-btn py-2 px-4 border-0 rounded-2 text-decoration-none">
                                        Sign In
                                    </button>
                                </div>
                            </form>

                            <div class="mt-3 text-center">
                                <p>New on our platform? <a href="{{ route('register') }}"
                                        class="theme-text text-decoration-none">Create
                                        an account</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const icon = this.querySelector('i');
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });
    </script>

</body>

</html>
