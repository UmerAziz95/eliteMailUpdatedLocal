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


<div class="container">
    <a href="#" class="text-purple text-decoration-none small">&lt; Back to site</a>
    <h2 class="mt-4 fs-5">Checkout</h2>

    <div class="row g-4">
        <!-- LEFT SIDE -->
        <div class="col-lg-8">
            <!-- Step 1: Cart -->
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
                                    <strong id="unit-price" class="me-2">$3.50 <small
                                            class="fw-light">X</small></strong>
                                    <button class="btn btn-primary rounded-0" type="button" id="decrease-btn">−</button>
                                    <input type="number" style="max-width: 70px; border: 1px solid #ffffff36;" id="qty"
                                        class="px-2 bg-transparent rounded-0" value="1">
                                    <button class="btn btn-primary rounded-0" type="button" id="increase-btn">+</button>
                                </div>
                                <strong id="item-total">$3.50</strong>
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
                            <input type="text" class="form-control" placeholder="First Name">
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" placeholder="Last Name">
                        </div>
                    </div>

                    <div class="mb-3">
                        <input type="text" class="form-control" placeholder="Address Line 1">
                    </div>

                    <div class="mb-3">
                        <input type="text" class="form-control" placeholder="Address Line 2 (optional)">
                    </div>

                    <div class="row mb-3">
                        <div class="col">
                            <input type="text" class="form-control" placeholder="City">
                        </div>
                        <div class="col">
                            <input type="text" class="form-control" placeholder="ZIP (optional)">
                        </div>
                    </div>

                    <div class="mb-3">
                        <select class="form-select">
                            <option selected>Pick a State</option>
                            <option>California</option>
                            <option>Texas</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <select class="form-select">
                            <option selected>United States</option>
                            <option>Canada</option>
                        </select>
                    </div>

                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" id="sameAddress">
                        <label class="form-check-label" for="sameAddress">
                            Shipping address is the same as my billing address
                        </label>
                    </div>
                </form>

                <div class="card card-custom p-4 mt-4">
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
                </div>

            </div>

            <!-- Step 3: Review Card -->
            <div id="review-left" class="">
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
            </div>



        </div>



        <!-- RIGHT SIDE -->
        <div class="col-lg-4">
            <div class="card card-custom p-4">
                <h5 class="mb-3">Order summary</h5>
                <div class="d-flex justify-content-between small mb-1">
                    <span>Google Workspace Package</span>
                    <span id="summary-unit-price">$3.50</span>
                </div>
                <div class="d-flex justify-content-between small fw-semibold mb-3">
                    <span>Subtotal (<span id="item-count">1</span> item)</span>
                    <span id="summary-subtotal">$3.50</span>
                </div>
                <hr class="border-secondary" />
                <div class="d-flex justify-content-between fw-bold mb-3">
                    <span>Total</span>
                    <span id="summary-total">$3.50</span>
                </div>
                <p class="small mb-4">
                    Next charge on <span class="text-white fw-semibold">August 15, 2025</span><br>
                    <a href="#" class="theme-text text-decoration-underline">Future charges</a>
                </p>
                <button id="checkoutBtn" class="btn btn-primary w-100 fw-semibold">Proceed To Checkout</button>

                <p class="text-center secure-note mt-3">
                    🔒 Secure Checkout by <strong>Chargebee</strong>
                </p>
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

            <button type="submit" id="pay-button" class="btn btn-primary w-100 mt-3">Pay Now</button>

            <p class="text-center secure-note mt-3">
                🔒 Secure Checkout by <strong>Chargebee</strong>
            </p>
        </form>


    </div>
</div>
@endsection


@push('scripts')


<script src="https://js.chargebee.com/v2/chargebee.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
  // 1. Initialize and store Chargebee instance
  const cbInstance = Chargebee.init({
    site:'projectinbox-test',
    publishableKey:'test_AhIaXMucdYCKah7boupv0BdwxrB3ljcdSk'
  });

  // 2. Load components module
  cbInstance.load("components").then(() => {
    // 3. Create the card component
    const cardComponent = cbInstance.createComponent("card", {
      placeholder: { number: "Number", expiry: "Expiry", cvv: "CVV" },
      classes: { focus: "focus", invalid: "invalid", empty: "empty", complete: "complete" },
style: {
  base: {
    backgroundColor: "#2c2f36 !important",        // Light inner panel contrast
    border: "1px solid #555 !important",
    borderRadius: "4px",
    color: "#ffffff !important",                  // Input text color
    fontFamily: "Lato, BlinkMacSystemFont, Segoe UI, sans-serif",
    fontSize: "16px",
    fontSmoothing: "antialiased",
    "::placeholder": { color: "#bbbbbb" },       // Placeholder color
    ":focus": { borderColor: "#888" },            // Focus border highlight
    ":focus::placeholder": { color: "#dddddd" }
  },
  empty: {
    "::placeholder": { color: "#888888" }         // Muted placeholder when empty
  },
  invalid: {
    borderColor: "#e41029",
    color: "#ff6b6b",                             // Input text for invalid state
    "::placeholder": { color: "#ff9e9e" },
    ":focus": { borderColor: "#ff4c4c" }
  }
},
      fonts: ["https://fonts.googleapis.com/css?family=Lato:400,700"],
    });

    // Mount card UI
    cardComponent.mount('#card-component');

    // 4. Set up form submission handling
    document.getElementById('payment-form').addEventListener('submit', function(event) {
      event.preventDefault();
      const submitButton = document.getElementById('pay-button');
      submitButton.disabled = true;
      submitButton.innerText = 'Processing...';

      // Use cbInstance.tokenize() with the cardComponent
      cbInstance.tokenize(cardComponent /*, optional extra data */)
        .then(data => {
          console.log("Tokenization successful:", data);
          if (data.token) createSubscription(data.token);
        })
        .catch(error => {
          console.error("Tokenization failed:", error);
          submitButton.disabled = false;
          submitButton.innerText = 'Pay Now';
          alert("Payment failed: " + (error.message || "Unknown error"));
        });
    });

    // Function to call your backend to create subscription
   function createSubscription(cbtoken) {
  fetch('/custom/checkout/subscribe', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    },
    body: JSON.stringify({ cbtoken: cbtoken })
  })
  .then(res => res.json())
  .then(data => {
    console.log('Subscription created:', data);
    //window.location.href = '/thank-you';
  })
  .catch(error => {
    console.error('Error creating subscription:', error);
    const button = document.getElementById('pay-button');
    button.disabled = false;
    button.innerText = 'Pay Now';
    alert('There was an error processing your payment. Please try again.');
  });
}

  });
});
</script>





@endpush

{{-- <script>
    document.addEventListener("DOMContentLoaded", function() {
            const increaseBtn = document.getElementById("increase-btn");
            const decreaseBtn = document.getElementById("decrease-btn");
            const qtyInput = document.getElementById("qty");
            const unitPrice = 3.50;

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
                const total = (unitPrice * qty).toFixed(2);
                qtyInput.value = qty;
                itemCount.textContent = qty;
                itemTotal.textContent = `$${total}`;
                summaryUnitPrice.textContent = `$${unitPrice.toFixed(2)} x ${qty}`;
                summarySubtotal.textContent = `$${total}`;
                summaryTotal.textContent = `$${total}`;
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

            updatePrices(parseInt(qtyInput.value));

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

        
</script> --}}