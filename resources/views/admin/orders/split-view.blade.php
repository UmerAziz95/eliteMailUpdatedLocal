@extends('admin.layouts.app')

@section('title', 'Orders')

{{-- 
    OPTIMIZED VERSION: This view now uses the $orderPanel variable for more efficient data access
    instead of foreach loops through $order->orderPanels. This improves performance and code clarity.
--}}

@section('content')
<section class="py-3 overflow-hidden">
    <div class="d-flex align-items-center justify-content-between">
        <a href="{{ url()->previous() }}" class="d-flex align-items-center justify-content-center"
            style="height: 30px; width: 30px; border-radius: 50px; background-color: #525252c6;">
            <i class="fa-solid fa-chevron-left"></i>
        </a>
    </div>

    @php
    $defaultImage = 'https://cdn-icons-png.flaticon.com/128/300/300221.png';
    $defaultProfilePic =
    'https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600';
    @endphp

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div>
            <h5 class="mb-3">Order #{{ $orderPanel->order->id ?? 'N/A' }} - Panel {{ $orderPanel->id }}</h5>
            <h6><span class="opacity-50 fs-6">Order Date:</span> {{ $orderPanel->order->created_at ? $orderPanel->order->created_at->format('M d, Y') : 'N/A' }}</h6>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="border border-{{ $orderPanel->split_status_color ?? 'secondary' }} rounded-2 py-1 px-2 text-{{ $orderPanel->split_status_color ?? 'secondary' }} bg-transparent">
            {{ ucfirst($orderPanel->status ?? 'Pending') }}
            </div>
            <button
            style="display:none;"
            id="openStatusModal"
            class="btn btn-outline-success btn-sm py-1 px-2">
            Change Status
            </button>
        </div>
        
    </div>

    <ul class="nav nav-tabs order_view d-flex align-items-center justify-content-between" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link fs-6 px-5 active" id="configuration-tab" data-bs-toggle="tab"
                data-bs-target="#configuration-tab-pane" type="button" role="tab" aria-controls="configuration-tab-pane"
                aria-selected="true">Configuration</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fs-6 px-5" id="other-tab" data-bs-toggle="tab" data-bs-target="#other-tab-pane"
                type="button" role="tab" aria-controls="other-tab-pane" aria-selected="false">Emails</button>
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

                @if(optional($orderPanel->order->reorderInfo)->count() > 0)
                <div class="d-flex align-items-center justify-content-between">
                    @php
                        // Calculate split total inboxes based on domains and inboxes per domain
                        $inboxesPerDomain = $orderPanel->order->reorderInfo->first()->inboxes_per_domain ?? 0;
                        $splitDomainsCount = 0;
                        
                        if ($orderPanel->orderPanelSplits && $orderPanel->orderPanelSplits->count() > 0) {
                            foreach ($orderPanel->orderPanelSplits as $split) {
                                if ($split->domains) {
                                    if (is_array($split->domains)) {
                                        $splitDomainsCount += count($split->domains);
                                    } else {
                                        $splitDomainsCount += 1;
                                    }
                                }
                            }
                        }
                        
                        $splitTotalInboxes = $splitDomainsCount * $inboxesPerDomain;
                    @endphp
                    <span>Split Total Inboxes <br> ({{ $splitDomainsCount }} domains * {{ $inboxesPerDomain }} inboxes per domain) = {{ $splitTotalInboxes }}</span>
                    <span>Inboxes per domain <br> {{ $inboxesPerDomain }}</span>
                </div>
                <hr>
                <div class="d-flex flex-column">
                    <span class="opacity-50">Prefix Variants</span>
                    @php
                        // Check if new prefix_variants JSON column exists and has data
                        $prefixVariants = $orderPanel->order->reorderInfo->first()->prefix_variants ?? [];
                        $inboxesPerDomain = $orderPanel->order->reorderInfo->first()->inboxes_per_domain ?? 1;
                        
                        // If new format doesn't exist, fallback to old individual fields
                        if (empty($prefixVariants)) {
                            $prefixVariants = [];
                            if ($orderPanel->order->reorderInfo->first()->prefix_variant_1) {
                                $prefixVariants['prefix_variant_1'] = $orderPanel->order->reorderInfo->first()->prefix_variant_1;
                            }
                            if ($orderPanel->order->reorderInfo->first()->prefix_variant_2) {
                                $prefixVariants['prefix_variant_2'] = $orderPanel->order->reorderInfo->first()->prefix_variant_2;
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
                    <span class="opacity-50">Profile Picture URL</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->profile_picture_link ?? 'N/A' }}</span>
                </div>
                <div class="d-flex flex-column mt-3">
                    <span class="opacity-50">Email Persona Password</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->email_persona_password ?? 'N/A' }}</span>
                </div>
                <div class="d-flex flex-column mt-3">
                    <span class="opacity-50">Master Inbox Email</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->master_inbox_email ?? 'N/A' }}</span>
                </div>
                @else
                <div class="text-muted">No email configuration data available</div>
                @endif
            </div>

            <div class="price-display-section" style="display:none !important;">
                    @if(isset($orderPanel->order->plan) && $orderPanel->order->plan)
                        @php
                            $totalInboxes = optional($orderPanel->order->reorderInfo->first())->total_inboxes ?? 0;
                            $originalPrice = $orderPanel->order->plan->price * $totalInboxes;
                        @endphp
                        <div class="d-flex align-items-center gap-3">
                            <div>
                                <img src="{{ $defaultImage }}" width="30" alt="Product Icon">
                            </div>
                            <div>
                                <span class="opacity-50">Officially Google Workspace Inboxes</span>
                                <br>
                                <span>({{ $totalInboxes }} x ${{ number_format($orderPanel->order->plan->price, 2) }} <small>/{{ $orderPanel->order->plan->duration }}</small>)</span>
                            </div>
                        </div>
                        <h6><span class="theme-text">Original Price:</span> ${{ number_format($originalPrice, 2) }}</h6>
                        <!-- <h6><span class="theme-text">Discount:</span> 0%</h6> -->
                        <h6><span class="theme-text">Total:</span> ${{ number_format($originalPrice, 2) }} <small>/{{ $orderPanel->order->plan->duration }}</small></h6>
                    @else
                        <h6><span class="theme-text">Original Price:</span> <small>Select a plan to view price</small></h6>
                        <h6><span class="theme-text">Total:</span> <small>Select a plan to view total</small></h6>
                    @endif
                </div>
        </div>

        <div class="col-md-6">
            <div class="card p-3">
                <h6 class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center"
                        style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                        <i class="fa-solid fa-earth-europe"></i>
                    </div>
                    Domains & Configuration
                </h6>

                @if(optional($orderPanel->order->reorderInfo)->count() > 0)
                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Hosting Platform</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->hosting_platform }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Platform Login</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->platform_login }}</span>
                </div>
                <!-- platform_password -->
                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Platform Password</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->platform_password }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Domain Forwarding Destination URL</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->forwarding_url }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Sending Platform</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->sending_platform }}</span>
                </div>

                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Sequencer Login</span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->sequencer_login }}</span>
                </div>
                <div class="d-flex flex-column mb-3">
                    <span class="opacity-50">Sending plateform Sequencer - Password </span>
                    <span>{{ $orderPanel->order->reorderInfo->first()->sequencer_password }}</span>
                </div>

                <div class="d-flex flex-column">
                    <span class="opacity-50">Domains (Split Assignment)</span>
                    
                    @php
                        // Get domains from the order panel splits collection
                        $domainsArray = [];
                        
                        if ($orderPanel->orderPanelSplits && $orderPanel->orderPanelSplits->count() > 0) {
                            // Get domains from the first split (or combine all splits if multiple)
                            foreach ($orderPanel->orderPanelSplits as $split) {
                                if ($split->domains) {
                                    if (is_array($split->domains)) {
                                        $domainsArray = array_merge($domainsArray, $split->domains);
                                    } else {
                                        $domainsArray[] = $split->domains;
                                    }
                                }
                            }
                        }
                    @endphp

                    @if(count($domainsArray) > 0)
                        @foreach ($domainsArray as $domain)
                            <span class="d-block">{{ $domain }}</span>
                        @endforeach
                    @else
                        <span class="text-muted">No domains available for this order panel</span>
                    @endif
                </div>
                @if($orderPanel->order->reorderInfo->first()->hosting_platform == 'namecheap')
                <div class="d-flex flex-column mb-3 mt-3">
                    <span class="opacity-50">Backup Codes</span>
                    @php
                    $backupCodes = explode(',', $orderPanel->order->reorderInfo->first()->backup_codes);
                    @endphp
                    @foreach($backupCodes as $backupCode)
                    <span>{{ trim($backupCode) }}</span>
                    @endforeach
                </div>
                @endif
                @else
                <div class="text-muted">No configuration information available</div>
                    @endif
                </div>
            </div>
        </div>
        
    </div>               
    <div class="tab-pane fade" id="other-tab-pane" role="tabpanel" aria-labelledby="other-tab" tabindex="0">
        <div class="col-12">
            <div class="card p-3">
                <h6 class="d-flex align-items-center gap-2">
                    <div class="d-flex align-items-center justify-content-center"
                        style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                        <i class="fa-solid fa-envelope"></i>
                    </div>
                    Email Accounts for Panel #{{ $orderPanel->id }}
                </h6>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <!-- <div>
                            <button id="addBulkEmail" class="btn btn-primary me-2" data-bs-toggle="modal"
                                data-bs-target="#BulkImportModal">
                                <i class="fa-solid fa-plus me-1"></i> Import Bulk Emails
                            </button>
                            <button id="addNewBtn" class="btn btn-primary me-2">
                                <i class="fa-solid fa-plus me-1"></i> Add Email
                            </button>
                            <button id="saveAllBtn" class="btn btn-success">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save All
                            </button>
                        </div> -->
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
                                    <div class="progress-bar bg-primary" id="emailProgressBar" role="progressbar"
                                        style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                    </div>
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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    

