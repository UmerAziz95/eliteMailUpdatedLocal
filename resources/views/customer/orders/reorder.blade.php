@extends('customer.layouts.app')

@section('title', 'Orders')

@push('styles')
<style>
    input,
    .form-control,
    textarea,
    .form-select {
        background-color: #1e1e1e !importa</style>
@endpush

@section('content')


<!-- Include Header -->
<section class="py-3 overflow-hidden">
    <div class="card mb-3 p-3">
        <h5>Credit Card</h5>
        <span class="opacity-50"><strong>VISA</strong> **** **** **** 4080 – Expires 2/2027</span>
        <div class="mt-3">
            <button class="c-btn"><i class="fa-solid fa-credit-card"></i> Change Card</button>
        </div>
    </div>

    <div class="card p-3">
        <h5 class="mb-4">Domains & hosting platform</h5>

        <div class="mb-3">
            <label for="forwarding">Domain forwarding destination URL *</label>
            <input type="text" id="forwarding" class="form-control"
                value="zlatin@expandacquisition.com" />
            <p class="note mb-0">(A link where you'd like to drive the traffic from the domains you
                send
                us –
                could be your main website, blog post, etc.)</p>
        </div>

        <div class="mb-3">
            <label for="hosting">Domain hosting platform *</label>
            <select id="hosting" class="form-control">
                <option selected>Namecheap</option>
            </select>
            <p class="note mb-0">(where your domains are hosted and can be accessed to modify the
                DNS
                settings)</p>
        </div>

        <div class="mb-3">
            <label for="tutorial">Domain Hosting Platform – Namecheap – Access Tutorial</label>
            <select id="tutorial" class="form-control">
                <option selected>
                    Yes – I reviewed the tutorial and am submitting the access information
                    in requested format.
                </option>
            </select>
            <p class="note mb-0">
                IMPORTANT – please follow the steps from this document to grant us access to your
                Namecheap account:
                <a href="#" class="highlight-link">Namecheap Access Tutorial Link</a><br>
                For Domain Hosting Login (Namecheap) please enter your username, NOT email.
            </p>
        </div>

        <div class="mb-3">
            <label for="backup-codes">Domain Hosting Platform – Namecheap – Backup Codes *</label>
            <textarea id="backup-codes" class="form-control" rows="8"></textarea>
        </div>

        <div class="mb-3">
            <label for="backup-codes">Domain Hosting Platform – Namecheap – Backup Codes *</label>
            <textarea id="backup-codes" class="form-control" rows="8"></textarea>
        </div>

        <div class="row">
            <div class="col-6">
                <label for="backup-codes">Domain Hosting Platform – Login *</label>
                <input type="text" id="forwarding" class="form-control"
                    value="zlatin@expandacquisition.com" />
            </div>

            <div class="col-6">
                <label for="backup-codes">Domain Hosting Platform – Password *</label>
                <input type="text" id="forwarding" class="form-control"
                    value="zlatin@expandacquisition.com" />
            </div>
        </div>

        <div class="mb-3">
            <label for="backup-codes">Domains *</label>
            <textarea id="backup-codes" class="form-control" rows="8"></textarea>
        </div>

        <div class="row g-3 mt-4">

            <h5 class="mb-2">Sending Platforms/ Sequencer</h5>

            <div class="col-md-12">
                <label>Sending Platform</label>
                <select class="form-control">
                    <option>Instantly</option>
                </select>
                <p class="note">(We upload and configure the email accounts for you - its a software
                    you use to send emails)</p>
            </div>

            <div class="col-md-6">
                <label>Sequencer Login</label>
                <input type="email" class="form-control" value="venkat.viswanathan2000@yahoo.com">
            </div>

            <div class="col-md-6">
                <label>Sequencer Password</label>
                <input type="password" class="form-control" value="Joy4Jesus">
            </div>

            <h5 class="mb-2 mt-5">Email Account Information</h5>

            <div class="col-md-6">
                <label>Total Inboxes</label>
                <input type="number" class="form-control" value="324">
                <p class="note">(How many email accounts you are requesting with this submission)
                </p>
            </div>

            <div class="col-md-6">
                <label>Inboxes per Domain</label>
                <select class="form-control">
                    <option selected>-- Select --</option>
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                </select>
                <p class="note">(How many email accounts per domain - the maximum is 3)</p>
            </div>

            <div class="col-md-6">
                <label>First Name</label>
                <input type="text" class="form-control" value="Venkat">
         git        <p class="note">(First name that you wish to use on the inbox profile)</p>
            </div>

            <div class="col-md-6">
                <label>Last Name</label>
                <input type="text" class="form-control" value="Viswanathan">
                <p class="note">(Last name that you wish to use on the inbox profile)</p>
            </div>

            <div class="col-md-6">
                <label>Prefix Variant 1</label>
                <input type="text" class="form-control" value="venkat">
            </div>

            <div class="col-md-6">
                <label>Prefix Variant 2</label>
                <input type="text" class="form-control" value="venkat.viswanathan">
            </div>

            <div class="col-md-6">
                <label>Persona Password</label>
                <input type="password" class="form-control" value="Joy4Jesus">
            </div>

            <div class="col-md-6">
                <label>Profile Picture Link</label>
                <input type="url" class="form-control"
                    value="https://drive.google.com/file/d/148yvyXqit0XxNS1foFALosulbeEHO-a-G/view?usp=sharing">
            </div>

            <div class="col-md-6">
                <label>Email Persona - Password</label>
                <input type="password" class="form-control" value="Joy4Jesus">
            </div>

            <div class="col-md-6">
                <label>Email Persona - Profile Picture Link</label>
                <input type="url" class="form-control"
                    value="https://drive.google.com/file/d/148yvyXqit0XxNS1foFALosulbeEHO-a-G/view?usp=sharing">
            </div>

            <div class="col-md-6">
                <label>Centralized master inbox email</label>
                <input type="text" class="form-control" value="Viswanathan">
                <p class="note">(This is optional - if you want to forward all email inboxes to a
                    specific email, enter above, otherwise leave as "optional")</p>
            </div>

            <h5 class="mb-2 mt-4">Additional Assets</h5>

            <div class="mb-3">
                <label for="backup-codes">Additional Information / Context *</label>
                <textarea id="backup-codes" class="form-control" rows="8"></textarea>
            </div>

            <div class="col-md-6">
                <label>Coupen Code</label>
                <input type="text" class="form-control" value="Viswanathan">
            </div>

            <div class="d-flex align-items-center gap-3 ">
                <div>
                    <img src="https://cdn-icons-png.flaticon.com/128/300/300221.png"
                        width="30" alt="">
                </div>
                <div>
                    <span class="opacity-50">Officially Google Workspace Inboxes</span>
                    <br>
                    <span>324 x $3.00 <small>/monthly</small> </span>
                </div>
            </div>

            <div>
                <h6><span class="theme-text">Original Price:</span> $972.00</h6>
                <h6><span class="theme-text">Discount:</span> 0%</h6>
                <h6><span class="theme-text">Total:</span> $972.00</h6>
            </div>

            <div>
                <button class="m-btn py-1 px-3 rounded-2 border-0">
                    <i class="fa-solid fa-cart-shopping"></i>
                    Purchase Accounts
                </button>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')

me-text:contains("Total:")').parent().html(totalHtml);
        
        // Update form's plan_id if there's a suitable plan
        if (suitablePlan) {
            $('input[name="plan_id"]').val(suitablePlan.id);
        }
    }

    // Calculate total inboxes whenever domains or inboxes per domain changes
    $('#domains, #inboxes_per_domain').on('input change', calculateTotalInboxes);

    // Initial calculation
    calculateTotalInboxes();
});
</script>
@endpush