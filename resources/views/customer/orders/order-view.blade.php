@extends('customer.layouts.app')

@section('title', 'Orders')

@section('content')
<section class="py-3 overflow-hidden">
    <div class="d-flex align-items-center justify-content-between">
        <a href="{{ route('customer.orders') }}" class="d-flex align-items-center justify-content-center"
            style="height: 30px; width: 30px; border-radius: 50px; background-color: #525252c6;">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
        <button class="c-btn text-decoration-none subscribe-btn">
            <i class="fa-solid fa-cart-plus"></i>
            Re-order
        </button>
        <!-- <a href="{{ route('customer.orders.reorder', ['order_id' => $order->id]) }}" class="c-btn text-decoration-none">
            <i class="fa-solid fa-cart-plus"></i>
            Re-order
        </a> -->
    </div>

    @php
    $defaultImage = 'https://cdn-icons-png.flaticon.com/128/300/300221.png';
    $defaultProfilePic = 'https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600';
    @endphp

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div>
            <h5 class="mb-3">Order #{{ $order->id ?? 'N/A' }}</h5>
            <h6><span class="opacity-50 fs-6">Order Date:</span> {{ $order->created_at ? $order->created_at->format('M d, Y') : 'N/A' }}</h6>
        </div>
        <div class="d-flex gap-2">
            <div class="border border-{{ $order->color_status2 }} rounded-2 py-1 px-2 text-{{ $order->color_status2 }} bg-transparent">
                {{ ucfirst($order->status2 ?? 'Pending') }}
            </div>
            <div id="exportCsvBtn" class="border rounded-2 py-1 text-white bg-transparent text-center" style="width: 120px">
                <a href="javascript:;">Bulk Export</a>            
            </div>
        </div>
    </div>

    <ul class="nav nav-tabs order_view d-flex align-items-center justify-content-between" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link fs-6 px-5 active" id="configuration-tab" data-bs-toggle="tab"
                data-bs-target="#configuration-tab-pane" type="button" role="tab"
                aria-controls="configuration-tab-pane" aria-selected="true">Configuration</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fs-6 px-5" id="email-tab" data-bs-toggle="tab"
                data-bs-target="#email-tab-pane" type="button" role="tab"
                aria-controls="email-tab-pane" aria-selected="false">Emails</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fs-6 px-5" id="subscription-tab" data-bs-toggle="tab"
                data-bs-target="#subscription-tab-pane" type="button" role="tab"
                aria-controls="subscription-tab-pane" aria-selected="false">Subscription</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fs-6 px-5" id="tickets-tab" data-bs-toggle="tab"
                data-bs-target="#tickets-tab-pane" type="button" role="tab"
                aria-controls="tickets-tab-pane" aria-selected="false">Tickets</button>
        </li>
    </ul>

    <div class="tab-content mt-3" id="myTabContent">
        <div class="tab-pane fade show active" id="configuration-tab-pane" role="tabpanel"
            aria-labelledby="configuration-tab" tabindex="0">
            <div class="row">
                <div class="col-md-6">
                    <div class="card p-3 mb-3">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-regular fa-envelope"></i>
                            </div>
                            Email configurations
                        </h6>

                        @if(optional($order->reorderInfo)->count() > 0)
                        <div class="d-flex align-items-center justify-content-between">
                            @php
                                $domains = $order->reorderInfo->first()->domains ?? '';
                                $inboxesPerDomain = $order->reorderInfo->first()->inboxes_per_domain ?? 1;
                                
                                // Parse domains and count them
                                $domainsArray = [];
                                $lines = preg_split('/\r\n|\r|\n/', $domains);
                                foreach ($lines as $line) {
                                    if (trim($line)) {
                                        $lineItems = explode(',', $line);
                                        foreach ($lineItems as $item) {
                                            if (trim($item)) {
                                                $domainsArray[] = trim($item);
                                            }
                                        }
                                    }
                                }
                                
                                $totalDomains = count($domainsArray);
                                $calculatedTotalInboxes = $totalDomains * $inboxesPerDomain;
                            @endphp
                            <span>Total Inboxes <br> {{ $calculatedTotalInboxes }} ({{ $totalDomains }} domains × {{ $inboxesPerDomain }})</span>
                            <span>Inboxes per domain <br> {{ $order->reorderInfo->first()->inboxes_per_domain ?? '0' }}</span>
                        </div>
                        <hr>
                        <div class="d-flex flex-column">
                            <span class="opacity-50">Prefix Variants</span>
                            @php
                                // Check if new prefix_variants JSON column exists and has data
                                $prefixVariants = $order->reorderInfo->first()->prefix_variants ?? [];
                                $inboxesPerDomain = $order->reorderInfo->first()->inboxes_per_domain ?? 1;
                                
                                // If new format doesn't exist, fallback to old individual fields
                                if (empty($prefixVariants)) {
                                    $prefixVariants = [];
                                    if ($order->reorderInfo->first()->prefix_variant_1) {
                                        $prefixVariants['prefix_variant_1'] = $order->reorderInfo->first()->prefix_variant_1;
                                    }
                                    if ($order->reorderInfo->first()->prefix_variant_2) {
                                        $prefixVariants['prefix_variant_2'] = $order->reorderInfo->first()->prefix_variant_2;
                                    }
                                }
                            @endphp
                            
                            @for($i = 1; $i <= $inboxesPerDomain; $i++)
                                @php
                                    $variantKey = "prefix_variant_$i";
                                    $variantValue = $prefixVariants[$variantKey] ?? 'N/A';
                                @endphp
                                <span>Variant {{ $i }}: {{ $variantValue }}</span>
                            @endfor
                        </div>
                        
                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50">Profile Picture URLs</span>

                            @if($order->reorderInfo->first()->prefix_variants_details)
                                @foreach($order->reorderInfo->first()->prefix_variants_details as $key => $variant)
                                    <div class="mt-1">
                                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                        @if(!empty($variant['profile_link']))
                                            <a href="{{ $variant['profile_link'] }}" target="_blank">{{ $variant['profile_link'] }}</a>
                                        @else
                                            <span>N/A</span>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <span>N/A</span>
                            @endif
                        </div>

                        <!-- <div class="d-flex flex-column mt-3" style="display:none;">
                            <span class="opacity-50">Email Persona Password</span>
                            <span>{{ $order->reorderInfo->first()->email_persona_password ?? 'N/A' }}</span>
                        </div> -->
                        <div class="d-flex flex-column mt-3">
                            <span class="opacity-50">Master Inbox Email</span>
                            <span>{{ $order->reorderInfo->first()->master_inbox_email ?? 'N/A' }}</span>
                        </div>
                        @else
                        <div class="opacity-50">No email configuration data available</div>
                        @endif
                    </div>

                    <div class="card p-3">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-cart-plus"></i>
                            </div>
                            Products
                        </h6>

                        <div class="price-display-section">
                            @if(isset($order->plan) && $order->plan)
                                @php
                                    $totalInboxes = optional(optional($order)->reorderInfo)->count() > 0 ? $order->reorderInfo->first()->total_inboxes : 0;
                                    $originalPrice = $order->plan->price * $totalInboxes;
                                @endphp
                                <div class="d-flex align-items-center gap-3">
                                    <div>
                                        <img src="{{ $defaultImage }}" width="30" alt="Product Icon">
                                    </div>
                                    <div>
                                        <span class="opacity-50">Officially Google Workspace Inboxes</span>
                                        <br>
                                        <span>({{ $totalInboxes }} x ${{ number_format($order->plan->price, 2) }} <small>/{{ $order->plan->duration }})</small></span>
                                    </div>
                                </div>
                                <h6><span class="theme-text small">Original Price:</span> ${{ number_format($originalPrice, 2) }}</h6>
                                <!-- <h6><span class="theme-text small">Discount:</span> 0%</h6> -->
                                <h6><span class="theme-text small">Total:</span> ${{ number_format($originalPrice, 2) }} <small>/{{ $order->plan->duration }}</small></h6>
                            @else
                                <h6><span class="theme-text small">Original Price:</span> <small>Select a plan to view price</small></h6>
                                <h6><span class="theme-text small">Total:</span> <small>Select a plan to view total</small></h6>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card p-3 overflow-y-auto" style="max-height: 45rem">
                        <h6 class="d-flex align-items-center gap-2">
                            <div class="d-flex align-items-center justify-content-center"
                                style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                                <i class="fa-solid fa-earth-europe"></i>
                            </div>
                            Domains & Configuration
                        </h6>

                        @if(optional($order->reorderInfo)->count() > 0)
                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Hosting Platform</span>
                            <span>{{ $order->reorderInfo->first()->hosting_platform }}</span>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Platform Login</span>
                            <span>{{ $order->reorderInfo->first()->platform_login }}</span>
                        </div>
                        <!-- platform_password -->
                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Platform Password</span>
                            <span>{{ $order->reorderInfo->first()->platform_password }}</span>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Domain Forwarding Destination URL</span>
                            <span>{{ $order->reorderInfo->first()->forwarding_url }}</span>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Sending Platform</span>
                            <span>{{ $order->reorderInfo->first()->sending_platform }}</span>
                        </div>

                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Cold Email Platform - Login </span>
                            <span>{{ $order->reorderInfo->first()->sequencer_login }}</span>
                        </div>
                        <!-- Cold Email Platform - Password  -->
                        <div class="d-flex flex-column mb-3">
                            <span class="opacity-50">Cold Email Platform - Password </span>
                            <span>{{ $order->reorderInfo->first()->sequencer_password }}</span>
                        </div>

                        <div class="d-flex flex-column">
                            <span class="opacity-50">Domains</span>
                            @php
                                // Get the domains string from the order
                                $domainsString = $order->reorderInfo->first()->domains;
                                
                                // Split by both newlines and commas
                                $domainsArray = [];
                                
                                // First split by newlines
                                $lines = preg_split('/\r\n|\r|\n/', $domainsString);
                                
                                // Then process each line
                                foreach ($lines as $line) {
                                    if (trim($line)) {
                                        // Split line by commas and add to domains array
                                        $lineItems = explode(',', $line);
                                        foreach ($lineItems as $item) {
                                            if (trim($item)) {
                                                $domainsArray[] = trim($item);
                                            }
                                        }
                                    }
                                }
                            @endphp

                            @foreach ($domainsArray as $domain)
                                <span class="d-block">{{ $domain }}</span>
                            @endforeach
                        </div>

                        @if($order->reorderInfo->first()->hosting_platform == 'namecheap')
                        <div class="d-flex flex-column mb-3 mt-3">
                            <span class="opacity-50">Backup Codes</span>
                            @php
                            $backupCodes = explode(',', $order->reorderInfo->first()->backup_codes);
                            @endphp
                            @foreach($backupCodes as $backupCode)
                            <span>{{ trim($backupCode) }}</span>
                            @endforeach
                        </div>
                        @endif
                        @else
                        <div class="opacity-50">No configuration information available</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="email-tab-pane" role="tabpanel" aria-labelledby="email-tab"
            tabindex="0">
            <div class="col-12">
                <div class="card p-3">
                    <h6 class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center"
                            style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                            <i class="fa-solid fa-earth-europe"></i>
                        </div>
                        Emails
                    </h6>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-3" style="display: none;">
                            <div style="display: none;">
                                <button id="addNewBtn" class="btn btn-primary me-2" style="display: none;">
                                    <i class="fa-solid fa-plus me-1"></i> Add Email
                                </button>
                                <button id="saveAllBtn" class="btn btn-success" style="display: none;">
                                    <i class="fa-solid fa-floppy-disk me-1"></i> Save All
                                </button>
                            </div>
                        </div>
                        <div class="email-stats d-flex align-items-center gap-3 bg- rounded p-2">
                            <div class="badge rounded-circle bg-primary p-2">
                                <i class="fa-solid fa-envelope text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Email Accounts</h6>
                                <div class="d-flex align-items-center gap-2">
                                    <span id="totalRowCount" class="fw-bold">0</span>
                                    <div class="progress" style="width: 100px; height: 6px;">
                                        <div class="progress-bar bg-primary" id="emailProgressBar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="email-configuration" class="display w-100">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Password</th>
                                    <!-- <th>Action</th> -->
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>

                    @push('scripts')
                    <script>
                        $(document).ready(function() {
                            // Get the total_inboxes from order configuration
                            // const totalInboxes = {{ $order->plan && $order->plan->max_inbox ? $order->plan->max_inbox : 0 }};
                            const totalInboxes = {{ optional($order->reorderInfo->first())->total_inboxes ?? 0 }};
                            const maxEmails = totalInboxes || 0; // If totalInboxes is 0, allow unlimited emails
                            
                            // Function declarations first
                            function updateRowCount(table) {
                                const rowCount = table.rows().count();
                                if (maxEmails > 0) {
                                    $('#totalRowCount').text(rowCount + "/" + totalInboxes);
                                } else {
                                    $('#totalRowCount').text(rowCount + " (Unlimited)");
                                }
                                updateProgressBar(table);
                            }

                            function updateAddButtonState(table) {
                                const rowCount = table.rows().count();
                                const addButton = $('#addNewBtn');
                                
                                if (maxEmails > 0 && rowCount >= maxEmails) {
                                    addButton.prop('disabled', true);
                                    addButton.attr('title', `Maximum limit of ${maxEmails} emails reached`);
                                } else {
                                    addButton.prop('disabled', false);
                                    addButton.removeAttr('title');
                                }
                            }

                            function updateProgressBar(table) {
                                const rowCount = table.rows().count();
                                let percentage = 0;
                                
                                if (maxEmails > 0) {
                                    percentage = Math.min((rowCount / maxEmails) * 100, 100);
                                } else {
                                    // For unlimited inboxes, use a different logic
                                    // Show progress based on number of emails (e.g., every 10 emails is 10% until 100)
                                    percentage = Math.min((rowCount / 100) * 100, 100);
                                }
                                
                                const progressBar = $('#emailProgressBar');
                                progressBar.css('width', percentage + '%')
                                         .attr('aria-valuenow', percentage);
                                
                                progressBar.removeClass('bg-primary bg-warning bg-danger');
                                if (maxEmails > 0) {
                                    // For limited inboxes
                                    if (percentage >= 90) {
                                        progressBar.addClass('bg-danger');
                                    } else if (percentage >= 70) {
                                        progressBar.addClass('bg-warning');
                                    } else {
                                        progressBar.addClass('bg-primary');
                                    }
                                } else {
                                    // For unlimited inboxes, always show primary color
                                    progressBar.addClass('bg-primary');
                                }
                            }

                            // Initialize DataTable
                            let emailTable = $('#email-configuration').DataTable({
                                responsive: true,
                                paging: true,
                                searching: false,
                                info: false,
                                dom: 'frtip',
                                autoWidth: false,
                                columnDefs: [
                                    { width: '33%', targets: 0 }, // Name column
                                    { width: '33%', targets: 1 }, // Email column
                                    { width: '33%', targets: 2 }, // Password column
                                    // { width: '10%', targets: 3 }  // Action column
                                ],
                                responsive: {
                                    details: {
                                        display: $.fn.dataTable.Responsive.display.modal({
                                            header: function(row) {
                                                return 'Email Details';
                                            }
                                        }),
                                        renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                                    }
                                },
                                ajax: {
                                    url: '/customer/orders/{{ $order->id }}/emails',
                                    dataSrc: function(json) {
                                        return json.data || [];
                                    }
                                },
                                columns: [
                                    { 
                                        data: 'name',
                                        render: function(data, type, row) {
                                            return data || '';
                                            // return `<input type="text" class="form-control name" value="${data || ''}" placeholder="Enter name">`;
                                        }
                                    },
                                    { 
                                        data: 'email',
                                        render: function(data, type, row) {
                                            return data || '';
                                            // return `<input type="email" class="form-control email" value="${data || ''}" placeholder="Enter email">`;
                                        }
                                    },
                                    { 
                                        data: 'password',
                                        render: function(data, type, row) {
                                            return data || '';
                                            // return `<input type="password" class="form-control password" value="${data || ''}" placeholder="Enter password">`;
                                        }
                                    }
                                    // ,
                                    // {
                                    //     data: 'id',
                                    //     render: function(data, type, row) {
                                    //         return `<button class="bg-transparent p-0 border-0 deleteEmailBtn" data-id="${data || ''}"><i class="fa-regular fa-trash-can text-danger"></i></button>`;
                                    //     }
                                    // }
                                ],
                                drawCallback: function(settings) {
                                    updateRowCount(this.api());
                                    updateAddButtonState(this.api());
                                }
                            });

                            // Event listeners
                            emailTable.on('draw', function() {
                                updateRowCount(emailTable);
                                updateAddButtonState(emailTable);
                                updateProgressBar(emailTable);
                            });

                            // Add new row button click handler
                            $('#addNewBtn').click(function() {
                                const rowCount = emailTable.rows().count();
                                if (maxEmails > 0 && rowCount >= maxEmails) {
                                    toastr.error(`You can only add up to ${maxEmails} email accounts as per your order configuration.`);
                                    return;
                                }

                                emailTable.row.add({
                                    name: '',
                                    email: '',
                                    password: '',
                                    id: ''
                                }).draw(false);
                            });

                            // Save all button click handler
                            $('#saveAllBtn').click(function() {
                                const emailsToSave = [];
                                let isValid = true;

                                $(emailTable.rows().nodes()).each(function() {
                                    const row = $(this);
                                    const nameField = row.find('.name');
                                    const emailField = row.find('.email');
                                    const passwordField = row.find('.password');

                                    // Reset validation classes
                                    nameField.removeClass('is-invalid');
                                    emailField.removeClass('is-invalid');
                                    passwordField.removeClass('is-invalid');

                                    const name = nameField.val()?.trim();
                                    const email = emailField.val()?.trim();
                                    const password = passwordField.val()?.trim();

                                    // Validate fields
                                    if (!name) {
                                        nameField.addClass('is-invalid');
                                        isValid = false;
                                    }
                                    if (!email) {
                                        emailField.addClass('is-invalid');
                                        isValid = false;
                                    }
                                    if (!password) {
                                        passwordField.addClass('is-invalid');
                                        isValid = false;
                                    }

                                    if (name && email && password) {
                                        emailsToSave.push({ name, email, password });
                                    }
                                });

                                if (!isValid) {
                                    toastr.error('Please fill in all required fields');
                                    return;
                                }

                                $.ajax({
                                    url: '/customer/orders/emails',
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    data: {
                                        _token: '{{ csrf_token() }}',
                                        order_id: '{{ $order->id }}',
                                        emails: emailsToSave
                                    },
                                    success: function(response) {
                                        toastr.success('Emails saved successfully');
                                        emailTable.ajax.reload();
                                    },
                                    error: function(xhr) {
                                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                                            // Loop through each error and mark fields as invalid
                                            Object.keys(xhr.responseJSON.errors).forEach(function(key) {
                                                // Handle array fields like emails.0.email
                                                if (key.includes('emails.')) {
                                                    const parts = key.split('.');
                                                    const index = parseInt(parts[1]); // Get the row index as integer
                                                    const field = parts[2]; // Get the field name (email, password, etc.)
                                                    const errorMsg = xhr.responseJSON.errors[key][0]; // Get the first error message
                                                    
                                                    // Find the input field at the specific row
                                                    const row = $(emailTable.rows().nodes()).eq(index);
                                                    const input = row.find(`.${field}`);
                                                    
                                                    if (input.length) {
                                                        input.addClass('is-invalid');
                                                        
                                                        // Add tooltip or display error message
                                                        input.attr('title', errorMsg);
                                                        
                                                        // Optionally create/update feedback element
                                                        let feedback = input.next('.invalid-feedback');
                                                        if (!feedback.length) {
                                                            input.after(`<div class="invalid-feedback">${errorMsg}</div>`);
                                                        } else {
                                                            feedback.text(errorMsg);
                                                        }
                                                    }
                                                }
                                            });
                                            toastr.error('Please correct the errors in the form');
                                        } else {
                                            toastr.error(xhr.responseJSON?.message || 'Error saving emails');
                                        }
                                    }
                                });
                            });

                            // Delete button click handler
                            $('#email-configuration tbody').on('click', '.deleteEmailBtn', function() {
                                const button = $(this);
                                const row = button.closest('tr');
                                const id = button.data('id');
                                
                                if (id) {
                                    // Delete existing record
                                    $.ajax({
                                        url: `/customer/orders/emails/${id}`,
                                        method: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                        },
                                        success: function() {
                                            toastr.success('Email deleted successfully');
                                            // Remove just the deleted row instead of reloading the entire table
                                            emailTable.row(row).remove().draw(false);
                                            updateRowCount(emailTable);
                                            updateAddButtonState(emailTable);
                                        },
                                        error: function(xhr) {
                                            toastr.error(xhr.responseJSON?.message || 'Error deleting email');
                                        }
                                    });
                                } else {
                                    // Remove unsaved row and redraw the table
                                    emailTable.row(row).remove().draw(false);
                                    updateRowCount(emailTable);
                                    updateAddButtonState(emailTable);
                                }
                            });

                            // ...existing code for delete button and other functionality...
                        });
                    </script>
                    @endpush
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="subscription-tab-pane" role="tabpanel"
            aria-labelledby="subscription-tab" tabindex="0">
            @if($order->subscription)
            <div class="card p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center"
                            style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                            <i class="fa-solid fa-cart-plus"></i>
                        </div>
                        Subscriptions
                    </h6>
                    <button class="py-1 px-2 text-{{ $order->subscription->status == 'active' ? 'success' : 'danger' }} rounded-2 border border-{{ $order->subscription->status == 'active' ? 'success' : 'danger' }} bg-transparent">
                        {{ ucfirst($order->subscription->status) }}
                    </button>
                </div>

                @if(isset($nextBillingInfo['next_billing_at']) && $nextBillingInfo['next_billing_at'] !== 'N/A')
                <span>Next Billing</span>

                <div>
                    <span class="theme-text">Price</span>
                    <h6>${{ number_format($order->amount, 2) }} <span class="opacity-50">/monthly</span></h6>
                </div>

                <div>
                    <span class="theme-text">Date</span>
                    <h6>{{ $nextBillingInfo['next_billing_at'] }}</h6>
                </div>
                @else
                <span>End Billing Date</span>

                <div>
                    <span class="theme-text">Price</span>
                    <h6>${{ number_format($order->amount, 2) }} <span class="opacity-50">/monthly</span></h6>
                </div>

                <div>
                    <span class="theme-text">Date</span>
                    <h6>{{ $nextBillingInfo['current_term_end'] ?? 'N/A' }}</h6>
                </div>
                @endif

                @if($order->subscription->status == 'active')
                <div class="d-flex justify-content-end">
                    <button type="button" data-bs-toggle="modal" data-bs-target="#cancel_subscription"
                        class="py-1 px-2 text-danger rounded-2 border border-danger bg-transparent">
                        Cancel Subscription
                    </button>
                </div>
                @endif
            </div>

            @if(!empty($nextBillingInfo))
            <!-- <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Subscription Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Status:</strong> {{ ucfirst($nextBillingInfo['status'] ?? 'N/A') }}</p>
                                <p><strong>Billing Period:</strong> {{ $nextBillingInfo['billing_period'] ?? 'N/A' }} {{ $nextBillingInfo['billing_period_unit'] ?? '' }}</p>
                                <p><strong>Current Term Start:</strong> {{ $nextBillingInfo['current_term_start'] }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Current Term End:</strong> {{ $nextBillingInfo['current_term_end'] }}</p>
                                <p><strong>Next Billing:</strong> {{ $nextBillingInfo['next_billing_at'] }}</p>
                            </div>
                        </div>
                    </div>
                </div> -->
            @endif
            <div class="card p-3 mt-3">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="row gy-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h5 class="mb-2">Filters</h5>
                                        <div>
                                            <button id="applyFilters" class="btn btn-primary btn-sm me-2">Filter</button>
                                            <button id="clearFilters" class="btn btn-secondary btn-sm">Clear</button>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="statusFilter" class="form-label">Invoice Status</label>
                                        <select id="statusFilter" class="form-select">
                                            <option value="">All Statuses</option>
                                            <option value="paid">Paid</option>
                                            <option value="pending">Pending</option>
                                            <option value="failed">Failed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="orderStatusFilter" class="form-label">Order Status</label>
                                        <select id="orderStatusFilter" class="form-select">
                                            <option value="">All Order Statuses</option>
                                            @foreach($statuses as $key => $status)
                                            <option value="{{ $key }}">{{ ucfirst(str_replace('_', ' ', $key)) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="startDate" class="form-label">Start Date</label>
                                        <input type="date" id="startDate" class="form-control" placeholder="Start Date">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="endDate" class="form-label">End Date</label>
                                        <input type="date" id="endDate" class="form-control" placeholder="End Date">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="priceRange" class="form-label">Price Range</label>
                                        <select id="priceRange" class="form-select">
                                            <option value="">All Prices</option>
                                            <option value="0-100">$0 - $100</option>
                                            <option value="101-500">$101 - $500</option>
                                            <option value="501-1000">$501 - $1000</option>
                                            <option value="1001+">$1000+</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card p-3 mt-3">
                <h6 class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center"
                        style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    Invoices
                </h6>

                <div class="table-responsive">
                    <table id="invoicesTable" class="display w-100">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Amount</th>
                                <th>Paid At</th>
                                <th>Status</th>
                                <!-- order-status -->
                                <th>Order Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            @push('scripts')
            <script>
                $(document).ready(function() {
                    let invoicesTable = $('#invoicesTable').DataTable({
                        processing: true,
                        serverSide: true,
                        responsive: {
                            details: {
                                display: $.fn.dataTable.Responsive.display.modal({
                                    header: function(row) {
                                        return 'Invoice Details';
                                    }
                                }),
                                renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                            }
                        },
                        ajax: {
                            url: "{{ route('customer.invoices.data') }}",
                            type: "GET",
                            data: function(d) {
                                d.order_id = "{{ $order->id }}";
                                d.status = $('#statusFilter').val();
                                d.order_status = $('#orderStatusFilter').val();
                                d.start_date = $('#startDate').val();
                                d.end_date = $('#endDate').val();
                                d.price_range = $('#priceRange').val();
                                return d;
                            },
                            error: function(xhr, error, thrown) {
                                console.error('Error:', error);
                                toastr.error('Error loading invoice data');
                            }
                        },
                        columns: [{
                                data: 'chargebee_invoice_id',
                                name: 'chargebee_invoice_id'
                            },
                            {
                                data: 'amount',
                                name: 'amount'
                            },
                            {
                                data: 'paid_at',
                                name: 'paid_at'
                            },
                            {
                                data: 'status',
                                name: 'status'
                            },
                            {
                                data: 'status_manage_by_admin',
                                name: 'status_manage_by_admin'
                            },
                            {
                                data: 'action',
                                name: 'action',
                                orderable: false,
                                searchable: false
                            }
                        ],
                        order: [
                            [0, 'desc']
                        ],
                        drawCallback: function(settings) {
                            if (settings.json && settings.json.error) {
                                toastr.error(settings.json.message || 'Error loading data');
                            }
                            $('[data-bs-toggle="tooltip"]').tooltip();
                        }
                    });

                    // Apply filters button click handler
                    $('#applyFilters').on('click', function() {
                        invoicesTable.draw();
                    });

                    // Clear filters button click handler
                    $('#clearFilters').on('click', function() {
                        $('#statusFilter').val('');
                        $('#orderStatusFilter').val('');
                        $('#startDate').val('');
                        $('#endDate').val('');
                        $('#priceRange').val('');
                        invoicesTable.draw();
                    });

                    // Date range validation
                    $('#endDate').on('change', function() {
                        const startDate = $('#startDate').val();
                        const endDate = $(this).val();

                        if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                            toastr.error('End date cannot be earlier than start date');
                            $(this).val('');
                        }
                    });

                    $('#startDate').on('change', function() {
                        const startDate = $(this).val();
                        const endDate = $('#endDate').val();

                        if (startDate && endDate && new Date(endDate) < new Date(startDate)) {
                            toastr.error('Start date cannot be later than end date');
                            $(this).val('');
                        }
                    });
                });

                $(document).ready(function() {
                    // Handle form submission
                    $('#cancel_subscription form').on('submit', function(e) {
                        e.preventDefault();

                        // Check if reason is provided
                        const reason = $('#cancellation_reason').val().trim();
                        if (!reason) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'The reason field is required.',
                                confirmButtonColor: '#3085d6'
                            });
                            return;
                        }

                        // Get form data and ensure remove_accounts is boolean
                        const formData = new FormData(this);
                        formData.set('remove_accounts', $('#remove_accounts').is(':checked'));

                        // Show confirmation dialog
                        confirmCancellation(formData);
                    });

                    function confirmCancellation(formData) {
                        Swal.fire({
                            title: 'Are you sure?',
                            text: "You won't be able to revert this!",
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, cancel it!'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                $.ajax({
                                    url: $('#cancel_subscription form').attr('action'),
                                    method: 'POST',
                                    data: Object.fromEntries(formData),
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    beforeSend: function() {
                                        // Show loading state
                                        Swal.fire({
                                            title: 'Processing...',
                                            text: 'Please wait while we cancel your subscription',
                                            allowOutsideClick: false,
                                            allowEscapeKey: false,
                                            showConfirmButton: false,
                                            didOpen: () => {
                                                Swal.showLoading();
                                            }
                                        });
                                    },
                                    success: function(response) {
                                        // Close the modal
                                        $('#cancel_subscription').modal('hide');

                                        // Show success message
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success!',
                                            text: 'Your subscription has been cancelled successfully.',
                                            confirmButtonColor: '#3085d6'
                                        }).then(() => {
                                            // Reload the page to reflect changes
                                            window.location.reload();
                                        });
                                    },
                                    error: function(xhr) {
                                        let errorMessage = 'An error occurred while cancelling your subscription.';
                                        if (xhr.responseJSON && xhr.responseJSON.message) {
                                            errorMessage = xhr.responseJSON.message;
                                        }

                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Oops...',
                                            text: errorMessage,
                                            confirmButtonColor: '#3085d6'
                                        });
                                    }
                                });
                            }
                        });
                    }
                });
            </script>
            @endpush
            @else
            <div class="card p-3">
                <div class="text-center text-muted">
                    No subscription information available
                </div>
            </div>
            @endif
        </div>

        <div class="tab-pane fade" id="tickets-tab-pane" role="tabpanel"
            aria-labelledby="tickets-tab" tabindex="0">
            <div class="card p-3">
                <div class="table-responsive">
                    <table id="myTable" class="display w-100">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Ticket #</th>
                                <th>Created At</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Order #</th>
                                <!-- <th>Subscription #</th>
                                <th>Description</th> -->
                            </tr>
                        </thead>
                        <tbody>
                            
                            @if($order->supportTickets && $order->supportTickets->count() > 0)
                            @foreach($order->supportTickets as $ticket)
                            <tr>
                                <td>{{ ucfirst($ticket->category) ?? 'N/A' }}</td>
                                <td>
                                    <a href="{{ route('customer.support.tickets.show', $ticket->id) }}" 
                                       class="text-primary text-decoration-none">
                                        {{ $ticket->ticket_number ?? 'N/A' }}
                                    </a>
                                </td>
                                <td>{{ $ticket->created_at ? 
                                    $ticket->created_at->format('d M, Y') : 'N/A' }}</td>
                                <td>
                                    <span class="badge bg-{{ $ticket->status === 'open' ? 'warning' : ($ticket->status === 'closed' ? 'success' : 'info') }}">
                                        {{ ucfirst(str_replace('_', ' ', $ticket->status)) ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $ticket->priority === 'high' ? 'danger' : ($ticket->priority === 'medium' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($ticket->priority) ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>{{ $ticket->order_id ?? 'N/A' }}</td>
                                <!-- <td>{{ $order->subscription ? $order->subscription->chargebee_subscription_id : 'N/A' }}</td>
                                <td>
                                    <span title="{{ $ticket->description }}">
                                        {{ strlen($ticket->description) > 50 ? substr($ticket->description, 0, 50) . '...' : ($ticket->description ?? 'N/A') }}
                                    </span>
                                </td> -->
                            </tr>
                            @endforeach
                            @else
                            <tr>
                                <td colspan="8" class="text-center text-muted">No tickets found for this order</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Subscription Modal -->
    <div class="modal fade" id="cancel_subscription" tabindex="-1"
        aria-labelledby="cancel_subscriptionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 class="d-flex flex-column align-items-center justify-content-start gap-2">
                        <div class="d-flex align-items-center justify-content-center"
                            style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                            <i class="fa-solid fa-cart-plus"></i>
                        </div>
                        Cancel Subscription
                    </h6>

                    <p class="note">
                        We are sad to to hear you're cancelling. Would you mind sharing the reason
                        for the cancelation? We strive to always improve and would appreciate your
                        feedback.
                    </p>

                    <form action="{{ route('customer.subscription.cancel.process') }}" method="POST">
                        @csrf
                        <input type="hidden" name="chargebee_subscription_id" value="{{ $order->chargebee_subscription_id }}">
                        <div class="mb-3">
                            <label for="cancellation_reason">Reason *</label>
                            <textarea id="cancellation_reason" name="reason" class="form-control" rows="8" required></textarea>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remove_accounts" id="remove_accounts">
                            <label class="form-check-label" for="remove_accounts">
                                I would like to have these email accounts removed and the domains
                                released immediately. I will not be using these inboxes any longer.
                            </label>
                        </div>

                        <div class="modal-footer border-0 d-flex align-items-center justify-content-between flex-nowrap">
                            <button type="button"
                                class="border boder-white text-white py-1 px-3 w-100 bg-transparent rounded-2"
                                data-bs-dismiss="modal">No, I changed my mind</button>
                            <button type="submit"
                                class="border border-danger py-1 px-3 w-100 bg-transparent text-danger rounded-2">Yes,
                                I'm sure</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection


@push('scripts')
<script>
    $(document).ready(function () {
        // Get the export URL with the order ID from Blade
        const exportUrl = "{{ url('/customer/orders/emails/' . $order->id . '/export') }}";

        $('#exportCsvBtn').on('click', function(e) {
            e.preventDefault();

            Swal.fire({
                title: 'Are you sure?',
                text: "",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Start file download by redirecting the browser
                    window.location.href = "{{ url('/customer/orders/emails/' . $order->id . '/export') }}";
                }
            });
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('.subscribe-btn').click(function() {
            const planId = @json($order->plan_id);
            console.log(planId);
            // reload to new order page
            window.location.href = `/customer/orders/new-order/${planId}`;
        });
    });
</script>
@endpush