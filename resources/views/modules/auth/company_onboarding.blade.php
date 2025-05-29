<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding - About You</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <link
        href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&amp;ampdisplay=swap"
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Public Sans', sans-serif;
        }

        .onboarding-wrapper {
            max-width: 700px;
            margin: 40px auto;
            background-color: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h5 {
            margin-bottom: 20px;
            font-weight: 600;
        }

        .btn-option {
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 8px 15px;
            cursor: pointer;
            margin-right: 10px;
            margin-bottom: 10px;
        }

        .btn-option.selected {
            background-color: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
        }

        .form-label {
            font-weight: 500;
        }

        .continue-btn {
            width: 100%;
            background-color: #343a40;
            color: white;
        }
    </style>
</head>

<body>

    <div class="container">
        <div class="onboarding-wrapper">
            <div class="text-center mb-4">
                <h4>About you</h4>
                <p>Please, fill out the form to help us offer the best product for you.</p>
            </div>

            <form id="onboardingForm">
                <!-- CSRF Token -->
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="encrypted" value="{{$encrypted}}">

                <!-- Profile Section -->
                <div class="form-section">
                    <h5>Profile</h5>

                    <div class="mb-3">
                        <label class="form-label">First name</label>
                        <input type="text" class="form-control" name="first_name" placeholder="First name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Last name</label>
                        <input type="text" class="form-control" name="last_name" placeholder="Last name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Your role</label>
                        <select class="form-select" name="role">
                            <option>Marketing Manager</option>
                            <option>Developer</option>
                            <option>Designer</option>
                            <option>Founder</option>
                            <option>Other</option>
                        </select>
                    </div>
                </div>

                <!-- Company Section -->
                <div class="form-section">
                    <h5>Company</h5>

                    <div class="mb-3">
                        <label class="form-label">Company name</label>
                        <input type="text" class="form-control" name="company_name" placeholder="Name">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Website</label>
                        <input type="url" class="form-control" name="website" placeholder="https://">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Company size</label>
                        <div class="d-flex flex-wrap gap-2" data-name="company_size">
                            <div class="btn-option">1–5</div>
                            <div class="btn-option">6–50</div>
                            <div class="btn-option">51–500</div>
                            <div class="btn-option">500+</div>
                        </div>
                        <input type="hidden" name="company_size">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Inboxes tested last month</label>
                        <div class="d-flex flex-wrap gap-2" data-name="inboxes_tested">
                            <div class="btn-option">0–20</div>
                            <div class="btn-option">21–50</div>
                            <div class="btn-option">51–500</div>
                            <div class="btn-option">500+</div>
                        </div>
                        <input type="hidden" name="inboxes_tested">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Monthly spend in inboxes</label>
                        <div class="d-flex flex-wrap gap-2" data-name="monthly_spend">
                            <div class="btn-option">0 – $99K</div>
                            <div class="btn-option">100K – $1M</div>
                            <div class="btn-option">1M – $5M</div>
                            <div class="btn-option">5M+</div>
                        </div>
                        <input type="hidden" name="monthly_spend">
                    </div>
                </div>

                <button type="submit" class="btn continue-btn">Continue</button>
            </form>
        </div>
    </div>


</body>

</html>
<script>
    // Toggle button selection and update hidden inputs
    document.querySelectorAll('.form-section .d-flex').forEach(group => {
        const inputName = group.dataset.name;
        const hiddenInput = document.querySelector(`input[name="${inputName}"]`);
        group.querySelectorAll('.btn-option').forEach(btn => {
            btn.addEventListener('click', () => {
                group.querySelectorAll('.btn-option').forEach(sib => sib.classList.remove('selected'));
                btn.classList.add('selected');
                hiddenInput.value = btn.textContent.trim();
            });
        });
    });

    // Clear previous validation errors
    function clearValidationErrors() {
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    }

    // Show validation error messages
    function showValidationErrors(errors) {
        for (const field in errors) {
            const input = document.querySelector(`[name="${field}"]`);
            if (input) {
                input.classList.add('is-invalid');
                const message = document.createElement('div');
                message.className = 'invalid-feedback';
                message.innerText = errors[field][0];
                // Prevent appending duplicate errors
                if (!input.parentElement.querySelector('.invalid-feedback')) {
                    input.parentElement.appendChild(message);
                }
            }
        }
    }

    // Reset toggle buttons
    function resetCustomToggles() {
        document.querySelectorAll('.btn-option').forEach(el => el.classList.remove('selected'));
    }

    // AJAX form submission
    document.getElementById('onboardingForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const form = e.target;
        const data = new FormData(form);
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        clearValidationErrors();

        fetch("{{ route('company.onboarding.store') }}", {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrf,
                "Accept": "application/json"
            },
            body: data
        })
        .then(async response => {
            const result = await response.json();

            if (response.ok) {
                toastr.success("Submitted successfully!");
                form.reset();
                resetCustomToggles();
                // Define redirection values from Blade (Laravel)
                const base_url = @json(url('/'));
                const encrypted = @json($encrypted);

                // Redirect to the desired page
                window.location.href = `${base_url}/plans/public/${encrypted}`;
                            } else if (response.status === 422) {
                showValidationErrors(result.errors);
                toastr.error("Validation failed. Please check the form.");
            } else {
                toastr.error("Submission failed due to a server error.");
            }
        })
        .catch(error => {
            console.error("Submission Error:", error);
            toastr.error("Something went wrong.");
        });
    });
</script>