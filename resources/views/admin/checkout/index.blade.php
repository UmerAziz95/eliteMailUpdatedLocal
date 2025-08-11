@extends('customer.layouts.app')

@section('title', 'Checkout')

@push('styles')
<style>
    .qty-input {
        width: 60px;
        text-align: center;
        background: transparent;
        border: none;
        color: #fff;
    }

    .btn-outline-light {
        border-color: #666;
        color: #fff;
    }

    .btn-outline-light:hover {
        background-color: #333;
    }

    .secure-note {
        font-size: 12px;
        color: #999;
    }
</style>
@endpush

@section('content')


@if($plan)
<div class="container">
    <a href="#" class="text-purple text-decoration-none small">&lt; Back to site</a>
    <h2 class="mt-4 fs-5">Checkout</h2>
    <div>
        <input type="hidden" id="page_id" value="{{$page_id}}">
        <input type="hidden" id="plan_id" value="{{$planId}}">
    </div>

    <div class="row g-4">
        <!-- LEFT SIDE -->
        <div class="col-lg-8">
            <!-- Step 1: Cart -->
            <div id="error-messages"></div>
            <div id="cart-left">
                <div class="card card-custom p-4">
                    <div class="d-flex">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg" alt="Google"
                            class="me-3" style="width: 60px; height: 60px; object-fit: contain;">
                        <div class="flex-grow-1">
                            <span class="badge bg-secondary text-uppercase small rounded-5">Plan</span>
                            <h5 class="mt-2 mb-1">Google Workspace Package</h5>
                            <ul class="mb-2">
                                <li class="small opacity-75">1 - 249 Inboxes: $3.50 / Inbox</li>
                                <li class="small opacity-75">250 - 1,249 Inboxes: $3.00 / Inbox</li>
                                <li class="small opacity-75">1,250+ Inboxes: $2.50 / Inbox</li>
                            </ul>
                            <p class="small mb-3">
                                Complete technical setup (DKIM, DMARC, MX Records, SPF, Custom Domain Tracking, and
                                Domain
                                Forwarding).
                                Unlimited free replacement inboxes. Delivery in 12 hours or less.
                            </p>

                            <!-- Quantity & Price -->
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="input-group input-group-sm w-auto">
                                    <strong id="unit-price" class="me-2">${{ $plan->price ?? 0 }}<small
                                            class="fw-light">X</small></strong>
                                    <button class="btn btn-primary rounded-0" type="button" id="decrease-btn">−</button>
                                    <input type="number" style="max-width: 70px; border: 1px solid #ffffff36;" id="qty"
                                        class="px-2 bg-transparent rounded-0" value="1">
                                    <button class="btn btn-primary rounded-0" type="button" id="increase-btn">+</button>
                                </div>
                                <strong id="item-total">${{ $plan->price ?? 0 }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Billing Form -->
            <div id="billing-left" class="">
                <div class="card card-custom p-3 mb-4">
                    <h5 class="mb-2">Account Details</h5>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <p class="mb-0 small text-white"><strong class="opacity-50 fw-semibold">First Name:</strong>
                                Paul</p>
                            <p class="mb-0 small text-white"><strong class="opacity-50 fw-semibold">Email:</strong>
                                5dsolutions.qualityassurance@gmail.com</p>
                        </div>
                        <a href="#" class="text-primary">Edit</a>
                    </div>
                </div>


                <form class="card p-3">
                    <h5 class="mb-3">Billing Address</h5>

                    <div class="row mb-3">
                        <div class="col">
                            <input type="email" class="form-control" id="billingEmail" name="email" placeholder="Email">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <input type="text" class="form-control" id="billingFirstName" name="first_name"
                                placeholder="First Name">
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" id="billingLastName" name="last_name"
                                placeholder="Last Name">
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="text" class="form-control" id="billingAddress1" name="address_line1"
                            placeholder="Address Line 1">
                    </div>

                    <div class="mb-3">
                        <input type="text" class="form-control" id="billingAddress2" name="address_line2"
                            placeholder="Address Line 2 (optional)">
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <input type="text" class="form-control" id="billingCity" name="city" placeholder="City">
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" id="billingZip" name="zip"
                                placeholder="ZIP (optional)">
                        </div>
                    </div>

                  <div class="mb-3">
    <input type="text" 
           class="form-control" 
           id="billingState" 
           name="state" 
           value=""
           placeholder="Enter State">
</div>

<div class="mb-3">
    <input type="text" 
           class="form-control" 
           id="billingCountry" 
           name="country" 
           placeholder="Enter Country" 
           value="">
</div>


                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="sameAddress" name="same_as_shipping">
                        <label class="form-check-label" for="sameAddress">
                            Shipping address is the same as my billing address
                        </label>
                    </div>
                </form>

                {{-- <div class="card card-custom p-4 mt-4">
                    <h5 class="mb-3">Payment Details</h5>

                    <label class="form-label fw-semibold">Card</label>
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <img src="https://img.icons8.com/color/40/visa.png" alt="Visa" height="24">
                        <img src="https://img.icons8.com/color/40/mastercard.png" alt="Mastercard" height="24">
                        <img src="https://img.icons8.com/color/40/discover.png" alt="Discover" height="24">
                        <img src="https://img.icons8.com/color/40/amex.png" alt="Amex" height="24">
                        <img src="https://img.icons8.com/color/40/mir.png" alt="Mir" height="24">
                    </div>

                    <div class="mb-3 position-relative">
                        <label for="cardNumber" class="form-label">Number</label>
                        <input type="text" id="cardNumber" class="form-control" placeholder="1234 5678 9012 3456">
                        <i class="fa-regular fa-credit-card position-absolute"
                            style="right: 12px; bottom: 12px; color: #888;"></i>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="expiry" class="form-label">Expiry</label>
                            <input type="text" id="expiry" class="form-control" placeholder="MM / YY">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="cvv" class="form-label">Cvv</label>
                            <input type="text" id="cvv" class="form-control" placeholder="123">
                        </div>
                    </div>
                </div> --}}

            </div>

            <!-- Step 3: Review Card -->
            {{-- <div id="review-left" class="">
                <!-- Account Details -->
                <div class="card card-custom p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-3">Account Details</h5>
                        <a href="#" class="text-primary toggle-edit" data-target="account">Edit</a>
                    </div>
                    <p class="mb-1 small text-white">
                        <strong class="opacity-50 fw-light">First Name:</strong>
                        <span id="reviewFirstName">Test Customer</span>
                        <input type="text" class="form-control d-none mt-1" id="inputFirstName" value="Test Customer">
                    </p>
                    <p class="mb-0 small text-white">
                        <strong class="opacity-50 fw-light">Email:</strong>
                        <span id="reviewEmail">qut.0112@hotmail.com</span>
                        <input type="email" class="form-control d-none mt-1" id="inputEmail"
                            value="qut.0112@hotmail.com">
                    </p>
                </div>

                <!-- Billing Address -->
                <div class="card card-custom p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-3">Billing Address</h5>
                        <a href="#" class="text-primary toggle-edit" data-target="billing">Edit</a>
                    </div>
                    <p class="mb-1 small text-white">
                        <strong class="opacity-50 fw-light">First Name:</strong>
                        <span id="reviewBillingFirstName">5D Customer</span>
                        <input type="text" class="form-control d-none mt-1" id="inputBillingFirstName"
                            value="5D Customer">
                    </p>
                    <p class="mb-1 small text-white">
                        <strong class="opacity-50 fw-light">Last Name:</strong>
                        <span id="reviewBillingLastName">Test</span>
                        <input type="text" class="form-control d-none mt-1" id="inputBillingLastName" value="Test">
                    </p>
                    <p class="mb-0 small text-white">
                        <strong class="opacity-50 fw-light">Address:</strong>
                        <span id="reviewBillingAddress">Address Line 1, City, ZIP</span>
                        <input type="text" class="form-control d-none mt-1" id="inputBillingAddress"
                            value="Address Line 1, City, ZIP">
                    </p>
                </div>

                <!-- Shipping Address -->
                <div class="card card-custom p-3 mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-3">Shipping Address</h5>
                        <a href="#" class="text-primary toggle-edit" data-target="shipping">Edit</a>
                    </div>
                    <p class="mb-1 small text-white">
                        <strong class="opacity-50 fw-light">First Name:</strong>
                        <span id="reviewShippingFirstName">5D Customer</span>
                        <input type="text" class="form-control d-none mt-1" id="inputShippingFirstName"
                            value="5D Customer">
                    </p>
                    <p class="mb-1 small text-white">
                        <strong class="opacity-50 fw-light">Last Name:</strong>
                        <span id="reviewShippingLastName">Test</span>
                        <input type="text" class="form-control d-none mt-1" id="inputShippingLastName" value="Test">
                    </p>
                    <p class="mb-0 small text-white">
                        <strong class="opacity-50 fw-light">Address:</strong>
                        <span id="reviewShippingAddress">Same as billing</span>
                        <input type="text" class="form-control d-none mt-1" id="inputShippingAddress"
                            value="Same as billing">
                    </p>
                </div>
            </div> --}}



        </div>



        <!-- RIGHT SIDE -->
        <div class="col-lg-4">
            <div class="card card-custom p-4">
                <h5 class="mb-3">Order summary</h5>
                <div class="d-flex justify-content-between small mb-1">
                    <span>Google Workspace Package</span>
                    <span id="summary-unit-price">${{ $plan->price ?? 0 }}</span>
                </div>
                <div class="d-flex justify-content-between small fw-semibold mb-3">
                    <span>Subtotal (<span id="item-count">1</span> item)</span>
                    <span id="summary-subtotal">${{ $plan->price ?? 0 }}</span>
                </div>
                <hr class="border-secondary" />
                <div class="d-flex justify-content-between fw-bold mb-3">
                    <span>Total</span>
                    <span id="summary-total">${{ $plan->price ?? 0 }}</span>
                </div>
                <?php
                $nextMonthDate = date('F j, Y', strtotime('+1 month'));
                ?>

                <p class="small mb-4">
                    Next charge on <span class="text-white fw-semibold">
                        <?= $nextMonthDate ?>
                    </span><br>
                    <a href="#" class="theme-text text-decoration-underline">Future charges</a>
                </p>
                {{-- <button id="checkoutBtn" class="btn btn-primary w-100 fw-semibold">Proceed To Checkout</button>
                --}}

                {{-- <p class="text-center secure-note mt-3">
                    🔒 Secure Checkout by <strong>Chargebee</strong>
                </p> --}}
            </div>
        </div>
        <form id="payment-form" class="card card-custom p-4 mt-4">
            <h5 class="mb-3">Payment Details</h5>

            <label class="form-label fw-semibold">Card Information</label>
            <div class="mb-3">
                <!-- The card component will be mounted here -->
                <div id="card-component" class="border rounded p-3" style="min-height: 150px;"></div>
                <div id="card-errors" class="text-danger small mt-2"></div>
            </div>

            <button type="submit" id="pay-button" class="btn btn-primary w-100 mt-3">Proceed to checkout</button>

            <p class="text-center secure-note mt-3">
                🔒 Secure Checkout by <strong>Chargebee</strong>
            </p>
        </form>


    </div>
</div>
@else
<div class="container">
    <div class="alert alert-danger mt-4" role="alert">
        <strong>Error:</strong> The selected plan is not available or has been removed.
    </div>
</div>
@endif
@endsection


@push('scripts')


<script src="https://js.chargebee.com/v2/chargebee.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {

    // 1. Initialize Chargebee instance
    const cbInstance = Chargebee.init({
        site: 'projectinbox-test',
        publishableKey: 'test_AhIaXMucdYCKah7boupv0BdwxrB3ljcdSk'
    });

    // 2. Load components module
    cbInstance.load("components").then(() => {

        // 3. Create the card component
        const cardComponent = cbInstance.createComponent("card", {
            gateway_account_id: "gw_Azqb55UtBKcr0Cks",
            placeholder: {
                number: "Number",
                expiry: "Expiry",
                cvv: "CVV"
            },
            classes: {
                focus: "focus",
                invalid: "invalid",
                empty: "empty",
                complete: "complete"
            },
            style: {
                base: {
                    backgroundColor: "#2c2f36",
                    border: "1px solid #555",
                    borderRadius: "4px",
                    color: "#ffffff",
                    fontFamily: "Lato, BlinkMacSystemFont, Segoe UI, sans-serif",
                    fontSize: "16px",
                    fontSmoothing: "antialiased",
                    "::placeholder": { color: "#bbbbbb" },
                    ":focus": { borderColor: "#888" },
                    ":focus::placeholder": { color: "#dddddd" }
                },
                empty: {
                    "::placeholder": { color: "#888888" }
                },
                invalid: {
                    borderColor: "#e41029",
                    color: "#ff6b6b",
                    "::placeholder": { color: "#ff9e9e" },
                    ":focus": { borderColor: "#ff4c4c" }
                }
            },
            fonts: ["https://fonts.googleapis.com/css?family=Lato:400,700"]
        });

        // 4. Mount card UI
        cardComponent.mount('#card-component');

        // 5. Form submission handling
        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('pay-button');

        form.addEventListener('submit', function (event) {
            event.preventDefault(); // Prevent default submit

            clearErrors();
            const isValid = fieldsValidator();

            if (!isValid) {
                setTimeout(() => {
                    submitButton.disabled = false; // ✅ Re-enable if validation fails
                    submitButton.innerText = 'Proceed to checkout';
                }, 1000);
                return; // Stop if validation failed
            }

            submitButton.disabled = true;
            submitButton.innerText = 'Processing...';

            // Tokenize payment details
            cbInstance.tokenize(cardComponent)
                .then(data => {
                    if (data && data.token) {
                        createSubscription(
                        data.token,
                        data.vaultToken,
                        
                        );
                    } else {
                        throw new Error("Token not generated");
                    }
                })
                .catch(error => {
                    console.error("Tokenization failed:", error);
                    submitButton.disabled = false;
                    submitButton.innerText = 'Pay Now';
                    showErrors(["Payment failed: " + (error.message || "Unknown error")]);
                });
        });

        // 6. Field validation function
        function fieldsValidator() {
            const email = document.getElementById("billingEmail");
            const first_name = document.getElementById("billingFirstName");
            const last_name = document.getElementById("billingLastName");
            const address_line1 = document.getElementById("billingAddress1");
            const city = document.getElementById("billingCity");
            const zip = document.getElementById("billingZip");
            const state = document.getElementById("billingState");
            const country = document.getElementById("billingCountry");

            let errors = [];

            if (!email.value.trim()) errors.push("Email is required");
            else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) errors.push("Invalid email format");

            if (!first_name.value.trim()) errors.push("First name is required");
            if (!last_name.value.trim()) errors.push("Last name is required");
            if (!address_line1.value.trim()) errors.push("Address Line 1 is required");
            if (!city.value.trim()) errors.push("City is required");
            if (!zip.value.trim()) errors.push("ZIP Code is required");
            if (!state.value.trim()) errors.push("State is required");
            if (!country.value.trim()) errors.push("Country is required");

            if (errors.length > 0) {
                showErrors(errors);
                return false;
            }

            return true;
        }

        // 7. Show errors
        function showErrors(errors) {
            const errorDiv = document.getElementById("error-messages");
            errorDiv.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        ${errors.map(err => `<li>${err}</li>`).join("")}
                    </ul>
                </div>
            `;
        }

        function showSuccess(message){
            const successDiv = document.getElementById("error-messages");
            successDiv.innerHTML = `
                <div class="alert alert-success" role="alert">
                    ${message}
                </div>
            `;
        }

        // 8. Clear errors
        function clearErrors() {
            document.getElementById("error-messages").innerHTML = "";
        }

        // 9. Function to call backend to create subscription
function createSubscription(cbtoken, vaultToken) {
    fetch('/custom/checkout/subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            cbtoken: cbtoken,
            vaultToken: vaultToken,
            email: document.getElementById("billingEmail").value,
            first_name: document.getElementById("billingFirstName").value,
            last_name: document.getElementById("billingLastName").value,
            address_line1: document.getElementById("billingAddress1").value,
            city: document.getElementById("billingCity").value,
            zip: document.getElementById("billingZip").value,
            state: document.getElementById("billingState").value,
            country: document.getElementById("billingCountry").value,
            quantity: document.getElementById("qty").value
        })
    })
    .then(async res => {
        let data;
        try {
            data = await res.json();
        } catch {
            data = {};
        }

        if (!res.ok) {
            throw { status: res.status, body: data };
        }

        return data;
    })
    .then(data => {
        // ✅ Success flow
        clearErrors();
        submitButton.disabled = false;
        submitButton.innerText = 'Pay Now';

        console.log("✅ Subscription created:", data);

        // Show success message from API response if available
        if (data.message) {
            showSuccess([data.message]);
        } else {
            showSuccess(["Payment successful! Thank you for your purchase."]);
        }

        // Optionally redirect if your API gives a URL
        if (data.redirect_url) {
            
            setTimeout(() => {
                window.location.href = data.redirect_url;
            }, 3000);
        }

        // Reset form fields
        document.getElementById("billingEmail").value = '';
        document.getElementById("billingFirstName").value = '';
        document.getElementById("billingLastName").value = '';
        document.getElementById("billingAddress1").value = '';
        document.getElementById("billingCity").value = '';
        document.getElementById("billingZip").value = '';
        document.getElementById("billingState").value = '';
        document.getElementById("billingCountry").value = '';
        document.getElementById("qty").value = 1;
    })
    .catch(error => {
        console.log("❌ Error object:", error);

        submitButton.disabled = false;
        submitButton.innerText = 'Pay Now';

        if (error.status === 419) {
            showErrors(["Oops! Page has expired, please go to Discord to access the new discount link."]);
            setTimeout(() => {
                window.location.reload();
            }, 5000);
        } else if (error.status === 422) {
            if (error.body && error.body.errors) {
                showErrors(Object.values(error.body.errors).flat());
            } else {
                showErrors(["Validation failed"]);
            }
        } else {
            showErrors(["There was an error processing your payment. Please try again."]);
        }
    });
}


    });
});
</script>




<script>
    document.addEventListener("DOMContentLoaded", function() {
            const increaseBtn = document.getElementById("increase-btn");
            const decreaseBtn = document.getElementById("decrease-btn");
            const qtyInput = document.getElementById("qty");
            const unitPrice = document.getElementById('unit-price');

            const itemTotal = document.getElementById("item-total");
            const summaryUnitPrice = document.getElementById("summary-unit-price");
            const summarySubtotal = document.getElementById("summary-subtotal");
            const summaryTotal = document.getElementById("summary-total");
            const itemCount = document.getElementById("item-count");

            const cartLeft = document.getElementById("cart-left");
            const billingLeft = document.getElementById("billing-left");
            const reviewLeft = document.getElementById("review-left");

            const checkoutBtn = document.getElementById("checkoutBtn");
            const confirmBillingBtn = document.getElementById("confirmBillingBtn");
            const editAccountBtn = document.getElementById("editAccountBtn");

            function updatePrices(qty) {
                // Make sure PHP outputs a proper JS string
                const baseUrl = "{{ url('/custom/checkout/calculate') }}";
                const fullUrl = `${baseUrl}/${qty}`;

                console.log("Fetching URL:", fullUrl);

                fetch(fullUrl)
                    .then(response => response.json())
                    .then(data => {
                        console.log("Data received:", data);
                        if (data.success) {
                            const total = data.total_price;
                            qtyInput.value = qty;
                            itemCount.textContent = qty;
                            unitPrice.textContent = `$${data.price_per_qty.toFixed(2)}`;
                            itemTotal.textContent = `$${total.toFixed(2)}`;
                            summaryUnitPrice.textContent = total;
                            summarySubtotal.textContent = `$${total.toFixed(2)}`;
                            summaryTotal.textContent = `$${total.toFixed(2)}`;
                        } else {
                            console.error("Error calculating total:", data.message);
                        }
                    })
                    .catch(error => console.error("Fetch error:", error));
            }

            increaseBtn.addEventListener("click", () => {
                let qty = parseInt(qtyInput.value);
                qty++;
                updatePrices(qty);
            });

            decreaseBtn.addEventListener("click", () => {
                let qty = parseInt(qtyInput.value);
                if (qty > 1) {
                    qty--;
                    updatePrices(qty);
                }
            });
            
            qtyInput.addEventListener("change", () => {
                if (isNaN(qtyInput.value) || qtyInput.value < 1) {
                    qtyInput.value = 1;
                }

            if(qtyInput.value>0){
                updatePrices(parseInt(qtyInput.value));
            }
            });


            
            checkoutBtn.addEventListener("click", () => {
                cartLeft.classList.add("d-none");
                billingLeft.classList.add("d-none");
                reviewLeft.classList.remove("d-none");
            });

            confirmBillingBtn.addEventListener("click", (e) => {
                e.preventDefault();

                // Get form values
                const firstName = billingLeft.querySelector('[placeholder="First Name"]').value || '-';
                const email = billingLeft.querySelector('p strong:contains("Email")')?.nextSibling
                    ?.textContent?.trim() || 'example@email.com';

                // Fill review card
                document.getElementById("reviewFirstName").textContent = firstName;
                document.getElementById("reviewEmail").textContent = email;

                billingLeft.classList.add("d-none");
                reviewLeft.classList.remove("d-none");
            });

            editAccountBtn.addEventListener("click", (e) => {
                e.preventDefault();
                reviewLeft.classList.add("d-none");
                billingLeft.classList.remove("d-none");
            });
        });

        document.querySelectorAll('.toggle-edit').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const target = this.dataset.target;

            const fields = {
                account: ['FirstName', 'Email'],
                billing: ['BillingFirstName', 'BillingLastName', 'BillingAddress'],
                shipping: ['ShippingFirstName', 'ShippingLastName', 'ShippingAddress']
            };

            fields[target].forEach(id => {
                const span = document.getElementById('review' + id);
                const input = document.getElementById('input' + id);
                const isHidden = input.classList.contains('d-none');

                if (isHidden) {
                    input.classList.remove('d-none');
                    span.classList.add('d-none');
                    this.textContent = 'Save';
                } else {
                    span.textContent = input.value;
                    input.classList.add('d-none');
                    span.classList.remove('d-none');
                    this.textContent = 'Edit';
                }
            });
        });
    });

        
</script>



@endpush