</section>

<!-- Bulk Import Modal -->
<div class="modal fade" id="BulkImportModal" tabindex="-1" aria-labelledby="BulkImportModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="d-flex flex-column align-items-center justify-content-start gap-2">
                    <div class="d-flex align-items-center justify-content-center"
                        style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                        <i class="fa-solid fa-cart-plus"></i>
                    </div>
                    Bulk Import Emails for Panel #{{ $orderPanel->id }}
                </h6>
                <div class="row text-muted">
                    <p class="text-danger">Only .csv files are accepted.</p>
                    <p class="text-danger">The CSV file must include the following headers: <strong>name</strong>,
                        <strong>email</strong>, and <strong>password</strong>.
                    </p>
                    <p><a href="{{url('/').'/assets/samples/emails.csv'}}"><strong class="text-primary">Download
                                Sample File</strong></a></p>
                </div>

                
            </div>
        </div>
    </div>
</div>

<!-- Status Change Modal -->
<div class="modal fade" id="cancel_subscription" tabindex="-1" aria-labelledby="cancel_subscriptionLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6 class="d-flex flex-column align-items-center justify-content-start gap-2">
                    <div class="d-flex align-items-center justify-content-center"
                        style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                        <i class="fa-solid fa-cart-plus"></i>
                    </div>
                    Mark Status
                </h6>



                
            </div>
        </div>
    </div>
