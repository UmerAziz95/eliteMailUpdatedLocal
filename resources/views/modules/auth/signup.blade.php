<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Signup</title>
    <link rel="icon" href="{{ asset('assets/favicon/favicon.png') }}" type="image/x-icon">
    
    <link rel="stylesheet" href="{{ url('assets/style.css') }}">
    <!-- <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toastr.min.css') }}" /> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    {{--
    <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toastr.min.css') }}" /> --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
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
    <!-- Include tracking scripts -->
    @if(View::exists('promoter.tracking-scripts'))
        @include('promoter.tracking-scripts')
    @endif
</head>
<script>
    var baseurl = "{{ url('/') }}";
</script>

<body>
    <!-- Vertically centered modal -->
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-center p-4 rounded-3">
                <div class="modal-body">
                    <h4 class="fw-bold mb-3 text-black">Verify your Email</h4>
                    <p class="mb-2  text-black">We've sent you a temporary link.<br>
                        Please, check your inbox at</p>
                    <p id="userEmail" class="fw-semibold text-primary"></p>

                    <button type="button" class="btn btn-link mt-3" data-bs-dismiss="modal">Back</button>
                </div>
            </div>
        </div>
    </div>



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
                        <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/auth-register-illustration-dark.png"
                            style="margin-bottom: -3rem" width="400" alt="">
                        <img src="https://demos.pixinvent.com/vuexy-html-admin-template/assets/img/illustrations/bg-shape-image-dark.png"
                            style="margin-bottom: -8rem" width="100%" alt="">
                    </div>

                    <div
                        class="col-md-5 col-lg-4 login-right d-flex align-items-center justify-content-center text-start p-5">
                        <div class="text-white position-relative w-100" style="z-index: 9">
                            <h5 class="fw-bold">Your adventure starts here 🚀</h5>
                            <p>
                                Join thousands of teams rethinking how they purchase Google inboxes for cold email.
                            </p>

                            <form id="registerForm">
                                @csrf
                                <div class="input-group">
                                    <label for="name">First name</label>
                                    <input type="text" name="name" id="name" placeholder="" required>
                                </div>
                                <div class="input-group">
                                    <label for="email">Email</label>
                                    <input type="email" name="email" id="email" placeholder="" required>
                                </div>
                                <div class="input-group">
                                    <label for="password">Password</label>
                                    <input type="password" name="password" id="password" placeholder=""
                                        required>
                                    <span id="togglePassword"
                                        class="input-group-text bg-transparent text-white border-0">
                                        <i class="fas fa-eye-slash"></i>
                                    </span>
                                </div>
                                <div class="input-group">
                                    <label for="password_confirmation">Password Confirmation</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation"
                                        placeholder="" required>
                                    <span id="togglePasswordConfirmation"
                                        class="input-group-text bg-transparent text-white border-0">
                                        <i class="fas fa-eye-slash"></i>
                                    </span>
                                </div>
                                {{-- Phone --}}
                                <!-- <div class="input-group">
                                    <label for="phone">Phone</label>
                                    <input type="text" name="phone" id="phone" placeholder="Phone" required>
                                </div> -->
                                {{-- role customer, contractor radio button --}}
                                <input type="hidden" name="role" id="role" value="customer">
                                <!-- <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="radioDefault1"
                                        value="customer">
                                    <label class="form-check-label" for="radioDefault1">
                                        Customer
                                    </label>
                                </div> -->
                                {{-- <div class="form-check">
                                    <input class="form-check-input" type="radio" name="role" id="radioDefault2"
                                        value="contractor">
                                    <label class="form-check-label" for="radioDefault2">
                                        Contractor
                                    </label>
                                </div> --}}

                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                                        <label class="form-check-label" for="flexCheckDefault">
                                            I agree to the  <a style="text-decoration:none;" class="theme-text" href="https://projectinbox.ai/privacy-policy">Privacy Policy</a> and <a style="text-decoration:none" class="theme-text" href="https://projectinbox.ai/terms-conditions">Terms Of Service</a> 
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" id="submitBtn" class="mt-3 w-100 m-btn py-2 px-4 border-0 rounded-2">Sign
                                    up</button>
                            </form>

                            <div class="mt-3 text-center">
                                <p>Already have an account? <a href="/" class="theme-text text-decoration-none">Sign in
                                        instead</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
        
        document.getElementById('togglePasswordConfirmation').addEventListener('click', function() {
            const passwordField = document.getElementById('password_confirmation');
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
    <script>
        
        $(document).ready(function() {
            $("#registerForm").submit(function(event) {
            event.preventDefault(); // Prevent default form submission
            console.log("Form submitted");

            // Check if terms and conditions checkbox is checked
            if (!$('#flexCheckDefault').is(':checked')) {
                toastr.error('Please accept the Privacy Policy and Terms of Service to continue.', 'Error');
                return false;
            }

            $(".text-danger").html(""); // Clear previous errors

            const submitBtn = $(this).find("button[type=submit]");
            submitBtn.prop("disabled", true).text("Submitting...");

            $.ajax({
                url: "{{ route('register') }}",
                type: "POST",
                data: $(this).serialize(),
               success: function(response) {
                    // toastr.info(response.message);
                    submitBtn.prop("disabled", false).text("Sign up");

                    // Get email from input
                    const email = $('#email').val();
                    // Set email in modal
                    $('#userEmail').text(email);
                    //cleaning input
                      $('#email').val('');
                      $('#name').val('');
                     
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('successModal'));
                    modal.show();
                },
             error: function(xhr) {
            submitBtn.prop("disabled", false).text("Register");

            if (xhr.status === 422) {
                let errors = xhr.responseJSON.errors;
                $.each(errors, function(key, value) {
                    toastr.error(value, 'Error');
                });
            } else if (xhr.status === 403) {
            
                toastr.error(xhr.responseJSON.message || 'User already exist! please login.');
            }else if(xhr.status==419){
                toastr.error('Session expired. Please try again.', 'Error');
                window.location.reload();
            } else {
                toastr.error('An error occurred. Please try again.', 'Error');
            }
           },

            });
        });
    });
    </script>


</body>

</html>