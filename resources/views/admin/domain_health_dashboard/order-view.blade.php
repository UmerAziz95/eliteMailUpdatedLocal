@extends('admin.layouts.app')

@section('title', 'Orders')

@section('content')
<section class="py-3 overflow-hidden">
 

    @php
    $defaultImage = 'https://cdn-icons-png.flaticon.com/128/300/300221.png';
    $defaultProfilePic =
    'https://images.pexels.com/photos/3763188/pexels-photo-3763188.jpeg?auto=compress&cs=tinysrgb&w=600';
    @endphp

    <div class="d-flex align-items-center justify-content-between mt-3">
        <div>
            <div style="cursor: pointer; display: inline-flex; align-items: center; margin-bottom: 10px;">
                <a href="{{ url('/admin/domain_health_dashboard') }}"
                    style="display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; background-color: #2f3349; border-radius: 50%; text-decoration: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </a>
            </div>
            <h5 class="mb-3">Order #{{ $order->id ?? 'N/A' }}</h5>
            <h6><span class="opacity-50 fs-6">Order Date:</span>
                {{ $order->created_at ? $order->created_at->format('M d, Y') : 'N/A' }}</h6>
        </div>
        <div
            class="border border-{{ $order->status_manage_by_admin == 'cancelled' ? 'warning' : ' success' }} rounded-2 py-1 px-2 text-{{ $order->status_manage_by_admin == 'reject' ? ' warning' : 'success' }} bg-transparent">
            {{ ucfirst($order->status_manage_by_admin ?? '') }}
        </div>
    </div>

    <ul class="nav nav-tabs order_view d-flex align-items-center justify-content-between" id="myTab" role="tablist">
       
        <li class="nav-item" role="presentation" style="display: block;">
            <button class="nav-link fs-6 px-5" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-tab-pane"
                type="button" role="tab" aria-controls="email-tab-pane" aria-selected="false">Domains</button>
        </li>
        
    </ul>

    <div class="tab-content mt-3" id="myTabContent">
    

        <div class="tab-pane fade active show" id="email-tab-pane" role="tabpanel" aria-labelledby="email-tab" tabindex="0">
            <div class="col-12">
                <div class="card p-3">
                    <h6 class="d-flex align-items-center gap-2">
                        <div class="d-flex align-items-center justify-content-center"
                            style="height: 35px; width: 35px; border-radius: 50px; color: var(--second-primary); border: 1px solid var(--second-primary)">
                            <i class="fa-solid fa-earth-europe"></i>
                        </div>
                        Domains
                    </h6>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="email-stats d-flex align-items-center gap-3 bg- rounded p-2">
                            <div class="badge rounded-circle bg-primary p-2">
                                <i class="fa-solid fa-envelope text-white"></i>
                            </div>
                           
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="email-configuration" class="display w-100">
                            <thead>
                               <tr>
                                    <th>Domain</th>
                                    <th>Status</th>
                                    <th>Summary</th>
                                    <th>Dns Status</th>
                                    <th>Black Listed</th>
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
                                    paging: false,
                                    searching: false,
                                    info: false,
                                    dom: 'frtip',
                                    autoWidth: false,
                                    columnDefs: [
                                        { width: '33%', targets: 0 }, // Domain column
                                        { width: '33%', targets: 1 }, // Status column
                                        { width: '33%', targets: 2 }, // Summary column
                                    ],
                                    responsive: {
                                        details: {
                                            display: $.fn.dataTable.Responsive.display.modal({
                                                header: function(row) {
                                                    return 'Domain Health Details';
                                                }
                                            }),
                                            renderer: $.fn.dataTable.Responsive.renderer.tableAll()
                                        }
                                    },
                                    ajax: {
                                    url: '/admin/domains/listings/{{ $order->id }}', // update your route accordingly
                                        dataSrc: function(json) {
                                            return json.data || [];
                                        }
                                    },
                                    columns: [
                                        {
                                            data: 'domain',
                                            render: function(data, type, row) {
                                                return data || '';
                                            }
                                        },
                                        {
                                            data: 'status',
                                            render: function(data, type, row) {
                                                return data || '';
                                            }
                                        },
                                        {
                                            data: 'summary',
                                            render: function(data, type, row) {
                                                return data || '';
                                            }
                                        },
                                        {
                                            data: 'dns_status',
                                            render: function(data, type, row) {
                                                return data || '';
                                            }
                                        },
                                        {
                                            data: 'blacklist_listed',
                                            render: function(data, type, row) {
                                                return data || '';
                                            }
                                        }
                                        // Add more columns here if you need (e.g., DNS errors, blacklist info, etc)
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
                                            toastr.error(
                                                `You can only add up to ${maxEmails} email accounts as per your order configuration.`
                                                );
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
                                                emailsToSave.push({
                                                    name,
                                                    email,
                                                    password
                                                });
                                            }
                                        });

                                        if (!isValid) {
                                            toastr.error('Please fill in all required fields');
                                            return;
                                        }

                                        $.ajax({
                                            url: '/admin/domains/listings',
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
                                                            const index = parseInt(parts[
                                                            1]); // Get the row index as integer
                                                            const field = parts[
                                                            2]; // Get the field name (email, password, etc.)
                                                            const errorMsg = xhr.responseJSON.errors[key][
                                                            0]; // Get the first error message

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
                                                                    input.after(
                                                                        `<div class="invalid-feedback">${errorMsg}</div>`
                                                                        );
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
                                                url: `/admin/orders/emails/${id}`,
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

       

    </div>

    <!-- Cancel Subscription Modal -->
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
                        Cancel Subscription
                    </h6>

                    <p class="note">
                        We are sad to to hear you're cancelling. Would you mind sharing the reason
                        for the cancelation? We strive to always improve and would appreciate your
                        feedback.
                    </p>

                    {{-- <form action="{{ route('customer.subscription.cancel') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="cancellation_reason">Reason *</label>
                            <textarea id="cancellation_reason" name="reason" class="form-control" rows="8"
                                required></textarea>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remove_accounts" id="remove_accounts">
                            <label class="form-check-label" for="remove_accounts">
                                I would like to have these email accounts removed and the domains
                                released immediately. I will not be using these inboxes any longer.
                            </label>
                        </div>

                        <div
                            class="modal-footer border-0 d-flex align-items-center justify-content-between flex-nowrap">
                            <button type="button"
                                class="border boder-white text-white py-1 px-3 w-100 bg-transparent rounded-2"
                                data-bs-dismiss="modal">No, I changed my mind</button>
                            <button type="submit"
                                class="border border-danger py-1 px-3 w-100 bg-transparent text-danger rounded-2">Yes,
                                I'm sure</button>
                        </div>
                    </form> --}}
                </div>
            </div>
        </div>
    </div>
</section>
@endsection