</div>
@endsection
@push('scripts')
<script>
    $('#openStatusModal').on('click', function() {
        // Use the order panel ID directly since we have it from the controller
        $('#order_panel_id_to_update').val('{{ $orderPanel->id }}');
        $('#cancel_subscription').modal('show');
    });

    // Handle status change to show/hide reason field
    $('.marked_status').on('change', function() {
        const selectedStatus = $(this).val();
        if (selectedStatus === 'rejected') {
            $('#reason_wrapper').removeClass('d-none');
            $('#cancellation_reason').prop('required', true);
        } else {
            $('#reason_wrapper').addClass('d-none');
            $('#cancellation_reason').prop('required', false);
        }
    });

    // Handle form submission with SweetAlert
    $('#cancelSubscriptionForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const selectedStatus = $('input[name="marked_status"]:checked').val();
        
        if (!selectedStatus) {
            Swal.fire({
                icon: 'warning',
                title: 'Status Required',
                text: 'Please select a status before submitting.'
            });
            return;
        }

        // Show loading alert
        Swal.fire({
            title: 'Updating Status...',
            text: 'Please wait while we update the panel status.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Submit the form via AJAX
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                Swal.close();
                $('#cancel_subscription').modal('hide');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: response.message || 'Panel status updated successfully.',
                    timer: 3000,
                    showConfirmButton: false
                }).then(() => {
                    // Optionally reload the page or update the UI
                    window.location.reload();
                });
            },
            error: function(xhr) {
                Swal.close();
                
                let errorMessage = 'Failed to update panel status.';
                
                if (xhr.responseJSON) {
                    errorMessage = xhr.responseJSON.message || errorMessage;
                    
                    // Show debug info if available
                    if (xhr.responseJSON.debug) {
                        console.log('Debug info:', xhr.responseJSON.debug);
                    }
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // Email Management JavaScript
    $(document).ready(function() {
        // Get the split total inboxes from order configuration
        @php
            $inboxesPerDomain = optional($orderPanel->order->reorderInfo->first())->inboxes_per_domain ?? 0;
            $splitDomainsCount = 0;
            
            if ($orderPanel->orderPanelSplits && $orderPanel->orderPanelSplits->count() > 0) {
                foreach ($orderPanel->orderPanelSplits as $split) {
                    if ($split->domains) {
                        if (is_array($split->domains)) {
                            $splitDomainsCount += count($split->domains);
                        } else {
                            $splitDomainsCount += 1;
                        }
                    }
                }
            }
            
            $splitTotalInboxes = $splitDomainsCount * $inboxesPerDomain;
        @endphp
        
        const splitTotalInboxes = {{ $splitTotalInboxes }};
        const maxEmails = splitTotalInboxes || 0; // If splitTotalInboxes is 0, allow unlimited emails
        
        // Function declarations first
        function updateRowCount(table) {
            const rowCount = table.rows().count();
            if (maxEmails > 0) {
                $('#totalRowCount').text(rowCount + "/" + splitTotalInboxes);
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
            paging: false,
            searching: false,
            info: false,
            dom: 'frtip',
            autoWidth: false,
            columnDefs: [
                { width: '30%', targets: 0 }, // Name column
                { width: '30%', targets: 1 }, // Email column
                { width: '30%', targets: 2 }, // Password column
                { width: '10%', targets: 3 }  // Action column
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
                url: '/admin/orders/panel/{{ $orderPanel->id }}/emails',
                dataSrc: function(json) {
                    return json.data || [];
                }
            },
            columns: [
                { 
                    data: 'name',
                    render: function(data, type, row) {
                        return `<input type="text" class="form-control name" value="${data || ''}" placeholder="Enter name">`;
                    }
                },
                { 
                    data: 'email',
                    render: function(data, type, row) {
                        return `<input type="email" class="form-control email" value="${data || ''}" placeholder="Enter email">`;
                    }
                },
                { 
                    data: 'password',
                    render: function(data, type, row) {
                        return `<input type="password" class="form-control password" value="${data || ''}" placeholder="Enter password">`;
                    }
                },
                {
                    data: 'id',
                    render: function(data, type, row) {
                        return `<button class="bg-transparent p-0 border-0 deleteEmailBtn" data-id="${data || ''}"><i class="fa-regular fa-trash-can text-danger"></i></button>`;
                    }
                }
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
                toastr.error(`You can only add up to ${maxEmails} email accounts as per your panel configuration.`);
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

            // Send as JSON to avoid max_input_vars limit
            $.ajax({
                url: '/admin/orders/panel/emails',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Content-Type': 'application/json'
                },
                data: JSON.stringify({
                    order_panel_id: {{ $orderPanel->id }},
                    emails: emailsToSave
                }),
                success: function(response) {
                    toastr.success('Emails saved successfully');
                    emailTable.ajax.reload();
                },
                error: function(xhr) {
                    console.error('Error response:', xhr.responseText);
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
                    url: `/admin/orders/panel/emails/${id}`,
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

        // Bulk import functionality
        $('#addBulkEmail').on('click', function() {
            $('#BulkImportModal').modal('show');
        });

        $('#BulkImportForm').on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const order_panel_id = {{ $orderPanel->id }};
            const split_total_inboxes = {{ $splitTotalInboxes }};

            Swal.fire({
                title: 'Are you sure?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // For file uploads, we still need to use FormData, not JSON
                    formData.append('order_panel_id', order_panel_id);
                    formData.append('split_total_inboxes', split_total_inboxes);

                    $.ajax({
                        url: $(this).attr('action'),
                        method: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        beforeSend: function() {
                            Swal.fire({
                                title: 'Processing...',
                                text: 'Please wait a while...',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                showConfirmButton: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });
                        },
                        success: function(response) {
                            $('#BulkImportModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'File has been imported successfully.',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                emailTable.ajax.reload();
                            });
                        },
                        error: function(xhr) {
                            let errorMessage = 'An error occurred while processing the file.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            } else if (xhr.responseText) {
                                console.error('Error response:', xhr.responseText);
                                // Check if it's a PHP max_input_vars error
                                if (xhr.responseText.includes('max_input_vars')) {
                                    errorMessage = 'File too large. Please try with a smaller file or contact administrator.';
                                }
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
        });
    });
</script>
@endpush
